<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Clan extends _Base_Controller {

    public function __construct()
    {
        parent::__construct();

        // date_default_timezone_set('Asia/Seoul');
        $this->load->library('form_validation');
    }
    
    public function clan_insert()
    {
        $return_array = array();

        $request = array(
            "user_id"       => trim($this->input->post('user_pk')),
            "authorization" => trim($this->input->post('authorization')),
            "clan_name"     => $this->input->post('clan_name') ? $this->input->post('clan_name') : NULL,
            "clan_level"    => $this->input->post('clan_level') ? $this->input->post('clan_level') : NULL,
            "recruit_type"  => $this->input->post('recruit_type') ? $this->input->post('recruit_type') : NULL,
            "recruit_yn"    => $this->input->post('recruit_yn') ? $this->input->post('recruit_yn') : NULL,
            "server"        => $this->input->post('server') ? $this->input->post('server') : NULL,
            "type"          => $this->input->post('type') ? $this->input->post('type') : NULL,
            "level"         => $this->input->post('level') ? $this->input->post('level') : NULL,
            "defense"       => $this->input->post('defense') ? $this->input->post('defense') : NULL,
            "job"           => $this->input->post('job') ? $this->input->post('job') : NULL,
            "description"   => $this->input->post('description') ? $this->input->post('description') : NULL,
            "welfare"       => $this->input->post('welfare') ? $this->input->post('welfare') : NULL,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = 400;
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('clan_mdl');
        
        //인설트 데이터
        $clan = array(
            "user_id"       => $request['user_id'],
            "recruit_yn"    => $request['recruit_yn'],
            "clan_name"     => $request['clan_name'],
            "clan_level"    => $request['clan_level'],
            "recruit_type"  => $request['recruit_type'],
            "server"        => $request['server'],
            "type"          => $request['type'],
            "level"         => $request['level'],
            "defense"       => $request['defense'],
            "job"           => $request['job'],
            "description"   => $request['description'],
            "welfare"       => $request['welfare'],
            "reg_date"      => date("Y-m-d H:i:s"),
        );

        $clan = $this->clan_mdl->insert_clan($clan);

        if($clan < 0)
        {
            $return_array['res_code'] = 500;
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = 200;
        $return_array['msg'] = "등록성공";
        $return_array['data']['info'] = $clan;
        echo json_encode($return_array);
        exit;
        
    }

    public function get_clan_info()
    {
        $return_array = array();

        $request = array(
            "clan_pk"       => $this->input->post('clan_pk') ? $this->input->post('clan_pk') : NULL,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = 400;
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('clan_mdl');
        $clan = $this->clan_mdl->get_clan_info_by_pk($request['clan_pk']);
        
        if($clan == NULL){
            
            $return_array['res_code'] = 404;
            $return_array['msg'] = "조회되지 않는 정보입니다";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = 200;
        $return_array['msg'] = "조회성공";
        $return_array['data']['info'] = $clan;
        echo json_encode($return_array);
        exit;
        
    }


    public function clan_list()
    {
        $return_array = array();

        $request = array(
            "type" => $this->input->post('type') ? $this->input->post('type') : null,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = 400;
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('clan_mdl');
        
        $clan_list = $this->clan_mdl->get_clan_list($request['type']);
        $clan_list_count = $this->clan_mdl->get_clan_list_count($request['type']);

        $return_array['res_code'] = 200;
        $return_array['msg'] = "조회성공";
        $return_array['data']['list'] = $clan_list;
        $return_array['data']['total_count'] = $clan_list_count['count'];
        echo json_encode($return_array);
        exit;
        
    }
    

    public function clan_modify()
    {
        $return_array = array();

        $request = array(
            "user_pk"       => trim($this->input->post('user_pk')),
            "authorization" => trim($this->input->post('authorization')),
            "clan_pk"       => $this->input->post('clan_pk') ? $this->input->post('clan_pk') : NULL,
            "clan_name"     => $this->input->post('clan_name') ? $this->input->post('clan_name') : NULL,
            "clan_level"    => $this->input->post('clan_level') ? $this->input->post('clan_level') : NULL,
            "recruit"       => $this->input->post('recruit') ? $this->input->post('recruit') : NULL,
            "server"        => $this->input->post('server') ? $this->input->post('server') : NULL,
            "type"          => $this->input->post('type') ? $this->input->post('type') : NULL,
            "level"         => $this->input->post('level') ? $this->input->post('level') : NULL,
            "defense"       => $this->input->post('defense') ? $this->input->post('defense') : NULL,
            "job"           => $this->input->post('job') ? $this->input->post('job') : NULL,
            "description"   => $this->input->post('description') ? $this->input->post('description') : NULL,
            "welfare"       => $this->input->post('welfare') ? $this->input->post('welfare') : NULL,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = 400;
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('clan_mdl');
        $clan = $this->clan_mdl->get_clan_info_by_pk($request['clan_pk']);
        
        if($clan == NULL){
            
            $return_array['res_code'] = 404;
            $return_array['msg'] = "조회되지 않는 정보입니다";
            echo json_encode($return_array);
            exit;
        }
        
        //인설트 데이터
        $clan = array(
            "user_id"       => $request['user_pk'],
            "recruit"       => $request['recruit'],
            "clan_name"     => $request['clan_name'],
            "clan_level"    => $request['clan_level'],
            "server"        => $request['server'],
            "type"          => $request['type'],
            "level"         => $request['level'],
            "defense"       => $request['defense'],
            "job"           => $request['job'],
            "description"   => $request['description'],
            "welfare"       => $request['welfare'],
            "reg_date"      => date("Y-m-d H:i:s"),
        );

        $clan = $this->clan_mdl->modify_clan($clan);

        $return_array['res_code'] = 200;
        $return_array['msg'] = "등록성공";
        $return_array['data']['info'] = $clan;
        echo json_encode($return_array);
        exit;
        
    }


}







