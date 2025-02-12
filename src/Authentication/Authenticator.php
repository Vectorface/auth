<?php

namespace Vectorface\Auth\Authentication;

use ArrayAccess;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LogLevel;

/**
 * A class representing a middleware-based Authentication framework
 */
class Authenticator
{
    /**
     * Provides setLogger method, and protected logger property.
     */
    use LoggerAwareTrait;

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
     * @param class-string<PluginInterface>|PluginInterface $plugin The plugin class or instance to be registered
     */
    public function register(string|PluginInterface $plugin): void
    {
        if (is_string($plugin)) {
            if (!is_a($plugin, PluginInterface::class, true)) {
                throw new InvalidArgumentException("Plugins must implement PluginInterface");
            }
            $plugin = new $plugin();
        }
        $this->plugins[] = $plugin;
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
        );
    }

    /**
     * Attempt to clear any authentication information
     */
    public function deauthenticate(): bool
    {
        return $this->executeStack(
            __FUNCTION__,
            [],
            ...array_map(fn($plugin) => fn($next) => $plugin->deauthenticate($next), $this->plugins),
        );
    }

    /**
     * Get the plugin instance if registered
     */
    public function __invoke(string $pluginClass): ?PluginInterface
    {
        $this->logger?->debug("Looking for plugin: {$pluginClass}");
        foreach ($this->plugins as $plugin) {
            if (is_a($plugin, $pluginClass, true)) {
                $this->logger?->debug("Authenticator: found plugin", ['class' => $pluginClass, 'plugin' => $plugin]);
                return $plugin;
            }
        }
        $this->logger?->debug("Authenticator: plugin not found", ['class' => $pluginClass]);
        return null;
    }

    private function executeStack(string $name, array $args, callable ...$callables): bool
    {
        reset($callables);
        $next = function(callable $next, ...$args) use($callables, $name): bool {
            $callable = current($callables);
            $identifier = key($callables);
            if ($callable === false) {
                return false;
            }
            next($callables);
            $result = $callable(fn(...$args) => $next($next, ...$args), ...$args);
            $this->logger?->debug("Authenticator: {$name}::{$identifier} result", ['result' => $result]);
            return $result;
        };

        $result = false;
        try {
            $result = $next($next, ...$args);
        } catch (\Exception $e) {
            $this->logger?->log($this->logLevel, "Authenticator: Exception occurred during {$name}", ['exception' => $e->getMessage()]);
        }

        return $result;
    }
}
