<?php namespace Cms\Traits;

use Lang;
use File;
use Input;
use Request;
use Editor\Classes\ApiHelpers;
use October\Rain\Filesystem\Definitions as FileDefinitions;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ApplicationException;

/**
 * EditorExtensionAssetsCrud implements Assets CRUD operations for the CMS Editor Extension
 */
trait EditorExtensionAssetsCrud
{
    /**
     * command_onAssetCreateDirectory
     */
    protected function command_onAssetCreateDirectory()
    {
        $documentData = $this->getRequestDocumentData();
        $metadata = $this->getRequestMetadata();
        $this->validateRequestTheme($metadata);

        $newName = trim(ApiHelpers::assertGetKey($documentData, 'name'));
        $parent = ApiHelpers::assertGetKey($documentData, 'parent');

        $this->editorCreateDirectory($this->getAssetsPath($this->getTheme()), $newName, $parent);
    }

    /**
     * command_onAssetDelete
     */
    protected function command_onAssetDelete()
    {
        $metadata = $this->getRequestMetadata();
        $this->validateRequestTheme($metadata);

        $documentData = $this->getRequestDocumentData();
        $fileList = ApiHelpers::assertGetKey($documentData, 'files');
        ApiHelpers::assertIsArray($fileList);

        $this->editorDeleteFileOrDirectory($this->getAssetsPath($this->getTheme()), $fileList);
    }

    /**
     * command_onAssetRename
     */
    protected function command_onAssetRename()
    {
        $metadata = $this->getRequestMetadata();
        $documentData = $this->getRequestDocumentData();
        $this->validateRequestTheme($metadata);

        $newName = trim(ApiHelpers::assertGetKey($documentData, 'name'));
        $originalPath = trim(ApiHelpers::assertGetKey($documentData, 'originalPath'));
        $assetExtensions = FileDefinitions::get('asset_extensions');

        $this->editorRenameFileOrDirectory($this->getAssetsPath($this->getTheme()), $newName, $originalPath, $assetExtensions);
    }

    /**
     * command_onAssetMove
     */
    protected function command_onAssetMove()
    {
        $metadata = $this->getRequestMetadata();
        $documentData = $this->getRequestDocumentData();
        $this->validateRequestTheme($metadata);

        $selectedList = ApiHelpers::assertGetKey($documentData, 'source');
        $destinationDir = ApiHelpers::assertGetKey($documentData, 'destination');
        $this->editorMoveFilesOrDirectories($this->getAssetsPath($this->getTheme()), $selectedList, $destinationDir);
    }

    /**
     * command_onAssetUpload
     */
    protected function command_onAssetUpload()
    {
        $metadata = [
            'theme' => Request::input('theme')
        ];
        $this->validateRequestTheme($metadata);

        $assetExtensions = FileDefinitions::get('asset_extensions');
        $this->editorUploadFiles($this->getAssetsPath($this->getTheme()), $assetExtensions);
    }

    /**
     * getAssetFullPath returns the full path for the current theme
     * @param $path string
     */
    protected function getAssetFullPath($path): string
    {
        return $this->getAssetsPath($this->getTheme()).'/'.ltrim($path, '/');
    }
}
