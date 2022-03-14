<?php
defined("BASEPATH") OR exit("No direct script access allowed");

/*
유저들이 선택한 선생님 해시태그 중 상위 태그 n개에 대한 리스트
count : 상위태그 갯수
it_uid : 뽑힌 상위 n개 태그중에 특정태그가 포함된 것. 빈값 시 상위 n개 정리한 리스트만 리턴
tu_uid : 특정선생님만 검색
toString : it_uid 코드가 아닌 문자로 리턴 여부
*/
function tutor_major_hashtag($count=5,$it_uid='',$tu_uid=[],$toString=false){
    if(!$count) return null;

    $CI =& get_instance();
    $CI->load->model('tutor_mdl');

    $where = '';
    $subwhere = '';
    if($tu_uid)
    {
        $subwhere = ' WHERE tu_uid IN ('.implode(',',$tu_uid).')';
    }

    $tag_like = [];
    if($it_uid)
    {
        $it_uid = explode(',',$it_uid);
        foreach($it_uid as $v)
        {
            $tag_like[] = " tmp.it_uid LIKE '%,".$v.",%' ";
        }
    }

    if(count($tag_like) > 0)
    {
        $where.= sprintf(' WHERE ( %s )',implode(' OR ',$tag_like));
    }

    $tag_list = $CI->tutor_mdl->tutor_major_hashtag($count,$where,$subwhere);
    
    $list_star_item = [];
    $return = [];
    if($tag_list)
    {
        foreach($tag_list as $row)
        {
            $it_uid_list = substr(substr($row['it_uid'],0,-1),1);
            if($toString)
            {
                $return[$row['tu_uid']] = tutor_star_item_to_str($it_uid_list, $list_star_item);
            }
            else
            {
                $return[$row['tu_uid']] = $it_uid_list;
            }
            
        }
    }
    
    return $return;
}

function tutor_merge_list_addinfo($tutor_l, $tag_cnt=5)
{
    $CI =& get_instance();
    $CI->load->model('tutor_mdl');

    // 각 강사에게 붙은 해시태그 구해서 넣어준다
    $tu_ids = array_column($tutor_l,'tu_uid');
    $hash_l = tutor_major_hashtag($tag_cnt,'',$tu_ids,true);
    
    foreach($tutor_l as $key=>$val)
    {
        $tutor_l[$key]['hashtag'] = array_key_exists($val['tu_uid'],$hash_l) ? $hash_l[$val['tu_uid']]:null;
        $tutor_l[$key]['tu_pic_main'] = $val['tu_pic_main'] ? $val['tu_pic_main']:'';
    }

    // 각 강사에게 최근평가 조회해서 넣어준다
    $tu_uid = array_column($tutor_l,'tu_uid');
    
    if(!empty($tu_uid))
    {
        $where = ' WHERE ts.tu_uid IN ('.implode(',',$tu_uid).') GROUP BY ts.tu_uid';
        $ts = $CI->tutor_mdl->list_tutor_star($where,', max(ts.ts_uid) as max_ts_uid, (SELECT count(distinct(uid)) AS cnt FROM tutor_star WHERE tu_uid = ts.tu_uid) AS ts_count ');
        
        if($ts)
        {
            $ts_uid = array_column($ts,'max_ts_uid');

            $where = ' WHERE ts.ts_uid IN ('.implode(',',$ts_uid).')';
            $select_col_content = ', ts.ts_content, (SELECT count(distinct(uid)) AS cnt FROM tutor_star WHERE tu_uid = ts.tu_uid) AS ts_count';
            $ts = $CI->tutor_mdl->list_tutor_star($where,$select_col_content);
    
            if($ts)
            {
                foreach($tutor_l as $key=>$val)
                {
                    foreach($ts as $val2)
                    {
                        $tutor_l[$key]['ts_content'] = '';
                        if($val['tu_uid'] == $val2['tu_uid'])
                        {
                            $tutor_l[$key]['ts_content'] = $val2['ts_content'];
                            $tutor_l[$key]['ts_count'] = $val2['ts_count'];
                            break;
                        }
                    }
                }
            }
        }
        

    }
    return $tutor_l;
}

//강사 세부정보 추가(해시태그, 평점, 추천연령)
function tutor_add_detail_info($request)
{
    $return = [];
    $CI =& get_instance();
    $CI->load->model('tutor_mdl');
    $CI->load->model('board_mdl');

    // 강사 평점
    $return['t_star'] = $CI->tutor_mdl->row_tutor_star_log($request['tu_uid']);

    // 강사 근태
    if($request['commute_limit'])
    {
        $where = sprintf("  WHERE mb.table_code=1134 AND mb.sim_content= '%s' ", $request['tu_uid']);
        $orderby = " ORDER BY `mb_unq` DESC ";
        $limit = " LIMIT ".$request['commute_limit'];
        $list_board = $CI->board_mdl->list_board("", $where, $orderby, $limit);
        $return['commute_log'] = board_list_writer($list_board);
        #$return['commute_log'] = $CI->tutor_mdl->list_tutor_commute_log($request['tu_uid'],$orderby);
    }
    
    // 강사 평가
    if($request['evaluation_limit'])
    {
        $where = sprintf(" WHERE ts.tu_uid=%s ORDER BY ts.ts_uid DESC LIMIT %s , %s", $request['tu_uid'], $request['evaluation_start'], $request['evaluation_limit']);
        $return['evaluation'] = tutor_list_evaluation($where);
    }

    // 추천연령
    #$return['recomment_log'] = $CI->tutor_mdl->row_tutor_recommend_log($request['tu_uid']);
    // 해시태그 분석(추천레벨,성향,스타일)
    $where = sprintf(" WHERE thl.tu_uid=%s ", $request['tu_uid']);
    $order = " ORDER BY thl.count DESC ";
    $tutor_hashtag_count_list = $CI->tutor_mdl->list_tutor_hashtag_log($where,$order,'');
    
    $where = sprintf(" WHERE tu_uid=%s AND item1 IS NOT NULL", $request['tu_uid']);
    $tutor_star_count_total = $CI->tutor_mdl->row_table_count('tutor_star', $where);
    $return['analyzed_hashtag'] = tutor_analyze_hashtag($tutor_hashtag_count_list, $tutor_star_count_total['cnt']);

    if($request['special_limit'] > 0)
    {
        $search_speicial = array(); 

        array_push($search_speicial, "mb.notice_yn ='N' AND mb.del_yn ='N' AND mb.tu_name = '".$request['tu_name']."'");

        $where_search = "";
        $where_search .= implode(" AND ", $search_speicial);

        if($where_search != "")
        {
            $where_special = sprintf(" WHERE %s", $where_search);
        }

        $limit_special = sprintf(" LIMIT %s , %s", $request['special_start'], $request['special_limit']);
        $order_special = sprintf(" ORDER BY %s %s", $request['special_order_field'], $request['special_order']);
        $inner_table = " INNER JOIN wiz_member wm ON mb.uid = wm.uid";
        
        $select_col_content = " 'dictation.list' as mb_table_code, '얼굴철판딕테이션' as mbn_table_name,";

        $list_board = $CI->board_mdl->list_board_cafeboard("", $where_special, $order_special, $limit_special, $select_col_content, $inner_table);
        $return['special_board'] = board_list_writer($list_board);
    }

    return $return;
}

function tutor_list_evaluation($where)
{
    $CI =& get_instance();
    $CI->load->model('tutor_mdl');

    $return = $CI->tutor_mdl->list_tutor_star_user_info($where);

    if($return)
    {

        $display_name = null;
        $item_list_all = null;
        foreach($return as $key=>$row)
        {
            if($return[$key]["nickname"]) $display_name = $return[$key]["nickname"];
            else $display_name = ($return[$key]['ename']) ? $return[$key]['ename'] : $return[$key]['name'];
            
            $return[$key]['hashtag'] =  tutor_star_item_to_str($row['item1'], $item_list_all);
            $return[$key]['display_name'] =  $display_name;
        }
    }

    return $return;
}

