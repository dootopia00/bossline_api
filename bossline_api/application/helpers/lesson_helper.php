<?php
defined("BASEPATH") OR exit("No direct script access allowed");


function lesson_current_class_status($uid='')
{
    if(!$uid) return false;

    $CI =& get_instance();
    $CI->load->model('lesson_mdl');

    $lesson = $CI->lesson_mdl->lesson_list_by_wm_uid($uid);

    $have_state = [];
    $free_lesson_ing = 'N';
    if($lesson)
    {
        foreach($lesson as $val)
        {
            if($val['schedule_ok']=="Y") 
            {
                if($val['tt_7']>0 && $val['tu_uid']=="158") $have_state[]= 3;
                else if($val['endday'] >= date("Y-m-d") && $val['lesson_state']=="in class") $have_state[]= 1;
                else if($val['startday'] > date("Y-m-d") && $val['lesson_state']=="in class") $have_state[]= 2;
                else if($val['refund_ok'] !='Y')  $have_state[]= 5;

                if($val['cl_gubun'] =='2' && $val['lesson_state']=="in class" && $val['endday'] >= date("Y-m-d"))
                {
                    $free_sc_data = lesson_check_freedomclass_cnt([
                            'wl_lesson_id' => $val['lesson_id'],
                            'wl_cl_number' => $val['cl_number'],
                            'wl_cl_class' => $val['cl_class'],
                            'wl_tt_add' => $val['tt_add'],
                        ]);

                    // 자유수업 횟수가 남아있으면 Y로 변경
                    if($free_sc_data['remain_class_total_cnt'] > 0)
                    {
                        $free_lesson_ing = 'Y';
                    }
                    
                }
            } 
            else if($val['regdate'] != "" && $val['plandate'] = "0000-00-00 00:00:00") $have_state[]= 4;
        }
    }
    

    if(in_array(1,$have_state))
    {
        $lesson_stateNow = '수업중';
    }
    elseif(in_array(2,$have_state))
    {
        $lesson_stateNow = '수업예정';
    }
    elseif(in_array(3,$have_state))
    {
        $lesson_stateNow = '장기연기중';
    }
    elseif(in_array(4,$have_state))
    {
        $lesson_stateNow = '수업배정중';
    }
    elseif(in_array(5,$have_state))
    {
        $lesson_stateNow = '수업종료';
    }
    else
    {
        $lesson_stateNow = '첫수강신청';
    }

    return array('main_lesson_state'=> $lesson_stateNow, 'free_lesson_ing' => $free_lesson_ing);
}

function lesson_schedule_present($present)
{
    $state = [
        1   => 'Ready',
        2   => 'Present',
        3   => 'Absent',
        4   => 'Cancel',
        5   => 'Holiday',
        6   => 'Postpone',
        7   => '장기연기'
    ];

    return array_key_exists($present,$state) ? $state[$present]:'';
}

/*
    화상영어 분수 표기 치환
*/
function lesson_replace_cl_name_minute($cl_name, $lesson_gubun, $only_num=false)
{  
    if($lesson_gubun == 'V' || $lesson_gubun == 'B' || $lesson_gubun == 'E') 
    {
        if($only_num)
        {
            $cl_name = str_replace('10','7',$cl_name);
            $cl_name = str_replace('20','15',$cl_name);
            $cl_name = str_replace('30','25',$cl_name);
            $cl_name = str_replace('60','55',$cl_name);
        }
        else
        {
            $cl_name = str_replace('10분','7분',$cl_name);
            $cl_name = str_replace('20분','15분',$cl_name);
            $cl_name = str_replace('30분','25분',$cl_name);
            $cl_name = str_replace('60분','55분',$cl_name);
        }
        
    }

    return $cl_name;
}

/*
    출석부를 등록했는지, 등록전인지, 종료되었는지.
*/
function lesson_regist_state_to_str($schedule_ok, $lesson_state='')
{  
    $state = '';
    if($schedule_ok == 'Y' && $lesson_state =='in class') 
    {
        $state = '수업중';
    }
    elseif($lesson_state =='holding') 
    {
        $state = '장기연기';
    }
    elseif($schedule_ok == 'N')
    {
        $state = '등록대기';
    }
    elseif($schedule_ok == 'Y' && $lesson_state =='finished')
    {
        $state = '수업종료';
    }

    return $state;
}


/*
    수업구분 코드->문자열
*/
function lesson_gubun_to_str($lesson_gubun)
{  
    $str = '';
    if($lesson_gubun == 'V') $str = '일반 화상영어';
    elseif($lesson_gubun =='M') $str = '전화영어';
    elseif($lesson_gubun =='E') $str = '민트영어Live';
    elseif($lesson_gubun =='T') $str = '전화영어';
    elseif($lesson_gubun =='B') $str = '민트비';
    elseif($lesson_gubun =='W') $str = '영어첨삭';  //임의의 구분자 값이다.

    return $str;
}

/*
    입금상태
*/
function lesson_pay_state_to_str($pay_ok, $refund_ok='')
{  
    $state = '';
    if($refund_ok == 'Y') 
    {
        $state = '환불완료';
    }
    elseif($pay_ok == 'Y')
    {
        $state = '결제완료';
    }
    elseif($pay_ok != 'Y')
    {
        $state = '미입금';
    }

    return $state;
}

