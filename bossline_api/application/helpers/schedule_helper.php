<?php
defined("BASEPATH") OR exit("No direct script access allowed");


/*
    고정 출석부 연장시 강사 스케쥴 확인
    - 강사 다음 스케쥴 확인 및 휴일 체크
    - lesson : 연장할 출석부
    - number_of_classes_to_convert : 추가할 횟수
*/
function schedule_tutor_check_regular_class_extension($lesson, $number_of_classes_to_convert)
{
    if(!$lesson || !$number_of_classes_to_convert) return NULL;

    $CI =& get_instance();
    $CI->load->model('point_mdl');
    

    /* 
        1. 날짜 구하기 
        - 수업을 추가하려는 날짜

        2. 해당 날짜의 회사휴일, 강사휴일, 브레이크타임 체크
        - 해당 날짜에 휴일 및 브레이크 타임이 포함되어 있다면 해당 날짜 제외하고 횟수만큼 날짜를 더구함

        3. 구한날짜를 가지고 강사 스케쥴 체크
         
        출석부 종료일
        - endday
        - 수업이 자정이후 시작하는 경우 다음 날짜가 기록됨. 마지막수업이 금요일 24:00에 시작한다면 토요일 날짜가 기록됨

        수업 시작시간 
        -stime

        수업시간
        - cl_titme

        주 수업횟수 
        - cl_number

        정규수업 (고정수업)
        - 주2회 : 화,목
        - 주3회 : 월,수,금
        - 주5회 : 월,화,수,목,금 
    */
    if($lesson['cl_number'] == "2")
    {
        $day_of_class = "2,4";
    }
    else if($lesson['cl_number'] == "3")
    {
        $day_of_class = "1,3,5";
    }
    else if($lesson['cl_number'] == "5")
    {
        $day_of_class = "1,2,3,4,5";
    }


}

