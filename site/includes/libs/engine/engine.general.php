<?php

/**
 * BitPeer Engine Logger Library
 *
 * Functions for logging actions and errors on the frontend
 *
 * @category   ArcticDesk
 * @package    Engine
 * @copyright  Copyright (c) 2014 BitPeer
 * @version    $Id: engine.init.php 2592 2014-01-28 23:41:56Z jay $
 * @since      File available since 1.0
 */

$className = 'engineGeneral';

class engineGeneral
{

    var $engine;

    /**
     * Required to access the other library functions
     * @param  class  &$engine
     */
    function engineGeneral(&$engine)
    {
        $this->engine = &$engine;
    }

    /**
     * Provides the floor of an 8 decimal number
     *
     * @return  float
     */
    function floor($number) {
        $number = $number * 100000000;
        $number = floor($number);
        return number_format($number / 100000000, 8, '.', '');
    }

    /**
     * Genereates a random ID for orders and trades
     *
     * @return  string  A unique random string in the form ABCD-12345678
     */
    function generateOrderNumber()
    {

        // Get the ticket format
        $ticketNumber = "%L{4}-%N{8}";

        // Find the repeat patterns (maximum number of repeats is set to 20)
        preg_match_all("/(\%[YymdjgGhHisLN])(?:\{([1-9]{1,20})\})?/", $ticketNumber, $m);

        // Repeat the valid parts of the string (%L{2} == %L%L)
        for ($i = 0; $i < count($m[1]); $i++) {

            $char = $m[1][$i];
            $repetitions = empty($m[2][$i]) ? 1 : $m[2][$i];

            // Process number / letter repetitions
            $ticketNumber = str_replace($m[0][$i], str_repeat($char, $repetitions), $ticketNumber);

            if ($char == "%L") {
                // Replace each %L with a random letter (limited to number of repetitions: $repetitions)
                $ticketNumber = preg_replace_callback("/\%L/", array($this, 'generateRandomLetter'), $ticketNumber, $repetitions);
            } else if ($char == "%N") {
                // Replace each %N with a random number (limited to number of repetitions $repetitions)
                $ticketNumber = preg_replace_callback("/\%N/", array($this, 'generateRandomNumber'), $ticketNumber, $repetitions);
            }

        }

        // Perform a check to see if the ticket number already exists
        $query = "SELECT id FROM working_orders WHERE order_id = :id";

        $inputs = array(
            ':id' => $ticketNumber
        );

        $numberExists = $this->engine->database->query(0, $query, $inputs);

        // Check if it does exist
        if (!empty($numberExists)) {
            // Generate another number
            return $this->generateOrderNumber();
        } else {
            // Return what we have
            return $ticketNumber;
        }

    }

    /**
     * Function to generate a single random letter
     * @return char Random letter from uppercase alphabet
     */
    function generateRandomLetter()
    {
        // Seed the random number generator
        srand((double) microtime() * 1000000);
        $letterSeed = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // Get random letter from seed
        $charidx = rand() % strlen($letterSeed);
        return substr($letterSeed, $charidx, 1);
    }

    /**
     * Function to generate a single random digit
     * @return string A random digit (0 - 9);
     */
    function generateRandomNumber()
    {
        return rand(0, 9);
    }

