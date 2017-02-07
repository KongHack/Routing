<?php
namespace GCWorld\Routing;

use GCWorld\Database\Database;
use GCWorld\Utilities\General;
use phpDocumentor\Reflection\DocBlock;

/**
 * Class LoadRoutes
 * @package GCWorld\Routing
 */
class LoadRoutes
{
    use General;

    /**
     * @var null
     */
    private static $instance = null;
    /**
     * @var array
     */
    private static $classes = [];
    /**
     * @var array
     */
    private static $paths = [];
    /**
     * @var int
     */
    private static $highestTime = 0;
    /**
     * @var int
     */
    private static $lastClassTime = PHP_INT_MAX;

    /**
     * @var \Redis|null
     */
    private static $redis = null;

    /**
     * @var  Database|null
     */
    private static $db = null;


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
     * Add a file system path for automatic parsing.
     *
     * @param $path
     * @return $this
     */
    public function addPath($path)
    {
        self::$paths[] = $path;

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
            if ($cTemp instanceof RawRoutesInterface) {
                $time = $cTemp->getFileTime();
                if ($time > self::$highestTime) {
                    self::$highestTime = $time;
                }
            }
        }

        $base  = dirname(__FILE__).'/Generated/*';
        $files = self::glob_recursive($base);
        foreach ($files as $file) {
            if (is_file($file)) {
                $time = filemtime($file);
                if ($time < self::$lastClassTime) {
                    self::$lastClassTime = $time;
                }
            }
        }

