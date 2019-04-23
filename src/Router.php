<?php
namespace GCWorld\Routing;

use GCWorld\Interfaces\AdvancedHandlerInterface;
use GCWorld\Interfaces\HandlerInterface;
use GCWorld\Interfaces\PEX;

/**
 * Class Router
 * @package GCWorld\Routing
 */
class Router
{
    const MISC = '\GCWorld\Routing\Generated\MasterRoute_MISC';
    const REPLACEMENT = '\GCWorld\Routing\Generated\MasterRoute_REPLACEMENT_KEY';

    const TOKENS = [
        ':single'   => '([a-zA-Z0-9]{1})',
        ':string'   => '([a-zA-Z]+)',
        ':number'   => '([0-9]+)',
        ':uuid'     => '([a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{12})',
        ':alpha'    => '([a-zA-Z0-9-_]+)',
        ':anything' => '([^/]+)',
        ':consume'  => '(.+)',
    ];

    private static $base            = null;
    private static $userClassName   = null;
    private static $forcedRoutes    = null;
    private static $pageWrapperName = null;

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
     * @var \GCWorld\Interfaces\PageWrapper
     */
    private static $pageWrapper = null;

    /**
     * When set, will prepend to reversed routes and remove from forward routing
     * @var string|null
     */
    private static $routePrefix = null;


    /**
     * @var mixed
     */
    public static $foundRouteName      = null;
    public static $foundRouteNameClean = null;
    public static $foundRouteArguments = null;
    public static $foundRouteData      = [];

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

