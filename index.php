<?php
header('HTTP/1.1 200 OK');
ob_start();
try {
    try {
        require_once 'inc/bootstrap.php';
    } catch (Exception $e) {
        ob_clean();
        // Remember error for displaying it later in a more prominent place.
        define('ERROR', TRUE);
        define('ERROR_MESSAGE', $e->getMessage());

        index::getInstance()->render();
    }
} catch (Exception $e) {
    die('Unknown error');
}
