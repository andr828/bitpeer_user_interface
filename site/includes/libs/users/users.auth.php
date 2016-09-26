 <?php

/**
 * BitPeer Users Authentication Library
 *
 * Functions related to user authentication in the frontend
 *
 * @category   ArcticDesk
 * @package    Users
 * @copyright  Copyright (c) 2014 BitPeer
 * @version    $Id: users.auth.php 3836 2014-07-10 13:18:12Z yatesj $
 * @since      File available since 1.0
 */
$className = 'usersAuth';

class usersAuth
{
    private $engine;
    private $saltLength = 15;
    public $user = array();
    public $isAuthenticated = false;
    
    function __construct(&$engine) {
        
        $this->engine = $engine;

        // Get the session ID from cookie
        $userSession = isset($_COOKIE['session_id']) ? $_COOKIE['session_id'] : '';

        $userData = false;

        // Check if we have a valid user session
        if ($userSession != '') {

            // Get the session from memcached
            if ($this->engine->memcache->cacheAvailable == true) {

                // Try and get the Users data from memcached
                $userData = $this->engine->memcache->get('user_'.$userSession);
            }

            if($userData == false) {

                // Verify the session from the DB
                $query = "SELECT id,session_id,name,email,active,last_active,login_time,2fa_enabled,2fa_secret,notifications,autologout,display_pref FROM users WHERE session_id = :session_id";
                $userData = $this->engine->database->query(0, $query, array(':session_id' => $userSession));

                if (isset($userData[0])) {
                    $userData = $userData[0];

                    // Save the data to memcached
                    if ($this->engine->memcache->cacheAvailable == true) {

                        // Try and get the Users data from memcached
                        $this->engine->memcache->set('user_'.$userSession, $userData, 3600);
                    }
                } else {
                    $userData = false;
                }
            }
        }

        // Check if we have some data
        if (is_array($userData) && !empty($userData)) {

            // Set the sesstion ID
            if (!isset($_SESSION['session_id'])) {
                $_SESSION['session_id'] = session_id();
            }
                    
            // Check if the users account is active - has somebody tried to hack it?
            if($userData['active'] == 1) {
                
                // Make sure we have a logout timer
                if(empty($userData['autologout'])) {
                    $userData['autologout'] = '2700';
                }
                
                // Check if the last_active timer is valid - 45 MINUTES
                if($userData['last_active'] > time() - $userData['autologout']) {
                    
                    // User is valid & hasnt timed out
                    $this->isAuthenticated = true;
                    $this->user = $userData;
                    
                } else {
                    
                    // User has timed out. Log them out
                    $this->logoutUser();
                }
            } else {
                
                // Log the user out
                $this->logoutUser();
            }
        } else {
            
            // User isnt valid
            $this->isAuthenticated = false;
            $this->user = array();
        }

    }

    // Function to authenticate an api request
    public function generateAPIKey() {

        // Add an API Key
        $query = "INSERT INTO api_keys (user_id, description, public, private, access, created) VALUES(:user_id, :description, :public, :private, :access, :created)";
        $inputs = array(':user_id' => $this->user['id'], 
                        ':description' => 'New API Key',
                        ':public' => $this->_generateAPIKey(),
                        ':private' => $this->_generateAPIKey(),
                        ':access' => '0',
                        ':created' => time()
                        );
        $userData = $this->engine->database->query(1, $query, $inputs);
    }

    public function deleteAPIKey($public) {

        // Delete an API key
        $this->engine->database->query(0, "DELETE FROM api_keys WHERE user_id = :user AND public = :public", array(':user' => $this->user['id'], ':public' => $public));

        return true;
    }

    public function regenerateAPIKey($public) {

        // Delete an API key
        $this->engine->database->query(0, "UPDATE api_keys SET public = :public_new, private = :private_new WHERE user_id = :user AND public = :public", 
            array(':user' => $this->user['id'], 
                  ':public' => $public,
                  ':public_new' => $this->_generateAPIKey(),
                  ':private_new' => $this->_generateAPIKey()));
        
        return true;
    }

    private function _generateAPIKey() {

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return hash_hmac('sha1', $randomString, 'hghs874ugjsHF8&*£sh29ghg4hf02lf'.$this->user['id']);
    }