        if ($force
            || count(self::$paths) || self::$highestTime > self::$lastClassTime
            || count($files) != count(self::$classes)
        ) {
            $routes = [];
            foreach (self::$classes as $fullClass) {
                $cTemp = new $fullClass;
                if ($cTemp instanceof RawRoutesInterface) {
                    $routes = array_merge($routes, $cTemp->getRoutes());
                }
            }

            $routes = array_merge($routes, self::generateAnnotatedRoutes());

            $processor = new Processor($debug);
            $processor->run($routes);

            if (self::$redis !== null) {
                self::$redis->del('GCWORLD_ROUTER');
            }

            if (self::$db !== null) {
                $this->storeRoutes($processor);
            }

        }
    }

    /**
     * @return array
     */
    private static function generateAnnotatedRoutes()
    {
        $return = [];
        if (count(self::$paths) > 0) {
            foreach (self::$paths as $path) {
                $classFiles = self::glob_recursive(rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*.php');
                foreach ($classFiles as $file) {
                    $namespace = '';
                    $className = '';
                    $fh        = fopen($file, 'r');
                    while (($buffer = fgets($fh)) !== false) {
                        if (substr($buffer, 0, 9) == 'namespace') {
                            $namespace = substr(trim($buffer), 10, -1);
                        }
                        if (substr($buffer, 0, 5) == 'class') {
                            $temp      = explode(' ', $buffer);
                            $className = $temp[1];
                            break;
                        }
                    }
                    $classString = trim('\\'.$namespace.'\\'.$className);
                    if (class_exists($classString)) {
                        $thisClass = new \ReflectionClass($classString);
                        if (($comment = $thisClass->getDocComment()) !== false) {
                            $phpDoc = new DocBlock($comment);
                            $routes = self::processTags($classString, $phpDoc);
                            if ($routes) {
                                $return = array_merge($return, $routes);
                            }
                        }
                    }
                }
            }
        }

        return $return;
    }

    /**
     * @param string                             $classString
     * @param \phpDocumentor\Reflection\DocBlock $phpDoc
     * @return array|bool
     */
    private static function processTags($classString, DocBlock $phpDoc)
    {
        if ($phpDoc->hasTag('router-1-pattern') && $phpDoc->hasTag('router-1-name')) {
            return self::processComplexTags($classString, $phpDoc);
        }

        if (!$phpDoc->hasTag('router-pattern') || !$phpDoc->hasTag('router-name')) {
            return false;
        }

        $routes  = [];
        $pattern = $phpDoc->getTagsByName('router-pattern');
        foreach ($pattern as $patMaster) {
            $pat = $patMaster->getContent();

            $routes[$pat] = [
                'class'       => $classString,
                'name'        => $phpDoc->getTagsByName('router-name')[0]->getContent(),
                'autoWrapper' => false,
            ];

            $session = $phpDoc->getTagsByName('router-session');
            if (count($session) > 0) {
                $sessionString           = strtolower($session[0]->getContent());
                $routes[$pat]['session'] = in_array($sessionString, ['true', 't', 'y', 'yes']);
            }

            // Remaining items that can be both a string or an array.
            $processingArray = [
                'pexCheck'      => $phpDoc->getTagsByName('router-pexCheck'),
                'pexCheckAny'   => $phpDoc->getTagsByName('router-pexCheckAny'),
                'pexCheckExact' => $phpDoc->getTagsByName('router-pexCheckExact'),
                'preArgs'       => $phpDoc->getTagsByName('router-preArgs'),
                'postArgs'      => $phpDoc->getTagsByName('router-postArgs'),
                'title'         => $phpDoc->getTagsByName('router-title'),
                'meta'          => $phpDoc->getTagsByName('router-meta'),
                'autoWrapper'   => $phpDoc->getTagsByName('router-autoWrapper'),
            ];

            foreach ($processingArray as $key => $var) {
                /** @var \phpDocumentor\Reflection\DocBlock\Tag[] $var */

                if (count($var) == 1) {
                    $routes[$pat][$key] = trim($var[0]->getContent());
                } elseif (count($var) > 1) {
                    $temp = [];
                    foreach ($var as $t) {
                        $temp[] = trim($t->getContent());
                    }
                    $routes[$pat][$key] = $temp;
                }
            }

            if (isset($routes[$pat]['meta'])) {
                $tmp = explode(' ', $routes[$pat]['meta']);
                // Reset and process
                $routes[$pat]['meta'] = [];
                foreach ($tmp as $item) {
                    $tmp2 = explode(':', $item);
                    if (count($tmp2) == 2) {
                        $routes[$pat]['meta'][$tmp2[0]] = $tmp2[1];
                    }
                }
            } else {
                $routes[$pat]['meta'] = [];
            }
            
            if(isset($routes[$pat]['preArgs']) && !is_array($routes[$pat]['preArgs'])) {
                $routes[$pat]['preArgs'] = [$routes[$pat]['preArgs']];
            }
            if(isset($routes[$pat]['postArgs']) && !is_array($routes[$pat]['postArgs'])) {
                $routes[$pat]['postArgs'] = [$routes[$pat]['postArgs']];
            }

            if (strlen($routes[$pat]['autoWrapper']) > 0) {
                $routes[$pat]['autoWrapper'] = in_array($routes[$pat]['autoWrapper'], ['true', 't', 'y', 'yes']);
            }
        }

        return $routes;
    }

    /**
     * @param string                             $classString
     * @param \phpDocumentor\Reflection\DocBlock $phpDoc
     * @return array|bool
     */
    private static function processComplexTags($classString, DocBlock $phpDoc)
    {
        if (!$phpDoc->hasTag('router-1-pattern') || !$phpDoc->hasTag('router-1-name')) {
            return false;
        }

        $routes = [];
        $i      = 0;

        while ($i < 1000) {   // Just to be safe...
            ++$i;
            $pattern = $phpDoc->getTagsByName('router-'.$i.'-pattern');
            if (!$pattern) {
                break;
            }
            foreach ($pattern as $patMaster) {
                $pat = $patMaster->getContent();

                $routes[$pat] = [
                    'class'       => $classString,
                    'name'        => $phpDoc->getTagsByName('router-'.$i.'-name')[0]->getContent(),
                    'autoWrapper' => false,
                ];

                $session = $phpDoc->getTagsByName('router-'.$i.'-session');
                if (count($session) > 0) {
                    $sessionString           = strtolower($session[0]->getContent());
                    $routes[$pat]['session'] = in_array($sessionString, ['true', 't', 'y', 'yes']);
                }

                // Remaining items that can be both a string or an array.
                $processingArray = [
                    'pexCheck'      => $phpDoc->getTagsByName('router-'.$i.'-pexCheck'),
                    'pexCheckAny'   => $phpDoc->getTagsByName('router-'.$i.'-pexCheckAny'),
                    'pexCheckExact' => $phpDoc->getTagsByName('router-'.$i.'-pexCheckExact'),
                    'preArgs'       => $phpDoc->getTagsByName('router-'.$i.'-preArgs'),
                    'postArgs'      => $phpDoc->getTagsByName('router-'.$i.'-postArgs'),
                    'title'         => $phpDoc->getTagsByName('router-'.$i.'-title'),
                    'meta'          => $phpDoc->getTagsByName('router-'.$i.'-meta'),
                    'autoWrapper'   => $phpDoc->getTagsByName('router-'.$i.'-autoWrapper'),
                ];

                foreach ($processingArray as $key => $var) {
                    /** @var \phpDocumentor\Reflection\DocBlock\Tag[] $var */

                    if (count($var) == 1) {
                        $routes[$pat][$key] = trim($var[0]->getContent());
                    } elseif (count($var) > 1) {
                        $temp = [];
                        foreach ($var as $t) {
                            $temp[] = trim($t->getContent());
                        }
                        $routes[$pat][$key] = $temp;
                    }
                }

                if (isset($routes[$pat]['meta'])) {
                    $tmp = explode(' ', $routes[$pat]['meta']);
                    // Reset and process
                    $routes[$pat]['meta'] = [];
                    foreach ($tmp as $item) {
                        $tmp2 = explode(':', $item);
                        if (count($tmp2) == 2) {
                            $routes[$pat]['meta'][$tmp2[0]] = $tmp2[1];
                        }
                    }
                } else {
                    $routes[$pat]['meta'] = [];
                }
                
                if(isset($routes[$pat]['preArgs']) && !is_array($routes[$pat]['preArgs'])) {
                    $routes[$pat]['preArgs'] = [$routes[$pat]['preArgs']];
                }
                if(isset($routes[$pat]['postArgs']) && !is_array($routes[$pat]['postArgs'])) {
                    $routes[$pat]['postArgs'] = [$routes[$pat]['postArgs']];
                }

                if (strlen($routes[$pat]['autoWrapper']) > 0) {
                    $routes[$pat]['autoWrapper'] = in_array($routes[$pat]['autoWrapper'], ['true', 't', 'y', 'yes']);
                }
            }
        }

        return $routes;
    }

    /**
     * @param \Redis $redis
     */
    public static function attachRedisCache(\Redis $redis)
    {
        self::$redis = $redis;
    }

    /**
     * @param \GCWorld\Database\Database $db
     */
    public static function attachDatabase(Database $db)
    {
        self::$db = $db;
    }

    /**
     * @return string
     */
    public function getOurRoot()
    {
        return dirname(__FILE__).'/../';
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return trim(file_get_contents($this->getOurRoot().'VERSION'));
    }

    protected function storeRoutes(Processor $processor)
    {
        $table = '_RouteRawList';
        // Make sure our table exists.
        if (!self::$db->tableExists($table)) {
            $sql = file_get_contents($this->getOurRoot().'datamodel/'.$table.'.sql');
            self::$db->exec($sql);
            self::$db->setTableComment($table, $this->getVersion());
        } else {
            $dbv = self::$db->getTableComment($table);
            if ($dbv != $this->getVersion()) {
                $sql = 'DROP TABLE '.$table;
                self::$db->exec($sql);
                $sql = file_get_contents($this->getOurRoot().'datamodel/'.$table.'.sql');
                self::$db->exec($sql);
                self::$db->setTableComment($table, $this->getVersion());
            }
        }

        $sql = 'TRUNCATE TABLE `_RouteRawList`';
        self::$db->exec($sql);

        $sql   = 'INSERT INTO `_RouteRawList`
            (route_path, route_name, route_session, route_autoWrapper, route_class, route_pre_args, route_post_args,
              route_pexCheck, route_pexCheckAny, route_pexCheckExact, route_meta)
            VALUES
            (:path, :name, :session, :autoWrapper, :class, :pre, :post, :pexCheck, :pexCheckAny, :pexCheckExact, :meta)
            ON DUPLICATE KEY UPDATE
              route_name = VALUES(route_name),
              route_session = VALUES(route_session),
              route_autoWrapper = VALUES(route_autoWrapper),
              route_class = VALUES(route_class),
              route_pre_args = VALUES(route_pre_args),
              route_post_args = VALUES(route_post_args),
              route_pexCheck = VALUES(route_pexCheck),
              route_pexCheckAny = VALUES(route_pexCheckAny),
              route_pexCheckExact = VALUES(route_pexCheckExact),
              route_meta = VALUES(route_meta)
        ';
        $query = self::$db->prepare($sql);

        $routes = $processor->getReverseRoutes();

        foreach ($routes as $name => $route) {
            $check      = '';
            $checkAny   = '';
            $checkExact = '';
            $meta       = '';

            if (isset($route['pexCheck'])) {
                if (!is_array($route['pexCheck'])) {
                    $route['pexCheck'] = array($route['pexCheck']);
                }
                $check = json_encode($route['pexCheck']);
            }
            if (isset($route['pexCheckAny'])) {
                if (!is_array($route['pexCheckAny'])) {
                    $route['pexCheckAny'] = array($route['pexCheckAny']);
                }
                $checkAny = json_encode($route['pexCheckAny']);
            }
            if (isset($route['pexCheckExact'])) {
                if (!is_array($route['pexCheckExact'])) {
                    $route['pexCheckExact'] = array($route['pexCheckExact']);
                }
                $checkExact = json_encode($route['pexCheckExact']);
            }
            if (isset($route['meta']) && $route['meta'] != null) {
                if (!is_array($route['meta'])) {
                    $route['meta'] = array($route['meta']);
                }
                $meta = json_encode($route['meta']);
            }

            $query->execute([
                ':path'          => $route['pattern'],
                ':name'          => $name,
                ':session'       => (isset($route['session']) ? intval($route['session']) : 0),
                ':autoWrapper'   => (isset($route['autoWrapper']) ? intval($route['autoWrapper']) : 0),
                ':class'         => $route['class'],
                ':pre'           => (isset($route['preArgs']) ? json_encode($route['preArgs']) : ''),
                ':post'          => (isset($route['postArgs']) ? json_encode($route['postArgs']) : ''),
                ':pexCheck'      => $check,
                ':pexCheckAny'   => $checkAny,
                ':pexCheckExact' => $checkExact,
                ':meta'          => $meta,
            ]);
            $query->closeCursor();
        }
        unset($routes, $route, $table, $fileName, $className);
    }
}
