<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Banner extends _Base_Controller {


    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('Asia/Seoul');
        $this->load->library('form_validation');
    }

    public function list_()
    {
        $return_array = array();

        $request = array(
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mp.nidx",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
            "sec_order_field" => trim($this->input->post('sec_order_field')),
            "sec_order" => trim($this->input->post('sec_order')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        $current_time = strtotime(date('Y-m-d'));
        $where = " WHERE NSTARTDATE < '".$current_time."' AND NENDDATE > '".$current_time."' AND mp.szview_mobile = 'Y'";
        $limit = NULL;
        $order = NULL;
        
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order_tmp = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        $order = ($request['sec_order_field'] && $request['sec_order']) ? sprintf($order_tmp." , %s %s", $request['sec_order_field'], $request['sec_order']) : $order_tmp."";

        $this->load->model('banner_mdl');
        $result = $this->banner_mdl->list_banner($where, $order, $limit);

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
    }

    
    public function clickcount()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim($this->input->post('wiz_id')),
            "popup_nidx" => trim($this->input->post('popup_nidx')),
            "category" => trim($this->input->post('category')),
            "is_app" => trim($this->input->post('is_app')),   // pc, mobile, app
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_member = base_get_wiz_member();

        $banner_click_param = [
            'popup_nidx' => $request['popup_nidx'],
            'click_gubun' => strtoupper($request['is_app']),
            'category' => $request['category'],
            'click_useragent' => $_SERVER['HTTP_USER_AGENT'],
            'click_ip' => $_SERVER['REMOTE_ADDR'],
            'uid' => $wiz_member ? $wiz_member['wm_uid']:0,
            'regdate' => date('Y-m-d H:i:s')
        ];

        $this->load->model('banner_mdl');
        $result = $this->banner_mdl->insert_banner_click_count($banner_click_param);

        if(!$result)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "성공";
        echo json_encode($return_array);
        exit;
    }

}








