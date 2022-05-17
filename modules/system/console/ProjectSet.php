<?php namespace System\Console;

use System\Models\Parameter;
use System\Classes\UpdateManager;
use October\Rain\Process\Composer as ComposerProcess;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Console\Command;
use Exception;

/**
 * ProjectSet sets the project license key.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class ProjectSet extends Command
{
     /**
     * @var string name of console command
     */
    protected $name = 'project:set';

    /**
     * @var string description of the console command
     */
    protected $description = 'Sets the project license key.';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        try {
            $licenseKey = (string) $this->argument('key');

            if (!$licenseKey) {
                $this->comment('Enter a valid License Key to proceed.');
                $licenseKey = trim($this->ask('License Key'));
            }

            $result = UpdateManager::instance()->requestProjectDetails($licenseKey);

            // Check status
            $isActive = $result['is_active'] ?? false;
            if (!$isActive) {
                $this->output->error('License is unpaid or has expired. Please visit octobercms.com to obtain a license.');
                return;
            }

            // Save project locally
            Parameter::set([
                'system::project.id' => $result['id'],
                'system::project.key' => $result['project_id'],
                'system::project.name' => $result['name'],
                'system::project.owner' => $result['owner'],
                'system::project.is_active' => $result['is_active']
            ]);

            // Save authentication token
            $projectKey = $result['project_id'] ?? null;
            $projectEmail = $result['email'] ?? null;
            $this->setComposerAuth($projectEmail, $projectKey);

            // Add October CMS gateway as a composer repo
            $composer = new ComposerProcess;
            $composer->addRepository('octobercms', 'composer', $this->getComposerUrl());

            // Thank the user
            $this->output->success('Thanks for being a customer of October CMS!');
        }
        catch (Exception $e) {
            $this->output->error($e->getMessage());
        }
    }

    /**
     * getArguments get the console command arguments
     */
    protected function getArguments()
    {
        return [
            ['key', InputArgument::OPTIONAL, 'The License Key'],
        ];
    }

    /**
     * setComposerAuth configures authentication for composer and October CMS
     */
    protected function setComposerAuth($email, $projectKey)
    {
        $composerUrl = $this->getComposerUrl(false);

        $this->injectJsonToFile(base_path('auth.json'), [
            'http-basic' => [
                $composerUrl => [
                    'username' => $email,
                    'password' => $projectKey
                ]
            ]
        ]);
    }

    /**
     * getComposerUrl returns the endpoint for composer
     */
    protected function getComposerUrl(bool $withProtocol = true): string
    {
        return UpdateManager::instance()->getComposerUrl($withProtocol);
    }

    /**
     * injectJsonToFile merges a JSON array in to an existing JSON file.
     * Merging is useful for preserving array values.
     */
    protected function injectJsonToFile(string $filename, array $jsonArr, bool $merge = false): void
    {
        $contentsArr = file_exists($filename)
            ? json_decode(file_get_contents($filename), true)
            : [];

        $newArr = $merge
            ? array_merge_recursive($contentsArr, $jsonArr)
            : $this->mergeRecursive($contentsArr, $jsonArr);

        $content = json_encode($newArr, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

        file_put_contents($filename, $content);
    }

    /**
     * mergeRecursive substitues the native PHP array_merge_recursive to be
     * more config friendly. Scalar values are replaced instead of being
     * merged in to their own new array.
     */
    protected function mergeRecursive(array $array1, $array2)
    {
        if ($array2 && is_array($array2)) {
            foreach ($array2 as $key => $val2) {
                if (
                    is_array($val2) &&
                    (($val1 = isset($array1[$key]) ? $array1[$key] : null) !== null) &&
                    is_array($val1)
                ) {
                    $array1[$key] = $this->mergeRecursive($val1, $val2);
                }
                else {
                    $array1[$key] = $val2;
                }
            }
        }

        return $array1;
    }
}
