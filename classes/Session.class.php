<?php
/**
 * A Singleton class that lives in $_SESSION.
 * To manage session wide variables.
 */
class Session
{
    private function __construct() {
        $this->token = token_generate();
    }

    /**
     * @var GoogleAccount
     *  The google account the user will log into.
     */
    public $account;

    /**
     * @var Boolean
     *  Set to true if the user is using an anonymous account.
     */
    public $incognito = FALSE;

    /**
     * @var User
     *  Object for user managment.
     */
    public $user;

    /**
     * @var AndroidMarket
     *  Object to talk to the android market.
     */
    public $market;

    /**
     * @var DeviceManager
     *  Object to manage devices.
     */
    public $device_manager;

    /**
     * @var
     *  The current state the application is in.
     *  States are defined in the AppState enum.
     */
    public $appstate = AppState::START;

    /**
     * @var string
     *  A token to prevent CSRF attacks.
     */
    public $token = '';

    /**
     * Get or create the instance of this class.
     *
     * @return Session
     *  The singleton of this class.
     */
    public static function getInstance()
    {
        if (!defined('SESSION_STARTED')) {
            session_start();
            define('SESSION_STARTED', TRUE);
        }
        if(!isset($_SESSION['session'])) {
            $_SESSION['session'] = new self();
        }
        
        return $_SESSION['session'];
    }
}
