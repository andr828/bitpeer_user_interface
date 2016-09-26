<?php

/**
 * BitPeer Trading Orders Library
 *
 * Functions related to orders in the frontend
 *
 * @category   ArcticDesk
 * @package    Trading
 * @copyright  Copyright (c) 2014 BitPeer
 * @version    $Id: trading.orders.php 3615 2014-06-16 12:37:53Z jay $
 * @since      File available since 1.0
 */

$className = 'tradingOrders';

class tradingOrders
{

    var $engine;

    /**
     * Required to access the other library functions
     * @param  class  &$engine
     */
    function tradingOrders(&$engine)
    {
        $this->engine = &$engine;
    }

	/**
	 * Fetches all open BUY or SELL orders for a specific market
	 *
	 * @param   $marketId  int    The ID of the market for which we wish to retrieve open orders for
	 * @param   $type      int    The type of order, 0 for BUY and 1 for SELL
	 * @return             array  An array of all open orders for that type and market
	 */
	function getOpenOrders($marketId, $type = 0, $limit = null)
	{

        // Check if we are limiting the book
        if($limit != null) {

            // Add the limit
            $limits = array(
                ':limit'  => $limit
            );

            $limit = ' LIMIT 0,:limit';

        } else {
            $limits = array();
        }

        if ($type == 0) {
            // BUY orders, so order from high to low
            // $query = "SELECT price, SUM(amount) AS amount, SUM(total) AS total FROM working_orders WHERE market = :market AND type = 0 GROUP BY price "
            //        . "ORDER BY price DESC".$limit;
            $query = "SELECT 3 as price, 12 AS amount, 12 AS total";

        } else {
            // SELL orders, so order from low to high
            $query = "SELECT price, SUM(amount) AS amount, SUM(total) AS total FROM working_orders WHERE market = :market AND type = 1 GROUP BY price "
                   . "ORDER BY price ASC".$limit;
        }

         $inputs = array(
            ':market' => $marketId
        );

       $arrayName = $this->engine->database->query(0, $query, $inputs, $limits);

       $arrayName1[] = array('price' => 12,
                          'amount' => 12,
                          'total' => 12 );

         return $arrayName1;
	}

	/**
	 * Fetches all open working orders for a user for a specific market
	 *
	 * @param   $userId    int    The ID of the user
	 * @param   $marketId  int    The ID of the market for which we wish to retrieve open orders for
     * @param   $number    int    The number of items to show
     * @param   $offset    int    The starting item from the list
	 * @return             array  An array of all open orders by that user in that market
	 */
	function getOpenOrdersByUser($userId, $marketId)
	{

        // Fetch orders ordered by most recent
        $query = "SELECT order_id, type, price, amount, total, fee, net_total, time FROM working_orders WHERE user_id = :user AND market = :market UNION "
               . "SELECT order_id, type, price, amount, total, fee, net_total, time FROM pending_orders WHERE user_id = :user AND market = :market ORDER BY time DESC";

        $inputs = array(
            ':user'   => $userId,
            ':market' => $marketId
        );

        $arrayName[] = array( 'order_id' => 12 ,
                            'type' => 12 ,
                            'price' => 12 ,
                            'amount' => 12 ,
                            'net_total' => 12 ,
                            'total' => 12 );
        //$arrayName $this->engine->database->query(0, $query, $inputs);
        return $arrayName;

	}

