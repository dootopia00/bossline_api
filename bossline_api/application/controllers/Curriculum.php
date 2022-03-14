<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Curriculum extends _Base_Controller {


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
            "start" => trim($this->input->post('start')) ? trim($this->input->post('start')):0,
            "limit" => trim($this->input->post('limit')) ? trim($this->input->post('limit')):5,
            "ename" => trim($this->input->post('ename')),
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
        $where = " WHERE mc.use_yn = 'Y'";
        
        // 교재명
        if($request['ename'])
        {   
            $where .= ' AND mc.ename like "%'.$request['ename'].'%"';
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

        $this->load->model('curriculum_mdl');

        $list_cnt = $this->curriculum_mdl->list_count_curriculum($where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }        

        $where .= sprintf(" ORDER BY %s %s", $request['order_field'], $request['order']);

        if($request['limit'] > 0)
        {   
            $where .= sprintf(" LIMIT %s , %s", $request['start'], $request['limit']);
        }

        $result = $this->curriculum_mdl->list_curriculum($where);

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        //커리큘럼에 교재 추가
        /* for($i=0; $i<count($result); $i++)
        {
            $books = $this->curriculum_mdl->list_book_by_mc_key($result[$i]['mc_mc_key']);
            $result[$i]['book'] = $books;
        } */
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['list'] = $result;
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        echo json_encode($return_array);
        exit;
    }

    public function view()
    {
        $return_array = array();

        $request = array(
            "wiz_id"        => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code"    => trim($this->input->post('table_code')),
            "mc_key"        => trim($this->input->post('mc_key')),
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

        //mc_key가 있을때 mc_key로 검색
        if($request['mc_key'])
            $result = $this->curriculum_mdl->row_curriculum_by_mc_key($request['mc_key']);
        else
            $result = $this->curriculum_mdl->row_curriculum_by_table_code($request['table_code']);

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        //커리큘럼에 교재 추가
        $books = $this->curriculum_mdl->list_book_by_mc_key($result['mc_mc_key']);
        $result['book'] = $books;

        // ahop일때 정보 추가 - DB정보가 구분되지않기에 정확한 정보를 출력하기 위해 가공하여 정보를 추가한다
        if($result['mc_table_code'] == '1364')
        {
            $mc_type = explode(' ', $result['mc_ename']);
            $result['mc_type'] = strtolower($mc_type[1]);
            $result['mc_etitle'] = $mc_type[1] == 'Social' ? 'Social Studies' : $mc_type[1];

            $mc_type2 = explode(' ', $result['mc_name']);
            $result['mc_title'] = $mc_type2[2];
            $result['mc_ktype'] = str_replace('과정', '', $mc_type2[2]);
        }

        //퀘스트
        MintQuest::request_batch_quest('7_8', $request['table_code']);
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "조회성공";
        $return_array['data'] = $result;
        echo json_encode($return_array);
        exit;
    }


    // 단체교육상담
    public function insert_consult()
    {
        $return_array = array();

        $request = array(
            "kind" => ($this->input->post('kind')) ,
            "com_name" => ($this->input->post('com_name')),
            "com_tel" => trim($this->input->post('com_tel')),
            "com_mobile" => trim($this->input->post('com_mobile')),
            "user_name" => ($this->input->post('user_name')),
            "user_tel" => ($this->input->post('user_tel')),
            "email" => trim($this->input->post('email')),
            "hope_su" => trim($this->input->post('hope_su')) ? trim($this->input->post('hope_su')) : '0',
            "hope_month" => trim($this->input->post('hope_month')) ? trim($this->input->post('hope_month')) : '0',
            "hope_date" => ($this->input->post('hope_date')),
            "hope_time" => ($this->input->post('hope_time')),
            "comment" => ($this->input->post('comment')),
            "check_ok" => ($this->input->post('check_ok') == 'Y') ? 'Y' : 'N',
            "table_code" => ($this->input->post('table_code'))
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        

        // table_code = junior : com_mobile 필수값

        // table_code = alliance : com_mobile 필수값 제외, user_tel에 insert data
        
        //인설트 데이터
        $info = array(
            "kind" => $request['kind'],
            "com_name" => $request['com_name'] ? $request['com_name'] : '',
            "com_tel" => $request['com_tel'],
            "com_mobile" => $request['com_mobile'],
            "user_name" => $request['user_name'],
            "user_tel" => $request['user_tel'],
            "email" => $request['email'],
            "hope_su" => $request['hope_su'],
            "hope_month" => $request['hope_month'],
            "hope_date" => $request['hope_date'],
            "hope_time" => $request['hope_time'],
            "comment" => $request['comment'],
            "regdate" => date('Y-m-d H:i:s'),
        );

        $this->load->model('curriculum_mdl');
        $result = $this->curriculum_mdl->insert_consult($info);

        $hope_time_array = array(
            '1' => 'anytime',
            '2' => '10시~11시',
            '3' => '11시~12시',
            '4' => '12시~13시',
            '5' => '13시~14시',
            '6' => '14시~15시',
            '7' => '15시~16시',
            '8' => '16시~17시',
            '9' => '17시~18시',
        );

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        
        //공이사님 번호
        $receive_num = '01088059327';    
        $options['content'] = "[민트영어] ".$request['com_name']." 의 교육 상담이 신청됐습니다.\r\n전화번호: ".$request['user_tel']."\r\n상담 희망날짜: ".$request['hope_date']."\r\n상담 희망시간: ".$hope_time_array[$request['hope_time']]."\r\n"."http://admin.mint05.com/ADMINISTRATOR/custom/consult.php";
        $send_sms = sms::send_sms($receive_num, '', $options);
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "수강문의 신청이 처리되었습니다. 빠른 답변 드리겠습니다.";
        $return_array['sms'] = $send_sms['state'];
        $return_array['sms_length'] = $send_sms['length'];
        echo json_encode($return_array);
        exit;

    }

    /**
     * AHOP 시험 정보
     */
    public function ahop_info()
    {
        $return_array = array();

        $request = array(
            "wiz_id"        => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "ahop_type"     => trim($this->input->post('ahop_type')),   // math, social, science
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }
        
        $this->load->model('curriculum_mdl');

        // 현재 교재 정보
        $book = $this->curriculum_mdl->get_book_with_book_exam($request['ahop_type']);
        $book_count = $this->curriculum_mdl->get_count_book_with_book_exam($request['ahop_type']);

        if(!$book)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $book_ex_id = array();
        foreach($book as $key=>$value)
        {
            $book_ex_id[] = $value['wbe_ex_id'];
        }
        $book_ex_id = implode(',', $book_ex_id);
        

        $result = array();
        $result['chapter'] = array();
        $complete = 0;

        // 챕터,시험진행상황. 닫기 눌러도 제한시간내에는 진행했던곳부터 재시작가능.
        $chapter = $this->curriculum_mdl->get_progress_chapter($request['ahop_type'], $wiz_member['wm_uid'], $book_ex_id);
        if($chapter){
            
            foreach($chapter as $key=>$value){

                if($value['reply_name'])    $result['chapter'][$value['ex_no']]['reply_name'] = $value['reply_name'];
                if($value['ex_id'])         $result['chapter'][$value['ex_no']]['ex_id']      = $value['ex_id'];
                if($value['book_id'])       $result['chapter'][$value['ex_no']]['book_id']    = $value['book_id'];
                if($value['exam_time'])     $result['chapter'][$value['ex_no']]['exam_time']  = $value['exam_time'];
                if($value['review_id'])     $result['chapter'][$value['ex_no']]['review_id']  = $value['review_id'];
                if($value['mb_unq'])        $result['chapter'][$value['ex_no']]['mb_unq']     = $value['mb_unq'];
                if($value['recom'])         $result['chapter'][$value['ex_no']]['recom']      = $value['recom'];
    
                if($value['reply_name'] == 'COMPLETE') $complete++;
            }
        }

        //모든 과정을 통과한지 체크
        $result['chapter']['complete'] = $complete;
        
        // 오답지우기 티켓갯수
        $ticket_result = $this->curriculum_mdl->check_ticket_count($wiz_member['wm_uid']);
        $result['ticket_cnt'] = $ticket_result ? $ticket_result['cnt'] : 0;

        // 시험무료로 볼수있는지. 매일 1회 무료이나 하루에 2회이상은 포인트 5000 소모한다.
        // 이전챕터 오늘 무료로 봐서 합격했으면, 다음챕터의 시험이라도 2회 이상에 속하므로 5000포인트 소모
        $free_exam_result = $this->curriculum_mdl->check_ticket_count($wiz_member['wm_uid']);
        $result['free_exam'] = $free_exam_result ? 0 : 1;

        
        $this->load->model('badge_mdl');
        $where_badge = " WHERE wb.type != 'admin' AND wb.type2 = '{$request['ahop_type']}' ORDER BY wb.id";
        $join_where = ' AND uid='.(int)$wiz_member['wm_uid'];
        $badge = $this->badge_mdl->get_user_badge($where_badge, $join_where);


        // 절대팔찌 기준은 step1~step6 까지 모두 합격 && 절대팔찌 3407 글쓰기
        $this->load->model('board_mdl');
        $where_speak = " WHERE sp_gubun = '3407' AND uid = '{$wiz_member['wm_uid']}'";  // 3407 절대팔찌 code
        $speak = $this->board_mdl->list_count_board_wiz_speak($where_speak);
        
        $bracelet = 'N';

        if($speak['cnt'] > 0){
            $bracelet = 'Y';
        }

        $return_array['res_code']     = '0000';
        $return_array['msg']          = "AHOP 시험 정보 불러오기 성공";
        $return_array['data']['book'] = $book;
        $return_array['data']['book_cnt'] = $book_count['cnt'];
        $return_array['data']['badge'] = $badge;
        $return_array['data']['bracelet'] = $bracelet;
        $return_array['data']['info'] = $result;
        echo json_encode($return_array);
        exit;
    }

    /**
     * 영자신문 토픽 리스트
     */
    public function english_article_topic_list_()
    {
        $return_array = array();

        $request = array(
            "lev_gubun" => trim($this->input->post('lev_gubun'))
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        
        /**
         * 시니어와, 주니어에 따라서 교재에 해당하는 id가 별도
         * 시니어 : 408, 주니어 : 419
         * 교재에 부모 id를 가져오기 위한 컬럼
         */
        $f_id = $request['lev_gubun'] === 'senior' ? '408' : '419';

        $this->load->model('curriculum_mdl');

        $result = $this->curriculum_mdl->list_english_article_topic($f_id);

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

    /**
     * 영자신문 토픽 게시물에 해당하는 리스트
     */
    public function english_article_list_()
    {
        $return_array = array();

        $request = array(
            "lev_gubun" => trim($this->input->post('lev_gubun')),
            "book_id" => trim($this->input->post('book_id')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mb.uid",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
            "start" => trim($this->input->post('start')) ? trim($this->input->post('start')) : 0,
            "limit" => trim($this->input->post('limit')) ? trim($this->input->post('limit')) : 10,
            "search_key" => trim($this->input->post('search_key')),
            "search_keyword" => trim($this->input->post('search_keyword')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        
        /**
         * 시니어와, 주니어에 따라서 교재에 해당하는 id가 별도
         * 시니어 : 408, 주니어 : 419
         * 교재에 부모 id를 가져오기 위한 컬럼
         */
        $f_id = $request['lev_gubun'] === 'senior' ? '408' : '419';

        /**
         * 사용여부, 교재 id
         */
        $where = " WHERE mb.use_yn = 'Y' AND wb.f_id = '".$f_id."'";

        // 교재명
        if($request['book_id'] !== 'all')
        {   
            $where .= ' AND mb.book_id = "'.$request['book_id'].'"';
        }

        /**
         * 검색 조건
         * 제목, 내용
         */
        if($request['search_key'])
        {
            $where .= ' AND '.$request['search_key'].' LIKE "%'.$request['search_keyword'].'%"';
        }

        /**
         * 리미트의 크기가 0 보다 클 경우
         */

        $limit = "";
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }

        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);


        $this->load->model('curriculum_mdl');

        $list_cnt = $this->curriculum_mdl->list_english_article_count($where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }        

        $result = $this->curriculum_mdl->list_english_article($where, $order, $limit);

        $topic_result = $this->curriculum_mdl->list_english_article_topic($f_id);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['list'] = $result;
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['topic'] = $topic_result;
        echo json_encode($return_array);
        exit;

    }

    /**
     * 영자신문 토픽 게시물에 해당하는 상세보기
     */
    public function english_article()
    {
        $return_array = array();

        $request = array(
            "lev_gubun" => trim($this->input->post('lev_gubun')),
            "book_id" => trim($this->input->post('book_id')),
            "uid" => trim($this->input->post('uid')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /**
         * 시니어와, 주니어에 따라서 교재에 해당하는 id가 별도
         * 시니어 : 408, 주니어 : 419
         * 교재에 부모 id를 가져오기 위한 컬럼
         */
        $f_id = $request['lev_gubun'] === 'senior' ? '408' : '419';
        $this->load->model('curriculum_mdl');

        $result = $this->curriculum_mdl->row_english_article($f_id, $request['book_id'], $request['uid']);
        $comment_cnt = $this->curriculum_mdl->list_count_english_article_comment($request['uid']);

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }        

        $img_array = array();

        /**
         * 이미지가 있을 경우
         * 총 3개의 이미지가 들어간다.
         */
        $img = explode("/",$result['mb_img']);
        /**
         * 이미지 정렬 위치
         * l = left
         * c = center
         * r = right
         */
        $align = explode("/",$result['mb_align']);

        for($i=0; $i<count($img); $i++)
        {
            if($img[$i] !== ''){
                array_push($img_array, array("img" => $img[$i], "align" => $align[$i]));
            }
        }

        $result['img_array'] = $img_array;

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "게시물조회성공";
        $return_array['data']['info'] = $result;
        $return_array['data']['info']['com_cnt'] = $comment_cnt['cnt'];
        echo json_encode($return_array);
        exit;

    }

    /**
     * 영자신문 토픽 게시물에 해당하는 상세보기에 해당하는 댓글 리스트
     */

    public function english_article_comment_list_()
    {
        $return_array = array();

        $request = array(
            "uid" => trim($this->input->post('uid')),
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

        $result_cnt = $this->curriculum_mdl->list_count_english_article_comment($request['uid']);

        if($result_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            $return_array['data']['cnt'] = $result_cnt['cnt'];
            echo json_encode($return_array);
            exit;
        }        

        $this->load->model('member_mdl');

        $result = $this->curriculum_mdl->list_english_article_comment($request['uid']);

        for($i=0; $i<count($result); $i++)
        {
            $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($result[$i]['mbc_writer_id']);

            /*
                알림시 표기되는 이름 추천회원 닉네임
                우선순위 1. 닉네임 , 2. 영어이름 3. 한글이름
            */
            if($wiz_member["wm_nickname"])
            {
                $result[$i]['display_name'] = $wiz_member["wm_nickname"];
            }
            else
            {
                $result[$i]['display_name'] = ($wiz_member['wm_ename']) ? $wiz_member['wm_ename'] : $wiz_member['wm_name'];
            }
        }

        
        /*
            알림시 표기되는 이름 추천회원 닉네임
            우선순위 1. 닉네임 , 2. 영어이름 3. 한글이름
        */
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "게시물조회성공";
        $return_array['data']['list'] = $result;
        $return_array['data']['cnt'] = $result_cnt['cnt'];
        echo json_encode($return_array);
        exit;

    }

    /**
     * 영자신문 해석 글 쓰기
     */
    public function insert_english_article_comment()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "lev_gubun" => trim($this->input->post('lev_gubun')),
            "book_id" => trim($this->input->post('book_id')),
            "uid" => trim($this->input->post('uid')),
            "comment" => trim(strtolower($this->input->post('comment'))),
        );

        /* 유효성 확인 */
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
         * 시니어와, 주니어에 따라서 교재에 해당하는 id가 별도
         * 시니어 : 408, 주니어 : 419
         * 교재에 부모 id를 가져오기 위한 컬럼
         */
        $f_id = $request['lev_gubun'] === 'senior' ? '408' : '419';

        /* 회원정보 */
        $this->load->model('member_mdl');

        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);

        $result = $this->curriculum_mdl->row_english_article($f_id, $request['book_id'], $request['uid']);

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        $result_cnt = $this->curriculum_mdl->list_count_english_article_comment($request['uid']);

        if($result_cnt['cnt'] >= 3)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0705";
            $return_array['data']['err_msg'] = "해석이 3개 이상 입니다.";
            echo json_encode($return_array);
            exit;
        }

        $checked_count = $this->curriculum_mdl->checked_count_english_article_comment_by_wiz_id($request['uid'], $request['wiz_id']);

        if($checked_count['cnt'] >= 1)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0705";
            $return_array['data']['err_msg'] = "한 기사의 해석은 1회만 가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        $comment = array(
            'b_uid' => $request['uid'],
            'writer_id' => $wiz_member['wm_wiz_id'],
            'writer_name' => $wiz_member['wm_name'],
            'comment' => $request['comment'],
            'regdate' => date('Y-m-d H:i:s'),
        );
        
        $comment_result = $this->curriculum_mdl->insert_comment_english_article($comment);

        if($comment_result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $pt_name = "영자신문 해석 이벤트로 ".number_format(2500)."포인트가 적립되었습니다.";

        // showYn 적립여부 (y:적립완료, n:적립예정(사용자페이지에 출력안함), d:회수,삭제(포인트 회수시에 d로 업데이트하고 있음))
        // wp_kind l:민트영어사용설명서 , 일반댓글 m:오늘의영어한마디, z:공지댓글  

        $point = array(
            'uid' => $wiz_member['wm_uid'],
            'name' => $wiz_member['wm_name'],
            'point' => 2500,
            'pt_name'=> $pt_name, 
            'kind'=> 'o', 
            'b_kind'=> 'boards',
            'table_code'=> $request['uid'],
            'co_unq'=> $comment_result, 
            'showYn'=> 'y',
            'regdate' => date("Y-m-d H:i:s")
        );

        /* 포인트 내역 입력 및 포인트 추가 */
        $this->load->model('point_mdl');
        $point_result = $this->point_mdl->set_wiz_point($point);

        if($point_result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $tmp_point = $this->member_mdl->get_wm_point_by_wiz_id($wiz_member['wm_wiz_id']);
            $wm_point =  $tmp_point['wm_point'];
        }


        $return_array['res_code'] = '0000';
        $return_array['msg'] = $pt_name;
        $return_array['data']['wm_point'] = $wm_point;
        echo json_encode($return_array);
        exit;

    }

    /*
        영자신문 해석 수정
    */
    public function modify_english_article_comment()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "lev_gubun" => trim($this->input->post('lev_gubun')),
            "book_id" => trim($this->input->post('book_id')),
            "uid" => trim($this->input->post('uid')),
            "comment" => trim(strtolower($this->input->post('comment'))),
            "co_unq" => trim(strtolower($this->input->post('co_unq'))),
        );

        /* 유효성 확인 */
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
    
        $this->load->model('curriculum_mdl');

        $article_comment = $this->curriculum_mdl->row_comment_english_article($request['co_unq'], $request['uid']);

        
        if(!$article_comment)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0309";
            $return_array['data']['err_msg'] = "해석이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article_comment['mbc_writer_id'] != $request['wiz_id'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0310";
            $return_array['data']['err_msg'] = "해석 수정 권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }


        $comment = NULL;
        $comment_result = NULL;

        $comment = array(
            'comment' => $request['comment'],
        );

        $comment_result = $this->curriculum_mdl->update_comment_english_article($comment, $request['co_unq'], $wiz_member['wm_wiz_id']);


        if($comment_result < 0 || $comment_result == null)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "해석이 수정되었습니다."; 
            echo json_encode($return_array);
            exit;
        }
        
    }

    /*
        영자신문 해석 삭제
    */
    public function delete_english_article_comment()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "lev_gubun" => trim($this->input->post('lev_gubun')),
            "book_id" => trim($this->input->post('book_id')),
            "uid" => trim($this->input->post('uid')),
            "co_unq" => trim(strtolower($this->input->post('co_unq'))),
        );

        /* 유효성 확인 */
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
         * 시니어와, 주니어에 따라서 교재에 해당하는 id가 별도
         * 시니어 : 408, 주니어 : 419
         * 교재에 부모 id를 가져오기 위한 컬럼
         */
        $f_id = $request['lev_gubun'] === 'senior' ? '408' : '419';

        $article = $this->curriculum_mdl->row_english_article($f_id, $request['book_id'], $request['uid']);

        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        $article_comment = $this->curriculum_mdl->row_comment_english_article($request['co_unq'], $request['uid']);

        if(!$article_comment)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0309";
            $return_array['data']['err_msg'] = "해석이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article_comment['mbc_writer_id'] != $request['wiz_id'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0310";
            $return_array['data']['err_msg'] = "해석 삭제 권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $comment_result = NULL;
        /* 회원정보 */
        $this->load->model('member_mdl');
        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);
        
        $comment_result = $this->curriculum_mdl->delete_comment_english_article($request['co_unq'], $request['uid'] ,$request['wiz_id'], $wiz_member['wm_uid']);

        if($comment_result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] =  "해석이 삭제되었습니다."; 
        $return_array['data']['wm_point'] = $comment_result;
        echo json_encode($return_array);
        exit;
    }

    //AHOP 시험 step 보상
    public function checked_ahop_exam()
    {
        $return_array = array();

        $request = array(
            'wiz_id'            => trim($this->input->post('wiz_id')) ? trim($this->input->post('wiz_id')) : null,
            "authorization"     => trim($this->input->post('authorization')),
            "book_id"           => $this->input->post('book_id') ? $this->input->post('book_id') : NULL,
            "ahop_type"         => trim($this->input->post('ahop_type')),   // math, social, science
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


        $this->load->model('curriculum_mdl');

        // 현재 교재 정보
        $book = $this->curriculum_mdl->get_book_with_book_exam($request['ahop_type']);

        if(!$book)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $book_ids = array();
        foreach($book as $key=>$value)
        {
            $book_ids[] = $value['wb_book_id'];
        }
        

        // 첫번째 인덱스 값은 숫자 0 으로 리턴돼서 str로 변환해서 null만 체크
        $search_array = array_search($request['book_id'], $book_ids);
        $search_array = strval($search_array);

        if($search_array == '0')
        {
            // 이전 시험 기록이 없을 경우
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "시험을 진행해주세요!";
            echo json_encode($return_array);
            exit;

        }else{

            if($search_array == ''){
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "시험정보를 확인해주세요.";
                echo json_encode($return_array);
                exit;
            
            }else{
    
                $prev_index = $book_ids[$search_array-1];
                $next_index = $book_ids[$search_array+1];
    
                $prev_log = $this->curriculum_mdl->checked_ahop_exam($prev_index, $wiz_member['wm_uid']);
                $next_log = $this->curriculum_mdl->checked_ahop_exam($next_index, $wiz_member['wm_uid']);
    
                // 이 전 시험 합격 && 다음 시험 정보 없어야 진행
                if($prev_log && !$next_log){
                    $return_array['res_code'] = '0000';
                    $return_array['msg'] = "시험을 진행해주세요!";
                    echo json_encode($return_array);
                    exit;
                }
            }
        }
    }

    //AHOP 시험 step 보상
    public function ahop_reward()
    {
        $return_array = array();

        $request = array(
            'wiz_id'            => trim($this->input->post('wiz_id')) ? trim($this->input->post('wiz_id')) : null,
            "authorization"     => trim($this->input->post('authorization')),
            "book_id"           => $this->input->post('book_id') ? $this->input->post('book_id') : NULL,
            "ex_id"             => $this->input->post('ex_id') ? $this->input->post('ex_id') : NULL,
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


        $this->load->model('curriculum_mdl');
        $log_count = $this->curriculum_mdl->get_count_exam_log_by_uid($request['ex_id'], $wiz_member['wm_uid']);
        
        if($log_count['cnt'] == 0){
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "시험정보가 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        $log = $this->curriculum_mdl->get_exam_log_by_uid($request['ex_id'], $wiz_member['wm_uid']);

        
        $this->load->model('board_mdl');
        $board = $this->board_mdl->row_article_recom_by_mb_unq($log['el_review_id'], $wiz_member['wm_wiz_id']);
        
        if($log['el_reply_name'] == 'COMPLETE'){
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0202";
            $return_array['data']['err_msg'] = "이미 지급된 보상입니다.";
            echo json_encode($return_array);
            exit;
        }

        if($log['el_reply_name'] != 'FINISH'){
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0203";
            $return_array['data']['err_msg'] = "모든 과정이 완료되지 않았습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($board['mb_recom'] < 10){
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0203";
            $return_array['data']['err_msg'] = "추천 수가 부족합니다. 현재 시험후기 추천 수 : ". $board['mb_recom'];
            echo json_encode($return_array);
            exit;
        }

        /* 포인트 지급 프로세스 START */


        // 지급 포인트 불러오기 
        $point = $this->curriculum_mdl->get_exam_point_by_book_id($request['book_id'], $wiz_member['wm_uid']);
        #   $point['wb_exam_point'];
        

        // 시험 최초 시작일 계산
        $startday = $this->curriculum_mdl->get_exam_start_day($request['book_id'], $wiz_member['wm_uid']);
        
        $exam_start90 = date('Y-m-d', strtotime($startday['startday']." +90 day"));
        $msg = "과정을 완료하여(x2배) 총 ".number_format($point['wb_exam_point'])."포인트를 획득 하였습니다.";
        
        
        
        if($exam_start90 >= date('Y-m-d')){
            $point['wb_exam_point'] = $point['wb_exam_point'] * 2;
            $msg = "과정을 90일 이내 완료하여(x2배) 총 ".number_format($point['wb_exam_point'])."포인트를 획득 하였습니다.";
        }
        

        $point_insert = array(
            'uid' => $wiz_member['wm_uid'],
            'name' => $wiz_member['wm_name'],
            'point' => $point['wb_exam_point'],
            'pt_name'=> '[AHOP] STEP Complete Point', 
            'kind'=> 'k', 
            'showYn' => 'y',
            'regdate' => date("Y-m-d H:i:s"),
        );

        
        /* 포인트 내역 입력 및 포인트 추가 */
        $this->load->model('point_mdl');
        $res_point = $this->point_mdl->set_wiz_point($point_insert);

        if($res_point < 0) {

            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }


        // 기존에 해당 커리큘럼으로 티켓지급 받았는지 체크
        $where_correct_gift = "WHERE uid = '{$wiz_member['wm_uid']}' AND memo = '{$request['ex_id']}' ";
        $count_correct_gift = $this->member_mdl->list_count_wiz_member_correct_gift($where_correct_gift);

        if($count_correct_gift['cnt'] == 0){

            // 티켓 10장 지급
            $multi_data = array();
    
            for($i=0; $i<10; $i++)
            {
                $gift_data = array(
                    "uid" => $wiz_member['wm_uid'],
                    "to_uid" => NULL,
                    "type" => 'exam',
                    "pay" => '1',
                    "price" => '0',
                    "comment" => '[AHOP] STEP Complete gift',
                    "use_startdate" => date("Y-m-d"),
                    "use_enddate" => '2019-06-30',          // 기존 구민트 소스에 하드코딩 돼있음
                    "used" => '',
                    "use_datetime" => '0000-00-00 00:00:00',
                    "etc" => '',
                    "memo" => $request['ex_id'],
                );
                array_push($multi_data, $gift_data);
            }
            $result_correct_gift = $this->member_mdl->insert_correct_gift($multi_data);
            
            if($result_correct_gift < 0) {
    
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB CORRECT GIFT ERROR";
                echo json_encode($return_array);
                exit;
            }
        }


        /* 시험 이력 업데이트 */
        $res_update_log = $this->curriculum_mdl->update_ahop_exam_log($request['ex_id'], $wiz_member['wm_uid']);
        
        if($res_update_log < 0) {

            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB LOG ERROR";
            echo json_encode($return_array);
            exit;
        }


        $book_name = explode(" ", $log['el_book_name']);
        $step = explode("STEP", $book_name[1]);

        if($step == 6){
            //인설트
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "축하합니다! \n".$msg."\n※ 모든 STEP를 완료 하였습니다.\n과정 완료 보상으로 업적 배지와 절대팔찌를 수령할 수 있습니다.";
            echo json_encode($return_array);
            exit;

        }else{
            //인설트
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "축하합니다! \n".$msg."\n※ 다음 스텝이 오픈되었습니다.\n도전해보세요!";
            echo json_encode($return_array);
            exit;
        }

    }


    /**
     * AHOP 시험 리스트
     * 해당하는 스탭의 시험 리스트를 불러온다
     * 
     * ex_id : 해당 챕터 시작(in) 번호
     */
    public function ahop_exam_list_()
    {
        $return_array = array();

        /**
         * 기존에는 book_id로 받았지만 정확히는 ex_id이므로 ex_id로 표기
         */
        $request = array(
            'wiz_id'        => trim($this->input->post('wiz_id')) ? trim($this->input->post('wiz_id')) : null,
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

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('curriculum_mdl');

        /**
         * 시험 리스트 불러오기
         * ex_id로 정보를 추출해서
         * book_id, f_id로 해당하는 리스트를 불러온다
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

        $list = $this->curriculum_mdl->get_ahop_exam_list_($exam_info['book_id'], $exam_info['f_id']);
        if(!$list)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0202";
            $return_array['data']['err_msg']  = "시험리스트를 불러오는데 실패했습니다.";
            echo json_encode($return_array);
            exit;
        }

        /**
         * 해당 유저의 시험 정보
         */
        $score = ''; //챕터별 만점 점수 저장
        foreach($list as $key=>$value)
        {
            $list[$key]['ing']      = 0;    //해당 챕터 진행 가능 여부 (0:진행불가, 1: 진행가능, 2: 재시도, 3: 진행중)
            $list[$key]['perc']     = 0;    //해당 챕터 진행률
            $list[$key]['progress'] = '';   //진행중인 시험 아이디

            //해당하는 챕터의 유저 시험 로그 정보
            $exam_log = $this->curriculum_mdl->get_exam_log_by_ex_no_to_uid($value['wbe_ex_id'], $wiz_member['wm_uid']);
            if(!$exam_log)
            {
                //만약 챕터 in 로그가 없는것이면 로그를 생성해준다
                if($value['wbe_step'] == 'In')
                {
                    $list[$key]['ing'] = 1;

                    //시험 문제들을 랜덤하게 섞고 시험번호를 넣어준다
                    $exam_chapter_list = $this->curriculum_mdl->get_ahop_exam_chapter_list_($value['wbe_ex_id']);
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
                        'ex_no'      => $value['wbe_ex_id'],
                        'book_id'    => $value['wbe_book_id'],
                        'book_name'  => $value['wbe_book_name'],
                        'uid'        => $wiz_member['wm_uid'],
                        'q_total'    => $exam_chapter_list['cnt'],
                        'o_total'    => 0,
                        'my_exam'    => $exam_chapter_list['ex_list'],
                        'regdate'    => date("Y-m-d H:i:s"),
                        'reply_name' => "START",
                        'reply_uid'  => "STATUS",
                    );
                    $this->curriculum_mdl->insert_wiz_book_exam_log($params);
                }

                //바로 이전 시험이 만점일 경우 or 첫번째 챕터일 경우
                if($score || $value['wbe_chapter'] == '1')
                {
                    $list[$key]['ing'] = 1; 
                    $score = '';
                }

                //진행중인 시험이 아니므로 다음으로 넘어간다
                continue;
            }

            //로그가 있으므로 이후는 무조건 재시도 이므로
            $list[$key]['ing'] = 2;

            //해당 챕터 진행 상황
            $perc = 0;
            if($exam_log['el_o_total'] && $exam_log['el_q_total'])
            {
                $perc = ceil( ($exam_log['el_o_total'] / $exam_log['el_q_total']) * 100 );
            }
            $list[$key]['perc'] = $perc;

            //해당 챕터 만점일 경우 저장(무료 시험 제외)
            if($value['wbe_chapter'] != 'in' && $perc == 100) $score = 'complete';
            else                                              $score = '';

            //진행중인 시험이 있는지 체크
            //시험시간이 남았는지 체크
            $now_date   = strtotime(date('Y-m-d H:i:s'));
            $el_regdate = strtotime("+".$exam_log['wbe_remain_time']." minutes", strtotime($exam_log['el_regdate']));
            if($el_regdate > $now_date)
            {
                //시험시간이 남았다면 해당 시험 로그 번호를 리턴
                $list[$key]['ing']      = 3; //진행중인 시험이있다
                $list[$key]['progress'] = $exam_log['el_ex_id'];
            }
            else
            {
                //시험시간이 지났다면 해당 시험을 완료 처리한다
                $params = array(
                    'examdate' => date('Y-m-d H:i:s'),
                );
                $where = array('ex_id' => $exam_log['el_ex_id']);
                $this->curriculum_mdl->update_wiz_book_exam_log($params, $where);
            }
        }

        //시험별 오늘 무료시험 진행 체크
        $free_exam_log = $this->curriculum_mdl->chk_free_exam_log_by_ex_no_to_uid($exam_info['book_id'], $wiz_member['wm_uid']);
        if($free_exam_log) $free = false;
        else               $free = true;

        $return_array['res_code']     = '0000';
        $return_array['msg']          = "시험리스트를 정상적으로 불러왔습니다."; 
        $return_array['data']['list'] = $list;
        $return_array['data']['free'] = $free;
        echo json_encode($return_array);
        exit;
    }

    /**
     * AHOP 시험 응시 시작
     * -> 해당 시험의 최초 로그 생성
     * -> 로그가 이미있다면(진행중인 시험이 있다면) 해당 로그를 리턴
     * -> 최초 무료시험(in)로그로 최종 시험이 완료됨을 체크하기때문에 step=in 인 시험로그가없다면 생성
     * 
     * ex_id : 해당 시험의 아이디
     * el_ex_id : 시험 로그 아이디 이값이 들어오면 진행중인 시험이 있다는 뜻
     */
    public function start_ahop_exam()
    {
        $return_array = array();

        $request = array(
            'wiz_id'        => trim($this->input->post('wiz_id')) ? trim($this->input->post('wiz_id')) : null,
            "authorization" => trim($this->input->post('authorization')),
            "ex_id"         => $this->input->post('ex_id'),
            "el_ex_id"      => $this->input->post('el_ex_id') ? $this->input->post('el_ex_id') : null,
            "free"          => $this->input->post('free') ? $this->input->post('free') : false
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
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

        if($request['el_ex_id'])
        {
            //로그아이디가있다면 로그 정보를 조회하고 가져온다
            $exam_log = $this->curriculum_mdl->get_wiz_book_exam_log_by_ex_id($request['el_ex_id']);
            if($exam_log)
            {
                //다시한번 시험시간이 남았는지 체크
                $now_date   = strtotime(date('Y-m-d H:i:s'));
                $el_regdate = strtotime("+".$exam_log['wbe_remain_time']." minutes", strtotime($exam_log['el_regdate']));
                if($el_regdate > $now_date)
                {
                    //시험시간이 남았다면 해당 시험 로그 번호를 리턴
                    $exam_log_id = $request['el_ex_id'];
                }
                else
                {
                    //시험시간이 지났다면 해당 시험을 완료 처리한다
                    $params = array(
                        'examdate' => date('Y-m-d H:i:s'),
                    );
                    $where = array('ex_id' => $exam_log['el_ex_id']);
                    $this->curriculum_mdl->update_wiz_book_exam_log($params, $where);

                    //진행중인 시험이 완료되었으므로 새로 로그를 만들어주기 위해
                    $request['el_ex_id'] = null;
                }
            }
        }
        
        if(!$request['el_ex_id'])
        {
            //무료 시험이 아닐경우 5000포인트를 차감하고 새 시험을 시작한다
            if(!$request['free'])
            {
                $this->load->model('point_mdl');

                // 현재포인트
                $cur_point = $this->point_mdl->check_current_point($wiz_member['wm_uid']);
                if(5000 > $cur_point['wm_point'])
                {
                    $return_array['res_code']         = '0900';
                    $return_array['msg']              = "프로세스오류";
                    $return_array['data']['err_code'] = "0328";
                    $return_array['data']['err_msg']  = "포인트가 부족합니다.";
                    echo json_encode($return_array);
                    exit;
                }
                
                // 포인트 소진
                $point = array(
                    'uid'     => $wiz_member['wm_uid'],
                    'name'    => $wiz_member['wm_name'],
                    'point'   => -5000,
                    'pt_name' => '[AHOP] 시험 재응시', 
                    'kind'    => '5',
                    'b_kind'  => 'exam',
                    'regdate' => date("Y-m-d H:i:s")
                );

                /* 포인트 내역 입력 및 포인트 추가 */
                $this->point_mdl->set_wiz_point($point);
            }

            //진행중인 시험이 없다면 새로 로그를 만들어준다
            $reply_name = $reply_uid = "";
            if($exam_info['step'] == 'In')
            {
                $reply_name = "START";
                $reply_uid  = "STATUS";
            }

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
                'uid'        => $wiz_member['wm_uid'],
                'q_total'    => $exam_chapter_list['cnt'],
                'o_total'    => 0,
                'my_answers' => '',
                'my_exam'    => $exam_chapter_list['ex_list'],
                'regdate'    => date("Y-m-d H:i:s"),
                'reply_name' => $reply_name,
                'reply_uid'  => $reply_uid,
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
     * AHOP 시험 현재 진행중인 시험 문제 정보
     * 입력한 정답을 로그에 저장시켜주는 역활도 수행한다
     * 
     * ex_id : 시험 로그 번호
     * ex_no : 현재 시험 문제 아이디(이 값이 있을 경우 이전,다음문제로 와서 수정하는 경우 임)
     * answer : 회원이 입력한 정답
     */
    public function ahop_exam_in_progress_info()
    {
        $return_array = array();

        $request = array(
            'wiz_id'        => trim($this->input->post('wiz_id')) ? trim($this->input->post('wiz_id')) : null,
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

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
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
     * AHOP 시험 완료 후 답안 채점 후 시험결과를 리턴해준다
     * 
     * ex_id : 시험 로그 번호
     */
    public function ahop_exam_grade()
    {
        $return_array = array();

        $request = array(
            'wiz_id'        => trim($this->input->post('wiz_id')) ? trim($this->input->post('wiz_id')) : null,
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

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
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
        $my_ox      = explode(',', $exam_log['el_my_ox']);
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
                    $my_ox[$key] = 'O';
                }
                else
                {
                    $my_ox[$key] = 'X';
                    $wrong_answer[] = $value;
                }
                
                $info = null;
            }           
        }

        // 시험시간 계산 => 전체 문항수 * 30초 - 남은 시간
        $ex_time = strtotime(date('Y-m-d H:i:s')) - strtotime($exam_log['el_regdate']);
        $ex_time = (30 * $q_total) - $ex_time;

        /**
         * 진행중인 시험 정보를 체크 하고 업데이트 한다
         */
        //최종 시험 기록 업데이트
        $params = array(
            'my_ox'      => implode(',', $my_ox),
            'my_answers' => implode('§', $my_answers),
            'my_exam'    => implode(',', $my_exam),
            'q_total'    => $q_total,
            'o_total'    => $o_total,
            'examdate'   => date("Y-m-d H:i:s"),
            'exam_time'  => $ex_time,
        );
        $where = array('ex_id' => $exam_log['el_ex_id']);
        $this->curriculum_mdl->update_wiz_book_exam_log($params, $where);
            
        //파이널 챕터에 만점일 경우
        if ($exam_log['chapter'] == "Fi" && $q_total == $o_total)
        {
            $params = array(
                'reply_name' => 'FINISH'
            );
            $where = array(
                'uid'        => $wiz_member['wm_uid'],
                'book_id'    => $exam_log['wbe_book_id'],
                'reply_name' => 'START',
                'reply_uid'  => 'STATUS',
            );
            $this->curriculum_mdl->update_wiz_book_exam_log($params, $where);
        }

        // 답안모두 처리 후 틀린 문제중 하나만 정답과 리턴
        if(count($wrong_answer) > 0)
        {
            $rand   = array_rand($wrong_answer);
            $answer = $this->curriculum_mdl->ahop_exam_info($wrong_answer[$rand]);
            $answer['q_total'] = $q_total;
            $answer['o_total'] = $o_total;
        }
        else
        {
            $answer = "";
        }
        
        $return_array['res_code']     = '0000';
        $return_array['msg']          = "시험이 정상적으로 완료되었습니다."; 
        $return_array['data']['info'] = $answer;
        echo json_encode($return_array);
        exit;
    }


    /**
     * AHOP 시험 리셋
     * 5000포인트를 소모해서 시험을 리셋한다
     */
    public function ahop_exam_reset()
    {
        $return_array = array();

        $request = array(
            'wiz_id'        => trim($this->input->post('wiz_id')) ? trim($this->input->post('wiz_id')) : null,
            "authorization" => trim($this->input->post('authorization')),
            "book_id"       => $this->input->post('book_id') ? $this->input->post('book_id') : NULL,
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

        $this->load->model('curriculum_mdl');
        $this->load->model('point_mdl');

        // 현재포인트
        $cur_point = $this->point_mdl->check_current_point($wiz_member['wm_uid']);
        if(5000 > $cur_point['wm_point'])
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0328";
            $return_array['data']['err_msg']  = "포인트가 부족합니다.";
            echo json_encode($return_array);
            exit;
        }

        // 포인트 소진
        $point = array(
            'uid'     => $wiz_member['wm_uid'],
            'name'    => $wiz_member['wm_name'],
            'point'   => -5000,
            'pt_name' => '[AHOP] 시험과정 초기화', 
            'kind'    => '5',
            'b_kind'  => 'exam',
            'regdate' => date("Y-m-d H:i:s")
        );

        /* 포인트 내역 입력 및 포인트 추가 */
        $set_point = $this->point_mdl->set_wiz_point($point);
        if($set_point)
        {
            $where = array(
                'book_id' => $request['book_id'],
                'uid'     => $wiz_member['wm_uid']
            );
            $this->curriculum_mdl->delete_wiz_book_exam_log($where);
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg']      = "포기하여 기존 STEP 시험 이력이 모두 삭제 되었습니다.";
        echo json_encode($return_array);
        exit;
    }

    /**
     * AHOP 시험 오답지우기
     * 오답지우기 티켓 사용 or 500포인트 사용해서 오답 1개를 지울수 있다
     * ex_id : 현재 진행중인 시험 문제 아이디값
     * 
     * answer_list : 현재 문제의 항목 리스트(이미 오답지우기를 사용하여 지워진 항목은 제외)
     * 배열로 받아와도 될꺼고... 그냥 문자열로 받아와도 될꺼고..(구분자넣어서)
     * 일단 배열로 받는다고 가정함
     * 
     * 최종적으로 오답 항목과 남은 티켓 or 남은 포인트를 리턴
     */
    public function ahop_exam_hint()
    {
        $return_array = array();

        $request = array(
            'wiz_id'        => trim($this->input->post('wiz_id')) ? trim($this->input->post('wiz_id')) : null,
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

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $result = array(
            'wrong_answer' => '', //지워질 항목
            'ticket'       => 0,  //남은 티켓
            'point'        => 0   //남은 포인트
        );

        $this->load->model('curriculum_mdl');
        $this->load->model('point_mdl');

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

        // 오답지우기 티켓갯수
        $ticket_result = $this->curriculum_mdl->check_ticket_count($wiz_member['wm_uid']);
        $ticket_cnt = $ticket_result ? $ticket_result['cnt'] : 0;

        $ing = false;
        if($ticket_cnt)
        {
            // 오답지우기 티켓이 있으면
            $use_ticket = $this->curriculum_mdl->use_wrong_answer_ticket($wiz_member['wm_uid']);
            if(!$use_ticket)
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0328";
                $return_array['data']['err_msg']  = "오답지우기 티켓이 부족합니다.";
                echo json_encode($return_array);
                exit;
            }

            $result['ticket'] = $ticket_cnt - 1;
            $ing = true;
        }
        else
        {
            //티켓이 없으면 포인트 소모

            // 현재포인트
            $cur_point = $this->point_mdl->check_current_point($wiz_member['wm_uid']);
            if(500 > $cur_point['wm_point'])
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0329";
                $return_array['data']['err_msg']  = "포인트가 부족합니다.";
                echo json_encode($return_array);
                exit;
            }

            // 포인트 소진
            $point = array(
                'uid'     => $wiz_member['wm_uid'],
                'name'    => $wiz_member['wm_name'],
                'point'   => -500,
                'pt_name' => '[AHOP] 오답 숨기기 찬스 사용', 
                'kind'    => '5',
                'b_kind'  => 'exam',
                'regdate' => date("Y-m-d H:i:s")
            );

            /* 포인트 내역 입력 및 포인트 추가 */
            $set_point = $this->point_mdl->set_wiz_point($point);
            if($set_point)
            {
                // 현재포인트
                $cur_point = $this->point_mdl->check_current_point($wiz_member['wm_uid']);
                $result['point'] = $cur_point ? $cur_point['wm_point'] : 0;
                $ing = true;
            }
        }

        //정상적으로 티켓사용 or 포인트 소모 되었다면
        if($ing)
        {
            // 배열 중 무작위 값 가져오기
            $rand = array_rand($request['answer_list']);
            $result['wrong_answer'] = $request['answer_list'][$rand];

            $return_array['res_code']     = '0000';
            $return_array['msg']          = "오답 숨기기 찬스를 정상적으로 사용하였습니다.";
            $return_array['data']['info'] = $result;
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0203";
            $return_array['data']['err_msg']  = "오답 숨기기 찬스사용에 실패하였습니다.";
            echo json_encode($return_array);
            exit;
        }
        
    }


}