/*
    지정한 날짜에 해당 강사 수업 잡을 수 있는지 시간대 별로 배열을 구성
    현재 MSET강사 위주로 체크하여 개발.

    $allow_time: 특정타임(분)만 스케쥴 잡을 수 있도록 허용
    ex) MSET은 모바일은 0분, 30분, 화상은 0분에만 스케쥴 잡을 수 있다. -> 현재는 둘다 0,30분에 잡을수있다
*/
function schedule_tutor_timeline($tu_uid, $date, $cl_time, $check_mode='class', $allow_time=null)
{
    $CI =& get_instance();
    $CI->load->model('lesson_mdl');
    $CI->load->model('tutor_mdl');

    // 해당 강사 해당일에 결근인지 확인
    $absent = $CI->tutor_mdl->check_tutor_absent_date($tu_uid, $date);
    if($absent) return ['state' => false, 'msg'=> $date.' 에는 강사를 선택할 수 없습니다.'];

    // 특정일이 강사 휴일인지 확인
    $break = $CI->tutor_mdl->check_tutor_blockdate_day($tu_uid, $date);
    if($break) return ['state' => false, 'msg'=> $date.' 는 강사가 휴무입니다.'];

    // 강사정보 확인
    $tutor = $CI->tutor_mdl->get_tutor_info_by_tu_uid($tu_uid);

    // mset강사는 sche_view가 N이지만 mset스케쥴 체크로 들어오면 통과시켜줘야한다.
    if(!$tutor || $tutor['del_yn'] !='n' || (($check_mode =='class' || $check_mode =='extend') && $tutor['sche_view'] !='Y' )) return ['state' => false, 'msg'=> '잘못된 강사정보입니다.'];

    /* 근무하는 시간대 추출
       해당일 부터 다음날 0시까지 구한다.

       시간대 추출 후 특정시간에 분단위 브레이크건 것은 없는지 체크해야한다.
        -특정 분 영구 브레이크
        -특정 분 특정일만 브레이크 
        두 종류가 있다.

        오늘날짜인 경우 앞으로 한시간 이후부터만 신청이 가능하다

        --참고--
        wiz_tutor_weekend
        스케쥴 잡을 수 있는 오픈된 기본 '시간' 저장

        분수가 비어있는경우는
        wiz_tutor_breakingtime 테이블에 브레이크 할 '분'이 저장된다.
        alldays테이블에 5,6,7,8,9,2,3,4가 지정된다(월화수목금토일)
        4는 공휴일인데 쓸모없는거같음. 용도불명
        0으로 지정되면 특정일만 임시 브레이크

        wiz_tutor 테이블에 t0, t1, t2...필드는 안쓰는거같다.
        시간설정하면 tt에만 반영된다.
    */

    $date_after_day = date('Y-m-d',strtotime('+1 day', strtotime($date)));  // 특정일의 다음날 
    $date_after_day_w = date('w',strtotime('+1 day', strtotime($date)));
    if($check_mode =='extend')
    {
        $limit_date = date('Y-m-d H:i');
    }
    else
    {
        $limit_date = date('Y-m-d H:i', strtotime('+1 hour'));  // MSET의 경우 한시간 이후 시간 부터 신청 가능
    }
    
    //브레이크 데이터 가져오기
    $break_data = tutor_breaking_time($tu_uid, $date, $date_after_day);

    //해당날짜 스케쥴 데이터 가져오기
    $schedule = $CI->lesson_mdl->list_tutor_schedule_by_date($tu_uid, $date.' 00:00:00', $date_after_day.' 01:00:00');

    //잡혀있는 스케쥴 정리
    $schedule_data = [];
    if($schedule)
    {
        foreach($schedule as $row)
        {
            $schedule_data[$row['ws_startday']] = $row['ws_endday'];
        }
    }
    
    // 기본근무시간 데이터
    $working_hour = $CI->tutor_mdl->check_tutor_working_hour($tu_uid, date('w',strtotime($date)), $date_after_day_w);

    $date_w = date('w', strtotime($date));
    $minute = ['00','10','20','30','40','50'];

    $timeline = []; // 배열 1차: 시간, 2차: 분.  값 1:스케쥴설정가능, 0:스케쥴설정불가
    
    // 시간, 분 별로 스케쥴 잡을수있는지 체크
    foreach($working_hour as $hour=>$val)
    {
        $hour = str_replace('t','',$hour);
        $hour = sprintf('%02d',$hour);  // 10보다 작은 수 앞에 0붙여주기.

        //24시는 다음날 0시로 세팅
        $loop_date = $hour =='24' ? $date_after_day:$date;
        $loop_hour = $hour =='24' ? '00':$hour;
        if($hour == '24') $date_w+=1;
        if($date_w ==7) $date_w = 0;

        // 분단위 스케쥴 설정 가능한지 체크
        foreach($minute as $min)
        {
            $timeline[$hour][$min] = 0; // 기본값 세팅. 1로 변해야만 스케쥴을 잡을 수 있는 상태이다.

            //현재 루프 시간 Y-m-d H:i
            $this_datetime = $loop_date.' '.$loop_hour.':'.$min;

            //limit_date특정시간을 지났으면 스케쥴 못잡는다
            if($val == 'Y' && $this_datetime > $limit_date)
            {
                $his = $loop_hour.':'.$min.':00';
                // 체크할 시각. 시작일
                $loop_date_ymdhis = $loop_date.' '.$his;    
                // 체크할 시각+cl_time. 종료일. 잡혀있는 스케쥴이 이것과 겹치는지 체크하는 용도
                $loop_date_ymdhis_after_cl_time = date('Y-m-d H:i:s', strtotime('+'.$cl_time.' minute -1 second', strtotime($loop_date_ymdhis)));
                $expect_end_hour = date('H', strtotime('+'.$cl_time.' minute -1 second', strtotime($loop_date_ymdhis)));

                //해당시간에 근무해도, 다음시간에 근무하지않는 경우. 예를들어 60분짜리를 30분에 잡는경우는 제외시켜야한다.
                if($expect_end_hour != $loop_hour && array_key_exists('t'.($hour+1),$working_hour) && $working_hour['t'.($hour+1)]=='N')
                {
                    continue;
                }

                $go_continue = false;
                //시작시간~종료시간 사이에 브레이크가 걸려있을 수 있으므로 체크해야한다....상시브레이크 체크
                if($break_data['perm'][$date_w])
                {
                    foreach($break_data['perm'][$date_w] as $breaktime)
                    {
                        if($date.' '.$breaktime >= $loop_date_ymdhis && $date.' '.$breaktime <= $loop_date_ymdhis_after_cl_time)
                        {
                            $go_continue = true;
                            break;
                        }
                    }

                    if($go_continue) continue;
                }

                // 시작시간~종료시간 사이에 브레이크가 걸려있을 수 있으므로 체크해야한다....특정일브레이크 체크
                if($break_data['temp'][$loop_date])
                {
                    foreach($break_data['temp'][$loop_date] as $breaktime)
                    {
                        if($date.' '.$breaktime >= $loop_date_ymdhis && $date.' '.$breaktime <= $loop_date_ymdhis_after_cl_time)
                        {
                            $go_continue = true;
                            break;
                        }
                    }

                    if($go_continue) continue;
                }

                /*
                    $allow_time: 특정타임만 스케쥴 잡을 수 있도록 허용
                    ex) MSET은 0분, 30분에만 스케쥴 잡을 수 있다.
                */
                if(!$allow_time || ($allow_time && in_array($min,$allow_time)))
                { 
                    /*
                        스케쥴 잡혀있는 시각 체크. 키:startday, 값:endday
                        (
                            [2021-02-22 21:00:00] => 2021-02-22 21:29:59
                            [2021-02-22 21:30:00] => 2021-02-22 21:59:59
                        )
                    */
                    $no_schedule = true;

                    // 잡혀있는 스케쥴과 겹치는지 확인
                    foreach($schedule_data as $sc_startday=>$sc_endday)
                    {
                        //잡고싶은 스케쥴의 시작일, 종료일과 잡혀있는 스케쥴이 겹치는지 체크
                        /*
                            ex)
                            잡혀있는 스케쥴
                            16:10:00 ~ 16:19:59
                            15:50:00 ~ 16:19:59
                            16:20:00 ~ 16:39:59
                            잡고싶은 시간
                            16:00:00 ~ 16:29:59
                        */
                        if(($sc_startday >= $loop_date_ymdhis && $sc_startday <= $loop_date_ymdhis_after_cl_time) || ($sc_endday >= $loop_date_ymdhis && $sc_endday <= $loop_date_ymdhis_after_cl_time))
                        {
                            $no_schedule = false;
                            break;
                        }

                    }   // END foreach 겹친 스케쥴 체크

                    if($no_schedule)
                    {
                        $timeline[$hour][$min] = 1;
                    }
                    
                }
                
                
            }
            
        }   // END foreach 분 체크
        
    }   // END foreach 시간 체크

    return $timeline;
}

