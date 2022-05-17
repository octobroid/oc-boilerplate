<?php namespace System\Twig;

use View;
use File;
use Twig\Source as TwigSource;
use Twig\Loader\LoaderInterface as TwigLoaderInterface;
use Exception;

/**
 * This class implements a Twig template loader for the core system and backend.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class Loader implements TwigLoaderInterface
{
    /**
     * @var array Cache
     */
    protected $cache = [];

    /**
     * Gets the path of a view file
     * @param  string $name
     * @return string
     */
    protected function findTemplate(string $name): string
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        return $this->cache[$name] = View::getFinder()->find($name);
    }

    /**
     * addCache adds a specific item to cache.
     */
    public function addCacheItem(string $name): void
    {
        $this->cache[$name] = $name;
    }

    /**
     * Returns the Twig content string.
     * This step is cached internally by Twig.
     */
    public function getSourceContext($name)
    {
        return new TwigSource(File::get($this->findTemplate($name)), $name);
    }

    /**
     * Returns the Twig cache key.
     */
    public function getCacheKey($name)
    {
        return $this->findTemplate($name);
    }

    /**
     * Determines if the content is fresh.
     */
    public function isFresh($name, $time)
    {
        return File::lastModified($this->findTemplate($name)) <= $time;
    }

    /**
     * Returns the file name of the loaded template.
     */
    public function getFilename($name)
    {
        return $this->findTemplate($name);
    }

    /**
     * Checks that the template exists.
     */
    public function exists($name)
    {
        try {
            $this->findTemplate($name);
            return true;
        }
        catch (Exception $exception) {
            return false;
        }
    }
}
