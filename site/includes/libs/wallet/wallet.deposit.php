<?php

/**
 * BitPeer Wallet Deposit Library
 *
 * Functions related to wallet deposits in the frontend
 *
 * @category   ArcticDesk
 * @package    Wallet
 * @copyright  Copyright (c) 2014 BitPeer
 * @version    $Id: wallet.deposit.php 3205 2014-04-10 15:06:56Z yatesj $
 * @since      File available since 1.0
 */

$className = 'walletDeposit';

class walletDeposit
{

	var $engine;

    /**
     * Required to access the other library functions
     * @param  class  &$engine
     */
    function walletDeposit(&$engine)
    {
        $this->engine = &$engine;
    }

    /**
     * Fetches a list of deposits made by the specified user, with option to include/exclude
     * pending or confirmed deposits.
     *
     * @param   $userId     int      The ID of the user
     * @param   $pending    boolean  True to include pending deposits, false otherwise
     * @param   $confirmed  boolean  True to include confirmed deposits, false otherwise
     * @return              array    An array of the deposits made by the user
     */
    function getDeposits($userId, $number = 30, $offset = 0, $coinId = null)
	{

        if (!empty($coinId)) {

            // Fetch orders ordered by most recent
            $query = "SELECT coins.code, transactions.address, transactions.amount, transactions.txid, transactions.time, transactions.pending, transactions.confirms, coins.req_confirms FROM transactions, coins WHERE transactions.type = 0 AND transactions.user_id = :user AND transactions.coin_id = :coin AND transactions.coin_id = coins.id ORDER BY time DESC LIMIT :offset,:limit";

            $inputs = array(
                ':user' => $userId,
                ':coin' => $coinId
            );

        } else {

            // Fetch orders ordered by most recent
            $query = "SELECT coins.code, transactions.address, transactions.amount, transactions.txid, transactions.time, transactions.pending, transactions.confirms, coins.req_confirms FROM transactions, coins WHERE transactions.type = 0 AND transactions.user_id = :user AND transactions.coin_id = coins.id ORDER BY time DESC LIMIT :offset,:limit";

            $inputs = array(
                ':user' => $userId
            );

        }

        $limits = array(
            ':offset' => $offset,
            ':limit'  => $number
        );

        $deposits = $this->engine->database->query(0, $query, $inputs, $limits);

        foreach ($deposits AS $key => $value) {
            $deposits[$key]['time_formatted'] = date("Y-m-d H:i:s", $value['time']);
            if ($value['pending'] == 0) {
                $deposits[$key]['status'] = "CONFIRMED";
            } else {
                $deposits[$key]['status'] = "PENDING";
            }
        }

        return $deposits;

	}

    /**
     * Fetches a list of deposits made by the specified user, with option to include/exclude
     * pending or confirmed deposits.
     *
     * @param   $userId     int      The ID of the user
     * @param   $pending    boolean  True to include pending deposits, false otherwise
     * @param   $confirmed  boolean  True to include confirmed deposits, false otherwise
     * @return              array    An array of the deposits made by the user
     */
    function getTotalDeposits($userId, $coinId = null)
    {

        if (!empty($coinId)) {

            // Fetch orders ordered by most recent
            $query = "SELECT COUNT(id) AS count FROM transactions WHERE type = 0 AND user_id = :user AND coin_id = :coin";

            $inputs = array(
                ':user' => $userId,
                ':coin' => $coinId
            );

        } else {

            // Fetch orders ordered by most recent
            $query = "SELECT COUNT(id) AS count FROM transactions WHERE type = 0 AND user_id = :user";

            $inputs = array(
                ':user' => $userId
            );

        }

        $count = $this->engine->database->query(0, $query, $inputs);

        return $count[0]['count'];

    }

    function getAddress($userId, $coinId)
    {

        $query = "SELECT address_address FROM addresses WHERE address_balance_id = (SELECT id FROM balances WHERE user_id = :user AND coin_id = :coin) ORDER BY address_id DESC";

        $inputs = array(
            ':user' => $userId,
            ':coin' => $coinId
        );

        $address = $this->engine->database->query(0, $query, $inputs);

        if (!empty($address)) {
            return $address[0]['address_address'];
        } else {
            return null;
        }

    }

	/**
     * Generates a new deposit address for a given coin
     *
     * @param   $userId  int     The ID of the user
     * @param   $coin    int     The ID of the coin
     * @return           string  The new deposit address
     */
    function requestNewAddress($userId, $coinId)
	{

        // See if the user has a balance row already
        $query = "SELECT id FROM balances WHERE user_id = :user AND coin_id = :coin";

        $inputs = array(
            ':user' => $userId,
            ':coin' => $coinId
        );

        $balanceId = $this->engine->database->query(0, $query, $inputs);

        if (!empty($balanceId)) {
            // Store the balance ID for use later
            $balanceId = $balanceId[0]['id'];
        } else {
            // Add a balance row
            $query = "INSERT INTO balances (user_id, coin_id) VALUES (:user, :coin)";
            // Save ID of new row
            $balanceId = $this->engine->database->query(1, $query, $inputs);
        }

        // Check when the user last generated an address
        $address = $this->engine->database->query(0, "SELECT address_created FROM addresses WHERE address_balance_id = :balance_id ORDER BY address_id DESC", array(':balance_id' => $balanceId));

        if (!empty($address)) {
           
           // Check if we have a latest address time
           if($address[0]['address_created'] != '') {

                // Generate the earliest time
                $earliestTime = time() - 900;

                // Check if the address is too young
                if($address[0]['address_created'] > $earliestTime) {
                    return null;
                }
           }
        }

        // Fetch a free address from the store
        $query = "SELECT id, address FROM address_store WHERE coin_id = :coin ORDER BY id LIMIT 1";

        $inputs = array(
            ':coin' => $coinId
        );

        $newAddress = $this->engine->database->query(0, $query, $inputs);

        // Delete address from store
        $query = "DELETE FROM address_store WHERE id = :id";

        $inputs = array(
            ':id' => $newAddress[0]['id']
        );

        $delete = $this->engine->database->query(3, $query, $inputs);

        // Save in addresses with user ID
        if ($delete) {
            $query = "INSERT INTO addresses (address_balance_id, address_address, address_created) VALUES (:balance_id, :address, '".time()."')";

            $inputs = array(
                ':balance_id' => $balanceId,
                ':address' => $newAddress[0]['address']
            );

            $insert = $this->engine->database->query(1, $query, $inputs);
        }

        if ($insert) {
            return $newAddress[0]['address'];
        } else {
            return null;
        }

	}

    function getNumberOfConfirms($coinId)
    {

        $query = "SELECT req_confirms FROM coins WHERE id = :coin";

        $inputs = array(
            ':coin' => $coinId
        );

        $confirms = $this->engine->database->query(0, $query, $inputs);

        return $confirms[0]['req_confirms'];

    }

}