//00~24시까지 10분단위의 빈 스케쥴 배열 생성
function schedule_make_empty_timeline()
{
    $timeline = [];
    for($i=0;$i<=24;$i++)
    {
        $hour = sprintf('%02d',$i);  // 10보다 작은 수 앞에 0붙여주기.
        $timeline[$hour] = [];
        for($j=0;$j<=50;$j+=10)
        {
            $min = sprintf('%02d',$j);
            $timeline[$hour][$min] = null;
        }
    }

    return $timeline;
}

/*
    특정시간에 수업비었는지 체크.
    시작시간 ~ 시작시간+수업시간 사이에 스케쥴 잡힌게 없어야 한다.
    연장시간이 강사 근무시간이여야하고 브레이크 역시 걸리지 않아있어야한다.

    endday|선택한 수업의 종료시간
*/
function schedule_is_empty_class_time($tu_uid, $start_time, $cl_time, $check_mode='class')
{
    $today = date('Y-m-d H:i:s');

    //다음시간 비엇는지 확인. 미래시간이여야한다.
    $next_start_date = date('Y-m-d H:i:s', $start_time);
    $next_end_date = date('Y-m-d H:i:s', strtotime('+'.$cl_time.' minutes',strtotime($next_start_date)) -1);

    if($next_start_date < $today) return false;

    //근무시간 확인
    $date = date('Y-m-d');

    //오늘 해당강사 타임별 스케쥴 잡혔는지 여부를 가져온다
    $timeline = schedule_tutor_timeline($tu_uid, $date, $cl_time, $check_mode);

    if($timeline['state'] === false) return false;

    $result = true;

    $this_date = $next_start_date;
    while($this_date < $next_end_date)
    {
        $this_hour = date('H', strtotime($this_date));
        $this_min = date('i', strtotime($this_date));

        if($timeline[$this_hour][$this_min] == 0)
        {
            $result = false;
            break;
        }
        $this_date = date('Y-m-d H:i:s', strtotime('+10 minutes',strtotime($this_date)));
    };

    return $result;
    
}

