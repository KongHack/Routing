<?php
namespace GCWorld\Routing\Core;

use Exception;
use GCWorld\Interfaces\PageWrapper;
use GCWorld\Interfaces\PEX;
use GCWorld\Interfaces\RoutingInterface;
use GCWorld\Routing\Debugger;
use GCWorld\Routing\Exceptions\ReverseRouteNotFoundException;
use GCWorld\Routing\Exceptions\RouteClassNotFoundException;
use GCWorld\Routing\Hook;
use GCWorld\Routing\Interfaces\AdvancedHandlerInterface;
use GCWorld\Routing\Interfaces\ConstantsInterface;
use GCWorld\Routing\Interfaces\HandlerInterface;
use GCWorld\Routing\Interfaces\JSONHandlerInterface;
use GCWorld\Routing\Interfaces\RouterExceptionInterface;
use GCWorld\Routing\Interfaces\RoutesInterface;
use GCWorld\Routing\Processor;
use Redis;

/**
 * Class CoreRouter
 */
class CoreRouter implements ConstantsInterface, RoutingInterface
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
    protected ?PageWrapper $cPageWrapper    = null;

    protected ?string $foundRouteName      = null;
    protected ?string $foundRouteNameClean = null;
    protected ?array  $foundRouteArguments = null;
    protected ?array  $foundRouteData      = [];

    /**
     * @param string $name
     * @return RoutingInterface
     */
    public static function getInstance(string $name = self::DEFAULT_NAME): RoutingInterface
    {
        if(!isset(static::$instances[$name])) {
            static::$instances[$name] = new static($name);
        }

        return static::$instances[$name];
    }

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $hookName
     * @param string $methodName
     * @return void
     */
    public function addHook(string $hookName, string $methodName): void
    {
        Hook::add($this->name, $hookName, $methodName);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return null|string
     */
    public function getFoundRouteName(): ?string
    {
        return  $this->foundRouteName;
    }

    /**
     * @return null|string
     */
    public function getFoundRouteNameClean(): ?string
    {
        return  $this->foundRouteNameClean;
    }

    /**
     * @return null|array
     */
    public function getFoundRouteArguments(): ?array
    {
        return  $this->foundRouteArguments;
    }

    /**
     * @return array
     */
    public function getFoundRouteData(): array
    {
        return  $this->foundRouteData;
    }

    /**
     * @return string
     */
    public function getPathFull(): string
    {
        return  $this->foundPathFull;
    }

    /**
     * @return string
     */
    public function getPathClean(): string
    {
        return  $this->foundPathClean;
    }

    /**
     * @return string
     */
    protected function getPathInfo(): string
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
     * @param string|null $path
     * @return RouteDiscoveryData|null
     */
    public function discoverRoute(string $path = null): ?RouteDiscoveryData
    {
        $path = $path ?? $this->getPathInfo();

        if ($this->routePrefix != null &&
            str_starts_with($path, $this->routePrefix)
        ) {
            $path = substr($path, strlen($this->routePrefix));
        }

        $cDiscovery = new RouteDiscovery($this->name);

        return $cDiscovery->execute($path);
    }

    /**
     * Process & Execute Route. Only call once from a front loader
     * @param null $path_info
     * @throws Exception
     */
    public function forward($path_info = null): void
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

        $cDiscovery = new RouteDiscovery($this->name);
        if($this->cRedis) {
            $cDiscovery->setRedis($this->cRedis);
        }
        if(!empty($this->forcedRoutes)) {
            $cDiscovery->forceRoutes($this->forcedRoutes);
        }
        $cDiscoveryData = $cDiscovery->execute($path_info);

        if($cDiscoveryData === null) {
            $this->fireHook('404');
            $this->fireHook('after_request');
            return;
        }

        $matches                   = $cDiscoveryData->getMatches();
        $this->foundRouteArguments = $matches;

        if ($this->cDebugger !== null) {
            $this->cDebugger->logHit($cDiscoveryData->getPattern());
        }

        $aHandler = $cDiscoveryData->getHandler();
        $cHandler = null;

        if (isset($aHandler['name'])) {
            $this->foundRouteName = $aHandler['name'];
            $tmp                  = explode('_', $aHandler['name']);
            if (is_numeric($tmp[count($tmp) - 1])) {
                array_pop($tmp);
                $this->foundRouteNameClean = implode('_', $tmp);
            } else {
                $this->foundRouteNameClean = $this->foundRouteName;
            }
        }

        $this->foundRouteData = $aHandler;

        $hasSession = false;
        //Used for new reverse name search.
        if (isset($aHandler['session']) &&
            $aHandler['session'] == true &&
            session_status() == PHP_SESSION_NONE
        ) {
            $this->fireHook('pre-session_start');
            session_start();
            $this->fireHook('post-session_start');
            $hasSession = true;
        }

        //Handle pre & post handler options
        if (isset($aHandler['preArgs']) && is_array($aHandler['preArgs'])) {
            $rev = array_reverse($aHandler['preArgs']);
            foreach ($rev as $arg) {
                array_unshift($matches, $arg);
            }
        }

        if (isset($aHandler['postArgs']) && is_array($aHandler['postArgs'])) {
            foreach ($aHandler['postArgs'] as $arg) {
                $matches[] = $arg;
            }
        }

        if($hasSession) {
            $this->securityCheck($aHandler, $matches);
        }

        if (isset($aHandler['class']) && is_string($aHandler['class'])) {
            $discovered_class = $aHandler['class'];
            if (class_exists($discovered_class)) {
                $cHandler = $this->instantiateHandlerClass($discovered_class, $matches);
            } else {
                throw new RouteClassNotFoundException($discovered_class, $matches);
            }
        }

        if(!$cHandler) {
            $this->fireHook('404');
            $this->fireHook('after_request');
            return;
        }

        try {

            if (($this->isXHRRequest() || $cHandler instanceof JSONHandlerInterface)
                && method_exists($cHandler, $request_method.'XHR')
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

            if (!method_exists($cHandler, $request_method)) {
                $this->fireHook('404');
                $this->fireHook('after_request');
                return;
            }

            $this->callingMethod = $request_method;
            if (isset($aHandler['autoWrapper'])
                && $aHandler['autoWrapper']
                && ($cHandler instanceof HandlerInterface
                    || $cHandler instanceof AdvancedHandlerInterface
                )
            ) {
                $title = $cHandler->getTitle();
                $cHandler->setBreadcrumbs();

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

            $this->fireHook('before_request_method');

            if (count($matches) > 0) {
                $this->callingMethod = $request_method;
                $result              = $cHandler->$request_method(...$matches);
            } else {
                $this->callingMethod = $request_method;
                $result              = $cHandler->$request_method();
            }

            $this->fireHook('after_request_method');

            if ($cHandler instanceof AdvancedHandlerInterface) {
                if ($this->isXHRRequest() && is_array($result)) {
                    echo \json_encode($result);
                } else {
                    echo $result;
                }
            } elseif($cHandler instanceof JSONHandlerInterface) {
                echo json_encode($result);
            }

            $this->fireHook('after_output');
        } catch(RouterExceptionInterface $e) {
            $e->executeLogic();
        }

        $this->fireHook('after_request');
    }

    /**
     * @param string $name
     * @param array  $params
     * @return string
     * @throws ReverseRouteNotFoundException
     */
    public function reverse(string $name, array $params = []): string
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
    public function reverseMe(array $params = []): string
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
    public function reverseAll(string $name, array $params = []): array
    {
        // We now add the count of parameters to the name. See Processor.php for more info.
        $name .= '_'.count($params);

        $temp   = explode('_', $name);
        $master = str_replace('__NAME__',$this->name, self::CLASS_ROUTABLE).Processor::cleanClassName($temp[0]);
        if (!class_exists($master)) {
            $master = str_replace('__NAME__',$this->name, self::CLASS_MISC);
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
    public function reverseObject(string $name, array $params = []): mixed
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
     * @return void
     */
    public function setBase(string $base): void
    {
        $this->base = rtrim($base, '/');
    }

    /**
     * @param string $user
     * @return void
     */
    public function setUserClassName(string $user): void
    {
        $this->userClassName = $user;
    }

    /**
     * @return bool
     */
    public function isXHRRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * @param string $pexNode
     * @param array  $regexMatches
     * @return string
     */
    protected function replacePexKeys($pexNode, array $regexMatches): string
    {
        foreach ($regexMatches as $k => $v) {
            $pexNode = str_replace('['.$k.']', $v, $pexNode);
        }

        return $pexNode;
    }

    /**
     * @param Debugger $cDebugger
     * @return void
     */
    public function attachDebugger(Debugger $cDebugger): void
    {
        $this->cDebugger = $cDebugger;
    }

    /**
     * @param array $routes
     * @return void
     */
    public function forceRoutes(array $routes): void
    {
        $this->forcedRoutes = $routes;
    }

    /**
     * @param Redis $cRedis
     * @return void
     */
    public function attachRedisCache(\Redis $cRedis): void
    {
        $this->cRedis = $cRedis;
    }

    /**
     * @param string|null $prefix
     * @return void
     */
    public function setRoutePrefix(string $prefix = null): void
    {
        $this->routePrefix = $prefix;
    }

    /**
     * @return null|string
     */
    public function getRoutePrefix(): ?string
    {
        return $this->routePrefix;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setPageWrapperName(string $name): void
    {
        $this->pageWrapperName = $name;
    }

    /**
     * @return null|string
     */
    public function getPageWrapperName(): ?string
    {
        return $this->pageWrapperName;
    }

    /**
     * @return string
     */
    public function getCallingMethod(): string
    {
        return $this->callingMethod;
    }

    /**
     * @param string     $className
     * @param array|null $args
     *
     * @return mixed
     */
    protected function instantiateHandlerClass(string $className, array $args = null): mixed
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
            Hook::fire($this->name, $type, $args);
        } catch (RouterExceptionInterface $e) {
            $e->executeLogic();
            die();
        }
    }

    /**
     * @param array $aHandler
     * @param array $matches
     * @return void
     * @throws Exception
     */
    protected function securityCheck(array $aHandler, array $matches)
    {
        // Security Testing!
        if ($this->userClassName != null) {
            /** @var mixed $temp */
            $temp  = $this->userClassName;
            $cUser = $temp::getInstance();
        }

        if ($cUser != null) {
            if (!$cUser instanceof PEX) {
                throw new Exception('The provided cUser class does not implement PEX. ('.
                    $this->userClassName.')');
            }
            $types = ['pexCheck', 'pexCheckAny', 'pexCheckExact', 'pexCheckMax'];
            foreach ($types as $type) {
                if (isset($aHandler[$type])) {
                    $good = false;
                    foreach ($aHandler[$type] as $node) {
                        if ($cUser->$type($this->replacePexKeys($node, $matches)) > 0) {
                            $good = true;
                            break;
                        }
                    }
                    if (!$good) {
                        $this->fireHook('403_pex', [
                            'node' => $aHandler[$type],
                        ]);
                    }
                }
            }
        }
    }
}
