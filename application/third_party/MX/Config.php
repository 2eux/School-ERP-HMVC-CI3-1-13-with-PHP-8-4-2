<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Modular Extensions - HMVC
 *
 * Description:
 * This library replaces the CI_Config class and provides support for modules.
 *
 * Install this file as application/third_party/MX/Config.php
 *
 * @copyright   Copyright (c) 2015 Wiredesignz
 * @version     5.5
 */

class MX_Config extends CI_Config
{
    public function load($file = '', $use_sections = FALSE, $fail_gracefully = FALSE, $_module = NULL)
    {
        $file = ($file === '') ? 'config' : str_replace('.php', '', $file);
        
        if (in_array($file, $this->is_loaded, TRUE)) {
            return $this->item($file);
        }

        $_module = $_module ?: CI::$APP->router->fetch_module();
        list($path, $file) = Modules::find($file, $_module, 'config/');

        if ($path === FALSE) {
            return parent::load($file, $use_sections, $fail_gracefully);
        }

        if ($config = Modules::load_file($file, $path, 'config')) {
            if ($use_sections === TRUE) {
                $this->config[$file] = isset($this->config[$file]) ? array_merge($this->config[$file], $config) : $config;
            } else {
                $this->config = array_merge($this->config, $config);
            }
            $this->is_loaded[] = $file;
            unset($config);
            return $this->item($file);
        }
    }

    public function site_url($uri = '', $protocol = NULL)
    {
        if ($_module = CI::$APP->router->fetch_module()) {
            if ($uri === '') {
                $uri = $_module . '/';
            } elseif (strpos($uri, '/') === FALSE) {
                $uri = $_module . '/' . $uri;
            }
        }
        return parent::site_url($uri, $protocol);
    }
}
