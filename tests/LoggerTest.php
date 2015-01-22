<?php

namespace Vectorface\Tests\Auth;

use Vectorface\Auth\Auth;
use Vectorface\Auth\Plugin\LoggerAwarePlugin;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    public function testLogging()
    {
        $logfile = sys_get_temp_dir() . '/LoggerTest';

        $test = new TestPlugin();
        $auth = new Auth();
        $auth->addPlugin(new LoggerAwarePlugin());
        $auth->addPlugin($test);

        $logger = new Logger('auth');
        $logger->pushHandler(new StreamHandler($logfile, Logger::WARNING));

        $auth->setLogger($logger);
        $this->assertEquals($logger, $auth->getLogger());

        $this->assertFalse((@file_get_contents($logfile)));
        $test->warning("Logger Test!");
        $this->assertTrue(strpos(@file_get_contents($logfile), "Logger Test!") !== false);

        @unlink($logfile);
    }
}
