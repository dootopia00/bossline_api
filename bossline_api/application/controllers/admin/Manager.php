<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/admin/_Admin_Base_Controller.php';

class Manager extends _Admin_Base_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');

    }

    public function login()
    {

        $return_array = array();

        $request = array(
            "a_id" => trim(strtolower($this->input->post('a_id'))),
            "a_pw" => trim($this->input->post('a_pw')),
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('admin/manager_mdl', 'a_manager_mdl');
        
        $result = $this->a_manager_mdl->login($request['a_id'], sha1($request['a_pw']));
    
        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0104";
            $return_array['data']['err_msg'] = "Wrong ID or PASSWORD";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "success login";
            $return_array['data']['api_token'] = token_create_tutor_token($request['a_id']);
            $return_array['data']['info'] = $result;
            echo json_encode($return_array);
            exit;
        }

    }


}








