<?php
defined("BASEPATH") OR exit("No direct script access allowed");

/*
    _Base_Controller 에서 호출

    - 회원인증정보 검증 및 저장 
    - 회원 출석체크 및 등업 체크
    - 관리자 회원계정 로그인 여부처리
    - 회원 최근 접속시간 갱신
*/
function base_init()
{
    $CI =& get_instance();
    
    $req_user_id = $CI->input->post('user_id'); 
    $req_authorization = $CI->input->post('authorization');
    
    /*
        아이디, 토큰 둘다 있을 경우에만 
        - 회원인증정보 검증 및 저장 
        - 회원 출석체크 및 등업 체크
        - 관리자 회원계정 로그인 여부처리
        - 회원 최근 접속시간 갱신
    */
    if($req_user_id && $req_authorization)
    {
        //아이디, 토큰 검증
        $auth_checked = token_user_token_validation($req_user_id, $req_authorization);
        
        //아이디, 토큰 검증값이 정상일 경우
        if($auth_checked['res_code'] == '0000')
        {
    
            $CI->load->model('user_mdl');

            $bl_user = NULL;

            /*
                회원 정보 
                - 로그인시 가져오는 정보와 동일 (동일한 함수 사용)
            */ 
            $wiz_member = $CI->member_mdl->get_wiz_member_by_wiz_id($req_user_id);

            // 회원 정보 저장
            base_set_wiz_member($wiz_member);

            /*
                관리자 회원계정 로그인일 경우
                - 관리자 아이디 저장 (member/trace 함수 통해 관리자 이동 경로 저장) 관리자 회원계정 사용 히스토리 확인 용도 
                - 출석체크 및 등업 체크 로직 제외
                - 회원 최근 접속시간 로직 제외
            */
            if($auth_checked['aid'])
            {
                //관리자 아이디 저장 (member/trace 함수 통해 관리자 이동 경로 저장) 관리자 회원계정 사용 히스토리 확인 용도 
                base_set_login_admin_id($auth_checked['aid']);
            }
            else
            {
                
                /*  
                    회원 출석체크 및 등업 체크
                    - API가 한번에 여러개 호출되기에 특정 API 호출되었을때만 호출 (member/trace) 
                */
                if(preg_match('/member\/trace/',$_SERVER['REQUEST_URI']))
                {
                    base_attendance_check();
                }

                 //회원 최근 접속시간 갱신
                member_set_last_connect($wiz_member['wm_uid']);
            }
        }
        else
        {
            //아이디, 토큰 검증값이 비정상일 경우
            base_set_err_auth_check_msg($auth_checked);
        }

    }

    

}


/* [강사사이트] 강사 인증 정보 저장 */
function base_set_wiz_tutor($wiz_tutor)
{
    $CI =& get_instance();
    $CI->WIZ_TUTOR_DATA = $wiz_tutor;
}

/* [강사사이트] 강사 정보 불러오기 */
function base_get_wiz_tutor()
{
    $CI =& get_instance();
    return $CI->WIZ_TUTOR_DATA;
}

/* 회원 인증 정보 저장 */
function base_set_wiz_member($wiz_member)
{
    $CI =& get_instance();
    $CI->WIZ_MEMBER_DATA = $wiz_member;
}

/* 회원정보 불러오기 */
function base_get_wiz_member()
{
    $CI =& get_instance();
    return $CI->WIZ_MEMBER_DATA;
}

/* 회원 인증 오류시 메시지 */
function base_set_err_auth_check_msg($auth_checked)
{
    $CI =& get_instance();
    $CI->ERR_AUTH_CHECK_MSG = $auth_checked;
}

/* 회원 인증 오류시 메시지 */
function base_get_err_auth_check_msg()
{
    $CI =& get_instance();
    return $CI->ERR_AUTH_CHECK_MSG;
}

/* 회원계정 로그인한 관리자 아이디 저장 */
function base_set_login_admin_id($admin_id)
{
    $CI =& get_instance();
    $CI->LOGIN_ADMIN_ID = $admin_id;
}

/* 회원계정 로그인한 관리자 아이디*/
function base_get_login_admin_id()
{
    $CI =& get_instance();
    return $CI->LOGIN_ADMIN_ID;
}