// 스케쥴 상태변경. 피드백 OR 강제상태변경
function lesson_schedule_state_change($sc_id, $param)
{
    $CI =& get_instance();
    $CI->load->model('lesson_mdl');
    $CI->load->model('tutor_mdl');

    $schedule = $CI->lesson_mdl->row_schedule_by_sc_id($sc_id,$param['uid']);
    $lesson_id = $schedule['lesson_id'];
    $present = $param['present'];

    if($schedule['wl_cl_gubun'] =='2')
    {
        $free_sc_data = lesson_check_freedomclass_cnt($schedule);
        //5,6 에서 1,2,3,4로 변경 시 금주 주기횟수 남아있으면 변경 가능.
        if($free_sc_data['remain_week_class_cnt'] < 1 && $present < 5 &&  $schedule['present'] > 4)
        {
            return array('state' => false, 'msg' => 'The student spent all the classes allocated this week.');
        }
    }

    // wiz_long_schedule update. 수업 상태 변경때 present 가 2 / 3 / 4 값이면 장기연기 카운팅 중인 수업인지 체크 하여 해제
    $delYn = 'N';
    if(in_array($present,array(2,3,4)))
    {
        $delYn = 'Y';
    }

    $nowTime = time();
    $Time120 = $nowTime-(3600*120);
    $CI->lesson_mdl->update_wiz_long_schedule($delYn, $param['uid'], $lesson_id, $Time120, $nowTime);

    // 스케쥴 상태 업데이트
    $scheduleParams = [
        'present' => $present,
        'topic'   => $param['topic_today'] ? $param['topic_today']:'',
    ];
    if($param['ab_ok']) $scheduleParams['ab_ok'] = $param['ab_ok'];
    $result = $CI->lesson_mdl->update_wiz_schedule($sc_id, $scheduleParams);

    if($result < 1)
    {
        return array('state' => false, 'msg' => 'UPDATE SCHEDULE - DB ERROR');
    }

    // 스케쥴 피드백 등록
    $queryParam = [
        #'rating_ls'     => $param['rating_ls'] ? $param['rating_ls']:'0',
        #'rating_ss'     => $param['rating_ss'] ? $param['rating_ss']:'0',
        #'rating_pro'    => $param['rating_pro'] ? $param['rating_pro']:'0',
        #'rating_voc'    => $param['rating_voc'] ? $param['rating_voc']:'0',
        #'rating_cg'     => $param['rating_cg'] ? $param['rating_cg']:'0',
        'pronunciation' => $param['pronunciation'] ? $param['pronunciation']:'',
        'grammar'       => $param['grammar'] ? $param['grammar']:'',
        'comment'       => $param['comment'] ? $param['comment']:'',
        'tutor_memo'    => $param['tutor_memo'] ? $param['tutor_memo']:'',
        'book_start'    => $param['book_start'] ? $param['book_start']:0,
        'book_end'      => $param['book_end'] ? $param['book_end']:0,
        'topic_previous'=> $param['topic_previous'] ? $param['topic_previous']:'',
        'topic_today'   => $param['topic_today'] ? $param['topic_today']:'',
        'topic_next'    => $param['topic_next'] ? $param['topic_next']:'',
        'topic_date'    => $param['topic_date'] ? $param['topic_date']:date('Y-m-d'),
        'absent_reason' => $param['absent_reason'] ? $param['absent_reason']:'',
    ];

    $feedback = $CI->lesson_mdl->row_lesson_feedback_by_sc_id($sc_id);

    if($feedback)
    {
        $result = $CI->lesson_mdl->update_wiz_schedule_result($sc_id,$queryParam);
        if($result < 1) return array('state' => false, 'msg' => 'UPDATE feedback - DB ERROR');
    }
    else
    {
        $queryParam['sc_id'] = $sc_id;
        $queryParam['lesson_id'] = $lesson_id;
        $result = $CI->lesson_mdl->insert_wiz_schedule_result($queryParam);
        if($result < 1) return array('state' => false, 'msg' => 'INSERT feedback - DB ERROR');
    }

    // wiz_lesson테이블에 업데이트 해줄 인자들 모아서 밑에서 한번에 업뎃시키위한 변수
    $wiz_lesson_update_param = [];

    // 강사상담에 의한 재수강 여부 저장
    if($param['renewal_ok'])
    {
        $wiz_lesson_update_param = [
            'renewal_ok' => $param['renewal_ok'],
            'renewal_reason' => $param['renewal_reason'],
        ];
    }

    if($schedule['wl_cl_gubun'] =='2')
    {
        $nextClass = $free_sc_data['remain_class_total_with_ready_cnt'] > 0 ? true : false;
    }
    else
    {
        // 다음수업 있는지 체크.
        $nextClass = $CI->tutor_mdl->get_schedule_info_for_chk($sc_id, $lesson_id, $schedule['startday']);
        // 다음수업에 토픽 미리 넣어주기
        if($nextClass)
        {
            $result = $CI->lesson_mdl->update_wiz_schedule($nextClass['sc_id'], [
                'topic' => $param['topic_next'] ? $param['topic_next']:'',
            ]);
        }
    }
    
    if(!$nextClass)
    {
        // 해당 수업 끝일 경우 finished
        if(in_array($present,array(2,3,4)) && $schedule['wl_lesson_state']!="holding")
        {
            $wiz_lesson_update_param['lesson_state'] = 'finished';
        }
    }

    // 수업일수 체크
    $TT = $CI->lesson_mdl->checked_tt_by_lesson_id($lesson_id);
    if($TT)
    {
        if($schedule['wl_cl_gubun'] =='2')
        {
            $TT['tt1'] = $free_sc_data['remain_class_total_with_ready_cnt'];
        }

        $queryParam = [
            'tt_1' => $TT['tt1'],
            'tt_2' => $TT['tt2'],
            'tt_3' => $TT['tt3'],
            'tt_4' => $TT['tt4'],
            'tt_5' => $TT['tt5'],
            'tt_6' => $TT['tt6'],
            'tt_7' => $TT['tt7'],
            'tt_8' => $TT['tt8'],
        ];
        $wiz_lesson_update_param = $wiz_lesson_update_param + $queryParam;
    }

    // wiz_lesson 업데이트
    if(!empty($wiz_lesson_update_param))
    {
        $CI->lesson_mdl->update_wiz_lesson($lesson_id, $wiz_lesson_update_param);
    }

    // 수업상태변경 로그
    if($schedule['present'] != $present)
    {
        // 벼락전용강사수업 같은 것은 자동으로 수업결석 처리하므로 시행자를 임의로 __SYSTEM__ 로 입력시킴
        if($param['is_cron'])
        {
            $man_id = '';
            $man_name = '__SYSTEM__';
        }
        else
        {
            // 로그인한 강사라면 로그인한 강사의 tu_uid로 불러와야한다.
            $man_id = $param['tu_id'];
            $man_name = $param['tu_name'];
        }

        $aDate = explode(" ", $schedule['startday']);

        $schedule_startday_chk = $CI->tutor_mdl->get_schedule_info_for_startday_chk($lesson_id);
        $MINDATE = $schedule_startday_chk['min_startday'];
        $MAXDATE = $schedule_startday_chk['max_startday'];

        $queryParam = [
            'lesson_id' => $lesson_id,
            'a_tuid' => $schedule['tu_uid'],
            'b_tuid' => $schedule['tu_uid'],
            'a_tutor' => $schedule['tu_name'],
            'b_tutor' => $schedule['tu_name'],
            'a_time' => $aDate[1],
            'b_time' => $aDate[1],
            'cl_time' => $schedule['cl_time'],
            'startday' => $MINDATE,
            'endday' => $schedule['wl_cl_gubun'] =='2' ? $schedule['wl_endday']:$MAXDATE,
            'a_date' => substr($schedule['startday'],0,10),
            'b_date' => substr($schedule['endday'],0,10),
            'man_id' => $man_id,
            'man_name' => $man_name,
            'regdate' => date('Y-m-d H:i:s'),
            'kind' => $param['kind'] ?  $param['kind']:'h',
        ];
        
        $content = lesson_schedule_present($schedule['present']).' --> '.lesson_schedule_present($present);

        $CI->lesson_mdl->insert_wiz_tutor_change($queryParam,$content);

        //업데이트 되는 상태가 2라면 수업완료 퀘스트 정보 보내기
        if($present == 2)
        {
            $q_idx = "17_25_50_182_225";
            //벼락치기 수업일경우 벼락치기 관련 퀘스트 q_idx 추가
            if($schedule['ws_kind'] == 'c')
            {
                $q_idx .= "_21_100";
            }
            
            //실시간 말소리 분석 서비스일때
            if(strpos($param['tu_name'], 'NS_Analyst') !== false) $q_idx .= "_77";
            
            //quest_api($q_idx, $LESSON['uid'], $sc_id);
            MintQuest::request_batch_quest($q_idx, $sc_id, $param['uid']);
        }

        if($present == 2 && $schedule['lesson_gubun'] =='E')
        {
            // maaltalk_note_log에 state=2 로그 없으면 강제로 넣어준다.
            $maal = $CI->tutor_mdl->get_maaltalk_note_log_for_chk($sc_id);
            if(!$maal)
            {
                $insert_maaltalk_note_log = array(
                    'tu_uid'        => $schedule['tu_uid'],
                    'wm_uid'        => $schedule['uid'],
                    'sc_id'         => $sc_id,
                    'state'         => '2',
                    'msg_type'      => '1',
                    'receipt_number'=> '-',
                    'loc'           => '3',
                    'regdate'       => date('Y-m-d H:i:s')
                );
                $CI->tutor_mdl->insert_maaltalk_note_log($insert_maaltalk_note_log);
            }
        }

        // 푸시 알림발송
        if($present == '2') 
        {
            $pInfo = array(
                "view_lesson"=> $lesson_id, 
                "teacher"=> $schedule['tu_name']
            );
            AppPush::send_push($schedule['uid'], "1103", $pInfo);
        } 
        elseif($present == '3')
        {
            $pInfo = array(
                "view_lesson"=> $lesson_id, 
                "teacher"=> $schedule['tu_name'], 
                "date"=> substr($schedule['startday'],0,10), 
            );
            AppPush::send_push($schedule['uid'], "1107", $pInfo);
        }

    }

    return array('state' => true);
}


/**
 * 할당된 자유수업 갯수 관련 정보
 * 소진 주기는 월~일
 * @param array $lesson | wiz_lesson 데이터
 */
