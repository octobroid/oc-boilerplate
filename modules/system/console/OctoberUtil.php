<?php namespace System\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use ApplicationException;

/**
 * OctoberUtil is a console command for other utility commands
 *
 * This provides functionality that doesn't quite deserve its own dedicated
 * console class. It is used mostly developer tools and maintenance tasks.
 *
 * Currently supported commands:
 *
 * - purge thumbs: Deletes all thumbnail files in the uploads directory.
 * - purge orphans: Deletes files in "system_files" that do not belong to any other model.
 * - purge uploads: Deletes files in the uploads directory that do not exist in the "system_files" table.
 * - git pull: Perform "git pull" on all plugins and themes.
 * - compile assets: Compile registered Language, LESS and JS files.
 * - compile js: Compile registered JS files only.
 * - compile less: Compile registered LESS files only.
 * - compile scss: Compile registered SCSS files only.
 * - compile lang: Compile registered Language files only.
 * - set build: Pull the latest stable build number from the update gateway and set it as the current build number.
 *
 * Available patch versions:
 *
 * - patch 2.0
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class OctoberUtil extends Command
{
    use \Illuminate\Console\ConfirmableTrait;
    use \System\Console\OctoberUtilPatches;
    use \System\Console\OctoberUtilCommands;
    use \System\Console\OctoberUtilRefitLang;

    /**
     * The console command name.
     */
    protected $name = 'october:util';

    /**
     * The console command description.
     */
    protected $description = 'Utility commands for October';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $command = implode(' ', (array) $this->argument('name'));
        $method = str_replace('.', 'Point', 'util'.studly_case($command));
        $list = $this->getAvailableCommands();

        if (!$this->argument('name')) {
            $message = 'There are no commands defined in the "util" namespace.';
            if (count($list) === 1) {
                $message .= "\n\nDid you mean this?\n    ";
            }
            else {
                $message .= "\n\nDid you mean one of these?\n    ";
            }

            $message .= implode("\n    ", $list);
            throw new ApplicationException($message);
        }

        if (!method_exists($this, $method)) {
            $this->error(sprintf('Utility command "%s" does not exist!', $command));
            return;
        }

        $this->$method();
    }

    /**
     * getAvailableCommands
     */
    protected function getAvailableCommands(): array
    {
        $methods = preg_grep('/^util/', get_class_methods(get_called_class()));
        $list = array_map(function ($item) {
            if (starts_with($item, 'utilPatch')) {
                return;
            }

            return "october:".snake_case($item, " ");
        }, $methods);

        return $list;
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::IS_ARRAY, 'The utility command to perform, For more info "http://octobercms.com/docs/console/commands#october-util-command".'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['debug', null, InputOption::VALUE_NONE, 'Run the operation in debug / development mode.'],
            ['value', null, InputOption::VALUE_REQUIRED, 'Specify a generic value for the command'],
        ];
    }
}
