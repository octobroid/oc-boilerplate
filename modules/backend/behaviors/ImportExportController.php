<?php namespace Backend\Behaviors;

use Lang;
use View;
use Backend;
use BackendAuth;
use Backend\Classes\ControllerBehavior;
use Backend\Behaviors\ImportExportController\TranscodeFilter;
use League\Csv\Reader as CsvReader;
use ApplicationException;
use ForbiddenException;

/**
 * ImportExportController adds features for importing and exporting data.
 *
 * This behavior is implemented in the controller like so:
 *
 *     public $implement = [
 *         \Backend\Behaviors\ImportExportController::class,
 *     ];
 *
 *     public $importExportConfig = 'config_import_export.yaml';
 *
 * The `$importExportConfig` property makes reference to the configuration
 * values as either a YAML file, located in the controller view directory,
 * or directly as a PHP array.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class ImportExportController extends ControllerBehavior
{
    use \Backend\Behaviors\ImportExportController\CanFormatCsv;
    use \Backend\Behaviors\ImportExportController\CanFormatJson;
    use \Backend\Behaviors\ImportExportController\HasImportMode;
    use \Backend\Behaviors\ImportExportController\HasExportMode;
    use \Backend\Behaviors\ImportExportController\HasListExport;

    /**
     * @inheritDoc
     */
    protected $requiredProperties = ['importExportConfig'];

    /**
     * @var array requiredConfig values that must exist when applying the primary config file.
     */
    protected $requiredConfig = [];

    /**
     * @var array actions visible in context of the controller
     */
    protected $actions = ['import', 'export', 'download'];

    /**
     * __construct the behavior
     * @param Backend\Classes\Controller $controller
     */
    public function __construct($controller)
    {
        parent::__construct($controller);

        // Build configuration
        $this->setConfig($controller->importExportConfig, $this->requiredConfig);
    }

    /**
     * beforeDisplay fires before the page is displayed and AJAX is executed.
     */
    public function beforeDisplay()
    {
        // Import form widgets
        if ($this->importUploadFormWidget = $this->makeImportUploadFormWidget()) {
            $this->importUploadFormWidget->bindToController();
        }

        if ($this->importOptionsFormWidget = $this->makeImportOptionsFormWidget()) {
            $this->importOptionsFormWidget->bindToController();
        }

        // Export form widgets
        if ($this->exportFormatFormWidget = $this->makeExportFormatFormWidget()) {
            $this->exportFormatFormWidget->bindToController();
        }

        if ($this->exportOptionsFormWidget = $this->makeExportOptionsFormWidget()) {
            $this->exportOptionsFormWidget->bindToController();
        }
    }

    /**
     * importExportMakePartial controller accessor for making partials within this behavior.
     * @param string $partial
     * @param array $params
     * @return string Partial contents
     */
    public function importExportMakePartial($partial, $params = [])
    {
        $contents = $this->controller->makePartial('import_export_'.$partial, $params + $this->vars, false);

        if (!$contents) {
            $contents = $this->makePartial($partial, $params);
        }

        return $contents;
    }

    /**
     * checkPermissionsForType checks to see if the import/export is controlled by permissions
     * and if the logged in user has permissions.
     */
    protected function checkPermissionsForType($type)
    {
        if (
            ($permissions = $this->getConfig($type.'[permissions]')) &&
            (!BackendAuth::getUser()->hasAnyAccess((array) $permissions))
        ) {
            throw new ForbiddenException;
        }
    }

    /**
     * makeOptionsFormWidgetForType
     */
    protected function makeOptionsFormWidgetForType($type)
    {
        if (!$this->getConfig($type)) {
            return null;
        }

        if ($fieldConfig = $this->getConfig($type.'[form]')) {
            $widgetConfig = $this->makeConfig($fieldConfig);
            $widgetConfig->model = $this->getModelForType($type);
            $widgetConfig->alias = $type.'OptionsForm';
            $widgetConfig->arrayName = ucfirst($type).'Options';

            return $this->makeWidget(\Backend\Widgets\Form::class, $widgetConfig);
        }

        return null;
    }

    /**
     * getModelForType
     */
    protected function getModelForType($type)
    {
        $cacheProperty = $type.'Model';

        if ($this->{$cacheProperty} !== null) {
            return $this->{$cacheProperty};
        }

        $modelClass = $this->getConfig($type.'[modelClass]');
        if (!$modelClass) {
            throw new ApplicationException(Lang::get('backend::lang.import_export.missing_model_class_error', [
                'type' => $type
            ]));
        }

        $model = new $modelClass;
        $this->controller->importExportExtendModel($model);

        return $this->{$cacheProperty} = $model;
    }

    /**
     * makeListColumns
     */
    protected function makeListColumns($config, $model)
    {
        $config = $this->makeConfig($config);
        $config->model = $model;

        $widget = $this->makeWidget(\Backend\Widgets\Lists::class, $config);
        $columns = $widget->getColumns();

        if (!isset($columns) || !is_array($columns)) {
            return null;
        }

        $result = [];
        foreach ($columns as $attribute => $column) {
            $result[$attribute] = $column->label;
        }

        return $result;
    }

    /**
     * getRedirectUrlForType
     */
    protected function getRedirectUrlForType($type = null)
    {
        $redirect = $this->getConfig($type.'[redirect]');

        if ($redirect !== null) {
            return $redirect ? Backend::url($redirect) : 'javascript:;';
        }

        return $this->controller->actionUrl($type);
    }

    /**
     * createCsvReader creates a new CSV reader with options selected by the user
     */
    protected function createCsvReader(string $path): CsvReader
    {
        $reader = CsvReader::createFromPath($path);
        $options = $this->getFormatOptionsFromPost();

        if ($options['delimiter'] !== null) {
            $reader->setDelimiter($options['delimiter']);
        }

        if ($options['enclosure'] !== null) {
            $reader->setEnclosure($options['enclosure']);
        }

        if ($options['escape'] !== null) {
            $reader->setEscape($options['escape']);
        }

        if ($options['encoding'] !== null) {
            $reader->addStreamFilter(sprintf(
                '%s%s:%s',
                TranscodeFilter::FILTER_NAME,
                strtolower($options['encoding']),
                'utf-8'
            ));
        }

        return $reader;
    }

    /**
     * getFormatOptionsFromPost returns the file format options from postback. This method
     * can be used to define presets.
     * @return array
     */
    protected function getFormatOptionsFromPost()
    {
        $fileFormat = post('file_format');

        $options = [
            'file_format' => $fileFormat,
            'delimiter' => $this->getConfig('defaultFormatOptions[delimiter]'),
            'enclosure' => $this->getConfig('defaultFormatOptions[enclosure]'),
            'escape' => $this->getConfig('defaultFormatOptions[escape]'),
            'encoding' => $this->getConfig('defaultFormatOptions[encoding]'),
        ];

        if ($fileFormat === 'csv_custom') {
            $options['delimiter'] = post('format_delimiter');
            $options['enclosure'] = post('format_enclosure');
            $options['escape'] = post('format_escape');
            $options['encoding'] = post('format_encoding');
        }

        return $options;
    }

    //
    // Overrides
    //

    /**
     * importExportGetFileName
     * @return string
     */
    public function importExportGetFileName()
    {
        return $this->exportFileName;
    }

    /**
     * importExportExtendModel
     * @param Model $model
     * @return Model
     */
    public function importExportExtendModel($model)
    {
        return $model;
    }

    /**
     * importExportExtendColumns
     */
    public function importExportExtendColumns($columns, $context = null)
    {
        return $columns;
    }
}
