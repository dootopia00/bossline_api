<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Msg extends _Base_Controller {


    public function __construct()
    {
        parent::__construct();

        date_default_timezone_set('Asia/Seoul');
        $this->load->library('form_validation');
        // $this->upload_path = '../../tmp/note/';
        $this->upload_path = 'attach/note/';

    }

    public function list_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "type" => trim(strtolower($this->input->post('type'))),
            "search_keyword" => trim($this->input->post('search_keyword')),
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mn.id",
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


        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }
        

        /* 회원정보 */
        $this->load->model('member_mdl');

        $search = "";

        /* if($request['type'] != "block" && $request['search_keyword'])
        {
            $search =  " AND match(message) against ('*".$request['search_keyword']."*' IN BOOLEAN MODE)";
        }
        else if($request['type'] == "block" && $request['search_keyword'])
        {
            $search = " AND match(wm.nickname) against('*".$request['search_keyword']."*' IN BOOLEAN MODE) ";
        } */

        if($request['type'] != "block" && $request['search_keyword'])
        {
            $search =  " AND message like '%".$request['search_keyword']."%'";
        }
        else if($request['type'] == "block" && $request['search_keyword'])
        {
            $search = " AND wm.nickname like '%".$request['search_keyword']."%'";
        }
      
        $list_cnt = NULL;
        $this->load->model('msg_mdl');
   
        if($request['type'] == 'receive')
        {
            $list_cnt = $this->msg_mdl->list_count_receive_msg_by_wm_uid($wiz_member['wm_uid'], $search);
        }
        else if($request['type'] == 'send')
        {
            $list_cnt = $this->msg_mdl->list_count_send_msg_by_wm_uid($wiz_member['wm_uid'], $search);
        }
        else if($request['type'] == 'save')
        {
            $list_cnt = $this->msg_mdl->list_count_save_msg_by_wm_uid($wiz_member['wm_uid'], $search);
        }
        else if($request['type'] == 'block')
        {
            $list_cnt = $this->msg_mdl->list_count_block_by_wm_uid($wiz_member['wm_uid'], $search);
        }

        $notice_list_msg = null;
        if($request['start'] == 0)
        {
            // 1페이지에 공지쪽지 있으면 같이 준다.
            $notice_list_msg = $this->msg_mdl->list_receive_msg_by_admin($wiz_member['wm_uid']);

            if($notice_list_msg)
            {
                foreach($notice_list_msg as $key=>$val)
                {
                    $notice_list_msg[$key]['message'] = mb_substr(strip_tags($val['message']),0,100,'utf-8');
                }
            }
        }
        
        if(!($request['type'] == 'receive' && $notice_list_msg))
        {
            if($list_cnt['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
                echo json_encode($return_array);
                exit;
            }
        }

        $list_msg = NULL;

        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }

        if($request['type'] == 'block')
        {
            $request['order_field'] = "mnb.id";
        }

        $order_tmp = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        $order = ($request['sec_order_field'] && $request['sec_order']) ? sprintf($order_tmp." , %s %s", $request['sec_order_field'], $request['sec_order']) : $order_tmp."";

        if($request['type'] == 'receive')
        {
     
            //$list_msg = msg_checked_encode_message($this->msg_mdl->list_receive_msg_by_wm_uid($wiz_member['wm_uid'], $order, $limit, $search));
            $list_msg = $this->msg_mdl->list_receive_msg_by_wm_uid($wiz_member['wm_uid'], $order, $limit, $search);
            if($notice_list_msg)
            {
                $list_msg = array_merge($notice_list_msg, $list_msg ? $list_msg:array());
            }
        }
        else if($request['type'] == 'send')
        {
            //$list_msg = msg_checked_encode_message($this->msg_mdl->list_send_msg_by_wm_uid($wiz_member['wm_uid'], $order, $limit, $search));
            $list_msg = $this->msg_mdl->list_send_msg_by_wm_uid($wiz_member['wm_uid'], $order, $limit, $search);
        }
        else if($request['type'] == 'save')
        {
            //$list_msg = msg_checked_encode_message($this->msg_mdl->list_save_msg_by_wm_uid($wiz_member['wm_uid'], $order, $limit, $search));
            $list_msg = $this->msg_mdl->list_save_msg_by_wm_uid($wiz_member['wm_uid'], $order, $limit, $search);
        }
        else if($request['type'] == 'block')
        {
            $list_msg = $this->msg_mdl->list_block_by_wm_uid($wiz_member['wm_uid'], $order, $limit, $search);
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['notice_cnt'] = $notice_list_msg ? count($notice_list_msg):0;
        $return_array['data']['list'] = $list_msg;
        echo json_encode($return_array);
        exit;
    }

    public function delete_list()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "type" => trim(strtolower($this->input->post('type'))),
            "idx" => $this->input->post('idx'),
            "msg_type" => $this->input->post('msg_type'),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $msg = "";

        /* 회원정보 */
        $this->load->model('member_mdl');
        $wiz_member = $this->member_mdl->get_wm_uid_by_wiz_id($request['wiz_id']);

        $idx = explode(',',$request['idx']);

        $this->load->model('msg_mdl');

        if($request['type'] == 'receive')
        {
            $result = $this->msg_mdl->delete_receive_msg($idx, $wiz_member['wm_uid']);
        }
        else if($request['type'] == 'send')
        {
            $result = $this->msg_mdl->delete_send_msg($idx, $wiz_member['wm_uid']);
        }
        else if($request['type'] == 'save')
        {
            if(!$request['msg_type'])
            {
                $return_array['res_code'] = '0400';
                $return_array['msg'] = "msg_type이 없습니다.";
                echo json_encode($return_array);
                exit;
            }

            $msg_type = explode(',',strtolower($request['msg_type']));

            if(sizeof($msg_type) != sizeof($idx))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['errCode'] = "0701";
                $return_array['data']['errMsg'] = "msg_type 과 idx의 길이가 일치 하지 않습니다.";
                echo json_encode($return_array);
                exit;
            }

            for($i=0; $i<sizeof($msg_type); $i++)
            {
                if($msg_type[$i] == 'send')
                {
                    $result = $this->msg_mdl->delete_save_send_msg($idx[$i], $wiz_member['wm_uid']);
                }
                else if($msg_type[$i] == 'receive')
                {
                    $result = $this->msg_mdl->delete_save_receive_msg($idx[$i], $wiz_member['wm_uid']);

                }

                if($result < 0)
                {
                    $return_array['res_code'] = '0500';
                    $return_array['msg'] = "DB ERROR";
                    echo json_encode($return_array);
                    exit;
                }
            }

        }
        
        

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록에서 삭제하였습니다.";
        echo json_encode($return_array);
        exit;

    }

    public function view()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => $this->input->post('wiz_id'),
            "idx" => trim($this->input->post('idx')),
        );
        
        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('msg_mdl');

        $result = $this->msg_mdl->row_msg_by_wm_uid($request['idx'], $wiz_member['wm_uid']);

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['errCode'] = "0203";
            $return_array['data']['errMsg'] = "해당 게시물이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $save_yn = ($result['sender_uid'] == $wiz_member['wm_uid']) ? $result['sender_save_at'] : $result['receiver_save_at'];

        $where = "";
        
        if(!$result['is_notice'])
        {
            $target_id = ($result['sender_uid'] == $wiz_member['wm_uid']) ? $result['receiver_uid'] : $result['sender_uid']; 
            /* $where = " WHERE mnb.blocker_id = '".$wiz_member['wm_uid']."' AND mnb.blocked_id = '".$target_id."' AND mnb.canceled_at IS NULL";
        
            $checked_block = $this->msg_mdl->chekced_block($where); */
            $checked_block = $this->member_mdl->checked_block_member($wiz_member['wm_uid'], $target_id);
            $checked_block = $checked_block ? 'Y':null;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "쪽지조회성공";
        $return_array['data']['info'] = $result;
        $return_array['data']['block_yn'] = $checked_block;
        $return_array['data']['save_yn'] = ($save_yn) ? "Y" : "N";
        echo json_encode($return_array);
        exit;


        
    }
 
   
    public function save()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "idx" => $this->input->post('idx'),
            "type" => $this->input->post('type'),
            "save_type" => $this->input->post('save_type'),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }


        /* 회원정보 */
        $this->load->model('member_mdl');
        $wiz_member = $this->member_mdl->get_wm_uid_by_wiz_id($request['wiz_id']);


        $this->load->model('msg_mdl');

        $result = NULL;

        if($request['type'] == 'receive')
        {
            if($request['save_type'] == 'save')
            {
                $result = $this->msg_mdl->update_save_receive_msg($request['idx'], $wiz_member['wm_uid']);
            }
            else if($request['save_type'] == 'un_save')
            {
                $result = $this->msg_mdl->update_unsave_receive_msg($request['idx'], $wiz_member['wm_uid']);
            }
        
        }
        else if($request['type'] == 'send')
        {
            if($request['save_type'] == 'save')
            {
                $result = $this->msg_mdl->update_save_send_msg($request['idx'], $wiz_member['wm_uid']);
        
            }
            else if($request['save_type'] == 'un_save')
            {
                $result = $this->msg_mdl->update_unsave_send_msg($request['idx'], $wiz_member['wm_uid']);
            }
        }
      
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = ($request['save_type'] == 'save') ? "쪽지를 보관함으로 이동하였습니다." : "쪽지를 보관 해제하였습니다.";
        echo json_encode($return_array);
        exit;

    }

    public function block()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "idx" => $this->input->post('idx'),
            "type" => trim(strtolower($this->input->post('type'))),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }


        /* 회원정보 */
        $this->load->model('member_mdl');
        $wiz_member = $this->member_mdl->get_wm_uid_by_wiz_id($request['wiz_id']);

        
        
        $this->load->model('msg_mdl');

        $msg = "";
        if($request['type'] == 'canceled')
        {
            $idx = explode(',',$request['idx']);

            $result = $this->msg_mdl->canceled_block($idx, $wiz_member['wm_uid']);
            $msg = "차단목록에서 삭제하였습니다.";
        }
        else
        {
            /* 상대방 회원정보 */
            $blocked_wiz_member = $this->member_mdl->get_wiz_member_by_wm_uid($request['idx']);

            if(!$blocked_wiz_member)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0105";
                $return_array['data']['err_msg'] = "상대방 회원 정보가 없습니다.";
                echo json_encode($return_array);
                exit;
            }
            
            $blocked = array(
                'blocker_id' => $wiz_member['wm_uid'],
                'blocked_id' => $blocked_wiz_member['wm_uid'],
                'created_at' => date('Y-m-d H:i:s')
            );
            
            $result = $this->msg_mdl->blocked_wiz_member($blocked);
            $msg = $blocked_wiz_member['wm_nickname']."님을 차단했습니다.";
        }

       
      
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = $msg;
        echo json_encode($return_array);
        exit;

    }


    public function send()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "receive_id" => $this->input->post('receive_id'),
            "message" => $this->input->post('message'),
            "files" => isset($_FILES["files"]) ? $_FILES["files"] : null,
        );
        
        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원정보 */
        $this->load->model('member_mdl');
        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);

        /*
            회원 블랙리스트 여부
            - 블랙리스트 회원은 포인트 수업 변환 불가
            - blacklist 
                :NULL: 차단, 차단해제 이력없음 
                :NULL이 아닌경우 : 차단, 차단해제 이력있음
            - kind : (Y: 블랙리스트 등록, N: 블랙리스트 해제)
        */
        $blacklist = $this->member_mdl->blacklist_by_wm_uid($wiz_member['wm_uid']);

        if($blacklist)
        {
            if($blacklist['kind'] == "Y")
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0360";
                $return_array['data']['err_msg'] = "쪽지 보내기 권한이 없습니다. 고객센터 실시간요청게시판으로 문의하세요.";
                echo json_encode($return_array);
                exit;
            }
        }

        /* 상대방 회원정보 */
        $receiver_wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['receive_id']);

        if(!$receiver_wiz_member)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0105";
            $return_array['data']['err_msg'] = "수신 회원 정보가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $file_name = NULL;
        $file_name_origin = NULL;

        //s3파일 업로드
        if(isset($request['files']))
        {
            $this->load->library('s3');

            $ext_array = array('xlsx', 'xls', 'doc', 'pdf', 'jpg', 'jpeg', 'png', 'txt');
            $upload_limit_size = 5;

            $res = S3::put_s3_object($this->upload_path, $request['files'], $upload_limit_size, $ext_array);
            
            if($res['res_code'] != '0000')
            {
                echo json_encode($res);
                exit;
            }
            
            $file_name = $res['file_name'];
            $file_name_origin = $res['file_name_origin'];
        }

        $this->load->model('msg_mdl');

        $where = " WHERE mnb.blocker_id = '".$wiz_member['wm_uid']."' AND mnb.blocked_id = '".$receiver_wiz_member['wm_uid']."' AND mnb.canceled_at IS NULL";
        $checked_block = $this->msg_mdl->chekced_block($where);

        $msg = array(
            'sender_type' => 'MEMBER',
            'sender_uid' => $wiz_member['wm_uid'],
            'sender_id' => $wiz_member['wm_wiz_id'],
            'sender_nickname' => isset($wiz_member['wm_nickname']) ? $wiz_member['wm_nickname'] : $wiz_member['wm_name'],
            'receiver_type' => 'MEMBER',
            'receiver_uid' => $receiver_wiz_member['wm_uid'],
            'receiver_id' => $receiver_wiz_member['wm_wiz_id'],
            'receiver_nickname' => $receiver_wiz_member['wm_nickname'],
            'message' => $request['message'],
            'created_at' => date('Y-m-d H:i:s'),
            'receiver_del_at' => ($checked_block) ? date('Y-m-d H:i:s') : null,
            'attached_file' => isset($file_name) ? $file_name : null,
            'attached_file_name' => isset($file_name_origin) ? $file_name_origin : null,
        );

        $result = $this->msg_mdl->send_message($msg);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = $receiver_wiz_member['wm_nickname']."님에게 쪽지를 보냈습니다.";
        echo json_encode($return_array);
        exit;

    }
}








