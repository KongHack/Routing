<?php
namespace GCWorld\Routing;

class Processor
{
	private $routes_straight = array();
	private $routes_reverse = array();
	private $routes_master  = array();
	private $storage        = array();

	public function __construct()
	{
		$this->storage = dirname(__FILE__).'/Generated/';
		if(!is_dir($this->storage))
		{
			mkdir($this->storage, 0755, true);
		}
		if(!is_dir($this->storage.'MasterRoute/'))
		{
			mkdir($this->storage.'MasterRoute/', 0755, true);
		}
	}

	public function addMasterRoute($key)
	{
		if(!in_array($key, $this->routes_master))
		{
			$this->routes_master[] = $key;
		}
	}

	public function run($routes)
	{
		foreach($routes as $k => $v)
		{
			if(strlen($k) < 1)
			{
				throw new \Exception('Null Route');
			}
			if(array_key_exists($k, $this->routes_straight))
			{
				throw new \Exception('Route Already Exists: '.$k);
			}
			if(array_key_exists($v['name'], $this->routes_reverse))
			{
				throw new \Exception('Named Route Already Exists: '.$v['class']);
			}
			
			$this->routes_straight[$k]          = $v['class'];
			$this->routes_reverse[$v['name']]   = $k;
		}
		//Cycle base routes, look for "groups" with 5 or more to create master groups.

		$hits = array();
		//Need to build up some bases.
		$bases = array();
		foreach($this->routes_reverse as $path => $junk)
		{
			$bases[] = $path;
		}
		foreach($this->routes_straight as $path => $junk)
		{
			if(!in_array($path, $bases))
			{
				$bases[] = $path;
			}
		}

		foreach($bases as $path)
		{
			$temp = explode('/',$path);
			if(isset($temp[1]))
			{
				if(!array_key_exists($temp[1], $hits))
				{
					$hits[$temp[1]] = 0;
				}
				++$hits[$temp[1]];
			}
		}

		foreach($hits as $key => $count)
		{
			if($count >= 3)
			{
				$this->addMasterRoute($key);
			}
		}

		//Generate some files.
		foreach($this->routes_master as $master)
		{
			$this->generateMaster($master);
		}
		$this->generateMisc();
	}

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
		foreach($this->routes_straight as $k => $v)
		{
			$temp = explode('/',$k);
			if($temp[1] != $master)
			{
				continue;
			}
			$php .= "\t\t\t'$k' => '$v',\n";
		}
		$php .= "\t\t);\n";
		$php .= "\t}\n\n";


		//Get Reverse Routes Function
		$php .= "\tpublic function getReverseRoutes()\n";
		$php .= "\t{\n";
		$php .= "\t\t return array(\n";
		foreach($this->routes_reverse as $k => $v)
		{
			$temp = explode('_',$k);
			if($temp[0] != $master)
			{
				continue;
			}
			$php .= "\t\t\t'$k' => '$v',\n";
		}
		$php .= "\t\t);\n";
		$php .= "\t}\n\n";

		//End of file
		$php .= "}\n";

		file_put_contents($this->storage.'MasterRoute/'.self::cleanClassName($master).'.php', $php);
	}

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
		foreach($this->routes_straight as $k => $v)
		{
			$temp = explode('/',$k);
			if(in_array($temp[1], $this->routes_master))
			{
				continue;
			}
			$php .= "\t\t\t'$k' => '$v',\n";
		}
		$php .= "\t\t);\n";
		$php .= "\t}\n\n";


		//Get Reverse Routes Function
		$php .= "\tpublic function getReverseRoutes()\n";
		$php .= "\t{\n";
		$php .= "\t\treturn array(\n";
		foreach($this->routes_reverse as $k => $v)
		{
			$temp = explode('_',$k);
			if(in_array($temp[0], $this->routes_master))
			{
				continue;
			}
			$php .= "\t\t\t'$k' => '$v',\n";
		}
		$php .= "\t\t);\n";
		$php .= "\t}\n\n";

		//End of file
		$php .= "}\n";

		file_put_contents($this->storage.'MasterRoute/MISC.php', $php);
	}

	public static function cleanClassName($master)
	{
		return str_replace('-','',strtoupper($master));
	}
}
