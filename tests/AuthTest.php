<?php

namespace Vectorface\Tests\Auth;

use Vectorface\Auth\Auth;
use Vectorface\Auth\AuthException;
use Vectorface\Auth\Plugin\SuccessPlugin;
use Vectorface\Auth\Plugin\NullPlugin;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use SplFixedArray;
use Exception;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    /**
     * @var Auth
     */
    private $auth;

    public function setUp()
    {
        $logger = new Logger('auth');
        $logger->pushHandler(new NullHandler());

        $this->auth = new Auth();
        $this->auth->setLogger($logger);
    }
    public function testNothingDoesNothing()
    {
        $this->assertTrue($this->auth->addPlugin(new NullPlugin()));
        $this->assertFalse($this->auth->login('a', 'b'));
        $this->assertFalse($this->auth->verify());
        $this->assertFalse($this->auth->logout());
    }

    public function testSuccess()
    {
        $this->assertTrue($this->auth->addPlugin(new SuccessPlugin()));
        $this->assertTrue($this->auth->login('a', 'b'));
        $this->assertTrue($this->auth->verify());
        $this->assertTrue($this->auth->logout());

    }

    public function testLogin()
    {
        $this->assertTrue($this->auth->addPlugin(new HardcodedUserPlugin()));
        $this->assertFalse($this->auth->login('u', 'p'));
        $this->assertTrue($this->auth->login('foo', 'bar'));
    }

    public function testForce()
    {
        $testFail = new TestPlugin();
        $testForce = new TestPlugin();
        $testFail->setResult(Auth::RESULT_FAILURE);
        $testForce->setResult(Auth::RESULT_FORCE);
        $this->assertTrue($this->auth->addPlugin($testForce)); // Force before fail.
        $this->assertTrue($this->auth->addPlugin($testFail));
        $this->assertTrue($this->auth->verify());
    }

    public function testArrayAccess()
    {
        $this->assertArrayNotHasKey('foo', $this->auth); // offsetExists
        $this->assertNull($this->auth['foo']); // offsetGet
        $this->auth['foo'] = 'bar'; // offsetSet
        $this->assertEquals('bar', $this->auth['foo']); // offsetGet
        unset($this->auth['foo']); // offsetUnset
        $this->assertNull($this->auth['foo']); // offsetGet
    }

    public function testFunctionPassthrough()
    {
        $test = new TestPlugin();
        $this->auth->addPlugin($test);

        $this->assertEquals($this->auth, $test->getAuthObject());
        $this->assertTrue($this->auth->returnTrue());

        try {
            $this->assertNull($this->auth->throwAuthException());
            $this->fail('Expected to pass up the AuthException');
        } catch (AuthException $e) {
            // Expected
        }
        $this->assertNull($this->auth->throwException()); // Gets caught and causes action failure.
        $this->assertNull($this->auth->notDefined()); // Method not implemented
    }

    public function testEdgeCases()
    {
        $test = new TestPlugin();
        $this->auth->addPlugin($test);
        $test->setResult(new SplFixedArray()); // This isn't a valid result.

        try {
            $this->auth->login('u', 'p');
            $this->fail("An invalid result should have triggered an AuthException");
        } catch (AuthException $e) {
            // Expected
        }

        $this->setUp();
        $this->assertTrue($this->auth->addPlugin('Vectorface\\Auth\\Plugin\\SuccessPlugin'));
        $this->auth->addPlugin($test);
        $test->setResult(new Exception("Exception added on purpose by test case.")); // Causes a log entry and failure.
        $this->assertFalse($this->auth->verify());

        $test->setResult(new AuthException());
        try {
            $this->auth->verify();
            $this->fail("Expected AuthException to be passed up.");
        } catch (AuthException $e) {
            // Expected
        }

        /* Loading by class name should work. */
        $this->assertTrue($this->auth->addPlugin('Vectorface\\Auth\\Plugin\\SuccessPlugin'));
        $this->assertFalse($this->auth->addPlugin(new SplFixedArray())); // Fails for obvious reasons.
    }
}
