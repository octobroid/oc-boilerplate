<?php namespace System\Twig;

use Twig\Environment as TwigEnvironment;
use Illuminate\Contracts\View\Engine as EngineInterface;

/**
 * View engine used by the system, used for converting .htm files to twig.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class Engine implements EngineInterface
{
    /**
     * @var TwigEnvironment
     */
    protected $environment;

    /**
     * @var System\Twig\Loader
     */
    protected $loader;

    /**
     * Constructor
     */
    public function __construct(TwigEnvironment $environment)
    {
        $this->environment = $environment;

        $this->loader = $this->environment->getLoader();
    }

    public function get($path, array $vars = [])
    {
        $this->loader->addCacheItem($path);

        $template = $this->environment->loadTemplate($path);

        return $template->render($vars);
    }
}