function lesson_check_freedomclass_cnt($lesson) 
{
    if(!$lesson) 
    {
        return false;
    }

    $CI =& get_instance();
    $CI->load->model('lesson_mdl');

    /*
    평일수업: 월요일 01:00 ~ 토요일 01:00
    주말수업: 토요일 06:00 ~ 일요일 24:00
    빈시간대: 토요일 01:00 ~ 토요일 06:00, 일요일 00:00~06:00, 월요일 00:00~01:00
    */

    // #요일별 수업여부('월:화:수:목:금:토:일'의 형식으로 구분자 :를 포함해 'Y or 빈칸'으로 입력됨. '월수금'일 경우 'Y::Y::Y::'으로 입력됨)
    //$require_class_cnt = str_replace(':','',$lesson['weekend']);
    // 소진해야하는 이번주 수업 갯수
    //$require_class_cnt = strlen($require_class_cnt);	
    $require_class_cnt = (int)$lesson['wl_cl_number'];

    // 주기변경으로  1월 2일 06시~ 1월 10일 24시까지 특수주기 적용
    if(date('Y-m-d') < '2021-01-11')
    //if(0)
    {
        $free_sc_check_start_date = '2021-01-02 06:00:00';
        $free_sc_check_end_date = '2021-01-10 23:59:59';
    }
    else
    {
        // 기본 주기 월~일
        $free_sc_check_start_date = date('w') == 0 ? date('Y-m-d',strtotime('-6 day')):date('Y-m-d',strtotime('-'.(date('w')-1).' day')).' 00:00:00'; 
        $free_sc_check_end_date = date('Y-m-d 23:59:59',strtotime('+6 day', strtotime($free_sc_check_start_date))); 
    }

    // 이번주기에 소진된 소진된 수업갯수. kind:f 자유수업출석부에서 등록된 수업. 벼락치기는 여전히 c로 등록되어있고, 횟수소진 카운트에 포함 안한다
    // 자유수업 주기 시작전 고정수업때 진행한 수업도 갯수 포함해주기 위해 kind n,f,t 로 검색
    $cnt = $CI->lesson_mdl->checked_count_spend_free_schedule_this_period($lesson['wl_lesson_id'], $free_sc_check_start_date, $free_sc_check_end_date);

    // 출석부의 토탈 수업 횟수. 정규횟수 + 포인트 추가 횟수
    // tt는 믿으면 안되는 데이터. tt_add만 증감해주는곳도 있고 tt와 tt_add둘다 증감되는 곳이 있어서 불확실한데이터.
    // 그래서 tt 대신 기본 수업갯수인 cl_class 데이터를 사용
    $total_class_cnt = $lesson['wl_cl_class'] + $lesson['wl_tt_add'];

    /* 
        출석부의 총 소진 횟수. 1:대기, 2:참석, 3:결석, 4:취소.
        대기는 소진 대기, 참석,결석,취소는 소진
    */
    $done_class_total_cnt = $CI->lesson_mdl->checked_count_spend_schedule($lesson['wl_lesson_id']);
    
    // 소진못한 결석 분
    $absent_total_cnt = $CI->lesson_mdl->checked_count_free_schedule_absent($lesson['wl_lesson_id']);

    // 소진하지않은 총 수업갯수. 출석했거나, 배정했는데 결석처리 분과 배정못하고 소진못한 결석 분을 같이 빼줘야한다.
    $remain_class_total_cnt = $total_class_cnt - (int)$done_class_total_cnt['cnt'] - (int)$absent_total_cnt['cnt'];

    // 진행하지 않은 남은 수업갯수. 대기상태 + 배정하지않은 갯수(remain_class_total_cnt)
    $remain_class_total_with_ready_cnt = $remain_class_total_cnt + (int)$done_class_total_cnt['ready_cnt'];

    // 소진하지않은 이번주기 할당받은 수업갯수
    $remain_class_cnt = $require_class_cnt - (int)$cnt['cnt'];

    // 출석부가 가진 총 수업갯수만큼 수업 소진했으면 '소진하지않은 이번주기 할당받은 수업갯수'($remain_week_class_cnt)를 0으로 넣어준다.
    if($remain_class_total_cnt < 1) $remain_class_cnt = 0;

    // 배정하지 않은 남은 수업 총갯수가 이번주 남은 횟수보다 적으면, 이번주 남은횟수를 잔여 총갯수로 넣어준다
    if($remain_class_total_with_ready_cnt < $remain_class_cnt) $remain_class_cnt = $remain_class_total_with_ready_cnt;

    return array(
        'have_class_cnt' => $require_class_cnt,             // 소진해야하는 이번주기 수업 할당량
        'done_class_week_cnt' => $cnt['cnt'],                      // 이번주기 소진한 자유수업 갯수
        'total_class_cnt' => $total_class_cnt,              // 출석부의 토탈 수업 횟수
        'done_class_total_cnt' => $done_class_total_cnt['cnt'],    // 출석부의 총 소진 횟수
        'remain_week_class_cnt' => $remain_class_cnt,       // 소진하지않은 이번주 할당받은 수업갯수
        'remain_class_total_cnt' => $remain_class_total_cnt,       // 소진하지않은 총 수업갯수. 배정된 수업은 제외
        'remain_class_total_with_ready_cnt' => $remain_class_total_with_ready_cnt,       // 진행하지 않은 수업갯수. 대기상태 + 배정하지않은 갯수
    );
}

/**
 * 자유수업의 종료일 구하기. 주기를 월~일으로 하기로 했으므로 종료일은 무조건 일요일
 * @param array $lesson | wiz_lesson 데이터
 * @param string $startday | 기준이 될 수업 시작일. 이번주기부터 바로 시작 시 빈값으로 요청. 현재는 수업시작일때만 넘겨 받는다.
 */
function lesson_check_freedomclass_endday($lesson, $startday='') 
{
    if(!$lesson) 
    {
        return false;
    }

    // 일 : 0 / 월 : 1 / 화 : 2 / 수 : 3 / 목 : 4 / 금 : 5 / 토 : 6
    // 시작일 미설정 시 이번주부터 주기 시작
    if(!$startday) $startday = date('Y-m-d');

    $this_sunday = $startday;
    for($i=0;$i<8;$i++)
    {
        // 오늘부터 하루씩 증가시켜서 일요일인지 확인
        if(date('w',strtotime($this_sunday)) == 0) break;
        else $this_sunday = date('Y-m-d', strtotime('+1 day',strtotime($this_sunday)));
    }

    $freedomclass_cnt = lesson_check_freedomclass_cnt($lesson);

    if(!$freedomclass_cnt) return false;

    // 소진하지않은 총 수업갯수가 없으면 이번주가 종료일
    if($freedomclass_cnt['remain_class_total_cnt'] < 1) return $this_sunday;

    // 소진하지않은 총 수업갯수 - 소진하지않은 이번주 할당받은 수업갯수
    // 남아있는게 없으면 종료일은 이번주 그대로.
    $remain_class_total_cnt = $freedomclass_cnt['remain_class_total_cnt'] - $freedomclass_cnt['remain_week_class_cnt'];

    // 이번주기부터 시작인데 (총횟수 - 이번주) 남은 횟수가 0이라면 종료일은 이번주.
    if($startday == date('Y-m-d') && $remain_class_total_cnt < 1)
    {
        return $this_sunday;
    }

    // 남은 주 횟수
    $remain_weeks = ceil($remain_class_total_cnt / $lesson['wl_cl_number']);

    $endday = '';
    // 이번주부터 시작
    if($startday == date('Y-m-d'))
    {
        $endday = date('Y-m-d',strtotime('+'.$remain_weeks.' week',strtotime($this_sunday)));
    }
    else
    {
        $next_sunday = $this_sunday;    // this_sunday는 startday를 기준으로 구한 돌아오는 일요일
        $endday = date('Y-m-d',strtotime('+'.$remain_weeks.' week',strtotime($next_sunday)));
    }

    return $endday;
}

/**
 * 출석부 정보
 */
