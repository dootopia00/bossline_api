<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Leveltest extends _Base_Controller {


    public function __construct()
    {
        parent::__construct();

        $this->load->library('form_validation');

    }

    public function leveltest_()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
        );

        /* 유효성 확인 */
        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        
        $this->load->model('leveltest_mdl');
        $list_cnt = $this->leveltest_mdl->list_count_leveltest($wiz_member['wm_uid']);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        // log_message("error", $request);

        $result = $this->leveltest_mdl->list_leveltest_by_uid($wiz_member['wm_uid']);

        //취소가능한지
        $first_date = $result[0]['le_start'];
        $return_array['data']['cancel_possible'] = ($result[0]['le_step'] == '1' && strtotime($first_date) - time() > 1800) ? 1:0;

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;

    }

    
    /* 레벨테스트 삭제(취소) */
    public function delete_leveltest()
    {
        $return_array = array();
        
        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "le_fid" => $this->input->post('le_fid') ? $this->input->post('le_fid') : NULL,
            "is_app" => (strtolower($this->input->post('is_app')) == "pc") ? "N" : "Y",         // pc, mobile, app
        );

        /* 유효성 확인 */
        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 정보 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }
    

        /* 
            이전 레벨테스트 진행상태 체크 및 정책
        
            1. 시작 30분전 삭제 / 변경 가능(기존 신청 내역 삭제)
            2. 시작 30분 이내~테스트 시간 모두 종료전까지 삭제 / 변경 불가
            3. 마지막 테스트 종료시간 지난 후 테스트들 중에 결석이 하나라도 있으면 테스트 재신청 가능(기존 테스트 내역은 유지한 상태로 추가 신청)
            4. 마지막 테스트 종료시간 지난 후 테스트들이 모두 출석이면 재신청 불가.
        */
        $this->load->model('leveltest_mdl');
        $cheked_leveltest = $this->leveltest_mdl->check_member_leveltest_progress($wiz_member['wm_uid'], $request['le_fid']);

        if(!$cheked_leveltest)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        /*
            레벨테스트 평가 완료 여부 확인
            total_cnt : 레벨테스트 횟수 , attendance_cnt : 출석 횟수
        */
        if($cheked_leveltest['total_cnt'] == $cheked_leveltest['attendance_cnt'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0607";
            $return_array['data']['err_msg'] = "평가 완료된 레벨테스트는 삭제할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        /*
            레벨테스트 삭제 - 시작시간 30분이내 인지 체크 , 30분 이내면 삭제 불가
            change_restriction_cnt : 삭제 불가능한 (시작 30분전) 레벨테스트 갯수
        */
        if($cheked_leveltest['change_restriction_cnt'] > 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0607";
            $return_array['data']['err_msg'] = "레벨테스트 시작 30분 전에는 스케줄을 삭제할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }


        // 레벨테스트 삭제
        $result = $this->leveltest_mdl->delete_leveltest($wiz_member['wm_uid'], $request['le_fid']);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "레벨테스트가 취소됐습니다.";
            echo json_encode($return_array);
            exit;
        }

    }


     /* 레벨테스트 신청 및 변경 */
    public function apply_leveltest()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "lesson_gubun" => $this->input->post('lesson_gubun'), 
            "lvt_contact" => $this->input->post('lvt_contact'),
            "hopedate" => $this->input->post('hopedate'),
            "hopetime1" => $this->input->post('hopetime1'),
            "hopetime2" => $this->input->post('hopetime2'),
            "englevel" => $this->input->post('englevel'),
            "memo" => $this->input->post('memo'),
            "is_app" => (strtolower($this->input->post('is_app')) == "pc") ? "N" : "Y",   // pc, mobile, app
            "re_apply" => strtoupper($this->input->post('re_apply')),  // Y: 레벨테스트 변경, N: 신청
            "le_fid" => ($this->input->post('le_fid')) ? $this->input->post('le_fid') : NULL,  // 레벨테스트 그룹번호 - 레벨테스트 변경요청시 해당 레벨테스트 그룹번호 
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 정보 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        
        /*
            레벨테스트 가능시간
            
            - 평일예약 가능시간: 월요일 새벽1시 ~ 토요일 새벽 00:00 까지
            - 주말예약 가능시간: 토요일 아침6시 ~ 저녁 23:00 / 일요일 아침6시~ 저녁 23:00
        */

        $week = array(" (일) " , " (월) "  , " (화) " , " (수) " , " (목) " , " (금) " ," (토) ") ;
        $weekday = $week[ date('w' , strtotime($request['hopedate']))] ;

        //희망 시간
        $request['hopetime'] = $request['hopetime1'].":".$request['hopetime2'].":00";
        $hope_date = strtotime($request['hopetime']);

        $date_01 = strtotime('01:00:00');
        $date_06 = strtotime('06:00:00');
        $date_23 = strtotime('23:00:00');

        if($weekday == ' (월) ' && ($hope_date < $date_01))
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0607";
            $return_array['data']['err_msg'] = "레벨테스트 평일 월요일 새벽1시 ~ 토요일 새벽 00:00 까지 가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        if( (($weekday == ' (토) ' || $weekday == ' (일) ') && ($hope_date < $date_06)) || (($weekday == ' (토) ' || $weekday == ' (일) ') && ($hope_date > $date_23)) )
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0607";
            $return_array['data']['err_msg'] = "레벨테스트는 주말은 아침6시 ~ 저녁 23:00 까지 가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        /* 
            이전 레벨테스트 진행상태 체크 및 정책
        
            1. 시작 30분전 삭제 / 변경 가능(기존 신청 내역 삭제)
            2. 시작 30분 이내~테스트 시간 모두 종료전까지 삭제 / 변경 불가
            3. 마지막 테스트 종료시간 지난 후 테스트들 중에 결석이 하나라도 있으면 테스트 재신청 가능(기존 테스트 내역은 유지한 상태로 추가 신청)
            4. 마지막 테스트 종료시간 지난 후 테스트들이 모두 출석이면 재신청 불가.
        */
        $this->load->model('leveltest_mdl');
        $cheked_leveltest = $this->leveltest_mdl->check_member_leveltest_progress($wiz_member['wm_uid'], $request['le_fid']);

        if($cheked_leveltest)
        {
            /*
                마지막 테스트 종료시간 지난 후 테스트들이 모두 출석이면 재신청 불가.
                total_cnt : 레벨테스트 횟수 , attendance_cnt : 출석 횟수
            */
            if($cheked_leveltest['total_cnt'] == $cheked_leveltest['attendance_cnt'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0607";
                $return_array['data']['err_msg'] = "레벨테스트는 1회만 신청 가능합니다. 재평가는 MSET 시험으로 신청해 주세요.";
                echo json_encode($return_array);
                exit;
            }

            
            /* 
                re_apply 재신청 여부
                Y : 레벨테스트 변경요청시 - 시작시간 30분이내 인지 체크 , 30분 이내면 변경 불가
                N : 레벨테스트 신규요청시 - 그전에 진행예정인 레벨테스트가 있는지 체크, 있으면 신규신청 불가 
            */
            if($request['re_apply'] == "Y")
            {
                /*  change_restriction_cnt : 변경 불가능한 (시작 30분전) 레벨테스트 갯수 */
                if($cheked_leveltest['change_restriction_cnt'] > 0)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0607";
                    $return_array['data']['err_msg'] = "레벨테스트 시작 30분 전에는 스케줄을 변경할 수 없습니다.";
                    echo json_encode($return_array);
                    exit;
                }

            }
            else  
            {
                /* schedule_cnt : 진행예정인 레벨테스트 */
                if($cheked_leveltest['schedule_cnt'] > 0)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0607";
                    $return_array['data']['err_msg'] = "진행 예정인 레벨테스트가 있습니다.";
                    echo json_encode($return_array);
                    exit;
                }

            }

        }
        

        // 레벨테스트는 E(민트영어라이브), M(휴대폰)만가능
        $lesson_gubun = $request['lesson_gubun'] == 'E' ? 'E':'M';

        //레슨별 횟수/시간 
        $lesson_term = $request['lesson_gubun'] == 'E' ? 3 : 3;
        $lesson_time = $request['lesson_gubun'] == 'E' ? 10 : 10;

        //첫 테스트 시작/종료 시간
        $request['le_start'] = $request['hopedate']." ".$request['hopetime'];
        $request['le_end'] = date('Y-m-d H:i:s',strtotime("+$lesson_time minutes -1 seconds", strtotime($request['le_start'])));

        //테스트 시간 비교
        $now_time = strtotime("+30 minutes");
        $max_time = strtotime($request['le_start']);

        if($max_time < $now_time)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0604";
            $return_array['data']['err_msg'] = "테스트 시간은 현재시간 기준으로 30분 이후부터 가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        //전화번호 추가
        $lvt_contact = common_checked_phone_format($request['lvt_contact']);
        $lvt_contact_type = common_checked_phone_number_type($request['lvt_contact']);
        $request['tel'] = ($lvt_contact_type == 'T') ? $lvt_contact : '';
        $request['mobile'] = ($lvt_contact_type == 'M') ? $lvt_contact : '';
        
        $multi_data = array();
    
        for($i=0; $i < $lesson_term; $i++)
        {
            if($i > 0)
            {
                /*
                    MEL은 쉬는시간 없이 20분씩 연달아 3회 -> 변경으로 전화영어처럼 10분잡는다
                    전화영어는 10분 테스트후 10분 쉬는시간 3회
                */
                
                $request['le_start'] = date('Y-m-d H:i:s', strtotime("+10 minutes +1 seconds", strtotime($request['le_end'])));
                $request['le_end'] = date('Y-m-d H:i:s', strtotime("+$lesson_time minutes -1 seconds", strtotime($request['le_start'])));
                

            }

            $hopetime = substr($request['le_start'],11);

            $data_leveltest = array(
                "uid" => $wiz_member['wm_uid'],
                "wiz_id" => $wiz_member['wm_wiz_id'],
                "name" => $wiz_member['wm_name'],
                "ename" => $wiz_member['wm_ename'],
                "lesson_gubun" => $lesson_gubun,
                "tel" => $request['tel'],
                "mobile" => $request['mobile'],
                "englevel" => $request['englevel'],
                "hopedate" => $request['hopedate'],
                "hopetime" => $hopetime,
                "sc_ok" => "N",
                "regdate" => date("Y-m-d H:i:s"),
                "le_start" => $request['le_start'],
                "le_end" => $request['le_end'],
                "mob" => $request['is_app'],
                'cl_time' => $lesson_time,
            );
            array_push($multi_data, $data_leveltest);
        }

        /* 레벨테스트 요청사항  */
        $data_memo = array(
            "uid" => $wiz_member['wm_uid'],
            "wiz_id" => $wiz_member['wm_wiz_id'],
            "memo" => $request['memo']
        );
