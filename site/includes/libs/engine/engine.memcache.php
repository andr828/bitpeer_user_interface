<?php

/**
 * BitPeer Memcached Abstraction
 *
 * Connects to the memcache server
 *
 * @category   ArcticDesk
 * @package    Engine
 * @copyright  Copyright (c) 2014 BitPeer
 * @version    $Id: database.mysql.php 2794 2014-02-22 20:38:04Z yatesj $
 * @since      File available since 1.0
 */

$className = 'engineMemcache';

class engineMemcache
{

    var $memcache;
    var $cacheAvailable;

    /**
     * Used by the engine to start a connection to the memcached server
     */
    function __construct()
    {

        // Memcached connection constants
        define('MEMCACHED_HOST', '127.0.0.1');
        define('MEMCACHED_PORT', '11211');

        // Connect to Memcached
        //$this->memcache = new Memcached();
        //$this->cacheAvailable = $this->memcache->addServer(MEMCACHED_HOST, MEMCACHED_PORT);

    }

    function get($key)
    {
        //return $this->memcache->get($key);
    }

    function set($key, $data)
    {
        //$this->memcache->set($key, $data);
    }

}
