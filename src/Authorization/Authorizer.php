<?php

namespace Vectorface\Auth\Authorization;

use InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;
use Vectorface\Auth\Common\StackExecutorTrait;

class Authorizer
{
    /* Provides setLogger method, and protected logger property. */
    use LoggerAwareTrait;

    /* Executes middleware-like stacks */
    use StackExecutorTrait;

    /**
     * @var PluginInterface[]
     */
    private array $plugins = [];

    /**
     * Create an Authorizer with the given plugins.
     *
     * @param class-string<PluginInterface>|PluginInterface ...$plugins
     */
    public function __construct(string|PluginInterface ...$plugins)
    {
        foreach ($plugins as $plugin)
            if (is_string($plugin)) {
                if (! is_a($plugin, PluginInterface::class, true)) {
                    throw new InvalidArgumentException(static::class . " doesn't implement " . PluginInterface::class);
                }
                $plugin = new $plugin();
            }
        $this->plugins[] = $plugin;
    }

    public function can(mixed $subject, mixed $action): bool
    {
        $callables = [];
        foreach ($this->plugins as $index => $plugin) {
            $callables[$plugin::class . "@{$index}"] = fn($next, ...$args) => $plugin->can($next, ...$args);
        }

        return $this->executeStack(
            __FUNCTION__,
            [$subject, $action],
            ...$callables,
        ) ?? false;
    }

    /**
     * Get the plugin instance if registered
     */
    public function __invoke(string $pluginClass): ?PluginInterface
    {
        foreach ($this->plugins as $plugin) {
            if (is_a($plugin, $pluginClass, true)) {
                $this->logger?->debug("Authorizer: found plugin", ['class' => $pluginClass, 'plugin' => $plugin]);
                return $plugin;
            }
        }
        $this->logger?->debug("Authenticator: plugin not found", ['class' => $pluginClass]);
        return null;
    }
}