/* 강사 평가 코드->한글변환. 배열로 리턴
$items : 아이템코드가 콤마로 구분된 형태
$list_star_item : tutor_star_item 테이블에서 미리 뽑아놨거나, 빈 변수를 같이 넘기면 & 로 리스트넣어준다. 루프돌릴때 사용
*/
function tutor_star_item_to_str($items,& $list_star_item=[])
{
    $return = [];

    if(empty($list_star_item))
    {
        $CI =& get_instance();
        $CI->load->model('tutor_mdl');
        $list_star_item_ins = $CI->tutor_mdl->list_star_item();
        $list_star_item = array_column($list_star_item_ins, 'it_name','it_uid');
    }
    
    $it_uid_list_arr = explode(',',$items);
    asort($it_uid_list_arr);

    foreach($it_uid_list_arr as $it_uid_v)
    {
        $return[] = [
            'it_uid' => $it_uid_v,
            'it_name' => $list_star_item[$it_uid_v]
        ];
    }

    return $return;
}

function tutor_analyze_hashtag($tutor_hashtag_log_list, $tutor_star_total)
{
    if($tutor_star_total == 0)
    {
        return null;
    }

    if(!$tutor_hashtag_log_list)
    {
        return null;
    }

    $types = tutor_get_list_star_item_type();
    

    # 로그를 타입별로 분류
    foreach($tutor_hashtag_log_list as $row)
    {
        if(!array_key_exists('sum_count', $types[$row['parent_uid']]))
        {
            $types[$row['parent_uid']]['sum_count'] = 0;
        }
        $types[$row['parent_uid']]['sum_count'] += $row['count'];
        $types[$row['parent_uid']]['item'][] = $row;
        
        // 성향 NULL값 강제로 넣어주기위해서
        if($row['parent_uid'] == 3){
            $diff_array[] = $row['it_name'];
        }
    }

    # 로그총 갯수당 비율, 타입간 비율계산
    foreach($types as $key=>$type)
    {
        $sum_count = $types[$key]['sum_count'];

        $rate_sum = 0;
        $i = 1;
        foreach($type['item'] as $k=>$v)
        {
            $rate_sum_count = round(($v['count'] / $sum_count) * 100);
            $rate_sum+= $rate_sum_count;

            // 총합이 100이 아니면 맞춰주기위해..
            if($i == count($type['item']) && $rate_sum != 100)
            {
                $rate_sum_count = $rate_sum > 100 ? ($rate_sum_count - ($rate_sum - 100)):($rate_sum_count + (100 - $rate_sum));
            }

            $types[$key]['item'][$k]['rate_item'] = $rate_sum_count;
            $types[$key]['item'][$k]['rate_total_count'] = round(($v['count'] / $tutor_star_total) * 100);

            $rate_total_count = $types[$key]['item'][$k]['rate_total_count'];

            // 점수가 약해보이기에 보정치 줌
            if($rate_total_count < 10){
                $rate_total_count = $rate_total_count + 30;
            }
            elseif($rate_total_count < 20){
                $rate_total_count = $rate_total_count * 2.5;
            }
            elseif($rate_total_count < 30){
                $rate_total_count = $rate_total_count * 2.3;
            }
            elseif($rate_total_count < 40){
                $rate_total_count = $rate_total_count * 2.1;
            }
            elseif($rate_total_count < 50){
                $rate_total_count = $rate_total_count * 1.9;
            }
            elseif($rate_total_count < 60){
                $rate_total_count = $rate_total_count * 1.8;
            }
            elseif($rate_total_count < 70){
                $rate_total_count = $rate_total_count * 1.5;
            }
            elseif($rate_total_count < 95){
                $rate_total_count = $rate_total_count * 1.4;
            }

            $rate_total_count = $rate_total_count > 100 ? 100 :floor($rate_total_count);

            $types[$key]['item'][$k]['rate_total_count'] = $rate_total_count;

            $i++;
        }
    }

    
    // 평가서 없을시 NULL이라서 강제로 0으로 넣어줌.(성향)
    $personal_item = array('친절함', '유쾌함', '섬세함', '엄격함', '차분함');
    //섬세함
    $cnt = 0;

    for($i=0; $i<count($personal_item); $i++){
        
        if(!in_array($personal_item[$i], $diff_array)){
            // 키 3: 성향
            $types['3']['item'][count($diff_array)+$cnt]['it_name'] = $personal_item[$i];
            $types['3']['item'][count($diff_array)+$cnt]['rate_item'] = 0;
            // 점수가 약해보이기에 보정치 줌
            $types['3']['item'][count($diff_array)+$cnt]['rate_total_count'] = 30;
            $cnt++;
        }else{
        }     
    }
    
    
    return tutor_hashtag_array_to_str($types);
}

function tutor_get_list_star_item_type($add_sub_item=false)
{
    $CI =& get_instance();
    $CI->load->model('tutor_mdl');
    $list_star_item_type = $CI->tutor_mdl->list_star_item_type();

    $types = [];
    
    foreach($list_star_item_type as $row)
    {
        $types[$row['it_uid']] = $row;
    }

    if($add_sub_item)
    {
        $list_star_sub_item = $CI->tutor_mdl->list_star_item();
        
        foreach($list_star_sub_item as $row)
        {
            #$types[$row['parent_uid']]['item'][$row['it_uid']] = $row;
            $types[$row['parent_uid']]['item'][] = $row;
        }
    }

    return $types;
}


function tutor_regist_evaluation($request)
{
    $CI =& get_instance();
    $CI->load->model('tutor_mdl');
    $CI->load->model('member_mdl');
    $CI->load->model('point_mdl');

    $wiz_member = $CI->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);
    
    $request['uid'] = $wiz_member['wm_uid'];
    $request['ename'] = $wiz_member['wm_ename'];
    $request['lev_gubun'] = $wiz_member['wm_lev_gubun'];
    

    # 오늘 등록 한 평가가 있는지 체크
    $today_regist = $CI->point_mdl->count_point_comment_limit_day_by_kind($request['uid'], "m", date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'));
    if($today_regist['cnt'] > 0 ) 
    {
        return ['state'=>false, 'msg' => '프로세스 오류', 'err_msg' => '하루에 한번 등록하실수 있습니다.', 'res_code'=>"0900", 'err_code'=>'0402'];
    }
    
    $item2 = substr($request['lev_gubun'], 0, 1);
    $regdate = date('Y-m-d H:i:s');

    $insert_param = [
        'tu_uid'        => $request['tu_uid'],
        'uid'           => $request['uid'],
        'ts_name'       => $request['ename'],
        'ts_star'       => $request['star'],
        'ts_content'    => $request['review'],
        'regdate'       => $regdate,
        'name_hide'     => 'N',                 // 현재는 공개등록만
        'item1'         => $request['item'],
        'item2'         => $item2,
    ];

    $result = $CI->tutor_mdl->insert_tutor_evaluation($insert_param);

    if(is_array($result) && $result['state'] === false)
    {
        return $result;
    }
    elseif($result < 0)
    {
        return ['state'=>false, 'msg' => 'DB ERROR!', 'res_code'=>"0500"];
    }

    # 포인트 등록
    $point = common_point_standard('tutor_evaluation');
    $point_param = [
        'uid'       => $request['uid'],
        'name'      => $wiz_member['wm_name'],
        'pt_name'   => '강사 평가 이벤트로 '.$point.'포인트 적립 축하',
        'point'     => $point,
        'kind'      => 'm',
        'b_kind'    => 'teacher',
        'co_unq'    => $result,
        'regdate'   => $regdate,
        'showYn'    => 'y',
    ];
    $result1 = $CI->point_mdl->set_wiz_point($point_param);

    if($result1 < 0)
    {
        return ['state'=>false, 'msg' => 'DB ERROR!!', 'res_code'=>"0500"];
    }
    
    # 강사정보
    $tutor_info = $CI->tutor_mdl->get_tutor_info_by_tu_uid($request['tu_uid']);

    $money = '0';
    if($request['star'] =='10' && $tutor_info['pay_type'] =='d')
    {
        $money = "2";
    }
    elseif($tutor_info['pay_type'] =='d')
    {
        $money = "1";
    }
    # 강사 인센티브 등록. 성과급형만 지급하며, 별점 10받으면 2, 9이하는 1 페소 받는다.
    $incentive_param = [
        'tu_uid'     => $request['tu_uid'],
        'tu_id'      => $tutor_info['tu_id'],
        'tu_name'    => $tutor_info['tu_name'],
        'lesson_id'  => $result,
        'uid'        => $request['uid'],
        'name'       => $request['ename'],
        'money'      => $money,
        'in_kind'    => '4',
        'in_yn'      => 'y',
        'regdate'    => $regdate,
    ];
    $CI->tutor_mdl->insert_tutor_incentive($incentive_param);

    return ['state'=>true, 'insert_id'=>$result];
}