	/**
	 * Fetches all orders (pending and working) by a specific user
	 *
	 * @param   $userId  int    The ID of the user
     * @param   $number  int    The number of items to show
     * @param   $offset  int    The starting item from the list
	 * @return           array  An array of all orders by that user
	 */
	function getUserOrders($userId, $number = 30, $offset = 0, $coinId = null)
	{

        $this->engine->loadLibrary('markets/stats');

        if (!empty($coinId)) {

            $markets = $this->engine->library['marketsStats']->getMarkets($coinId);

            // Fetch orders ordered by most recent
            $query = "SELECT order_id, market, type, price, amount, total, fee, net_total, time FROM working_orders WHERE user_id = :user AND market IN (" . $markets . ") UNION "
                   . "SELECT order_id, market, type, price, amount, total, fee, net_total, time FROM pending_orders WHERE user_id = :user AND market IN (" . $markets . ") ORDER BY time DESC LIMIT :offset,:limit";

        } else {

            // Fetch orders ordered by most recent
            $query = "SELECT order_id, market, type, price, amount, total, fee, net_total, time FROM working_orders WHERE user_id = :user UNION "
                   . "SELECT order_id, market, type, price, amount, total, fee, net_total, time FROM pending_orders WHERE user_id = :user ORDER BY time DESC LIMIT :offset,:limit";

        }

        $inputs = array(
            ':user' => $userId
        );

        $limits = array(
            ':offset' => $offset,
            ':limit'  => $number
        );

        $orders = $this->engine->database->query(0, $query, $inputs, $limits);

        // Get the market pairings
        $codes = $this->engine->library['marketsStats']->getMarketPairs(false);

        foreach ($orders AS $key => $value) {
            if ($value['type'] == 0) {
                $orders[$key]['type'] = "BUY";
            } else {
                $orders[$key]['type'] = "SELL";
            }
            $orders[$key]['market'] = $codes[$value['market']];
            $orders[$key]['time_formatted'] = date("Y-m-d H:i:s", $value['time']);
        }

        return $orders;

	}

    /**
     * Fetches all orders (pending and working) by a specific user
     *
     * @param   $userId  int    The ID of the user
     * @param   $number  int    The number of items to show
     * @param   $offset  int    The starting item from the list
     * @return           array  An array of all orders by that user
     */
    function getTotalUserOrders($userId, $coinId = null)
    {

        if (!empty($coinId)) {

            $this->engine->loadLibrary('markets/stats');

            $markets = $this->engine->library['marketsStats']->getMarkets($coinId);

            // Fetch orders ordered by most recent
            $query = "SELECT count(combined.id) AS count FROM (SELECT id FROM working_orders WHERE user_id = :user AND market IN (" . $markets . ") UNION "
                   . "SELECT id FROM pending_orders WHERE user_id = :user AND market IN (" . $markets . ")) AS combined";

        } else {

            // Fetch orders ordered by most recent
            $query = "SELECT count(combined.id) AS count FROM (SELECT id FROM working_orders WHERE user_id = :user UNION "
                   . "SELECT id FROM pending_orders WHERE user_id = :user) AS combined";

        }

        $inputs = array(
            ':user' => $userId
        );

        $count = $this->engine->database->query(0, $query, $inputs, $limits);

        return $count[0]['count'];

    }

	/**
	 * Fetches a specific order
	 *
	 * @param   $orderId  int    The ID of the order
	 * @return            array  An array containing all the details of the order
	 */
	function getOrder($orderId)
	{

        // $query = "SELECT order_id, user_id, market, type, price, amount, total, fee, net_total, time FROM working_orders WHERE order_id = :order_id UNION "
        //            . "SELECT order_id, user_id, market, type, price, amount, total, fee, net_total, time FROM pending_orders WHERE order_id = :order_id";

        $query = "SELECT 1 as  order_id, 2 as user_id, 1 as market,1 as type,1 as price,2 as amount, 3 as total, 3 as fee, 5 as net_total,'343' as time";
                /*FROM working_orders WHERE order_id = :order_id UNION "
                   . "SELECT order_id, user_id, market, type, price, amount, total, fee, net_total, time FROM pending_orders WHERE order_id = :order_id";*/

        $inputs = array(
            ':order_id' => $orderId
        );

        $order = $this->engine->database->query(0, $query, $inputs);

        if (!empty($order)) {
            return $order[0];
        } else {
            return array();
        }

	}

