<?php
/**
 * @file
 *  Widget for managing devices.
 */

class DeviceException extends Exception {}

class device extends Controller
{
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Creates a new device from $_POST data.
     *
     * @throws DeviceException
     *  If invalid data was sent.
     */
    public function add()
    {
        if (Session::getInstance()->appstate !== AppState::LOGGEDIN) {
            throw new DeviceException('You can\'t add a new device now.');
        }
        if (Session::getInstance()->incognito) {
            throw new DeviceException(
                'You can\'t create new devices if your using the incognito login mode');
        }
        if (!valid_token()) {
            throw new DeviceException('Invalid token.');
        }
        if (empty($_POST['device-name'])) {
            throw new DeviceException('You need to give your new device a name.');
        }
        if (empty($_POST['device-type'])
          || !in_array($_POST['device-type'], array_keys(DeviceManager::listAvailableDevices()))) {
            throw new DeviceException('You need to pick a device from the list.');
        }
        if (empty($_POST['device-country'])
          || !in_array($_POST['device-country'], array_keys(LanguageCodes::$languages))) {
            throw new DeviceException('You need to choose a language for the device.');
        }

        // Sanitize name.
        $name = preg_replace('/[^a-zA-Z0-9äÄöÖüÜß\\.\\-éÉèÈâÂêÊŷŶáÁàÀ@?! ]/', '_', $_POST['device-name']);

        // Create the new device.
        Session::getInstance()->device_manager->checkinNewDevice($_POST['device-type'], $name, $_POST['device-country']);

        $this->render();
    }

    /**
     * Sets the device id.
     *
     * @param Integer $id
     *  The device id.
     *
     * @throws DeviceException
     *  If invalid data was sent.
     */
    public function deviceid($id)
    {
        if (Session::getInstance()->appstate !== AppState::LOGGEDIN) {
            throw new DeviceException('You can\'t select a device now.');
        }
        if (!valid_token()) {
            throw new DeviceException('Invalid token.');
        }
        if (empty($id)) {
            throw new DeviceException('The device id is required.');
        }
        if (preg_match('/^[0-9a-fA-F]+$/', $id) !== 1) {
            throw new DeviceException('The device id has to be a hexadecimal number.');
        }

        // Log into market with new device id.
        Session::getInstance()->market = new AndroidMarket(
            Session::getInstance()->account,
            $id
        );
        Session::getInstance()->appstate = AppState::MARKET;

        $this->render();
    }

    /**
     * Deselect the device by changing back the state.
     */
    public function change()
    {
        if (Session::getInstance()->appstate < AppState::MARKET) {
            throw new DeviceException('You can\'t change the device because you haven\'t selected one.');
        }

        Session::getInstance()->appstate = AppState::LOGGEDIN;

        $this->render();
    }

    /**
     * This widget is shown on index so show index.
     */
    public function render()
    {
        // Display index page.
        index::getInstance()->render();
    }

    /**
     * Show the widget.
     */
    public function _widget()
    {
        // Check if this widget is currently active.
        $this->view->active = (Session::getInstance()->appstate == AppState::LOGGEDIN);

        // The current state of the application.
        $this->view->state = (Session::getInstance()->appstate);

        // Check if the user is using an incognito account.
        $this->view->incognito = Session::getInstance()->incognito;

        if (Session::getInstance()->appstate >= AppState::LOGGEDIN) {
            // Attach common devices to incognito account.
            // If an incognito account is used.
            if (Session::getInstance()->incognito) {
                Session::getInstance()->device_manager->addCommonDevices();
            }

            // A list of devices the user has registrated.
            $this->view->devices = Session::getInstance()->device_manager->listDevices();
        }

        $this->_render();
    }
}
