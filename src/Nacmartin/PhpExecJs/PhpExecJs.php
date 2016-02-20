<?php

namespace Nacmartin\PhpExecJs;
use Nacmartin\PhpExecJs\Runtime\RuntimeInterface;

class PhpExecJs
{
    /**
     * 
     */
    private $runtime;

    public function __construct(RuntimeInterface $runtime = null)
    {
        if (!$runtime) {
            $runtimeAutodetector = new RuntimeAutodetector();
            $this->runtime = $runtimeAutodetector->autodetect();
        } else {
            $this->runtime = $runtime;
        }
    }

    /**
     * Returns the name of the current runtime
     *
     * @returns string
     */
    public function getRuntimeName()
    {
        return $this->runtime->getName();
    }

    /**
     * Evaluates JS code and returns the output
     *
     * @param $code string Code to evaulate
     * @returns string
     */
    public function evalJs($code)
    {
        return $this->runtime->evalJs($code);
    }

    /**
     * Stores code as context, so we can eval other JS with this context
     *
     * @param $code string
     * @return void
     */
    public function createContext($code)
    {
        $this->runtime->createContext($code);
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
        return $this->runtime->call($function, $arguments);
    }

}
