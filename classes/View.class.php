<?php
/**
 * Every controller has a view object to which it can attach propertys.
 * The controller will render view files from the views folder through
 * it's view object, that way proper scoping is enforced.
 */
class View
{
    /**
     * Require view with given path relative
     * to the the views folder.
     */
    public function display($path)
    {
        $path = __DIR__ . "/../views/$path.phtml";
        if (file_exists($path)) {
            require $path;
        } else {
            throw new Exception("Could not get view $path.");
        }
    }
}