/*
    수업연장 가능한지 체크. 수업종료 N분전에 체크하러 진입한다. $start_time: 수업종료시간(다음연장시작시간)
    -고정, 자유둘다 내일 오전 6시 이전에 잡혀있는 스케쥴은 연장으로 땡겨올수 없고, 이후스케쥴을 소모하여 연장가능.
    -자유수업 연장 시 주기 횟수를 우선, 주기횟수가 다 소모했다면 벼락으로 횟수소모.
    -b2b회원은 옵션으로 허용해야 사용가능한 기능이다.
    $lesson| lesson_info 함수로 생성된 데이터
*/
function schedule_check_possible_extend_class($uid, $lesson_id, $tu_uid, $start_time, $cl_time, $lesson=array(), $d_id='')
{
    $CI =& get_instance();
    $CI->load->model('lesson_mdl');
    $CI->load->model('member_mdl');

    if($d_id =='')
    {
        $wiz_member = $CI->member_mdl->get_wiz_member_by_wm_uid($uid);
        $d_id = $wiz_member['wm_d_did'];
    }

    $wiz_dealer = $CI->member_mdl->get_wiz_dealer($d_id);

    if($wiz_dealer['use_extend_class'] !='Y') return false;

    $today = date('Y-m-d H:i:s');
    $tommorow = date('Y-m-d', strtotime('+1 day'));
    
    $next_start_date = date('Y-m-d H:i:s', $start_time);
    $next_end_date = date('Y-m-d H:i:s', strtotime('+'.$cl_time.' minutes',strtotime($next_start_date)) -1);
    if($next_start_date < $today) return false;
    
    //다음시간 시작시간~종료시간까지 해당 회원에게 스케쥴 잡혀있는게 있는지 확인.
    $schedule = $CI->lesson_mdl->check_exist_schedule_by_date($uid, $next_start_date, $next_end_date);
    if($schedule) return false;

    //연장할만큼 수업이 남아있는지 체크
    $lesson = empty($lesson) ? lesson_info($lesson_id, $uid):$lesson;

    //자유수업은 아직 선생님선택하지 않은 갯수 남아있는지 확인. 선택된 수업은 오늘날짜 수업or 지난수업이니 건들지 않는다.
    if($lesson['lesson']['wl_cl_gubun'] =='2')
    {
        if($lesson['free_sc_data']['remain_class_total_cnt'] < 1) return false;
    }
    else
    {
        //내일 오전 6시 이후에 스케쥴이 남아있는지 확인
        $where = " WHERE ws.lesson_id = ".$lesson_id." AND ws.present=1 AND ws.kind='n' AND ws.startday >='".$tommorow.' 06:00:00'."'";
        $list_schedule_count = $CI->lesson_mdl->list_count_schedule('', $where);
        if($list_schedule_count['cnt'] < 1) return false;
    }
    
    //강사 스케쥴 비엇는지 체크
    $check = schedule_is_empty_class_time($tu_uid, $start_time, $cl_time, 'extend');
    if(!$check) return false;

    return true;
}


