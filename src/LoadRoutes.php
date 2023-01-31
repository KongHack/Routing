<?php
namespace GCWorld\Routing;

use Exception;
use GCWorld\Database\Database;
use GCWorld\Routing\Interfaces\ConstantsInterface;
use GCWorld\Routing\Interfaces\RawRoutesInterface;
use GCWorld\Utilities\Traits\General;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;

/**
 * Class LoadRoutes
 */
class LoadRoutes
{
    use General;

    protected static array $instances     = [];

    protected string $instanceName;

    protected ?Database $db            = null;
    protected ?\Redis   $redis         = null;
    protected array     $classes       = [];
    protected array     $paths         = [];
    protected int       $highestTime   = 0;
    protected int       $lastClassTime = PHP_INT_MAX;
    protected bool      $doLint        = true;
    /**
     * @var string
     * @todo Implement
     */
    protected string    $dbTableName   = '_RouteRawList';

    /**
     * Singleton Format
     */
    protected function __clone()
    {
    }

    /**
     * @param string $name
     */
    protected function __construct(string $name)
    {
        $this->instanceName = $name;
    }

    /**
     * @param string $name
     * @return static
     */
    public static function getInstance(string $name = ConstantsInterface::DEFAULT_NAME)
    {
        if(!isset(self::$instances[$name])) {
            self::$instances[$name] = new static($name);
        }

        return self::$instances[$name];
    }

    /**
     * @param string $fullClass
     * @param bool   $skipCheck
     * @return $this
     * @throws Exception
     */
    public function addRoute(string $fullClass, bool $skipCheck = false)
    {
        if (!$skipCheck) {
            if (!class_exists($fullClass)) {
                throw new \Exception('Class Not Found: '.$fullClass);
            }
        }
        $this->classes[] = $fullClass;

        return $this;
    }

    /**
     * @param bool $lint
     */
    public function setLint(bool $lint)
    {
        $this->doLint = $lint;
    }

    /**
     * Add a file system path for automatic parsing.
     *
     * @param string $path
     * @return $this
     */
    public function addPath(string $path)
    {
        $this->paths[] = $path;

        return $this;
    }

    /**
     * @param bool $force
     * @param bool $debug
     * @throws Exception
     */
    public function generateRoutes(bool $force = false, bool $debug = false)
    {
        foreach ($this->classes as $fullClass) {
            $cTemp = new $fullClass;
            if ($cTemp instanceof RawRoutesInterface) {
                $time = $cTemp->getFileTime();
                if ($time > $this->highestTime) {
                    $this->highestTime = $time;
                }
            }
        }

        $base  = dirname(__FILE__).'/Generated/*';
        $files = self::glob_recursive($base);
        foreach ($files as $file) {
            if (is_file($file)) {
                $time = filemtime($file);
                if ($time < $this->lastClassTime) {
                    $this->lastClassTime = $time;
                }
            }
        }

        if ($force
            || count($this->paths) || $this->highestTime > $this->lastClassTime
            || count($files) != count($this->classes)
        ) {
            $routes = [];
            foreach ($this->classes as $fullClass) {
                $cTemp = new $fullClass;
                if ($cTemp instanceof RawRoutesInterface) {
                    $routes = array_merge($routes, $cTemp->getRoutes());
                }
            }

            $routes = array_merge($routes, $this->generateAnnotatedRoutes($debug));

            if($debug) {
                echo 'Starting Processor',PHP_EOL;
            }

            $cProcessor = new Processor();
            $cProcessor->setDebug($debug);
            $cProcessor->run($routes);


            if($debug) {
                echo 'Processor Complete',PHP_EOL;
            }

            if ($this->redis !== null) {

                if($debug) {
                    echo 'Redis Found, Deleting GCWORLD_ROUTER key',PHP_EOL;
                }

                $this->redis->del('GCWORLD_ROUTER');
            }

            if ($this->db !== null) {

                if($debug) {
                    echo 'DB Found, storing routes',PHP_EOL;
                }

                $this->storeRoutes($cProcessor);
            }
        }
    }

