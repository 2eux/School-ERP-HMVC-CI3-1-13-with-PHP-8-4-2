<?php
defined('BASEPATH') OR exit('No direct script access allowed');

(defined('EXT')) OR define('EXT', '.php');

global $CFG;

/* Get module locations from config settings or use the default module location and offset */
Modules::$locations = $CFG->item('modules_locations') ?: array(
    APPPATH . 'modules/' => '../modules/',
);

/* PHP5+ spl_autoload */
spl_autoload_register('Modules::autoload');

class Modules
{
    public static $routes = [];
    public static $registry = [];
    public static $locations;

    /**
     * Run a module controller method
     * Output from module is buffered and returned.
     */
    public static function run($module)
    {
        $method = 'index';

        if (($pos = strrpos($module, '/')) !== false) {
            $method = substr($module, $pos + 1);
            $module = substr($module, 0, $pos);
        }

        if ($class = self::load($module)) {
            if (method_exists($class, $method)) {
                ob_start();
                $args = func_get_args();
                $output = call_user_func_array([$class, $method], array_slice($args, 1));
                $buffer = ob_get_clean();
                return $output !== null ? $output : $buffer;
            }
        }

        log_message('error', "Module controller failed to run: {$module}/{$method}");
        return null;
    }

    /** Load a module controller **/
    public static function load($module)
    {
        is_array($module) ? list($module, $params) = each($module) : $params = null;

        /* Get the requested controller class name */
        $alias = strtolower(basename($module));

        /* Create or return an existing controller from the registry */
        if (!isset(self::$registry[$alias])) {
            /* Find the controller */
            list($class) = CI::$APP->router->locate(explode('/', $module));

            /* Controller cannot be located */
            if (empty($class)) {
                return null;
            }

            /* Set the module directory */
            $path = APPPATH . 'controllers/' . CI::$APP->router->directory;

            /* Load the controller class */
            $class .= CI::$APP->config->item('controller_suffix');
            self::load_file(ucfirst($class), $path);

            /* Create and register the new controller */
            $controller = ucfirst($class);
            self::$registry[$alias] = new $controller($params);
        }

        return self::$registry[$alias];
    }

    /** Library base class autoload **/
    public static function autoload($class)
    {
        if (strpos($class, 'CI_') !== false || strpos($class, config_item('subclass_prefix')) !== false) {
            return;
        }

        if (strpos($class, 'MX_') !== false) {
            if (is_file($location = dirname(__FILE__) . '/' . substr($class, 3) . EXT)) {
                include_once $location;
                return;
            }
            show_error('Failed to load MX core class: ' . $class);
        }

        if (is_file($location = APPPATH . 'core/' . ucfirst($class) . EXT)) {
            include_once $location;
            return;
        }

        if (is_file($location = APPPATH . 'libraries/' . ucfirst($class) . EXT)) {
            include_once $location;
            return;
        }
    }

    /** Load a module file **/
    public static function load_file($file, $path, $type = 'other', $result = true)
    {
        $file = str_replace(EXT, '', $file);
        $location = $path . $file . EXT;

        if ($type === 'other') {
            if (class_exists($file, false)) {
                log_message('debug', "File already loaded: {$location}");
                return $result;
            }
            include_once $location;
        } else {
            include $location;

            if (!isset($$type) || !is_array($$type)) {
                show_error("{$location} does not contain a valid {$type} array");
            }

            $result = $$type;
        }
        log_message('debug', "File loaded: {$location}");
        return $result;
    }

    /** Parse module routes **/
    public static function parse_routes($module, $uri)
    {
        if (!isset(self::$routes[$module])) {
            if (list($path) = self::find('routes', $module, 'config/')) {
                $path && self::$routes[$module] = self::load_file('routes', $path, 'route');
            }
        }

        if (!isset(self::$routes[$module])) {
            return;
        }

        foreach (self::$routes[$module] as $key => $val) {
            $key = str_replace([':any', ':num'], ['.+', '[0-9]+'], $key);

            if (preg_match('#^' . $key . '$#', $uri)) {
                if (strpos($val, '$') !== false && strpos($key, '(') !== false) {
                    $val = preg_replace('#^' . $key . '$#', $val, $uri);
                }
                return explode('/', $module . '/' . $val);
            }
        }
    }

    /** Find a file **/
    public static function find($file, $module, $base)
    {
        $segments = explode('/', $file);
        $file = array_pop($segments);
        $file_ext = pathinfo($file, PATHINFO_EXTENSION) ? $file : $file . EXT;
        $path = ltrim(implode('/', $segments) . '/', '/');
        $module ? $modules[$module] = $path : $modules = [];

        if (!empty($segments)) {
            $modules[array_shift($segments)] = ltrim(implode('/', $segments) . '/', '/');
        }

        foreach (Modules::$locations as $location => $offset) {
            foreach ($modules as $module => $subpath) {
                $fullpath = $location . $module . '/' . $base . $subpath;

                if ($base === 'libraries/' || $base === 'models/') {
                    if (is_file($fullpath . ucfirst($file_ext))) {
                        return [$fullpath, ucfirst($file)];
                    }
                } elseif (is_file($fullpath . $file_ext)) {
                    return [$fullpath, $file];
                }
            }
        }

        return [false, $file];
    }
}
