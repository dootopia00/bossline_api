<?php
defined("BASEPATH") OR exit("No direct script access allowed");



function notify_comment_special_insert($table_code, $mb_unq, $co_unq, $wm_uid)
{
    /*
        CLI API 호출
        비동기처리
    */
    $command = "php -f ".$_SERVER['DOCUMENT_ROOT']."/index.php _batch/comment_special_insert_notify \"".$table_code."\" \"".$mb_unq."\" \"".$co_unq."\" \"".$wm_uid."\" > /dev/null 2>/dev/null &";

    exec($command);
}

function notify_comment_insert($table_code, $mb_unq, $co_unq, $wm_uid, $co_fid)
{
    /*
        CLI API 호출
        비동기처리
    */
    $command = "php -f ".$_SERVER['DOCUMENT_ROOT']."/index.php _batch/comment_insert_notify \"".$table_code."\" \"".$mb_unq."\" \"".$co_unq."\" \"".$wm_uid."\" \"".$co_fid."\" > /dev/null 2>/dev/null &";

    exec($command);
}

/* 카카오 알림톡 */
function notify_send_sms($wm_uid, $atalk_code, $sms_id)
{
    $command = "php -f ".$_SERVER['DOCUMENT_ROOT']."/index.php _batch/notify_send_sms \"".$wm_uid."\" \"".$atalk_code."\" \"".$sms_id."\" > /dev/null 2>/dev/null &";

    exec($command);
}

/* 알림 내용 HTML태그 제거 */
function notify_list_strip_tags($list)
{
    if($list)
    {
        for($i=0; $i<sizeof($list); $i++)
        {
            if(isset($list[$i]['content']))
            {
                $list[$i]['content'] = strip_tags($list[$i]['content']);

                $list[$i]['board_name'] = ($list[$i]['board_name']) ? $list[$i]['board_name'] : $list[$i]['table_name'];
            }
            
        }
    }

    return $list;
}  
?>