function tutor_modify_evaluation($request)
{
    $CI =& get_instance();
    $CI->load->model('tutor_mdl');
    $CI->load->model('member_mdl');
    $CI->load->model('point_mdl');

    $wiz_member = $CI->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);
    
    if(!tutor_check_evaluation_in_24hours($request['ts_uid'],$wiz_member['wm_uid']))
    {
        return ['state'=>false, 'msg' => '프로세스 오류', 'res_code'=>"0900", 'err_msg' =>'강사평가는 등록 후 24시간 경과 시 수정 및 삭제가 불가합니다.','err_code'=>'0403'];
    }

    $where['uid'] = $wiz_member['wm_uid'];
    $where['ts_uid'] = $request['ts_uid'];

    $update_param = [
        'ts_star'       => $request['star'],
        'ts_content'    => $request['review'],
        'name_hide'     => 'N',                 // 현재는 공개등록만
        'item1'         => $request['item'],
    ];

    $result = $CI->tutor_mdl->update_tutor_evaluation($update_param, $where);

    if($result < 0)
    {
        return ['state'=>false, 'msg' => 'DB ERROR!', 'res_code'=>"0500"];
    }

    return ['state'=>true];
}

function tutor_delete_evaluation($request)
{
    $CI =& get_instance();
    $CI->load->model('tutor_mdl');
    $CI->load->model('member_mdl');
    $CI->load->model('point_mdl');

    $wiz_member = $CI->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);
    
    if(!tutor_check_evaluation_in_24hours($request['ts_uid'], $wiz_member['wm_uid']))
    {
        return ['state'=>false, 'msg' => '프로세스 오류', 'res_code'=>"0900", 'err_msg' =>'강사평가는 등록 후 24시간 경과 시 수정 및 삭제가 불가합니다.','err_code'=>'0403'];
    }

    $where['uid'] = $wiz_member['wm_uid'];
    $where['ts_uid'] = $request['ts_uid'];

    # 평가삭제
    $result = $CI->tutor_mdl->delete_tutor_evaluation($where);

    if($result < 0)
    {
        return ['state'=>false, 'msg' => 'DB ERROR!', 'res_code'=>"0500"];
    }

    $point = common_point_standard('tutor_evaluation');
    # 포인트 반환 
    $point_param = [
        'pt_name'   => '강사 평가 게시물 삭제로 '.$point.'포인트 회수됨',
        'del_regate'=> date('Y-m-d H:i:s'),
        'showYn'    => 'd',
    ];
    $where = [
        'uid'       => $wiz_member['wm_uid'], 
        'b_kind'    => 'teacher',
        'co_unq'    => $request['ts_uid'],
    ];
    $result = $CI->point_mdl->update_wiz_point($point_param, $where);
    if($result < 0)
    {
        return ['state'=>false, 'msg' => 'DB ERROR!!', 'res_code'=>"0500"];
    }

    # 강사인센티브 회수
    $where = [
        'in_kind'    => '4',
        'lesson_id'  => $request['ts_uid'],
    ];
    $CI->tutor_mdl->delete_tutor_incentive($where);

    return ['state'=>true];
}

function tutor_check_evaluation_in_24hours($ts_uid,$uid)
{
    if(!$ts_uid) return true;
    $CI =& get_instance();
    $CI->load->model('tutor_mdl');

    $where = " WHERE ts.regdate > '".date('Y-m-d H:i:s',strtotime('-1 day'))."' AND ts.uid=".$uid." AND ts.ts_uid=".$ts_uid;
    $res = $CI->tutor_mdl->list_tutor_star($where);

    return $res ? true:false;
}

function tutor_tropys_replace_to_str($tropy)
{
    if(!$tropy) return null;
    $tropy_arr = explode('|',$tropy);

    $return = [];
    foreach($tropy_arr as $row)
    {
        if($row == '4') continue;
        $v = common_tropy_to_str($row);
        $return[][$v[0]] = $v[1];
    }
    return $return;
}


function tutor_hashtag_step1_to_str($tag,$onlystr=false)
{
    $main_hash = [
        1 => [
            'recomm_level',
            '추천레벨'
        ],
        2 => [
            'recomm_age',
            '추천연령'
        ],
        3 => [
            'personal',
            '성향'
        ],
        4 => [
            'style',
            '스타일'
        ],
        5 => [
            'skill',
            '스킬'
        ],
    ];

    return $onlystr ? $main_hash[$tag][1]:$main_hash[$tag];
}

function tutor_hashtag_array_to_str($list)
{
    $return = [];
    foreach($list as $row)
    {
        $v = tutor_hashtag_step1_to_str($row['it_uid']);
        $return[$v[0]] = $row;
    }
    return $return;
}

/* 
    기존 강사페이지
    top.php 리스트 체크
*/
function tutor_check_valid_list($table_code, $wiz_tutor)
{
    $CI =& get_instance();
    $CI->load->model('board_mdl');

    $now = date('Y-m-d H:i:s');
    
    $notice_cnt = 0;
    $msg = array();
    $str = '';

    if($table_code == 'correction')
    {
        // 영어첨삭 - 데드라인 지난경우
        $where = " WHERE mb.tu_uid = '{$wiz_tutor['wt_tu_uid']}' AND mb.w_step!=2 AND mb.w_hopedate <= '{$now}'";
        $list_count_board_wiz_correct = $CI->board_mdl->list_count_board_wiz_correct($where);

        if($list_count_board_wiz_correct['cnt'] > 0)
        {
            // echo "<script>alert('Your GC deadline is OVERDUE. Please finish the pending GC ASAP.');</script>";
            $str = "Your GC deadline is OVERDUE. Please finish the pending GC ASAP.";  
            array_push($msg, $str);
            $notice_cnt++;
        }

    }
    else if($table_code == 'express')
    {
    	//수업대본서비스 - 36시간이 지난경우
        $where = " WHERE mb.tu_uid = '{$wiz_tutor['wt_tu_uid']}' AND mb.wiz_id IS NOT NULL AND '{$now}' > DATE_ADD(mb.regdate, interval +36 hour) AND work_state=4;";
        $list_count_board = $CI->board_mdl->list_count_board('', $where);
        
        if($list_count_board['cnt'] > 0)
        {
            // echo "<script>alert('Your GC deadline is OVERDUE. Please finish the pending GC ASAP.');</script>";
            $str = "Your Transcription deadline is OVERDUE. Please finish the pending Transcription ASAP";
            array_push($msg, $str);
            $notice_cnt++;
        }
    }

    return array(
        'msg' => $msg,
        'notice_cnt' =>$notice_cnt
    );
}

// 강사와 1:1대화 알림 전송용 보드 링크만들기
function tutor_message_make_viwe_link($mb_unq)
{    
    if($_SERVER['HTTP_HOST'] == 'localhost:8000' || $_SERVER['HTTP_HOST'] =='dsapi.mintspeaking.com')
    {
        $domain = 'https://dsm.mintspeaking.com';
    }
    else
    {
        $domain = 'https://story.mint05.com';
    }

    $state = '/#/request-view';

    return $domain . $state. '?bt=custom&mu='.$mb_unq;
}

