<?php

namespace Nacmartin\PhpExecJs;
use Symfony\Component\Process\Process;
use Nacmartin\PhpExecJs\Runner;

class RunnerAutodetector
{
    private $runners = array();

    public function __construct()
    {
        $this->runners[] = new Runner\ExternalRunner('Node.js (V8)', 'node');
    }

    public function autodetect()
    {
        foreach ($this->runners as $runner) {
            if ($runner->isAvailable()) {
                return $runner;
            }
        }
        throw new \RuntimeException('PhpExecJs: Cannot autodetect any JavaScript runtime');
    }
}
