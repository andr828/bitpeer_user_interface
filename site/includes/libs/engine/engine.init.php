<?php

/**
 * BitPeer Engine Logger Library
 *
 * Functions for logging actions and errors on the frontend
 *
 * @category   ArcticDesk
 * @package    Engine
 * @copyright  Copyright (c) 2014 BitPeer
 * @version    $Id: engine.init.php 3036 2014-03-29 15:14:25Z yatesj $
 * @since      File available since 1.0
 */

$className = 'engineInit';

class engineInit
{

    var $auth;
    var $database;
    var $general;
    var $library;
    var $loaded;
    var $logger;

	function __construct()
	{

        require_once(LOCATION."includes/libs/database/database.mysql.php");
        $this->database = new databaseMysql();

        require_once(LOCATION."includes/libs/engine/engine.general.php");
        $this->general = new engineGeneral($this);

        require_once(LOCATION."includes/libs/engine/engine.logger.php");
        $this->logger = new engineLogger($this);

        require_once(LOCATION."includes/libs/engine/engine.memcache.php");
        $this->memcache = new engineMemcache($this);

        require_once(LOCATION."includes/libs/users/users.auth.php");
        $this->auth = new usersAuth($this);

	}

	/**
    * Load libraries
    *
    * @param  string  $libraryName  String of library to load
    */
    function loadLibrary($libraryName)
    {

        if (!isset($this->loaded[$libraryName])) {

            // Explode the lib to load
            $exploded = explode("/", $libraryName);

            // Check to see if we are loading a specific library file or starting the application
            if (!empty($exploded[1])) {

                // Load a specific file
                $toLoad = LOCATION."includes/libs/" . $exploded[0] . "/" . $exploded[0] . "." . $exploded[1] . ".php";

                // Check it exists
                if (file_exists($toLoad)) {

                    // Load it
                    require_once($toLoad);

                    // Start the class
                    $this->library[$className] = new $className($this);
                    $this->loaded[$libraryName] = 1;

                }

            }

        }

    }

}