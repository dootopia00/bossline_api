<?php
defined('BASEPATH') OR exit('No direct script access allowed');
// require_once APPPATH.'/controllers/_Base_Controller.php';

/* 
    강사사이트 
    - _Base_Controller 역활 > __construct 에서 처리
*/
class _tutor extends CI_Controller{

    //강사 인증 정보
    public $WIZ_TUTOR_DATA = NULL;
    //강사 인증 오류시 메시지
    public $ERR_AUTH_CHECK_MSG = '로그인 정보가 없습니다.';
    //강사계정 관리자 로그인시 관리자 아이디
    public $LOGIN_ADMIN_ID = NULL;
    
    public $upload_path_correct = ISTESTMODE ? 'test_upload/edu/correct/':'edu/correct/';   // 강사가 올리는 mp3 파일경로
    public $upload_path_teacher_1n1 = ISTESTMODE ? 'test_upload/attach/teacher_1n1/' : 'attach/teacher_1n1/';
    public $upload_path_boards      = ISTESTMODE ? 'test_upload/attach/boards/' : 'attach/boards/';


    public function __construct()
    {
        parent::__construct();
        
        date_default_timezone_set('Asia/Seoul');
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
        header("Access-Control-Allow-Headers: X-Requested-With, Authorization, Develop, Content-Type");
        header('Content-Type: application/json');
        
        $this->load->library('form_validation');

        base_tutor_init();
    }


