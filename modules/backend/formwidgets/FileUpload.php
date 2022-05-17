<?php namespace Backend\FormWidgets;

use Input;
use Response;
use Validator;
use Backend\Widgets\Form;
use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use System\Models\File as FileModel;
use October\Rain\Filesystem\Definitions as FileDefinitions;
use ApplicationException;
use ValidationException;
use Exception;

/**
 * FileUpload renders a form file uploader field.
 *
 * Supported options:
 *
  *    file:
 *        label: Some file
 *        type: fileupload
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class FileUpload extends FormWidgetBase
{
    use \Backend\Traits\FormModelSaver;
    use \Backend\Traits\FormModelWidget;

    //
    // Configurable properties
    //

    /**
     * @var int imageWidth for preview
     */
    public $imageWidth = 190;

    /**
     * @var int imageHeight for preview
     */
    public $imageHeight = 190;

    /**
     * @var mixed fileTypes accpetd
     */
    public $fileTypes = false;

    /**
     * @var mixed mimeTypes accepted
     */
    public $mimeTypes = false;

    /**
     * @var mixed maxFilesize allowed (MB)
     */
    public $maxFilesize;

    /**
     * @var mixed maxFiles allowed
     */
    public $maxFiles;

    /**
     * @var string Defines a mount point for the editor toolbar.
     * Must include a module name that exports the Vue application and a state element name.
     * Format: module.name::stateElementName
     * Only works in Vue applications and form document layouts.
     */
    public $externalToolbarAppState = null;

    /**
     * @var string Defines an event bus for an external toolbar.
     * Must include a module name that exports the Vue application and a state element name.
     * Format: module.name::eventBus
     * Only works in Vue applications and form document layouts.
     */
    public $externalToolbarEventBus = null;

    /**
     * @var array thumbOptions used for generating thumbnails
     */
    public $thumbOptions = [
        'mode' => 'crop',
        'extension' => 'auto'
    ];

    /**
     * @var boolean useCaption allows the user to set a caption
     */
    public $useCaption = true;

    /**
     * @var boolean attachOnUpload automatically attaches the uploaded file on upload
     * if the parent record exists instead of using deferred binding to attach on save
     * of the parent record. Defaults to false.
     */
    public $attachOnUpload = false;

    //
    // Object properties
    //

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'fileupload';

    /**
     * @var Backend\Widgets\Form configFormWidget is the embedded form for modifying the
     * properties of the selected file.
     */
    protected $configFormWidget;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->maxFilesize = $this->getUploadMaxFilesize();

        $this->fillFromConfig([
            'imageWidth',
            'imageHeight',
            'fileTypes',
            'maxFilesize',
            'maxFiles',
            'mimeTypes',
            'thumbOptions',
            'useCaption',
            'attachOnUpload',
            'externalToolbarAppState',
            'externalToolbarEventBus'
        ]);

        if ($this->formField->disabled) {
            $this->previewMode = true;
        }

        $this->getConfigFormWidget();
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('fileupload');
    }

    /**
     * prepareVars for the view data
     */
    protected function prepareVars()
    {
        if ($this->formField->disabled) {
            $this->previewMode = true;
        }

        if ($this->previewMode) {
            $this->useCaption = false;
        }

        $maxPhpSetting = $this->getUploadMaxFilesize();
        if ($maxPhpSetting && $this->maxFilesize > $maxPhpSetting) {
            throw new ApplicationException('Maximum allowed size for uploaded files: ' . $maxPhpSetting);
        }

        $this->vars['size'] = $this->formField->size;
        $this->vars['fileList'] = $fileList = $this->getFileList();
        $this->vars['singleFile'] = $fileList->first();
        $this->vars['displayMode'] = $this->getDisplayMode();
        $this->vars['emptyIcon'] = $this->getConfig('emptyIcon', 'icon-upload');
        $this->vars['imageHeight'] = $this->imageHeight;
        $this->vars['imageWidth'] = $this->imageWidth;
        $this->vars['acceptedFileTypes'] = $this->getAcceptedFileTypes(true);
        $this->vars['maxFilesize'] = $this->maxFilesize;
        $this->vars['maxFiles'] = $this->maxFiles;
        $this->vars['cssDimensions'] = $this->getCssDimensions();
        $this->vars['useCaption'] = $this->useCaption;
        $this->vars['externalToolbarAppState'] = $this->externalToolbarAppState;
        $this->vars['externalToolbarEventBus'] = $this->externalToolbarEventBus;
    }

    /**
     * getFileRecord for this request, returns false if none available
     * @return System\Models\File|false
     */
    protected function getFileRecord()
    {
        $record = false;

        if (!empty(post('file_id'))) {
            $record = $this->getRelationModel()::find(post('file_id')) ?: false;
        }

        return $record;
    }

    /**
     * getConfigFormWidget for the instantiated Form widget
     */
    public function getConfigFormWidget()
    {
        if ($this->configFormWidget) {
            return $this->configFormWidget;
        }

        $config = $this->makeConfig('~/modules/system/models/file/fields.yaml');
        $config->model = $this->getFileRecord() ?: $this->getRelationModel();
        $config->alias = $this->alias . $this->defaultAlias;
        $config->arrayName = $this->getFieldName();

        $widget = $this->makeWidget(Form::class, $config);
        $widget->bindToController();

        return $this->configFormWidget = $widget;
    }

    /**
     * getFileList returns a list of associated files
     */
    protected function getFileList()
    {
        $list = $this
            ->getRelationObject()
            ->withDeferred($this->getSessionKey())
            ->orderBy('sort_order')
            ->get()
        ;

        /*
         * Decorate each file with thumb and custom download path
         */
        $list->each(function ($file) {
            $this->decorateFileAttributes($file);
        });

        return $list;
    }

    /**
     * getDisplayMode for the file upload. Eg: file-multi, image-single, etc
     * @return string
     */
    protected function getDisplayMode()
    {
        $mode = $this->getConfig('mode', 'image');

        if (str_contains($mode, '-')) {
            return $mode;
        }

        $relationType = $this->getRelationType();
        $mode .= ($relationType == 'attachMany' || $relationType == 'morphMany') ? '-multi' : '-single';

        return $mode;
    }

    /**
     * getCssDimensions returns the CSS dimensions for the uploaded image,
     * uses auto where no dimension is provided.
     */
    protected function getCssDimensions(): string
    {
        if (!$this->imageWidth && !$this->imageHeight) {
            return '';
        }

        $cssDimensions = '';

        if ($this->imageWidth && !$this->imageHeight) {
            $cssDimensions .= 'width: '.$this->imageWidth.'px;';
        }

        if ($this->imageHeight && !$this->imageWidth) {
            $cssDimensions .= 'height: '.$this->imageHeight.'px;';
        }

        return $cssDimensions;
    }

    /**
     * getAcceptedFileTypes returns the specified accepted file types, or the
     * default based on the mode. Image mode will return:
     * - jpg,jpeg,bmp,png,gif,svg
     * @return string
     */
    public function getAcceptedFileTypes($includeDot = false)
    {
        $types = $this->fileTypes;

        if ($types === false) {
            $definitionCode = starts_with($this->getDisplayMode(), 'image')
                ? 'image_extensions'
                : 'default_extensions';

            $types = implode(',', FileDefinitions::get($definitionCode));
        }

        if (!$types || $types === '*') {
            return null;
        }

        if (!is_array($types)) {
            $types = explode(',', $types);
        }

        $types = array_map(function ($value) use ($includeDot) {
            $value = trim($value);

            if (substr($value, 0, 1) == '.') {
                $value = substr($value, 1);
            }

            if ($includeDot) {
                $value = '.'.$value;
            }

            return $value;
        }, $types);

        return implode(',', $types);
    }

    /**
     * onRemoveAttachment removes a file attachment
     */
    public function onRemoveAttachment()
    {
        $fileModel = $this->getRelationModel();
        if (($fileId = post('file_id')) && ($file = $fileModel::find($fileId))) {
            $this->getRelationObject()->remove($file, $this->getSessionKey());
        }
    }

    /**
     * onSortAttachments sorts file attachments
     */
    public function onSortAttachments()
    {
        if ($sortData = post('sortOrder')) {
            $ids = array_keys($sortData);
            $orders = array_values($sortData);

            $fileModel = $this->getRelationModel();
            $fileModel->setSortableOrder($ids, $orders);
        }
    }

    /**
     * onLoadAttachmentConfig loads the configuration form for an attachment,
     * allowing title and description to be set
     */
    public function onLoadAttachmentConfig()
    {
        $file = $this->getFileRecord();
        if (!$file) {
            throw new ApplicationException('Unable to find file, it may no longer exist');
        }

        $file = $this->decorateFileAttributes($file);

        $this->vars['file'] = $file;
        $this->vars['displayMode'] = $this->getDisplayMode();
        $this->vars['cssDimensions'] = $this->getCssDimensions();

        return $this->makePartial('config_form');
    }

    /**
     * onSaveAttachmentConfig commits the changes of the attachment configuration form
     */
    public function onSaveAttachmentConfig()
    {
        try {
            $formWidget = $this->getConfigFormWidget();

            $file = $formWidget->model;
            if (!$file) {
                throw new ApplicationException('Unable to find file, it may no longer exist');
            }

            $this->performSaveOnModel($file, $formWidget->getSaveData(), $formWidget->getSessionKey());

            return [
                'displayName' => $file->title ?: $file->file_name,
                'description' => trim($file->description)
            ];
        }
        catch (Exception $ex) {
            return json_encode(['error' => $ex->getMessage()]);
        }
    }

    /**
     * @inheritDoc
     */
    protected function loadAssets()
    {
        $this->addCss('css/fileupload.css', 'core');
        $this->addJs('js/fileupload.js', 'core');
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return FormField::NO_SAVE_DATA;
    }

    /**
     * onUpload handler for the server-side processing of uploaded files
     */
    public function onUpload()
    {
        try {
            if (!Input::hasFile('file_data')) {
                throw new ApplicationException('File missing from request');
            }

            $fileModel = $this->getRelationModel();
            $uploadedFile = Input::file('file_data');

            $validationRules = ['max:'.($this->maxFilesize * 1024)];
            if ($fileTypes = $this->getAcceptedFileTypes()) {
                $validationRules[] = 'extensions:'.$fileTypes;
            }

            if ($this->mimeTypes) {
                $validationRules[] = 'mimes:'.$this->mimeTypes;
            }

            $validation = Validator::make(
                ['file_data' => $uploadedFile],
                ['file_data' => $validationRules]
            );

            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            if (!$uploadedFile->isValid()) {
                throw new ApplicationException('File is not valid');
            }

            $fileRelation = $this->getRelationObject();

            $file = $fileModel;
            $file->data = $uploadedFile;
            $file->is_public = $fileRelation->isPublic();
            $file->save();

            /**
             * Attach directly to the parent model if it exists and attachOnUpload has been set to true
             * else attach via deferred binding
             */
            $parent = $fileRelation->getParent();
            if ($this->attachOnUpload && $parent && $parent->exists) {
                $fileRelation->add($file);
            }
            else {
                $fileRelation->add($file, $this->getSessionKey());
            }

            $file = $this->decorateFileAttributes($file);

            $result = [
                'id' => $file->id,
                'thumb' => $file->thumbUrl,
                'path' => $file->pathUrl
            ];

            $response = Response::make($result, 200);
        }
        catch (Exception $ex) {
            $response = Response::make($ex->getMessage(), 400);
        }

        return $response;
    }

    /**
     * decorateFileAttributes adds the bespoke attributes used
     * internally by this widget. Added attributes are:
     * - thumbUrl
     * - pathUrl
     * @return System\Models\File
     */
    protected function decorateFileAttributes($file)
    {
        $path = $thumb = $file->getPath();

        if ($this->imageWidth || $this->imageHeight) {
            $thumb = $file->getThumb($this->imageWidth, $this->imageHeight, $this->thumbOptions);
        }

        $file->pathUrl = $path;
        $file->thumbUrl = $thumb;

        return $file;
    }

    /**
     * getUploadMaxFilesize returns max upload filesize in MB
     */
    protected function getUploadMaxFilesize(): float
    {
        return FileModel::getMaxFilesize() / 1024;
    }
}
