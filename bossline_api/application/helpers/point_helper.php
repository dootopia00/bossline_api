<?php
defined("BASEPATH") OR exit("No direct script access allowed");


/**
 * 포인트 수업추가 정책
 * 
 * 출석부 포인트로 수업추가 가능여부 :  point_addclass_yn (Y: 가능, N: 불가)
 * 출석부 포인트로 수업추가 불가 이유 :  point_addclass_yn_desc 
 * 
 * - 기업관리
 *  : 각 딜러의 설정에 따라 포인트 허용/비허용
 * - 쿠폰관리
 *  : 각 쿠폰 등록 시 설정에 따라 포인트 허용/비허용 
 * - 블랙리스트
 *  : 관리자 memberlist > Blacklist 등록시 모든 출석부 포인트 사용불가
 * - 장기 연기 출석부는 포인트 사용 불가
 * - 환불이 완료된 출석부는 포인트 사용불가
 * - 현재 수업중일 경우에만 포인트로 수업추가 가능
 *  : lesson_state : (in class : 수업중) 
 * - 1,3개월 출석부 제한해제
 *  : (현재) - 출석부 제한해제 버튼사용 체크된 회원만 2020년4월24일까지 결제된 출석부에 포인트 사용가능
 *  : (예정) - 1월7일부터 제한해제 버튼사용 체크된 회원만 2020년 12월 31일까지 결제된 출석부에 포인트 사용가능
 */
function point_policy_wiz_lesson($list_lesson, $wiz_member)
{
    if(!$list_lesson || !$wiz_member) return NULL;

    $CI =& get_instance();
    $CI->load->model('point_mdl');
    $CI->load->model('lesson_mdl');
    /*
        회원 블랙리스트 여부
        - 블랙리스트 회원은 포인트 수업 변환 불가
        - blacklist 
            :NULL: 차단, 차단해제 이력없음 
            :NULL이 아닌경우 : 차단, 차단해제 이력있음
        - kind : (Y: 블랙리스트 등록, N: 블랙리스트 해제)
    */
    $CI->load->model('member_mdl');
    $blacklist = $CI->member_mdl->blacklist_by_wm_uid($wiz_member['wm_uid']);
        

    for($i=0; $i<sizeof($list_lesson); $i++)
    {
        // 수강명칭 분수 치환
        $list_lesson[$i]['cl_name'] = lesson_replace_cl_name_minute($list_lesson[$i]['cl_name'], $list_lesson[$i]['lesson_gubun']);
        /*
            회원 포인트로 추가수업 가능여부 확인 및 딜러 설정으로 값 초기화
            - 각 딜러의 설정에 따라 포인트 변환 허용/비허용 가능
            - wd_point_addclass_yn  : (Y: 가능, N: 불가)
            - point_addclass_yn : 출석부 포인트로 수업추가 가능여부 (Y: 가능, N: 불가)
            - point_addclass_yn_desc : 출석부 포인트로 수업추가 불가 이유 
        */
        $list_lesson[$i]['point_addclass_yn'] = $wiz_member['wd_point_addclass_yn'];
        $list_lesson[$i]['point_addclass_yn_desc'] = ($wiz_member['wd_point_addclass_yn'] == "Y") ? "" : "해당 회원은 포인트를 수업으로 변환할 수 없습니다.";

        /* 회원 블랙리스트 여부 */
        if($list_lesson[$i]['point_addclass_yn'] == "Y")
        {
            if($blacklist)
            {
                if($blacklist['kind'] == "Y")
                {
                    $list_lesson[$i]['point_addclass_yn'] = "N";
                    $list_lesson[$i]['point_addclass_yn_desc'] = "해당 회원은 포인트를 수업으로 변환할 수 없습니다.";
                }
            }
        }
        
        /* 
            쿠폰 설정시 포인트로 수업추가 사용여부 체크  
            - 출석부 > 수업정보(wiz_class) 쿠폰 설정시 포인트로 수업 추가 사용 가능 여부 체크 
            - point_use : (Y: 가능, N:불가능) 
        */
        if($list_lesson[$i]['point_addclass_yn'] == "Y")
        {
            $coupon = $CI->point_mdl->row_class_coupon_by_cl_id($list_lesson[$i]['cl_id']);
            if($coupon)
            {
                if($coupon['point_use'] == "N")
                {
                    $list_lesson[$i]['point_addclass_yn'] = "N";
                    $list_lesson[$i]['point_addclass_yn_desc'] = "해당 출석부는 포인트를 수업으로 변환할 수 없습니다.";
                }
            }
        }
        
        /* 
            장기 연기 출석부는 포인트 사용 불가
            - tu_uid : 158 (장기연기)
        */
        if($list_lesson[$i]['point_addclass_yn'] == "Y")
        {
            if($list_lesson[$i]['tu_uid'] == '158')
            {
                $list_lesson[$i]['point_addclass_yn'] = "N";
                $list_lesson[$i]['point_addclass_yn_desc'] = "장기연기 중인 출석부는 포인트를 수업으로 변환할 수 없습니다.";
            }
        }

        /* 
            환불이 완료된 출석부는 포인트 사용 불가
            - refund_ok : Y (환불완료)
        */
        if($list_lesson[$i]['point_addclass_yn'] == "Y")
        {
            if($list_lesson[$i]['refund_ok'] == 'Y')
            {
                $list_lesson[$i]['point_addclass_yn'] = "N";
                $list_lesson[$i]['point_addclass_yn_desc'] = "환불이 완료된 출석부는 포인트를 수업으로 변환할 수 없습니다.";
            }
        }
        
        /*
            현재 수업중일 경우에만 포인트로 수업 추가 가능
            - lesson_state : (in class : 수업중) 
        */
        if($list_lesson[$i]['point_addclass_yn'] == "Y")
        {
            if(strtolower($list_lesson[$i]['lesson_state']) != "in class")
            {
                $list_lesson[$i]['point_addclass_yn'] = "N";
                $list_lesson[$i]['point_addclass_yn_desc'] = "현재 수업 중이 아닌 출석부는 포인트를 수업으로 변환할 수 없습니다.";
            }
        }

        /* 
            1,3개월 출석부 제한해제 
            - 1월7일부터 제한해제 버튼사용 체크된 회원만 2020년 12월 31일까지 결제된 출석부에 포인트 사용가능
            - cl_month : 출석부 개월수
            - wp_order_no : 주문번호 (년월일 6자리 + 랜덤 8자리)
            - wm_grade_standby : 2 (1,3개월 포인트 수업추가 출석부 제한 해제권한)
            - 테이블 wiz_member_correct_gift에 출석부 해제정보가 들어있다.
            
            2021-02-25일 부로 출석부 제한 없어져서 주석
        */
        /* if($list_lesson[$i]['point_addclass_yn'] == "Y")
        {
            if($list_lesson[$i]['cl_month'] == '1' || $list_lesson[$i]['cl_month'] == '3')
            {
                // 출석부 생성일이 2020년 12월 31일 이후인 출석부 
                if($list_lesson[$i]['wp_order_no'] > "201231"."99999999")
                {
                    $list_lesson[$i]['point_addclass_yn'] = "N";
                    $list_lesson[$i]['point_addclass_yn_desc'] = "해당 출석부는 포인트를 수업으로 변환할 수 없습니다.";
                }

                // 출석부 생성일이 2020년 12월 31일 이전인 출석부일지라도 출석부 제한해제가 되어있지 않다면 포인트로 수업추가 불가 
                if($list_lesson[$i]['wp_order_no'] < "201231"."99999999")
                {
                    if($wiz_member['wm_grade_standby'] != "2")
                    {
                        $list_lesson[$i]['point_addclass_yn'] = "N";
                        $list_lesson[$i]['point_addclass_yn_desc'] = "해당 출석부는 포인트를 수업으로 변환할 수 없습니다.";
                    }
                    
                    // 해제권한을 가지고 있더라도 실제로 출석부 제한해제를 해야 포인트로 수업추가 가능하다.
                    $check_permit_lesson = $CI->lesson_mdl->checked_has_permission_add_class($list_lesson[$i]['lesson_id']);

                    if(!$check_permit_lesson)
                    {
                        $list_lesson[$i]['point_addclass_yn'] = "N";
                        $list_lesson[$i]['point_addclass_yn_desc'] = "해당 출석부는 제한해제된 상태가 아닙니다.";
                    }
                }
            
            }
        } */

    }

    return $list_lesson;

}