    // Function to authenticate an api request
    public function authenticateAPI($publicKey) {

        if(isset($publicKey) && $publicKey != '') {

            // Check the users data
            $query = "SELECT users.id,users.email,api_keys.access,api_keys.private FROM users,api_keys WHERE users.active = 1 AND users.confirmed = 1 AND users.api_enabled = 1 AND api_keys.public = :publicKey AND users.id = api_keys.user_id";
            $userData = $this->engine->database->query(0, $query, array(':publicKey' => $publicKey));

            // Check if we have a record
            if(isset($userData[0])) {

                // Set the users data
                $this->user = $userData[0];

                // Return true
                return true;

            } else {
                return false;
            }
        }
    }

    public function apiAuthDetails() {

        // Query for the API details
        $apiKeys = $this->engine->database->query(0, "SELECT * FROM api_keys WHERE user_id = :user", array(':user' => $this->user['id']));
        $apiData = $this->engine->database->query(0, "SELECT api_enabled FROM users WHERE id = :user", array(':user' => $this->user['id']));

        // Return the API data
        return array('api_enabled' => $apiData[0]['api_enabled'], 'api_keys' => $apiKeys);
    }
    
    // Function to logout the user
    public function logoutUser() {

        // Delete the data from memcached
        if ($this->engine->memcache->cacheAvailable == true && isset($this->user['session_id'])) {
            $this->engine->memcache->set('user_'.$this->user['session_id'], false, 30);
        }
        
        // Remove the session ID
        if(isset($this->user['id'])) {
            $this->engine->database->query(0, "UPDATE users SET session_id = '' WHERE id = :user", array(':user' => $this->user['id']));
        }
        // Reset the variable
        $_SESSION['session_id'] = 0;
        
        // Destroy the session
        @session_destroy();
        session_regenerate_id();

        // Destroy the cookie
        $_COOKIE['session_id'] = '';
        unset($_COOKIE['session_id']);
        setcookie('session_id', null, -1, '/');
        
        // Reset the variables
        $this->isAuthenticated = false;
        $this->user = array();
        
    }

    // Function to get the last login details
    public function getLastLogin() {

        // Check the users data
        $query = "SELECT 34 as login_ip,342 as login_time,1 as login_success FROM users_login_log WHERE login_user_id = :user_id ORDER BY login_id DESC LIMIT 2";
        $loginData = $this->engine->database->query(0, $query, array(':user_id' => $this->user['id']));
        
        // Check if we have a record
        if(isset($loginData[1])) {

            $arrau = array(
                'login_ip' => 123 ,
                'login_time' => 12,
                'login_success' => 1, );
            return $arrau;
        }
      return  $arrau = array(
                'login_ip' => 123 ,
                'login_time' => 12,
                'login_success' => '0', );
    }

