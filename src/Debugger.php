<?php
namespace GCWorld\Routing;

use GCWorld\Interfaces\Database;

/**
 * Class Debugger
 * @package GCWorld\Routing
 */
class Debugger
{
    const table = '_RouteDebugData';

    /**
     * @var \GCWorld\Interfaces\Database|\GCWorld\Database\Database
     */
    private $db      = null;
    private $storage = null;

    /**
     * @param Database $db
     * @throws \Exception
     */
    public function __construct($db)
    {
        if ($db instanceof Database) {
            $this->db = $db;
        } else {
            throw new \Exception('Must implement the GCWorld Database interface');
        }

        $processor = new Processor(false);
        $this->storage = $processor->getStorageLocation();

        // Make sure our table exists.
        if (!$db->tableExists(self::table)) {
            $sql = file_get_contents($this->getOurRoot().'datamodel/'.self::table.'.sql');
            $this->db->exec($sql);
            $this->db->setTableComment(self::table, $this->getVersion());
        } else {
            $dbv = $this->db->getTableComment(self::table);
            if ($dbv != $this->getVersion()) {
                $sql = 'DROP TABLE '.self::table;
                $this->db->exec($sql);
                $sql = file_get_contents($this->getOurRoot().'datamodel/'.self::table.'.sql');
                $this->db->exec($sql);
                $this->db->setTableComment(self::table, $this->getVersion());
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
        return file_get_contents($this->getOurRoot().'VERSION');
    }

    /**
     * Feeds the entire routing table into the database.
     */
    public function storeStructure()
    {
        $sql = 'INSERT INTO '.self::table.'
            (route_path, route_name, route_session, route_class, route_pre_args, route_post_args,
              route_pexCheck, route_pexCheckAny, route_pexCheckExact)
            VALUES
            (:path, :name, :session, :class, :pre, :post, :pexCheck, :pexCheckAny, :pexCheckExact)
            ON DUPLICATE KEY UPDATE
              route_name = VALUES(route_name),
              route_session = VALUES(route_session),
              route_class = VALUES(route_class),
              route_pre_args = VALUES(route_pre_args),
              route_post_args = VALUES(route_post_args),
              route_pexCheck = VALUES(route_pexCheck),
              route_pexCheckAny = VALUES(route_pexCheckAny),
              route_pexCheckExact = VALUES(route_pexCheckExact)
        ';
        $query = $this->db->prepare($sql);


        // Do not truncate.  Only on duplicate key update.
        $files = glob($this->storage.'*.php');
        foreach ($files as $file) {
            $tmp = explode(DIRECTORY_SEPARATOR, $file);
            $fileName = array_pop($tmp);
            $tmp = explode('.', $fileName);
            $className = array_shift($tmp);
            unset($tmp);
            $fqcn = '\\GCWorld\\Routing\\Generated\\'.$className;

            /** @var RoutesInterface $table */
            $table  = new $fqcn();
            $routes = $table->getForwardRoutes();

            foreach ($routes as $path => $route) {
                $check = '';
                $checkAny = '';
                $checkExact = '';
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

                $query->execute(array(
                    ':path'          => $path,
                    ':name'          => $route['name'],
                    ':session'       => (isset($route['session'])?intval($route['session']):0),
                    ':class'         => $route['class'],
                    ':pre'           => (isset($route['pre_args'])?json_encode($route['pre_args']):''),
                    ':post'          => (isset($route['pre_args'])?json_encode($route['post_args']):''),
                    ':pexCheck'      => $check,
                    ':pexCheckAny'   => $checkAny,
                    ':pexCheckExact' => $checkExact,
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
        $sql = 'UPDATE _RouteDebugData SET route_hits = route_hits + 1 WHERE route_path = :path';
        $query = $this->db->prepare($sql);
        $query->execute(array(':path'=>$path));
        $query->closeCursor();
    }
}
