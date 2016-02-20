<?php

namespace Nacmartin\PhpExecJs;
use Nacmartin\PhpExecJs\Runner\RunnerInterface;

class PhpExecJs
{
    /**
     * 
     */
    private $runner;

    public function __construct(RunnerInterface $runner = null)
    {
        if (!$runner) {
            $runnerAutodetector = new RunnerAutodetector();
            $this->runner = $runnerAutodetector->autodetect();
        } else {
            $this->runner = $runner;
        }
    }

    /**
     * Returns the name of the current runner
     *
     * @returns string
     */
    public function getRunnerName()
    {
        return $this->runner->getName();
    }

    /**
     * Evaluates JS code and returns the output
     *
     * @param $code string Code to evaulate
     * @returns string
     */
    public function evalJs($code)
    {
        return $this->runner->evalJs($code);
    }

    /**
     * Stores code as context, so we can eval other JS with this context
     *
     * @param $code string
     * @return void
     */
    public function createContext($code)
    {
        $this->runner->createContext($code);
    }

    /**
     * Creates context by reading a file
     *
     * @param $filename string
     * @return void
     */
    public function createContextFromFile($filename)
    {
        $this->createContext(file_get_contents($filename));
    }

    /**
     * Calls a JavaScript function against an array of arguments
     * 
     * @param string $function 
     * @param array $arguments 
     * @return string
     */
    public function call($function, $arguments = array())
    {
        return $this->runner->call($function, $arguments);
    }

}
