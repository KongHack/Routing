<?php
namespace GCWorld\Routing;

/**
 * Class Hook
 */
class Hook
{
    /**
     * @var
     */
    private static $instance;

    /**
     * @var array
     */
    private $hooks = array();

    /**
     * Singleton Format
     */
    private function __construct()
    {
    }

    /**
     * Singleton Format
     */
    private function __clone()
    {
    }

    /**
     * @param $hook_name
     * @param $fn
     */
    public static function add($hook_name, $fn)
    {
        $instance = self::getInstance();
        $instance->hooks[$hook_name][] = $fn;
    }

    /**
     * @param string $hook_name
     * @param mixed  $params
     */
    public static function fire($hook_name, $params = null)
    {
        $instance = self::getInstance();
        if (isset($instance->hooks[$hook_name])) {
            foreach ($instance->hooks[$hook_name] as $fn) {
                call_user_func_array($fn, array(&$params));
            }
        }
    }

    /**
     * @return Hook
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
