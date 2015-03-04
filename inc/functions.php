<?php
/**
 * Escape given string for the use in html.
 */
function q($msg) {
    return htmlspecialchars($msg, ENT_QUOTES);
}

/**
 * Escape given string for the use in html
 * and echo it out.
 */
function qe($msg) {
    echo q($msg);
}

/**
 * Gettext im printf Format.
 */
function _f($format)
{
    return vsprintf(gettext($format), array_slice(func_get_args(), 1));
}

/**
 * Build an url to any content that should be fetched through a controller.
 * Url will be written nicely if mod_rewrite is supported.
 *
 * @param $path
 *  The path containing the controller and a method and optional further parameters:
 *  Controller/method[/param1][/param2 ...]
 *  Example:
 *   Device/deviceid/12efab43899efg
 *
 * @param array $query
 *  The query to append to the path.
 *
 * @param bool $download
 *  If set to true the url will point to the download url instead of default url.
 *
 * @param string $lang
 *  The language of the content
 *
 * @return string
 *  The complete url.
 */
function url($path, $query = array(), $download = FALSE, $lang = LANGUAGE) {
    $base = $download ? BASE_DOWNLOAD_URL : BASE_URL;
    $path = "$lang/$path";
    if (!MOD_REWRITE) {
        $query = array_merge($query, array('q' => $path));
        $path = 'index.php';
    }
    $q = empty($query) ? '' : '?' . http_build_query($query);
    return $base . $path . $q;
}

/**
 * Build url to same content (but with different language.
 */
function alt_lang_url($lang) {
    $path = (!empty($_GET['q']) ? rtrim($_GET['q'], '/') : '');
    $pieces = explode('/', $path);
    if (in_array($pieces[0], array_keys(unserialize(LANGUAGES)))) {
        $path = implode('/', array_slice($pieces, 1));
    }
    return url(
        $path,
        array_diff_key(array('q' => ''), $_GET),
        FALSE,
        $lang
    );
}

/**
 * Generate a token for the use against csrf attacks.
 */
function token_generate()
{
    return str_replace(
        array('+', '/'), array('-', '_'),
        rtrim(base64_encode(openssl_random_pseudo_bytes(32)), '=')
    );
}

/**
 * Kill old session and all data associated with it
 * and start a new one.
 */
function reset_session()
{
    $_SESSION = array();
    session_destroy();
    session_start();
    session_regenerate_id(true); 
}

/**
 * Inserts a query into an existing url.
 */
function insert_query($url, $query)
{
    $q_string = http_build_query($query);

    $q_pos = strpos($url, '?');
    if ($q_pos > 0) {
        return substr_replace($url, $q_string . '&', $q_pos +1, 0);
    }
    $h_pos = strpos($url, '#');
    if ($h_pos > 0) {
        return substr_replace($url, '?' . $q_string, $q_pos, 0);
    }
    return $url . '?' . $q_string;
}

/**
 * Check if csrf token is given in $_GET and if it matches
 * the token from the session.
 */
function valid_token()
{
    $req = array_merge($_GET, $_POST);
    if (isset($req['token'])
      && ($req['token'] == Session::getInstance()->token)) {
        return TRUE;
    }
    return FALSE;
}

/**
 * Insert the csrf token into given url.
 */
function url_token($url)
{
    return insert_query($url, array(
        'token' => Session::getInstance()->token,   
    ));
}

/**
 * Add the csrf token as a hidden input field.
 */
function input_token()
{
    echo '<input type="hidden" name="token" value="';
    qe(Session::getInstance()->token);
    echo '">';
}


/**
 * Sums up two hex string of unlimited length.
 */
function hex_sum($hex_a, $hex_b)
{
    // Resulting hex string.
    $result = '';

    // Max number of characters in the two hex strings.
    $max = max(strlen($hex_a), strlen($hex_b));

    // Adjust length of hex strings to even length.
    $hex_a = str_pad($hex_a, $max, '0', STR_PAD_LEFT);
    $hex_b = str_pad($hex_b, $max, '0', STR_PAD_LEFT);

    // Walk through all places of the two hex strings
    // from LSB to MSB.
    $carry = 0;
    for ($i = $max -1; $i >= 0; $i--) {
        $place_a = substr($hex_a, $i, 1);
        $place_b = substr($hex_b, $i, 1);

        // Sum up places and carry
        $value = hexdec($place_a) + hexdec($place_b) + $carry;

        // Prepend current place to result string
        // and calculate carry.
        $result = dechex($value % 16) . $result;
        $carry = floor($value / 16); // Integer division.
    }

    // Handle carry over.
    if ($carry != 0) {
        $result = $carry . $result;
    }

    return $result;
}

/**
 * Calculate luhn checksum of given number.
 * @see http://en.wikipedia.org/wiki/Luhn_algorithm.
 *
 * @param int $number
 *  The number of which to calcualte the checkusm.
 *
 * @return int
 *  The checksum digit.
 */
function luhn($number)
{
    // Walk through all digits in reverse order.
    // Double every odd digit and calculate the cross sum.
    // Sum up all values into $sum.
    $sum = 0;
    $rev_digits = array_reverse(str_split($number));
    foreach($rev_digits as $i => $digit) {
        if (($i % 2) == 0) {
            // Cross sum of $digit*2.
            $sum += array_sum(str_split($digit*2));
        } else {
            $sum += $digit;
        }
    }

    // Calculate check digit from sum.
    return ($sum*9) % 10;
}

/**
 * Transform a large number from dec to hex.
 */
function dec2hex($number)
{
    $hex = '';
    while($number != '0')
    {
        $hex = dechex(bcmod($number,'16')) . $hex;
        $number = bcdiv($number, '16' , 0);
    }

    return $hex;
}

/**
 * Add a trailing dot to the host. Most browsers will treat example.com. as a different domain than
 * example.com and won't send cookies for .example.com . Meanwhile apache will treat them as the same.
 *
 * This does not work in Safari (desktop and mobile) and some no name browsers.
 *
 * @param $url
 *  The url to patch.
 *
 * @return string
 *  Patched url, which will hopefully not send cookies to destination.
 *
 * @throws Exception
 *  If $url is not a valid uri.
 */
function dot_hack($url)
{
    // Split URL into pre and post path parts.
    if (preg_match('!^([^/]*//[^/]*)(/.*)$!', $url, $matches) !== 1) {
        throw new Exception('Invalid url.');
    }

    // Insert dot before path.
    return $matches[1] . '.' . $matches[2];
}

/**
 * Check if package with given name exists on the play store.
 * To validate package name a HEAD request to the online site
 * of the google play store is made.
 */
function package_exists($package)
{
    $url = 'https://play.google.com/store/apps/details?'
      . http_build_query(array('id' => $package));

    $curl = curl_init();
    curl_setopt ($curl, CURLOPT_URL, $url);
    curl_setopt ($curl, CURLOPT_NOBODY, TRUE);
    curl_setopt ($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, 5);

    curl_exec($curl);
    $httpstatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    switch($httpstatus) {
        case 200:
            return TRUE;
        case 404:
            return FALSE;
        case 28:
            throw new Exception ('Could not validate package name: '
              . 'Request to play.google.com timed out.');
        default:
            throw new Exception
                ('Could not validate package name: Request to '
                . "play.google.com failed (status code $httpstatus).");
    }
}

/**
 * Converts plain preformatted text to html.
 */
function  plain2html($text) {
    return nl2br(str_replace(
        array("\t", "  "),
        array('&nbsp;&nbsp;&nbsp;&nbsp;', '&nbsp;&nbsp;'),
        q($text)
    ));
}