// 소셜 아이디면 소셜 아이콘 가져오기
function tutor_get_sosocial_icon($regi_gubun, $wiz_id, $social_email)
{    
    return ($regi_gubun !='mint05' && $regi_gubun) ? common_social_icon($regi_gubun).' '.$social_email : $wiz_id;
}

/* 
    강사 브레이크 데이터 가져오기

    시간대 추출 후 특정시간에 분단위 브레이크건 것은 없는지 체크해야한다.
    -특정 분 영구 브레이크
    -특정 분 특정일만 브레이크 
    두 종류가 있다.

    wiz_tutor_breakingtime 테이블에 브레이크 할 '분'이 저장된다.
    alldays테이블에 5,6,7,8,9,2,3,4가 지정된다(월화수목금토일)
    4는 공휴일인데 쓸모없는거같음. 용도불명
    0으로 지정되면 특정일만 임시 브레이크

    --참고--
    wiz_tutor_weekend(기본근무시간)
    스케쥴 잡을 수 있는 오픈된 기본 '시간' 저장

    wiz_tutor 테이블에 t0, t1, t2...필드는 안쓰는거같다.
    시간설정하면 tt에만 반영된다.
*/
function tutor_breaking_time($tu_uid, $date, $date_after_day)
{
    $CI =& get_instance();
    $CI->load->model('tutor_mdl');

    $break = $CI->tutor_mdl->list_tutor_breaking($tu_uid, $date, $date_after_day);

    $break_data = [
        'perm' => array(),  // 상시 브레이크 데이터
        'temp' => array(),  // 특정일 브레이크 데이터
    ];

    // db alldays에 이상한 숫자가 들어있어서 date('w')값과 매칭시켜준다.
    $match_alldays = [
        '5' => '1', //월
        '6' => '2', //화
        '7' => '3', //수
        '8' => '4', //목
        '9' => '5', //금
        '2' => '6', //토
        '3' => '0', //일
    ];

    // 브레이크 정리
    if($break)
    {
        foreach($break as $row)
        {
            // 특정일 브레이크
            if($row['alldays'] == '0')
            {
                $break_data['temp'][$row['date']][] = $row['time'];
            }
            else
            {
                // 상시 브레이크
                $break_data['perm'][$match_alldays[$row['alldays']]][] = $row['time'];
            }
        }
    
    }

    return $break_data;
}

/*
    target_date 날짜 기준 강사급여 정보
    강사 급여정보는 mint_tutor_pay에 로그형식으로 데이터가 누적되어있다.
    app_date(effective date 적용일)에 따라 적용될 로우가 결정된다.
*/
function tutor_pay_config($tu_uid, $target_date='')
{
    $CI =& get_instance();
    $CI->load->model('tutor_mdl');
    $CI->load->model('holiday_mdl');

    $date = $target_date ? $target_date:date('Y-m-d');
    $date_w = date('w',strtotime($date));
    $date_YmdHis = $date.' 00:00:00';

    //휴일데이터 가져오기
    $holiday = $CI->holiday_mdl->check_holiday($date);
    $is_holiday = $holiday['disabled_lesson'] && $holiday['disabled_thunder'] ? 1:0;

    $week_num = $is_holiday ? 9:$date_w;

    //급여 설정 데이터 전부 가져온 후 루프돌려서 $date에 적용되는 급여정보를 리턴해줘야한다
    $mint_tutor_pay = $CI->tutor_mdl->all_mint_tutor_pay($tu_uid);

    if(!$mint_tutor_pay) return;
    
    //토,일,휴일,평일 데이터 중 $date 날짜에 맞는 데이터 선택해주기 위한 구분값
    if ($week_num == '0') $sMiddleName = '_sun_';
    elseif ($week_num == '6') $sMiddleName = '_sat_';
    elseif ($week_num == '9') $sMiddleName = '_holiday_';
    else $sMiddleName = '_';

    foreach($mint_tutor_pay as $key=>$info)
    {
        //적용일
        $start_app_date = $info['app_date'].' 00:00:00';
        //적용종료일
        $end_app_date = $mint_tutor_pay[$key+1]['app_date'] != ''? date('Y-m-d H:i:s',strtotime($mint_tutor_pay[$key+1]['app_date'].' 00:00:00') - 1): '2099-12-31 23:59:59';

        if($start_app_date <= $date_YmdHis && $date_YmdHis <= $end_app_date) 
        {
            $info['today_type'] = $info['pay'.$sMiddleName.'type'];
            $info['today_group'] = $info['pay'.$sMiddleName.'group'];
            $info['today_fix'] = $info['pay'.$sMiddleName.'fix'];
            $info['today_change1'] = $info['pay'.$sMiddleName.'change1'];
            $info['today_change2'] = $info['pay'.$sMiddleName.'change2'];
            $info['today_jung'] = $info['pay_jung'];	//변동A(b), 변동B(c)에서만 유효하며, 주말수업에는 사용하지 않음
            $info['today_level'] = $info['pay_level'];	//레벨테스트로 받는 급여, 주말수업에는 사용하지 않음
            $info['pay_level_incentive'] = $info['pay_level_incentive'];	//레벨테스트로 받은 학생이 수강 시 강사에게 인센티브 지급해줄 금액. 평일 주말 휴일 전부 사용

            return $info;
        }
    }
    
}