function lesson_info($lesson_id='', $wm_uid='', $lesson=array()) 
{
    if($lesson_id =='' && empty($lesson)) 
    {
        return false;
    }

    $CI =& get_instance();
    $CI->load->model('lesson_mdl');

    // lesson 데이터 없으면 구한다
    if(empty($lesson))
    {
        $lesson = $CI->lesson_mdl->row_wiz_lesson_by_lesson_id($lesson_id, $wm_uid);
    }

    if(!$lesson) return false;
    
    $free_sc_data = null;

    // 자유수업 출석부라면 수업횟수 관련정보 추가로 설정
    if($lesson['wl_cl_gubun'] =='2')
    {
        $free_sc_data = lesson_check_freedomclass_cnt($lesson);

        if($free_sc_data)
        {
            // 남은수업
            $lesson['wl_tt_1'] = $free_sc_data['remain_class_total_with_ready_cnt'];
        }
    }

    // 수강명칭 분수 치환
    $lesson['wl_cl_name'] = lesson_replace_cl_name_minute($lesson['wl_cl_name'], $lesson['wl_lesson_gubun']);
    // 코드형태로 되어있는 결제수단 한글명칭으로 변경
    
    $lesson['pay_method'] = payment_code_to_str($lesson['wl_payment']);
    // 출석부를 등록했는지, 등록전인지, 종료되었는지. 한글로 변환
    $lesson['lesson_regist_state'] = lesson_regist_state_to_str($lesson['wl_schedule_ok'], $lesson['wl_lesson_state']);
    // 입금상태
    $lesson['lesson_pay_state'] = lesson_pay_state_to_str($lesson['wp_pay_ok'], $lesson['wp_refund_ok']);

    //수업요일정보 처리 'Y:Y:Y:Y:Y::' 와 같은 형식으로 저장되어 있는 정보를 array(1,2,3,4,5) 와 같은 형식으로 변경해서 저장
    $lesson['@weekend'] = array();
    $week = explode(":", $lesson['wl_weekend']);
    foreach ($week as $key=>$value)
    {
        if ($value == 'Y') $lesson['@weekend'][] = $key == 6 ? 0 : ($key + 1);
    }

    return array(
        'lesson' => $lesson,             // 출석부 wiz_lesson 정보
        'free_sc_data' => $free_sc_data, // 자유수업 정보
    );

}

/**
 * 출석부 진행률
 * 
 * tt(등록 강의 수), tt_1(남은 강의 수), tt_2(출석), tt_3(결석), tt_4(취소), tt_5(휴강), tt_6(하루연기)
 * tt_7(장기연기), tt_8(Change Schedule, 현재 사용안함), tt_9(보강, 현재 사용안함), tt_add(추가 수업(무료)), cl_service (서비스교육)
 * 기본적으로 tt들의 정보는 위와 같지만 tt경우 제대로 반영되지 않은 경우가 있으므로 신뢰되지 않는 정보이다.
 * cl_service은 현재 사용하지 않는것으로 보인다.
 */
function lesson_progress_rate($lesson=array(), $lesson_id='', $wm_uid='') 
{
    if($lesson_id =='' && empty($lesson)) 
    {
        return false;
    }

    $CI =& get_instance();
    $CI->load->model('lesson_mdl');

    // lesson 데이터 없으면 구한다
    if(empty($lesson))
    {
        $lesson = $CI->lesson_mdl->row_wiz_lesson_by_lesson_id($lesson_id, $wm_uid);
    }

    $stats = [];
    
    // 유료 강의 수
    $stats['lesson_pay'] = $lesson['wl_cl_class'];
    // 무료 강의 수
    $stats['lesson_free'] = $lesson['wl_tt_add'] + $lesson['wl_cl_service'];
    // 총 수업일
    $stats['lesson_total'] = $stats['lesson_free'] + $stats['lesson_pay'];
    // 진행된 강의 수. 참석 / 배정된 수업 결석 / 미배정된 수업(자유수업) 결석 / 수업취소
    $stats['lesson_off'] = $lesson['wl_tt_2'] + $lesson['wl_tt_3'] + $lesson['wl_tt_3_1'] + $lesson['wl_tt_4'];
    // 남은 강의 수
    $stats['lesson_rest'] = $stats['lesson_total'] - $stats['lesson_off'];
    if ($stats['lesson_rest'] < 0) {
        $stats['lesson_rest'] = 0;
    }

    // 출석률
    $stats['att_rate'] = $stats['lesson_off'] == 0 ? 0:(number_format(($lesson['wl_tt_2'] / $stats['lesson_off'] ) * 100, 0));
    // 진도율
    $stats['prog_rate'] = $stats['lesson_total'] == 0 ? 0:(number_format(($stats['lesson_off'] / $stats['lesson_total'] ) * 100, 0));

    // 회원 Display 전용(임시)
    $lesson_rest_m = $lesson['wl_tt_1'];
    
    if($lesson['wl_cl_gubun'] == 2)
    {
        // 총 수업일
        $lesson_total_m = $lesson['wl_cl_class'] + $lesson['wl_tt_add'];
    }
    else
    {
        // 고정수업은 왜 스케쥴 갯수로 총수업수를 집계하는거지?? -> tt데이터가 불확실해서 그런거같다..
        $lesson_total_m = $stats['lesson_off']  + $lesson_rest_m;
    }
    
    if($lesson_total_m)
        $prog_rate_m = number_format(($stats['lesson_off']  / $lesson_total_m ) * 100, 0);
    else
        $prog_rate_m = 0;

    $stats['lesson_rest_m']  = number_format($lesson_rest_m);
    $stats['lesson_total_m'] = number_format($lesson_total_m);
    $stats['prog_rate_m']    = $prog_rate_m > 100 ? 100 : $prog_rate_m;

    return $stats;
}

/**
 * 출석부 장기연기 정보
 */
function lesson_postpone_list($wiz_member) 
{
    $CI =& get_instance();
    $CI->load->model('lesson_mdl');

    //장기연기 확인용 출석부 리스트
    $lesson = $CI->lesson_mdl->postpone_list_unfinished_wiz_lesson_by_wm_uid($wiz_member['wm_uid']);
    if(!$lesson) return false;
        
    foreach($lesson as $key=>$value)
    {
        
        // 수업이름
        $cl_name = "";
        if ($value['wl_cl_label'] != '')      $cl_name = str_replace("<", "&lt;", $value['wl_cl_label']);
        else if ($value['wl_cl_name2'] != '') $cl_name = $value['wl_cl_name2'];
        else                                  $cl_name = $value['wl_cl_name'];

        if ($value['wl_tu_uid'] == '153')
        {
            // 영어첨삭의 경우
            $cl_name = str_replace('[', '', array_shift(explode(']', $cl_name))).'</strong>';
        }
        else if ($value['wl_cl_label'] == '')
        {
            // 수강명칭 분수 치환
            $cl_name = lesson_replace_cl_name_minute($cl_name, $value['wl_lesson_gubun']);
        }
        $lesson[$key]['wl_cl_name'] = $cl_name;

        // 남은 수업 횟수
        if($lesson[$key]['wl_tt_7'] < 0) $lesson[$key]['wl_tt_7'] = 0;
        $tt   = $lesson[$key]['wl_tt_1'] + $lesson[$key]['wl_tt_2'] + $lesson[$key]['wl_tt_3'] + $lesson[$key]['wl_tt_4'] + $lesson[$key]['wl_tt_7'] + $lesson[$key]['wl_tt_9'];
        $tted = $lesson[$key]['wl_tt_2'] + $lesson[$key]['wl_tt_3'] + $lesson[$key]['wl_tt_4'];
        if($lesson[$key]['wl_tu_uid'] == "158" && $lesson[$key]['wl_tt_7'] > 0)
        {
            $lesson[$key]['tting'] = $lesson[$key]['wl_tt_7'];
        }
        else if($value['wl_cl_gubun'] =='2')
        {
            $free_sc_data = lesson_check_freedomclass_cnt($value);
            if($free_sc_data)
                $lesson[$key]['tting'] = $free_sc_data['remain_class_total_with_ready_cnt'];
            else
                $lesson[$key]['tting'] = 0;
        }
        else
        {
            $lesson[$key]['tting'] = ($tt - $tted);
        }

        //포인트 사용가능 정보 저장
        $lesson[$key]['pointUseYn'] = "Y";
        $lesson[$key]['usePoint']   = 0;

        $nowTime = time();
        $Time120 = $nowTime-(3600*120);
        $long_schedule = $CI->lesson_mdl->get_wiz_long_schedule($wiz_member['wm_uid'], $lesson[$key]['wl_lesson_id'], $Time120, $nowTime);
        if($long_schedule['long_cnt'])
        {
            $lesson[$key]['chkTimeVal'] = ($long_schedule['startTime'] + (60*60*120)) - time(); //남은시간
            $lesson[$key]['usePoint']   = $long_schedule['long_cnt'] * 5000;

            if($wiz_member['wm_point'] < $lesson[$key]['usePoint'])
            {
                $lesson[$key]['pointUseYn'] = "N";
            }
        }

        //장기연기 버튼 세팅
        //postpone['type'] = 0:연기불가 , 1:연기신청 , 2:재개신청
        $lesson[$key]['postpone'] = array('text'=>null,'type'=>0);
        if($value['wl_e_id']=="20" || $value['wl_e_id']=="24" || $value['wl_e_id']=="29" || $value['wl_cl_id']=="1404" || $value['wl_tu_uid']=="153" || preg_match("/첨삭/i",$value['wl_cl_name']))
        {
            $lesson[$key]['postpone']['text'] = '영어첨삭중';
        }
        else if($value['wl_e_id'])
        {
            $lesson[$key]['postpone']['text'] = '수업중';
        }
        else
        {
            //장기연기 스케줄 개수 체크
            $postpone_lesson = $CI->lesson_mdl->chk_postpone_lesson_cnt($value['wl_lesson_id']);

            $coupon = false;
            if($value['wl_payment'] == 'coupon:')
            {
                $CI->load->model('point_mdl');
                $coupon = $CI->point_mdl->row_class_coupon_by_cl_id($value['wl_cl_id']);

                if(!$postpone_lesson['cnt'] && $coupon['postpone_use'] == "N")
                {
                    $lesson[$key]['postpone']['text'] = '수업중';
                    $coupon = true;
                }
            }
            
            if(!$coupon)
            {
                if($postpone_lesson['cnt'] > 0)
                {
                    $lesson[$key]['postpone']['text'] = '장기연기중';
                    $lesson[$key]['postpone']['type'] = 2;
                }
                else
                {
                    $lesson[$key]['postpone']['text'] = '수업중';
                    if($wiz_member['wm_uid'] != $value['wl_uid'])
                        $lesson[$key]['postpone']['type'] = 0;
                    else if($wiz_member['wd_long_postpone_yn'] != 'N')
                        $lesson[$key]['postpone']['type'] = 1;
                }
            }
        }
    }

    return $lesson;
}