    // Returns true or false for a valid login
    public function userAuth($email, $password) {
        // Check if the user is banned from logging in?
        if($this->isBanned()) {
 
            // Rerturn banned
            return array('response' => false, 'reason' => "You have failed too many logins and have been banned for 24hrs. Please contact support.");
            exit;
        }
        
        if(isset($email) && isset($password)) {

            // Check the users data
            $query = "SELECT id,password,active,failed_logins,email,confirmed,2fa_secret,2fa_enabled, autologout FROM users WHERE email = :email";
            $userData = $this->engine->database->query(0, $query, array(':email' => $email));
           
            // Check if we have a record
            if(isset($userData[0])) {
                // Set the data
                $this->user = $userData[0];

                // Check if the account is active
                if($this->user['active'] == '1') {

                    // Validate the password
                    if($this->validateHash($this->user['password'], $password)) {

                        // Check if the account is confirmed
                        if($this->user['confirmed'] == 1) {
                            
                            // Check if the user has 2FA enabled
                            if($this->user['2fa_enabled'] == 0) {
                                 
                                // We need to redirect the user to the 2FA page
                                $hash = $this->hashPassword($this->user['2fa_secret']);
                               
                                // Save the hash so we can find the user later
                                $query = "UPDATE users SET session_id = :session_id WHERE id = :id";
                                $this->engine->database->query(2, $query, array(':id' => $this->user['id'], ':session_id' => $hash));
                            
                                // Redirect
                                return array('response' => true, 'action' => $hash);
                                
                            } else {

                                // Add a valid login
                                $this->updateLoginLog(1);

                                // Generate a new session ID
                                session_regenerate_id();

                                // Set the cookie
                                setcookie("session_id", session_id(), time() + 60 * 60 * 24 * 7, '/');
                                $_SESSION['session_id'] = session_id();

                                // Reset the failed logins & session ID
                                $query = "UPDATE users SET last_active = '".time()."', login_time = '".time()."', failed_logins = '0', session_id = :session_id WHERE id = :id";
                                $this->engine->database->query(2, $query, array(':id' => $this->user['id'], ':session_id' => session_id()));

                                return array('response' => true);
                            }
                        } else {
                            return array('response' => false, 'reason' => "You must confirm your email address before logging in.");
                        }

                    } else {
                        
                        // Add a failed login
                        $this->updateLoginLog(0);
                        $this->banUser();
                        
                        // Check the number of failed logins
                        if($this->user['failed_logins'] == 3) {
                            
                            // Make the validate hash
                            $validateHash = $this->hashPassword($this->user['email']."blocked");
                            
                            // Trigger an email to the user
                            $this->emailUser('blocked', false, array('link' => $validateHash, 'name' => $this->user['name'], 'email' => $this->user['email']));
                            
                            // Block the account
                            $query = "UPDATE users SET active = '0', session_id = :session_id WHERE id = :id";
                            $this->engine->database->query(2, $query, array(':id' => $this->user['id'], ':session_id' => $validateHash));
                            
                        } else {

                            // Trigger an email to the user
                            $this->emailUser('failed', false, array('name' => $this->user['name'], 'email' => $this->user['email']));
                            
                            // Incrase the failed logins
                            $query = "UPDATE users SET failed_logins = :login_count WHERE id = :id";
                            $this->engine->database->query(2, $query, array(':id' => $this->user['id'], ':login_count'  => ++$this->user['failed_logins']));
                        }

                        // Return false - INVALID PASSWORD
                        return array('response' => false, 'reason' => "Invalid username / password. Please try again.");
                    }
                 } else {
                    // Return false - ACCOUNT BLOCKED
                    return array('response' => false, 'reason' => "Sorry - Your account is disabled. Please check your email for details on how to re-activate your account.");
                }
            } else {
                // Return false - INVALID ACCOUNT
                return array('response' => false, 'reason' => "Invalid username / password. Please try again.");
            }
        } else {
            // Return false - MISSING DATA
            return array('response' => false, 'reason' => "Invalid username / password. Please fill out all fields.");
        }
    }

    // Check if the users IP is banned
    public function isBanned() {

        $query = "SELECT ban_time FROM ip_ban WHERE ban_ip = :ip ORDER BY ban_id DESC LIMIT 1";
        $banData = $this->engine->database->query(0, $query, array(':ip' => $this->getIp()));
            
        // Check if we have a ban record
        if(isset($banData[0])) {

            // CHeck if the IP ban is still in affect
            $banTime = time() - 86400;
            if($banData[0]['ban_time'] >= $banTime) {

                // user is banned still
                return true;

            } else {

                // User is not banned - time has passed
                return false;
            }
        }

        // User is good
        return false;
    }

    // Check if the user should be banned
    public function banUser() {

        // Query the login data for the last 24h
        $lastTime = time() - 86400;
        $query = "SELECT login_id FROM users_login_log WHERE login_ip = :ip AND login_time > '".$lastTime."' AND login_success = 0";
        $banData = $this->engine->database->query(0, $query, array(':ip' => $this->getIp()));

        // Check if we have some data
        if(isset($banData[0])) {

            // Check if we have hit the threshold
            if(count($banData) >= 9) {

                // Ban the users account
                $this->engine->database->query(1, "INSERT INTO ip_ban VALUES('', :ip, '".time()."')", array(':ip' => $this->getIp()));

                // Return false
                return false;

            } else {

                // Account is still ok
                return true;
            }
        } else {

            // Account is fine
            return false;
        }
    }
    