/*
    출석체크 및 등업 조건 체크
*/
function base_attendance_check()
{
    $wiz_member = base_get_wiz_member();
    
    //오늘 출석했으면 리턴
    if(substr($wiz_member['wm_last_attendance'],0,10) == date('Y-m-d')) return;

    $CI =& get_instance();
    //출석수
    $attendance = $wiz_member['wm_attendance'] + 1;
    $update = [
        'attendance' => $attendance,
        'last_attendance' => date('Y-m-d H:i:s'),
    ];

    // 출석체크 업데이트
    $CI->member_mdl->update_member($update,$wiz_member['wm_wiz_id']);

    $CI->load->model('board_mdl');
    // 게시글수
    $where = " WHERE mb.wiz_id = '".$wiz_member['wm_wiz_id']."' AND (mb.table_code BETWEEN 1100 AND 1199 OR mb.table_code BETWEEN 1300 AND 1399)";
    $count_board = $CI->board_mdl->list_count_board('', $where);
    // 댓글수
    $where = " WHERE mbc.writer_id = '".$wiz_member['wm_wiz_id']."' AND (mbc.table_code BETWEEN 1100 AND 1199 OR mbc.table_code BETWEEN 1300 AND 1399)";
    $count_comment = $CI->board_mdl->list_count_comment('', $where);

    // 등급 리스트
    $grade_list = grade_list();

    $now_grade = $grade_list[$wiz_member['wm_grade']] ? $grade_list[$wiz_member['wm_grade']] : null;
    //$nowGradeStandby = $GradeList[$wiz_member['wm_grade_standby']] ? $GradeList[$wiz_member['wm_grade_standby']]:null;

    foreach($grade_list as $grade)
    {
        // 지금보다 하위 등급은 무시
        if($now_grade !== null && (int)$now_grade['grade'] >= (int)$grade['grade'])
        {
            continue;
        }

        // grade_standby 필드를 출석부 제한해제에서 혼용하고있어서 현재 쓰지않는 등업대기 소스 주석함
        // 대기중 상태인 등급이라면 무시
        /* if($nowGradeStandby !== null && (int)$nowGradeStandby['grade'] == (int)$grade['grade'])
        {
            continue;
        } */
    
        // 등업조건 채웠으면 등업 프로세스 진입
        if($grade['cond_board'] < $count_board['cnt'] && $grade['cond_comment'] < $count_comment['cnt'] && $grade['cond_attendance'] < $attendance)
        {
            switch ($grade['mode'])
            {
                case 'AUTO': // 자동등업
                    grade_auto_upgrade($grade, $now_grade, $wiz_member, array('POINT'=>true));
                    break;
                //case 'MANUAL': // 등업대기
                 //   grade_standby($grade,$wiz_member);
                //    break;
            }
            $now_grade = $grade;
        }            
        
    }

    //퀘스트. 회원가입, 일일출석
    MintQuest::request_batch_quest('4_111', date('Y-m-d'));

}


/*
    [강사사이트] _Teacher 에서 호출
    - 강사인증정보 검증 및 저장 
*/
function base_tutor_init()
{
    $CI =& get_instance();

    $req_tu_id = $CI->input->post('tu_id'); 
    $req_authorization = $CI->input->post('authorization');
    
    /*
        아이디, 토큰 둘다 있을 경우에만 
        - 회원인증정보 검증 및 저장 
    */
    if($req_tu_id && $req_authorization)
    {
        //아이디, 토큰 검증
        $auth_checked = token_tutor_token_validation($req_tu_id, $req_authorization);
        
        //아이디, 토큰 검증값이 정상일 경우
        if($auth_checked['res_code'] == '0000')
        {
    
            $CI->load->model('tutor_mdl');

            $wiz_tutor = NULL;

            /*
                강사 정보 
                - 로그인시 가져오는 정보와 동일 (동일한 함수 사용)
            */ 
            $wiz_tutor = $CI->tutor_mdl->get_wiz_tutor_by_tu_id($req_tu_id);


            // 회원 정보 저장
            base_set_wiz_tutor($wiz_tutor);

            if($auth_checked['aid'])
            {
                //관리자 아이디 저장. 현재 신강사에서 자동로그인한 관리자 아이디로 처리된 부분 로그빼고 없음
                base_set_login_admin_id($auth_checked['aid']);
            }

        }
        else
        {
            //아이디, 토큰 검증값이 비정상일 경우
            base_set_err_auth_check_msg($auth_checked);
        }

    }

    

}




