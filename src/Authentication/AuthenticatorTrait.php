<?php

namespace Vectorface\Auth\Authentication;

use InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;
use Vectorface\Auth\Common\StackExecutorTrait;

trait AuthenticatorTrait
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
    private array $authenticationPlugins = [];

    /**
     * Register a plugin to the Authenticator module.
     *
     * @param class-string<PluginInterface>|PluginInterface $plugin
     */
    public function register(string|PluginInterface $plugin, ?string $identifier = null): static
    {
        if (is_string($plugin)) {
            if (!is_a($plugin, PluginInterface::class, true)) {
                throw new InvalidArgumentException(static::class . " doesn't implement " . PluginInterface::class);
            }
            $plugin = new $plugin();
        }

        if (isset($identifier)) {
            if (isset($this->authenticationPlugins[$identifier])) {
                throw new InvalidArgumentException(static::class . " plugin already registered with identifier: {$identifier}");
            }
            $this->authenticationPlugins[$identifier] = $plugin;
        } else {
            $this->authenticationPlugins[] = $plugin;
        }

        return $this;
    }

    /**
     * Attempt to authenticate with the credentials provided
     *
     * @return bool True if the login was successful, false otherwise.
     * @throws \Exception
     */
    public function authenticate(Credential ...$credentials): bool
    {
        $callables = [];
        foreach ($this->authenticationPlugins as $index => $plugin) {
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
        foreach ($this->authenticationPlugins as $index => $plugin) {
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
     *
     * @template T
     * @param class-string<T> $pluginClass
     * @return ?T
     */
    public function plugin(string $pluginClass): ?PluginInterface
    {
        foreach ($this->authenticationPlugins as $identifier => $plugin) {
            if ($identifier === $pluginClass || is_a($plugin, $pluginClass, true)) {
                $this->logger?->debug("Authenticator: found plugin", ['class' => $pluginClass, 'plugin' => $plugin]);
                return $plugin;
            }
        }
        $this->logger?->debug("Authenticator: plugin not found", ['class' => $pluginClass]);
        return null;
    }
}