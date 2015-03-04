<?php
require_once __DIR__ . '/config_host.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../classes/Session.class.php';
require_once __DIR__ . '/autoload.php';

require_once(__DIR__ . '/../ext-libs/Protobuf-PHP/library/DrSlump/Protobuf.php');
\DrSlump\Protobuf::autoload();

set_time_limit(120);
ini_set('memory_limit', '1024M');

// If a query is given in $_GET['q'] it will be parsed
// and the specified controller and method will be invoked.
// Content will be delivered in requested language (if available).
//
// Query Format: language/controller[/method][/param1][.../paramN]
//
// Example:
//   $_GET['q'] = 'Login/login
//
//   Will result in:
//
//   new Login()->login();
    $languages = unserialize(LANGUAGES);

    // If no language is given redirect to English version.
    $query = explode('/', rtrim(!empty($_GET['q']) ? $_GET['q'] : ''));
    if (
        empty($query)
        || !in_array($query[0], array_keys($languages))
    ) {
        // Fallback to english.
        header ('Location:' . alt_lang_url('en'));
        exit();
    } else {
        define('LANGUAGE', $query[0]);
        setlocale(LC_MESSAGES, $languages[LANGUAGE] . '.utf8');
        bindtextdomain('messages', BASE_DIR . 'locale');
        bind_textdomain_codeset('messages', 'UTF-8');
        textdomain('messages');
    }

    // Initialize or include components, that need to be language aware.
    LanguageCodes::init();
    require_once BASE_DIR . 'inc/data.php';

    // Will be set to true in the event that no controller is given
    // or the controller does not exist.
    $fallback = FALSE;
    // Look for controller in the query and check that it exists.
    if (!empty($query[1]) && class_exists($query[1])) {
        $controller = $query[1]::getInstance();

        // Look for controller method in the query and check that it is public
        // and does not start with _.
        if (
            !empty($query[2])
            && (preg_match('/^_/', $query[2]) !== 1)
            && is_callable(array($controller, $query[2]))
        ) {
            $params = array_slice($query, 3);
            call_user_func_array(array($controller, $query[2]), $params);
        }
        // If no method is given default to render.
        else {
            $controller->render();
        }
    }
    else {
        $fallback = TRUE;
    }

    // Show index page.
    if ($fallback) {
        index::getInstance()->render();
    }

