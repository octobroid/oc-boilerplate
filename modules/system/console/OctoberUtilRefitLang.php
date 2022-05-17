<?php namespace System\Console;

use File;
use Exception;

/**
 * OctoberUtilRefitLang is a dedicated class for the refit lang util command
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
trait OctoberUtilRefitLang
{
    /**
     * utilRefitLang
     */
    protected function utilRefitLang()
    {
        $input = $this->option('value');
        if (!$input) {
            $this->comment('Missing language key.');
            $input = $this->ask('Enter language key');
        }

        // Ex[author.plugin::lang.some.key]
        $parts = explode('::', $input);

        // Ex[author.plugin]
        $namespace = $parts[0];

        // Ex[lang.some.key]
        $langKey = $parts[1];
        $langParts = explode('.', $langKey);

        // Ex[lang.php]
        $fileName = array_shift($langParts) . '.php';

        // Ex[some.key]
        $arrPath = implode('.', $langParts);

        // Ex[/path/to/plugins/author/plugin]
        if (strpos($namespace, '.') !== false) {
            $fileDir = plugins_path(str_replace('.', '/', $namespace));
        }
        else {
            $fileDir = base_path('modules/'.$namespace);
        }

        // Ex[cs,de,en]
        $dirs = array_map(function($path) {
            return basename($path);
        }, File::directories($fileDir . '/lang'));

        // Rewrite the language key for each lang
        foreach ($dirs as $lang) {
            $this->refitLangKeyRewrite($fileDir, $lang, $arrPath);
        }

        // Delete the language key for each lang
        foreach ($dirs as $lang) {
            $this->refitLangKeyDelete($fileDir, $lang, $arrPath);
        }
    }

    /**
     * refitLangKey
     */
    protected function refitLangKeyRewrite(string $basePath, string $lang, string $key, string $fileName = null)
    {
        if (!$fileName) {
            $fileName = 'lang.php';
        }

        $englishPath = "{$basePath}/lang/en/{$fileName}";
        $englishArr = include($englishPath);

        $legacyPath = "{$basePath}/lang/{$lang}/{$fileName}";
        $legacyArr = include($legacyPath);

        $newPath = "{$basePath}/lang/{$lang}.json";
        $newArr = [];

        if (file_exists($newPath)) {
            $newArr = json_decode(file_get_contents($newPath), true);
        }

        $english = array_get($englishArr, $key);
        if (!$english) {
            throw new Exception("Missing key for english [{$key}] in [{$englishPath}]");
        }

        $translated = array_get($legacyArr, $key);
        if (!$translated) {
            $this->comment("Missing key for english [{$key}] in [{$legacyPath}]");
            return;
        }

        $newArr[$english] = $translated;

        File::put($newPath, json_encode($newArr, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

        $this->comment("[{$lang}] → {$english}:{$translated}");

        if ($lang === 'en') {
            $this->comment(PHP_EOL);
            if (strpos($english, "'") !== false) {
                $this->comment("<?= e(__(\"{$english}\")) ?>");
            }
            else {
                $this->comment("<?= e(__('{$english}')) ?>");
            }
        }
    }

    /**
     * refitLangKeyDelete
     */
    protected function refitLangKeyDelete(string $basePath, string $lang, string $key, string $fileName = null)
    {
        if (!$fileName) {
            $fileName = 'lang.php';
        }

        $legacyPath = "{$basePath}/lang/{$lang}/{$fileName}";

        $legacyArr = include($legacyPath);

        array_forget($legacyArr, $key);

        File::put($legacyPath, '<?php return '.$this->refitVarExportShort($legacyArr, true).';');
    }

    /**
     * refitVarExportShort
     */
    protected function refitVarExportShort($data, $return = true)
    {
        $dump = var_export($data, true);

        // Array starts
        $dump = preg_replace('#(?:\A|\n)([ ]*)array \(#i', '[', $dump);

        // Array ends
        $dump = preg_replace('#\n([ ]*)\),#', "\n$1],", $dump);

        // Array empties
        $dump = preg_replace('#=> \[\n\s+\],\n#', "=> [],\n", $dump);

        // Object states
        if (gettype($data) == 'object') {
            $dump = str_replace('__set_state(array(', '__set_state([', $dump);
            $dump = preg_replace('#\)\)$#', "])", $dump);
        }
        else {
            $dump = preg_replace('#\)$#', "]", $dump);
        }

        // 2 char to 4 char indent
        $dump = str_replace('  ', '    ', $dump);

        if ($return === true) {
            return $dump;
        }
        else {
            echo $dump;
        }
    }
}
