<?php
defined("BASEPATH") OR exit("No direct script access allowed");


function member_checked_assistant_code($wiz_member) 
{
    $assistant_code = array();
    
    $comment = 'N';
    $recomm = 'N';
    $express = 'N';
    $composition = 'N';
    $describe = 'N';
    $solver = 'N';

    if(isset($wiz_member["wm_assistant_code"])) 
    {
        $assistant_code = explode("*", $wiz_member["wm_assistant_code"]);
        for($i = 0; $i < count($assistant_code); $i++) 
        {
            if($assistant_code[$i] == "comment") $comment = "Y";
            if($assistant_code[$i] == "recomm") $recomm = "Y";
            if($assistant_code[$i] == "express") $express = "Y";
            if($assistant_code[$i] == "1127") $composition = "Y";
            if($assistant_code[$i] == "1129") $describe = "Y";
            // 딕테이션 해결사
            if($assistant_code[$i] == "solver") $solver = "Y";
        }
    }
    

    /* 딕테이션 해결사 도우미 뱃지 여부 체크 */
    $type = 'Dictation';
    $type2 = 'Helper';

    $badge_solver = member_checked_badge($wiz_member['wm_uid'], $type, $type2);
    if($badge_solver)
    {
        $solver = "Y";
    }
    

    $assistant_code1 = array(
        "wm_assistant_comment" => ($comment == "Y") ?  "Y" : "N",
        "wm_assistant_recomm" =>($recomm == "Y") ?  "Y" : "N",
        "wm_assistant_express" => ($express == "Y") ?  "Y" : "N",
        "wm_assistant_1127" => ($composition == "Y") ?  "Y" : "N",
        "wm_assistant_1129" => ($describe == "Y") ?  "Y" : "N",
        "wm_assistant_solver" => ($solver == "Y") ?  "Y" : "N",
    );

    return $assistant_code1;


}



function member_generate_random_Password($length=8, $strength=0) 
{
    $vowels = 'aeuy';
    $consonants = 'bdghjmnpqrstvz';

    if($strength & 1)
    {
        $consonants .= 'BDGHJLMNPQRSTVWXZ';
    }

    if($strength & 2)
    {
        $vowels .= "AEUY";
    }

    if($strength & 4)
    {
        $consonants .= '23456789';
    }

    if($strength & 8)
    {
        $consonants .= '@#$%';
    }

    $password = '';
    $alt = time() % 2;

    for($i=0; $i<$length; $i++)
    {
        if($alt == 1)
        {
            $password .= $consonants[(rand() % strlen($consonants))];
            $alt = 0;
        }
        else
        {
            $password .= $vowels[(rand() % strlen($vowels))];
            $alt = 1;
        }
    }
    return $password;
} 

// 임시 비밀번호 8자리
function member_generate_random_string($length = 8) 
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for($i=0; $i<$length; $i++) 
    {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
}

function member_get_oldmint_autologin_str($uid,$wiz_pw){
    $enc = new OldEncrypt('(*&DHajaan=f0#)2');
    $u = $uid.'||'.$wiz_pw.'||'.time();
    return $enc->encrypt($u);
}

/*
* 유저 아이콘
*/
function member_get_icon($wiz_member)
{
    // 뱃지 우선
    if ($wiz_member['wmb_badge_id']) 
    {
        $icon = array(
            'icon' => (ISTESTMODE ? '/test_upload' : '').'/assets/badge/'.$wiz_member['wb_img'],
            'icon_desc' => $wiz_member['wb_description'],
        );
    }
    // 주니어 회원 아이콘 
    elseif($wiz_member['wm_age'] <= 15)
    {
        $icon = array(
            'icon' => '/assets/icon/junior/junior_s.png',
            'icon_desc' => '우리나라 청소년 만세!',
        );
    }
    // 회원 등급에 따른 아이콘
    elseif($wiz_member['mmg_icon'])
    {
        $icon = array(
            'icon' => (ISTESTMODE ? '/test_upload' : '').'/attach/member/'.$wiz_member['mmg_icon'],
            'icon_desc' => $wiz_member['mmg_description'],
        );
    }

    return $icon;
}

