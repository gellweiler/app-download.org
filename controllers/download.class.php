<?php
class DownloadException extends Exception {}

class download extends Controller
{
    protected function __construct()
    {
        parent::__construct();

        // Set this to an url to provide a download.
        $this->view->download = '';
    }

    /**
     * Show the download widget.
     */
    public function _widget()
    {
        // Check if this widget is currently active.
        $this->view->active = (Session::getInstance()->appstate == AppState::MARKET);

        // The current state of the application.
        $this->view->state = (Session::getInstance()->appstate);

        $this->_render();
    }

    /**
     * This widget is shown on index, so show index.
     */
    public function render()
    {
        index::getInstance()->render();
    }



    /**
     * Prepare download for package received via $_GET.
     *
     * @throws DownloadException
     *  If invalid data was sent or the package does not exists.
     */
    public function prepare()
    {
        if (Session::getInstance()->appstate !== AppState::MARKET) {
            throw new DownloadException('You need to log in and select a device before you can download.');
        }
        if (!valid_token()) {
            throw new DownloadException('Invalid token.');
        }
        if (!isset($_GET['package'])) {
            throw new DownloadException('Packkage name is required.');
        }
        $pattern = '^([a-zA-Z_]{1}[a-zA-Z0-9_]*(\\.[a-zA-Z_]{1}[a-zA-Z0-9_]*)*)?$';
        if (preg_match("/$pattern/", $_GET['package']) !== 1) {
            throw new DownloadException('Given package name is not a valid java name.');
        }

        // Fetch download information from google.
        $download_info = Session::getInstance()->market->fetchDownloadInfo($_GET['package']);

        $this->_prepare($download_info);
    }

    /**
     * Generate download link for given download info
     * and redirect user to download.
     */
    protected function _prepare($download_info)
    {
        // Generate a unique and secure token for the download
        $raw_token = hash('sha256', uniqid() . openssl_random_pseudo_bytes(256), TRUE);
        $download_token = rtrim(strtr(base64_encode($raw_token), '+/', '-_'), '=');

        // Store the download information in the DB.
        $pdo = DB::getInstance()->db;
        $sth = $pdo->prepare(
<<<EOF
    INSERT INTO downloads (token, url, marketda, package, expires)
    VALUES (:token, :url, :marketda, :package, strftime('%s', 'now') + :ttl);
EOF
        );
        $sth->bindValue(':token', $download_token);
        $sth->bindValue(':url', $download_info['url']);
        $sth->bindValue(':marketda', $download_info['marketda']);
        $sth->bindValue(':package', $download_info['package']);
        $sth->bindValue(':ttl', DOWNLOAD_ENTRY_TTL);
        $sth->execute();

        $url = url('download/download/' . $download_token, array(), TRUE);
        header ('Location: ' . $url, TRUE, 302);
    }

    /**
     * Start download for given download token.
     */
    public function download($download_token = NULL)
    {
        if (empty($download_token)) {
            throw new DownloadException('No download token given.');
        }

        // Get info for given download.
        $pdo = DB::getInstance()->db;
        $sth = $pdo->prepare(
<<<EOF
    SELECT url, marketda, package FROM downloads
    WHERE token = :token AND expires > strftime('%s', 'now');
EOF
        );
        $sth->bindValue(':token', trim($download_token));
        $result = $sth->execute()->fetchArray(SQLITE3_ASSOC);

        if (empty($result)) {
            throw new DownloadException('No download requested for given token. Or download expired.');
        }

        // Give the file a nice name.
        $filename = preg_replace('/[^A-Za-z0-9\\-_\\.]/', '_', $result['package']);
        header("Content-Disposition: attachment; filename=\"$filename.apk\"");
        header('Content-Type: application/vnd.android.package-archive');

        // Ugly workaround for bug in built-in android browser.
        if (
          isset($_SERVER['HTTP_X_REQUESTED_WITH'])
          && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'com.android.browser')
          && isset($_SERVER['HTTP_ACCEPT_ENCODING'])
          && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'identity') === FALSE)
        ) {
            // The built-in android browser will make two requests for the apk
            // file, one with Accept-Encoding gzip and deflate and one with
            // Accept-Encoding identity. But it will only serve the second request
            // with Accept encoding identity. So stop the first request.

            // If the first request is shown, the workaround didn't work.
            die ("Oh no! I'm sorry. The built-in android browser has a nasty bug."
            . " It's hard to work around. If you see this the workaround failed."
            . " Please try downloading with a different browser.");
        }

        // Flush headers.
        ob_end_flush();
        flush();

        // Delete this download entry
        // and other expired entries.
        $pdo = DB::getInstance()->db;
        $sth = $pdo->prepare(
<<<EOF
    DELETE FROM downloads
    WHERE token = :token OR expires < strftime('%s', 'now');
EOF
        );
        $sth->bindValue(':token', trim($download_token));
        $status = $sth->execute();

        if ($status === FALSE) {
            throw new DownloadException('Could not delete download record.');
        }

        // Start download.
        AndroidMarket::download(
            $result['url'],
            $result['marketda']
        );
    }

    /**
     * Download requested package direct (without needing to be logged in).
     */
    public function direct($package = NULL)
    {
        if(empty($package)) {
            throw new DownloadException('Packagename is required.');
        }

        // Pick a random account from a list of fake accounts.
        $accounts = unserialize(FAKE_ACCOUNTS);
        $email = array_rand($accounts);
        $password = $accounts[$email];
        $account = new GoogleAccount($email, $password);

        // Get device manager for account.
        $device_manager = new DeviceManager(new User($account), $account);

        // Get registrated device for account with current language.
        // Make sure anonymous account has common devices.
        $device_manager->addCommonDevices();
        $devices = $device_manager->listDevices();

        $common_devices = unserialize(COMMON_DEVICES);
        $languages = unserialize(LANGUAGES);
        $device_id = $devices[$common_devices[$languages[LANGUAGE]][0]];

        // Get Market object for account and device.
        $market = new AndroidMarket($account, $device_id);

        // Get download info.
        $info = $market->fetchDownloadInfo($package);

        $this->_prepare($info);
    }
}
