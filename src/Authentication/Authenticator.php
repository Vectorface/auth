<?php

namespace Vectorface\Auth\Authentication;

use ArrayAccess;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LogLevel;
use Vectorface\Auth\Common\StackExecutorTrait;

/**
 * A class representing a middleware-based Authentication framework
 */
class Authenticator
{
    /* Provides setLogger method, and protected logger property. */
    use LoggerAwareTrait;

    /* Executes middleware-like stacks */
    use StackExecutorTrait;

    /**
     * An array of auth plugins.
     *
     * @var PluginInterface[]
     */
    private array $plugins = [];

    private string $logLevel = LogLevel::WARNING;

    /**
     * Add a plugin to the Authenticator module.
     *
     * @param class-string<PluginInterface>|PluginInterface ...$plugins
     */
    public function __construct(string|PluginInterface ...$plugins)
    {
        foreach ($plugins as $plugin) {
            if (is_string($plugin)) {
                if (!is_a($plugin, PluginInterface::class, true)) {
                    throw new InvalidArgumentException(static::class . " doesn't implement " . PluginInterface::class);
                }
                $plugin = new $plugin();
            }
            $this->plugins[] = $plugin;
        }
    }

    /**
     * Attempt to authenticate with the credentials provided
     *
     * @return bool True if the login was successful, false otherwise.
     * @throws Exception
     */
    public function authenticate(Credential ...$credentials): bool
    {
        $callables = [];
        foreach ($this->plugins as $index => $plugin) {
            $callables[$plugin::class . "@{$index}"] = fn($next, ...$args) => $plugin->authenticate($next, ...$args);
        }

        return $this->executeStack(
            __FUNCTION__,
            $credentials,
            ...$callables,
        ) ?? false;
    }

    /**
     * Attempt to clear any authentication information
     */
    public function deauthenticate(): bool
    {
        $callables = [];
        foreach ($this->plugins as $index => $plugin) {
            $callables[$plugin::class . "@{$index}"] = fn($next) => $plugin->deauthenticate($next);
        }

        return $this->executeStack(
            __FUNCTION__,
            [],
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
                $this->logger?->debug("Authenticator: found plugin", ['class' => $pluginClass, 'plugin' => $plugin]);
                return $plugin;
            }
        }
        $this->logger?->debug("Authenticator: plugin not found", ['class' => $pluginClass]);
        return null;
    }
}
