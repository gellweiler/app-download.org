<?php
class GoogleLoginFailedException extends Exception {}

class GoogleAccount
{
    public $email;
    public $firstname;
    public $lastname;

    protected $password;

    /**
     * Login in into google with email and password.
     */
    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;

        // Get account data.
        $data = $this->login('ac2dm', array(
            'add_account' => '1',
            'lang' => 'en_US',
        ));

        $this->firstname = $data['firstname'];
        $this->lastname = $data['lastname'];
    }

    /**
     * Login into any google service using the REST api used by android
     * and return extracted data as array matching field => value all
     * field names are converted to lower case.
     *
     * @throws {GoogleLoginFailedException}
     *  Error that get's thrown when the login fails.
     */
    public function login($service, $extra_query = array())
    {
        $result = array();

        $curl = curl_init();

        // Build the request.
        $query = array_merge(array(
            'Email' => $this->email,
            'Passwd' => $this->password,
            'service' => $service,
        ), $extra_query);

        curl_setopt($curl, CURLOPT_URL, 'https://android.clients.google.com/auth');
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($query));
        curl_setopt($curl, CURLOPT_USERAGENT, 'GoogleAuth/1.4 (generic KK)');

        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE); 
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, TRUE);

        // Get result and status code of request.
        $httpresult = curl_exec($curl);
        $httpstatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Transform data from key=value notation into an array.
        foreach(\explode("\n", $httpresult, 256) as $line) {
            if (\preg_match('/^([^=]*)=(.*)$/', $line, $matches) === 1) {
                $result[strtolower($matches[1])] = $matches[2];
            }        
        }

        // Check if response status is ok and look for an error message.
        if ($httpstatus === 28) {
            throw new GoogleLoginFailedException(_('Login failed: Request timed out.'));
        } elseif ($httpstatus !== 200 && isset($result['error'])) {
            if (trim($result['error']) == 'BadAuthentication') {
                throw new GoogleLoginFailedException(_('Login failed: The email or password you entered is incorrect.'));
            }
            throw new GoogleLoginFailedException(
                'Login failed: ' . $result['error'] . '.');
        } elseif ($httpstatus !== 200) {
            throw new GoogleLoginFailedException(_('Login failed for an unknown reason.'));
        }

        return $result;
    }
}
