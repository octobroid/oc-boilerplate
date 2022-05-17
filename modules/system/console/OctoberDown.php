<?php namespace System\Console;

use Illuminate\Console\Command;

/**
 * OctoberDown is deprecated
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 * @deprecated
 * @see System\Console\OctoberMigrate
 */
class OctoberDown extends Command
{
    /**
     * @var string name of console command
     */
    protected $name = 'october:down';

    /**
     * @var string description of the console command
     */
    protected $description = '[Deprecated] Destroys all database tables for October and all plugins.';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $this->error('Command october:down is deprecated, please use october:migrate --rollback instead');
    }
}