/*
    수업선호 방식 문자열
*/
function lesson_prefer_type_text()
{  
    $text = [
        'greeting' => [
            'Y' => '- I\'d like to say hello!', //안부 인사를 하고싶어요!
            'N' => '- Please skip the greeting and proceed to the class right away',    //안부 인사는 생략하고 바로 수업 진행해주세요
        ],
        'speed_slowly' => [
            'Y' => '- Please talk slowly',  //천천히 대화해주세요 
            'N' => '- Please talk normal speed',    //보통 빠르기로 대화해주세요
        ],
        'focus_book' => [
            'Y' => '- Please focus on the progress of the class rather than the conversation.', //대화 보다는 수업진도에 집중해주세요
            'N' => '- Please focus on the conversation rather than the class.', //수업진도 보다는 대화에 집중해주세요
        ],
        'feedback_inclass' => [
            'Y' => '- If there is anything wrong with me, please give me feedback right away during class.',    //제가 틀린부분이 있다면 수업 중 바로 피드백 주세요
            'N' => '- If there is anything wrong with me, please give me feedback after class.',    //제가 틀린부분이 있다면 수업 후 피드백 주세요
        ],
    ];

    return $text;
}

/*
	네오텍으로 결제한 이력 있는 지 확인
	wiz_pay에서 pay_name으로 Video 문자 체크한다.
	내역이 없으면 결제페이지에서 네오텍 화상은 보여주지 않는다.
	수업변경도 네오텍으로 불가
*/
function lesson_check_exist_neoteck_pay($uid)
{
    //비로그인은 false
	if(!$uid) return false;

	$CI =& get_instance();
    $CI->load->model('lesson_mdl');
    $check_exist = $CI->lesson_mdl->check_exist_neoteck_pay($uid);

	return $check_exist ? true:false;
}

/**
 * 개근상 처리
 */
function set_give_all_clear_point($wiz_member, $lesson_id)
{
    if($lesson_id == '') 
    {
        return array("msg"=>"존재하지 않는 수강정보입니다.");
    }

    $CI =& get_instance();
    $CI->load->model('lesson_mdl');

    // lesson 데이터 구하기
    $lesson = $CI->lesson_mdl->row_wiz_lesson_by_lesson_id($lesson_id, $wiz_member['wm_uid']);

    if (!$lesson || $lesson['wl_uid'] != $wiz_member['wm_uid']) return array("msg"=>"존재하지 않는 수강정보입니다.");

    $tt = $lesson['wl_tt_1'] + $lesson['wl_tt_2'] + $lesson['wl_tt_3'] + $lesson['wl_tt_3_1'] + $lesson['wl_tt_4'] + $lesson['wl_tt_9'];
    $tted = $lesson['wl_tt_2'];
    if ($tted / $tt * 100 != "100") return array("msg"=>"출석률이 100.0%가 아닙니다.");

    if ($lesson['wl_tt_3'] != "0" || $lesson['wl_tt_3_1'] != "0") return array("msg"=>"결석한 수업이 있어서 개근상을 받을 수 없습니다.");
    if ($lesson['wl_tt_4'] != "0")                                return array("msg"=>"취소한 수업이 있어서 개근상을 받을 수 없습니다.");
    if ($lesson['wl_tt_6'] != "0")                                return array("msg"=>"수업 하루연기를 사용한 적이 있어서 개근상을 받을 수 없습니다.");
    if ($lesson['wl_tt_holding_count'] != "0")                    return array("msg"=>"장기연기를 사용한 적이 있어서 개근상을 받을 수 없습니다.");
    if ($lesson['wl_tt_7'] != "0")                                return array("msg"=>"장기연기중인 스케줄로 인해 수업이 종료되지 않아 개근상을 받을 수 없습니다.");
    if ($lesson['wl_endday'] == date('Y-m-d'))                    return array("msg"=>"수업종료일 당일에는 개근상을 수령할 수 없습니다.");
    if ($wiz_member['wd_point_addclass_yn'] !='Y')                return array("msg"=>"개근상을 받을 수 없는 대상입니다.");

    $is_take_point = $CI->lesson_mdl->is_take_all_clear_point($lesson_id);
    if ($is_take_point['cnt'] > 0) return array("msg"=>"이미 개근상 포인트를 수령하셨습니다.");

    //수령받을 포인트 계산 및 개근상 수여 처리
    /*
    10분 - 5000 포인트 / 15~20분 - 10000포인트 / 25~30분 15000 포인트
    예) 주2회 20분 3개월 -> 2x10000x3 = 60,000

    관리자사이트 -> 멤버리스트 아이디 검색
    -> 종료된강의&결제내역 (추가 나의 출석부 확인)
    예) 님 개근상이벤트로 1/4수업에 해당하는 60000포인트 적립예정
    */
    if ($lesson['wl_cl_time'] == 10)                                    $default_point = 10 * 500;
    else if ($lesson['wl_cl_time'] > 10 && $lesson['wl_cl_time'] <= 20) $default_point = 20 * 500;
    else if ($lesson['wl_cl_time'] > 20 && $lesson['wl_cl_time'] <= 30) $default_point = 30 * 500;
    else                                                                $default_point = $lesson['wl_cl_time'] * 500;

    $gift_point = $default_point * (int)($lesson['wl_cl_class'] / 4);	// 수업수의 4분의 1(소숫점 버림)만큼 서비스로 제공

    /* 포인트 내역 입력 및 포인트 추가 */
    $point = array(
        'uid'     => $wiz_member['wm_uid'],
        'name'    => $wiz_member['wm_name'],
        'point'   => $gift_point,
        'pt_name' => '개근상 이벤트로 1/4수업에 해당하는 '.number_format($gift_point).'포인트 적립예정', 
        'kind'    => 'k', 
        'regdate' => date("Y-m-d H:i:s")
    );
    $CI->load->model('point_mdl');
    $CI->point_mdl->set_wiz_point($point);

    $params = array(
        'lesson_id' => $lesson_id,
        'point'     => $gift_point,
        'regdate'   => date("Y-m-d H:i:s")
    );
    $CI->lesson_mdl->insert_wiz_lesson_allclear($params);

    return array("msg"=>"개근상으로 ".number_format($gift_point)." 포인트를 수령했습니다.");
}

