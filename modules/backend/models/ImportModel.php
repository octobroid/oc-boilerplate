<?php namespace Backend\Models;

use Str;
use Lang;
use Model;
use SystemException;

/**
 * ImportModel for importing data
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class ImportModel extends Model
{
    use \Backend\Models\ImportModel\DecodesCsv;
    use \Backend\Models\ImportModel\DecodesJson;
    use \October\Rain\Database\Traits\Validation;

    /**
     * guarded attributes that aren't mass assignable.
     * @var array
     */
    protected $guarded = [];

    /**
     * attachOne relations
     */
    public $attachOne = [
        'import_file' => [\System\Models\File::class, 'public' => false],
    ];

    /**
     * @var array resultStats is the import statistics store.
     */
    protected $resultStats = [
        'updated' => 0,
        'created' => 0,
        'errors' => [],
        'warnings' => [],
        'skipped' => []
    ];

    /**
     * importData is called when data is being imported.
     * The $results array should be in the format of:
     *
     *    [
     *        'db_name1' => 'Some value',
     *        'db_name2' => 'Another value'
     *    ],
     *    [...]
     *
     */
    abstract public function importData($results, $sessionKey = null);

    /**
     * import data based on column names matching header indexes in the CSV.
     * The $matches array should be in the format of:
     *
     *    [
     *        0 => [db_name1, db_name2],
     *        1 => [db_name3],
     *        ...
     *    ]
     *
     * The key (0, 1) is the column index in the CSV and the value
     * is another array of target database column names.
     */
    public function import($matches, $options = [])
    {
        $sessionKey = $options['sessionKey'] ?? null;

        $importFilePath = $options['importFilePath'] ?? null;

        $path = $importFilePath ?: $this->getImportFilePath($sessionKey);

        $data = $this->processImportData($path, $matches, $options);

        return $this->importData($data, $sessionKey);
    }

    /**
     * importFile
     */
    public function importFile($filePath, $options = [])
    {
        $matches = $options['matches'] ?? null;

        return $this->import($matches, ['importFilePath' => $filePath] + $options);
    }

    /**
     * processImportData converts column index to database column map to an array containing
     * database column names and values pulled from the CSV file. Eg:
     *
     *   [0 => [first_name], 1 => [last_name]]
     *
     * Will return:
     *
     *   [first_name => Joe, last_name => Blogs],
     *   [first_name => Harry, last_name => Potter],
     *   [...]
     *
     * @return array
     */
    protected function processImportData($filePath, $matches, $options)
    {
        // Prepare output
        if ($this->file_format === 'json') {
            $result = $this->processImportDataAsJson($filePath, $matches, $options);
        }
        else {
            if (!$matches) {
                throw new SystemException('Importing as CSV requires column matches definition');
            }

            $result = $this->processImportDataAsCsv($filePath, $matches, $options);
        }

        return $result;
    }

    /**
     * decodeArrayValue prepares an array object for the file type.
     * @return array
     */
    protected function decodeArrayValue($value, $delimeter = '|')
    {
        if ($this->file_format === 'json') {
            return $this->decodeArrayValueForJson($value);
        }
        else {
            return $this->decodeArrayValueForCsv($value, $delimeter);
        }
    }

    /**
     * getImportFilePath returns an attached imported file local path, if available
     * @return string
     */
    public function getImportFilePath($sessionKey = null)
    {
        $file = $this
            ->import_file()
            ->withDeferred($sessionKey)
            ->orderBy('id', 'desc')
            ->first()
        ;

        if (!$file) {
            return null;
        }

        return $file->getLocalPath();
    }

    /**
     * getFormatEncodingOptions returns all available encodings values from the localization config
     * @return array
     */
    public function getFormatEncodingOptions()
    {
        $options = [
            'utf-8',
            'us-ascii',
            'iso-8859-1',
            'iso-8859-2',
            'iso-8859-3',
            'iso-8859-4',
            'iso-8859-5',
            'iso-8859-6',
            'iso-8859-7',
            'iso-8859-8',
            'iso-8859-0',
            'iso-8859-10',
            'iso-8859-11',
            'iso-8859-13',
            'iso-8859-14',
            'iso-8859-15',
            'Windows-1250',
            'Windows-1251',
            'Windows-1252'
        ];

        $translated = array_map(function ($option) {
            return Lang::get('backend::lang.import_export.encodings.'.Str::slug($option, '_'));
        }, $options);

        return array_combine($options, $translated);
    }

    //
    // Result logging
    //

    /**
     * getResultStats
     */
    public function getResultStats()
    {
        $this->resultStats['errorCount'] = count($this->resultStats['errors']);
        $this->resultStats['warningCount'] = count($this->resultStats['warnings']);
        $this->resultStats['skippedCount'] = count($this->resultStats['skipped']);

        $this->resultStats['hasMessages'] = (
            $this->resultStats['errorCount'] > 0 ||
            $this->resultStats['warningCount'] > 0 ||
            $this->resultStats['skippedCount'] > 0
        );

        return (object) $this->resultStats;
    }

    /**
     * logUpdated
     */
    protected function logUpdated()
    {
        $this->resultStats['updated']++;
    }

    /**
     * logCreated
     */
    protected function logCreated()
    {
        $this->resultStats['created']++;
    }

    /**
     * logError
     */
    protected function logError($rowIndex, $message)
    {
        $this->resultStats['errors'][$rowIndex] = $message;
    }

    /**
     * logWarning
     */
    protected function logWarning($rowIndex, $message)
    {
        $this->resultStats['warnings'][$rowIndex] = $message;
    }

    /**
     * logSkipped
     */
    protected function logSkipped($rowIndex, $message)
    {
        $this->resultStats['skipped'][$rowIndex] = $message;
    }
}
