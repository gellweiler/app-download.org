<?php
/**
 * Look for classes in class folders.
 * Every class file needs to be called ClassName.class.php.
 */
function autoload($class)
{
    $success = FALSE;
    foreach (array('controllers', 'classes', 'enums') as $folder) {
        $file = __DIR__ . "/../$folder/$class.class.php";
        if (file_exists($file)) {
            require_once $file;
            $success = TRUE;
        }
    }
    return $success;
}
spl_autoload_register('autoload');