    // Function to register an account
    public function registerAccount($data) {
        
        // Check if we have all the fields
        if(isset($data['fullname']) && isset($data['email']) && isset($data['password']) && isset($data['password2']) && isset($data['answer1']) && isset($data['answer2'])) {
           
            // Check the passwords match
            if($data['password'] == $data['password2']) {
                
                // Check the email does not exist already
                $query = "SELECT id FROM users WHERE email = :email";
                $userData = $this->engine->database->query(0, $query, array(':email' => $data['email']));
                
                if(!isset($userData[0])) {
                    
                    // Hash the users email
                    $validateHash = $this->hashPassword($data['email'].$data['password']."register");
                    
                    // Add the user
                    $query = "INSERT INTO users (name,email,password,security_question_1,security_question_2,security_answer_1,security_answer_2,creation_date, session_id, notifications) VALUES(:name, :email, :password, :security_question_1, :security_question_2, :security_answer_1, :security_answer_2, :creation_date, :session_id, :notifications)";

                    $inputs = array(
                        ':name' => strip_tags($data['fullname']),
                        ':email' => strip_tags($data['email']),
                        ':password' => $this->hashPassword($data['password']),
                        ':security_question_1' => $data['question1'],
                        ':security_question_2' => $data['question2'],
                        ':security_answer_1' => $this->hashPassword($data['answer1']),
                        ':security_answer_2' => $this->hashPassword($data['answer2']),
                        ':creation_date' => time(),
                        ':session_id' => $validateHash,
                        ':notifications' => serialize(array('login' => true, 'withdrawConfirm' => true))
                    );

                    $this->engine->database->query(1, $query, $inputs);
                    
                    // Send the email to the user
                    $this->emailUser('register', $data['email'], array('link' => $validateHash, 'name' => $data['fullname'], 'email' => $data['email']));
                    
                    // Return done
                    return array('response' => true);
                    
                } else {
                    // Account exists
                    return array('response' => false, 'reason' => "An account with that email address already exists. Please try again or use the forgotten password feature.");
                }
                
            } else {
                // Passwords dont match
                return array('response' => false, 'reason' => "The passwords you entered do not match. Please try again.");
            }
        } else {
            // Missing some data
            return array('response' => false, 'reason' => "One or more fields were missing. Please try again");
        }
    }
    
    // Function to verify the users account
    function verifyAccount($verifyString) {
        
        // Check its not blank
        if(!empty($verifyString)) {
            
            // Check if we have a user
            $query = "SELECT id, confirmed FROM users WHERE session_id = :session_id";
            $userData = $this->engine->database->query(0, $query, array(':session_id' => $verifyString));
                
            // Check if we got anything
            if(isset($userData[0])) {
                
                // Check if the account is already verified
                if($userData[0]['confirmed'] == '0') {
               
                    // Update the user account
                    $query = "UPDATE users SET confirmed = '1' WHERE id = :id";
                    $this->engine->database->query(2, $query, array(':id' => $userData[0]['id']));

                    return array('response' => true);
                } else {
                    return array('response' => false, 'reason' => 'Your account has already been confirmed. Please login below.');
                }
            } else {
                return array('response' => false);
            }
        }
    }
    
    // Function to authenticate 2FA
    function auth2fa($hash, $token) {
        
        require_once 'includes/libs/PHPGangsta/GoogleAuthenticator.php';
        
        // Start the class
        $ga = new PHPGangsta_GoogleAuthenticator();
        
        // Try and get the user based on the hash
        $query = "SELECT id, 2fa_secret, autologout FROM users WHERE session_id = :session_id";
        $userData = $this->engine->database->query(0, $query, array(':session_id' => $hash));

        if(isset($userData[0])) {
        
            // Get a secret
            $checkResult = $ga->verifyCode($userData[0]['2fa_secret'], $token, 4);
            
            if ($checkResult == true) {

                // Add a valid login
                $this->updateLoginLog(1,$userData[0]['id']);

                // Generate a new session ID
                session_regenerate_id();
                
                // Set the cookie
                setcookie("session_id", session_id(), time() + 60 * 60 * 24 * 7, '/', 'www.bitPeer.com', true, false);
                $_SESSION['session_id'] = session_id();

                // Reset the failed logins & session ID
                $query = "UPDATE users SET last_active = '".time()."', login_time = '".time()."', failed_logins = '0', session_id = :session_id WHERE id = :id";
                $this->engine->database->query(2, $query, array(':id' => $userData[0]['id'], ':session_id' => session_id()));

                return array('response' => true);

            } else {
                return array('response' => false, "reason" => 'Unable to verify 2 Factor Authentication. Please try again.');
            }
        } else {
            return array('response' => false, "reason" => 'Unable to verify 2 Factor Authentication. Please try again.');
        }
    }
    
