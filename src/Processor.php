<?php
namespace GCWorld\Routing;

/**
 * Class Processor
 * @package GCWorld\Routing
 */
class Processor
{
    /**
     * @var array
     */
    private $routes_straight = array();
    /**
     * @var array
     */
    private $routes_reverse = array();
    /**
     * @var array
     */
    private $routes_master  = array();
    /**
     * @var string
     */
    private $storage        = null;
    /**
     * @var bool
     */
    private $debug          = false;

    /**
     * @param bool $debug
     */
    public function __construct($debug = false)
    {
        $this->debug = $debug;
        $this->storage = dirname(__FILE__).'/Generated/';
        if (!is_dir($this->storage)) {
            mkdir($this->storage, 0755, true);
        }
    }

    /**
     * @return string
     */
    public function getStorageLocation()
    {
        return $this->storage;
    }

    /**
     * @param $key
     */
    public function addMasterRoute($key)
    {
        if (!in_array($key, $this->routes_master)) {
            $this->routes_master[] = $key;
        }
    }

    /**
     * @param $routes
     * @throws \Exception
     */
    public function run($routes)
    {
        if ($this->debug) {
            if (function_exists('d')) {
                d($routes);
            } else {
                echo '<pre><b>$routes</b><br>';
                print_r($routes);
                echo '</pre>';
            }
        }

        foreach ($routes as $k => $v) {
            if (strlen($k) < 1) {
                throw new \Exception('Null Route');
            }
            if (array_key_exists($k, $this->routes_straight)) {
                throw new \Exception('Route Already Exists: '.$k);
            }

            // We need to re-write name based on the number of options.  This way we can use the same name for paging
            // $v['name'].=  '_' . substr_count($v['name'],':'); And this doesn't work why?
            $v['name'] =  $v['name'] . '_' . substr_count($k, ':');

            if (array_key_exists($v['name'], $this->routes_reverse)) {
                if ($this->debug) {
                    if (function_exists('d')) {
                        d($this);
                    } else {
                        echo '<pre>';
                        print_r($this->routes_reverse);
                        echo '</pre>';
                    }
                }
                throw new \Exception('Named Route Already Exists: '.$v['name'].' - '.$v['class']);
            }

            $this->routes_straight[$k]   = $v;
            $name = $v['name'];
            unset($v['name']);
            $this->routes_reverse[$name] = array('pattern'=>$k) + $v;
        }

        if ($this->debug) {
            if (function_exists('d')) {
                d($this->routes_straight);
                d($this->routes_reverse);
            } else {
                echo '<pre><b>straight</b><br>';
                print_r($this->routes_straight);
                echo '</pre>';
                echo '<pre><b>reverse</b><br>';
                print_r($this->routes_reverse);
                echo '</pre>';
            }
        }

        //Cycle base routes, look for "groups" with 5 or more to create master groups.

        $hits = array();
        //Need to build up some bases.
        $bases = array();
        foreach ($this->routes_reverse as $path => $junk) {
            $bases[] = $path;
        }
        foreach ($this->routes_straight as $path => $junk) {
            if (!in_array($path, $bases)) {
                $bases[] = $path;
            }
        }

        foreach ($bases as $path) {
            $temp = explode('/', $path);
            if (isset($temp[1])) {
                if (!array_key_exists($temp[1], $hits)) {
                    $hits[$temp[1]] = 0;
                }
                ++$hits[$temp[1]];
            }
        }

        foreach ($hits as $key => $count) {
            if ($count >= 3) {
                $this->addMasterRoute($key);
            }
        }

        //Generate some files.
        foreach ($this->routes_master as $master) {
            $this->generateMaster($master);
        }
        $this->generateMisc();
    }

    /**
     * @param $master
     */
    private function generateMaster($master)
    {
        //We need to generate both a forward and reverse bank, followed by proper wrappers.

        $php = "<?php\n";
        $php .= "namespace GCWorld\\Routing\\Generated;\n";
        $php .= "\n";
        $php .= "class MasterRoute_".self::cleanClassName($master)." Implements \\GCWorld\\Routing\\RoutesInterface\n";
        $php .= "{\n";

        //Get File Time Function
        $php .= "\tpublic function getFileTime()\n";
        $php .= "\t{\n";
        $php .= "\t\treturn filemtime(__FILE__);\n";
        $php .= "\t}\n\n";

        //Get Forward Routes Function
        $php .= "\tpublic function getForwardRoutes()\n";
        $php .= "\t{\n";
        $php .= "\t\t return array(\n";
        foreach ($this->routes_straight as $k => $v) {
            $temp = explode('/', $k);
            if ($temp[1] != $master) {
                continue;
            }
            $php .= "\t\t\t'$k' => ".var_export($v, true).",\n";
        }
        $php .= "\t\t);\n";
        $php .= "\t}\n\n";


        //Get Reverse Routes Function
        $php .= "\tpublic function getReverseRoutes()\n";
        $php .= "\t{\n";
        $php .= "\t\t return array(\n";
        foreach ($this->routes_reverse as $k => $v) {
            $temp = explode('_', $k);
            if ($temp[0] != $master) {
                continue;
            }
            $php .= "\t\t\t'$k' => ".var_export($v, true).",\n";
        }
        $php .= "\t\t);\n";
        $php .= "\t}\n\n";

        //End of file
        $php .= "}\n";

        file_put_contents($this->storage.'MasterRoute_'.self::cleanClassName($master).'.php', $php);
    }

    /**
     * Generates the MISC route file
     */
    private function generateMisc()
    {
        //We need to generate both a forward and reverse bank, followed by proper wrappers.

        $php = "<?php\n";
        $php .= "namespace GCWorld\\Routing\\Generated;\n";
        $php .= "\n";
        $php .= "class MasterRoute_MISC Implements \\GCWorld\\Routing\\RoutesInterface\n";
        $php .= "{\n";

        //Get File Time Function
        $php .= "\tpublic function getFileTime()\n";
        $php .= "\t{\n";
        $php .= "\t\treturn filemtime(__FILE__);\n";
        $php .= "\t}\n\n";

        //Get Forward Routes Function
        $php .= "\tpublic function getForwardRoutes()\n";
        $php .= "\t{\n";
        $php .= "\t\t return array(\n";
        foreach ($this->routes_straight as $k => $v) {
            $temp = explode('/', $k);
            if (in_array($temp[1], $this->routes_master)) {
                continue;
            }
            $php .= "\t\t\t'$k' => ".var_export($v, true).",\n";
        }
        $php .= "\t\t);\n";
        $php .= "\t}\n\n";


        //Get Reverse Routes Function
        $php .= "\tpublic function getReverseRoutes()\n";
        $php .= "\t{\n";
        $php .= "\t\treturn array(\n";
        foreach ($this->routes_reverse as $k => $v) {
            $temp = explode('_', $k);
            if (in_array($temp[0], $this->routes_master)) {
                continue;
            }
            $php .= "\t\t\t'$k' => ".var_export($v, true).",\n";
        }
        $php .= "\t\t);\n";
        $php .= "\t}\n\n";

        //End of file
        $php .= "}\n";

        file_put_contents($this->storage.'MasterRoute_MISC.php', $php);
    }

    /**
     * @param $master
     * @return mixed
     */
    public static function cleanClassName($master)
    {
        return str_replace(array('-',':'), array('','REPLACE'), strtoupper($master));
    }
}
