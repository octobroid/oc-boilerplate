<?php namespace System\Console;

use Illuminate\Console\Command;

/**
 * OctoberUp is deprecated
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 * @deprecated
 * @see System\Console\OctoberMigrate
 */
class OctoberUp extends Command
{
    /**
     * @var string name of console command
     */
    protected $name = 'october:up';

    /**
     * @var string description of the console command
     */
    protected $description = '[Deprecated] Builds database tables for October and all plugins.';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $this->error('Command october:up is deprecated, please use october:migrate instead');
    }
}
