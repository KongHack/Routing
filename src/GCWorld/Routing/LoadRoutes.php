<?php
namespace GCWorld\Routing;

class LoadRoutes
{
	private $classes        = array();
	private $highestTime    = 0;
	private $lastClassTime  = 0;


	public function addRoute($fullClass)
	{
		if(!class_exists($fullClass))
		{
			throw new \Exception('Class Not Found: '.$fullClass);
		}

		$this->classes[] = $fullClass;
	}

	public function generateRoutes()
	{
		foreach($this->classes as $fullClass)
		{
			$cTemp = new $fullClass;
			if($cTemp instanceof \GCWorld\Routing\RawRoutesInterface)
			{
				$time = $cTemp->getFileTime();
				if($time > $this->$highestTime)
				{
					$this->$highestTime = $time;
				}
			}
		}
		if($this->$highestTime > $$this->lastClassTime)
		{
			$routes = array();
			foreach($this->classes as $fullClass)
			{
				$cTemp = new $fullClass;
				$routes = array_merge($routes,$cTemp->getRoutes());
			}

			$processor = new Processor();
			$processor->run($routes);
		}
	}

}
