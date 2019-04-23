<?php

namespace Renatio\BackupManager\Classes;

/**
 * Class SystemRequirements
 * @package Renatio\BackupManager\Classes
 */
class SystemRequirements
{

    /**
     * @var array
     */
    protected $issues = [];

    /**
     * @return mixed
     */
    public function check()
    {
        $this->checkMemoryLimit();

        return $this->issues;
    }

    /**
     * @return void
     */
    protected function checkMemoryLimit()
    {
        if (ini_get('memory_limit') < 128) {
            $this->issues[] = trans(
                'renatio.backupmanager::lang.issue.memory_limit',
                ['limit' => ini_get('memory_limit')]
            );
        }
    }

}