    public function login()
    {
        $return_array = array();

        $request = array(
            "tu_id" => trim(strtolower($this->input->post('tu_id'))),
            "tu_pw" => trim($this->input->post('tu_pw')),

            // 관리자에서 자동로그인 시 추가로 넘겨받는 파라미터. 아래 파라미터들이 있으면 관리자 자동로그인으로 간주
            "aid" => trim($this->input->post('aid')),       // 관리자 아이디
            "ui" => trim($this->input->post('ui')),         // 회원 uid
            "c" => trim($this->input->post('c')),           // 자동로그인 인증코드. 1회용
        );

        $this->load->model('tutor_mdl');

        $admin_login = false;
        
        if($request['aid'] && $request['ui'] && $request['c']){
            // 넘겨받은 어드민 로그인 파라미터들이 유효한지 체크
            $al_check = $this->tutor_mdl->tutor_admin_log_check($request['ui'], $request['c']);
            if(!$al_check)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0114";
                $return_array['data']['err_msg'] = "비정상적인 접근입니다.";
                echo json_encode($return_array);
                exit;
            }
            else
            {
                $request['tu_id'] = $al_check['tu_id'];
                $request['tu_pw'] = $al_check['tu_pw'];
                $admin_login = true;
            }
        }

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }


        
        $result = $this->tutor_mdl->login($request['tu_id'], $request['tu_pw']);
    
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

            $log = array(
                "tu_uid" => $result['wt_tu_uid'],
                "tu_name" => $result['wt_tu_name'],
                "tu_log_ip" => $_SERVER["REMOTE_ADDR"],
                "tu_log_date_time" => time() - (86400*30),
                "tu_log_date_ymd" => date("Y-m-d H:i:s"),
                "tu_log_regdate" => date("Y-m-d"),
            );   

            $this->tutor_mdl->log_login($log);

            $maaltalk_tutor_url = $this->tutor_mdl->maaltalk_tutor_url_info($result['wt_tu_uid']);
            $result['mntu_tutor_url'] = $maaltalk_tutor_url ? $maaltalk_tutor_url['mntu_tutor_url']:'';

            $return_array['res_code'] = '0000';
            $return_array['msg'] = "success login";
            $return_array['data']['api_token'] = token_create_tutor_token($result['wt_tu_id'], $request['aid']);
            $return_array['data']['info'] = $result;
            echo json_encode($return_array);
            exit;
        }
        
    }

    /**
     * 강사 정보 가져오기
     */
    public function tutor_info()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "강사 정보를 가져왔습니다.";
        $return_array['data']['info'] = $wiz_tutor;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 강사 정보 수정
     */
    public function tutor_modify()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "man_pw"        => trim($this->input->post('man_pw')), //필수
            "new_pw"        => ($this->input->post('new_pw')) ? trim($this->input->post('new_pw')) : NULL,
            "re_pw"         => ($this->input->post('re_pw')) ? trim($this->input->post('re_pw')) : NULL,
            "man_email"     => ($this->input->post('man_email')) ? trim($this->input->post('man_email')) : NULL,
            "correct_yn"    => ($this->input->post('correct_yn')) ? trim($this->input->post('correct_yn')) : NULL,
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        // 강사 비밀번호를 가져온다
        $this->load->model('Tutor_mdl');
        $tutor = $this->Tutor_mdl->get_tutor_pw($wiz_tutor['wt_tu_uid']);

        $article = array();
        
        //기존 비밀번호 체크
        if($tutor['wt_tu_pw'] <> $request['man_pw'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0104";
            $return_array['data']['err_msg'] = "Passwords do not match.";
            echo json_encode($return_array);
            exit;
        }

        //비밀번호 변경시 변경비밀번호 체크
        if($request['new_pw'])
        {
            if($request['new_pw'] <> $request['re_pw'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0104";
                $return_array['data']['err_msg'] = "The password to be changed does not match.";
                echo json_encode($return_array);
                exit;
            }
            else
            {
                $article['tu_pw'] = $request['new_pw'];
            }
        }

        if($request['man_email'])  $article['tu_email']  = $request['man_email'];
        if($request['correct_yn']) $article['correct_yn'] = $request['correct_yn'];

        $where = array(
            'tu_uid' => $wiz_tutor['wt_tu_uid']
        );

        $result = $this->Tutor_mdl->update_tutor($article, $where);
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "정보 수정 성공!";
        echo json_encode($return_array);
        exit;
    }


    /* 강사 스케쥴 */
    public function schedule_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "tu_id" => trim(strtolower($this->input->post('tu_id'))),
            "day" => trim($this->input->post('day')) ? trim($this->input->post('day')):date('Y-m-d'),       //스케쥴 찾을 날짜
            "add_week" => trim($this->input->post('add_week')) ? trim($this->input->post('add_week')):'0',  //더해줄 N주
        );
        
        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('lesson_mdl');
        $this->load->model('holiday_mdl');
        $this->load->model('tutor_mdl');

        /*
            주간스케쥴에서 이전주, 다음주클릭했을때 해당파라미터가 요청되서 들어온다.
            기본요청날짜(day)는 이번주의 날짜이지만,
            이번주의 날짜 기준으로 +add_week, 혹은 -add_week을 하여 이전주,다음주의 날짜를 구한다.
        */
        if($request['add_week'])
        {
            $addweek = $request['add_week'] > 0 ? ('+'.$request['add_week']):$request['add_week'];
            $request['day'] = date('Y-m-d',strtotime($addweek.' week', strtotime($request['day'])));
        }
        
        $date = $request['day'];
        $date_tomorrow_w = date('w',strtotime('+1 day', strtotime($date)));
        $date_tomorrow = date('Y-m-d',strtotime('+1 day', strtotime($date)));  // 특정일의 다음날 
        $date_yesterday = date('Y-m-d',strtotime('-1 day', strtotime($date)));  // 특정일의 전날

        //미완료된 피드백들
        $incomplete_feedback_sc = NULL;

        //날짜조회가 오늘이라면 피드백 못한거 있는지 체크. 최대 갯수 50개
        if($date == date('Y-m-d'))
        {
            //페이징 필요없이 쭉 리스팅할거라 리미트 설정만
            $limit_count = 50;
            $incomplete_feedback_sc = $this->lesson_mdl->list_schedule_incomplete_feedback($wiz_tutor['wt_tu_uid'], $date_yesterday, $date, $limit_count);
            
        }

        //00~24시까지 10분단위의 빈 스케쥴 배열 생성
        $timeline = schedule_make_empty_timeline();

        //휴일데이터 가져오기
        $holiday = $this->holiday_mdl->check_holiday($date);
        $holiday_yesterday = $this->holiday_mdl->check_holiday($date_yesterday);

        //해당날짜, 해당강사의 스케쥴 전부뽑는다
        $schedule = $this->lesson_mdl->list_schedule_by_tu_uid_and_startday($wiz_tutor['wt_tu_uid'], $date);

        //스케쥴 뽑았으면 타임라인에 맞게 스케쥴 데이터 삽입
        if($schedule)
        {
            foreach($schedule as $row)
            {
                $row['wml_content'] = nl2br($row['wml_content']);
                $temp = explode(' ', $row['ws_startday']);
                $time = explode(':', $temp[1]);
                $timeline[$time[0]][$time[1]][] = $row;     //단기연기(6) + 단기연기된 자리에 다른수업이 있을수 있어서 다 보여주기 위해 배열로 받는다
            }
        }
        /*
            --휴일관련--
            기존 - 정규수업불가+벼락불가 일떄 휴일, 휴일이여도 wiz_tutor_weekend 평일꺼로 체크
            개편 - 정규수업불가+벼락불가 일때 휴일, 휴일일때 wiz_tutor_weekend 휴일꺼(9) 체크

            이과장님과 협의하에 휴일인데 레벨테스트만 가능이면 휴일 워킹타임설정을 써야한다. 
            구민트는 휴일에 레벨테스트 진행하면 워킹타임이 평일로 처리되어서 매 휴일마다 워킹타임 변경해야했다.

            disabled_lesson
            disabled_thunder
            disabled_leveltest
            위 셋다 휴일설정이라면 스케쥴에 보여줄 데이터는 없다.
            하지만 휴일이여도 보통은 레벨테스트를 진행하기에, disabled_thunder, disabled_lesson 만 휴일 설정되어있을경우 레벨테스트는 휴일워킹타임으로 사용하기로 협의.
        */

        $is_holiday = $holiday && $holiday['disabled_lesson'] && $holiday['disabled_thunder'] && $holiday['disabled_leveltest'] ? 1:0;
        $is_holiday_yesterday = $holiday_yesterday && $holiday_yesterday['disabled_lesson'] && $holiday_yesterday['disabled_thunder'] && $holiday_yesterday['disabled_leveltest'] ? 1:0;

        $check_week = date('w',strtotime($date));
        if($holiday && $holiday['disabled_lesson'] && $holiday['disabled_thunder'])
        {
            $check_week = 9;    
        }

        //강사 기본근무시간 데이터
        $working_hour = $this->tutor_mdl->check_tutor_working_hour($wiz_tutor['wt_tu_uid'], $check_week, $date_tomorrow_w);
        //내일꺼는 안뿌려준다
        unset($working_hour['t24']);

        //브레이크 데이터 가져오기
        $break_data = tutor_breaking_time($wiz_tutor['wt_tu_uid'], $date, $date_tomorrow);

        //시간표 재정리해서 담을 변수
        $timeline_sort = null;

        $date_w = date('w',strtotime($date));
        $minute = ['00','10','20','30','40','50'];
        /*
            스케쥴 분단위 배열에 휴일 등 특이사항 집어넣기
             -근무시간 아니면 아얘 리턴데이터에서 삭제.
             -브레이크는 표기해준다
             -어제 오늘 휴일이였으면 보여줄게 없다

            --홀리데이 유형--
            어제오늘 휴일    스케쥴 필요없음
            오늘만 휴일      0~1시는 어제자 수업으로 간주하므로 0~1시 스케쥴 필요
            어제만 휴일      0~1시는 어제자므로 1~24시
            어제오늘 일함    0~24시
        
            -날짜휴일체크. 정규수업, 벼락, 레벨테스트 별로 휴일이 별도로 있다.
            -워킹타임 아닌 시간은 아얘 안보여준다
            -블럭,결근은 스케쥴 표기에 영향없다.
            -브레이크는 Breaktime이라고 별도표기를 해줘야한다.그리고 남은 시간에는 브레이크를 걸수도 있다.
        */
        if((!$is_holiday || !$is_holiday_yesterday) && $working_hour)
        {

            foreach($working_hour as $hour=>$val)
            {

                $hour = str_replace('t','',$hour);
                $hour = sprintf('%02d',$hour);  // 10보다 작은 수 앞에 0붙여주기.

                //오늘만 휴일      0~1시는 어제자 수업으로 간주하므로 0~1시 스케쥴 필요
                if($is_holiday && $hour !='00') continue;
        
                //24시는 다음날 0시로 세팅
                $loop_date = $hour =='24' ? $date_tomorrow:$date;
                $loop_hour = $hour =='24' ? '00':$hour;
                if($hour == '24') $date_w+=1;
                if($date_w ==7) $date_w = 0;
    
                //근무시간만 타임라인을 세팅한다
                if($val == 'Y')
                {
                    
                    foreach($minute as $min)
                    {
                        $timeline_sort[$hour.':'.$min]['break'] = '';
                        //sc_info 키 없으면 초기화
                        if(!array_key_exists('sc_info',$timeline_sort[$hour.':'.$min])) $timeline_sort[$hour.':'.$min]['sc_info'] = null;
    
                        $his = $loop_hour.':'.$min.':00';
                        // 체크할 시각. 시작일
                        $loop_date_ymdhis = $loop_date.' '.$his;    
    
                        //상시 브레이크
                        if($break_data['perm'][$date_w])
                        {
                            foreach($break_data['perm'][$date_w] as $breaktime)
                            {
                                if($date.' '.$breaktime == $loop_date_ymdhis)
                                {
                                    $timeline_sort[$hour.':'.$min]['break'] = 'perm';
                                    break;
                                }
                            }
                        }
                        //특정일, 특정시각 브레이크
                        if($break_data['temp'][$loop_date])
                        {
                            foreach($break_data['temp'][$loop_date] as $breaktime)
                            {
                                if($date.' '.$breaktime == $loop_date_ymdhis)
                                {
                                    $timeline_sort[$hour.':'.$min]['break'] = 'temp';
                                    break;
                                }
                            }
                        }
                        
                        //해당시각에 스케쥴 있으면 timeline_sort에 cl_time 만큼 넣어준다
                        if($timeline[$hour][$min])
                        {
                            foreach($timeline[$hour][$min] as $sc)
                            {
                                $cl_time = (int)$sc['ws_cl_time']/10;

                                if($cl_time)
                                {
                                    $tH = (int)$hour;
                                    $tM = (int)$min;
                                    for($i = 0; $i < $cl_time; $i++){
                                        //30분짜리 수업이 50분에 시작하면 TH(시간) 증가시키고 다음시간 00분, 10분에도 넣어준다
                                        if($tM >= 60){
                                            $tM = 0;
                                            $tH++;
                                        }
                                        $timeline_sort[sprintf('%02d',$tH).':'.sprintf('%02d',$tM)]['sc_info'][] = $sc;
                                        $tM += 10;
                                    }
                                }
                            }
                        }
                        
                    }
                }
            }

            //프론트 처리에 용이한 형태인 배열로 변경하여 리턴해주기 위해
            /* foreach($timeline_sort as $hour=>$mins)
            {
                $min_data = [];
                foreach($mins as $min=>$val)
                {
                    $min_data[] = array(
                        'min' => $min.'',   // string으로 값 고정 해주려고 .'' 붙임
                        'schedule' => $val,
                    );
                }
    
                $timeline_array[] = array(
                    'hour' => $hour.'',
                    'min_data' => $min_data,
                );
                
            } */
        }

        // 한달 이내 스케쥴 present별 갯수 구하기
        // 현재 북미에서만 쓰이므로 북미일때만
        if($wiz_tutor['mc_nationAs'] == 'usa')
        {
            $now = date('Y-m-d');
            $sdate = date('Y-m-d',strtotime($now."-1 month"));
            $present = $this->lesson_mdl->count_schedule_present_by_tu_uid($wiz_tutor['wt_tu_uid'], $sdate, $now);
            $return_array['data']['present'] = $present;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['timeline'] = $timeline_sort;
        $return_array['data']['uniq_check'] = md5(json_encode($timeline_sort));  //프론트에서 일정주기로 재호출하는데 바뀐내용있는지 체크해주기 위해
        $return_array['data']['incomplete_feedback_sc'] = $incomplete_feedback_sc;
        $return_array['data']['lesson_prefer_type_text'] = lesson_prefer_type_text();
        //외국과 시간차이가 약간 나므로 js로 날짜 추출하면 문제가 있을수 있으므로 여기서 생성하여 리턴
        $return_array['data']['day'] = [
            'day'        => $request['day'],
            'yesterday'  => $date_yesterday,
            'tomorrow'   => $date_tomorrow,
            'today'      => date('Y-m-d'),
            'weekstr'   => date('D', strtotime($date)),
            'nowdate'   => date('Y-m-d H:i:s'),
        ];
        
        echo json_encode($return_array);
        exit;

    }

    /**
     * 강사 스케쥴 상세보기
     */
    public function schedule_article()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "lesson_id"     => trim($this->input->post('lesson_id')), //필수
            "sc_id"         => $this->input->post('sc_id') ? $this->input->post('sc_id') : '',
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('Tutor_mdl');
        $this->load->model('lesson_mdl');

        //수업(and 학생) 정보 가져오기
        $lesson = $this->Tutor_mdl->detail_schedule_lesson($request['lesson_id']);
        if(!$lesson)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0212";
            $return_array['data']['err_msg']  = "No exists schedule!(1)";
            echo json_encode($return_array);
            exit;
        }

        //스케쥴(and 강사) 정보 가져오기
        $schedule = $this->Tutor_mdl->detail_schedule($request['lesson_id'], $request['sc_id']);
        if(!$schedule)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0212";
            $return_array['data']['err_msg']  = "No exists schedule!(2)";
            echo json_encode($return_array);
            exit;
        }

        //수강 결과 보고서가 없을 경우 등록
        if(!$schedule['wsr_scr_id'])
        {
            $article = array(
                'sc_id'     => $schedule['ws_sc_id'],
                'lesson_id' => $lesson['wl_lesson_id']
            );
            $this->lesson_mdl->insert_wiz_schedule_result($article);
        }

        $lesson['wl_id_val'] = tutor_get_sosocial_icon($lesson['wm_regi_gubun'], $lesson['wm_wiz_id'], $lesson['wm_social_email']);

        //나이 계산
        if(strlen($lesson['wm_birth']) >= 4) $lesson['wm_age'] = date("Y", time()) - substr($lesson['wm_birth'],0,4);
        else                                 $lesson['wm_age'] = 'unknown';

        //성별 정보
        $lesson['wm_gender'] = ($lesson['wm_gender'] == 'M') ? 'Male' : 'Female';

        $where = "sc_id != ".$schedule['ws_sc_id']." AND lesson_id = ".$lesson['wl_lesson_id']." AND present NOT IN (7,8,9)";
        //이전수업 정보
        $prev_where = $where." AND startday < '".$schedule['ws_startday']."' ORDER BY startday DESC";
        $schedule['prev'] = $this->Tutor_mdl->prev_next_schedule($prev_where);
        //다음수업 정보
        $next_where = $where." AND startday > '".$schedule['ws_startday']."' ORDER BY startday ASC";
        $schedule['next'] = $this->Tutor_mdl->prev_next_schedule($next_where);

        //민트영어라이브일 경우 미팅키가져가기 //사용하지않음
        // if($lesson['wl_lesson_gubun'] == 'B' || $schedule['ws_lesson_gubun'] == 'B')
        //     $lesson['meeting_key'] = $this->Tutor_mdl->webex_meeting_key($schedule['ws_sc_id']);

        //그룹수업일 경우 그룹맴버 정보 가져가기
        $lesson['classmate'] = null;
        if($lesson['wl_student_su'] > 2)
        {
            $group_uid = $lesson['wm_uid'].$lesson['wl_student_uid'];

            //빈정보를 거르기 위해 배열로 저장했다가 다시 풀어준다
            $group_uid = explode(",",$group_uid);
            $group_uid = array_filter($group_uid);
            $group_uid = implode(",",$group_uid);
            
			$lesson['classmate'] = $this->Tutor_mdl->classmate_info($group_uid);
        }

        //학생 요구 사항 - 선생님 유형
        $lesson['wmt_teacher'] = explode("-",$lesson['wmt_teacher']);

        //자동재수강 선택을 위한 값 - 수업종료날짜 부터 7일이내 일경우
        $lesson['renewal'] = false;
        $endday= strtotime($lesson['wl_endday']);
        $now   = strtotime(date("Y-m-d", time())." 00:00:00");

        if( $lesson['wl_refund_ok'] != 'Y' && ($lesson['wl_lesson_state'] == 'finished' || $lesson['wl_lesson_state'] == 'in class')
            && $lesson['wl_schedule_ok']=='Y' && $lesson['wl_newlesson_ok']=='Y' && strtotime('-7 day',$endday) < $now )
        {
            $lesson['renewal'] = true;
        }

        //수업상태저장을 위한 날짜값
        $schedule['startday_timestamp'] = strtotime($schedule['ws_startday']);
        $schedule['today_timestamp']    = strtotime(date("Y-m-d", time())." 00:00:00");
		$schedule['limit_timestamp']    = $schedule['today_timestamp'] + 86400;

        //교재 정보 가져오기
        $book = $this->Tutor_mdl->schedule_book_info($lesson['wl_lesson_id']);
        if($book['wb_book_link3']) $book['wb_book_link'] = $book['wb_book_link3'];

        //데이터변환
        $lesson['wlt_content']         = nl2br(common_input_out($lesson['wlt_content']));
        $lesson['wml_content']         = nl2br(common_input_out($lesson['wml_content']));
        $schedule['wsr_stu_info2']     = nl2br($schedule['wsr_stu_info2']);
        $schedule['wsr_grammar']       = stripslashes(common_input_out($schedule['wsr_grammar']));
        $schedule['wsr_comment']       = stripslashes(common_input_out($schedule['wsr_comment']));

        /*
            피드백 페이지가 리뉴얼 되었다.
            기존에는 wsr_pronunciation 에 text데이터가 들어가지만
            변경 후에는 json 데이터가 들어간다.
            기존 데이터는 임의로 쪼갤수가 없어서 기존형태와 리뉴얼 형태가 공존하게 된다.
        */
        $new_feedback = true;
        if($schedule['wsr_pronunciation'])
        {
            $pron_json_check = json_decode($schedule['wsr_pronunciation'], true);
            if($pron_json_check)
            {
                $schedule['wsr_pronunciation'] = $pron_json_check;
            }
            else
            {
                $new_feedback = false;
                $schedule['wsr_pronunciation'] = stripslashes(common_input_out($schedule['wsr_pronunciation']));
            }
        }

        /*
            다음시간에 수업있는지 체크. 비엇으면 수업연장가능
        */
        if($_SERVER['HTTP_HOST'] !='api.mint05.com')
        {
            $is_possible_extent = schedule_check_possible_extend_class($lesson['wm_uid'], $lesson['wl_lesson_id'], $wiz_tutor['wt_tu_uid'], strtotime($schedule['ws_endday']) + 1, $schedule['ws_cl_time'], '', $lesson['wm_d_id']);
            $check_request_extend = $this->lesson_mdl->row_class_extension_by_sc_id($request['sc_id']);
        }
        
        //해당학생 수업이 남아있는지

        $return_array['res_code']         = '0000';
        $return_array['msg']              = "";
        $return_array['data']['lesson']   = $lesson;
        $return_array['data']['schedule'] = $schedule;
        $return_array['data']['book']     = $book;
        $return_array['data']['lesson_prefer_type_text'] = lesson_prefer_type_text();
        $return_array['data']['new_feedback'] = $new_feedback;
        $return_array['data']['is_possible_extent'] = $is_possible_extent ? 1:0;
        $return_array['data']['extend_info'] = $check_request_extend;
        $return_array['data']['extend_code'] = $check_request_extend ? (new OldEncrypt('(*&DHajaan=f0#)2'))->encrypt($check_request_extend['idx']):'';
        echo json_encode($return_array);
        exit;
    }

    /**
     * 강사 스케쥴 상세보기 수정
     */
    public function modify_schedule()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "lesson_id"     => trim($this->input->post('lesson_id')), //필수
            "sc_id"         => trim($this->input->post('sc_id')), //필수
            "present"       => $this->input->post('present') ? trim($this->input->post('present')) : '',
            #"rating_ls"     => $this->input->post('rating_ls') ? trim($this->input->post('rating_ls')) : '',
            #"rating_ss"     => $this->input->post('rating_ss') ? trim($this->input->post('rating_ss')) : '',
            #"rating_pro"    => $this->input->post('rating_pro') ? trim($this->input->post('rating_pro')) : '',
            #"rating_voc"    => $this->input->post('rating_voc') ? trim($this->input->post('rating_voc')) : '',
            #"rating_cg"     => $this->input->post('rating_cg') ? trim($this->input->post('rating_cg')) : '',
            "pronunciation" => $this->input->post('pronunciation') ? trim(common_textarea_in($this->input->post('pronunciation'))) : '',
            "grammar"       => $this->input->post('grammar') ? trim(common_textarea_in($this->input->post('grammar'))) : '',
            "comment"       => $this->input->post('comment') ? trim(common_textarea_in($this->input->post('comment'))) : '',
            "topic_previous"=> $this->input->post('topic_previous') ? trim(common_textarea_in($this->input->post('topic_previous'))) : '',
            "topic_today"   => $this->input->post('topic_today') ? trim(common_textarea_in($this->input->post('topic_today'))) : '',
            "topic_next"    => $this->input->post('topic_next') ? trim(common_textarea_in($this->input->post('topic_next'))) : '',
            "absent_reason" => $this->input->post('absent_reason') ? trim($this->input->post('absent_reason')) : '',
            "renewal_ok"    => $this->input->post('renewal_ok') ? trim($this->input->post('renewal_ok')) : '',
            "renewal_reason"=> $this->input->post('renewal_reason') ? trim($this->input->post('renewal_reason')) : '',
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('lesson_mdl');

        $lesson = $this->lesson_mdl->row_schedule_by_sc_id_and_tu_uid($request['sc_id'], $wiz_tutor['wt_tu_uid']);

        if(!$lesson)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0212";
            $return_array['data']['err_msg']  = "No exists schedule!";
            echo json_encode($return_array);
            exit;
        }
        
        //6->하루연기, 7->장기연기
        if($lesson['present'] =='6' || $lesson['present'] =='7')
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0213";
            $return_array['data']['err_msg']  = "Student changed hold lesson!";
            echo json_encode($return_array);
            exit;
        }

        //스케쥴이 휴강일 때 상태값을 변경하지 못하도록
        if($lesson['present'] =='5' && $lesson['present'] != $request['present'])
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0214";
            $return_array['data']['err_msg']  = "Not changed holiday schedule!";
            echo json_encode($return_array);
            exit;
        }

        $request['uid'] = $lesson['uid'];
        $request['tu_name'] = $wiz_tutor['wt_tu_name'];
        
        //상태업데이트 함수 진입
        $result = lesson_schedule_state_change($request['sc_id'], $request);

        if($result['state'] === false)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = $result['msg'];
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg']      = "Success";
        echo json_encode($return_array);
        exit;
    }

    /**
     * 강사 스케쥴 상세보기 수정
     */
    public function modify_schedule_new()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "lesson_id"     => trim($this->input->post('lesson_id')), //필수
            "sc_id"         => trim($this->input->post('sc_id')), //필수
            "present"       => $this->input->post('present') ? trim($this->input->post('present')) : '',
            "grammar"       => $this->input->post('grammar') ? trim(common_textarea_in($this->input->post('grammar'))) : '',
            "comment"       => $this->input->post('comment') ? trim(common_textarea_in($this->input->post('comment'))) : '',
            "topic_previous"=> $this->input->post('topic_previous') ? trim(common_textarea_in($this->input->post('topic_previous'))) : '',
            "topic_today"   => $this->input->post('topic_today') ? trim(common_textarea_in($this->input->post('topic_today'))) : '',
            "topic_next"    => $this->input->post('topic_next') ? trim(common_textarea_in($this->input->post('topic_next'))) : '',
            "absent_reason" => $this->input->post('absent_reason') ? trim($this->input->post('absent_reason')) : '',
            "renewal_ok"    => $this->input->post('renewal_ok') ? trim($this->input->post('renewal_ok')) : '',
            "renewal_reason"=> $this->input->post('renewal_reason') ? trim($this->input->post('renewal_reason')) : '',

            //아래 신규 항목은 배열로 받는다.
            "corrections_student" => $this->input->post('corrections_student'),             //1개 이상
            "corrections_better"  => $this->input->post('corrections_better'),             //1개 이상
            "pronunciations" => $this->input->post('pronunciations'),       //0개 이상
            "today_expression" => $this->input->post('today_expression'),     //0개 이상
            "today_explanation" => $this->input->post('today_explanation'),   //0개 이상
            "great_job" => $this->input->post('great_job'),                 //1개 이상
            "need_to_improve" => $this->input->post('need_to_improve'),     //1개 이상
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('lesson_mdl');

        $lesson = $this->lesson_mdl->row_schedule_by_sc_id_and_tu_uid($request['sc_id'], $wiz_tutor['wt_tu_uid']);

        if(!$lesson)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0212";
            $return_array['data']['err_msg']  = "No exists schedule!";
            echo json_encode($return_array);
            exit;
        }
        
        //6->하루연기, 7->장기연기
        if($lesson['present'] =='6' || $lesson['present'] =='7')
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0213";
            $return_array['data']['err_msg']  = "Student changed hold lesson!";
            echo json_encode($return_array);
            exit;
        }

        //스케쥴이 휴강일 때 상태값을 변경하지 못하도록
        //북미강사는 제외(직접 휴강상태를 조정할수있기 때문)
        if($lesson['present'] =='5' && $lesson['present'] != $request['present'] && $wiz_tutor['mc_nationAs'] != 'usa')
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0214";
            $return_array['data']['err_msg']  = "Not changed holiday schedule!";
            echo json_encode($return_array);
            exit;
        }

        //출석일때만 처리
        if($request['present'] == '2')
        {
                
            if(!is_array($request['corrections_student']) || $request['corrections_student'][0]['value'] == '')
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0217";
                $return_array['data']['err_msg']  = "Enter at least one 'Corrections of words and sentences'";
                echo json_encode($return_array);
                exit;
            }

            if(!is_array($request['corrections_better']) || $request['corrections_better'][0]['value'] == '')
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0217";
                $return_array['data']['err_msg']  = "Enter at least one 'Corrections of words and sentences'";
                echo json_encode($return_array);
                exit;
            }

            if(count($request['corrections_better']) != count($request['corrections_student']))
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0217";
                $return_array['data']['err_msg']  = "Enter at least one 'Corrections of words and sentences'";
                echo json_encode($return_array);
                exit;
            }

            if(!is_array($request['great_job']) || $request['great_job'][0]['value'] == '')
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0217";
                $return_array['data']['err_msg']  = "Enter at least one 'great job'";
                echo json_encode($return_array);
                exit;
            }
            
            if(!is_array($request['need_to_improve']) || $request['need_to_improve'][0]['value'] == '')
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0217";
                $return_array['data']['err_msg']  = "Enter at least one 'Needs improvement'";
                echo json_encode($return_array);
                exit;
            }

            if(count($request['today_explanation']) != count($request['today_expression']))
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0218";
                $return_array['data']['err_msg']  = "Enter both 'expression' and 'explanation' on 'Today’s expression'";
                echo json_encode($return_array);
                exit;
            }
        }


        /**
         * 강사 휴강 처리(단! 지금은 북미에서만 바꿀 수 있어야한다)
         * 본래 관리자에서 강사 휴강처리할때 쓰는 기능임
         * 수업상태가 1~4였다가 5~6(휴강)으로 처리 된 경우 > 스케쥴 마지막날에 스케쥴 추가
         * 수업상태가 5~6였다가 1~4로 변경 된 경우 > 마지막날 스케쥴 삭제
         * 실사용시 : false > $wiz_tutor['mc_nationAs'] == 'usa'
         */
        $present = $request['present'];
        $request['present'] = substr($present, 0, 1);
        $ab_ok = substr($present, 1, 1);
        $request['ab_ok'] = $ab_ok ? $ab_ok : 'N'; 
        
        if($lesson['wl_cl_gubun'] != '2' && $wiz_tutor['mc_nationAs'] == 'usa')
        {
            //현재 수업 상태 : $lesson['present']
            //변경 수업 상태 : $request['present']

            if(preg_match("/^[5-6]{1}$/",$request['present']) && preg_match("/^[1-4]{1}$/",$lesson['present']))
            {
                // 1~4 였는데 5~6 으로 변경한 경우 > class_add

                //정규강사가 postpone 이면 장기연장이므로 추가되는 수업은 postpone(7)이 되어야 하며, 그 외에는 READY(1)이 된다.
                $present_status = $lesson['tu_uid'] == '158' ? 7 : 1;

                //마지막 수업일 이후에 스케줄 추가
                $set_schedule = schedule_add_new_schedule_to_last($lesson['lesson_id'], $lesson['uid'], $present_status);
                if (!$set_schedule)
                {
                    //이 수업의 마지막 날짜에 스케줄 추가하는 작업을 실패했습니다.
                    $return_array['res_code']         = '0900';
                    $return_array['msg']              = "프로세스오류";
                    $return_array['data']['err_code'] = "0219";
                    $return_array['data']['err_msg']  = "Faild to add a schedule after the last schedule for this lesson.";
                    echo json_encode($return_array);
                    exit;
                }

                //스케줄 재정리 작업 진행
                //휴일처리 현재는 포함하지않음
                lesson_rescheduling_next_lesson($lesson['lesson_id'], $lesson['uid']);
            }
            else if(preg_match("/^[1-4]{1}$/",$request['present']) && preg_match("/^[5-6]{1}$/",$lesson['present']))
            {
                // 5~6 이었는데 1~4로 변경한 경우 > class_del

                //마지막 수업일의 스케줄 삭제
                $remove_schedule = schedule_remove_last_schedule($lesson['lesson_id'], $lesson['uid']);
                if (!$remove_schedule)
                {
                    //이 수업의 마지막 스케줄 삭제를 실패했습니다.
                    $return_array['res_code']         = '0900';
                    $return_array['msg']              = "프로세스오류";
                    $return_array['data']['err_code'] = "0219";
                    $return_array['data']['err_msg']  = "Faild to delete the last schedule for this lesson.";
                    echo json_encode($return_array);
                    exit;
                }

                //스케줄 재정리 작업 진행
                //휴일처리 현재는 포함하지않음
                lesson_rescheduling_next_lesson($lesson['lesson_id'], $lesson['uid']);
            }
        }


        $request['pronunciation'] = json_encode([
            'corrections_better'    => array_filter($request['corrections_better']),
            'corrections_student'   => array_filter($request['corrections_student']),
            'pronunciations'        => array_filter($request['pronunciations']),
            'today_expression'      => array_filter($request['today_expression']),
            'today_explanation'     => array_filter($request['today_explanation']),
            'great_job'             => array_filter($request['great_job']),
            'need_to_improve'       => array_filter($request['need_to_improve']),
        ]);

        $request['uid'] = $lesson['uid'];
        $request['tu_name'] = $wiz_tutor['wt_tu_name'];
        $request['new_feedback'] = true;
        
        //상태업데이트 함수 진입
        $result = lesson_schedule_state_change($request['sc_id'], $request);

        if($result['state'] === false)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = $result['msg'];
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg']      = "Success";
        echo json_encode($return_array);
        exit;
    }


    /**
     * 강사 스케쥴 상세보기 - 말톡 녹화파일 조회
     */
    public function checked_maalk_history_result()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "sc_id"         => trim($this->input->post('sc_id')), //필수
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('tutor_mdl');
        
        $result = $this->tutor_mdl->checked_maalk_history_result($request['sc_id']);
        
        $now = date('Y-m-d H:i:s');
        $start_day = date("Y-m-d H:i", strtotime("-9 hours -2 minutes", strtotime($result['ws_startday'])));
        $end_day = date("Y-m-d H:i", strtotime("-9 hours +2 minutes", strtotime($result['ws_endday'])));

        $room_start_day = date("Y-m-d H:i", strtotime("-9 hours -5 minutes", strtotime($result['ws_startday'])));
        // room_end_day > 11:59:59 초  형태로 돼있어서 짤라내면 59-2 > 57분
        $room_end_day = date("Y-m-d H:i", strtotime("-9 hours -2 minutes", strtotime($result['ws_endday'])));

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg']     = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code']                       = '0000';
        $return_array['msg']                            = "녹화 파일 조회 성공";
        $return_array['data']['list']                   = $result;
        $return_array['data']['list']['start_day']      = $start_day;
        $return_array['data']['list']['end_day']        = $end_day;
        $return_array['data']['list']['room_start_day'] = $room_start_day;
        $return_array['data']['list']['room_end_day']   = $room_end_day;
        echo json_encode($return_array);
        exit;
    }

    /**
     * SMS 전송 - 회원정보 조회, 템플릿 조회
     */
    public function get_member_with_sms_templete()
    {
        $return_array = array();    

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "uid"           => trim($this->input->post('uid')), //필수
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('tutor_mdl');
        $this->load->model('sms_mdl');
        
        $member = $this->tutor_mdl->get_wiz_member_by_wiz_dealer($request['uid']);
        if(!$member)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No Data(1)";
            echo json_encode($return_array);
            exit;
        }

        $templete = $this->sms_mdl->get_all_sms_templete();
        if(!$templete)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No Data(2)";
            echo json_encode($return_array);
            exit;
        }

        if($member['wd_schedule_yn'] == "y") {
            if($member['wb_sms_receive'])
                $member['return_tel'] = $member['wb_sms_receive'];
            else
                $member['return_tel'] = str_replace("-","",$member['dea_tel']);
        } else {
            $config = $this->tutor_mdl->get_wiz_config();
            $member['return_tel'] = str_replace("-","",$config['send_number']);
        }

        foreach($templete as $key=>$val)
        {
            $templete[$key]['sms_title']   = common_input_out($val['sms_title']);
            $templete[$key]['sms_content'] = common_textarea_out($val['sms_content']);

            if($member['wd_schedule_yn'] == "y") {
                $templete[$key]['sms_content'] = str_replace("민트영어",$member['wd_d_name'],$templete[$key]['sms_content']);
                $templete[$key]['sms_content'] = str_replace("민트",$member['wd_d_name'],$templete[$key]['sms_content']);

                if($member['wb_sms_receive'])
                    $templete[$key]['tel'] = $member['wd_sms_receive']; 
                else
                    $templete[$key]['tel'] = str_replace("-","",$member['wd_dea_tel']);
            } else {
                $templete[$key]['tel'] = str_replace("-","",$val['sms_return_no']);
            }
        }

        $return_array['res_code']         = '0000';
        $return_array['msg']              = "템플릿 조회 성공";
        $return_array['data']['member']   = $member;
        $return_array['data']['templete'] = $templete;
        echo json_encode($return_array);
        exit;
    }

    /**
     * SMS 전송
     */
    public function tutor_send_sms()
    {
        $return_array = array();    

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "mobile"        => trim($this->input->post('mobile')), //필수
            "content"       => trim($this->input->post('content')), //필수
            "uid"           => trim($this->input->post('uid')), //필수
            "reserve_date"  => $this->input->post('reserve_date') ? $this->input->post('reserve_date') : date('Y-m-d H:i:s')
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        //강사정보  
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $member = array('wm_uid'=>'', 'wm_wiz_id'=>'', 'wm_name'=>'');
        if($request['uid'])
        {
            $this->load->model('tutor_mdl');
            $member = $this->tutor_mdl->get_member($request['uid']);
        }

        $options = array(
            'uid'          => $member['wm_uid'],
            'wiz_id'       => $member['wm_wiz_id'],
            'name'         => $member['wm_name'],
            'content'      => $request['content'],
            'reserve_date' => $request['reserve_date'],
            'man_id'       => $wiz_tutor['wt_tu_id'],
            'man_name'     => $wiz_tutor['wt_tu_name']
        );        
        $mobile_num = str_replace(",",";",$request['mobile']);
        $mobile_num = str_replace("-","",$mobile_num);

        $sms = sms::send_sms($mobile_num,'',$options);
        if(!$sms['state'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg']      = "process error";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg']      = "SMS send success";
        echo json_encode($return_array);
        exit;
    }

    //임시브레이크 설정
    public function set_break()
    {
        $return_array = array();

        $request = array(
            "tu_id" => trim(strtolower($this->input->post('tu_id'))),
            "authorization" => trim($this->input->post('authorization')),
            "break_time" => trim($this->input->post('break_time')),
            "break_set" => trim($this->input->post('break_set')),
        );
        
        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('tutor_mdl');
        //임시브레이크는 오늘날짜만 설정가능하다.
        $today = date('Y-m-d');
        $time = $request['break_time'].':00';

        $check = $this->tutor_mdl->checked_tutor_break_temp($wiz_tutor['wt_tu_uid'], $today, $time);

        //설정
        if($request['break_set'])
        {
            if($check['cnt'] ==0)
            {
                // 해당시간 브레이크 설정가능한지 스케쥴 확인
                $checked = $this->tutor_mdl->checked_tutor_break_possible_time($wiz_tutor['wt_tu_uid'], $today.' '.$time);

                if($checked['cnt'] ==0)
                {
                    $param = [
                        'tu_uid' => $wiz_tutor['wt_tu_uid'],
                        'date'   => $today,
                        'time'   => $time,
                        'alldays' => 0,
                        'man_id' => '',
                        'man_name' => '',
                        'regdate' => date('Y-m-d H:i:s'),
                    ];
                    $this->tutor_mdl->insert_wiz_tutor_breakingtime($param);
                }
                else
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "process error";
                    $return_array['data']['err_code'] = "0210";
                    $return_array['data']['err_msg'] = "Schedule already exists.";
                    echo json_encode($return_array);
                    exit;
                }
            }
        }
        //설정삭제
        else
        {
            $where = [
                'tu_uid' => $wiz_tutor['wt_tu_uid'],
                'date'   => $today,
                'time'   => $time,
                'alldays' => 0,
            ];
            $this->tutor_mdl->delete_wiz_tutor_breakingtime($where);
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = 'SUCCESS '.($request['break_set'] ? 'Set':'Cancel')." break: ". $request['break_time'];
        echo json_encode($return_array);
        exit;
    }

    // 강사 영어첨삭/수업대본 리스트 조회 API
    public function special_()
    {
        $return_array = array();

        $request = array(
            "type" => strtolower($this->input->post('type')) ? strtolower($this->input->post('type')) : 'correction',
            "tu_id" => trim(strtolower($this->input->post('tu_id'))),
            // "tu_uid" => trim(strtolower($this->input->post('tu_uid'))),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "w_step" => trim($this->input->post('w_step')) ? trim($this->input->post('w_step')) : '',   //w_step : 1:Ready, 2:Complete, 3:On going
            "search" => trim($this->input->post('search')) ? trim($this->input->post('search')) : '',
            "keyword" => trim($this->input->post('keyword')) ? trim($this->input->post('keyword')) : '',
            "start" => trim($this->input->post('start')) ? trim($this->input->post('start')) : '0',
            "limit" => trim($this->input->post('limit')) ? trim($this->input->post('limit')) : '20',
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mb.w_id",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );
        
        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('board_mdl');
        $this->load->model('tutor_mdl');
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        if($request['table_code'] == 'correction')
        {

            $request['order_field'] = $this->input->post('order_field') ? $this->input->post('order_field') : "mb.w_id";
            
            $where = " WHERE mb.tu_uid = '{$wiz_tutor['wt_tu_uid']}'";
            // 영어 첨삭 게시판
            if($request['w_step'] == '1')
            {
                // $where = ' WHERE (w_step = 1 || w_step = 3)';
                $where = " WHERE mb.tu_uid = '{$wiz_tutor['wt_tu_uid']}' AND mb.w_step IN ('1', '3')";
            }
            else if($request['w_step'] == '2')
            {
                $where = " WHERE mb.tu_uid = '{$wiz_tutor['wt_tu_uid']}' AND mb.w_step = '{$request['w_step']}'";
            }
    
            if($request['search'] && $request['keyword']){
                
                $where .= " AND {$request['search']} LIKE '%{$request['keyword']}%'";
            }
            // echo $where;exit;

            $list_cnt = $this->board_mdl->list_count_board_wiz_correct($where);
        
            if($list_cnt['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "No Data";
                echo json_encode($return_array);
                exit;
            }
            
            $order = " ORDER BY ".$request['order_field']." ".$request['order'];
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
            $list_board = $this->tutor_mdl->tutor_list_board_wiz_correct('', $where, $order, $limit, '');
        
        }
        else if($request['table_code'] == '1130')
        {
            // 수업대본 서비스

            $request['order_field'] = $this->input->post('order_field') ? $this->input->post('order_field') : "mb.mb_unq";

            $where = " WHERE mb.table_code = {$request['table_code']} AND mb.tu_uid = '{$wiz_tutor['wt_tu_uid']}'";

            if($request['search'] && $request['keyword']){
                //속도문제로 wiz_member 조인 안하고 서브쿼리로 변경
                if($request['search'] == 'wm.ename')
                {
                    $where .= " AND mb.wiz_id IN (SELECT wm.wiz_id FROM wiz_member as wm WHERE wm.ename like '%{$request['keyword']}%')";
                }
                else
                {
                    $where .= " AND {$request['search']} LIKE '%{$request['keyword']}%'";
                }
            }

            $list_cnt = $this->board_mdl->list_count_board('', $where);

            if($list_cnt['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "No Data(2)";
                echo json_encode($return_array);
                exit;
            }

            $select_col_content = ",(SELECT mi.money FROM mint_incentive mi WHERE mi.lesson_id = mb.mb_unq AND in_kind = '14' ORDER BY mi.in_id DESC LIMIT 1) AS mi_incentive_money,
                                    (SELECT mi.money FROM mint_incentive mi WHERE mi.lesson_id = mb.mb_unq AND in_kind = '13' ORDER BY mi.in_id DESC LIMIT 1) AS mi_incentive_point";
            $order = " ORDER BY ".$request['order_field']." ".$request['order'];
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
            $list_board = $this->board_mdl->list_board('', $where, $order, $limit, $select_col_content);

            //속도문제로 wiz_member 조인 안하고 아래 정보는 따로 가져온다
            //wm.regi_gubun AS wm_regi_gubun, wm.email AS wm_email, wm.social_email AS wm_social_email, wm.ename AS wm_ename
            $list_board = board_list_add_wizmember_info($list_board, 'mb_wiz_id');
            
        }



        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['list'] = $list_board;
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['social_icon'] = common_social_icon();
        // $return_array['data']['notice_msg'] = $tutor_valid_msg;
        echo json_encode($return_array);
        exit;
        
    }

    // 강사 영어첨삭/수업대본서비스 상세보기 API
    public function article_special()
    {
        $return_array = array();

        $request = array(
            "mb_unq" => trim(strtolower($this->input->post('mb_unq'))),
            "tu_id" => trim($this->input->post('tu_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('board_mdl');

        if($request['table_code'] == 'correction')
        {
            // 영어 첨삭 게시판 상세보기
            $where = " WHERE mb.w_id = {$request['mb_unq']}";
    
            $list_board_count = $this->board_mdl->list_count_board_wiz_correct('', $where, $request['order'], $request['limit'], '');
    
            if($list_board_count['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "No Data";
                echo json_encode($return_array);
                exit;
            }
            
            $article = $this->board_mdl->row_article_wiz_correct_by_pk($request['mb_unq']);
            
            //데이터변환
            $article['mb_reply'] = common_textarea_out($article['mb_reply']);
        }
        else
        {
            // 수업대본서비스 게시판 상세보기

            $where = " WHERE mb.mb_unq = {$request['mb_unq']}";

            $list_board_count = $this->board_mdl->list_count_board('', $where, $request['order'], $request['limit'], '');
    
            if($list_board_count['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "No Data(2)";
                echo json_encode($return_array);
                exit;
            }
            
            $article = $this->board_mdl->row_board_by_mb_unq($request['table_code'], $request['mb_unq']);
            
            //데이터변환
            $article['mb_content'] = common_textarea_out($article['mb_content']);
            
            if(!$article['mb_cafe_unq'])
            {   
                $sim_content2 = explode("__",$article['mb_sim_content2']);
                $article['mc_b_kind'] = $sim_content2[0];
                $article['mc_vd_url'] = $article['mb_sim_content'];
            }
            $article['mc_vd_url'] = common_textarea_out($article['mc_vd_url']);
            
        }

        //소셜 아이콘
        $article['wm_id_val'] = tutor_get_sosocial_icon($article['wm_regi_gubun'], $article['mb_wiz_id'], $article['wm_social_email']);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['info'] = $article;
        echo json_encode($return_array);
        exit;
    }

    // 강사 영어첨삭/수업대본서비스 수정 API
    public function modify_article_special()
    {
        $return_array = array();

        $request = array(
            "tu_id" => trim($this->input->post('tu_id')),
            "mb_unq" => trim(strtolower($this->input->post('mb_unq'))),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "w_step" => trim($this->input->post('w_step')) ? trim($this->input->post('w_step')) : '',   //w_step : 1:Ready, 2:Complete, 3:On going
            "w_reply" => $this->input->post('w_reply') ? $this->input->post('w_reply') : '',            // 영어 첨삭 서비스 강사 답변 컬럼
            "work_state" => trim($this->input->post('work_state')) ? trim($this->input->post('work_state')) : '',   //work_state : 4:on going, 5:Complete
            "content" => $this->input->post('content') ? $this->input->post('content') : '',            // 수업 대본 서비스 강사랑 학생 content 하나로 수정
            "files" => isset($_FILES["files"]) ? $_FILES["files"] : null,
        );

        /*
            영어첨삭 서비스 일 때 w_step -> w_step 진행상황(1:Ready, 2:Complete, 3:On going)
            수업대본 서비스 일 때 w_step -> work_state (1:빈값, 2:Translation(미정), 3:Claim(요청), 4:On going(진행중), 5:complete(완료), 6:확인완료, 7:재점검요망, 8:보류, 9:일부완료)
        */
        
        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('board_mdl');

        if($request['table_code'] == 'correction')
        {
            // 영어 첨삭 서비스
            $where = " WHERE mb.w_id = {$request['mb_unq']}";
            $list_board_count = $this->board_mdl->list_count_board_wiz_correct('', $where, $request['order'], $request['limit'], '');
    
            if($list_board_count['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "No Data";
                echo json_encode($return_array);
                exit;
            }
            
            $article = $this->board_mdl->row_article_wiz_correct_by_pk($request['mb_unq']);
            $file_name = $article['mb_tutor_upfile'];

            // 파일업로드
            if($request["files"])
            {

                if($article['mb_tutor_upfile'])
                {
                    S3::delete_s3_object($this->upload_path_correct, $article['mb_tutor_upfile']);
                }
                
                $upload_limit_size = 5;
                
                $ext_array = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'mp3', 'mp4');
                
                $this->load->library('s3');

                $res = S3::put_s3_object($this->upload_path_correct, $request["files"], $upload_limit_size, $ext_array);

                if($res['res_code'] != '0000')
                {
                    echo json_encode($res);
                    exit;
                }
                
                $file_name = $res['file_name'];
            }
            
            $update_param = [
                'tu_uid'         => $wiz_tutor['wt_tu_uid'],
                'tu_name'         => $wiz_tutor['wt_tu_name'],
                // 'table_code'    => $request['table_code'],
                'w_reply'       => $request['w_reply'],
                'w_step'       => $request['w_step'],
                'filename'      => $file_name,
                'w_replydate'   => date('Y-m-d H:i:s'),
            ];
            
            $result = $this->board_mdl->update_correct($update_param, $request['mb_unq']);
    
            if($result < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }
            
            
            // 알림톡 전송  ( w_step == 2 : Complete )
            if($article['mb_rsms'] == 'Y' && $request['w_step'] == '2')
            {
                $options = array(
                    'name'  =>  $article['mb_name'],
                    'uid'  =>  $article['mb_uid'],
                    'wiz_id'  =>  $article['mb_wiz_id'],
                );
            
                sms::send_atalk($article['wm_mobile'], 'MINT06002P', $options);
            }

            // Ready : 1, Complete : 2, On going : 3 
            // on going -> complete 상태값으로 변경했을때 알림추가
            if($article['mb_w_step'] != '2' && $request['w_step'] == '2')
            {

                $this->load->model('notify_mdl');
    
                /* 게시글 작성자 알림*/
                $notify = array(
                    'uid' => $article['mb_uid'], 
                    'code' => ($request["files"]) ? 122 : 121, 
                    'message' => ($request["files"]) ? '영어첨삭이 완료되었습니다. MP3는 업로드가 다소 지연될 수 있습니다.' : '영어첨삭이 완료되었습니다.', 
                    'table_code' => 'correction.view', 
                    'user_name' => '관리자',
                    'board_name' => '1:1영어첨삭게시판', 
                    'content'=> $article['mb_content'], 
                    'mb_unq' => $request['mb_unq'], 
                    'regdate' => date('Y-m-d H:i:s'),
                );
    
                $notify_result = $this->notify_mdl->insert_notify($notify);
    
                if($notify_result < 0)
                {
                    $return_array['res_code'] = '0500';
                    $return_array['msg'] = "DB ERROR";
                    echo json_encode($return_array);
                    exit;
                }
            }

            // Ready : 1, Complete : 2, On going : 3 
            // complete -> on going 상태값으로 변경했을때 알림추가
            if($article['mb_w_step'] == '2' && $request['w_step'] != '2')
            {
                $this->load->model('notify_mdl');

                /* 게시글 작성자 알림*/
                $notify = array(
                    'removed' => 1,
                    'disabled' => 1,
                    'view' => 1
                );

                $where = array(
                    'uid' => $article['wm_uid'],
                    'table_code' => 'correction.view',
                    'mb_unq' => $request['mb_unq'],
                );

                $where_in = array('121', '122');

                $notify_result = $this->notify_mdl->disabled_notify_where_in($notify, $where, $where_in);
                
                if($notify_result < 0)
                {
                    $return_array['res_code'] = '0500';
                    $return_array['msg'] = "DB ERROR";
                    echo json_encode($return_array);
                    exit;
                }
            }

        }
        else
        {
            // 수업 대본 서비스
            // boards/modify.proc.php
            // work_state (1:빈값, 2:Translation(미정), 3:Claim(요청), 4:On going(진행중), 5:complete(완료), 6:확인완료, 7:재점검요망, 8:보류, 9:일부완료)
            // 글 등록시 work_state 1 이었다가 강사 배정시 4로 바뀜
            $where = " WHERE mb.mb_unq = {$request['mb_unq']}";
            $list_board_count = $this->board_mdl->list_count_board('', $where, $request['order'], $request['limit'], '');
    
            if($list_board_count['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "No Data(2)";
                echo json_encode($return_array);
                exit;
            }
            
            $article = $this->board_mdl->row_board_by_mb_unq($request['table_code'], $request['mb_unq']);

            $update_param = [
                // 파람 값 확인해서 넣기
                // 'tu_id'         => $request['tu_id'],
                // 'table_code'    => $request['table_code'],
                'content'       => $request['content'],
                'work_state'       => $request['work_state'],
                // 'w_replydate'   => date('Y-m-d H:i:s'),
                // 'w_regdate'     => date('Y-m-d H:i:s'),
            ];

            $result = $this->board_mdl->update_article($update_param, $request['mb_unq'], $article['mb_wiz_id']);
    

            if($result < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }


            // 알림톡 전송  ( w_step == 5 : Complete )
            if($article['mb_rsms'] == 'Y' && $request['w_step'] == '5')
            {
                $options = array(
                    'name'  =>  $article['mb_name'],
                    'uid'  =>  $article['wm_uid'],
                    'wiz_id'  =>  $article['mb_wiz_id'],
                );
            
                sms::send_atalk($article['wm_mobile'], 'MINT06002T', $options);
            }

            // On going : 4, Complete : 5
            // on going -> complete 상태값으로 변경했을때 알림추가
            if($article['mb_work_state'] != '5' && $request['w_step'] == '5')
            {
                $this->load->model('notify_mdl');
    
                /* 게시글 작성자 알림*/
                $notify = array(
                    'uid' => $article['wm_uid'], 
                    'code' => 123, 
                    'message' => '수업대본서비스가 완료되었습니다.',
                    'table_code' => '1130', 
                    'user_name' => '관리자',
                    'board_name' => '수업대본서비스', 
                    'content'=> $article['mb_content'], 
                    'mb_unq' => $request['mb_unq'], 
                    'regdate' => date('Y-m-d H:i:s'),
                );
    
                $notify_result = $this->notify_mdl->insert_notify($notify);
    
                if($notify_result < 0)
                {
                    $return_array['res_code'] = '0500';
                    $return_array['msg'] = "DB ERROR";
                    echo json_encode($return_array);
                    exit;
                }
            }


            // On going : 4, Complete : 5
            // complete -> on going 으로 다시 변경했을때 기존에 등록된 알림 삭제 처리
            if($article['mb_work_state'] == '5' && $request['w_step'] != '5')
            {
                $this->load->model('notify_mdl');
    
                /* 게시글 작성자 알림*/
                $notify = array(
                    'removed' => 1,
                    'disabled' => 1,
                    'view' => 1
                );

                $where = array(
                    'uid' => $article['wm_uid'],
                    'code' => 123,
                    'table_code' => '1130',
                    'mb_unq' => $request['mb_unq'],
                );
                
                $notify_result = $this->notify_mdl->disabled_notify($notify, $where);
    
                if($notify_result < 0)
                {
                    $return_array['res_code'] = '0500';
                    $return_array['msg'] = "DB ERROR";
                    echo json_encode($return_array);
                    exit;
                }
            }
            
        }    


        $return_array['res_code'] = '0000';
        $return_array['msg'] = "OK, modified";
        echo json_encode($return_array);
        exit;
    }

    /*
        강사 BOARD - 공지사항 목록
    */
    public function board_notice_list_()
    {
        $return_array = array();

        $request = array(
            'start' => $this->input->post('start') ? $this->input->post('start') : 0,
            'limit' => $this->input->post('limit') ? $this->input->post('limit') : '20',
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
            "search" => trim($this->input->post('search')),
            "keyword" => trim($this->input->post('keyword')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtoupper($this->input->post('order_field'))) : "mnb.nb_unq",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $search = array();
        $search[] = "(mnb.tu_id = '".$wiz_tutor['wt_tu_id']."' || mnb.tu_id='all')";

        //검색
        if($request['search'] && $request['keyword']) $search[] = "mnb.".$request['search']." like '%".$request['keyword']."%'";

        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('Tutor_mdl');
        $list_cnt = $this->Tutor_mdl->list_tutor_notice_count($where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data.";
            echo json_encode($return_array);
            exit;
        }

        $limit = "";
        
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        $result = $this->Tutor_mdl->list_notice_board($where, $order, $limit);

        if($result < 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data.(2)";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /*
        강사 BOARD - 공지사항 정보(view)
    */
    public function board_notice_article()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
            "no" => trim($this->input->post('no')), //필수
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('Tutor_mdl');
        $result = $this->Tutor_mdl->writer_notice_board($request['no']);
        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data";
            echo json_encode($return_array);
            exit;
        }

        //데이터변환
        $result['mnb_content'] = common_textarea_out($result['mnb_content']);
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "게시물조회성공";
        $return_array['data']['info'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /*
        강사 BOARD - 강사와 1:1 목록
    */
    public function board_toteacher_list_()
    {
        $return_array = array();

        $request = array(
            'start' => $this->input->post('start') ? $this->input->post('start') : 0,
            'limit' => $this->input->post('limit') ? $this->input->post('limit') : '20',
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
            "search" => trim($this->input->post('search')),
            "keyword" => trim($this->input->post('keyword')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtoupper($this->input->post('order_field'))) : "wt.to_id",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $search = array();
        $search[] = "wt.tu_uid = '".$wiz_tutor['wt_tu_uid']."'";

        //검색
        if($request['search'] && $request['keyword']) $search[] = "wt.".$request['search']." like '%".$request['keyword']."%'";

        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('Tutor_mdl');
        $list_cnt = $this->Tutor_mdl->list_tutor_toteacher_count($where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data";
            echo json_encode($return_array);
            exit;
        }

        $limit = "";
        
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        $result = $this->Tutor_mdl->list_toteacher_board($where, $order, $limit);

        if($result < 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data(2)";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /*
        강사 BOARD - 강사와 1:1 정보(view)
    */
    public function board_toteacher_article()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
            "no" => trim($this->input->post('no')), //필수
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('Tutor_mdl');
        $result = $this->Tutor_mdl->writer_toteacher_board($wiz_tutor['wt_tu_uid'], $request['no']);

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data";
            echo json_encode($return_array);
            exit;
        }

        $result['wt_memo'] = stripslashes($result['wt_memo']);
        if($result['wt_c_yn'] == "n") $result['wt_memo'] = nl2br($result['wt_memo']);

        $result['wt_reply'] = common_textarea_out($result['wt_reply']);
        if($result['wt_r_yn'] == "n") $result['wt_reply'] = nl2br($result['wt_reply']);

        $result['wt_id_val'] = tutor_get_sosocial_icon($result['wt_regi_gubun'], $result['wt_wiz_id'], $result['wt_social_email']);
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "게시물조회성공";
        $return_array['data']['info'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /*
        강사와 1:1게시판 학생에게 쪽지 보내기
    */
    public function board_message_write()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "uid"           => trim($this->input->post('uid')), //필수
            "title"         => ($this->input->post('title')) ? trim($this->input->post('title')) : NULL,
            "file"          => isset($_FILES["file"]) ? $_FILES["file"] : null,
            "file2"         => isset($_FILES["file2"]) ? $_FILES["file2"] : null,
            "memo"          => ($this->input->post('memo')) ? trim($this->input->post('memo')) : NULL,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        //s3파일 업로드
        $file_name = null;
        if($request['file'])
        {
            $upload_limit_size = 10; // TODO: 기존 파일등록시 업로드 크기제한이 없음
            $ext_array = array('jpg', 'jpeg', 'png', 'gif');

            $this->load->library('s3');
            $res = S3::put_s3_object($this->upload_path_teacher_1n1, $request["file"], $upload_limit_size, $ext_array);
            if($res['res_code'] != '0000')
            {
                echo json_encode($res);
                exit;
            }

            $file_name = $res['file_name'];
        }

        $file_name2 = null;
        if($request['file2'])
        {
            $upload_limit_size = 10; // TODO: 기존 파일등록시 업로드 크기제한이 없음
            $ext_array = array('doc', 'docx', 'ppt', 'pptx', 'xlsx', 'mp3');

            $this->load->library('s3');
            $res = S3::put_s3_object($this->upload_path_teacher_1n1, $request["file2"], $upload_limit_size, $ext_array);
            if($res['res_code'] != '0000')
            {
                echo json_encode($res);
                exit;
            }

            $file_name2 = $res['file_name'];
        }

        
        // 발송 회원(학생) 정보 찾기
        $this->load->model('Tutor_mdl');
        if($request['uid'] == 'ALL')
        {
            $now = date('Y-m-d H:i:s');
            $where2 = " WHERE wl.tu_uid = ".$wiz_tutor['wt_tu_uid']." AND '".$now."' BETWEEN wl.startday AND wl.endday GROUP BY wl.uid";
        }
        else
        {
            $where2 = " WHERE wl.uid = ".$request['uid']." limit 1";
        }
        $result = $this->Tutor_mdl->list_message_student($where2);

        $memo = $request['memo'];
        $tu_name = $wiz_tutor['wt_tu_name'];

        foreach($result as $row)
        {
            $member = $this->Tutor_mdl->get_member($row['wl_uid']);
            if(!$member) continue;

            $article = array(
                'uid'       => $member['wm_uid'],
                'wiz_id'    => $member['wm_wiz_id'],
                'name'      => $member['wm_name'],
                'ename'     => $member['wm_ename'],
                'tu_uid'    => $wiz_tutor['wt_tu_uid'],
                'tu_name'   => $tu_name,
                'title'     => $request['title'],
                'filename'  => $file_name,
                'filename2' => $file_name2,
                'memo'      => $memo,
                'step'      => 'N',
                'to_gubun'  => 'T',
                'regdate'   => date('Y-m-d H:i:s')
            );
            
            $insert = $this->Tutor_mdl->write_message($article);
            if($insert < 0) continue;

            // 강사와 1:1게시판 등록 성공시 SMS 전송
            if($member['wm_mobile'])
            {
                $content = $tu_name." 선생님으로부터 메세지가 도착하였습니다. ".tutor_message_make_viwe_link($insert);
                $options = array(
                    'uid'      => $member['wm_uid'],
                    'wiz_id'   => $member['wm_wiz_id'],
                    'name'     => $member['wm_name'],
                    'content'  => $content,
                    'man_id'   => $wiz_tutor['wt_tu_id'],
                    'man_name' => $tu_name
                );
                $mobile_num = str_replace("-","",$member['wm_mobile']);
                sms::send_sms($mobile_num,'',$options);
            }

            // 답변등록 알림
            $this->load->model('notify_mdl');
            
            $aNotifyData = array(
                'uid'        => $member['wm_uid'],
                'code'       => '105',
                'table_code' => 'teacher.view',
                'board_name' => '강사와1:1게시판',
                'user_name'  => $tu_name,
                'content'    => $memo,
                'message'    => str_replace('%%',$tu_name,'%% 선생님으로부터 메세지가 도착하였습니다.'),
                'mb_unq'     => $insert,
                'co_unq'     => NULL,
                'go_url'     => tutor_message_make_viwe_link($insert),
                'regdate'    => date('Y-m-d H:i:s')
            );
            $notify_result = $this->notify_mdl->insert_notify($aNotifyData);

            // 푸시 알림발송
            //$pInfo = array("teacher"=>$tu_name, "no"=>$result);
            //AppPush::send_push($member['uid'], "2004", $pInfo);
        }

        $return_array['res_code'] = '0000';
        $return_array['msg']      = "게시물을 등록 했습니다.";
        echo json_encode($return_array);
        exit;
    }

    /*
        강사와 1:1게시판 수정
        TODO: 구분잘할것 reply(학생 쪽지 답변), content(학생에게보낸 메세지 수정)
    */
    public function board_message_reply_modify()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "no"            => trim($this->input->post('no')), //필수
            "r_yn"          => trim($this->input->post('r_yn')),
            "reply"         => trim($this->input->post('reply')),
            "file3"         => isset($_FILES["file3"]) ? $_FILES["file3"] : null,
            "file4"         => isset($_FILES["file4"]) ? $_FILES["file4"] : null,
            "del_file3"     => trim($this->input->post('del_file3')),
            "del_file4"     => trim($this->input->post('del_file4')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        // 데이터 검사
        $this->load->model('Tutor_mdl');
        $toteacher = $this->Tutor_mdl->writer_message($request['no']);
        if(!$toteacher)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No Data";
            echo json_encode($return_array);
            exit;
        }

        if($toteacher['replydate'] != "0000-00-00 00:00:00") $replydate = $toteacher['replydate'];
	    else                                                 $replydate = date("Y-m-d H:i:s");

        //s3파일 업로드
        $this->load->library('s3');
        $file_name3 = null;
        $file_update_chk3 = false;

        if($request['del_file3'] && $toteacher['filename3'])
        {
            S3::delete_s3_object($this->upload_path_teacher_1n1, $toteacher['filename3']);
            $file_update_chk3 = true;
        }

        if($request['file3'])
        {
            if($toteacher['filename3'])
            {
                S3::delete_s3_object($this->upload_path_teacher_1n1, $toteacher['filename3']);
            }

            $upload_limit_size = 10; // TODO: 기존 파일등록시 업로드 크기제한이 없음
            $ext_array = array('jpg', 'jpeg', 'png', 'gif');

            $res = S3::put_s3_object($this->upload_path_teacher_1n1, $request["file3"], $upload_limit_size, $ext_array);
            if($res['res_code'] != '0000')
            {
                echo json_encode($res);
                exit;
            }

            $file_name3 = $res['file_name'];
            $file_update_chk3 = true;
        }

        $file_name4 = null;
        $file_update_chk4 = false;

        if($request['del_file4'] && $toteacher['filename4'])
        {
            S3::delete_s3_object($this->upload_path_teacher_1n1, $toteacher['filename4']);
            $file_update_chk4 = true;
        }

        if($request['file4'])
        {
            if($toteacher['filename4'])
            {
                S3::delete_s3_object($this->upload_path_teacher_1n1, $toteacher['filename4']);
            }

            $upload_limit_size = 10; // TODO: 기존 파일등록시 업로드 크기제한이 없음
            $ext_array = array('doc', 'docx', 'ppt', 'pptx', 'xlsx', 'mp3');

            $res = S3::put_s3_object($this->upload_path_teacher_1n1, $request["file4"], $upload_limit_size, $ext_array);
            if($res['res_code'] != '0000')
            {
                echo json_encode($res);
                exit;
            }

            $file_name4 = $res['file_name'];
            $file_update_chk4 = true;
        }

        $article = array(
            'step'      => 'Y',
            'reply'     => $request['reply'],
            'r_yn'      => ($request['r_yn'] ? $request['r_yn'] : 'y'),
            'replydate' => $replydate
        );

        if($file_update_chk3) $article['filename3'] = $file_name3;
        if($file_update_chk4) $article['filename4'] = $file_name4;

        $where = array(
            'to_id' => $request['no']
        );

        $result = $this->Tutor_mdl->update_message($article, $where);
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg']      = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        // 강사와 1:1게시판 답변 성공시 SMS 전송
        if($toteacher['mobile'] && $toteacher['rsms'] == "Y")
        {
            $content = $toteacher['tu_name']." 선생님으로 부터 답변이 도착하였습니다. ".tutor_message_make_viwe_link($request['no']);
            $options = array(
                'uid'      => $toteacher['uid'],
                'wiz_id'   => $toteacher['wiz_id'],
                'name'     => $toteacher['name'],
                'content'  => $content,
                'man_id'   => $wiz_tutor['wt_tu_id'],
                'man_name' => $toteacher['tu_name']
            );
            $mobile_num = str_replace("-","",$toteacher['mobile']);
            sms::send_sms($mobile_num,'',$options);
        }

        // 답변등록 알림
        $this->load->model('notify_mdl');

        if($request['reply'] && $toteacher['reply'] =='')
        {
            $aNotifyData = array(
                'uid'        => $toteacher['uid'],
                'code'       => '101',
                'table_code' => 'teacher.view',
                'board_name' => '강사와1:1게시판',
                'user_name'  => $toteacher['tu_name'],
                'content'    => $toteacher['memo'],
                'message'    => str_replace('%%',$toteacher['tu_name'],'%% 선생님으로부터 답변이 도착하였습니다.'),
                'mb_unq'     => $request['no'],
                'co_unq'     => NULL,
                'go_url'     => tutor_message_make_viwe_link($request['no']),
                'regdate'    => date('Y-m-d H:i:s')
            );
            $notify_result = $this->notify_mdl->insert_notify($aNotifyData);
        }

        // 푸시 알림발송
        //$pInfo = array("teacher"=>$toteacher['tu_name'], "no"=>$request['no']);
        //AppPush::send_push($toteacher['uid'], "2003", $pInfo);

        $return_array['res_code'] = '0000';
        $return_array['msg']      = "success";
        echo json_encode($return_array);
        exit;
    }

    /*
        강사와 1:1게시판 수정
        TODO: 구분잘할것 reply(학생 쪽지 답변), content(학생에게보낸 메세지 수정)
    */
    public function board_message_content_modify()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "no"            => trim($this->input->post('no')), //필수
            "c_yn"          => trim($this->input->post('c_yn')),
            "memo"          => trim($this->input->post('memo')),
            "file"          =>  isset($_FILES["file"])  ? $_FILES["file"]  : null,
            "file2"         => isset($_FILES["file2"]) ? $_FILES["file2"] : null,
            "del_file"      => trim($this->input->post('del_file')),
            "del_file2"     => trim($this->input->post('del_file2')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        // 데이터 검사
        $this->load->model('Tutor_mdl');
        $toteacher = $this->Tutor_mdl->writer_message($request['no']);

        if(!$toteacher)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No Data";
            echo json_encode($return_array);
            exit;
        }

        //s3파일 업로드
        $this->load->library('s3');
        $file_name = null;
        $file_update_chk = false;

        if($request['del_file'] && $toteacher['filename'])
        {
            S3::delete_s3_object($this->upload_path_teacher_1n1, $toteacher['filename']);
            $file_update_chk = true;
        }

        if($request['file'])
        {
            if($toteacher['filename'])
            {
                S3::delete_s3_object($this->upload_path_teacher_1n1, $toteacher['filename']);
            }
            
            $upload_limit_size = 10; // TODO: 기존 파일등록시 업로드 크기제한이 없음
            $ext_array = array('jpg', 'jpeg', 'png', 'gif');

            $res = S3::put_s3_object($this->upload_path_teacher_1n1, $request["file"], $upload_limit_size, $ext_array);
            if($res['res_code'] != '0000')
            {
                echo json_encode($res);
                exit;
            }

            $file_name = $res['file_name'];
            $file_update_chk = true;
        }

        $file_name2 = null;
        $file_update_chk2 = false;

        if($request['del_file2'] && $toteacher['filename2'])
        {
            S3::delete_s3_object($this->upload_path_teacher_1n1, $toteacher['filename2']);
            $file_update_chk2 = true;
        }

        if($request['file2'])
        {
            if($toteacher['filename2'])
            {
                S3::delete_s3_object($this->upload_path_teacher_1n1, $toteacher['filename2']);
            }

            $upload_limit_size = 10; // TODO: 기존 파일등록시 업로드 크기제한이 없음
            $ext_array = array('doc', 'docx', 'ppt', 'pptx', 'xlsx', 'mp3');

            $res = S3::put_s3_object($this->upload_path_teacher_1n1, $request["file2"], $upload_limit_size, $ext_array);
            if($res['res_code'] != '0000')
            {
                echo json_encode($res);
                exit;
            }

            $file_name2 = $res['file_name'];
            $file_update_chk2 = true;
        }

        $article = array(
            'memo' => $request['memo'],
            'c_yn' => ($request['c_yn'] ? $request['c_yn'] : 'y')
        );
        
        if($file_update_chk)  $article['filename']  = $file_name;
        if($file_update_chk2) $article['filename2'] = $file_name2;

        $where = array(
            'to_id' => $request['no']
        );
        $result = $this->Tutor_mdl->update_message($article, $where);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg']      = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg']      = "modify success";
        echo json_encode($return_array);
        exit;
    }

    /*
        강사 BOARD - 강사와 매니저 목록
    */
    public function board_mantutor_list_()
    {
        $return_array = array();

        $request = array(
            'start' => $this->input->post('start') ? $this->input->post('start') : 0,
            'limit' => $this->input->post('limit') ? $this->input->post('limit') : '20',
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
            "search" => trim($this->input->post('search')),
            "keyword" => trim($this->input->post('keyword')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtoupper($this->input->post('order_field'))) : "mt.to_id",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $search = array();
        $search[] = "mt.tu_uid = '".$wiz_tutor['wt_tu_uid']."'";

        //검색
        if($request['search'] && $request['keyword']) $search[] = $request['search']." like '%".$request['keyword']."%'";

        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('Tutor_mdl');
        $list_cnt = $this->Tutor_mdl->list_tutor_mantutor_count($where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data";
            echo json_encode($return_array);
            exit;
        }

        $limit = "";
        
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        $result = $this->Tutor_mdl->list_mantutor_board($where, $order, $limit);

        if($result < 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data(2)";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /*
        강사 BOARD - 강사와 매니저 정보(view)
    */
    public function board_mantutor_article()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
            "no" => trim($this->input->post('no')), //필수
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('Tutor_mdl');
        $result = $this->Tutor_mdl->writer_mantutor_board($wiz_tutor['wt_tu_uid'], $request['no']);

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data";
            echo json_encode($return_array);
            exit; 
        }

        //매니저의 메세지를 처음 확인 하는거라면 확인날짜 업데이트
        if($result['mt_writer_gubun'] == 'M' && $result['mt_view'] == 'N')
        {
            $article = array(
                'viewdate' => date('Y-m-d H:i:s'),
                'view' => 'Y'
            );
    
            $where = array(
                'to_id' => $result['mt_to_id']
            );

            $this->Tutor_mdl->update_mantutor($article, $where);
        }

        $result['mt_memo'] = common_textarea_out($result['mt_memo']);
        $result['mt_reply'] = common_textarea_out($result['mt_reply']);
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['info'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /*
        강사와 매니저 게시판 매니저에게 쓰기
    */
    public function board_mantutor_write()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "title"         => trim($this->input->post('title')),
            "file"          => isset($_FILES["file"]) ? $_FILES["file"] : null,
            "memo"          => trim($this->input->post('memo')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        //s3파일 업로드
        $file_name = null;
        if($request['file'])
        {
            $upload_limit_size = 10; // TODO: 기존 파일등록시 업로드 크기제한이 없음
            $ext_array = array('jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'ppt', 'pptx', 'xlsx', 'mp3');

            $this->load->library('s3');
            $res = S3::put_s3_object($this->upload_path_boards, $request["file"], $upload_limit_size, $ext_array);
            if($res['res_code'] != '0000')
            {
                echo json_encode($res);
                exit;
            }

            $file_name = $res['file_name'];
        }

        $article = array(
            'title'        => $request['title'],
            'tu_uid'       => $wiz_tutor['wt_tu_uid'],
            'tu_name'      => $wiz_tutor['wt_tu_name'],
            'man_id'       => '',
            'man_ename'    => '',
            'step'         => 'N',
            'memo'         => $request['memo'],
            'writer_gubun' => 'T',
            'filename'     => $file_name,
            'regdate'      => date('Y-m-d H:i:s')
        );

        $this->load->model('Tutor_mdl');
        $result = $this->Tutor_mdl->write_mantutor($article);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg']      = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg']      = "Regist success";
        echo json_encode($return_array);
        exit;
    }

    /*
        강사와 매니저 게시판 수정
        modify > isset.POST.memo  : 매니저에게 보낸 메세지 수정
        reply  > isset.POST.reply : 매니저에게 받은 메세지에 답장
    */
    public function board_mantutor_modify()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "no"            => trim($this->input->post('no')), //필수
            "file"          => isset($_FILES["file"]) ? $_FILES["file"] : null,
            "reply"         => ($this->input->post('reply')) ? trim($this->input->post('reply')) : null,
            "memo"          => ($this->input->post('memo'))  ? trim($this->input->post('memo'))  : null,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        //s3파일 업로드 TODO: 기존 수정에서 사용하지않음 혹시 필요할지모르니 남겨둠
        // $file_name = null;
        // if($request['file'])
        // {
        //     $upload_limit_size = 10; // TODO: 기존 파일등록시 업로드 크기제한이 없음
        //     $ext_array = array('jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'ppt', 'pptx', 'xlsx', 'mp3');

        //     $this->load->library('s3');
        //     $res = S3::put_s3_object($this->upload_path_boards, $request["file"], $upload_limit_size, $ext_array);
        //     if($res['res_code'] != '0000')
        //     {
        //         echo json_encode($res);
        //         exit;
        //     }

        //     $file_name = $res['file_name'];
        // }

        $article = array();
        if($request['memo'])
        {
            $article['memo'] = $request['memo'];
        }
        if($request['reply'])
        {
            $article['reply'] = $request['reply'];
            $article['step'] = 'Y';
            $article['replydate'] = date('Y-m-d H:i:s');
        }

        $where = array(
            'tu_uid' => $wiz_tutor['wt_tu_uid'],
            'to_id' => $request['no']
        );

        $this->load->model('Tutor_mdl');
        $result = $this->Tutor_mdl->update_mantutor($article, $where);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg']      = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg']      = "modify success";
        echo json_encode($return_array);
        exit;
    }

    /*
        강사 notice COMMENT 정보(view)
    */
    public function notice_article_comment()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
            "no" => trim($this->input->post('no')), //필수
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('Tutor_mdl');
        $result = $this->Tutor_mdl->list_notice_comment($request['no'], $request['tu_id']);

        $result['mnb_title']   = stripslashes($result['mnb_title']);
        $result['mnb_content'] = stripslashes($result['mnb_content']);

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data";
            echo json_encode($return_array);
            exit; 
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['info'] = $result;
        echo json_encode($return_array);
        exit;
    }


    /*
        강사 notice COMMENT 글쓰기
    */
    public function notice_modify_comment()
    {
        $return_array = array();

        $request = array(
            "sub_no"        => ($this->input->post('sub_no')) ? trim(strtoupper($this->input->post('sub_no'))) : "",
            "authorization" => trim($this->input->post('authorization')),
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "no"            => trim($this->input->post('no')), //필수
            "title"         => trim($this->input->post('title')),
            "comment"       => trim($this->input->post('comment')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $article = array(
            'nb_unq'  => $request['no'],
            'f_order' => 1, //어떤 값인지 모르겠음...
            'tu_id'   => $request['tu_id'],
            'title'   => $request['title'],
            'comment' => $request['comment'],
            'hit'     => 0,
            'regdate' => date('Y-m-d H:i:s')
        );

        $this->load->model('Tutor_mdl');
        if($request['sub_no'])
        {
            //sub_no이 있으면 수정
            $where = array(
                'nb_sub_unq' => $request['sub_no']
            );
            $result = $this->Tutor_mdl->update_notice_comment($article, $where);
            $msg = "modify";
        }
        else
        {
            //sub_no이 없으면 등록
            $result = $this->Tutor_mdl->insert_notice_comment($article);
            $request['sub_no'] = $result;
            $msg = "regist";
        }

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = $msg." success";
        $return_array['no'] = $request['sub_no'];
        echo json_encode($return_array);
        exit;
    }
    /*
        강사 notice COMMENT 삭제
    */
    public function notice_delete_comment()
    {
        $return_array = array();

        $request = array(
            "sub_no" => ($this->input->post('sub_no')) ? trim(strtoupper($this->input->post('sub_no'))) : "", //필수
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $article = array(
            'nb_sub_unq' => $request['sub_no']
        );

        $this->load->model('Tutor_mdl');
        $result = $this->Tutor_mdl->delete_notice_comment($article);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "delete success";
        echo json_encode($return_array);
        exit;
    }

    /*
        해당 강사의 학생 리스트
    */
    public function student_list_()
    {
        $return_array = array();

        $request = array(
            'start' => $this->input->post('start') ? $this->input->post('start') : 0,
            'limit' => $this->input->post('limit') ? $this->input->post('limit') : '20',
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
            "search" => trim($this->input->post('search')),
            "keyword" => trim($this->input->post('keyword')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtoupper($this->input->post('order_field'))) : "wl.lesson_id",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $search = array();
        $search[] = "wl.tu_uid = '".$wiz_tutor['wt_tu_uid']."'";
        $search[] = "(wl.lesson_state = 'in class' || wl.lesson_state = 'finished')";
        $search[] = "wl.startday <= '".date("Y-m-d")."' AND wl.endday >= '".date("Y-m-d")."'";

        //검색
        if($request['search'] && $request['keyword']) $search[] = "wl.".$request['search']." like '%".$request['keyword']."%'";

        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('Tutor_mdl');
        $list_cnt = $this->Tutor_mdl->list_student_count($where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data";
            echo json_encode($return_array);
            exit;
        }

        $limit = "";
        
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        $list = $this->Tutor_mdl->list_student($where, $order, $limit);

        if($list < 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data(2)";
            echo json_encode($return_array);
            exit;
        }

        foreach($list as $key=>$val)
        {
            $list[$key]['wl_id_val']    = tutor_get_sosocial_icon($val['wl_regi_gubun'], $val['wl_wiz_id'], $val['wl_social_email']);
            $list[$key]['wl_stime']     = date("H:i",$val['wl_stime']);
            $list[$key]['wl_sum_tt234'] = $val['wl_tt_2']+$val['wl_tt_3']+$val['wl_tt_4'];

            //현재상태를 체크
            $STS = strtotime($val['wl_startday'].' 00:00:00');
            $ETS = strtotime($val['wl_endday'].' 23:59:59');
            $TTS = strtotime(date('Y-m-d H:i:s'));
            if($TTS >= $STS && $TTS < $ETS) $list[$key]['wl_status'] = "Ongoing";
            else if($TTS < $STS)            $list[$key]['wl_status'] = "Stand by";
            else                            $list[$key]['wl_status'] = "Finished";
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "학생목록 조회 성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $list;
        echo json_encode($return_array);
        exit;
    }

    /*
        해당 강사의 학생 리스트 (select box 용)
    */
    public function selectbox_student_list_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('Tutor_mdl');
        $result = $this->Tutor_mdl->seletcbox_list_student($wiz_tutor['wt_tu_uid']);

        if($result < 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "학생목록 조회 성공";
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 교재 목록을 불러온다
     */
    public function textbooks_list_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
            "search" => trim($this->input->post('search')) ? trim($this->input->post('search')) : '',
            "keyword" => trim($this->input->post('keyword')) ? trim($this->input->post('keyword')) : '',
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $search = array();
        $search[] = "wb.useyn = 'y'";

        //검색
        if($request['search'] && $request['keyword']) $search[] = "((wb.".$request['search']." like '%".$request['keyword']."%' AND wb.book_step=2) OR wb.book_step=1)";

        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('Tutor_mdl');
        $book_list = $this->Tutor_mdl->list_textbooks($where);

        $book_data = [];
        
        foreach($book_list as $book)
        {
            if($book['wb_book_step'] =='1')
            {
                $book_data[$book['wb_f_id']] = $book;
            }
            else
            {
                // 스텝1 없는교재 제외
                if(array_key_exists($book['wb_f_id'],$book_data))
                {
                    $book_data[$book['wb_f_id']]['wb_book_step2'][] = $book;
                }
            }
        }

        $list_cnt = 0;
        $result = [];
        foreach($book_data as $step1)
        {
            $step2 = $step1['wb_book_step2'];
            unset($step1['wb_book_step2']);

            //스텝2 없는교재 제외
            if(!$step2) continue;

            $result[] = $step1;
            $list_cnt++;

            if($step2)
            {
                foreach($step2 as $val)
                {
                    $result[] = $val;
                    $list_cnt++;
                }
            }
            
        }

        if($result)
        {
            $retrun_array['res_code'] = '0000';
            $retrun_array['msg'] = "교재 목록 조회 성공";
            $retrun_array['data']['total_cnt'] = $list_cnt;
            $retrun_array['data']['list'] = $result;
            echo json_encode($retrun_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No Data";
            echo json_encode($return_array);
            exit;
        }
    }

    /*
        monthly/report.php      type -> report
        monthly/complete.php    type -> complete
    */
    public function monthly_reports()
    {
        $return_array = array();

        $request = array(
            "type" => trim($this->input->post('type')) ? trim($this->input->post('type')) : '',
            // "tu_id" => trim(strtolower($this->input->post('tu_id'))),
            // "authorization" => trim($this->input->post('authorization')),
            "search" => trim($this->input->post('search')) ? trim($this->input->post('search')) : '',
            "keyword" => trim($this->input->post('keyword')) ? trim($this->input->post('keyword')) : '',
            "start" => trim($this->input->post('start')) ? trim($this->input->post('start')) : '0',
            "limit" => trim($this->input->post('limit')) ? trim($this->input->post('limit')) : '20',
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "lesson_id",
        );
        
        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        // 영어 첨삭 게시판
        if($request['type'] == 'report')
        {
            //해당 강사로 걸려있는 출석부 조회
            
            /*
                report.php  ->	    report.detail.php        ->	wiz_report 등록(스케쥴 조회후 출석률/점수 인설트)
                해당 강사로 걸려있는 출석부 조회    ->	wiz_schedule 로 진행도 조회
            */

            // report_app == 1 이 뭔지 확인해봐야한다<<<<
            $this->load->model('lesson_mdl');
            $where = " WHERE tu_uid = '{$wiz_tutor['tu_uid']}' AND report_app='1' ";

            $list_lesson_count = $this->lesson_mdl->list_count_lesson($where);
        
            if($list_lesson_count['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "No Data.";
                echo json_encode($return_array);
                exit;
            }
            

            $order = " ORDER BY ".$request['order_field']." ".$request['order'];
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
            $result = $this->lesson_mdl->list_lesson($where, $order, $limit);

        }
        else if($request['type'] == 'complete')
        {
            //type : complete -> 해당 강사가 작성한 월말평가서(wiz_report) 조회

            /*
                complete.php    ->	report.complete.php ->	wiz_report 업데이트(출석률 업데이트가 아니라 점수만 업데이트)
                해당 강사가 작성한 월말평가서(wiz_report) 조회	->	wiz_report 로 월말 평가서 점수조회
            */
            
            $this->load->model('lesson_mdl');
            $where = " WHERE tu_uid = '{$wiz_tutor['tu_uid']}'";
            $list_report_count = $this->lesson_mdl->list_count_report($where);
        
            if($list_report_count['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "No Data(1)";
                echo json_encode($return_array);
                exit;
            }

            $order = " ORDER BY ".$request['order_field']." ".$request['order'];
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
            $result = $this->lesson_mdl->list_report($where, $order, $limit);

        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['list'] = $result;
        // $return_array['data']['tutor_msg'] = $tutor_valid_msg;
        echo json_encode($return_array);
        exit;
    }

    /*
        monthly/report.php(type:report)     ->  Evaluation 팝업
            fid ->  해당 레쓴 id로 조회되는 첫번쨰 스케쥴 정보
            eid ->  해당 레쓴 id로 조회되는 마지막 스케쥴 정보
            lesson_id   ->  레쓴 id 

        monthly/complete.php(type:complete)    ->  Evaluation 팝업
            re_id -> report pk key 
    */
    public function report_view()
    {
        $return_array = array();

        $request = array(
            "tu_id" => trim(strtolower($this->input->post('tu_id'))),       
            "authorization" => trim($this->input->post('authorization')), 
            "type" => trim($this->input->post('type')) ? trim($this->input->post('type')) : '',
            "re_id" => trim($this->input->post('re_id')) ? trim($this->input->post('re_id')) : '',      // 
            "lesson_id" => trim($this->input->post('lesson_id')) ? trim($this->input->post('lesson_id')) : '',
            "f_id" => trim($this->input->post('f_id')) ? trim($this->input->post('f_id')) : '',     // 해당 레쓴 id로 조회되는 첫번쨰 스케쥴정보
            "e_id" => trim($this->input->post('e_id')) ? trim($this->input->post('e_id')) : '',     // 해당 레쓴 id로 조회되는 마지막 스케쥴정보
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        if($request['type'] == 'report')
        {
            if(!$request['lesson_id'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0203";
                $return_array['data']['errMsg'] = "lesson_id를 입력해주세요";
                echo json_encode($return_array);
                exit;
            }

            $this->load->model('lesson_mdl');
            $lesson = $this->lesson_mdl->row_wiz_lesson_by_tu_id($request['lesson_id'], $wiz_tutor['wt_tu_uid']);
            
            if(!$lesson)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0215";
                $return_array['data']['errMsg'] = "No exists class!";
                echo json_encode($return_array);
                exit;
            }

            $this->load->model('lesson_mdl');
            $first_schedule = $this->lesson_mdl->row_schedule_by_lesson_id_sc_id($request['lesson_id'], $request['f_id']);
            if(!$first_schedule)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0212";
                $return_array['data']['errMsg'] = "No exists schedule!";
                echo json_encode($return_array);
                exit;
            }

            $last_schedule = $this->lesson_mdl->row_schedule_by_lesson_id_sc_id($request['lesson_id'], $request['e_id']);
            if(!$last_schedule)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0203";
                $return_array['data']['errMsg'] = "No exists last schedule!";
                echo json_encode($return_array);
                exit;
            }

            $where = " WHERE startday BETWEEN '{$first_schedule['startday']}' AND '{$last_schedule['startday']}' AND lesson_id = '{$request['lesson_id']}'";
            $tt_datas = $this->lesson_mdl->checked_tt_by_where($where);
            
        }
        else if($request['type'] == 'complete')
        {
            if(!$request['re_id'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0203";
                $return_array['data']['errMsg'] = "re_id를 입력해주세요";
                echo json_encode($return_array);
                exit;
            }
            
            $this->load->model('lesson_mdl');
            $report = $this->lesson_mdl->row_report_by_re_id($request['re_id']);
            
            if(!$report)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0201";
                $return_array['data']['errMsg'] = "No data";
                echo json_encode($return_array);
                exit;
            }

            $result = $this->lesson_mdl->row_wiz_lesson_by_lesson_id($report['wr_lesson_id'], $report['wr_uid']);
            if(!$result)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0212";
                $return_array['data']['errMsg'] = "No exists schedule!";
                echo json_encode($return_array);
                exit;
            }
            
            $schedule = $this->lesson_mdl->list_schedule_by_lesson_id($report['wr_lesson_id']);
            if(!$schedule)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0212";
                $return_array['data']['errMsg'] = "No exists start schedule!";
                echo json_encode($return_array);
                exit;
            }
        }
        

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "게시물조회성공";
        $return_array['data']['report'] = ($request['type'] == 'complete') ? $report : $tt_datas;
        $return_array['data']['info'] = $result;
        echo json_encode($return_array);
        exit;
    }

    public function report_update()
    {

        $return_array = array();

        $request = array(
            "tu_id" => trim(strtolower($this->input->post('tu_id'))),       
            "authorization" => trim($this->input->post('authorization')), 
            "type" => trim($this->input->post('type')) ? trim($this->input->post('type')) : '',
            
            "re_id" => trim($this->input->post('re_id')) ? trim($this->input->post('re_id')) : '',      // 
            
            // 아래는 report.detail.php report.proc.php 필수값
            "lesson_id" => trim($this->input->post('lesson_id')) ? trim($this->input->post('lesson_id')) : '',
            "f_id" => trim($this->input->post('f_id')) ? trim($this->input->post('f_id')) : '',     
            "e_id" => trim($this->input->post('e_id')) ? trim($this->input->post('e_id')) : '',     
            
            "listening" => trim($this->input->post('listening')) ? trim($this->input->post('listening')) : '',     
            "speaking" => trim($this->input->post('speaking')) ? trim($this->input->post('speaking')) : '',     
            "pronunciation" => trim($this->input->post('pronunciation')) ? trim($this->input->post('pronunciation')) : '',     
            "vocabulary" => trim($this->input->post('vocabulary')) ? trim($this->input->post('vocabulary')) : '',     
            "grammar" => trim($this->input->post('grammar')) ? trim($this->input->post('grammar')) : '',     
            "ev_memo" => $this->input->post('ev_memo') ? $this->input->post('ev_memo') : '',     
            "gra_memo" => $this->input->post('gra_memo') ? $this->input->post('gra_memo') : '',  
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }
        
        if($request['type']=='report')
        {

            if(!$request['lesson_id'])
            {
                $return_array['res_code'] = '0401';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0203";
                $return_array['data']['errMsg'] = "lesson_id를 입력해주세요";
                echo json_encode($return_array);
                exit;
            }
            if(!$request['f_id'])
            {
                $return_array['res_code'] = '0401';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0203";
                $return_array['data']['errMsg'] = "f_id 입력해주세요";
                echo json_encode($return_array);
                exit;
            }
            if(!$request['e_id'])
            {
                $return_array['res_code'] = '0401';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0203";
                $return_array['data']['errMsg'] = "e_id 입력해주세요";
                echo json_encode($return_array);
                exit;
            }

            // 테스트 해봐야함
            $this->load->model('lesson_mdl');
            $lesson = $this->lesson_mdl->row_wiz_lesson_by_tu_id($request['lesson_id'], $wiz_tutor['wt_tu_uid']);
            
            if(!$lesson)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0215";
                $return_array['data']['errMsg'] = "No exists class!";
                echo json_encode($return_array);
                exit;
            }

            $this->load->model('lesson_mdl');
            $first_schedule = $this->lesson_mdl->row_schedule_by_lesson_id_sc_id($request['lesson_id'], $request['f_id']);
            if(!$first_schedule)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0212";
                $return_array['data']['errMsg'] = "No exists schedule!";
                echo json_encode($return_array);
                exit;
            }

            $last_schedule = $this->lesson_mdl->row_schedule_by_lesson_id_sc_id($request['lesson_id'], $request['e_id']);
            if(!$last_schedule)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0216";
                $return_array['data']['errMsg'] = "No exists last schedule!";
                echo json_encode($return_array);
                exit;
            }

            $where = " WHERE startday BETWEEN '{$first_schedule['startday']}' AND '{$last_schedule['startday']}' AND lesson_id = '{$request['lesson_id']}'";
            $tt_datas = $this->lesson_mdl->checked_tt_by_where($where);

            $params = [

                'uid' => $lesson['wl_uid'],
                'wiz_id' => $lesson['wl_wiz_id'],
                'name' => $lesson['wl_name'],
                'ename' => $lesson['wl_ename'],
                'tu_uid' => $wiz_tutor['wt_tu_uid'],
                'tu_name' => $wiz_tutor['wt_tu_name'],
                'lesson_id' => $request['lesson_id'],
                're_start' => substr($first_schedule['startday'], 0, 10),
                're_end' => substr($last_schedule['startday'], 0, 10),
                're_time' => $lesson['wl_stime'],
                
                'report_num' => $lesson['wl_report_num'],
                'listening' => $request['listening'],
                'speaking' => $request['speaking'],
                'pronunciation' => $request['pronunciation'],
                'vocabulary' => $request['vocabulary'],
                'grammar' => $request['grammar'],
                'ev_memo' => $request['ev_memo'],
                'gra_memo' => $request['gra_memo'],
                
                'tt_2' => $tt_datas['tt1'],
                'tt_3' => $tt_datas['tt2'],
                'tt_4' => $tt_datas['tt3'],
                'tt_5' => $tt_datas['tt4'],
                'tt_6' => $tt_datas['tt5'],
                'tt_7' => $tt_datas['tt6'],

                'regdate' => date('Y-m-d H:i:s'),
                'modifydate' => date('Y-m-d H:i:s'),

            ];

            $this->load->model('lesson_mdl');
            $result_report = $this->lesson_mdl->insert_wiz_report($params, $first_schedule['startday'], $last_schedule['startday']);

            if($result_report < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }
            
        }
        else if($request['type']=='complete')
        {
            if(!$request['re_id'])
            {
                $return_array['res_code'] = '0401';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0203";
                $return_array['data']['errMsg'] = "re_id 입력해주세요";
                echo json_encode($return_array);
                exit;
            }
        
            $params = [
                'listening' => $request['listening'],
                'speaking' => $request['speaking'],
                'pronunciation' => $request['pronunciation'],
                'vocabulary' => $request['vocabulary'],
                'grammar' => $request['grammar'],
                'ev_memo' => $request['ev_memo'],
                'gra_memo' => $request['gra_memo'],

                'modifydate' => date('Y-m-d H:i:s'),
            ];

            $this->load->model('lesson_mdl');
            $result_report = $this->lesson_mdl->update_wiz_report($request['re_id'], $params);

            if($result_report < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }

        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = ($request['type'] == 'report') ? "regist success" : "modify success";
        // $return_array['data']['api_token'] = token_create_member_token($request['wiz_id']);
        // $return_array['data']['user_info'] = $wiz_member;
        echo json_encode($return_array);
        exit;
    }

    /*
        인센티브 리스트
    */
    public function incentive_list_()
    {
        $return_array = array();

        $request = array(
            'start' => $this->input->post('start') ? $this->input->post('start') : 0,
            'limit' => $this->input->post('limit') ? $this->input->post('limit') : '20',
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
            "startDate" => ($this->input->post('startDate')) ? trim($this->input->post('startDate')) : NULL,
            "endDate" => ($this->input->post('endDate')) ? trim($this->input->post('endDate')) : NULL,
            "search" => trim($this->input->post('search')),
            "keyword" => trim($this->input->post('keyword')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtoupper($this->input->post('order_field'))) : "mi.in_id",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
            "in_kind" => ($this->input->post('in_kind')) ? trim(strtoupper($this->input->post('in_kind'))) : "",
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $search = array();
        $search[] = "mi.in_gubun = 'T'";
        $search[] = "mi.tu_uid = '".$wiz_tutor['wt_tu_uid']."'";
        $search[] = "mi.in_yn = 'y'";

        //검색
        if($request['search'] && $request['keyword']) $search[] = $request['search']." like '%".$request['keyword']."%'";
        
        if($request['in_kind'] && $request['in_kind'])
        {
            switch($request['in_kind'])
            {
                case 'ai' : $search[] = "mi.money >= 0";break;
                case 'ap' : $search[] = "mi.money < 0";break;
                default   : $search[] = "mi.in_kind = '".$request['in_kind']."'";break;
            }
        }

        if($request['startDate']) $search[] = "DATE_FORMAT(mi.regdate,'%Y-%m-%d') >= '".$request['startDate']."'";
        if($request['endDate'])   $search[] = "DATE_FORMAT(mi.regdate,'%Y-%m-%d') <= '".$request['endDate']."'";

        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('Tutor_mdl');
        $list_cnt = $this->Tutor_mdl->list_incentive_count($where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No data";
            echo json_encode($return_array);
            exit;
        }

        $limit = "";
        
        if($request['limit'] > 0)
        {
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        $list = $this->Tutor_mdl->list_incentive($where, $order, $limit);

        if($list < 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No data(2)";
            echo json_encode($return_array);
            exit;
        }

        foreach($list as $key=>$val)
        {
            $list[$key]['mi_id_val'] = tutor_get_sosocial_icon($val['mi_regi_gubun'], $val['mi_wiz_id'], $val['mi_social_email']);

            $list[$key]['mi_kind_text'] = "";
            switch($val['mi_in_kind'])
            {
                case '1'  : $list[$key]['mi_kind_text'] = "Renewal Incentive";break;
                case '15' : $list[$key]['mi_kind_text'] = "Registration incentive";break;
                case '2'  : $list[$key]['mi_kind_text'] = "Gammar Corrections";break;
                case '14' : $list[$key]['mi_kind_text'] = "Transcrption";break;
                case '3'  : $list[$key]['mi_kind_text'] = "Holiday Pay";break;
                case '4'  : $list[$key]['mi_kind_text'] = "Feedback Incentive";break;
                case '5'  : $list[$key]['mi_kind_text'] = "Small Group Attendance";break;

                case '5a' : $list[$key]['mi_kind_text'] = "Attendance";break;
                case '5b' : $list[$key]['mi_kind_text'] = "Perfect Attendance";break;
                case '5c' : $list[$key]['mi_kind_text'] = "Part Time Attendance";break;
                case '5d' : $list[$key]['mi_kind_text'] = "Perfect Part Time Attendance";break;

                case '6'  : $list[$key]['mi_kind_text'] = "Small Group SC";break;
                case '6a' : $list[$key]['mi_kind_text'] = "Small Group 201-250";break;
                case '6b' : $list[$key]['mi_kind_text'] = "Small Group 251-300";break;
                case '6c' : $list[$key]['mi_kind_text'] = "Small Group 301-350";break;
                case '6d' : $list[$key]['mi_kind_text'] = "Small Group 351-400";break;
                case '6e' : $list[$key]['mi_kind_text'] = "Small Group Part Time 151-200";break;
                case '6f' : $list[$key]['mi_kind_text'] = "Small Group Part Time 201-250";break;

                case '7'  : $list[$key]['mi_kind_text'] = "Student's Gift";break;
                case '7a' : $list[$key]['mi_kind_text'] = "Student's Gift";break;
                case '7b' : $list[$key]['mi_kind_text'] = "Best Student's Gift";break;

                case '8'  : $list[$key]['mi_kind_text'] = "Top Performer";break;
                case '8a' : $list[$key]['mi_kind_text'] = "Top Performer 1";break;
                case '8b' : $list[$key]['mi_kind_text'] = "Top Performer 2-5";break;
                case '8c' : $list[$key]['mi_kind_text'] = "Best Top Performer 1";break;
                case '8d' : $list[$key]['mi_kind_text'] = "Best Top Performer 2-5";break;
                case '8e' : $list[$key]['mi_kind_text'] = "Part Time Top 1";break;
                case '8f' : $list[$key]['mi_kind_text'] = "Part Time Top 2-5";break;
                case '8g' : $list[$key]['mi_kind_text'] = "Best Part Time Top 1";break;
                case '8h' : $list[$key]['mi_kind_text'] = "Best Part Time Top 2-5";break;

                case '9'  : $list[$key]['mi_kind_text'] = "Transportation Allowance";break;
                case '10' : $list[$key]['mi_kind_text'] = "Additional incentive";break;
                case '11' : $list[$key]['mi_kind_text'] = "Grammar Correction Rating";break;
                case '12' : $list[$key]['mi_kind_text'] = "Medical Benefit";break;
                case '13' : $list[$key]['mi_kind_text'] = "Transcrption Rating";break;

                case '16' : $list[$key]['mi_kind_text'] = "MINT ENGLISH CHAT New post";break;
                case '17' : $list[$key]['mi_kind_text'] = "MINT ENGLISH CHAT New comment";break;
                case 'lti' : $list[$key]['mi_kind_text'] = "Leveltest Incentive";break;

                case 'a'  : $list[$key]['mi_kind_text'] = "Schedule Adherence";break;
                case 'b'  : $list[$key]['mi_kind_text'] = "Tardiness";break;
                case 'c'  : $list[$key]['mi_kind_text'] = "Absence";break;
                case 'ca' : $list[$key]['mi_kind_text'] = "Monday Absence";break;
                case 'cb' : $list[$key]['mi_kind_text'] = "Absences";break;
                case 'd'  : $list[$key]['mi_kind_text'] = "Breaking Protocol";break;
            }
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $list;
        echo json_encode($return_array);
        exit;
    }

    /*
        강사 Student Count
        tutor_student_chg
        학생 수업 변경 리스트? 인것같은데 학생카운트라고되어있음
        그래서 함수명은 수업변경리스트로!
    */
    public function student_change_list_()
    {
        $return_array = array();

        $request = array(
            'start' => $this->input->post('start') ? $this->input->post('start') : 0,
            'limit' => $this->input->post('limit') ? $this->input->post('limit') : '20',
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
            "startDate" => ($this->input->post('startDate')) ? trim($this->input->post('startDate')) : date('Y-m-').'01',
            "endDate" => ($this->input->post('endDate')) ? trim($this->input->post('endDate')) : date('Y-m-d'),
            "order_field" => ($this->input->post('order_field')) ? trim(strtoupper($this->input->post('order_field'))) : "wtc.tt_id",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $search = array();
        $search[] = "(wtc.a_tuid = '".$wiz_tutor['wt_tu_uid']."' || wtc.b_tuid = '".$wiz_tutor['wt_tu_uid']."')";
        $search[] = "wtc.kind IN ('r','u','c')";
        $search[] = "wtc.a_tuid != wtc.b_tuid";
        if($request['startDate']) $search[] = "wtc.regdate >= '".$request['startDate']." 00:00:00'";
        if($request['endDate'])   $search[] = "wtc.regdate <= '".$request['endDate']." 23:59:59'";

        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('Tutor_mdl');
        $list_cnt = $this->Tutor_mdl->list_student_change_count($where, $wiz_tutor['wt_tu_uid']);

        if($list_cnt['total_cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No data";
            echo json_encode($return_array);
            exit;
        }

        $limit = "";
        
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        $list = $this->Tutor_mdl->list_student_change($where, $order, $limit);

        if($list < 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No data(2)";
            echo json_encode($return_array);
            exit;
        }

        foreach($list as $key=>$val)
        {
            $list[$key]['wtc_id_val'] = tutor_get_sosocial_icon($val['wtc_regi_gubun'], $val['wtc_wiz_id'], $val['wtc_social_email']);

            $list[$key]['wtc_kind_class_su'] = "";
            $class_su = ($val['wtc_class_su'] > 0) ? $val['wtc_class_su']."회" : "";
            switch($val['wtc_kind'])
            {
                case 'c' : $list[$key]['wtc_kind_class_su'] = "changed (".$class_su.")";break;
                case 'p' : $list[$key]['wtc_kind_class_su'] = "<font color=blue>Added(수업추가".$class_su.")</font>";break;
                case 'h' : $list[$key]['wtc_kind_class_su'] = "Class Detail 변경";break;
                case 'd' : $list[$key]['wtc_kind_class_su'] = "<font color=red>Del수업삭제</font>";break;
                case 'a' : $list[$key]['wtc_kind_class_su'] = "<font color=green>Del선택삭제".$class_su."</font>";break;
                case 'b' : $list[$key]['wtc_kind_class_su'] = "포인트로 수업추가";break;
                case 's' : $list[$key]['wtc_kind_class_su'] = "직접시간변경";break;
                case 't' : $list[$key]['wtc_kind_class_su'] = "직접강사시간변경";break;
                case 'e' : $list[$key]['wtc_kind_class_su'] = "직접수업연기";break;
                case 'r' : $list[$key]['wtc_kind_class_su'] = "Registered";break;
                case 'u' : $list[$key]['wtc_kind_class_su'] = "<font color=green>Postpone (".$class_su.")</font>";break;
            }
        }

        $cnt = $list_cnt; //리스트 총 갯수, + IN,OUT 갯수

        $lesson_cnt = $this->tutor_mdl->lesson_student_count($wiz_tutor['wt_tu_uid']); //총 학생 수
        $cnt['lesson_cnt'] = $lesson_cnt['cnt'];

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['cnt'] = $cnt;
        $return_array['data']['list'] = $list;
        echo json_encode($return_array);
        exit;
    }

    /*
        MSET HISTORY LIST
    */
    public function mset_history_list_()
    {
        $return_array = array();

        $request = array(
            'start' => $this->input->post('start') ? $this->input->post('start') : 0,
            'limit' => $this->input->post('limit') ? $this->input->post('limit') : '20',
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
            "search" => trim($this->input->post('search')),
            "keyword" => trim($this->input->post('keyword')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtoupper($this->input->post('order_field'))) : "m.idx",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $search = array();
        $search[] = "m.tu_uid = '".$wiz_tutor['wt_tu_uid']."'";

        //검색
        if($request['search'] && $request['keyword']) $search[] = "m.".$request['search']." like '%".$request['keyword']."%'";

        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('Tutor_mdl');
        $list_cnt = $this->Tutor_mdl->list_mset_history_count($where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No data";
            echo json_encode($return_array);
            exit;
        }

        $limit = "";
        
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        $result = $this->Tutor_mdl->list_mset_history($where, $order, $limit);

        if($result < 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No data(2)";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /*
        스케쥴 BOARD 게시판 출력
    */
    public function schedule_board_list_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id" => trim(strtolower($this->input->post('tu_id'))), //필수
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('tutor_mdl');
        $this->load->model('board_mdl');
        $this->load->model('lesson_mdl');

        /**
         * 강사 나라 별로 출력하는 테이블이 다르다
         * 기존 : 공지사항, 강사와 1:1, 강사와 매니저
         * 북미 : DRB, 강사와 1:1
         */
        $drb_list = null;
        $notice_list = null;
        $mantutor_list = null;
        if($wiz_tutor['mc_nationAs'] == 'usa')
        {
            // DRB (공지사항 + 강사와 매니저 합친 북미 전용 게시판)
            $notce_where = " WHERE (md.md_receiver_uid = '".$wiz_tutor['wt_tu_uid']."' || md.md_receiver_uid='0') ";
            $notce_where .= " AND (md.md_company = '".$wiz_tutor['wt_company']."' || md.md_company = '0') ";
            $notce_where .= " AND md_is_comment='0' ";
            $drb_list = $this->tutor_mdl->list_drb($notce_where, "ORDER BY md.md_id DESC", "LIMIT 0,3");
        }
        else
        {
            // 공지사항
            $notce_where = " WHERE (mnb.tu_id = '".$wiz_tutor['wt_tu_id']."' || mnb.tu_id='all') ";
            $notice_list = $this->tutor_mdl->list_notice_board($notce_where, "ORDER BY mnb.nb_unq DESC", "LIMIT 0,3");

            // 강사와 매니저
            $mantutor_where = " WHERE mt.tu_uid = '".$wiz_tutor['wt_tu_uid']."' ";
            $mantutor_list = $this->tutor_mdl->list_mantutor_board($mantutor_where, "ORDER BY mt.to_id DESC", "LIMIT 0,3");   
        }

        // 강사와 1:1
        $toteacher_where = " WHERE wt.tu_uid = '".$wiz_tutor['wt_tu_uid']."' ";
        $toteacher_list = $this->tutor_mdl->list_toteacher_board($toteacher_where, "ORDER BY wt.to_id DESC", "LIMIT 0,3");

        // Monthly Reports
        $monthly_reports_where = " WHERE  wl.tu_uid = '{$wiz_tutor['wt_tu_uid']}' AND report_app = '1'";
        $monthly_reports_list = $this->lesson_mdl->list_lesson($monthly_reports_where, 'ORDER BY lesson_id DESC', 'LIMIT 0,5', '');

        // Grammar Correction List
        $correction_where = " WHERE mb.tu_uid = '{$wiz_tutor['wt_tu_uid']}' AND (w_step = '1' or (w_step = '3' and chk_tu_uid = 0))";
        $correction_list = $this->tutor_mdl->tutor_list_board_wiz_correct('', $correction_where, 'ORDER BY w_id DESC', 'LIMIT 0,5', '');
        
        // Transcription List
        $select_col_content = ",(SELECT mi.money FROM mint_incentive mi WHERE mi.lesson_id = mb.mb_unq AND in_kind = '14') AS mi_incentive_money,
                (SELECT mi.money FROM mint_incentive mi WHERE mi.lesson_id = mb.mb_unq AND in_kind = '13') AS mi_incentive_point";
        $transcription_where = " WHERE mb.table_code = '1130' AND mb.tu_uid = '{$wiz_tutor['wt_tu_uid']}' AND work_state != '5'";
        $transcription_list = $this->board_mdl->list_board('', $transcription_where, 'ORDER BY mb_unq DESC', 'LIMIT 0, 5', $select_col_content);


        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['notice_list'] = $notice_list;
        $return_array['data']['drb_list'] = $drb_list;
        $return_array['data']['toteacher_list'] = $toteacher_list;
        $return_array['data']['mantutor_list'] = $mantutor_list;
        $return_array['data']['monthly_reports_list'] = $monthly_reports_list;
        $return_array['data']['correction_list'] = $correction_list;
        $return_array['data']['transcription_list'] = $transcription_list;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 월간 보고서 - DUE 목록
     */
    public function monthly_reports_due_list_()
    {
        $return_array = array();

        $request = array(
            'start'         => $this->input->post('start') ? $this->input->post('start') : 0,
            'limit'         => $this->input->post('limit') ? $this->input->post('limit') : '20',
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "search"        => trim($this->input->post('search')),
            "keyword"       => trim($this->input->post('keyword')),
            "order_field"   => ($this->input->post('order_field')) ? trim(strtoupper($this->input->post('order_field'))) : "wl.lesson_id",
            "order"         => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $search = array();
        $search[] = "wl.tu_uid = '".$wiz_tutor['wt_tu_uid']."'";
        $search[] = "wl.report_app = '1'";

        //검색
        if($request['search'] && $request['keyword']) $search[] = "wl.".$request['search']." like '%".$request['keyword']."%'";

        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('Tutor_mdl');
        $list_cnt = $this->Tutor_mdl->list_monthly_reports_due_count($where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No data";
            echo json_encode($return_array);
            exit;
        }

        $limit = "";
        
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        $list = $this->Tutor_mdl->list_monthly_reports_due($where, $order, $limit);

        if($list < 0)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No data(2)";
            echo json_encode($return_array);
            exit;
        }

        foreach($list as $key=>$val)
        {
            $list[$key]['wl_id_val'] = tutor_get_sosocial_icon($val['wl_regi_gubun'], $val['wl_wiz_id'], $val['wl_social_email']);
            $list[$key]['wl_stime']     = date("H:i",$val['wl_stime']);

            $evaluation_period = $this->Tutor_mdl->reports_evaluation_period($val['wl_lesson_id']);
            $list[$key]['wl_report_start'] = $evaluation_period['sp'];
            $list[$key]['wl_report_end']   = $evaluation_period['ep'];
        }

        $return_array['res_code']          = '0000';
        $return_array['msg']               = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list']      = $list;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 월간 보고서 - DUE 상세보기
     */
    public function monthly_reports_due_article()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "no"            => trim($this->input->post('no')) //필수
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('Tutor_mdl');
        $result = $this->Tutor_mdl->writer_monthly_reports_due($request['no']);

        if($result < 0)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No data";
            echo json_encode($return_array);
            exit;
        }

        $result['wl_id_val'] = tutor_get_sosocial_icon($result['wl_regi_gubun'], $result['wl_wiz_id'], $result['wl_social_email']);

        $ep = $this->Tutor_mdl->reports_evaluation_period($result['wl_lesson_id']);
        $result['wl_startday'] = $ep['sp'];
        $result['wl_endday']   = $ep['ep'];

        $pr = $this->Tutor_mdl->reports_present_rate($result['wl_lesson_id'], $result['wl_startday'], $result['wl_endday']);
        $result = array_merge($result, $pr);

        $return_array['res_code']     = '0000';
        $return_array['msg']          = "목록조회성공";
        $return_array['data']['info'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 월간 보고서 - DUE report 등록하면서 스케쥴,강의정보에서 report 업데이트
     */
    public function monthly_reports_due_update()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "lesson_id"     => trim($this->input->post('lesson_id')), //필수
            "report_num"    => $this->input->post('report_num') ? $this->input->post('report_num') : 0,
            "uid"           => $this->input->post('uid') ? $this->input->post('uid') : '',
            "wiz_id"        => $this->input->post('wiz_id') ? $this->input->post('wiz_id') : '',
            "name"          => $this->input->post('name') ? $this->input->post('name') : '',
            "ename"         => $this->input->post('ename') ? $this->input->post('ename') : '',
            "startDay"      => $this->input->post('startDay') ? $this->input->post('startDay') : '',
            "endDay"        => $this->input->post('endDay') ? $this->input->post('endDay') : '',
            "stime"         => $this->input->post('stime') ? $this->input->post('stime') : '',
            "tt_2"          => $this->input->post('tt_2') ? $this->input->post('tt_2') : 0,
            "tt_3"          => $this->input->post('tt_3') ? $this->input->post('tt_3') : 0,
            "tt_4"          => $this->input->post('tt_4') ? $this->input->post('tt_4') : 0,
            "tt_5"          => $this->input->post('tt_5') ? $this->input->post('tt_5') : 0,
            "tt_6"          => $this->input->post('tt_6') ? $this->input->post('tt_6') : 0,
            "tt_7"          => $this->input->post('tt_7') ? $this->input->post('tt_7') : 0,
            "listening"     => $this->input->post('listening') ? $this->input->post('listening') : '1',
            "speaking"      => $this->input->post('speaking') ? $this->input->post('speaking') : '1',
            "pronunciation" => $this->input->post('pronunciation') ? $this->input->post('pronunciation') : '1',
            "vocabulary"    => $this->input->post('vocabulary') ? $this->input->post('vocabulary') : '1',
            "grammar"       => $this->input->post('grammar') ? $this->input->post('grammar') : '1',
            "ev_memo"       => $this->input->post('ev_memo') ? $this->input->post('ev_memo') : '',
            "gra_memo"      => $this->input->post('gra_memo') ? $this->input->post('gra_memo') : '',
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $article = array(
            'uid'           => $request['uid'],
            'wiz_id'        => $request['wiz_id'],
            'report_num'    => $request['report_num'],
            'name'          => $request['name'],
            'ename'         => $request['ename'],
            'tu_uid'        => $wiz_tutor['wt_tu_uid'],
            'tu_name'       => $wiz_tutor['wt_tu_name'],
            'lesson_id'     => $request['lesson_id'],
            're_start'      => substr($request['startDay'],0,10),
            're_end'        => substr($request['endDay'],0,10),
            're_time'       => $request['stime'],
            'tt_2'          => $request['tt_2'],
            'tt_3'          => $request['tt_3'],
            'tt_4'          => $request['tt_4'],
            'tt_5'          => $request['tt_5'],
            'tt_6'          => $request['tt_6'],
            'tt_7'          => $request['tt_7'],
            'listening'     => $request['listening'],
            'speaking'      => $request['speaking'],
            'pronunciation' => $request['pronunciation'],
            'vocabulary'    => $request['vocabulary'],
            'grammar'       => $request['grammar'],
            'ev_memo'       => $request['ev_memo'],
            'gra_memo'      => $request['gra_memo'],
            'regdate'       => date('Y-m-d H:i:s'),
            'modifydate'    => date('Y-m-d H:i:s')
        );

        // report 등록
        $this->load->model('Tutor_mdl');
        $result = $this->Tutor_mdl->insert_monthly_reports_due($article, $request['lesson_id'], $request['startDay'], $request['endDay'], $request['report_num']);
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg']      = "보고서 등록 성공";
        echo json_encode($return_array);
        exit;
    }

    /**
     * 월간 보고서 - COMPLETE 목록
     */
    public function monthly_reports_complete_list_()
    {
        $return_array = array();

        $request = array(
            'start'         => $this->input->post('start') ? $this->input->post('start') : 0,
            'limit'         => $this->input->post('limit') ? $this->input->post('limit') : '20',
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "search"        => trim($this->input->post('search')),
            "keyword"       => trim($this->input->post('keyword')),
            "order_field"   => ($this->input->post('order_field')) ? trim(strtoupper($this->input->post('order_field'))) : "wr.lesson_id",
            "order"         => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $search = array();
        $search[] = "wr.tu_uid = '".$wiz_tutor['wt_tu_uid']."'";

        //검색
        if($request['search'] && $request['keyword']) $search[] = "wr.".$request['search']." like '%".$request['keyword']."%'";

        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('Tutor_mdl');
        $list_cnt = $this->Tutor_mdl->list_monthly_reports_complete_count($where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No data";
            echo json_encode($return_array);
            exit;
        }

        $limit = "";
        
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        $list = $this->Tutor_mdl->list_monthly_reports_complete($where, $order, $limit);

        if($list < 0)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No data(2)";
            echo json_encode($return_array);
            exit;
        }

        foreach($list as $key=>$val)
        {
            $list[$key]['wr_id_val'] = tutor_get_sosocial_icon($val['wr_regi_gubun'], $val['wr_wiz_id'], $val['wr_social_email']);
        }

        $return_array['res_code']          = '0000';
        $return_array['msg']               = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list']      = $list;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 월간 보고서 - COMPLETE 상세보기
     */
    public function monthly_reports_complete_article()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "no"            => trim($this->input->post('no')) //필수
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('Tutor_mdl');
        $result = $this->Tutor_mdl->writer_monthly_reports_complete($request['no']);

        if($result < 0)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No data";
            echo json_encode($return_array);
            exit;
        }

        $result['wl_ev_memo']  = stripslashes($result['wl_ev_memo']);
        $result['wl_gra_memo'] = stripslashes($result['wl_gra_memo']);
        $result['wl_id_val']   = tutor_get_sosocial_icon($result['wl_regi_gubun'], $result['wl_wiz_id'], $result['wl_social_email']);

        $return_array['res_code']     = '0000';
        $return_array['msg']          = "목록조회성공";
        $return_array['data']['info'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 월간 보고서 - COMPLETE 업데이트
     */
    public function monthly_reports_complete_update()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "no"            => trim($this->input->post('no')), //필수
            "listening"     => $this->input->post('listening') ? $this->input->post('listening') : '1',
            "speaking"      => $this->input->post('speaking') ? $this->input->post('speaking') : '1',
            "pronunciation" => $this->input->post('pronunciation') ? $this->input->post('pronunciation') : '1',
            "vocabulary"    => $this->input->post('vocabulary') ? $this->input->post('vocabulary') : '1',
            "grammar"       => $this->input->post('grammar') ? $this->input->post('grammar') : '1',
            "ev_memo"       => $this->input->post('ev_memo') ? $this->input->post('ev_memo') : '',
            "gra_memo"      => $this->input->post('gra_memo') ? $this->input->post('gra_memo') : '',
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $where = array(
            're_id' => $request['no']
        );

        $article = array(
            'listening'     => $request['listening'],
            'speaking'      => $request['speaking'],
            'pronunciation' => $request['pronunciation'],
            'vocabulary'    => $request['vocabulary'],
            'grammar'       => $request['grammar'],
            'ev_memo'       => $request['ev_memo'],
            'gra_memo'      => $request['gra_memo'],
            'modifydate'    => date('Y-m-d H:i:s')
        );

        // report 등록
        $this->load->model('Tutor_mdl');
        $result = $this->Tutor_mdl->update_monthly_reports_complete($article, $where);
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg']      = "보고서 수정 성공";
        echo json_encode($return_array);
        exit;
    }


    /**
     * dpr - 기간 내 강사급여정보
     */
    public function dpr()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))),
            "sdate"         => trim($this->input->post('sdate')) ? trim($this->input->post('sdate')):date('Y-m-d'),
            "edate"         => trim($this->input->post('edate')) ? trim($this->input->post('edate')):date('Y-m-d'),
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $sdatetime = strtotime($request['sdate']);
        $edatetime = strtotime($request['edate']);

        $str_diff = ($edatetime - $sdatetime) / 86400;
	    if ($str_diff > 32) 
        { 
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0211";
            $return_array['data']['err_msg'] = "You can search within 31 days only.";
            echo json_encode($return_array);
            exit;
        }

        //시작일의 급여정보를 가져온다
        $pay_config = tutor_pay_config($wiz_tutor['wt_tu_uid'], $request['sdate']);

        $dpr_data = tutor_pay_dpr_data($wiz_tutor, $request['sdate'], $request['edate']);

        $pay_config['today_group_arr'] = explode('-', $pay_config['today_group']);
	    $pay_config['today_change1_arr'] = explode('-', $pay_config['today_change1']);
	    $pay_config['today_change2_arr'] = explode('-', $pay_config['today_change2']);

        $pay_config['pay_group_arr'] = explode('-', $pay_config['pay_group']);
        $pay_config['pay_change1_arr'] = explode('-', $pay_config['pay_change1']);
        $pay_config['pay_change2_arr'] = explode('-', $pay_config['pay_change2']);

        $pay_config['pay_sat_group_arr'] = explode('-', $pay_config['pay_sat_group']);
        $pay_config['pay_sat_change1_arr'] = explode('-', $pay_config['pay_sat_change1']);
        $pay_config['pay_sat_change2_arr'] = explode('-', $pay_config['pay_sat_change2']);

        $pay_config['pay_sun_group_arr'] = explode('-', $pay_config['pay_sun_group']);
        $pay_config['pay_sun_change1_arr'] = explode('-', $pay_config['pay_sun_change1']);
        $pay_config['pay_sun_change2_arr'] = explode('-', $pay_config['pay_sun_change2']);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['pay_config'] = $pay_config;
        $return_array['data']['dpr'] = $dpr_data;

        echo json_encode($return_array);
        exit;
    }

    
    /**
     * dpr호출하는 날짜에 해당하는 스케쥴 리스트
     */
    public function dpr_schedule_list_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))),
            "sdate"         => trim($this->input->post('sdate')) ? trim($this->input->post('sdate')):date('Y-m-d'),
            "edate"         => trim($this->input->post('edate')) ? trim($this->input->post('edate')):date('Y-m-d'),
            "order"         => trim($this->input->post('order')) ? trim($this->input->post('order')):'ASC',
            "order_field"   => trim($this->input->post('order_field')) ? trim($this->input->post('order_field')):'ws.startday',
            "start"         => trim($this->input->post('start')) ? trim($this->input->post('start')):'0',
            "limit"         => trim($this->input->post('limit')) ? trim($this->input->post('limit')):'20',
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $sdatetime = strtotime($request['sdate']);
        $edatetime = strtotime($request['edate']);

        $str_diff = ($edatetime - $sdatetime) / 86400;
	    if ($str_diff > 32) 
        { 
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0211";
            $return_array['data']['err_msg'] = "You can search within 31 days only.";
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('lesson_mdl');

        $where = " WHERE ws.tu_uid = '".$wiz_tutor['wt_tu_uid']."' AND ws.present IN (2,3,4) 
                AND ws.startday >= '".$request['sdate']." 00:00:00' AND ws.startday <= '".$request['edate']." 23:59:59'";
        $index = "";

        $sc_count = $this->lesson_mdl->list_count_schedule($index, $where);

        if($sc_count['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No data";
            echo json_encode($return_array);
            exit;
        }

        $select_col_content = "";
        $order = sprintf(' ORDER BY %s %s',$request['order_field'],$request['order']);
        $limit = sprintf(' LIMIT %s , %s',$request['start'], $request['limit']);

        $list = $this->lesson_mdl->list_schedule($index, $where, $order, $limit, $select_col_content);

        $pay_config_limit = [];
        $total_cl_time = [
            'level' => [
                'total' => 0,
                'present' => 0,
                'absent' => 0,
            ],
            'class' => [
                'total' => 0,
                'present' => 0,
                'absent' => 0,
                'cancel' => 0,
                'postpone' => 0,
            ],
        ];
        
        if($list)
        {
            foreach($list as $key=>$row)
            {
                $startday_ymd = substr($row['ws_startday'], 0, 10);
                if(!in_array($startday_ymd, $pay_config_limit))
                {
                    $pay_config_limit[$startday_ymd] = tutor_pay_config($wiz_tutor['wt_tu_uid'], $startday_ymd);
                }

                $list[$key]['config_today_type'] = $pay_config_limit[$startday_ymd]['today_type'] ? strtoupper($pay_config_limit[$startday_ymd]['today_type']):'';

                if($row['ws_lesson_id'] == '100000000')
                {
                    //MEL은 10분짜리도 20분으로 잡혀있기에 10분으로 바꿔서 리턴해준다
                    //ㄴ2021-05-17 17"45분 이후 기준 다시 10분으로 잡고있다
                    if($row['ws_lesson_gubun'] =='E' && $list[$key]['ws_cl_time'] == 20)
                    {
                        $list[$key]['ws_cl_time'] = $list[$key]['ws_cl_time'] -10;
                        $row['ws_cl_time'] = $row['ws_cl_time'] - 10;
                    }
                    
                    $total_cl_time['level']['total'] += $row['ws_cl_time'];
                    if($row['ws_present'] == "2") $total_cl_time['level']['present'] += $row['ws_cl_time'];
                    elseif($row['ws_present'] == "3") $total_cl_time['level']['absent'] += $row['ws_cl_time'];
                }
                else
                {
                    $total_cl_time['class']['total'] += $row['ws_cl_time'];
                    if($row['ws_present'] == "2") $total_cl_time['class']['present'] += $row['ws_cl_time'];
                    elseif($row['ws_present'] == "3") $total_cl_time['class']['absent'] += $row['ws_cl_time'];
                    elseif($row['ws_present'] == "4") $total_cl_time['class']['cancel'] += $row['ws_cl_time'];
                    elseif($row['ws_present'] == "5") $total_cl_time['class']['postpone'] += $row['ws_cl_time'];
                }
            }
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['list'] = $list;
        $return_array['data']['total_cnt'] = $sc_count['cnt'];
        $return_array['data']['total_cl_time'] = $total_cl_time;
        echo json_encode($return_array);
        exit;
    }

    
    /**
     * 강사사이트 기본적으로 필요한 정보들 리턴. 매 페이지 이동 시마다 호출
     * 사용자 소스의 new_와 비슷한 용도로 사용
     * 
     * 서버시간
     * 영어첨삭 - 데드라인이 지난경우
     * 수업대본서비스 - 36시간이 지난경우
     * 강사와 1:1게시판 24시간 지나면 알림; 11월13일 이후 게시물 부터
     * monthly reports 오늘 일정 지난것
     * trace로그
     */
    public function new_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))),
            "state"        => trim(strtolower($this->input->post('state'))),
            "hash"        => trim(strtolower($this->input->post('hash'))),
        );

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('lesson_mdl');
        $this->load->model('board_mdl');

        $now = date('Y-m-d H:i:s');
    
        $mobile_agent = "/(iPod|iPhone|Android|BlackBerry|SymbianOS|SCH-M\d+|Opera Mini|Windows CE|Nokia|SonyEricsson|webOS|PalmOS)/";
        if(preg_match($mobile_agent, $_SERVER['HTTP_USER_AGENT'])){
            $ver = 1;
        }else{
            $ver = 0;
        }

        $admin = base_get_login_admin_id();
        $param = [
            'tu_uid' => $wiz_tutor['wt_tu_uid'],
            'admin_id' => $admin ? $admin:'',
            'ip' => $_SERVER["REMOTE_ADDR"],
            'is_mobile' => $ver,
            'regdate' => date('Y-m-d H:i:s'),
            'state' => $request["state"],
            'php_self' => str_replace('#/'.$request["state"],'',$request["hash"]),
        ];

        $this->tutor_mdl->insert_trace_tutor_log($param);
        
        $alert_msg = array();

        //강사와 1:1 페이지에서 호출하면 알림안함
        if(strpos($request['p_self'],'toteacher') === false)
        {
            // 강사와 1:1게시판 24시간 지나면 알림; 11월13일 이후 게시물 부터
            $where = " WHERE wt.tu_uid = '".$wiz_tutor['wt_tu_uid']."' AND wt.step = 'N' AND wt.to_gubun = 'S' 
            AND wt.regdate >= '2017-11-14 00:00:00' AND wt.regdate < '". date('Y-m-d H:i:s', strtotime('-1 day')) . "'";
            $tutor_toteacher_count = $this->tutor_mdl->list_tutor_toteacher_count($where);

            if($tutor_toteacher_count['cnt'] > 0)
            {
                $str = "-You have a PRIVATE MESSAGE that needs to be replied to.";
                array_push($alert_msg, $str);
            }
        }

        //첨삭,수업대본 페이지에서 호출하면 알림안함
        if(strpos($request['p_self'],'special-board') === false)
        {
            // 영어첨삭 - 데드라인 지난경우
            $where = " WHERE mb.tu_uid = '".$wiz_tutor['wt_tu_uid']."' AND mb.w_step!=2 AND mb.w_hopedate <= '".$now."'";
            $list_count_board_wiz_correct = $this->board_mdl->list_count_board_wiz_correct($where);

            if($list_count_board_wiz_correct['cnt'] > 0)
            {
                $str = "-Your GC deadline is OVERDUE. Please finish the pending GC ASAP.";  
                array_push($alert_msg, $str);
            }
            
            //수업대본서비스 - 36시간이 지난경우
            $where = " WHERE mb.tu_uid = '".$wiz_tutor['wt_tu_uid']."' AND mb.wiz_id IS NOT NULL AND '".$now."' > DATE_ADD(mb.regdate, interval +36 hour) AND work_state=4;";
            $list_count_board = $this->board_mdl->list_count_board('', $where);
            
            if($list_count_board['cnt'] > 0)
            {
                $str = "-Your Transcription deadline is OVERDUE. Please finish the pending Transcription ASAP.";
                array_push($alert_msg, $str);
            }
        }

        //스케쥴페이지에서만 호출
        if($request['p_self'] == 'schedule' || $request['p_self'] == 'week-schedule')
        {
            $one_week = date('Y-m-d H:i:s',strtotime('-1 week'));
            $where = " WHERE mt.tu_uid = '".$wiz_tutor['wt_tu_uid']."' AND mt.step != 'Y' AND mt.regdate >= '".$one_week."' AND mt.writer_gubun='M'";
            $mantutor = $this->tutor_mdl->list_tutor_mantutor_count($where);

            if($mantutor['cnt'] > 0)
            {
                $str = "-Plz reply now MANAGER:TEACHER boards.";  
                array_push($alert_msg, $str);
            }
        }

        //해당 강사가 특정일에 쉬는지 체크
        $chk_block_date = $this->tutor_mdl->check_tutor_blockdate_day($wiz_tutor['wt_tu_uid'], date('Y-m-d').' 00:00:00');

        //monthly reports 메뉴에 오늘 일정 지난거 숫자표기 해주기 위한 용도
        $where = " WHERE wl.tu_uid = '".$wiz_tutor['wt_tu_uid']."' AND wl.report_app = '1'
                AND CURDATE() >= (SELECT date_format(ws.startday,\"%Y-%m-%d\") FROM wiz_schedule ws WHERE ws.lesson_id = wl.lesson_id AND ws.present BETWEEN 2 AND 4 ORDER BY ws.startday desc limit 1)";
        $list_cnt = $this->tutor_mdl->list_monthly_reports_due_count($where);

        // 강사 말톡노트 url
        $maaltalk = $this->tutor_mdl->maaltalk_tutor_url_info($wiz_tutor['wt_tu_uid']);
        
        //$alert_msg = [];

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['mel_url'] = $maaltalk;
        $return_array['data']['server_time'] = date('Y.m.d H:i:s');
        $return_array['data']['server_week_str'] = date('D');
        $return_array['data']['alert_msg'] = $alert_msg;
        $return_array['data']['reports_cnt'] = $list_cnt['cnt'];
        $return_array['data']['chk_block_date'] = $chk_block_date;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 스케쥴을 캘린더 형식으로 보여주기 위해 정보를 가져온다
     */
    public function schedule_calendar()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "lesson_id"     => trim($this->input->post('lesson_id')), //필수
            "year"          => $this->input->post('year') ? $this->input->post('year') : date('Y'),
            "month"         => $this->input->post('month') ? $this->input->post('month') : date('m')
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('tutor_mdl');

        $lesson = $this->tutor_mdl->calendar_lesson($request['lesson_id']);
        if(!$lesson)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0215";
            $return_array['data']['err_msg']  = "No exists class!";
            echo json_encode($return_array);
            exit;
        }

        $lesson['wl_id_val'] = tutor_get_sosocial_icon($lesson['wl_regi_gubun'], $lesson['wl_wiz_id'], $lesson['wl_social_email']);

        $startDay = $request['year']."-".$request['month']."-01 00:00:00";
        $last_day = date('t',strtotime($startDay));
        $endDay   = $request['year']."-".$request['month']."-".$last_day." 23:59:59";

        //스케쥴(and 강사) 정보 가져오기
        $schedule = $this->tutor_mdl->calendar_schedule($request['lesson_id'], $wiz_tutor['wt_tu_uid'], $startDay, $endDay);
        
        //스케쥴 정보를 날짜별로 재배치
        $day_schedule = array();
        if($schedule)
        {
            foreach($schedule as $key=>$val)
            {
                $day  = date('d', strtotime($val['ws_startday']));
                $day  = (int)$day;
                $day_schedule[$day] = $val;
            }
        }

        for($day=1;$day<=$last_day;$day++)
        {
            if(!isset($day_schedule[$day]))
            {
                $day_schedule[$day] = null;
            }
        }

        $return_array['res_code']         = '0000';
        $return_array['msg']              = "캘린더 정보를 불러왔습니다";
        $return_array['data']['lesson']   = $lesson;
        $return_array['data']['schedule'] = $day_schedule;
        echo json_encode($return_array);
        exit;
    }

    
    /**
     * 전화영어 시 부재중이라면 문자 보내놓기
     */
    public function sendsms_missed_call()
    {
        $return_array = array();    

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))),
            "sc_id"     => trim($this->input->post('sc_id')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('lesson_mdl');

        $sc = $this->lesson_mdl->row_schedule_by_sc_id_and_tu_uid($request['sc_id'], $wiz_tutor['wt_tu_uid']);
        if(!$sc)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0212";
            $return_array['data']['err_msg']  = "No exists schedule!";
            echo json_encode($return_array);
            exit;
        }

        $mobile = $sc['ws_mobile'] ? $sc['ws_mobile']:$sc['wl_mobile'];

        //알림톡 보내기
        sms::send_atalk($mobile,'MINT06002M3', [
            'uid'    => $sc['uid'],
            'wiz_id' => $sc['wiz_id'],
            'name'   => $sc['ws_name'],
            'time'   => substr($sc['startday'],0,16),
        ]);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "success";
        echo json_encode($return_array);
        exit;
    }

    /**
     * MSET 평가 보기
     */
    public function mset_report()
    {
        $return_array = array();    

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))),
            "sc_id"         => trim($this->input->post('sc_id')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('tutor_mdl');
        $mset = $this->tutor_mdl->row_mset_report($request['sc_id']);
        if(!$mset)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No data";
            echo json_encode($return_array);
            exit;
        }

        //평가 대기이고 평가 시작 1시간전이면 Wait 에서 Ready 로 업데이트
        if($mset['mr_status'] == '0' && strtotime($mset['ws_startday']) - time() <= 3600)
        {
            $this->load->model('mset_mdl');
            $where = array('idx'=>$mset['mr_idx']);
            $param = array('status'=>'1');
            $mset_update = $this->mset_mdl->update_mset($param,$where);
            if($mset_update) $mset['mr_status'] = '1';
        }

        //MSET 평가 정보 목록 추출
        $evaluation_list = $this->tutor_mdl->get_result_list_mest();
        $evaluation = array();
        foreach ($evaluation_list as $val)
        {
            $evaluation[$val['category']][$val['level']] = $val;
        }

        //나이 계산
        $mset['wm_age'] = date("Y", time()) - substr($mset['wm_birth'],0,4);

        //성별 정보
        $mset['wm_gender'] = ($mset['wm_gender'] == 'M') ? 'Male' : 'Female';

        $return_array['res_code']           = '0000';
        $return_array['msg']                = "MSET 정보를 불러왔습니다";
        $return_array['data']['info']       = $mset;
        $return_array['data']['evaluation'] = $evaluation;
        echo json_encode($return_array);
        exit;
    }

    /**
     * MSET 평가 업데이트
     */
    public function update_mset_report()
    {
        $return_array = array();    

        $request = array(
            "authorization"           => trim($this->input->post('authorization')), //필수
            "tu_id"                   => trim(strtolower($this->input->post('tu_id'))), //필수
            "sc_id"                   => trim($this->input->post('sc_id')), //필수
            "mset_idx"                => trim($this->input->post('mset_idx')), //필수
            "present"                 => trim($this->input->post('present')) ? trim($this->input->post('present')):'',
            "startday"                => trim($this->input->post('startday')) ? trim($this->input->post('startday')):'',
            "status"                  => trim($this->input->post('status')) ? trim($this->input->post('status')):'',
            "uid"                     => trim($this->input->post('uid')) ? trim($this->input->post('uid')):'',
            "name"                    => trim($this->input->post('name')) ? trim($this->input->post('name')):'',
            "wiz_id"                  => trim($this->input->post('wiz_id')) ? trim($this->input->post('wiz_id')):'',
            "mobile"                  => trim($this->input->post('mobile')) ? trim($this->input->post('mobile')):'',
            "overall_score"           => trim($this->input->post('overall_score')) ? trim($this->input->post('overall_score')):0,
            "overall_level"           => trim($this->input->post('overall_level')) ? trim($this->input->post('overall_level')):0,
            "overall_level_message"   => trim($this->input->post('overall_level_message')) ? trim($this->input->post('overall_level_message')):0,
            "overall_description"     => trim($this->input->post('overall_description')) ? trim($this->input->post('overall_description')):'',
            "overall_description_add" => trim($this->input->post('overall_description_add')) ? trim($this->input->post('overall_description_add')):'',
            "overall_comment"         => trim($this->input->post('overall_comment')) ? trim($this->input->post('overall_comment')):'',
            "examiner_job"            => trim($this->input->post('examiner_job')) ? trim($this->input->post('examiner_job')):''
        );

        $evaluationList = array('pronunciation','fluency','vocabulary','speaking','grammar','listening','function');
        foreach($evaluationList as $val)
        {
            $request[$val.'_level']           = trim($this->input->post($val.'_level')) ? trim($this->input->post($val.'_level')):0;
            $request[$val.'_description']     = trim($this->input->post($val.'_description')) ? trim($this->input->post($val.'_description')):'';
            $request[$val.'_description_add'] = trim($this->input->post($val.'_description_add')) ? trim($this->input->post($val.'_description_add')):'';
            $request[$val.'_advice']          = trim($this->input->post($val.'_advice')) ? trim($this->input->post($val.'_advice')):'';
            $request[$val.'_advice_add']      = trim($this->input->post($val.'_advice_add')) ? trim($this->input->post($val.'_advice_add')):'';
            $request[$val.'_comment']         = trim($this->input->post($val.'_comment')) ? trim($this->input->post($val.'_comment')):'';
        }

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $status = "";
        switch($request['present'])
        {
            case '1' : $status = (strtotime($request['startday']) - time() < 3600? "1" : "0");break;
            case '2' : $status = "2";break;
            case '3' : $status = "5";break;
            case '4' : $status = "3";break;
        }

        $this->load->model('mset_mdl');
        $this->load->model('lesson_mdl');
        $this->load->model('tutor_mdl');

        $where = array('idx'=>$request['mset_idx']);
        $param = array(
            'status'                  => $status,
            'overall_score'           => $request['overall_score'],
            'overall_total'           => '100',
            'overall_level'           => $request['overall_level'],
            'overall_level_message'   => $request['overall_level_message'],
            'overall_description'     => $request['overall_description'],
            'overall_description_add' => $request['overall_description_add'],
            'overall_comment'         => $request['overall_comment'],
            'description_mint_level'  => 'Level '.$request['overall_level'],
            'examiner_job'            => $request['examiner_job'],
            'change_regdate'          => date('Y-m-d H:i:s')
        );
        foreach($evaluationList as $val)
        {
            $param[$val.'_level']           = $request[$val.'_level'];
            $param[$val.'_description']     = $request[$val.'_description'];
            $param[$val.'_description_add'] = $request[$val.'_description_add'];
            $param[$val.'_advice']          = $request[$val.'_advice'];
            $param[$val.'_advice_add']      = $request[$val.'_advice_add'];
            $param[$val.'_comment']         = $request[$val.'_comment'];
        }
        $mset = $this->mset_mdl->update_mset($param,$where);
        if($mset < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg']      = "MSET UPDATE DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $param2 = array('present' => $request['present']);
        $schedule = $this->lesson_mdl->update_wiz_schedule($request['sc_id'],$param2);
        if($schedule < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg']      = "SCHEDULE UPDATE DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        //평가완료 안내 발송
		if ($request['status'] != '2' && $request['present'] == '2') 
        {
            //퀘스트 정보 전송
            MintQuest::request_batch_quest('11_55_23', $request['mset_idx'], $request['uid']);

            //평가 완료 안내 알림톡 발송
            if($request['mobile'])
            {
                $aDate = explode(" ", $request['startday']);
                $options = array(
                    'args'           => 'class',
                    'time'           => substr($aDate[1],0,-3),
                    'date'           => $aDate[0],
                    'name'           => $request['name'],
                    'uid'            => $request['uid'],
                    'wiz_id'         => $request['wiz_id'],
                    'spare'          => 'Y',
                    'spare_code'     => '200',
                    'spare_term_min' => '5'
                );
                sms::send_atalk($request['mobile'], 'MINT06001F', $options);
            }

            // 평가완료 안내 알림
            $this->load->model('notify_mdl');
            
            $aNotifyData = array(
                'uid'        => $request['uid'],
                'code'       => '310',
                'board_name' => 'MSET 평가결과 등록 알림',
                'message'    => 'MSET 평가결과가 등록되었습니다.',
                'go_url'     => 'https://story.mint05.com/#/mset-details',
                'regdate'    => date('Y-m-d H:i:s')
            );
            $this->notify_mdl->insert_notify($aNotifyData);
        }

        //MSET 레벨 정보 업데이트
        $this->tutor_mdl->update_mset_level_for_member($request['uid'],$request['overall_level']);

        $return_array['res_code'] = '0000';
        $return_array['msg']      = "update success!";
        echo json_encode($return_array);
        exit;
    }
    

    // 레벨테스트 뷰 팝업
    public function popup_leveltest_view()
    {
        $return_array = array();

        $request = array(
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "authorization" => trim($this->input->post('authorization')),
            "sc_id"         => trim($this->input->post('sc_id')) ? trim($this->input->post('sc_id')) : '',
            // "uid"           => trim($this->input->post('uid')) ? trim($this->input->post('uid')) : '',
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('lesson_mdl');
        $where_schedule = "WHERE sc_id = '{$request['sc_id']}' AND lesson_id = 100000000";
        $list_schedule_count = $this->lesson_mdl->list_count_schedule('', $where_schedule);
        
        if($list_schedule_count['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0212";
            $return_array['data']['err_msg'] = "No exists schedule";
            echo json_encode($return_array);
            exit;
        }


        $this->load->model('leveltest_mdl');
        $where = "WHERE sc_id = '{$request['sc_id']}'";
        $list_leveltest_count = $this->leveltest_mdl->list_count_leveltest_where('', $where);
        
        if($list_leveltest_count['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "No data";
            echo json_encode($return_array);
            exit;
        }

        $leveltest = $this->leveltest_mdl->row_leveltest_by_sc_id($request['sc_id']);

        $recommended_course = $this->leveltest_mdl->list_recomended_course();
        $recommended_level = $this->leveltest_mdl->list_recomended_level();

        // 추천 교재 정보 
        $recommended_textbook = array();
        $list_textbooks = $this->leveltest_mdl->list_textbook();

        foreach($list_textbooks AS $list_textbook){
            $list_recomended_textbooks = $this->leveltest_mdl->list_recomended_textbook($list_textbook['wb_f_id']);
            array_push($recommended_textbook, $list_textbook);

            foreach($list_recomended_textbooks AS $list_recomended_textbook){
                array_push($recommended_textbook, $list_recomended_textbook);
            }
        }

        $member_howman = $this->leveltest_mdl->row_member_howman_by_uid($leveltest['wtutor_mdlm_uid']);


        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['info'] = $leveltest;
        $return_array['data']['recommended_course'] = $recommended_course;
        $return_array['data']['recommended_level'] = $recommended_level;
        $return_array['data']['recommended_textbook'] = $recommended_textbook;
        $return_array['data']['member_howman'] = $member_howman;
        
        echo json_encode($return_array);
        exit;
    }

    public function update_popup_leveltest()
    {
        $return_array = array();
        
        $request = array(
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "authorization" => trim($this->input->post('authorization')),
            "uid"           => trim($this->input->post('uid')) ? trim($this->input->post('uid')) : '',
            "sc_id"         => trim($this->input->post('sc_id')) ? trim($this->input->post('sc_id')) : '',
            "le_id"         => trim($this->input->post('le_id')) ? trim($this->input->post('le_id')) : '',
            "repclass"      => trim($this->input->post('repclass')) ? trim($this->input->post('repclass')) : '',
            "book_id"       => trim($this->input->post('book_id')) ? trim($this->input->post('book_id')) : '',
            "lev_id"        => trim($this->input->post('lev_id')) ? trim($this->input->post('lev_id')) : '',
            "ev_memo"       => $this->input->post('ev_memo') ? ($this->input->post('ev_memo')) : '',
            "present"       => trim($this->input->post('present')) ? trim($this->input->post('present')) : '',
            "listening"     => trim($this->input->post('listening')) ? trim($this->input->post('listening')) : '',
            "speaking"      => trim($this->input->post('speaking')) ? trim($this->input->post('speaking')) : '',
            "pronunciation" => trim($this->input->post('pronunciation')) ? trim($this->input->post('pronunciation')) : '',
            "vocabulary"    => trim($this->input->post('vocabulary')) ? trim($this->input->post('vocabulary')) : '',
            "grammar"       => trim($this->input->post('grammar')) ? trim($this->input->post('grammar')) : '',
            "mail_send"     => $this->input->post('mail_send') ? trim($this->input->post('mail_send')) : 'n',
            "le_start"      => $this->input->post('le_start') ? ($this->input->post('le_start')) : '',
            "resultdate"    => $this->input->post('resultdate') ? ($this->input->post('resultdate')) : '',
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $now = time();
        $le_start = strtotime($request['le_start']);

        if($le_start > $now){
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0202";
            $return_array['data']['err_msg'] = "Can not save before testing !!";
            echo json_encode($return_array);
            exit;
        }

        if($request['present'] == '1'){
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "Standby -> (X) .. Attendance error 2";
            echo json_encode($return_array);
            exit;
        }

        if($request['present'] == '2'){

            if(!$request['repclass']){
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0301";
                $return_array['data']['err_msg'] = "please, Insert Recommended Course..";
                echo json_encode($return_array);
                exit;
            }
            if(!$request['lev_id']){
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0302";
                $return_array['data']['err_msg'] = "please, Insert Recommended Level..";
                echo json_encode($return_array);
                exit;
            }
            if($request['lev_id'] == '31'){
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0303";
                $return_array['data']['err_msg'] = "please, ABSENT 선택 하면 안됩니다.";
                echo json_encode($return_array);
                exit;
            }

            if(!$request['book_id']){
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0304";
                $return_array['data']['err_msg'] = "please, Insert Recommended Textbook..";
                echo json_encode($return_array);
                exit;
            }

            if(!$request['listening']){
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0305";
                $return_array['data']['err_msg'] = "please, listening..";
                echo json_encode($return_array);
                exit;
            }

            if(!$request['speaking']){
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0306";
                $return_array['data']['err_msg'] = "please, speaking..";
                echo json_encode($return_array);
                exit;
            }

            if(!$request['pronunciation']){
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0307";
                $return_array['data']['err_msg'] = "please, pronunciation";
                echo json_encode($return_array);
                exit;
            }

            if(!$request['vocabulary']){
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0308";
                $return_array['data']['err_msg'] = "please, vocabulary";
                echo json_encode($return_array);
                exit;
            }

            if(!$request['grammar']){
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0309";
                $return_array['data']['err_msg'] = "please, grammar";
                echo json_encode($return_array);
                exit;
            }

            $le_step = '3';

            //퀘스트 정보 보내기 , 수정일 경우에만
            MintQuest::request_batch_quest('5', $request['le_id'], $request['uid']);

        }else if($request['present'] == '3'){

            $repclass="";
            $lev_id="31";
            $book_id="";
        
            $listening="";
            $speaking="";
            $pronunciation="";
            $vocabulary="";
            $grammar="";
        
            $le_step = '2';
        }

        $this->load->model('leveltest_mdl');

        $res_wiz_book = $this->leveltest_mdl->row_wiz_book_by_book_id($request['book_id']);
        $res_wiz_level = $this->leveltest_mdl->row_wiz_level_by_lev_id($request['lev_id']);


        if($request['resultdate']=="" || $request['resultdate']=="0000-00-00 00:00:00"){
            $resultdate = date("Y-m-d H:i:s");
        }else{
            $resultdate = $request['resultdate'];
        }

        $params = array(
            'book_id' => $request['book_id'],
            'book_name' => $res_wiz_book['wb_book_name'],
            'lev_id' => $request['lev_id'],
            'lev_name' => $res_wiz_level['wl_lev_name'],
            'lev_gubun' => $res_wiz_level['wl_lev_gubun'],
            'repclass' => $request['repclass'],
            'listening' => $request['listening'],
            'speaking' => $request['speaking'],
            'pronunciation' => $request['pronunciation'],
            'vocabulary' => $request['vocabulary'],
            'grammar' => $request['grammar'],
            'ev_memo' => $request['ev_memo'],
            'gra_memo' => $request['gra_memo'],
            'pro_memo' => $request['pro_memo'],
            'rec_memo' => $request['rec_memo'],
            'le_step' => $le_step,
            'resultdate' => $resultdate,
            'mail_send' => $request['mail_send'],
        );

        $result = $this->leveltest_mdl->update_leveltest($params, $request['le_id'], $request['present'], $request['sc_id']);

        if($result > 0){

            $return_array['res_code'] = '0000';
            $return_array['msg'] = "OK~~";
            echo json_encode($return_array);
            exit;

        }else{

            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
    }

    public function join_mint_english()
    {

        $return_array = array();
        
        $request = array(
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "authorization" => trim($this->input->post('authorization')),
            "type"          => trim($this->input->post('type')) ? trim($this->input->post('type')) : '',
            "sc_id"         => trim($this->input->post('sc_id')) ? trim($this->input->post('sc_id')) : '',
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $socket_room_info = array();

        $this->load->model('tutor_mdl');
        $schedule = $this->tutor_mdl->row_schedule_by_sc_id($request['sc_id']);

        if(!$schedule)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg']  = "수업 정보가 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        /*
            번호 순서
            1. wm_member['mobile'];
            2. wiz_schedule['mobile'];
            3. wiz_schedule['pmobile'];
            4. 0000 << 해외거주자일 가능성이 높음
        */

        if($request['type']=='join'){

            $maaltalk = $this->tutor_mdl->maaltalk_tutor_url_info($wiz_tutor['wt_tu_uid']);

            // 강사 방으로 입장후 로그 쌓기
            $insert_maaltalk_note_log_mobile = array(
                'tu_uid'                    => $wiz_tutor['wt_tu_uid'],
                'wm_uid'                    => '',
                'sc_id'                     => $request['sc_id'],
                'state'                     => '0',
                'receipt_number'            => '',
                'invitational_url'          => $maaltalk['mntu_tutor_url'],
                'msg_type'                  => '',
                'loc'                       => '',
                'class_start_time'          => $schedule['ws_startday'],
                'class_end_time'            => $schedule['ws_endday'],
                'regdate'                   => date('Y-m-d H:i:s')
            );
            $tutor_log = $this->tutor_mdl->insert_maaltalk_note_log($insert_maaltalk_note_log_mobile);
            if($tutor_log < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "INSERT MAALTALK NOTE Mobile LOG - DB ERROR";
                echo json_encode($return_array);
                exit;
            }

        }
        
        $res_shorten_url = maaltalk_invite_set_url($wiz_tutor, $request['sc_id']);
            
            
        if($res_shorten_url['code'] == '0000')
        {
            $content = $schedule['wm_name'].'님! 민트영어 강의실이 열렸어요! '.$res_shorten_url['m_shorten_url']['result']['url'];
            $options = array(
                'uid'      => $schedule['wm_uid'],
                'wiz_id'   => $schedule['wm_wiz_id'],
                'name'     => $schedule['wm_name'],
                'content'  => $content,
                'man_id'   => $wiz_tutor['wt_tu_id'],
                'man_name' => $wiz_tutor['wt_tu_name']
            );

            /* 푸시 전송후 푸시 실패시 sms */

            $p_data = array();
            $p_data['msg'] = $options['content'];
            $p_data['member'] = $schedule['wm_name'];

            // $p_data['url'] = $res_shorten_url['m_shorten_url']['result']['url'];
            
            // 어플에는 변환 url이 아닌 원본 url 전송
            $p_data['url'] = urldecode($res_shorten_url['student_url']);
            // test 강사 url : https://vc.dial070.co.kr/checkpage.html?uri=/wt_1462&st=mint&id=wt_1462
            // test 학생 url : https://vc.dial070.co.kr/wt_1462?st=mint&id=LeeDoori[01089509715]
            
            $res_push = AppPush::send_push($schedule['wm_uid'], '5000', $p_data);
            $text = '초대 푸시 전송';

            if($res_push['res_code'] != '0000'){

                // 등록된 토큰으로 푸시 전송 전부 실패시 문자 전송
                $sms = sms::send_sms($schedule['ws_mobile'],'',$options);
                $text = '초대 문자 전송';
            }

            $insert_maaltalk_note_log_mobile = array(
                'tu_uid'                    => $wiz_tutor['wt_tu_uid'],
                'wm_uid'                    => $schedule['wm_uid'],
                'sc_id'                     => $request['sc_id'],
                'state'                     => '1',
                'receipt_number'            => $schedule['ws_mobile'],
                'origin_url'                => $res_shorten_url['m_url'],
                'invitational_url'          => $res_shorten_url['m_shorten_url']['result']['url'],
                'msg_type'                  => '1',
                'loc'                       => '1',
                'class_start_time'          => $schedule['ws_startday'],
                'class_end_time'            => $schedule['ws_endday'],
                'regdate'                   => date('Y-m-d H:i:s')
            );
            $mobile_log = $this->tutor_mdl->insert_maaltalk_note_log($insert_maaltalk_note_log_mobile);
            if($mobile_log < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "INSERT MAALTALK NOTE Mobile LOG - DB ERROR";
                echo json_encode($return_array);
                exit;
            }

            $insert_maaltalk_note_log_pc = array(
                'tu_uid'                    => $wiz_tutor['wt_tu_uid'],
                'wm_uid'                    => $schedule['wm_uid'],
                'sc_id'                     => $request['sc_id'],
                'state'                     => '1',
                'receipt_number'            => '-',
                'origin_url'                => $res_shorten_url['pc_url'],
                'invitational_url'          => $res_shorten_url['pc_shorten_url']['result']['url'],
                'msg_type'                  => '1',
                'loc'                       => '2',
                'class_start_time'          => $schedule['ws_startday'],
                'class_end_time'            => $schedule['ws_endday'],
                'regdate'                   => date('Y-m-d H:i:s')
            );
            $pc_log = $this->tutor_mdl->insert_maaltalk_note_log($insert_maaltalk_note_log_pc);
            if($pc_log < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "INSERT MAALTALK NOTE Mobile LOG - DB ERROR";
                echo json_encode($return_array);
                exit;
            }

            //소켓룸 배열에 룸정보 푸시(uid)
            array_push($socket_room_info, $schedule['wm_uid']);
            
        }
        
        /*
            1:다수 수업일 경우 서브 학생들에게도 초대 문자 전송 후 log 추가
        */
        if($schedule['wl_student_uid'])
        {

            $student_sub_uid = explode(",",$schedule['wl_student_uid']);
            
            for($i=1; $i<count($student_sub_uid); $i++) 
            {
                if($student_sub_uid[$i] != '' && $student_sub_uid[$i] != null) 
                {
                    $sub_member = $this->tutor_mdl->get_wiz_member_by_wm_uid($student_sub_uid[$i]);

                    if($sub_member)
                    {
                        $res_shorten_url_sub_member = maaltalk_invite_set_url_sub_member($wiz_tutor, $request['sc_id'], $sub_member);

                        $sub_member_mobile = str_replace("-","",$sub_member['wm_mobile']); 
                        $content = $sub_member['wm_name'].'님! 민트영어 강의실이 열렸어요! '.$res_shorten_url_sub_member['m_shorten_url']['result']['url'];
                        $options = array(
                            'uid'      => $sub_member['wm_uid'],
                            'wiz_id'   => $sub_member['wm_wiz_id'],
                            'name'     => $sub_member['wm_name'],
                            'content'  => $content,
                            'man_id'   => $wiz_tutor['wt_tu_id'],
                            'man_name' => $wiz_tutor['wt_tu_name']
                        );
                        $sms = sms::send_sms($sub_member['wm_mobile'],'',$options);


                        $insert_maaltalk_note_log_mobile_sub = array(
                            'tu_uid'                    => $wiz_tutor['wt_tu_uid'],
                            'wm_uid'                    => $sub_member['wm_uid'],
                            'sc_id'                     => $request['sc_id'],
                            'state'                     => '1',
                            'receipt_number'            => $sub_member['wm_mobile'],
                            'origin_url'                => $res_shorten_url_sub_member['m_url'],
                            'invitational_url'          => $res_shorten_url_sub_member['m_shorten_url']['result']['url'],
                            'msg_type'                  => '1',
                            'loc'                       => '1',
                            'class_start_time'          => $schedule['ws_startday'],
                            'class_end_time'            => $schedule['ws_endday'],
                            'regdate'                   => date('Y-m-d H:i:s')
                        );
                        $imnl = $this->tutor_mdl->insert_maaltalk_note_log($insert_maaltalk_note_log_mobile_sub);
                        if($imnl < 0)
                        {
                            $return_array['res_code'] = '0500';
                            $return_array['msg'] = "INSERT MAALTALK NOTE Mobile LOG - DB ERROR";
                            echo json_encode($return_array);
                            exit;
                        }

                        $insert_maaltalk_note_log_pc_sub = array(
                            'tu_uid'                    => $wiz_tutor['wt_tu_uid'],
                            'wm_uid'                    => $sub_member['wm_uid'],
                            'sc_id'                     => $request['sc_id'],
                            'state'                     => '1',
                            'receipt_number'            => '-',
                            'origin_url'                => $res_shorten_url_sub_member['pc_url'],
                            'invitational_url'          => $res_shorten_url_sub_member['pc_shorten_url']['result']['url'],
                            'msg_type'                  => '1',
                            'loc'                       => '2',
                            'class_start_time'          => $schedule['ws_startday'],
                            'class_end_time'            => $schedule['ws_endday'],
                            'regdate'                   => date('Y-m-d H:i:s')
                        );
                        $sub_member_log = $this->tutor_mdl->insert_maaltalk_note_log($insert_maaltalk_note_log_pc_sub);
                        if($sub_member_log < 0)
                        {
                            $return_array['res_code'] = '0500';
                            $return_array['msg'] = "INSERT MAALTALK NOTE Mobile LOG - DB ERROR";
                            echo json_encode($return_array);
                            exit;
                        }

                        //소켓룸 배열에 룸정보 푸시(uid)
                        array_push($socket_room_info, $sub_member['wm_uid']);
                        
                    }
                }
            }
        }


        if($res_shorten_url['code'] == '0000'){

            $return_array['res_code'] = '0000';
            $return_array['msg'] = $text.' 성공';
            $return_array['data']['socket_room_info'] = $socket_room_info;
            $return_array['data']['pc_url'] = $res_shorten_url['pc_url'];
            $return_array['data']['startday'] = $schedule['ws_startday'];
            $return_array['data']['endday'] = $schedule['ws_endday'];
            echo json_encode($return_array);
            exit;

        }else{

            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0501";
            $return_array['data']['err_msg'] = '단축 url 실패';
            echo json_encode($return_array);
            exit;
        }      
        
    }

    // 레벨테스트 리스트 팝업
    public function popup_leveltest_list()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "uid"           => trim($this->input->post('uid')) //필수
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }
        
        $this->load->model('tutor_mdl');
        
        //회원정보 가져오기
        $userInfo = $this->tutor_mdl->get_member($request['uid']);
        if(!$userInfo)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0212";
            $return_array['data']['err_msg']  = "No exists schedule";
            echo json_encode($return_array);
            exit;
        }

        $leveltest = $this->tutor_mdl->list_wiz_leveltest($request['uid']);

        $return_array['res_code']     = '0000';
        $return_array['msg']          = "목록조회성공";
        $return_array['data']['info'] = $userInfo;        
        $return_array['data']['list'] = $leveltest;        
        echo json_encode($return_array);
        exit;
    }
    
    
    //수업연장 가능한지 체크
    public function check_possible_extend_class()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), 
            "sc_id"           => trim($this->input->post('sc_id')) 
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }
        
        $this->load->model('tutor_mdl');
        $this->load->model('lesson_mdl');

        //스케쥴(and 강사) 정보 가져오기
        $schedule = $this->lesson_mdl->row_schedule_by_sc_id_and_tu_uid($request['sc_id'], $wiz_tutor['wt_tu_uid']);
        if(!$schedule)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0212";
            $return_array['data']['err_msg']  = "No exists schedule!(2)";
            echo json_encode($return_array);
            exit;
        }
        
        $is_possible_extent = schedule_check_possible_extend_class($schedule['uid'], $schedule['lesson_id'], $wiz_tutor['wt_tu_uid'], strtotime($schedule['endday']) + 1, $schedule['cl_time']);

        if(!$is_possible_extent)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0218";
            $return_array['data']['err_msg']  = "No possible extend class";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code']     = '0000';
        $return_array['msg']          = "";     
        echo json_encode($return_array);
        exit;
    }


    //수업연장 요청
    public function send_request_extend_class()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), 
            "sc_id"           => trim($this->input->post('sc_id')) 
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //강사정보
        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }
        
        $this->load->model('lesson_mdl');

        //이미 보낸내역 있으면 요청만 한다
        $check = $this->lesson_mdl->row_class_extension_by_sc_id($request['sc_id']);

        if($check['approval_date'])
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "이미 처리되었습니다.";
            echo json_encode($return_array);
            exit;
        }

        //스케쥴(and 강사) 정보 가져오기
        $schedule = $this->lesson_mdl->row_schedule_by_sc_id_and_tu_uid($request['sc_id'], $wiz_tutor['wt_tu_uid']);
        if(!$schedule)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0212";
            $return_array['data']['err_msg']  = "No exists schedule!(2)";
            echo json_encode($return_array);
            exit;
        }

        if($check && $check['is_deny'] =='0')
        {
            $insert_id = $check['idx'];
            $next_start_date = substr($check['limit_date'],11,5);

            $code = (new OldEncrypt('(*&DHajaan=f0#)2'))->encrypt($insert_id);
            $link = set_new_or_old_url('/#/class-extend?u='.$code);
        }
        else
        {
            $is_possible_extent = schedule_check_possible_extend_class($schedule['uid'], $schedule['lesson_id'], $wiz_tutor['wt_tu_uid'], strtotime($schedule['endday']) + 1, $schedule['cl_time']);

            if(!$is_possible_extent)
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0218";
                $return_array['data']['err_msg']  = "No possible extend class";
                echo json_encode($return_array);
                exit;
            }

            $insert_id = $this->lesson_mdl->insert_wiz_class_extension([
                'uid'           => $schedule['uid'],
                'sc_id'         => $request['sc_id'],
                'request_date'  => date('Y-m-d H:i:s'),
                'limit_date'    => date('Y-m-d H:i:s', strtotime($schedule['endday']) + 1),
            ]);

            if($insert_id  < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }

            $next_start_date = date('H:i', strtotime($schedule['endday']) + 1);

            $code = (new OldEncrypt('(*&DHajaan=f0#)2'))->encrypt($insert_id);

            $link = set_new_or_old_url('/#/class-extend?u='.$code);

            //SMS 혹은 알림특 요청
            $options = array(
                'uid'          => $schedule['uid'],
                'wiz_id'       => $schedule['wiz_id'],
                'name'         => $schedule['ws_name'],
                'man_id'       => $wiz_tutor['wt_tu_id'],
                'man_name'     => $wiz_tutor['wt_tu_name'],
                'time'         => $next_start_date,     //연장제한시간은 이전수업의 종료시간까지이므로 다음수업의 시작예정시간이기도 하다.
                'cl_time'      => $schedule['cl_time'],
                'tu_name'      => $wiz_tutor['wt_tu_name'],
                'link'         => $link,
            );

            sms::send_sms($schedule['ws_mobile'],340,$options);
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['extend_code'] = $code;
        $return_array['data']['extend_link'] = $link;
        echo json_encode($return_array);
        exit;
    }


    /*
        북미 강사 DRB 목록
    */
    public function drb_list_()
    {
        $return_array = array();

        $request = array(
            'start'         => $this->input->post('start') ? $this->input->post('start') : 0,
            'limit'         => $this->input->post('limit') ? $this->input->post('limit') : '20',
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "search"        => trim($this->input->post('search')),
            "keyword"       => trim($this->input->post('keyword')),
            "order_field"   => ($this->input->post('order_field')) ? trim(strtoupper($this->input->post('order_field'))) : "md.md_id",
            "order"         => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $search = array();
        $search[] = "(md.md_receiver_uid = '".$wiz_tutor['wt_tu_uid']."' || md.md_receiver_uid='0')";
        $search[] = "(md.md_company = '".$wiz_tutor['wt_company']."' || md.md_company = '0')";
        $search[] = "md.md_is_comment='0'";

        //검색
        if($request['search'] && $request['keyword']) $search[] = $request['search']." like '%".$request['keyword']."%'";

        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('Tutor_mdl');
        $list_cnt = $this->Tutor_mdl->list_drb_count($where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No Data";
            echo json_encode($return_array);
            exit;
        }

        $limit = "";
        
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        $result = $this->Tutor_mdl->list_drb($where, $order, $limit);
        if($result < 0)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No Data(2)";
            echo json_encode($return_array);
            exit;
        }

        foreach($result as $key=>$value)
        {
            $comment = $this->Tutor_mdl->list_drb_comment_count($value['md_id']);
            $result[$key]['comment_cnt'] = $comment ? $comment['cnt'] : 0;
        }

        $return_array['res_code']          = '0000';
        $return_array['msg']               = "DRB list";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list']      = $result;
        echo json_encode($return_array);
        exit;
    }

    /*
        북미 강사 DRB 정보(view)
    */
    public function drb_article()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "no"            => trim($this->input->post('no')), //필수
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('Tutor_mdl');

        $result = $this->Tutor_mdl->writer_drb($wiz_tutor['wt_tu_uid'], $request['no']);
        if(!$result)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No Data";
            echo json_encode($return_array);
            exit; 
        }

        $result['md_content']  = common_textarea_out($result['md_content']);
        
        $return_array['res_code']     = '0000';
        $return_array['msg']          = "";
        $return_array['data']['info'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /*
        북미 강사 DRB 코맨트 리스트(view)
    */
    public function drb_comment_list_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "no"            => trim($this->input->post('no')), //필수
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('Tutor_mdl');

        $result = $this->Tutor_mdl->list_drb_comment($request['no']);
        if(!$result)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "No Data";
            echo json_encode($return_array);
            exit; 
        }

        foreach($result as $key=>$value)
        {
            $result[$key]['md_content'] = nl2br($value['md_content']);
        }
        
        $return_array['res_code']     = '0000';
        $return_array['msg']          = "";
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /*
        북미 강사 DRB 쓰기
        no : no값이 들어오면 코맨트 작성으로 취급한다.
    */
    public function drb_write()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "no"            => $this->input->post('no') ? trim($this->input->post('no')) : '',
            "title"         => $this->input->post('title') ? trim($this->input->post('title')) : '',
            "content"       => trim($this->input->post('content')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $article = array(
            'md_title'         => $request['title'],
            'md_writer_uid'    => $wiz_tutor['wt_tu_uid'],
            'md_writer_name'   => $wiz_tutor['wt_tu_name'],
            'md_company'       => '',
            'md_receiver_uid'  => '',
            'md_receiver_name' => '',
            'md_content'       => $request['content'],
            'md_regdate'       => date('Y-m-d H:i:s')
        );

        if($request['no'])
        {
            $article['md_parents_id'] = $request['no'];
            $article['md_is_comment'] = '1';
        }
        else
        {
            $article['md_company']       = '0';
            $article['md_receiver_uid']  = '0';
            $article['md_receiver_name'] = 'Manager';
        }

        $this->load->model('Tutor_mdl');

        $result = $this->Tutor_mdl->write_drb($article);
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg']      = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code']   = '0000';
        $return_array['msg']        = "Regist success";
        $return_array['data']['id'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /*
        북미 강사 DRB 수정
    */
    public function drb_modify()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "no"            => trim($this->input->post('no')), //필수
            "title"         => ($this->input->post('title')) ? trim($this->input->post('title')) : '',
            "content"       => ($this->input->post('content')) ? trim($this->input->post('content')) : '',
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $article = array(
            'md_title'   => $request['title'],
            'md_content' => $request['content']
        );

        $where = array(
            'md_writer_uid' => $wiz_tutor['wt_tu_uid'],
            'md_id'         => $request['no']
        );

        $this->load->model('Tutor_mdl');

        $result = $this->Tutor_mdl->update_drb($article, $where);
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg']      = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg']      = "modify success";
        echo json_encode($return_array);
        exit;
    }

    /*
        북미 강사 DRB 삭제
    */
    public function drb_delete()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')), //필수
            "tu_id"         => trim(strtolower($this->input->post('tu_id'))), //필수
            "no"            => trim($this->input->post('no')), //필수
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_tutor = base_get_wiz_tutor();
        if(!$wiz_tutor)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('Tutor_mdl');

        $result = $this->Tutor_mdl->delete_drb($wiz_tutor['wt_tu_uid'], $request['no']);
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg']      = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg']      = "delete success";
        echo json_encode($return_array);
        exit;
    }
    
}

