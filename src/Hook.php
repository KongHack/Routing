<?php
namespace GCWorld\Routing;

use GCWorld\Interfaces\RoutingInterface;

/**
 * Class Hook
 */
class Hook
{
    protected static array $instances = [];

    protected string $name;
    protected array  $hooks = [];

    /**
     * @param string $name
     */
    protected function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Singleton Format
     */
    private function __clone()
    {
    }

    /**
     * @param string $name
     * @param string $hook_name
     * @param string $fn
     * @return void
     */
    public static function add(string $name, string $hook_name, string $fn): void
    {
        $instance = self::getInstance($name);
        $instance->hooks[$hook_name][] = $fn;
    }

    /**
     * @param string $name
     * @param string $hook_name
     * @param ?array $params
     * @return void
     */
    public static function fire(string $name, string $hook_name, ?array $params = null): void
    {
        $instance = self::getInstance($name);
        if (isset($instance->hooks[$hook_name])) {
            foreach ($instance->hooks[$hook_name] as $fn) {
                self::call($fn, $params??[]);
            }
        }
    }

    /**
     * @param string $fn
     * @param array $params
     * @return void
     */
    protected static function call(string $fn, array $params): void
    {
        if(empty($params)) {
            $fn();
            return;
        }

        $fn(...$params);
    }

    /**
     * @param string $name
     * @return static
     *
     */
    public static function getInstance(string $name = RoutingInterface::DEFAULT_NAME): static
    {
        if(!isset(self::$instances[$name])) {
            self::$instances[$name] = new static($name);
        }

        return self::$instances[$name];
    }
}
