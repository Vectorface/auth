<?php

namespace Vectorface\Auth\Plugin;

use Psr\Log\LoggerTrait as PsrLoggerTrait;

/**
 * Plugins wishing to perform their own logging may do so using this trait.
 */
trait SharedLoggerTrait
{
    /**
     * Use the PSR logger trait, but make the methods protected so they aren't exposed via the Auth object.
     */
    use PsrLoggerTrait {
        emergency as protected;
        alert as protected;
        critical as protected;
        error as protected;
        warning as protected;
        notice as protected;
        info as protected;
        debug as protected;
        log as protected;
    }

    /**
     * Logs with an arbitrary level, or don't if no logger has been set.
     *
     * @param mixed $level The log level. A LogLevel::* constant (usually)
     * @param string $message The message to log.
     * @param array $context Further information about the context of the log message.
     */
    protected function log($level, $message, array $context = array())
    {
        $logger = null;
        if (isset($this->logger)) {
            $logger = $this->logger;
        } elseif ($auth = $this->getAuth()) {
            $logger = $auth->getLogger();
        }

        if ($logger) {
            $logger->log($level, $message, $context);
        }
    }

    /**
     * Get the Auth class instance
     *
     * @return Auth
     */
    abstract protected function getAuth();
}
