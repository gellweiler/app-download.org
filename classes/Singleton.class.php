<?php
/*
 * Base Singleton class.
 */
class Singleton
{
    protected function __construct() {}

    /**
     * @var array
     *  An array of instances.
     */
    static $instances = array();

    /**
     * Get or create the instance of this class.
     *
     * @return Singleton
     *  The singleton instance of this class.
     */
    public static function getInstance()
    {
        $class = get_called_class();
        if(!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }
}