function set_point_category_name($type)
{
    $txt = null;

    switch($type)
    {
        case null : $txt = "";        break;
        case 'a'  : $txt = "회원가입";        break;
        case 'aa' : $txt = "게시판 이벤트";        break;
        case 'b'  : $txt = "Cafe(얼철딕, 영자신문, 브레인워시 등..)";   break;
        case 'c'  : $txt = "10% 적립이벤트";break;
        case 'd'  : $txt = "영어첨삭 장려";  break;
        case 'e'  : $txt = "수업체험후기";   break;
        case 'f'  : $txt = "친구추천";   break;
        case 'g'  : $txt = "시스템이상";   break;
        case 'h'  : $txt = "할인대신 포인트 지급";   break;
        case 'i'  : $txt = "Absent or Cancel";   break;
        case 'j'  : $txt = "과제물 포인트 지급";   break;
        case 'k'  : $txt = "기타";   break;
        case 'kg' : $txt = "채택";   break;
        case 'l'  : $txt = "댓글 등록 포인트";   break;
        case 'm'  : $txt = "댓글 등록 추가 포인트";   break;
        case 'n'  : $txt = "소셜회원가입 후 상세정보 업데이트";   break;
        case 'o'  : $txt = "영자신문 해석하기 이벤트";   break;
        case 'p'  : $txt = "설문지 등록";   break;
        case 'r'  : $txt = "게시글 추천/댓글 추천";   break;
        case 'q'  : $txt = "수업을 포인트로 전환";   break;
        case 't'  : $txt = "얼철딕 게시글 등록";   break;
        case 'v'  : $txt = "얼철딕 커멘드 등록";   break;
        case 'w'  : $txt = "포인트로 수업 하루 연기하기";   break;
        case 'x'  : $txt = "게시물로 얻은 포인트";   break;
        case 'y'  : $txt = "수업방식 변경";   break;
        case 'z'  : $txt = "댓글로 얻은 포인트";   break;
        case 'qu' : $txt = "퀘스트 완료 보상";   break;
        case 'sl' : $txt = "딕테이션 해결사";   break;
        case 'cp' : $txt = "쿠폰 포인트";   break;
        case 'R'  : $txt = "영상이벤트";   break;
        case 'u'  : $txt = "회원 등업 포인트";   break;
        case 'Z'  : $txt = "영문법아작내기";   break;
        case '1'  : $txt = "영어첨삭/NS";   break;
        case '2'  : $txt = "기존 스케줄 수업추가[사용]";   break;
        case '3'  : $txt = "새로운 스케줄 수업추가[사용]";   break;
        case '4'  : $txt = "포인트양도[사용]";   break;
        case '5'  : $txt = "기타[사용]";   break;
        case '6'  : $txt = "ahop";   break;
        case '10' : $txt = "AHOP STEP";   break;
    }

    return $txt;
}
?>