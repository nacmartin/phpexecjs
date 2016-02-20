<?php

namespace Nacmartin\PhpExecJs;
use Symfony\Component\Process\Process;
use Nacmartin\PhpExecJs\Runtime;

class RuntimeAutodetector
{
    private $runtimes = array();

    public function __construct()
    {
        $this->runtimes[] = new Runtime\ExternalRuntime('Node.js (V8)', 'node');
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