    /**
     * @param bool $debug
     * @return array
     */
    protected function generateAnnotatedRoutes(bool $debug = false)
    {
        $cPhpDocFactory  = DocBlockFactory::createInstance();

        $return = [];
        if (count($this->paths) > 0) {
            foreach ($this->paths as $path) {
                $classFiles = self::glob_recursive(rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*.php');
                foreach ($classFiles as $file) {
                    if($debug) {
                        echo ' - Processing: ',$file,PHP_EOL;
                    }

                    if($this->doLint) {
                        exec("php -l {$file}", $execOutput, $execError);
                        if ($execError !== 0) {
                            if($debug) {
                                echo 'ERROR IN FILE DETECTED, SKIPPING', PHP_EOL;
                                echo '  - file: ', $file, PHP_EOL;
                                echo '  - error: ', implode(PHP_EOL, $execOutput), PHP_EOL;
                            }
                            continue;
                        }
                    }

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
                        $thisClass = new ReflectionClass($classString);
                        if (($comment = $thisClass->getDocComment()) !== false) {
                            $phpDoc = $cPhpDocFactory->create($comment);
                            $routes = $this->processTags($classString, $phpDoc);
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
     * @param string $file
     * @return array
     */
    public function lintFile(string $file)
    {
        $response = [
            'success'  => true,
            'message'  => '',
            'routes'   => false,
        ];

        exec("php -l {$file}", $execOutput, $execError);
        if ($execError !== 0) {
            $response['success'] = false;
            $response['message'] = 'Failed to pass PHP Lint';
            return $response;
        }

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
        if (!class_exists($classString)) {
            $response['success'] = false;
            $response['message'] = 'Failed to load class';
            return $response;
        }
        try {
            $thisClass = new \ReflectionClass($classString);
        } catch (\Exception $e) {
            $response['success'] = false;
            $response['message'] = 'Failed to get reflection.'.PHP_EOL.$e->getMessage();
            return $response;
        }

        if (($comment = $thisClass->getDocComment()) !== false) {
            $cPhpDocFactory     = DocBlockFactory::createInstance();
            $phpDoc             = $cPhpDocFactory->create($comment);
            $response['routes'] = $this->processTags($classString, $phpDoc);
            return $response;
        }
        $response['success'] = false;
        $response['message'] = 'Could not get doc comment';
        return $response;
    }


    /**
     * @param string   $classString
     * @param DocBlock $phpDoc
     * @return array|bool
     */
    protected function processTags($classString, DocBlock $phpDoc)
    {
        if ($phpDoc->hasTag('router-1-pattern') && $phpDoc->hasTag('router-1-name')) {
            return $this->processComplexTags($classString, $phpDoc);
        }

        if (!$phpDoc->hasTag('router-pattern') || !$phpDoc->hasTag('router-name')) {
            return false;
        }


        $routes  = [];
        $pattern = $phpDoc->getTagsByName('router-pattern');
        foreach ($pattern as $patMaster) {
            $pat = (string) $patMaster;

            $routes[$pat] = [
                'class'       => $classString,
                'name'        => (string) $phpDoc->getTagsByName('router-name')[0],
                'autoWrapper' => false,
            ];

            $session = $phpDoc->getTagsByName('router-session');
            if (count($session) > 0) {
                $sessionString           = strtolower((string) $session[0]);
                $routes[$pat]['session'] = in_array($sessionString, ['true', 't', 'y', 'yes']);
            }

            // Remaining items that can be both a string or an array.
            $processingArray = [
                'pexCheck'      => $phpDoc->getTagsByName('router-pexCheck'),
                'pexCheckAny'   => $phpDoc->getTagsByName('router-pexCheckAny'),
                'pexCheckExact' => $phpDoc->getTagsByName('router-pexCheckExact'),
                'pexCheckMax'   => $phpDoc->getTagsByName('router-pexCheckMax'),
                'preArgs'       => $phpDoc->getTagsByName('router-preArgs'),
                'postArgs'      => $phpDoc->getTagsByName('router-postArgs'),
                'title'         => $phpDoc->getTagsByName('router-title'),
                'meta'          => $phpDoc->getTagsByName('router-meta'),
                'autoWrapper'   => $phpDoc->getTagsByName('router-autoWrapper'),
            ];

            foreach ($processingArray as $key => $var) {
                /** @var DocBlock\Tag[] $var */

                if (count($var) == 1) {
                    $routes[$pat][$key] = trim((string) $var[0]);
                } elseif (count($var) > 1) {
                    $temp = [];
                    foreach ($var as $t) {
                        $temp[] = trim((string) $t);
                    }
                    $routes[$pat][$key] = $temp;
                }
            }

            if (isset($routes[$pat]['meta'])) {
                if (!is_array($routes[$pat]['meta'])) {
                    $routes[$pat]['meta'] = [$routes[$pat]['meta']];
                }

                $meta = [];
                foreach ($routes[$pat]['meta'] as $v) {
                    $tmp = explode(':', $v);
                    if (count($tmp)==2) {
                        $meta[$tmp[0]] = $tmp[1];
                    }
                }
                $routes[$pat]['meta'] = $meta;
            } else {
                $routes[$pat]['meta'] = [];
            }

            if (isset($routes[$pat]['preArgs']) && !is_array($routes[$pat]['preArgs'])) {
                $routes[$pat]['preArgs'] = [$routes[$pat]['preArgs']];
            }
            if (isset($routes[$pat]['postArgs']) && !is_array($routes[$pat]['postArgs'])) {
                $routes[$pat]['postArgs'] = [$routes[$pat]['postArgs']];
            }

            if (strlen($routes[$pat]['autoWrapper']) > 0) {
                $routes[$pat]['autoWrapper'] = in_array($routes[$pat]['autoWrapper'], ['true', 't', 'y', 'yes']);
            }
        }

        return $routes;
    }

    /**
     * @param string   $classString
     * @param DocBlock $phpDoc
     * @return array|bool
     */
    protected function processComplexTags(string $classString, DocBlock $phpDoc)
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
                $pat = (string) $patMaster;

                $routes[$pat] = [
                    'class'       => $classString,
                    'name'        => (string) $phpDoc->getTagsByName('router-'.$i.'-name')[0],
                    'autoWrapper' => false,
                ];

                $session = $phpDoc->getTagsByName('router-'.$i.'-session');
                if (count($session) > 0) {
                    $sessionString           = strtolower((string) $session[0]);
                    $routes[$pat]['session'] = in_array($sessionString, ['true', 't', 'y', 'yes']);
                }

                // Remaining items that can be both a string or an array.
                $processingArray = [
                    'pexCheck'      => $phpDoc->getTagsByName('router-'.$i.'-pexCheck'),
                    'pexCheckAny'   => $phpDoc->getTagsByName('router-'.$i.'-pexCheckAny'),
                    'pexCheckExact' => $phpDoc->getTagsByName('router-'.$i.'-pexCheckExact'),
                    'pexCheckMax'   => $phpDoc->getTagsByName('router-'.$i.'-pexCheckMax'),
                    'preArgs'       => $phpDoc->getTagsByName('router-'.$i.'-preArgs'),
                    'postArgs'      => $phpDoc->getTagsByName('router-'.$i.'-postArgs'),
                    'title'         => $phpDoc->getTagsByName('router-'.$i.'-title'),
                    'meta'          => $phpDoc->getTagsByName('router-'.$i.'-meta'),
                    'autoWrapper'   => $phpDoc->getTagsByName('router-'.$i.'-autoWrapper'),
                ];

                foreach ($processingArray as $key => $var) {
                    /** @var DocBlock\Tag[] $var */

                    if (count($var) == 1) {
                        $routes[$pat][$key] = trim((string) $var[0]);
                    } elseif (count($var) > 1) {
                        $temp = [];
                        foreach ($var as $t) {
                            $temp[] = trim((string) $t);
                        }
                        $routes[$pat][$key] = $temp;
                    }
                }

                if (isset($routes[$pat]['meta'])) {
                    if (!is_array($routes[$pat]['meta'])) {
                        $routes[$pat]['meta'] = [$routes[$pat]['meta']];
                    }
                    
                    $meta = [];
                    foreach ($routes[$pat]['meta'] as $v) {
                        $tmp = explode(':', $v);
                        if (count($tmp)==2) {
                            $meta[$tmp[0]] = $tmp[1];
                        }
                    }
                    $routes[$pat]['meta'] = $meta;
                } else {
                    $routes[$pat]['meta'] = [];
                }
                
                if (isset($routes[$pat]['preArgs']) && !is_array($routes[$pat]['preArgs'])) {
                    $routes[$pat]['preArgs'] = [$routes[$pat]['preArgs']];
                }
                if (isset($routes[$pat]['postArgs']) && !is_array($routes[$pat]['postArgs'])) {
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
    public function attachRedisCache(\Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param Database $db
     */
    public function attachDatabase(Database $db)
    {
        $this->db = $db;
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

    /**
     * @param Processor $cProcessor
     * @return void
     * @throws Exception
     */
    protected function storeRoutes(Processor $cProcessor)
    {
        // TODO: Implement $this->dbTableName

        $table = '_RouteRawList';
        // Make sure our table exists.
        if (!$this->db->tableExists($table)) {
            $sql = file_get_contents($this->getOurRoot().'datamodel/'.$table.'.sql');
            $this->db->exec($sql);
            $this->db->setTableComment($table, $this->getVersion());
        } else {
            $dbv = $this->db->getTableComment($table);
            if ($dbv != $this->getVersion()) {
                $sql = 'DROP TABLE '.$table;
                $this->db->exec($sql);
                $sql = file_get_contents($this->getOurRoot().'datamodel/'.$table.'.sql');
                $this->db->exec($sql);
                $this->db->setTableComment($table, $this->getVersion());
            }
        }

        $sql = 'TRUNCATE TABLE `_RouteRawList`';
        $this->db->exec($sql);

        $sql   = 'INSERT INTO `_RouteRawList`
            (route_path, route_name, route_title, route_session, route_autoWrapper, route_class, route_pre_args, route_post_args,
              route_pexCheck, route_pexCheckAny, route_pexCheckExact, route_meta)
            VALUES
            (:path, :name, :title, :session, :autoWrapper, :class, :pre, :post, :pexCheck, :pexCheckAny, :pexCheckExact, :meta)
            ON DUPLICATE KEY UPDATE
              route_name = VALUES(route_name),
              route_title = VALUES(route_title),
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
        $query = $this->db->prepare($sql);

        $routes = $cProcessor->getReverseRoutes();

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
                ':title'         => (isset($route['title']) ? json_encode($route['title']) : ''),
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
