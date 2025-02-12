<?php

namespace Vectorface\Tests\Auth;

use Vectorface\Auth\Authenticator;
use Vectorface\Auth\Exception;
use Vectorface\Auth\Plugin\SuccessPlugin;
use Vectorface\Auth\Plugin\NullPlugin;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use SplFixedArray;
use Exception;
use PHPUnit\Framework\TestCase;
use Vectorface\Tests\Auth\Helpers\HardcodedUserPlugin;
use Vectorface\Tests\Auth\Helpers\TestPlugin;

class AuthTest extends TestCase
{
    /**
     * @var Authenticator
     */
    private $auth;

    protected function setUp() : void
    {
        $logger = new Logger('auth');
        $logger->pushHandler(new NullHandler());

        $this->auth = new Authenticator();
        $this->auth->setLogger($logger);
    }

    /**
     * @throws Exception
     */
    public function testNothingDoesNothing()
    {
        $this->assertTrue($this->auth->addPlugin(new NullPlugin()));
        $this->assertFalse($this->auth->login('a', 'b'));
        $this->assertFalse($this->auth->verify());
        $this->assertFalse($this->auth->logout());
    }

    /**
     * @throws Exception
     */
    public function testSuccess()
    {
        $this->assertTrue($this->auth->addPlugin(new SuccessPlugin()));
        $this->assertTrue($this->auth->login('a', 'b'));
        $this->assertTrue($this->auth->verify());
        $this->assertTrue($this->auth->logout());
    }

    /**
     * @throws Exception
     */
    public function testLogin()
    {
        $this->assertTrue($this->auth->addPlugin(new HardcodedUserPlugin()));
        $this->assertFalse($this->auth->login('u', 'p'));
        $this->assertTrue($this->auth->login('foo', 'bar'));
    }

    /**
     * @throws Exception
     */
    public function testForce()
    {
        $testFail = new TestPlugin();
        $testForce = new TestPlugin();
        $testFail->setResult(Authenticator::RESULT_FAILURE);
        $testForce->setResult(Authenticator::RESULT_FORCE);
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

    /**
     * @noinspection PhpUndefinedMethodInspection
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function testFunctionPassthrough()
    {
        $test = new TestPlugin();
        $this->auth->addPlugin($test);

        $this->assertEquals($this->auth, $test->getAuthObject());
        $this->assertTrue($this->auth->returnTrue());

        try {
            $this->assertNull($this->auth->throwAuthException());
            $this->fail('Expected to pass up the Exception');
        } catch (Exception $e) {
            // Expected
        }
        $this->assertNull($this->auth->throwException()); // Gets caught and causes action failure.
        $this->assertNull($this->auth->notDefined()); // Method not implemented
    }

    public function testAddPlugin()
    {
        $this->assertTrue($this->auth->addPlugin(new TestPlugin()));
        $this->assertFalse($this->auth->addPlugin(new SplFixedArray()), 'Not an Authenticator plugin');
        $this->assertFalse($this->auth->addPlugin('SplFixedArray'), 'Not an Authenticator plugin');
        $this->assertFalse($this->auth->addPlugin(1.2), 'Not an Authenticator plugin');
    }

    /**
     * @throws Exception
     */
    public function testEdgeCases()
    {
        $test = new TestPlugin();
        $this->auth->addPlugin($test);
        $test->setResult(new SplFixedArray()); // This isn't a valid result.

        try {
            $this->auth->login('u', 'p');
            $this->fail("An invalid result should have triggered an Exception");
        } catch (Exception $e) {
            // Expected
        }

        $this->setUp();
        $this->assertTrue($this->auth->addPlugin('Vectorface\\Auth\\Plugin\\SuccessPlugin'));
        $this->auth->addPlugin($test);
        $test->setResult(new Exception("Exception added on purpose by test case.")); // Causes a log entry and failure.
        $this->assertFalse($this->auth->verify());

        $test->setResult(new Exception());
        try {
            $this->auth->verify();
            $this->fail("Expected Exception to be passed up.");
        } catch (Exception $e) {
            // Expected
        }

        /* Loading by class name should work. */
        $this->assertTrue($this->auth->addPlugin('Vectorface\\Auth\\Plugin\\SuccessPlugin'));
        $this->assertFalse($this->auth->addPlugin(new SplFixedArray())); // Fails for obvious reasons.
    }
}
