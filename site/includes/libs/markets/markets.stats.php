<?php

/**
 * BitPeer Markets Stats Library
 *
 * Functions related to market stats in the frontend
 *
 * @category   ArcticDesk
 * @package    Markets
 * @copyright  Copyright (c) 2014 BitPeer
 * @version    $Id: markets.stats.php 3843 2014-07-11 09:45:46Z yatesj $
 * @since      File available since 1.0
 */
$className = 'marketsStats';

class marketsStats
{

    var $engine;
    var $count;

    /**
     * Required to access the other library functions
     * @param  class  &$engine
     */
    function marketsStats(&$engine)
    {
        $this->engine = &$engine;
    }

    function getLatestPrices($exchange = 1)  
    {

        // Fetch completed trades by most recent
        $query = "SELECT markets.market_id, coins.code, markets.last_price, markets.yesterday_price FROM markets, coins WHERE markets.active = 1 AND markets.coin = coins.id AND markets.exchange = :exchange ORDER BY coins.code ASC";

        $inputs = array(
            ':exchange' => $exchange
        );

        $markets = $this->engine->database->query(0, $query, $inputs);



        foreach ($markets AS $key => $value) {

            if ($value['yesterday_price'] == "0") {
                // No price yesterday, so set change to 0
                $change = '0.00';
            } else {
                // Get the difference
                $difference = $value['last_price'] - $value['yesterday_price'];
                // Divide the difference by yesterdays price and multiply by 100 for the percentage
                $change = number_format((float) ($difference / $value['yesterday_price']) * 100, 2, '.', '');
                if ($change > 1000) {
                    $change = ">+999.99";
                } else if ($change > 0) {
                    $change = "+" . $change;
                }
            }

            $markets[$key]['change'] = $change;
        }
        $markets[] =  array(
            'market_id' => 1,
            'code' => BTC,
            'last_price' => 12,
            'yesterday_price' => 12,
            'change' => 0
        );
        return $markets;
    }

    function getOverallStats()
    {

        // First we check that our cache server is available
        if ($this->engine->memcache->cacheAvailable == true) {

            // Get the data
            return $this->engine->memcache->get('marketData');

        } else {

            // Return empty array
            return array();

        }


    }

    function getCoins()
    {
       
        // Query to get all coins
        //$query = "SELECT id, code, name from coins WHERE active = 1 ORDER BY code";
        $query = "SELECT id, code from coins";
        return $this->engine->database->query(0, $query);
        
    }

    function getCoinFromCode($code)
    {
           
        if ($this->engine->auth->user['id'] == '1' || $this->engine->auth->user['id'] == '2') {
            // Query to find the coin ID from the code, include inactive ones as it is for our test accounts
            $query = "SELECT id from coins WHERE code = :code";
        } else {
            // Query to find the coin ID from the code
            $query = "SELECT id from coins WHERE code = :code AND active = 1";
        }

        $inputs = array(
            ':code' => $code
        );

        $coin = $this->engine->database->query(0, $query, $inputs);

        if (!empty($coin)) {
            return $coin[0]['id'];
        } else {
            return 1;//false;
        }
    }

    function getCoinNameFromCode($code)
    {

        // Query to find the coin ID from the code
        $query = "SELECT name from coins WHERE code = :code";

        $inputs = array(
            ':code' => $code
        );

        $coin = $this->engine->database->query(0, $query, $inputs);

        if (!empty($coin)) {
            return $coin[0]['name'];
        } else {
            return 'BTC';//false;
        }
    }

    function getMarketPairs($active = true)
    {

        if ($active) {
    
            $query = "SELECT markets.market_id, coin1.code, coin2.code as code2 FROM coins coin1, coins coin2, markets WHERE coin1.id = markets.coin AND coin2.id = markets.exchange AND markets.active = 1";

        } else {

            $query = "SELECT markets.market_id, coin1.code, coin2.code as code2 FROM coins coin1, coins coin2, markets WHERE coin1.id = markets.coin AND coin2.id = markets.exchange";

        }

        $codes = $this->engine->database->query(0, $query);

        $finalCodes = array();

        foreach ($codes AS $key => $value) {
            $finalCodes[$value['market_id']] = $value['code'] . '/' . $value['code2'];
        }

        return $finalCodes;
    }

    function getMarkets($coinId)
    {

        // Query to find the coin ID from the code
        $query = "SELECT market_id from markets WHERE coin = :coin OR exchange = :coin";

        $inputs = array(
            ':coin' => $coinId
        );

        $markets = $this->engine->database->query(0, $query, $inputs);

        $result = '';

        foreach ($markets AS $value) {
            $result .= $value['market_id'] . ',';
        }

        return rtrim($result, ",");
    }

