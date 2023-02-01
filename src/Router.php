<?php
namespace GCWorld\Routing;

use GCWorld\Routing\Core\CoreRouter;
use GCWorld\Routing\Exceptions\ReverseRouteNotFoundException;
use Redis;

/**
 * Class Router
 *
 * @deprecated Use CoreRouter instead
 */
class Router
{
    /**
     * @return null|string
     */
    public static function getFoundRouteName()
    {
        return CoreRouter::getInstance()->getFoundRouteName();
    }

    /**
     * @return null|string
     */
    public static function getFoundRouteNameClean()
    {
        return CoreRouter::getInstance()->getFoundRouteNameClean();
    }

    /**
     * @return null|array
     */
    public static function getFoundRouteArguments()
    {
        return CoreRouter::getInstance()->getFoundRouteArguments();
    }

    /**
     * @return array
     */
    public static function getFoundRouteData()
    {
        return CoreRouter::getInstance()->getFoundRouteData();
    }

    /**
     * @return string
     */
    public static function getPathFull()
    {
        return CoreRouter::getInstance()->getPathFull();
    }

    /**
     * @return string
     */
    public static function getPathClean()
    {
        return CoreRouter::getInstance()->getPathClean();
    }

    /**
     * @param string $name
     * @param array  $params
     * @return string
     * @throws ReverseRouteNotFoundException
     */
    public static function reverse(string $name, array $params = [])
    {
        return CoreRouter::getInstance()->reverse($name, $params);
    }

    /**
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public static function reverseMe(array $params = [])
    {
        return CoreRouter::getInstance()->reverseMe($params);
    }

    /**
     * @param string $name
     * @param array  $params
     * @return array
     * @throws ReverseRouteNotFoundException
     */
    public static function reverseAll(string $name, array $params = [])
    {
        return CoreRouter::getInstance()->reverseAll($name, $params);

    }

    /**
     * @param string $name
     * @param array  $params
     * @return mixed
     */
    public static function reverseObject(string $name, array $params = [])
    {
        return CoreRouter::getInstance()->reverseObject($name, $params);
    }

    /**
     * @param string $base
     */
    public static function setBase(string $base)
    {
        CoreRouter::getInstance()->setBase($base);
    }

    /**
     * @param string $user
     */
    public static function setUserClassName(string $user)
    {
        CoreRouter::getInstance()->setUserClassName($user);
    }

    /**
     * @return bool
     */
    protected static function isXHRRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * @param string $pexNode
     * @param array  $regexMatches
     * @return string
     */
    protected static function replacePexKeys($pexNode, array $regexMatches)
    {
        foreach ($regexMatches as $k => $v) {
            $pexNode = str_replace('['.$k.']', $v, $pexNode);
        }

        return $pexNode;
    }

    /**
     * @param Debugger $debugger
     */
    public static function attachDebugger(Debugger $debugger)
    {
        CoreRouter::getInstance()->attachDebugger($debugger);
    }

    /**
     * @param array $routes
     */
    public static function forceRoutes(array $routes)
    {
        CoreRouter::getInstance()->forceRoutes($routes);
    }

    /**
     * @param Redis $redis
     */
    public static function attachRedisCache(\Redis $redis)
    {
        CoreRouter::getInstance()->attachRedisCache($redis);
    }

    /**
     * @param string|null $prefix
     */
    public static function setRoutePrefix(string $prefix = null)
    {
        CoreRouter::getInstance()->setRoutePrefix($prefix);
    }

    /**
     * @return null|string
     */
    public static function getRoutePrefix()
    {
        return CoreRouter::getInstance()->getRoutePrefix();
    }

    /**
     * @param string $name
     */
    public static function setPageWrapperName(string $name)
    {
        CoreRouter::getInstance()->setPageWrapperName($name);
    }

    /**
     * @return null|string
     */
    public static function getPageWrapperName()
    {
        return CoreRouter::getInstance()->getPageWrapperName();
    }

    /**
     * @return string
     */
    public static function getCallingMethod()
    {
        return CoreRouter::getInstance()->getCallingMethod();
    }
}
