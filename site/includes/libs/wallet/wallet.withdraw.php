<?php

/**
 * BitPeer Wallet Withdraw Library
 *
 * Functions related to wallet withdrawals in the frontend
 *
 * @category   ArcticDesk
 * @package    Wallet
 * @copyright  Copyright (c) 2014 BitPeer
 * @version    $Id: wallet.withdraw.php 3886 2014-07-30 02:09:38Z jay $
 * @since      File available since 1.0
 */

$className = 'walletWithdraw';

class walletWithdraw
{

    var $engine;

    /**
     * Required to access the other library functions
     * @param  class  &$engine
     */
    function walletWithdraw(&$engine)
    {
        $this->engine = &$engine;
    }

    /**
     * Fetches a list of withdrawals made by the specified user, with option to include/exclude
     * pending or confirmed withdrawals.
     *
     * @param   $userId     int      The ID of the user
     * @param   $pending    boolean  True to include pending withdrawals, false otherwise
     * @param   $confirmed  boolean  True to include confirmed withdrawals, false otherwise
     * @return              array    An array of the withdrawals made by the user
     */
    function getWithdrawals($userId, $number = 30, $offset = 0, $coinId = null)
    {

        // Get confirmed withdrawals first
        if (!empty($coinId)) {

            // Fetch orders ordered by most recent
            $query = "SELECT transactions.id, coins.code, transactions.address, transactions.amount, transactions.fee, transactions.txid, transactions.time, transactions.pending FROM transactions, coins WHERE transactions.type = 1 AND transactions.user_id = :user AND transactions.coin_id = :coin AND transactions.coin_id = coins.id ORDER BY time DESC LIMIT :offset,:limit";

            $inputs = array(
                ':user' => $userId,
                ':coin' => $coinId
            );

        } else {

            // Fetch orders ordered by most recent
            $query = "SELECT transactions.id, coins.code, transactions.address, transactions.amount, transactions.fee, transactions.txid, transactions.time, transactions.pending FROM transactions, coins WHERE transactions.type = 1 AND transactions.user_id = :user AND transactions.coin_id = coins.id ORDER BY time DESC LIMIT :offset,:limit";

            $inputs = array(
                ':user' => $userId
            );

        }

        $limits = array(
            ':offset' => $offset,
            ':limit'  => $number
        );

        $confirmedWithdrawals = $this->engine->database->query(0, $query, $inputs, $limits);

        if (!empty($confirmedWithdrawals)) {

            foreach ($confirmedWithdrawals AS $key => $value) {
                $confirmedWithdrawals[$key]['time_formatted'] = date("Y-m-d H:i:s", $value['time']);
                $confirmedWithdrawals[$key]['status'] = "SUCCESSFUL";
            }

        } else {
            $confirmedWithdrawals = array();
        }


        // Now get pending/pushed back withdrawals
        if (!empty($coinId)) {

            // Fetch orders ordered by most recent
            $query = "SELECT pending_withdraw.id, coins.code, pending_withdraw.address, pending_withdraw.amount, coins.withdraw_fee AS fee, pending_withdraw.email_confirm, pending_withdraw.time FROM pending_withdraw, coins WHERE pending_withdraw.user_id = :user AND pending_withdraw.coin_id = :coin AND pending_withdraw.coin_id = coins.id ORDER BY time DESC LIMIT :offset,:limit";

            $inputs = array(
                ':user' => $userId,
                ':coin' => $coinId
            );

        } else {

            // Fetch orders ordered by most recent
            $query = "SELECT pending_withdraw.id, coins.code, pending_withdraw.address, pending_withdraw.amount, coins.withdraw_fee AS fee, pending_withdraw.email_confirm, pending_withdraw.time FROM pending_withdraw, coins WHERE pending_withdraw.user_id = :user AND pending_withdraw.coin_id = coins.id ORDER BY time DESC LIMIT :offset,:limit";

            $inputs = array(
                ':user' => $userId
            );

        }

        $limits = array(
            ':offset' => $offset,
            ':limit'  => $number
        );

        $pendingWithdrawals = $this->engine->database->query(0, $query, $inputs, $limits);

        if (!empty($pendingWithdrawals)) {

            foreach ($pendingWithdrawals AS $key => $value) {
                $pendingWithdrawals[$key]['time_formatted'] = date("Y-m-d H:i:s", $value['time']);
                $pendingWithdrawals[$key]['fee'] = number_format($value['fee'], 8, '.', '');
                if ($value['email_confirm'] == 1) {
                    $pendingWithdrawals[$key]['status'] = "CONFIRMED";
                    $pendingWithdrawals[$key]['reason'] = "The withdrawal will be processed very shortly.";
                } else if ($value['email_confirm'] == 0) {
                    $pendingWithdrawals[$key]['status'] = "UNCONFIRMED";
                    $pendingWithdrawals[$key]['reason'] = "Please confirm the withdrawal by email.";
                } else if ($value['email_confirm'] == -1) {
                    $pendingWithdrawals[$key]['status'] = "UNSUCCESSFUL";
                    $pendingWithdrawals[$key]['reason'] = "There was an error trying to process this withdrawal, please contact <a href='mailto:support@bitPeer.com'>support</a> with details of the withdrawal.";
                } else if ($value['email_confirm'] == -99) {
                    $pendingWithdrawals[$key]['status'] = "UNSUCCESSFUL";
                    $pendingWithdrawals[$key]['reason'] = "There was an error trying to process this withdrawal, please contact <a href='mailto:support@bitPeer.com'>support</a> with details of the withdrawal.";
                } else {
                    $pendingWithdrawals[$key]['status'] = "PENDING";
                    $pendingWithdrawals[$key]['reason'] = "This withdrawal requires manual verification, we will process it very soon.";
                }
            }

        } else {
            $pendingWithdrawals = array();
        }

        $withdrawals = array_merge($pendingWithdrawals, $confirmedWithdrawals);

        return $withdrawals;

    }