    /**
     * Function to translate URL params to a market ID
     */
    function getMarketIDFromUrl($coin = null, $exchange = null)
    {
        // Check if we are using the function parpams
        if ($coin != null && $exchange != null) {
            $_REQUEST['coin'] = $coin;
            $_REQUEST['exchange'] = $exchange;
        }

        // Set a default market if visiting home page or click on trading
        if (empty($_REQUEST) || (isset($_REQUEST['view']) && $_REQUEST['view'] == 'market' && !isset($_REQUEST['coin']) && !isset($_REQUEST['exchange']))) {
            
            // Get the display preferences
            if (isset($this->engine->auth->user['display_pref'])) {

                // Try and unserialize it
                $display_pref = unserialize($this->engine->auth->user['display_pref']);

                if (isset($display_pref[0])) {

                    // Fetch the coin and exchange codes for this market
                    $query = "SELECT coin1.code AS coin, coin2.code AS exchange FROM markets, coins coin1, coins coin2 WHERE markets.market_id = :id AND markets.active = 1 AND markets.coin = coin1.id AND markets.exchange = coin2.id";

                    $inputs = array(
                        ':id' => $display_pref[0]
                    );

                    $markets = $this->engine->database->query(0, $query, $inputs);

                    if (isset($markets[0])) {

                        // Need to set the fields for market name
                        $_REQUEST['coin'] = $markets[0]['coin'];
                        $_REQUEST['exchange'] = $markets[0]['exchange'];

                        return $display_pref[0];

                    } else {
                        
                        // Set default market to BC/BTC
                        $_REQUEST['coin'] = 'BC';
                        $_REQUEST['exchange'] = 'BTC';

                    }

                } else {

                    // Set default market to BC/BTC
                    $_REQUEST['coin'] = 'BC';
                    $_REQUEST['exchange'] = 'BTC';

                }

            } else {

                // Set default market to BC/BTC
                $_REQUEST['coin'] = 'BC';
                $_REQUEST['exchange'] = 'BTC';

            }

        }

        // Check if Jay or Jason is accessing the market
        if ((isset($this->engine->auth->user['id']) && ($this->engine->auth->user['id'] == '1' || $this->engine->auth->user['id'] == '2'))) {

            // Query to find the market ID
            $query = "SELECT market_id FROM markets WHERE exchange = (SELECT id from coins WHERE code = :exchange) AND coin = (SELECT id from coins WHERE code = :coin)";

             $inputs = array(
                ':exchange' => isset($_REQUEST['exchange']) ? $_REQUEST['exchange'] : '',
                ':coin' => isset($_REQUEST['coin']) ? $_REQUEST['coin'] : ''
            );

            $markets = $this->engine->database->query(0, $query, $inputs);

            if (isset($markets[0])) {
                return $markets[0]['market_id'];
            } else {
                return false;
            }

        } else {

            // Try and get the market ID from memcached
            if ($this->engine->memcache->cacheAvailable == true) {
                if(isset($_REQUEST['coin']) && isset($_REQUEST['exchange'])) {
                    $marketMemcached = $this->engine->memcache->get('marketID_'.$_REQUEST['coin'].$_REQUEST['exchange']);
                } else {
                    return false;
                }
            } else {
                $marketMemcached = false;
            }

            // Fall back to MySQL
            if($marketMemcached == false) {

                // Query to find the market ID
                $query = "SELECT market_id FROM markets WHERE exchange = (SELECT id from coins WHERE code = :exchange) AND coin = (SELECT id from coins WHERE code = :coin) AND active = 1";
        
                $inputs = array(
                    ':exchange' => isset($_REQUEST['exchange']) ? $_REQUEST['exchange'] : '',
                    ':coin' => isset($_REQUEST['coin']) ? $_REQUEST['coin'] : ''
                );

                $markets = $this->engine->database->query(0, $query, $inputs);

                if (isset($markets[0])) {
                    
                    // Save to memcached
                    $marketMemcached = $this->engine->memcache->set('marketID_'.$_REQUEST['coin'].$_REQUEST['exchange'], $markets[0]['market_id']);

                    // Return it
                    return $markets[0]['market_id'];

                } else {

                    // Invalid market
                    return false;
                }

            } else {

                // Return the vaue from memcached
                return $marketMemcached;
            }
        }
    }

    function getNotices($market = null)
    {

        if (isset($market)) {

            // Query to find any notices for a specific market first and then general notices
            $query = "SELECT type, message FROM notices WHERE active = 1 AND ((level = 1 AND market = :market) OR level = 0) ORDER BY level ASC, id DESC";

            $inputs = array(
                ':market' => $market
            );

            $notices = $this->engine->database->query(0, $query, $inputs);

        } else {

            // Query to find any notices
            $query = "SELECT type, message FROM notices WHERE active = 1 AND level = 0 ORDER BY id DESC";

            $notices = $this->engine->database->query(0, $query);

        }

        // If a notice exists, we only want to return the first one
        if (!empty($notices)) {
            return $notices[0];
        } else {
            return false;
        }

    }

    function send_mailgun($email, $subject, $html)
    {

        $config = array();
        $config['api_key'] = "key-287en3jfsbjr-zzy9sp-2-yaxpaqwk-4";
        $config['api_url'] = "https://api.mailgun.net/v2/bitPeer.com/messages";

        $message = array();
        $message['from'] = "BitPeer Support <noreply@bitPeer.com>";
        $message['to'] = $email;
        $message['h:Reply-To'] = "<support@bitPeer.com>";
        $message['subject'] = $subject;
        $message['html'] = $html;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['api_url']);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "api:{$config['api_key']}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    // Function to push a message to the new crossbar server
    function _doPush($topic, $kwargs, $args = array()) {

        // Check if we have a topic
        if($topic == '') {
            return false;
        }

        // Check if we have args
        if(!is_array($kwargs)) {
            return false;
        }

        // Set the data
        $data = array();
        $data['args'] = $args;
        $data['kwargs'] = $kwargs;
        $data['topic'] = $topic;

        // Double check we have cURL
        if(function_exists('curl_init')) {

            // Start cURL
            $c = curl_init();
            curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

            $data = json_encode($data);

            // Setup the remainder of the cURL request
            curl_setopt($c, CURLOPT_URL, "http://192.168.200.131:8080");
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_POSTFIELDS, $data);
            curl_setopt($c, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json',                                                                                
                'Content-Length: ' . strlen($data))                                                                       
            );         

            // Execute the API call and return the response
            $result = curl_exec($c);
            curl_close($c);

            return true;

        } else {
            return false;
        }
    }

}