<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

require dirname(__FILE__) . '/Modules.php';

class MX_Router extends CI_Router
{
    public $module = '';
    private $located = 0;
    public $directory = '';
    protected $uri;

    public function __construct()
    {
        parent::__construct();
        $this->uri = new CI_URI();
    }

    public function fetch_module()
    {
        return $this->module;
    }

    protected function _set_request($segments = array())
    {
        // Translate dashes in URI segments
        if ($this->translate_uri_dashes === true) {
            foreach (range(0, 2) as $v) {
                if (isset($segments[$v])) {
                    $segments[$v] = str_replace('-', '_', $segments[$v]);
                }
            }
        }

        // Locate the controller
        $segments = $this->locate($segments);

        // Handle 404 or default controller
        if ($this->located == -1) {
            $this->_set_404override_controller();
            return;
        }

        if (empty($segments)) {
            $this->_set_default_controller();
            return;
        }

        // Set controller and method
        $this->set_class($segments[0]);
        $this->set_method(isset($segments[1]) ? $segments[1] : 'index');

        // Set remaining URI segments
        array_unshift($segments, null);
        unset($segments[0]);
        $this->uri->rsegments = $segments;
    }

    public function locate($segments)
    {
        $this->located = 0;
        $ext = $this->config->item('controller_suffix') . EXT;

        // Parse module routes
        if (isset($segments[0]) && $routes = Modules::parse_routes($segments[0], implode('/', $segments))) {
            $segments = $routes;
        }

        list($module, $directory, $controller) = array_pad($segments, 3, null);

        // Search in module locations
        foreach (Modules::$locations as $location => $offset) {
            if (is_dir($source = $location . $module . '/controllers/')) {
                $this->module = $module;
                $this->directory = $offset . $module . '/controllers/';

                if ($directory) {
                    if (is_dir($source . $directory . '/')) {
                        $source .= $directory . '/';
                        $this->directory .= $directory . '/';

                        if ($controller && is_file($source . ucfirst($controller) . $ext)) {
                            $this->located = 3;
                            return array_slice($segments, 2);
                        }
                    } elseif (is_file($source . ucfirst($directory) . $ext)) {
                        $this->located = 2;
                        return array_slice($segments, 1);
                    }
                }

                if (is_file($source . ucfirst($module) . $ext)) {
                    $this->located = 1;
                    return $segments;
                }
            }
        }

        // Search in the application controllers directory
        if (is_dir(APPPATH . 'controllers/' . $module . '/')) {
            $this->directory = $module . '/';
            return array_slice($segments, 1);
        }

        if (is_file(APPPATH . 'controllers/' . ucfirst($module) . $ext)) {
            return $segments;
        }

        $this->located = -1;
    }

    protected function _set_default_controller()
    {
        if (empty($this->default_controller)) {
            show_error('A default route has not been specified in the routing configuration.');
        }

        $segments = explode('/', $this->default_controller);

        $this->set_class($segments[0]);
        $this->set_method(isset($segments[1]) ? $segments[1] : 'index');

        $this->uri->rsegments = array(
            1 => $this->class,
            2 => $this->method
        );
    }

    protected function _set_404override_controller()
    {
        if (!empty($this->routes['404_override'])) {
            $segments = explode('/', $this->routes['404_override']);

            $this->set_class($segments[0]);
            $this->set_method(isset($segments[1]) ? $segments[1] : 'index');

            $this->uri->rsegments = array(
                1 => $this->class,
                2 => $this->method
            );
        } else {
            show_404();
        }
    }
}