//해당날짜이후에 설정된 주간수업요일에 해당하는 날짜 리턴. (다음수업날짜 찾기) nextdate:지난수업일자+1일로 요청
function schedule_find_next_class_time($nextdate, $lessonWeek, $tu_uid)
{
    if (!$lessonWeek) return;

    $CI =& get_instance();
    $CI->load->model('holiday_mdl');
    $CI->load->model('tutor_mdl');

    $week_arr = explode(':',$lessonWeek);

    $sDate = $nextdate;
    while (1) 
    {
        $iWeek = date("w", strtotime($sDate));
        //수업요일정보 처리 'Y:Y:Y:Y:Y::' 와 같은 형식으로 저장되어 있는 정보가 배열형식으로 변환되서, 0배열이 월요일이다
        $iWeek = $iWeek == 0 ? 6:$iWeek-1;
        if($week_arr[$iWeek] =='') 
        {
            $sDate = date("Y-m-d", strtotime($sDate." +1 day"));
            continue;
        }
        
        $holiday = $CI->holiday_mdl->check_holiday($sDate);
        if($holiday)
        {
            $sDate = date("Y-m-d", strtotime($sDate." +1 day"));
            continue;
        }

        //강사 휴일 체크
        $tutor_blockdate = $CI->tutor_mdl->check_tutor_blockdate_day($tu_uid, $sDate);
        if($tutor_blockdate)
        {
            $sDate = date("Y-m-d", strtotime($tutor_blockdate['endday']." +1 day"));
            continue;
        }

        break;
    }
    return $sDate;
}

//같은 수강상품인데 스케쥴이 중복된경우 있는지 확인해서 정리. 자유수업은 호출하지 말것
function schedule_refresh_overlap_class($lesson_id, $weekend, $startday)
{
    $CI =& get_instance();
    $dupl = $CI->lesson_mdl->find_duplicate_class_in_lesson($lesson_id);

    if($dupl)
    {
        $admin = base_get_login_admin_id();
        $start_time = substr($dupl[0]['startday'],11);
        $last_class = $CI->lesson_mdl->row_last_class_present_1_after_1day($lesson_id, date('Y-m-d 00:00:00',strtotime('+1 day')));

        if($last_class)
        {
            $next_start_date = $last_class['ws_startday'];

            foreach($dupl as $row)
            {
                $sc_list = explode(',',$row['sc_ids']);
                $dupl_cnt = 0;

                foreach($sc_list as $key=>$sc_id)
                {
                    if($key == 0) continue; //첫번째는 패스
                    //두번째 중복건부터 순서대로 마지막날의 다음수업날짜를 계산해서 날짜 업데이트 해준다.

                    $next_start_date = schedule_find_next_class_time(date('Y-m-d', strtotime('+1 day', strtotime($next_start_date))), $weekend, $last_class['ws_tu_uid']).' '.$start_time;
                    $next_end_date = date('Y-m-d H:i:s', strtotime('+'.$last_class['ws_cl_time'].' minutes',strtotime($next_start_date)) -1);

                    $CI->lesson_mdl->update_wiz_schedule($sc_id,[
                        'startday'      => $next_start_date,
                        'endday'        => $next_end_date,
                        'weekend'       => date('w', strtotime($next_start_date)),
                    ]);

                    $dupl_cnt++;
                }

                if($dupl_cnt)
                {
                    //수업변경기록
                    $CI->lesson_mdl->insert_wiz_tutor_change([
                        'lesson_id' => $lesson_id,
                        'a_tuid'    => $last_class['ws_tu_uid'],
                        'b_tuid'    => $last_class['ws_tu_uid'],
                        'a_tutor'   => $last_class['ws_tu_name'],
                        'b_tutor'   => $last_class['ws_tu_name'],
                        'a_time'    => $start_time,
                        'b_time'    => $start_time,
                        'cl_time'   => $last_class['ws_cl_time'],
                        'startday'  => $startday,
                        'endday'    => $next_end_date,
                        'a_date'    => substr($row['startday'],0,11),
                        'b_date'    => substr($next_start_date,0,11),
                        'man_id'    => $admin ? $admin:'',
                        'man_name'  => '',
                        'regdate'   => date('Y-m-d H:i:s'),
                        'kind'      => 'x',
                    ], '같은 시간의 중복된 수업 '.$dupl_cnt.'개 정리(API)');
                }

            }
        }
        
    }

}