    // Enable 2FA for a users account
    function enable2fa($secret) {
        
        // Check if not empty
        if(!empty($secret)) {
            $query = "UPDATE users SET 2fa_enabled = '1', 2fa_secret = :secret WHERE id = :id";
            $this->engine->database->query(2, $query, array(':id' => $this->user['id'], ':secret' => $secret));
        }
    }
    
    // Enable 2FA for a users account
    function disable2fa()
    {
        $query = "UPDATE users SET 2fa_enabled = '0', 2fa_secret = '' WHERE id = :id";
        $this->engine->database->query(2, $query, array(':id' => $this->user['id']));
    }
    
    // Function to ubblock the users account
    function unblockAccount($verifyString) {
        
        // Check its not blank
        if(!empty($verifyString)) {
            
            // Check if we have a user
            $query = "SELECT id FROM users WHERE session_id = :session_id AND active = '0'";
            $userData = $this->engine->database->query(0, $query, array(':session_id' => $verifyString));
                
            // Check if we got anything
            if(isset($userData[0])) {
               
                // Update the user account
                $query = "UPDATE users SET session_id = '', active = '1', failed_logins = '0' WHERE id = :id";
                $this->engine->database->query(2, $query, array(':id' => $userData[0]['id']));
                
                return true;
            } else {
                return false;
            }
        }
    }
    
    // Function to send an email to the user
    // blocked - account is blocked
    // failed - failed a login
    // register - new registration
    public function emailUser($type, $emailAddress = false, $params = false) {
        
        if($type == 'register') {
            
            $subject = 'BitPeer - Registration';
            $html = "Hi ".$params['name'].",<br /><br />Thanks for creating a BitPeer account! You’re almost ready to go!<br /><br />Before you can start using your account you need to verify your email address by clicking the link below:<br /><br /><a href='https://www.bitPeer.com/login/verify/".$params['link']."/'>https://www.bitPeer.com/login/verify/".$params['link']."/</a><br /><br />Once you’ve activated your account you can log in and start trading!<br /><br />Thanks,<br />BitPeer<br /><br />(This email was sent to ".$params['email']." by BitPeer)";
        
        } elseif ($type == 'failed') {
            
            $subject = 'BitPeer - Failed Login';
            $html = "Hi ".$params['name'].", <br /><br />An attempt was made to log in to BitPeer with invalid credentials, the details of which are below. Failed login attempts may be a sign of someone trying to gain unauthorised access to your account.<br /><br /><strong>IP Address:</strong> ".$this->getIp()."<br /><br />Please be aware that three consecutive failed logins will result in your account being disabled.<br/><br/> Thanks,<br/>BitPeer<br /><br />(This email was sent to ".$params['email']." by BitPeer)";
        
        } elseif ($type == 'blocked') {
            
            $subject = 'BitPeer - Account Disabled';
            $html = "Hi ".$params['name'].",<br /><br />Three or more attempts were made to login to BitPeer with invalid credentials. As a result your account has been disabled to help improve your account security.<br/><br/>Before you can login to your account again you will need to re-activate it by clicking the link below:<br/><br/><a href='https://www.bitPeer.com/login/unblock/".$params['link']."/'>https://www.bitPeer.com/login/unblock/".$params['link']."/</a><br/><br/> Thanks,<br/>BitPeer<br /><br />(This email was sent to ".$params['email']." by BitPeer)";
        
        } elseif ($type == 'withdraw') {
            
            $subject = 'BitPeer - Confirm Withdraw';
            $html = "Hi,<br /><br />You have requested a coin withdrawal at BitPeer.<br><br>Amount: ".number_format($params['amount'], 8, '.', '')."<br>Address: ".$params['address']."<br><br>Before it can be processed you need to confirm the request by clicking the link below:<br><a href='https://www.bitPeer.com/withdraw/confirm/".$params['hash']."'>https://www.bitPeer.com/withdraw/confirm/".$params['hash']."</a><br><br>If you did not make this request please disregard this email and contact support immediately.<br/><br/> Thanks,<br/>BitPeer<br /><br />(This email was sent to ".$this->user['email']." by BitPeer)";
            
        } elseif ($type == 'forgot') {
            
            $subject = 'BitPeer - Forgot Password';
            $html = "Hi ".$params['name'].",<br /><br />Somebody has completed the Forgotten Password form at BitPeer.com for this email address<br><br>Before you can reset your password, you need to confirm the request by clicking the link below:<br><a href='https://www.bitPeer.com/forgot/confirm/".$params['hash']."'>https://www.bitPeer.com/forgot/confirm/".$params['hash']."</a><br><br>If you did not make this request please disregard this email and contact support immediately.<br/><br/> Thanks,<br/>BitPeer";
            
        } elseif ($type == 'orderSubmit') {
            
            //$subject = 'BitPeer - New Order Submitted';
            //$html = "Hi,<br /><br />Somebody has completed the Forgotten Password form at BitPeer.com for this email address<br><br>Before you can reset your password, you need to confirm the request by clicking the link below:<br><a href='https://www.bitPeer.com/forgot/confirm/".$params['hash']."'>https://www.bitPeer.com/forgot/confirm/".$params['hash']."</a><br><br>If you did not make this request please disregard this email and contact support immediately.<br/><br/> Thanks,<br/>BitPeer";
            
        }
        
        // Check if we are taking the users email
        if($emailAddress == false) {
            $emailAddress = $this->user['email'];
        }
       
        if(isset($html)) {
            // Send the email
            $this->engine->general->send_mailgun($emailAddress, $subject, $html);
        }
    }
    