        if (self::$routePrefix != null) {
            $pos = strpos($path_info, self::$routePrefix);
            if ($pos === 0) {
                $path_info = substr_replace($path_info, '', $pos, strlen(self::$routePrefix));
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
                foreach ($routes as $pattern => $routeConfig) {
                    $pattern = strtr($pattern, self::TOKENS);
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
                    $regex_matches      = [];

                    if (isset($routes[$path_info])) {
                        $pattern            = $path_info;
                        $discovered_handler = $routes[$path_info];
                    } elseif ($routes) {
                        foreach ($routes as $pattern => $routeConfig) {
                            $pattern = strtr($pattern, self::TOKENS);
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

        self::$foundRouteArguments = $regex_matches;

        $result           = null;
        $handler_instance = null;
        if ($discovered_handler) {
            if (self::$debugger !== null && $pattern != '') {
                self::$debugger->logHit($pattern);
            }

            if (is_string($discovered_handler)) {
                if (class_exists($discovered_handler)) {
                    $handler_instance = self::instantiateHandlerClass($discovered_handler, $regex_matches);
                } else {
                    throw new \Exception('Class Not Found: '.$discovered_handler);
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

                self::$foundRouteData = $discovered_handler;

                //Used for new reverse name search.
                if (isset($discovered_handler['session']) &&
                    $discovered_handler['session'] == true &&
                    session_status() == PHP_SESSION_NONE
                ) {
                    Hook::fire('pre-session_start', compact('discovered_handler', 'request_method', 'regex_matches'));
                    session_start();
                    Hook::fire('post-session_start', compact('discovered_handler', 'request_method', 'regex_matches'));
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
                                if (self::$user->$type(self::replacePexKeys(
                                        $discovered_handler[$type],
                                        $regex_matches
                                    )) < 1
                                ) {
                                    Hook::fire(
                                        '403',
                                        compact('discovered_handler', 'request_method', 'regex_matches')
                                    );
                                }
                            } else {
                                $good = false;
                                foreach ($discovered_handler[$type] as $node) {
                                    if (self::$user->$type(self::replacePexKeys($node, $regex_matches)) > 0) {
                                        $good = true;
                                        break;
                                    }
                                }
                                if (!$good) {
                                    Hook::fire(
                                        '403',
                                        compact('discovered_handler', 'request_method', 'regex_matches')
                                    );
                                }
                            }
                        }
                    }
                }

                if (isset($discovered_handler['class']) && is_string($discovered_handler['class'])) {
                    $discovered_class = $discovered_handler['class'];
                    if (class_exists($discovered_class)) {
                        $handler_instance = self::instantiateHandlerClass($discovered_class, $regex_matches);
                    } else {
                        throw new \Exception('Class Not Found: '.$discovered_class);
                    }
                }
            } elseif (is_callable($discovered_handler)) {
                $handler_instance = $discovered_handler($regex_matches);
            }
        }

        if ($handler_instance) {
            try {

                if (self::isXHRRequest() && method_exists($handler_instance, $request_method.'XHR')) {
                    header('Content-type: application/json');
                    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                    header('Cache-Control: no-store, no-cache, must-revalidate');
                    header('Cache-Control: post-check=0, pre-check=0', false);
                    header('Pragma: no-cache');
                    $request_method .= 'XHR';
                }

                if (method_exists($handler_instance, $request_method)) {
                    if (isset($discovered_handler['autoWrapper']) && $discovered_handler['autoWrapper']) {
                        if ($handler_instance instanceof HandlerInterface
                            || $handler_instance instanceof AdvancedHandlerInterface
                        ) {
                            $title = $handler_instance->getTitle();
                            $handler_instance->setBreadcrumbs();


                            if (self::$pageWrapper == null && self::$pageWrapperName != null) {
                                /** @var mixed $temp */
                                $temp = self::$pageWrapperName;
                                if (class_exists($temp)) {
                                    self::$pageWrapper = $temp::getInstance();
                                }
                            }
                            if (self::$pageWrapper != null) {
                                self::$pageWrapper->setTitle($title);
                            }
                        }
                    }

                    Hook::fire('before_request_method',compact('discovered_handler', 'request_method', 'regex_matches'));

                    if (count($regex_matches) > 0) {
                        $result = $handler_instance->$request_method(...$regex_matches);
                    } else {
                        $result = $handler_instance->$request_method();
                    }

                    Hook::fire('after_request_method',compact('discovered_handler', 'request_method', 'regex_matches'));

                    if ($handler_instance instanceof AdvancedHandlerInterface) {
                        if (self::isXHRRequest() && is_array($result)) {
                            echo json_encode($result);
                        } else {
                            echo $result;
                        }
                    }

                    Hook::fire(
                        'after_output',
                        compact('discovered_handler', 'request_method', 'regex_matches', 'result')
                    );
                } else {
                    Hook::fire('404', compact('discovered_handler', 'request_method', 'regex_matches'));
                }

            } catch(RouterExceptionInterface $e) {
                $e->executeLogic();
                die();
            }

        } else {
            Hook::fire(
                '404',
                compact(
                    'discovered_handler',
                    'request_method',
                    'regex_matches',
                    'master',
                    'temp',
                    'className'
                )
            );
        }
        Hook::fire(
            'after_request',
            compact('discovered_handler', 'request_method', 'regex_matches', 'result')
        );
    }

    /**
     * @param       $name
     * @param array $params
     * @return bool|string
     */
    public static function reverse($name, $params = [])
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
        if (self::$routePrefix != null) {
            $route = self::$routePrefix.$route;
        }

        if (self::$base != null) {
            $route = self::$base.$route;
        }

        return $route;
    }

    /**
     * @param array $params
     * @return bool|string
     */
    public static function reverseMe($params = [])
    {
        if (empty(self::$foundRouteNameClean)) {
            return false;
        }

        return self::reverse(self::$foundRouteNameClean, $params);
    }

    /**
     * @param       $name
     * @param array $params
     * @return bool|array
     */
    public static function reverseAll($name, $params = [])
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

        if (isset($routeArray['preArgs']) && is_array($routeArray['preArgs'])) {
            $rev = array_reverse($routeArray['preArgs']);
            foreach ($rev as $arg) {
                array_unshift($params, $arg);
            }
        }
        if (isset($routeArray['postArgs']) && is_array($routeArray['postArgs'])) {
            foreach ($routeArray['postArgs'] as $arg) {
                $params[] = $arg;
            }
        }

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
    private static function isXHRRequest()
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

    /**
     * @param \Redis $redis
     */
    public static function attachRedisCache(\Redis $redis)
    {
        self::$redis = $redis;
    }

    /**
     * @param $prefix
     */
    public static function setRoutePrefix($prefix)
    {
        self::$routePrefix = $prefix;
    }

    /**
     * @return null|string
     */
    public static function getRoutePrefix()
    {
        return self::$routePrefix;
    }

    /**
     * @param $name
     */
    public static function setPageWrapperName($name)
    {
        self::$pageWrapperName = $name;
    }

    /**
     * @return null
     */
    public static function getPageWrapperName()
    {
        return self::$pageWrapperName;
    }

    /**
     * @param string     $className
     * @param array|null $args
     *
     * @return mixed
     */
    private static function instantiateHandlerClass(string $className, array $args = null)
    {
        Hook::fire('before_handler', $args);
        try {
            $obj = new $className($args);
        } catch(RouterExceptionInterface $e) {
            $e->executeLogic();
            die();
        }
        Hook::fire('after_handler', $args);

        return $obj;
    }
}
