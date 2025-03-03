<?php

namespace Vectorface\Auth\Authorization;

use InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;
use Vectorface\Auth\Common\StackExecutorTrait;
use Vectorface\Auth\Authorization\Exception as AuthorizationException;

trait AuthorizerTrait
{
    /* Provides setLogger method, and protected logger property. */
    use LoggerAwareTrait;

    /* Executes middleware-like stacks */
    use StackExecutorTrait;

    /**
     * @var PluginInterface[]
     */
    private array $authorizationPlugins = [];

    /**
     * Create an Authorizer with the given plugins.
     *
     * @param class-string<PluginInterface>|PluginInterface $plugin
     */
    public function register(string|PluginInterface $plugin, ?string $identifier = null): static
    {
        if (is_string($plugin)) {
            if (! is_a($plugin, PluginInterface::class, true)) {
                throw new InvalidArgumentException(static::class . " doesn't implement " . PluginInterface::class);
            }
            $plugin = new $plugin();
        }

        if (isset($identifier)) {
            if (isset($this->authorizationPlugins[$identifier])) {
                throw new InvalidArgumentException(static::class . " plugin already registered with identifier: {$identifier}");
            }
            $this->authorizationPlugins[$identifier] = $plugin;
        } else {
            $this->authorizationPlugins[] = $plugin;
        }

        return $this;
    }

    /**
     * Check if the subject (typically a user) is authorized to access a resource
     */
    public function can(mixed $resource, mixed $subject = null): bool
    {
        $callables = [];
        foreach ($this->authorizationPlugins as $index => $plugin) {
            $callables[$plugin::class . "@{$index}"] = fn($next, $resource, $subject) => $plugin->can($next, $resource, $subject);
        }

        return $this->executeStack(
            __FUNCTION__,
            [$resource, $subject],
            ...$callables,
        ) ?? false;
    }

    /**
     * Throw an exception if the subject is not authorized to access a resource
     */
    public function canOrFail(mixed $resource, mixed $subject = null): void
    {
        if (! $this->can($resource, $subject)) {
            throw new AuthorizationException("Unauthorized access to resource");
        }
    }

    /**
     * Get the plugin instance if registered
     */
    public function plugin(string $pluginClass): ?PluginInterface
    {
        foreach ($this->authorizationPlugins as $identifier => $plugin) {
            if ($identifier === $pluginClass || is_a($plugin, $pluginClass, true)) {
                $this->logger?->debug("Authorizer: found plugin", ['class' => $pluginClass, 'plugin' => $plugin]);
                return $plugin;
            }
        }
        $this->logger?->debug("Authenticator: plugin not found", ['class' => $pluginClass]);
        return null;
    }
}