-
        
        $result_leveltest = $this->leveltest_mdl->apply_leveltest($multi_data, $data_memo, $wiz_member['wm_uid'], $wiz_member['wm_muu_key'], $request['le_fid']);

        if($result_leveltest < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        //알림톡 전송
        $CONFIG_ATALK_CODE = $this->config->item('ATALK_CODE');
        $CONFIG_SMS_ID = $this->config->item('SMS_ID');

        /* 비동기 전송시 보낼 알림톡 코드/SMS ID 세팅  */
        if($lesson_gubun == 'M')
        {
            $ATALK_CODE = $CONFIG_ATALK_CODE['APPLY_LEVELTEST_TEL'];
            $SMS_ID = $CONFIG_SMS_ID['APPLY_LEVELTEST_TEL'];
        }
        else if($lesson_gubun == 'E')
        {
            $ATALK_CODE = $CONFIG_ATALK_CODE['APPLY_LEVELTEST_MEL'];
            $SMS_ID = $CONFIG_SMS_ID['APPLY_LEVELTEST_MEL'];
        }

        // 비동기 전송
        notify_send_sms($wiz_member['wm_uid'], $ATALK_CODE, $SMS_ID);
        

        $month = date("m",strtotime($request['hopedate']))."월 ";
        $day = date("d",strtotime($request['hopedate']))."일 ";
        $hour = date("H",strtotime($request['hopetime']));
        $minute = date("i",strtotime($request['hopetime']));
        $hopetime = "";

        if($hour >= 12)
        {
            if($hour > 12)
            {
                $hour = $hour - 12;
            }

            $hopetime = "오후 ".$hour.":".$minute;
        }
        else
        {
            $hopetime = "오전 ".$hour.":".$minute;
        }

        $return_msg = $month.$day.$weekday.$hopetime;
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "레벨 테스트가 신청되었습니다.";
        $return_array['data']['le_fid'] = $result_leveltest;
        $return_array['data']['test_date'] = $return_msg;
        echo json_encode($return_array);
        exit;

    }

    /* 레벨테스트 상세결과 */
    public function detailed_result()
    {
        $return_array = array();

        $request = array(
            "wiz_id"        => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "le_id"         => trim($this->input->post('le_id')),
            "le_fid"        => trim($this->input->post('le_fid')),
        );

        /* 유효성 확인 */
        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }
        
        $this->load->model('leveltest_mdl');
        $result = $this->leveltest_mdl->row_leveltest_by_le_id($wiz_member['wm_uid'], $request['le_id'], $request['le_fid']);

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        // 레벨테스트 테스트 종료일때
        if($result['le_step'] == 3)
        {
            if($result['book_id'])
            {
                $recommend_level = $this->leveltest_mdl->row_curriculum_by_book_id($result['book_id']);
                $result['recommend_level'] = $recommend_level['recommend_level'];
                $result['table_code'] = $recommend_level['table_code'];
                $result['curriculum_image'] = $recommend_level['image'];
            }

            //평균 점수 구하기
            $average = $this->leveltest_mdl->row_leveltest_user_avergae($result['le_start']);
            if($average)
            {
                $result['avg_pronunciation'] = $average['avg_pronunciation'];
                $result['avg_vocabulary'] = $average['avg_vocabulary'];
                $result['avg_speaking'] = $average['avg_speaking'];
                $result['avg_listening'] = $average['avg_listening'];
                $result['avg_grammar'] = $average['avg_grammar'];
            }

            // $this->load->model('tutor_mdl');
            // $tutor = $this->tutor_mdl->row_tutor_star_log($result['tu_uid']);

        }

        //녹화파일 조회
        if($result['lesson_gubun'] == "T")      $call_tel = $result['tel'];
        else if($result['lesson_gubun'] == "M") $call_tel = $result['mobile'];
        $call_tel2 = $call_tel;
        $call_tel = str_replace("-", "",$call_tel);
        $param = array(
            'date'   => substr($result['le_start'],0,10),
            'tel'    => $call_tel,
            'tel2'   => $call_tel2,
            'sc_id'  => $result['sc_id'],
            'wm_uid' => $result['uid']
        );
        $record_list = DialComm::record_list($result['lesson_gubun'], $param);

        MintQuest::request_batch_quest('5', $request['le_id']);

        $return_array['res_code']            = '0000';
        $return_array['msg']                 = "상세결과 조회성공";
        $return_array['data']['info']        = $result;
        $return_array['data']['record_list'] = $record_list;
        echo json_encode($return_array);
        exit;

    }

    /* 레벨테스트 진행 여부 체크 */
    public function checked_leveltest_le_step()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
        );
        
        /* 유효성 확인 */
        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        

        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('leveltest_mdl');
        $cheked_leveltest = $this->leveltest_mdl->chcked_progress_leveltest($wiz_member['wm_uid']);

        // 진행중인 레벨테스트 있는지 체크
        if($cheked_leveltest['cnt'] > 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0607";
            $return_array['data']['err_msg'] = "진행중인 레벨테스트가 있습니다.";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "레벨테스트 진행 가능";
        echo json_encode($return_array);
        exit;

    }

    /* 레벨테스트 진행 여부 체크 */
    public function checked_progress_leveltest()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "le_fid" => $this->input->post('le_fid') ? $this->input->post('le_fid') : NULL,
        );

        
        /* 유효성 확인 */
        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        

        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }


        $this->load->model('leveltest_mdl');
        $cheked_leveltest = $this->leveltest_mdl->check_member_leveltest_progress($wiz_member['wm_uid'], $request['le_fid']);

        if($cheked_leveltest)
        {
            /*
                마지막 테스트 종료시간 지난 후 테스트들이 모두 출석이면 재신청 불가.
                total_cnt : 레벨테스트 횟수 , attendance_cnt : 출석 횟수
            */
            if($cheked_leveltest['total_cnt'] == $cheked_leveltest['attendance_cnt'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0607";
                $return_array['data']['err_msg'] = "레벨테스트는 1회만 신청 가능합니다. 재평가는 MSET 시험으로 신청해 주세요.";
                echo json_encode($return_array);
                exit;
            }

            /*  change_restriction_cnt : 변경 불가능한 (시작 30분전) 레벨테스트 갯수 */
            if($cheked_leveltest['change_restriction_cnt'] > 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0608";
                $return_array['data']['err_msg'] = "레벨테스트 시작 30분 전에는 스케줄을 변경할 수 없습니다.";
                echo json_encode($return_array);
                exit;
            }
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "레벨테스트 진행 가능";
        echo json_encode($return_array);
        exit;

    }

}