    /**
     * Fetches a list of withdrawals made by the specified user, with option to include/exclude
     * pending or confirmed withdrawals.
     *
     * @param   $userId     int      The ID of the user
     * @param   $pending    boolean  True to include pending withdrawals, false otherwise
     * @param   $confirmed  boolean  True to include confirmed withdrawals, false otherwise
     * @return              array    An array of the withdrawals made by the user
     */
    function getTotalWithdrawals($userId, $coinId = null)
    {

        // Check confirmed ones first
        if (!empty($coinId)) {

            // Fetch orders ordered by most recent
            $query = "SELECT COUNT(id) AS count FROM transactions WHERE type = 1 AND user_id = :user AND coin_id = :coin";

            $inputs = array(
                ':user' => $userId,
                ':coin' => $coinId
            );

        } else {

            // Fetch orders ordered by most recent
            $query = "SELECT COUNT(id) AS count FROM transactions WHERE type = 1 AND user_id = :user";

            $inputs = array(
                ':user' => $userId
            );

        }

        $count = $this->engine->database->query(0, $query, $inputs);

        $confirmedCount = $count[0]['count'];

        // Now check pending withdrawals.
        if (!empty($coinId)) {

            // Fetch orders ordered by most recent
            $query = "SELECT COUNT(id) AS count FROM pending_withdraw WHERE user_id = :user AND coin_id = :coin";

            $inputs = array(
                ':user' => $userId,
                ':coin' => $coinId
            );

        } else {

            // Fetch orders ordered by most recent
            $query = "SELECT COUNT(id) AS count FROM pending_withdraw WHERE user_id = :user";

            $inputs = array(
                ':user' => $userId
            );

        }

        $count = $this->engine->database->query(0, $query, $inputs);

        $pendingCount = $count[0]['count'];

        // Add together for total count
        $totalCount = $pendingCount + $confirmedCount;

        return $totalCount;

    }

    function getWithdrawal($id, $userId)
    {

        // Check pending withdrawals first
        $query = "SELECT id, coin_id, address, amount, time, email_confirm FROM pending_withdraw WHERE id = :id AND user_id = :user_id";

        $inputs = array(
            ':id'      => $id,
            ':user_id' => $userId
        );

        $withdrawal = $this->engine->database->query(0, $query, $inputs);

        if (!empty($withdrawal)) {

            // Consistency with getWithdrawals
            $withdrawal[0]['time_formatted'] = date("Y-m-d H:i:s", $withdrawal[0]['time']);
            if ($value['email_confirm'] == 1) {
                $withdrawal[0]['status'] = "CONFIRMED";
                $withdrawal[0]['reason'] = "The withdrawal will be processed very shortly.";
            } else if ($value['email_confirm'] == 0) {
                $withdrawal[0]['status'] = "UNCONFIRMED";
                $withdrawal[0]['reason'] = "Please confirm the withdrawal by email.";
            } else if ($value['email_confirm'] == -1) {
                $withdrawal[0]['status'] = "UNSUCCESSFUL";
                $withdrawal[0]['reason'] = "There was an error trying to process this withdrawal, please contact <a href='mailto:support@bitPeer.com'>support</a> with details of the withdrawal.";
            } else if ($value['email_confirm'] == -99) {
                $withdrawal[0]['status'] = "UNSUCCESSFUL";
                $withdrawal[0]['reason'] = "There was an error trying to process this withdrawal, please contact <a href='mailto:support@bitPeer.com'>support</a> with details of the withdrawal.";
            } else {
                $withdrawal[0]['status'] = "PENDING";
                $withdrawal[0]['reason'] = "This withdrawal requires manual verification, we will process it very soon.";
            }

            return $withdrawal[0];
        }

        // Didn't find it in pending withdrawals, check transactions
        $query = "SELECT id, coin_id, address, amount, fee, txid, time FROM transactions WHERE id = :id AND user_id = :user_id AND type = 1";

        $inputs = array(
            ':id'      => $id,
            ':user_id' => $userId
        );

        $withdrawal = $this->engine->database->query(0, $query, $inputs);

        if (!empty($withdrawal)) {

            // Consistency with getWithdrawals
            $withdrawal[0]['time_formatted'] = date("Y-m-d H:i:s", $withdrawal[0]['time']);
            $withdrawal[0]['status'] = "SUCCESSFUL";

            return $withdrawal[0];

        } else {

            return array();

        }

    }

