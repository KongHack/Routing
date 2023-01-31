<?php
namespace GCWorld\Routing;

use Exception;
use GCWorld\Interfaces\PageWrapper;
use GCWorld\Interfaces\PEX;
use GCWorld\Routing\Exceptions\ReverseRouteNotFoundException;
use GCWorld\Routing\Interfaces\AdvancedHandlerInterface;
use GCWorld\Routing\Interfaces\ConstantsInterface;
use GCWorld\Routing\Interfaces\HandlerInterface;
use GCWorld\Routing\Interfaces\JSONHandlerInterface;
use GCWorld\Routing\Interfaces\RoutesInterface;
use GCWorld\Routing\Interfaces\RouterExceptionInterface;
use Redis;

/**
 * Class Router
 */
class CoreRouter implements ConstantsInterface
{
    protected static array $instances = [];      
    
    protected string $name;
    
    protected ?string      $base            = null;
    protected ?string      $userClassName   = null;
    protected ?string      $pageWrapperName = null;
    protected ?array       $forcedRoutes    = null;
    protected string       $callingMethod   = '';
    protected string       $foundPathFull   = '';
    protected string       $foundPathClean  = '';
    protected ?string      $routePrefix     = null;
    protected ?Debugger    $cDebugger       = null;
    protected ?Redis       $cRedis          = null;
    protected ?PEX         $cUser           = null;
    protected ?PageWrapper $cPageWrapper    = null;
    
    protected ?string $foundRouteName      = null;
    protected ?string $foundRouteNameClean = null;
    protected ?array  $foundRouteArguments = null;
    protected ?array  $foundRouteData      = [];

    public static function getInstance(string $name = self::DEFAULT_NAME)
    {
        if(!isset(self::$instances[$name])) {
            self::$instances[$name] = new static($name);
        }
        return self::$instances[$name];
    }

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    
    
    /**
     * @return null|string
     */
    public function getFoundRouteName()
    {
        return  $this->foundRouteName;
    }

    /**
     * @return null|string
     */
    public function getFoundRouteNameClean()
    {
        return  $this->foundRouteNameClean;
    }

    /**
     * @return null|array
     */
    public function getFoundRouteArguments()
    {
        return  $this->foundRouteArguments;
    }

    /**
     * @return array
     */
    public function getFoundRouteData()
    {
        return  $this->foundRouteData;
    }

    /**
     * @return string
     */
    public function getPathFull()
    {
        return  $this->foundPathFull;
    }
    
    /**
     * @return string
     */
    public function getPathClean()
    {
        return  $this->foundPathClean;
    }

    /**
     * @return string
     */
    protected function getPathInfo()
    {
        if (!empty($_SERVER['PATH_INFO'])) {
            return $_SERVER['PATH_INFO'];
        } elseif (!empty($_SERVER['ORIG_PATH_INFO']) && $_SERVER['ORIG_PATH_INFO'] !== '/index.php') {
            return $_SERVER['ORIG_PATH_INFO'];
        } else {
            if (!empty($_SERVER['REQUEST_URI'])) {
                return (strpos($_SERVER['REQUEST_URI'], '?') > 0) ? strstr(
                    $_SERVER['REQUEST_URI'],
                    '?',
                    true
                ) : $_SERVER['REQUEST_URI'];
            }
        }

        return '/';
    }
    
