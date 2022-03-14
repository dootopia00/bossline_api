<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/admin/_Admin_Base_Controller.php';

class Test extends _Admin_Base_Controller {
    public $upload_path_badge = ISTESTMODE ? 'test_upload/attach/badge/':'attach/badge/';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('badge_mdl');
        $this->load->library('form_validation');

    }

    public function test()
    {
        echo 1;
    }


}








