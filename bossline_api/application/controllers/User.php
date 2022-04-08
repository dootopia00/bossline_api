<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class User extends _Base_Controller {

    public function __construct()
    {
        parent::__construct();

        // date_default_timezone_set('Asia/Seoul');
        $this->load->library('form_validation');
    }

    //로그인
    public function sign_in()
    {
        $return_array = array();

        $request = array(
            "user_id" => trim(strtolower($this->input->post('user_id'))),
            "email"   => trim(strtolower($this->input->post('email'))),
        );


        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('user_mdl');
        
        // 아이디 유무 체크
        $user = $this->user_mdl->get_user_id($request['user_id'], $request['email']);
        
        // echo $user;exit;
        
        // 가입된 user_id 가 없으면 회원가입후 sign in
        // 가입된 user_id 가 있으면 바로 로그인
        
        $user_info = array(
            "user_id"   => $request['user_id'],
            "email"     => $request['email'],
            "type"      => 'kakao',
            "reg_date"  => date("Y-m-d H:i:s"),
        );

        if($user == NULL){

            // 회원가입
            
            //인설트 데이터
            $user = $this->user_mdl->insert_user($user_info);

            $return_array['res_code'] = 200;
            $return_array['msg'] = "회원가입성공";
            $return_array['data']['info'] = $user;
            $return_array['data']['api_token'] = token_create_member_token($user['user_id']);
            echo json_encode($return_array);
            exit;
            
        }else{

            // 로그인
            
            $return_array['res_code'] = 200;
            $return_array['msg'] = "로그인성공";
            $return_array['data']['info'] = $user;
            // print_r($user);exit;
            $return_array['data']['api_token'] = token_create_member_token($user['user_id']);
            echo json_encode($return_array);
            exit;
            
        }


        

    }

}






