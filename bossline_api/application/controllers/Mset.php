<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Mset extends _Base_Controller {


    public function __construct()
    {
        parent::__construct();

        $this->load->library('form_validation');

    }

    /**
     * MSET신청할 수 있는 상태인지체크. 신청 가능하면 가능한 스케쥴리스트 리턴
     */
    public function check_mset_regist_possible_date()
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

        $this->load->model('mset_mdl');
        $this->load->model('holiday_mdl');
        /*
            토, 일은 시험이 없다.
            기본적으로 주간에는 매일 시험이 있으며 시험가능한 시간대는 평가관1,2 강사의 스케쥴에 따른다.
            전화는 매시 0,30분에 잡을수 있고, 화상은 장애확률때문에 1시간을 통으로 잡기에 매시 0분에 1개만 잡을 수있다.
            화상(MEL)은 해외거주자만 선택가능하게 해야한다.
            홀리데이 disabled_lesson 활성되어있는 날은 정규수업 불가한 날로, MSET도 불가하다.
        */

        //대기중인 mset 있으면 재접수 불가
        $check = $this->mset_mdl->check_ongoing_mset($wiz_member['wm_uid']);

        if($check)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0404";
            $return_array['data']['err_msg'] = "현재 진행 대기중인 MSET이 있습니다. 한번에 두 개 이상을 진행할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        //이번주에 시험 봤으면 응시 불가. 그런데 토,일요일이면 다음주꺼 신청가능하므로 패스시켜야한다
        if(date('w') != '6' && date('w') != '0')
        {
            $monday = date('w') == 0 ? date('Y-m-d',strtotime('-6 day')):date('Y-m-d',strtotime('-'.(date('w')-1).' day')).' 00:00:00'; 
            $friday = date('Y-m-d 23:59:59',strtotime('+4 day', strtotime($monday))); 
    
            $check = $this->mset_mdl->check_run_mset_thisweek($wiz_member['wm_uid'], $monday, $friday);
    
            if($check)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0405";
                $return_array['data']['err_msg'] = "이번 주에 MSET을 진행한 기록이 있습니다. MSET은 한 주에 한 번만 가능합니다.";
                echo json_encode($return_array);
                exit;
            }
        }
       

        // 주말 제외한 선택가능한 평일 날짜 구하기
        $aPossibleDayList = array();	//선택가능한 날짜 목록
        $iDays = 3;	                    //선택가능한 날짜 수
        $aWeekLabelData = array('일','월','화','수','목','금','토','일');
        $aWeekData = array(1,2,3,4,5);
        $sDate = '';
        for ($i=0; count($aPossibleDayList)<$iDays; $i++) {
            $sDate = date("Y-m-d", ($i? strtotime($sDate." +1 day"):time()));
            while (1) {
                $iWeek = date("w", strtotime($sDate));
                if (!in_array($iWeek, $aWeekData)) {
                    $sDate = date("Y-m-d", strtotime($sDate." +1 day"));
                    continue;
                }
                break;
            }
            
            $aPossibleDayList[] = $sDate;
        }

        // 해외거주자면 mel도 선택지에 보여줘야한다.
        $mel_open = $wiz_member['wm_regi_area'] == '1' || $wiz_member['wm_regi_area'] == '3' ? 1:0;

        // mset 시험관 리스트
        $mset_tutor = $this->mset_mdl->list_mset_tutor();

        $result_date_info = [];
        

        // 날짜 별 루프 돌려 시험관의 시간표를 구성한다
        /* foreach($aPossibleDayList as $date)
        {
            // 30분, 60분 잡는 시험 각각 따로 시간표 구성
            $result_date_info[$date]['30'] = null;
            $result_date_info[$date]['60'] = null;
            $tutor_timeline = [];
            
            // 해당날짜의 시험관별 시간표
            foreach($mset_tutor as $tutor)
            {
                $holi = $this->holiday_mdl->check_holiday($date);
                
                //정규수업 불가라면 mset도 불가
                if($holi && $holi['disabled_lesson'])
                {
                    $result_date_info[$date]['holiday'] = 1;
                }
                else
                {
                    $result_date_info[$date]['holiday'] = 0;
                    
                    $tutor_timeline['30'][$tutor['wt_tu_uid']] = schedule_tutor_timeline($tutor['wt_tu_uid'], $date, 30, 'mset', ['00','30']);
                    if($mel_open)
                    {
                        $tutor_timeline['60'][$tutor['wt_tu_uid']] = schedule_tutor_timeline($tutor['wt_tu_uid'], $date, 60, 'mset', ['00']);
                    }
                    
                }
                
            }

            // 시험관 별 신청가능시간 병합
            foreach($tutor_timeline as $cl_time=>$tu_uid)
            {
                foreach($tu_uid as $hours)
                {
                    foreach($hours as $hour=>$mins)
                    {
                        foreach($mins as $min=>$val)
                        {
                            $result_date_info[$date][$cl_time][$hour][$min] = ($val == 1 || $result_date_info[$date][$cl_time][$hour][$min] == 1) ? 1:0;
                        }
                    }
                }
                
                
            }
            
        } */

        // MSET의 경우 한시간 이후 시간 부터 신청 가능
        $limit_date = date('Y-m-d H:i', strtotime('+1 hour'));  
        $nowdata = date('Y-m-d H:i:s');  

        foreach($aPossibleDayList as $date)
        {
            $tutor_timeline = [];
            //프론트에서 json 오브젝트 키만으로는 정렬이 제대로 안되서 여기서 배열로 재구성해서 넘겨주기 위한 변수
            $sort_timeline = [];

            $holi = $this->holiday_mdl->check_holiday($date);
            //정규수업 불가라면 mset도 불가
            if($holi && $holi['disabled_lesson'])
            {
                $holiday = 1;
            }
            else
            {
                $holiday = 0;
                // 해당날짜의 시험관별 시간표
                foreach($mset_tutor as $tutor)
                {    
                    $tutor_timeline['30'][$tutor['wt_tu_uid']] = schedule_tutor_timeline($tutor['wt_tu_uid'], $date, 30, 'mset', ['00','30']);
                    if($mel_open)
                    {
                        $tutor_timeline['60'][$tutor['wt_tu_uid']] = schedule_tutor_timeline($tutor['wt_tu_uid'], $date, 60, 'mset', ['00','30']);                        
                    }
                }

                $tutor_merge_timeline = [];
                // 시험관 별 신청가능시간 병합
                foreach($tutor_timeline as $cl_time=>$tu_uid)
                {
                    foreach($tu_uid as $hours)
                    {
                        foreach($hours as $hour=>$mins)
                        {
                            foreach($mins as $min=>$val)
                            {
                                $tutor_merge_timeline[$cl_time][$hour][$min] = ($val == 1 || $tutor_merge_timeline[$cl_time][$hour][$min] == 1) ? 1:0;
                            }
                        }
                    }
                }

                foreach($tutor_merge_timeline as $cl_time=>$hours)
                {
                    foreach($hours as $hour=>$mins)
                    {
                        // 현재시각이 시간표 특정시각보다 크면 continue
                        if($nowdata > $date.' '.$hour.':00:00') continue;

                        $min_data = [];
                        foreach($mins as $min=>$val)
                        {
                            $min_data[] = array(
                                'min' => $min.'',   // string으로 값 고정 해주려고 .'' 붙임
                                'schedule' => $val,
                            );
                        }

                        $sort_timeline[$cl_time][] = array(
                            'hour' => $hour,
                            'min_data' => $min_data,
                        );
                    }
                }

            }
            
            $result_date_info[] = array(
                'date'=> $date,
                'holiday'=> $holiday,
                'timeline'=> $sort_timeline,
            );
            
        }   // END date foreach 
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "조회성공";
        $return_array['data']['free_mset'] = mset_check_free($wiz_member['wm_uid'], $wiz_member['wm_d_did']);    //무료대상자 여부
        $return_array['data']['schedule'] = $result_date_info;
        $return_array['data']['wm_regi_area'] = $wiz_member['wm_regi_area'];    // 재로그인 안하면 로컬스토리지에 값 없을수 있어서 데이터 리턴해줌
        
        echo json_encode($return_array);
        exit;

    }

    /**
     * MSET 등록. 유효성 체크에 check_mset_regist_possible_date 에서 쓰인 로직 대부분 사용
     */
    public function regist_mset()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "lesson_gubun" => trim($this->input->post('lesson_gubun')),
            "tel" => trim($this->input->post('tel')),
            "date" => trim($this->input->post('date')),
            "time" => trim($this->input->post('time')),
            "is_app" => trim($this->input->post('is_app')),   // pc, mobile
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

        $this->load->model('mset_mdl');
        $this->load->model('holiday_mdl');
        /*
            토, 일은 시험이 없다.
            기본적으로 주간에는 매일 시험이 있으며 시험가능한 시간대는 평가관1,2 강사의 스케쥴에 따른다.
            전화는 매시 0,30분에 잡을수 있고, 화상은 장애확률때문에 1시간을 통으로 잡기에 매시 0분에 1개만 잡을 수있다.
            화상(MEL)은 해외거주자만 선택가능하게 해야한다.
            홀리데이 disabled_lesson 활성되어있는 날은 정규수업 불가한 날로, MSET도 불가하다.
        */

        //대기중인 mset 있으면 재접수 불가
        $check = $this->mset_mdl->check_ongoing_mset($wiz_member['wm_uid']);

        if($check)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0404";
            $return_array['data']['err_msg'] = "현재 진행 대기중인 MSET이 있습니다. 한번에 두 개 이상을 진행할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        //이번주에 시험 봤으면 응시 불가. 그런데 토,일요일이면 다음주꺼 신청가능하므로 패스시켜야한다
        if(date('w') != '6' && date('w') != '0')
        {
            $monday = date('w') == 0 ? date('Y-m-d',strtotime('-6 day')):date('Y-m-d',strtotime('-'.(date('w')-1).' day')).' 00:00:00'; 
            $friday = date('Y-m-d 23:59:59',strtotime('+4 day', strtotime($monday))); 
    
            $check = $this->mset_mdl->check_run_mset_thisweek($wiz_member['wm_uid'], $monday, $friday);
    
            if($check)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0405";
                $return_array['data']['err_msg'] = "이번 주에 MSET을 진행한 기록이 있습니다. MSET은 한 주에 한 번만 가능합니다.";
                echo json_encode($return_array);
                exit;
            }
        }
       

        // 주말 제외한 선택가능한 평일 날짜 구하기
        $aPossibleDayList = array();	//선택가능한 날짜 목록
        $iDays = 3;	                    //선택가능한 날짜 수
        $aWeekLabelData = array('일','월','화','수','목','금','토','일');
        $aWeekData = array(1,2,3,4,5);
        $sDate = '';
        for ($i=0; count($aPossibleDayList)<$iDays; $i++) {
            $sDate = date("Y-m-d", ($i? strtotime($sDate." +1 day"):time()));
            while (1) {
                $iWeek = date("w", strtotime($sDate));
                if (!in_array($iWeek, $aWeekData)) {
                    $sDate = date("Y-m-d", strtotime($sDate." +1 day"));
                    continue;
                }
                break;
            }
            
            $aPossibleDayList[] = $sDate;
        }

        if(!in_array($request['date'], $aPossibleDayList))
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0406";
            $return_array['data']['err_msg'] = "MSET 신청할수 없는 날짜입니다.";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $holi = $this->holiday_mdl->check_holiday($request['date']);

            //정규수업 불가라면 mset도 불가
            if($holi && $holi['disabled_lesson'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0406";
                $return_array['data']['err_msg'] = "MSET 신청할수 없는 날짜입니다.(2)";
                echo json_encode($return_array);
                exit;
            }
        }

        // 해외거주자면 mel도 선택지에 포함
        $mel_open = $wiz_member['wm_regi_area'] == '1' || $wiz_member['wm_regi_area'] == '3' ? 1:0;

        if(!$mel_open && $request['lesson_gubun'] =='T')
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0407";
            $return_array['data']['err_msg'] = "잘못된 테스트방식입니다.";
            echo json_encode($return_array);
            exit;
        }

        
        $free_mset = mset_check_free($wiz_member['wm_uid'], $wiz_member['wm_d_did']);    //무료대상자 여부

        $need_point = $free_mset ? 0:common_point_standard('mset');

        $this->load->model('point_mdl');
        // 소모 포인트 가지고 있는지 체크
        if($need_point > 0)
        {
            $nowpoint = $this->point_mdl->check_current_point($wiz_member['wm_uid']);
            $wiz_member['wm_point'] = $nowpoint['wm_point'] ? $nowpoint['wm_point']:0;

            if($need_point > $nowpoint['wm_point'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0328";
                $return_array['data']['err_msg'] = "포인트가 부족합니다.";
                echo json_encode($return_array);
                exit;
            }
        }

        // mset 시험관
        $tu_uid_list = $this->mset_mdl->list_mset_tutor();

        if(!$tu_uid_list)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0408";
            $return_array['data']['err_msg'] = "평가관이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        // 랜덤으로 순서 섞은 후 비어있는 시험관시간을 선택해준다.
        shuffle($tu_uid_list);

        //E 화상은 수업시간 60분으로 잡는다
        $cl_time = $request['lesson_gubun'] =='E' ? 60:30;  

        foreach($tu_uid_list as $tutor)
        {
            // 해당 평가관 스케쥴 뽑기
            $schedule = schedule_tutor_timeline(
                $tutor['wt_tu_uid'], 
                $request['date'], 
                $cl_time,                 
                'mset', 
                $request['lesson_gubun'] =='E' ? ['00','30']:['00','30']     //E 화상은 60분으로 잡기때문에 정각에만 스케쥴 잡기 허용
            );

            $split_time = explode(':',$request['time']);

            if($schedule[$split_time[0]][$split_time[1]]) break;
        }

        // 값이 1이여야 시험 스케쥴 잡기 가능. 시험관 전부 선택된 시간에 잡기 불가능하면 에러
        if(!$schedule[$split_time[0]][$split_time[1]])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0409";
            $return_array['data']['err_msg'] = "선택된 시간은 신청 할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        // --체크 끝. mset 등록 시작

        $startday = $request['date'].' '.$request['time'].':00';
        $endday = date("Y-m-d H:i:s", strtotime($startday) + ($cl_time * 60) - 1);

        //평가완료된 횟수
		$mset_complete_cnt = $this->mset_mdl->count_mset_complete($wiz_member['wm_uid']);
        $mset_complete_cnt = $mset_complete_cnt ? $mset_complete_cnt['cnt']:0;

        $phone = common_checked_phone_format($request['tel']);

        $schedule_param = [
            'lesson_id' => 100000001,
            'uid'       => $wiz_member['wm_uid'],
            'wiz_id'    => $wiz_member['wm_wiz_id'],
            'name'      => $wiz_member['wm_name'],
            'tu_uid'    => $tutor['wt_tu_uid'],
            'tu_name'   => $tutor['wt_tu_name'],
            'present'   => '1',
            'startday'  => $request['date'].' '.$request['time'].':00',
            'endday'    => $endday,
            'cl_time'   => $cl_time,
            'lesson_gubun' => $request['lesson_gubun'],
            'tel'       => $phone,
            'mobile'    => $phone,
            'kind'      => 'm',
        ];

        //스케쥴 insert
        $insert_id = $this->lesson_mdl->insert_wiz_schedule($schedule_param);
        
        if($insert_id < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR!";
            echo json_encode($return_array);
            exit;
        }

        // mint_mset_report insert
        $exam_idx = $this->mset_mdl->check_mset_paper_idx($request['date']);
        
        $report_param = [
            'uid'           => $wiz_member['wm_uid'],
            'tu_uid'        => $tutor['wt_tu_uid'],
            'examiner_id'   => $tutor['wt_tu_id'],
            'examiner_name' => $tutor['wt_tu_name'],
            'newbie'        => '1',
            'retakes'       => $mset_complete_cnt,
            'status'        => '0',
            'use_point'     => $need_point,
            'mset_gubun'    => $request['lesson_gubun'],
            'tel'           => $phone,
            'mobile'        => $phone,
            'regdate'       => date('Y-m-d H:i:s'),
            'startday'      => $startday,
            'endday'        => $endday,
            'test_time'     => $cl_time,
            'sc_id'         => $insert_id,
            'korean_name'   => $wiz_member['wm_name'],
            'english_name'  => $wiz_member['wm_ename'] ? $wiz_member['wm_ename']:'',
            'nick_name'     => $wiz_member['wm_nickname'] ? $wiz_member['wm_nickname']:'',
            'exam_idx'      => $exam_idx['idx'],
            'ismobile'      => $request['is_app'] =='pc' ? 0:1,
        ];

        $insert_id_report = $this->mset_mdl->insert_mset($report_param);

        $document_no = date("Ymd").'-'.$exam_idx['idx'].'-'.sprintf("%05d", $insert_id_report);

        $update_report_param = [
            'document_no'  => $document_no,
        ];

        $where = [
            'idx' => $insert_id_report,
        ];
        // document_no 업뎃
        $this->mset_mdl->update_mset($update_report_param,$where);

        $where = [
            'idx !=' => $insert_id_report,
            'uid' => $wiz_member['wm_uid'],
            'newbie' => 1,
        ];

        $update_report_param = [
            'newbie'  => 0,
        ];
        // 지난 mset newbie=0 으로 업뎃
        $this->mset_mdl->update_mset($update_report_param,$where);

        //마지막 MSET 회원 정보 업데이트
        $this->mset_mdl->update_member_last_msetdate($wiz_member['wm_uid']);

        // 포인트 차감
        if($need_point > 0)
        {
            $point_param = [
                'uid'       => $wiz_member['wm_uid'],
                'name'      => $wiz_member['wm_name'],
                'pt_name'   => 'MSET 신청비용 지불',
                'point'     => $need_point * -1,
                'kind'      => '5',
                'regdate'   => date('Y-m-d H:i:s'),
                'showYn'    => 'y',
            ];
            $this->point_mdl->set_wiz_point($point_param);
        }

        // 알림톡 전송
        $options = array(
            'uid'       => $wiz_member['wm_uid'],
            'wiz_id'    => $wiz_member['wm_wiz_id'],
            'name'      => $wiz_member['wm_name'],
            'time'      => $request['time'],
            'date'      => $request['date'],
            'sms_push_yn'   => 'Y',
            'sms_push_code' => '198',
            'sms_term_min'  => '5'
        );

        sms::send_atalk($request['tel'],'MINT06001D',$options);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "MSET 신청이 완료되었습니다.";

        echo json_encode($return_array);
        exit;

    }

    /**
     * MSET신청내역
     */
    public function mset_apply_list_()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "mset_code" => trim($this->input->post('msetCode')) ? trim($this->input->post('msetCode')):'',
            "start" => trim($this->input->post('start')) ? trim($this->input->post('start')):'0',
            "limit" => trim($this->input->post('limit')) ? trim($this->input->post('limit')):'5',
            "order_field" => trim($this->input->post('order_field')) ? trim($this->input->post('order_field')):'mmr.idx',
            "order" => trim($this->input->post('order')) ? trim($this->input->post('order')):'desc',
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

        $this->load->model('mset_mdl');

        $dealer = $this->member_mdl->get_wiz_dealer($wiz_member['wm_d_did']);
        //wiz_member.d_id != 16 && wiz_dealer.mset_result_yn ='N' 이면 나의 엠셋(그래프와 결과 안보여준다)

        $where = ' mmr.uid='.$wiz_member['wm_uid'].' AND mmr.disabled=0 ';

        //불만족 리포트에서 원글 보기 같이 단일 조회 시 들어온다
        if($request['mset_code'])
        {
            $where .= ' AND mmr.idx = '.$request['mset_code'];
        }
        
        $count = $this->mset_mdl->list_count_mset_apply($where);
        $list_count = $count ? $count['cnt']:0;

        $orderby = ' ORDER BY '.$request['order_field'].' '.$request['order'];
        $limit = ' LIMIT '.$request['start'].', '.$request['limit'];
        $list = $this->mset_mdl->list_mset_apply($where, $orderby, $limit);

        $mset_result_yn = $wiz_member['wm_d_did'] != '16' && $dealer['mset_result_yn'] =='N' ? 'N':'Y';

        // N이면 결과 보여주지 않는다.
        if($list && $mset_result_yn =='N')
        {
            foreach($list as $key=>$row)
            {
                $list[$key]['mmr_overall_level'] = '';
            }
        }


        $return_array['res_code'] = '0000';
        $return_array['msg'] = "조회성공";
        $return_array['data']['list'] = $list;
        $return_array['data']['total_cnt'] = $list_count;
        $return_array['data']['mset_result_yn'] = $mset_result_yn;

        echo json_encode($return_array);
        exit;

    }

    /**
     * MSET신청취소
     */
    public function mset_cancel()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "idx" => trim($this->input->post('idx')),
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

        $this->load->model('mset_mdl');
        $this->load->model('lesson_mdl');

        $row = $this->mset_mdl->row_mset_apply($request['idx']);

        if(!$row || $row['mmr_uid'] != $wiz_member['wm_uid'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0410";
            $return_array['data']['err_msg'] = "MSET 정보가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        //status - 0:신청완료, 1:평가대기, 2:평가완료, 3:취소완료(회원), 4:취소완료(관리자), 5:결석처리
        
        // 0상태이고 시험시간까지 남은 시간이 1시간 이하면 취소 못함.
        if($row['mmr_status'] == '0' && strtotime($row['mmr_startday']) - time() < 3600)
        {
            $where = [
                'idx' => $request['idx'],
            ];
    
            $update_report_param = [
                'status' => 1,
            ];
            // 상태 1로 업뎃
            $this->mset_mdl->update_mset($update_report_param,$where);

            $row['mmr_status'] = '1';
        }

        if($row['mmr_status'] != '0')
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0411";
            $return_array['data']['err_msg'] = "취소할수 없는 상태입니다.";
            echo json_encode($return_array);
            exit;
        }

        //스케쥴 삭제
        $result = $this->lesson_mdl->delete_wiz_schedule($row['mmr_sc_id']);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR!";
            echo json_encode($return_array);
            exit;
        }

        //엠셋 status 취소상태로
        $where = [
            'idx' => $request['idx'],
        ];

        $update_report_param = [
            'status' => 3,
            'change_regdate' => date('Y-m-d H:i:s'),
        ];
        
        $this->mset_mdl->update_mset($update_report_param,$where);

        //회원의 마지막 mset날짜업뎃
        $this->mset_mdl->update_member_last_msetdate($wiz_member['wm_uid']);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "취소되었습니다.";

        echo json_encode($return_array);
        exit;

    }

    /**
     * MSET 그래프데이터
     */
    public function mset_graph()
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

        $this->load->model('mset_mdl');

        $where = ' uid='.$wiz_member['wm_uid'].' AND disabled=0 AND status = 2 ORDER BY startday DESC ';
        //내 mset 정보 전부 불러온다
        $data = $this->mset_mdl->mset_level_data($where);

        $result = null;

        $aver_level = 0;

        if($data)
        {
            $result = [
                'recent' => [],
                'all' => [],
            ];

            $temp = array();
            //최근6개까지만
            foreach($data as $i=>$x) 
            {
                $temp[] = $x;
                if ($i>=5) break;
            }

            $temp = array_reverse($temp);
            foreach($temp as $val)
            {
                $sd = strtotime($val['startday']);

                $val['startday'] = array(
                    date('Y', $sd),
                    date('m/d', $sd)
                );

                foreach($val as $key2=>$row)
                {
                    $result['recent'][$key2][] = $row;
                }
                
                // 레벨로 평균 -> 총점으로 평균 내주기로 변경
                // $aver_level+= $val['overall_level'];
                $aver_level+= $val['overall_score'];
            }

            $aver_level = floor(($aver_level / count($temp))/10);
            
            //전체
            $data = array_reverse($data);
            foreach($data as $key=>$val)
            {
                // 처음과 마지막거만 날짜데이터 넣어준다.
                if($key == 0 || (count($data)-1) == $key)
                {
                    $sd = strtotime($val['startday']);
                    $val['startday'] = array(
                        date('Y', $sd),
                        date('m/d', $sd)
                    );
                }
                else
                {
                    $val['startday'] = [];
                }

                foreach($val as $key2=>$row)
                {
                    $result['all'][$key2][] = $row;
                }
                
            }
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "조회성공";
        $return_array['data']['graph'] = $result;
        $return_array['data']['aver_level'] = $aver_level;

        echo json_encode($return_array);
        exit;

    }
    
    /**
     * MSET 결과
     */
    public function mset_result()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "idx" => trim($this->input->post('idx')),
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

        $this->load->model('mset_mdl');
        $this->load->model('objection_mdl');

        $result = $this->mset_mdl->row_mset_apply($request['idx']);

        if(!$result || $result['mmr_uid'] != $wiz_member['wm_uid'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0410";
            $return_array['data']['err_msg'] = "MSET 정보가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($result['mmr_status'] !='2')
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0412";
            $return_array['data']['err_msg'] = "MSET결과완료 상태가 아닙니다.";
            echo json_encode($return_array);
            exit;
        }

        //엠셋레벨
        $mset_level = $result['mmr_overall_level'];
        $mset_score = $result['mmr_overall_score'];
        $higher_scores_than_me = 0;

        //MSET 평가등급 비교표 추출
        $result['score_comparison'] = $this->mset_mdl->mset_score_comparison($mset_level);
        
        //mset 점수 요약데이터 추출
        $summary = $this->mset_mdl->mset_score_summary();

        //점수별 분포
        $stats = [
            'scores' => [],  //점수 분포
            'aver' => [],   //각 영역 평균
        ];

        $total_member = 0; // 시험친 전체 유저 명수
        $total_score_sum = 0; // 총점수 합
        foreach($summary as $val)
        {
            if(preg_match('/^[0-9]+$/',$val['key'],$m))
            {
                $stats['scores'][$val['key']] = $val['value'];

                //내가 상위 몇%인지 체크하기 위해
                if($val['key'] > $mset_score)
                {
                    $higher_scores_than_me+= $val['value'];     // 내 점수보다 높은 사람이 몇명인지 전부 더한다.
                }

                $total_member+= $val['value']; 
                $total_score_sum+= $val['key'] * $val['value']; // 아래에서 전체 평균을 구하기 위해 점수 * 명수한 값을 전부 더한다.
            }
            else
            {
                $stats['aver'][$val['key']] = $val['value'];
            }
        }

        $tmp_stat = [
            'score' => [],
            'cnt' => [],
        ];

        // 배열형태로 1~100점까지 세팅
        for($i=1;$i<=100;$i++)
        {
            $tmp_stat['score'][] = $i;
            $tmp_stat['cnt'][] = $stats['scores'][$i] ? (int)$stats['scores'][$i]:0;
        }

        $stats['scores'] = $tmp_stat;
        
        foreach($result as $key=>$val)
        {
            if(preg_match('/advice|comment|description/',$key))
            {
                $result[$key] = nl2br($val);
            }
            
        }
        // Level 4-xxxxx 형태에서 뒷문자만 추출
        $result['mmr_overall_level_message'] = explode('-',$result['mmr_overall_level_message'])[1];
        //내가 상위 몇%인지
        $result['my_percentage'] = sprintf('%.2f',($higher_scores_than_me/$total_member) * 100);
        //전체평균점수
        $result['total_score_aver'] = (int)($total_score_sum/$total_member);
        //다음 mset리포트가 있는지
        $next_idx = $this->mset_mdl->checked_mset_next_report($request['idx'], $wiz_member['wm_uid']);
        $result['next_idx'] = $next_idx ? $next_idx['idx']:'';
        //이전 mset리포트가 있는지
        $prev_idx = $this->mset_mdl->checked_mset_prev_report($request['idx'], $wiz_member['wm_uid']);
        $result['prev_idx'] = $prev_idx ? $prev_idx['idx']:'';

        //불만족 리포트 등록되어있는지 확인
        $where = ' WHERE mset_idx='.$request['idx'];
        $has_report = $this->objection_mdl->count_objection($where);

        //녹화파일 조회
        if($result['mmr_mset_gubun'] == "T")      $call_tel = $result['mmr_tel'];
        else if($result['mmr_mset_gubun'] == "M") $call_tel = $result['mmr_mobile'];
        $call_tel2 = $call_tel;
        $call_tel = str_replace("-", "",$call_tel);
        $param = array(
            'date'   => substr($result['mmr_startday'],0,10),
            'tel'    => $call_tel,
            'tel2'   => $call_tel2,
            'sc_id'  => $result['mmr_sc_id'],
            'wm_uid' => $result['mmr_uid']
        );

        $record_list = DialComm::record_list($result['mmr_mset_gubun'], $param);
    
        $return_array['res_code']            = '0000';
        $return_array['msg']                 = "조회성공";
        $return_array['data']['result']      = $result;
        $return_array['data']['stats']       = $stats;
        $return_array['data']['has_report']  = $has_report['cnt'] > 0 ? 1:0;
        $return_array['data']['record_list'] = $record_list;
        echo json_encode($return_array);
        exit;

    }

}