    /*
     * Function to verify the withdraw address based on the first char
     */
    private function verifyWithdrawAddress($address, $coin) {

        // Get the withdraw address code from the DB
        $coinQuery = $this->engine->database->query(0, "SELECT address_code FROM coins WHERE id = :id", array(':id' => $coin));

        if(isset($coinQuery[0])) {

            // Explode it
            $exploded = explode(',', $coinQuery[0]['address_code']);

            // Loop it
            foreach ($exploded AS $code) {

                // Check if the first char matches
                if($address[0] == $code) {
                    return true;
                }
            }

            return false;
        }

    }

    /**
     * Processes a withdrawal request
     *
     * @param   $userId   int      The ID of the user
     * @param   $coinId   int      The ID of the coin
     * @param   $address  string   The address to withdraw to
     * @param   $amount   float    The amount to withdraw
     * @return            boolean  True if the withdrawal has been successful, false otherwise
     */
    function requestWithdrawal($userId, $coinId, $address, $amount, $paymentid = null)
    {

        // Fetch the coin Details
        $query = "SELECT min_withdraw, max_withdraw, cryptonote FROM coins WHERE id = :coin";

        $inputs = array(
            ':coin' => $coinId
        );

        $coinDetails = $this->engine->database->query(0, $query, $inputs);

        // Only validate the address if it's a non-cryptonote currency
        if(isset($coinDetails[0]['cryptonote']) && $coinDetails[0]['cryptonote'] == 0) {

            // 0.1 Check if the withdraw address is valid based on the start letter
            if ($this->verifyWithdrawAddress($address, $coinId) == false) {
                return array(
                    'response' => false,
                    'reason'   => 'Invalid withdraw address specified for this coin. Please try again.'
                );
            }
        } else {

            // Quick hack to stop people withdrawing back to our own wallet
            if($address == '46aaTzGffy6MmCsY7rQ5CdSAbpPHPj5xkf7yDdfDZSs9YWWEvFhSSkjdr2veqC44q8dt3q1egrLdnZ3oecB1JSMF856eDwb') {
                return array(
                'response' => false,
                'reason'   => 'You cannot withdraw back to a BitPeer deposit address. Please try again.'
            );
            }
        }

        // 0.2 Check if trying to withdraw to the another BitPeer address
        $addressQuery = $this->engine->database->query(0, "SELECT address_id FROM addresses WHERE address_address = :address", array(':address' => $address));
        if (isset($addressQuery[0])) {
            return array(
                'response' => false,
                'reason'   => 'You cannot withdraw back to a BitPeer deposit address. Please try again.'
            );
        }

        // 1. Check to make sure user hasn't already requested more than the maxmimum or less than the minimum
        // Check if below the minimum limit
        if ($amount < $coinDetails[0]['min_withdraw']) {
            return array(
                'response' => false,
                'reason'   => 'You have requested an amount below the minimum withdrawal limit.'
            );
        }

        // See if the user has already requested any other withdrawals for this coin
        $query = "SELECT sum(amount) AS total FROM pending_withdraw WHERE user_id = :user AND coin_id = :coin";

        $inputs = array(
            ':user' => $userId,
            ':coin' => $coinId
        );

        $withdrawals = $this->engine->database->query(0, $query, $inputs);

        // Check if above the maximum limit with their other withdrawals
        if (($withdrawals[0]['total'] + $amount) > $coinDetails[0]['max_withdraw']) {
            if ($withdrawals[0]['total'] > 0) {
                // They have older withdrawals too
                return array(
                    'response' => false,
                    'reason'   => 'You have exceeded the maximum withdrawal limit. Please confirm or cancel any existing withdrawals and let them process before trying again.'
                );
            } else {
                // Just this one withdrawal is too large
                return array(
                    'response' => false,
                    'reason'   => 'You have requested an amount above the maximum withdrawal limit.'
                );
            }
        }

        try {

            // 2. Confirm enough balance and move available balance to pending balance

            // We're editing balances, let's use a transaction here to lock the row!
            $this->engine->database->beginTransaction();

            $query = "SELECT balance_pending_withdraw, balance_available FROM balances WHERE user_id = :user AND coin_id = :coin FOR UPDATE";

            $inputs = array(
                ':user' => $userId,
                ':coin' => $coinId
            );

            $balance = $this->engine->database->query(0, $query, $inputs);
            $balanceAvailable = $balance[0]['balance_available'];
            $balancePending = $balance[0]['balance_pending_withdraw'];

            $newBalanceAvailable = $balanceAvailable - $amount;
            $newBalancePending = $balancePending + $amount;

            // Do they have enough?
            if ($newBalanceAvailable < 0) {
                // Something went wrong, roll back!
                $this->engine->database->rollBack();
                return array(
                    'response' => false,
                    'reason'   => 'You do not have enough available balance to cover this withdrawal.'
                );
            }

            $query = "UPDATE balances SET balance_available = :balance_available, balance_pending_withdraw = :balance_pending_withdraw WHERE user_id = :user AND coin_id = :coin";

            $inputs = array(
                ':user' => $userId,
                ':coin' => $coinId,
                ':balance_available' => $newBalanceAvailable,
                ':balance_pending_withdraw' => $newBalancePending
            );

            $update = $this->engine->database->query(2, $query, $inputs);
            $balanceCheck = $this->engine->database->rowCount();

            if ($update && $balanceCheck) {

                // Done with balances, commit the transaction
                $this->engine->database->commit();

                // Logging the balance change
                $this->engine->logger->logAction(0, $userId, '', 'Making withdrawal request, moving amount ' . $amount . ' from available balance to pending withdraw');

                // Generate a validate hash
                $hashTime = time();
                $withdrawHash = $this->engine->auth->hashPassword($address . intval($amount) . $hashTime);

                // Perform a risk check on the withdraw
                $riskFactor = $this->riskWithdraw($userId, $coinId, $amount);

                // 3. Add to pending_withdraw
                $query = "INSERT INTO pending_withdraw (user_id, coin_id, address, amount, time, email_confirm, hash, risk, payment_id) VALUES (:user, :coin, :address, :amount, :time, 0, :hash, :risk, :payment_id)";

                $inputs = array(
                    ':user'    => $userId,
                    ':coin'    => $coinId,
                    ':address' => $address,
                    ':amount'  => $amount,
                    ':time'    => $hashTime,
                    ':hash'    => $withdrawHash,
                    ':risk'    => $riskFactor,
                    ':payment_id' => $paymentid
                );

                $insert = $this->engine->database->query(1, $query, $inputs);

                // Email the user
                $this->engine->auth->emailUser('withdraw', false, array('hash' => $withdrawHash, 'address' => $address, 'amount' => $amount));

            } else {

                // Something went wrong, roll back!
                $this->engine->database->rollBack();

                return array(
                    'response' => false,
                    'reason'   => 'You do not have enough available balance to cover this withdrawal.'
                );
            }

            if ($insert) {

                // SUCCESS!

                $this->engine->loadLibrary('wallet/balance');

                // Get balance for user
                $coinBalance = $this->engine->library['walletBalance']->getCoinBalance($userId, $coinId);

                return array(
                    'response' => true,
                    'withdrawal_id' => $insert,
                    'new_balance' => $coinBalance
                );

            } else {
                return array(
                    'response' => false,
                    'reason'   => 'There was an error processing your request, please try again.'
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
     * Works out the risk of a users withdraw and gives them a rating.
     *
     * We look at the following factors:
     *
     * 1 Coming from a known bad IP = 50
     * 2 Coming from a TOR exit node = 30
     * 3 Account Age
     *   3.1 24 hrs old = 30
     *   3.2 48 hrs old = 20
     *   3.3 48+ hrs old = 10
     * 4 Disposable email = 30
     * 5 BTC value of withdraw (alt coins are converted) = 20/5 (over/under limit)
     * 6 Total BTC 24hr withdraw volume. High is risky = 20/5 (over/under limit)
     * 7 Overall number of deposits - higher is better
     * 8 Overall number of withdraws - higher is better
     * 
     * Risk is reduced by a factor of coin based riskyness. 
     * If you deposit DRK or BTC etc and withdraw LTC, the risk is almost nonexistant.
     * This is decided on a coin by coin basis
     *
     * The closer to 0 the overallRisk is the better.
     */
     function riskWithdraw($userId, $coinId, $amount) {

        // Log it
        error_log('wallet.withdraw[risk] : '.$userId.' : Starting Process. Coin: '.$coinId.' Amount: '.$amount, 0);
        
        $overallRisk = 0; // Start the withdraws overall risk
        $btcValue = 6; // Single BTC value
        $btc24hValue = 15; // 24hr BTC Value
        
        // **** 1 & 2 - Check if we are logged in from a known bad IP and / or TOR
        $overallRisk = $this->checkBadNode($userId);
        error_log('wallet.withdraw[risk] : '.$userId.' : IP Risk completed. OverallRisk: '.$overallRisk, 0);
        
        // **** 3 - Get the age of the users account
        $usersAccount = $this->engine->database->query(0, "SELECT creation_date, email FROM users WHERE id = :userId", array(':userId' => $userId));
        
        // Check the age of the users account
        if(isset($usersAccount[0])) {
        
            // Work out the age of the account
            $accountAge = time() - $usersAccount[0]['creation_date'];
            
            // Check if the account is 6 hours old
            if($accountAge <= 21600) {
            
                // SUPER risky withdraw - 6hrs
                $overallRisk = $overallRisk + 40;
                error_log('wallet.withdraw[risk] : '.$userId.' : Account is 6 hours old! OverallRisk: '.$overallRisk, 0);
            
            } elseif($accountAge <= 86400) {
            
                // Fairly risky withdraw - 24hrs
                $overallRisk = $overallRisk + 30;
                error_log('wallet.withdraw[risk] : '.$userId.' : Account is 24 hours old! OverallRisk: '.$overallRisk, 0);
            
            } elseif($accountAge <= 172800) {
            
                // Not TOO bad
                $overallRisk = $overallRisk + 20;
                error_log('wallet.withdraw[risk] : '.$userId.' : Account is 48 hours old. OverallRisk: '.$overallRisk, 0);
            
            } else {
            
                // Minimal risk
                $overallRisk = $overallRisk + 5;
                error_log('wallet.withdraw[risk] : '.$userId.' : Account is fairly old. OverallRisk: '.$overallRisk, 0);
            }
        
        } else {
        
            // Something went very wrong. High risk!
            $overallRisk = $overallRisk + 40;
            error_log('wallet.withdraw[risk] : '.$userId.' : Cant find an account. WTF. OverallRisk: '.$overallRisk, 0);
        }
        
        // **** 5 - BTC value of withdraw (alt coins are converted). High is risky
        if($coinId != 1) {
            
            // Convert the coins withdraw to BTC so we can compare it
            $amount = $this->convertAmountToBTC($coinId, $amount);
        }
        
        // Work out the risk of the withdraw
        if($amount >= $btcValue) {
        
            // High risk withdraw
            $overallRisk = $overallRisk + 20;
            error_log('wallet.withdraw[risk] : '.$userId.' : High Single BTC value '.$amount.'. OverallRisk: '.$overallRisk, 0);
            
        } else {
        
            // Low risk withdraw
            $overallRisk = $overallRisk + 10;
            error_log('wallet.withdraw[risk] : '.$userId.' : Low Single BTC value '.$amount.'. OverallRisk: '.$overallRisk, 0);
        }
        
        // **** 6 Total BTC 24hr withdraw volume. High is risky
        $timeWindow = time() - 86400;
        $balanceQuery = $this->engine->database->query(0, "SELECT sum(amount) as amount FROM transactions WHERE user_id = '" . $userId . "' AND coin_id = '" . $coinId . "' AND type = '1' AND time > " . $timeWindow . "");

        // Check we got a total
        if (isset($balanceData['amount'])) {
        
            // Check if it's an ALT coin and convert to BTC if it is
            if($coinId != 1) {
            
                // Convert the coins withdraw to BTC so we can compare it
                $amount = $this->convertAmountToBTC($coinId, $balanceData['amount']);
                
            } else {
            
                // We are withdrawing BTC
                $amount = $balanceData['amount'];
            }
        }
        
        // Check if the user has gone over the BTC total
        if($amount >= $btc24hValue) {
        
            // High risk withdraw
            $overallRisk = $overallRisk + 20;
            error_log('wallet.withdraw[risk] : '.$userId.' : High 24hr BTC value '.$amount.'. OverallRisk: '.$overallRisk, 0);
            
        } else {
        
            // Low risk withdraw
            $overallRisk = $overallRisk + 10;
            error_log('wallet.withdraw[risk] : '.$userId.' : Low 24hr BTC value '.$amount.'. OverallRisk: '.$overallRisk, 0);
        }
        
        // **** 7 & 8 Overall number of deposits - higher is better
        $txHistory = $this->engine->database->query(0, "SELECT * FROM transactions WHERE user_id = :userId ORDER BY id ASC", array(':userId' => $userId));
        $totalDeposits = 0;
        $totalWithdraws = 0;
        $lastDeposit = null;
        
        // Loop the transactions
        if(is_array($txHistory)) {
            foreach($txHistory AS $transaction) {
            
                if($transaction['type'] == 0 && $transaction['pending'] == 0) {
                    ++$totalDeposits;
                    $lastDeposit = $transaction;
                } elseif($transaction['type'] == 1) {
                    ++$totalWithdraws;
                }
            }
        }
        
        // Work out the deposits
        if($totalDeposits <= 3) {
        
            // Fairly risky
            $overallRisk = $overallRisk + 20;
            error_log('wallet.withdraw[risk] : '.$userId.' : Low total deposits '.$totalDeposits.'. OverallRisk: '.$overallRisk, 0);
            
        } elseif($totalDeposits <= 10) {
        
            // Less risky
            $overallRisk = $overallRisk + 10;
            error_log('wallet.withdraw[risk] : '.$userId.' : Medium total deposits '.$totalDeposits.'. OverallRisk: '.$overallRisk, 0);
            
        } else {
        
            // Hardly any risk
            $overallRisk = $overallRisk + 5;
            error_log('wallet.withdraw[risk] : '.$userId.' : High total deposits '.$totalDeposits.'. OverallRisk: '.$overallRisk, 0);
        }
        
        // Work out withdraws
        if($totalWithdraws <= 3) {
        
            // Fairly risky
            $overallRisk = $overallRisk + 20;
            error_log('wallet.withdraw[risk] : '.$userId.' : Low total withdraws '.$totalWithdraws.'. OverallRisk: '.$overallRisk, 0);
            
        } elseif($totalWithdraws <= 10) {
        
            // Less risky
            $overallRisk = $overallRisk + 10;
            error_log('wallet.withdraw[risk] : '.$userId.' : Medium total withdraws '.$totalWithdraws.'. OverallRisk: '.$overallRisk, 0);
            
        } else {
        
            // Hardly any risk
            $overallRisk = $overallRisk + 5;
            error_log('wallet.withdraw[risk] : '.$userId.' : High total withdraws '.$totalWithdraws.'. OverallRisk: '.$overallRisk, 0);
        }
        
        // Only do the email check if we already have a high risk
        if($overallRisk >= 70 && $totalWithdraws == 0) {

            error_log('wallet.withdraw[risk] : '.$userId.' : Performing DEA email check. Total risk is over 70', 0);

            // **** 11 - Disposable email address check
            $emailCheck = $this->_doEmailCheck($usersAccount[0]['email'], $userId);
            if($emailCheck == true) {

                // Risky business
                $overallRisk = $overallRisk + 30;
                error_log('wallet.withdraw[risk] : '.$userId.' : User has a Disposable email address. '.$usersAccount[0]['email'], 0);

            } else {

                // Not sure what hapened
                $overallRisk = $overallRisk + 5;
                error_log('wallet.withdraw[risk] : '.$userId.' : User does not have a disposable email address', 0);
            }
        }
        
        // **** Risk factor
        if(isset($lastDeposit['coin_id'])) {
            $riskFactor = $this->engine->database->query(0, "SELECT risk_factor FROM coins WHERE id = :coinId", array(':coinId' => $lastDeposit['coin_id']));
            if(isset($riskFactor[0]['risk_factor'])) {
            
                // Calculate the new factor
                $newRisk = 5 * $riskFactor[0]['risk_factor'];
                $overallRisk = $overallRisk + $newRisk;

                error_log('wallet.withdraw[risk] : '.$userId.' : Users last deposit '.$lastDeposit['coin_id'].' has a risk factor of '.$newRisk, 0);
            }
        } else {

            // Give it some risk as we dont know what they deposited - WTF
            $overallRisk = $overallRisk + 10;
            error_log('wallet.withdraw[risk] : '.$userId.' : Unable to determine the last deposit. Cant use a risk factor', 0);
        }

        error_log('wallet.withdraw[risk] : '.$userId.' : Risk process complete. Overall withdraw risk: '.$overallRisk, 0);
        
        // Output the final risk factor
        return $overallRisk;
     }

    /**
     * Interfaces with fullcontact API to check an email address
     *
     */
    function _doEmailCheck($emailAddress, $userId) {

        // Explode the email address
        $emailExplode = explode("@", $emailAddress);

        // Check if the email is valid
        if(isset($emailExplode[1])) {

            error_log('wallet.withdraw[risk] : '.$userId.' : Checking email domain '.$emailExplode[1], 0);

            // Check the DB if we already know about this address
            $addressCheck = $this->engine->database->query(0, "SELECT disposable FROM email_check WHERE email = :email", array(':email' => $emailExplode[1]));
    
            // Did we get a match
            if(isset($addressCheck[0])) {

                // Check if we cached the domain result
                if($addressCheck[0]['disposable'] == 1) {
                    error_log('wallet.withdraw[risk] : '.$userId.' : Cached result - Disposable!', 0);
                    return true;
                } else {
                    error_log('wallet.withdraw[risk] : '.$userId.' : Cached result - Not Disposable!', 0);
                    return false;
                }

            }

            error_log('wallet.withdraw[risk] : '.$userId.' : No cached results, checking with fullcontact.com', 0);

            // Setup keys
            $apiPublicKey = "843cb746e826b0f0";

            // Setup URL
            $apiUrl = "https://api.fullcontact.com/v2/email/disposable.json?apiKey=".$apiPublicKey."&email=".urlencode($emailAddress);

            // Start cURL
            $c = curl_init();
            curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

            // Setup the remainder of the cURL request
            curl_setopt($c, CURLOPT_URL, $apiUrl);

            // Execute the API call and return the response
            $result = curl_exec($c);
            curl_close($c);

            // Return the results of the API call
            $results = json_decode($result);

            if(isset($results->disposableEmailDomain)) {

                // Check if it's disposable
                if($results->disposableEmailDomain == true) {

                    // Cache the result
                    $this->engine->database->query(1, "INSERT INTO email_check (email, disposable) VALUES (:email, 1)", array(':email' => $emailExplode[1]));
                    error_log('wallet.withdraw[risk] : '.$userId.' : fullcontact result - Disposable! ', 0);
                    return true;

                } else {

                    // Cache the result
                    $this->engine->database->query(1, "INSERT INTO email_check (email, disposable) VALUES (:email, 0)", array(':email' => $emailExplode[1]));
                    error_log('wallet.withdraw[risk] : '.$userId.' : fullcontact result - Not Disposable! ', 0);
                    return false;
                }
            } else {

                error_log('wallet.withdraw[risk] : '.$userId.' : ERROR: fullcontact failed. Message: '.$results->message, 0);

                // Not sure what happened
                return false;
            }

        } else {

            // We can't decide
            return false;
        }

    }

    
    /**
     * Convert a coin amount to BTC for comparison
     * If a coin has no BTC market we convert to LTC and then to BTC
     */
    function convertAmountToBTC($coinId, $amount) {
    
        // Get the markets for a particular coin
        $coinMarkets = $this->engine->database->query(0, "SELECT last_price FROM markets WHERE coin = :coinId AND exchange = 1", array(':coinId' => $coinId));
    
        // Did we get a market?
        if(isset($coinMarkets[0])) {
        
            // Convert the value to BTC
            return $amount * $coinMarkets[0]['last_price'];
        
        } else {
        
            // Coin must be LTC only?
            $coinMarkets = $this->engine->database->query(0, "SELECT last_price FROM markets WHERE coin = :coinId AND exchange = 2", array(':coinId' => $coinId));
            
            // Did we get a market?
            if(isset($coinMarkets[0])) {
            
                // Convert the value to LTC
                $amount = $amount * $coinMarkets[0]['last_price'];
                
                // Get the LTC/BTC market price
                $ltcMarket = $this->engine->database->query(0, "SELECT last_price FROM markets WHERE coin = 2 AND exchange = 1");
            
                // Did we get a market?
                if(isset($ltcMarket[0])) {
                
                    // Convert the LTC price to BTC
                    return $amount * $coinMarkets[0]['last_price'];
                
                } else {
                
                    // WTF?? Something is FUBAR
                    return -1;
                }
            } else {
            
                // Couldnt find a BTC or LTC market
                return -1;
            }
        }
    }
    
    /**
     * Check if a user is logged in from a know bad node
     * Extra "badness" is given to a TOR exit node
     */
    function checkBadNode($userId) {
    
        // Get the users currently logged in IP
        $userIP = $this->engine->database->query(0, "SELECT login_ip FROM users_login_log WHERE login_user_id = :userId ORDER BY login_id DESC LIMIT 1", array(':userId' => $userId));
    
        // Check if we got a result
        if(isset($userIP[0])) {
        
            // Find if it's a known bad IP
            $badIP = $this->engine->database->query(0, "SELECT * FROM bad_ip WHERE ip = :userIP", array(':userIP' => $userIP[0]['login_ip']));
            
            // Check if we got a result
            if(isset($badIP[0])) {
            
                // Check if we are a TOR node
                if($badIP[0]['tor'] == 1) {
                
                    // User is coing from a TOR exit node. So lets assume they are somehwat risky
                    error_log('wallet.withdraw[risk] : '.$userId.' : IP is TOR IP. Risky.', 0);
                    return 30;
                    
                } else {
                
                    // We have specifically banned this guy. So it's BAD
                    error_log('wallet.withdraw[risk] : '.$userId.' : IP is manually blocked. Risky!!', 0);
                    return 50;
                }
            } else {
            
                // User is good!
                error_log('wallet.withdraw[risk] : '.$userId.' : IP is not a known bad IP.', 0);
                return 10;
            }
        } else {
        
            // We can't work out, so lets assume it's fairly risky
            error_log('wallet.withdraw[risk] : '.$userId.' : Unable to fnd and IP login to the account ', 0);
            return 40;
            
        }
    }

    /**
     * Cancels a withdrawal request
     *
     * @param   $withdrawalId  int    The ID of the pending withdrawal
     * @param   $userId        int    The ID of the user
     * @return                 array  Contains the response, true if the cancellation has been successful, false otherwise
     */
    function cancelWithdrawal($withdrawalId, $userId)
    {

        // 1. Let's make sure the withdrawal exists first and fetch the amount
        $query = "SELECT amount, coin_id FROM pending_withdraw WHERE id = :id AND user_id = :user AND email_confirm != 1 AND email_confirm != 2 AND email_confirm != -99";

        $inputs = array(
            ':id'   => $withdrawalId,
            ':user' => $userId
        );

        $withdrawal = $this->engine->database->query(0, $query, $inputs);

        if (!empty($withdrawal)) {
            $returnAmount = $withdrawal[0]['amount'];
            $coinId = $withdrawal[0]['coin_id'];
        } else {
            return array(
                'response' => false,
                'reason'   => 'The withdrawal does not exist or has already been confirmed, please try again.'
            );
        }

        // 2. Delete the withdrawal

        try {

            // We're editing balances, let's use a transaction here to lock the row!
            $this->engine->database->beginTransaction();

            $query = "DELETE FROM pending_withdraw WHERE id = :id AND email_confirm != 1 AND email_confirm != 2 AND email_confirm != -99";

            $inputs = array(
                ':id'   => $withdrawalId
            );

            $delete = $this->engine->database->query(2, $query, $inputs);
            $deleteCheck = $this->engine->database->rowCount();

            if ($delete && $deleteCheck) {

                // 3. Move the balance from balance_pending_withdraw to balance_available
                $query = "SELECT balance_pending_withdraw, balance_available FROM balances WHERE user_id = :user AND coin_id = :coin FOR UPDATE";

                $inputs = array(
                    ':user' => $userId,
                    ':coin' => $coinId
                );

                $balance = $this->engine->database->query(0, $query, $inputs);
                $balanceAvailable = $balance[0]['balance_available'];
                $balancePending = $balance[0]['balance_pending_withdraw'];

                $newBalanceAvailable = $balanceAvailable + $returnAmount;
                $newBalancePending = $balancePending - $returnAmount;

                // Do they have enough for the credit back?
                if ($newBalancePending < 0) {
                    // Something went wrong, roll back!
                    $this->engine->database->rollBack();
                    return array(
                        'response' => false,
                        'reason'   => 'You do not have enough pending withdrawal balance to cover this cancellation, please contact support.'
                    );
                }

                $query = "UPDATE balances SET balance_available = :balance_available, balance_pending_withdraw = :balance_pending_withdraw WHERE user_id = :user AND coin_id = :coin";

                $inputs = array(
                    ':user' => $userId,
                    ':coin' => $coinId,
                    ':balance_available' => $newBalanceAvailable,
                    ':balance_pending_withdraw' => $newBalancePending
                );

                $update = $this->engine->database->query(2, $query, $inputs);
                $balanceCheck = $this->engine->database->rowCount();

                if ($update && $balanceCheck) {

                    // Done with balances, commit the transaction
                    $this->engine->database->commit();

                    // Logging the balance change
                    $this->engine->logger->logAction(0, $userId, '', 'Cancelling withdrawal request, moving amount ' . $returnAmount . ' from pending withdraw to available balance.');

                    return array(
                        'response' => true,
                        'reason'   => 'The pending withdrawal has successfully been cancelled and the balance made available again.'
                    );

                } else {

                    // Something went wrong, roll back!
                    $this->engine->database->rollBack();

                    return array(
                        'response' => false,
                        'reason'   => 'Something went wrong, please try again.'
                    );
                }

            } else {
                // Roll back as something went wrong
                $this->engine->database->rollBack();
                return array(
                    'response' => false,
                    'reason'   => 'There was an error cancelling the withdrawal, please try again.'
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

    function getWithdrawFees()
    {

        $query = "SELECT code, name, withdraw_fee FROM coins WHERE active = 1 ORDER BY code";

        return $this->engine->database->query(0, $query);

    }

    function getWithdrawFee($coinId)
    {
        

        $query = "SELECT 13 as withdraw_fee FROM coins";// WHERE id = :coin";

        $inputs = array(
            ':coin' => $coinId
        );

        $fee = $this->engine->database->query(0, $query, $inputs);
        //echo $fee[0]['withdraw_fee'];
        //exit();
        return $fee[0]['withdraw_fee'];

    }

    function getWithdrawLimits($coinId)
    {

        $query = "SELECT 12 as min_withdraw, 12 as max_withdraw FROM coins";// WHERE id = :coin";

        $inputs = array(
            ':coin' => $coinId
        );

        $limits = $this->engine->database->query(0, $query, $inputs);

        return $limits[0];

    }

    function confirmWithdraw($hash) {

        // Check if the withdraw is valid
        $query = "SELECT * FROM pending_withdraw WHERE hash = :hash AND user_id = :id AND email_confirm = 0";

        $inputs = array(
            ':hash' => $hash,
            ':id' => $this->engine->auth->user['id']
        );

        $confirm = $this->engine->database->query(0, $query, $inputs);

        // Check if we have anything
        if(isset($confirm[0])) {

            // Check if the risk is too high
            if($confirm[0]['risk'] >= 120) {

                // Withdraw is too risky. Lock it down
                error_log('wallet.withdraw[confirmWithdraw] : '.$confirm[0]['user_id'].' : RISK-ERROR: Withdraw '.$confirm[0]['id'].' @ '.$confirm[0]['amount'].' of coin '.$confirm[0]['coin_id'].' deemed too risky. Risk Rating: '.$confirm[0]['risk'].'. Please approve.', 0);
                $query = "UPDATE pending_withdraw SET email_confirm = -2 WHERE hash = :hash AND user_id = :id";

            } else {

                // Withdraw is fine, let it pass
                error_log('wallet.withdraw[confirmWithdraw] : '.$confirm[0]['user_id'].' : Withdraw '.$confirm[0]['id'].' confirmed successfully.', 0);
                $query = "UPDATE pending_withdraw SET email_confirm = 1 WHERE hash = :hash AND user_id = :id";
            }

            $inputs = array(
                ':hash' => $hash,
                ':id' => $this->engine->auth->user['id']
            );

            $confirm = $this->engine->database->query(2, $query, $inputs);

            return true;
        } else {
            return false;
        }
    }

}