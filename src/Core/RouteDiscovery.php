<?php
namespace GCWorld\Routing\Core;

use Exception;
use GCWorld\Routing\Interfaces\ConstantsInterface;
use GCWorld\Routing\Interfaces\RoutesInterface;
use GCWorld\Routing\Processor;

/**
 * Class RouteDiscovery.
 */
class RouteDiscovery implements ConstantsInterface
{
    protected string  $name;

    protected ?\Redis $cRedis       = null;
    protected ?array  $forcedRoutes = null;
    
    /**
     * @param string $name
     */
    public function __construct(string $name = ConstantsInterface::DEFAULT_NAME)
    {
        $this->name = $name;
    }

    /**
     * @param \Redis $cRedis
     * @return void
     */
    public function setRedis(\Redis $cRedis)
    {
        $this->cRedis = $cRedis;
    }

    /**
     * @return void
     */
    public function purgeRedis()
    {
        if($this->cRedis === null) {
            return;
        }

        $this->cRedis->del(self::REDIS_PREFIX.$this->name);
    }

    /**
     * @param array $routes
     */
    public function forceRoutes(array $routes)
    {
        $this->forcedRoutes = $routes;
    }

    /**
     * @param string $path
     *
     * @return RouteDiscoveryData|null
     */
    public function execute(string $path)
    {
        if ($this->cRedis) {
            $data = $this->cRedis->hGet(self::REDIS_PREFIX.$this->name, $path);
            if ($data) {
                $cData = \unserialize($data);
                if($cData instanceof RouteDiscoveryData) {
                    return $cData;
                }
            }
        }

        if ($this->forcedRoutes == null) {
            $temp = explode('/', $path);
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

        $cData = $this->matchRoute($routes, $path);
        if($cData === null)  {
            $className = str_replace('__NAME__', $this->name, self::CLASS_REPLACEMENT);
            if (!class_exists($className)) {
                $className = str_replace('__NAME__', $this->name, self::CLASS_MISC);;
            }
            if (class_exists($className)) {
                /** @var RoutesInterface $loader */
                $loader = new $className();
                $routes = $loader->getForwardRoutes();

                $cData  = $this->matchRoute($routes, $path);
            }
        }

        if($cData !== null && $this->cRedis !== null) {
            $this->cRedis->hSet(self::REDIS_PREFIX.$this->name, $path, serialize($cData));
        }

        return $cData;
    }

    /**
     * @param array  $routes
     * @param string $path
     * @return RouteDiscoveryData|null
     */
    protected function matchRoute(array $routes, string $path)
    {
        if (isset($routes[$path])) {
            return new RouteDiscoveryData($path, $routes[$path]);
        }
        foreach ($routes as $pattern => $routeConfig) {
            $pattern = strtr($pattern, self::ROUTING_TOKENS);
            if (preg_match('#^/?'.$pattern.'/?$#', $path, $matches)) {
                unset($matches[0]);
                $matches = array_values($matches);
                return new RouteDiscoveryData($pattern, $routeConfig, $matches);
            }
        }

        return null;
    }

}