    function getMarketFees($marketId)
    {

        // Query to find the coin ID from the code
        $query = "SELECT buyer_fee, seller_fee from markets WHERE market_id = :market_id";

        $inputs = array(
            ':market_id' => $marketId
        );

        $fees = $this->engine->database->query(0, $query, $inputs);
            
            $fees[] = array(
                'buyer_fee' => 12 ,
                'seller_fee' => 34,
            );
        return $fees[0];
    }

    function getSummary($coin = null, $exchange = null)
    {

        // Get the MD from cache
        if ($this->engine->memcache->cacheAvailable == true) {

            // Pull it down
            $marketData = $this->engine->memcache->get('marketData');
            $marketDataPrices = $this->engine->memcache->get('marketDataPrices');

        } else {

            // Something is fubar
            $marketData = array();
            $marketDataPrices = array();
        }

        // Loop the market data
        foreach ($marketData AS $key => $value) {

            // Update the last_price from the live array
            if(isset($marketDataPrices[$value['market_id']])) {
                $marketData[$key]['last_price'] = $marketDataPrices[$value['market_id']];
            }

            // Handle the price change
            if ($value['yesterday_price'] == "0") {
                // No price yesterday, so set change to 0
                $marketData[$key]['change'] = '0.00';
            } else {
                // Get the difference
                $difference = $value['last_price'] - $value['yesterday_price'];
                // Divide the difference by yesterdays price and multiply by 100 for the percentage
                $marketData[$key]['change'] = number_format((float) ($difference / $value['yesterday_price']) * 100, 2, '.', '');
                if ($value['change'] > 1000) {
                    $marketData[$key]['change'] = ">+999.99";
                } else if ($change > 0) {
                    $marketData[$key]['change'] = "+" . $marketData[$key]['change'];
                }
            }

            // Rename the high
            $marketData[$key]['24hhigh'] = $value['high'];
            unset($marketData[$key]['high']);

            // Rename the low
            $marketData[$key]['24hlow'] = $value['low'];
            unset($marketData[$key]['low']);

            // Rename the vol
            $marketData[$key]['24hvol'] = $value['volume'];
            unset($marketData[$key]['volume']);

            if ($value['exchange'] == 'LTC') {
                // LTC market, work out BTC volume
                $marketData[$key]['24hvol'] = number_format($value['volume'] * $marketDataPrices[19], 3, '.', '');
            }

            $marketData[$key]['top_bid'] = $value['bid'];
            unset($marketData[$key]['bid']);

            $marketData[$key]['top_ask'] = $value['ask'];
            unset($marketData[$key]['ask']);
        }

        return $marketData;
    }

    function getActiveMarkets($limit = 30, $start = 0, $sort = null, $dir = 'des')
    {

        // Get the markets data
        $markets = $this->getSummary();

        // Save the number of markets
        $this->count = count($markets);

        // Fresh array for sorting
        $sorted = array();

        // Build an array with the item we want to sort
        if ($sort == '24hvol') {

            foreach ($markets as $key => $row) {
                $sorted[$key] = $row['24hvol'];
            }

        } else if ($sort == 'code') {

            foreach ($markets as $key => $row) {
                $sorted[$key] = $row['code'];
            }

        } else if ($sort == 'coin') {

            foreach ($markets as $key => $row) {
                $sorted[$key] = strtolower($row['coin']);
            }

        } else if ($sort == 'last_price') {

            foreach ($markets as $key => $row) {
                $sorted[$key] = $row['last_price'];
            }

        } else if ($sort == 'change') {

            foreach ($markets as $key => $row) {
                $sorted[$key] = $row['change'];
            }

        } else if ($sort == '24hhigh') {

            foreach ($markets as $key => $row) {
                $sorted[$key] = $row['24hhigh'];
            }

        } else if ($sort == '24hlow') {

            foreach ($markets as $key => $row) {
                $sorted[$key] = $row['24hlow'];
            }

        } else if ($sort == 'top_bid') {

            foreach ($markets as $key => $row) {
                $sorted[$key] = $row['top_bid'];
            }

        } else if ($sort == 'top_ask') {

            foreach ($markets as $key => $row) {
                $sorted[$key] = $row['top_ask'];
            }

        }


        // Now sort it
        if ($dir == 'asc') {
            array_multisort($sorted, SORT_ASC, $markets);
        } else {
            array_multisort($sorted, SORT_DESC, $markets);
        }

        $markets = array_slice($markets, $start, $limit);

        return $markets;

    }

    function getTotalMarkets() {

        return $this->count;

    }

}