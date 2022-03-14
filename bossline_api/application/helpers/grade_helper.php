<?php
defined("BASEPATH") OR exit("No direct script access allowed");

/*
* 등급 리스트
*/
function grade_list()
{
    $CI =& get_instance();
    $CI->load->model('grade_mdl');

    $where = ' ORDER BY grade, created_at';
    $gradelist = $CI->grade_mdl->list_grade($where);
    
    $return = [];
    foreach($gradelist as $row){
        $return[$row['id']] = $row;
    }

    return $return;
}

/*
* 자동등급업
*/
function grade_auto_upgrade($grade,$nowgrade,$wiz_member,$options)
{
    $CI =& get_instance();
    $CI->load->model('grade_mdl');
    $CI->load->model('point_mdl');
    
    $update = [
        'grade' => $grade === null ? null:$grade['id'],
        //'grade_standby' => null,
    ];
    // 등급 변경
    $CI->member_mdl->update_member($update,$wiz_member['wm_wiz_id']);

    // 등급 변경되었는데 포인트 지급옵션 있다면 지급
    $point = $grade['point'];
    if($grade !== null && $options['POINT'] === true && $point > 0)
    {
        // 포인트 적립
        $point_msg = '['.$grade['title'].']등업으로 '.$point.'포인트 적립';
        $point_msg = $nowgrade === null ? $point_msg:'['.$nowgrade['title'].'] => '.$point_msg;
        
        $point_param = [
            'uid'       => $wiz_member['wm_uid'],
            'name'      => $wiz_member['wm_name'],
            'pt_name'   => $point_msg,
            'point'     => $point,
            'kind'      => 'u',
            'regdate'   => date('Y-m-d H:i:s'),
            'showYn'    => 'y',
        ];

        $result = $CI->point_mdl->set_wiz_point($point_param);

        // 등급 변경이력
        $param = [
            'uid' => $wiz_member['wm_uid'],
            'grade_id' => $grade ? $grade['id']:null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if($result > 0)
        {
            $param['point_id'] = $result;
            $param['point'] = $point;
        }
        
        $CI->grade_mdl->insert_member_grade_history($param);

    }
    
}

function grade_standby($grade,$wiz_member)
{
    $CI =& get_instance();
    $update = [
        'grade_standby' => $grade['id'],
    ];

    // 등업대기 업뎃
    $CI->member_mdl->update_member($update,$wiz_member['wm_wiz_id']);
}