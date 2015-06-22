<?php
namespace GCWorld\Routing;

/**
 * Class LoadRoutes
 * @package GCWorld\Routing
 */
class LoadRoutes
{
    /**
     * @var null
     */
    private static $instance       = null;
    /**
     * @var array
     */
    private static $classes        = array();
    /**
     * @var int
     */
    private static $highestTime    = 0;
    /**
     * @var int
     */
    private static $lastClassTime  = PHP_INT_MAX;

    /**
     * Singleton Format
     */
    private function __clone()
    {
    }

    /**
     * Singleton Format
     */
    private function __construct()
    {
    }

    /**
     * @return \GCWorld\Routing\LoadRoutes|null
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param      $fullClass
     * @param bool $skipCheck
     * @return $this
     * @throws \Exception
     */
    public function addRoute($fullClass, $skipCheck = false)
    {
        if (!$skipCheck) {
            if (!class_exists($fullClass)) {
                throw new \Exception('Class Not Found: '.$fullClass);
            }
        }
        self::$classes[] = $fullClass;
        return $this;
    }

    /**
     * @param bool $force
     * @param bool $debug
     * @throws \Exception
     */
    public function generateRoutes($force = false, $debug = false)
    {
        foreach (self::$classes as $fullClass) {
            $cTemp = new $fullClass;
            if ($cTemp instanceof \GCWorld\Routing\RawRoutesInterface) {
                $time = $cTemp->getFileTime();
                if ($time > self::$highestTime) {
                    self::$highestTime = $time;
                }
            }
        }

        $base = dirname(__FILE__).'/Generated/*';
        $files = self::glob_recursive($base);
        foreach ($files as $file) {
            if (is_file($file)) {
                $time = filemtime($file);
                if ($time < self::$lastClassTime) {
                    self::$lastClassTime = $time;
                }
            }
        }

        if (self::$highestTime > self::$lastClassTime || count($files) != count(self::$classes) || $force) {
            $routes = array();
            foreach (self::$classes as $fullClass) {
                $cTemp = new $fullClass;
                if ($cTemp instanceof \GCWorld\Routing\RawRoutesInterface) {
                    $routes = array_merge($routes, $cTemp->getRoutes());
                }
            }

            $processor = new Processor($debug);
            $processor->run($routes);
        }
    }

    /**
     * @param     $pattern
     * @param int $flags
     * @return array
     */
    private static function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $files = array_merge($files, self::glob_recursive($dir.'/'.basename($pattern), $flags));
        }
        return $files;
    }
}
