<?php

namespace Nacmartin\PhpExecJs\Runtime;

class V8jsRuntime implements RuntimeInterface
{
    /**
     * @var \V8Js
     */
    private $v8;

    private $cache = null;

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
    public function createContext($code, $cachename = null)
    {
        if ($cachename) {
            $cacheItem = $this->cache->getItem($cachename);
            if ($cacheItem->isHit()) {
                $snapshot = $cacheItem->get();
            } else {
                $snapshot = \V8Js::createSnapshot($code);
                $cacheItem->set($snapshot);
                $this->cache->save($cacheItem);
            }
        } else {
            $snapshot = \V8Js::createSnapshot($code);
        }
        $this->v8 = new \V8Js('PHP', [], [], true, $snapshot);
    }

    public function supportsCache()
    {
        return true;
    }

    public function setCache($cache)
    {
        $this->cache = $cache;
    }
}
