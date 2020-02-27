<?php
namespace GCWorld\Routing;

use GCWorld\Interfaces\Database;
use Exception;
use GCWorld\Routing\Interfaces\RoutesInterface;

/**
 * Class Debugger
 */
class Debugger
{
    const TABLE = '_RouteDebugData';

    /**
     * @var Database|\GCWorld\Database\Database
     */
    private $db      = null;
    private $storage = null;

    /**
     * @param Database $db
     * @throws Exception
     */
    public function __construct($db)
    {
        if ($db instanceof Database) {
            $this->db = $db;
        } else {
            throw new \Exception('Must implement the GCWorld Database interface');
        }

        $processor     = new Processor(false);
        $this->storage = $processor->getStorageLocation();

        // Make sure our table exists.
        if (!$db->tableExists(self::TABLE)) {
            $sql = file_get_contents($this->getOurRoot().'datamodel/'.self::TABLE.'.sql');
            $this->db->exec($sql);
            $this->db->setTableComment(self::TABLE, $this->getVersion());
        } else {
            $dbv = $this->db->getTableComment(self::TABLE);
            if ($dbv != $this->getVersion()) {
                $sql = 'DROP TABLE '.self::TABLE;
                $this->db->exec($sql);
                $sql = file_get_contents($this->getOurRoot().'datamodel/'.self::TABLE.'.sql');
                $this->db->exec($sql);
                $this->db->setTableComment(self::TABLE, $this->getVersion());
            }
        }
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
     * Feeds the entire routing table into the database.
     */
    public function storeStructure()
    {
        $sql   = 'INSERT INTO '.self::TABLE.'
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


        // Do not truncate.  Only on duplicate key update.
        $files = glob($this->storage.'*.php');
        foreach ($files as $file) {
            $tmp       = explode(DIRECTORY_SEPARATOR, $file);
            $fileName  = array_pop($tmp);
            $tmp       = explode('.', $fileName);
            $className = array_shift($tmp);
            $className = Processor::cleanClassName($className);
            unset($tmp);
            $fqcn = '\\GCWorld\\Routing\\Generated\\'.$className;

            /** @var RoutesInterface $table */
            $table  = new $fqcn();
            $routes = $table->getForwardRoutes();

            foreach ($routes as $path => $route) {
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

                $query->execute(array(
                    ':path'          => $path,
                    ':name'          => $route['name'],
                    ':title'         => $route['title'],
                    ':session'       => (isset($route['session']) ? intval($route['session']) : 0),
                    ':autoWrapper'   => (isset($route['autoWrapper']) ? intval($route['autoWrapper']) : 0),
                    ':class'         => $route['class'],
                    ':pre'           => (isset($route['preArgs']) ? json_encode($route['preArgs']) : ''),
                    ':post'          => (isset($route['postArgs']) ? json_encode($route['postArgs']) : ''),
                    ':pexCheck'      => $check,
                    ':pexCheckAny'   => $checkAny,
                    ':pexCheckExact' => $checkExact,
                    ':meta'          => $meta,
                ));
                $query->closeCursor();
            }
            unset($routes, $route, $table, $fileName, $className, $fqcn);
        }
    }

    /**
     * Logs a hit to the database
     * @param string $path
     */
    public function logHit($path)
    {
        $sql   = 'UPDATE _RouteDebugData SET route_hits = route_hits + 1 WHERE route_path = :path';
        $query = $this->db->prepare($sql);
        $query->execute([':path' => $path]);
        $query->closeCursor();
    }
}
