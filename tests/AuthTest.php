<?php

namespace Vectorface\Tests\Auth;

// hack to override the default "error_log" method so we don't print errors to
// the screen while testing
require_once __DIR__.'/helpers/error_log.php';

use Vectorface\Auth\Auth;
use Vectorface\Auth\AuthException;
use Vectorface\Auth\SuccessPlugin;
use Vectorface\Auth\NullPlugin;
use \SplFixedArray;
use \Exception;

class AuthTest extends \PHPUnit_Framework_TestCase
{
    public function testNothingDoesNothing()
    {
        $auth = new Auth();
        $auth->addPlugin(new NullPlugin());

        $this->assertFalse($auth->login('a', 'b'));
        $this->assertFalse($auth->verify());
        $this->assertFalse($auth->logout());
    }

    public function testSuccess()
    {
        $auth = new Auth();
        $auth->addPlugin(new SuccessPlugin());

        $this->assertTrue($auth->login('a', 'b'));
        $this->assertTrue($auth->verify());
        $this->assertTrue($auth->logout());

    }

    public function testLogin()
    {
        $auth = new Auth();
        $auth->addPlugin(new HardcodedUserPlugin());

        $this->assertFalse($auth->login('u', 'p'));
        $this->assertTrue($auth->login('foo', 'bar'));
    }

    public function testForce()
    {
        $auth = new Auth();
        $testFail = new TestPlugin();
        $testForce = new TestPlugin();
        $testFail->setResult(Auth::RESULT_FAILURE);
        $testForce->setResult(Auth::RESULT_FORCE);
        $auth->addPlugin($testForce); // Force before fail.
        $auth->addPlugin($testFail);

        $this->assertTrue($auth->verify());
    }

    public function testArrayAccess()
    {
        $auth = new Auth();

        $this->assertFalse(isset($auth['foo'])); // offsetExists
        $this->assertNull($auth['foo']); // offsetGet
        $auth['foo'] = 'bar'; // offsetSet
        $this->assertEquals('bar', $auth['foo']); // offsetGet
        unset($auth['foo']); // offsetUnset
        $this->assertNull($auth['foo']); // offsetGet
    }

    public function testFunctionPassthrough()
    {
        $auth = new Auth();
        $test = new TestPlugin();
        $auth->addPlugin($test);

        $this->assertTrue($test->getAuthObject() === $auth);
        $this->assertTrue($auth->returnTrue());

        try {
            $this->assertNull($auth->throwAuthException());
            $this->fail('Expected to pass up the AuthException');
        } catch (AuthException $e) {
            // Expected
        }
        $this->assertNull($auth->throwException()); // Gets caught and causes action failure.
        $this->assertNull($auth->notDefined()); // Method not implemented
    }

    public function testEdgeCases()
    {
        $auth = new Auth();
        $test = new TestPlugin();
        $auth->addPlugin($test);
        $test->setResult(new SplFixedArray()); // This isn't a valid result.

        try {
            $auth->login('u', 'p');
            $this->fail("An invalid result should have triggered an AuthException");
        } catch (AuthException $e) {
            // Expected
        }

        $auth = new Auth();
        $this->assertTrue($auth->addPlugin('Vectorface\\Auth\\SuccessPlugin'));
        $auth->addPlugin($test);
        $test->setResult(new Exception("Exception added on purpose by test case.")); // Causes a log entry and failure.
        $this->assertFalse($auth->verify());

        $test->setResult(new AuthException());
        try {
            $auth->verify();
            $this->fail("Expected AuthException to be passed up.");
        } catch (AuthException $e) {
            // Expected
        }

        /* Loading by class name should work. */
        $this->assertTrue($auth->addPlugin('Vectorface\\Auth\\SuccessPlugin'));
        $this->assertFalse($auth->addPlugin(new SplFixedArray())); // Fails for obvious reasons.
    }
}
