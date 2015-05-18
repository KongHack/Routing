<?php
namespace GCWorld\Routing;

class LoadRoutes
{
	private static $instance       = null;
	private static $classes        = array();
	private static $highestTime    = 0;
	private static $lastClassTime  = PHP_INT_MAX;

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

	public function addRoute($fullClass, $skipCheck = false)
	{
		if(!$skipCheck)
		{
			if(!class_exists($fullClass))
			{
				throw new \Exception('Class Not Found: '.$fullClass);
			}
		}
		self::$classes[] = $fullClass;
		return $this;
	}

	public function generateRoutes($force = false, $debug = false)
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

		$base = dirname(__FILE__).'/Generated/*';
		$files = self::glob_recursive($base);
		foreach($files as $file)
		{
			if(is_file($file))
			{
				$time = filemtime($file);
				if($time < self::$lastClassTime)
				{
					self::$lastClassTime = $time;
				}
			}
		}

		if(self::$highestTime > self::$lastClassTime || count($files) != count(self::$classes) || $force)
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

			$processor = new Processor($debug);
			$processor->run($routes);
		}
	}

	private static function glob_recursive($pattern, $flags = 0)
	{
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
		{
			$files = array_merge($files, self::glob_recursive($dir.'/'.basename($pattern), $flags));
		}
		return $files;
	}
}