/**
 * 자동재수강 시 미래날짜 스케쥴 비엇는지 확인 및 스케쥴 일정 구성
 */
function lesson_check_retake_lesson_isEmpty_schedule($relec_lesson, $selected_month='')
{
    $CI =& get_instance();
    $CI->load->model('lesson_mdl');
    $CI->load->model('goods_mdl');
    $CI->load->model('holiday_mdl');
    
    $w = explode(":",$relec_lesson['wl_weekend']);
    $stime2 = date("H:i",$relec_lesson['wl_stime']);

    $TM = explode(":",$stime2);
    $ymd = $relec_lesson['wl_endday'];
    $list = $CI->goods_mdl->list_mint_goods_regular();

    $goods_list = array();
    //상품리스트 루프 돌려 정리
    foreach($list as $row)
    {
        //첨삭
        if($row['mg_g_id'] =='56')
        {
            $row['mg_l_gubun'] = 'W';
            $goods_list[$row['mg_l_gubun']] = $row;
        }
        //정규수업상품
        else
        {
            $goods_list[$row['mg_l_gubun']][$row['mg_l_month']][$row['mg_l_time']][$row['mg_l_timeS']] = $row;
        }
    }

    if($TM[0] == "00" || $TM[0]=="01") 
    { 
        $hday = "2";
        $START =  mktime($TM[0],$TM[1],$TM[2],substr($ymd,5,2),substr($ymd,8,2),substr($ymd,0,4)) + 172800;
    }
    else 
    {
        $hday = "1"; 
        $START =  mktime($TM[0],$TM[1],$TM[2],substr($ymd,5,2),substr($ymd,8,2),substr($ymd,0,4)) + 86400;		
    }

    $class_time = $relec_lesson['wl_cl_time'] * 60 -1;
    $classCnt = [];
    $classEndDay = [];
    $re_pay_info = [];

    $alreay_exists = 0;
    $arrayMonth = $selected_month ? array($selected_month):array(1,3,6,12);
    for($im=0;$im<count($arrayMonth);$im++){
        if(strpos($relec_lesson['wl_cl_name'], '영어첨삭') === false)
        {
            $re_pay_info[$arrayMonth[$im]] = $goods_list[$relec_lesson['wl_lesson_gubun']][$arrayMonth[$im]][$relec_lesson['wl_cl_time']][$relec_lesson['wl_cl_number']];
            $break_class_Count = $goods_list[$relec_lesson['wl_lesson_gubun']][$arrayMonth[$im]][$relec_lesson['wl_cl_time']][$relec_lesson['wl_cl_number']]['mg_l_class'];
        }
        else
        {
            $re_pay_info[$arrayMonth[$im]] = $goods_list['W'];
            $break_class_Count = $goods_list['W']['mg_l_class'];
        }

        //선택한 수업개월이 이전수업의 개월수가 똑같다면 현재금액이 아닌 예전에 결제한 금액으로 재결제가 가능하다
        if($relec_lesson['wl_cl_month'] == $arrayMonth[$im])
        {
            $re_pay_info[$arrayMonth[$im]]['mg_price'] = $relec_lesson['wl_pay_sum'];
        }

        $check_start_date = [];
        $i = $START;
        $j = 0;
        $check = 0;
        $CLASS_START = [];
        $CLASS_END = [];
        $STIME = [];
        $WEEKEND = []; 
        while(1) {
            $wk = date("w",$i);
            $reg_ok = false;
            $DATE = date("Y-m-d",$i);
            $datestart = date("Y-m-d H:i:s",$i);
            $dateend = date("Y-m-d H:i:s",$i+$class_time);
            //0시,1시 수업은 이전날의 수업으로 간주한다.EX) 화요일 0시 30분수업-> 월요일수업이다
            if($TM[0]=="00" || $TM[0]=="01") {
                if($wk=="2" && $w[0]=="Y") $reg_ok=true;
                if($wk=="3" && $w[1]=="Y") $reg_ok=true;
                if($wk=="4" && $w[2]=="Y") $reg_ok=true;
                if($wk=="5" && $w[3]=="Y") $reg_ok=true;
                if($wk=="6" && $w[4]=="Y") $reg_ok=true;
                if($wk=="0" && $w[5]=="Y") $reg_ok=true;
                if($wk=="1" && $w[6]=="Y") $reg_ok=true;
            } else {
                if($wk=="1" && $w[0]=="Y") $reg_ok=true;
                if($wk=="2" && $w[1]=="Y") $reg_ok=true;
                if($wk=="3" && $w[2]=="Y") $reg_ok=true;
                if($wk=="4" && $w[3]=="Y") $reg_ok=true;
                if($wk=="5" && $w[4]=="Y") $reg_ok=true;
                if($wk=="6" && $w[5]=="Y") $reg_ok=true;
                if($wk=="0" && $w[6]=="Y") $reg_ok=true;
            }
        
            $cnt = $CI->holiday_mdl->count_holiday($DATE);
            $cnt = $cnt['cnt'];
            
            if($j == "$i"){ 
                $reg_ok = false;
            }
            if($reg_ok==true && $cnt < $hday) { // 휴일처리
                if($relec_lesson['wl_cl_time'] < 70){ 
                    $cl_s = 60;
                }else{
                    $cl_s = $relec_lesson['wl_cl_time'];
                } 
                if($relec_lesson['wl_cl_time'] < 10){
                    $clT = $relec_lesson['wl_cl_time'];
                }else{
                    $clT = 10;
                }
                for($s=-$cl_s;$s<$relec_lesson['wl_cl_time'];$s+=10) {
                    $start = date("Y-m-d H:i:s",$i + $s*60);
                    $ct = -($s);
                    
                    $where = "startday = '".$start."'";
                    if($ct > 0){
                        $where .= " AND cl_time > ".$ct;
                    }else{
                        $where .= " AND cl_time >= ".$clT;
                    }
                    //모아서 아래에서 한번에 쿼리 조회
                    $check_start_date[] = $where;
                }
                
                $CLASS_START[] = $datestart;
                $CLASS_END[] = $dateend;
                $STIME[] = $i;
                $WEEKEND[] = date("w",$i); 
                $check++;

                if($check>=$break_class_Count) break;
                $j++;
            }
            if($hday=="2") { 
                if($cnt == 1){ 
                    $j= $i+86400;
                }else{ 
                    $j = $i;
                }
            }
            $i+=86400;
        }

        $classCnt[$arrayMonth[$im]] = $break_class_Count;
        $classEndDay[$arrayMonth[$im]] = substr($dateend,0,10);

        $re_pay_info[$arrayMonth[$im]]['expect_endday'] = substr($dateend,0,10);

        if(strpos($relec_lesson['wl_cl_name'], '첨삭') !== false)
        {
            break;
        }
    }

    if(strpos($relec_lesson['wl_cl_name'], '첨삭') === false)
    {
        $check_start_date = array_unique($check_start_date);
        $where = " WHERE tu_uid = ".$relec_lesson['wl_tu_uid']." AND present NOT IN(7,8) AND ( (".implode(') OR (',$check_start_date).') )';
        $sche_ret = $CI->lesson_mdl->list_count_schedule('USE INDEX(daykey1)',$where);

        //등록된 수업이 있으면 불가
        if($sche_ret['cnt'] > 0) {
            $alreay_exists = 1;
        }
    }

    $edu_week = [];
    if($w[0]=="Y") $edu_week[] = "월";
    if($w[1]=="Y") $edu_week[] = "화";
    if($w[2]=="Y") $edu_week[] = "수";
    if($w[3]=="Y") $edu_week[] = "목";
    if($w[4]=="Y") $edu_week[] = "금";
    if($w[5]=="Y") $edu_week[] = "토";
    if($w[6]=="Y") $edu_week[] = "일";

    $retakeLessonInfo = [
        'tu_name'       => $relec_lesson['wl_tu_name'],
        'cl_month'      => $relec_lesson['wl_cl_month'],
        'cl_number'     => $relec_lesson['wl_cl_number'],
        'startday'      => substr($CLASS_START[0],0,10),
        're_pay_info'   => $re_pay_info,
        'edu_week'      => implode('/',$edu_week),
        'edu_time1'     => lesson_replace_cl_name_minute($relec_lesson['wl_cl_time'], $relec_lesson['wl_lesson_gubun'], true),
        'edu_time2'     => substr($CLASS_START[0],11,5).'~'.substr($CLASS_END[0],11,5),
    ];

    if(strpos($relec_lesson['wl_cl_name'], '영어첨삭') !== false) $retakeLessonInfo['edu_time1'] = '1일 1회';

    return array(
        'state'             => $alreay_exists ? 0:1, 
        'retakeLessonInfo'  => $retakeLessonInfo, 
        'CLASS_START'       => $CLASS_START,
        'CLASS_END'         => $CLASS_END,
        'WEEKEND'           => $WEEKEND,
        'STARTTIME'           => $START,
    );
}


