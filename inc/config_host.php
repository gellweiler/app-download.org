<?php
/**
 * @file
 *  In here go all configuration directives that are different across hosts.
 */

/**
 * The base directory of the application.
 */
define ('BASE_DIR', __DIR__ . '/../');

/**
 * The base url of the application.
 */
define ('BASE_URL', 'http://app.download.org/');

/**
 * Url from where to start the download.
 * Useful to start the actual download on a http connection.
 *
 * @warning
 *  If you set this to a http url be sure to set this to an url
 *  that does not receive cookies so that the session id can't be stolen.
 */
define ('BASE_DOWNLOAD_URL', 'http://dl.app.download.org/');

/**
 * Set this to TRUE if mod rewrite is supported.
 */
define ('MOD_REWRITE', TRUE);
