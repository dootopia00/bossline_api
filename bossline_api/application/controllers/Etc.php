<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Etc extends _Base_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }

    public function mint_manual()
    {
        $return_array = array();

        // 댓글관련 요청값들
        $request = array(
            "start" => trim($this->input->post('start')) ? trim($this->input->post('start')):0,
            "limit" => trim($this->input->post('limit')) ? trim($this->input->post('limit')):10,
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mbc.co_unq",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );

        $this->load->model('board_mdl');

        //$wiz_member = base_get_wiz_member();

        $ck_comm_where = ' WHERE mbc.table_code = 1135 AND mbc.co_thread = "A" ';
        $ck_comm = $this->board_mdl->list_count_comment('',$ck_comm_where);

        $where = " WHERE mb.table_code = '1135' ";
        $order = " order by mb.regdate asc ";
        $limit = '';
        $select_col_content = ', (select count(1) from mint_boards_comment mbc where mbc.mb_unq = mb.mb_unq) as comm_ea';
        $list_board = $this->board_mdl->list_board('', $where, $order, $limit, $select_col_content);
        $list = board_list_writer($list_board, NULL,NULL, NULL, array('content_tag'=>true));

        $limit = "";
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order_tmp = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        $order = ($request['sec_order_field'] && $request['sec_order']) ? sprintf($order_tmp." , %s %s", $request['sec_order_field'], $request['sec_order']) : $order_tmp."";
        
        $comm_list = $this->board_mdl->list_comment($ck_comm_where, $order, $limit);
        $comm_list = board_list_writer($comm_list);

        $return_array['data']['comm_total'] = $ck_comm['cnt'];
        $return_array['data']['list'] = $list;
        $return_array['data']['comm_list'] = $comm_list;
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        echo json_encode($return_array);
        exit;
    }

    
    public function insert_utm()
    {
        $return_array = array();

        $request = array(
            "muu_key" => $this->input->post('muu_key') ? $this->input->post('muu_key') : NULL,
            "ref_key" => $this->input->post('ref_key'),
            "ref_uid" => $this->input->post('ref_uid'),
            "type" => $this->input->post('type'),                             // 1: 뷰(방문횟) , 2:(가입), 3:(레벨테스트), 4:(결제), 5:뷰(방문자)
            "loc" => $this->input->post('loc'),                               // 1: pc, 2: mobile
            "ip" => $_SERVER["REMOTE_ADDR"],
            "regdate" => date("Y-m-d H:i:s"),
        );

        
        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('etc_mdl');
        $checked_utm_url = $this->etc_mdl->checked_count_utm_url($request['muu_key']);

        if($checked_utm_url['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0901';
            $return_array['data']['err_msg'] = '존재하지 않는 utm url 입니다.';
            echo json_encode($return_array);
            exit;
        }


        if($request['type'] == '1')
        {
            $today_log = $this->etc_mdl->checked_today_log($request['muu_key'], $request['ref_key'], $request['loc'], $request['ip']);

            if($today_log)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = '0902';
                $return_array['data']['err_msg'] = '[방문횟수] 오늘 로그가 남아있는 방문자입니다.';
                echo json_encode($return_array);
                exit;
            }
        }

        $checked_utm_url = $this->etc_mdl->insert_utm($request);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "UTM 등록을 성공했습니다.";
        echo json_encode($return_array);
        exit;
    }

}








