<?php

namespace Vectorface\Tests\Auth;

use Exception;
use Vectorface\Auth\Auth;
use Vectorface\Auth\AuthException;
use Vectorface\Auth\Plugin\BaseAuthPlugin;
use Vectorface\Auth\Plugin\SharedLoggerTrait;
use Psr\Log\LoggerAwareTrait;

/**
 * An auth plugin for testing.
 */
class TestPlugin extends BaseAuthPlugin
{
    use LoggerAwareTrait;
    use SharedLoggerTrait;

    protected $result = Auth::RESULT_NOOP;

    private function action()
    {
        if ($this->result instanceof Exception) {
            throw $this->result;
        }
        return $this->result;
    }

    /**
     * Pass through the auth object for unit test.
     *
     * @return Auth
     */
    public function getAuthObject()
    {
        return $this->getAuth();
    }

    public function setResult($result)
    {
        $this->result = $result;
    }
    public function login($username, $password)
    {
        return $this->action();
    }
    public function verify()
    {
        return $this->action();
    }
    public function logout()
    {
        return $this->action();
    }

    public function returnTrue()
    {
        return true;
    }
    public function throwException()
    {
        throw new Exception("Exception thrown on purpose in test case.");
    }
    public function throwAuthException()
    {
        throw new AuthException("AuthException thrown on purpose in test case.");
    }
    public function testWarning($message)
    {
        $this->warning($message);
    }
}