/**
 * set Continue처리
 */
function lesson_set_conti($uid)
{
    $CI =& get_instance();
    $CI->load->model('lesson_mdl');
    $CI->load->model('member_mdl');

    $lessons = $CI->lesson_mdl->all_wiz_lesson_schedule_ok_by_uid($uid);

    if($lessons)
    {
        foreach($lessons as $key=>$le)
        {
            $CI->lesson_mdl->update_wiz_lesson($le['lesson_id'], [
                'conti' => $key + 1
            ]);
        }

        $CI->member_mdl->update_member(['wiz_conti' => count($lessons)], $lessons[0]['wiz_id']);
    }

}


/**
 * 자동재수강 스케쥴 삽입
 */
function lesson_insert_schedule_retake($relec_lesson, $check_retake, $prepay, $wiz_member, $lesson_id, $update_point='N')
{
    $CI =& get_instance();
    $CI->load->model('lesson_mdl');
    $CI->load->model('goods_mdl');
    $CI->load->model('book_mdl');

    $CLASS_START = $check_retake['CLASS_START'];
    $CLASS_END = $check_retake['CLASS_END'];
    $WEEKEND = $check_retake['WEEKEND'];

    for($z=0;$z<$prepay['cl_class'];$z++) 
    {
        $CI->lesson_mdl->insert_wiz_schedule([
            'lesson_id'     => $lesson_id,
            'lesson_gubun'  => $relec_lesson['wl_lesson_gubun'],
            'uid'           => $relec_lesson['wl_uid'],
            'wiz_id'        => $relec_lesson['wl_wiz_id'],
            'name'          => $relec_lesson['wl_name'],
            'tu_uid'        => $relec_lesson['wl_tu_uid'],
            'tu_name'       => $relec_lesson['wl_tu_name'],
            'present'       => 1,
            'startday'      => $CLASS_START[$z],
            'endday'        => $CLASS_END[$z],
            'weekend'       => $WEEKEND[$z],
            'cl_time'       => $relec_lesson['wl_cl_time'],
            'tel'           => $prepay['tel'],
            'mobile'        => $prepay['mobile'],
            'cl_number'     => $relec_lesson['wl_cl_number'],
        ]);
    }

    $new_lesson_startday = substr($CLASS_START[0],0,10);
    $new_lesson_endday = substr($CLASS_START[count($CLASS_START)-1],0,10);

    $CI->lesson_mdl->update_wiz_lesson($lesson_id, [
        'weekend'       => $relec_lesson['wl_weekend'],
        'tt'            => $prepay['cl_class'],
        'tt_1'          => $prepay['cl_class'],
        'startday'      => $new_lesson_startday,
        'endday'        => $new_lesson_endday,
        'stime'         => $check_retake['STARTTIME'][0] ? $check_retake['STARTTIME'][0]:0,
        'stime2'        => $check_retake['STARTTIME'][0] ? date('H:i',$check_retake['STARTTIME'][0]):'',
        'schedule_ok'   => 'Y',
        'book_id'       => $relec_lesson['wl_book_id'],
        'book_name'     => $relec_lesson['wl_book_name'],
        'lesson_state'  => 'in class',
        'plandate'      => date("Y-m-d H:i:s")
    ]);

    //구민트는 시작일 휴일체크하여 처리했으나 이곳에 진입 전 CLASS_START 구성할때 미리 휴일인지 체크하니 해당 로직은 빼도될듯하여 일단 제외

    //교재로그 등록
    $CI->book_mdl->insert_wiz_bookhistory([
        'lesson_id' => $lesson_id,
        'book_id'   => $relec_lesson['wl_book_id'],
        'book_name' => $relec_lesson['wl_book_name'],
        'book_date' => date("Y-m-d H:i:s"),
        'man_id'    => '',
        'man_name'  => 'student renew',
        'regdate'   => date("Y-m-d H:i:s")
    ]);

    //Continue처리
    lesson_set_conti($prepay['uid']);

    //수업등록 로그남기기
    $queryParam = array(
        'lesson_id' => $lesson_id,
        'a_tuid'    => $relec_lesson['wl_uid'],
        'b_tuid'    => $relec_lesson['wl_uid'],
        'a_tutor'   => $relec_lesson['wl_tu_name'],
        'b_tutor'   => $relec_lesson['wl_tu_name'],
        'a_time'    => date("H:i:s",$relec_lesson['wl_stime']),
        'b_time'    => date("H:i:s",$relec_lesson['wl_stime']),
        'cl_time'   => $relec_lesson['wl_cl_time'],
        'startday'  => $new_lesson_startday,
        'endday'    => $new_lesson_endday,
        'man_id'    => $wiz_member['wm_wiz_id'],
        'man_name'  => $wiz_member['wm_name'],
        'regdate'   => date("Y-m-d H:i:s"),
        'kind'      => 'r',
        'class_su'  => $prepay['cl_class'],
    );
    $content = '자동재수강 수업등록';
    $CI->lesson_mdl->insert_wiz_tutor_change($queryParam,$content);

    // 결제되었으니 payment_order_progress 에서 사용안함 n으로 insert 해놓은것을 y로 업뎃
    if($update_point == 'Y')
    {
        $point_param = [
            'showYn' => 'y'
        ];
        $point_where = [
            'uid'        => $prepay['uid'],
            'lesson_id'  => $prepay['goods_id'],    //이전출석부ID
        ];
        $CI->point_mdl->update_wiz_point($point_param, $point_where);

        $in_param = [
            'in_yn' => 'y'
        ];
        $in_where = [
            'uid'        => $prepay['uid'],
            'lesson_id'  => $prepay['lesson_id'],  //새로 생성된 출석부ID
            'in_kind'    => '1',
        ];
        
        $CI->tutor_mdl->update_tutor_incentive($in_param, $in_where);
    }
}

//출석부 시작일, 종료일 재계산. 벼락, 하루변경 같은 기능으로 스케쥴 변동이 생겼을때 호출
function lesson_resetting_endday($lesson_id, $wm_uid, $lesson=array())
{
    $CI =& get_instance();
    $CI->load->model('lesson_mdl');

    // lesson 데이터 없으면 구한다
    if(empty($lesson))
    {
        $lesson = $CI->lesson_mdl->row_wiz_lesson_by_lesson_id($lesson_id, $wm_uid);
    }

    //수강정보의 시작일과 종료일 재계산
    if($lesson['cl_gubun'] == '2')
    {
        $startday = $lesson['wl_startday'];	// 그대로
        $endday = lesson_check_freedomclass_endday($lesson);
    }
    else
    {
        $seday = $CI->lesson_mdl->get_lesson_start_end_day($lesson_id);
        $startday = $seday['startday'];
        $endday = $seday['endday'];
    }

    $param = [
        'startday' => $startday,
        'endday'   => $endday,
    ];

    $CI->lesson_mdl->update_wiz_lesson($lesson_id, $param);

    return $param;

}