    /**
     * Processes routes.
     * @param null $path_info
     * @throws Exception
     */
    public function forward($path_info = null)
    {
        $this->fireHook('before_request');

        $request_method = strtolower($_SERVER['REQUEST_METHOD']);

        if ($path_info == null) {
            $path_info = $this->getPathInfo();
        }

        $this->foundPathFull = $path_info;

        if ($this->routePrefix != null) {
            if(str_starts_with($path_info, $this->routePrefix)) {
                $path_info = substr($path_info, strlen($this->routePrefix));
            }
        }

        $this->foundPathClean = $path_info;

        $pattern            = '';
        $discovered_handler = null;
        $regex_matches      = [];
        $cacheMatched       = false;

        if ($this->cRedis) {
            $data = $this->cRedis->hGet('GCROUTES:'.$this->name, $path_info);
            if ($data) {
                $routeData          = json_decode($data, true);
                $pattern            = $routeData['p'];
                $discovered_handler = $routeData['h'];
                $regex_matches      = $routeData['m'];
                $cacheMatched       = true;
            }
        }


        if ($discovered_handler == null) {
            if ($this->forcedRoutes == null) {
                $temp = explode('/', $path_info);
                if (count($temp) > 1) {
                    $master    = Processor::cleanClassName($temp[1]);
                    $className = str_replace('__NAME__', $this->name, self::CLASS_ROUTABLE).$master;
                    if (!class_exists($className)) {
                        $className = str_replace('__NAME__', $this->name, self::CLASS_MISC);
                        if (!class_exists($className)) {
                            throw new Exception('No Route Class Found For Base (1)');
                        }
                    }
                } else {
                    $className = str_replace('__NAME__', $this->name, self::CLASS_MISC);
                    if (!class_exists($className)) {
                        throw new Exception('No Route Class Found For Base (2)');
                    }
                }

                /** @var RoutesInterface $loader */
                $loader = new $className();
                $routes = $loader->getForwardRoutes();
            } else {
                $routes = $this->forcedRoutes;
            }

            if (isset($routes[$path_info])) {
                $pattern            = $path_info;
                $discovered_handler = $routes[$path_info];
            } elseif ($routes) {
                foreach ($routes as $pattern => $routeConfig) {
                    $pattern = strtr($pattern, self::ROUTING_TOKENS);
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
                $className = str_replace('__NAME__', $this->name, self::CLASS_REPLACEMENT);
                if (!class_exists($className)) {
                    $className = str_replace('__NAME__', $this->name, self::CLASS_MISC);;
                }
                if (class_exists($className)) {
                    /** @var RoutesInterface $loader */
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
                            $pattern = strtr($pattern, self::ROUTING_TOKENS);
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

        if (!$cacheMatched && $this->cRedis) {
            $this->cRedis->hSet('GCROUTES:'.$this->name, $path_info, json_encode([
                'p' => $pattern,
                'h' => $discovered_handler,
                'm' => $regex_matches
            ]));
        }

        $this->foundRouteArguments = $regex_matches;

        $handler_instance = null;
        if ($discovered_handler) {
            if ($this->cDebugger !== null && $pattern != '') {
                $this->cDebugger->logHit($pattern);
            }

            if (is_string($discovered_handler)) {
                if (class_exists($discovered_handler)) {
                    $handler_instance = $this->instantiateHandlerClass($discovered_handler, $regex_matches);
                } else {
                    throw new Exception('Class Not Found: '.$discovered_handler);
                }
            } elseif (is_array($discovered_handler)) {
                if (isset($discovered_handler['name'])) {
                    $this->foundRouteName = $discovered_handler['name'];
                    $tmp                  = explode('_', $discovered_handler['name']);
                    if (is_numeric($tmp[count($tmp) - 1])) {
                        array_pop($tmp);
                        $this->foundRouteNameClean = implode('_', $tmp);
                    } else {
                        $this->foundRouteNameClean = $this->foundRouteName;
                    }
                }

                $this->foundRouteData = $discovered_handler;

                $hasSession = false;
                //Used for new reverse name search.
                if (isset($discovered_handler['session']) &&
                    $discovered_handler['session'] == true &&
                    session_status() == PHP_SESSION_NONE
                ) {
                    $this->fireHook('pre-session_start');
                    session_start();
                    $this->fireHook('post-session_start');
                    $hasSession = true;
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

                if($hasSession) {
                    $this->securityCheck($discovered_handler, $regex_matches);
                }

                if (isset($discovered_handler['class']) && is_string($discovered_handler['class'])) {
                    $discovered_class = $discovered_handler['class'];
                    if (class_exists($discovered_class)) {
                        $handler_instance = $this->instantiateHandlerClass($discovered_class, $regex_matches);
                    } else {
                        throw new Exception('Class Not Found: '.$discovered_class);
                    }
                }
            } elseif (is_callable($discovered_handler)) {
                $handler_instance = $discovered_handler($regex_matches);
            }
        }

        if ($handler_instance) {
            try {

                if (($this->isXHRRequest() || $handler_instance instanceof JSONHandlerInterface)
                    && method_exists($handler_instance, $request_method.'XHR')
                ) {
                    $this->callingMethod = $request_method.'XHR';
                    header('Content-type: application/json');
                    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                    header('Cache-Control: no-store, no-cache, must-revalidate');
                    header('Cache-Control: post-check=0, pre-check=0', false);
                    header('Pragma: no-cache');
                    $request_method .= 'XHR';
                }

                if (method_exists($handler_instance, $request_method)) {
                    $this->callingMethod = $request_method;
                    if (isset($discovered_handler['autoWrapper']) && $discovered_handler['autoWrapper']) {
                        if ($handler_instance instanceof HandlerInterface
                            || $handler_instance instanceof AdvancedHandlerInterface
                        ) {
                            $title = $handler_instance->getTitle();
                            $handler_instance->setBreadcrumbs();


                            if ($this->cPageWrapper == null && $this->pageWrapperName != null) {
                                /** @var mixed $temp */
                                $temp = $this->pageWrapperName;
                                if (class_exists($temp)) {
                                    $this->cPageWrapper = $temp::getInstance();
                                }
                            }
                            if ($this->cPageWrapper != null) {
                                $this->cPageWrapper->setTitle($title);
                            }
                        }
                    }
                    $this->fireHook('before_request_method');

                    if (count($regex_matches) > 0) {
                        $this->callingMethod = $request_method;
                        $result              = $handler_instance->$request_method(...$regex_matches);
                    } else {
                        $this->callingMethod = $request_method;
                        $result              = $handler_instance->$request_method();
                    }

                    $this->fireHook('after_request_method');

                    if ($handler_instance instanceof AdvancedHandlerInterface) {
                        if ($this->isXHRRequest() && is_array($result)) {
                            echo \json_encode($result);
                        } else {
                            echo $result;
                        }
                    } elseif($handler_instance instanceof JSONHandlerInterface) {
                        echo json_encode($result);
                    }

                    $this->fireHook('after_output');
                } else {
                    $this->fireHook('404');
                }

            } catch(RouterExceptionInterface $e) {
                $e->executeLogic();

                return;
            }
        } else {
            $this->fireHook('404');
        }
        $this->fireHook('after_request');
    }

    /**
     * @param string $name
     * @param array  $params
     * @return string
     * @throws ReverseRouteNotFoundException
     */
    public function reverse(string $name, array $params = [])
    {
        if (($routeArray = $this->reverseAll($name, $params)) === false) {
            throw new ReverseRouteNotFoundException($name, $params);
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
        if ($this->routePrefix != null) {
            $route = $this->routePrefix.$route;
        }

        if ($this->base != null) {
            $route = $this->base.$route;
        }

        return $route;
    }

    /**
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function reverseMe(array $params = [])
    {
        if (empty($this->getFoundRouteNameClean())) {
            throw new \Exception('Found Route Name Clean is empty!');
        }

        return $this->reverse($this->getFoundRouteNameClean(), $params);
    }

    /**
     * @param string $name
     * @param array  $params
     * @return array
     * @throws ReverseRouteNotFoundException
     */
    public function reverseAll(string $name, array $params = [])
    {
        // We now add the count of parameters to the name. See Processor.php for more info.
        $name .= '_'.count($params);

        $temp   = explode('_', $name);
        $master = '\GCWorld\Routing\Generated\MasterRoute_'.Processor::cleanClassName($temp[0]);
        if (!class_exists($master)) {
            $master = '\GCWorld\Routing\Generated\MasterRoute_MISC';
        }

        /** @var RoutesInterface $cTemp */
        $cTemp  = new $master();
        $routes = $cTemp->getReverseRoutes();

        if (!array_key_exists($name, $routes)) {
            throw new ReverseRouteNotFoundException($name, $params);
        }

        return $routes[$name];
    }

    /**
     * @param string $name
     * @param array  $params
     * @return mixed
     */
    public function reverseObject(string $name, array $params = [])
    {
        // throws an exception now, woo!
        $routeArray = $this->reverseAll($name, $params);
        $className  = $routeArray['class'];

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
     * @param string $base
     */
    public function setBase(string $base)
    {
        $this->base = rtrim($base, '/');
    }

    /**
     * @param string $user
     */
    public function setUserClassName(string $user)
    {
        $this->userClassName = $user;
    }

    /**
     * @return bool
     */
    protected function isXHRRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * @param string $pexNode
     * @param array  $regexMatches
     * @return string
     */
    protected function replacePexKeys($pexNode, array $regexMatches)
    {
        foreach ($regexMatches as $k => $v) {
            $pexNode = str_replace('['.$k.']', $v, $pexNode);
        }

        return $pexNode;
    }

    /**
     * @param Debugger $cDebugger
     */
    public function attachDebugger(Debugger $cDebugger)
    {
        $this->cDebugger = $cDebugger;
    }

    /**
     * @param array $routes
     */
    public function forceRoutes(array $routes)
    {
        $this->forcedRoutes = $routes;
    }

    /**
     * @param Redis $cRedis
     */
    public function attachRedisCache(\Redis $cRedis)
    {
        $this->cRedis = $cRedis;
    }

    /**
     * @param string|null $prefix
     */
    public function setRoutePrefix(string $prefix = null)
    {
        $this->routePrefix = $prefix;
    }

    /**
     * @return null|string
     */
    public function getRoutePrefix()
    {
        return $this->routePrefix;
    }

    /**
     * @param string $name
     */
    public function setPageWrapperName(string $name)
    {
        $this->pageWrapperName = $name;
    }

    /**
     * @return null|string
     */
    public function getPageWrapperName()
    {
        return $this->pageWrapperName;
    }

    /**
     * @return string
     */
    public function getCallingMethod()
    {
        return $this->callingMethod;
    }

    /**
     * @param string     $className
     * @param array|null $args
     *
     * @return mixed
     */
    protected function instantiateHandlerClass(string $className, array $args = null)
    {
        $this->fireHook('before_handler', $args);
        try {
            $obj = new $className($args);
        } catch(RouterExceptionInterface $e) {
            $e->executeLogic();
            die();
        }
        $this->fireHook('after_handler', $args);

        return $obj;
    }

    /**
     * @param string     $type
     * @param array|null $args
     *
     * @return void
     */
    protected function fireHook(string $type, array $args = null)
    {
        try {
            Hook::fire($type, $args);
        } catch (RouterExceptionInterface $e) {
            $e->executeLogic();
            die();
        }
    }

    /**
     * @param mixed $discovered_handler
     * @param array $regex_matches
     * @return void
     * @throws Exception
     */
    protected function securityCheck($discovered_handler, array $regex_matches)
    {

        // Security Testing!
        if ($this->userClassName != null) {
            /** @var mixed $temp */
            $temp       = $this->userClassName;
            $this->cUser = $temp::getInstance();
        }

        if ($this->cUser != null) {
            if (!$this->cUser instanceof PEX) {
                throw new Exception('The provided cUser class does not implement PEX. ('.
                    $this->userClassName.')');
            }
            $types = ['pexCheck', 'pexCheckAny', 'pexCheckExact', 'pexCheckMax'];
            foreach ($types as $type) {
                if (isset($discovered_handler[$type])) {
                    if (!is_array($discovered_handler[$type])) {
                        if ($this->cUser->$type($this->replacePexKeys(
                                $discovered_handler[$type],
                                $regex_matches
                            )) < 1
                        ) {
                            $this->fireHook('403_pex',[
                                'node' => $discovered_handler[$type],
                            ]);
                        }
                    } else {
                        $good = false;
                        foreach ($discovered_handler[$type] as $node) {
                            if ($this->cUser->$type($this->replacePexKeys($node, $regex_matches)) > 0) {
                                $good = true;
                                break;
                            }
                        }
                        if (!$good) {
                            $this->fireHook('403_pex',[
                                'node' => $discovered_handler[$type],
                            ]);
                        }
                    }
                }
            }
        }
    }
}
