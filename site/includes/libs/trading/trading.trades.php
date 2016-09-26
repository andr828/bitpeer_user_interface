<?php

/**
 * BitPeer Trading Trades Library
 *
 * Functions related to trades in the frontend
 *
 * @category   ArcticDesk
 * @package    Trading
 * @copyright  Copyright (c) 2014 BitPeer
 * @version    $Id: trading.trades.php 3335 2014-04-25 14:18:02Z jay $
 * @since      File available since 1.0
 */

$className = 'tradingTrades';

class tradingTrades
{

	var $engine;

    /**
     * Required to access the other library functions
     * @param  class  &$engine
     */
    function tradingTrades(&$engine)
    {
        $this->engine = &$engine;
    }

    /**
     * Fetches a limited number of recent trades for a specific market
     *
     * @param   $marketId  int    The ID of the market
     * @param   $number    int    The number of trades to return
     * @param   $offset    int    The starting item from the list
     * @return             array  An array of the last $number completed trades in the specified market
     */
    function getMarketTrades($marketId, $since = 0, $number = 30, $offset = 0)
	{

        // Fetch completed trades by most recent
        $query = "SELECT time, type, price, amount, total FROM trades WHERE market = :market AND time > :time ORDER BY time DESC LIMIT :offset, :limit";

        $inputs = array(
            ':market' => $marketId,
            ':time' => $since
        );

        $limits = array(
            ':offset' => $offset,
            ':limit'  => $number
        );

        $trades = $this->engine->database->query(0, $query, $inputs, $limits);

        

        $trades[] = $arrayName = array('time' => 12,'price'=>23,'type' => 1, 'amount' => 12,'total' => 12);
        foreach ($trades AS $key => $value) {
            $trades[$key]['type'] = ($value['type'] == 0 ? 'BUY' : 'SELL');
        }
        return $trades;

	}

    /**
     * Fetches a list of all trades by a specific user
     *
     * @param   $marketId  int    The ID of the user
     * @param   $number    int    The number of trades to return
     * @param   $offset    int    The starting item from the list
     * @return             array  An array of all completed trades by the user
     */
    function getUserTrades($userId, $number = 30, $offset = 0, $coinId = null, $market = null)
	{

        $this->engine->loadLibrary('markets/stats');

        if (!empty($coinId)) {

            if (!empty($market)) {

                $query = "SELECT * FROM trades WHERE (buyer_id = :user OR seller_id = :user) AND market = :market "
                       . "ORDER BY time DESC LIMIT :offset, :limit";

            } else {

                $markets = $this->engine->library['marketsStats']->getMarkets($coinId);

                // Fetch completed trades by most recent
                $query = "SELECT * FROM trades WHERE (buyer_id = :user OR seller_id = :user) AND market IN (" . $markets . ")"
                       . "ORDER BY time DESC LIMIT :offset, :limit";

            }

        } else {

            // Fetch completed trades by most recent
            $query = "SELECT * FROM trades WHERE (buyer_id = :user OR seller_id = :user) "
                   . "ORDER BY time DESC LIMIT :offset, :limit";

        }

        $inputs = array(
            ':user'   => $userId
        );

        if (!empty($market)) {
            $inputs['market'] = $market;
        }

        $limits = array(
            ':offset' => $offset,
            ':limit'  => $number
        );

       // $trades = $this->engine->database->query(0, $query, $inputs, $limits);
        $trades[] = $arrayName = array(
                                        'buyer_id' => 12,
                                        'type' => '',
                                        'fee' => 12,
                                        'buyer_fee' => 12,
                                        'buyer_fee' => 12,
                                        'seller_fee' => 12,
                                        'net_total' => 12,
                                        'buyer_net_total' => 12,
                                        'seller_net_total' => 12,
                                        'order_id' => 12,
                                        'buy_order_id' => 12,
                                        'sell_order_id' => 12 );
        // Get the market pairings
        $codes = $this->engine->library['marketsStats']->getMarketPairs(false);

        foreach ($trades AS $key => $value) {
            if ($value['buyer_id'] == $userId) {
                $trades[$key]['type'] = "BUY";
                $trades[$key]['fee'] = $value['buyer_fee'];
                unset($trades[$key]['buyer_fee']);
                unset($trades[$key]['seller_fee']);
                $trades[$key]['net_total'] = $value['buyer_net_total'];
                unset($trades[$key]['buyer_net_total']);
                unset($trades[$key]['seller_net_total']);
                $trades[$key]['order_id'] = $value['buy_order_id'];
                unset($trades[$key]['buy_order_id']);
                unset($trades[$key]['sell_order_id']);
            } else {
                $trades[$key]['type'] = "SELL";
                $trades[$key]['fee'] = $value['seller_fee'];
                unset($trades[$key]['buyer_fee']);
                unset($trades[$key]['seller_fee']);
                $trades[$key]['net_total'] = $value['seller_net_total'];
                unset($trades[$key]['buyer_net_total']);
                unset($trades[$key]['seller_net_total']);
                $trades[$key]['order_id'] = $value['sell_order_id'];
                unset($trades[$key]['buy_order_id']);
                unset($trades[$key]['sell_order_id']);
            }
            unset($trades[$key]['id']);
            unset($trades[$key]['buyer_id']);
            unset($trades[$key]['seller_id']);
            $trades[$key]['market'] = $codes[$value['market']];
            $trades[$key]['time_formatted'] = date("Y-m-d H:i:s", $value['time']);
        }

        return $trades;

	}

