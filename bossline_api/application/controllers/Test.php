<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Test extends _Base_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('test_mdl');
        $this->load->library('form_validation');

    }

    public function get_dooropen()
    {

        $return_array = array();

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data'] = 'test';
        echo json_encode($return_array);
        exit;

        $request = array(
            "order" => $this->input->post('order'),
        );

        // print_r($request);exit;

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('test_mdl');
        $row = $this->test_mdl->get_dooropen();        

        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data'] = $row;
        echo json_encode($return_array);
        exit;
        
    }
    

    public function get_bossline()
    {
        $return_array = array();

        $request = array(
            "order" => $this->input->post('order'),
        );

        // print_r($request);exit;

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('test_mdl');
        $row = $this->test_mdl->get_bossline();        

        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공 -git pull";
        $return_array['data'] = $row;
        echo json_encode($return_array);
        exit;
        
    }
    
}








