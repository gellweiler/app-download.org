<?php
/**
 * @file
 *  Device management for users (list devices and add new devices).
 */

class DeviceManagerException extends Exception {}

class DeviceManager
{
    /**
     * @var User
     *  The user for which to manage devices.
     */
    protected $user;

    /**
     * @var GoogleAccount
     *  A Google account to use for checking in new devices.
     */
    protected $login;

    /**
     * @param User $user
     *  The user for which to manage devices.
     *
     * @param GoogleAccount $login
     *  A Google account to use for checking in new devices.
     */
    public function __construct(User $user, GoogleAccount $login)
    {
        $this->user = $user;
        $this->login = $login;
    }

    /**
     * Get's a list of all devices belonging to this user.
     *
     * @return array
     *  All devices in the form:
     *  array(
     *      $device_name => $deviceid,
     *      ...
     *  );
     */
    public function listDevices()
    {
        $result = array();

        // Query a list of devices from the db.
        $sql =
<<<'EOF'
SELECT name, deviceid
FROM devices
WHERE owner=:uid
EOF;
        $db = DB::getInstance()->db;
        $sth = $db->prepare($sql);
        $sth->bindValue(':uid', $this->user->uid, SQLITE3_INTEGER);
        $res = $sth->execute();

        // Transform result of query into key => value notation.
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $result[($row['name'])] = $row['deviceid'];
        }

        return $result;
    }

    /**
     * Check-in a new device and add it to the user.
     *
     * @param string $device_type
     *  The device type name as in /devices (ex. galaxy-nexus).
     *
     * @param string $device_name
     *  A human readable name of the device, so that the user can identify his device.
     *
     * @param string $device_local
     *  The value to set local to.
     *
     * @throws DeviceManagerException
     *  If inserting a record for the new device fails.
     */
    public function checkinNewDevice($device_type, $device_name, $device_local)
    {
        // Check-in new device.
        $checkin = new Checkin($this->login);
        $checkin->changeDevice($device_type);
        $checkin->local = $device_local;
        $deviceid = $checkin->checkin();

        $sql =
<<<'EOF'
INSERT INTO devices(owner, name, deviceid)
VALUES(:uid, :name, :deviceid)
EOF;
        $db = DB::getInstance()->db;
        $sth = $db->prepare($sql);
        $sth->bindValue(':uid', $this->user->uid, SQLITE3_INTEGER);
        $sth->bindValue(':name', $device_name, SQLITE3_TEXT);
        $sth->bindValue(':deviceid', $deviceid, SQLITE3_TEXT);
        if ($status = $sth->execute() === FALSE) {
            throw new DeviceManagerException(_('Could not create a record for the new device in the DB.'));
        }
    }

    /**
     * Get the name of the device with given deviceid.
     *
     * @param string
     *  The device id as hex string.
     *
     * @return mixed
     *  The device name as string or FALSE if the user has no device registrated with that device id.
     */
    public function deviceName($deviceid)
    {
        $sql =
<<<'EOF'
    SELECT name
    FROM devices
    WHERE owner = :uid AND LOWER(deviceid) = LOWER(:deviceid)
EOF;

        $db = DB::getInstance()->db;
        $sth = $db->prepare($sql);
        $sth->bindValue(':uid', $this->user->uid, SQLITE3_INTEGER);
        $sth->bindValue(':deviceid', $deviceid, SQLITE3_TEXT);
        $res = $sth->execute();

        $row = $res->fetchArray(SQLITE3_ASSOC);
        if ($row === FALSE) {
            return FALSE;
        }

        return $row['name'];
    }

    /**
     * List all available devices that can be checked in.
     *
     * @return array
     *  All available devices in the form:
     *  array(
     *      $machine_name => $human_name,
     *      ...
     *  );
     */
    public static function listAvailableDevices()
    {
        return parse_ini_file(__DIR__ . '/../devices/devices.ini');
    }

    /**
     * Add list of common devices to account.
     * Used for anonymous accounts. Skips devices
     * that are allready attached to device.
     */
    public function addCommonDevices()
    {
        // Only registrer devices that haven't been registrated yet for the account.
        $old = array_keys($this->listDevices());
        foreach (unserialize(COMMON_DEVICES) as $lang => $params) {
            if (!in_array($params[0], $old)) {
               $this->checkinNewDevice($params[1], $params[0], $lang);
            }
        }
    }
}
