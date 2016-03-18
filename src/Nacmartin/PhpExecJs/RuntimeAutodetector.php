<?php

namespace Nacmartin\PhpExecJs;

use Nacmartin\PhpExecJs\Runtime\ExternalRuntime;
use Nacmartin\PhpExecJs\Runtime\V8jsRuntime;

class RuntimeAutodetector
{
    private $runtimes = array();

    public function __construct()
    {
        // Runners will be checked for availabilty by order in array so order
        // matters
        $this->runtimes[] = new V8jsRuntime('V8js PHP Extension (V8)');
        $this->runtimes[] = new ExternalRuntime('Node.js (V8)', array('node', 'nodejs'));
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
