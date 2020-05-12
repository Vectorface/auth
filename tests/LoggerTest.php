<?php

namespace Vectorface\Tests\Auth;

use Vectorface\Auth\Auth;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PHPUnit\Framework\TestCase;
use Vectorface\Tests\Auth\Helpers\TestPlugin;

class LoggerTest extends TestCase
{
    /**
     * @noinspection PhpUsageOfSilenceOperatorInspection
     */
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
        $test->testWarning("Logger Test!");
        $this->assertContains("Logger Test!", @file_get_contents($logfile));
        $this->assertContains("GlobalLogger", @file_get_contents($logfile));
        @unlink($logfile);

        /* ... Or its own logger! */
        $this->assertFalse((@file_get_contents($logfile)));
        $test->setLogger($internalLogger);
        $test->testWarning("Logger Test!");
        $this->assertContains("Logger Test!", @file_get_contents($logfile));
        $this->assertContains("InternalLogger", @file_get_contents($logfile));
        @unlink($logfile);
    }
}
