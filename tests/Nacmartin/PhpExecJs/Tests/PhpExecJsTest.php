<?php

namespace Nacmartin\PhpExecJs\Tests;
use Nacmartin\PhpExecJs\PhpExecJs;
use Nacmartin\PhpExecJs\Runner\ExternalRunner;

class PhpExecJsTest extends \PHPUnit_Framework_TestCase
{
    public function testAutodetectRunner()
    {
        $phpExecJs = new PhpExecJs();
        $this->assertEquals('Node.js (V8)', $phpExecJs->getRunnerName());
    }

    public function testForceRunner()
    {
        $runner = new ExternalRunner('foo');
        $phpExecJs = new PhpExecJs($runner);
        $this->assertEquals('foo', $phpExecJs->getRunnerName());
    }

    public function testEval()
    {
        $phpExecJs = new PhpExecJs();
        $this->assertEquals(2, $phpExecJs->evalJs('1 + 1'));
    }

    public function testContext()
    {
        $phpExecJs = new PhpExecJs();
        $phpExecJs->createContext('var a = 1');

        $this->assertEquals(2, $phpExecJs->evalJs('a + 1'));
    }

    public function testCall()
    {
        $phpExecJs = new PhpExecJs();
        $phpExecJs->createContext('var sum = function(a, b) { return a + b;}');

        $this->assertEquals(3, $phpExecJs->call('sum', [1, 2]));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testError()
    {
        $phpExecJs = new PhpExecJs();
        $phpExecJs->call('something', [1, 2]);
    }
}
