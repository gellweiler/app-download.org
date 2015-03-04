<?php
require_once(__DIR__ . '/../proto/logs.php');
require_once(__DIR__ . '/../proto/config.php');
require_once(__DIR__ . '/../proto/checkin.php');

class CheckinException extends Exception {}

class Checkin
{
    /**
     * @var GoogleAccount
     *  The account with which to authenticate.
     */
    protected $login;

    /**
     * @var string
     *  The session id received on logging in.
     */
    protected $sid;

    /**
     * @var string
     *  The local (country and language) of the device.
     */
    public $local = "en_US";

    /**
     * @var string
     *  The device configuration to use.
     *  An ini file under /devices hast to exist for the given $device.
     *  Should be only changed through changeDevice().
     */
    private $device = 'galaxy-nexus';

    public function __construct(GoogleAccount $login)
    {
        $this->login = $login;
        $logindata = $this->login->login('print');
        if (!isset($logindata['sid'])) {
            throw new GoogleLoginFailedException(_('Login failed: No SID received.'));
        }
        $this->sid = $logindata['sid'];
    }

    /**
     * Generate a new random valid Meid (Imei).
     */
    protected function generateMeid()
    {
        // Use a well known tac as the base of the Imei.
        // In this case: Google Nexus 5.
        $tac = '35824005';

        // Generate a 6 digits random serial number.
        $serial = rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9);

        // Calculate luhn checksum digit.
        $luhn = luhn($tac.$serial);

