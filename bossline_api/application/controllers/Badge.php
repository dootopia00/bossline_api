<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Badge extends _Base_Controller {
    public $upload_path_badge = ISTESTMODE ? 'test_upload/attach/badge/':'attach/badge/';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('badge_mdl');
        $this->load->library('form_validation');

    }

    public function list_()
    {
        $wiz_member = base_get_wiz_member();
        // wiz_id, authorization 없이 요청하면 리스트만 리턴
        if($this->input->post('wiz_id') && $this->input->post('authorization') && !$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }
        
        $where = " WHERE wb.type != 'admin' ORDER BY wb.id";
        $join_where = ' AND uid='.(int)$wiz_member['wm_uid'];
        $list = $this->badge_mdl->list_badge($where,$join_where);
        foreach($list as $key=>$val)
        {
            $list[$key]['img'] = Thumbnail::$cdn_default_url . '/' . $this->upload_path_badge . $val['img'];
            $list[$key]['img_big_on'] = Thumbnail::$cdn_default_url . '/' . $this->upload_path_badge . $val['img_big_on'];
            $list[$key]['img_big_off'] = Thumbnail::$cdn_default_url . '/' . $this->upload_path_badge . $val['img_big_off'];
        }
        $return_array['data']['list'] = $list;
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        echo json_encode($return_array);
        exit;
    }

    
    public function change_badge()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "badge_id" => trim(strtolower($this->input->post('badge_id'))),
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
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }
        
        $result_wiz_member = $this->badge_mdl->update_use_badge($request['badge_id'],$wiz_member['wm_uid']);

        if($result_wiz_member < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
        }
        else
        {
            $icon = member_get_icon($result_wiz_member);
            
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "뱃지가 변경됐습니다.";
            $return_array['data'] = $icon;
        }
        
        echo json_encode($return_array);
        exit;
    }
    
    public function change_trophy()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "tropy_ut_idx" => $this->input->post('tropy_ut_idx') ? $this->input->post('tropy_ut_idx') : null,
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
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        //회원 트로피 정보 업데이트
        $result = $this->member_mdl->update_user_tropy($wiz_member['wm_uid'], $request['tropy_ut_idx']);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
        }
        else
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "트로피가 변경됐습니다.";
        }
        
        echo json_encode($return_array);
        exit;
    }


    //AHOP 북마크 등록/해제
    public function insert_ahop_badge()
    {
        $return_array = array();

        $request = array(
            'wiz_id'            => trim($this->input->post('wiz_id')) ? trim($this->input->post('wiz_id')) : null,
            "authorization"     => trim($this->input->post('authorization')),
            "type"              => $this->input->post('type') ? $this->input->post('type') : NULL,
            "type2"             => $this->input->post('type2') ? $this->input->post('type2') : NULL,
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

        
        $this->load->model('badge_mdl');
        $where_badge = " WHERE type = '{$request['type']}'";
        if($request['type2']) $where_badge .= " AND type2 = '{$request['type2']}'";

        // 존재하는 뱃지인지 체크
        $badge = $this->badge_mdl->get_badge($where_badge);
        if(!$badge){
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 뱃지가 없습니다.";
            echo json_encode($return_array);
            exit;
        }


        // 이미 받은 뱃지인지 체크
        $where_badge2 = " WHERE uid='{$wiz_member['wm_uid']}' AND wb.type = '{$request['type']}'";
        if($request['type2']) $where_badge2 .= " AND type2 = '{$request['type2']}'";
        $badge_user = $this->badge_mdl->get_user_badge($where_badge2, '');

        if($badge_user){
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0202";
            $return_array['data']['err_msg'] = "이미 지급된 뱃지입니다.";
            echo json_encode($return_array);
            exit;
        }


        // 시험을 정말 완료했는지 체크
        $this->load->model('book_mdl');
        $where_book = ' ';
        if($request['type'] == 'AHOP') $where_book .= " AND book_name LIKE '%{$request['type2']}%'";
        $books_ahop = $this->book_mdl->list_wiz_book_ahop_result($request['type'], $where_book);
        $books_ahop_count = $this->book_mdl->list_count_wiz_book_ahop_result($request['type'], $where_book);


        $book_ids = array();
        foreach($books_ahop as $book_ahop){
            array_push($book_ids, $book_ahop['book_id']); 
        }

        #AND book_id IN  ('404', '429', '432', '435', '438', '441' ) 
        $where_exam = "AND wbel.book_id IN ('".implode("','",$book_ids)."')";
        $count_exam_log = $this->book_mdl->list_count_ahop_wiz_book_exam_log($wiz_member, $where_exam);

        if($count_exam_log['cnt'] != $books_ahop_count['cnt'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0203";
            $return_array['data']['err_msg'] = "시험을 모두 합격한 뒤에 신청해주세요!";
            echo json_encode($return_array);
            exit;
        }


        $datas = array(
            "uid" => $wiz_member['wm_uid'],
            "badge_id" => $badge['wb_id'],
            "use_yn"=> 'N',
            'regdate' => date("Y-m-d H:i:s"),
        );

        $result = $this->badge_mdl->insert_badge($datas);
        
        if($result < 0) {

            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;

        }else{
    
            //인설트
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "뱃지가 지급됐습니다.";
            echo json_encode($return_array);
            exit;
        }
    }

}