    // Update the users acitivity timer
    public function updateActivityTimer() {
        
        // Check if we have a logged in user
        if($this->isAuthenticated == true) {

            // Get the session from memcached
            if ($this->engine->memcache->cacheAvailable == true) {

                // Try and get the Users data from memcached
                $userData = $this->engine->memcache->get('user_'.$this->user['session_id']);

                if(is_array($userData)) {
                    $userData['last_active'] = time();
                    $this->engine->memcache->set('user_'.$this->user['session_id'], $userData, 3600);
                }
            } else {
                // Update the users timer in the DB
                $query = "UPDATE users SET last_active = '".time()."' WHERE id = :id";
                $this->engine->database->query(2, $query, array(':id' => $this->user['id']));
            }
            return true;
        }
    }
    
    // Function to update account profile
    public function updateProfile($data) {
        
        // Check we have the right fields
        if(isset($data['fullname'])) {
            
            // Handle the notification options
            $notificationOptions = array('login' => true, 'withdrawConfirm' => true);
            if(isset($data['notification'])) {
                foreach($data['notification'] AS $notifcation) {
                    $notificationOptions[$notifcation] = true;
                }
            }

            // Save the popupStyle to the notification options
            $notificationOptions['style'] = $data['popupStyle'];
            
            // Serialize the notifications
            $notificationOptions = serialize($notificationOptions);

            // Save the market preferences
            $marketPreferences = array();
            // Put default market at key 0
            $marketPreferences[0] = $data['defaultMarket'];
            if(is_array($data['marketPreferences'])) {
                foreach($data['marketPreferences'] AS $preference) {

                    // Explode the value
                    $preference = explode("-", $preference);

                    if(isset($preference[1])) {
                        $marketPreferences[$preference[0]] = $preference[1];
                    }
                }
            }

            // Serialize the array
            $marketPreferences = serialize($marketPreferences);

            // Handle saving the API data
            if(isset($data['api_keys'])) {
                if(is_array($data['api_keys'])) {
                    foreach($data['api_keys'] AS $apiKey) {

                        // Check if we got the right fields
                        if(isset($data[$apiKey.'-description']) && isset($data[$apiKey.'-permissions'])) {

                            // Update the API key
                            $query = "UPDATE api_keys SET description = :description, access = :access WHERE user_id = :user_id AND public = :public";
                            $this->engine->database->query(2, $query, array(':user_id' => $this->user['id'], ':description' => strip_tags($data[$apiKey.'-description']), ':access' => $data[$apiKey.'-permissions'], ':public' => $apiKey));
                        }
                    }
                }
            }
            
            // Update the user details
            $query = "UPDATE users SET name = :name, autologout = :autologout, notifications = :notifications, display_pref = :display_pref, api_enabled = :api_enabled, last_active = :last_active WHERE id = :id";
            $this->engine->database->query(2, $query, array(':id' => $this->user['id'], ':name' => strip_tags($data['fullname']), ':api_enabled' => $data['api_access'], ':display_pref' => $marketPreferences, ':autologout' => $data['autologout'], ':notifications' => $notificationOptions, ':last_active' => time()));
            
            // Update the password if the user wants it
            if(!empty($data['password']) && !empty($data['password2'])) {
                if($data['password'] == $data['password2']) {
                    
                    // Change the password
                    $query = "UPDATE users SET password = :password WHERE id = :id";
                    $this->engine->database->query(2, $query, array(':id' => $this->user['id'], ':password' => $this->hashPassword($data['password'])));
                    
                } else {
                    return array('response' => false, "reason" => 'The passwords you entered do not match. Please try again.');
                }
            }

            // Delete the data from memcached to force it to be called again
            if ($this->engine->memcache->cacheAvailable == true) {
                $this->engine->memcache->set('user_'.$this->user['session_id'], false, 300);
            }
            
            // We made it
            return array('response' => true, "reason" => 'Profile updated successfully.');
        } else {
            return array('response' => false, "reason" => 'Unable to update your profile. Please try again.');
        }
        
    }
    