/*
    강사별 급여정보 리턴(구버전)
*/
function tutor_pay_dpr_data($wiz_tutor, $sdate='', $edate='')
{
    $CI =& get_instance();
    $CI->load->model('tutor_mdl');
    $CI->load->model('holiday_mdl');
    $CI->load->model('lesson_mdl');

    $tu_uid = $wiz_tutor['wt_tu_uid'];
    $aTotalSalary = [];
    $aTotalClassTime = [];

    $iUseHolidayTime = '2017-06-01';	//휴일에 벼락치기를 사용하기 시작한 날짜
    $iNowTime = time();

    $iTotalPay = 0;			//합산된 총 급여

    $iPresentTime = 0;		//출석시간(고정급)
    $iAbsentTime = 0;		//결석시간(고정급)
    $iCancelTime = 0;		//취소시간(고정급)
    $iLeveltestPresentTime = 0;	//레벨테스트 출석시간(고정급)
    $iLeveltestAbsentTime = 0 ;	//레벨테스트 결석시간(고정급)

    $iPresentG2 = 0;	//그룹수업 2인(고정급)
    $iPresentG3 = 0;	//그룹수업 3인(고정급)
    $iPresentG4 = 0;	//그룹수업 4인(고정급)

    $iPresentTimePhone = 0;		//TEL/Mobile 출석시간(성과급)
    $iAbsentTimePhone = 0;		//TEL/Mobile 결석시간(성과급)
    $iCancelTimePhone = 0;		//TEL/Mobile 취소시간(성과급)

    $iPresentTimeVOD = 0;		//VOD/Skype 출석시간(성과급)
    $iAbsentTimeVOD = 0;		//VOD/Skype 결석시간(성과급)
    $iCancelTimeVOD = 0;		//VOD/Skype 취소시간(성과급)
    $iLeveltestPresentTimeZ = 0;	//레벨테스트 진행시간(성과급)
    $iLeveltestAbsentTimeZ = 0;		//레벨테스트 결석시간(성과급)

    $iPresentG2VOD = 0;	//그룹수업 2인(성과급)
    $iPresentG3VOD = 0;	//그룹수업 3인(성과급)
    $iPresentG4VOD = 0;	//그룹수업 4인(성과급)

    $iRenewCount = 0;	//리뉴얼 선택횟수
    $iIncentiveSUM = 0;	//인센티브 합계
    $iFixPayTotal = 0;	//고정급 합계
    $iFixTimeTotal = 0;	//고정급을 받아서 제외시켜야할 출석시간(기본보장 성과급)
    $iPenaltySUM = 0;   //인센티브 페널티 합계

    $today = date('Y-m-d');

    $iLevelPay = 0;
    $iTodayLeveltestTime = 0;

    $sumTotalSalary = [];
    
    while($sdate <= $edate)
    {
        $sTargetDate = $sdate;
        $sdatetime = $sTargetDate.' 00:00:00';
        $edatetime = $sTargetDate.' 23:59:59';
        //$iTargetWeek = date('w',strtotime($sdate));

        //조사하려는 날짜가 미래인 경우 무시한다.
        if ($sTargetDate > $today) { $aTotalSalary[$sTargetDate] = array(); continue; }

        //해당날짜에 속하는 급여정보를 가져온다
        $aPayConfigData = tutor_pay_config($tu_uid, $sTargetDate); 

        //해당날짜가 휴일인지
        $holiday = $CI->holiday_mdl->check_holiday($sTargetDate);
        $is_holiday = $holiday['disabled_lesson'] && $holiday['disabled_thunder'] ? 1:0;

        $aTodayPayGroup = explode('-', $aPayConfigData['today_group']);
        $aTodayPayChange1 = explode('-', $aPayConfigData['today_change1']); // 전화 T,M
        $aTodayPayChange2 = explode('-', $aPayConfigData['today_change2']); // 화상 V,S

        $iTodayPresentTime = 0;
        $iTodayPresentG2Time = 0;
        $iTodayPresentG3Time = 0;
        $iTodayPresentG4Time = 0;
        $iTodayAbsentTime = 0;
        $iTodayCancelTime = 0;
        $iTodayPresentTimeVOD = 0;
        $iTodayPresentG2TimeVOD = 0;
        $iTodayPresentG3TimeVOD = 0;
        $iTodayPresentG4TimeVOD = 0;
        $iTodayLeveltestPay = 0;
        $iDayPay = 0;	//해당 날짜의 급여

        $present_T = 0;
        $absent_T = 0;
        $cancel_T = 0;
        $present_V = 0;
        $present_G2 = 0;
        $present_G3 = 0;
        $present_G4 = 0;
        $absent_V = 0;
        $cancel_V = 0;
        $present_Time = 0;
        $absent_Time = 0;
        $cancel_Time = 0;

        if (in_array($aPayConfigData['today_type'], array('a', 'b', 'c'))) {	//고정급(기본급), 변동A, 변동B

            $cl_time_list = $CI->lesson_mdl->total_cl_time_group_by_student_su($tu_uid, $sdatetime, $edatetime);
            
            if($cl_time_list)
            {
                foreach ($cl_time_list as $x) 
                {
                    switch ($x['student_su']) 
                    {
                        case '2': $iPresentTime += $x['cl_time']; $iTodayPresentTime = $x['cl_time']; break;
                        case '3': $iPresentG2 += $x['cl_time']; $iTodayPresentG2Time = $x['cl_time']; break;
                        case '4': $iPresentG3 += $x['cl_time']; $iTodayPresentG3Time = $x['cl_time']; break;
                        case '5': $iPresentG4 += $x['cl_time']; $iTodayPresentG4Time = $x['cl_time']; break;
                    }
                }
            }

            //학생이 결석한 모든 수업시간 계산(레벨테스트 제외)
            $cl_time_row = $CI->lesson_mdl->total_cl_time_except_leveltest($tu_uid, $sdatetime, $edatetime, 3);
            $iTodayAbsentTime = $cl_time_row ? $cl_time_row['cl_time']:0;
            $iAbsentTime += $iTodayAbsentTime;

            //학생이 취소한 모든 수업시간 계산(레벨테스트 제외)
            $cl_time_row = $CI->lesson_mdl->total_cl_time_except_leveltest($tu_uid, $sdatetime, $edatetime, 4);
            $iTodayCancelTime = $cl_time_row ? $cl_time_row['cl_time']:0;
            $iCancelTime += $iTodayCancelTime;

            //학생이 레벨테스트를 진행한 시간 계산
            //이기범과장님 요청으로 레벨테스트MEL은 집계시 20분->10분으로 계산
            //ㄴ2021-05-17 17"45분 이후 기준 다시 10분으로 잡고있다
            $cl_time_row = $CI->lesson_mdl->total_cl_time_leveltest($tu_uid, $sdatetime, $edatetime, 2);
            $iTodayLeveltestPresentTime = $cl_time_row ? $cl_time_row['cl_time']:0;
            $iLeveltestPresentTime += $iTodayLeveltestPresentTime;

            //학생이 레벨테스트에 결석한 시간 계산
            //이기범과장님 요청으로 레벨테스트MEL은 집계시 20분->10분으로 계산
            //ㄴ2021-05-17 17"45분 이후 기준 다시 10분으로 잡고있다
            $cl_time_row = $CI->lesson_mdl->total_cl_time_leveltest($tu_uid, $sdatetime, $edatetime, 2);
            $iTodayLeveltestAbsentTime = $cl_time_row ? $cl_time_row['cl_time']:0;
            $iLeveltestAbsentTime += $iTodayLeveltestAbsentTime;

            switch ($aPayConfigData['today_type']) 
            {
                default:
                case 'a':	//고정급(기본급)
                    if ((!$is_holiday || ($sTargetDate > $iUseHolidayTime && $is_holiday['disabled_thunder'] == '0'))	//휴일에 수업을 안하던 때인지 체크
                        && substr($wiz_tutor['wt_tu_regdate'], 0, 10) <= $sTargetDate	//강사등록전인지..(등록일까지 포함함)
                        && ($wiz_tutor['wt_del_date'] == '' || $wiz_tutor['wt_del_date'] == '0000-00-00 00:00:00' || ($wiz_tutor['wt_del_date'] >= $sTargetDate))	//퇴사했는지..
//							&& $iTargetWeek != '6'	//토요일인지..
//							&& $iTargetWeek != '0'	//일요일인지..
//							&& $iTimestamp <= $iNowTime	//다가오지 않은 날짜인지..
                    ) 
                    {
                        $iDayPay = $aPayConfigData['today_fix'] > 0? $aPayConfigData['today_fix']: 0;
                    }

                    $iFixPayTotal += $iDayPay;
                    break;
                case 'b':	//변동 A
                    $clTime = $iTodayPresentTime + $iTodayAbsentTime + $iTodayCancelTime;

                    $iTmp = $aPayConfigData['today_jung'] * ($clTime / 10) + $iLevelPay;

                    if(!$aTodayPayGroup[0]) $day_pay1_1 = ($iTodayPresentG2Time[0]/10) * (int)$aTodayPayChange2[0];
                    else $day_pay1_1 = ($iTodayPresentG2Time[0]/10) * $aTodayPayGroup[0];
                    if(!$aTodayPayGroup[1]) $day_pay1_2 = ($iTodayPresentG3Time[0]/10) * (int)$aTodayPayChange2[0];
                    else $day_pay1_2 = ($iTodayPresentG3Time[0]/10) * $aTodayPayGroup[1];
                    if(!$aTodayPayGroup[2]) $day_pay1_3 = ($iTodayPresentG4Time[0]/10) * (int)$aTodayPayChange2[0];
                    else $day_pay1_3 = ($iTodayPresentG4Time[0]/10) * $aTodayPayGroup[2];

                    $iDayPay = $iTmp + $day_pay1_1 + $day_pay1_2 + $day_pay1_3;
                    $iLevelPay =  $aPayConfigData['today_level'] * ($iTodayLeveltestTime / 10);
                    break;

                case 'c':	//변동 B
                    $clTime = $iTodayPresentTime[0] + $iTodayCancelTime[0];

                    $iTmp = $aPayConfigData['today_jung'] * ($clTime / 10) + $iLevelPay;

                    if(!$aTodayPayGroup[0]) $day_pay1_1 = ($iTodayPresentG2Time[0]/10) *  (int)$aTodayPayChange2[0];
                    else $day_pay1_1 = ($iTodayPresentG2Time[0]/10) * $aTodayPayGroup[0];
                    if(!$aTodayPayGroup[1]) $day_pay1_2 = ($iTodayPresentG3Time[0]/10) *  (int)$aTodayPayChange2[0];
                    else $day_pay1_2 = ($iTodayPresentG3Time[0]/10) * $aTodayPayGroup[1];
                    if(!$aTodayPayGroup[2]) $day_pay1_3 = ($iTodayPresentG4Time[0]/10) *  (int)$aTodayPayChange2[0];
                    else $day_pay1_3 = ($iTodayPresentG4Time[0]/10) * $aTodayPayGroup[2];
                    $iDayPay = $iTmp + $day_pay1_1 + $day_pay1_2 + $day_pay1_3;
                    $iLevelPay =  $aPayConfigData['today_level'] * ($iTodayLeveltestTime / 10);

                    break;
            }

        } elseif (in_array($aPayConfigData['today_type'], array('d', 'z'))) {	//성과급, 기본보장 성과급

            //수업형태가 VOD/Mobile인 학생이 출석/결석/취소한 수업시간 계산(레벨테스트 제외)
            $cl_time_list = $CI->lesson_mdl->total_cl_time_groupby_present($tu_uid, $sdatetime, $edatetime, [2,3,4], ['T','M']);
            
            if ($cl_time_list)
            { 
                foreach ($cl_time_list as $x) 
                {
                    switch ($x['present']) 
                    {
                        case '2': $iPresentTimePhone += $x['cl_time']; $iTodayPresentTime = $x['cl_time']; break;
                        case '3': $iAbsentTimePhone += $x['cl_time']; $iTodayAbsentTime = $x['cl_time']; break;
                        case '4': $iCancelTimePhone += $x['cl_time']; $iTodayCancelTime = $x['cl_time']; break;
                    }
                }
            }

            
            //수업형태가 VOD/Skype/WebEx인 학생이 출석한 모든 수업시간 계산(그룹수업 포함, 레벨테스트 제외)
            $cl_time_list = $CI->lesson_mdl->total_cl_time_group_by_student_su($tu_uid, $sdatetime, $edatetime," AND ws.lesson_gubun IN ('V','S','B','E') ");
            
            if ($cl_time_list) 
            {
                foreach ($cl_time_list as $x) 
                {
                    switch ($x['student_su']) 
                    {
                        case '2': $iPresentTimeVOD += $x['cl_time']; $iTodayPresentTimeVOD = $x['cl_time']; break;
                        case '3': $iPresentG2VOD += $x['cl_time']; $iTodayPresentG2TimeVOD = $x['cl_time']; break;
                        case '4': $iPresentG3VOD += $x['cl_time']; $iTodayPresentG3TimeVOD = $x['cl_time']; break;
                        case '5': $iPresentG4VOD += $x['cl_time']; $iTodayPresentG4TimeVOD = $x['cl_time']; break;
                    }
                }
            }

            //수업형태가 VOD/Skype/WebEx인 학생이 학생이 결석한 시간 계산
            $cl_time_row = $CI->lesson_mdl->total_cl_time_except_leveltest($tu_uid, $sdatetime, $edatetime, 3," AND ws.lesson_gubun IN ('V','S','B','E') ");
            $iTodayAbsentTimeVOD = $cl_time_row ? $cl_time_row['cl_time']:0;
            $iAbsentTimeVOD += $iTodayAbsentTimeVOD;

            //수업형태가 VOD/Skype/WebEx인 학생이 학생이 취소한 시간 계산
            $cl_time_row = $CI->lesson_mdl->total_cl_time_except_leveltest($tu_uid, $sdatetime, $edatetime, 4," AND ws.lesson_gubun IN ('V','S','B','E') ");
            $iTodayCancelTimeVOD = $cl_time_row ? $cl_time_row['cl_time']:0;
            $iCancelTimeVOD += $iTodayCancelTimeVOD;

            //학생이 레벨테스트를 진행한 시간 계산
            //이기범과장님 요청으로 레벨테스트MEL은 집계시 20분->10분으로 계산
            //ㄴ2021-05-17 17"45분 이후 기준 다시 10분으로 잡고있다
            $cl_time_row = $CI->lesson_mdl->total_cl_time_leveltest($tu_uid, $sdatetime, $edatetime, 2);
            $iTodayLeveltestPresentTime = $cl_time_row ? $cl_time_row['cl_time']:0;
            $iLeveltestPresentTimeZ += $iTodayLeveltestPresentTime;
            $iTodayLeveltestPay = $aPayConfigData['today_level'] * ($iTodayLeveltestPresentTime / 10);

            //학생이 레벨테스트를 결석한 시간 계산
            //이기범과장님 요청으로 레벨테스트MEL은 집계시 20분->10분으로 계산
            //ㄴ2021-05-17 17"45분 이후 기준 다시 10분으로 잡고있다
            $cl_time_row = $CI->lesson_mdl->total_cl_time_leveltest($tu_uid, $sdatetime, $edatetime, 3);
            $iTodayLeveltestAbsentTime = $cl_time_row ? $cl_time_row['cl_time']:0;
            $iLeveltestAbsentTimeZ += $iTodayLeveltestAbsentTime;

            $present_T += $iTodayPresentTime;
            $absent_T += $iTodayAbsentTime;
            $cancel_T += $iTodayCancelTime;

            $present_V += $iTodayPresentTimeVOD;
            $present_G2 += $iTodayPresentG2TimeVOD;
            $present_G3 += $iTodayPresentG3TimeVOD;
            $present_G4 += $iTodayPresentG4TimeVOD;

            $absent_V += $iTodayAbsentTimeVOD;
            $cancel_V += $iTodayCancelTimeVOD;

            $present_Time += $iTodayPresentTime + $iTodayPresentTimeVOD;
            $absent_Time += $iTodayAbsentTime + $iTodayAbsentTimeVOD;
            $cancel_Time += $iTodayCancelTime + $iTodayCancelTimeVOD;

            $day_pay1 = ($iTodayPresentTime / 10) * $aTodayPayChange1[0];
            $day_pay2 = ($iTodayAbsentTime / 10) * $aTodayPayChange1[1];
            $day_pay3 = ($iTodayCancelTime / 10) * $aTodayPayChange1[2];
            $day_pay4 = ($iTodayPresentTimeVOD / 10) * $aTodayPayChange2['0'];

            //그룹수업에 해당하는 급여 산출(해당 그룹의 급여가 없으면 일반 수업급여로 처리)
            if (!$aTodayPayGroup['0']) 
            {
                $iTodayGroup2Pay = ($present_G2 / 10) *  $aTodayPayChange2['0'];
            } 
            else 
            {
                $iTodayGroup2Pay = ($present_G2 / 10) * $aTodayPayGroup['0'];
            }

            if (!$aTodayPayGroup['1']) 
            {
                $iTodayGroup3Pay = ($present_G3 / 10) *  $aTodayPayChange2['0'];
            } 
            else 
            {
                $iTodayGroup3Pay = ($present_G3 / 10) * $aTodayPayGroup['1'];
            }

            if (!$aTodayPayGroup['2']) 
            {
                $iTodayGroup4Pay = ($present_G4 / 10) *  $aTodayPayChange2['0'];
            } 
            else 
            {
                $iTodayGroup4Pay = ($present_G4 / 10) * $aTodayPayGroup['2'];
            }

            $day_pay5 = ($iTodayAbsentTimeVOD / 10) * $aTodayPayChange2['1'];
            $day_pay6 = ($iTodayCancelTimeVOD / 10) * $aTodayPayChange2['2'];

            $iDayPay = $day_pay1 + $day_pay2 + $day_pay3 + $day_pay4 + $day_pay5 + $day_pay6 + $iTodayGroup2Pay + $iTodayGroup3Pay + $iTodayGroup4Pay + $iTodayLeveltestPay;

            //기본보장 성과급의 경우 오늘 성과급이 기본급에 미치지 못하면 기본급으로 처리(휴일인 경우 처리하지 않음)
//				if ($aPayConfigData['today_type'] == 'z' && $iDayPay < $aPayConfigData['today_fix'] && ($iTimestamp > $iUseHolidayTime || !$this->isItHoliday($sTargetDate))) {
            if ($aPayConfigData['today_type'] == 'z'
                && $iDayPay < $aPayConfigData['today_fix']
                && (!$is_holiday || ($sTargetDate > $iUseHolidayTime && $is_holiday['disabled_thunder'] == '0'))
            ) {	//휴일에 수업을 안하던 때인지 체크 포함
                $iFixPayTotal += $aPayConfigData['today_fix'];	//고정급 표기 추가
                $iFixTimeTotal += $iTodayPresentTime + $iTodayPresentTimeVOD; //총 출석 시간에서 제외시킬 시간 추가
                $iDayPay = $aPayConfigData['today_fix'];
            }

        }

        $iIncentiveSUMResult = 0;
        $iPenaltySUMResult = 0;
        //오늘 하루의 성과급(과거의 날짜에만 급여가 지급된다.)
        if (strtotime($sTargetDate) < $iNowTime) 
        {
            //리뉴얼 선택횟수
            $sum_incentive = $CI->tutor_mdl->sum_tutor_incentive($tu_uid, $sdatetime, $edatetime, " AND in_kind='1' ");
            $iRenewCountResult = $sum_incentive ? (int)$sum_incentive['cnt']:0;
            $iRenewCount += $iRenewCountResult;

            //인센티브
            $sum_incentive = $CI->tutor_mdl->sum_tutor_incentive($tu_uid, $sdatetime, $edatetime, " AND kind IN ('p', 'g') ");
            $iIncentiveSUMResult = $sum_incentive ? (int)$sum_incentive['money']:0;
            $iIncentiveSUM += $iIncentiveSUMResult;

            //패널티
            $sum_incentive = $CI->tutor_mdl->sum_tutor_incentive($tu_uid, $sdatetime, $edatetime, " AND kind='m' AND in_kind!='1' ");
            $iPenaltySUMResult = $sum_incentive ? (int)$sum_incentive['money']:0;
            $iPenaltySUM += $iPenaltySUMResult;
        }

        $sLabelPayType = '';
        //급여타입 표기용
        if ($is_holiday) 
        {
            $sLabelPayType = 'Holiday ';
        } 

        if ($aPayConfigData['today_type'] == 'a' && !$aPayConfigData['today_fix']) 
        {
            $sLabelPayType .= '';
        } 
        else 
        {
            $sLabelPayType .= strtoupper($aPayConfigData['today_type']);
        }

        $aTotalSalary[$sTargetDate] = array(
            'total'     => ($iDayPay + $iIncentiveSUMResult + $iPenaltySUMResult),
            'pay_type'  => $sLabelPayType,
            'salary'    => $iDayPay, 
            'renew'     => $iRenewCountResult,
            'incentive' => $iIncentiveSUMResult, 
            'penalty'   => $iPenaltySUMResult,
            'weekstr'   => date('D', strtotime($sTargetDate))
        );

        $sumTotalSalary['total'] += $aTotalSalary[$sTargetDate]['total'];
        $sumTotalSalary['renew'] += $aTotalSalary[$sTargetDate]['renew'];
        $sumTotalSalary['incentive'] += $aTotalSalary[$sTargetDate]['incentive'];
        $sumTotalSalary['penalty'] += $aTotalSalary[$sTargetDate]['penalty'];
        $sumTotalSalary['salary'] += $aTotalSalary[$sTargetDate]['salary'];

        $iTotalPay += $iDayPay + $iIncentiveSUMResult + $iPenaltySUMResult;

        $sdate = date('Y-m-d',strtotime('+1 day',strtotime($sdate)));
    }

    return array(
        'total'=>array( 
            'present'=>($iPresentTime + $iLeveltestPresentTime + $iPresentG2 + $iPresentG3 + $iPresentG4 + $iPresentG2VOD + $iPresentG3VOD + $iPresentG4VOD + $iPresentTimePhone + $iPresentTimeVOD + $iLeveltestPresentTimeZ),
            'absent'=>($iAbsentTime + $iLeveltestAbsentTime + $iAbsentTimePhone + $iAbsentTimeVOD + $iLeveltestAbsentTimeZ),
            'cancel'=>($iCancelTime + $iCancelTimePhone + $iCancelTimeVOD),
            'leveltest_present'=>($iLeveltestPresentTime + $iLeveltestPresentTimeZ),
            'leveltest_absent'=>($iAbsentTime + $iLeveltestAbsentTimeZ),
            'renew'=>$iRenewCount, 
            'incentive'=>$iIncentiveSUM,
            'penalty'=>$iPenaltySUM,
            'fix_pay'=>$iFixPayTotal,
            'fix_time'=>$iFixTimeTotal,
            'pay'=>$iTotalPay
        ), 
        'dailySalary' => $aTotalSalary,
        'sumTotalSalary' => $sumTotalSalary,
        'fix'=>array('present'=>$iPresentTime, 'absent'=>$iAbsentTime, 'cancel'=>$iCancelTime),		                //기본급 - 수업 시간
        'fix_leveltest'=>array('present'=>$iLeveltestPresentTime, 'absent'=>$iLeveltestAbsentTime),	                //기본급 - 레벨테스트 시간
        'fix_group'=>array('2'=>$iPresentG2, '3'=>$iPresentG3, '4'=>$iPresentG4),					                //기본급 - 그룹수업 시간
        'phone'=>array('present'=>$iPresentTimePhone, 'absent'=>$iAbsentTimePhone, 'cancel'=>$iCancelTimePhone),	//성과급 - 전화수업(Tel, Mobile) 시간
        'vod'=>array('present'=>$iPresentTimeVOD, 'absent'=>$iAbsentTimeVOD, 'cancel'=>$iCancelTimeVOD),			//성과급 - 화상수업(VOD, Skype) 시간
        'leveltest'=>array('present'=>$iLeveltestPresentTimeZ, 'absent'=>$iLeveltestAbsentTimeZ),					//성과급 - 레벨테스트 출석/결석 시간
        'group'=>array('2'=>$iPresentG2VOD, '3'=>$iPresentG3VOD, '4'=>$iPresentG4VOD),								//성과급 - 그룹수업 시간
    );
    
}

