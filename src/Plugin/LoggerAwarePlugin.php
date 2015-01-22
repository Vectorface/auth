<?php

namespace Vectorface\Auth\Plugin;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * A plugin that adds a common shared logger.
 */
class LoggerAwarePlugin extends BaseAuthPlugin implements LoggerAwareInterface
{
    /**
     * The name of the key used to share the logger among plugins.
     */
    const SHARED_KEY_LOGGER = 'logger';

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger The logger to activate for auth.
     */
    public function setLogger(LoggerInterface $logger)
    {
        $auth = $this->getAuth();
        $auth[self::SHARED_KEY_LOGGER] = $logger;
    }

    /**
     * Get the logger instance set on this object
     *
     * @return LoggerInterface The shared logger, or null if not set.
     */
    public function getLogger()
    {
        $auth = $this->getAuth();
        return $auth[self::SHARED_KEY_LOGGER];
    }
}
