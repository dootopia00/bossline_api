<?php
defined("BASEPATH") OR exit("No direct script access allowed");

/*
위메프 요청 URL생성
$params['cp_number'] = 쿠폰번호
$params['req']	= 요청타입 (info:쿠폰정보요청,process:쿠폰사용요청,cancle:쿠폰사용철회)
$params['type'] = 요청문서 (json,xml)
$params['cnt']	= 수량(쿠폰사용/철회일때만 사용) 기본:1
*/
function wemake_request($params, $cp_data, $wemake)
{
    $http_uri = wemake_make_uri($params, $wemake);
    $http_uri.= wemake_make_data($params, $cp_data, $wemake);

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$http_uri);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $output = curl_exec($ch);
    curl_close($ch);

    $WEMAKE_DATA_J = $output;
    $WEMAKE_DATA = json_decode($WEMAKE_DATA_J);
    return $WEMAKE_DATA;
}

// 요청 URL배치
function wemake_make_uri($params, $wemake)
{
    return $wemake['request']['input']['url'].$wemake['request']['input']['type'][$params['type']].$wemake['request']['list'][$params['req']].':';
}

/*
요청 DATA배치
req = 요청타입 (info:쿠폰정보요청,process:쿠폰사용요청,cancle:쿠폰사용철회)
cp_number = 쿠폰번호
*/
function wemake_make_data($params, $cp_data, $wemake)
{
    $retData = array();
    $retData[] = $wemake['user']['cid'];
    if($cp_data['wmc_coupon_type']=='wemake')
    { 
        // 위메프 일경우 딜 아이디
        $retData[] = $cp_data['deal_id'];
    }
    $retData[] = $params['cp_number'];
    if($params['req']=='process' || $params['req']=='cancel')
    {
        $retData[] = $params['cnt'];
    }
    return implode(':',$retData);
}

// 위메프 쿠폰정보 등록
function wemake_log_add($row,$cp_data,$wiz_member,$req='',$err_msg='')
{
    $CI = & get_instance();
    $CI->load->model('coupon_mdl');
    
    foreach($row as $k=>$v)
    {
        $row->{$k} = iconv('UTF-8','EUC-KR',$row->{$k});
    }

    if($req=='process')
    {
        // 요청 성공
        if($row->result=='1')
            $row->coupon_status = '1';
        else
            $row->coupon_status = '2';
        
        $params = array(
            'coupon_status' => $row->coupon_status,
            'message'       => $row->message,
        );
        $CI->coupon_mdl->update_wiz_coupon_log($params,$wiz_member['wm_uid'],$cp_data['wcp_cp_id']);
    }
    else
    {
        if($err_msg != '')
        {
            $row->message = $err_msg;
            $row->coupon_status = '2';
        }
        else
        {
            $row->coupon_status = '1';
        }

        $params = array(
            'uid'                => $wiz_member['wm_uid'],
            'mint_coupon_number' => $cp_data['wcp_cp_id'],
            'mint_coupon_group'  => $cp_data['wmc_coupon_group'],
            'mint_coupon_id'     => $cp_data['wmc_coupon_id'],
            'order_id'           => $row->order_id,
            'order_no'           => $row->order_no,
            'order_name'         => $row->order_name,
            'deal_id'            => $row->deal_id,
            'message'            => $row->message,
            'deal_name'          => $row->deal_name,
            'm_id'               => $row->m_id,
            'company_id'         => $row->company_id,
            'order_mobile'       => $row->order_mobile,
            'coupon_status'      => $row->coupon_status,
            'final_qty'          => $row->final_qty,
            'total_amount'       => $row->total_amount,
            'payment_amount'     => $row->payment_amount,
            'valid_start_time'   => $row->valid_start_time,
            'valid_end_time'     => $row->valid_end_time,
            'coupon_id'          => $row->coupon_id,
            'parent_id'          => $row->parent_id,
            'valid_count'        => $row->valid_count,
            'options'            => $row->options,
            'regdate'            => date('Y-m-d'),
        );
        $CI->coupon_mdl->insert_coupon_log($params);
    }
}

