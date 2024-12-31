<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'third_party/MX/Base.php';

class MX_Controller extends CI_Controller
{
    public $autoload = [];
    public $log;
    public $benchmark;
    public $hooks;
    public $config;
    public $utf8;
    public $uri;
    public $router;
    public $output;
    public $security;
    public $input;
    public $lang;
    public $session;

    public function __construct()
    {
        parent::__construct();
        $this->load->_autoloader($this->autoload);
    }
}