/*
* 유저 display_name
*/
function member_display_name($data)
{
    $display_name = '';
    // 닉네임 우선
    if ($data['nickname']) 
    {
        $display_name = $data['nickname'];
    }
    // 영어이름 
    elseif($data['ename'])
    {
        $display_name = $data['ename'];
    }
    // 이름
    elseif($data['name'])
    {
        $display_name = $data['name'];
    }

    return $display_name;
}

function member_get_block_list($uid)
{
    $CI = & get_instance();
    $CI->load->model('member_mdl');
    $block_member_list = $CI->member_mdl->list_member_block($uid);
    $list = [];
    if($block_member_list)
    {
        foreach($block_member_list as $block)
        {
            $list[] = $block['wm_wiz_id'];
        }
    }

    return $list;
    
}

// 최근접속 갱신
function member_set_last_connect($uid='')
{
    $CI = & get_instance();
    $CI->load->model('member_mdl');

    $param = [
        'regdate' => date('Y-m-d H:i:s'),
        'mobile' => $CI->agent->is_mobile() ? 1:0,
    ];

    if($uid)    // 회원
    {
        $param['uid'] = $uid;
        $CI->member_mdl->replace_last_connect($param);
    }
    else        // 비로그인한 게스트
    {
        $param['ip'] = $_SERVER['REMOTE_ADDR'];
        $CI->member_mdl->replace_last_connect_guest($param);
    }
    
}



// 현재접속자, 오늘 누적접속자 체크
function member_get_last_connect_count()
{
    $CI = & get_instance();
    $CI->load->model('member_mdl');

    // 10분내 접속기록 있는 사람
    $where = " WHERE regdate > '".date('Y-m-d H:i:s',time() - 600)."'";
    $currect_cnt = $CI->member_mdl->count_current_connect($where);

    // 오늘 누적접속자 수
    $today_cnt = $CI->member_mdl->count_current_connect();
    
    return array(
        'currect_cnt' => $currect_cnt,
        'today_cnt' => $today_cnt,
    );
}


// 가입경로 설문조사 작성여부
function member_checked_join_qna($uid)
{
    $CI = & get_instance();
    $CI->load->model('member_mdl');

    $reulst = NULL;
    $reulst = $CI->member_mdl->checked_join_qna($uid);
    
    return ($reulst['cnt'] > 0) ? "Y" : "N";
}


/* 
    회원 뱃지 수여
    type: wiz_badge.type
    type2: wiz_badge.type2 
*/
function member_badge_award($wm_uid, $type, $type2)
{

    $CI = & get_instance();
    
    //뱃지 정보
    $CI->load->model('badge_mdl');
    $badge = $CI->badge_mdl->row_badge_info($type, $type2);

    //뱃지 정보 있을때만 체크  
    if($badge)
    {
        //회원 뱃지 지급 여부 체크 및 지급
        $CI->load->model('member_mdl');
        $CI->member_mdl->checked_member_badge_award($wm_uid, $badge);

    }
    
}
/* 
    회원 뱃지 여부 조회
    type: wiz_badge.type
    type2: wiz_badge.type2 
*/
function member_checked_badge($wm_uid, $type, $type2)
{
    $CI = & get_instance();
    
    //뱃지 정보
    $CI->load->model('badge_mdl');
    $badge = $CI->badge_mdl->row_badge_info($type, $type2);

    //뱃지 정보 있을때만 체크  
    if($badge)
    {
        //회원 뱃지 조회
        $CI->load->model('member_mdl');
        $result = $CI->member_mdl->get_member_badge($wm_uid, $badge['wb_id']);

        return $result['uid'];
    }
    else
    {
        return false;
    }
    
}


/**
 * 날짜 타입 AM, PM 분류
 */
function getTime_PM_AM($date) {
    $result = "";

    $hour = date("H", strtotime($date));
    $minute = date("i", strtotime($date));

    if($hour > 12) {
        $hour = $hour - 12;
        $hour = ($hour < 10) ? "0".$hour : $hour;
        $result = "PM ".$hour.":".$minute;
    } else {
        $result = "AM ".$hour.":".$minute;
    }
    return $result;
}