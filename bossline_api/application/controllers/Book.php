<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Book extends _Base_Controller {


    public function __construct()
    {
        parent::__construct();

        $this->load->library('form_validation');

    }

    public function list_main_book_()
    {
        $return_array = array();

        $request = array(
            "start"             => $this->input->post('start') ? trim($this->input->post('start')):0,
            "limit"             => $this->input->post('limit') ? trim($this->input->post('limit')):100,
            "order_field"       => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "book_id",
            "order"             => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
            "sec_order_field"   => trim($this->input->post('sec_order_field')),
            "sec_order"         => trim($this->input->post('sec_order')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('book_mdl');

        if($request['order_field'])
        {
            $orderby = sprintf(' ORDER BY %s %s ',$request['order_field'],$request['order']);;
        }

        if($request['sec_order_field'])
        {
            $orderby.= sprintf(', %s %s',$request['sec_order_field'],$request['sec_order']);
        }

        $limit = sprintf(' LIMIT %s , %s',$request['start'], $request['limit']);
        //추후커리큘럼테이블 변경
        $book_list = $this->book_mdl->list_main_book($orderby.$limit);        

        if($book_list)
        {
            $retrun_array['data']['list'] = $book_list;
            $retrun_array['res_code'] = '0000';
            $retrun_array['msg'] = "";
            echo json_encode($retrun_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

    }

    public function list_book_step2_(){
        $return_array = array();

        $request = array(
            "f_id" => $this->input->post('f_id') 
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('book_mdl');

        $book_list = $this->book_mdl->list_select_step2($request['f_id']);        

        if($book_list)
        {
            $return_array['data']['list'] = $book_list;
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
    }

    // 스텝1,2전부 포함된 리스트
    public function list_search_book_()
    {
        $return_array = array();

        $this->load->model('book_mdl');

        $book_list = $this->book_mdl->list_book();
        $book_data = [];
        
        foreach($book_list as $book)
        {
            if($book['wb_book_step'] =='1')
            {
                $book_data[$book['wb_f_id']] = $book;
            }
            else
            {
                // 스텝1 없는교재 제외
                if(array_key_exists($book['wb_f_id'],$book_data))
                {
                    $book_data[$book['wb_f_id']]['book_step2'][] = $book;
                }
            }
        }

        $result = [];
        foreach($book_data as $step1)
        {
            $step2 = $step1['book_step2'];
            unset($step1['book_step2']);
            $result[] = $step1;

            if($step2)
            {
                foreach($step2 as $val)
                {
                    $result[] = $val;
                }
            }
            
        }

        if($result)
        {
            $retrun_array['data']['list'] = $result;
            $retrun_array['res_code'] = '0000';
            $retrun_array['msg'] = "";
            echo json_encode($retrun_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

    }

    //북마크 리스트 조회
    //bookhistory_id 기준으로 조회
    public function list_bookmark_bookhistory_id()
    {
        $return_array = array();

        $request = array(
            // 'wiz_id' => ($this->input->post('wiz_id')) ? ($this->input->post('wiz_id')) : null,
            // 'bookhistory_id' => ($this->input->post('bookhistory_id')) ? ($this->input->post('bookhistory_id')) : null,
            'uid'           => ($this->input->post('uid')) ? ($this->input->post('uid')) : null,
            'book_id'       => ($this->input->post('book_id')) ? ($this->input->post('book_id')) : null,
            'lesson_id'     => ($this->input->post('lesson_id')) ? ($this->input->post('lesson_id')) : null,
            "start"         => $this->input->post('start') ? trim($this->input->post('start')):0,
            "limit"         => $this->input->post('limit') ? trim($this->input->post('limit')):100,
            "order_field"   => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mbb.mbb_key",
            "order"         => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $join = '';
        $where = " WHERE wbh.lesson_id = {$request['lesson_id']} AND wbh.book_id = {$request['book_id']}";
        $order = NULL;
        $limit = NULL;
        $select_col = "";

        $this->load->model('book_mdl');
        $bookhistory = $this->book_mdl->row_class_book_by_info($join, $where, $order, $limit, $select_col);
        
        if($bookhistory == NULL)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0501";
            $return_array['data']['err_msg'] = "등록된 교재가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        $limit = sprintf(' LIMIT %s , %s',$request['start'], $request['limit']);

        $this->load->model('book_mdl');
        $result = $this->book_mdl->list_book_bookmark($request['uid'], $bookhistory['bh_id'], $order, $limit);

        if($result)
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "목록조회성공";
            $return_array['data']['list'] = $result;
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "등록된 데이터가 없습니다.";
            $return_array['data']['list'] = $result;
            echo json_encode($return_array);
            exit;
        }
    }
    
    /*
        판서에 필요한 교재정보(bookmark정보, wiz_lesson정보)
        판서 교재보기에도 사용하지만 커리큘런 전체보기 교재(55번 서버 mint_book/public.html/book_script/js) 에서도 사용
    */
    public function class_info_bookmark_lesson()
    {
        $return_array = array();

        $request = array(
            'uid'           => ($this->input->post('uid')) ? ($this->input->post('uid')) : null,
            'book_id'       => ($this->input->post('book_id')) ? ($this->input->post('book_id')) : null,
            'lesson_id'     => ($this->input->post('lesson_id')) ? ($this->input->post('lesson_id')) : null,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        
        $this->load->model('member_mdl');
        $wiz_member = $this->member_mdl->get_wiz_member_by_wm_uid($request['uid']);

        if(!$wiz_member)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0110";
            $return_array['data']['err_msg'] = "해당하는 유저 정보를 찾을 수 없습니다";
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('book_mdl');
        $bookmark = $this->book_mdl->list_bookmark_by_book_id($request['uid'], $request['book_id']);
        $bookmark_cnt = $this->book_mdl->list_bookmark_count_by_book_id($request['uid'], $request['book_id']);

        $class_info = null;

        // 커리큘럼에서 교재보기는 lesson_id 가 없음
        if($request['lesson_id']){

            $class_info = $this->book_mdl->row_uid_by_wiz_lesson($request['lesson_id']);

            if($class_info['wl_uid'] != $request['uid']){
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0111";
                $return_array['data']['err_msg'] = "로그인 정보가 일치하지 않습니다. 다시 로그인해 주세요.";
                echo json_encode($return_array);
                exit;
            }

            if($class_info['wl_book_id'] != $request['book_id']){
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0112";
                $return_array['data']['err_msg'] = "수업에 해당하는 교재가 아닙니다. 교재를 확인해 주세요.";
                echo json_encode($return_array);
                exit;
            }
            
            if(!$class_info){
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0113";
                $return_array['data']['err_msg'] = "일치하는 수업이 없습니다. 수업을 확인해 주세요.";
                echo json_encode($return_array);
                exit;
            }
        }

        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['class_info'] = $class_info;
        $return_array['data']['bookmark']['list'] = $bookmark;
        $return_array['data']['bookmark']['total_cnt'] = $bookmark_cnt['cnt'];
        echo json_encode($return_array);
        exit;
    
    }


    //북마크 등록/해제
    public function book_bookmark()
    {
        $return_array = array();

        $request = array(
            // 'wiz_id'             => ($this->input->post('wiz_id')) ? ($this->input->post('wiz_id')) : null,
            // 'lesson_id'          => ($this->input->post('lesson_id')) ? ($this->input->post('lesson_id')) : null,
            'uid'                   => ($this->input->post('uid')) ? ($this->input->post('uid')) : null,
            'book_id'               => ($this->input->post('book_id')) ? ($this->input->post('book_id')) : null,
            'bookmark_chapter_name' => ($this->input->post('bookmark_chapter_name')) ? ($this->input->post('bookmark_chapter_name')) : null,
            'bookmark_lesson_name'  => ($this->input->post('bookmark_lesson_name')) ? ($this->input->post('bookmark_lesson_name')) : null,
            'bookmark_page'         => ($this->input->post('bookmark_page')) ? ($this->input->post('bookmark_page')) : null,
            "del_yn"                => ($this->input->post('del_yn')) ? trim(strtoupper($this->input->post('del_yn'))) : null ,            
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        
        $this->load->model('member_mdl');
        $result = $this->member_mdl->get_wiz_member_by_wm_uid($request['uid']);

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0110";
            $return_array['data']['err_msg'] = "해당하는 유저 정보를 찾을 수 없습니다,";
            echo json_encode($return_array);
            exit;
        }
        
        // $this->load->model('book_mdl');
        // $book_history = $this->book_mdl->row_bookhistory_by_info($request);

        // if($book_history == NULL)
        // {
        //     $return_array['res_code'] = '0900';
        //     $return_array['msg'] = "프로세스오류";
        //     $return_array['data']['err_code'] = "0501";
        //     $return_array['data']['err_msg'] = "등록된 교재가 없습니다.";
        //     echo json_encode($return_array);
        //     exit;
        // }

        // $where = " WHERE wbh.lesson_id = {$request['lesson_id']}";
        // $order = " ORDER BY wbh.bh_id DESC";
        // $limit = " LIMIT 1";
        // $class_book = $this->book_mdl->row_class_book_by_info($where, $order, $limit);
        
        // if($class_book['bh_id'] != $book_history['bh_id'])
        // {
        //     $return_array['res_code'] = '0900';
        //     $return_array['msg'] = "프로세스오류";
        //     $return_array['data']['err_code'] = "0502";
        //     $return_array['data']['err_msg'] = "현재 수업중인 교재가 아닙니다.";
        //     echo json_encode($return_array);
        //     exit;
        // }

        $bookmark_info = array(
            // "bookhistory_id" => $book_history['bh_id'],
            "uid" => $request['uid'],
            "book_id" => $request['book_id'],
            "bookmark_chapter_name"=> $request['bookmark_chapter_name'],
            "bookmark_lesson_name"=> $request['bookmark_lesson_name'],
            "bookmark_page"=> $request['bookmark_page'],
            'regdate' => date("Y-m-d H:i:s"),
        );

        $this->load->model('book_mdl');
        $result = $this->book_mdl->update_book_bookmark(array_filter($bookmark_info));
        
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;

        }else if($result == 0){
            //삭제
            $return_array['res_code'] = '0000';
            $return_array['msg'] =  "북마크가 해제됐습니다.";
            $return_array['result'] = 'N';
            echo json_encode($return_array);
            exit;
        
        }else{
    
            //인설트
            $return_array['res_code'] = '0000';
            $return_array['result'] = 'Y';
            $return_array['msg'] = "북마크가 등록됐습니다.";
            echo json_encode($return_array);
            exit;
        }
    }

    public function class_book_update()
    {
        $return_array = array();

        $request = array(
            // 'uid'    => ($this->input->post('uid')) ? ($this->input->post('uid')) : null,
            'tu_uid'    => ($this->input->post('tu_uid')) ? ($this->input->post('tu_uid')) : null,
            'lesson_id' => ($this->input->post('lesson_id')) ? ($this->input->post('lesson_id')) : null,
            'book_id'   => ($this->input->post('book_id')) ? ($this->input->post('book_id')) : null,
            'book_page' => ($this->input->post('book_page')) ? ($this->input->post('book_page')) : null,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }


        $this->load->model('book_mdl');
        $book_history = $this->book_mdl->row_bookhistory_by_info($request);

        if($book_history == NULL)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0501";
            $return_array['data']['err_msg'] = "현재 학생 출석부와 매칭된 교재가 맞는지 확인해주세요.";
            echo json_encode($return_array);
            exit;
        }


        $info = array(
            "tu_uid" => $request['tu_uid'],
            "uid" => $book_history['uid'],
            "bookhistory_id" => $book_history['bh_id'],
            "lesson_id"=> $request['lesson_id'],
            "book_page"=> $request['book_page'],
            "book_id"=> $request['book_id'],
            'regdate' => date("Y-m-d H:i:s"),
        );
        
        $result = $this->book_mdl->update_class_book(array_filter($info));

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        
        $return_array['res_code'] = '0000';
        // $return_array['msg'] =  "수업 정보가 갱신됐습니다.";
        $return_array['msg'] =  "Class information has been updated";
        echo json_encode($return_array);
        exit;
    }

    //내가 수업중인 교재가 맞는지 체크
    public function get_inclass_book_info()
    {
        $return_array = array();

        $request = array(
            'uid'       => ($this->input->post('uid')) ? ($this->input->post('uid')) : null,
            'lesson_id' => ($this->input->post('lesson_id')) ? ($this->input->post('lesson_id')) : null,
            'book_id'   => ($this->input->post('book_id')) ? ($this->input->post('book_id')) : null,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $join = " INNER JOIN wiz_lesson wl ON wbh.lesson_id = wl.lesson_id";
        $where = " WHERE wbh.lesson_id = '".$request['lesson_id']."' AND wl.lesson_state = 'in class' AND wbh.book_id = '".$request['book_id']."' AND wl.uid = '".$request['uid']."'";
        $order = " ORDER BY wbh.regdate DESC";
        $limit = " LIMIT 1";
        $select_col = ", wl.lesson_state, wl.uid";

        $this->load->model('book_mdl');
        $inclass_book = $this->book_mdl->row_class_book_by_info($join, $where, $order, $limit, $select_col);

        $result = isset($inclass_book) ? 'Y' : 'N';

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "교재조회성공";
        $return_array['data']['result'] = $result;
        $return_array['data']['book_name'] =  $inclass_book['book_name'];
        echo json_encode($return_array);
        exit;
    }
    
    public function get_last_class_book_page()
    {
        $return_array = array();

        $request = array(
            'tu_uid'    => ($this->input->post('tu_uid')) ? ($this->input->post('tu_uid')) : null,
            'lesson_id' => ($this->input->post('lesson_id')) ? ($this->input->post('lesson_id')) : null,
            'book_id'   => ($this->input->post('book_id')) ? ($this->input->post('book_id')) : null,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }


        $this->load->model('book_mdl');
        $book_page = $this->book_mdl->row_last_class_book_page($request);

        if(!$book_page)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0901";
            $return_array['data']['err_msg'] = "There are no saved pages.";
            echo json_encode($return_array);
            exit;
        }


        $return_array['res_code'] = '0000';
        $return_array['msg'] =  "정보조회 성공";
        $return_array['last_class'] =  $book_page;
        echo json_encode($return_array);
        exit;
    }

    public function test()
    {
        $return_array = array();

        $request = array(
            'tu_uid'    => ($this->input->post('tu_uid')) ? ($this->input->post('tu_uid')) : null,
            'lesson_id' => ($this->input->post('lesson_id')) ? ($this->input->post('lesson_id')) : null,
            'book_id' => ($this->input->post('book_id')) ? ($this->input->post('book_id')) : null,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }


        $this->load->model('book_mdl');
        $book_page = $this->book_mdl->row_last_class_book_page($request);


        $return_array['res_code'] = '0000';
        $return_array['msg'] =  "정보조회 성공";
        $return_array['last_class'] =  $book_page;
        echo json_encode($return_array);
        exit;

    }

    /* public function english_article_list_()
    {
        $return_array = array();

        $request = array(
            "start" => trim($this->input->post('start')) ? trim($this->input->post('start')):0,
            "limit" => trim($this->input->post('limit')) ? trim($this->input->post('limit')):5,
            "name" => trim($this->input->post('name')),
            "recommend_level" => trim($this->input->post('recommend_level')),
            "course_age" => trim($this->input->post('course_age')),
            "course_type" => trim($this->input->post('course_type')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mc.sorting",
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
        
        $limit = "";
        $order = "";
        $where = " WHERE mc.mc_key IS NOT NULL AND mc.use_yn = 'Y'";
        
        // 교재명
        if($request['name'])
        {   
            $where .= ' AND mc.name like "%'.$request['name'].'%"';
        }

        // 코스. 1:정규, 2:특별, 3:AHOP
        if($request['course_type'])
        {   
            $course_type = explode(',',$request['course_type']);
            $where .= ' AND mc.course_type IN ("'.implode('","',$course_type).'")';
        }

        // j:주니어, s:시니어, a:둘다
        if($request['course_age'])
        {   
            $course_age = explode(',',$request['course_age']);
            $where .= ' AND mc.course_age IN ("'.implode('","',$course_age).'")';
        }

        if($request['recommend_level'])
        {   
            $recommend_level = explode(',',$request['recommend_level']);
            $level_like = [];
            foreach($recommend_level as $level)
            {
                $level_like[] = '(mc.recommend_level LIKE "%'.$level.'%")';
            }

            if($level_like)
            {
                $where .= ' AND ('.implode(' OR ',$level_like).')';
            }
        }

        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        

        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        $this->load->model('curriculum_mdl');
        $result = $this->curriculum_mdl->list_curriculum($where, $order, $limit);

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
    } */

}








