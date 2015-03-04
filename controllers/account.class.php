<?php
class AccountException extends Exception {}

class account extends Controller
{
    protected function __construct() {
        parent::__construct();
    }

    /**
     * Show account widget.
     */
    public function _widget()
    {
        $this->view->account =& Session::getInstance()->account;

        // Get current state of the application.
        $this->view->state = Session::getInstance()->appstate;

        // Check if this widget is currently active.
        $this->view->active = (Session::getInstance()->appstate == AppState::START);

        // Check if the user is logged in in incognito mode.
        $this->view->incognito = Session::getInstance()->incognito;

        $this->_render();
    }

    /**
     * This widget is shown on index so show index.
     */
    public function render()
    {
        index::getInstance()->render();
    }

    /**
     * Login the user to google with the data provided from
     * the login form or with a fake account.
     */
    public function login()
    {
        if (Session::getInstance()->appstate >= AppState::LOGGEDIN) {
            throw new AccountException(_('You are already logged in.'));
        }
        if (!valid_token()) {
            throw new AccountException('Invalid token.');
        }
        if (empty($_POST['terms'])) {
            throw new AccountException('You need to accept the Terms of Service (AGB).');
        }
        if (empty($_POST['type'])
          || !in_array($_POST['type'], array('identity', 'anonymous'))) {
            throw new AccountException('Form not submitted with one of the buttons.');
        }
        
        // Support two types of login through one form.
        if ($_POST['type'] == 'identity') {
            if (empty($_POST['email'])
             || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                throw new AccountException('Email adress is invalid.');
            }
            if (empty($_POST['password'])) {
                throw new AccountException('No passord provided.');
            }
            
            $this->_login($_POST['email'], $_POST['password']);
        }
        else {
            $accounts = unserialize(FAKE_ACCOUNTS);
            // Pick a random account from a list of fake accounts.
            $email = array_rand($accounts);
            $password = $accounts[$email];

            // Mark that the user is logged in using an incognito account.
            Session::getInstance()->incognito = TRUE;

            $this->_login($email, $password);
        }

        $this->render();
    }

    /**
     * Login user background function.
     */
    protected function _login($email, $password)
    {
        // Store account in session.
        Session::getInstance()->account =
         new GoogleAccount($email, $password);
        Session::getInstance()->appstate = AppState::LOGGEDIN;

        // Login user to local account and create a new device manager for that user.
        Session::getInstance()->user = new User(Session::getInstance()->account);
        Session::getInstance()->device_manager = new DeviceManager(
            Session::getInstance()->user,
            Session::getInstance()->account
        );
    }

    /**
     * Log out user (destroy session).
     */
    public function logout()
    {
        if (Session::getInstance()->appstate < AppState::LOGGEDIN) {
            throw new AccountException('You can\'t logout because you\'re not logged in.');
        }
        if (!valid_token()) {
            throw new AccountException('Invalid token.');
        }

        reset_session();

        $this->render();
    }
}