/**
 * 수업 상태가 1-4에서 5,6으로 변환시
 * 마지막날에 스케줄 추가
 * 리턴값은 새로 추가된 sc_id
 * setAddNewScheduleToLast
 */
function schedule_add_new_schedule_to_last($lesson_id, $wm_uid, $present=1)
{
    if (!$lesson_id) return false;

    $CI =& get_instance();
    $CI->load->model('lesson_mdl');
    $CI->load->model('schedule_mdl');
    $CI->load->model('tutor_mdl');

    $lesson_info = lesson_info($lesson_id, $wm_uid);
    if (!$lesson_info) return false;

    $free_sc_data = $lesson_info['free_sc_data'];
    $lesson = $lesson_info['lesson'];

    $last_schedule = $CI->schedule_mdl->row_last_schedule($lesson_id);
    if (!$last_schedule) return false;

    $last_startday = substr($last_schedule['ws_startday'], 0, 10);

    /**
    * 1. 예전 수업은 05시~25시 수업이라 24시 이상 수업은 하루씩 밀려서 들어갔었음
    * 2. 0~23으로 시스템을 개편했지만 옛날 시스템에서 등록한 수업은 그대로 남아있음
    * 3. 이 사람들이 수업을 하루 연기등으로 미루면 개편된 시스템 요일로 수업이 붙는 문제가 있음
    *    - 화목 수업이 24시가 넘었다면 수금으로 추가
    *    - 하지만 이 사람들이 수업을 연기하면 수금이 아닌 화목에 수업이 붙어서 문제
    * 4. 그러한 수업들을 따로 필터링하여 수업에 맞는 요일에 수업을 넣어주는 루틴
    **/
    $old_lesson = false;

    // 이런 문제는 항상 00~01시 사이에 발생함
    if(date("H", $lesson['wl_stime']) < 2) 
    {
        $schedule_array = $CI->schedule_mdl->get_last_schedule_data($lesson_id);

        //날짜와 요일을 넘겨서 일치하는지 확인
        //해당 요일에 수업이 없다면 당연히 옛날 수업임
        $old_week_cnt = 0;
        foreach($schedule_array as $key=>$val)
        {
            $w = date("w", strtotime($val['ws_startday']));
            if($lesson['@weekend'][0] == $w) $old_week_cnt++;
        }

        if($old_week_cnt == 0)
        {
            $old_lesson = true;
            $last_startday = date("Y-m-d", strtotime($last_startday) - 86400);
        }
    }

    //지정된 날짜로부터 시작해서 수업요일에 해당되는 날짜 리턴(지정된 날짜 포함, 공휴일도 포함됨)
    $start_date = date("Y-m-d", strtotime($last_startday) + 86400);
    $start_date_week = date("w", strtotime($start_date));
    if (!in_array($start_date_week, $lesson['@weekend']))
    {
        $start_date = date("Y-m-d", strtotime($start_date." +1 day"));
    }

    //강사 휴일 체크
    $tutor_blockdate = $CI->tutor_mdl->check_tutor_blockdate_day($lesson['wl_tu_uid'], $start_date);
    if($tutor_blockdate)
    {
        $start_date = date("Y-m-d", strtotime($tutor_blockdate['endday']." +1 day"));
    }

    //자정이후 수업이면 하루 이후로 변경
    $start_day = date("Y-m-d", strtotime($start_date)).' '.date("H:i:s", $lesson['wl_stime']);
    if($old_lesson)
    {
        $start_day = date("Y-m-d H:i:s", strtotime($start_day)+86400);
    }

    //수업종료날짜
    $endday = date("Y-m-d H:i:s", strtotime($start_day) + $lesson['wl_cl_time'] * 60 - 1);

    //수업요일
    $weekend = date("w", strtotime($start_day));

    $params = array(
        'lesson_id'    => $lesson_id,
        'lesson_gubun' => $lesson['wl_lesson_gubun'],
        'uid'          => $lesson['wl_uid'],
        'wiz_id'       => $lesson['wl_wiz_id'],
        'name'         => $lesson['wl_name'],
        'tu_uid'       => $lesson['wl_tu_uid'],
        'tu_name'      => $lesson['wl_tu_name'],
        'present'      => $present,
        'startday'     => $start_day,
        'endday'       => $endday,
        'weekend'      => $weekend,
        'cl_time'      => $lesson['wl_cl_time'],
        'tel'          => $lesson['wl_tel'],
        'mobile'       => $lesson['wl_mobile'],
        'cl_number'    => $lesson['wl_cl_number'],
        'kind'         => 'n'
    );
    $schedule_id = $CI->lesson_mdl->insert_wiz_schedule($params);
    if (!$schedule_id) return false;

    lesson_resetting_tt($lesson_id, $lesson['wl_cl_gubun'], $free_sc_data);

    return $schedule_id;
}

