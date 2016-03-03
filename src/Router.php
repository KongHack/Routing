<?php
namespace GCWorld\Routing;

use GCWorld\Interfaces\PEX;

/**
 * Class Router
 * @package GCWorld\Routing
 */
class Router
{
    const MISC = '\GCWorld\Routing\Generated\MasterRoute_MISC';
    const REPLACEMENT = '\GCWorld\Routing\Generated\MasterRoute_REPLACEMENT_KEY';

    private static $base          = null;
    private static $userClassName = null;
    private static $forcedRoutes  = null;
    /**
     * @var Debugger|null
     */
    private static $debugger = null;

    /**
     * @var \Redis|null
     */
    private static $redis = null;

    /**
     * @var \GCWorld\Interfaces\PEX
     */
    private static $user = null;

    /**
     * @var mixed
     */
    public static $foundRouteName      = null;
    public static $foundRouteNameClean = null;

    /**
     * Processes routes.
     * @param null $path_info
     * @throws \Exception
     */
    public static function forward($path_info = null)
    {
        Hook::fire('before_request', compact('routes'));

        $request_method = strtolower($_SERVER['REQUEST_METHOD']);

        if ($path_info == null) {
            $path_info = '/';
            if (!empty($_SERVER['PATH_INFO'])) {
                $path_info = $_SERVER['PATH_INFO'];
            } elseif (!empty($_SERVER['ORIG_PATH_INFO']) && $_SERVER['ORIG_PATH_INFO'] !== '/index.php') {
                $path_info = $_SERVER['ORIG_PATH_INFO'];
            } else {
                if (!empty($_SERVER['REQUEST_URI'])) {
                    $path_info = (strpos($_SERVER['REQUEST_URI'], '?') > 0) ? strstr(
                        $_SERVER['REQUEST_URI'],
                        '?',
                        true
                    ) : $_SERVER['REQUEST_URI'];
                }
            }
        }


        $pattern            = '';
        $discovered_handler = null;
        $regex_matches      = [];
        $cacheMatched       = false;

        if (self::$redis) {
            $data = self::$redis->hGet('GCWORLD_ROUTER', $path_info);
            if ($data) {
                $routeData          = json_decode($data, true);
                $pattern            = $routeData['p'];
                $discovered_handler = $routeData['h'];
                $regex_matches      = $routeData['m'];
                $cacheMatched       = true;
            }
        }


        if ($discovered_handler == null) {

            if (self::$forcedRoutes == null) {
                $temp = explode('/', $path_info);
                if (count($temp) > 1) {
                    $master    = Processor::cleanClassName($temp[1]);
                    $className = '\GCWorld\Routing\Generated\MasterRoute_'.$master;
                    if (!class_exists($className)) {
                        $className = self::MISC;
                        if (!class_exists($className)) {
                            throw new \Exception('No Route Class Found For Base (1)');
                        }
                    }
                } else {
                    $className = self::MISC;
                    if (!class_exists($className)) {
                        throw new \Exception('No Route Class Found For Base (2)');
                    }
                }

                // TODO: Clean this up.
                // I hate having to copy/paste large blocks of code like this, but I don't
                //  have the time to clean & fix right now. :(

                /** @var \GCWorld\Routing\RoutesInterface $loader */
                $loader = new $className();
                $routes = $loader->getForwardRoutes();
            } else {
                $routes = self::$forcedRoutes;
            }

            if (isset($routes[$path_info])) {
                $pattern            = $path_info;
                $discovered_handler = $routes[$path_info];
            } elseif ($routes) {
                $tokens = array(
                    ':string'   => '([a-zA-Z]+)',
                    ':number'   => '([0-9]+)',
                    ':alpha'    => '([a-zA-Z0-9-_]+)',
                    ':anything' => '([^/]+)',
                    ':consume'  => '(.+)',
                );
                foreach ($routes as $pattern => $routeConfig) {
                    $pattern = strtr($pattern, $tokens);
                    if (preg_match('#^/?'.$pattern.'/?$#', $path_info, $matches)) {
                        $discovered_handler = $routeConfig;
                        $regex_matches      = $matches;
                        unset($regex_matches[0]);
                        $regex_matches = array_values($regex_matches);
                        break;
                    }
                }
            }

            if (!$discovered_handler) {
                $className = self::REPLACEMENT;
                if (!class_exists($className)) {
                    $className = self::MISC;
                }
                if (class_exists($className)) {
                    /** @var \GCWorld\Routing\RoutesInterface $loader */
                    $loader = new $className();
                    $routes = $loader->getForwardRoutes();

                    $pattern            = '';
                    $discovered_handler = null;
                    $regex_matches      = array();

                    if (isset($routes[$path_info])) {
                        $pattern            = $path_info;
                        $discovered_handler = $routes[$path_info];
                    } elseif ($routes) {
                        $tokens = array(
                            ':string'   => '([a-zA-Z]+)',
                            ':number'   => '([0-9]+)',
                            ':alpha'    => '([a-zA-Z0-9-_]+)',
                            ':anything' => '([^/]+)',
                            ':consume'  => '(.+)',
                        );
                        foreach ($routes as $pattern => $routeConfig) {
                            $pattern = strtr($pattern, $tokens);
                            if (preg_match('#^/?'.$pattern.'/?$#', $path_info, $matches)) {
                                $discovered_handler = $routeConfig;
                                $regex_matches      = $matches;
                                unset($regex_matches[0]);
                                $regex_matches = array_values($regex_matches);
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!$cacheMatched && self::$redis) {
            self::$redis->hSet('GCWORLD_ROUTER', $path_info, json_encode([
                'p' => $pattern,
                'h' => $discovered_handler,
                'm' => $regex_matches
            ]));
        }

        $result           = null;
        $handler_instance = null;
        if ($discovered_handler) {
            if (self::$debugger !== null && $pattern != '') {
                self::$debugger->logHit($pattern);
            }

            if (is_string($discovered_handler)) {
                if (class_exists($discovered_handler)) {
                    $handler_instance = new $discovered_handler($regex_matches);
                } else {
                    echo 'Class Not Found: '.$discovered_handler;
                    die();
                }
            } elseif (is_array($discovered_handler)) {
                if (isset($discovered_handler['name'])) {
                    self::$foundRouteName = $discovered_handler['name'];
                    $tmp                  = explode('_', $discovered_handler['name']);
                    if (is_numeric($tmp[count($tmp) - 1])) {
                        array_pop($tmp);
                        self::$foundRouteNameClean = implode('_', $tmp);
                    } else {
                        self::$foundRouteNameClean = self::$foundRouteName;
                    }
                }

                //Used for new reverse name search.
                if (isset($discovered_handler['session']) &&
                    $discovered_handler['session'] == true &&
                    session_status() == PHP_SESSION_NONE
                ) {
                    session_start();
                }
                //Handle pre & post handler options
                if (isset($discovered_handler['preArgs']) && is_array($discovered_handler['preArgs'])) {
                    $rev = array_reverse($discovered_handler['preArgs']);
                    foreach ($rev as $arg) {
                        array_unshift($regex_matches, $arg);
                    }
                }
                if (isset($discovered_handler['postArgs']) && is_array($discovered_handler['postArgs'])) {
                    foreach ($discovered_handler['postArgs'] as $arg) {
                        $regex_matches[] = $arg;
                    }
                }

                if (isset($discovered_handler['class']) && is_string($discovered_handler['class'])) {
                    $discovered_handler = $discovered_handler['class'];
                    if (class_exists($discovered_handler)) {
                        $handler_instance = new $discovered_handler($regex_matches);
                    } else {
                        echo 'Class Not Found: '.$discovered_handler;
                        die();
                    }
                }

                // Security Testing!
                if (self::$userClassName != null) {
                    /** @var mixed $temp */
                    $temp       = self::$userClassName;
                    self::$user = $temp::getInstance();
                }

                if (self::$user != null) {
                    if (!self::$user instanceof PEX) {
                        throw new \Exception('The provided user class does not implement PEX. ('.
                            self::$userClassName.')');
                    }
                    $types = array('pexCheck', 'pexCheckAny', 'pexCheckExact');
                    foreach ($types as $type) {
                        if (isset($discovered_handler[$type])) {
                            if (!is_array($discovered_handler[$type])) {
                                if (!self::$user->$type(self::replacePexKeys($discovered_handler[$type],
                                    $regex_matches))
                                ) {
                                    Hook::fire('403',
                                        compact('routes', 'discovered_handler', 'request_method', 'regex_matches'));
                                }
                            } else {
                                $good = false;
                                foreach ($discovered_handler[$type] as $node) {
                                    if (self::$user->$type(self::replacePexKeys($node, $regex_matches))) {
                                        $good = true;
                                        break;
                                    }
                                }
                                if (!$good) {
                                    Hook::fire('403',
                                        compact('routes', 'discovered_handler', 'request_method', 'regex_matches'));
                                }
                            }
                        }
                    }
                }
            } elseif (is_callable($discovered_handler)) {
                $handler_instance = $discovered_handler($regex_matches);
            }
        }

        if ($handler_instance) {
            if (self::is_xhr_request() && method_exists($handler_instance, $request_method.'_xhr')) {
                header('Content-type: application/json');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Pragma: no-cache');
                $request_method .= '_xhr';
            }

            if (method_exists($handler_instance, $request_method)) {
                Hook::fire('before_handler',
                    compact('routes', 'discovered_handler', 'request_method', 'regex_matches'));

                $args     = &$regex_matches; //Only for cleaner code
                if(count($args) > 0) {
                    $result = $handler_instance->$request_method(...$args);
                } else {
                    $result = $handler_instance->$request_method();
                }

                Hook::fire('after_handler',
                    compact('routes', 'discovered_handler', 'request_method', 'regex_matches', 'result'));
            } else {
                Hook::fire('404', compact('routes', 'discovered_handler', 'request_method', 'regex_matches'));
            }
        } else {
            Hook::fire('404',
                compact('routes', 'discovered_handler', 'request_method', 'regex_matches', 'master', 'temp',
                    'className'));
        }
        Hook::fire('after_request',
            compact('routes', 'discovered_handler', 'request_method', 'regex_matches', 'result'));
    }

    /**
     * @param       $name
     * @param array $params
     * @return bool|string
     */
    public static function reverse($name, $params = array())
    {
        if (($routeArray = self::reverseAll($name, $params)) === false) {
            return false;
        }

        $route = $routeArray['pattern'];
        if (count($params) > 0) {
            $temp  = explode('/', $route);
            $index = 0;
            foreach ($temp as $k => $v) {
                if (substr($v, 0, 1) == ':') {
                    $temp[$k] = $params[$index];
                    ++$index;
                }
            }
            $route = implode('/', $temp);
        }
        if (self::$base != null) {
            $route = self::$base.$route;
        }

        return $route;
    }

    /**
     * @param       $name
     * @param array $params
     * @return bool|array
     */
    public static function reverseAll($name, $params = array())
    {
        // We now add the count of parameters to the name. See Processor.php for more info.
        $name .= '_'.count($params);

        $temp   = explode('_', $name);
        $master = '\GCWorld\Routing\Generated\MasterRoute_'.Processor::cleanClassName($temp[0]);
        if (!class_exists($master)) {
            $master = '\GCWorld\Routing\Generated\MasterRoute_MISC';
        }

        /** @var \GCWorld\Routing\RoutesInterface $cTemp */
        $cTemp  = new $master();
        $routes = $cTemp->getReverseRoutes();

        if (array_key_exists($name, $routes)) {
            return $routes[$name];
        }

        return false;
    }

    /**
     * @param       $name
     * @param array $params
     * @return mixed
     */
    public static function reverseObject($name, $params = array())
    {
        if (($routeArray = self::reverseAll($name, $params)) === false) {
            return false;
        }
        $className = $routeArray['class'];

        return new $className($params);
    }

    /**
     * @param $base
     */
    public static function setBase($base)
    {
        self::$base = rtrim($base, '/');
    }

    /**
     * @param string $user
     * @throws \Exception
     */
    public static function setUserClassName($user)
    {
        self::$userClassName = $user;
    }

    /**
     * @return bool
     */
    private static function is_xhr_request()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * @param string $pexNode
     * @param array  $regexMatches
     * @return string
     */
    private static function replacePexKeys($pexNode, array $regexMatches)
    {
        foreach ($regexMatches as $k => $v) {
            $pexNode = str_replace('['.$k.']', $v, $pexNode);
        }

        return $pexNode;
    }

    /**
     * @param \GCWorld\Routing\Debugger $debugger
     */
    public static function attachDebugger(Debugger $debugger)
    {
        self::$debugger = $debugger;
    }

    /**
     * @param array $routes
     */
    public static function forceRoutes(array $routes)
    {
        self::$forcedRoutes = $routes;
    }

    public static function attachRedisCache(\Redis $redis)
    {

    }
}