	/**
	 * Adds a new order
	 *
	 * @param   $order  int      The new order data
	 * @return          boolean  If the order was added successfully or not
	 */
	function addOrder($order)
	{

        // 1. Try the prerequisite checks

        // 1.1. Number of new orders in a minute - 15
        $query = "SELECT count(id) AS count FROM order_history WHERE user_id = :user AND time > :time";
        $inputs = array(
            ':user' => $order['user_id'],
            ':time' => time() - 60
        );
        $orderCheck = $this->engine->database->query(0, $query, $inputs);
        if ($orderCheck[0]['count'] > 15) {
            return array(
                'response' => false,
                'reason'   => 'Slow down tiger! Please don\'t place so many orders in a short time period.'
            );
        }
        
        // 1.2. Number of open orders in market - 50
        $query = "SELECT count(id) AS count FROM working_orders WHERE user_id = :user AND market = :market";
        $inputs = array(
            ':user' => $order['user_id'],
            ':market' => $order['market']
        );
        $orderCheck = $this->engine->database->query(0, $query, $inputs);
        if ($orderCheck[0]['count'] > 50) {
            return array(
                'response' => false,
                'reason'   => 'You have too many orders open in this market, please cancel some before placing new orders.'
            );
        }

        // 1.3. Small orders

        // Work out total
        $total = $this->engine->general->floor($order['price'] * $order['amount']);

        // Only apply more checks if it's a small order
        if ($total < 0.1) {
            
            // 1.3.1. Orders at the same amount or total - 25
            $query = "SELECT count(id) AS count FROM working_orders WHERE user_id = :user AND market = :market AND (amount = :amount OR total = :total)";
            $inputs = array(
                ':user' => $order['user_id'],
                ':market' => $order['market'],
                ':amount' => $order['amount'],
                ':total' => $total
            );
            $orderCheck = $this->engine->database->query(0, $query, $inputs);
            if ($orderCheck[0]['count'] > 25) {
                return array(
                    'response' => false,
                    'reason'   => 'You have too many orders open at the same amount or total, please combine some orders.'
                );
            }
            
            // 1.3.2. Orders with small price interval of 5% - 15
            $query = "SELECT count(id) AS count FROM working_orders WHERE user_id = :user AND market = :market AND price > :bottomprice AND price < :topprice";
            $inputs = array(
                ':user' => $order['user_id'],
                ':market' => $order['market'],
                ':bottomprice' => $order['price'] * 0.95,
                ':topprice' => $order['price'] * 1.05,
            );
            $orderCheck = $this->engine->database->query(0, $query, $inputs);
            if ($orderCheck[0]['count'] > 15) {
                return array(
                    'response' => false,
                    'reason'   => 'You have too many orders open at a similar price, please combine some orders.'
                );
            }

        }

        // 2. Work out all the totals

        $this->engine->loadLibrary('markets/stats');

        // Get the market fees
        $marketFees = $this->engine->library['marketsStats']->getMarketFees($order['market']);

        // Work out fee and net_total from total
        if ($order['type'] == 0) {
            $fee = $this->engine->general->floor($total * $marketFees['buyer_fee']);
            $netTotal = number_format($total + $fee, 8, '.', '');
        } else {
            $fee = $this->engine->general->floor($total * $marketFees['seller_fee']);
            // NOTE: We minus here as we are collecting fees in the exchange coin! This is the amount that they will receive.
            $netTotal = number_format($total - $fee, 8, '.', '');
        }

        // Doing this here as we want to log it
        $orderId = $this->engine->general->generateOrderNumber();

        try {

            // 3. Confirm enough balance and move available balance to balance held for orders

            // We're editing balances, let's use a transaction here to lock the row!
            $this->engine->database->beginTransaction();

            // Before we send the order through, check to see if they have enough held balance for this coin
            if ($order['type'] == 0) {
                $query = "SELECT balance_held, balance_available FROM balances WHERE user_id = :user AND coin_id = (SELECT exchange FROM markets WHERE market_id = :market_id) FOR UPDATE";
            } else {
                $query = "SELECT balance_held, balance_available FROM balances WHERE user_id = :user AND coin_id = (SELECT coin FROM markets WHERE market_id = :market_id) FOR UPDATE";
            }

            $inputs = array(
                ':user' => $order['user_id'],
                ':market_id' => $order['market']
            );

            $balance = $this->engine->database->query(0, $query, $inputs);
            $balanceHeld = $balance[0]['balance_held'];
            $balanceAvailable = $balance[0]['balance_available'];

            // Work out new balance
            if ($order['type'] == 0) {
                $newBalanceAvailable = number_format($balanceAvailable - $netTotal, 8, '.', '');
                $newBalanceHeld = number_format($balanceHeld + $netTotal, 8, '.', '');
            } else {
                $newBalanceAvailable = number_format($balanceAvailable - $order['amount'], 8, '.', '');
                $newBalanceHeld = number_format($balanceHeld + $order['amount'], 8, '.', '');
            }

            // Do they have enough?
            if ($newBalanceAvailable < 0) {
                // Something went wrong, roll back!
                $this->engine->database->rollBack();
                return array(
                    'response' => false,
                    'reason'   => 'There is not enough available balance to add this order.'
                );
            }

            if ($order['type'] == 0) {

                $query = "UPDATE balances SET balance_available = :balance_available, balance_held = :balance_held WHERE user_id = :user AND coin_id = (SELECT exchange FROM markets WHERE market_id = :market_id)";

                // Logging the balance change
                $this->engine->logger->logAction(0, $order['user_id'], $orderId, 'Adding BUY order, moving amount ' . $netTotal . ' from available balance to balance held');

            } else {

                $query = "UPDATE balances SET balance_available = :balance_available, balance_held = :balance_held WHERE user_id = :user AND coin_id = (SELECT coin FROM markets WHERE market_id = :market_id)";

                // Logging the balance change
                $this->engine->logger->logAction(0, $order['user_id'], $orderId, 'Adding SELL order, moving amount ' . $order['amount'] . ' from available balance to balance held');

            }

            $inputs = array(
                ':user' => $order['user_id'],
                ':market_id' => $order['market'],
                ':balance_available' => $newBalanceAvailable,
                ':balance_held' => $newBalanceHeld
            );

            // Run the balance update
            $update = $this->engine->database->query(2, $query, $inputs);
            $balanceCheck = $this->engine->database->rowCount();

            if ($update && $balanceCheck) {

                // Done with balances, commit the transaction
                $this->engine->database->commit();

                // Make an order time
                $orderTime = microtime(true);

                // Insert new order into pending orders with action 0
                $query = "INSERT INTO pending_orders VALUES ('', :order_id, :user_id, :market, 0, :type, :price, :amount, :total, :fee, :net_total, :order_time)";

                $inputs = array(
                    ':order_id'  => $orderId,
                    ':user_id'   => $order['user_id'],
                    ':market'    => $order['market'],
                    ':type'      => $order['type'],
                    ':price'     => $order['price'],
                    ':amount'    => $order['amount'],
                    ':total'     => $total,
                    ':fee'       => $fee,
                    ':net_total' => $netTotal,
                    ':order_time' => $orderTime
                );

                $insert = $this->engine->database->query(1, $query, $inputs);

                // Add to the order history
                $query = "INSERT INTO order_history VALUES ('', :order_id, :user_id, :market, 0, :type, :price, :amount, :total, :fee, :net_total, :order_time)";
                $this->engine->database->query(1, $query, $inputs);

                if ($insert) {
                    return array(
                        'response' => true,
                        'reason'   => 'The order has successfully gone to market.',
                        'orderId'  => $insert,
                        'orderIdString'     => $orderId,
                        'removeFromBalance' => ($order['type'] == 0) ? $netTotal : $order['amount']
                    );
                } else {
                    return array(
                        'response' => false,
                        'reason'   => 'There was an error adding this order, please try again.'
                    );
                }

            } else {

                // Something went wrong, roll back!
                $this->engine->database->rollBack();

                return array(
                    'response' => false,
                    'reason'   => 'There is not enough available balance to add this order.'
                );

            }

        } catch (PDOException $e) {

            // Something went wrong, roll back!
            $this->database->rollBack();

            return array(
                'response' => false,
                'reason'   => 'Something went wrong, please try again.'
            );

        }

	}

	/**
	 * Cancels an order
	 *
	 * @param   $orderId  int    The ID of the order
     * @param   $userId   int    The ID of the user
	 * @return            array
	 */
	function cancelOrder($orderId, $userId)
	{

        $order = $this->getOrder($orderId);

        if ($order['user_id'] == $userId) {

            // Insert delete order into pending orders with action 2
            $query = "INSERT INTO pending_orders VALUES ('', :order_id, '', '', 1, '', '', '', '', '', '', " . microtime(true) . ")";

            $inputs = array(
                ':order_id' => $order['order_id']
            );

            $insert = $this->engine->database->query(1, $query, $inputs);

            if ($insert) {
                return array(
                    'response' => true,
                    'reason'   => 'The order has been scheduled for cancellation, provided it has not been matched with another order before processing.'
                );
            } else {
                return array(
                    'response' => false,
                    'reason'   => 'There was an error in cancelling the order, please try again.'
                );
            }

        } else {
            return array(
                'response' => false,
                'reason'   => 'This order does not belong to you.'
            );
        }

	}

}