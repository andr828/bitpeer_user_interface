<?php

/**
 * BitPeer Markets Voting Library
 *
 * Functions related to coin voting in the frontend
 *
 * @category   ArcticDesk
 * @package    Markets
 * @copyright  Copyright (c) 2014 BitPeer
 * @version    $Id: $
 * @since      File available since 1.0
 */
$className = 'marketsVoting';

class marketsVoting {

    var $engine;

    /**
     * Required to access the other library functions
     * @param  class  &$engine
     */
    function marketsVoting(&$engine) {
        $this->engine = &$engine;
    }

    /**
     *
     * Function to return a list of coins being voted on
     *
     */
    function getCoins() {

        $coins = $this->engine->database->query(0, "SELECT * FROM vote_coins WHERE active = '1' ORDER BY votes+bribe_votes DESC");

        return $coins;
    }

    /**
     *
     * Function to add a vote to a coin
     *
     */
    function addVote($coin) {

        $query = "SELECT ip FROM bad_ip WHERE ip = :ip";
        $inputs = array(
            ':ip' => $this->getIp()
        );
        $banned = $this->engine->database->query(0, $query, $inputs);

        if (isset($banned[0])) {
            // Tor user, not allowing vote
            return false;
        }

        $query = "SELECT first_vote, count FROM vote_votes WHERE user_id = :id";
        $inputs = array(
            ':id' => $this->engine->auth->user['id']
        );
        $votes = $this->engine->database->query(0, $query, $inputs);

        // Check if there is a row
        if(count($votes)) {

            // Check if the user has hit 1 votes already
            if($votes[0]['count'] >= 1) {

                // Check if the user first voted less than an hour ago
                $voteTime = time() - $votes[0]['first_vote'];
                if($voteTime >= 3600) {

                    // They first voted over an hour ago, so we're good!
                    $this->updateVote($coin, true);

                    // Succcessful vote
                    return true;

                } else {

                    // Naughty user
                    return false;
                }
            } else {
                // Add a vote - user hasn't hit 1!
                $this->updateVote($coin, false);

                // Succcessful vote
                return true;
            }

        } else {
            // Add a vote
            $this->insertVote($coin);

            // Succcessful vote
            return true;
        }
    }

    function insertVote($coin) {
        // User hasn't voted yet, add a row
        $query = "INSERT INTO vote_votes VALUES ('', :id, :ip, '" . time() . "', '1', :coin, '1')";
        $inputs = array(
            ':id' => $this->engine->auth->user['id'],
            ':ip' => $this->getIp(),
            ':coin' => $coin
        );
        $this->engine->database->query(1, $query, $inputs);

        $query = "UPDATE vote_coins SET votes = votes+1 WHERE id = :coin";
        $inputs = array(
            ':coin' => $coin
        );
        $this->engine->database->query(1, $query, $inputs);
    }

    function updateVote($coin, $resetTime) {

        // Check if we are resetting it
        if ($resetTime == true) {
            $resetTime = "SET ip=:ip, count=1, totalCount=totalCount+1, last_coin = :coin, first_vote = '".time()."'";
        } else {
            $resetTime = 'SET ip=:ip, count=count+1, totalCount=totalCount+1, last_coin = :coin';
        }

        // User hasn't voted yet, add a row
        $query = "UPDATE vote_votes $resetTime WHERE user_id = :id";
        $inputs = array(
            ':id' => $this->engine->auth->user['id'],
            ':ip' => $this->getIp(),
            ':coin' => $coin
        );
        $this->engine->database->query(2, $query, $inputs);

        $query = "UPDATE vote_coins SET votes=votes+1 WHERE id = :coin";
        $inputs = array(
            ':coin' => $coin
        );
        $this->engine->database->query(2, $query, $inputs);

    }


    /**
     * Determines client IP address
     *
     * Retrieves the best guess of the client's actual IP address.
     * Takes into account numerous HTTP proxy headers due to variations
     * in how different ISPs handle IP addresses in headers between hops.
     *
     * @return string Client IP address or empty string
     */
    public function getIp()
    {
        // check for shared internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        // check for IPs passing through proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // check if multiple ips exist in var
            $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($iplist as $ip) {
                if ($this->validate_ip($ip)) {
                    return $ip;
                }
            }
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_X_FORWARDED']))
            return $_SERVER['HTTP_X_FORWARDED'];
        if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
            return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && $this->validate_ip($_SERVER['HTTP_FORWARDED_FOR']))
            return $_SERVER['HTTP_FORWARDED_FOR'];
        if (!empty($_SERVER['HTTP_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_FORWARDED']))
            return $_SERVER['HTTP_FORWARDED'];

        // return unreliable ip since all else failed
        return (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    /**
    * Ensures an IP address is both a valid IP and does not fall within a private network range.
    *
    * @param string ip Input IP
    */
    private function validate_ip($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4
                                                | FILTER_FLAG_IPV6
                                                | FILTER_FLAG_NO_PRIV_RANGE
                                                | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        } else {
            return true;
        }
    }

}
