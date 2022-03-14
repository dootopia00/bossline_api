<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Event extends _Base_Controller {


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
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "e.e_order",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "ASC",
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        $where = " WHERE e.e_id != '' AND e.e_use = 'y' AND e.e_show = 'y'";
        $order = null;
        $limit = null;

        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }

        $this->load->model('event_mdl');
        $result = $this->event_mdl->list_event($where, $order, $limit);

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

    public function view()
    {
        $return_array = array();

        $request = array(
            "e_id" => trim($this->input->post('e_id')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        $where = " WHERE e.e_id != '' AND e.e_use = 'y' AND e.e_show = 'y' AND e.e_id = '{$request['e_id']}'";
        $order = " ORDER BY e.e_order ASC";
        $limit = null;

        $this->load->model('event_mdl');
        $result = $this->event_mdl->view_event($where);

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
        $return_array['data'] = $result;
        echo json_encode($return_array);
        exit;
    }


    /* 말톡노트 베타테스트 참여 신청 */
    public function beta_maaltalk_note()
    {
        $return_array = array();

        $request = array(
            "wm_uid" => trim($this->input->post('wm_uid')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('event_mdl');

        $param = array(
            "wm_uid" => $request['wm_uid'],
            "regdate" => date("Y-m-d H:i:s")
        );

        $result = $this->event_mdl->beta_maaltalk_note($param);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "베타 테스트 참여 신청 완료";
        echo json_encode($return_array);
        exit;



    }

    
    /* 유효한 코드인지 체크 */
    public function sms_promotion_check()
    {
        $return_array = array();

        $request = array(
            "code" => trim($this->input->post('code')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('payment_mdl');

        $code = (new OldEncrypt('(*&DHajaan=f0#)2'))->decrypt($request['code']);
        $code = explode('||',$code);
        $uid = $code[0];
        $sp_list_id = $code[1];

        $sms_promotion_info = payment_sms_promotion_info('', $request['code']);

        if(!$sms_promotion_info)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "종료된 프로모션입니다.";
            echo json_encode($return_array);
            exit;
        }

        $param = [
            'viewdate' => date('Y-m-d H:i:s')
        ];
        $where = [
            'uid' => $uid,
            'sp_list_id' => $sp_list_id,
        ];
        $this->payment_mdl->update_sms_promotion_log($param, $where);
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        echo json_encode($return_array);
        exit;

    }

    /* 
        이벤트상품 정보
    */
    public function event_goods()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "event_code" => (int)($this->input->post('event_code')),
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

        $this->load->model('goods_mdl');
        $this->load->model('lesson_mdl');
        $this->load->model('payment_mdl');

        $goods = $this->goods_mdl->row_event_info_by_e_id($request['event_code']);
        $check_err_msg = payment_valid_event_goods($wiz_member, $goods);

        if($check_err_msg !='')
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0813';
            $return_array['data']['err_msg'] = $check_err_msg;
            echo json_encode($return_array);
            exit;
        }

        $goods_info = [];
        $event_goods_gubun = $this->goods_mdl->event_goods_groupby_gubun($request['event_code']);

        if($event_goods_gubun)
        {
            foreach($event_goods_gubun as $e_goods)
            {
                $info = [];
                $info['lesson_gubun'] = $e_goods['l_gubun'] =='T' && $e_goods['l_event']=="c" ? 'W':$e_goods['l_gubun'];
                $info['lesson_gubun_str'] = lesson_gubun_to_str($e_goods['l_gubun']);
                
                $g_list = $this->goods_mdl->event_goods_list($request['event_code'], $e_goods['l_gubun']);

                if($g_list)
                {
                    foreach($g_list as $key=>$val)
                    {
                        $g_list[$key]['mg_l_time'] = lesson_replace_cl_name_minute($val['mg_l_time'], $e_goods['l_gubun'], true);
                    }
                }

                $info['goods_list'] = $g_list;
                $goods_info[$info['lesson_gubun']] = $info;
            }
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['goods_info'] = $goods_info;      
        $return_array['data']['event_finish'] = $goods['me_e_on'] =='y' ? 0:1;
        
        echo json_encode($return_array);
        exit;
    }

}








