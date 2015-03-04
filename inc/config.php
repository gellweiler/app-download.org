<?php
/**
 * @file
 *  In here go all configuration directives that can be the same across hosts.
 */

/**
 * The maximum time in minutes the download of an apk can take.
 */
define('DOWNLOAD_TIMEOUT', 60);

/**
 * The time to live of a download entry in seconds.
 */
define('DOWNLOAD_ENTRY_TTL', 240);

/**
 * A static salt used for hashing emails, to make it harder for spammers to steal email adresses.
 * A dynamic hash can't be used because that would make it impossible to lookup email addresses in the db.
 */
define('STATIC_EMAIL_SALT', 'BfBOIw7r>]kq&lC,Ae:]vH\'84mrSrI#Hd8a{4oq');

/**
 * The mail address to sent contact emails to.
 */
define('CONTACT_MAIL', 'contact@app-download.org');

/**
 * The mail to sent error messages to.
 */
define('ERROR_MAIL', CONTACT_MAIL);

/**
 * A list of supported languages.
 */
define ('LANGUAGES', serialize(array(
    'en' => 'en_US',
    'de' => 'de_DE',
)));

/**
 * A list of fake Gmail Accounts to use for this service.
 * Key is the email address, value the password.
 */
define('FAKE_ACCOUNTS', serialize(array(
    'CHANGE@gmail.com' => 'CHANGE',
)));