    private function updateLoginLog($success, $overrideID = false)
    {
        // Add the IP to the history
        $query = "INSERT INTO users_login_log VALUES ('', :id, :ip, '" . time() . "', :success)";

        $inputs = array(
            ':id' => ($overrideID == false)?$this->user['id']:$overrideID,
            ':ip' => $this->getIp(),
            ':success' => $success
        );

        $this->engine->database->query(1, $query, $inputs);
    }

    // Function to send teh reset password email to a user
    public function sendResetPassword($emailAddress)
    {
       
        // Check if the user exists?
        $query = "SELECT name,security_answer_1, security_answer_2 FROM users WHERE email = :email";
        $userData = $this->engine->database->query(0, $query, array(':email' => $emailAddress));
        
        // Check if we got some data?
        if(isset($userData[0])) {
            
            // Generate a hash
            $verifyHash = $this->hashPassword($userData[0]['security_answer_1'].$userData[0]['security_answer_1'].time());
            
            // Email the user
            $this->emailUser('forgot', $emailAddress, array('name' => $userData[0]['name'], 'hash' => $verifyHash));
            
            // Save it to the DB
            $query = "UPDATE users SET hash = :hash WHERE email = :email";
            $userData = $this->engine->database->query(2, $query, array(':hash' => $verifyHash, ':email' => $emailAddress));
            
            return array('response' => true);
            
        } else {
            return array('response' => false, "reason" => 'Unable to locate an account with that email address'); 
        }
        
    }
    
    public function validateForgotPassword($hash)
    {
       
        // Check if the user exists?
        $query = "SELECT security_question_1, security_question_2 FROM users WHERE hash = :hash";
        $userData = $this->engine->database->query(0, $query, array(':hash' => $hash));
        
        // Check if we got some data?
        if(isset($userData[0])) {
            
            return array('response' => true, "data" => $userData[0]);
            
        } else {
            return array('response' => false, "reason" => 'Unable to locate an account with that email address'); 
        }
    }
    
