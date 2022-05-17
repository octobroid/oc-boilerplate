<?php namespace Cms\Traits;

/**
 * Provides data for the front-end CMS IntelliSense feature.
 */
trait EditorIntellisense
{
    protected function intellisenseLoadOctoberTags() {
        return $this->loadAndLocalizeJsonFile(__DIR__.'/editorintellisense/octobertags.json');
    }

    protected function intellisenseLoadTwigFilters() {
        return $this->loadAndLocalizeJsonFile(__DIR__.'/editorintellisense/twigfilters.json');
    }
}