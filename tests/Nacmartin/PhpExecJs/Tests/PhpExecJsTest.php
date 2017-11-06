<?php

namespace Nacmartin\PhpExecJs\Tests;

use Nacmartin\PhpExecJs\PhpExecJs;
use Nacmartin\PhpExecJs\Runtime\ExternalRuntime;

class PhpExecJsTest extends \PHPUnit\Framework\TestCase
{
    public function testAutodetectRuntime()
    {
        $phpExecJs = new PhpExecJs();
        if (extension_loaded('v8js')) {
            $this->assertEquals('V8js PHP Extension (V8)', $phpExecJs->getRuntimeName());
        } else {
            $this->assertEquals('Node.js (V8)', $phpExecJs->getRuntimeName());
        }
    }

    public function testForceRuntime()
    {
        $runtime = new ExternalRuntime('foo');
        $phpExecJs = new PhpExecJs($runtime);
        $this->assertEquals('foo', $phpExecJs->getRuntimeName());
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
