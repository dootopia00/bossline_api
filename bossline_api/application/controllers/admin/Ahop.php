<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/admin/_Admin_Base_Controller.php';

class Ahop extends _Admin_Base_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');

    }

    public function list_()
    {
        $return_array = array();

        $request = array(
            "manager_id"    => trim(strtolower($this->input->post('manager_id'))),
            "authorization" => trim($this->input->post('authorization')),
            "book_id" => $this->input->post('book_id') ? $this->input->post('book_id') : null,   
            "use_yn" => $this->input->post('use_yn') ? $this->input->post('use_yn') : null,   
            "start" => $this->input->post('start') ? $this->input->post('start') : null,   
            "limit" => $this->input->post('limit') ? $this->input->post('limit') : null,
            "order_field" => trim(strtolower($this->input->post('order_field'))) ? trim(strtolower($this->input->post('order_field'))) : "wbe.book_name, wbe.chapter",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );


        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        $where = " WHERE qno = '0' ";
        $limit = "";

        if($request['book_id']) $where .= " AND wbe.book_id = '{$request['book_id']}'";
        if($request['use_yn']) $where .= " AND wbe.use_yn = '{$request['use_yn']}'";

        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        $this->load->model('admin/ahop_mdl', 'a_ahop_mdl');

        $list_cnt = $this->a_ahop_mdl->list_count_exam($where, $order, $limit);
        // if($list_cnt['cnt'] == 0)
        // {
        //     $return_array['res_code']         = '0900';
        //     $return_array['msg']              = "프로세스오류";
        //     $return_array['data']['err_code'] = "0201";
        //     $return_array['data']['err_msg']  = "등록된 시험이 없습니다.";
        //     echo json_encode($return_array);
        //     exit;
        // }

        $result = $this->a_ahop_mdl->list_exam($where, $order, $limit);

        $count = (int)1;

        if($result){

            foreach($result as $key => $value){
    
                if($result[$key]['wbe_book_top']){
                    $book_top = str_replace("★","",$result[$key]['wbe_book_top']);
                    $result[$key]['wbe_replace_book_top'] = $book_top;
                }
    
                if($result[$key]['wbe_qtitle']){
                    $qtitle = explode(': ', $result[$key]['wbe_qtitle']);
                    $result[$key]['wbe_replace_qtitle'] = $qtitle[1];
                }
    
                if($result[$key]){
                    $result[$key]['wbe_number'] = $count;
                }
    
                $count ++;
            }
        }

        
        $categories = $this->a_ahop_mdl->list_exam_category();

        foreach($categories as $key => $value){
            $category[$categories[$key]['wb_book_id']] = $categories[$key]['wb_book_name'];
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['category'] = $category;
        $return_array['data']['list'] = $result;
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        echo json_encode($return_array);
        exit;
    }

    public function ahop_exam_change_time()
    {
        $return_array = array();

        $request = array(
            "manager_id"    => trim(strtolower($this->input->post('manager_id'))),
            "authorization" => trim($this->input->post('authorization')),
            "select_minute" => $this->input->post('select_minute') ? $this->input->post('select_minute') : null,
            "exam_idxs"     => $this->input->post('exam_idxs') ? $this->input->post('exam_idxs') : null,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $datas = array();
        for($i=0; $i < count($request['exam_idxs']); $i++)
        {
            $data = array(
                "ex_id"         => $request['exam_idxs'][$i],
                "remain_time"   => $request['select_minute'],
            );
            array_push($datas, $data);
        }

        $this->load->model('admin/ahop_mdl', 'a_ahop_mdl');
        $result = $this->a_ahop_mdl->update_ahop_exam($datas);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "시험시간이 변경됐습니다.";
            echo json_encode($return_array);
            exit;
        }
    }

    public function ahop_exam_change_use()
    {
        $return_array = array();

        $request = array(
            "manager_id"    => trim(strtolower($this->input->post('manager_id'))),
            "authorization" => trim($this->input->post('authorization')),
            "ex_id"         => $this->input->post('ex_id') ? $this->input->post('ex_id') : null,
            "use_yn"        => $this->input->post('use_yn') ? strtolower($this->input->post('use_yn')) : null,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('admin/ahop_mdl', 'a_ahop_mdl');
        $result = $this->a_ahop_mdl->update_ahop_exam_use($request['ex_id'], $request['use_yn']);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "Changed. 수정 되었습니다.";
            echo json_encode($return_array);
            exit;
        }
    }

    public function view_ahop_exam()
    {
        $return_array = array();

        $request = array(
            "manager_id"    => trim(strtolower($this->input->post('manager_id'))),
            "authorization" => trim($this->input->post('authorization')),
            "ex_id"         => $this->input->post('ex_id') ? $this->input->post('ex_id') : null,   
            "parent_id"     => $this->input->post('parent_id') ? $this->input->post('parent_id') : null,   
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        $where = " WHERE ex_id != '{$request['ex_id']}' AND parent_id = '{$request['parent_id']}' AND qno > '0' ";

        $this->load->model('admin/ahop_mdl', 'a_ahop_mdl');
        $list_cnt = $this->a_ahop_mdl->list_count_exam($where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "등록된 시험이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $result = $this->a_ahop_mdl->list_exam($where);

        $count = (int)1;
        foreach($result as $key => $value){
            if($result[$key]){
                $result[$key]['wbe_number'] = $count;
            }
            $count ++;
        }

        $where_exam = " WHERE ex_id = '{$request['ex_id']}'";
        $result_exam = $this->a_ahop_mdl->get_exam($where_exam);

        if(!$result_exam)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "등록된 시험이 없습니다.(2)";
            echo json_encode($return_array);
            exit;
        }
        

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['exam'] = $result_exam;
        $return_array['data']['list'] = $result;
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        echo json_encode($return_array);
        exit;
    }

    public function delete_ahop_exam()
    {
        $return_array = array();

        $request = array(
            "manager_id"    => trim(strtolower($this->input->post('manager_id'))),
            "authorization" => trim($this->input->post('authorization')),
            "ex_id"         => $this->input->post('ex_id') ? $this->input->post('ex_id') : null,   
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        $this->load->model('admin/ahop_mdl', 'a_ahop_mdl');
        $result = $this->a_ahop_mdl->delete_wiz_book_exam($request['ex_id']);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "시험이 삭제되었습니다.";
        echo json_encode($return_array);
        exit;
    }

    public function get_ahop_chapter_info()
    {
        $return_array = array();

        $request = array(
            "manager_id"    => trim(strtolower($this->input->post('manager_id'))),
            "authorization" => trim($this->input->post('authorization')),
            "book_id"       => $this->input->post('book_id') ? $this->input->post('book_id') : null,   
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        $this->load->model('admin/ahop_mdl', 'a_ahop_mdl');
        $lists = $this->a_ahop_mdl->list_ahop_exam_select_info($request['book_id']);

        if(!$lists)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "등록된 시험이 없습니다. Initail 부터 등록해주세요.";
            echo json_encode($return_array);
            exit;
        }

        $datas_step = array();

        
        foreach($lists as $list){
            
            // echo gettype($list['wbe_step']);
            array_push($datas_step, (int)$list['wbe_step']);
        }

        // if(in_array('Fi', $datas_step)){

        //     $return_array['res_code']         = '0900';
        //     $return_array['msg']              = "프로세스오류";
        //     $return_array['data']['err_code'] = "0201";
        //     $return_array['data']['err_msg']  = "해당 시험은 Final 까지 작성돼있습니다.";
        //     echo json_encode($return_array);
        //     exit;
        // }

        $max = max( array_values( $datas_step ));
        

        // if($list['step'])

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['list'] = $datas_step;
        $return_array['data']['max'] = $max;
        echo json_encode($return_array);
        exit;
    }

    public function ahop_curriculum_option()
    {
        $return_array = array();

        $request = array(
            "manager_id"    => trim(strtolower($this->input->post('manager_id'))),
            "authorization" => trim($this->input->post('authorization')),
            // "book_id"       => $this->input->post('book_id') ? $this->input->post('book_id') : null,   
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        $this->load->model('admin/ahop_mdl', 'a_ahop_mdl');
        $list_cnt = $this->a_ahop_mdl->list_count_wiz_book();

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "등록된 교재가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $where = " WHERE book_step = '1' AND useyn = 'y' AND d_id = '16'";
        $order = " ORDER BY sort";
        $result = $this->a_ahop_mdl->list_wiz_book($where, $order);

        // print_r($result);exit;

        foreach($result as $key => $value){
            
            $where_book = "WHERE book_step = '2' AND f_id = '{$value['wb_f_id']}' AND useyn = 'y'";
            $order_book = " ORDER BY sort";
            $result_book = $this->a_ahop_mdl->list_wiz_book($where_book, $order_book);

            $result[$key]['book_list'] = $result_book;
        }

        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['list'] = $result;
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        // print_r($return_array);
        echo json_encode($return_array);
        exit;
    }

    public function ahop_curriculum_option_save()
    {
        $return_array = array();

        $request = array(
            "manager_id"    => trim(strtolower($this->input->post('manager_id'))),
            "authorization" => trim($this->input->post('authorization')),
            "datas"         => $this->input->post('datas') ? $this->input->post('datas') : null,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('admin/ahop_mdl', 'a_ahop_mdl');
        
        $datas = array();
        $count = 0;

        foreach($request['datas'] as $key => $value){

            $exam = '';
            if($value['chapter'] == 'Y')    $exam .= 'C';
            if($value['unit'] == 'Y')       $exam .= 'U';
            if($value['lesson'] == 'Y')     $exam .= 'L';
            if($value['initial'] == 'Y')    $exam .= 'I';

            $datas[$count]['book_id']       = $key;
            $datas[$count]['exam']          = $exam;
            $datas[$count]['exam_point']    = $value['point'];

            $count++;
        }

        $result = $this->a_ahop_mdl->update_wiz_book($datas);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "Changed. 수정 되었습니다.";
            echo json_encode($return_array);
            exit;
        }
    }

    public function write_ahop_exam()
    {
        $return_array = array();

        $request = array(
            "manager_id"    => trim(strtolower($this->input->post('manager_id'))),
            "authorization" => trim($this->input->post('authorization')),
            "datas"         => $this->input->post('datas') ? $this->input->post('datas') : null,
            "datas_parent"  => $this->input->post('datas_parent') ? $this->input->post('datas_parent') : null,
            "book_id"       => $this->input->post('book_id') ? $this->input->post('book_id') : null,
        );
        
        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('admin/ahop_mdl', 'a_ahop_mdl');
        $this->load->model('book_mdl', 'book_mdl');
        
        $book_cnt = $this->book_mdl->list_count_wiz_book_by_book_id($request['book_id']);

        if($book_cnt['cnt'] == 0){

            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "등록된 교재가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        $book_result = $this->book_mdl->row_wiz_book_by_book_id($request['book_id']);

        $datas_parent = array();

        // In, parent 1로우 먼저 등록 -> pk_key 얻기위해서
        foreach($request['datas_parent'] as $key => $value){

            $datas_parent['step']        = $value['step'];
            $datas_parent['chapter']     = $value['chapter'];
            $datas_parent['remain_time'] = $value['remain_time'];
            $datas_parent['comment']     = $value['comment'];
            $datas_parent['qno']         = $value['qno'];
            $datas_parent['a1']          = '';
            $datas_parent['a2']          = '';
            $datas_parent['a3']          = '';
            $datas_parent['a4']          = '';
            $datas_parent['answer']      = '';
            $datas_parent['atxt']        = $value['atxt'];
            $datas_parent['book_id']     = $value['book_id'];
            $datas_parent['book_name']   = $book_result['wb_book_name'];
            $datas_parent['f_id']        = $book_result['f_book_id'];
            $datas_parent['book_top']    = $book_result['f_book_name'];
            $datas_parent['use_yn']      = 'y';

            if($value['step'] == 'In'){
                #   AHOP STEP1 Math : AHOP STEP 1 Math Initial Test
                $datas_parent['qtitle'] = $book_result['wb_book_name']." : ".strip_tags($value['qtitle']);
            }

            if($value['chapter']){
                #   AHOP STEP1 Math § Ch1 : AHOP STEP 1 Math Chapter 1 Test
                $datas_parent['qtitle'] = $book_result['wb_book_name']." § ".'CH'.$value['chapter']." : ".strip_tags($value['qtitle']);
            }

        }

        $result_parent = $this->a_ahop_mdl->insert_wiz_book_exam($datas_parent);

        if($result_parent < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR Parent";
            echo json_encode($return_array);
            exit;
        }


        
        $datas = array();
        $count = 0;

        // 남은 시험문제 batch로 등록
        foreach($request['datas'] as $key => $value){

            $datas[$count]['step']      = $value['step'];
            $datas[$count]['chapter']   = $value['chapter'];
            $datas[$count]['qno']       = $value['qno'];
            $datas[$count]['qtitle']    = $value['qtitle'];
            $datas[$count]['a1']        = $value['a1'];
            $datas[$count]['a2']        = $value['a2'];
            $datas[$count]['a3']        = $value['a3'];
            $datas[$count]['a4']        = $value['a4'];
            $datas[$count]['answer']    = $value['answer'];
            $datas[$count]['atxt']      = $value['atxt'];
            $datas[$count]['book_id']   = $value['book_id'];
            $datas[$count]['book_name'] = $book_result['wb_book_name'];
            $datas[$count]['f_id']      = $book_result['f_book_id'];
            $datas[$count]['book_top']  = $book_result['f_book_name'];
            $datas[$count]['parent_id'] = $result_parent;
            $datas[$count]['use_yn']    = 'y';

            $count++;
        }

        $result = $this->a_ahop_mdl->insert_batch_wiz_book_exam($datas);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "시험이 등록 되었습니다.";
            echo json_encode($return_array);
            exit;
        }
    }

    public function modify_ahop_exam()
    {
        $return_array = array();

        $request = array(
            "manager_id"    => trim(strtolower($this->input->post('manager_id'))),
            "authorization" => trim($this->input->post('authorization')),
            "datas_update"  => $this->input->post('datas') ? $this->input->post('datas') : null,
            "datas_parent"  => $this->input->post('datas_parent') ? $this->input->post('datas_parent') : null,
            "datas_insert"  => $this->input->post('datas_insert') ? $this->input->post('datas_insert') : null,
            "book_id"       => $this->input->post('book_id') ? $this->input->post('book_id') : null,
        );
        
        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('admin/ahop_mdl', 'a_ahop_mdl');
        $this->load->model('book_mdl', 'book_mdl');
        
        $book_cnt = $this->book_mdl->list_count_wiz_book_by_book_id($request['book_id']);

        if($book_cnt['cnt'] == 0){

            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "등록된 교재가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        $book_result = $this->book_mdl->row_wiz_book_by_book_id($request['book_id']);

        $datas_parent = array();

        if($request['datas_parent']){

            // In, parent 1로우 먼저 등록 -> pk_key 얻기위해서
            foreach($request['datas_parent'] as $key => $value){
    
                $parent_id = $value['ex_id'];
    
                $datas_parent['ex_id']       = $value['ex_id'];
                $datas_parent['remain_time'] = $value['remain_time'];
                $datas_parent['comment']     = $value['comment'];
                $datas_parent['step']        = $value['step'];
                $datas_parent['chapter']     = $value['chapter'];
                $datas_parent['qno']         = $value['qno'];
                $datas_parent['a1']          = '';
                $datas_parent['a2']          = '';
                $datas_parent['a3']          = '';
                $datas_parent['a4']          = '';
                $datas_parent['answer']      = '';
                $datas_parent['atxt']        = $value['atxt'];
                $datas_parent['book_id']     = $value['book_id'];
                $datas_parent['book_name']   = $book_result['wb_book_name'];
                $datas_parent['f_id']        = $book_result['f_book_id'];
                $datas_parent['book_top']    = $book_result['f_book_name'];
                $datas_parent['use_yn']      = 'y';
    
                if($value['step'] == 'In'){
                    #   AHOP STEP1 Math : AHOP STEP 1 Math Initial Test
                    $datas_parent['qtitle'] = $book_result['wb_book_name']." : ".strip_tags($value['qtitle']);
                }
    
                if($value['chapter']){
                    #   AHOP STEP1 Math § Ch1 : AHOP STEP 1 Math Chapter 1 Test
                    $datas_parent['qtitle'] = $book_result['wb_book_name']." § ".'CH'.$value['chapter']." : ".strip_tags($value['qtitle']);
                }
    
            }
        }


        $result_parent = $this->a_ahop_mdl->update_wiz_book_exam($datas_parent);

        if($result_parent < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR Parent";
            echo json_encode($return_array);
            exit;
        }


        
        $datas_update = array();
        $count = 0;

        // 기존에 있던 시험문제 batch로 업데이트
        if($request['datas_update']){

            foreach($request['datas_update'] as $key => $value){
    
                $datas_update[$count]['ex_id']     = $value['ex_id'];
                $datas_update[$count]['step']      = $value['step'];
                $datas_update[$count]['chapter']   = $value['chapter'];
                $datas_update[$count]['qno']       = $value['qno'];
                $datas_update[$count]['qtitle']    = $value['qtitle'];
                $datas_update[$count]['a1']        = $value['a1'];
                $datas_update[$count]['a2']        = $value['a2'];
                $datas_update[$count]['a3']        = $value['a3'];
                $datas_update[$count]['a4']        = $value['a4'];
                $datas_update[$count]['answer']    = $value['answer'];
                $datas_update[$count]['atxt']      = $value['atxt'];
                $datas_update[$count]['book_id']   = $value['book_id'];
                $datas_update[$count]['book_name'] = $book_result['wb_book_name'];
                $datas_update[$count]['f_id']      = $book_result['f_book_id'];
                $datas_update[$count]['book_top']  = $book_result['f_book_name'];
                $datas_update[$count]['parent_id'] = $parent_id;
                $datas_update[$count]['use_yn']    = 'y';
    
                $count++;
            }
    
            $result_update = $this->a_ahop_mdl->update_ahop_exam($datas_update);
    
            if($result_update < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }
            
        }


        $datas_insert = array();
        $count_insert = 0;

        if($request['datas_insert']){

            // 추가로 작성된 시험문제 batch로 인설트
            foreach($request['datas_insert'] as $key => $value){
    
                $datas_insert[$count_insert]['step']      = $value['step'];
                $datas_insert[$count_insert]['chapter']   = $value['chapter'];
                $datas_insert[$count_insert]['qno']       = $value['qno'];
                $datas_insert[$count_insert]['qtitle']    = $value['qtitle'];
                $datas_insert[$count_insert]['a1']        = $value['a1'];
                $datas_insert[$count_insert]['a2']        = $value['a2'];
                $datas_insert[$count_insert]['a3']        = $value['a3'];
                $datas_insert[$count_insert]['a4']        = $value['a4'];
                $datas_insert[$count_insert]['answer']    = $value['answer'];
                $datas_insert[$count_insert]['atxt']      = $value['atxt'];
                $datas_insert[$count_insert]['book_id']   = $value['book_id'];
                $datas_insert[$count_insert]['book_name'] = $book_result['wb_book_name'];
                $datas_insert[$count_insert]['f_id']      = $book_result['f_book_id'];
                $datas_insert[$count_insert]['book_top']  = $book_result['f_book_name'];
                $datas_insert[$count_insert]['parent_id'] = $parent_id;
                $datas_insert[$count_insert]['use_yn']    = 'y';
    
                $count_insert++;
            }
    
            $result_insert = $this->a_ahop_mdl->insert_batch_wiz_book_exam($datas_insert);
            
            if($result_insert < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }
            
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "시험이 등록 되었습니다.";
        echo json_encode($return_array);
        exit;
    }

    public function list_category()
    {
        $return_array = array();

        $request = array(
            "manager_id"    => trim(strtolower($this->input->post('manager_id'))),
            "authorization" => trim($this->input->post('authorization')),
            // "book_id"       => $this->input->post('book_id') ? $this->input->post('book_id') : null,   
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        $this->load->model('admin/ahop_mdl', 'a_ahop_mdl');
        $list_cnt = $this->a_ahop_mdl->list_count_wiz_book();

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "등록된 교재가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $where = " WHERE book_step = '1' AND useyn = 'y' AND d_id = '16'";
        $order = " ORDER BY sort";
        $result = $this->a_ahop_mdl->list_wiz_book($where, $order);

        $list = array();
        foreach($result as $key => $value){
            
            $where_book = "WHERE book_step = '2' AND f_id = '{$value['wb_f_id']}' AND useyn = 'y'";
            $order_book = " ORDER BY sort";
            $result_book = $this->a_ahop_mdl->list_wiz_book($where_book, $order_book);
            
            foreach($result_book as $key => $value){

                $list[$result_book[$key]['wb_book_id']] = $result_book[$key]['wb_book_name'];

            }
        }

        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['list'] = $list;
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        // print_r($return_array);
        echo json_encode($return_array);
        exit;
    }


    /**
     * AHOP 테스트 시험 응시 시작
     * -> 해당 시험의 최초 로그 생성
     * -> 로그가 이미있다면(진행중인 시험이 있다면) 해당 로그를 리턴
     * 
     * ex_id : 해당 시험의 아이디
     */
    public function test_ahop_exam()
    {
        $return_array = array();

        $request = array(
            'manager_id'    => trim($this->input->post('manager_id')) ? trim($this->input->post('manager_id')) : null,
            "authorization" => trim($this->input->post('authorization')),
            "ex_id"         => $this->input->post('ex_id')
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('curriculum_mdl');

        /**
         * 시험 문제 정보 불러오기
         */
        $exam_info = $this->curriculum_mdl->ahop_exam_info($request['ex_id']);
        if(!$exam_info)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "시험정보가 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        //해당하는 챕터의 유저 시험 로그 정보
        $exam_log_id = '';
        $exam_log = $this->curriculum_mdl->get_exam_log_by_ex_no_to_uid($exam_info['ex_id'], $request['manager_id']);
        if($exam_log)
        {
            $now_date   = strtotime(date('Y-m-d H:i:s'));
            $el_regdate = strtotime("+".$exam_log['wbe_remain_time']." minutes", strtotime($exam_log['el_regdate']));
            if($el_regdate > $now_date)
            {
                //시험시간이 남았다면 해당 시험 로그 번호를 리턴
                $exam_log_id = $exam_log['el_ex_id'];
            }
            else
            {
                //시험시간이 지났다면 해당 테스트 시험을 삭제한다
                $where = array(
                    'ex_no'      => $request['ex_id'],
                    'uid'        => $request['manager_id'],
                    'reply_name' => 'TEST'
                );
                $this->curriculum_mdl->delete_wiz_book_exam_log($where);
            }
        }
        
        if($exam_log_id == '')
        {
            //진행중인 시험이 없다면 새로 로그를 만들어준다
            //시험 문제들을 랜덤하게 섞고 시험번호를 넣어준다
            $exam_chapter_list = $this->curriculum_mdl->get_ahop_exam_chapter_list_($exam_info['ex_id']);
            if(!$exam_chapter_list)
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0202";
                $return_array['data']['err_msg']  = "시험 목록이 존재하지 않습니다.";
                echo json_encode($return_array);
                exit;
            }

            $params = array(
                'ex_no'      => $exam_info['ex_id'],
                'book_id'    => $exam_info['book_id'],
                'book_name'  => $exam_info['book_name'],
                'uid'        => $request['manager_id'],
                'q_total'    => $exam_chapter_list['cnt'],
                'o_total'    => 0,
                'my_answers' => '',
                'my_exam'    => $exam_chapter_list['ex_list'],
                'regdate'    => date("Y-m-d H:i:s"),
                'reply_name' => "TEST",
                'reply_uid'  => "STATUS",
            );
            $exam_log_id = $this->curriculum_mdl->insert_wiz_book_exam_log($params);
        }

        $return_array['res_code']            = '0000';
        $return_array['msg']                 = "시험문제를 정상적으로 불러왔습니다."; 
        $return_array['data']['exam_log_id'] = $exam_log_id;
        echo json_encode($return_array);
        exit;
    }

    /**
     * AHOP 테스트 시험 현재 진행중인 시험 문제 정보
     * 입력한 정답을 로그에 저장시켜주는 역활도 수행한다
     * 
     * ex_id : 시험 로그 번호
     * ex_no : 현재 시험 문제 아이디(이 값이 있을 경우 이전,다음문제로 와서 수정하는 경우 임)
     * answer : 회원이 입력한 정답
     */
    public function test_ahop_exam_in_progress_info()
    {
        $return_array = array();

        $request = array(
            'manager_id'    => trim($this->input->post('manager_id')) ? trim($this->input->post('manager_id')) : null,
            "authorization" => trim($this->input->post('authorization')),
            "ex_id"         => $this->input->post('ex_id') ? $this->input->post('ex_id') : NULL,
            "ex_no"         => $this->input->post('ex_no') ? $this->input->post('ex_no') : NULL,
            "answer"        => $this->input->post('answer') ? $this->input->post('answer') : NULL,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('curriculum_mdl');

        /**
         * 시험 로그 정보 불러오기
         */
        $exam_log = $this->curriculum_mdl->get_wiz_book_exam_log_by_ex_id($request['ex_id']);
        if(!$exam_log)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "시험정보가 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        //이전,다음 문제정보를 불러와야할 경우(답이 이미 입력된 경우)
        if($request['ex_no'])
        {
            //현재 진행중인 문제 정보
            $info = $this->curriculum_mdl->ahop_exam_info($request['ex_no']);
            if(!$info)
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg']  = "시험정보가 존재하지 않습니다.";
                echo json_encode($return_array);
                exit;
            }

            if($exam_log['el_my_answers']) $my_answers = explode('§', $exam_log['el_my_answers']);
            else                           $my_answers = array();
            $my_exam    = explode(',', $exam_log['el_my_exam']);
            foreach($my_exam as $key => $value)
            {
                if($request['ex_no'] == $value)
                {
                    //이전 문제 아이디 값
                    if(array_key_exists($key-1, $my_exam)) $info['prev'] = $my_exam[$key-1];
                    //다음 문제 아이디 값
                    if(array_key_exists($key+1, $my_exam)) $info['next'] = $my_exam[$key+1];

                    //입력된 정답이 있다면
                    if($request['answer'])
                    {
                        $my_answers[$key]  = $request['answer'];
                        $info['answer_in'] = $request['answer'];
                    }
                    else
                    {
                        $info['answer_in'] = $my_answers[$key];
                    }
                    break;
                }
            }

            //정답을 저장해준다
            if($request['answer'])
            {
                $params = array(
                    'my_answers' => implode('§', $my_answers),
                );
                $where = array('ex_id' => $request['ex_id']);
                $this->curriculum_mdl->update_wiz_book_exam_log($params, $where);

                //현재 진행중인 문제 정보를 다시 불러온다
                $info = $this->curriculum_mdl->ahop_exam_info($request['ex_no']);
            }
        }
        else
        {
            //정답을 저장해준다
            if($request['answer'])
            {
                if($exam_log['el_my_answers']) $my_answers = explode('§', $exam_log['el_my_answers']);
                else                           $my_answers = array();
                $my_answers[] = $request['answer'];
                $params = array(
                    'my_answers' => implode('§', $my_answers),
                );
                $where = array('ex_id' => $request['ex_id']);
                $this->curriculum_mdl->update_wiz_book_exam_log($params, $where);

                //로그정보를 다시 불러온다
                $exam_log = $this->curriculum_mdl->get_wiz_book_exam_log_by_ex_id($request['ex_id']);
            }
            
            /**
             * 현재 진행중인 시험번호를 구한다
             * my_ox or my_answers에 입력되어있으면 해당 문제는 이미 푼것이므로 다음 문제정보를 넘겨준다
             */
            //$my_ox      = explode(',', $exam_log['el_my_ox']);
            $my_answers = $exam_log['el_my_answers'] ? explode('§', $exam_log['el_my_answers']) : array();
            $my_exam    = explode(',', $exam_log['el_my_exam']);
            foreach($my_exam as $key => $value)
            {
                if(count($my_answers) > 0 && array_key_exists($key, $my_answers)) continue;

                //현재 진행중인 문제 정보(정답이 입력되지 않은)
                $info = $this->curriculum_mdl->ahop_exam_info($value);
                //이전 문제 아이디 값
                if(array_key_exists($key-1, $my_exam)) $info['prev'] = $my_exam[$key-1];
                break;
            }
        }

        //회원이 입력한 정답(전체)
        $info['answer'] = $exam_log['el_my_answers'];

        $return_array['res_code']     = '0000';
        $return_array['msg']          = "시험문제를 정상적으로 불러왔습니다."; 
        $return_array['data']['info'] = $info;
        echo json_encode($return_array);
        exit;
    }

    /**
     * AHOP 테스트 시험 완료 후 답안 채점 후 시험결과를 리턴해준다
     * 
     * ex_id : 시험 로그 번호
     */
    public function test_ahop_exam_grade()
    {
        $return_array = array();

        $request = array(
            'manager_id'    => trim($this->input->post('manager_id')) ? trim($this->input->post('manager_id')) : null,
            "authorization" => trim($this->input->post('authorization')),
            "ex_id"         => $this->input->post('ex_id') ? $this->input->post('ex_id') : NULL
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('curriculum_mdl');

        //현재 시험 로그
        $exam_log = $this->curriculum_mdl->get_wiz_book_exam_log_by_ex_id($request['ex_id']);
        if(!$exam_log)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "시험정보가 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        $q_total = $exam_log['el_q_total']; //챕터 총 문제 수
        $o_total = 0; //정답 문제 수
        $wrong_answer = array();
        $my_answers = explode('§', $exam_log['el_my_answers']);
        $my_exam    = explode(',', $exam_log['el_my_exam']);
        foreach($my_exam as $key=>$value)
        {
            if(array_key_exists($key, $my_answers))
            {
                $info = $this->curriculum_mdl->ahop_exam_info($value);
    
                //정답 여부
                if($info['answer'] == $my_answers[$key])
                {
                    $o_total++;
                }
                else
                {
                    $wrong_answer['ex_id'][] = $value;
                    $wrong_answer['answer'][$value] = $my_answers[$key];
                }
                
                $info = null;
            }           
        }

        //테스트 시험이 완료되었다면 테스트 로그를 삭제해준다
        $where = array(
            'ex_id' => $exam_log['el_ex_id']
        );
        $this->curriculum_mdl->delete_wiz_book_exam_log($where);

        // 답안모두 처리 후 틀린 문제를 모두 보여주기
        if(count($wrong_answer) > 0)
        {
            $list = $this->curriculum_mdl->get_test_ahop_exam_wrong_answer_list_(implode(',', $wrong_answer['ex_id']));
            foreach($list as $key=>$value)
            {
                $list[$key]['wrong_answer'] = $wrong_answer['answer'][$value['ex_id']];
            }
            $list['q_total'] = $q_total;
            $list['o_total'] = $o_total;
        }
        else
        {
            $list = "";
        }
        
        $return_array['res_code']     = '0000';
        $return_array['msg']          = "시험이 정상적으로 완료되었습니다."; 
        $return_array['data']['list'] = $list;
        echo json_encode($return_array);
        exit;
    }

    /**
     * AHOP 테스트 시험 오답지우기
     * 테스트 시험용이기때문에 소모하는 것이없다
     * 
     * ex_id : 현재 진행중인 시험 문제 아이디값
     * answer_list : 현재 문제의 항목 리스트(이미 오답지우기를 사용하여 지워진 항목은 제외)
     * 
     * 최종적으로 오답 항목 리턴
     */
    public function test_ahop_exam_hint()
    {
        $return_array = array();

        $request = array(
            'manager_id'    => trim($this->input->post('manager_id')) ? trim($this->input->post('manager_id')) : null,
            "authorization" => trim($this->input->post('authorization')),
            "ex_id"         => $this->input->post('ex_id') ? $this->input->post('ex_id') : NULL,
            "answer_list"   => $this->input->post('answer_list') ? $this->input->post('answer_list') : array(),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $result = array(
            'wrong_answer' => '', //지워질 항목
        );

        $this->load->model('curriculum_mdl');

        //문제 정보
        $exam_info = $this->curriculum_mdl->ahop_exam_info($request['ex_id']);
        if(!$exam_info)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "시험정보가 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        // 오답지우기
        // 보기문항 중 정답을 삭제한다.
        $request['answer_list'] = explode('§', $request['answer_list']);
        $num = array_search($exam_info['answer'], $request['answer_list']);
        if ($num !== false) unset($request['answer_list'][$num]);
        
        if(count($request['answer_list']) <= 0)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0202";
            $return_array['data']['err_msg']  = "더이상 오답 숨기기 찬스를 사용할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        // 배열 중 무작위 값 가져오기
        $rand = array_rand($request['answer_list']);
        $result['wrong_answer'] = $request['answer_list'][$rand];

        $return_array['res_code']     = '0000';
        $return_array['msg']          = "오답 숨기기 찬스를 정상적으로 사용하였습니다.";
        $return_array['data']['info'] = $result;
        echo json_encode($return_array);
        exit;
    }


}