        return $tac.$serial.$luhn;
    }

    /**
     * Generate a new random valid Mac address.
     */
    protected function generateMacAddr()
    {
        // Use a valid base for the mac address generator.
        $base = 'b407f9';

        // Generate a 6 places long random hex serial number.
        $serial =  '';
        for ($i = 0; $i < 6; $i++) {
            $serial .= dechex(rand(0x0, 0xF));
        }

        return $base.$serial;
    }

    /**
     * Generate a new random serial number.
     */
    protected function generateSerialNumber()
    {
        $base = '3933E6';

        // Generate a 10 places long random hex serial number.
         $serial =  '';
        for ($i = 0; $i < 10; $i++) {
            $serial .= dechex(rand(0x0, 0xF));
        }

        return $base.$serial;
    }

    /**
     * Generate a new random logging id.
     */
    protected function generateLoggingId()
    {
        // Generate a new 8-bit random number.
        $id = 0;
        for ($i = 0; $i < 8; $i++) {
            $id = ($id << 8) | rand(0, 16);
        }
        return $id;
    }

    /**
     * Change the device.
     *
     * @param String $device.
     *  The device type name as defined in devices.ini.
     *
     * @throws CheckinException
     *  If the device is not supported.
     */
    public function changeDevice($device)
    {
        // Filter device name, for safe use in file names.
        $device_e = preg_replace('/[^a-zA-Z0-9\\-_]/', '', $device);

        $file = __DIR__ . "/../devices/$device_e.ini";
        // Check that configuration for given device exists.
        if (!file_exists($file)) {
            throw new CheckinException(_f("Device %s is not supported.", $device_e));
        }

        // Set device.
        $this->device = $device;
    }

    /**
     * Generate the check-in message.
     *
     * @return String
     *  Protobuffer raw message.
     */
    public function checkinMessage()
    {
        // Load device configuration.
        $file = __DIR__ . "/../devices/$this->device.ini";
        $config = parse_ini_file($file, TRUE);

        $meid = $this->generateMeid();
        $serial_number = $this->generateSerialNumber();
        $mac_addr = $this->generateMacAddr();
        $logging_id = $this->generateLoggingId();

        // Build device proto.
        // Read in device settings from config.
        $device = new AndroidBuildProto();
        foreach (array(
            'fingerprint' => 'setId',
            'board' => 'setProduct',
            'carrier' => 'setCarrier',
            'baseband' => 'setRadio',
            'client' => 'setClient',
            'bootloader' => 'setBootloader',
            'services' => 'setGoogleServices',
            'device' => 'setDevice',
            'sdk' => 'setSdkVersion',
            'model' => 'setModel',
            'manufacturer' => 'setManufacturer',
            'product' => 'setBuildProduct',
            'ota' => 'setOtaInstalled',
        ) as $ini_key => $proto_method) {
            if (isset($config['Device'][$ini_key])) {
                foreach ((array) $config['Device'][$ini_key] as $value) {
                    $device->$proto_method($value);
                }
            }
        }
        $device->setTimestamp(time());

        // Build device configuration proto.
        // Read in hardware settings.
        $device_config = new DeviceConfigurationProto();
        foreach(array(
            'cpu' => 'addNativePlatform',
            'touchscreen' => 'setTouchScreen',
            'keyboard' => 'setKeyboard',
            'navigation' => 'setNavigation',
            'screenlayout' => 'setScreenLayout',
            'hardkeyboard' => 'setHasHardKeyboard',
            'fivewaynav' => 'setHasFiveWayNavigation',
            'density' => 'setScreenDensity',
            'screenwidth' => 'setScreenWidth',
            'screenheight' => 'setScreenHeight',
        ) as $ini_key => $proto_method) {
            if(isset($config['Hardware'][$ini_key])) {
                foreach ((array) $config['Hardware'][$ini_key] as $value) {
                    $device_config->$proto_method($value);
                }
            }
        }
        // Read in software settings.
        foreach(array(
            'gleversion' => 'setGLEsVersion',
            'libs' => 'addSystemSharedLibrary',
            'features' => 'addSystemAvailableFeature',
            'locals' => 'addSystemSupportedLocale',
            'glexts' => 'addGlExtension',
        ) as $ini_key => $proto_method) {
            foreach ((array) $config['Software'][$ini_key] as $value) {
                $device_config->$proto_method($value);
            }
        }

        // Logging event.
        $event = new AndroidEventProto();
        $event->setTag('event_log_start');
        $event->setTimeMsec(time() * 1000);

        // Checkin.
        $checkin = new AndroidCheckinProto();
        $checkin->setBuild($device);
        $checkin->setLastCheckinMsec(0);
        $checkin->addEvent($event);
        $checkin->setCellOperator("310260"); // T-Mobile
        $checkin->setSimOperator("310260");  // T-Mobile
        $checkin->setRoaming("mobile-notroaming");
        $checkin->setUserNumber(0);

        $request = new AndroidCheckinRequest();
        $request->setId(0);
        $request->setDigest("1-929a0dca0eee55513280171a8585da7dcd3700f8");
        $request->setCheckin($checkin);
        $request->setLocale($this->local);
        $request->setLoggingId($logging_id);
        $request->addMacAddr($mac_addr);
        $request->setMeid($meid);
        $request->addAccountCookie('[' . $this->login->email . ']');
        $request->addAccountCookie($this->sid);
        $request->setTimeZone("America/New_York");
        $request->addMacAddrType("wifi");
        $request->setFragment(0);

        // securityToken;
        $request->setVersion(3);
        $request->addOtaCert("71Q6Rn2DDZl1zPDVaaeEHItd");
        $request->setSerialNumber($serial_number);
        $request->setDeviceConfiguration($device_config);

        return $request->serialize();
    }

    /**
     * Send a check-in request to google.
     *
     * @return String
     *  The device id of the new device as hex string.
     *
     * @throws CheckinException
     *  If the check-in request fails.
     */
    public function checkin()
    {
        $deviceid = '';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://android.clients.google.com/checkin');
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_USERAGENT, 'Android-Checkin/2.0 (maguro JRO03L); gzip');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-protobuffer',
            'Content-Encoding: gzip',
        ));
        curl_setopt($curl, CURLOPT_POSTFIELDS, gzencode($this->checkinMessage()));

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);

        // Get result and status code of request.
        $httpresult = curl_exec($curl);
        $httpstatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpstatus === 28) {
            throw new CheckinException(_('Failed to check-in device: Request timed out.'));
        } elseif ($httpstatus !== 200) {
            throw new CheckinException(_f('Failed to check-in device: %s', $httpresult));
        }

        if ($httpstatus === 200) {
            $response = new AndroidCheckinResponse($httpresult);
            $deviceid = $response->getAndroidId();
            if (empty($response) || !is_numeric($deviceid)) {
                throw new CheckinException(_('Failed to check-in device: Received no deviceid.'));
            }
        }

        curl_close($curl);

        return dec2hex($deviceid);
    }
}
