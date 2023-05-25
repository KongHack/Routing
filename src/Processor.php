<?php
namespace GCWorld\Routing;

use GCWorld\Interfaces\RoutingInterface;
use Riimu\Kit\PHPEncoder\PHPEncoder;

/**
 * Class Processor
 */
class Processor
{
    protected string $name;
    protected array  $routes_straight = [];
    protected array  $routes_reverse  = [];
    protected array  $routes_master   = [];
    protected string $storage         = '';
    protected bool   $debug           = false;

    /**
     * @param bool $debug
     */
    public function __construct(string $name = RoutingInterface::DEFAULT_NAME)
    {
        $this->name    = $name;
        $this->storage = __DIR__.DIRECTORY_SEPARATOR.'Generated'.DIRECTORY_SEPARATOR;
        if (!is_dir($this->storage)) {
            mkdir($this->storage, 0755, true);
        }
        $this->storage .= $name.DIRECTORY_SEPARATOR;
        if (!is_dir($this->storage)) {
            mkdir($this->storage, 0755, true);
        }
    }

    /**
     * @param bool $debug
     * @return void
     */
    public function setDebug(bool $debug)
    {
        $this->debug = $debug;
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
            $v['name'] = $v['name'].'_'.substr_count($k, ':');

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

            $this->routes_straight[$k] = $v;
            $name                      = $v['name'];
            unset($v['name']);
            $this->routes_reverse[$name] = ['pattern' => $k] + $v;
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

        $hits = [];
        //Need to build up some bases.
        $bases = [];
        if($this->debug) {
            echo PHP_EOL,' - Iterating routes to generate bases',PHP_EOL;
        }
        foreach ($this->routes_reverse as $path => $junk) {
            $bases[] = $path;
        }
        foreach ($this->routes_straight as $path => $junk) {
            if (!in_array($path, $bases)) {
                $bases[] = $path;
            }
        }
        if($this->debug) {
            if(function_exists('d')) {
                d($bases);
            } else {
                echo 'Bases',PHP_EOL;
                print_r($bases);
            }
            echo PHP_EOL;
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
        if($this->debug) {
            if(function_exists('d')) {
                d($hits);
            } else {
                echo 'Bases',PHP_EOL;
                print_r($hits);
            }
            echo PHP_EOL;
        }

        foreach ($hits as $key => $count) {
            if ($count >= 3) {
                if($this->debug) {
                    echo ' - Adding Master Route: ',$key,PHP_EOL;
                }
                $this->addMasterRoute($key);
            }
        }

        //Generate some files.
        foreach ($this->routes_master as $master) {
            if($this->debug) {
                echo ' - Generating Master Route: ',$master,PHP_EOL;
            }
            $this->generateMaster($master);
        }
        if($this->debug) {
            echo ' - Generating MISC Route', PHP_EOL;
        }
        $this->generateMisc();
        if($this->debug) {
            echo ' - [!!] Done generating route files! ', PHP_EOL;
        }
    }

    /**
     * @param $master
     */
    protected function generateMaster($master)
    {
        //We need to generate both a forward and reverse bank, followed by proper wrappers.

        $php = "<?php\n";
        $php .= "namespace GCWorld\\Routing\\Generated\\{$this->name};\n";
        $php .= "\n";
        $php .= "class MasterRoute_".self::cleanClassName($master)." Implements \\GCWorld\\Routing\\Interfaces\\RoutesInterface\n";
        $php .= "{\n";

        //Get File Time Function
        $php .= "    public function getFileTime()\n";
        $php .= "    {\n";
        $php .= "        return filemtime(__FILE__);\n";
        $php .= "    }\n\n";

        //Get Forward Routes Function
        $php .= "    public function getForwardRoutes()\n";
        $php .= "    {\n";
        $php .= "         return [\n";
        foreach ($this->routes_straight as $k => $v) {
            $temp = explode('/', $k);
            if ($temp[1] != $master) {
                continue;
            }
            $cEncoder = new PHPEncoder();
            $encoded  = $cEncoder->encode($v, [
                'array.base'         => 12,
                'array.inline'       => false,
                'array.omit'         => false,
                'array.align'        => true,
                'array.indent'       => 4,
                'boolean.capitalize' => true,
                'null.capitalize'    => true,
            ]);
            $php .= "            '$k' => ".$encoded.",\n";
        }
        $php .= "        ];\n";
        $php .= "    }\n\n";


        //Get Reverse Routes Function
        $php .= "    public function getReverseRoutes()\n";
        $php .= "    {\n";
        $php .= "         return [\n";
        foreach ($this->routes_reverse as $k => $v) {
            $temp = explode('_', $k);
            if ($temp[0] != $master) {
                continue;
            }
            $cEncoder = new PHPEncoder();
            $encoded  = $cEncoder->encode($v, [
                'array.base'         => 12,
                'array.inline'       => false,
                'array.omit'         => false,
                'array.align'        => true,
                'array.indent'       => 4,
                'boolean.capitalize' => true,
                'null.capitalize'    => true,
            ]);
            $php .= "            '$k' => ".$encoded.",\n";
        }
        $php .= "        ];\n";
        $php .= "    }\n\n";

        //End of file
        $php .= "}\n";

        file_put_contents($this->storage.'MasterRoute_'.self::cleanClassName($master).'.php', $php);
    }

    /**
     * Generates the MISC route file
     */
    protected function generateMisc()
    {
        //We need to generate both a forward and reverse bank, followed by proper wrappers.

        $php = "<?php\n";
        $php .= "namespace GCWorld\\Routing\\Generated\\{$this->name};\n";
        $php .= "\n";
        $php .= "class MasterRoute_MISC Implements \\GCWorld\\Routing\\Interfaces\\RoutesInterface\n";
        $php .= "{\n";

        //Get File Time Function
        $php .= "    public function getFileTime()\n";
        $php .= "    {\n";
        $php .= "        return filemtime(__FILE__);\n";
        $php .= "    }\n\n";

        //Get Forward Routes Function
        $php .= "    public function getForwardRoutes()\n";
        $php .= "    {\n";
        $php .= "         return [\n";
        foreach ($this->routes_straight as $k => $v) {
            $temp = explode('/', $k);
            if (in_array($temp[1], $this->routes_master)) {
                continue;
            }
            $cEncoder = new PHPEncoder();
            $encoded  = $cEncoder->encode($v, [
                'array.base'         => 12,
                'array.inline'       => false,
                'array.omit'         => false,
                'array.align'        => true,
                'array.indent'       => 4,
                'boolean.capitalize' => true,
                'null.capitalize'    => true,
            ]);
            $php .= "            '$k' => ".$encoded.",\n";
        }
        $php .= "        ];\n";
        $php .= "    }\n\n";


        //Get Reverse Routes Function
        $php .= "    public function getReverseRoutes()\n";
        $php .= "    {\n";
        $php .= "        return [\n";
        foreach ($this->routes_reverse as $k => $v) {
            $temp = explode('_', $k);
            if (in_array($temp[0], $this->routes_master)) {
                continue;
            }
            $cEncoder = new PHPEncoder();
            $encoded  = $cEncoder->encode($v, [
                'array.base'         => 12,
                'array.inline'       => false,
                'array.omit'         => false,
                'array.align'        => true,
                'array.indent'       => 4,
                'boolean.capitalize' => true,
                'null.capitalize'    => true,
            ]);
            $php .= "            '$k' => ".$encoded.",\n";
        }
        $php .= "        ];\n";
        $php .= "    }\n\n";

        //End of file
        $php .= "}\n";

        file_put_contents($this->storage.'MasterRoute_MISC.php', $php);
    }

    /**
     * @return array
     */
    public function getReverseRoutes()
    {
        return $this->routes_reverse;
    }
    
    /**
     * @param $master
     * @return mixed
     */
    public static function cleanClassName($master)
    {
        // If we start with a replacement key
        if (strstr($master, ':')) {
            return 'REPLACEMENT_KEY';
        }

        return str_replace('-', '', strtoupper($master));
    }
}
