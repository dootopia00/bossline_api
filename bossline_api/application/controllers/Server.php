<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Server extends _Base_Controller {

    public function __construct()
    {
        parent::__construct();

        // date_default_timezone_set('Asia/Seoul');
        $this->load->library('form_validation');
    }

    //로그인
    public function server_list()
    {
        $return_array = array();

        $request = array(
            "type" => $this->input->post('type') ? $this->input->post('type') : null,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('server_mdl');
        
        $server_list = $this->server_mdl->get_server_list($request['type']);
        $server_list_count = $this->server_mdl->get_server_list_count($request['type']);

        $return_array['res_code'] = 200;
        $return_array['msg'] = "조회성공";
        $return_array['data']['list'] = $server_list;
        $return_array['data']['total_count'] = $server_list_count['count'];

        echo json_encode($return_array);
        exit;
        
    }

}







