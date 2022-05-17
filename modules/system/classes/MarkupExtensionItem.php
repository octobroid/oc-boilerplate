<?php namespace System\Classes;

/**
 * MarkupExtensionItem class
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class MarkupExtensionItem
{
    /**
     * {{ 'a'|filter }}
     */
    const TYPE_FILTER = 'filter';

    /**
     * {{ function() }}
     */
    const TYPE_FUNCTION = 'function';

    /**
     * {% token %}
     */
    const TYPE_TOKEN_PARSER = 'token';

    /**
     * @var string name
     */
    public $name;

    /**
     * @var string type
     */
    public $type;

    /**
     * @var callable callback
     */
    public $callback;

    /**
     * @var bool escapeOutput
     */
    public $escapeOutput;

    /**
     * useConfig
     */
    public function useConfig(array $data): MarkupExtensionItem
    {
        [$callback, $escapeOutput] = $this->parseDefinition($data['definition'] ?? null);

        $this->name = $data['name'] ?? $this->name;
        $this->type = $data['type'] ?? $this->type;
        $this->callback = $data['callback'] ?? $callback;
        $this->escapeOutput = $data['escapeOutput'] ?? $escapeOutput;

        return $this;
    }

    /**
     * parseDefinition will check if a callable definition contains output escaping
     */
    protected function parseDefinition($definition): array
    {
        $escapeOutput = false;
        $callback = $definition;

        // If the last item in the array is a boolean, it defines output escaping
        if (
            is_array($callback) &&
            count($callback) > 1 &&
            is_bool($callback[array_key_last($callback)])
        ) {
            $escapeOutput = array_pop($callback);
        }

        // Convert an array with 1 item to a string, to make it callable
        // for example, ['count'] is not callable
        if (is_array($callback) && count($callback) <= 1) {
            $callback = implode("", $callback);
        }

        return [$callback, $escapeOutput];
    }

    /**
     * isWildCallable will test if the callback uses a wildcard,
     * for example, str_* can route to Str::*
     */
    public function isWildCallable(): bool
    {
        $callable = $this->callback;
        $isWild = false;

        if (is_string($callable) && strpos($callable, '*') !== false) {
            $isWild = true;
        }

        if (is_array($callable)) {
            if (is_string($callable[0]) && strpos($callable[0], '*') !== false) {
                $isWild = true;
            }

            if (!empty($callable[1]) && strpos($callable[1], '*') !== false) {
                $isWild = true;
            }
        }

        return $isWild;
    }

    /**
     * getWildCallback replaces wildcard with a real callable function
     */
    public function getWildCallback(string $name)
    {
        $callable = $this->callback;
        $wildCallback = null;

        if (is_string($callable) && strpos($callable, '*') !== false) {
            $wildCallback = str_replace('*', $name, $callable);
        }

        if (is_array($callable)) {
            if (is_string($callable[0]) && strpos($callable[0], '*') !== false) {
                $wildCallback = $callable;
                $wildCallback[0] = str_replace('*', $name, $callable[0]);
            }

            if (!empty($callable[1]) && strpos($callable[1], '*') !== false) {
                $wildCallback = $wildCallback ?: $callable;
                $wildCallback[1] = str_replace('*', $name, $callable[1]);
            }
        }

        return $wildCallback;
    }

    /**
     * getTwigOptions returns options passed to the Twig definition
     */
    public function getTwigOptions(): array
    {
        return $this->escapeOutput
            ? []
            : ['is_safe' => ['html']];
    }
}
