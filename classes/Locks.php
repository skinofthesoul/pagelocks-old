<?php

namespace Grav\Plugin\PageLocks;

use ArrayAccess;
use Countable;
use Exception;
use Iterator;

/**
 * 
 */
class Locks implements ArrayAccess, Countable, Iterator
{
    /** @var Lock[] */
    private $container = [];

    /** @var int */
    private $index = 0;

    public function __construct(array $arg)
    {
        foreach ($arg as $route => $lockItem) {
           $this->container[$route] = new Lock($lockItem);
        }
    }

    public function toArray(): array
    {
        $locks = [];

        foreach ($this->container as $route => $lock) {
            $locks[$route] = (array) $lock;
        }

        return $locks;
    }

    /** 
     * @param string $route
     * @param Lock $lock
     */
    public function offsetSet($route, $lock)
    {
        $this->container[$route] = $lock;
    }

    /** 
     * @param string $route
     * @return bool
     */
    public function offsetExists($route)
    {
        return isset($this->container[$route]);
    }

    /**
     * @param string $route
     */
    public function offsetUnset($route)
    {
        unset($this->container[$route]);
    }

    /**
     * @param string $route
     * @return Lock
     * @throws Exception If offset does not exist
     */
    public function offsetGet($route)
    {
        if (isset($this->container[$route])) {
            return $this->container[$route];
        } else {
            throw new Exception("No lock for route $route");
        }
    }

    /*
     * Implementation of Countable
     */

    /**
     * @return int
     */
    public function count()
    {
        return count($this->container);
    }

    /*
     * Implementation of Iterator
     */

    /**
     * @return void
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * @return Lock
     */
    public function current()
    {
        $routes = array_keys($this->container);
        $lock = $this->container[$routes[$this->index]];

        return $lock;
    }

    /**
     * @return string
     */
    public function key()
    {
        $routes = array_keys($this->container);
        $route = $routes[$this->index];

        return $route;
    }

    public function next()
    {
        $this->index++;

        // $routes = array_keys($this->container);

        // if (isset($routes[++$this->index])) {
        //     $lock = $this->container[$routes[$this->index]];

        //     return $lock;
        // } else {
        //     return false;
        // }
    }

    /**
     * $return bool
     */
    public function valid()
    {
        $routes = array_keys($this->container);
        $isValid = isset($routes[$this->index]);

        return $isValid;
    }
}