    /**
     * Fetches the total number of trades done by a user
     *
     * @param   $marketId  int  The ID of the user
     * @return             int  Number of completed trades
     */
    function getTotalUserTrades($userId, $coinId = null)
    {

        if (!empty($coinId)) {

            $this->engine->loadLibrary('markets/stats');

            $markets = $this->engine->library['marketsStats']->getMarkets($coinId);

            // Fetch completed trades by most recent
            $query = "SELECT COUNT(id) as count FROM trades WHERE (buyer_id = :user OR seller_id = :user) AND market IN (" . $markets . ")";

        } else {

            // Fetch completed trades by most recent
            $query = "SELECT COUNT(id) as count FROM trades WHERE (buyer_id = :user OR seller_id = :user)";

        }

        $inputs = array(
            ':user'   => $userId
        );

        $count = $this->engine->database->query(0, $query, $inputs);

        return $count[0]['count'];

    }

	/**
	 * Fetches a specific trade
	 *
	 * @param   $tradeId  int    The ID of the trade
	 * @return            array  An array containing all the details of the trade
	 */
	function getTrade($tradeId)
	{

        // Fetch trade from trade ID
        $query = "SELECT * FROM trades WHERE trade_id = :id";

        $inputs = array(
            ':id' => $tradeId
        );

        $trade = $this->engine->database->query(0, $query, $inputs);

        if (!empty($trade)) {
            return $trade[0];
        } else {
            return array();
        }

	}

    function getUserTradesSince($userId, $time)
    {

        // Fetch trades since time
        $query = "SELECT trades.price, trades.amount, coins.code FROM trades, markets, coins WHERE (trades.buyer_id = :user_id OR trades.seller_id = :user_id) AND markets.market_id = trades.market AND coins.id = markets.coin AND trades.time > :time";

        $inputs = array(
            ':user_id' => $userId,
            ':time'    => $time
        );

        $trades = $this->engine->database->query(0, $query, $inputs);

        return $trades;

    }

    /**
     * Fetches the first trade from a market
     *
     * @param   $marketId  int    The ID of the trade
     * @return             array  An array containing all the details of the trade
     */
    function getFirstMarketTrade($marketId)
    {

        // Fetch trade from trade ID
        $query = "SELECT price, time FROM trades WHERE market = :market_id ORDER BY time ASC LIMIT 1";

        $inputs = array(
            ':market_id' => $marketId
        );

        $trade = $this->engine->database->query(0, $query, $inputs);

        if (!empty($trade)) {
            return $trade[0];
        } else {
            return array();
        }

    }


}