function maaltalk_invite_set_url($wiz_tutor, $sc_id)
{
    /*
    state -> 1:SMS발송, 2:초대URL클릭   msg_type-> 1:SMS, 2:카카오알림톡 
    1. 문자전송
    2. 학생 pc url 로그 추가(maaltalk_note_log)
    3. 학생 m  url 로그 추가(maaltalk_note_log)
    4. 소켓룸 입장할 pc_url 리턴
    */

    $CI =& get_instance();
    $CI->load->model('tutor_mdl');
    $schedule = $CI->tutor_mdl->row_schedule_by_sc_id($sc_id);
    $maaltalk = $CI->tutor_mdl->maaltalk_tutor_url_info($wiz_tutor['wt_tu_uid']);

    $explode_url = explode('&id=' , $maaltalk['mntu_tutor_url']);

    $schedule['ws_mobile'] = str_replace("-","",$schedule['ws_mobile']); 
    
    /**
     * 영어이름을 꺼내올 경우 공백이 있는 경우가 있어서 공백을 제거 후 변수에 넣어준다
     */
    $schedule['wm_ename'] = preg_replace("/\s+/", "", $schedule['wm_ename']);

    $student_url = $explode_url[0].'&id='.$schedule['wm_ename'].'['.$schedule['ws_mobile'].']';
    $student_url = urlencode($student_url);

    // 신민트로 보낼 암호화할 param
    // tu_uid||wm_uid||sc_id||state||msg_type||receipt_number||loc
    $m_params = $wiz_tutor['wt_tu_uid'].'$$'.$schedule['wm_uid'].'$$'.$sc_id.'$$'.'1'.'$$'.$schedule['ws_mobile'].'$$2';
    $pc_params = $wiz_tutor['wt_tu_uid'].'$$'.$schedule['wm_uid'].'$$'.$sc_id.'$$'.'1'.'$$-$$1';


    if($_SERVER['HTTP_HOST'] == 'localhost:8000' || $_SERVER['HTTP_HOST'] =='dsapi.mintspeaking.com')
    {
        $domain = 'https://dsm.mintspeaking.com';
    }
    else
    {
        $domain = 'https://story.mint05.com';
    }
    
    $m_url = $domain.'/ssr/live.url.php?url='.$student_url.'&datas='.$m_params;
    $pc_url = $domain.'/ssr/live.url.php?url='.$student_url.'&datas='.$pc_params;

    $m_shorten_url = request_shorten_url($m_url);
    $pc_shorten_url = request_shorten_url($pc_url);


    if($m_shorten_url['code'] == 200 && $pc_shorten_url['code'] == 200){
        $res = array(
            'code'              => '0000',
            'msg'               => 'success',
            'm_url'             => $m_url,
            'pc_url'            => $pc_url,
            'm_shorten_url'     => $m_shorten_url,
            'pc_shorten_url'    => $pc_shorten_url,
            'student_url'       => $student_url,
            // 'student_url_encode'       => $student_url_encode,
            // 'maaltalk'       => $maaltalk,
            // 'schedule'       => $schedule,
        );
    } else {
        $res = array(
            'code'      => '0900',
            'msg'       => 'shorten url fail'
        );
    }

    return $res;
}

