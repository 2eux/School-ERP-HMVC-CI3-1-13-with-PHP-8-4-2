<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Modular Extensions - HMVC
 *
 * Description:
 * This library replaces the CI_Lang class and provides additional functionality
 * for working with modules.
 *
 * Install this file as application/third_party/MX/Lang.php
 *
 * @copyright   Copyright (c) 2015 Wiredesignz
 * @version     5.5
 */

class MX_Lang extends CI_Lang
{
    /**
     * Load a language file
     *
     * @param string $langfile
     * @param string $lang
     * @param bool   $return
     * @param bool   $add_suffix
     * @param string $alt_path
     * @return void|array
     */
    public function load($langfile, $lang = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '')
    {
        if (is_array($langfile)) {
            foreach ($langfile as $_lang) {
                $this->load($_lang, $lang, $return, $add_suffix, $alt_path);
            }
            return;
        }

        if ($add_suffix === TRUE) {
            $langfile = str_replace('.php', '', $langfile) . '_lang';
        }

        if (in_array($langfile, $this->is_loaded, TRUE)) {
            return $this->language;
        }

        $_module = CI::$APP->router->fetch_module();
        list($path, $file) = Modules::find($langfile, $_module, 'language/' . $lang);

        if ($path === FALSE) {
            parent::load($langfile, $lang, $return, $add_suffix, $alt_path);
            return;
        }

        if ($lang === '') {
            $lang = CI::$APP->config->item('language');
        }

        if ($return === TRUE) {
            $lang_array = Modules::load_file($file, $path, 'lang');
            return $lang_array;
        }

        Modules::load_file($file, $path, 'lang');
        $this->is_loaded[] = $langfile;
        log_message('debug', 'Language file loaded: ' . $path . $file . '.php');
        return $this->language;
    }
}
