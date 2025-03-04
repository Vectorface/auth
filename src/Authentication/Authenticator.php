<?php

namespace Vectorface\Auth\Authentication;

/**
 * A class representing a middleware-based Authentication framework
 */
class Authenticator
{
    use AuthenticatorTrait;

    /**
     * Create an authenticator with the given plugins
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
     *
     * @template T
     * @param class-string<T> $pluginClass
     * @return ?T
     */
    public function __invoke(string $pluginClass): ?PluginInterface
    {
        return $this->plugin($pluginClass);
    }
}