    public function resetPassword($data) {
        
        // Check if we have enough data
        if(isset($data['answer1']) && isset($data['answer2']) && isset($data['password']) && isset($data['password2']) && isset($data['hash'])) {
            
            // Check if the passwords match
            if($data['password'] == $data['password2']) {
                
                // Get the users security answers
                $query = "SELECT security_answer_1, security_answer_2 FROM users WHERE hash = :hash";
                $userData = $this->engine->database->query(0, $query, array(':hash' => $data['hash']));
                
                // Check we still got it
                if(isset($userData[0])) {
            
                    // Validate the hashes
                    if($this->validateHash($userData[0]['security_answer_1'], $data['answer1']) & $this->validateHash($userData[0]['security_answer_2'], $data['answer2'])) {
                        
                        // Reset the users password
                        $query = "UPDATE users SET password = :password, hash = '', session_id = '', active = '1', failed_logins = '0'  WHERE hash = :hash";
                        $userData = $this->engine->database->query(2, $query, array(':hash' => $data['hash'], ':password' => $this->hashPassword($data['password'])));
                        
                        return array("response" => true);
                    } else {
                        return array('response' => false, "reason" => 'Unable to verify your account details. Please try again.'); 
                    }
                } else {
                    return array('response' => false, "reason" => 'Unable to verify your account details. Please try again.'); 
                }
            } else {
                return array('response' => false, "reason" => 'Unable to verify your account details. Please try again. '); 
            }
        } else {
            return array('response' => false, "reason" => 'Unable to verify your account details. Please try again. '); 
        }
    }

    /**
     * Hashes a users password using a random salt
     *
     * @param   string  $password  The password to be hashed
     * @return  string             A hashed string
     */
    public function hashPassword($password)
    {

        // Set the static salt -- DO NOT CHANGE THIS AS EXISTING PASSWORDS WILL FAIL
        $staticSalt = 'M1ntp4Lr0cks';
        $randomSalt = $this->generateRandomSalt();

        // Generate the complex salt
        $complexSalt = substr(sha1($staticSalt . $randomSalt), 0, $this->saltLength);

        // Hash the password with SHA1 and return it
        return sha1($password . $complexSalt) . $complexSalt;
    }

    /**
     * Validates a password and its hash
     *
     * @param   string   $passwordHash  The hash to validate
     * @param   string   $password      The password string
     * @return  boolean                 True if the hash validates, false otherwise
     */
    public function validateHash($passwordHash, $password)
    {

        // Get the salt
        $salt = substr($passwordHash, strlen($passwordHash) - $this->saltLength, strlen($passwordHash));

        // Now hash the plaintext password and check if it's the same as the password hash
        if (sha1($password . $salt) . $salt == $passwordHash) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Generates a random salt to use in password hashing
     *
     * @return  string  A random string to the length of saltLength
     */
    private function generateRandomSalt()
    {

        // The range of characters that can appear in the salt
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"£$%^&*()_-=';

        $salt = '';

        for ($p = 0; $p < $this->saltLength; $p++) {
            // Append a random character to the salt string on each pass
            $salt .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        return $salt;
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


    /**
     * Fetches a list of login attempts by the user, both successful and unsuccessful
     *
     * @param   $userId  int    The ID of the user
     * @param   $number  int    The number of items to return
     * @param   $offset  int    The starting point
     * @return           array  An array of the login attempts made by the user
     */
    function getLoginHistory($userId, $number = 30, $offset = 0)
    {

        // Fetch orders ordered by most recent
        $query = "SELECT login_ip, login_time, login_success FROM users_login_log WHERE login_user_id = :user ORDER BY login_time DESC LIMIT :offset,:limit";

        $inputs = array(
            ':user' => $userId
        );

        $limits = array(
            ':offset' => $offset,
            ':limit'  => $number
        );

        $logins = $this->engine->database->query(0, $query, $inputs, $limits);

        foreach ($logins AS $key => $value) {
            $logins[$key]['login_time_formatted'] = date("Y-m-d H:i:s", $value['login_time']);
        }

        return $logins;

    }

    /**
     * The total number of login attempts by the user in the log
     *
     * @param   $userId     int  The ID of the user
     * @return              int  The number of login attempts
     */
    function getTotalLoginHistory($userId)
    {

        // Fetch orders ordered by most recent
        $query = "SELECT COUNT(login_id) AS count FROM users_login_log WHERE login_user_id = :user";

        $inputs = array(
            ':user' => $userId
        );

        $count = $this->engine->database->query(0, $query, $inputs);

        return $count[0]['count'];

    }

}