/**
 * 제일 마지막날의 스케줄 삭제
 * setRemoveLastSchedule
 */
function schedule_remove_last_schedule($lesson_id, $wm_uid)
{
    $CI =& get_instance();
    $CI->load->model('lesson_mdl');
    $CI->load->model('schedule_mdl');

    $last_schedule = $CI->schedule_mdl->row_last_schedule($lesson_id);
    if (!$last_schedule) return false;

    $schedule_del = $CI->lesson_mdl->delete_wiz_schedule($last_schedule['ws_sc_id']);
    if(!$schedule_del) return false;

    $lesson_info = lesson_info($lesson_id, $wm_uid);
    if (!$lesson_info) return false;

    $free_sc_data = $lesson_info['free_sc_data'];
    $lesson = $lesson_info['lesson'];

    lesson_resetting_tt($lesson_id, $lesson['wl_cl_gubun'], $free_sc_data);

    return true;
}


//지정된 날짜에 존재하는 수업을 holiday(5) 처리
//setHoliday
function schedule_holiday($psDate='', $uid=0)
{
    $CI =& get_instance();
    $CI->load->model('holiday_mdl');

    if ($psDate == '') $psDate = date("Y-m-d");
    
    $holiday_list = $CI->holiday_mdl->list_holiday_all($psDate);

    if ($holiday_list)
    {
        foreach ($holiday_list as $x)
        {
            if ($x['disabled_lesson'])
            {	
                //정규수업 불가 - 모든 회원들의 수업이 휴일로 설정됨
                if (!schedule_modify_for_holiday($x['holiday'], $uid)) return $x['holiday'].' 휴일의 작업을 실패했습니다.';

            } 
            else if ($x['d_id'] != '')
            {	
                //정규수업 가능 - 특정 딜러들의 회원들만 휴일로 설정됨
                $dealer_list = explode("*", $x['d_id']);
                if (!schedule_modify_for_holiday($x['holiday'], $uid, $dealer_list)) return $x['holiday'].' 휴일의 딜러작업을 실패했습니다.';
            }
        }
    }
}

