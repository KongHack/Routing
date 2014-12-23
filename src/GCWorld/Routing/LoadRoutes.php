<?php
namespace GCWorld\Routing;

class LoadRoutes
{
	private static $instance       = null;
	private static $classes        = array();
	private static $highestTime    = 0;
	private static $lastClassTime  = 0;

	private function __clone(){}
	private function __construct(){}

	public static function getInstance()
	{
		if(self::$instance == null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function addRoute($fullClass)
	{
		if(!class_exists($fullClass))
		{
			throw new \Exception('Class Not Found: '.$fullClass);
		}
		self::$classes[] = $fullClass;
		return $this;
	}

	public function generateRoutes()
	{
		foreach(self::$classes as $fullClass)
		{
			$cTemp = new $fullClass;
			if($cTemp instanceof \GCWorld\Routing\RawRoutesInterface)
			{
				$time = $cTemp->getFileTime();
				if($time > self::$highestTime)
				{
					self::$highestTime = $time;
				}
			}
		}

		$base = dirname(__FILE__).'/Generated/';
		$files = scandir($base);
		foreach($files as $file)
		{
			d($file);
			if(is_file($file))
			{
				$time = filemtime($file);
				d($time);
				if($time > self::$lastClassTime)
				{
					self::$lastClassTime = $time;
				}
			}
		}
		d($base);
		d($files);
		dd($this);
		if(self::$highestTime > self::$lastClassTime)
		{
			$routes = array();
			foreach(self::$classes as $fullClass)
			{
				$cTemp = new $fullClass;
				if($cTemp instanceof \GCWorld\Routing\RawRoutesInterface)
				{
					$routes = array_merge($routes, $cTemp->getRoutes());
				}
			}

			$processor = new Processor();
			$processor->run($routes);
		}
	}
}
