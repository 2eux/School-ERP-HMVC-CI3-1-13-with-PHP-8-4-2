<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Enable dynamic properties for this class in PHP 8.2+
#[AllowDynamicProperties]
class Welcome extends CI_Controller {
    public function index() {
        echo "Default Controller Works!";
    }
}
