<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class User extends _Base_Controller {

    public function __construct()
    {
        parent::__construct();

        //한국 시간 설정
        // date_default_timezone_set('Asia/Seoul');
        $this->load->library('form_validation');
    }

    //로그인
    public function sign_in()
    {
        $return_array = array();

        $request = array(
            
            "user_id" => trim(strtolower($this->input->post('user_id'))),
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
        $user = $this->user_mdl->get_user_id($request['user_id']);
        
        // echo $user;exit;
        
        // 가입된 user_id 가 없으면 회원가입후 sign in
        // 가입된 user_id 가 있으면 바로 로그인
        
        if($user == NULL){

            // 회원가입

            
            //인설트 데이터
            $user = array(
                "id" => 2,
                "user_id" => $request['user_id'],
                "type" => 'kakao',
                "reg_date" => date("Y-m-d H:i:s"),
            );

            $user = $this->user_mdl->insert_user($user);

        }else{

            // 로그인
            
        }


        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['info'] = $user;
        echo json_encode($return_array);
        exit;

    }

}