//특정날짜의 정규수업을 Holiday로 처리하고 마지막수업일 이후에 READY 스케줄 추가하기
//setModifyScheduleForHoliday
function schedule_modify_for_holiday($psHoliday, $uid, $dealer_id=false)
{
    $CI =& get_instance();
    $CI->load->model('schedule_mdl');

    //$dealer_id 값은 Array() 형태로도 들어올 수 있다.
    $holiday_time = strtotime($psHoliday);

    // 휴일이더라도 다음날 1시까지는 수업이 가능해야 하므로...
    // 휴일만큼은 예외처리 시간 1시간을 둠 - 190415 bskim
    $today = date("Y-m-d 01:00:00", $holiday_time);
    $nextday = date("Y-m-d 00:59:59", $holiday_time + 86400); // 24시 이후 수업 체크

    $join = "";
    if ($dealer_id)
    {
      	//특정딜러의 수업만 휴일로 설정하는 경우
        $join = " JOIN wiz_member as wm ON ws.uid = ws.uid AND ". (is_array($dealer_id) ? "wm.d_id IN ('".implode("','", $dealer_id)."')" : "wm.d_id='".$dealer_id."'");
    }

    //100000000 : 레벨테스트 제외, 100000001 : MSET 제외
    $where = " WHERE ws.startday BETWEEN '".$today."' AND '".$nextday."' AND ws.lesson_id < '100000000' AND ws.present='1'";

    //특정인의 스케줄만 휴강처리해야 하는 경우
    if ($uid) $where .= " AND ws.uid = '".$uid."'";
    else      $where .= " AND ws.uid != '33512'";
    
    $schedule = $CI->schedule_mdl->list_schedule($join, $where, '', '');

    if($schedule)
    {
        $lesson_list = array();
        foreach($schedule as $row)
        {
            //등록된 스케줄의 날짜를 휴일연기한다.
            $holiday = schedule_make_holiday($row, $psHoliday);

            //삭제시 필요 lesson_id2는 수업이 두개일때 등록됨
            $params = array(
                'lesson_id'  => $row['lesson_id'],
                'lesson_id2' => $holiday['lesson_id'],
                'sc_id'      => $row['sc_id'],
                'sc_id2'     => $holiday['sc_id'],
                'holiday'    => $psHoliday
            );
            $CI->schedule_mdl->insert_wiz_schedule_out($params);

            if (!$lesson_list || !in_array($row['lesson_id'], $lesson_list)) $lesson_list[] = $row['lesson_id'];
        }
    }
    
    return true;
}


//등록된 스케줄의 날짜를 휴일연기한다.
//setMakeHoliday
function schedule_make_holiday($row, $psDate='', $manager='')
{
    if (!$row || $row['present'] != '1') return;

    //마지막 수업일 이후에 스케줄 추가
    $sc_id = schedule_add_new_schedule_to_last($row['lesson_id'], $row['uid']);

    $CI =& get_instance();
    $CI->load->model('lesson_mdl');

    //지정된 스케줄의 휴강처리
    $params = array(
        'present' => '5',
    );
    $CI->lesson_mdl->update_wiz_schedule($row['sc_id'], $params);

    $lesson_info = lesson_info($row['lesson_id'], $row['uid']);
    $free_sc_data = $lesson_info['free_sc_data'];
    $lesson = $lesson_info['lesson'];

    //수강상품 시작,종료일
    $period_date = $CI->lesson_mdl->get_lesson_start_end_day($row['lesson_id']);
    
    //처리자(매니저) 아이디 없을 경우 강사가 휴일 처리 한 경우 이므로 강사 아이디를 넣어준다
    if($manager == '')
    {
        $manager['man_id']   = $row['tu_uid'];
        $manager['man_name'] = $row['tu_name'];
    }

    //스케쥴 변경 기록
    $CI->lesson_mdl->insert_wiz_tutor_change([
        'lesson_id' => $row['lesson_id'],
        'a_tuid'    => $row['tu_uid'],
        'b_tuid'    => $row['tu_uid'],
        'a_tutor'   => $row['tu_name'],
        'b_tutor'   => $row['tu_name'],
        'a_time'    => date("H:i:s", $lesson['wl_stime']),
        'b_time'    => date("H:i:s", $lesson['wl_stime']),
        'cl_time'   => $lesson['wl_cl_time'],
        'startday'  => $period_date['startday'],
        'endday'    => $period_date['endday'],
        'a_date'    => $psDate,
        'b_date'    => $psDate,
        'man_id'    => $manager['man_id'],
        'man_name'  => $manager['man_name'],
        'regdate'   => date('Y-m-d H:i:s'),
        'kind'      => 'f',
        'class_su'  => '0'
    ], '스케쥴 변경');

    //수강상품의 시작일,종료일,수업일수 재계산
    lesson_resetting_tt($row['lesson_id'], $lesson['wl_cl_gubun'], $free_sc_data);

    return array('lesson_id'=>'0', 'sc_id'=>$sc_id);
}
