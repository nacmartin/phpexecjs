<?php

namespace Nacmartin\PhpExecJs\Runtime;

class V8jsRuntime implements RuntimeInterface
{
    /**
     * @var \V8Js
     */
    private $v8;

    /**
     * @var resource Our compiled context
     */
    public $context = null;

    public function __construct()
    {
        if ($this->isAvailable()) {
            $this->v8 = new \V8Js();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function evalJs($code)
    {
        return $this->v8->executeString($code);
    }

    /**
     * {@inheritdoc}
     */
    public function call($function, $arguments = array())
    {
        return $this->evalJs($function.'.apply(this, '.json_encode($arguments).')');
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable()
    {
        return extension_loaded('v8js');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'V8js PHP Extension (V8)';
    }

    /**
     * {@inheritdoc}
     */
    public function createContext($code)
    {
        $this->context = $this->v8->compileString($code);
        $this->v8->executeScript($this->context);
    }
}
