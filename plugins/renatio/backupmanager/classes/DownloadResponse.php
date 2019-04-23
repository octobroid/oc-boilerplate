<?php

namespace Renatio\BackupManager\Classes;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class DownloadResponse
 * @package Renatio\BackupManager\Classes
 */
class DownloadResponse
{

    /**
     * @var
     */
    protected $backup;

    public function __construct($backup)
    {
        $this->backup = $backup;
    }

    /**
     * @return StreamedResponse
     */
    public function create()
    {
        $response = new StreamedResponse(function () {
            $this->readStream();
        });

        $response->headers->set('Content-Type', 'application/zip');

        $contentDisposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->backup->disk_name
        );
        $response->headers->set('Content-Disposition', $contentDisposition);

        return $response;
    }

    /**
     * @return void
     */
    protected function readStream()
    {
        foreach ($this->backup->disks() as $disk) {
            if ($this->backup->exists($disk)) {
                $stream = Storage::disk($disk)->getDriver()->readStream($this->backup->file_path);

                fpassthru($stream);
            }
        }
    }

}