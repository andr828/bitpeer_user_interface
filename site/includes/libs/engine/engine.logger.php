<?php

/**
 * BitPeer Engine Logger Library
 *
 * Functions for logging actions on the frontend
 *
 * @category   ArcticDesk
 * @package    Engine
 * @copyright  Copyright (c) 2014 BitPeer
 * @version    $Id: engine.logger.php 2688 2014-02-05 20:04:28Z jay $
 * @since      File available since 1.0
 */

$className = 'engineLogger';

class engineLogger
{

	var $engine;

    /**
     * Required to access the other library functions
     * @param  class  &$engine
     */
    function engineLogger(&$engine)
    {
        $this->engine = &$engine;
    }

	function logAction($type = 0, $userId, $orderId = '', $action)
	{

        try {
            // Add log entry
            $query = 'INSERT INTO action_log VALUES ("", :type, :user_id, :order_id, ' . microtime(true) . ', :action)';

            $inputs = array(
                ':type'     => $type,
                ':user_id'  => $userId,
                ':order_id' => $orderId,
                ':action'   => $action
            );

            $this->engine->database->query(1, $query, $inputs);

        } catch (PDOException $e) {
            echo('ERROR: ' . $e->getMessage()."\n");
        }

	}

}