<?php

namespace Nacmartin\PhpExecJs;

use Nacmartin\PhpExecJs\Runtime\ExternalRuntime;

class RuntimeAutodetector
{
    private $runtimes = array();

    public function __construct()
    {
        $this->runtimes[] = new ExternalRuntime('Node.js (V8)', 'node');
    }

    public function autodetect()
    {
        foreach ($this->runtimes as $runtime) {
            if ($runtime->isAvailable()) {
                return $runtime;
            }
        }
        throw new \RuntimeException('PhpExecJs: Cannot autodetect any JavaScript runtime');
    }
}
