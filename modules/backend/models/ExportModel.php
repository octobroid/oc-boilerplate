<?php namespace Backend\Models;

use File;
use Lang;
use Model;
use Response;
use ApplicationException;

/**
 * ExportModel used for exporting data
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class ExportModel extends Model
{
    use \Backend\Models\ExportModel\EncodesCsv;
    use \Backend\Models\ExportModel\EncodesJson;

    /**
     * exportData is called when data is being exported.
     * The return value should be an array in the format of:
     *
     *   [
     *       'db_name1' => 'Some attribute value',
     *       'db_name2' => 'Another attribute value'
     *   ],
     *   [...]
     *
     */
    abstract public function exportData($columns, $sessionKey = null);

    /**
     * export data based on column names and labels.
     * The $columns array should be in the format of:
     *
     *   [
     *       'db_name1' => 'Column label',
     *       'db_name2' => 'Another label',
     *       ...
     *   ]
     *
     */
    public function export($columns, $options)
    {
        $sessionKey = array_get($options, 'sessionKey');

        $data = $this->exportData(array_keys($columns), $sessionKey);

        return $this->processExportData($columns, $data, $options);
    }

    /**
     * download a previously compiled export file.
     * @return Response
     */
    public function download($name, $outputName = null)
    {
        if (!preg_match('/^oc[0-9a-z]*$/i', $name)) {
            throw new ApplicationException(Lang::get('backend::lang.import_export.file_not_found_error'));
        }

        $csvPath = temp_path($name);
        if (!file_exists($csvPath)) {
            throw new ApplicationException(Lang::get('backend::lang.import_export.file_not_found_error'));
        }

        $contentType = ends_with($name, 'xjson')
            ? 'application/json'
            : 'text/csv';

        return Response::download($csvPath, $outputName, [
            'Content-Type' => $contentType,
        ])->deleteFileAfterSend(true);
    }

    /**
     * processExportData converts a data collection to a CSV file.
     */
    protected function processExportData($columns, $results, $options)
    {
        // Validate
        if (!$results) {
            throw new ApplicationException(Lang::get('backend::lang.import_export.empty_error'));
        }

        // Extend columns
        $columns = $this->exportExtendColumns($columns);

        // Save for download
        $fileName = uniqid('oc');

        // Prepare output
        if ($this->file_format === 'json') {
            $fileName .= 'xjson';
            $output = $this->processExportDataAsJson($columns, $results, $options);
        }
        else {
            $fileName .= 'xcsv';
            $output = $this->processExportDataAsCsv($columns, $results, $options);
        }

        File::put(temp_path($fileName), $output);

        return $fileName;
    }

    /**
     * exportExtendColumns used to override column definitions at export time.
     */
    protected function exportExtendColumns($columns)
    {
        return $columns;
    }

    /**
     * getColumnHeaders extracts the headers from the column definitions.
     */
    protected function getColumnHeaders($columns)
    {
        $headers = [];

        foreach ($columns as $column => $label) {
            $headers[] = Lang::get($label);
        }

        return $headers;
    }

    /**
     * matchDataToColumns ensures the correct order of the column data.
     */
    protected function matchDataToColumns($data, $columns)
    {
        $results = [];

        foreach ($columns as $column => $label) {
            $results[] = array_get($data, $column);
        }

        return $results;
    }

    /**
     * encodeArrayValue prepares an array object for the file type.
     * @return mixed
     */
    protected function encodeArrayValue($data, $delimeter = '|')
    {
        if (!is_array($data)) {
            return '';
        }

        if ($this->file_format === 'json') {
            return $this->encodeArrayValueForJson($data);
        }
        else {
            return $this->encodeArrayValueForCsv($data, $delimeter);
        }
    }
}
