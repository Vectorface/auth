<?php

namespace Vectorface\Tests\Auth;

use Vectorface\Auth\Auth;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    public function testLogging()
    {
        $logfile = sys_get_temp_dir() . '/LoggerTest';

        $test = new TestPlugin();
        $auth = new Auth();
        $auth->addPlugin($test);

        $globalLogger = new Logger('GlobalLogger');
        $globalLogger->pushHandler(new StreamHandler($logfile, Logger::WARNING));

        $internalLogger = new Logger('InternalLogger');
        $internalLogger->pushHandler(new StreamHandler($logfile, Logger::WARNING));

        $auth->setLogger($globalLogger);
        $this->assertEquals($globalLogger, $auth->getLogger());

        /* It can use the global logger... */
        $this->assertFalse((@file_get_contents($logfile)));
        $auth->testWarning("Logger Test!");
        $this->assertTrue(strpos(@file_get_contents($logfile), "Logger Test!") !== false);
        $this->assertTrue(strpos(@file_get_contents($logfile), "GlobalLogger") !== false);
        @unlink($logfile);

        /* ... Or its own logger! */
        $this->assertFalse((@file_get_contents($logfile)));
        $test->setLogger($internalLogger);
        $auth->testWarning("Logger Test!");
        $this->assertTrue(strpos(@file_get_contents($logfile), "Logger Test!") !== false);
        $this->assertTrue(strpos(@file_get_contents($logfile), "InternalLogger") !== false);
        @unlink($logfile);
    }
}
