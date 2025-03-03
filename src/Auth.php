<?php

namespace Vectorface\Auth;

use Vectorface\Auth\Authentication\AuthenticatorTrait;
use Vectorface\Auth\Authorization\AuthorizerTrait;
use Vectorface\Auth\Authorization\PluginInterface as AuthorizationPlugin;
use Vectorface\Auth\Authentication\PluginInterface as AuthenticationPlugin;

class Auth
{
    use AuthenticatorTrait, AuthorizerTrait {
        // These next two lines seem useless, but PHP throws a compile-time error without them
        AuthenticatorTrait::register insteadof AuthorizerTrait;
        AuthenticatorTrait::plugin insteadof AuthorizerTrait;
        AuthenticatorTrait::register as registerAuthenticationPlugin;
        AuthenticatorTrait::plugin as getAuthenticationPlugin;
        AuthorizerTrait::register as registerAuthorizationPlugin;
        AuthorizerTrait::plugin as getAuthorizationPlugin;
    }

    public function __construct(
        AuthenticationPlugin|AuthorizationPlugin ...$plugins
    ) {
        foreach ($plugins as $plugin) {
            if (is_a($plugin, AuthenticationPlugin::class, true)) {
                $this->registerAuthenticationPlugin($plugin);
            }
            if (is_a($plugin, AuthorizationPlugin::class, true)) {
                $this->registerAuthorizationPlugin($plugin);
            }
        }
    }

    public function __invoke(string $pluginClass): AuthenticationPlugin|AuthorizationPlugin|null
    {
        // final `?? null` is redundant but left for readability
        return $this->getAuthenticationPlugin($pluginClass) ?? $this->getAuthorizationPlugin($pluginClass) ?? null;
    }
}