function maaltalk_invite_set_url_sub_member($wiz_tutor, $sc_id, $sub_member=array())
{
    /*
    state -> 1:SMS발송, 2:초대URL클릭   msg_type-> 1:SMS, 2:카카오알림톡 
    1. 문자전송
    2. 학생 pc url 로그 추가(maaltalk_note_log)
    3. 학생 m  url 로그 추가(maaltalk_note_log)
    4. 소켓룸 입장할 pc_url 리턴
    */

    $CI =& get_instance();
    $CI->load->model('tutor_mdl');
    $maaltalk = $CI->tutor_mdl->maaltalk_tutor_url_info($wiz_tutor['wt_tu_uid']);
    
    $explode_url = explode('&id=' , $maaltalk['mntu_tutor_url']);
    
    $sub_member['wm_mobile'] = str_replace("-","",$sub_member['wm_mobile']); 
        
    /**
     * 영어이름을 꺼내올 경우 공백이 있는 경우가 있어서 공백을 제거 후 변수에 넣어준다
     */
    $sub_member['wm_ename'] = preg_replace("/\s+/", "", $sub_member['wm_ename']);

    $student_url = $explode_url[0].'&id='.$sub_member['wm_ename'].'['.$sub_member['wm_mobile'].']';
    $student_url = urlencode($student_url);

    // 신민트로 보낼 암호화할 param
    // tu_uid||wm_uid||sc_id||state||msg_type||receipt_number||loc
    $m_params = $wiz_tutor['wt_tu_uid'].'$$'.$sub_member['wm_uid'].'$$'.$sc_id.'$$'.'1'.'$$'.$sub_member['wm_mobile'].'$$2';
    $pc_params = $wiz_tutor['wt_tu_uid'].'$$'.$sub_member['wm_uid'].'$$'.$sc_id.'$$'.'1'.'$$-$$1';


    if($_SERVER['HTTP_HOST'] == 'localhost:8000' || $_SERVER['HTTP_HOST'] =='dsapi.mintspeaking.com')
    {
        $domain = 'https://dsm.mintspeaking.com';
    }
    else
    {
        $domain = 'https://story.mint05.com';
    }

    
    $m_url  = $domain.'/ssr/live.url.php?url='.$student_url.'&datas='.$m_params;
    $pc_url = $domain.'/ssr/live.url.php?url='.$student_url.'&datas='.$pc_params;

    $m_shorten_url = request_shorten_url($m_url);
    $pc_shorten_url = request_shorten_url($pc_url);


    if($m_shorten_url['code'] == 200 && $pc_shorten_url['code'] == 200){
        $res = array(
            'code'              => '0000',
            'msg'               => 'success',
            'm_url'             => $m_url,
            'pc_url'            => $pc_url,
            'm_shorten_url'     => $m_shorten_url,
            'pc_shorten_url'    => $pc_shorten_url,
        );
    } else {
        $res = array(
            'code'      => '0900',
            'msg'       => 'shorten url fail'
        );
    }

    return $res;
}

