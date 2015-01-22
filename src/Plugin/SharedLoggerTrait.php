<?php

namespace Vectorface\Auth\Plugin;

use Psr\Log\LoggerTrait as PsrLoggerTrait;

/**
 * Plugins wishing to perform logging may do so using this trait.
 *
 * It should be expected that plugins exhibit this trait for their own benefit;
 * So they can call $this->warning() etc. on themselves. The info/notice/etc.
 * methods will be exposed by the auth instance itself, but they're fairly
 * pointless in that context.
 */
trait SharedLoggerTrait {
    use PsrLoggerTrait;

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array()) {
        $auth = $this->getAuth();
        if ($logger = $auth[LoggerAwarePlugin::SHARED_KEY_LOGGER]) {
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

