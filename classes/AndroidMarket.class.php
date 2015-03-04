<?php
require_once(__DIR__ . '/../proto/download.php');
require_once(__DIR__ . '/../proto/error.php');

class DownloadFailedException extends Exception {}
class DownloadPreparationFailedException extends Exception {}

class AndroidMarket
{
    protected $login;
    protected $auth;
    public $deviceid;

    
    public function __construct(GoogleAccount $login, $deviceid)
    {
        $this->login = $login;
        $this->deviceid = $deviceid;
        $logindata = $this->login->login('androidmarket');
        if (!isset($logindata['auth'])) {
            throw new GoogleLoginFailedException(_('Login failed: No auth key received.'));
        }
        $this->auth = $logindata['auth'];
    }

    /**
     * Fetch the download information for given package.
     *
     * @param string $package
     *  The package to download.
     *
     * @return array
     *  Array containing the download url, cookie and package name.
     *  For example: array(
     *      'url' => 'http://android.clients.google.com/ ...',
     *      'marketda' => 'MarketDA=34a2 ...',
     *      'package' => 'com.netflix.mediaclient',
     *  );
     *
     * @throws DownloadPreparationFailedException
     *  If something goes wrong during preparation of download.
     */
    public function fetchDownloadInfo($package)
    {
        $query = array(
            'doc' => $package,
        );
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'https://android.clients.google.com/fdfe/purchase');
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($query));
        curl_setopt($curl, CURLOPT_USERAGENT,
            'Android-Finsky/4.6.17 (api=3,versionCode=80260017)');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: GoogleLogin auth=$this->auth",
            "X-DFE-Device-Id: $this->deviceid"
        ));

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);

        // Get result and status code of request.
        $httpresult = curl_exec($curl);
        $httpstatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpstatus === 28) {
            throw new DownloadPreparationFailedException(_('Failed to fetch download information: Request timed out.'));
        } elseif ($httpstatus !== 200) {
            mail(ERROR_MAIL, 'Download Failed', "Package:$package\nHTTP-Status:$httpstatus\n\n$httpresult");

            throw new DownloadPreparationFailedException(_(
"Oh no! Something went wrong. Possible reasons are:
    1. The app might no longer be available on the Play Store.
    2. App-Download.org usually simulates a Nexus 5. The app might not be available for that device.
Please excuse this incident. The administrator has been notified."
            ));
        }

        // Get marketda cookie and download url from response.
        try {
            // Walk through nested messages.
            $message_a = new DownloadMessageA($httpresult);
            $message_b = $message_a->getNext();
            $message_c = $message_b->getNext();
            $message_d = $message_c->getNext();
            $downloadinfo = $message_d->getDownloadInformation();

            // Get download information.
            $url = $downloadinfo->getUrl();
            $marketda = $downloadinfo->getCookie()->getValue();

            if (empty($url) || empty($marketda)) {
                throw new \DrSlump\Protobuf\Exception();
            }
        } catch (\DrSlump\Protobuf\Exception $e) {
            throw new DownloadPreparationFailedException(_('Could not get download information for given package.'));
        }

        return array(
            'url' => $url,
            'marketda' => $marketda,
            'package' => $package,
        );
    }

    /**
     * Download app from given location
     * with given download information.
     *
     * @param string $url
     *  The url from where to download.
     *
     * @param $marketda
     *  The MarketDA cookie to use for the download.
     */
    public static function download($url, $marketda)
    {
        // Use curl to pass through the apk to the user.
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_COOKIE, "MarketDA=$marketda");
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);

        curl_exec($curl);
        curl_close($curl);
    }
}
