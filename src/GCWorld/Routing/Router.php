<?php
namespace GCWorld\Routing;

class Router
{
    public static function jsonServe($path)
    {
        if(!file_exists($path))
        {
            die('Routing Path Not Found');
        }

        $routes = json_decode($path);
        self::serve($routes);
    }

    public static function serve($routes)
    {
        Hook::fire('before_request', compact('routes'));

        $request_method = strtolower($_SERVER['REQUEST_METHOD']);
        $path_info = '/';
        if (!empty($_SERVER['PATH_INFO']))
        {
            $path_info = $_SERVER['PATH_INFO'];
        }
        else if (!empty($_SERVER['ORIG_PATH_INFO']) && $_SERVER['ORIG_PATH_INFO'] !== '/index.php')
        {
            $path_info = $_SERVER['ORIG_PATH_INFO'];
        }
        else
        {
            if (!empty($_SERVER['REQUEST_URI']))
            {
                $path_info = (strpos($_SERVER['REQUEST_URI'], '?') > 0) ? strstr($_SERVER['REQUEST_URI'], '?', true) : $_SERVER['REQUEST_URI'];
            }
        }
        
        $discovered_handler = null;
        $regex_matches = array();

        if(isset($routes[$path_info]))
        {
            $discovered_handler = $routes[$path_info];
        }
        elseif($routes)
        {
            $tokens = array(
                ':string'     => '([a-zA-Z]+)',
                ':number'     => '([0-9]+)',
                ':alpha'      => '([a-zA-Z0-9-_]+)',
                ':anything'   => '([^/]+)',
            );
            foreach ($routes as $pattern => $handler_name)
            {
                $pattern = strtr($pattern, $tokens);
                if (preg_match('#^/?' . $pattern . '/?$#', $path_info, $matches))
                {
                    $discovered_handler = $handler_name;
                    $regex_matches = $matches;
                    break;
                }
            }
        }

        $result = null;

        $handler_instance = null;
        if ($discovered_handler)
        {
            if (is_string($discovered_handler))
            {
                if(class_exists($discovered_handler))
                {
                    $handler_instance = new $discovered_handler();
                }
                else
                {
                    echo 'Class Not Found: '.$discovered_handler;
                    die();
                }
            }
            elseif(is_array($discovered_handler))
            {
                //Used for new reverse name search.
                if(isset($discovered_handler['handler']) && is_string($discovered_handler['handler']))
                {
                    $discovered_handler = $discovered_handler['handler'];
                    if(class_exists($discovered_handler))
                    {
                        $handler_instance = new $discovered_handler();
                    }
                    else
                    {
                        echo 'Class Not Found: '.$discovered_handler;
                        die();
                    }
                }
            }
            elseif (is_callable($discovered_handler))
            {
                $handler_instance = $discovered_handler();
            }
        }

        if ($handler_instance)
        {
            unset($regex_matches[0]);

            if (self::is_xhr_request() && method_exists($handler_instance, $request_method . '_xhr'))
            {
                header('Content-type: application/json');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Pragma: no-cache');
                $request_method .= '_xhr';
            }

            if (method_exists($handler_instance, $request_method))
            {
                Hook::fire('before_handler', compact('routes', 'discovered_handler', 'request_method', 'regex_matches'));
                $result = call_user_func_array(array($handler_instance, $request_method), $regex_matches);
                Hook::fire('after_handler', compact('routes', 'discovered_handler', 'request_method', 'regex_matches', 'result'));
            }
            else
            {
                Hook::fire('404', compact('routes', 'discovered_handler', 'request_method', 'regex_matches'));
            }
        }
        else
        {
            Hook::fire('404', compact('routes', 'discovered_handler', 'request_method', 'regex_matches'));
        }
        Hook::fire('after_request', compact('routes', 'discovered_handler', 'request_method', 'regex_matches', 'result'));
    }

    private static function is_xhr_request()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}
