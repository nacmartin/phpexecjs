<?php

namespace Nacmartin\PhpExecJs\Runtime;

interface RuntimeInterface
{
    /**
     * Evaluates JS code and returns the output.
     *
     * @param $code string Code to evaulate
     * @returns string
     */
    public function evalJs($code);

    /**
     * Calls a JavaScript function against an array of arguments.
     *
     * @param string $function
     * @param array  $arguments
     *
     * @return string
     */
    public function call($function, $arguments);

    /**
     * Checks if the runtime is available.
     *
     * @return bool
     */
    public function isAvailable();

    /**
     * Returns the name of the runtime.
     *
     * @return string
     */
    public function getName();

    /**
     * Stores code as context, so we can eval other JS with this context.
     *
     * @param $code string
     */
    public function createContext($code);
}