function request_shorten_url($url)
{
      // 네이버 단축URL Open API 예제
    $client_id = "cqxuhtPciNDrwwjWSU04"; // 네이버 개발자센터에서 발급받은 CLIENT ID
    $client_secret = "6eMhiaUtPe";// 네이버 개발자센터에서 발급받은 CLIENT SECRET
    
    $encText = urlencode($url);
    $postvars = "url=".$encText;
    $url = "https://openapi.naver.com/v1/util/shorturl?url=".$encText ;
    $is_post = false;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, $is_post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = array();
    $headers[] = "X-Naver-Client-Id: ".$client_id;
    $headers[] = "X-Naver-Client-Secret: ".$client_secret;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec ($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close ($ch);

    if($status_code == 200) {
        return json_decode($response, true);
    } else {
        return "Error 내용:".json_decode($response, true);
    }
}

/*
	출석부 활성화 시 본 함수를 진입한다.
	-5월 10일 이후 레벨테스트 받은 학생이 수업을 활성화 시키면 강사에게 인센티브를 넣어준다.
	-5월 10일은 하드코딩으로 체크한다. effective date는 무시하며, 현재설정에서 Lt incentive가 존재할 시 지급한다
	-레벨테스트 해준 강사 전부 인센티브를 지급하며, 학생 한명이 여러번 활성해도 최초 1회만 인센티브를 지급한다.
	-piece Rate(성과급) 에만 적용한다.(pay_type=d)
	-인센티브 항목은 평일급여 정보에만 설정되어있어도 주말,휴일,평일에 공통적용된다
	-어드민에서 출석부 등록 해주는 경우도 있으므로 어드민 소스에도 본 함수가 있다. 어드민은 /ADMINISTRATOR/lesson/call.regi.php 에서만 출석부를 활성화시켜준다
*/
function tutor_leveltest_incentive($wiz_member)
{
    $CI =& get_instance();
    $CI->load->model('leveltest_mdl');
    $CI->load->model('tutor_mdl');

	$leveltest = $CI->leveltest_mdl->get_member_did_leveltest_paytype_d($wiz_member['wm_uid']);

	//해당안되면 리턴
	if(!$leveltest) return;

	$today = date("Y-m-d");

	foreach($leveltest as $tutor)
	{
		//급여정보 불러오기
        $pay_config = tutor_pay_config($tutor['tu_uid'], $today);

		//레벨테스트 인센티브항목이 설정되어 있는지 체크한다
		if($pay_config['pay_level_incentive'] > 0)
		{
			//해당강사에게 해당학생에 대한 레벨테스트 인센티브 지급내역 있는지 확인하여 있으면 패스
			$check_exist = $CI->tutor_mdl->check_mint_incentive_by_tuuid_uid_kind_inyn($tutor['tu_uid'], $wiz_member['wm_uid'], 'lti', 'y');

			//레벨테스트 진행해준 강사들에게 인센티브 데이터를 넣어준다.
			if(!$check_exist)
			{
                $incentive_param = [
                    'tu_uid'     => $tutor['tu_uid'],
                    'tu_id'      => $tutor['tu_id'],
                    'tu_name'    => $tutor['tu_name'],
                    'lesson_id'  => $tutor['le_id'],
                    'uid'        => $wiz_member['wm_uid'],
                    'name'       => $wiz_member['wm_name'],
                    'money'      => $pay_config['pay_level_incentive'],
                    'in_kind'    => 'lti',
                    'in_yn'      => 'y',
                    'regdate'    => date("Y-m-d H:i:s"),
                ];
                $CI->tutor_mdl->insert_tutor_incentive($incentive_param);
			}
		}
	}
}

