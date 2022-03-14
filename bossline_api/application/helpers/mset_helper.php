<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// 엠셋 무료대상자인지 체크. 1:무료대상자, 0:무료대상아님
function mset_check_free($wm_uid, $wm_did)
{
    $CI =& get_instance();

    $CI->load->model('mset_mdl');
    $CI->load->model('lesson_mdl');

    $mset_date = NULL;
    $is_free = 0;

    /*  
        60일마다 1회씩 무료 참여 가능 
        취소한 경우도 체크해야한다
    */

    $mset_row = $CI->mset_mdl->check_is_freetest($wm_uid);
    $mset_date = $mset_row ? $mset_row['startday']:null;

    if(!$mset_date || $mset_date == '0000-00-00' || strtotime(substr($mset_date, 0, 10)) < strtotime(date("Y-m-d")." -60 days")) 
    {
        // 16은 본사, 17은 구민트 , 딜러 id 17 구민트가 어떤걸 의미하는지를 몰라서 우선 넣어놈
        /* if($wm_did == '16' || $wm_did == '17')
        {
            $checked_paid_lesson = $CI->lesson_mdl->checked_paid_lesson($wm_uid);
        }
        else
        {
            $checked_paid_lesson = $CI->lesson_mdl->checked_paid_lesson_dealer_id($wm_uid);
        }
        $is_free = $checked_paid_lesson['cnt'] > 0 ? 1:0; */

        //이기범과장님 요청으로 유료수강안해도 무료엠셋볼수있도록 수정
        $is_free = 1;
    }

    return $is_free;
}

?>
