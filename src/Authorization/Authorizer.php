<?php

namespace Vectorface\Auth\Authorization;

class Authorizer
{
    use AuthorizerTrait;

    /**
     * Create an authorizer with the given plugins
     *
     * @param class-string<PluginInterface>|PluginInterface ...$plugins
     */
    public function __construct(string|PluginInterface ...$plugins)
    {
        foreach ($plugins as $identifier => $plugin) {
            $this->register($plugin, is_string($identifier) ? $identifier : null);
        }
    }

    /**
     * Get a registered plugin; Sugar wrapped around the plugin() method.
     */
    public function __invoke(string $pluginClass): ?PluginInterface
    {
        return $this->plugin($pluginClass);
    }
}