//wiz_lesson tt 정보 재설정
function lesson_resetting_tt($lesson_id, $cl_gubun, $free_sc_data)
{
    $CI =& get_instance();
    $CI->load->model('lesson_mdl');

   //수업일수 체크 및 업뎃
   $TT = $CI->lesson_mdl->checked_tt_by_lesson_id($lesson_id);
        
   if($TT)
   {
       if($cl_gubun =='2')
       {
           $TT['tt1'] = $free_sc_data['remain_class_total_with_ready_cnt'];
       }

       $wiz_lesson_update_param = [
           'tt_1' => $TT['tt1'],
           'tt_2' => $TT['tt2'],
           'tt_3' => $TT['tt3'],
           'tt_4' => $TT['tt4'],
           'tt_5' => $TT['tt5'],
           'tt_6' => $TT['tt6'],
           'tt_7' => $TT['tt7'],
           'tt_8' => $TT['tt8'],
       ];

       //시작일, 종료일
       if($cl_gubun =='1')
       {
           $check = $CI->lesson_mdl->get_lesson_start_end_day($lesson_id);

           $wiz_lesson_update_param['startday'] = $check['startday'];
           $wiz_lesson_update_param['endday'] = $check['endday'];
       }

       $CI->lesson_mdl->update_wiz_lesson($lesson_id, $wiz_lesson_update_param);
   }

}

/*
    자동재수강처럼 현재출석부 끝나고 바로 동일시간,동일강사로 연결된 다음출석부가 존재할수 있다.
    이경우 찾아서 현재 출석부 종료일이 땡겨졌으면, 자동재수강으로 생성된 다음출석부도 땡겨진 날짜만큼 땡겨줘야한다.
    미뤄진경우도 미뤄진만큼 다음출석부도 미뤄줘야한다.

    자동재수강으로 연결된 수업은 일반적으로, 현재수업의 마지막수업의 바로 다음타임이 자동재수강 출석부의 시작일이다.
    ( Ex) 주2회수업인경우 화요일에 현수업이 끝나면, 목요일에 자동재수강 첫 수업이 붙는다.)
    그래서 현재수업의 마지막 수업일자가 변동되면, 자동재수강 출석부의 시작일도 변동시켜줘야한다.
*/
function lesson_rescheduling_next_lesson($lesson_id, $wm_uid, $lesson_info=array(), $except_search_lesson_id=array())
{
    $CI =& get_instance();
    $CI->load->model('lesson_mdl');
    
    $admin = base_get_login_admin_id();

    // lesson 데이터 없으면 구한다
    if(empty($lesson_info))
    {
        $lesson_info = lesson_info($lesson_id, $wm_uid);
    }

    $free_sc_data = $lesson_info['free_sc_data'];
    $lesson = $lesson_info['lesson'];

    if(!$lesson || $lesson['wl_cl_gubun'] =='2') return;

    //현재 스케쥴 중복 정리
    schedule_refresh_overlap_class($lesson_id, $lesson['wl_weekend'], $lesson['wl_startday']);
    //현재수업 tt,startday,endday 정리
    lesson_resetting_tt($lesson_id, $lesson['wl_cl_gubun'], $free_sc_data);

    //정리 후 데이터 다시 가져오기
    $lesson = $CI->lesson_mdl->row_wiz_lesson_by_lesson_id($lesson_id, $wm_uid);

    /*
        자동재수강으로 연결된 동일시간, 동일강사의 다른 출석부가 있는지 확인한다.
        현재수업일의 종료일 이후에 동일시간, 동일강사로 잡혀진 가장빠른 다음수업을 구한다.
        자동재수강으로 직접적으로 연결되지 않았다하더라도, 위 조건에 맞으면 스케쥴 조정대상이다.
    */
    $except_search_lesson_id[] = $lesson_id;    //다음수업검색 시 제외할 출석부id
    $next_lesson = $CI->lesson_mdl->find_next_auto_retake_lesson($except_search_lesson_id, $wm_uid, $lesson['wl_tu_uid'], date('Y-m-d 00:00:00'), $lesson['wl_stime2']);

    if(!$next_lesson) return;
    $next_lesson_id = $next_lesson['lesson_id'];

    //이 구간에 구민트에는 휴일처리가 있었는데 필요하나?????
    //schedule_holiday < 휴일 처리 함수

    //다음수업정보 구하기
    $next_lesson_info = lesson_info($next_lesson_id, $wm_uid);
    $next_free_sc_data = $next_lesson_info['free_sc_data'];
    $next_lesson = $next_lesson_info['lesson'];

    /*
        다음출석부 날짜 전부 재설정. 
        현재출석부의 마지막 수업의 다음수업시간이 다음출석부의 첫 수업인지 체크한다.
        같으면 재설정할필요없음.
    */
    $next_start_date = schedule_find_next_class_time(date('Y-m-d', strtotime('+1 day', strtotime($lesson['wl_endday']))), $next_lesson['wl_weekend'], $next_lesson['wl_tu_uid']).' '.$next_lesson['wl_stime2'].':00';
    $next_end_date = date('Y-m-d H:i:s', strtotime('+'.$next_lesson['wl_cl_time'].' minutes',strtotime($next_start_date)) -1);

    $first_class = $CI->lesson_mdl->row_first_class($next_lesson_id);

    //현재출석부의 마지막 수업의 다음수업시간이 저장된 다음출석부의 첫 수업값이 다르면 밀리거나, 땡겨진것이므로 DB스케쥴 재조정해준다.
    if($first_class['ws_startday'] != $next_start_date)
    {
        $next_startday = $next_start_date;
        $where = " WHERE ws.lesson_id=".$next_lesson_id." AND ws.present=1 AND ws.kind='n'";
        $order = " ORDER BY ws.startday ASC "; 
        $limit = "";
        $next_sc_list = $CI->lesson_mdl->list_schedule('',$where, $order, $limit);

        if($next_sc_list)
        {
            foreach($next_sc_list as $key=>$val)
            {
                $CI->lesson_mdl->update_wiz_schedule($val['ws_sc_id'],[
                    'startday'      => $next_start_date,
                    'endday'        => $next_end_date,
                    'weekend'       => date('w', strtotime($next_start_date)),
                ]);

                //수업변경기록
                $CI->lesson_mdl->insert_wiz_tutor_change([
                    'lesson_id' => $next_lesson_id,
                    'a_tuid'    => $next_lesson['wl_tu_uid'],
                    'b_tuid'    => $next_lesson['wl_tu_uid'],
                    'a_tutor'   => $next_lesson['wl_tu_name'],
                    'b_tutor'   => $next_lesson['wl_tu_name'],
                    'a_time'    => substr($val['ws_startday'],11),
                    'b_time'    => $next_lesson['wl_stime2'].':00',
                    'cl_time'   => $next_lesson['wl_cl_time'],
                    'startday'  => substr($next_startday,0,11),
                    'endday'    => substr($next_start_date,0,11),
                    'a_date'    => substr($val['ws_startday'],0,11),
                    'b_date'    => substr($next_start_date,0,11),
                    'man_id'    => $admin ? $admin:'',
                    'man_name'  => '',
                    'regdate'   => date('Y-m-d H:i:s'),
                    'kind'      => 'x',
                ], '앞수업의 스케쥴이 달라 뒷수업 스케쥴 자동조정(API)');
                
                $next_start_date = schedule_find_next_class_time(date('Y-m-d', strtotime('+1 day', strtotime($next_start_date))), $next_lesson['wl_weekend'], $next_lesson['wl_tu_uid']).' '.$next_lesson['wl_stime2'].':00';
                $next_end_date = date('Y-m-d H:i:s', strtotime('+'.$next_lesson['wl_cl_time'].' minutes',strtotime($next_start_date)) -1);
            }
        }
    }

    //다음수업 tt,startday,endday 정리
    lesson_resetting_tt($next_lesson_id, $next_lesson['wl_cl_gubun'], $next_free_sc_data);
    
    //다다음 자동재수강상품있으면 진행.(보통없다)
    lesson_rescheduling_next_lesson($next_lesson_id, $wm_uid, $next_lesson_info, $except_search_lesson_id);
}

//수업이 연강인 경우 마지막 수업의 종료시간을 찾는다.
function lesson_find_enddatetime($schedule)
{
    $CI =& get_instance();
    $CI->load->model('lesson_mdl');

    $next_end_date = date('Y-m-d H:i:s', strtotime($schedule['ws_endday']) +1);

    //다음시간 같은 강사와 수업이 있는지 확인
    $next_schedule = $CI->lesson_mdl->row_schedule_by_startday($schedule['wm_uid'], $schedule['ws_lesson_id'], $next_end_date);

    if(!$next_schedule) return $schedule['ws_endday'];
    else return lesson_find_enddatetime($next_schedule);
}