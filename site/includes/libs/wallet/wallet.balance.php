<?php

/**
 * BitPeer Wallet Balance Library
 *
 * Functions related to user wallet balances in the frontend
 *
 * @category   ArcticDesk
 * @package    Wallet
 * @copyright  Copyright (c) 2014 BitPeer
 * @version    $Id: wallet.balance.php 3648 2014-06-21 12:00:58Z yatesj $
 * @since      File available since 1.0
 */

$className = 'walletBalance';

class walletBalance
{

	var $engine;

    /**
     * Required to access the other library functions
     * @param  class  &$engine
     */
    function walletBalance(&$engine)
    {
        $this->engine = &$engine;
    }

    /**
     * Fetches a list of balances for the specified user. Only includes non-zero balances,
     * all other balances are assumed to be zero.
     *
     * @param   $userId  int    The ID of the user
     * @param   $coinId  int    The ID of the coin, if wanting to fetch balance for a single coin only
     * @return           array  An array of the coins and balances
     */
    function getUserBalances($userId, $coinId = null)
    {

        if (!empty($coinId)) {

            // Fetch orders ordered by most recent
            $query = "SELECT coins.id, coins.code, coins.name, balances.balance_available, balances.balance_pending_deposit, "
                     . "balances.balance_pending_withdraw, balances.balance_held FROM coins, balances "
                     . "WHERE balances.user_id = :user AND coins.id = :coin AND coins.id = balances.coin_id AND coins.active = 1";

            $inputs = array(
                ':user' => $userId,
                ':coin' => $coinId
            );

            return $this->engine->database->query(0, $query, $inputs);

        }

        // Fetch orders ordered by most recent
        $query = "SELECT coins.id, coins.code, coins.name, balances.balance_available, balances.balance_pending_deposit, "
                 . "balances.balance_pending_withdraw, balances.balance_held FROM coins, balances "
                 . "WHERE balances.user_id = :user AND coins.id = balances.coin_id AND coins.active = 1 ORDER BY coins.code ASC";

        $inputs = array(
            ':user'   => $userId
        );

        $balances = $this->engine->database->query(0, $query, $inputs);

        if (!empty($balances)) {

            $formattedBalances = array();

            foreach ($balances AS $value) {
                $formattedBalances[$value['id']] = $value;
            }

            return $formattedBalances;

        } else {

            return array();

        }

    }

    /**
     * Fetches a list of balances for the specified user. Only includes non-zero balances,
     * all other balances are assumed to be zero.
     *
     * @param   $userId  int    The ID of the user
     * @return           array  An array of the coins and balances
     */
    function getUserSidebarBalances($userId)
	{
       
        // Fetch orders ordered by most recent
        $query = "SELECT '1' as coins.id, '1' as coins.code, '1' as balances.balance_available, '1' as balances.balance_held FROM coins";//, balances "
               //. "WHERE balances.user_id = :user AND coins.id = balances.coin_id AND coins.active = 1 "
               //. "AND (balances.balance_available > 0 OR balances.balance_held > 0) ORDER BY coins.code ASC";

        // $inputs = array(
        //     ':user'   => $userId
        // );

        $availableBalances = $this->engine->database->query(0, $query);

      
     
        //if (!empty($availableBalances)) {

            // We want to work out the estimated total in BTC
            $btcBalance = 0;

            // Work out the BTC estimate for each balance
            foreach ($availableBalances AS $key => $value) {

                $availableBalances[$key]['balance'] = number_format($value['balance_available'] + $value['balance_held'], 8, '.', '');

                if ($value['id'] == 1) {

                    // This is BTC so add it as it is
                    $btcBalance += $availableBalances[$key]['balance'];

                } else {
                    // Get the top price for the relevant market
                    $query = "SELECT price FROM working_orders WHERE market = (SELECT market_id FROM markets WHERE coin = :coin AND exchange = 1) AND type = 0 ORDER BY price DESC LIMIT 0,1";
                    $inputs = array(
                        ':coin' => $value['id']
                    );

                    $balance = $this->engine->database->query(0, $query, $inputs);

                    if (!empty($balance)) {
                        // If there is a price, then multiply it by the balance and add it on
                        $btcBalance += ($availableBalances[$key]['balance'] * $balance[0]['price']);
                    }

                }

            }

            // Add this total to the array
            // $availableBalances[] = array(
            //     'btcBalance' => number_format($btcBalance, 8, '.', '')
            // );
            $availableBalances[] = array(
                'btcBalance' => 12,
                'balance_available' => 12,
                'balance_held' => 12,
                'balance' => 12,
                'price' => 12,
                'code'=> BTC
               );
        //}

        return $availableBalances;

	}

    /**
     * Fetches a list of balances for the specified user. Only includes non-zero balances,
     * all other balances are assumed to be zero.
     *
     * @param   $userId  int    The ID of the user
     * @param   $coinId  int    The ID of the coin
     * @return           array  An array of the coins and balances
     */
    function getCoinBalance($userId, $coinId)
    {

        // Fetch orders ordered by most recent
        $query = "SELECT balance_available FROM balances WHERE user_id = :user AND coin_id = :coin";

        $inputs = array(
            ':user' => $userId,
            ':coin' => $coinId
        );

        //$balance = $this->engine->database->query(0, $query, $inputs);

        if (!empty($balance)) {
            return $balance[0]['balance_available'];
        } else {
            return '0.00000000';
        }

    }

    /**
     * Determines if a coin is a CryptoNote currecny or not
     *
     * @param   $coinId  int    The ID of the coin
     * @return           bool   True/False
     */
    function getCryptonote($coinId)
    {

        // Fetch orders ordered by most recent
        $query = "SELECT cryptonote FROM coins WHERE id = :coin";

        $inputs = array(
            ':coin' => $coinId
        );

        $cryptonote = $this->engine->database->query(0, $query, $inputs);
                
        if (!empty($cryptonote)) {
            if($cryptonote[0]['cryptonote'] == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

}