<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Lesson extends _Base_Controller {


    public function __construct()
    {
        parent::__construct();

        $this->load->library('form_validation');

    }
    public function checked_holiday_leveltest()
    {
        $retrun_array = array();

        $this->load->model('lesson_mdl');

        $today = date('Y-m-d');
        $day_after = date("Y-m-d", strtotime("+3 days"));

        /* '2020-12-24', '2020-12-25', '2020-12-31', '2021-01-01' 예외 처리 */
        $where = "WHERE whc.disabled_thunder = '1' AND whc.holiday >= '".$today."' AND  whc.holiday <= '".$day_after."' AND whc.holiday NOT IN ('2020-12-24', '2020-12-25', '2020-12-31', '2021-01-01')";

        $holiday = $this->lesson_mdl->checked_holiday($where);

        if($holiday)
        {
            $retrun_array['res_code'] = '0900';
            $retrun_array['msg'] = "레벨테스트가 불가능한 날짜가 있습니다.";
            $retrun_array['holiday'] = $holiday;
            echo json_encode($retrun_array);
            exit;
        }

        $retrun_array['res_code'] = '0000';
        $retrun_array['msg'] = "레벨테스트가 불가능한 날짜가 없습니다.";
        echo json_encode($retrun_array);
        exit;
        
    }

    public function class_list_()
    {
        
        $retrun_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => $this->input->post('wiz_id'),
        );

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

        $this->load->model('lesson_mdl');
        $class = $this->lesson_mdl->class_list($wiz_member['wm_uid']);
    
        if($class)
        {
            $retrun_array['res_code'] = '0000';
            $retrun_array['msg'] = "";
            $retrun_array['list'] = $class;
        }
        else
        {
            $retrun_array['res_code'] = '0900';
            $retrun_array['msg'] = "출석부가 없습니다.";
        }

        echo json_encode($retrun_array);
        exit;
    }

    // 출석부 정보
    public function info()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "lesson_id" => trim($this->input->post('lesson_id')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('lesson_mdl');

        $lesson_info = lesson_info($request['lesson_id'], $wiz_member['wm_uid']);
        $lesson = $lesson_info['lesson'];

        if($lesson)
        {        
            $lesson_stats = lesson_progress_rate($lesson);
            $lesson['cl_time_real'] = lesson_replace_cl_name_minute($lesson['wl_cl_time'], $lesson['wl_lesson_gubun']);
            
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "";
            $return_array['data']['info'] = $lesson;
            $return_array['data']['stats'] = $lesson_stats;
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
        }
        
        echo json_encode($return_array);
        exit;
    }
    
    // 출석부 리스트. 장기연기 관련 데이터 포함
    public function class_list_postpone()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id"        => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization'))
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('lesson_mdl');
        
        $lesson = lesson_postpone_list($wiz_member);
        if(!$lesson)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        //설문조사를 위한 정보(상세사유) 가져오기
        $reason = $this->lesson_mdl->get_postpone_survey_reason();

        $return_array['res_code']       = '0000';
        $return_array['msg']            = "출석부 리스트를 불러왔습니다.";
        $return_array['data']['list']   = $lesson;
        $return_array['data']['survey'] = $reason;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 장기 연기 신청 처리, 설문조사 등록
     */
    public function class_postpone_apply()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id"        => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "lesson_id"     => trim($this->input->post('lesson_id')),
            "reason_kind"   => trim($this->input->post('reason_kind')),
            "reason_detail" => trim($this->input->post('reason_detail')),
            "use_point"     => trim($this->input->post('use_point')),
            "is_app"        => trim($this->input->post('is_app')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('lesson_mdl');
        $this->load->model('member_mdl');

        if($request['use_point'] > 0)
        {
            // 포인트 보유 여부 체크
            if($wiz_member['wm_point'] < $_POST['changeUsePoint']){
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg']  = "포인트가 부족하여 장기 연기 신청 할 수 없습니다.";
                echo json_encode($return_array);
                exit;
            }
        }

        //로그 남기기
        $log_content = "[장기연기신청 ".$request['is_app']."] LESSON_ID:".$request['lesson_id'];
        $log = array(
            'uid'     => $wiz_member['wm_uid'],
            'type'    => 'front_postpone_'.($request['is_app']=='M' ? 'mobile' : 'pc'),
            'content' => $log_content,
            'regdate' => date("Y-m-d H:i:s")
        );
        $this->member_mdl->insert_mint_log($log);

        //수업정보 가져오기
        $lesson = $this->lesson_mdl->row_wiz_lesson_by_lesson_id($request['lesson_id'], $wiz_member['wm_uid']);
        if(!$lesson)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "일치하는 수업정보를 찾을 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        //대기중인 수업 목록 가져오기
        //20분 전 수업일경우 안보이게
        $sdate = date('Y-m-d H:i:s', time() + 1200);
        $schedule = $this->lesson_mdl->get_present_schedule_list($request['lesson_id'], $sdate, '1');
        $schedule_num = $schedule ? count($schedule) : 0;
        
        //장기 연기 등록
        if($lesson['wl_cl_gubun'] == '2')
        {
            $free_sc_data = lesson_check_freedomclass_cnt($lesson);
            if (!$free_sc_data)
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg']  = "출석부가 유효하지 않습니다.";
                echo json_encode($return_array);
                exit;
            }
    
            if($free_sc_data['remain_class_total_cnt'] + $schedule_num < 1)
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg']  = "변경할 스케쥴이 없습니다.";
                echo json_encode($return_array);
                exit;
            }

            // 배정된 수업있으면 삭제
            if($schedule_num > 0)
            {
                $this->lesson_mdl->delete_assign_schedule($request['lesson_id'], $sdate);
            }

            // 배정된,대기상태에서 대기상태 갯수 제외, 배정했지만 20분이후꺼도 제외
            // 남은 수업은 20분 내로 지정되있는수업
            $tt1       = $free_sc_data['remain_class_total_with_ready_cnt'] - $free_sc_data['remain_class_total_cnt'] - $schedule_num;
            $remain_sc = $free_sc_data['remain_class_total_with_ready_cnt'] - $tt1;

            $count_schedule = $this->lesson_mdl->get_count_hyugang_dangi_schedule($request['lesson_id']);

            $params = array(
                'tu_uid'           => '158',
                'tu_name'          => 'postpone',
                'lesson_state'     => 'holding',
                'tt_1'             => $tt1,
                'tt_5'             => $count_schedule['hyugang'],
                'tt_6'             => $count_schedule['dangi'],
                'tt_7'             => $remain_sc,
                'tt_holding_count' => $lesson['wl_tt_holding_count'] + 1,
            );
            $this->lesson_mdl->update_wiz_lesson($request['lesson_id'], $params);
            
            //스케줄 변경 로그 남기기
            $queryParam = array(
                'lesson_id' => $request['lesson_id'],
                'cl_time'   => $lesson['wl_cl_time'],
                'startday'  => $lesson['wl_startday'],
                'endday'    => $lesson['wl_endday'],
                'regdate'   => date("Y-m-d H:i:s"),
                'kind'      => 'u',
                'man_id'    => $wiz_member['wm_wiz_id'],
                'man_name'  => $wiz_member['wm_name'],
                'class_su'  => $remain_sc
            );
            $content = lesson_schedule_present(1).' --> '.lesson_schedule_present(7); //대기중인 수업을 장기연기로
            $this->lesson_mdl->insert_wiz_tutor_change($queryParam,$content);
        }
        else
        {
            if($schedule_num < 1)
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg']  = "변경할 스케쥴이 없습니다.";
                echo json_encode($return_array);
                exit;
            }
		    $tt1 = $lesson['wl_tt_1'] - $schedule_num;

            //홀딩상태의 스케줄을 조사하여 READY 로 바꾸면서 개수가 중복되지 않게 정리
            $i = 0;
            $hold_schedule = $this->lesson_mdl->get_present_schedule_list($request['lesson_id'], $sdate, '5,6');
            if($hold_schedule)
            {
                foreach($hold_schedule as $row)
                {
                    //선생님/학생홀딩에 의한 상태를 READY 로 변경
                    $params = array(
                        'present' => '1'
                    );
                    $this->lesson_mdl->update_wiz_schedule($row['sc_id'], $params);

                    //맨 뒤에서부터 READY인 상태의 스케줄을 하나 삭제한다(개수 맞추기 위함)
                    $delete_schedule = $this->lesson_mdl->delete_wiz_schedule($schedule[$i]['sc_id']);
                    if($delete_schedule) $i++;
                }
            }

            //삭제 되지않은 READY상태의 스케줄을 장기연기 처리
            $ready_schedule_num = $schedule_num - $i;
            for($i;$i<$ready_schedule_num;$i++)
            {
                $params = array(
                    'tu_uid'  => '158',
                    'tu_name' => 'postpone',
                    'present' => '7'
                );
                $this->lesson_mdl->update_wiz_schedule($schedule[$i]['sc_id'], $params);
            }

            $not_hold_schedule = $this->lesson_mdl->get_not_hold_schedule_startday_endday($request['lesson_id']);
            $count_schedule    = $this->lesson_mdl->get_count_hyugang_dangi_schedule($request['lesson_id']);

            $params = array(
                'tu_uid'           => '158',
                'tu_name'          => 'postpone',
                'lesson_state'     => 'holding',
                'tt_1'             => $tt1,
                'tt_5'             => $count_schedule['hyugang'],
                'tt_6'             => $count_schedule['dangi'],
                'tt_7'             => $schedule_num,
                'tt_holding_count' => $lesson['wl_tt_holding_count'] + 1,
                'startday'         => $not_hold_schedule['startday'],
                'endday'           => $not_hold_schedule['endday'],
            );
            $this->lesson_mdl->update_wiz_lesson($request['lesson_id'], $params);
            
            //스케줄 변경 로그 남기기
            $queryParam = array(
                'lesson_id' => $request['lesson_id'],
                'a_tutor'   =>  $lesson['wl_tu_name'],
                'b_tutor'   => "Postpone",
                'a_time'    => date("H:i:s",$lesson['wl_stime']),
                'b_time'    => date("H:i:s",$lesson['wl_stime']),
                'cl_time'   => $lesson['wl_cl_time'],
                'startday'  => date("Y-m-d"),
                'endday'    => $lesson['wl_endday'],
                'man_id'    => $wiz_member['wm_wiz_id'],
                'man_name'  => $wiz_member['wm_name'],
                'regdate'   => date('Y-m-d H:i:s'),
                'kind'      => 'u',
                'class_su'  => $schedule_num
            );
            $content = lesson_schedule_present(1).' --> '.lesson_schedule_present(7); //대기중인 수업을 장기연기로
            $this->lesson_mdl->insert_wiz_tutor_change($queryParam,$content);
        }

        $nowTime = time();
        $Time120 = $nowTime-(3600*120);
        $long_schedule = $this->lesson_mdl->get_wiz_long_schedule($wiz_member['wm_uid'], $request['lesson_id'], $Time120, $nowTime);
        $long_cnt = $long_schedule ? $long_schedule['long_cnt'] : 0;

        //포인트 사용
        if($request['use_point'] > 0)
        {
            $changeUsePoint = ($request['use_point'] * -1);
            $point = array(
                'uid'     => $wiz_member['wm_uid'],
                'name'    => $wiz_member['wm_name'],
                'point'   => $changeUsePoint,
                'pt_name' => '수업장기연기 신청 / 수업재개 신청', 
                'kind'    => 'j', 
                'b_kind'  => 'service',
                'showYn'  => 'y',
                'regdate' => date("Y-m-d H:i:s")
            );

            /* 포인트 내역 입력 및 포인트 추가 */
            $this->load->model('point_mdl');
            $this->point_mdl->set_wiz_point($point);
            $long_cnt = $long_cnt + 1;

            $long_schedule_data = array(
                'uid'       => $wiz_member['wm_uid'],
                'lesson_id' => $request['lesson_id'],
                'long_cnt'  => $long_cnt,
                'startTime' => $long_schedule['startTime'],
                'regdate'   => date("Y-m-d H:i:s")
            );
            $this->lesson_mdl->insert_wiz_long_schedule($long_schedule_data);
        }

        //설문조사 등록
        $params = array(
            'uid'              => $wiz_member['wm_uid'],
            'count'            => $long_cnt,
            'reason_id'        => $request['reason_kind'],
            'detail'           => $request['reason_detail'],
            'first_class_date' => $lesson['wl_startday'].' '.date("H:i:s",$lesson['wl_stime']),
            'regdate'          => date('Y-m-d H:i:s')
        );
        $this->lesson_mdl->insert_mint_postpone_survey($params);

        //알림톡 전송
        if($wiz_member['wm_mobile'])
        {
            $class_name  = ($lesson['wl_cl_label'] != "") ? "(".$lesson['wl_cl_label'].") " : "";
            $class_name .= ($lesson['wl_cl_name2'] != "") ? $lesson['wl_cl_name2'] : $lesson['wl_cl_name'];
            $class_name .= " [".$lesson['wl_startday']."~".$lesson['wl_endday']."]";

            $options = array(
                'name'        => $wiz_member['wm_name'],
                'class_name'  => $class_name
            );
            $send_atalk = sms::send_atalk($wiz_member['wm_mobile'], 'MINT06002Q', $options);
        }

        //푸시 전송
        AppPush::send_push($wiz_member['wm_uid'], '1405');

        $return_array['res_code'] = '0000';
        $return_array['msg']      = "장기 연기 신청이 완료되었습니다.";
        echo json_encode($return_array);
        exit;
    }


    /**
     * 피드백 정보 가져오기
     */
    public function feedback_info()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id"        => trim($this->input->post('wiz_id')), //필수
            "authorization" => trim($this->input->post('authorization')), //필수
            "sc_id"         => trim($this->input->post('no')), //필수
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('lesson_mdl');

        $feedback = $this->lesson_mdl->feedback_info($request['sc_id'], $wiz_member['wm_uid']);
        if(!$feedback)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "일치하는 수업 정보를 찾을수 없습니다.";
        }

        /*
            피드백 페이지가 리뉴얼 되었다.
            기존에는 wsr_pronunciation 에 text데이터가 들어가지만
            변경 후에는 json 데이터가 들어간다.
            기존 데이터는 임의로 쪼갤수가 없어서 기존형태와 리뉴얼 형태가 공존하게 된다.
        */
        $new_feedback = true;
        if($feedback['wsr_pronunciation'])
        {
            $pron_json_check = json_decode($feedback['wsr_pronunciation'], true);
            if($pron_json_check)
            {
                $feedback['wsr_pronunciation'] = array();
                foreach($pron_json_check as $name=>$array)
                {
                    foreach($array as $key=>$value)
                    {
                        if($name == 'great_job' || $name == 'need_to_improve')
                            $feedback['wsr_pronunciation'][$name][$key]['value'] = stripslashes(common_input_out($value['value']));
                        else
                            $feedback['wsr_pronunciation'][$name][$key]['value'] = nl2br(stripslashes(common_input_out($value['value'])));
                    }
                }
            }
            else
            {
                $new_feedback = false;
                $feedback['wsr_pronunciation'] = nl2br(stripslashes(common_input_out($feedback['wsr_pronunciation'])));
            }
        }

        //출력 데이터 변환
        $feedback['wsr_comment'] = $feedback['wsr_comment'] ? nl2br(stripslashes(common_input_out($feedback['wsr_comment']))) : '숙제가 없습니다.';
        $feedback['wsr_grammar'] = nl2br(stripslashes(common_input_out($feedback['wsr_grammar'])));

        $this->load->model('tutor_mdl');
        // 강사 정보
        $tutor = $this->tutor_mdl->get_tutor_info_by_tu_uid($feedback['ws_tu_uid']);
        $tutor['t_star'] = $this->tutor_mdl->row_tutor_star_log($feedback['ws_tu_uid']);

        $where = "sc_id != ".$request['sc_id']." AND lesson_id = ".$feedback['ws_lesson_id']." AND present NOT IN (7,8,9)";
        //이전수업 정보
        $prev_where = $where." AND startday < '".$feedback['ws_startday']."' ORDER BY startday DESC";
        $feedback['prev'] = $this->tutor_mdl->prev_next_schedule($prev_where);
        //다음수업 정보
        $next_where = $where." AND startday > '".$feedback['ws_startday']."' ORDER BY startday ASC";
        $feedback['next'] = $this->tutor_mdl->prev_next_schedule($next_where);

        //녹화파일 조회
        if($feedback['ws_lesson_gubun'] == "T")      $call_tel = $feedback['ws_tel'];
        else if($feedback['ws_lesson_gubun'] == "M") $call_tel = $feedback['ws_mobile'];
        $call_tel2 = $call_tel;
        $call_tel = str_replace("-", "",$call_tel);
        $param = array(
            'date'   => substr($feedback['ws_startday'],0,10),
            'tel'    => $call_tel,
            'tel2'   => $call_tel2,
            'sc_id'  => $request['sc_id'],
            'wm_uid' => $feedback['wm_uid']
        );
        

        $record_list = DialComm::record_list($feedback['ws_lesson_gubun'], $param);
        
        $return_array['res_code']             = '0000';
        $return_array['msg']                  = "피드백 정보를 불러왔습니다.";
        $return_array['data']['info']         = $feedback;
        $return_array['data']['new_feedback'] = $new_feedback;
        $return_array['data']['tutor_info']   = $tutor;
        $return_array['data']['record_list']  = $record_list;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 종료된 강의 리스트
     */
    public function lesson_finish_list_()
    {
        $return_array = array();

        $request = array(
            'start'         => $this->input->post('start') ? $this->input->post('start') : 0,
            'limit'         => $this->input->post('limit') ? $this->input->post('limit') : 10,
            "authorization" => trim($this->input->post('authorization')), //필수
            "wiz_id"        => trim(strtolower($this->input->post('wiz_id'))), //필수
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $now = date("Y-m-d");
        $where1 = "WHERE wl.student_uid LIKE '%,".$wiz_member['wm_uid'].",%' AND wl.schedule_ok='Y' AND '".$now."' >= wl.endday AND wl.tt_7=0";
        $where2 = "WHERE wl.uid = '".$wiz_member['wm_uid']."' AND wl.schedule_ok='Y' AND '".$now."' >= wl.endday AND wl.tt_7=0";

        $this->load->model('lesson_mdl');
        $list_cnt = $this->lesson_mdl->list_lesson_finish_count($where1, $where2);
        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "종료된 강의 목록이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $limit = "";

        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = "ORDER BY wl_lesson_id DESC";

        $list = $this->lesson_mdl->list_lesson_finish($where1, $where2, $order, $limit);
        if(!$list)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "종료된 강의 목록이 없습니다.(2)";
            echo json_encode($return_array);
            exit;
        }

        foreach($list as $key=>$val)
        {
            $lesson_stats = lesson_progress_rate($val);

            $list[$key]['att_rate']       = $lesson_stats['att_rate']; //출석률
            $list[$key]['lesson_total_m'] = $lesson_stats['lesson_total_m']; //총 수업일

            //현재 수업 상태
            $ing = "";
            if($val['wl_refund_ok']=="Y")      $ing = "환불";
            else if($val['wl_refund_ok']=="C") $ing = "환불취소";
            else
            {
                $SD = explode("-",$val['wl_startday']);
                $ED = explode("-",$val['wl_endday']);
                $SMK = mktime(0,0,0,$SD[1],$SD[2],$SD[0]);
                $EMK = mktime(23,59,59,$ED[1],$ED[2],$ED[0]);
                $TMK = time();
                if($TMK >= $SMK && $TMK < $EMK) $ing = "수업중";
                else if($TMK < $SMK)            $ing = "수업대기";
                else                            $ing = "수업종료";
            }
            $list[$key]['lesson_state'] = $ing;

            /**
             * 개근상 수령 표시 여부
             * 딜러설정 개근상후보표시 Y일때
             * 종료일 당일에는 출력되지 않음
             */
            $list[$key]['gaegeun_view']   = false;
            if($lesson_stats['prog_rate_m'] == "100"
            && $val['wl_tt_3'] == "0" && $val['wl_tt_3_1'] == "0" && $val['wl_tt_4'] == "0"
            && $val['wl_tt_6'] == "0" && $val['wl_tt_7'] == "0" && $val['wl_tt_holding_count'] == "0"
            && $wiz_member['wd_gaegeun_yn'] == 'Y' && $val['wl_endday'] != date("Y-m-d"))
            {
                $list[$key]['gaegeun_view'] = true;
            }
        }

        $return_array['res_code']          = '0000';
        $return_array['msg']               = "종료된 강의 목록을 정상적으로 불러왔습니다.";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list']      = $list;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 개근상 포인트 얻기
     */
    public function get_all_clear_point()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "wiz_id"        => trim(strtolower($this->input->post('wiz_id'))), //필수
            "lesson_id"     => trim(strtolower($this->input->post('lesson_id'))), //필수
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $result = set_give_all_clear_point($wiz_member, $request['lesson_id']);
        $return_array['res_code'] = '0000';
        $return_array['msg']      = $result['msg'];
        echo json_encode($return_array);
        exit;
    }

    /**
     * 수강평가표 상세보기
     */
    public function lesson_evaluation()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "wiz_id"        => trim(strtolower($this->input->post('wiz_id'))), //필수
            "re_id"         => trim(strtolower($this->input->post('re_id'))), //필수
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('lesson_mdl');
        $article = $this->lesson_mdl->lesson_evaluation_article($request['re_id']);
        if(!$article)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "수강평가표가 없습니다";
            echo json_encode($return_array);
            exit;
        }

        $where = "uid = '".$wiz_member['wm_uid']."' AND re_id != '".$article['wr_re_id']."'";
        //이전 수강평가표 정보
        $prev_where = $where." AND re_id < '".$article['wr_re_id']."' AND lesson_id = '{$article['wl_lesson_id']}' ORDER BY re_id DESC";
        $article['prev'] = $this->lesson_mdl->prev_next_evaluation($prev_where);
        //다음 수강평가표 정보
        $next_where = $where." AND re_id > '".$article['wr_re_id']."' AND lesson_id = '{$article['wl_lesson_id']}' ORDER BY re_id ASC";
        $article['next'] = $this->lesson_mdl->prev_next_evaluation($next_where);

        //수업 통계 카운트
        $lesson_stats = lesson_progress_rate($article);
        $article['att_rate']   = $lesson_stats['att_rate'];
        $article['lesson_off'] = $lesson_stats['lesson_off'];

        $now = substr($article['wl_startday'],0,10);
        
        //민트영어 회원 평균 레벨
        $startday = date("Y-m-d", strtotime($now." -1 day"));
        $average_level = $this->lesson_mdl->get_average_level_to_feedback_stat($startday);
        if($average_level > 0)
        {
            $average_level['pro_avg_length'] = $average_level['pro_avg'] * 14.28;
        
            $average_level['voc_avg_length'] = $average_level['voc_avg'] * 14.28;
        
            $average_level['ss_avg_length'] = $average_level['ss_avg'] * 14.28;
        
            $average_level['ls_avg_length'] = $average_level['ls_avg'] * 14.28;
        
            $average_level['cg_avg_length'] = $average_level['cg_avg'] * 14.28;

            $article['mint_stat'] = $average_level;
        }

        $rating_avg = ($article['wr_pronunciation']+$article['wr_vocabulary']+$article['wr_speaking']+$article['wr_listening']+$article['wr_grammar']) / 5;
        switch ($rating_avg)
        {
            case ($rating_avg >=1 && $rating_avg <2) : $article['averge_level'] = "1-2";break;
            case ($rating_avg >=2 && $rating_avg <3) : $article['averge_level'] = "2-3";break;
            case ($rating_avg >=3 && $rating_avg <4) : $article['averge_level'] = "3-4";break;
            case ($rating_avg >=4 && $rating_avg <5) : $article['averge_level'] = "4-5";break;
            case ($rating_avg >=5 && $rating_avg <6) : $article['averge_level'] = "5-6";break;
            case ($rating_avg >=6 && $rating_avg <7) : $article['averge_level'] = "6-7";break;
            case ($rating_avg >=7)                   : $article['averge_level'] = "7";break;
            default                                  : $article['averge_level'] = "1";break;
        }

        //출력 데이터 변환
        $article['wl_stime']    = date("H:i",$article['wl_stime']);
        $article['wr_ev_memo']  = nl2br(stripslashes(common_input_out($article['wr_ev_memo'])));
        $article['wr_gra_memo'] = nl2br(stripslashes(common_input_out($article['wr_gra_memo'])));

        $return_array['res_code']     = '0000';
        $return_array['msg']          = "수강평가표를 정상적으로 불러왔습니다.";
        $return_array['data']['info'] = $article;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 수강평가표 리스트
     */
    public function lesson_evaluation_list_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "wiz_id"        => trim(strtolower($this->input->post('wiz_id'))), //필수
            "lesson_id"     => trim(strtolower($this->input->post('lesson_id'))), //필수
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('lesson_mdl');
        $where = " WHERE wr.lesson_id = '".$request['lesson_id']."'";
        $order = " ORDER BY wr.re_id DESC";
        $list = $this->lesson_mdl->list_report($where, $order, '');
        if(!$list)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "수강평가표 목록이 없습니다";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code']     = '0000';
        $return_array['msg']          = "수강평가표 목록을 정상적으로 불러왔습니다.";
        $return_array['data']['list'] = $list;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 지난강의 내역 리스트
     */
    public function past_lesson_schedule_list_()
    {
        $return_array = array();

        $request = array(
            "start"         => $this->input->post('start') ? $this->input->post('start') : 0,
            "limit"         => $this->input->post('limit') ? $this->input->post('limit') : 10,
            "authorization" => trim($this->input->post('authorization')), //필수
            "wiz_id"        => trim(strtolower($this->input->post('wiz_id'))), //필수
            "lesson_id"     => trim(strtolower($this->input->post('lesson_id'))), //필수
            "present"       => trim(strtolower($this->input->post('present'))),
            "year"          => trim(strtolower($this->input->post('year'))),
            "month"         => trim(strtolower($this->input->post('month'))),
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $where = "WHERE ws.lesson_id = '".$request['lesson_id']."' AND ws.present NOT IN (8,9) ";
        //검색 처리
        if($request['present']) $where .= " AND ws.present = '".$request['present']."' ";
        if($request['year'])    $where .= " AND DATE_FORMAT(ws.startday, '%Y') = '".$request['year']."' ";
        if($request['month'])   $where .= " AND DATE_FORMAT(ws.startday, '%m') = '".$request['month']."' ";

        $this->load->model('lesson_mdl');
        $list_cnt = $this->lesson_mdl->list_past_lesson_schedule_count($where);
        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "지난 강의 목록이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $limit = "";

        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = "ORDER BY ws.startday DESC";

        $list = $this->lesson_mdl->list_past_lesson_schedule($where, $order, $limit);
        if(!$list)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "지난 강의 목록이 없습니다.(2)";
            echo json_encode($return_array);
            exit;
        }

        $month_ago = strtotime(date('Y-m-d', strtotime("-1 month")));
        foreach($list as $key=>$value)
        {
            // endday 1초 추가 - 59초로 끝나기 때문
            $list[$key]['ws_endday'] = date("Y-m-d H:i:s", strtotime("+1 seconds", strtotime($value['ws_endday'])));

            if($value['ws_present'] == 2)
            {
                $startday_time = strtotime(date('Y-m-d', strtotime($value['ws_startday'])));

                //수업과 연결된 얼철딕
                $where = "WHERE wsbp.uid = '".$wiz_member['wm_uid']."' AND wsbp.schedule_id = '".$value['ws_sc_id']."' AND wsbp.table_code = '9002' ";
                $order = "";
                $dictation = $this->lesson_mdl->get_wiz_schedule_board_pivot($where, $order);
                $list[$key]['dictation']['id'] = $dictation ? $dictation['wsbp_board_id'] : null;
                $list[$key]['dictation']['view'] = $dictation ? true : (($startday_time >= $month_ago) ? true : false);

                //수업과 연결된 수업대본
                $where = "WHERE wsbp.uid = '".$wiz_member['wm_uid']."' AND wsbp.schedule_id = '".$value['ws_sc_id']."' AND wsbp.table_code = '1130' ";
                $order = "ORDER BY wsbp.created_at DESC LIMIT 1";
                $script = $this->lesson_mdl->get_wiz_schedule_board_pivot($where, $order);
                $list[$key]['script']['id'] = $script ? $script['wsbp_board_id'] : null;
                $list[$key]['script']['view'] = $script ? true : (($startday_time >= $month_ago) ? true : false);
            }
        }

        $return_array['res_code']          = '0000';
        $return_array['msg']               = "지난 강의 목록을 정상적으로 불러왔습니다.";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list']      = $list;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 수업 정보 가져오기
     */
    public function lesson_article()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "wiz_id"        => trim(strtolower($this->input->post('wiz_id'))), //필수
            "lesson_id"     => trim(strtolower($this->input->post('lesson_id'))), //필수
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        //수업정보 가져오기
        $this->load->model('lesson_mdl');
        $lesson = $this->lesson_mdl->row_wiz_lesson_by_lesson_id($request['lesson_id'], $wiz_member['wm_uid']);
        if(!$lesson)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "일치하는 수업정보를 찾을 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        //수업 통계 카운트
        $lesson_stats = lesson_progress_rate($lesson);
        $lesson['att_rate']       = $lesson_stats['att_rate'];
        $lesson['lesson_off']     = $lesson_stats['lesson_off'];
        $lesson['lesson_total_m'] = $lesson_stats['lesson_total_m'];
        $lesson['lesson_rest_m']  = $lesson_stats['lesson_rest_m'];
        $lesson['prog_rate_m']    = $lesson_stats['prog_rate_m'];

        //수강 시작일 ~ 종료일 사이의 날짜를 모두 구한다
        $dates = array();
        $period = new DatePeriod( new DateTime($lesson['wl_startday']), new DateInterval('P1M'), new DateTime($lesson['wl_endday']." +1 month"));
        foreach ($period as $date) $dates[$date->format("Y")][] = $date->format("m");

        $return_array['res_code']     = '0000';
        $return_array['msg']          = "수업 정보를 정상적으로 불러왔습니다.";
        $return_array['data']['info'] = $lesson;
        $return_array['data']['date'] = $dates;
        echo json_encode($return_array);
        exit;
    }

    //자유수업 출석부 시작
    public function start_free_lesson_class()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "lesson_id" => trim($this->input->post('lesson_id')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('point_mdl');
        $this->load->model('lesson_mdl');

        $lesson = lesson_info($request['lesson_id'], $wiz_member['wm_uid']);

        if($lesson === false)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0805';
            $return_array['data']['err_msg'] = '해당 출석부가 존재하지 않습니다';
            echo json_encode($return_array);
            exit;
        }

        $free_sc_data = $lesson['free_sc_data'];
        $lesson = $lesson['lesson'];

        if($lesson['wl_lesson_state']=='')
        {
            $tu_uid = 0;
            $tu_name = '';
            $today = date('Y-m-d');
    
            // 무조건 돌아오는 월요일에 시작되도록 해야한다.
            // 오늘이 월요일이면 오늘 시작
            if(date('w') == '1')
            {
                $startday = date('Y-m-d');
            }
            else
            {
                $this_startday = $today;
                for($i=0;$i<8;$i++)
                {
                    // 오늘부터 하루씩 증가시켜서 월요일인지 확인
                    if(date('w',strtotime($this_startday)) == 1) break;
                    else $this_startday = date('Y-m-d', strtotime('+'.$i.' day'));
                }
    
                $startday = $this_startday;
            }
            
            //종료일 구하기
            $endday = lesson_check_freedomclass_endday($lesson, $startday);

            $weekend = '';
            if($lesson['wl_cl_number'] == '2'){
                $weekend = ':Y::Y:::';
            }
            elseif($lesson['wl_cl_number'] == '3'){
                $weekend = 'Y::Y::Y::';
            }
            elseif($lesson['wl_cl_number'] == '5'){
                $weekend = 'Y:Y:Y:Y:Y::';
            }

            $lev_id = 0;
		    $lev_name = '';

            $l_param = [
                'tu_uid'        => $tu_uid,
                'tu_name'       => $tu_name,
                'lev_id'        => $lev_id,
                'lev_name'      => $lev_name,
                'weekend'       => $weekend,
                'cl_gubun'      => '2',
                'stime'         => time(),
                'lesson_state'  => 'in class',
                'tt'            => $lesson['wl_cl_class'],
                'tt_1'          => $lesson['wl_cl_class'],
                'startday'      => $startday,
                'endday'        => $endday,
                'man_id'        => $wiz_member['wm_wiz_id'],
                'man_name'      => $wiz_member['wm_name'],
                'plandate'      => date('Y-m-d H:i:s'),
                'stime2'        => '',
                'schedule_ok'   => 'Y',
            ];
            $this->lesson_mdl->update_wiz_lesson($request['lesson_id'], $l_param);

            lesson_set_conti($wiz_member['wm_uid']);

            //포인트 지급
            $point_row = $this->point_mdl->check_point_received_by_lesson_id($request['lesson_id'], $wiz_member['wm_uid'], 'c');

            if($point_row)
            {
                if($point_row['showYn'] =='n')
                {
                    $update_param = [
                        'showYn' =>'y'
                    ];

                    $where = [
                        'pt_id' => $point_row['pt_id'],
                    ];

                    $this->point_mdl->update_wiz_point($update_param, $where);
                }
            }
            else
            {
                if($lesson['wl_fee'] >= 10000 && $lesson['wl_payment']!='coupon:') 
                {
                    $point = array(
                        'uid'     => $wiz_member['wm_uid'],
                        'name'    => $wiz_member['wm_name'],
                        'point'   => (int)$lesson['wl_fee'] / 10,
                        'pt_name' => '직접 수업 등록(자유수업) 10% 적립 이벤트', 
                        'kind'    => 'c', 
                        'lesson_id'  => $request['lesson_id'],
                        'showYn'  => 'y',
                        'regdate' => date("Y-m-d H:i:s")
                    );
        
                    /* 포인트 내역 입력 및 포인트 추가 */
                    $this->point_mdl->set_wiz_point($point);
                }
            }

            //강사변동로그
            $queryParam = array(
                'lesson_id' => $request['lesson_id'],
                'a_tuid' => '',
                'b_tuid' => '',
                'a_tutor' => '',
                'b_tutor' => '',
                'a_time' => date("H:i",$lesson['wl_stime']),
                'b_time' => '09:00',                        // 임의로 넣은 자유수업 시작시간. 의미없는 수치이다
                'cl_time'   => $lesson['wl_cl_time'],
                'startday'  => $startday,
                'endday'    => $endday,
                'regdate'   => date("Y-m-d H:i:s"),
                'kind'      => 'r',
                'man_id'    => $wiz_member['wm_wiz_id'],
                'man_name'  => $wiz_member['wm_name'],
                'class_su'  => $lesson['wl_cl_class']
            );
            $content = '직접 수업 등록(자유수업)';
            $this->lesson_mdl->insert_wiz_tutor_change($queryParam,$content);

            //레벨테스트 인센티브
            tutor_leveltest_incentive($wiz_member);

            //sms전송
            if($lesson['wl_lesson_gubun'] =='V') $sms_templete_code = '326';
            elseif($lesson['wl_lesson_gubun'] =='E') $sms_templete_code = '332';
            else $sms_templete_code = '325';
            
            $options = array(
                'uid'       => $wiz_member['wm_uid'], 
                'wiz_id'    => $wiz_member['wm_wiz_id'], 
                'user_name' => $wiz_member['wm_name'], 
                'name'      => $wiz_member['wm_name'], 
                'cl_number' => $lesson['wl_cl_number'], 
                'cl_time'   => lesson_replace_cl_name_minute($lesson['wl_cl_time'], $lesson['wl_lesson_gubun'], true), 
                'cl_month'  => $lesson['wl_cl_month'], 
                'week_name' => '',
                'date'      => $startday,
            );

            sms::send_sms($wiz_member['wm_mobile'], $sms_templete_code, $options);
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0807';
            $return_array['data']['err_msg'] = '이미 자유수업이 활성화가 되었습니다.';
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";

        echo json_encode($return_array);
        exit;
    }

    
    //수업연장처리
    public function extend_class()
    {
        $return_array = array();    

        $request = array(    
            "code" => trim($this->input->post('code')),
            "anwser" => trim($this->input->post('anwser')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('lesson_mdl');
        $this->load->model('member_mdl');
        $this->load->model('tutor_mdl');

        $wce_idx = (new OldEncrypt('(*&DHajaan=f0#)2'))->decrypt($request['code']);
        $extend = $this->lesson_mdl->row_class_extension_by_idx($wce_idx);

        if(!$extend)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0201';
            $return_array['data']['err_msg'] = '등록된 데이터가 없습니다.';
            echo json_encode($return_array);
            exit;
        }

        $wiz_member = $this->member_mdl->get_wiz_member_by_wm_uid($extend['uid']);
        $schedule = $this->lesson_mdl->row_schedule_by_sc_id($extend['sc_id'], $extend['uid']);

        $lesson_info = lesson_info($schedule['lesson_id'], $schedule['uid']);
        $free_sc_data = $lesson_info['free_sc_data'];
        $lesson = $lesson_info['lesson'];

        $tutor = $this->tutor_mdl->get_tutor_info_by_tu_uid($schedule['tu_uid']);
        
        //mel수업중이였으면 되돌아가기 위해 링크 구해서 같이 리턴
        $mel_link = '';
        if($schedule['lesson_gubun'] =='E')
        {
            $this->load->model('webrtc_mdl');
            $mel_link = $this->webrtc_mdl->checked_classroom_invitation_by_sc_id($schedule['uid'], $schedule['sc_id']);
            $mel_link = $mel_link ? $mel_link['invitational_url']:'';
        }

        $class_info = array(
            'startday'      => $schedule['startday'],
            'tu_name'        => $schedule['tu_name'],
            'tu_pic'        => $tutor['tu_pic_main'] ? ('https://cdn.mintspeaking.com/edu/tutor/tu_main/'.$tutor['tu_pic_main']):'',
            'cl_time'       => $schedule['cl_time'],
            'remain_class'  => $lesson['wl_tt_1'],
            'cl_name'       => $lesson['wl_cl_name'],
        );

        //공통 리턴데이터
        $return_array['data']['wm_uid'] = $extend['uid'];
        $return_array['data']['info'] = $class_info;
        $return_array['data']['mel_link'] = $mel_link;

        if($extend['approval_date'] || $extend['is_deny'] =='1')
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "이미 ".($extend['is_deny'] ? '거절':'승인')." 처리되었습니다.";
            $return_array['data']['is_deny'] = $extend['is_deny'] ? 1:0;
            echo json_encode($return_array);
            exit;
        }

        //연장 거절 시 업뎃하고 끝
        if($request['anwser'] =='N')
        {
            $this->lesson_mdl->update_wiz_class_extension($extend['idx'], [
                'is_deny' => 1,
            ]);

            $return_array['res_code'] = '0000';
            $return_array['msg'] = "수업연장을 거절하였습니다.";
            $return_array['data']['is_deny'] = 1;
            echo json_encode($return_array);
            exit;
        }

        if($extend['limit_date'] < date('Y-m-d H:i:s'))
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0814';
            $return_array['data']['err_msg'] = '시간이 초과되어 연장요청이 무효처리 되었습니다.';
            echo json_encode($return_array);
            exit;
        }

        if(!$wiz_member || !$schedule)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0201';
            $return_array['data']['err_msg'] = '등록된 데이터가 없습니다.(2)';
            echo json_encode($return_array);
            exit;
        }

        $is_possible_extent = schedule_check_possible_extend_class($schedule['uid'], $schedule['lesson_id'], $schedule['tu_uid'], strtotime($schedule['endday']) + 1, $schedule['cl_time'], $lesson_info, $wiz_member['wm_d_did']);

        if(!$is_possible_extent)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0218';
            $return_array['data']['err_msg'] = '연장할수 없습니다.';
            echo json_encode($return_array);
            exit;
        }

        /*
            -고정, 자유둘다 내일 오전 6시 이전에 잡혀있는 스케쥴은 연장으로 땡겨올수 없고, 이후스케쥴을 소모하여 연장가능.
            -자유수업 연장 시 주기 횟수를 우선, 주기횟수가 다 소모했다면 벼락으로 횟수소모.
        */

        $next_start_date = date('Y-m-d H:i:s', strtotime($schedule['endday']) + 1);
        $next_end_date = date('Y-m-d H:i:s', strtotime('+'.$schedule['cl_time'].' minutes',strtotime($next_start_date)) -1);

        $sc_kind = 'c'; //c:벼락상태값
        $log_kind = 'o';

        if($lesson['wl_cl_gubun'] =='2')
        {
            //소진할 주기가 남아있다면
            if($free_sc_data['remain_week_class_cnt'] > 0)
            {
                $sc_kind = 'f'; //f:벼락이 아닌 주기소모 상태값
                $log_kind = 'k';
            }

            $result = $this->lesson_mdl->insert_wiz_schedule([
                'lesson_id'     => $schedule['lesson_id'],
                'uid'           => $schedule['uid'],
                'wiz_id'        => $schedule['wiz_id'],
                'name'          => $schedule['name'],
                'cl_time'       => $schedule['cl_time'],
                'mobile'        => $schedule['mobile'],
                'cl_number'     => $schedule['wl_cl_number'],
                'tu_uid'        => $schedule['tu_uid'],
                'tu_name'       => $schedule['tu_name'],
                'startday'      => $next_start_date,
                'endday'        => $next_end_date,
                'be_tu_id'      => '',
                'weekend'       => date('w'),
                'kind'          => $sc_kind,
                'lesson_gubun'  => $schedule['lesson_gubun'],
            ]);

            $extend_sc_id = $result;
        }
        else
        {
            //마지막 수업정보를 가져온다. 가져온 수업의 정보를 연장수업 정보로 업데이트 시키기위해 사용
            $last_class = $this->lesson_mdl->row_last_class_present_1_after_1day($schedule['lesson_id'], date('Y-m-d 06:00:00',strtotime('+1 day')));
            if(!$last_class)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = '0218';
                $return_array['data']['err_msg'] = '연장할수 없습니다.(2)';
                echo json_encode($return_array);
                exit;
            }

            $extend_sc_id = $last_class['ws_sc_id'];

            $result = $this->lesson_mdl->update_wiz_schedule($extend_sc_id,[
                'tu_uid'        => $schedule['tu_uid'],
                'tu_name'       => $schedule['tu_name'],
                'startday'      => $next_start_date,
                'endday'        => $next_end_date,
                'weekend'       => date('w'),
                'kind'          => $sc_kind,
                'lesson_gubun'  => $schedule['lesson_gubun'],
            ]);
        }

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        //종료일 재설정
        $seday = lesson_resetting_endday($schedule['lesson_id'], $schedule['uid'], $lesson);
         
        //수업변경기록
        $this->lesson_mdl->insert_wiz_tutor_change([
            'lesson_id' => $schedule['lesson_id'],
            'a_tutor'   => $lesson['wl_tu_name'],
            'b_tutor'   => $schedule['tu_name'],
            'a_time'    => date("H:i:s",$lesson['wl_stime']),
            'b_time'    => substr($next_end_date,11),
            'cl_time'   => $schedule['cl_time'],
            'startday'  => $seday['startday'],
            'endday'    => $seday['endday'],
            'a_date'    => substr($next_start_date,0,11),
            'b_date'    => substr($next_start_date,0,11),
            'man_id'    => $wiz_member['wm_uid'],
            'man_name'  => $wiz_member['wm_name'],
            'regdate'   => date('Y-m-d H:i:s'),
            'kind'      => $log_kind,
        ], '수업연장');

        //연장테이블에 연장된 수업번호, 연장시각 업뎃
        $this->lesson_mdl->update_wiz_class_extension($extend['idx'], [
            'extended_sc_id' => $extend_sc_id,
            'approval_date' => date('Y-m-d H:i:s'),
        ]);

        if($lesson['wl_cl_gubun'] =='1')
        {
            //자동재수강으로 연결된 수업 날짜 당긴다.
            lesson_rescheduling_next_lesson($schedule['lesson_id'], $schedule['uid'], $lesson);
        }
        
        $class_info['remain_class'] = $class_info['remain_class']-1;

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['is_deny'] = 0;
        $return_array['data']['info'] = $class_info;

        echo json_encode($return_array);
        exit;
    }

}


