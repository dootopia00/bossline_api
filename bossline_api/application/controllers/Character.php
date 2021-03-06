<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';


class Character extends _Base_Controller {

    public function __construct()
    {
        parent::__construct();

        $this->load->library('form_validation');
    }

    public function character_modify()
    {
        $return_array = array();

        $request = array(
            "user_pk"       => trim($this->input->post('user_pk')),
            "authorization" => trim($this->input->post('authorization')),
            "nickname"      => $this->input->post('nickname') ? $this->input->post('nickname') : NULL,
            "clan_name"     => $this->input->post('clan_name') ? $this->input->post('clan_name') : NULL,
            "defense"       => $this->input->post('defense') ? $this->input->post('defense') : 0,
            "level"         => $this->input->post('level') ? $this->input->post('level') : 0,
            "job"           => $this->input->post('job') ? $this->input->post('job') : NULL,
            "change"        => $this->input->post('change') ? $this->input->post('change') : NULL,
            "email"         => $this->input->post('email') ? $this->input->post('email') : NULL,
            "type"          => $this->input->post('type') ? $this->input->post('type') : NULL,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = 400;
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        // $character = $this->character_mdl->get_character_info_by_request($request);
        
        //수정 데이터
        $character = array(
            "user_id"       => $request['user_pk'],
            "nickname"      => $request['nickname'],
            "clan_name"     => $request['clan_name'],
            "defense"       => $request['defense'],
            "level"         => $request['level'],
            "job"           => $request['job'],
            "change"        => $request['change'],
            "email"         => $request['email'],
            "type"          => $request['type'],
        );

        $this->load->model('character_mdl');
        $res = $this->character_mdl->modify_character($character);

        if($res < 0)
        {
            $return_array['res_code'] = 500;
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }


        $return_array['res_code'] = 200;
        $return_array['msg'] = "성공";
        $return_array['data'] = $res;
        echo json_encode($return_array);
        exit;
        
    }
}







