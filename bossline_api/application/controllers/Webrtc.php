<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Webrtc extends _Base_Controller {


    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }
    /*
        WEB RTC 페이지 강사 스케줄 정보 API
    */
    public function receive_schedule_info()
    {
        $return_array = array();    

        $request = array(
            'sc_id' => $this->input->post('sc_id') ? $this->input->post('sc_id') : null,          //13676188           //스케쥴 id      
            'tu_uid' => $this->input->post('tu_uid') ? $this->input->post('tu_uid') : null,                            //tu_uid
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('webrtc_mdl');
        $schedule_info = $this->webrtc_mdl->get_wiz_schedule_by_sc_id($request['sc_id'], $request['tu_uid']);

        if($schedule_info == NULL)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = '프로세스 오류';
            $return_array['data']['err_code'] = "0101";
            $return_array['data']['err_msg'] = "존재하지 않는 수강정보 입니다.";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = '수업정보 조회에 성공하였습니다.';
        $return_array['data']['info'] = $schedule_info;
        echo json_encode($return_array);
        exit;

    }

    /*
        WEB RTC 어플 푸시 보내기 API
    */
    public function send_push()
    {
        $return_array = array();

        $request = array(
            'wiz_id' => ($this->input->post('wiz_id')) ? ($this->input->post('wiz_id')) : null,                            //푸시받을 학생 wiz_id    
            'sc_id' => $this->input->post('sc_id') ? $this->input->post('sc_id') : null,          //13676188              //스케쥴 id      
            'tu_uid' => $this->input->post('tu_uid') ? $this->input->post('tu_uid') : null,        //158                   //푸시보낼 강사 tu_uid
            'p_type' => $this->input->post('p_type') ? $this->input->post('p_type') : null,                            //화상영어 : webrtc_video, 전화영어 : webrtc_voice
            'channel_name' => $this->input->post('channel_name') ? $this->input->post('channel_name') : null,          //채널명
            'state' => $this->input->post('state') ? $this->input->post('state') : '1',                                //1: 푸시발송, 2:푸시수신, 3: 수신거부, 4: 통화연결, 5:부재중(푸시 수신후부터 60초), 6: 연결실패(통화연결을 시도했으나 실패)
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }


        $this->load->model('webrtc_mdl');
        $wiz_member = $this->webrtc_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);

        if($wiz_member == NULL){

            $return_array['res_code'] = '0900';
            $return_array['msg'] = '프로세스 오류';
            $return_array['data']['err_code'] = "0101";
            $return_array['data']['err_msg'] = "존재하지 않는 id 입니다.";
            echo json_encode($return_array);
            exit;
        }
        
        $token_count = $this->webrtc_mdl->token_count_by_wiz_uid($wiz_member['uid']);
        
        if($token_count['cnt']== NULL){

            $return_array['res_code'] = '0900';
            $return_array['msg'] = '프로세스 오류';
            $return_array['data']['err_code'] = "0102";
            $return_array['data']['err_msg'] = $request['wiz_id']." 는 해당 토큰값이 없습니다.";
            echo json_encode($return_array);
            exit;
        }
    
        //tutor
        $wiz_tutor = $this->webrtc_mdl->get_wiz_tutor_by_tu_uid($request['tu_uid']);
        
        if($wiz_tutor == NULL){

            $return_array['res_code'] = '0900';
            $return_array['msg'] = '프로세스 오류';
            $return_array['data']['err_code'] = "0103";
            $return_array['data']['err_msg'] = "존재하지 않는 강사 uid 입니다.";
            echo json_encode($return_array);
            exit;
        }

        //tutor
        $missed_count = $this->webrtc_mdl->missed_count_by_sc_id($request['sc_id']);

        //p_data 세팅
        $p_data = array();
        $p_data['p_type'] = $request['p_type'];
        $p_data['uid'] = $wiz_member['uid'];
        $p_data['channel_name'] = $request['channel_name'];
        $p_data['tu_uid'] = $request['tu_uid'];
        $p_data['sc_id'] = $request['sc_id'];
        $p_data['tutor_name'] = $wiz_tutor['tu_name'];
        $p_data['tutor_image'] = 'https://cdn.mintspeaking.com/edu/tutor/picture/'.$wiz_tutor['tu_pic'];
        $p_data['missed_count'] = $missed_count['count'];

        $wiz_member_token = $this->webrtc_mdl->get_last_token_by_uid($wiz_member['uid']);

        if($wiz_member_token['device'] == 'Android'){
            $androidTokens = array();
            array_push($androidTokens, $wiz_member_token['token']);

            $log = array(
                'tu_uid' => $request['tu_uid'],
                'uid' => $wiz_member['uid'],
                'sc_id' => $request['sc_id'],
                'state' => $request['state'],
                'platform' => 'Android',
                'regdate' => date("Y-m-d H:i:s"),
                'update_date' => date("Y-m-d H:i:s"),
            );

            $result = $this->webrtc_mdl->insert_webrtc_push_log($log);
            $pk_key = $result;
            $p_data['wpl_pk_key'] = $pk_key;
            
            $res = AppPush::send_push_android($androidTokens, $p_data);
            
            if($res == 'success'){

                $return_array['res_code'] = '0000';
                $return_array['msg'] = 'Android 푸시 보내기에 성공하였습니다.';
                $return_array['data']['info'] = $wiz_member;
                echo json_encode($return_array);
                exit;

            }else{
                $return_array['res_code'] = '0900';
                $return_array['msg'] = 'Android 푸시 보내기에 실패하였습니다.';
                echo json_encode($return_array);
                exit;

            }

        }else if($wiz_member_token['device'] == 'Ios'){

            $log = array(
                'tu_uid' => $request['tu_uid'],
                'uid' => $wiz_member['uid'],
                'sc_id' => $request['sc_id'],
                'state' => $request['state'],
                'platform' => 'Android',
                'regdate' => date("Y-m-d H:i:s"),
                'update_date' => date("Y-m-d H:i:s"),
            );
            
            $result = $this->webrtc_mdl->insert_webrtc_push_log($log);
            $pk_key = $result;
            $p_data['wpl_pk_key'] = $pk_key;
            
            $res = AppPush::send_push_ios($wiz_member_token['token'], $p_data);

            if($res['result']=='success'){

                $return_array['res_code'] = '0000';
                $return_array['msg'] = 'Ios 푸시 보내기에 성공하였습니다.';
                $return_array['data']['info'] = $wiz_member;
                $return_array['data']['push_log'] = $log;
                echo json_encode($return_array);
                exit;
            }else{

                $return_array['res_code'] = '0900';
                $return_array['msg'] = 'Ios 푸시 보내기에 실패하였습니다.';
                echo json_encode($return_array);
                exit;
            }
        }
    }

    /*
        WEB RTC 푸시 로그 받기
    */
    public function receive_push_log()
    {
        $return_array = array();    

        $request = array(
            'sc_id' => $this->input->post('sc_id') ? $this->input->post('sc_id') : null,          //13676188           //스케쥴 id      
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('webrtc_mdl');
        $result = $this->webrtc_mdl->list_webrtc_push_log_by_sc_id($request['sc_id']);
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = '조회에 성공하였습니다';
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /*
        WEB RTC 푸시 로그 받기
    */
    public function receive_push_device()
    {

        $return_array = array();    

        $request = array(
            'wpl_pk_key' => $this->input->post('wpl_pk_key') ? $this->input->post('wpl_pk_key') : null,                 //webrtc_push_log pk_key    
            'device' => $this->input->post('device') ? $this->input->post('device') : null,                             //디바이스 종류
            'state' => $this->input->post('state') ? $this->input->post('state') : null,                                //1: 푸시발송, 2:푸시수신, 3: 수신거부, 4: 통화연결, 5:부재중(푸시 수신후부터 60초), 6: 연결실패(통화연결을 시도했으나 실패)  
            'desc' => $this->input->post('desc') ? $this->input->post('desc') : null,                                   //안드로이드 리턴했을때 에러시 로그 입력
            // 'sc_id' => $this->input->post('sc_id') ? $this->input->post('sc_id') : null,                             //재전송 강사 스케쥴 id            
            // 'tu_uid' => $this->input->post('tu_uid') ? $this->input->post('tu_uid') : null,                          //재전송 학생 tu_uid    
            // 'uid' => $this->input->post('uid') ? $this->input->post('uid') : null,                                   //회원 uid
            // 'p_type' => $this->input->post('p_type') ? $this->input->post('p_type') : null,                          //화상영어 : webrtc_video, 전화영어 : webrtc_voice    
            // 'tutor_name' => $this->input->post('tutor_name') ? $this->input->post('tutor_name') : null,              //임시 강사 이름
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('webrtc_mdl');
        $result = $this->webrtc_mdl->checked_webrtc_push_log_by_wpl_key($request['wpl_key']);

        if($result == null)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = '프로세스 오류';
            $return_array['data']['err_code'] = "0101";
            $return_array['data']['err_msg'] = "존재하지 않는 로그입니다.";
            echo json_encode($return_array);
            exit;
        }

        $data = array(
            "state" => $request['state'],
            "device" => $request['device'],
            "desc" => $request['desc'],
            "update_date" => date('Y-m-d H:i:s'),
        );
        
        $result = $this->webrtc_mdl->update_webrtc_push_log(array_filter($data), $request['wpl_pk_key']);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "정보 갱신에 성공하였습니다.";
        echo json_encode($return_array);
        exit;
    }

    public function send_push_check()
    {

        $return_array = array();    

        $request = array(
            'wpl_pk_key' => $this->input->post('wpl_pk_key') ? $this->input->post('wpl_pk_key') : null,                 //webrtc_push_log pk_key    
            'sc_id' => $this->input->post('sc_id') ? $this->input->post('sc_id') : null,                                //재전송 강사 스케쥴 id  
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('webrtc_mdl');
        $sc_time_checked = $this->webrtc_mdl->sc_time_checked_webrtc_push_log_by_sc_id($request['sc_id']);

        if($sc_time_checked == NULL)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스 오류";
            $return_array['data']['err_code'] = "0102";
            $return_array['data']['err_msg'] = "해당 수업시간이 아닙니다.";
            $return_array['data']['state'] = "N";
            echo json_encode($return_array);
            exit;
        }

        $key_checked = $this->webrtc_mdl->over_checked_webrtc_push_log_by_wpl_key($request['wpl_pk_key']);

        if($key_checked != NULL)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스 오류";
            $return_array['data']['state'] = "N";
            $return_array['data']['err_code'] = "0103";
            $return_array['data']['err_msg'] = "해당 pk_key 이후 푸시 내역이 존재합니다.";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "해당 pk_key 이후 푸시 내역이 없습니다.";
        $return_array['data']['state'] = "Y";
        echo json_encode($return_array);
        exit;
        
    }


    /*
        말톡노트 로그 입력
    */
    public function insert_maaltalk_note_log()
    {
        $return_array = array();    

        $request = array(
            'tu_uid' => $this->input->post('tu_uid') ? $this->input->post('tu_uid') : null, 
            'wm_uid' => $this->input->post('wm_uid') ? $this->input->post('wm_uid') : null, 
            'sc_id' => $this->input->post('sc_id') ? $this->input->post('sc_id') : null, 
            'state' => $this->input->post('sc_id') ? $this->input->post('state') : null, 
            'invitational_url' => $this->input->post('invitational_url') ? $this->input->post('invitational_url') : null, 
            'msg_type' => $this->input->post('msg_type') ? $this->input->post('msg_type') : null,  
            'receipt_number' => $this->input->post('receipt_number') ? $this->input->post('receipt_number') : null,  
            'loc' => $this->input->post('loc') ? $this->input->post('loc') : null,  
        );

        //log_message("error", $request['invitational_url']);

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        /* member_mdl 함수 사용해서 본인 스케쥴 여부체크 */

        $this->load->model('webrtc_mdl');
        $this->load->model('member_mdl');
        $this->load->model('lesson_mdl');
        $this->load->model('tutor_mdl');
 
        /* 본인 수업정보 맞으면 시간 체크 수업2분전 ~ 종료시까지 입장가능*/
        $schedule = $this->tutor_mdl->row_schedule_by_sc_id($request['sc_id']);

        if(!$schedule)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0201';
            $return_array['data']['err_msg'] = '등록된 데이터가 없습니다.';
            echo json_encode($return_array);
            exit;
        }

        $endday = lesson_find_enddatetime($schedule);

        //수업 종료시간이후에 접근하면..
        if($endday < date('Y-m-d H:i:s'))
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0201';
            $return_array['data']['err_msg'] = $schedule['ws_startday'].' 에 시작하는 본 수업은 이미 종료되었습니다.';
            echo json_encode($return_array);
            exit;
        }

        $log = array(
            'tu_uid' => $request['tu_uid'],
            'wm_uid' => $request['wm_uid'],
            'sc_id' => $request['sc_id'],
            'state' => $request['state'],
            'invitational_url' => $request['invitational_url'],
            'msg_type' => $request['msg_type'],
            'receipt_number' => $request['receipt_number'],
            'loc' => $request['loc'],
            'regdate' => date("Y-m-d H:i:s"),  
        );

        
        $result = $this->webrtc_mdl->insert_maaltalk_note_log($log);
        
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "로그 입력 성공";
        echo json_encode($return_array);
        exit;

    }



}








