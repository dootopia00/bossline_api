<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Board extends _Base_Controller {
    public $upload_path_boards = ISTESTMODE ? 'test_upload/attach/boards/':'attach/boards/';
    public $upload_path_qna = ISTESTMODE ? 'test_upload/attach/qna/':'attach/qna/';
    public $upload_path_toteacher = ISTESTMODE ? 'test_upload/attach/teacher_1n1/':'attach/teacher_1n1/';
    public $upload_path_dictation = ISTESTMODE ? 'test_upload/attach/dictation/':'attach/dictation/';
    public $upload_path_correct = ISTESTMODE ? 'test_upload/attach/correct/':'attach/correct/';     // 회원이 올리는 첨삭 참고자료
    public $upload_path_summernote = ISTESTMODE ? 'test_upload/editor/summernote/':'editor/summernote/';

    public $knowledge_qna_type_board = NULL;

    public function __construct()
    {
        parent::__construct();
        
        date_default_timezone_set('Asia/Seoul');
        $this->load->library('form_validation');
        $this->knowledge_qna_type_board = $this->config->item('MBN_KNOWLEDGE_LIST');
    }

    /*
        게시판 설정
   */
    public function phpinfo()
    {
        phpinfo();
    }


    public function config()
    {
        $return_array = array();

        $request = array(
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "ex_id" => trim($this->input->post('ex_id')) ? trim($this->input->post('ex_id')):'',            // 시험후기로 들어왔을경우
            "ex_no" => trim($this->input->post('ex_no')) ? trim($this->input->post('ex_no')):'',            // 시험후기로 들어왔을경우
            "cafe" => trim($this->input->post('cafe')) ? trim($this->input->post('cafe')):'',       
            "sc_id" => trim($this->input->post('sc_id')) ? trim($this->input->post('sc_id')):'',            // 출석부로 수업대본서비스 들어왔을경우
            "cafe_unq" => trim($this->input->post('cafe_unq')) ? trim($this->input->post('cafe_unq')):'',   // 얼철딕후기로 수업대본서비스 들어왔을경우
            "event" => trim($this->input->post('event')),               // 시험합격 후 실시간요청 시 팔찌배송하기 위해 기본값 설정해주기 위한 값
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('board_mdl');

        if(!($request['table_code'] =='request' && $request['event'] == 'pass'))
        {
            $result = $this->board_mdl->row_board_config_by_table_code($request['table_code']);

            if(!$result)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
                echo json_encode($return_array);
                exit;
            }

            //팁&가이드 내용 변환
            $result['mbn_tip_guide'] = $result['mbn_tip_guide'] ? stripslashes($result['mbn_tip_guide']) : null;
        }
        
        $table_code = $request['table_code'];
        $wiz_member = base_get_wiz_member();
        // pre_content에 없는 기본 content 값 설정.
        if($wiz_member)
        {
            // ahop 후기 작성 시 ex_id와 ex_no 둘중 하나가 값으로 넘어오면 기본 content값 설정해준다.
            if($request['table_code'] =='1111' && ($request['ex_id'] || $request['ex_no'] || $request['cafe']=='true'))
            {
                if($request['cafe']=='true')
                {
                    $category = $this->board_mdl->list_board_category_by_bc_unq(52);    // 얼철딕 후기 말머리 번호 
                }
                else
                {
                    $pre_content = board_make_ahop_pre_content_new($wiz_member, $request['ex_no'],$request['ex_id']);
                    
                    if($pre_content)
                    {
                        if($pre_content['pre_content'])
                        {
                            $result['mbn_pre_content'] = $pre_content['pre_content'];
                        }
        
                        if($pre_content['pre_title'])
                        {
                            $result['mbn_pre_title'] = $pre_content['pre_title'];
                        }
        
                        $category = $this->board_mdl->list_board_category_by_bc_unq($pre_content['category_num']);
                        $category[0]['ex_id'] = $pre_content['ex_id'];  //wiz_book_exam_log 키값. 후기 글쓰기 시 해당 ex_id값을 받아 처리해야할것이 있어 세팅해서 리턴
                    }
                }
            }
            elseif($request['table_code'] =='request' && $request['event'] == 'pass')
            {
                // 시험합격 후 실시간요청 시 팔찌배송하기 위해 기본값 설정
                $result['mbn_pre_content'] = board_make_request_pre_content_for_pass();
                $result['mbn_pre_title_lock'] = 'N';
                $result['mbn_file_ext'] = '';
            }
            //수업대본 서비스
            else if($request['table_code'] == '1130')
            {
                $title = null;
                $sc_id = $request['sc_id'];
                $sim_content = null;
                $sim_content2 = null;
                $b_kind = null;
                $file_name = null;
                $content = null;
                $mins = null;
                // 얼철딕 통해 들어온 경우, $request['cafe_unq']
                if($request['cafe_unq'])
                {
                    $this->load->model('board_mdl');
                    $mint_cafe = $this->board_mdl->get_1130_by_cafe_unq($request['cafe_unq']);
                    
                    if(!$mint_cafe)
                    {
                        $return_array['res_code'] = '0900';
                        $return_array['msg'] = "프로세스오류";
                        $return_array['data']['err_code'] = "0203";
                        $return_array['data']['err_msg'] = "해당 게시물을 찾을 수 없습니다.";
                        echo json_encode($return_array);
                        exit;
                    }

                    $mins = $mint_cafe['mb_mins'];
                    $content = $mint_cafe['mb_content'];
                    $file_name = $mint_cafe['mb_filename'];
                    $sim_content = $mint_cafe['mb_vd_url'];

                    $subject = explode("--", $mint_cafe['mb_subject']);

                    // print_r($subject);exit;
                    /*
                        $subject == 247--1328--4697
                        [0] == book_id
                        [1] == tu_uid
                        [2] == 얼철딕 카운트
                    */
                    $this->load->model('book_mdl');
                    $book = $this->book_mdl->row_book_by_id($subject[0]);
                    
                    $this->load->model('tutor_mdl');
                    $tutor = $this->tutor_mdl->get_tu_name_by_tu_uid($subject[1]);

                    $title = "[".$mint_cafe['mb_class_date']."]에 [".$tutor['tu_name']."]강사님과 [".$book['book_name']."]교재로 [".$mins."분]수업한 내용";

                    $where = " uid = '".$wiz_member['wm_uid']."' AND table_code = '9002' AND board_id = '".$request['cafe_unq']."'";
                    $pivot = $this->board_mdl->checked_wiz_schedule_board_pivot($where);

                    if($pivot['wsbp_schedule_id']) $sc_id = $pivot['wsbp_schedule_id'];
                    $b_kind = $mint_cafe['mb_b_kind'];

                }
                // 출석부 통해 들어온 경우, $request['sc_id']
                else if($request['sc_id'])
                {
                    $this->load->model('lesson_mdl');
                    $shedule = $this->lesson_mdl->row_schedule_by_sc_id($request['sc_id'], $wiz_member['wm_uid']);
                    
                    if(!$shedule)
                    {
                        $return_array['res_code'] = '0900';
                        $return_array['msg'] = "프로세스오류";
                        $return_array['data']['err_code'] = "0203";
                        $return_array['data']['err_msg'] = "해당 게시물을 찾을 수 없습니다.";
                        echo json_encode($return_array);
                        exit;
                    }

                    $start_date = date('Y-m-d', strtotime($shedule['startday']));

                    
                    $this->load->model('book_mdl');
                    
                    $start_date = date('Y-m-d', strtotime($shedule['startday']));

                    $where = " WHERE wbh.lesson_id = '".$shedule['lesson_id']."' AND ( book_date < '".$start_date."' OR (book_date = '".$start_date."' AND regdate < '".$shedule['startday']."') )";
                    $order = " ORDER BY wbh.bh_id DESC";
                    $limit = " LIMIT 1";

                    $bookhistory = $this->book_mdl->row_bookhistory_by_schedule_id($where, $order, $limit);
                    
                    $title =  '['.$start_date.']에 ['.$shedule['tu_name'].']강사님과 ['.$bookhistory['book_name'].']교재로 ['.$shedule['cl_time'].']분 수업한 내용 입니다.';

            		// 폰/화상 구분자(T:폰, V:화상)
                    if($shedule['lesson_gubun'] == 'V' || $shedule['lesson_gubun'] == 'E') $b_kind = 'V';
                    else $b_kind = 'T';
            
                    $sim_content2 = $b_kind.'__'.$start_date.'__'.$shedule['cl_time'].'__'.$bookhistory['book_id'].'__'.$shedule['tu_uid'];
                    $mins = $shedule['cl_time'];

                    $where_pivot = " table_code = '9002' AND schedule_id = '".$request['sc_id']."'";
                    $pivot = $this->board_mdl->checked_wiz_schedule_board_pivot($where_pivot);

                    $mint_cafe = $this->board_mdl->get_1130_by_cafe_unq($pivot['schedule_id']);
                    $file_name = $mint_cafe['mb_filename'];
                    $content = $mint_cafe['mb_content'];
                    $sc_id = $request['sc_id'];
                }
                else
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0202";
                    $return_array['data']['err_msg'] = "나의 출석부에서 신청가능합니다.";
                    echo json_encode($return_array);
                    exit;
                }

                $result['mbn_pre_title'] = $title;
                $result['mbn_pre_content'] = $content;
                $result['mbn_sc_id'] = $sc_id;
                $result['mbn_b_kind'] = $b_kind;
                $result['mbn_sim_content'] = $sim_content;
                $result['mbn_sim_content2'] = $sim_content2;
                $result['mbn_mins'] = $mins;
                $result['mbn_filename'] = $file_name;

            }
            /*
            딕테이션 해결사
            여기서 이전 등록 글 채택했는지 처리 
            */
            else if($request['table_code'] == '1138')
            {
                
                $mins = null;
                $content = null;
                $file_name = null;
                

                // 얼철딕을 썼는지 체크
                if($request['cafe_unq'])
                {
                    $this->load->model('board_mdl');
                    $mint_cafe = $this->board_mdl->get_1130_by_cafe_unq($request['cafe_unq']);
                    
                    if(!$mint_cafe)
                    {
                        $return_array['res_code'] = '0900';
                        $return_array['msg'] = "프로세스오류";
                        $return_array['data']['err_code'] = "0203";
                        $return_array['data']['err_msg'] = "해당 게시물을 찾을 수 없습니다.";
                        echo json_encode($return_array);
                        exit;
                    }

                    $mins = $mint_cafe['mb_mins'];
                    $mc_class_date = $mint_cafe['mb_class_date'];
                    $sim_content = $mint_cafe['mb_vd_url'];

                    $subject = explode("--", $mint_cafe['mb_subject']);

                    /*
                        $subject == 247--1328--4697
                        [0] == book_id
                        [1] == tu_uid
                        [2] == 얼철딕 등록 당시 얼철딕 토탈 카운트
                    */

                    $this->load->model('book_mdl');
                    $book = $this->book_mdl->row_book_by_id($subject[0]);
                    
                    $this->load->model('tutor_mdl');
                    $tutor = $this->tutor_mdl->get_tu_name_by_tu_uid($subject[1]);

                    // 얼철딕 카운트
                    // $where_dictation = " WHERE mb.uid='".$wiz_member['wm_uid']."'";
                    // $list_cnt = $this->board_mdl->list_count_board_cafeboard($where_dictation);
                    
                    // $title = "[".$mint_cafe['mb_class_date']."]에 [".$tutor['tu_name']."]강사님과 [".$book['book_name']."]교재로 [".$mins."분]수업한 내용";
                    $title = "[".((int)$subject[2]+(int)1)."]번째 [얼철딕] 에 대한 딕테이션 요청";


                    $content = $mint_cafe['mb_content'];
                    $file_name = $mint_cafe['mb_filename'];
                    $mc_book_id = $subject[0];
                    $mc_book_name = $book['book_name'];
                    $mc_tu_uid = $subject[1];
                    $mc_tu_name = $tutor['tu_name'];
                    $mc_title = $title;

                }
                
                // (딕테이션 해결사)새 글을 쓸때 이 전에 (딕테이션 해결사) 썼던 글이 채택을 받았는지 여부
                /* $select_board = $this->board_mdl->checked_count_board_solve_select($request['table_code'], $request['wiz_id']);
                $result['mbn_board_solver'] = $select_board['cnt']; */
                
                $isset_board_solve = $this->board_mdl->checked_board_solve_cafe($request['table_code'], $request['cafe_unq']);
                $result['mbn_checked_overlap'] = 'N';
                if($isset_board_solve)
                {
                    $result['mbn_checked_overlap'] = 'Y';
                }

                
                $result['mbn_mins'] = $mins;
                $result['mbn_pre_content'] = $content;
                $result['mbn_sim_content'] = $sim_content;
                $result['mbn_filename'] = $file_name;
                $result['mbn_book_id'] = $mc_book_id;
                $result['mbn_book_name'] = $mc_book_name;
                $result['mbn_tu_uid'] = $mc_tu_uid;
                $result['mbn_tu_name'] = $mc_tu_name;
                $result['mbn_class_date'] = $mc_class_date;
                $result['mbn_title'] = $mc_title;

                
            }
        }

        if(!$category)
        {
            $where = '';
            if($request['table_code'] == '1111'){
                $where = " AND mbc.bc_unq != 52";
            }

            $category = $this->board_mdl->list_board_category_by_table_code($table_code, $where);
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "설정정보조회성공";
        $return_array['data']['category'] = $category;
        $return_array['data']['info'] = $result;
        //$return_array['data']['bookmark'] = ($bookmark) ? "Y" : "N";
        echo json_encode($return_array);
        exit;

    }

    /*
        최신글목록 / 인기글목록 / 인증글목록 
    */
    public function theme_()
    {
        $return_array = array();

        $request = array(
            "board_type" => trim($this->input->post('board_type')),
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mb.mb_unq",
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

        $index = ""; 
        $search = array();
        
        if($request['board_type'] == "new")
        {
            $index = "USE INDEX(PRIMARY)";
            
            // AND ((mb.table_code BETWEEN 1100 AND 1137) OR (mb.table_code BETWEEN 1139 AND 1199) OR (mb.table_code BETWEEN 1300 AND 1399) OR (mb.table_code = 1138 AND (mb.parent_key = '0' || mb.parent_key IS NULL )))
            array_push($search, "mb.showdate <= '".date("Y-m-d")."' AND mb.noticeYn ='N'
            AND ( mb.daum_img IS NULL OR mb.daum_img <> 'H' )
            AND (mb.table_code NOT IN ('1131', '1356','1354', '1380') OR (mb.table_code = '1356' AND mb.wiz_id != ''))
            AND ((mb.table_code BETWEEN 1100 AND 1199) OR (mb.table_code BETWEEN 1300 AND 1399))
            ");
        }

        else if($request['board_type'] == "hot")
        {
            $index = "USE INDEX(PRIMARY)";

            $request['order_field'] = "mb.mb_unq"; 
            $request['order'] = "DESC";
            /*
            $request['order_field'] = "left(mb.mb_unq,10)"; 
            $request['order'] = "DESC";
            */
            

            // AND ((mb.table_code BETWEEN 1100 AND 1137) OR (mb.table_code BETWEEN 1139 AND 1199) OR (mb.table_code BETWEEN 1300 AND 1399) OR (mb.table_code = 1138 AND (mb.parent_key = '0' || mb.parent_key IS NULL )))
            array_push($search, "mb.hit >= 100 AND mb.comm_hit > 10 AND (mb.table_code!='1356' || (mb.table_code='1356' AND mb.tu_uid IS NULL)) AND mb.table_code NOT IN ('1380')
            AND (mb.table_code BETWEEN 1100 AND 1199 OR mb.table_code BETWEEN 1300 AND 1399) 
            AND ( mb.daum_img IS NULL OR mb.daum_img <> 'H' ) AND mb.noticeYn ='N'
            ");
        }
        else if($request['board_type'] == "certify")
        {
            $index = "USE INDEX(idx_certify_date)";

            $request['order_field'] = "mb.certify_date"; 
            $request['order'] = "DESC";
            

            array_push($search, " 
            (mb.table_code BETWEEN 1100 AND 1199 OR mb.table_code BETWEEN 1300 AND 1399) 
            AND mb.showdate <= '".date("Y-m-d")."'
            AND (mb.table_code NOT IN ('1131', '1356','1354','1381', '1382') OR (mb.table_code = '1356' AND mb.wiz_id != ''))
            AND mb.certify_view ='Y'  AND ( mb.daum_img IS NULL OR mb.daum_img <> 'H' ) AND mb.noticeYn ='N'");
        }

        $wiz_member = base_get_wiz_member();
        // 리스팅 시 차단한 회원은 제외
        if($wiz_member)
        {
            $block_member_list = member_get_block_list($wiz_member['wm_uid']);
            if($block_member_list)
            {
                array_push($search, "((mb.wiz_id NOT IN ('".implode("','",$block_member_list)."') OR mb.wiz_id IS NULL))");
            }
        }


        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }
        
        $this->load->model('board_mdl');

        $list_cnt = $this->board_mdl->list_count_board_theme($request['board_type']);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        $limit = "";
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        
        $list_board = $this->board_mdl->list_theme($index, $where, $order, $limit);
        $result = board_list_writer($list_board,NULL,NULL,NULL,array('content_del'=>true));

        /*
            최신글 테마 첫페이지 공지사항 함께 출력
        */
        $notice_cnt = 0;
        /*
        if($request['board_type'] == "new" && $request['start'] == 0)
        {
            $index = "USE INDEX(idx_notice_yn)";         
            $where = "WHERE mb.noticeYn = 'A' AND (mb.table_code BETWEEN 1100 AND 1199 OR mb.table_code BETWEEN 1300 AND 1399)";
            // 리스팅 시 차단한 회원은 제외
            if($block_member_list)
            {
                $where.= " AND mb.wiz_id NOT IN ('".implode("','",$block_member_list)."')";
                
            }
            $order = "ORDER BY mb.noticeYn ,mb.mb_unq DESC";
            $limit = "";

            $list_notice = $this->board_mdl->list_board($index, $where, $order, $limit);
            if($list_notice)
            {
                $notice_cnt = sizeof($list_notice);
                $result_notice = board_list_writer($list_notice);
                $result = array_merge($result_notice, $result);
            }
            
        
        }
        */
    
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['notice_cnt'] = $notice_cnt;
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;

    }


    /*
        게시판별 목록
    */
    public function list_()
    {
        $return_array = array();

        $request = array(
            "type" => trim($this->input->post('type')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "category_code" => trim($this->input->post('category_code')),
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "except_notice" => trim($this->input->post('except_notice')),
            "include_content" => trim($this->input->post('include_content')),   // 1로 요청들어오면 content 날리지않고 리턴해준다
            "start" => trim((int)$this->input->post('start')),  // NaN으로 요청들어오는경우가 있어서 강제형변환시킴
            "limit" => trim((int)$this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mb.mb_unq",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
            "board_type" => $this->input->post('board_type') ? $this->input->post('board_type') : null, // 수업체험/이벤트체험 분류값 class: 수업체험, event: 이벤트체험
            "chapter" => $this->input->post('chapter') ? $this->input->post('chapter') : null, // (title)챕터 검색용
            "ahop" => $this->input->post('ahop') ? $this->input->post('ahop') : null, // (title)챕터 검색용
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }


        // 홈페이지 노출게시판이 아니라면 접근불가.
        if(!(($request['table_code'] >= 1100 && $request['table_code'] <= 1199) || ($request['table_code'] >= 1300 && $request['table_code'] <= 1399)))
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0313";
            $return_array['data']['err_msg'] = "권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        // 민트폐인방, 이러쿵저러쿵(임시폐쇄) 게시판 목록 접근 불가
        if($request['table_code'] == '1380' || $request['table_code'] == '1340')
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0313";
            $return_array['data']['err_msg'] = "[민트영어 2.0] 민트영어 커뮤니티가 전반적인 업그레이드 작업 중에 있습니다.<br> 
            해당 게시판의 본인 글은 로그인 후, 내 활동보기 > 내 게시글에서 열람이 가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        $count_index = "USE INDEX(idx_list_count)"; 
        $index = "USE INDEX(idx_table_code)"; 

        $search = array();
        $select_col_content = "";
        $table_code = $request['table_code'];
        

        $this->load->model('board_mdl');
        $this->load->model('lesson_mdl');

        $config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);
        if(!$config)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0319";
            $return_array['data']['err_msg'] = "게시판 정보가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        /*
            수업체험게시판/이벤트체험 게시판
        */
        $where_category = '';

        $category = NULL;
        $category = $this->board_mdl->list_board_category_by_table_code($table_code, $where_category);

        //회원 게시판 즐겨찾기 여부
        $bookmark = NULL;
        //회원 차단 목록
        $block_member_list = NULL;
        
        $wiz_member = base_get_wiz_member();

        //팁&가이드 내용 변환
        $config['mbn_tip_guide'] = $config['mbn_tip_guide'] ? stripslashes($config['mbn_tip_guide']) : null;

        // 수업중일때만 볼수 있는 게시판에는 회원이 수업중인지 체크
        $check_valid_class_member = true;
        if($config['mbn_check_inclass'] =='Y' || $config['mbn_check_holding'] =='Y')
        {
            if(!$wiz_member)
            {
                $check_valid_class_member = false;
            }
            else
            {
                if($config['mbn_check_inclass'] =='Y')
                {
                    $checkwhere[] = "'in class'";
                } 
                if($config['mbn_check_holding'] =='Y')
                {
                    $checkwhere[] = "'holding'";
                }

                $checkwhere = " AND lesson_state IN (".implode(",", $checkwhere).")";
                $check_valid_class_member = $this->lesson_mdl->check_in_class_member($wiz_member['wm_uid'],$checkwhere);
            }
            
        }

        if(!$check_valid_class_member)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0313";
            $return_array['data']['err_msg'] = "권한이 없습니다.(수업 중인 회원만 접근 할 수 있습니다.)";
            echo json_encode($return_array);
            exit;
        }


        if($request["type"] == "my")
        {
            if(!$wiz_member)
            {
                echo json_encode(base_get_err_auth_check_msg());
                exit;
            }

            $index = "USE INDEX(idx_wiz_id)"; 
            $count_index = "USE INDEX(idx_wiz_id)"; 
            array_push($search, " mb.wiz_id = '".$request['wiz_id']."'");

        }

        

        //1138(딕테이션 해결사) 예외처리(부모글은 parent_key 0)
        /* if($request['table_code'] == '1138')
        {
            if($request["type"] == "my_anwser")
            {
                if(!$wiz_member)
                {
                    echo json_encode(base_get_err_auth_check_msg());
                    exit;
                }
    
                $index = "USE INDEX(idx_wiz_id)"; 
    
                // 내가 쓴 답변글의 부모글을 구한다.
                array_push($search, " mb.mb_unq IN ( SELECT parent_key FROM mint_boards as mb_sub WHERE mb_sub.wiz_id = '".$request['wiz_id']."' AND mb_sub.parent_key > 0)");
            }
            else if($request["type"] == "selected")
            {
                if(!$wiz_member)
                {
                    echo json_encode(base_get_err_auth_check_msg());
                    exit;
                }
    
                $index = "USE INDEX(idx_wiz_id)"; 
    
                // 내가 채택받은 글을 구한다.
                array_push($search, " mb.mb_unq IN ( 
                    SELECT mb.mb_unq FROM mint_boards mb 
                    LEFT OUTER JOIN mint_boards mb2 ON mb.select_key = mb2.mb_unq
                    WHERE mb2.wiz_id = '".$request['wiz_id']."'
                )");
            }
            else
            {
                array_push($search, "(mb.parent_key IS NULL || mb.parent_key = '0')");
            }
            
        }
        else */
        if(in_array($request['table_code'], $this->knowledge_qna_type_board))
        {
            if($request["type"] == "my_anwser")
            {
                if(!$wiz_member)
                {
                    echo json_encode(base_get_err_auth_check_msg());
                    exit;
                }
    
                $index = "USE INDEX(idx_wiz_id)"; 
    
                // 내가 쓴 답변글의 부모글을 구한다.
                array_push($search, " mb.mb_unq IN ( SELECT parent_key FROM mint_boards as mb_sub WHERE mb_sub.wiz_id = '".$request['wiz_id']."' AND mb_sub.parent_key > 0)");
            }
            else if($request["type"] == "selected")
            {
                if(!$wiz_member)
                {
                    echo json_encode(base_get_err_auth_check_msg());
                    exit;
                }
    
                $index = "USE INDEX(idx_wiz_id)"; 
    
                // 내가 채택받은 글을 구한다.
                array_push($search, " mb.mb_unq IN ( 
                    SELECT mba.q_mb_unq FROM mint_boards_adopt mba
                    WHERE mba.table_code=".$request['table_code']." AND mba.a_uid = '".$wiz_member['wm_uid']."'
                )");
            }
            else if($request["type"] == "waiting")
            {
    
                $index = ""; 
    
                // 답변가능글 보기
                array_push($search, " mb.regdate > '2021-03-17 10:15:00' AND mb.mb_unq NOT IN ( 
                    SELECT mb_sub.parent_key 
                    FROM mint_boards as mb_sub 
                    WHERE mb_sub.parent_key > 0 AND mb_sub.table_code=".$request['table_code']." GROUP BY mb_sub.parent_key HAVING count(*) > 2
                ) 
                AND mb.mb_unq NOT IN (
                    SELECT mba.q_mb_unq FROM mint_boards_adopt as mba WHERE mba.table_code = mb.table_code AND mba.q_mb_unq=mb.mb_unq AND mba.type=1
                )
                AND (mb.parent_key IS NULL || mb.parent_key = '0')
                ");
            }
            else
            {
                array_push($search, "(mb.parent_key IS NULL || mb.parent_key = '0')");
            }
            
            //채택된 글 있는지 체크
            $select_col_content = ", (SELECT a_mb_unq FROM mint_boards_adopt mba WHERE mba.table_code = mb.table_code AND mba.q_mb_unq=mb.mb_unq LIMIT 1) as mba_a_mb_unq";
        }


        if($wiz_member)
        {
            // 리스팅 시 차단한 회원은 제외
            $block_member_list = member_get_block_list($wiz_member['wm_uid']);
            if($block_member_list)
            {
                array_push($search, " (mb.wiz_id NOT IN ('".implode("','",$block_member_list)."') OR mb.wiz_id IS NULL)");  // NOT IN 시 null도 제외되버려서 OR 추가함
            }

            // 회원 게시판 즐겨찾기 여부
            $bookmark = $this->board_mdl->bookmark_checked_by_wiz_id($request['wiz_id'], $request['table_code']);
        }
        

        //1127(일일명작문)의 작성자(게시판지기)는 미래날짜도 조회가능
        if($request['table_code'] != '1127' || false === stripos($wiz_member['wm_assistant_code'], "*1127*"))
        {
            array_push($search, "mb.showdate <= '".date("Y-m-d")."'");
        }

        array_push($search, " mb.table_code = '".$table_code."' AND mb.noticeYn ='N'");

        if($request['category_code'])
        {
            array_push($search, " mb.category_code = '".$request['category_code']."'");
        }

        //1365 AHOP 비디오 챕터 검색
        if($request['chapter'])
        {
            array_push($search, " mb.title LIKE '%".$request['chapter']."%'");
        }
        
        
        $array_catogory_key = null;
        // 수업체험후기/이벤트체험후기 게시판
        // if($request['table_code'] == '1111' || $request['table_code'] == '1143' ){

        //     $array_catogory_key =array();

        //     for($i=0; $i<count($category); $i++){
        //         array_push($array_catogory_key, $category[$i]['mbc_bc_unq']);
        //     }
            
        //     if(!$request['category_code']){
        //         array_push($search, " mb.category_code IN ('".implode("','", $array_catogory_key)."')");
        //     }
        // }


        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        
        $list_cnt = $this->board_mdl->list_count_board($count_index, $where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            $return_array['data']['config'] = $config;
            $return_array['data']['bookmark'] = ($bookmark) ? "Y" : "N";
            $return_array['data']['category'] = $category;
            echo json_encode($return_array);
            exit;
        }

        /*
        게시물 1만건 이상시 INDEX PRIMARY , 이하 idx_table_code
            1353: [이야기]주니어모임방
            1131: [평가]강사평가서등록
            1335: [익명]이러쿵저러쿵
            1356: MINT ENGLISH CHAT
            1354: NS과제물게시판
        */
        
        $index = "USE INDEX(idx_table_code)";
        $limit = "";
        
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        
        $list_board = $this->board_mdl->list_board($index, $where, $order, $limit, $select_col_content);

        $list_writer_config = array( 
            'content_del'=> ($request['include_content']) ? FALSE : TRUE
        );

        $result = board_list_writer($list_board, NULL, NULL, $wiz_member, $list_writer_config);
        
        if($request['table_code'] == '1130')        // 수업대본서비스
        {
            $result = board_exception_1130($result);
        }
        //else if($request['table_code'] == '1138')        // 딕테이션 해결사
        //{
            /* 딕테이션 해결사 게시글 리스트에 보드(자식) 게시물 추가 */
        //    $result = board_list_dictation_solution_add_child($result, $wiz_member);
        //}
        elseif(in_array($request['table_code'], $this->knowledge_qna_type_board))
        {
            /* 지식인 게시판 게시글 리스트에 보드(자식) 게시물 추가 */
            $result = board_list_knowledge_add_child($result, $request['table_code'], 'mb_mb_unq');
        }

        if($wiz_member)
        {
            // 일일 도전 영작문,오늘의 영어한마디, 도전 AI딕테이션. 해당글에 내가 댓글을 썻는지 체크
            if($request['table_code'] == '1127' || $request['table_code'] == '1132' || $request['table_code'] == '1144')
            {
                $result = board_exception_1127($result,$wiz_member['wm_wiz_id']);
            } 
        }

        //1365 AHOP 비디오 강좌보기일때 북마크 추가했는지 체크
        if($request['table_code'] == '1365')
        {
            $this->load->model('book_mdl');
            $res_ahop_bookmark = $this->book_mdl->list_wiz_book_vd_pass_by_uid($wiz_member['wm_uid'], $table_code);

            $result = board_list_add_ahop_bookmark($result, $res_ahop_bookmark, $request['ahop']);
            // sort($result);
        }

        $notice_cnt = 0;
        // except_notice에 값이 있으면 공지를 같이 주지않는다. ex)커리큘럼 뷰에서 공지없이 진짜 후기글만 필요한경우
        if($request['start'] == 0 && !$request['except_notice'])
        {
            $index = "USE INDEX(idx_notice_yn)";         
            $where = "WHERE mb.showdate <= '".date("Y-m-d")."' AND mb.table_code = '".$request['table_code']."' AND mb.noticeYn != 'N'";
            // 리스팅 시 차단한 회원은 제외
            if($block_member_list)
            {
                $where.= " AND (mb.wiz_id NOT IN ('".implode("','",$block_member_list)."') OR mb.wiz_id IS NULL)";
            }

            $order = "ORDER BY mb.noticeYn ,mb.mb_unq DESC";
            $limit = "";

            $list_notice = $this->board_mdl->list_board($index, $where, $order, $limit);
            
            if($list_notice)
            {
                $notice_cnt = sizeof($list_notice);
                $result_notice = board_list_writer($list_notice, NULL, NULL, NULL, array('content_del' => TRUE));
                $result = array_merge($result_notice, $result);
            }
        
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['notice_cnt'] = $notice_cnt;
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        $return_array['data']['config'] = $config;
        $return_array['data']['bookmark'] = ($bookmark) ? "Y" : "N";
        $return_array['data']['category'] = $category;
        echo json_encode($return_array);
        exit;

    }

    /*
        특수게시판 목록

        - correction - 영어첨삭게시판
        - dictation.list - 얼굴철판딕테이션
        - express - 이런표현어떻게
        - request - 실시간요청게시판
    */

    // 특수 게시판 설정 가져오기
    public function special_config()
    {
        $return_array = array();

        $request = array(
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization'))
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        // 나중에 DB에 테이블 코드를 저장해야 되지않을까 합니다
        $bookmark_code = null;
        if($request['table_code'] == 'express')
        {
            $bookmark_code = "9001";
        }
        else if($request['table_code'] == 'correction')
        {
            $bookmark_code = "9004";
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            $bookmark_code = "9002";
        }
        else if($request['table_code'] == 'request')
        {
            $bookmark_code = "9999";
        }else{
            //모두다 아닐경우 일반게시판 설정을 가져온다.
            $this->config();
            exit;
        }
        
        $this->load->model('board_mdl');
        $result = $this->board_mdl->row_board_special_config_by_table_code($bookmark_code);

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        //팁&가이드 내용 변환
        $result['mbn_tip_guide'] = $result['mbn_tip_guide'] ? stripslashes($result['mbn_tip_guide']) : null;

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "특수게시판 설정 정보 조회 성공";
        $return_array['data']['info'] = $result;
        echo json_encode($return_array);
        exit;
    }
    
    public function special_()
    {
        $return_array = array();

        $request = array(
            "type" => trim($this->input->post('type')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "category_code" => trim($this->input->post('category_code')),
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "search_key" => trim($this->input->post('search_key')),
            "search_keyword" => trim($this->input->post('search_keyword')),
            "select_book" => trim($this->input->post('select_book')),
            "select_teacher" => trim($this->input->post('select_teacher')),
            "select_w_kind" => trim($this->input->post('select_w_kind')),
            "select_w_mp" => trim($this->input->post('select_w_mp')),
            "select_tu_uid" => trim($this->input->post('select_tu_uid')),
            "select_way" => trim($this->input->post('select_way')),
            "start" => trim((int)$this->input->post('start')),  // NaN으로 요청들어오는경우가 있어서 강제형변환시킴
            "limit" => trim($this->input->post('limit')),
            "order_field" => trim(strtolower($this->input->post('order_field'))),
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


        $index = "";
        $inner_table = "";
        $search = array();

        $wiz_member = base_get_wiz_member();

        if($request["type"] == "my" && $request["wiz_id"])
        {
            /* 회원 확인 */
            if(!$wiz_member)
            {
                echo json_encode(base_get_err_auth_check_msg());
                exit;
            }

            
            if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list'
            || $request['table_code'] == 'correction')
            {
                $inner_table = "INNER JOIN wiz_member wm ON mb.uid = wm.uid AND wm.wiz_id = '".$request['wiz_id']."'";
            }
            else
            {
                array_push($search, " mb.wiz_id = '".$request['wiz_id']."'");
            }
        }

        //부모글은 parent_key
        if($request['table_code'] == 'express')
        {
            if($request["type"] == "my_anwser")
            {
                if(!$wiz_member)
                {
                    echo json_encode(base_get_err_auth_check_msg());
                    exit;
                }
    
                $index = ""; 
    
                // 내가 쓴 답변글의 부모글을 구한다.
                array_push($search, " mb.uid IN ( SELECT parent_key FROM mint_express as mb_sub WHERE mb_sub.wiz_id = '".$request['wiz_id']."' AND mb_sub.parent_key > 0)");
            }
            else if($request["type"] == "selected")
            {
                if(!$wiz_member)
                {
                    echo json_encode(base_get_err_auth_check_msg());
                    exit;
                }
    
                $index = ""; 
    
                // 내가 채택받은 글을 구한다.
                array_push($search, " mb.uid IN ( 
                    SELECT mba.q_mb_unq FROM mint_boards_adopt mba
                    WHERE mba.table_code=9001 AND mba.a_uid = '".$wiz_member['wm_uid']."'
                )");
            }
            else if($request["type"] == "waiting")
            {
    
                $index = ""; 
    
                // 답변가능글 보기
                array_push($search, " mb.regdate > '2021-03-17 10:15:00' AND mb.uid NOT IN ( 
                    SELECT mb_sub.parent_key 
                    FROM mint_express as mb_sub 
                    WHERE mb_sub.parent_key > 0 GROUP BY mb_sub.parent_key HAVING count(*) > 2
                ) 
                AND mb.uid NOT IN (
                    SELECT mba.q_mb_unq FROM mint_boards_adopt as mba WHERE mba.table_code = 9001 AND mba.q_mb_unq=mb.uid AND mba.type=1
                )
                AND (mb.parent_key IS NULL || mb.parent_key = '0') ");
            }
            else
            {
                array_push($search, "(mb.parent_key IS NULL || mb.parent_key = '0')");
            }
            
        }


        if($request['search_key'] && $request['search_keyword'])
        {
            $index = "";

            if($request['table_code'] == 'express')
            {
                // 질문+답변
                if($request['search_key'] =='qna')
                {
                    array_push($search, "(mb.content like '%".$request['search_keyword']."%' OR mbc.comment like '%".$request['search_keyword']."%')");
                }
                // 질문, 답변,질문자, 답변자 mb.content,mbc.comment, mb.m_name,mbc.c_name
                else
                {
                    array_push($search, $request['search_key']." like '%".$request['search_keyword']."%'");
                }

                if($request['search_key'] =='qna' || $request['search_key'] =='mbc.comment' || $request['search_key'] =='mbc.c_name')
                {
                    $inner_table = ' JOIN mint_express_com as mbc ON mb.uid=mbc.e_id ';
                }

            }
            else
            {
                if( ($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list'
                || $request['table_code'] == 'correction') && $request['search_key'] == "wm.nickname")
                {
                    $inner_table = "INNER JOIN wiz_member wm ON mb.uid = wm.uid AND wm.nickname like '".$request['search_keyword']."%'";
                }
                else
                {
                    array_push($search, $request['search_key'] ." like '".$request['search_keyword']."%'");
                }
            }

        }

        if(strpos($request['table_code'],'dictation') !==false)
        {
            array_push($search, "mb.notice_yn ='N' AND mb.del_yn ='N'");

            
            /* 얼굴철판딕테이션 검색 */
            if($request['select_teacher'])
            {
                array_push($search, "mb.tu_name ='".$request['select_teacher']."'");
            }

            if($request['select_book'])
            {
                array_push($search, "mb.book_name ='".$request['select_book']."'");
            }

            if($request['select_way'])
            {
                if($request['select_way'] == 'T' || $request['select_way'] == 'V'){
                    array_push($search, "mb.notice_yn ='N' AND mb.b_kind = '".$request['select_way']."' AND mb.del_yn ='N'");
                }                
            }

        }
        

        /* 영어첨삭 검색 */
        if($request['table_code'] == 'correction' && $request["select_w_kind"])
        {
            array_push($search, "mb.w_kind  ='".$request["select_w_kind"]."'");
        }
        
        if($request['table_code'] == 'correction' && $request["select_w_mp"])
        {
            array_push($search, "mb.w_mp3  ='".$request["select_w_mp"]."'");
        }

        if($request['table_code'] == 'correction' && $request["select_tu_uid"])
        {
            array_push($search, "mb.tu_uid  ='".$request["select_tu_uid"]."'");
        }

        
        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }
        
        $list_cnt = NULL;
        $table_name = NULL;
        $bookmark_code = NULL;

        $this->load->model('board_mdl');

        if($request['table_code'] == 'express')
        {
            $table_name = "이런표현어떻게";
            $bookmark_code = "9001";
            $request['order_field'] = ($request['order_field']) ? $request['order_field'] : "mb.uid";
            $list_cnt = $this->board_mdl->list_count_board_express($where, $inner_table);
        }
        else if($request['table_code'] == 'correction')
        {
            $table_name = "영어첨삭게시판";
            $bookmark_code = "9004";
            $request['order_field'] = ($request['order_field']) ? $request['order_field'] : "mb.w_id";
            $list_cnt = $this->board_mdl->list_count_board_wiz_correct($where, $inner_table);
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            $table_name = "얼굴철판딕테이션";
            $bookmark_code = "9002";
            $request['order_field'] = ($request['order_field']) ? $request['order_field'] : "mb.c_uid";
            $list_cnt = $this->board_mdl->list_count_board_cafeboard($where, $inner_table);
        }
        else if($request['table_code'] == 'request')
        {
            $table_name = "실시간요청게시판";
            $bookmark_code = "9999";
            $request['order_field'] = ($request['order_field']) ? $request['order_field'] : "mb.sp_id";
            $list_cnt = $this->board_mdl->list_count_board_wiz_speak($where);
        }

        $bookmark = NULL;
        if($request['wiz_id'])
        {
            $bookmark = $this->board_mdl->bookmark_checked_by_wiz_id($request['wiz_id'], $bookmark_code);
        }

        $config = array(
            "mbn_table_code" => $request['table_code'],
            "mbn_table_name" =>	$table_name,
            "mbn_bookmark_yn" => "Y",
            "mbn_write_login" => "Y",
            "mbn_anonymous_yn" => "N",
            "mbn_recom_yn" => "추천 여부",
            "mbn_certify_yn" => "인증 여부",
            "mbn_category_yn" => "카테고리 여부",
            "mbn_pre_title_lock" =>	"본인글만 보기",
            "mbn_view_login" => "로그인 글보기",
        );

        $config_special = $this->board_mdl->row_board_special_config_by_table_code($bookmark_code);
        
        if($config_special)
        {
            $config = array_merge($config, $config_special);
        }
        
        //팁&가이드 내용 변환
        $config['mbn_tip_guide'] = $config['mbn_tip_guide'] ? stripslashes($config['mbn_tip_guide']) : null;
       
        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            $return_array['data']['bookmark'] = ($bookmark) ? "Y" : "N";
            $return_array['data']['config'] = $config;
            echo json_encode($return_array);
            exit;
        }
        
        $notice_cnt = 0;
        $limit = "";
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
      
        
        $list_board = NULL;
        if($request['table_code'] == 'express')
        {
            // 리스트에 각글을 내가 스크랩했는지 여부 포함
            $select_col_content = '';
            if($wiz_member['wm_wiz_id'])
            {
                $select_col_content = ',(select CASE WHEN count(1) > 0 THEN "Y" ELSE "N" END from mint_clip_boards mcb WHERE mcb.table_code=9001 AND mb.uid = mcb.mb_unq AND mcb.reg_wiz_id="'.$wiz_member['wm_wiz_id'].'") as mb_clip_yn';
            }

            //채택된 글 있는지 체크
            $select_col_content .= ", (SELECT a_mb_unq FROM mint_boards_adopt mba WHERE mba.table_code = 9001 AND mba.q_mb_unq=mb.uid LIMIT 1) as mba_a_mb_unq";
            
            $list_board = $this->board_mdl->list_board_express($index, $where, $order, $limit,$select_col_content, $inner_table);
            $result = board_list_writer($list_board);

            $result = board_list_knowledge_add_child($result, $request['table_code'], 'mb_uid');
        }
        else if($request['table_code'] == 'correction')
        {
            
            $list_board = $this->board_mdl->list_board_wiz_correct($index, $where, $order, $limit, $inner_table);
            $result = board_list_writer($list_board);
        }
        else if( ($request['table_code'] == "dictation.t" || $request['table_code'] == 'dictation.v' 
        || $request['table_code'] == 'dictation.list'))
        {

            //$index = ($request['order_field'] == "mb.recom") ? "USE INDEX(idx_recom)" : "USE INDEX(PRIMARY)";
            
            $index = "";
            $select_col_content = "";

            if($inner_table == "")
            {
                $inner_table = "INNER JOIN wiz_member wm ON mb.uid = wm.uid";
            }
            
            if($request['table_code'] == 'dictation.t')
            {
                $select_col_content = " 'dictation.t' as mb_table_code, '실시간전화영어듣기' as mbn_table_name,";
            }
            else if($request['table_code'] == 'dictation.v')
            {
                $select_col_content = " 'dictation.v' as mb_table_code, '실시간화상영어보기' as mbn_table_name,";
            }
            else if($request['table_code'] == 'dictation.list')
            {
                $select_col_content = " 'dictation.list' as mb_table_code, '얼굴철판딕테이션' as mbn_table_name,";
            }

            if($request['search_key'] && $request['search_keyword'] && $request['search_key'] == "mb.content")
            {
                $select_col_content .= "mb.content as mb_content,";
            }
            else if($request['search_key'] && $request['search_keyword'] && $request['search_key'] == "mb.content2")
            {
                $select_col_content .= "mb.content2 as mb_content,";
            }

            /*
                검색 내용표시부터
            */
            $list_board = $this->board_mdl->list_board_cafeboard($index, $where, $order, $limit, $select_col_content, $inner_table);

            $result = board_list_writer($list_board, (($request['search_key'] == "mb.content" || $request['search_key'] == "mb.content2") && $request['search_keyword']) ? $request['search_keyword'] : NULL);
            /*
                화상얼철딕 첫페이지 공지사항
            */
            if( ($request['table_code'] == "dictation.t" || $request['table_code'] == 'dictation.v' 
            || $request['table_code'] == 'dictation.list') && $request['start'] == 0)
            {
                
                
                $where = "";
                if($request['table_code'] == 'dictation.t')
                {
                    $where = "WHERE mb.notice_yn = 'Y' AND mb.del_yn ='N' AND mb.b_kind = 'T'";
                }
                else if($request['table_code'] == 'dictation.v')
                {
                    $where = "WHERE mb.notice_yn = 'Y' AND mb.del_yn ='N' AND mb.b_kind = 'V'";
                }
                else if($request['table_code'] == 'dictation.list')
                {
                    $where = "WHERE mb.notice_yn = 'Y' AND mb.del_yn ='N'";
                }
                
                $index = "USE INDEX(idx_notice_yn)";         
                $order = "ORDER BY mb.c_uid DESC";
                $limit = "";
                /**
                 * request['real_time'] = 'Y' 일 경우 얼굴철판딕테이션 공지사항 제거
                 * request['real_time'] = 'N' 일 경우 얼굴철판딕테이션 공지사항 추가
                 */
                $list_notice = $this->board_mdl->list_board_cafeboard_notice($index, $where, $order, $limit, $select_col_content);
                if($list_notice)
                {
                    $notice_cnt = sizeof($list_notice);
                    $result_notice = board_list_writer($list_notice);
                    $result = array_merge($result_notice, $result);
                }
            
        
            }
            
        }
        else if($request['table_code'] == 'request')
        {
            
            $list_board = $this->board_mdl->list_board_wiz_speak($index, $where, $order, $limit);
            $result = board_list_writer($list_board);
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['notice_cnt'] = $notice_cnt;
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        $return_array['data']['bookmark'] = ($bookmark) ? "Y" : "N";
        $return_array['data']['config'] = $config;
        echo json_encode($return_array);
        exit;

    }

    /*
        새댓글목록
    */
    public function comment_()
    {
        $return_array = array();

        $request = array(
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mbc.co_unq",
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

        $search = array();
        
        // AND ((mb.table_code BETWEEN 1100 AND 1137) OR (mb.table_code BETWEEN 1139 AND 1199) OR (mb.table_code BETWEEN 1300 AND 1399) OR (mb.table_code = 1138 AND (mb.parent_key = '0' || mb.parent_key IS NULL )))
        array_push($search, "mbc.table_code NOT IN (1127, 1129, 1356, 1380, 1381, 1382, 1379,1144) 
        AND (mbc.table_code BETWEEN 1100 AND 1199 OR mbc.table_code BETWEEN 1300 AND 1399) 
        AND ( mbc.tu_uid IS NULL OR mbc.tu_uid <> '99999' )");
        
        // 본인글 만 보기 체크되어있는 게시판은 제외
        $search[] = "mbn.pre_title_lock NOT IN ('YN', 'YY')";
        
        $wiz_member = base_get_wiz_member();
        // 리스팅 시 차단한 회원은 제외
        if($wiz_member)
        {
            $block_member_list = member_get_block_list($wiz_member['wm_uid']);
            if($block_member_list)
            {
                array_push($search, "(mbc.writer_id NOT IN ('".implode("','",$block_member_list)."') OR mbc.writer_id IS NULL)");
            }
        }

        $where = "";

        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('board_mdl');

        $list_cnt = $this->board_mdl->list_count_board_theme("comment");

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        $limit = "";
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $index = ' USE INDEX(PRIMARY) ';
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

        $list_comment = $this->board_mdl->list_comment($where, $order, $limit, $index);
        $result = board_list_writer($list_comment);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;

    }

    /*
        게시물 정보
    */
    public function article()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "board_type" => trim($this->input->post('board_type')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "mb_unq" => trim($this->input->post('mb_unq')),
            "wiz_id" => trim($this->input->post('wiz_id')),
            "order_type" => ($this->input->post('order_type')) ? trim(strtolower($this->input->post('order_type'))) : "new",
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $result = NULL;
        $config = NULL;

        $this->load->model('board_mdl');
        $config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);


        $wiz_member = base_get_wiz_member();

        if($request['table_code'] == 'express')
        {
            $article = $this->board_mdl->row_article_express_by_mb_uid($request['mb_unq']);
            if($article)
            {
                $result = board_article_writer($article);
            }
            $config = $this->board_mdl->row_board_special_config_by_table_code(9001);
        }
        else if($request['table_code'] == 'request')
        {
            $article = $this->board_mdl->row_article_request_by_sp_id($request['mb_unq']);
            if($article)
            {
                $result = board_article_writer($article);
            }
        }
        else if($request['table_code'] == 'correction')
        {
            $article = $this->board_mdl->row_article_wiz_correct_by_w_id($request['mb_unq']);

            if($article)
            {
                $result = board_article_writer($article);
            }
            $config = $this->board_mdl->row_board_special_config_by_table_code(9004);
        }
        else if(strpos($request['table_code'],'dictation') !==false)
        {
            $article = $this->board_mdl->row_article_cafeboard_by_c_uid($request['mb_unq']);
            if($article)
            {
                $result = board_article_writer($article);
            }
            $config = $this->board_mdl->row_board_special_config_by_table_code(9002);
        }
        else if($request['table_code'] == "toteacher")
        {
            $article = 1;
            $result = $this->board_mdl->row_article_toteacher_by_to_id($request['mb_unq']);

            if($result && $result['mb_uid'] != $wiz_member['wm_uid'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0203";
                $return_array['data']['err_msg'] = "해당 게시물을 찾을 수 없습니다.";
                $return_array['data']['config'] = $config;
                echo json_encode($return_array);
                exit;
            }
        }
        else
        {
            /* 
                일반게시판
            */
            
            // 홈페이지 노출게시판이 아니라면 접근불가.
            if(!(($request['table_code'] >= 1100 && $request['table_code'] <= 1199) || ($request['table_code'] >= 1300 && $request['table_code'] <= 1399)))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0313";
                $return_array['data']['err_msg'] = "권한이 없습니다.";
                echo json_encode($return_array);
                exit;
            }

            $article = $this->board_mdl->row_article_by_mb_unq($request['table_code'], $request['mb_unq']);
            
            if($article)
            {
                //검색테이블 업데이트(조회수 증감에 따른)
                $search_params = array(
                    'mb_unq' => $request['mb_unq'],
                    'hit'    => $article['mb_hit'],
                    'recom'  => $article['mb_recom']
                );
                $this->board_mdl->update_search_boards($request['table_code'], $search_params);

                $result = board_article_writer($article);

                // 블라인드 요청한 게시글인지 체크
                if($wiz_member)
                {
                    $check_blind = $this->board_mdl->check_already_blinded($request['table_code'],$wiz_member['wm_uid'],$request['mb_unq'],0);
                    $result['mbh_blind_state'] = $check_blind ? '1':'0';
                }
                
                //수업대본 서비스 일경우 tu_name 추가
                if($request['table_code'] == "1130")
                {
                    if($article['mb_tu_uid'])
                    {
                        $this->load->model('tutor_mdl');
                        $tutor = $this->tutor_mdl->get_tu_name_by_tu_uid($article['mb_tu_uid']);
                        $result['mb_tu_name'] = $tutor['tu_name'];
                    }
                    
                    if($article['mb_cafe_unq'])
                    {
                        //얼철딕으로 등록된 파일 추가하기 위해서
                        $mint_cafeboard = $this->board_mdl->get_1130_by_cafe_unq($article['mb_cafe_unq']);
                        $result['cafe_filename'] = $mint_cafeboard['mb_filename'];
                    }
                    
                }
                else if(false && $request['table_code'] == "1138")
                {
                    if($article['mb_noticeYn'] != 'Y')
                    {
                        
                        /* 상세보기에 딕테이션 해결사 게시글에 댓글 게시물 정리 추가 */
                        $result = board_article_dictation_solution_add_child($result);

                        $mins = null;
                        $content = null;
                        $file_name = null;
                        $result['mbn_checked_wrote'] = 'N';
                        
                        // 딕테이션 해결사 권한 or 게시판 지기 권한 체크 변수
                        $result['is_auth_solve'] ="N";

                        $type = 'Dictation';
                        $type2 = 'Helper';

                        $badge_solver = member_checked_badge($wiz_member['wm_uid'], $type, $type2);
                        
                        if($badge_solver)
                        {
                            $result['is_auth_solve'] ="Y";
                        }

                        //딕테이션 해결사 (게시판 지기 활동여부)
                        if(false !== stripos($wiz_member['wm_assistant_code'], "*solver*"))
                        {
                            $result['is_auth_solve'] ="Y";
                        }


                        // 얼철딕을 썼는지 체크
                        if($result['mb_cafe_unq'])
                        {
                            $this->load->model('board_mdl');
                            $mint_cafe = $this->board_mdl->get_1130_by_cafe_unq($result['mb_cafe_unq']);
                            

                            if(!$mint_cafe)
                            {
                                $return_array['res_code'] = '0900';
                                $return_array['msg'] = "프로세스오류";
                                $return_array['data']['err_code'] = "0203";
                                $return_array['data']['err_msg'] = "해당 얼철딕 게시물을 찾을 수 없습니다.";
                                echo json_encode($return_array);
                                exit;
                            }

                            $mins = $mint_cafe['mb_mins'];
                            $mc_class_date = $mint_cafe['mb_class_date'];
                            $sim_content = $mint_cafe['mb_vd_url'];

                            $subject = explode("--", $mint_cafe['mb_subject']);

                            /*
                                $subject == 247--1328--4697
                                [0] == book_id
                                [1] == tu_uid
                                [2] == 얼철딕 카운트
                            */

                            // book_id로 조회한 book 정보
                            $this->load->model('book_mdl');
                            $book = $this->book_mdl->row_book_by_id($subject[0]);
                            
                            //f_id로 조회한 book 정보
                            $book_info = $this->book_mdl->row_book_by_sub_query($subject[0]);
                            $book_table_code = str_replace('href=../boards/board_list.php?table_code=','',$book_info['book_link2']);

                            $this->load->model('tutor_mdl');
                            $tutor = $this->tutor_mdl->get_tu_name_by_tu_uid($subject[1]);
                            
                            // 이 글에 내가 답변을 했는지 ( 한 uid당 딕테이셩 해결사 답변 1개만 가능 )
                            $isset_board_solve = $this->board_mdl->checked_board_solve_wrote($request['table_code'], $result['mb_cafe_unq'], $request['wiz_id']);
                            
                            if($isset_board_solve)
                            {
                                $result['mbn_checked_wrote'] = 'Y';
                            }
                            

                            $today = date('Y-m-d');
                            // 얼철딕 카운트
                            $where_dictation = " WHERE mb.uid='".$wiz_member['wm_uid']."'";
                            $list_cnt = $this->board_mdl->list_count_board_cafeboard($where_dictation);
                        
                            $title = "[".$list_cnt['cnt']."]번째 [얼철딕]";

                            $wrote_board_count_parent = $this->board_mdl->checked_count_today_write_1138_parent($wiz_member['wm_wiz_id'], $request['table_code'], $today);
                            $wrote_board_count_child = $this->board_mdl->checked_count_today_write_1138_child($wiz_member['wm_wiz_id'], $request['table_code'], $today);

                            /*
                                딕테이션 의뢰글 1일 5회
                                딕테이션 답변글 1일 5회
                            */
                            
                            $result['mbn_today_wrote_parent_count'] = $wrote_board_count_parent['cnt'];
                            $result['mbn_today_wrote_parent_count_limit'] = $this->limit_oneday_count_parent;
                            $result['mbn_today_wrote_child_count'] = $wrote_board_count_child['cnt'];
                            $result['mbn_today_wrote_child_count_limit'] = $this->limit_oneday_count_parent;

                            
                            $title = "[".$list_cnt['cnt']."]번째 [얼철딕]";

                        }
                        else
                        {

                            $return_array['res_code'] = '0900';
                            $return_array['msg'] = "프로세스오류";
                            $return_array['data']['err_code'] = "0400";
                            $return_array['data']['err_msg'] = "cafe_unq 를 입력해주세요.";
                            echo json_encode($return_array);
                            exit;
                            
                        }

                    $result['book_table_code'] = $book_table_code;
                    $result['mbn_mins'] = $mins;
                    $result['mbn_pre_content'] = $mint_cafe['mb_content'];
                    $result['mbn_sim_content'] = $sim_content;
                    $result['mbn_filename'] = $mint_cafe['mb_filename'];
                    $result['mbn_book_id'] = $subject[0];
                    $result['mbn_book_name'] = $book['book_name'];
                    $result['mbn_tu_uid'] = $subject[1];
                    $result['mbn_tu_name'] = $tutor['tu_name'];
                    $result['mbn_class_date'] = $mc_class_date;
                    $result['mbn_title'] = $title;
                    
                    }

                }

            }
            
        }

        // 게시물 조회시 차단한 회원의 게시물은 조회불가
        if($wiz_member)
        {
            $block_member_list = $this->member_mdl->check_member_block($wiz_member['wm_uid'],  $result['mb_wiz_id']);
            if($block_member_list)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0352";
                $return_array['data']['err_msg'] = "차단한 회원의 게시글은 조회할 수 없습니다.";
                $return_array['data']['config'] = $config;
                echo json_encode($return_array);
                exit;

            }
        }

        

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0203";
            $return_array['data']['err_msg'] = "해당 게시물을 찾을 수 없습니다.";
            $return_array['data']['config'] = $config;
            echo json_encode($return_array);
            exit;
        }

        

        if(in_array($request['table_code'], $this->knowledge_qna_type_board))
        {
            $var_conf = board_knowledge_var_conf($request['table_code']);

            $config['limit_oneday_count_parent'] = $var_conf['limit_oneday_count_parent'];
            $config['limit_oneday_count_child'] = $var_conf['limit_oneday_count_child'];
            $config['limit_reply_count_solver'] = $var_conf['limit_reply_count_solver'];

            //채택된 답변글 있는지 체크
            $adopt = $this->board_mdl->row_find_child_article_adopt($request['table_code'] == 'express' ? 9001:$request['table_code'] , $request['mb_unq']);
            $result['mba_a_mb_unq'] = $adopt ? $adopt['mba_a_mb_unq']:'';

            //지식인 게시판에 달린 답변글있으면 붙여준다.list_와 같은 함수를 쓰기에 첫 파라미터를 배열로 담아 넘긴다.
            $result = board_list_knowledge_add_child(array($result), $request['table_code'], $request['table_code'] == 'express' ? 'mb_uid':'mb_unq', true, false);
            $result = $result[0];

            // 이 글에 내가 답변을 했는지 ( 한 uid당 딕테이셩 해결사 답변 1개만 가능 )
            $result['mbn_checked_wrote'] = 'N';
            if($request['wiz_id'])
            {
                if($request['table_code'] == 'express')
                {
                    $isset_board_solve = $this->board_mdl->checked_knowledge_article_anwsered_express($request['mb_unq'], $request['wiz_id']);   
                }
                else
                {
                    $isset_board_solve = $this->board_mdl->checked_knowledge_article_anwsered($request['table_code'], $request['mb_unq'], $request['wiz_id']);
                }
            
                if($isset_board_solve)
                {
                    $result['mbn_checked_wrote'] = 'Y';
                }
            }

            if($request['table_code'] == "1138")
            {
                /* 상세보기에 딕테이션 해결사 게시글에 댓글 게시물 정리 추가 */
                $mins = null;
                $content = null;
                $file_name = null;
                
                // 딕테이션 해결사 권한 or 게시판 지기 권한 체크 변수
                $result['is_auth_solve'] ="N";

                $type = 'Dictation';
                $type2 = 'Helper';

                $badge_solver = member_checked_badge($wiz_member['wm_uid'], $type, $type2);
                
                //딕테이션 해결사 (게시판 지기 활동여부)
                if($badge_solver || false !== stripos($wiz_member['wm_assistant_code'], "*solver*"))
                {
                    $result['is_auth_solve'] ="Y";
                }

                if($article['mb_noticeYn'] != 'Y')
                {
                    // 얼철딕을 썼는지 체크
                    if($result['mb_cafe_unq'])
                    {
                        $this->load->model('board_mdl');
                        $mint_cafe = $this->board_mdl->get_1130_by_cafe_unq($result['mb_cafe_unq']);

                        if(!$mint_cafe)
                        {
                            $return_array['res_code'] = '0900';
                            $return_array['msg'] = "프로세스오류";
                            $return_array['data']['err_code'] = "0203";
                            $return_array['data']['err_msg'] = "해당 얼철딕 게시물을 찾을 수 없습니다.";
                            echo json_encode($return_array);
                            exit;
                        }

                        $mins = $mint_cafe['mb_mins'];
                        $mc_class_date = $mint_cafe['mb_class_date'];
                        $sim_content = $mint_cafe['mb_vd_url'];

                        $subject = explode("--", $mint_cafe['mb_subject']);

                        /*
                            $subject == 247--1328--4697
                            [0] == book_id
                            [1] == tu_uid
                            [2] == 얼철딕 카운트
                        */

                        // book_id로 조회한 book 정보
                        $this->load->model('book_mdl');
                        $book = $this->book_mdl->row_book_by_id($subject[0]);
                        
                        //f_id로 조회한 book 정보
                        $book_info = $this->book_mdl->row_book_by_sub_query($subject[0]);
                        $book_table_code = str_replace('href=../boards/board_list.php?table_code=','',$book_info['book_link2']);

                        $this->load->model('tutor_mdl');
                        $tutor = $this->tutor_mdl->get_tu_name_by_tu_uid($subject[1]);

                        // 얼철딕 카운트
                        $where_dictation = " WHERE mb.uid='".$wiz_member['wm_uid']."'";
                        $list_cnt = $this->board_mdl->list_count_board_cafeboard($where_dictation);
                        
                        $title = "[".$list_cnt['cnt']."]번째 [얼철딕]";

                    }
                    else
                    {

                        $return_array['res_code'] = '0900';
                        $return_array['msg'] = "프로세스오류";
                        $return_array['data']['err_code'] = "0400";
                        $return_array['data']['err_msg'] = "cafe_unq 를 입력해주세요.";
                        echo json_encode($return_array);
                        exit;
                        
                    }
                }
                
                
                $result['book_table_code'] = $book_table_code;
                $result['mbn_mins'] = $mins;
                $result['mbn_pre_content'] = $mint_cafe['mb_content'];
                $result['mbn_sim_content'] = $sim_content;
                $result['mbn_filename'] = $mint_cafe['mb_filename'];
                $result['mbn_book_id'] = $subject[0];
                $result['mbn_book_name'] = $book['book_name'];
                $result['mbn_tu_uid'] = $subject[1];
                $result['mbn_tu_name'] = $tutor['tu_name'];
                $result['mbn_class_date'] = $mc_class_date;
                $result['mbn_title'] = $title;
            }
            
        }
        
        
        /* 댓글 추천 여부 확인 */
        $recoomend_join_table = "";
        $recoomend_select = "";
    
        /* 게시글 추천 ,스크랩,블라인드 여부 */
        if($request['wiz_id'])
        {
            
            /* 회원 확인 */
            if(!$wiz_member)
            {
                echo json_encode(base_get_err_auth_check_msg());
                exit;
            }

            /* 회원정보 */
            $this->load->model('member_mdl');

            if($request['table_code'] == 'express')
            {
            
                $recommend_code = "9001";
                $recommend_where = "WHERE mr.table_code = ".$recommend_code." AND mr.mb_unq = ".$request['mb_unq']." AND mr.send_uid = ".$wiz_member['wm_uid'];
                $checked = $this->board_mdl->checked_article_recommend_by_wm_uid($recommend_where);
                $result['mb_recommend_yn'] = ($checked) ? "Y" : "N";
                
                /* 댓글 추천 여부 확인 */
                $recoomend_select = ", if( mr.re_unq IS NOT NULL , 'Y', 'N' ) as mbc_recommend_yn";
                $recoomend_join_table = "LEFT OUTER JOIN mint_recommend mr ON mbc.uid = mr.co_unq AND mr.table_code = ".$recommend_code." AND mr.send_uid = ".$wiz_member['wm_uid'];

                /* 스크랩 여부 확인 */
                $where = "WHERE mcb.reg_wiz_id = '".$request['wiz_id']."' AND mcb.table_code = '".$recommend_code."' AND mcb.mb_unq = '".$request['mb_unq']."'";
                $clip = $this->board_mdl->checked_article_clip_by_wiz_id($where);
                $result['mb_clip_yn'] = ($clip) ? "Y" : "N";

            }
            else if($request['table_code'] == 'request')
            {
                //추천기능없음

                /* 이전 다음글 (회원 본인이 작성한글) */
                $next = $this->board_mdl->row_next_article_request_by_wiz_id($request['mb_unq'] ,$wiz_member['wm_wiz_id']);
                $pre = $this->board_mdl->row_pre_article_request_by_wiz_id($request['mb_unq'] ,$wiz_member['wm_wiz_id']);

                $result['next'] = $next;
                $result['pre'] = $pre;
            }
            else if($request['table_code'] == 'correction')
            {
                //추천기능없음
                $recommend_code = "9004";
                $recommend_where = "WHERE mr.table_code IN (".$recommend_code.") AND mr.mb_unq = ".$request['mb_unq']." AND mr.send_uid = ".$wiz_member['wm_uid'];
                $checked = $this->board_mdl->checked_article_recommend_by_wm_uid($recommend_where);
                $result['mb_recommend_yn'] = ($checked) ? "Y" : "N";

                /* 스크랩 여부 확인 */
                $where = "WHERE mcb.reg_wiz_id = '".$request['wiz_id']."' AND mcb.table_code = '".$recommend_code."' AND mcb.mb_unq = '".$request['mb_unq']."'";
                $clip = $this->board_mdl->checked_article_clip_by_wiz_id($where);
                $result['mb_clip_yn'] = ($clip) ? "Y" : "N";

            }
            else if(strpos($request['table_code'],'dictation') !==false)
            {
                //9002, 9003같이 사용중
                $recommend_code = "9002,9003";
                $recommend_where = "WHERE mr.table_code IN (".$recommend_code.") AND mr.mb_unq = ".$request['mb_unq']." AND mr.send_uid = ".$wiz_member['wm_uid'];
                $checked = $this->board_mdl->checked_article_recommend_by_wm_uid($recommend_where);
                $result['mb_recommend_yn'] = $checked && $checked['send_uid'] != $checked['receive_uid'] ? "Y" : "N";
                $result['mb_derecommend_yn'] = $checked && $checked['send_uid'] == $checked['receive_uid'] ? "Y" : "N";

                /* 댓글 추천 여부 확인 */
                $recoomend_select = ", if( mr.re_unq IS NOT NULL , 'Y', 'N' ) as mbc_recommend_yn";
                $recoomend_join_table = "LEFT OUTER JOIN mint_recommend mr ON mbc.unq = mr.co_unq AND mr.table_code IN (".$recommend_code.") AND mr.send_uid = ".$wiz_member['wm_uid'];
                
                /* 스크랩 여부 확인 */
                $where = "WHERE mcb.reg_wiz_id = '".$request['wiz_id']."' AND mcb.table_code IN(".$recommend_code.") AND mcb.mb_unq = '".$request['mb_unq']."'";
                $clip = $this->board_mdl->checked_article_clip_by_wiz_id($where);
                $result['mb_clip_yn'] = ($clip) ? "Y" : "N";

            }
            else if($request['table_code'] == "toteacher")
            {
                //추천기능없음
                
                /* 이전 다음글 (회원 본인이 작성한글) */
                $next = $this->board_mdl->row_next_article_toteacher_by_wiz_id($request['mb_unq'] ,$wiz_member['wm_wiz_id']);
                $pre = $this->board_mdl->row_pre_article_toteacher_by_wiz_id($request['mb_unq'] ,$wiz_member['wm_wiz_id']);

                $result['next'] = $next;
                $result['pre'] = $pre;
            }
            else
            {
                $recommend_code = $request['table_code'];
                $recommend_where = "WHERE mr.table_code = ".$recommend_code." AND mr.co_unq=0 AND mr.mb_unq = ".$request['mb_unq']." AND mr.send_uid = ".$wiz_member['wm_uid'];
                $checked = $this->board_mdl->checked_article_recommend_by_wm_uid($recommend_where);
                $result['mb_recommend_yn'] = ($checked) ? "Y" : "N";

                /* 댓글 추천 여부 확인 */
                $recoomend_select = ", if( mr.re_unq IS NOT NULL , 'Y', 'N' ) as mbc_recommend_yn";
                $recoomend_join_table = "LEFT OUTER JOIN mint_recommend mr ON mbc.co_unq = mr.co_unq AND mr.table_code IN (".$recommend_code.") AND mr.send_uid = ".$wiz_member['wm_uid'];
            
                /* 스크랩 여부 확인 */
                $where = "WHERE mcb.reg_wiz_id = '".$request['wiz_id']."' AND mcb.table_code = '".$recommend_code."' AND mcb.mb_unq = '".$request['mb_unq']."'";
                $clip = $this->board_mdl->checked_article_clip_by_wiz_id($where);
                $result['mb_clip_yn'] = ($clip) ? "Y" : "N";

                /* 블라인드 여부 확인*/
                $recoomend_select.= ", if( mbh.unq IS NULL , 0, 1 ) as mbh_blind_state";
                $recoomend_join_table.= ' LEFT JOIN mint_boards_hide as mbh ON (mbh.mb_unq = mbc.mb_unq AND mbh.co_unq = mbc.co_unq AND uid = '.$wiz_member['wm_uid'].')';
            }
        }
        
        if(strpos($request['table_code'],'dictation') !==false)
        {
            $content = common_textarea_out($result['mb_content']);
            $result['mb_content'] = $content;

            $content2 = common_textarea_out($result['mb_content2']);
            $result['mb_content2'] = $content2;

            $this->load->model('book_mdl');
            //강사, 교재정보 subject에 있음. 북id--강사id--얼철딕횟수
            if($result['mb_title'] && strpos($result['mb_title'],'--') !== false)
            {
                $subject = explode('--',$result['mb_title']);
                $result['mb_tu_uid'] = $subject[1];
                if($subject[0])
                {
                    $book_info = $this->book_mdl->row_book_by_sub_query($subject[0]);
                    $result['book_table_code'] = str_replace('href=../boards/board_list.php?table_code=','',$book_info['book_link2']);
                }
            }
            
        }
        else if($request['table_code'] == 'correction')
        {
            $content = common_textarea_out($result['mb_content']);
            $result['mb_content'] = $content;

            $reply = common_textarea_out($result['mb_reply']);
            $result['mb_reply'] = $reply;
        }
        else if($request['table_code'] == 'request')
        {
            $mb_content = common_textarea_out($result['mb_content']);
            $result['mb_content'] = $mb_content;

            $mb_reply = common_textarea_out($result['mb_reply']);
            $result['mb_reply'] = $mb_reply;

            $sp_header = common_textarea_out($result['sp_header']);
            $result['sp_header'] = $sp_header;

            $sp_bottom = common_textarea_out($result['sp_bottom']);
            $result['sp_bottom'] = $sp_bottom;
        }
        else if($request['table_code'] == 'toteacher')
        {
            //에디터 사용안함 
        }
        else if($request['table_code'] != 'express')
        {

            $content = common_textarea_out($result['mb_content']);
            $input_txt =stripslashes($result['mb_input_txt']);
            if($result['mb_c_yn']=="n")
            {
                $content = nl2br($content);
            }

            $content = preg_replace("/http:\/\/friend./i","http://new.",$content);
            $content = preg_replace("/_iframe_/i",$input_txt,$content);

            $n_match_array_before = array(
                '../../daumeditor/',
                'http://new.mint05.com/daumeditor/',
                'http://www.youtube.com/embed',
            );
            $n_match_array_after = array(
                Thumbnail::$cdn_default_url.'/editor/deco_img/daumeditor/',
                Thumbnail::$cdn_default_url.'/editor/deco_img/daumeditor/',
                'https://www.youtube.com/embed',
            );
            $content = str_replace($n_match_array_before,$n_match_array_after,$content);
            $result['mb_content'] = $content;
        }

        $comment = [];
        $comment_cnt = 0;
        // 댓글+대댓글 갯수
        $count_reply_all = 0;

        
        $this->load->model('board_mdl');

        $order = NULL;

        if($request['table_code'] == 'express')
        {
            $order = ' GROUP BY mbc.uid '.(($request['order_type'] == 'new') ? "ORDER BY mbc.uid DESC" : "ORDER BY mbc.recom DESC");
            
            $comment = $this->board_mdl->list_article_express_comment($request['mb_unq'], $recoomend_select, $recoomend_join_table ,$order);
            if($comment)
            {
                $comment_cnt = sizeof($comment);
                $count_reply_all = $comment_cnt;
                $comment = board_comment_writer(array_splice($comment,0,5));
            }
        }
        else if(strpos($request['table_code'],'dictation') !==false)
        {

            $order = ' GROUP BY mbc.unq '.(($request['order_type'] == 'new') ? "ORDER BY mbc.unq DESC" : "ORDER BY mbc.recom DESC");

            $comment = $this->board_mdl->list_article_cafeboard_comment($request['mb_unq'], $recoomend_select, $recoomend_join_table ,$order);

            if($comment)
            {
                $comment_cnt = sizeof($comment);
                $count_reply_all = $comment_cnt;
                $comment = board_comment_writer(array_splice($comment,0,5));
            }

        }
        else if($request['table_code'] == 'correction')
        {
        //영어첨삭 댓글없음
        }
        else if($request['table_code'] == 'toteacher')
        {
            //강사와 1:1 게시판 댓글없음
        }
        else if($request['table_code'] == 'request')
        {
            //요청게시판 댓글없음
        }
        else
        {   
            
           
            
            /* 
                일일 도전 영작문(1127), 오늘의영어한마디(1132) 정책
                - 해당글에 댓글을 썻을때만 댓글 노출
            */
            if($wiz_member && ($request['table_code'] == '1127' || $request['table_code'] == '1132'))   
            {
                $ck_comm_where = ' WHERE mbc.mb_unq = '.$request['mb_unq'].' AND mbc.writer_id = "'.$wiz_member['wm_wiz_id'].'"';
                $ck_comm = $this->board_mdl->list_count_comment('',$ck_comm_where);
                $result['reply_exist'] = $ck_comm['cnt'] > 0 ? 1:0;
            }

            //ai딕테이션 내가 댓글 단거 있으면 내꺼만 가져온다
            if($request['table_code'] == '1144')
            {
                $result['my_dictation'] = null;
                if($wiz_member)
                {
                    $result['my_dictation'] = $this->board_mdl->row_article_comment_by_mbunq_writer($request['mb_unq'],$wiz_member['wm_wiz_id']);
                    /* if($result['my_dictation'])
                    {
                        $result['my_dictation']['mbc_comment'] = nl2br($result['my_dictation']['mbc_comment']);
                    } */
                }
            }
            elseif($result['reply_exist'] !== 0)
            {

                $where_balcklist = "";
                if($wiz_member)
                {
                    $block_member_list = member_get_block_list($wiz_member['wm_uid']);
                    if($block_member_list)
                    {
                        $where_balcklist = " AND (writer_id NOT IN ('".implode("','",$block_member_list)."') OR writer_id IS NULL) ";


                        $where_balcklist_reply = " AND co_fid NOT IN (SELECT co_fid FROM mint_boards_comment WHERE writer_id IN ('".implode("','",$block_member_list)."')) ";
                    }
                }

                // 대 댓글 갯수
                $comm_where = " AND co_thread != 'A' ".$where_balcklist_reply; 
                $count_reply = $this->board_mdl->list_count_mint_boards_comment($request['mb_unq'],$comm_where);
                $count_reply = $count_reply ? $count_reply['cnt']:0;

                // 뎁스 A인 댓글 갯수
                $comm_where = " AND co_thread = 'A' ".$where_balcklist; 
                $count = $this->board_mdl->list_count_mint_boards_comment($request['mb_unq'],$comm_where);
                $count = $count ? $count['cnt']:0;


                
                $where = $where_balcklist.($request['order_type'] == 'new') ? " GROUP BY mbc.co_unq ORDER BY mbc.notice_yn ASC, mbc.co_fid DESC, mbc.co_thread ASC" : "AND mbc.co_thread = 'A' GROUP BY mbc.co_unq ORDER BY mbc.notice_yn ASC, mbc.recom DESC";
            
                $comment = $this->board_mdl->list_article_comment($request['mb_unq'], $recoomend_select, $recoomend_join_table, $where);

                $count_reply_all = $count + $count_reply;

                if($request['order_type'] == 'recom' && $comment)
                {
                    $comment_tmp = NULL;
                    $comment_result = [];
                    for($i=0; $i<sizeof($comment); $i++)
                    {
                        array_push($comment_result, $comment[$i]);
                        $order = "AND mbc.co_fid =".$comment[$i]['mbc_co_fid']." AND mbc.co_thread != 'A' GROUP BY mbc.co_unq ORDER BY mbc.co_unq ASC";
                        $comment_tmp = $this->board_mdl->list_article_comment($request['mb_unq'], $recoomend_select, $recoomend_join_table, $order);

                        if($comment_tmp)
                        {
                            for($j=0; $j<sizeof($comment_tmp); $j++)
                            {
                                array_push($comment_result, $comment_tmp[$j]);
                            }
                        }
                    }

                    $comment_cnt = sizeof($comment_result);
                    $comment = array_splice($comment_result,0,5);
                }
                if($comment)
                {
                    $comment_cnt = sizeof($comment);
                    $comment = board_comment_writer(array_splice($comment,0,5));
                }
            }
        
        }

        //퀘스트
        $quest_q_idx = '';
        if($request['table_code'] =='1140' || $request['table_code'] =='1142')
        {
            if($request['mb_unq'] =='464407')   //얼굴철판딕테이션 이벤트 상세페이지 확인하기
            {
                $quest_q_idx = '30';
            }
            elseif($request['mb_unq'] =='464402')   //버프이벤트 상세페이지 확인하기
            {
                $quest_q_idx = '31';
            }
            elseif($request['mb_unq'] =='464419')   //브레인워시 상세이벤트 확인하기
            {
                $quest_q_idx = '32';
            }
            else    // 그 외 이벤트 3가지 상세페이지 확인하기
            {
                $quest_q_idx = '33';
            }
        }
        elseif($request['table_code'] =='1350') //[민트사용노하우] 게시글 3회 읽어보기
        {
            $quest_q_idx = '34';
        }
        elseif($request['table_code'] =='1111') //[수업체험후기] 게시글 3회 읽어보기
        {
            $quest_q_idx = '35';
        }
        elseif($request['table_code'] =='1118') //[민트에서빛난회원들] 게시글 3회 읽어보기
        {
            $quest_q_idx = '36';
        }
        elseif($request['table_code'] =='1347') //[베스트글모음방] 게시글 3회 읽어보기
        {
            $quest_q_idx = '37';
        }
        elseif($request['table_code'] =='1106') //[왕초보옹알이강좌] 영상 강좌 10회 듣기
        {
            $quest_q_idx = '51';
        }
        elseif($request['table_code'] =='1110') //[영문법아작내기] 영상 강좌 5회 듣기
        {
            $quest_q_idx = '52';
        }
        elseif($request['table_code'] =='1132') //[오늘의영어한마디] 영상 강좌 5회 듣기
        {
            $quest_q_idx = '54';
        }

        //퀘스트번호 있으면 퀘스트실행
        if($quest_q_idx)
        {
            MintQuest::request_batch_quest($quest_q_idx, $request['mb_unq'].MintQuest::make_quest_subfix($request['table_code']));
        }

        //팁&가이드 내용 변환
        $config['mbn_tip_guide'] = $config['mbn_tip_guide'] ? stripslashes($config['mbn_tip_guide']) : null;

        //작성자 네임카드용 정보 가져오기
        $param = array(
            'wm_age'          => $result['wm_age'],
            'mmg_description' => $result['mmg_description'],
            'mmg_icon'        => $result['mmg_icon'],
        );
        $name_card = get_name_card_data($result['wm_uid'], $param);
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "게시물조회성공";
        $return_array['data']['info'] = $result;
        $return_array['data']['name_card'] = $name_card;
        $return_array['data']['config'] = $config;
        $return_array['data']['comment_cnt'] = $count_reply_all;
        $return_array['data']['comment'] = $comment;
        echo json_encode($return_array);
        exit;

    }


    /*
        상세보기 댓글 리스트
        
        일반게시판
        mbc.co_unq 최신순
        mbc.recom 댓글순

        이런표현어떻게
        mbc.uid 최신순
        mbc.recom   댓글순
    */
    public function article_comment_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => $this->input->post('wiz_id'),
            "table_code" => trim($this->input->post('table_code')),
            "mb_unq" => trim($this->input->post('mb_unq')),
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "order_type" => ($this->input->post('order_type')) ? trim(strtolower($this->input->post('order_type'))) : "recom",
        );


        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $result = [];
        $result_reply = [];
        $config = NULL;
        $search = array();
        $order = NULL;
        $article = NULL;
        //댓글+대댓글 갯수
        $count_reply_all = 0;

        $this->load->model('board_mdl');

        $wiz_member = base_get_wiz_member();
        /* 댓글 추천 여부 확인 */
        $recoomend_join_table = "";
        $recoomend_select = "";
    
        /* 게시글 추천 여부 */
        if($request['wiz_id'])
        {
            /* 회원 확인 */
            if(!$wiz_member)
            {
                echo json_encode(base_get_err_auth_check_msg());
                exit;
            }

            /* 회원정보 */
            $this->load->model('member_mdl');

            if($request['table_code'] == 'express')
            {
                
                $recommend_code = "9001";
                
                /* 댓글 추천 여부 확인 */
                $recoomend_select = ", if( mr.re_unq IS NOT NULL , 'Y', 'N' ) as mbc_recommend_yn";
                $recoomend_join_table = "LEFT OUTER JOIN mint_recommend mr ON mbc.uid = mr.co_unq AND mr.table_code = ".$recommend_code." AND mr.send_uid = ".$wiz_member['wm_uid'];

            }
            else if($request['table_code'] == 'request')
            {
                //추천기능없음
            }
            else if($request['table_code'] == 'correction')
            {
                //추천기능없음
            }
            else if($request['table_code'] == "dictation.t" || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
            {
                //9002, 9003같이 사용중
                $recommend_code = "9002,9003";
                
                /* 댓글 추천 여부 확인 */
                $recoomend_select = ", if( mr.re_unq IS NOT NULL , 'Y', 'N' ) as mbc_recommend_yn";
                $recoomend_join_table = "LEFT OUTER JOIN mint_recommend mr ON mbc.unq = mr.co_unq AND mr.table_code IN (".$recommend_code.") AND mr.send_uid = ".$wiz_member['wm_uid'];
                
            }
            else if($request['table_code'] == "toteacher")
            {
                //추천기능없음
            }
            else
            {
                $recommend_code = $request['table_code'];

                /* 댓글 추천 여부 확인 */
                $recoomend_select = ", if( mr.re_unq IS NOT NULL , 'Y', 'N' ) as mbc_recommend_yn";
                $recoomend_join_table = "LEFT OUTER JOIN mint_recommend mr ON mbc.co_unq = mr.co_unq AND mr.table_code IN (".$recommend_code.") AND mr.send_uid = ".$wiz_member['wm_uid'];

                /* 블라인드 여부 확인*/
                $recoomend_select.= ", if( mbh.unq IS NULL , 0, 1 ) as mbh_blind_state";
                $recoomend_join_table.= ' LEFT JOIN mint_boards_hide as mbh ON (mbh.mb_unq = mbc.mb_unq AND mbh.co_unq = mbc.co_unq AND uid = '.$wiz_member['wm_uid'].')';
            }
        }

        if($request['table_code'] == 'express')
        {
            $recommend_code = "9001";
            $config = [];

            $count = $this->board_mdl->list_count_mint_express_com($request['mb_unq']);
            $count = $count ? $count['cnt']:0;

            $count_reply_all = $count;
            if($count)
            {
                $order = ' GROUP BY mbc.uid '.(($request['order_type'] == 'new') ? "ORDER BY mbc.uid DESC" : "ORDER BY mbc.recom DESC");
                if($request['limit'])
                {
                    $order.= ' LIMIT '.$request['start'].', '.$request['limit'];
                }
                
                $result = $this->board_mdl->list_article_express_comment($request['mb_unq'], $recoomend_select, $recoomend_join_table, $order);
    
            }
            
            if($result)
            {
                $result = board_comment_writer($result);
            }
        }
        else if($request['table_code'] == "dictation.t" || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            $recommend_code = "9002";
            $config = [];
            $count = $this->board_mdl->list_count_mint_cafeboard_com($request['mb_unq']);
            $count = $count ? $count['cnt']:0;
            $count_reply_all = $count;

            if($count)
            {
                $order = ' GROUP BY mbc.unq '.($request['order_type'] == 'new' ? "ORDER BY mbc.unq DESC" : "ORDER BY mbc.recom DESC");
                if($request['limit'])
                {
                    $order.= ' LIMIT '.$request['start'].', '.$request['limit'];
                }
                $result = $this->board_mdl->list_article_cafeboard_comment($request['mb_unq'], $recoomend_select, $recoomend_join_table, $order);
            }
            

            if($result)
            {
                $result = board_comment_writer($result);
            }
        }
        else if($request['table_code'] == 'correction')
        {
            //영어첨삭 댓글없음
        }
        else
        {
        
            $config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);
            
            if($request['table_code'] == '1127' || $request['table_code'] == '1132')    // 일일 도전 영작문. 해당글에 내가 댓글을 썻는지 체크
            {
                if($wiz_member)
                {
                    $ck_comm_where = ' WHERE mbc.mb_unq = '.$request['mb_unq'].' AND mbc.writer_id = "'.$wiz_member['wm_wiz_id'].'"';
                    $ck_comm = $this->board_mdl->list_count_comment('',$ck_comm_where);
                    $reply_exist = $ck_comm['cnt'] > 0 ? 1:0;
                }
                else
                {
                    $reply_exist = 0;
                }
                
                if($reply_exist === 0)
                {
                    $return_array['res_code'] = '0000';
                    $return_array['msg'] = "";
                    $return_array['data']['total_cnt'] = 0;
                    $return_array['data']['list'] = [];
                    $return_array['data']['reply_exist'] = $reply_exist;
                    $return_array['data']['config'] = $config;
                    echo json_encode($return_array);
                    exit;
                }
            }

            $article = $this->board_mdl->row_article_by_mb_unq($request['table_code'], $request['mb_unq']);
            if($article)
            {
                //검색테이블 업데이트(조회수 증감에 따른)
                $search_params = array(
                    'mb_unq' => $request['mb_unq'],
                    'hit'    => $article['mb_hit'],
                    'recom'  => $article['mb_recom']
                );
                $this->board_mdl->update_search_boards($request['table_code'], $search_params);
            }

            $where_balcklist = "";
            if($wiz_member)
            {
                $block_member_list = member_get_block_list($wiz_member['wm_uid']);
                if($block_member_list)
                {
                    $where_balcklist = " AND (writer_id NOT IN ('".implode("','",$block_member_list)."') OR writer_id IS NULL) ";


                    $where_balcklist_reply = " AND co_fid NOT IN (SELECT co_fid FROM mint_boards_comment WHERE writer_id IN ('".implode("','",$block_member_list)."')) ";
                }
            }

            // 대 댓글 갯수
            $comm_where = " AND co_thread != 'A' ".$where_balcklist_reply; 
            $count_reply = $this->board_mdl->list_count_mint_boards_comment($request['mb_unq'],$comm_where);
            $count_reply = $count_reply ? $count_reply['cnt']:0;

            // 뎁스 A인 댓글 갯수
            $comm_where = " AND co_thread = 'A' ".$where_balcklist; 
            $count = $this->board_mdl->list_count_mint_boards_comment($request['mb_unq'],$comm_where);
            $count = $count ? $count['cnt']:0;

            $count_reply_all = $count + $count_reply;

            $recoomend_select.= ', if( mbc.admin_id IS NULL , 0, 1 ) as is_admin ';
            
            // 뎁스 A인 댓글 가져온다.
            if($count)
            {
                $order = " AND mbc.co_thread = 'A' ".$where_balcklist.($request['order_type'] == 'new' ? " GROUP BY mbc.co_unq ORDER BY mbc.notice_yn ASC, mbc.co_fid DESC, mbc.co_thread ASC" : " GROUP BY mbc.co_unq ORDER BY mbc.notice_yn ASC, mbc.recom DESC");
                if($request['limit'])
                {
                    $order.= ' LIMIT '.$request['start'].', '.$request['limit'];
                }
                $result_reply = $this->board_mdl->list_article_comment($request['mb_unq'], $recoomend_select, $recoomend_join_table, $order);
            }
            
            // 뎁스 A인 댓글이 존재하면 대댓글 가져와서 리턴할 배열 순서 재정렬 ex)A1->A1A->A1A->A2->A3->A3A
            if($result_reply)
            {
                $comment_tmp = NULL;
                $comment_result = [];
                for($i=0; $i<sizeof($result_reply); $i++)
                {
                    if($result_reply[$i]['mbc_co_fid'])
                    {
                        array_push($comment_result, $result_reply[$i]);
                        $order = " AND mbc.co_fid =".$result_reply[$i]['mbc_co_fid']."  AND mbc.co_thread != 'A' ".$where_balcklist." GROUP BY mbc.co_unq  ORDER BY mbc.co_unq ASC";
                        $comment_tmp = $this->board_mdl->list_article_comment($request['mb_unq'], $recoomend_select, $recoomend_join_table, $order);
    
                        if($comment_tmp)
                        {
                            for($j=0; $j<sizeof($comment_tmp); $j++)
                            {
                                array_push($comment_result, $comment_tmp[$j]);
                            }
                        }
                    }
                    
                }

                $result = $comment_result;
            }

            // 댓글 + 대댓글에 대한 글쓴이 정보 가져온다
            if($result)
            {
                $result = board_comment_writer($result);
            }
        }

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            $return_array['data']['config'] = $config;
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['reply_exist'] = $reply_exist;
        $return_array['data']['total_cnt'] = $count_reply_all;
        $return_array['data']['a_reply_cnt'] = $count;
        $return_array['data']['is_blind_article'] = $article && $article['mb_daum_img'] == 'H' ? 1:0;
        $return_array['data']['list'] = $result;
        $return_array['data']['config'] = $config;
        echo json_encode($return_array);
        exit;

    }


    /*
        검색

        공지사항 1113
        이런표현어떻게 express
        영어첨삭게시판 correction
        영어해석커뮤니티 1102
        영어문법질문&답변1120
        수업대본서비스 1130
        [소모임]스터디모집방 1125
        [소개]영어공부추천사이트 1346
        [소개]유용한영어표현 1128
        [도전]일일영작문 1127
        [익명]이러쿵저러쿵 1335
        영어고민&권태기상담 1337
        MINT ENGLISH CHAT 1356
        CEO의 러브레터 1334
        [이야기]Moby매니저방 1355
        [이야기]민트폐인방 1340
        베스트글모음방 1347
        [평가]강사평가서등록 1131
        수업체험후기 1111
        필리핀교육센터소식 1304
        [감동]학생에게선물받다 1117
        민트에서빛난회원들 1118
        당신의열정을보여줘 1308
        팁&노하우 1350
        오늘의영어한마디 1132
        왕초보옹알이강좌 1106
        영문법아작내기 1110
        팝송아작내기 1112
        토익스피킹강좌 1330
        자주묻는질문 FAQ 1123
    */
    public function search_old()
    {
        $return_array = array();

        $request = array(
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "search_type" => trim(strtolower($this->input->post('search_type'))),
            "search_keyword" => trim($this->input->post('search_keyword')),
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mb.mb_unq",
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


        $index = ""; 
        $count_index = ""; 

        $index_express = "";
        
        $search = array();
        $search_express = array();
     
        if(!$request['table_code'])
        {
            /*최신글 포함 게시판만 */
            array_push($search, "mb.table_code IN ('1113','1102','1120','1130','1125','1346','1128','1127','1335',
            '1337','1356','1334','1355','1340','1347','1131','1111','1304','1117','1118','1308','1350','1132','1106',
            '1110','1112','1330','1123')  AND ( mb.daum_img IS NULL OR mb.daum_img <> 'H' )");
        }
        else
        {
            array_push($search, "mb.table_code = '".$request['table_code']."' 
            AND ( mb.daum_img IS NULL OR mb.daum_img <> 'H' )");
        }
      

        /*띄어쓰기 검색어 입력불가 */
        if($request['search_keyword'])
        {   
            if($request['search_type'] == "title")
            {
                array_push($search, "match(title) against('*".$request['search_keyword']."*' IN BOOLEAN MODE)");
                array_push($search_express, "match(content) against('*".$request['search_keyword']."*' IN BOOLEAN MODE)");
            }
            else if($request['search_type'] == "content")
            {
                array_push($search, "match(content) against('*".$request['search_keyword']."*' IN BOOLEAN MODE)");
                array_push($search_express, "match(content) against('*".$request['search_keyword']."*' IN BOOLEAN MODE)");
            }
            else if($request['search_type'] == "all")
            {
                array_push($search, "match(content, title) against('*".$request['search_keyword']."*' IN BOOLEAN MODE)");
                array_push($search_express, "match(content) against('*".$request['search_keyword']."*' IN BOOLEAN MODE)");
            }
        }

        $where = "";
        $where_search = "";
        $where_search .= implode(" AND ", $search);

        if($where_search != "")
        {
            $where = sprintf(" WHERE %s", $where_search);
        }

        $where_express = "";

        $where_search_express = "";
        $where_search_express .= implode(" AND ", $search_express);

        if($where_search_express != "")
        {
            $where_express = sprintf(" WHERE %s", $where_search_express);
        }
        
        $this->load->model('board_mdl');

        $list_cnt = NULL;
        $list_cnt_express = NULL; 

        if(!$request['table_code'])
        {
            $list_cnt = $this->board_mdl->list_count_board($count_index, $where);
            $list_cnt_express = $this->board_mdl->list_count_board_express($where_express);
        }
        else if($request['table_code'] == 'express')
        {
            $list_cnt_express = $this->board_mdl->list_count_board_express($where_express);
        }
        else if($request['table_code'] != 'express')
        {
            $list_cnt = $this->board_mdl->list_count_board($count_index, $where);
        }

        if((!$request['table_code'] && $list_cnt['cnt'] == 0 && $list_cnt_express['cnt'] == 0) || 
        ($request['table_code'] == 'express' && $list_cnt_express['cnt'] == 0) || 
        ($request['table_code'] && $request['table_code'] != 'express' && $list_cnt['cnt'] == 0))
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        else
        {

            /* 이런표현 어떻게 + 일반게시판 */
            if(!$request['table_code'])
            {
                $result = [];
                $result_express = [];
                $result_board = [];
    
                $order_express = "";
    
                $limit = "";
                if($request['limit'] > 0)
                {   
                    $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
                }
                
                $select_col_content = ", mb.content as mb_content";
                $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
              

                $list_board = $this->board_mdl->list_board($index, $where, $order, $limit, $select_col_content);
                
                if($list_board)
                {
                    $result_board = board_list_writer($list_board, $request['search_keyword'] , $request['search_type']);
                }
                

                                    
                if(sizeof($result_board) !=  $request['limit'])
                {
                
                    $start =  ((int) $request['start'] + (int)$request['limit']) - (int)$list_cnt['cnt'];
                    
                    $limit = ($start < $request['limit']) ? $start : $request['limit'];
                    $start = ($start < $request['limit']) ? 0 : (int)$start - (int)$request['limit'];
                
                
                    $limit = sprintf("LIMIT %s , %s", $start, $limit);
                
                
                    if($request['order_field'] == "mb.mb_unq")
                    {
                        $order_express = sprintf("ORDER BY %s %s", "mb.uid", $request['order']);
                    }
                
                    $list_board_express = $this->board_mdl->list_board_express($index_express, $where_express, $order_express, $limit);
                    if($list_board_express)
                    {
                        $result_express = board_list_writer($list_board_express, $request['search_keyword'], 'title');
                    }
                
                }
    
    
                $result = array_merge($result_board, $result_express);
            
                $return_array['res_code'] = '0000';
                $return_array['msg'] = "목록조회성공";
                $return_array['data']['total_cnt'] = (int)$list_cnt['cnt'] + (int)$list_cnt_express['cnt'];
                $return_array['data']['list'] = $result;
                echo json_encode($return_array);
                exit;
            }
            else if($request['table_code'] == 'express')
            {
            
                $result_express = [];
                $order_express = "";
    
                $limit = "";
                if($request['limit'] > 0)
                {   
                    $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
                }

                if($request['order_field'] == "mb.mb_unq")
                {
                    $order_express = sprintf("ORDER BY %s %s", "mb.uid", $request['order']);
                }
                
                $list_board_express = $this->board_mdl->list_board_express($index_express, $where_express, $order_express, $limit);


                if($list_board_express)
                {
                    $result_express = board_list_writer($list_board_express, $request['search_keyword'], 'title');
                }

                $return_array['res_code'] = '0000';
                $return_array['msg'] = "목록조회성공";
                $return_array['data']['total_cnt'] = $list_cnt_express['cnt'];
                $return_array['data']['list'] = $result_express;
                echo json_encode($return_array);
                exit;

            }
            else if($request['table_code'] && $request['table_code'] != 'express')
            {
            
                $result_board = [];
    
                $order_express = "";
    
                $limit = "";
                if($request['limit'] > 0)
                {   
                    $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
                }
                
                $select_col_content = ", mb.content as mb_content";
                $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
               
                
                $list_board = $this->board_mdl->list_board($index, $where, $order, $limit, $select_col_content);

                if($list_board)
                {
                    $result_board = board_list_writer($list_board, $request['search_keyword'] , $request['search_type']);
                }

                $return_array['res_code'] = '0000';
                $return_array['msg'] = "목록조회성공";
                $return_array['data']['total_cnt'] = $list_cnt['cnt'];
                $return_array['data']['list'] = $result_board;
                echo json_encode($return_array);
                exit;
            }
        }
        
    }


    /*
        검색어가 있는 검색은 search_()함수 사용 
        없을때에는 일반 list_(일반게시판) / special_(특수게시판)함수 사용 
    */
    public function search_()
    {

        $return_array = array();

        $request = array(
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "search_type" => trim(strtolower($this->input->post('search_type'))),
            "search_keyword" => trim($this->input->post('search_keyword')),
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "type" => trim($this->input->post('type')),
            "select_w_kind" => trim($this->input->post('select_w_kind')),
            "select_w_mp" => trim($this->input->post('select_w_mp')),
            "select_tu_uid" => trim($this->input->post('select_tu_uid')),
            "select_book" => trim($this->input->post('select_book')),
            "select_teacher" => trim($this->input->post('select_teacher')),
            "start_date" => trim($this->input->post('start_date')),
            "end_date" => trim($this->input->post('end_date')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "bs_unq",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
            "config_table_code" => trim($this->input->post('config_table_code')),
        );
        
        
        $where = null;
        $order = null;
        $limit = null;
        $select_col_content = '';

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $search = array();
        $multiple_search_key = explode(' ', $request['search_keyword']);

        $multiple_strong_key = Array();
        foreach($multiple_search_key as $num){
            $multiple_strong_key[] = "<b>".$num."</b>";
        }

        $this->load->model('board_mdl');

        //일반게시판
        $where = " WHERE mb.showdate <= '".date("Y-m-d")."' AND mb.del_yn !='Y' AND (mb.name_hide ='N' OR mb.name_hide IS NULL) ";

        $wiz_member = base_get_wiz_member();

        // 내글은 비공개여도 검색되도록
        if($wiz_member["wm_wiz_id"])
        {
            array_push($search, " (mb.secret!='Y' OR (mb.secret='Y' AND mb.wiz_id = '".$wiz_member['wm_wiz_id']."' ))");
        }
        else
        {
            array_push($search, " mb.secret!='Y' ");
        }
        
        if($request["type"] == "my" && $wiz_member["wm_wiz_id"])
        {
            /* 회원 확인 */
            if(!$wiz_member)
            {
                echo json_encode(base_get_err_auth_check_msg());
                exit;
            }

            array_push($search, " mb.wiz_id = '".$wiz_member['wm_wiz_id']."'");
        }
        
        
        $table_code = $request['table_code'];
    
        if($table_code)
        {
            $table_code_arr = explode(',',$table_code);
            $table_code_pretty = [];
            foreach($table_code_arr as $tc)
            {
                if($tc == 'express')		//이런표현어떻게
                {
                    $table_code = "9001";
                }
                else if(strpos($request['table_code'],'dictation') !==false)	//얼철딕
                {
                    $table_code = "9002";
                }
                else if($tc == 'correction')		//영어첨삭게시판
                {
                    $table_code = "9004";
                }
                /* else if($tc == 'toteacher')		//강사와1:1
                {
                    $table_code = "9998";
                }
                else if($tc == 'request')		//실시간요청게시판
                {
                    $table_code = "9999";
                    
                } */
                else
                {
                    $table_code = $tc;
                }

                $table_code_pretty[] = $table_code;
            }
            
            $where .= " AND mb.table_code IN (".implode(',',$table_code_pretty).")";
        } 
        else
        {
            $list_mint_boards = $this->board_mdl->list_mint_boards_name();
            $list_boards = array();
            //$list_boards = array("9001"=>"이런표현어떻게", "9002"=>"얼굴철판딕테이션", "9004"=>"영어첨삭");
            $list_boards = array("9001"=>"이런표현어떻게");
            
    
            foreach($list_mint_boards as $list_mint_board)
            {
                $list_boards[$list_mint_board['table_code']] = $list_mint_board['table_name'];
            }
    
            $board_table_list = implode("','",(array_keys($list_boards)));
            
            $where .= " AND mb.table_code in ('{$board_table_list}') ";
            
        }

        // 닉네임 검색 시 익명글은 검색 되지 않아야 한다.
        if($request['search_type'] == 'nickname')
        {
            $or_add = "";
            if($wiz_member["wm_wiz_id"])
            {
                $or_add = " OR mb.wiz_id='".$wiz_member["wm_wiz_id"]."'";
            }

            $where .= " AND (mb.anonymous_yn='N' OR mb.anonymous_yn IS NULL ".$or_add.")";
        }

        if($request['start_date'] && $request['end_date'])
        {
            $where .= " AND mb.regdate BETWEEN '".$request['start_date']." 00:00:00' AND '".$request['end_date']." 23:59:59' ";
        }

        
        /* 영어첨삭 검색 */
        if($request['table_code'] == 'correction')
        {
            if($request["select_w_kind"])
            {
                array_push($search, "mb.w_kind  ='".$request["select_w_kind"]."'");
            }

            // N mp3만, Y 첨삭만, A 둘다
            if($request["select_w_mp"])
            {
                if($request["select_w_mp"] =='A')
                {
                    array_push($search, "mb.w_mp3 ='Y'");
                    array_push($search, "mb.w_mp3_type !=''");
                }
                elseif($request["select_w_mp"] =='Y')
                {
                    array_push($search, "mb.w_mp3  ='".$request["select_w_mp"]."'");
                    array_push($search, "mb.w_mp3_type =''");
                }
                else
                {
                    array_push($search, "(mb.w_mp3  ='".$request["select_w_mp"]."' OR mb.w_mp3 IS NULL)");
                    array_push($search, "mb.w_mp3_type !=''");
                }
            }

            if($request["select_tu_uid"])
            {
                array_push($search, "mb.tu_uid  ='".$request["select_tu_uid"]."'");
            }
        }
        /* 영어첨삭 검색 */
        elseif(strpos($request['table_code'],'dictation') !==false)
        {
            array_push($search, "mb.noticeYn ='N'");

            // cafeboard 테이블과 서치테이블의 notice_yn 필드명칭이 달라서 맞춰주기 위해 추가함
            $select_col_content = ", mb.noticeYn AS mb_notice_yn, IF(mb.vd_url!='','V','T') as mb_b_kind";
            // V: 화상, M : 폰
            if($request["select_w_kind"] =='V')
            {
                array_push($search, "mb.vd_url !=''");
            }
            elseif($request["select_w_kind"] =='M')
            {
                array_push($search, "mb.vd_url =''");
            }

            if($request["select_book"])
            {
                array_push($search, "mb.book_name ='".$request["select_book"]."'");
            }

            if($request["select_teacher"])
            {
                array_push($search, "mb.tu_name  ='".$request["select_teacher"]."'");
            }
        }

        /* 1138(딕테이션 해결사) 예외처리(부모글은 parent_key 0) */
        if($request['table_code'] == '1138')
        {
            // array_push($search, "(mb.parent_key IS NULL || mb.parent_key = '0')");
        }

        if($request['search_keyword'])
        {   
            if($request['search_type'] == "title" || $request['search_type']=="content" || $request['search_type']=="content2" || $request['search_type']=="nickname")
            {
                array_push($search, "match(mb.{$request['search_type']}) against('\"{$request['search_keyword']}\"' IN BOOLEAN MODE)");
            }
            else if($request['search_type'] == "tc")
            {
                array_push($search, "match(mb.content, mb.title) against('\"{$request['search_keyword']}\"' IN BOOLEAN MODE)");
            }
            //게시글 + 댓글
            else if($request['search_type'] == "all")
            {
                array_push($search, "match(mb.content, mb.title) against('\"{$request['search_keyword']}\"' IN BOOLEAN MODE)");
            }
        }

        if(!empty($search))
        {
            $where .= ' AND '. join(' AND ',$search);
        }

        if(strpos($request['table_code'],'dictation') ===false)
        {
            $where .= " AND ( mb.vd_url IS NULL OR mb.vd_url <> 'H' ) ";
        }

        // 리스팅 시 차단한 회원은 제외
        if($wiz_member)
        {
            $block_member_list = member_get_block_list($wiz_member['wm_uid']);
            if($block_member_list)
            {
                $where .= " AND (mb.wiz_id NOT IN ('".implode("','",$block_member_list)."') OR mb.wiz_id IS NULL)";
            }
        }

        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        
        $list_count_mint_search_boards = $this->board_mdl->list_count_mint_search_boards($where);
        
        if($list_count_mint_search_boards['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $list_mint_search_boards = $this->board_mdl->list_mint_search_boards($where, $order, $limit, $select_col_content);

        if($list_mint_search_boards)
        {
            // 섬네일 정보 따로 가져오기 위해 mb_unq추출
            $add_search_mb_unq = [];
            foreach($list_mint_search_boards as $key=>$val)
            {
                //게시판 설정정보
                $board_config = $this->board_mdl->row_board_config_by_table_code($val['mb_table_code']);
                
                $list_mint_search_boards[$key]['mbn_table_name'] = ($board_config) ? $board_config['mbn_table_name'] : NULL;
                $list_mint_search_boards[$key]['mbn_certify_yn'] = ($board_config) ? $board_config['mbn_certify_yn'] : NULL;
                $list_mint_search_boards[$key]['mbn_anonymous_yn'] = ($board_config) ? $board_config['mbn_anonymous_yn'] : NULL;
                $list_mint_search_boards[$key]['mbn_secret_yn'] = ($board_config) ? $board_config['mbn_secret_yn'] : NULL;
                $list_mint_search_boards[$key]['mbn_list_hit'] = ($board_config) ? $board_config['mbn_list_hit'] : NULL;
                $list_mint_search_boards[$key]['mbn_view_login'] = ($board_config) ? $board_config['mbn_view_login'] : NULL;
                
                if($val['mb_table_code'] >= 9000) continue;
                $add_search_mb_unq[] = $val['mb_mb_unq'];
            }
            if($add_search_mb_unq)
            {
                $thumb_data = $this->board_mdl->get_thumbnail_field($add_search_mb_unq);
                $thumb_data_sort = [];
                // 섬네일 정보 정렬
                if($thumb_data)
                {
                    foreach($thumb_data as $key=>$val)
                    {
                        $thumb_data_sort[$val['mb_unq']] = $val['thumb'];
                    }
                    
                }
                // 섬네일 정보 리스트 데이터에 삽입
                foreach($list_mint_search_boards as $key=>$val)
                {
                    if(array_key_exists($val['mb_mb_unq'],$thumb_data_sort))
                    {
                        $list_mint_search_boards[$key]['mb_thumb'] = $thumb_data_sort[$val['mb_mb_unq']];
                    }
                }
            }

            $result_board = board_list_writer($list_mint_search_boards, $request['search_keyword'] , $request['search_type']);

            if($request['table_code'] == '1138')
            {
                /* 리스트에 딕테이션 해결사 게시글에 댓글 게시물 정리 추가 */
                // $result_board = board_list_dictation_solution_add_child($result_board);
            }
        }

        $config = NULL;
        $category = NULL;
        $bookmark = NULL;

        /*
            검색페이지 UI가 아닌 일반게시판 페이지 UI사용시 게시판 config, category, bookmark 정보 필요
        */
        if($request['config_table_code'])
        {
            $config = $this->board_mdl->row_board_config_by_table_code($request['config_table_code']);
            $category = $this->board_mdl->list_board_category_by_table_code($request['config_table_code']);
            if($request['wiz_id'])
            {
                $bookmark = $this->board_mdl->bookmark_checked_by_wiz_id($request['wiz_id'], $request['config_table_code']);
            }

        }

        
       
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_count_mint_search_boards['cnt'];
        $return_array['data']['list'] = $result_board;
        $return_array['data']['config'] = $config;
        $return_array['data']['bookmark'] = ($bookmark) ? "Y" : "N";
        $return_array['data']['category'] = $category;
        echo json_encode($return_array);
        exit;

    }


    /*
        게시판 새글 여부 목록   
        - 기준 : 2일이내에 쓴글이 있으면 새글
        로그인정보 있을경우 아래 정보 추가
        - 회원 정보
        - 회원 즐겨찾기 게시판목록
        - 회원 새 알림 메시지 유무
        - 회원 새 쪽지 유무
        - 회원 수업 및 테스트 정보 
    */
    public function new_()
    {

        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
        );

        
        /*
            게시판 정보
            - new : 새글 여부 목록 
        */
        $result_boards = NULL;
        
        $where = "WHERE mbn.table_code IN ('1309','1301','1111','1304','1118','1117','1102','1103','1303','1106','1120','1125','1346','1126','1128','1127','1129','1130','1132','1110','1354'
            ,'1131','1113','1337','1335','1334','1336','1308','1339','1340','1341','1345','1347','1350','1351','1343','1342','1352','1353','1355','1356','1133', '1123','1376','1366'
            ,'1367','1120','1379','1378','1377','1112','1330','1380', '1381','1138','1383','1388','1140','1142','1144')";

        $this->load->model('board_mdl');

        $boards_new = $this->board_mdl->list_mint_boards_new_checked($where);
        $cafeboard_t_cnt = $this->board_mdl->list_count_mint_cafeboard_checked("T");
        $cafeboard_v_cnt = $this->board_mdl->list_count_mint_cafeboard_checked("V");
        $wiz_correct_cnt = $this->board_mdl->list_count_wiz_correct_checked();
        $mint_express_cnt = $this->board_mdl->list_count_mint_express_checked();
        $mint_news_letter_cnt = $this->board_mdl->list_count_mint_news_letter_checked();
        $wiz_speak_cnt = $this->board_mdl->list_count_wiz_speak_checked();         

        
        /**
         * check_inclass : 수업중인 회원만 접근가능
         * check_holding : 장기연기중인 회원만 접근가능
         * 일반게시판 이외에는 해당 설정값이 없음
         */
        $cafeboard_t_array = array("table_code" => "dictation.T" , "new" => ($cafeboard_t_cnt['cnt'] > 0) ? "Y" : "N", "check_inclass" => "N", "check_holding" => "N");
        $cafeboard_v_array = array("table_code" => "dictation.V" , "new" => ($cafeboard_v_cnt['cnt'] > 0) ? "Y" : "N", "check_inclass" => "N", "check_holding" => "N");
        $cafeboard_list_array = array("table_code" => "dictation.list" , "new" => ($cafeboard_v_cnt['cnt'] > 0 || $cafeboard_t_cnt['cnt'] > 0) ? "Y" : "N", "check_inclass" => "N", "check_holding" => "N");
        $wiz_correct_array = array("table_code" => "correction" , "new" => ($wiz_correct_cnt['cnt'] > 0) ? "Y" : "N", "check_inclass" => "N", "check_holding" => "N");
        $mint_express_array = array("table_code" => "express" , "new" => ($mint_express_cnt['cnt'] > 0) ? "Y" : "N", "check_inclass" => "N", "check_holding" => "N");
        $mint_news_letter_array = array("table_code" => "news.letter" , "new" => ($mint_news_letter_cnt['cnt'] > 0) ? "Y" : "N", "check_inclass" => "N", "check_holding" => "N");
        $wiz_speak_array = array("table_code" => "request" , "new" => ($wiz_speak_cnt['cnt'] > 0) ? "Y" : "N", "check_inclass" => "N", "check_holding" => "N");
        
        array_push($boards_new, $cafeboard_t_array);
        array_push($boards_new, $cafeboard_v_array);
        array_push($boards_new, $cafeboard_list_array);
        array_push($boards_new, $wiz_correct_array);
        array_push($boards_new, $mint_express_array);
        array_push($boards_new, $mint_news_letter_array);
        array_push($boards_new, $wiz_speak_array);
    
        $result_boards['new'] = $boards_new;
       
        /*
            회원 정보
            - info : 회원정보
            - bookmark : 회원 즐겨찾기 게시판목록
            - notify : 회원 새 알림 메시지 유무         
            - msg : 회원 새 쪽지 유무                
        */
        $result_member = NULL;

        /*
            수업정보
            - next_schedule : 다음 스케쥴 정보
            - inclass : 현재 수업중 여부 
            - mel_classroom_invitation : MEL수강중일때 강의실 초대 정보
        */
        $result_class_information = NULL;

        /*
            테스트 정보
            - mset : MSET 무료 대상자 여부
                비로그인은 테스트 대상자 팝업을 띄워주지 않는다.
                60일마다 1회씩 무료 참여 가능 
                0: 불가, 1:가능
            - leveltest : 레벨테스트 대상자 여부 
                비로그인은 테스트 대상자
                0: 불가, 1:가능 
            - v_leveltest : 네오텍 화상 레벨테스트 강의실 입장 팝업 디스플레이 여부  
                0: N,  1: Y 
        */
        $result_test_information = NULL;
        $result_test_information['mset'] = 0;
        $result_test_information['leveltest'] = 1;


        /*
            로그인정보 있을경우
            - 회원 정보
            - 회원 즐겨찾기 게시판목록
            - 회원 새 알림 메시지 유무
            - 회원 새 쪽지 유무
            - 회원 수업 및 테스트 정보 
        */
        if($request['wiz_id'])
        {
            /* 회원정보 */
            $wiz_member = base_get_wiz_member();
            if(!$wiz_member)
            {
                echo json_encode(base_get_err_auth_check_msg());
                exit;
            }

            /* 회원 즐겨찾기 게시판 목록 */
            $result_member['bookmark'] = $this->board_mdl->list_boards_bookmark_by_wm_uid($wiz_member['wm_uid']);

            /* 회원 새 알림 메시지 유무 */
            $this->load->model('notify_mdl');
            $checked_notify = $this->notify_mdl->checked_notify_by_wm_uid($wiz_member['wm_uid']);
            $result_member['notify'] = array("cnt" => $checked_notify['cnt'], "not_read" => ($checked_notify['cnt'] > 0) ? "Y" : "N");

            /* 
                회원 새 쪽지 유무
                - checked_msg : 읽지않은 일반 쪽지
                - checked_notice_msg : 읽지않은 공지 쪽지
                - note_cnt(읽지 않은 쪽지 수) : 읽지않은 일반 쪽지 + 읽지않은 공지 쪽지
                - sender_nickname(보낸이) : 가장 최근 일반 쪽지를 보낸이(없을 경우 > 공지 쪽지가 있으면 "민트영어"로 출력)
            */
            $this->load->model('msg_mdl');
            $checked_msg = $this->notify_mdl->checked_msg_by_wm_uid($wiz_member['wm_uid']);
            $checked_notice_msg = $this->msg_mdl->count_receive_msg_by_admin($wiz_member['wm_uid']);
            $note_cnt = (int)$checked_notice_msg['cnt'] + (int)$checked_msg['cnt'];
            $checked_msg['sender_nickname'] = ($checked_msg['sender_nickname'] == '슈퍼관리자') ? '민트영어' : $checked_msg['sender_nickname'];
            $sender_nickname = $checked_msg['sender_nickname'] ? $checked_msg['sender_nickname'] : ($checked_notice_msg['cnt'] ? '민트영어' : '');
            $result_member['msg'] = array("cnt" => $note_cnt , "not_read" => ($note_cnt > 0) ? "Y" : "N", "sender_nickname" => $sender_nickname);

            /*
                회원 정보
                - 뱃지 정보 추가 (뱃지 이미지 URL , 뱃지 설명)
                - 딕테이션도우미 권한체크 및 권한 정보 추가 (딕테이션해결사 답변글쓰기 권한)
                - 현재 보유 포인트 정보 갱신
                - 현재 수업 상태 정보 추가
            */

            //뱃지 정보 추가 (뱃지 이미지 URL , 뱃지 설명)
            $icon = member_get_icon($wiz_member);
            $wiz_member['icon'] = $icon['icon'];
            $wiz_member['icon_desc'] = $icon['icon_desc'];

            //딕테이션도우미 권한체크 및 권한 정보 추가 (딕테이션해결사 답변글쓰기 권한)
            $wiz_member['wm_assistant_solver'] = (member_checked_badge($wiz_member['wm_uid'], 'Dictation', 'Helper')) ? "Y" : "N";

            //현재 보유 포인트 정보 갱신
            $this->load->model('point_mdl');
            $nowpoint = $this->point_mdl->check_current_point($wiz_member['wm_uid']);
            $wiz_member['wm_point'] = $nowpoint['wm_point'] ? $nowpoint['wm_point']:0;
            
            //현재 수업 상태 정보 추가
            $current_class_status = lesson_current_class_status($wiz_member['wm_uid']);
            $wiz_member['current_class_state'] = $current_class_status['main_lesson_state'];
            $result_member['info'] = $wiz_member;


            /* 
                회원 수업 정보
                - next_schedule : 다음 수업 스케쥴 정보
                - inclass : 현재 수업중 여부 
                - mel_classroom_invitation : MEL수강중일때 강의실 초대 정보 
                    show : 초대 팝업 디스플레이 여부
                    url :강의실 url
            */
            $this->load->model('lesson_mdl');
            
            // 자유수업 수강중인지 여부 Y/N
            $result_class_information['free_lesson_ing'] = $current_class_status['free_lesson_ing'];

            //다음 수업 스케쥴 정보
            $next_schedule = $this->lesson_mdl->checked_nextclass_by_wm_uid($wiz_member['wm_uid']);
            $result_class_information['next_schedule'] = $next_schedule;
            
            //현재 수업중 여부 
            $checkwhere = " AND schedule_ok='Y' AND lesson_list_view='Y' AND tu_uid NOT IN (153, 158) AND endday >= '".date("Y-m-d")."' LIMIT 1";
            $inclass = $this->lesson_mdl->check_in_class_member($wiz_member['wm_uid'],$checkwhere);
            $result_class_information['inclass'] = ($inclass) ? "Y" : "N";

            //MEL수강중일때 강의실 초대 정보
            $this->load->model('webrtc_mdl');
            $classroom_invitation = $this->webrtc_mdl->checked_classroom_invitation($wiz_member['wm_uid']);
            $result_class_information['mel_classroom_invitation']['show'] = ($classroom_invitation) ? "Y" : "N";
            $result_class_information['mel_classroom_invitation']['url'] = ($classroom_invitation) ? $classroom_invitation['invitational_url'] : "";

            //수업연장알림
            $class_extension = $this->lesson_mdl->check_class_extension_now($wiz_member['wm_uid']);
            $result_class_information['class_extension']['show'] = $class_extension ? "Y" : "N";
            if($class_extension)
            {
                $code = (new OldEncrypt('(*&DHajaan=f0#)2'))->encrypt($class_extension['idx']);
                $link = set_new_or_old_url('/#/class-extend?u='.$code);
            }
            $result_class_information['class_extension']['url'] = $class_extension ? $link: "";

 
            /*
                회원 테스트 정보
                - mset : MSET 무료 대상자 여부
                    비로그인은 테스트 대상자띄우지 않는다
                    60일마다 1회씩 무료 참여 가능 
                    0: 불가, 1:가능
                - leveltest : 레벨테스트 대상자 여부 
                    비로그인은 테스트 대상자
                    0: 불가, 1:가능 
                - v_leveltest : 네오텍 화상 레벨테스트 강의실 입장 팝업 디스플레이 여부  
                    0: N,  1: Y 
            */

            $result_test_information['mset'] = mset_check_free($wiz_member['wm_uid'], $wiz_member['wm_d_did']);

            //레벨테스트 대상자 여부
            $this->load->model('Leveltest_mdl');
            $lv_row = $this->Leveltest_mdl->check_leveltest_exist($wiz_member['wm_uid']);
            $result_test_information['leveltest'] = $lv_row ? 0:1;    

            //네오텍 화상 레벨테스트 강의실 입장 팝업 디스플레이 여부  
            $result_test_information['v_leveltest'] = ($lv_row && $lv_row['lesson_gubun'] == 'V' && $lv_row['le_end'] > date("Y-m-d H:i:s") && $lv_row['lev_name'] =='') ? 1:0;

            //수강이력 확인
            $has_lesson = $this->lesson_mdl->checked_has_lesson_history($wiz_member['wm_uid']);
            $result_class_information['has_lesson'] = $has_lesson ? 1:0;
        }
        
        // 자가부담금 있는 딜러인지
        $result_member['wd_has_member_fee'] = $wiz_member ? $wiz_member['wd_has_member_fee']:0;

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "정보조회성공";
        $return_array['data']['boards'] = $result_boards;
        $return_array['data']['member'] = $result_member;
        $return_array['data']['class_information'] = $result_class_information;
        $return_array['data']['test_information'] = $result_test_information;
        echo json_encode($return_array);
        exit;

    }

    /* 
        게시판 즐겨찾기
    */
    public function bookmark()
    {

        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "authorization" => trim($this->input->post('authorization')),
            "del_yn" => trim(strtoupper($this->input->post('del_yn')))
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

        $this->load->model('board_mdl');

        /*
            즐겨찾기 추가시에만 게시판 설정 확인, 해제는 설정과 관계없이 모두 가능
            - 게시판 운영중 즐겨찾기 사용 못하게 설정 변경 하는 경우가 있음, 이 경우 기존에 추가해놓은 즐겨찾기 해제가 불가능
        */
        if($request['del_yn'] == 'N')
        {
            /* 게시판설정 확인 */
            $board_config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);
            if($request['table_code'] != "express" && $request['table_code'] !="request" && $request['table_code'] !="dictation.list" && 
            $request['table_code'] !="dictation.v" && $request['table_code'] !="dictation.t" && $request['table_code'] !="correction" &&
            $board_config['mbn_bookmark_yn'] != "Y")
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0301";
                $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 즐겨찾기 할 수 없습니다.";
                echo json_encode($return_array);
                exit;
            }

        }
        

        
        $this->load->model('member_mdl');

        $wiz_member = $this->member_mdl->get_wm_uid_by_wiz_id($request['wiz_id']);
        
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        /*
            table_code 분기기준
            - correction - 영어첨삭게시판
            - dictation.list - 얼굴철판딕테이션
            - express - 이런표현어떻게
            - request - 실시간요청게시판
            - 그외 테이블코드

            url 처리
            - correction - boards/correction.php
            - dictation.list - boards/dictation.list.php
            - express - boards/express.php
            - request - boards/request.php
            - 그외 테이블코드 - boards/board_list.php?table_code=해당 테이블 코드
        */

        $bookmark_url = NULL;
        $bookmark_code = NULL;

        if($request['table_code'] == 'correction')
        {
            $bookmark_code = "9004";
            $bookmark_url = "boards/correction.php";
        }
        else if($request['table_code'] == 'dictation.list')
        {
            $bookmark_code = "9002";
            $bookmark_url = "boards/dictation.list.php";
        }
        else if($request['table_code'] == 'express')
        {
            $bookmark_code = "9001";
            $bookmark_url = "boards/express.php";
        }
        else if($request['table_code'] == 'request')
        {
            $bookmark_code = "9999";
            $bookmark_url = "boards/request.php";
        }
        else
        {
            $bookmark_code = $request['table_code'];
            $bookmark_url = "boards/board_list.php?table_code=".$request['table_code'];
        }

        $bookmark = array(
            'uid' => $wiz_member['wm_uid'],
            'wiz_id' => $request['wiz_id'],
            'code' => $bookmark_code,
            'url' => $bookmark_url, 
            'del_yn' => $request['del_yn'],
        );

        $result = $this->board_mdl->update_boards_bookmark($bookmark);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            if($request['del_yn'] != "Y")
            {
                MintQuest::request_batch_quest('15', $bookmark_code);
            }

            $return_array['res_code'] = '0000';
            $return_array['msg'] = ($request['del_yn'] == "Y") ? "즐겨찾기가 해제되었습니다." : "즐겨찾기가 추가되었습니다.";
            $return_array['data']['del_yn'] = $request['del_yn'];
            echo json_encode($return_array);
            exit;
        }
    }


    /*
        일반게시판 게시물 추천
    */
    public function recommend_article()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim($this->input->post('wiz_id')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "recommend_key" => trim(strtolower($this->input->post('recommend_key'))),
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

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('board_mdl');
        /* 게시판설정 확인 */
        $board_config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);
        
        /* 추천 기능 사용여부 체크 */
        if($board_config['mbn_recom_yn'] != "Y")
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0302";
            $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 추천 할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        /* 게시물 , 댓글 수업중인 회원만 추천가능 여부 */
        if($board_config['mbn_recom_yn_inclass'] == "Y")
        {
            $where = "WHERE (wl.wiz_id = '".$wiz_member['wm_wiz_id']."' || wl.student_uid LIKE '%,".$wiz_member['wm_uid'].",%') AND wl.lesson_state='in class'";
            $inclass = $this->member_mdl->checked_inclass($where);
        
            if($inclass['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0303";
                $return_array['data']['err_msg'] = "수업 중인 회원만 추천할 수 있습니다.";
                echo json_encode($return_array);
                exit;
            }
        }

        /*
            알림시 표기되는 이름 추천회원 닉네임
            우선순위 1. 닉네임 , 2. 영어이름 3. 한글이름
        */
        $display_name = "";
        if($wiz_member["wm_nickname"])
        {
            $display_name = $wiz_member["wm_nickname"];
        }
        else
        {
            $display_name = ($wiz_member['wm_ename']) ? $wiz_member['wm_ename'] : $wiz_member['wm_name'];
        }

        /* 익명게시판 예외처리 */
        $MBN_ANONYMOUS_YN =  $this->config->item('MBN_ANONYMOUS_YN');

        /* 
            익명게시판 여부 
            - config 설정 or 날코딩
        */
        if($board_config['mbn_anonymous_yn'] == "Y" || in_array($request["table_code"], $MBN_ANONYMOUS_YN))
        {     
            $display_name = "익명 회원";
        }
        
        /*
            게시판지기 체크 
            - 추천 무제한 가능
        */
        $is_assistant = "N";
        if(false !== stripos($wiz_member['wm_assistant_code'], "*recomm*"))
        {
            $is_assistant = "Y";
        } 


        $recommend_code = $request['table_code'];

        
        /* 게시글 추천 */

        $article = NULL;
        
        $article = $this->board_mdl->row_article_by_mb_unq($request['table_code'], $request['recommend_key']);
        if($article)
        {
            //검색테이블 업데이트(조회수 증감에 따른)
            $search_params = array(
                'mb_unq' => $request['mb_unq'],
                'hit'    => $article['mb_hit'],
                'recom'  => $article['mb_recom']
            );
            $this->board_mdl->update_search_boards($request['table_code'], $search_params);
        }

        /* 작성자 이름 */
        $article_writer_name = NULL;

        if($article["mb_nickname"])
        {
            $article_writer_name = $article["mb_nickname"];
        }
        else
        {
            $article_writer_name = ($article['mb_ename']) ? $article['mb_ename'] : $article['mb_name'];
        }


        /* 해당 게시물 유무 체크 */
        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

            /* 본인글 추천 제한 */
        if($article["mb_wiz_id"] == $request["wiz_id"])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0305";
            $return_array['data']['err_msg'] = "본인 게시글에는 추천을 할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        /* 추천가능 횟수,현재 횟수 */
        $limit_cnt_msg = "";
        
        /* 추천자 포인트 적립 메세지 */
        $rpoint_msg = "";

        /* 추천후 결과 메세지 */
        $return_msg = "";

        /* 중복추천 수 */
        $overlap_cnt = 0;

        $result = NULL;

        $mb_unq = NULL;
        $tmp_wm = $this->member_mdl->get_wm_uid_by_wiz_id($article['mb_wiz_id']);
        $writer_wm_uid = $tmp_wm['wm_uid'];    // 추천받은 글쓴이의 UID
        
        $notify_table_code = NULL;
        
        $mb_unq = $article['mb_unq']; 
        $notify_table_code = $request['table_code'];

        /* 회원 보유포인트 */
        $wm_point = $wiz_member['wm_point'];

        $recommend = array(
            "mb_unq" => $mb_unq,
            "table_code" => $recommend_code,
            "send_uid" => $wiz_member['wm_uid'],
            "receive_uid" => $writer_wm_uid,
            "co_unq" => 0,
            "regdate" => date('Y-m-d H:i:s')
        );
        

        
        /* 해당 게시글 추천 수 */
        $recommend_article_history = $this->board_mdl->checked_count_article_recommend($wiz_member['wm_uid'], $request["recommend_key"]);
        $overlap_cnt = (int)$recommend_article_history['cnt'] + 1;

        /* 게시판 게시글당 추천 제한여부 */
        if($is_assistant == "N" && $board_config['mbn_recom_ea'] > 0)
        {
            $limit_cnt_msg = "[추천 가능횟수 ".$overlap_cnt."/".$board_config['mbn_recom_ea']."]\n";
        }
        
        /* 
            추천내역 입력 및 추천수 업데이트 
        */
        $result = $this->board_mdl->recommend_article($recommend,$wiz_member,$board_config,$request["recommend_key"]);

        if(is_array($result) && array_key_exists('res_code',$result))
        {
            echo json_encode($result);
            exit;
        }
        elseif(!$result)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        /* 추천시 지급 포인트 없을때 */
        if(($board_config['mbn_recom_rpoint'] == 0 && $board_config['mbn_recom_wpoint'] == 0) || $is_assistant == "Y")
        {
            $return_msg = "추천하였습니다. 훌륭한 글에는 추천을 많이 눌러주세요.";
        }

        /* 추천자 - 게시글 추천시 지급포인트 */
        if($is_assistant == "N" && $board_config['mbn_recom_rpoint'] > 0)
        {            
            /* 익명일시 중복 추천수 미 표기 */
            $tmp_name = ($display_name == "익명 회원") ? $display_name : $display_name.'('.$overlap_cnt.')';
            $secret = ($display_name == "익명 회원") ? "Y" : "N";
        
            $pt_name = $board_config['mbn_table_name'].'의 '.$tmp_name.' 님으로부터 '.number_format($board_config['mbn_recom_rpoint']).'포인트 선물 받았어요*^^*';

            if($wiz_member['wm_uid'] != '' && $wiz_member['wm_uid'] != '0')
            {
                $point = array(
                    'uid' => $wiz_member['wm_uid'],
                    'name' => $wiz_member['wm_name'],
                    'point' => $board_config['mbn_recom_rpoint'],
                    'pt_name'=> $pt_name, 
                    'kind'=> 'R', 
                    'b_kind'=> 'boards',
                    'table_code'=> $recommend_code,
                    'co_unq'=> $mb_unq, 
                    'showYn'=> 'y',
                    'secret'=> $secret,
                    'regdate' => date("Y-m-d H:i:s")
                );

                /* 포인트 내역 입력 및 포인트 추가 */
                $this->load->model('point_mdl');
                $rpoint = $this->point_mdl->set_wiz_point($point);

                if($rpoint < 0)
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
            }
            
        }
        

        /* 작성자 - 댓글 추천시 지급포인트 */
        if($is_assistant == "N" && $board_config['mbn_recom_wpoint'] > 0)
        {
            $rpoint_msg = ($board_config['mbn_recom_rpoint'] > 0) ? " / 본인에게 ".number_format($board_config['mbn_recom_rpoint'])."포인트" : "";
            
                /* 게시물 댓글당 추천이 1회만 가능할때 */
            if($board_config['mbn_recom_ea'] == 1)
            {
                $return_msg = $limit_cnt_msg."게시글 작성자 ".number_format($board_config['mbn_recom_wpoint'])."포인트".$rpoint_msg." 선물 적립. \n훌륭한 글에는 추천을 많이 눌러주세요.";
            }
            else
            {
                $return_msg = $limit_cnt_msg."게시글 작성자 ".number_format($board_config['mbn_recom_wpoint'])."포인트".$rpoint_msg." 선물 적립. \n".number_format($board_config['mbn_recom_ea'])."번까지 중복 추천 가능, 훌륭한 글이라면 ".$board_config['mbn_recom_ea']."추 가즈아!!";
            }

            
            /* 익명일시 중복 추천수 미 표기 */
            $tmp_name = ($display_name == "익명 회원") ? $display_name : $display_name.'('.$overlap_cnt.')';
            $secret = ($display_name == "익명 회원") ? "Y" : "N";

            $pt_name = $board_config['mbn_table_name'].'의 '.$tmp_name.' 님으로부터 '.number_format($board_config['mbn_recom_wpoint']).'포인트 선물 받았어요*^^*';


            if($writer_wm_uid != '' && $writer_wm_uid != '0')
            {
                $point = array(
                    'uid' => $writer_wm_uid,
                    'name' => $article_writer_name,
                    'point' => $board_config['mbn_recom_wpoint'],
                    'pt_name'=> $pt_name, 
                    'kind'=> 'R', 
                    'b_kind'=> 'boards',
                    'table_code'=> $recommend_code,
                    'co_unq'=> $mb_unq, 
                    'showYn'=> 'y',
                    'secret'=> $secret,
                    'regdate' => date("Y-m-d H:i:s")
                );

                /* 포인트 내역 입력 및 포인트 추가 */
                $this->load->model('point_mdl');
                $wpoint = $this->point_mdl->set_wiz_point($point);

                if($wpoint < 0)
                {
                    $return_array['res_code'] = '0500';
                    $return_array['msg'] = "DB ERROR";
                    echo json_encode($return_array);
                    exit;
                }
                    

            }
            
        }
        
        

        /* 
            인증마크
            mbn_certify_yn : 게시판 인증설정

            게시판 설정이 켜져있으며 현재 게시물이 인증되지 않은상태 일때
        */
        if($board_config['mbn_certify_yn'] == "Y" && $article['mb_certify_view'] == 'N')
        {
            /* 인증마크 추천수와 현재 게시물의 추천수를 비교 */
            if($board_config['mbn_certify_move_ea'] <= $result['mb_recom'])
            {
                $regdate = date('Y-m-d H:i:s');
                $certify = $this->board_mdl->certify_article($mb_unq, $regdate);

                if($certify < 0)
                {
                    $return_array['res_code'] = '0500';
                    $return_array['msg'] = "DB ERROR";
                    echo json_encode($return_array);
                    exit;
                }
                else
                {
                    $this->load->model('notify_mdl');
                    //$dealer = $this->notify_mdl->get_dealer_sms_by_wm_uid($wiz_member['wm_uid']);

                    /* 알림*/
                    $notify = array(
                        'uid' => $writer_wm_uid, 
                        'code' => 143, 
                        'message' => '작성하신 글이 민트인증 마크를 받았습니다.', 
                        'table_code' => $notify_table_code, 
                        'user_name' => 'SYSTEM',
                        'board_name' => '', 
                        'content'=> $board_config['mbn_table_name'], 
                        'mb_unq'=>$mb_unq, 
                        'regdate' => date('Y-m-d H:i:s'),
                    );

                    $notify_result = $this->notify_mdl->insert_notify($notify);

                    if($notify_result < 0)
                    {
                        $return_array['res_code'] = '0500';
                        $return_array['msg'] = "DB ERROR";
                        echo json_encode($return_array);
                        exit;
                    }
                }

            }

        }

        /* 얼굴철판딕테이션 수업체험 후기 포인트 지급 및 권한 부여 체크 
            ahop 후기 추천 10회 받으면 알람
        */
        if($request['table_code'] == "1111" && $writer_wm_uid)
        {
            $this->load->model('book_mdl');
            $exam_log = $this->book_mdl->check_exam_log_by_review_id($mb_unq, $writer_wm_uid);

            // AHOP시험 후기일때
            if ($exam_log && $result['mb_recom'] == 10) 
            {
                $this->load->model('notify_mdl');
                $book_name = explode(" ", $exam_log['book_name']);
                
                $aNotifyData = array(
                    'uid'   => $writer_wm_uid,
                    'code'  =>' 320',
                    'table_code' => $request['table_code'],
                    'user_name' => 'SYSTEM',
                    'content' =>$board_config['mbn_table_name'],
                    'message' => str_replace('##',$exam_log['book_name'],'작성하신 ## 합격후기에 추천 10회가 달성되었습니다. 시험응시 페이지에서 보상을 받으세요.'),
                    'mb_unq' =>$mb_unq,
                    'co_unq' =>'',
                    'go_url'=>'http://www.mint05.com/pubhtml/boards/curriculum_list.php?table_code=1367&sub='.$book_name[2].'&tab=3'
                );
                $notify_result = $this->notify_mdl->insert_notify($aNotifyData);

                // 푸시 알림발송
                $pInfo = array("table_code"=> $request['table_code'], "mb_unq" => $mb_unq, "subject" => $exam_log['book_name']);
                AppPush::send_push($writer_wm_uid, "2204", $pInfo);
            }
            else
            {
                $recommend_code = "9002";
                $special_config = $this->board_mdl->row_board_special_config_by_table_code($recommend_code);

                /* 얼철딕 후기 자동승인 추천수 설정 확인*/
                if($special_config["mbn_review_recomm_count"] > 0 && $result['mb_recom'] >= $special_config["mbn_review_recomm_count"])
                {
                    $approval = $this->board_mdl->approval_cafaboard($mb_unq,$writer_wm_uid);

                    if($approval)
                    {
                        $cafeCount = $approval['cnt'];
                        $point = board_get_point($mb_unq, $writer_wm_uid, 'Y');
                        if($point !== false)
                        {
                            $message = "얼철딕 {$cafeCount}회 도전 성공 ".number_format($point)."포인트 포상 및 얼철딕 ".($cafeCount+1)."회~".($cafeCount+$special_config['mbn_cafe_count'])."회 작성권한 부여";
                            $pointparam = array(
                                'uid' => $writer_wm_uid,
                                'point' => $point,
                                'name' => $article['mb_name'],
                                'pt_name'=> $message, 
                                'kind' => 'e',
                                'b_kind' => 'cafe',
                                'co_unq'=> $mb_unq, 
                                'showYn'=> 'y',
                                'regdate' => date("Y-m-d H:i:s")
                            );

                            /* 포인트 내역 입력 및 포인트 추가 */
                            $this->load->model('point_mdl');
                            $this->point_mdl->set_wiz_point($pointparam);
                            
                            // 승인댓글 달기
                            $comm_params = array(
                                'mb_unq' => $mb_unq,
                                'comment' => $message,
                                'writer_id' =>'admin',
                                'writer_name' =>'민트영어',
                                'table_code' =>'1111',
                                'notice_yn' =>'1',
                                'co_thread' =>'A',
                                'regdate' =>date('Y-m-d H:i:s'),
                            );
                            $this->board_mdl->insert_comment($comm_params);

                        }
                        
                    }

                }
            }
            
        }
       
        /* 베스트글 복사 여부 체크 */
        if($board_config['mbn_copy_yn'] == "Y")
        {         
            if($result['mb_recom'] == $board_config['mbn_copy_move_ea'])
            {
                board_checked_best_article($request['table_code'], $mb_unq);
            }

            // 추천수 100일때 1118 민트를 빛낸 회원게시판에 게시글 복사
            $brilliant_allow_table = [
                '1337',
                '1128',
                '1340',
                '1111',
                '1350',
            ];
			if (in_array($request['table_code'],$brilliant_allow_table) && $result['mb_recom'] == 100) {
                board_checked_brilliant_article($request['table_code'], $mb_unq, $board_config, $article);
			}
        }

        $parent_article = null;
        if($article['mb_parent_key'])
        {
            $parent_article = $this->board_mdl->row_article_by_mb_unq($request['table_code'], $article['mb_parent_key']);
            if($parent_article)
            {
                //검색테이블 업데이트(조회수 증감에 따른)
                $search_params = array(
                    'mb_unq' => $request['mb_unq'],
                    'hit'    => $parent_article['mb_hit'],
                    'recom'  => $parent_article['mb_recom']
                );
                $this->board_mdl->update_search_boards($request['table_code'], $search_params);
            }
        }

        // 지식인 게시판 답변글이 추천 일정횟수 이상일 시 채택시켜준다. 적용이후꺼만 채택 시켜야한다
        if($article['mb_parent_key'] && $parent_article['mb_regdate'] > '2021-03-17 10:15:00' && in_array($request['table_code'], $this->knowledge_qna_type_board) && $board_config['mbn_adopt_ea'] > 0 && $board_config['mbn_adopt_ea'] == $result['mb_recom'])
        {
            $limit_over = null;
            //채택수가 채택횟수제한에 걸리지 않았는지 체크
            if($board_config['mbn_recom_adopt_limit'])
            {
                $limit_over = $this->board_mdl->checked_anwser_article_adopt_limit_over($request['table_code'], $article['mb_parent_key'], 2, $board_config['mbn_recom_adopt_limit']);
            }
            
            if(!$limit_over)
            {
                $datas = array(
                    'selected_uid'  => $writer_wm_uid,              //채택된유저 uid
                    'mb_unq'        => $article['mb_parent_key'],   //질문글의 pk
                    'select_key'    => $request['recommend_key'],   //채택받을 답변글
                    'table_code'    => $request['table_code'],
                    'star'          => '5',
                    'adopt_type'    => 2,      // 1:질문자채택, 2:시스템채택
                    'sim_content3'  => '추천 '.$board_config['mbn_adopt_ea'].'회 달성 시스템 자동채택',
                );
        
                $this->board_mdl->knowledge_adopt_article($datas);
    
                if($article['mbn_recom_adopt_reward_point'] > 0)
                {
                    //자동채택 보상 포인트
                    $pointparam = array(
                        'uid' => $writer_wm_uid,
                        'point' => $article['mbn_recom_adopt_reward_point'],
                        'name' => $article['mb_name'],
                        'pt_name'=> '추천 '.$board_config['mbn_adopt_ea'].'회 달성 시스템 자동채택 포인트보상', 
                        'kind' => 'kg',
                        'b_kind' => 'boards',
                        'co_unq'=> $request['recommend_key'], 
                        'showYn'=> 'y',
                        'regdate' => date("Y-m-d H:i:s")
                    );

                    /* 포인트 내역 입력 및 포인트 추가 */
                    $this->load->model('point_mdl');
                    $this->point_mdl->set_wiz_point($pointparam);
                }

            }
            
        }

        //퀘스트
        MintQuest::request_batch_quest('13');
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = $return_msg;
        $return_array['data']['mb_recom'] = $result['mb_recom'];
        $return_array['data']['wm_point'] = $wm_point;
        
        echo json_encode($return_array);
        exit;
        

    }


    /*
        특수 게시판 게시물 추천
        
        인증마크 설정적용
        베스트글 이동 설정X
    */
    public function recommend_article_special()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim($this->input->post('wiz_id')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "recommend_key" => trim(strtolower($this->input->post('recommend_key'))),
            "recommend_type" => trim(strtolower($this->input->post('recommend_type'))),
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

        /* 회원 확인 */
        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('member_mdl');

        if(strpos($request['table_code'],'dictation') !==false)
        {
            $request['table_code'] = 'dictation';
        }

        /* 게시판설정 확인 */
        $this->load->model('board_mdl');
        $board_config = [];
        if($request['table_code'] == 'express')
        {
            $recommend_code = "9001";
        }
        else if($request['table_code'] == 'correction')
        {
            $recommend_code = "9004";
        }
        else if($request['table_code'] =='dictation')
        {
            $recommend_code = "9002";
        }

        $board_config = array_merge($board_config,$this->board_mdl->row_board_special_config_by_table_code($recommend_code));

        $recom_word = $request['table_code'] =='dictation' ? '추천 혹은 반대':'추천';

        /* 추천 기능 사용여부 체크 */
        if($board_config['mbn_recom_yn'] != "Y")
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0302";
            $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 ".$recom_word." 할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }


        /* 게시물 , 댓글 수업중인 회원만 추천가능 여부 */
        if($board_config['mbn_recom_yn_inclass'] == "Y")
        {
            $where = "WHERE (wl.wiz_id = '".$wiz_member['wm_wiz_id']."' || wl.student_uid LIKE '%,".$wiz_member['wm_uid'].",%') AND wl.lesson_state='in class'";
            $inclass = $this->member_mdl->checked_inclass($where);
        
            if($inclass['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0303";
                $return_array['data']['err_msg'] = "수업 중인 회원만 ".$recom_word."할 수 있습니다.";
                echo json_encode($return_array);
                exit;
            }
        }

        /*
            알림시 표기되는 이름 추천회원 닉네임
            우선순위 1. 닉네임 , 2. 영어이름 3. 한글이름
        */
        $display_name = "";
        if($wiz_member["wm_nickname"])
        {
            $display_name = $wiz_member["wm_nickname"];
        }
        else
        {
            $display_name = ($wiz_member['wm_ename']) ? $wiz_member['wm_ename'] : $wiz_member['wm_name'];
        }
        
        /*
            게시판지기 체크 
            - 추천 무제한 가능
        */
        $is_assistant = "N";
        if(false !== stripos($wiz_member['wm_assistant_code'], "*recomm*"))
        {
            $is_assistant = "Y";
        } 
   

        /* 게시글 추천 */
        $article = NULL;
        /* 작성자 이름 */
        $article_writer_name = NULL;
        
        if($request['table_code'] == 'express')
        {
            $article = $this->board_mdl->row_article_express_by_mb_uid($request['recommend_key']);
            $article_writer_name = ($article['wm_nickname']) ? $article['wm_nickname'] : $article['mb_m_name'];
        }
        else if($request['table_code'] =='dictation')
        {
            $article = $this->board_mdl->row_article_cafeboard_by_c_uid($request['recommend_key']);
            $article_writer_name = $article['mb_name'];
        }
        else if($request['table_code'] == 'correction')
        {
            $article = $this->board_mdl->row_article_wiz_correct_by_w_id($request['recommend_key']);
            $article_writer_name = $article['mb_name'];
        }

        
       

        /* 해당 게시물 유무 체크 */
        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

            /* 본인글 추천 제한 */
        if($article["mb_wiz_id"] == $request["wiz_id"])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0305";
            $return_array['data']['err_msg'] = "본인 게시글에는 ".$recom_word." 할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

    
            /* 게시판 1일 추천 제한여부 */
        if($is_assistant == "N" && $board_config['mbn_recom_limit'] > 0)
        {
            /* 금일 해당 게시판 추천 수 */
            $today = date('Y-m-d');
            $recommend_today_history = $this->board_mdl->checked_count_today_article($wiz_member['wm_uid'], $recommend_code, $today);
            
            if($board_config['mbn_recom_limit'] <= $recommend_today_history['cnt'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0306";
                $return_array['data']['err_msg'] = $board_config['mbn_table_name']." 게시판의 하루 ".$recom_word." 제한 수를 초과하여 ".$board_config['mbn_table_name']." 게시판은 오늘 하루 동안 ".$recom_word." 불가능합니다.";
                echo json_encode($return_array);
                exit;
            }

        }

        /* 추천가능 횟수,현재 횟수 */
        $limit_cnt_msg = "";
        
        /* 추천자 포인트 적립 메세지 */
        $rpoint_msg = "";

        /* 추천후 결과 메세지 */
        $return_msg = "";

        /* 중복추천 수 */
        $overlap_cnt = 0;


        /* 해당 게시글 추천 수 */
        $recommend_article_history = $this->board_mdl->checked_count_article_recommend($wiz_member['wm_uid'], $request["recommend_key"]);
        $overlap_cnt = (int)$recommend_article_history['cnt'] + 1;

        /* 게시판 게시글당 추천 제한여부 */
        if($is_assistant == "N" && $board_config['mbn_recom_ea'] > 0)
        {
            if($board_config['mbn_recom_ea'] <= $recommend_article_history['cnt'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0307";
                $return_array['data']['err_msg'] = $board_config['mbn_table_name']." 게시판은 글당 ".$board_config['mbn_recom_ea']."회 까지 ".$recom_word." 가능합니다.";
                echo json_encode($return_array);
                exit;
            }

            $limit_cnt_msg = "[추천 가능횟수 ".$overlap_cnt."/".$board_config['mbn_recom_ea']."]\n";
        }


        $result = NULL;

        $mb_unq = NULL;
        $tmp_wm = $this->member_mdl->get_wm_uid_by_wiz_id($article['mb_wiz_id']);
        $wm_uid = $tmp_wm['wm_uid'];
        
        $notify_table_code = NULL;
        
        if($request['table_code'] == 'express')
        {
            $mb_unq = $article['mb_uid']; 
            $notify_table_code = 'express.view';
        }
        else if($request['table_code'] =='dictation')
        {
            $mb_unq = $article['mb_c_uid']; 
            $notify_table_code = 'dictation.view';
        }
        else if($request['table_code'] == 'correction')
        {
            $mb_unq = $article['mb_w_id']; 
            $notify_table_code = 'correction.view';
        }
    
        /* 회원 보유포인트 */
        $wm_point = $wiz_member['wm_point'];

        $recommend = array(
            "mb_unq" => $mb_unq,
            "table_code" => $recommend_code,
            "send_uid" => $wiz_member['wm_uid'],
            "receive_uid" => $wm_uid,
            "co_unq" => 0,
            "regdate" => date('Y-m-d H:i:s')
        );

        if($request['table_code'] =='dictation' && $request['recommend_type'] =='decl')
        {
            $recommend['receive_uid'] = $wiz_member['wm_uid'];  // 반대는 send_uid, receive_uid 둘다 행위자의 uid
        }
        
        /* 
            추천내역 입력 및 추천수 업데이트 
        */

        if($request['table_code'] == 'express')
        {
            $result = $this->board_mdl->recommend_article_express($recommend);
        }
        else if($request['table_code'] =='dictation')
        {
            $result = $this->board_mdl->recommend_article_cafeboard($recommend , $request['recommend_type']);
        }
        else if($request['table_code'] == 'correction')
        {
            $result = $this->board_mdl->recommend_article_correct($recommend);
        }

        if(!$result)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }


        /* 추천시 지급 포인트 없을때 */
        if(($board_config['mbn_recom_rpoint'] == 0 && $board_config['mbn_recom_wpoint'] == 0) || $is_assistant == "Y")
        {
            $return_msg = $request['table_code'] =='dictation' && $request['recommend_type'] =='decl' ? '반대하였습니다.':"추천하였습니다. 훌륭한 글에는 추천을 많이 눌러주세요.";
        }

        // 반대 시 행위자에게만 포인트 24 지급
        if($request['table_code'] =='dictation' && $request['recommend_type'] =='decl')
        {
            $pt_name = $return_msg = '얼굴철판딕테이션 추천/반대 이벤트로 24포인트 적립 축하';
            if($wiz_member['wm_uid'])
            {
                $point = array(
                    'uid' => $wiz_member['wm_uid'],
                    'name' => $wiz_member['wm_name'],
                    'point' => '24',
                    'pt_name'=> $pt_name, 
                    'kind'=> 'v', 
                    'b_kind'=> 'cafe_board',
                    'table_code'=> $recommend_code,
                    'co_unq'=> $mb_unq, 
                    'showYn'=> 'y',
                    'regdate' => date("Y-m-d H:i:s")
                );

                /* 포인트 내역 입력 및 포인트 추가 */
                $this->load->model('point_mdl');
                $rpoint = $this->point_mdl->set_wiz_point($point);

                if($rpoint < 0)
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
            }
        }
        else
        {
            /* 추천자 - 게시글 추천시 지급포인트 */
            if($is_assistant == "N" && $board_config['mbn_recom_rpoint'] > 0)
            {            
                /* 익명일시 중복 추천수 미 표기 */
                $tmp_name = ($display_name == "익명 회원") ? $display_name : $display_name.'('.$overlap_cnt.')';
                $secret = ($display_name == "익명 회원") ? "Y" : "N";

                $pt_name = $board_config['mbn_table_name'].'의 '.$tmp_name.' 님으로부터 '.number_format($board_config['mbn_recom_rpoint']).'포인트 선물 받았어요*^^*';

                if($wiz_member['wm_uid'])
                {
                    $point = array(
                        'uid' => $wiz_member['wm_uid'],
                        'name' => $wiz_member['wm_name'],
                        'point' => $board_config['mbn_recom_rpoint'],
                        'pt_name'=> $pt_name, 
                        'kind'=> ($request['table_code'] == "dictation.t" || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list') ? 'v' : 'r', 
                        'b_kind'=> ($request['table_code'] == "dictation.t" || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list') ? 'cafe_board' : 'boards',
                        'table_code'=> $recommend_code,
                        'co_unq'=> $mb_unq, 
                        'showYn'=> 'y',
                        'secret'=> $secret,
                        'regdate' => date("Y-m-d H:i:s")
                    );

                    /* 포인트 내역 입력 및 포인트 추가 */
                    $this->load->model('point_mdl');
                    $rpoint = $this->point_mdl->set_wiz_point($point);

                    if($rpoint < 0)
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
                }
                
            }


            /* 작성자 - 댓글 추천시 지급포인트 */
            if($is_assistant == "N" && $board_config['mbn_recom_wpoint'] > 0)
            {
                $rpoint_msg = ($board_config['mbn_recom_rpoint'] > 0) ? " / 본인에게 ".number_format($board_config['mbn_recom_rpoint'])."포인트" : "";
                
                    /* 게시물 댓글당 추천이 1회만 가능할때 */
                if($board_config['mbn_recom_ea'] == 1)
                {
                    $return_msg = $limit_cnt_msg."게시글 작성자 ".number_format($board_config['mbn_recom_wpoint'])."포인트".$rpoint_msg." 선물 적립. \n훌륭한 글에는 추천을 많이 눌러주세요.";
                }
                else
                {
                    $return_msg = $limit_cnt_msg."게시글 작성자 ".number_format($board_config['mbn_recom_wpoint'])."포인트".$rpoint_msg." 선물 적립. \n".number_format($board_config['mbn_recom_ea'])."번까지 중복 추천 가능, 훌륭한 글이라면 ".$board_config['mbn_recom_ea']."추 가즈아!!";
                }

                
                /* 익명일시 중복 추천수 미 표기 */
                $tmp_name = ($display_name == "익명 회원") ? $display_name : $display_name.'('.$overlap_cnt.')';
                $secret = ($display_name == "익명 회원") ? "Y" : "N";

                $pt_name = $board_config['mbn_table_name'].'의 '.$tmp_name.' 님으로부터 '.number_format($board_config['mbn_recom_wpoint']).'포인트 선물 받았어요*^^*';

                if($wm_uid != '' && $wm_uid != '0')
                {
                    $point = array(
                        'uid' => $wm_uid,
                        'name' => $article_writer_name,
                        'point' => $board_config['mbn_recom_wpoint'],
                        'pt_name'=> $pt_name, 
                        'kind'=> ($request['table_code'] == "dictation.t" || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list') ? 'v' : 'r', 
                        'b_kind'=> ($request['table_code'] == "dictation.t" || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list') ? 'cafe_board' : 'boards',
                        'table_code'=> $recommend_code,
                        'co_unq'=> $mb_unq, 
                        'showYn'=> 'y',
                        'secret'=> $secret,
                        'regdate' => date("Y-m-d H:i:s")
                    );

                    /* 포인트 내역 입력 및 포인트 추가 */
                    $this->load->model('point_mdl');
                    $wpoint = $this->point_mdl->set_wiz_point($point);

                    if($wpoint < 0)
                    {
                        $return_array['res_code'] = '0500';
                        $return_array['msg'] = "DB ERROR";
                        echo json_encode($return_array);
                        exit;
                    }
                        

                }
                
            }

            /* 
                인증마크
                mbn_certify_yn : 게시판 인증설정

                게시판 설정이 켜져있으며 현재 게시물이 인증되지 않은상태 일때
            */
            if($board_config['mbn_certify_yn'] == "Y" && $article['mb_certify_view'] == 'N')
            {
                /* 인증마크 추천수와 현재 게시물의 추천수를 비교 */
                if($board_config['mbn_certify_move_ea'] <= $result['mb_recom'])
                {
                    $regdate = date('Y-m-d H:i:s');
                    if($request['table_code'] == 'express')
                    {
                        $certify = $this->board_mdl->certify_article_express($mb_unq, $regdate);
                    }
                    else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
                    {
                        $certify = $this->board_mdl->certify_article_cafeboard($mb_unq, $regdate);
                    }
                    if($request['table_code'] == 'correction')
                    {
                        $certify = $this->board_mdl->certify_article_correct($mb_unq, $regdate);
                    }
                

                    if($certify < 0)
                    {
                        $return_array['res_code'] = '0500';
                        $return_array['msg'] = "DB ERROR";
                        echo json_encode($return_array);
                        exit;
                    }
                    else
                    {
                        $this->load->model('notify_mdl');
                        //$dealer = $this->notify_mdl->get_dealer_sms_by_wm_uid($wiz_member['wm_uid']);

                        /* 알림*/

                        $notify = array(
                            'uid' => $wm_uid, 
                            'code' => 143, 
                            'message' => '작성하신 글이 민트인증 마크를 받았습니다.', 
                            'table_code' => $notify_table_code, 
                            'user_name' => 'SYSTEM',
                            'board_name' => '', 
                            'content'=> $board_config['mbn_table_name'], 
                            'mb_unq'=>$mb_unq, 
                            'regdate' => date('Y-m-d H:i:s'),
                        );

                        $notify_result = $this->notify_mdl->insert_notify($notify);

                        if($notify_result < 0)
                        {
                            $return_array['res_code'] = '0500';
                            $return_array['msg'] = "DB ERROR";
                            echo json_encode($return_array);
                            exit;
                        }
                    }

                }

            }
        }

        $parent_article = null;
        if($article['mb_parent_key'])
        {
            $parent_article = $this->board_mdl->row_article_express_by_mb_uid($article['mb_parent_key']);
        }
        
        // 지식인 게시판 답변글이 추천 일정횟수 이상일 시 채택시켜준다. 적용이후꺼만 채택 시켜야한다
        if($article['mb_parent_key'] && $parent_article['mb_regdate'] > '2021-03-17 10:15:00' && in_array($request['table_code'], $this->knowledge_qna_type_board) && $board_config['mbn_adopt_ea'] > 0 && $board_config['mbn_adopt_ea'] == $result['mb_recom'])
        {
            $limit_over = null;
            //채택수가 채택횟수제한에 걸리지 않았는지 체크
            if($board_config['mbn_recom_adopt_limit'])
            {
                $limit_over = $this->board_mdl->checked_anwser_article_adopt_limit_over($request['table_code'], $article['mb_parent_key'], 2, $board_config['mbn_recom_adopt_limit']);
            }
            
            if(!$limit_over)
            {
                $datas = array(
                    'selected_uid'  => $wm_uid,                     //채택된유저 uid
                    'mb_unq'        => $article['mb_parent_key'],   //질문글의 pk
                    'select_key'    => $request['recommend_key'],   //채택받을 답변글
                    'table_code'    => $recommend_code,
                    'star'          => '5',
                    'adopt_type'    => 2,      // 1:질문자채택, 2:시스템채택
                    'sim_content3'  => '추천 '.$board_config['mbn_adopt_ea'].'회 달성 시스템 자동채택',
                );
        
                $this->board_mdl->knowledge_adopt_article($datas);

                if($article['mbn_recom_adopt_reward_point'] > 0)
                {
                    //자동채택 보상 포인트
                    $pointparam = array(
                        'uid' => $wm_uid,
                        'point' => $article['mbn_recom_adopt_reward_point'],
                        'name' => $article['mb_m_name'],
                        'pt_name'=> '추천 '.$board_config['mbn_adopt_ea'].'회 달성 시스템 자동채택 포인트보상', 
                        'kind' => 'kg',
                        'b_kind' => 'boards',
                        'co_unq'=> $request['recommend_key'], 
                        'showYn'=> 'y',
                        'regdate' => date("Y-m-d H:i:s")
                    );

                    /* 포인트 내역 입력 및 포인트 추가 */
                    $this->load->model('point_mdl');
                    $this->point_mdl->set_wiz_point($pointparam);
                }
                
            }
            
        }

        //퀘스트
        MintQuest::request_batch_quest('13', $request['recommend_key'].MintQuest::make_quest_subfix($request['table_code']));

        $return_array['res_code'] = '0000';
        $return_array['msg'] = $return_msg;
        $return_array['data']['recommend'] = ($request['recommend_type'] == 'decl') ? $result['mb_decl'] : $result['mb_recom'];
        $return_array['data']['wm_point'] = $wm_point;
        echo json_encode($return_array);
        exit;

    }


    /*
        일반게시판 , 특수게시판 공통 댓글 추천
    */
    public function recommend_comment()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim($this->input->post('wiz_id')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "recommend_key" => trim(strtolower($this->input->post('recommend_key'))),
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

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('board_mdl');
        /* 게시판설정 확인 */
       

        if($request['table_code'] == 'express')
        {
            $recommend_code = "9001";
            $board_config = $this->board_mdl->row_board_special_config_by_table_code($recommend_code);
        }
        else if($request['table_code'] == 'correction')
        {
            $recommend_code = "9004";
            $board_config = [];
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            $recommend_code = "9002";
            $board_config = $this->board_mdl->row_board_special_config_by_table_code($recommend_code);
        }
        else
        {
            $board_config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);
        }
    

        /* 추천 기능 사용여부 체크 */
        if($board_config['mbn_recom_yn'] != "Y")
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0302";
            $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 추천 할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        /* 댓글 수업중인 회원만 추천가능 여부 */
        if($board_config['mbn_recom_inclass_re'] == "Y")
        {
            $where = "WHERE (wl.wiz_id = '".$wiz_member['wm_wiz_id']."' || wl.student_uid LIKE '%,".$wiz_member['wm_uid'].",%') AND wl.lesson_state='in class'";
            $inclass = $this->member_mdl->checked_inclass($where);
        
            if($inclass['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0303";
                $return_array['data']['err_msg'] = "수업 중인 회원만 추천할 수 있습니다.";
                echo json_encode($return_array);
                exit;
            }
        }

        /*
            알림시 표기되는 이름 추천회원 닉네임
            우선순위 1. 닉네임 , 2. 영어이름 3. 한글이름
        */
        $display_name = "";
        if($wiz_member["wm_nickname"])
        {
            $display_name = $wiz_member["wm_nickname"];
        }
        else
        {
            $display_name = ($wiz_member['wm_ename']) ? $wiz_member['wm_ename'] : $wiz_member['wm_name'];
        }

        /* 익명게시판 예외처리 */
        $MBN_ANONYMOUS_YN =  $this->config->item('MBN_ANONYMOUS_YN');

        /* 
            익명게시판 여부 
            - config 설정 or 날코딩
        */
        if($board_config['mbn_anonymous_yn'] == "Y" || in_array($request["table_code"], $MBN_ANONYMOUS_YN))
        {     
            $display_name = "익명 회원";
        }
        
        /*
            게시판지기 체크 
            - 추천 무제한 가능
        */
        $is_assistant = "N";
        if(false !== stripos($wiz_member['wm_assistant_code'], "*recomm*"))
        {
            $is_assistant = "Y";
        } 


        if($request['table_code'] == 'express')
        {
            $recommend_code = "9001";
        }
        else if($request['table_code'] == 'correction')
        {
            $recommend_code = "9004";
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            $recommend_code = "9002";
        }
        else
        {
            $recommend_code = $request['table_code'];
        }

        

        /* 댓글 추천 */
        $comment = NULL;
        
        if($request['table_code'] == 'express')
        {
            $comment = $this->board_mdl->row_article_express_comment_by_uid($request['recommend_key']);
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            $comment = $this->board_mdl->row_article_cafeboard_comment_by_unq($request['recommend_key']);
        }
        else
        {
            $comment = $this->board_mdl->row_article_comment_by_co_unq($request['recommend_key']);
        }

        /* 해당 게시물 유무 체크 */
        if(!$comment)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        $mb_unq = NULL;
        $mbc_co_unq = NULL;
        $tmp_wm = NULL;
        $comment_wm_uid = NULL;
        $comment_wm_name = NULL;
        $comment_writer_id = NULL;

        if($request['table_code'] == 'express')
        {
            $mb_unq = $comment['mbc_e_id']; 
            $mbc_co_unq = $comment['mbc_co_unq'];
            $tmp_wm = $this->member_mdl->get_wm_uid_by_wiz_id($comment['mbc_wiz_id']);
            $comment_wm_uid = $tmp_wm['wm_uid'];
            $comment_wm_name = $comment['mbc_writer_nickname'];
            $comment_writer_id = $comment['mbc_wiz_id'];
        }
        else if($request['table_code'] == "dictation.t" || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            $mb_unq = $comment['mbc_c_uid']; 
            $mbc_co_unq = $comment['mbc_unq'];
            $tmp_wm = $this->member_mdl->get_wm_uid_by_wiz_id($comment['mbc_wirter_id']);
            $comment_wm_uid = $tmp_wm['wm_uid'];
            $comment_wm_name = $comment['mbc_writer_name'];
            $comment_writer_id = $comment['mbc_wirter_id'];
        }
        else
        {
            $mb_unq = $comment['mb_unq']; 
            $mbc_co_unq = $comment['mbc_co_unq'];
            $comment_wm_uid = $comment['wm_uid'];
            $comment_wm_name = $comment['wm_name'];
            $comment_writer_id = $comment['mbc_wiz_id'];
        }


        /* 본인글 추천 제한 */
        if($comment_writer_id == $request['wiz_id'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0305";
            $return_array['data']['err_msg'] = "본인 댓글에는 추천을 할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        /* 추천가능 횟수,현재 횟수 */
        $limit_cnt_msg = "";
        
        /* 추천자 포인트 적립 메세지 */
        $rpoint_msg = "";

        /* 추천후 결과 메세지 */
        $return_msg = "";

        /* 중복추천 수 */
        $overlap_cnt = 0;


        /* 해당 댓글 추천 수 */
        $recommend_comment_history = $this->board_mdl->checked_count_comment_recommend($wiz_member['wm_uid'], $request["recommend_key"]);
        $overlap_cnt = (int)$recommend_comment_history['cnt'] + 1;

        /* 게시판 댓글당 추천 제한여부 */
        if($is_assistant == "N" && $board_config['mbn_recom_ea_re'] > 0)
        {
            $limit_cnt_msg = "[추천 가능횟수 ".$overlap_cnt."/".$board_config['mbn_recom_ea_re']."]\n";
        }
        
        $result = NULL;

        /*회원 현재 보유포인트 */
        $wm_point = $wiz_member['wm_point'];

        $recommend = array(
            "mb_unq" => $mb_unq,
            "table_code" => $recommend_code,
            "send_uid" => $wiz_member['wm_uid'],
            "receive_uid" => $comment_wm_uid,
            "co_unq" => $mbc_co_unq,
            "regdate" => date('Y-m-d H:i:s')
        );
        
        /* 
            추천내역 입력 및 추천수 업데이트 
        */

        $special_table = '';
        if($request['table_code'] == 'express')
        {
            //$result = $this->board_mdl->recommend_article_express_commend($recommend);
            $special_table = 'express';
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            //$result = $this->board_mdl->recommend_article_cafeboard_commend($recommend);
            $special_table = 'cafeboard';
        }
        
        $result = $this->board_mdl->recommend_article_commend($recommend,$special_table,$wiz_member,$board_config,$request["recommend_key"]);

        if(is_array($result) && array_key_exists('res_code',$result))
        {
            echo json_encode($result);
            exit;
        }
        elseif(!$result)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }


        /* 추천시 지급 포인트 없을때 */
        if(($board_config['mbn_recom_rpoint_re'] == 0 && $board_config['mbn_recom_wpoint_re'] == 0) || $is_assistant == "Y")
        {
            $return_msg = "추천하였습니다. 훌륭한 글에는 추천을 많이 눌러주세요.";
        }

        /* 추천자 - 댓글 추천시 지급포인트 */
        if($is_assistant == "N" && $board_config['mbn_recom_rpoint_re'] > 0)
        {
            $rpoint_msg = " / 본인에게 ".number_format($board_config['mbn_recom_rpoint_re'])."포인트";
            
            /* 익명일시 중복 추천수 미 표기 */
            $tmp_name = ($display_name == "익명 회원") ? $display_name : $display_name.'('.$overlap_cnt.')';
            $secret = ($display_name == "익명 회원") ? "Y" : "N";
        
            $pt_name = $board_config['mbn_table_name'].'의 '.$tmp_name.' 님으로부터 '.number_format($board_config['mbn_recom_rpoint_re']).'포인트 선물 받았어요*^^*';

            if($wiz_member['wm_uid'] != '' && $wiz_member['wm_uid'] != '0')
            {
                $point = array(
                    'uid' => $wiz_member['wm_uid'],
                    'name' => $wiz_member['wm_name'],
                    'point' => $board_config['mbn_recom_rpoint_re'],
                    'pt_name'=> $pt_name, 
                    'kind'=> 'R', 
                    'b_kind'=> 'boards_co',
                    'co_unq'=> $mbc_co_unq, 
                    'table_code'=> $recommend_code,
                    'showYn'=> 'y',
                    'secret'=> $secret,
                    'regdate' => date("Y-m-d H:i:s")
                );

                /* 포인트 내역 입력 및 포인트 추가 */
                $this->load->model('point_mdl');
                $rpoint = $this->point_mdl->set_wiz_point($point);

                if($rpoint < 0)
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

            }
            
        }
        

        /* 작성자 - 댓글 추천시 지급포인트 */
        if($is_assistant == "N" && $board_config['mbn_recom_wpoint_re'] > 0)
        {
            $rpoint_msg = ($board_config['mbn_recom_rpoint_re'] > 0) ? " / 본인에게 ".number_format($board_config['mbn_recom_rpoint_re'])."포인트" : "";

                /* 게시물 댓글당 추천이 1회만 가능할때 */
            if($board_config['mbn_recom_ea_re'] == 1)
            {
                $return_msg = $limit_cnt_msg."게시글 작성자 ".number_format($board_config['mbn_recom_wpoint_re'])."포인트".$rpoint_msg." 선물 적립. \n훌륭한 글에는 추천을 많이 눌러주세요.";
            }
            else
            {
                $return_msg = $limit_cnt_msg."게시글 작성자 ".number_format($board_config['mbn_recom_wpoint_re'])."포인트".$rpoint_msg." 선물 적립. \n".number_format($board_config['mbn_recom_ea_re'])."번까지 중복 추천 가능, 훌륭한 글이라면 ".$board_config['mbn_recom_ea_re']."추 가즈아!!";
            }

            
            
            /* 익명일시 중복 추천수 미 표기 */
            $tmp_name = ($display_name == "익명 회원") ? $display_name : $display_name.'('.$overlap_cnt.')';
            $secret = ($display_name == "익명 회원") ? "Y" : "N";

            $pt_name = $board_config['mbn_table_name'].'의 '.$tmp_name.' 님으로부터 '.number_format($board_config['mbn_recom_wpoint_re']).'포인트 선물 받았어요*^^*';

            if($comment_wm_uid != '' && $comment_wm_uid != '0')
            {
                $point = array(
                    'uid' => $comment_wm_uid,
                    'name' => $comment_wm_name,
                    'point' => $board_config['mbn_recom_wpoint_re'],
                    'pt_name'=> $pt_name, 
                    'kind'=> 'R', 
                    'b_kind'=> 'boards_co',
                    'co_unq'=> $mbc_co_unq, 
                    'table_code'=> $recommend_code,
                    'showYn'=> 'y',
                    'secret'=> $secret,
                    'regdate' => date("Y-m-d H:i:s")
                );

                /* 포인트 내역 입력 및 포인트 추가 */
                $this->load->model('point_mdl');
                $wpoint = $this->point_mdl->set_wiz_point($point);

                if($wpoint < 0)
                {
                    $return_array['res_code'] = '0500';
                    $return_array['msg'] = "DB ERROR";
                    echo json_encode($return_array);
                    exit;
                }

            }
            
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = $return_msg;
        $return_array['data']['mbc_recom'] = $result['mbc_recom'];
        $return_array['data']['wm_point'] = $wm_point;
       
        echo json_encode($return_array);
        exit;



    }





    /*
        스크랩
    */
    public function clip()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "mb_unq" => trim(strtoupper($this->input->post('mb_unq'))),
            "msg" => $this->input->post('msg'),
            "clip_type" => $this->input->post('clip_type') ? $this->input->post('clip_type') : null,
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

        $this->load->model('board_mdl');
        
        if($request['table_code'] == 'express')
        {
            $article = $this->board_mdl->row_article_express_by_mb_uid($request['mb_unq']);
        }
        else if($request['table_code'] == 'correction')
        {
            $article = $this->board_mdl->row_article_wiz_correct_by_w_id($request['mb_unq']);
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            $article = $this->board_mdl->row_article_cafeboard_by_c_uid($request['mb_unq']); 
        }
        else
        {
            if($request['clip_type'] == 'movie'){

                
                $article_movie = $this->board_mdl->row_article_table_code_by_mb_unq($request['mb_unq']);

                if(!$article_movie){

                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0305";
                    $return_array['data']['err_msg'] = "동영상강좌 테이블코드를 확인해주세요.";
                    echo json_encode($return_array);
                    exit;
                }

                $article = $this->board_mdl->row_article_by_mb_unq($article_movie['mb_table_code'], $request['mb_unq']);

            }else{
                $article = $this->board_mdl->row_article_by_mb_unq($request['table_code'], $request['mb_unq']);
            }

            if($article)
            {
                //검색테이블 업데이트(조회수 증감에 따른)
                $search_params = array(
                    'mb_unq' => $request['mb_unq'],
                    'hit'    => $article['mb_hit'],
                    'recom'  => $article['mb_recom']
                );
                $this->board_mdl->update_search_boards($request['table_code'], $search_params);
            }
        }

        
        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        $clip_code = NULL;
        $clip_url = NULL;
        $mb_unq = NULL;
        $file = NULL;
        $file2 = NULL;

        if($request['table_code'] == 'express')
        {
            $clip_code = "9001";
            $article['mb_content'] = $article['mb_title'];
            $mb_unq = $article['mb_uid'];
            $clip_url = "/express.view.php?no=".$mb_unq."#ank";
        }
        else if($request['table_code'] == 'correction')
        {
            $clip_code = "9004";
            $mb_unq = $article['mb_w_id'];
            $file = $article['mb_tutor_upfile'];
            $file2 = $article['mb_student_upfile'];
            $clip_url = "/correction.view.php?no=".$mb_unq."#ank";
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            $clip_code = "9002";
            $mb_unq = $article['mb_c_uid'];
            $file = $article['mb_filename'];
            $file2 = $article['mb_filename2'];
            $clip_url = "/dictation.view.php?no=".$mb_unq."#ank";
            
            if(strpos($article['mb_title'],'--') !== false)
            {
                $subject = explode('--',$article['mb_title']);
                $article['mb_title'] = number_format($subject[2]+1).'번째 얼철딕';
            }
            
        }
        else
        {
            // 커리큘럼 상세보기 동영상강좌들은 테이블코드가 다 달라서 다시 세팅.
            if($request['clip_type'] == 'movie'){
                $clip_code = $article['mb_table_code'];
            }else{
                $clip_code = $request['table_code'];
            }
            $mb_unq = $article['mb_unq'];
            $file = $article['mb_filename'];
            $clip_url = '/board_view.php?table_code='.$clip_code.'&mb_unq='.$mb_unq;
        }
        
        if(array_key_exists('mb_thumb',$article)) 
        {
            $article['mb_content'] = Thumbnail::replace_image_thumbnail($article['mb_content'],$article['mb_thumb'],'editor','pc');
        }

        $clip = array(
            'mb_unq' => $mb_unq,
            'table_code' => $clip_code,
            'wiz_id' => $article['mb_wiz_id'],
            'title' => $article['mb_title'],
            'content' => $article['mb_content'],
            'file' => $file,
            'file2' => $file2,
            'input_txt' => $request['msg'],
            'url' => $clip_url,
            'reg_wiz_id' => $request['wiz_id'],
            'regdate' => date("Y-m-d H:i:s")
        );


        $result = $this->board_mdl->clip_article($clip);

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
            $return_array['msg'] = "게시글을 스크랩했습니다.";
            echo json_encode($return_array);
            exit;
        }

    }

    // 강사&교재 리스트
    public function select_ditaction()
    {
        $return_array = array();

        $this->load->model('tutor_mdl');
        $this->load->model('curriculum_mdl');
        
        $book = [];

        $where = " WHERE mc.mc_key IS NOT NULL AND mc.use_yn = 'Y'";
        $order = ' ORDER BY mc.sorting';

        $book = $this->curriculum_mdl->list_curriculum($where, $order);

        for($i=0; $i<count($book); $i++)
        {
            $books = $this->curriculum_mdl->list_book_by_mc_key($book[$i]['mc_mc_key']);
            $book[$i]['book'] = $books;
        }

        $tutor = $this->tutor_mdl->list_select_ditaction();

        if(!$tutor && !$book)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "목록조회성공";
            $return_array['data']['tutor'] = $tutor;
            $return_array['data']['book'] = $book;
            echo json_encode($return_array);
            exit;
        }

    }

    // 첨삭게시판에서 강사목록
    public function select_correction()
    {
        $return_array = array();

        $request = array(
            "type" => trim($this->input->post('type')) ? trim($this->input->post('type')):'write',
        );

        $this->load->model('tutor_mdl');
        $this->load->model('board_mdl');
        
        //글쓰기 시 선택가능한 강사목록
        if($request['type'] =='write')
        {
            $recent = null;
            $wiz_member = base_get_wiz_member();
            if($wiz_member)
            {
                $where = ' WHERE mb.uid='.$wiz_member['wm_uid'].' AND (uid,w_regdate) IN( SELECT uid,MAX(w_regdate) AS regdate FROM wiz_correct WHERE uid ='.$wiz_member['wm_uid'].' AND tu_uid!="" GROUP BY tu_uid ORDER BY regdate )';
                $order = ' ORDER BY mb.w_regdate DESC';
                $recent = $this->board_mdl->list_board_wiz_correct('', $where, $order, ' LIMIT 3');
            }

            $where = " AND wt.correct_yn IN( 'Y','C','M') ";
            $tutor = $this->tutor_mdl->list_select_correction('',$where);

            if($recent)
            {
                $recent_arrange = [];
                foreach($recent as $row)
                {
                    $recent_arrange[] = $row['mb_tu_uid'];
                }
                $imsi = [];
                foreach($tutor as $key=>$row)
                {
                    if(in_array($row['wt_tu_uid'],$recent_arrange))
                    {
                        $row['recent'] = '1';
                        $imsi[] = $row;
                        unset($tutor[$key]);
                    }
                }

                $tutor = array_merge($imsi,$tutor);
            }
        }
        else
        {
            // 첨삭리스트에서 검색가능한 강사목록
            $join = ' INNER JOIN wiz_correct wc ON wt.tu_uid = wc.tu_uid ';
            $where = " AND wt.tu_name NOT like 'webex%' group by wt.tu_uid ";
            $tutor = $this->tutor_mdl->list_select_correction($join,$where);
        }

        if(!$tutor)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "목록조회성공";
            $return_array['data']['tutor'] = $tutor;
            echo json_encode($return_array);
            exit;
        }

    }

    /*
        일반게시판 댓글등록
    */
    public function comment()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "mb_unq" => trim($this->input->post('mb_unq')),
            "co_fid" => trim($this->input->post('co_fid')),
            "co_thread" => trim($this->input->post('co_thread')),
            "comment" => $this->input->post('comment'),
            "notice_yn" => ($this->input->post('notice_yn')) ? trim(strtoupper($this->input->post('notice_yn'))) : "N",
            "mob" =>  trim(strtoupper($this->input->post('mob'))) ? trim(strtoupper($this->input->post('mob'))) : "N",
            "concordance_rate" => $this->input->post('concordance_rate'),
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
                $return_array['data']['err_msg'] = "글쓰기 권한이 없습니다. 고객센터 실시간요청게시판으로 문의하세요.";
                echo json_encode($return_array);
                exit;
            }
        }
        
        /* 회원 보유포인트 */
        $wm_point = $wiz_member['wm_point'];
        /*
            게시판지기 체크 
            - 댓글공지
        */
        $is_assistant = "N";

        if(false !== stripos($wiz_member['wm_assistant_code'], "*comment*") || false !== stripos($wiz_member['wm_assistant_code'], "*1127*"))
        {
            $is_assistant = "Y";
        } 


        $this->load->model('board_mdl');
    
        $board_config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);
        $article = $this->board_mdl->row_article_by_mb_unq($request['table_code'], $request['mb_unq']);

        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        //검색테이블 업데이트(조회수 증감에 따른)
        $search_params = array(
            'mb_unq' => $request['mb_unq'],
            'hit'    => $article['mb_hit'],
            'recom'  => $article['mb_recom']
        );
        $this->board_mdl->update_search_boards($request['table_code'], $search_params);
    

        // 댓글쓰기 허용한 게시판인지 체크
        if($board_config['mbn_comment_yn'] != "y")
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0353";
            $return_array['data']['err_msg'] = "댓글쓰기가 불가능한 게시판입니다.";
            echo json_encode($return_array);
            exit;
        }
    
        // 수업중 회원만 댓글쓰기 허용 
        if($board_config['mbn_comment_yn_inclass'] == "Y")
        {
            $where = "WHERE (wl.wiz_id = '".$wiz_member['wm_wiz_id']."' || wl.student_uid LIKE '%,".$wiz_member['wm_uid'].",%') AND wl.lesson_state='in class'";
            $inclass = $this->member_mdl->checked_inclass($where);
        
            if($inclass['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0351";
                $return_array['data']['err_msg'] = "수업 중인 회원만 댓글쓰기가 가능한 게시판입니다.";
                echo json_encode($return_array);
                exit;
            }
        }

        /* 댓글공지 권한 체크 */
        if($request['notice_yn'] == "Y" &&  $is_assistant == "N")
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0308";
            $return_array['data']['err_msg'] = "댓글공지 권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $accuracy = null;
        if($request['table_code'] =='1144')
        {
            $check = $this->board_mdl->row_article_comment_by_mbunq_writer($request['mb_unq'],$request['wiz_id']);
            if($check)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0308";
                $return_array['data']['err_msg'] = "이미 AI딕테이션에 참여한 글 입니다.";
                echo json_encode($return_array);
                exit;
            }

            //similar_text(preg_replace('/\s+/','',(strip_tags($article['mb_content']))), preg_replace('/\s+/','',(strip_tags($request['comment']))), $accuracy);
            //$accuracy = (int)$accuracy;
        }
        

        $co_fid = NULL;
        $co_thread = NULL;
        $comment_child = NULL;

        if($request['co_fid'])
        {
            /* 대댓글일시 */
            $co_fid = $request['co_fid'];
            $co_thread = $request['co_thread'];;
            $comment_child = $this->board_mdl->comment_child($co_fid, $co_thread);
        
            if($comment_child) 
            {
                /* 대댓글일시 2번째부터 */
                $thread_head = substr($comment_child['mbc_co_thread'], 0, -1);
                $thread_foot = ++$comment_child['mbc_right_co_thread'];
                $co_thread = $thread_head . $thread_foot;
            } 
            else 
            {
                /* 대댓글일시 1번째 */
                $co_thread = "AA";
            }

        }
        else
        {
            /* 댓글일시 */
            $co_thread = "A";
        }

    
        $comment = array(
            'mb_unq' => $request['mb_unq'],
            'writer_id' => $wiz_member['wm_wiz_id'],
            'writer_name' => $wiz_member['wm_name'],
            'writer_ename' => $wiz_member['wm_ename'],
            'writer_nickname' => $wiz_member['wm_nickname'],
            'comment' => $request['comment'],
            'table_code' => $request['table_code'],
            'notice_yn' => ($request['notice_yn'] == "Y") ? 1 : 2,
            'mob' => $request['mob'],
            'co_fid' => $co_fid,
            'co_thread' => $co_thread,
            'regdate' => date("Y-m-d H:i:s"),
            'memo' => $accuracy,
        );

        $comment_result = $this->board_mdl->insert_comment($comment);
      

        if($comment_result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        /* 
            댓글 작성시 포인트 지급기준
            
            # 공지댓글
            오늘의영어한마디 : 500
            영문법아작내기 : 200
            그외 : 100
            지급제한 : 같은게시물 중복댓글 안됨

            # 일반댓글 
            민트영어사용설명서 
            -지급포인트 : 5,000
            -지급제한 : 처음1회만  
            오늘의영어한마디
            -지급포인트 : 500
            -지급제한 : 하루1회
            그외
            -지급포인트 : 200
            -지급제한 : 하루5회 , 같은게시물 중복댓글 안됨
        */

        
        $wp_kind = "";
        $point_comment = NULL;
        $checked_point_comment = NULL;

        $this->load->model('point_mdl');

        // master로 조회해줘야하나?
        if(($request['table_code'] == '1132' || $request['table_code'] == "1110" || $request['table_code']  == "1127" || $request['table_code'] =='1129') && $article['mb_noticeYn'] == 'N')
        {
            
            $wp_kind = "Z";

            $date = date("Y-m-d");
            $s_date = $date." 00:00:00";
            $e_date = $date." 23:59:59";

            if($request['table_code'] == '1132')
            {
                // 오늘의 영어한마디 500포인트
                //오늘의 영어한마디 게시물당 포인트 지급이 아닌 게시판 당 1일 1회
                $point_comment = common_point_standard('today_a_word_comment');
                $checked_point_comment = $this->point_mdl->checked_point_today_comment_by_table_code($request['table_code'], $wiz_member['wm_wiz_id'], $wiz_member['wm_uid']);
                if(!$checked_point_comment)
                {
                    $checked_point_comment = $this->point_mdl->checked_point_comment_by_mb_unq($request['mb_unq'], $wiz_member['wm_wiz_id'], "z", $wiz_member['wm_uid']);
                }
            
            }
            else if($request['table_code'] == "1110")
            {
                //영문법아작내기 200포인트
                $point_comment = common_point_standard('azak_comment');
                // 하루동안 받은 댓글 포인트 지급 횟수
                $count_point_comment = $this->point_mdl->count_point_comment_limit_day_by_kind_table_code($wiz_member['wm_uid'], "z", $request['table_code'], $s_date, $e_date);

                if($count_point_comment['cnt'] >= 5)
                {   
                    $checked_point_comment = true;  // 하루 댓글포인트 5회 초과분 부터 포인트 지급안함
                }
                else
                {
                    // 해당 게시물에 댓글 포인트 지급이력 체크. 5회 초과 안되었어도 해당게시물에 댓글포인트 지급했으면 이후 지급 안함
                    $checked_point_comment = $this->point_mdl->checked_point_comment_by_mb_unq($request['mb_unq'], $wiz_member['wm_wiz_id'], "z",$wiz_member['wm_uid']);
                }

            }
            else if($request['table_code'] == "1127")
            {
                // 도전 일일명작문

                $point_comment = common_point_standard('etc_comment');
                // 하루동안 받은 댓글 포인트 지급 횟수
                $count_point_comment = $this->point_mdl->count_point_comment_limit_day_by_kind_table_code($wiz_member['wm_uid'], "z", $request['table_code'], $s_date, $e_date);

                if($count_point_comment['cnt'] >= 5)
                {   
                    $checked_point_comment = true;  // 하루 댓글포인트 5회 초과분 부터 포인트 지급안함
                }
                else
                {
                    // 해당 게시물에 댓글 포인트 지급이력 체크. 5회 초과 안되었어도 해당게시물에 댓글포인트 지급했으면 이후 지급 안함
                    $checked_point_comment = $this->point_mdl->checked_point_comment_by_mb_unq($request['mb_unq'], $wiz_member['wm_wiz_id'], "z",$wiz_member['wm_uid']);
                }
            }
            else
            {
                // 사진묘사 100포인트
                $point_comment = common_point_standard('etc_comment');
                $checked_point_comment = $this->point_mdl->checked_point_comment_by_mb_unq($request['mb_unq'], $wiz_member['wm_wiz_id'], "z", $wiz_member['wm_uid']);
            }
            
        }
        // 민트사용설명서
        elseif($request['table_code'] == '1135')
        {
            $wp_kind = "l";
            $point_comment = common_point_standard('mint_manual_comment');
            $checked_point_comment = $this->point_mdl->checked_point_comment_limit_one_by_table_code_kind($wiz_member['wm_uid'], $request['table_code'], "l");
        }
        // AI딕테이션
        elseif($request['table_code'] == '1144')
        {
            if((int)$request['concordance_rate'] >= 50)
            {
                $date = date("Y-m-d");
                $s_date = $date." 00:00:00";
                $e_date = $date." 23:59:59";
    
                $wp_kind = "ai";
                $point_comment = 100;
    
                // 하루동안 받은 댓글 포인트 지급 횟수
                $count_point_comment = $this->point_mdl->count_point_comment_limit_day_by_kind_table_code($wiz_member['wm_uid'], "ai", $request['table_code'], $s_date, $e_date);
    
                if($count_point_comment['cnt'] >= 5)
                {
                    $checked_point_comment = true;  // 하루 댓글포인트 5회 초과분 부터 포인트 지급안함
                }
            }
            else
            {
                $checked_point_comment = true;
            }
            
        }
        else
        {
            $date = date("Y-m-d");
            $s_date = $date." 00:00:00";
            $e_date = $date." 23:59:59";

            $wp_kind = "l";
            $point_comment = common_point_standard('common_comment');
            // 하루동안 받은 댓글 포인트 지급 횟수, 
            $count_point_comment = $this->point_mdl->count_point_comment_limit_day_by_kind_except_1127($wiz_member['wm_uid'], "l", $s_date, $e_date);

            if($count_point_comment['cnt'] >= 5)
            {   
                $checked_point_comment = true;  // 하루 댓글포인트 5회 초과분 부터 포인트 지급안함
            }
            else
            {
                // 해당 게시물에 댓글 포인트 지급이력 체크. 5회 초과 안되었어도 해당게시물에 댓글포인트 지급했으면 이후 지급 안함
                $checked_point_comment = $this->point_mdl->checked_point_comment_by_mb_unq($request['mb_unq'], $wiz_member['wm_wiz_id'], "l",$wiz_member['wm_uid']);
            }

        }
        

        $pt_name = NULL;
            
        /* 
            댓글 작성 포인트 지급
            checked_point_comment 
            - NULL : 지급이력없음  
            - NULL이 아닌경우 : 지급이력있음
        */
        if(!$checked_point_comment)
        {
            $pt_name = $board_config['mbn_table_name']." 게시판 댓글 이벤트로 ".number_format($point_comment)."포인트가 적립되었습니다.";

            // showYn 적립여부 (y:적립완료, n:적립예정(사용자페이지에 출력안함), d:회수,삭제(포인트 회수시에 d로 업데이트하고 있음))
            // wp_kind l:민트영어사용설명서 , 일반댓글 m:오늘의영어한마디, z:공지댓글  

            $point = array(
                'uid' => $wiz_member['wm_uid'],
                'name' => $wiz_member['wm_name'],
                'point' => $point_comment,
                'pt_name'=> $pt_name, 
                'kind'=> $wp_kind, 
                'b_kind'=> 'boards',
                'table_code'=> $request['table_code'],
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
        }

        /* 댓글 입력 알림 비동기처리 */
        notify_comment_insert($request['table_code'], $request['mb_unq'], $comment_result, $wiz_member['wm_uid'], $co_fid);
        // $list_count_article_comment = $this->board_mdl->list_count_article_comment($request['mb_unq']);
    

        /* 비동기 mint_total_rows 갱신 */

        if((1100 <= $request['table_code'] && $request['table_code'] <= 1199) OR ( 1300 <= $request['table_code'] && $request['table_code'] <= 1399))
        {
            board_comment_list_count_update();
        }

        //퀘스트
        $q_idx = '14_146'; //일반 게시판 댓글쓰기
        switch($request['table_code']){
            case '1127' : $q_idx .= '_42';break; // 일일영작문
            case '1132' : $q_idx .= '_43';break; // 오늘의영어 한마디
            case '1102' : $q_idx .= '_72';break; // 영어해석커뮤니티
            case '1120' : $q_idx .= '_73';break; // 영어문법질문&답변
            case '1138' : $q_idx .= '_74';break; // 딕테이션 해결사
            case '1337' : $q_idx .= '_93';break; // 영어고민&권태기상담
        }
        MintQuest::request_batch_quest($q_idx, $comment_result.MintQuest::make_quest_subfix($request['table_code'], 'comm'));

        $return_array['res_code'] = '0000';
        $return_array['msg'] = ($pt_name) ? $pt_name : "댓글이 등록되었습니다.";
        $return_array['data']['wm_point'] = $wm_point;
        // $return_array['data']['total_cnt'] = $list_count_article_comment['cnt'];
        echo json_encode($return_array);
        exit;


    }

    /*
        이런표현어떻게
        얼굴철판딕테이션
        # 영어첨삭게시판 - 댓글없음
    */
    public function comment_special()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "mb_unq" => trim($this->input->post('mb_unq')),
            "comment" => $this->input->post('comment'),
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

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('member_mdl');
        
        /* 회원 보유포인트 */
        $wm_point = $wiz_member['wm_point'];

    
        $article = NULL;

        $this->load->model('board_mdl');
      
        if($request['table_code'] == 'express')
        {
            /* 이련표현어떻게 답변글쓰기 권한체크 */
            /* if(false === stripos($wiz_member['wm_assistant_code'], "*express*"))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0308";
                $return_array['data']['err_msg'] = "답변글 쓰기 권한이 없습니다.";
                echo json_encode($return_array);
                exit;
            }  */
            
         
            $article = $this->board_mdl->row_article_express_by_mb_uid($request['mb_unq']);
         
           
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            
            $article = $this->board_mdl->row_article_cafeboard_by_c_uid($request['mb_unq']); 
           
        }
        
        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

      
        $comment = NULL;
        $comment_result = NULL;

        if($request['table_code'] == 'express')
        {

            $comment = array(
                'e_id' => $request['mb_unq'],
                'comment' => $request['comment'],
                'wiz_id' => $wiz_member['wm_wiz_id'],
                'c_name' => $wiz_member['wm_name'],
                'regdate' => date("Y-m-d H:i:s"),
            );

            $comment_result = $this->board_mdl->insert_comment_express($comment);
            $list_count_article_comment = $this->board_mdl->list_count_mint_express_com($request['mb_unq']);
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {  
            
            $comment = array(
                'c_uid' => $request['mb_unq'],
                'comment' => $request['comment'],
                'writer_id' => $wiz_member['wm_wiz_id'],
                'writer_name' => $wiz_member['wm_name'],
                'regdate' => date("Y-m-d H:i:s"),
            );

            $comment_result = $this->board_mdl->insert_comment_cafeboard($comment);
            $list_count_article_comment = $this->board_mdl->list_count_mint_cafeboard_com($request['mb_unq']);
        }
        
        /* 댓글 입력 알림 비동기처리 */
        notify_comment_special_insert($request['table_code'], $request['mb_unq'], $comment_result, $wiz_member['wm_uid']);
       
        if($comment_result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            //퀘스트
            $q_idx = '14_146'; //특수 게시판 댓글쓰기
            switch($request['table_code']){
                case 'express' : $q_idx .= '_71';break; // 이런표현어떻게
            }
            MintQuest::request_batch_quest($q_idx, $comment_result.MintQuest::make_quest_subfix($request['table_code'], 'comm'));
            
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "댓글이 등록되었습니다."; 
            $return_array['data']['wm_point'] = $wm_point;
            $return_array['data']['insert_id'] = $comment_result;
            $return_array['data']['regdate'] = $comment['regdate'];
            $return_array['data']['total_cnt'] = $list_count_article_comment['cnt'];
            echo json_encode($return_array);
            exit;
        }
    }

    /*
        댓글수정
        - 대댓글 있을시 수정 불가
        - 추천 있을시 수정 불가
    */

    public function modify_comment_special()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "mb_unq" => trim($this->input->post('mb_unq')),
            "co_unq" => trim($this->input->post('co_unq')),
            "comment" => $this->input->post('comment'),
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
        /* 회원 보유포인트 */
        $wm_point = $wiz_member['wm_point'];

        $board_config = NULL;
        $article = NULL;
        $tmp_article_wm_uid = NULL;
        $article_wm_uid = NULL;
        $notify_table_code = NULL;

        $this->load->model('board_mdl');
        
        if($request['table_code'] == 'express')
        {
            /* 이련표현어떻게 답변글쓰기 권한체크 */
            /* if(false === stripos($wiz_member['wm_assistant_code'], "*express*"))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0308";
                $return_array['data']['err_msg'] = "답변 글 쓰기 권한이 없습니다.";
                echo json_encode($return_array);
                exit;
            }  */
            
            $board_config = [];
            $board_config['mbn_table_name'] = "이런표현어떻게";
            $article = $this->board_mdl->row_article_express_by_mb_uid($request['mb_unq']);
            $notify_table_code = 'express.view';
            
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            $board_config = [];
            $board_config['mbn_table_name'] = "얼굴철판딕테이션";
            $article = $this->board_mdl->row_article_cafeboard_by_c_uid($request['mb_unq']); 
            $notify_table_code = 'dictation.view';
        }
        
        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        $article_comment = NULL;
        if($request['table_code'] == 'express')
        {
            $article_comment = $this->board_mdl->row_article_comment_express_by_uid($request['co_unq']);
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            $article_comment = $this->board_mdl->row_article_comment_cafeboard_by_unq($request['co_unq']);
        }

        if(!$article_comment)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0309";
            $return_array['data']['err_msg'] = ($request['table_code'] == 'express') ? "답변 글이 존재하지 않습니다." : "댓글이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article_comment['mbc_wiz_id'] != $request['wiz_id'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0310";
            $return_array['data']['err_msg'] = ($request['table_code'] == 'express') ? "답변 글 수정 권한이 없습니다.": "댓글 수정 권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article_comment['mbc_recom'] > 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0311";
            $return_array['data']['err_msg'] = ($request['table_code'] == 'express') ? "추천이 있는 답변 글은 수정할 수 없습니다." : "추천이 있는 댓글은 수정할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $tmp_article_wm_uid = $this->member_mdl->get_wm_uid_by_wiz_id($article['mb_wiz_id']);
        $article_wm_uid = $tmp_article_wm_uid['wm_uid'];

    
        $comment = NULL;
        $comment_result = NULL;

        if($request['table_code'] == 'express')
        {

            $comment = array(
                'comment' => $request['comment'],
                'c_name' => $wiz_member['wm_name'],
            );

            $comment_result = $this->board_mdl->update_comment_express($comment, $request['co_unq'], $wiz_member['wm_wiz_id']);

        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {  
            
            $comment = array(
                'comment' => $request['comment'],
                'writer_name' => $wiz_member['wm_name'],
            );

            $comment_result = $this->board_mdl->update_comment_cafeboard($comment, $request['co_unq'], $wiz_member['wm_wiz_id']);

        }
        
        
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
            $return_array['msg'] = ($request['table_code'] == 'express') ? "답변 글이 수정되었습니다." : "댓글이 수정되었습니다."; 
            $return_array['data']['wm_point'] = $wm_point;
            echo json_encode($return_array);
            exit;
        }
        
    }

    /*
        댓글 삭제
        - 대댓글 있을시 수정 불가
        - 추천 있을시 수정 불가
    */
    public function delete_comment_special()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "mb_unq" => trim($this->input->post('mb_unq')),
            "co_unq" => trim($this->input->post('co_unq')),
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
        /* 회원 보유포인트 */
        $wm_point = $wiz_member['wm_point'];

        $board_config = NULL;
        $article = NULL;
        $tmp_article_wm_uid = NULL;
        $article_wm_uid = NULL;
        $notify_table_code = NULL;

        $this->load->model('board_mdl');
    
        if($request['table_code'] == 'express')
        {
            /* 이련표현어떻게 답변글쓰기 권한체크 */
            /* if(false === stripos($wiz_member['wm_assistant_code'], "*express*"))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0308";
                $return_array['data']['err_msg'] = "답변 글 삭제 권한이 없습니다.";
                echo json_encode($return_array);
                exit;
            }  */
            
            $board_config = [];
            $board_config['mbn_table_name'] = "이런표현어떻게";
            $article = $this->board_mdl->row_article_express_by_mb_uid($request['mb_unq']);
            $notify_table_code = 'express.view';
           
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            $board_config = [];
            $board_config['mbn_table_name'] = "얼굴철판딕테이션";
            $article = $this->board_mdl->row_article_cafeboard_by_c_uid($request['mb_unq']); 
            $notify_table_code = 'dictation.view';
        }
        
        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        $article_comment = NULL;
        if($request['table_code'] == 'express')
        {
            $article_comment = $this->board_mdl->row_article_comment_express_by_uid($request['co_unq']);
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        {
            $article_comment = $this->board_mdl->row_article_comment_cafeboard_by_unq($request['co_unq']);
        }

        if(!$article_comment)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0309";
            $return_array['data']['err_msg'] = ($request['table_code'] == 'express') ? "답변 글이 존재하지 않습니다." : "댓글이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article_comment['mbc_wiz_id'] != $request['wiz_id'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0310";
            $return_array['data']['err_msg'] = ($request['table_code'] == 'express') ? "답변 글 삭제 권한이 없습니다.": "댓글 삭제 권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article_comment['mbc_recom'] > 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0311";
            $return_array['data']['err_msg'] = ($request['table_code'] == 'express') ? "추천이 있는 답변 글은 삭제할 수 없습니다." : "추천이 있는 댓글은 삭제할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $tmp_article_wm_uid = $this->member_mdl->get_wm_uid_by_wiz_id($article['mb_wiz_id']);
        $article_wm_uid = $tmp_article_wm_uid['wm_uid'];

    
        $comment = NULL;
        $comment_result = NULL;

        if($request['table_code'] == 'express')
        {
            $comment_result = $this->board_mdl->delete_comment_express($request['co_unq'] , $wiz_member['wm_wiz_id']);
            $list_count_article_comment = $this->board_mdl->list_count_mint_express_com($request['mb_unq']);
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')
        { 
            $comment_result = $this->board_mdl->delete_comment_cafeboard($request['co_unq'], $wiz_member['wm_wiz_id']);
            $list_count_article_comment = $this->board_mdl->list_count_mint_cafeboard_com($request['mb_unq']);
        }
        
       
        if(!$comment_result)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $this->load->model('notify_mdl');
            //$dealer = $this->notify_mdl->get_dealer_sms_by_wm_uid($wiz_member['wm_uid']);

            /* 게시글 작성자 알림*/
            $notify = array(
                'removed' => 1,
                'disabled' => 1,
                'view' => 1
            );

            $where = array(
                'code' => 102,
                'uid' => $article_wm_uid,
                'table_code' => $notify_table_code,
                'mb_unq' => $request['mb_unq'],
                'co_unq' => $request['co_unq'],
            );

            $notify_result = $this->notify_mdl->disabled_notify($notify, $where);

            if($notify_result < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }

            //퀘스트취소
            $q_idx = '14_146'; //특수 게시판 댓글쓰기
            switch($request['table_code']){
                case 'express' : $q_idx .= '_71';break; // 이런표현어떻게
            }
            MintQuest::request_batch_quest_decrement($q_idx, $request['co_unq'].MintQuest::make_quest_subfix($request['table_code'], 'comm'));
            
            $return_array['res_code'] = '0000';
            $return_array['msg'] = ($request['table_code'] == 'express') ? "답변 글이 삭제되었습니다." : "댓글이 삭제되었습니다."; 
            $return_array['data']['wm_point'] = $wm_point;
            $return_array['data']['total_cnt'] = $list_count_article_comment['cnt'];
            echo json_encode($return_array);
            exit;
        }
        
    }

   
    /*
        일반게시판 댓글수정
        - 대댓글 있을시 수정 불가
        - 추천 있을시 수정 불가
    */

    public function modify_comment()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "mb_unq" => trim($this->input->post('mb_unq')),
            "co_unq" => trim($this->input->post('co_unq')),
            "comment" => $this->input->post('comment'),
            "notice_yn" => ($this->input->post('notice_yn')) ? trim(strtoupper($this->input->post('notice_yn'))) : "N",
            "mob" =>  trim(strtoupper($this->input->post('mob'))) ? trim(strtoupper($this->input->post('mob'))) : "N",
            "concordance_rate" => $this->input->post('concordance_rate'),
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
                $return_array['data']['err_msg'] = "글쓰기 권한이 없습니다. 고객센터 실시간요청게시판으로 문의하세요.";
                echo json_encode($return_array);
                exit;
            }
        }

        /* 회원 보유포인트 */
        $wm_point = $wiz_member['wm_point'];
        /*
            게시판지기 체크 
            - 댓글공지
        */
        $is_assistant = "N";
        if(false !== stripos($wiz_member['wm_assistant_code'], "*comment*"))
        {
            $is_assistant = "Y";
        } 

        $board_config = NULL;
        $article = NULL;
        $tmp_article_wm_uid = NULL;
        $article_wm_uid = NULL;
        $notify_table_code = NULL;

        $this->load->model('board_mdl');
      
        $board_config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);
        $article = $this->board_mdl->row_article_by_mb_unq($request['table_code'], $request['mb_unq']);
        $notify_table_code = $request['table_code'];

        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        //검색테이블 업데이트(조회수 증감에 따른)
        $search_params = array(
            'mb_unq' => $request['mb_unq'],
            'hit'    => $article['mb_hit'],
            'recom'  => $article['mb_recom']
        );
        $this->board_mdl->update_search_boards($request['table_code'], $search_params);

        $tmp_article_wm_uid = $this->member_mdl->get_wm_uid_by_wiz_id($article['mb_wiz_id']);
        $article_wm_uid = $tmp_article_wm_uid['wm_uid'];

        /* 댓글공지 권한 체크 */
        if($request['notice_yn'] == "Y" &&  $is_assistant == "N")
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0308";
            $return_array['data']['err_msg'] = "댓글공지 권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $article_comment = $this->board_mdl->row_article_comment_by_co_unq($request['co_unq']);

        if(!$article_comment)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0309";
            $return_array['data']['err_msg'] =  "댓글이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article_comment['mbc_wiz_id'] != $request['wiz_id'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0310";
            $return_array['data']['err_msg'] = "댓글 수정 권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article_comment['mbc_recom'] > 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0311";
            $return_array['data']['err_msg'] = "추천이 있는 댓글은 수정할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $checked_comment_co_thread = $this->board_mdl->checked_comment_co_thread($article_comment['mb_unq'], $article_comment['mbc_co_fid'], $article_comment['mbc_co_thread']);
        
        if($checked_comment_co_thread)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0312";
            $return_array['data']['err_msg'] = "대댓글이 있는 댓글은 수정할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $accuracy = null;
        

        $comment = array(
            'writer_name' => $wiz_member['wm_name'],
            'writer_ename' => $wiz_member['wm_ename'],
            'writer_nickname' => $wiz_member['wm_nickname'],
            'comment' => $request['comment'],
            'notice_yn' => ($request['notice_yn'] == "Y") ? 1 : 2,
            'mob' => $request['mob'],
            'regdate' => date("Y-m-d H:i:s"),
            'memo' => $accuracy,
        );

        $comment_result = $this->board_mdl->update_comment($comment, $request['co_unq'], $wiz_member['wm_wiz_id']);
      

        if(!$comment_result)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        
        $pt_name= '';
        //일치율 50% 이상일 시 포인트 지급
        if($request['table_code'] =='1144' && (int)$request['concordance_rate'] >= 50)
        {
            $this->load->model('point_mdl');
            $date = date("Y-m-d");
            $s_date = $date." 00:00:00";
            $e_date = $date." 23:59:59";

            $wp_kind = "ai";
            $point_comment = 100;

            // 해당 게시물에 댓글 포인트 지급이력 체크.
            $checked_point_comment = $this->point_mdl->checked_point_comment_by_mb_unq($request['mb_unq'], $wiz_member['wm_wiz_id'], "ai",$wiz_member['wm_uid']);

            if(!$checked_point_comment)
            {
                // 하루동안 받은 댓글 포인트 지급 횟수
                $count_point_comment = $this->point_mdl->count_point_comment_limit_day_by_kind_table_code($wiz_member['wm_uid'], "ai", $request['table_code'], $s_date, $e_date);
                
                if($count_point_comment['cnt'] >= 5)
                {
                    $checked_point_comment = true;  // 하루 댓글포인트 5회 초과분 부터 포인트 지급안함
                }
            }
            
            if(!$checked_point_comment)
            {
                $pt_name = $board_config['mbn_table_name']." 게시판 댓글 이벤트로 ".number_format($point_comment)."포인트가 적립되었습니다.";

                // showYn 적립여부 (y:적립완료, n:적립예정(사용자페이지에 출력안함), d:회수,삭제(포인트 회수시에 d로 업데이트하고 있음))
                // wp_kind l:민트영어사용설명서 , 일반댓글 m:오늘의영어한마디, z:공지댓글  
    
                $point = array(
                    'uid' => $wiz_member['wm_uid'],
                    'name' => $wiz_member['wm_name'],
                    'point' => $point_comment,
                    'pt_name'=> $pt_name, 
                    'kind'=> $wp_kind, 
                    'b_kind'=> 'boards',
                    'table_code'=> $request['table_code'],
                    'co_unq'=> $request['co_unq'], 
                    'showYn'=> 'y',
                    'regdate' => date("Y-m-d H:i:s")
                );
    
                /* 포인트 내역 입력 및 포인트 추가 */
                $point_result = $this->point_mdl->set_wiz_point($point);
    
                $tmp_point = $this->member_mdl->get_wm_point_by_wiz_id($wiz_member['wm_wiz_id']);
                $wm_point =  $tmp_point['wm_point'];
            }
            
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "댓글이 수정되었습니다.".$pt_name;
        $return_array['data']['wm_point'] = $wm_point;
        echo json_encode($return_array);
        exit;
        
    }


    /*
        일반게시판 댓글삭제
        - 대댓글 있을시 삭제 불가
        - 추천 있을시 삭제 불가
    */

    public function delete_comment()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "mb_unq" => trim($this->input->post('mb_unq')),
            "co_unq" => trim($this->input->post('co_unq')),
            "comment" => $this->input->post('comment'),
            "notice_yn" => ($this->input->post('notice_yn')) ? trim(strtoupper($this->input->post('notice_yn'))) : "N",
            "mob" =>  trim(strtoupper($this->input->post('mob'))),
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
        /* 회원 보유포인트 */
        $wm_point = $wiz_member['wm_point'];
        /*
            게시판지기 체크 
            - 댓글공지
        */
        $is_assistant = "N";
        if(false !== stripos($wiz_member['wm_assistant_code'], "*comment*"))
        {
            $is_assistant = "Y";
        } 

        $board_config = NULL;
        $article = NULL;
        $tmp_article_wm_uid = NULL;
        $article_wm_uid = NULL;
        $notify_table_code = NULL;

        $this->load->model('board_mdl');
    
        $board_config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);
        $article = $this->board_mdl->row_article_by_mb_unq($request['table_code'], $request['mb_unq']);
        $notify_table_code = $request['table_code'];

        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        //검색테이블 업데이트(조회수 증감에 따른)
        $search_params = array(
            'mb_unq' => $request['mb_unq'],
            'hit'    => $article['mb_hit'],
            'recom'  => $article['mb_recom']
        );
        $this->board_mdl->update_search_boards($request['table_code'], $search_params);
        
        $tmp_article_wm_uid = $this->member_mdl->get_wm_uid_by_wiz_id($article['mb_wiz_id']);
        $article_wm_uid = $tmp_article_wm_uid['wm_uid'];

        
        $article_comment = $this->board_mdl->row_article_comment_by_co_unq($request['co_unq']);

        if(!$article_comment)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0309";
            $return_array['data']['err_msg'] =  "댓글이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article_comment['mbc_wiz_id'] != $request['wiz_id'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0310";
            $return_array['data']['err_msg'] = "댓글 삭제 권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article_comment['mbc_recom'] > 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0311";
            $return_array['data']['err_msg'] = "추천이 있는 댓글은 삭제할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $checked_comment_co_thread = $this->board_mdl->checked_comment_co_thread($article_comment['mb_unq'], $article_comment['mbc_co_fid'], $article_comment['mbc_co_thread']);
        
        if($checked_comment_co_thread)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0312";
            $return_array['data']['err_msg'] = "대댓글이 있는 댓글은 삭제할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        $comment_result = $this->board_mdl->delete_comment($article_comment['mb_unq'] , $request['co_unq'], $wiz_member['wm_wiz_id'], $wiz_member['wm_uid']);
        $list_count_article_comment = $this->board_mdl->update_count_article_comment($article_comment['mb_unq']);
        
        if($comment_result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $this->load->model('notify_mdl');
            //$dealer = $this->notify_mdl->get_dealer_sms_by_wm_uid($wiz_member['wm_uid']);

            /* 게시글 작성자 알림*/
            $notify = array(
                'removed' => 1,
                'disabled' => 1,
                'view' => 1
            );

            $where = array(
                'code' => 102,
                'uid' => $article_wm_uid,
                'table_code' => $notify_table_code,
                'mb_unq' => $request['mb_unq'],
                'co_unq' => $request['co_unq'],
            );

            $notify_result = $this->notify_mdl->disabled_notify($notify, $where);

            if($notify_result < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }

            /* 비동기 mint_total_rows 갱신 */

            if((1100 <= $request['table_code'] && $request['table_code'] <= 1199) OR ( 1300 <= $request['table_code'] && $request['table_code'] <= 1399))
            {
                board_comment_list_count_update();
            }

            //퀘스트 취소
            $q_idx = '14_146'; //일반 게시판 댓글쓰기
            switch($request['table_code']){
                case '1127' : $q_idx .= '_42';break; // 일일영작문
                case '1132' : $q_idx .= '_43';break; // 오늘의영어 한마디
                case '1102' : $q_idx .= '_72';break; // 영어해석커뮤니티
                case '1120' : $q_idx .= '_73';break; // 영어문법질문&답변
                case '1138' : $q_idx .= '_74';break; // 딕테이션 해결사
                case '1337' : $q_idx .= '_93';break; // 영어고민&권태기상담
            }
            MintQuest::request_batch_quest_decrement($q_idx, $request['co_unq'].MintQuest::make_quest_subfix($request['table_code'], 'comm'));

            $return_array['res_code'] = '0000';
            $return_array['msg'] = "댓글이 삭제되었습니다.";
            $return_array['data']['wm_point'] = $comment_result;
            $return_array['data']['total_cnt'] = $list_count_article_comment;
            echo json_encode($return_array);
            exit;
        }
    }

    
    /* 일반게시판 글쓰기 */
    public function write_article()
    {
        $return_array = array();
        
        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "noticeYn" => ($this->input->post('noticeYn')) ? trim(strtoupper($this->input->post('noticeYn'))) : "N",
            "title" => $this->input->post('title'),
            "content" => $this->input->post('content'),
            "files" => isset($_FILES["files"]) ? $_FILES["files"] : null,
            "sim_content" => trim($this->input->post('sim_content')),
            "sim_content2" => trim($this->input->post('sim_content2')),
            "cafe_unq" => trim($this->input->post('cafe_unq')),
            "work_state" => ($this->input->post('work_state')) ? trim($this->input->post('work_state')) : '1',
            "secret" => ($this->input->post('secret')) ? trim($this->input->post('secret')) : "N",
            "clip_yn" => ($this->input->post('clip_yn')) ? trim($this->input->post('clip_yn')) : "Y",
            "category_code" => trim($this->input->post('category_code')),
            "category_title" => trim($this->input->post('category_title')),
            "c_yn" => ($this->input->post('c_yn')) ? trim($this->input->post('c_yn')) : "N",
            "rsms" => ($this->input->post('rsms')) ? trim($this->input->post('rsms')) : "N",
            "ns_step" => $this->input->post('ns_step'),
            "ns_lesson" => $this->input->post('ns_lesson'),
            "ielts_step" => $this->input->post('ielts_step'),
            "ielts_chapter" => $this->input->post('ielts_chapter'),
            "ielts_lesson" => $this->input->post('ielts_lesson'),
            "ahop_step" => $this->input->post('ahop_step'),
            "ahop_book" => $this->input->post('ahop_book'),
            "ahop_chapter" => $this->input->post('ahop_chapter'),
            "ahop_lesson" => $this->input->post('ahop_lesson'),
            "name_hide" => $this->input->post('name_hide') ? trim($this->input->post('name_hide')) : 'N',
            "ex_id" => $this->input->post('ex_id'),
            "cafe" => $this->input->post('cafe'),
            "showdate" => $this->input->post('showdate'),
            "tu_uid" => $this->input->post('tu_uid'),
            "tsStar" => $this->input->post('tsStar'),
            "ts_content" => $this->input->post('ts_content'),
            "item1" => $this->input->post('item1'),
            "sc_id" => $this->input->post('sc_id'),
            "file_name" => $this->input->post('file_name') ? $this->input->post('file_name') : null,                     // 수업대본 서비스/딕테이션 해결사 차감 포인트 계산용
            "cl_time" => $this->input->post('cl_time') ? $this->input->post('cl_time') : null,                           // 수업대본 서비스/딕테이션 해결사 차감 포인트 계산용
            "parent_key" => $this->input->post('parent_key') ? $this->input->post('parent_key') : null,                 // 딕테이션 해결사 답글용
            "sim_content3" => $this->input->post('sim_content3') ? $this->input->post('sim_content3') : null,           // 딕테이션 해결사 의뢰글의 요청사항
            "sim_content4" => $this->input->post('sim_content4') ? $this->input->post('sim_content4') : null,           // 딕테이션 해결사 답변글의 주의사항
            "set_point" => $this->input->post('set_point') ? $this->input->post('set_point') : 0,                       // 딕테이션 해결사 차감포인트 
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

        
        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();

        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

         /* 게시판 설정확인 */
        $this->load->model('board_mdl');
        $board_config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);

        if(!$board_config)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0209";
            $return_array['data']['err_msg'] = "잘못된 게시판입니다";
            echo json_encode($return_array);
            exit;
        }

        //$this->load->library('CI_Benchmark');
        //$this->benchmark->mark('banner_start');
        //$this->benchmark->mark('banner_end');
        //echo 'banner : '.$this->benchmark->elapsed_time('banner_start', 'banner_end').PHP_EOL;

        $valid_config = [
            'parent_key'=>$request['parent_key'],
            'knowledge_qna_type_board' => $this->knowledge_qna_type_board
        ];
        
        $err_code = board_check_valid_write_page($request['table_code'], $wiz_member, $board_config, $valid_config);

        if(!empty($err_code) && $err_code['err_code'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data'] = $err_code;
            echo json_encode($return_array);
            exit;
        }

        $table_unq = $board_config['mbn_unq'];
        $table_code = $request['table_code'];
        $title = $request['title'];
        $file_name = $request['file_name'];
        $content = cut_content($request['content'], $request['table_code']);
        $notice_yn = $request['noticeYn'];
        $work_state = $request['work_state'];
        $secret = $request['secret'];
        $clip_yn = $request['clip_yn'];
        $category_code = $request['category_code'];
        $category_title = $request['category_title'];
        $c_yn = $request['c_yn'];
        $rsms = $request['rsms'];
        $sim_content = $request['sim_content'];
        $sim_content2 = $request['sim_content2'];
        $cafe_unq = $request['cafe_unq'];
        $sc_id = $request['sc_id'];
        $cl_time = $request['cl_time'];
        $parent_key = $request['parent_key'];
        $sim_content3 = $request['sim_content3'];
        $sim_content4 = $request['sim_content4'];
        $set_point = $request['set_point'];

        
        $upload_limit_size = NULL;
        $ext_array = NULL;



        /* 게시판 포인트 지급 체크 필요여부 / true: 체크 / false : 필요없음 */
        $checked_point = false;
        /* 실제 차감되는 포인트 / 0이상 : 포인트 차감 /0 : 포인트 차감 안함 */
        $flag_point = 0;
        /* 게시글 작성시 필요한 포인트*/
        $article_point = 0;
        
        /* 
            특정 게시판 제목에 회원이름 노출용도 
            1354: NS과제물게시판     
            1376: IELTS 과제물게시판
            1133: [이벤트]미국vs영국vs필리핀
            1366: AHOP과제게시판
            1130: 수업대본서비스
        */
        $title_name = "";

        /* 회원정보 */
        $this->load->model('member_mdl');
        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);
        $wm_point = $wiz_member['wm_point'];
        
        $this->load->model('lesson_mdl');

        /* 
            1354(NS과제물게시판),  1366(AHOP과제게시판) 1130(수업대본 서비스) 게시판을 제외한 게시판은 본문 내용 입력 필수
        */
        if( $request['table_code'] != '1354' &&  $request['table_code'] != '1366' &&  $request['table_code'] != '1130' && !$request['content'] )
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = "본문 내용을 입력해주세요.";
            echo json_encode($return_array);
            exit;
        }

        /* 
            특정 게시판 제목 생성 ,  work_state, simcontent 초기값 설정
            수강중인 회원만 글작성 가능한지 체크 
            파일업로드 제한 (용량, 확장자)
            작성시 포인트 소진 여부

            1366: AHOP 게시판1354
            1382: AHOP 수정요청 게시판
            1354: NS과제물게시판     
            1376: IELTS 과제물게시판
            1133: [이벤트]미국vs영국vs필리핀
            1130: 수업대본서비스
            1138: 딕테이션 해결사
        */

        
        if($request['table_code'] == '1354' || $request['table_code'] == '1376' || $request['table_code'] == '1131'
        || $request['table_code'] == '1133' || $request['table_code'] == '1366' || $request['table_code'] == '1130'
        || $request['table_code'] == '1382' || $request['table_code'] == '1138')
        {

            /* book_code 값 있을시 수강중인 회원만 작성가능 */
            $book_code = NULL;
            /* checked_point 값 true: 포인트 차감 , flase:무료 */
            $checked_point = false;

            if($request['name_hide'] =='Y')
            {
                $title_name ='비공개';
            }
            else
            {
                if($wiz_member['wm_nickname'])
                {
                    $title_name = $wiz_member['wm_nickname'];
                }
                else if($wiz_member['wm_ename'])
                {
                    $title_name = $wiz_member['wm_ename'];
                }
                else if($wiz_member['wm_name'])
                {
                    $title_name = $wiz_member['wm_name'];
                }
            }
            

            if($request['table_code'] == '1354')
            {
                if(!$request['ns_step'] || !$request['ns_lesson'])
                {
                    $return_array['res_code'] = '0400';
                    $return_array['msg'] = "NS과정을 선택해주세요.";
                    echo json_encode($return_array);
                    exit;
                }
    
                if(!isset($_FILES["files"]))
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0318";
                    $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 첨부파일을 필수로 업로드해주셔야 합니다.";
                    echo json_encode($return_array);
                    exit;
                }

                //1354 보드에 오늘 글썼는지 체크(1회이상 카운트 체크
                $now = date('Y-m-d');
                $index = null;
                $where = " WHERE table_code = '".$request['table_code'] ."' AND wiz_id = '".$wiz_member['wm_wiz_id']."' AND date_format(regdate,'%Y-%m-%d') = '".$now."'";
                $this->load->model('board_mdl');
                $mint_boards_1354 = $this->board_mdl->list_count_board($index, $where);

                if($mint_boards_1354['cnt'] > 0)
                {
                    // 글작성 시 소모 포인트
                    $checked_point = true;
                    $article_point = 1000;
                }

                // $upload_limit_size = 5;
                // $ext_array = array('mp3');

                /* 
                    NS게시판 정책
                    현재 NS 상관없이 수업중(in class) && NS 교재를 썼던 이력이 있는사람
                */
                // $book_code = 390;
                $work_state = 4;
                $title = '['.$title_name.']님의 [step '.$request['ns_step'].']의 레슨['.$request['ns_lesson'].'] 과제물입니다.';
                $sim_content2 = $request['ns_step'].'|'.$request['ns_lesson'];

            }
            else if($request['table_code'] == '1376')
            {
                if(!$request['ielts_step'] || !$request['ielts_chapter'] || !$request['ielts_lesson'])
                {
                    $return_array['res_code'] = '0400';
                    $return_array['msg'] = "IELTS 정보를 선택해주세요.";
                    echo json_encode($return_array);
                    exit;
                }
    
                if(!isset($_FILES["files"]))
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0318";
                    $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 첨부파일을 필수로 업로드해주셔야 합니다.";
                    echo json_encode($return_array);
                    exit;
                }

                $upload_limit_size = 20;
                $ext_array = NULL;
                // 글작성 시 소모 포인트
                $checked_point = true;
                $article_point = 10000;

                $book_code = 387;
                $work_state = 1;
                $sim_content2 = $request['ielts_step'];

                $title =  '['.$title_name.']님의 IELTS [Step '.$request['ielts_step'].']의 챕터['.$request['ielts_chapter'].']의 레슨['.$request['ielts_lesson'].'] 과제물입니다.';
            }            
            else if($request['table_code'] == '1366')
            {
                if(!$request['ahop_step'] || !$request['ahop_book'] || !$request['ahop_chapter'] || !$request['ahop_lesson'])
                {
                    $return_array['res_code'] = '0400';
                    $return_array['msg'] = "AHOP정보를 입력해주세요.";
                    echo json_encode($return_array);
                    exit;
                }

                if(!isset($_FILES["files"]) && trim($request['content']) == '')
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0318";
                    $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 본문 내용을 입력하거나 첨부파일을 업로드해 주셔야 합니다.";
                    echo json_encode($return_array);
                    exit;
                }

                $upload_limit_size = 5;
                $ext_array = NULL;

                $checked_point = false;

                $book_code = 403;
                $work_state = 4;
                $sim_content2 = $request['ahop_step'].'|'.$request['ahop_book'].'|'.$request['ahop_chapter'].'|'.$request['ahop_lesson'];
                $title = '['.$title_name.']님의 AHOP [Step '.$request['ahop_step'].']의 ['.$request['ahop_book'].']교재 챕터['.$request['ahop_chapter'].']의 레슨['.$request['ahop_lesson'].'] 과제물입니다.';
            }
            else if($request['table_code'] == '1130')   // 수업대본서비스
            {
                /* b_kind == 'V' << 화상영어, b_kind == 'T' << 전화영어  */

                // 글작성 시 소모 포인트
                if(!$cl_time)
                {
                    $return_array['res_code'] = '0400';
                    $return_array['msg'] = "cl_time 을 입력해주세요.";
                    echo json_encode($return_array);
                    exit;
                }

                if(!$cafe_unq && !$sc_id)
                {
                    $return_array['res_code'] = '0400';
                    $return_array['msg'] = "sc_id 을 입력해주세요.";
                    echo json_encode($return_array);
                    exit;
                }

                $checked_point = true;
                $article_point = 500 * $cl_time;

                $upload_limit_size = 50;
                $ext_array = array('mp3', 'aac');

                if(!$cafe_unq){
                    $sc_data = $this->lesson_mdl->row_schedule_by_sc_id($sc_id, $wiz_member['wm_uid']);

                    switch ($sc_data['lesson_gubun']){
                        case 'M':
                        case 'T':
                            $b_kind = 'T';
                            break;
                        case 'V': 
                        case 'E':
                            $b_kind = 'V';
                            break;
                        case 'B':
                            $b_kind = 'T';
                            break;
                    }
    
                    $sim_content2 = $b_kind."__".substr($sc_data['startday'],0,10)."__".$cl_time."__".$sc_data['wl_book_id']."__".$sc_data['tu_uid'];
                }
                
                
            }
            else if($request['table_code'] == '1138')   // 딕테이션 해결사
            {

                //딕테이션 해결사 의뢰(첫번째 쓰는 글)
                if(!$parent_key)
                {

                    // $result['mbn_checked_today_count'] = 'N';

                    // 수업시간 - 글작성 시 소모 포인트 계산용도
                    if(!$set_point)
                    {
                        $return_array['res_code'] = '0400';
                        $return_array['msg'] = "채택시 제공할 포인트를 선택해주세요.";
                        echo json_encode($return_array);
                        exit;
                    }

                    $today = date('Y-m-d');

                    
                    // 이전에 등록한 글이 채택하지 않고 새 글 썼을때 리턴
                    // $select_board = $this->board_mdl->checked_count_board_solve_select($request['table_code'], $request['wiz_id']);
                
                    // if($select_board['cnt'] > 0)
                    // {
                    //     $return_array['res_code'] = '0900';
                    //     $return_array['msg'] = "프로세스오류";
                    //     $return_array['data']['err_code'] = "0344";
                    //     $return_array['data']['err_msg'] = "이 전에 등록하신 딕테이션 해결사를 채택한 후에 등록해주세요!";
                    //     echo json_encode($return_array);
                    //     exit;
                    // }
                    
                    /* 
                        신청글(도우미한테 하고싶은말) 
                        $sim_content3
                    */

                    $where = " uid = '".$wiz_member['wm_uid']."' AND table_code = '9002' AND board_id = '".$request['cafe_unq']."'";
                    $pivot_cafe = $this->board_mdl->checked_wiz_schedule_board_pivot($where);
                    if($pivot_cafe['wsbp_schedule_id']) $sc_id = $pivot_cafe['wsbp_schedule_id'];

                    //본인이 정한만큼 포인트 차감
                    $checked_point = true;
                    $article_point = $set_point;

                    $upload_limit_size = 50;
                    $ext_array = array('mp3', 'aac');
                    
                }
                //딕테이션 해결사 답글
                else
                {
                    
                    $today = date('Y-m-d');

                    // 자식 보드 타이틀 만듬
                    $parent = $this->board_mdl->row_article_solution_by_mb_unq($parent_key);
                    $title = $parent['mb_title'].' 의 답변';

                    
                    
                    /* 
                        답변글(신청자한테 하고싶은말) 
                        $sim_content4
                    */
                    
                    // 의뢰자가 정한 포인트의 85% 포인트 받음
                    $checked_point = true;
                    $article_point = 0;

                }

            }

            else if($request['table_code'] == '1131' || $request['table_code'] == '1133')   // 강사평가서, 미국vs영국vs필리핀
            {
                if(!$request['tu_uid'])
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0323";
                    $return_array['data']['err_msg'] = "선택된 강사가 없습니다.";
                    echo json_encode($return_array);
                    exit; 
                }
                $this->load->model('tutor_mdl');
                $tutor = $this->tutor_mdl->get_tu_name_by_tu_uid($request['tu_uid']);
                
                if(!$tutor)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0324";
                    $return_array['data']['err_msg'] = "일치하는 강사 정보가 없습니다.";
                    echo json_encode($return_array);
                    exit; 
                }

                $eval_mb_unq = $this->board_mdl->row_mint_boards_notice_sim_content($request['table_code']);

                if(!$eval_mb_unq['mb_unq'] || strpos($eval_mb_unq['sim_content'],$request['tu_uid']) === false)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0325";
                    $return_array['data']['err_msg'] = "평가 데이터 조회 실패";
                    echo json_encode($return_array);
                    exit; 
                }

                $evaled = $this->tutor_mdl->check_tutor_star_evaluated($wiz_member['wm_uid'],$request['tu_uid'],$eval_mb_unq['mb_unq']);
                if($evaled)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0326";
                    $return_array['data']['err_msg'] = "평가를 이미 등록한 선생님입니다.";
                    echo json_encode($return_array);
                    exit; 
                }
                
                
                $upload_limit_size = 50;
                $ext_array = array('mp3', 'aac');
            }
            
            /* book_id 로 조회. 수강중인 회원만 글 작성가능 여부 */   
            if($book_code)
            {
                $this->load->model('lesson_mdl');
                $checked_class = $this->lesson_mdl->checked_class_by_f_id($wiz_member['wm_uid'], $book_code);

                if(!$checked_class)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0317";
                    $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 수업 과정 중인 회원만 작성 가능합니다.";
                    echo json_encode($return_array);
                    exit;
                }
            }

            /* 글작성시 포인트 차감여부 */   
            if($checked_point)
            {
                /*
                    1130 : 수업대본 서비스
                    1138 : 딕테이션 해결사
                */
                if($request['table_code'] == '1130' || $request['table_code'] == '1138')
                {
                    if($wm_point < $article_point)
                    {
                        $return_array['res_code'] = '0900';
                        $return_array['msg'] = "프로세스오류";
                        $return_array['data']['err_code'] = "0319";
                        $return_array['data']['err_msg'] = "게시글 작성에 필요한 포인트가 부족합니다.";
                        echo json_encode($return_array);
                        exit;
                    }
                    $flag_point = -$article_point;
                }
                /*
                    NS 게시판은 처음에는 1일 첫회만 무료
                    이후에는 포인트 차감
                */
                else 
                {
                    $today = date('Y-m-d');
                    $checked_count_today = $this->board_mdl->checked_count_today_write_article($wiz_member['wm_wiz_id'], $request["table_code"], $today);

                    if($checked_count_today['cnt'] != 0)
                    {
                        if($wm_point < $article_point)
                        {
                            $return_array['res_code'] = '0900';
                            $return_array['msg'] = "프로세스오류";
                            $return_array['data']['err_code'] = "0319";
                            $return_array['data']['err_msg'] = "게시글 작성에 필요한 포인트가 부족합니다.";
                            echo json_encode($return_array);
                            exit;
                        }
                        $flag_point = -$article_point;
                    }
                }
                

            }
        }
        // 지식인 게시판
        elseif(in_array($request['table_code'],$this->knowledge_qna_type_board) && $request['table_code'] != '1138')
        {
            if($parent_key)
            {
                $parent = $this->board_mdl->row_article_solution_by_mb_unq($parent_key);
                $title = $parent['mb_title'].' 의 답변';
            }
        }

        /*
            예외처리
            [도전]일일영작문 
        */
        if ($request['table_code'] == '1127')
        {
        
            /* 글쓰기 권한체크 */
            if(false === stripos($wiz_member['wm_assistant_code'], "*1127*"))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0308";
                $return_array['data']['err_msg'] = "게시글 작성 권한이 없습니다.";
                echo json_encode($return_array);
                exit;
            } 

            if(!$request['showdate'])
            {
                $return_array['res_code'] = '0400';
                $return_array['msg'] = "게시물 노출일자를 입력해주세요.";
                echo json_encode($return_array);
                exit;
            }

            if($request['showdate'] < date('Y-m-d', time()) )
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0315";
                $return_array['data']['err_msg'] = "게시물 노출일자는 반드시 미래여야 합니다.";
                echo json_encode($return_array);
                exit;
            }
            
            if($request['showdate'] > date('Y-m-d',time()+86400*8))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0315";
                $return_array['data']['err_msg'] = "게시물 노출일자는 일주일까지만 설정 가능합니다.";
                echo json_encode($return_array);
                exit;
            }

            $showdate = $request['showdate'];
            $regdate =  $showdate." ".date("H:i:s");
        }
        else
        {
            $showdate = date("Y-m-d");
            $regdate = date("Y-m-d H:i:s");
        }

        
        /* 익명게시판 예외처리 */
        $MBN_ANONYMOUS_YN =  $this->config->item('MBN_ANONYMOUS_YN');

        /* 
            익명게시판 여부 
            - config 설정 or 날코딩
        */
        if(strtoupper($board_config['mbn_anonymous_yn']) == "Y" || in_array($request["table_code"], $MBN_ANONYMOUS_YN))
        {     
            $name_hide = "Y";
        }
        else
        {
            // 딕테이션 해결사는 기존 얼철딕의 name_hide 에 따라감.
            if($table_code == '1138')
            {
                $cafe_board_1138 = $this->board_mdl->get_1130_by_cafe_unq($cafe_unq);
                $name_hide = $cafe_board_1138['mb_name_hide'];
            }
            else
            {
                $name_hide = $request['name_hide'];
            }
        }
        
        /* 
            에디터 사용시 본문내용 중 이미지 필수 포함 체크 
            0 : 이미지 필수 아님
            1 : 이미지 필수
        */
        if($board_config['mbn_image_required'] == "1")
        {
            /*
                html 태그내에 이미지 첨부 여부 확인
            */
            if(!preg_match('/<img[^>]*src/',$content,$matches))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0316";
                $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 내용에 이미지가 필수로 포함되어 있어야 합니다.";
                echo json_encode($return_array);
                exit;
            }

        }

        $json_array = null;
        //s3파일 업로드
        if($request['files'])
        {
            if(strtoupper($board_config['mbn_file_yn']) != "Y")
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0301";
                $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 파일첨부를 할 수 없습니다.";
                echo json_encode($return_array);
                exit;
            }

            /*
                파일 업로드 확장자 제한여부
                null : 제한없음
                null 아닐시 : 제한
            */
            if(!$upload_limit_size)
            {
                $upload_limit_size = 5;
            }
            
            if(!$ext_array)
            {
                $ext_array = array('xlsx', 'xls', 'doc', 'pdf', 'jpg', 'jpeg', 'png', 'txt', 'mp3', 'aac');
            }
            
            if($board_config['mbn_file_ext'])
            {
                $ext_array = explode(',',$board_config['mbn_file_ext']);
            }

            $res = S3::put_s3_object($this->upload_path_boards, $request["files"], $upload_limit_size, $ext_array);

            if($res['res_code'] != '0000')
            {
                echo json_encode($res);
                exit;
            }

            $thumb_result = Thumbnail::create_thumbnail_formfile($request['files'],$res['file_name'],'filename',array('ori_path'=>$this->upload_path_boards));
            if($thumb_result) {
                $json_array['form'] = $thumb_result;
            }

            $file_name = $res['file_name'];
        }

        $thumbnail_content = Thumbnail::create_thumbnail_parse_content($content);
        $content = $thumbnail_content['content'];
        if(!empty($thumbnail_content['thumbnail_info'])){
            $json_array['editor'] = $thumbnail_content['thumbnail_info'];
        }

        $mbn_wpoint = $board_config['mbn_wpoint'];
        // 학부모 게시판은 익명설정 시 포인트 지급안한다.
        if($name_hide =='Y' && $request['table_code'] == '1383')
        {
            $mbn_wpoint = 0;
        }

        /* 작성자 - 지급포인트 */
        if($mbn_wpoint > 0)
        {
            /* 금일 해당 게시판 글 작성 횟수 포인트 지급횟수 제한에서 사용*/
            $today = date('Y-m-d');
            $write_today_history = $this->board_mdl->checked_count_today_write_article($wiz_member['wm_wiz_id'], $request["table_code"], $today);
        }


        $article = array(
            'noticeYn' => $notice_yn,
            'table_code' => $table_code,
            'table_unq' => $table_unq,
            'wiz_id' => $wiz_member['wm_wiz_id'],
            'name' => $wiz_member['wm_name'],
            'ename' => $wiz_member['wm_ename'],
            'nickname' => $wiz_member['wm_nickname'],
            'title' => $title,
            'filename' => ($file_name) ? $file_name : null,
            'content' => $content,
            'sim_content' => $sim_content,
            'sim_content2' => $sim_content2,
            'regdate' => $regdate,
            'showdate' => $showdate,
            'secret' => $secret,
            'work_state' => $work_state,            
            'c_yn' => $c_yn,
            'cafe_unq' => $cafe_unq,
            'rsms' => $rsms,
            'name_hide' => $name_hide,
            'clip_yn' => $clip_yn,
            'category_code' => $category_code,
            'category_title' => $category_title,
            'thumb' => $json_array ? json_encode($json_array):'',
            'parent_key' => $parent_key,
            'sim_content3' => $sim_content3,
            'sim_content4' => $sim_content4,
            'set_point' => $set_point
        );

        
        /* $mb_unq 리턴줘야됨 포인트 삽입시 필요 */
        $this->load->model('board_mdl');
        $mb_unq = $this->board_mdl->write_article(($article));

        if($mb_unq < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        // 수업대본을 수업과 연결
        // if($table_code=="1130") 
        if($table_code=="1130") 
        {
            $param = array(
                'uid' => $wiz_member['wm_uid'],
                'schedule_id' => $sc_id,
                'table_code' => $table_code,
                'board_id' => $mb_unq,
                'created_at' => $regdate,
            );

            $pivot = $this->board_mdl->insert_schedule_board_pivot($param);
        }
        
        // 딕테이션 해결사의 부모글
        if($table_code=="1138" && !$parent_key) 
        {
            $param = array(
                'uid' => $wiz_member['wm_uid'],
                'schedule_id' => $sc_id,
                'table_code' => $table_code,
                'board_id' => $mb_unq,
                'created_at' => $regdate,
            );

            $pivot = $this->board_mdl->insert_schedule_board_pivot($param);

        }
        /*
            지식인게시판
            답변(자식)글을 등록했을때 알림 보내기
            댓글은 비동기로 보내고있음.
            지식인게시판은 답글이(게시판) 예외적으로 달리는 게시판이라 여기서만 알림톡 보내기 추가
        */
        else if(in_array($table_code,$this->knowledge_qna_type_board) && $parent_key)
        {
            $anonymous_name = "익명";
            $display_name = "";

            if($wiz_member["wm_nickname"])
            {
                $display_name = $wiz_member["wm_nickname"];
            }
            else
            {
                $display_name = ($wiz_member['wm_ename']) ? $wiz_member['wm_ename'] : $wiz_member['wm_name'];
            }
            
            /* 게시글 작성자, 댓글작성자 차단목록 확인*/
            $checked_blcok_list = $this->board_mdl->checked_block_list($wiz_member['wm_uid'], $parent['wm_uid']);

            // 알림톡 발송
            if(!$checked_blcok_list && $parent['mb_rsms'] =='Y' && $wiz_member['wm_uid'] != $parent['wm_uid'] )
            {
                $board_link = board_make_viwe_link($request["table_code"], $parent['mb_mb_unq']);
                $options = array(
                    'name'  =>  $parent['wm_name'],
                    'board_name'  =>  $board_config["mbn_table_name"],
                    'url'   =>  $board_link
                );
            
                sms::send_atalk($parent['wm_mobile'], 'MINT06004N', $options);
            }

            /* 차단목록에 없다면 알림 내가 쓴글에 내가 댓글 달때 제외*/
            if(!$checked_blcok_list && $wiz_member['wm_uid'] != $parent['wm_uid'])
            {
                $this->load->model('notify_mdl');

                /* 게시글 작성자 알림*/
                $notify = array(
                    'uid' => $parent['wm_uid'], 
                    'code' => 102, 
                    'message' => '작성하신 게시글에 '.$anonymous_name.'님의 답글이 등록되었습니다.', 
                    'table_code' => $table_code, 
                    'user_name' => $display_name,
                    'board_name' => $parent['mbn_table_name'], 
                    'content'=> $parent['mb_content'], 
                    'mb_unq' => $parent['mb_mb_unq'], 
                    'co_unq' => NULL,
                    'parent_key' => $parent_key,
                    'regdate' => date('Y-m-d H:i:s'),
                );

                $notify_result = $this->notify_mdl->insert_notify($notify);

                if($notify_result < 0)
                {
                    $return_array['res_code'] = '0500';
                    $return_array['msg'] = "DB ERROR";
                    echo json_encode($return_array);
                    exit;
                }
            }
        }

        if($request['table_code'] == 'express')		//이런표현어떻게
        {
            $table_code = "9001";
        }
        else if($request['table_code'] == 'dictation.t' || $request['table_code'] == 'dictation.v' || $request['table_code'] == 'dictation.list')	//얼철딕
        {
            $table_code = "9002";
        }
        else if($request['table_code'] == 'correction')		//영어첨삭게시판
        {
            $table_code = "9004";
        }
        else if($request['table_code'] == 'toteacher')		//강사와1:1
        {
            $table_code = "9998";
        }
        else if($request['table_code'] == 'request')		//실시간요청게시판
        {
            $table_code = "9999";
        }
        else
        {
            $table_code = $request['table_code'];
        }

        /* 작성자 - 지급포인트 */
        if($mbn_wpoint > 0)
        {
            /* 금일 해당 게시판 글 작성 횟수넘으면 지급안함 */
            if($board_config['mbn_wpoint_limit'] > 0 && $board_config['mbn_wpoint_limit'] > $write_today_history['cnt'])
            {
                if($wiz_member['wm_uid'] != '' && $wiz_member['wm_uid'] != '0')
                {
                    $pt_name = $board_config['mbn_table_name']." 게시글 등록 ". number_format($mbn_wpoint) . "포인트 적립";
                    $point = array(
                        'uid' => $wiz_member['wm_uid'],
                        'name' => $wiz_member['wm_name'],
                        'point' => $mbn_wpoint,
                        'pt_name'=> $pt_name, 
                        'kind'=> 'x', 
                        'b_kind'=> 'boards',
                        'table_code'=> $request['table_code'],
                        'co_unq'=> $mb_unq, 
                        'showYn'=> 'y',
                        'secret'=> 'N',
                        'regdate' => date("Y-m-d H:i:s")
                    );

                
                    /* 포인트 내역 입력 및 포인트 추가 */
                    $this->load->model('point_mdl');
                    $rpoint = $this->point_mdl->set_wiz_point($point);

                    if($rpoint < 0)
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

                }
            }
            
        }

        /* 포인트 차감 */
        if($flag_point != 0)
        {
            $kind = NULL;
            $b_kind = NULL;
            $pt_name = NULL;

            if($request['table_code'] == '1130')
            {
                //수업대본서비스
                $kind = "x";
                $b_kind = "boards";
            }
            else if($request['table_code'] == '1354')
            {
                //NS과제물게시판   
                $kind = "1";
                $b_kind = NULL;
            }
            else if($request['table_code'] == '1376')
            {
                //IELTS 과제물게시판
                $kind = "j";
                $b_kind = "service";
            }
            else if($request['table_code'] == '1138')
            {
                /**
                 * 딕테이션해결사 포인트 감소
                 * 임시적으로 sl로 지칭
                */ 
                $kind = "sl";
                $b_kind = "dictation";
            }
            
            $pt_name = $board_config['mbn_table_name']." 게시글 등록 ". number_format($flag_point) . "포인트 차감";
            
            $point = array(
                'uid' => $wiz_member['wm_uid'],
                'name' => $wiz_member['wm_name'],
                'point' => $flag_point,
                'pt_name'=> $pt_name, 
                'kind'=> $kind, 
                'b_kind' => $b_kind, 
                'showYn' => 'y',
                'regdate' => date("Y-m-d H:i:s"),
            );
            
            // 딕테이션 해결사는 포인트 취소시 반납해야함
            if($request['table_code'] ='1138')
            {
                $point['co_unq'] = $mb_unq;
            }
        
            /* 포인트 내역 입력 및 포인트 추가 */
            $this->load->model('point_mdl');
            $rpoint = $this->point_mdl->set_wiz_point($point);

            if($rpoint < 0)
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

        }


        if($table_code == '1111'){
            // 얼철딕 후기 작성 시 board_id 세팅
            if($request['cafe'] =='true')
            {
                $mca_where = " WHERE mca.uid = '".$wiz_member['wm_uid']."' AND mca.approval = 'N' AND mca.board_id IS NULL ";
                
                $checked_approval = $this->board_mdl->checked_approval_cafaboard($mca_where);
                if($checked_approval){
                    $this->board_mdl->update_approval_cafaboard_board_id($mb_unq,$checked_approval['mca_id']);
                }
            }
            
            // ahop 후기 작성 시 review_id 세팅
            if($request['ex_id'])
            {
                $this->load->model('book_mdl');
                $ahop_test = $this->book_mdl->check_exam_log_by_ex_id($wiz_member['wm_uid'],$request['ex_id']);

                if($ahop_test)
                {
                    $this->book_mdl->update_book_exam_log_review_id($wiz_member['wm_uid'],$mb_unq, $ahop_test['book_id']);
                }
                
            }
        }

        // 강사평가서, 미국vs영국vs필리핀 강사평가 등록
        if($request['table_code'] == '1131' || $request['table_code'] == '1133')   
        {
            $ts_param = [
                'tu_uid' => $request['tu_uid'],
                'uid' => $wiz_member['wm_uid'],
                'mb_unq' => $eval_mb_unq['mb_unq'],
                'mb_unq_board' => $mb_unq,
                'ts_name' => $tutor['tu_name'],
                'ts_star' => $request['tsStar'],
                'ts_content' => $request['ts_content'],
                'regdate' => date('Y-m-d H:i:s'),
                'name_hide' => 'N',
                'item1' => $request['item1'],
                'item2' => substr($wiz_member['wm_lev_gubun'], 0, 1),
            ];
            $this->tutor_mdl->insert_tutor_evaluation($ts_param,true);
        }

        
        /* 비동기 mint_total_rows 갱신 */
        
        if((1100 <= $table_code && $table_code <= 1199) OR ( 1300 <= $table_code && $table_code <= 1399))
        {
            board_list_count_update();
        }

        // 검색테이블로 인설트
        board_insert_search_boards($request['table_code'], $mb_unq);

        //퀘스트
        $q_idx = '12'; //일반 게시판 글쓰기

        if($request['table_code'] =='1102' || $request['table_code'] =='1120' || $request['table_code'] =='1128' || $request['table_code'] =='1125' || $request['table_code'] =='1126'
          || $request['table_code'] =='1388' || $request['table_code'] =='1383' || $request['table_code'] =='1353' || $request['table_code'] =='1337' || $request['table_code'] =='1350'
        ) 
        {
            $q_idx.= '_124';
        }

        switch($table_code){
            case '1130' : $q_idx .= '_45_194';break; // 수업대본서비스
            case '1120' : // 영어문법질문&답변
                if(!$parent_key) $q_idx .= '_47';
                break; 
            case '1138' : 
                if($parent_key) $q_idx .= '_214'; // 딕테이션 해결사 답글 달기
                else            $q_idx .= '_48';  // 딕테이션 해결사 글쓰기
                break;
            case '1337' : // 영어고민&권태기상담
                if(!$parent_key) $q_idx .= '_49';
                break; 
            case '1131' : $q_idx .= '_67';break; // 강사평가서등록
            case '1133' : $q_idx .= '_68';break; // 미국vs영국vs필리핀
            case '1128' : $q_idx .= '_75';break; // 유용한영어표현
            case '1111' : $q_idx .= '_91_236';break; // 수업체험후기
            case '1350' : $q_idx .= '_92';break; // 민트사용노하우
            case '1354' : $q_idx .= '_76';break; // ns수강하기->과제물 등록으로 체크
        }
        MintQuest::request_batch_quest($q_idx, $mb_unq.MintQuest::make_quest_subfix($table_code));

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "게시물을 등록했습니다.";
        $return_array['data']['wm_point'] = $wm_point;
        $return_array['data']['mb_unq'] = $mb_unq;
        echo json_encode($return_array);
        exit;


    }

    // 만족도 
    public function update_star()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim($this->input->post('wiz_id')),
            "article_key" => trim($this->input->post('article_key')),
            "star" => trim($this->input->post('star')),
            "table_code" => trim($this->input->post('table_code')),
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

        $this->load->model('board_mdl');
        $this->load->model('point_mdl');
        $this->load->model('tutor_mdl');

        if($request['table_code'] == '1130')
        {
            /* 1130(수업대본서비스)게시글 있는지 체크 */
            $article = $this->board_mdl->row_article_by_mb_unq($request['table_code'], $request['article_key']);
            if($article)
            {
                //검색테이블 업데이트(조회수 증감에 따른)
                $search_params = array(
                    'mb_unq' => $request['mb_unq'],
                    'hit'    => $article['mb_hit'],
                    'recom'  => $article['mb_recom']
                );
                $this->board_mdl->update_search_boards($request['table_code'], $search_params);
            }
        }
        else if($request['table_code'] == 'correction')
        {
            /* 영어첨삭게시글 있는지 체크 */
            $article = $this->board_mdl->row_article_wiz_correct_by_w_id($request['article_key']);
        }
       
        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article['mb_wiz_id'] != $request['wiz_id'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0321";
            $return_array['data']['err_msg'] = "게시물 권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($request['table_code'] == 'correction')
        {
            if($article['mb_w_step'] != '2')
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0349";
                $return_array['data']['err_msg'] = "영어첨삭 만족도 평가는 진행 상태가 완료된 후에 가능합니다.";
                echo json_encode($return_array);
                exit;
            }
        }
        
        if($request['table_code'] == '1130')
        {
            if($article['mb_work_state'] != '5')
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0350";
                $return_array['data']['err_msg'] = "수업대본서비스 만족도 평가는 진행 상태가 완료된 후에 가능합니다.";
                echo json_encode($return_array);
                exit;
            }
        }

        if($article['mb_star'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0343";
            $return_array['data']['err_msg'] = "이미 만족도가 등록되어 있습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        $return_msg = "";
        
        if($request['table_code'] == '1130')
        {
            $return_msg = "수업대본 서비스 만족도 평가에 참여해 주셔서 감사합니다.";
            $result = $this->board_mdl->update_star($wiz_member['wm_wiz_id'], $request['article_key'],  $request['star']);

            if($result && $request['star'] == '5')
            {
                # 강사정보
                $tutor_info = $this->tutor_mdl->get_tutor_info_by_tu_uid($article['mb_tu_uid']);

                // 기본급 강사는 받지 않음
                if($tutor_info['del_yn'] =='n' && $tutor_info['pay_type'] !='a')
                {
                    if($article['mb_cafe_unq']) $money = "20";
                    else                        $money = "10";

                    # 강사 인센티브 등록.
                    $incentive_param = [
                        'tu_uid'     => $article['mb_tu_uid'],
                        'tu_id'      => $tutor_info['tu_id'],
                        'tu_name'    => $tutor_info['tu_name'],
                        'lesson_id'  => $request['article_key'],
                        'uid'        => $wiz_member['wm_uid'],
                        'name'       => $wiz_member['wm_ename'],
                        'money'      => $money,
                        'in_kind'    => '13',
                        'in_yn'      => 'y',
                        'regdate'    => date("Y-m-d H:i:s"),
                    ];
                    $this->tutor_mdl->insert_tutor_incentive($incentive_param);
                }
            }
        }
        else if($request['table_code'] == 'correction')
        {
            // 만족도 5라면 1000포인트 소모, 강사 인센티브 지급
            if($request['star'] == '5')
            {
                // 현재포인트
                $cur_point = $this->point_mdl->check_current_point($wiz_member['wm_uid']);
                $need_point = 1000;

                if($need_point > $cur_point['wm_point'])
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0328";
                    $return_array['data']['err_msg'] = "포인트가 부족합니다.";
                    echo json_encode($return_array);
                    exit;
                }
                else
                {
                    $point = array(
                        'uid' => $wiz_member['wm_uid'],
                        'name' => $wiz_member['wm_name'],
                        'point' => '-'.$need_point,
                        'pt_name'=> '[영어첨삭게시판] 만족도 5점 평가 '.number_format($need_point).' 포인트 차감', 
                        'kind'=> 'n', 
                        'b_kind'=> 'correction', 
                        'co_unq'=> $request['article_key'], 
                        'regdate' => date("Y-m-d H:i:s")
                    );
    
                    /* 포인트 내역 입력 및 포인트 추가 */
                    $this->point_mdl->set_wiz_point($point);
                }
            }
            $return_msg = "영어첨삭 서비스 만족도 평가에 참여해 주셔서 감사합니다.";
            $result = $this->board_mdl->update_star_wiz_correct($wiz_member['wm_uid'], $request['article_key'],  $request['star']);   
            
            if($result && $request['star'] == '5')
            {
                # 강사정보
                $tutor_info = $this->tutor_mdl->get_tutor_info_by_tu_uid($article['mb_tu_uid']);

                // 기본급 강사는 받지 않음
                if($tutor_info['del_yn'] =='n' && $tutor_info['pay_type'] !='a')
                {
                    // 2020-10-19 기준 48,52에 해당하는 강사 없음
                    if($tutor_info['group_id2']=="48" || $tutor_info['group_id2']=="52")
                    {
                        $money = "40"; 
                    }
                    else 
                    {
                        $money = "20";
                    }

                    # 강사 인센티브 등록.
                    $incentive_param = [
                        'tu_uid'     => $article['mb_tu_uid'],
                        'tu_id'      => $tutor_info['tu_id'],
                        'tu_name'    => $tutor_info['tu_name'],
                        'lesson_id'  => $request['article_key'],
                        'uid'        => $wiz_member['wm_uid'],
                        'name'       => $wiz_member['wm_ename'],
                        'money'      => $money,
                        'in_kind'    => '11',
                        'in_yn'      => 'y',
                        'regdate'    => date("Y-m-d H:i:s"),
                    ];
                    $this->tutor_mdl->insert_tutor_incentive($incentive_param);
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

        $cur_point = $this->point_mdl->check_current_point($wiz_member['wm_uid']);

        $return_array['data']['wm_point'] = $cur_point['wm_point'];
        $return_array['res_code'] = '0000';
        $return_array['msg'] = $return_msg;
        echo json_encode($return_array);
        exit;
    }


    public function check_valid_write_page()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim($this->input->post('wiz_id')),
            "table_code" => trim($this->input->post('table_code')),
            "sc_id" => trim($this->input->post('sc_id')),
            "co_unq" => trim($this->input->post('co_unq')),
            "parent_key" => trim($this->input->post('parent_key')),
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

        if(strpos($request['table_code'],'dictation') !==false)
        {
            $request['table_code'] = 'dictation';
        }

        $return_array['data']['write_over'] = 0;
        $config = [
            'sc_id'=>$request['sc_id'], 
            'parent_key'=>$request['parent_key'],
            'co_unq'=>$request['co_unq'],
            'knowledge_qna_type_board' => $this->knowledge_qna_type_board
        ];
        $err_code = board_check_valid_write_page($request['table_code'],$wiz_member,[],$config);

        if(!empty($err_code) && $err_code['err_code'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data'] = $err_code;
        }
        elseif(!empty($err_code) && !$err_code['err_code'])
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "";
            $return_array['data'] = $err_code;
        }
        else
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "";
        }

        echo json_encode($return_array);
        exit;
    }

    /*
        일반게시판 글삭제
    */
    public function delete_article()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "mb_unq" => trim($this->input->post('mb_unq'))
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
        /* 회원 보유포인트 */
        $wm_point = $wiz_member['wm_point'];
        

        $article = NULL;
        $tmp_article_wm_uid = NULL;

        $this->load->model('board_mdl');
        $this->load->model('tutor_mdl');

        $article = $this->board_mdl->row_article_by_mb_unq($request['table_code'], $request['mb_unq']);

        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        //검색테이블 업데이트(조회수 증감에 따른)
        $search_params = array(
            'mb_unq' => $request['mb_unq'],
            'hit'    => $article['mb_hit'],
            'recom'  => $article['mb_recom']
        );
        $this->board_mdl->update_search_boards($request['table_code'], $search_params);

        $tmp_article_wm_uid = $this->member_mdl->get_wm_uid_by_wiz_id($article['mb_wiz_id']);
        $article_wm_uid = $tmp_article_wm_uid['wm_uid'];

        $article_comment = $this->board_mdl->checked_article_comment_by_mb_unq($request['mb_unq']);

        if($article_comment)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0320";
            $return_array['data']['err_msg'] =  "댓글이 있는 글은 삭제 할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article['mb_wiz_id'] != $request['wiz_id'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0321";
            $return_array['data']['err_msg'] = "게시물 삭제 권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article['mb_recom'] > 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0322";
            $return_array['data']['err_msg'] = "추천이 있는 게시물은 삭제할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        // 강사평가서는 1주이내 삭제가능
        if($request['table_code'] == '1131' && $article['mb_regdate'] < date('Y-m-d H:i:s',strtotime('-7 day')))
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0327";
            $return_array['data']['err_msg'] =  "강사평가서는 작성 후 1주 이내 삭제가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        // 딕테이션 해결사 
        /* if($request['table_code'] == '1138')
        {
            // 채택 이후에는 삭제 불가
            if($article['select_key'] != NULL)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0347";
                $return_array['data']['err_msg'] =  "이미 채택된 답변은 수정/삭제할 수 없습니다.\r
                답변에 부적절한 내용이 포함된 경우에는 고객센터>실시간요청게시판으로 문의해 주세요.";
                echo json_encode($return_array);
                exit;
            }

            // 게시글에 답변이 달렸는지 체크
            $article_solve = $this->board_mdl->list_article_solve_by_parentkey($request['table_code'], $article['mb_unq']);

            if($article_solve)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0348";
                $return_array['data']['err_msg'] =  "게시글에 이미 답변이 등록되어 수정/삭제할 수 없습니다.\r
                답변 채택 또는 부적절한 답변이 등록된 경우 고객센터>실시간요청게시판으로 문의해 주세요.";
                echo json_encode($return_array);
                exit;
            }
        } */

        // 지식인보드
        if(in_array($request['table_code'], $this->knowledge_qna_type_board))
        {
            // 답변글이라면 채택 후 삭제 불가
            if($article['mb_parent_key'])
            {
                $check_adopt = $this->board_mdl->checked_article_adopt_without_type($request['table_code'], $article['mb_parent_key'], $request['mb_unq']);   

                if($check_adopt)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0347";
                    $return_array['data']['err_msg'] =  "이미 채택된 답변은 수정/삭제할 수 없습니다.\r
                    답변에 부적절한 내용이 포함된 경우에는 고객센터>실시간요청게시판으로 문의해 주세요.";
                    echo json_encode($return_array);
                    exit;
                }
                
            }
            else
            {
                // 질문글이라면 게시글에 답변이 달렸는지 체크
                $article_solve = $this->board_mdl->checked_knowledge_article_has_anwser($request['mb_unq']);

                if($article_solve)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0348";
                    $return_array['data']['err_msg'] =  "게시글에 이미 답변이 등록되어 수정/삭제할 수 없습니다.\r
                    답변 채택 또는 부적절한 답변이 등록된 경우 고객센터>실시간요청게시판으로 문의해 주세요.";
                    echo json_encode($return_array);
                    exit;
                }
            }

            
        }

        // 얼철딕 후기는 삭제 시 연관된 테이블이 꼬일 우려가 있어 삭제 금지
        if($article['mb_table_code'] == '1111' && $article['mb_category_code'] =='52')
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0354";
            $return_array['data']['err_msg'] = "얼철딕 후기는 삭제할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        /* s3이미지,첨부파일 삭제 비동기처리 */
        //board_delete_board_edit_files($request['table_code'], $request['mb_unq']);

        /* 비동기 처리에서 동기로 변경. 비동기로 해버리면 아래 delete_article 함수가 먼저 진행 될수가 있으므로 파일데이터를 못가져오는 경우가 생길 수 있어 변경.
        board_delete_files는 임시테이블에 삭제할 데이터를 저장만 해준다.
        살제 삭제는 매일 임시이미지 삭제하는 크론에서 배치로 같이 삭제된다 */
        board_delete_files($article['mb_filename'], $this->upload_path_boards, $article['mb_content'], json_decode($article['mb_thumb'],true));

        $article_result = $this->board_mdl->delete_article($article['mb_unq'], $wiz_member['wm_wiz_id'], $wiz_member['wm_uid'], $request['table_code']);
    
        if($article_result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        //강의평가와 필vs미vs영은 삭제 시 강사평사도 삭제
        if($request['table_code'] == '1131' || $request['table_code'] == '1133')
        {
            //mb_unq_board에 게시글번호로 들어가있는지 체크하여 있으면 삭제
            $evaled = $this->tutor_mdl->check_tutor_star_evaluated_by_mb_unq_board($wiz_member['wm_uid'],$article['mb_unq']);
            if($evaled)
            {
                $tu_delete_where = [
                    'uid' => $wiz_member['wm_uid'],
                    'mb_unq_board' => $article['mb_unq'],
                ];
                $this->tutor_mdl->delete_tutor_evaluation($tu_delete_where);
            }
    
        }

       /* 비동기 mint_total_rows 갱신 */
        
        if((1100 <= $request['table_code'] && $request['table_code'] <= 1199) OR ( 1300 <= $request['table_code'] && $request['table_code'] <= 1399))
        {
            board_list_count_update();
        }

        
        if($request['table_code'] == '1111'){
            // 얼철딕 후기 삭제 시 board_id 세팅되어있으면 매칭된 board_id를 널로 다시 바꿔준다.
            $mca_where = " WHERE mca.uid = '".$wiz_member['wm_uid']."' AND mca.approval = 'N' AND mca.board_id IS NOT NULL ";
            
            $checked_approval = $this->board_mdl->checked_approval_cafaboard($mca_where);
            if($checked_approval){
                $this->board_mdl->update_approval_cafaboard_board_id(null,$checked_approval['mca_id']);
            }
            
        }

        // 검색 테이블에서 삭제
        board_delete_search_boards($request['table_code'], $request['mb_unq'], $request['wiz_id']);

        //퀘스트 진행 취소
        $q_idx = '12'; //일반 게시판 글쓰기

        if($request['table_code'] =='1102' || $request['table_code'] =='1120' || $request['table_code'] =='1128' || $request['table_code'] =='1125' || $request['table_code'] =='1126'
          || $request['table_code'] =='1388' || $request['table_code'] =='1383' || $request['table_code'] =='1353' || $request['table_code'] =='1337' || $request['table_code'] =='1350'
        ) 
        {
            $q_idx.= '_124';
        }

        switch($request['table_code'])
        {
            case '1130' : $q_idx .= '_45_194';break; // 수업대본서비스
            case '1120' : // 영어문법질문&답변
                if(!$article['mb_parent_key']) $q_idx .= '_47';
                break; 
            case '1138' : 
                if($article['mb_parent_key']) $q_idx .= '_214'; // 딕테이션 해결사 답글 달기
                else $q_idx .= '_48';                           // 딕테이션 해결사 글쓰기
                break;
            case '1337' : // 영어고민&권태기상담
                if(!$article['mb_parent_key']) $q_idx .= '_49';
                break; 
            case '1131' : $q_idx .= '_67';break; // 강사평가서등록
            case '1133' : $q_idx .= '_68';break; // 미국vs영국vs필리핀
            case '1128' : $q_idx .= '_75';break; // 유용한영어표현
            case '1111' : $q_idx .= '_91_236';break; // 수업체험후기
            case '1350' : $q_idx .= '_92';break; // 민트사용노하우
            case '1354' : $q_idx .= '_76';break; // ns수강하기->과제물 등록으로 체크
        }
        MintQuest::request_batch_quest_decrement($q_idx, $request['mb_unq'].MintQuest::make_quest_subfix($request['table_code']));

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "게시물을 삭제했습니다.";
        $return_array['data']['wm_point'] = $article_result;
        echo json_encode($return_array);
        exit;
    }



    /* 일반게시판 글쓰기 */
    public function modify_article()
    {

        $return_array = array();

        $request = array(
            "mb_unq" => trim($this->input->post('mb_unq')),
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "noticeYn" => ($this->input->post('noticeYn')) ? trim(strtoupper($this->input->post('noticeYn'))) : "N",
            "title" => $this->input->post('title'),
            "content" => $this->input->post('content'),
            "files" => isset($_FILES["files"]) ? $_FILES["files"] : null,
            "sim_content" => trim($this->input->post('sim_content')),
            "sim_content2" => trim($this->input->post('sim_content2')),
            "cafe_unq" => trim($this->input->post('cafe_unq')),
            "work_state" => ($this->input->post('work_state')) ? trim($this->input->post('work_state')) : '1',
            "secret" => ($this->input->post('secret')) ? trim($this->input->post('secret')) : "N",
            "clip_yn" => ($this->input->post('clip_yn')) ? trim($this->input->post('clip_yn')) : "Y",
            "category_code" => trim($this->input->post('category_code')),
            "category_title" => trim($this->input->post('category_title')),
            "c_yn" => ($this->input->post('c_yn')) ? trim($this->input->post('c_yn')) : "N",
            "rsms" => ($this->input->post('rsms')) ? trim($this->input->post('rsms')) : "N",
            "ns_step" => $this->input->post('ns_step'),
            "ns_lesson" => $this->input->post('ns_lesson'),
            "ielts_step" => $this->input->post('ielts_step'),
            "ielts_chapter" => $this->input->post('ielts_chapter'),
            "ielts_lesson" => $this->input->post('ielts_lesson'),
            "ahop_step" => $this->input->post('ahop_step'),
            "ahop_book" => $this->input->post('ahop_book'),
            "ahop_chapter" => $this->input->post('ahop_chapter'),
            "ahop_lesson" => $this->input->post('ahop_lesson'),
            "name_hide" => $this->input->post('name_hide') ? trim($this->input->post('name_hide')) : 'N',
            "file_name" => $this->input->post('file_name') ? $this->input->post('file_name') : null,                     // 수업대본 서비스/딕테이션 해결사 차감 포인트 계산용
            "cl_time" => $this->input->post('cl_time') ? $this->input->post('cl_time') : null,                           // 수업대본 서비스/딕테이션 해결사 차감 포인트 계산용
            "sim_content3" => $this->input->post('sim_content3') ? $this->input->post('sim_content3') : null,           // 딕테이션 해결사 의뢰글의 요청사항
            "sim_content4" => $this->input->post('sim_content4') ? $this->input->post('sim_content4') : null,           // 딕테이션 해결사 답변글의 주의사항
            "set_point" => $this->input->post('set_point') ? $this->input->post('set_point') : 0,                       // 딕테이션 해결사 차감포인트 
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

        /* 게시판 설정확인 */
        $this->load->model('board_mdl');
        $board_config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);

    
        $table_unq = $board_config['mbn_unq'];
        $table_code = $request['table_code'];
        $title = $request['title'];
        $content = cut_content($request['content']);
        $notice_yn = $request['noticeYn'];
        $work_state = $request['work_state'];
        $secret = $request['secret'];
        $clip_yn = $request['clip_yn'];
        $category_code = $request['category_code'];
        $category_title = $request['category_title'];
        $c_yn = $request['c_yn'];
        $rsms = $request['rsms'];
        $sim_content = $request['sim_content'];
        $sim_content2 = $request['sim_content2'];
        $sim_content3 = $request['sim_content3'];
        $sim_content4 = $request['sim_content4'];
        $cl_time = $request['cl_time'];
        $cafe_unq = $request['cafe_unq'];
        $set_point = $request['set_point'];

        
        $upload_limit_size = NULL;
        $ext_array = NULL;

        /* 게시판 포인트 지급 체크 필요여부 / true: 체크 / false : 필요없음 */
        $checked_point = false;
        /* 실제 차감되는 포인트 / 0이상 : 포인트 차감 /0 : 포인트 차감 안함 */
        $flag_point = 0;
        /* 게시글 작성시 필요한 포인트*/
        $article_point = 0;
        
        /* 
            특정 게시판 제목에 회원이름 노출용도 
            1354: NS과제물게시판     
            1376: IELTS 과제물게시판
            1133: [이벤트]미국vs영국vs필리핀
            1366: AHOP과제게시판
            1130: 수업대본서비스
        */
        $title_name = "";
        
        /* 회원정보 */
        $this->load->model('member_mdl');
        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);
        $wm_point = $wiz_member['wm_point'];

        $tmp_article_wm_uid = NULL;
        $article_wm_uid = NULL;
    

        $article = $this->board_mdl->row_article_by_mb_unq($request['table_code'], $request['mb_unq']);

        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        //검색테이블 업데이트(조회수 증감에 따른)
        $search_params = array(
            'mb_unq' => $request['mb_unq'],
            'hit'    => $article['mb_hit'],
            'recom'  => $article['mb_recom']
        );
        $this->board_mdl->update_search_boards($request['table_code'], $search_params);

        $tmp_article_wm_uid = $this->member_mdl->get_wm_uid_by_wiz_id($article['mb_wiz_id']);
        $article_wm_uid = $tmp_article_wm_uid['wm_uid'];

        
        $article_comment = $this->board_mdl->checked_article_comment_by_mb_unq($request['mb_unq']);

        if($article['mb_wiz_id'] != $request['wiz_id'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0321";
            $return_array['data']['err_msg'] = "게시물 수정 권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if(strtoupper($board_config['mbn_write_yn']) != "Y")
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0301";
            $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 글쓰기가 제한되어 있습니다.";
            echo json_encode($return_array);
            exit;
        }

        // 강사평가서는 1주이내 수정가능
        if($request['table_code'] == '1131' && $article['mb_regdate'] < date('Y-m-d H:i:s',strtotime('-7 day')))
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0327";
            $return_array['data']['err_msg'] =  "강사평가서는 작성 후 1주 이내 수정가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        /* 
            1354(NS과제물게시판),  1366(AHOP과제게시판) 게시판을 제외한 게시판은 본문 내용 입력 필수
        */
        if($request['table_code'] != '1354' &&  $request['table_code'] != '1366' && !$request['content'])
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = "본문 내용을 입력해주세요.";
            echo json_encode($return_array);
            exit;
        }

        if($request['table_code'] == '1130')
        {
            $sim_content2 = $article['mb_sim_content2'];
        }
        /* 
            특정 게시판 제목 생성 ,  work_state, simcontent 초기값 설정
            수강중인 회원만 글작성 가능한지 체크 
            파일업로드 제한 (용량, 확장자)
            작성시 포인트 소진 여부

            1354: NS과제물게시판     
            1376: IELTS 과제물게시판
            1366: AHOP과제게시판
            1133: [이벤트]미국vs영국vs필리핀
            1130: 수업대본서비스
        */

        // 지식인 게시판
        if(in_array($request['table_code'],$this->knowledge_qna_type_board))
        {
            
            // 답변글이라면 채택 후 삭제 불가
            if($article['mb_parent_key'])
            {
                $check_adopt = $this->board_mdl->checked_article_adopt_without_type($request['table_code'], $article['mb_parent_key'], $request['mb_unq']);   

                if($check_adopt)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0347";
                    $return_array['data']['err_msg'] =  "이미 채택된 답변은 수정/삭제할 수 없습니다.\r
                    답변에 부적절한 내용이 포함된 경우에는 고객센터>실시간요청게시판으로 문의해 주세요.";
                    echo json_encode($return_array);
                    exit;
                }

                $parent = $this->board_mdl->row_article_solution_by_mb_unq($article['mb_parent_key']);
                $title = $parent['mb_title'].' 의 답변';
                
            }
            else
            {
                // 질문글이라면 게시글에 답변이 달렸는지 체크
                $article_solve = $this->board_mdl->checked_knowledge_article_has_anwser($request['mb_unq']);

                if($article_solve)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0348";
                    $return_array['data']['err_msg'] =  "게시글에 이미 답변이 등록되어 수정/삭제할 수 없습니다.\r
                    답변 채택 또는 부적절한 답변이 등록된 경우 고객센터>실시간요청게시판으로 문의해 주세요.";
                    echo json_encode($return_array);
                    exit;
                }
            }

            if($article['mb_recom'] > 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0351";
                $return_array['data']['err_msg'] =  "추천이 된 게시물은 수정할 수 없습니다.";
                echo json_encode($return_array);
                exit;
            }
        }
        elseif($request['table_code'] == '1354' || $request['table_code'] == '1376' || $request['table_code'] == '1131'
        || $request['table_code'] == '1133' || $request['table_code'] == '1366' || $request['table_code'] == '1130' || $request['table_code'] == '1138')
        {
            /* book_code 값 있을시 수강중인 회원만 작성가능 */
            $book_code = NULL;
            /* checked_point 값 true: 포인트 차감 , flase:무료 */
            $checked_point = false;

            if($wiz_member['wm_nickname'])
            {
                $title_name = $wiz_member['wm_nickname'];
            }
            else if($wiz_member['wm_ename'])
            {
                $title_name = $wiz_member['wm_ename'];
            }
            else if($wiz_member['wm_name'])
            {
                $title_name = $wiz_member['wm_name'];
            }

            if($request['table_code'] == '1354')
            {
                if(!$request['ns_step'] || !$request['ns_lesson'])
                {
                    $return_array['res_code'] = '0400';
                    $return_array['msg'] = "NS과정을 선택해주세요.";
                    echo json_encode($return_array);
                    exit;
                }
    
                // if(!isset($_FILES["files"]))
                // {
                //     $return_array['res_code'] = '0900';
                //     $return_array['msg'] = "프로세스오류";
                //     $return_array['data']['err_code'] = "0318";
                //     $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 첨부파일을 필수로 업로드해주셔야 합니다.";
                //     echo json_encode($return_array);
                //     exit;
                // }

                // $upload_limit_size = 5;
                // $ext_array = array('mp3');

                // $checked_point = true;
                // $article_point = 1000;

                $book_code = 390;
                $work_state = 4;

                $title = '['.$title_name.']님의 [step '.$request['ns_step'].']의 레슨['.$request['ns_lesson'].'] 과제물입니다.';
                $sim_content2 = $request['ns_step'].'|'.$request['ns_lesson'];

            }
            else if($request['table_code'] == '1376')
            {
                if(!$request['ielts_step'])
                {
                    $return_array['res_code'] = '0400';
                    $return_array['msg'] = "IELTS 정보를 선택해주세요.";
                    echo json_encode($return_array);
                    exit;
                }
    
                // if(!isset($_FILES["files"]))
                // {
                //     $return_array['res_code'] = '0900';
                //     $return_array['msg'] = "프로세스오류";
                //     $return_array['data']['err_code'] = "0318";
                //     $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 첨부파일을 필수로 업로드해주셔야 합니다.";
                //     echo json_encode($return_array);
                //     exit;
                // }

                $upload_limit_size = 20;
                $ext_array = NULL;

                $checked_point = true;
                $article_point = 10000;

                $book_code = 387;
                $work_state = 1;
                $sim_content2 = $request['ielts_step'];

                $title =  '['.$title_name.']님의 IELTS [Step '.$request['ielts_step'].']의 챕터['.$request['ielts_chapter'].']의 레슨['.$request['ielts_lesson'].'] 과제물입니다.';
            }            
            else if($request['table_code'] == '1366')
            {
                if(!$request['ahop_step'] || !$request['ahop_book'] || !$request['ahop_chapter'] || !$request['ahop_lesson'])
                {
                    $return_array['res_code'] = '0400';
                    $return_array['msg'] = "AHOP정보를 입력해주세요.";
                    echo json_encode($return_array);
                    exit;
                }

                // if(!isset($_FILES["files"]) && trim($request['content']) == '')
                // {
                //     $return_array['res_code'] = '0900';
                //     $return_array['msg'] = "프로세스오류";
                //     $return_array['data']['err_code'] = "0318";
                //     $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 본문 내용을 입력하거나 첨부파일을 업로드해 주셔야 합니다.";
                //     echo json_encode($return_array);
                //     exit;
                // }

                $upload_limit_size = 5;
                $ext_array = NULL;

                $checked_point = false;

                $book_code = 403;
                $work_state = 4;
                $sim_content2 = $request['ahop_step'].'|'.$request['ahop_book'].'|'.$request['ahop_chapter'].'|'.$request['ahop_lesson'];
                $title = '['.$title_name.']님의 AHOP [Step '.$request['ahop_step'].']의 ['.$request['ahop_book'].']교재 챕터['.$request['ahop_chapter'].']의 레슨['.$request['ahop_lesson'].'] 과제물입니다.';
            }
            else if($request['table_code'] == '1130')
            {
                $upload_limit_size = 50;
                $ext_array = array('mp3', 'aac');

            }
            else if($request['table_code'] == '1131')
            {
            
            }
            else if($request['table_code'] == '1133')
            {
                $upload_limit_size = 50;
                $ext_array = array('mp3', 'aac');
            }
            else if($request['table_code'] == '1138')
            {

                // 자식게시물 일 때만 보드 타이틀 만듬
                if($article['mb_parent_key'])
                {
                    $parent = $this->board_mdl->row_article_solution_by_mb_unq($article['mb_parent_key']);
                    $title = $parent['mb_title'].' 의 답변';
                }

                $upload_limit_size = 50;
                $ext_array = array('mp3', 'aac');
            }
            
        }

        /*
            예외처리
            [도전]일일영작문 
        */
        if ($request['table_code'] == '1127')
        {
        
            /* 글쓰기 권한체크 */
            if(false === stripos($wiz_member['wm_assistant_code'], "*1127*"))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0308";
                $return_array['data']['err_msg'] = "게시글 수정 권한이 없습니다.";
                echo json_encode($return_array);
                exit;
            } 
      
        }

 
        /* 익명게시판 예외처리 */
        $MBN_ANONYMOUS_YN =  $this->config->item('MBN_ANONYMOUS_YN');

        /* 
            익명게시판 여부 
            - config 설정 or 날코딩
        */
        if(strtoupper($board_config['mbn_anonymous_yn']) == "Y" || in_array($request["table_code"], $MBN_ANONYMOUS_YN))
        {     
            $name_hide = "Y";
        }
        else
        {
            $name_hide = $request['name_hide'];
        }

        /* 
            에디터 사용시 본문내용 중 이미지 필수 포함 체크 
            0 : 이미지 필수 아님
            1 : 이미지 필수
        */
        if($board_config['mbn_image_required'] == "1")
        {
            /*
                html 태그내에 이미지 첨부 여부 확인
            */
            if(!preg_match('/<img[^>]*src/',$content,$matches))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0316";
                $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 내용에 이미지가 필수로 포함되어 있어야 합니다.";
                echo json_encode($return_array);
                exit;
            }
        }
        $file_name = $request['file_name'] ? $request['file_name'] : $article['mb_filename'];

        // $file_name = $article['mb_filename'];
        $json_array = json_decode($article['mb_thumb'],true);
        //s3파일 업로드
        if($request['files'])
        {
            if(strtoupper($board_config['mbn_file_yn']) != "Y")
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0301";
                $return_array['data']['err_msg'] = $board_config["mbn_table_name"]." 게시판은 파일첨부를 할 수 없습니다.";
                echo json_encode($return_array);
                exit;
            }

            
            // 기존파일 있으면 삭제
            if($article['mb_filename'])
            {
                board_delete_files($article['mb_filename'],$this->upload_path_boards,'',array('form'=>$json_array['form']));
            }

            /*
                파일 업로드 확장자 제한여부
                null : 제한없음
                null 아닐시 : 제한
            */
            if(!$upload_limit_size)
            {
                $upload_limit_size = 5;
            }
            
            if(!$ext_array)
            {
                $ext_array = array('xlsx', 'xls', 'doc', 'pdf', 'jpg', 'jpeg', 'png', 'txt', 'mp3', 'aac');
            }
            
            if($board_config['mbn_file_ext'])
            {
                $ext_array = explode(',',$board_config['mbn_file_ext']);
            }

            $res = S3::put_s3_object($this->upload_path_boards, $_FILES["files"], $upload_limit_size, $ext_array);
            
            if($res['res_code'] != '0000')
            {
                echo json_encode($res);
                exit;
            }
            
            $thumb_result = Thumbnail::create_thumbnail_formfile($request['files'],$res['file_name'],'filename',array('ori_path'=>$this->upload_path_boards));
            if($thumb_result) 
            {
                $json_array['form'] = $thumb_result;
            }
            else
            {
                unset($json_array['form']);
            }

            $file_name = $res['file_name'];

        }

        $thumbnail_content = Thumbnail::create_thumbnail_parse_content($content,$json_array,array('type'=>'editor'),$article['mb_content']);
        $content = $thumbnail_content['content'];
        if(!empty($thumbnail_content['thumbnail_info']))
        {
            $json_array['editor'] = $thumbnail_content['thumbnail_info'];
        }
        else
        {
            unset($json_array['editor']);
        }

        $article_update = array(
            'noticeYn' => $notice_yn,
            'table_code' => $table_code,
            'table_unq' => $table_unq,
            'wiz_id' => $wiz_member['wm_wiz_id'],
            'name' => $wiz_member['wm_name'],
            'ename' => $wiz_member['wm_ename'],
            'nickname' => $wiz_member['wm_nickname'],
            'title' => $title,
            'filename' => ($file_name) ? $file_name : null,
            'content' => $content,
            'sim_content' => $sim_content,
            'sim_content2' => $sim_content2,
            'secret' => $secret,
            'work_state' => $work_state,            
            'c_yn' => $c_yn,
            'cafe_unq' => $cafe_unq,
            'rsms' => $rsms,
            'name_hide' => $name_hide,
            'clip_yn' => $clip_yn,
            'category_code' => $category_code,
            'category_title' => $category_title,
            'thumb' => $json_array ? json_encode($json_array):'',
            'sim_content3' => $sim_content3,
            'sim_content4' => $sim_content4,
            'set_point' => $set_point
        );

        // 수업체험후기 얼철딕은 카테고리 변경 불가
        if($request['table_code'] =='1111' && $article['mb_category_code'] =='52')
        {
            unset($article_update['category_code']);
            unset($article_update['category_title']);
        }


        /* $mb_unq 리턴줘야됨 포인트 삽입시 필요 */
        $this->load->model('msg_mdl');
        $result = $this->board_mdl->update_article(($article_update), $request['mb_unq'], $wiz_member['wm_wiz_id']);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        // 검색테이블로 인설트
        board_insert_search_boards($request['table_code'], $request['mb_unq']);

        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "게시물을 수정했습니다.";
        echo json_encode($return_array);
        exit;
    }


    public function write_article_special()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim($this->input->post('wiz_id')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "title" => $this->input->post('title') ? $this->input->post('title'):'',
            "content" => $this->input->post('content'),
            "clip_yn" => ($this->input->post('clip_yn')) ? trim($this->input->post('clip_yn')) : "Y",
            "rsms" => ($this->input->post('rsms')) ? trim($this->input->post('rsms')) : "N",
            "tu_uid" => trim($this->input->post('tu_uid')) ? trim($this->input->post('tu_uid')):'',
            "files" => isset($_FILES["files"]) ? $_FILES["files"] : null,
            // 강사와1:1전용
            "img_files" => isset($_FILES["img_files"]) ? $_FILES["img_files"] : null,
            "etc_files" => isset($_FILES["etc_files"]) ? $_FILES["etc_files"] : null,
            "c_yn" => ($this->input->post('c_yn')) ? trim($this->input->post('c_yn')) : "Y",
            // 실시간요청전용
            "sp_gubun" => trim($this->input->post('sp_gubun')),
            "sp_time" => trim($this->input->post('sp_time')),
            "sp_files" => isset($_FILES["sp_files"]) ? $_FILES["sp_files"] : null,
            // 첨삭전용
            "kind" => trim($this->input->post('kind')) ? trim($this->input->post('kind')):'',
            "chumchk" => trim($this->input->post('chumchk')) ? trim($this->input->post('chumchk')):'',
            "mp3chk" => trim($this->input->post('mp3chk')) ? trim($this->input->post('mp3chk')):'',
            "add_mp3chk" => 'fast',
            "secret" => trim($this->input->post('secret')) ? trim($this->input->post('secret')):'',
            // 얼철딕전용
            "sc_id" => trim($this->input->post('sc_id')),
            "name_hide" => trim($this->input->post('name_hide')) ? trim($this->input->post('name_hide')):'N',
            "book_uid" => trim($this->input->post('book_uid')) ? trim($this->input->post('book_uid')):'',
            "vd_url" => trim($this->input->post('vd_url')) ? trim($this->input->post('vd_url')):'',
            "postscript" => trim($this->input->post('postscript')) ? trim($this->input->post('postscript')):'',
            //이런표헌어떻게 지식인 관련
            "parent_key" => $this->input->post('parent_key') ? $this->input->post('parent_key') : null,                 // 지식인 답글용
            "sim_content3" => $this->input->post('sim_content3') ? $this->input->post('sim_content3') : null,           // 지식인 의뢰글의 요청사항
            "sim_content4" => $this->input->post('sim_content4') ? $this->input->post('sim_content4') : null,           // 지식인 답변글의 주의사항
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

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }
        $this->load->model('point_mdl');
        $this->load->model('board_mdl');
        $this->load->model('tutor_mdl');

        /* 회원정보 */
        $this->load->model('member_mdl');
        $wm_point = $wiz_member['wm_point'];

        $article = NULL;
        $result = NULL;

        $sp_file_name = NULL;
        $img_file_name = NULL;
        $etc_file_name = NULL;
        $today = date('Y-m-d');

        if(strpos($request['table_code'],'dictation') !==false)
        {
            $request['table_code'] = 'dictation';
        }
        
        // 첨삭 글쓰기
        if($request['table_code'] == 'correction')
        {
            $correct_kind = ['A','B','C','I','D'];
            if(!in_array($request['kind'],$correct_kind))
            {
                $return_array['res_code'] = '0400';
                $return_array['msg'] = "첨삭용도를 선택해주세요.";
                echo json_encode($return_array);
                exit;
            }

            if(!$request['chumchk'] && !$request['mp3chk'])
            {
                $return_array['res_code'] = '0400';
                $return_array['msg'] = "첨삭 요청사항을 한개 이상 선택해주세요.";
                echo json_encode($return_array);
                exit;
            }

            $this->load->model('lesson_mdl');
            
            // 출석부 갯수
            $checkwhere = " AND tu_name != 'postpone' AND (lesson_state='in class' || lesson_state='finished') AND startday<='".$today."' AND endday >= '".$today."'";
            $check_valid_class_cnt = $this->lesson_mdl->check_in_class_member($wiz_member['wm_uid'],$checkwhere);
            $check_valid_class_cnt = $check_valid_class_cnt ? $check_valid_class_cnt:0;

            if(!$check_valid_class_cnt)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0313";
                $return_array['data']['err_msg'] = "권한이 없습니다.(수업 중인 회원만 접근 할 수 있습니다.)";
                echo json_encode($return_array);
                exit;
            }

            // 오늘 작성한 첨삭게시글 수
            $where = " WHERE mb.uid= ".$wiz_member['wm_uid']." AND mb.w_regdate BETWEEN '".$today." 00:00:00' AND '".$today." 23:59:59'";
            $list_count_board_wiz_correct = $this->board_mdl->list_count_board_wiz_correct($where);
            $list_count_board_wiz_correct = $list_count_board_wiz_correct ? $list_count_board_wiz_correct['cnt']:0;

            $need_point = 0;
            // 출석부 갯수만큼 무료이용가능. 이후는 5천포인트소모.
            if($list_count_board_wiz_correct >= $check_valid_class_cnt)
            {
                $need_point = 5000;
            }

            // 비공개 등록시 500포인트 소모
            if($request['secret'] =='Y')
            {
                $need_point+= 500;
            }

            if($need_point > 0)
            {
                // 현재포인트
                $cur_point = $this->point_mdl->check_current_point($wiz_member['wm_uid']);
                if($need_point > $cur_point['wm_point'])
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0328";
                    $return_array['data']['err_msg'] = "포인트가 부족합니다.";
                    echo json_encode($return_array);
                    exit;
                }
            }
            
            $tu_uid = $request['tu_uid'];
            $tu_name = '';
            if($tu_uid) 
            {
                $check_correct_tutor = $this->tutor_mdl->check_correct_tutor($tu_uid);
                
                if($check_correct_tutor)
                {
                    $tu_name = $check_correct_tutor['tu_name'];
                    // 선택된 강사가 오늘 내일 쉬는날인지 체크
                    if ($this->tutor_mdl->check_tutor_blockdate($tu_uid,date("Y-m-d"),date("Y-m-d", time() + 86400))) {
                        $return_array['res_code'] = '0900';
                        $return_array['msg'] = "프로세스오류";
                        $return_array['data']['err_code'] = "0331";
                        $return_array['data']['err_msg'] = "해당 강사님은 오늘 휴무로 선택할 수 없습니다.";
                        echo json_encode($return_array);
                        exit;
                    }
                } 
                else
                {    
                    $tu_uid = "";
                }
            }

            // 첨삭참고자료
            $file_name = '';
            if($request["files"])
            {
                $upload_limit_size = 5;
                
                $ext_array = array('jpg', 'jpeg', 'png', 'gif','pdf','doc','docx');
                
                $this->load->library('s3');            
                $res = S3::put_s3_object($this->upload_path_correct, $request["files"], $upload_limit_size, $ext_array);
                
                if($res['res_code'] != '0000')
                {
                    echo json_encode($res);
                    exit;
                }
                
                                
                $file_name = $res['file_name'];
            }

            $content = cut_content($request['content'], $request['table_code']);

            //'img' 제거 하는 부분 없앰 
            foreach (array('textarea','form','input','a') as $val) 
            {
                $content = preg_replace("/<{$val}[^>]*>/i", '', $content);
                $content = preg_replace("/<\/{$val}>/i", '', $content);
            }

            $content = preg_replace("/<iframe(.*?)<\/iframe>/is","",$content); //iframe 제거
            $content = preg_replace("/<style(.*?)<\/style>/is","",$content); //style 제거


            $where = " WHERE mb.uid= ".$wiz_member['wm_uid'];
            $su = $this->board_mdl->list_count_board_wiz_correct($where);
            $su = $su ? $su['cnt']:0;

            if($tu_uid) $hopeday = 2;
            else $hopeday = 1;

            $w_hopedate = board_calculate_date_except_holiday($hopeday);

            $insert_param = [
                'uid'       => $wiz_member['wm_uid'],
                'wiz_id'    => $wiz_member['wm_wiz_id'],
                'name'      => $wiz_member['wm_name'],
                'ename'     => $wiz_member['wm_ename'],
                'chk_tu_uid'=> $tu_uid,
                'tu_uid'    => $tu_uid,
                'tu_name'   => $tu_name,
                'w_title'   => $request['title'],
                'w_kind'    => $request['kind'],
                'w_tutor'   => $check_correct_tutor ? $check_correct_tutor['tu_id']:'',
                'w_mp3'     => $request['chumchk'] ? 'Y':'N',
                'w_mp3_type'=> $request['mp3chk'] ? $request['add_mp3chk']:'',
                'w_memo'    => $content,
                'w_step'    => '1',
                'w_secret'  => $request['secret'] ? $request['secret']:"N",
                'w_regdate' => date('Y-m-d H:i:s'),
                'w_hopedate'=> $w_hopedate,
                'filename2' => $file_name,
                'pwd'       => '',
                'rsms'      => $request['rsms'],
                'su'        => $su+1,
                'clip_yn'   => $request['clip_yn'],
                'w_hope_text'=> ''
            ];
            $result = $this->board_mdl->insert_correct($insert_param);

            if($result)
            {
                // 글 등록후 에디터에 올려진 이미지(삭제 준비중인 이미지) 삭제
                $matches = common_find_s3_src_from_content($request['content']);
                if(count($matches[1])> 0){
                    foreach($matches[1] as $match)
                    {
                        $this->board_mdl->delete_board_edit_files($match);
                    }
                }

                // 출석부 갯수만큼 무료이용가능. 이후는 5천포인트소모.
                if($list_count_board_wiz_correct >= $check_valid_class_cnt)
                {
                    $point = array(
                        'uid' => $wiz_member['wm_uid'],
                        'name' => $wiz_member['wm_name'],
                        'point' => -5000,
                        'pt_name'=> '['.date("Y-m-d").'] 영어첨삭게시판 글작성', 
                        'kind'=> '1', 
                        'regdate' => date("Y-m-d H:i:s")
                    );
    
                    /* 포인트 내역 입력 및 포인트 추가 */
                    $this->point_mdl->set_wiz_point($point);
                }

                // 비공개 등록시 500포인트 소모
                if($request['secret'] =='Y')
                {
                    $point = array(
                        'uid' => $wiz_member['wm_uid'],
                        'name' => $wiz_member['wm_name'],
                        'point' => -500,
                        'pt_name'=> '[영어첨삭게시판] 비공개 등록 500 포인트 차감', 
                        'kind'=> '1', 
                        'regdate' => date("Y-m-d H:i:s")
                    );
    
                    /* 포인트 내역 입력 및 포인트 추가 */
                    $this->point_mdl->set_wiz_point($point);
                }
            }
            else
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }

        }
        elseif($request['table_code'] == 'express')
        {
            $valid_config = [
                'parent_key'=>$request['parent_key'],
            ];
            
            $err_code = board_check_valid_write_page($request['table_code'], $wiz_member, '', $valid_config);
    
            if(!empty($err_code) && $err_code['err_code'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data'] = $err_code;
                echo json_encode($return_array);
                exit;
            }

            $m_name = NULL;
            if($wiz_member['wm_nickname'])
            {
                $m_name = $wiz_member['wm_nickname'];
            }
            else if($wiz_member['wm_ename'])
            {
                $m_name = $wiz_member['wm_ename'];
            }
            else if($wiz_member['wm_name'])
            {
                $m_name = $wiz_member['wm_name'];
            }
            
            $article = array(
                'subject' => $request['title'],
                'content' => cut_content($request['content']),
                'wiz_id' => $wiz_member['wm_wiz_id'],
                'm_name' => $m_name,
                'regdate' => date("Y-m-d H:i:s"),
                'clip_yn' => $request['clip_yn'],
                'rsms' => $request['rsms'],
                'parent_key' => $request['parent_key'],
                'sim_content3' => $request['sim_content3'],
                'sim_content4' => $request['sim_content4'],
            );

            $result = $this->board_mdl->write_article_express($article);

            if($result > 0){
                // 글 등록후 에디터에 올려진 이미지(삭제 준비중인 이미지) 삭제
                $matches = common_find_s3_src_from_content($request['content']);
                if(count($matches[1])> 0){
                    foreach($matches[1] as $match)
                    {
                        $this->board_mdl->delete_board_edit_files($match);
                    }
                }

            }

            if($request['parent_key'])
            {
                $anonymous_name = "익명";
                $display_name = "";

                if($wiz_member["wm_nickname"])
                {
                    $display_name = $wiz_member["wm_nickname"];
                }
                else
                {
                    $display_name = ($wiz_member['wm_ename']) ? $wiz_member['wm_ename'] : $wiz_member['wm_name'];
                }
                
                $parent = $this->board_mdl->row_article_express_by_mb_uid($request['parent_key']);
                /* 게시글 작성자, 댓글작성자 차단목록 확인*/
                $checked_blcok_list = $this->board_mdl->checked_block_list($wiz_member['wm_uid'], $parent['wm_uid']);

                // 알림톡 발송
                if(!$checked_blcok_list && $parent['mb_rsms'] =='Y' && $wiz_member['wm_uid'] != $parent['wm_uid'] )
                {
                    $board_link = board_make_viwe_link($request["table_code"], $parent['mb_uid']);

                    $options = array(
                        'name'  =>  $parent['wm_name'],
                        'board_name'  =>  '이런표현어떻게',
                        'url'   =>  $board_link
                    );

                    sms::send_atalk($parent['wm_mobile'], 'MINT06004N', $options);
                }

                /* 차단목록에 없다면 알림 내가 쓴글에 내가 댓글 달때 제외*/
                if(!$checked_blcok_list && $wiz_member['wm_uid'] != $parent['wm_uid'])
                {
                    $this->load->model('notify_mdl');

                    /* 게시글 작성자 알림*/
                    $notify = array(
                        'uid' => $parent['wm_uid'], 
                        'code' => 102, 
                        'message' => '작성하신 게시글에 '.$anonymous_name.'님의 답글이 등록되었습니다.', 
                        'table_code' => 'express.view', 
                        'user_name' => $display_name,
                        'board_name' => $parent['mbn_table_name'], 
                        'content'=> $parent['mb_title'], 
                        'mb_unq' => $parent['mb_uid'], 
                        'co_unq' => NULL,
                        'parent_key' => $request['parent_key'],
                        'regdate' => date('Y-m-d H:i:s'),
                    );

                    $notify_result = $this->notify_mdl->insert_notify($notify);

                    if($notify_result < 0)
                    {
                        $return_array['res_code'] = '0500';
                        $return_array['msg'] = "DB ERROR";
                        echo json_encode($return_array);
                        exit;
                    }
                }
            }
        }
        else if($request['table_code'] == 'toteacher')
        {
            if(!$request['tu_uid'])
            {
                $return_array['res_code'] = '0400';
                $return_array['msg'] = "tu_uid를 입력해주세요.";
                echo json_encode($return_array);
                exit;
            }

            $this->load->model('tutor_mdl');
            $tutor = $this->tutor_mdl->get_tu_name_by_tu_uid($request['tu_uid']);

            if(!$tutor)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0323";
                $return_array['data']['err_msg'] = "일치하는 선생님 정보가 없습니다.";
                echo json_encode($return_array);
                exit;
            }

            //이미지 파일 업로드
            if(isset($_FILES["img_files"]))
            {
                
                /*
                    파일 업로드 확장자 제한여부
                    null : 제한없음
                    null 아닐시 : 제한
                */
                $upload_limit_size = 5;
                
                $ext_array = array('jpg', 'jpeg', 'png', 'gif');
                
                $this->load->library('s3');            
                $res = S3::put_s3_object($this->upload_path_toteacher, $request["img_files"], $upload_limit_size, $ext_array);
                
                if($res['res_code'] != '0000')
                {
                    echo json_encode($res);
                    exit;
                }
                
                $img_file_name = $res['file_name'];
                $img_file_name_origin = $res['file_name_origin'];
            }

            //이미지 파일 업로드
            if(isset($_FILES["etc_files"]))
            {
                /*
                    파일 업로드 확장자 제한여부
                    null : 제한없음
                    null 아닐시 : 제한
                */
                $upload_limit_size = 5;
                
                $ext_array = array('pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'mp3');
                
                $this->load->library('s3');            
                $res = S3::put_s3_object($this->upload_path_toteacher, $request["etc_files"], $upload_limit_size, $ext_array);
                
                if($res['res_code'] != '0000')
                {
                    echo json_encode($res);
                    exit;
                }
                
                $etc_file_name = $res['file_name'];
            }

            $article = array(
                'title' => $request['title'],
                'memo' => cut_content($request['content']),
                'uid' => $wiz_member['wm_uid'],
                'wiz_id' => $wiz_member['wm_wiz_id'],
                'name' => $wiz_member['wm_name'],
                'ename' => $wiz_member['wm_ename'],
                'tu_uid' => $request['tu_uid'],
                'tu_name' => $tutor['tu_name'],
                'c_yn' => 'y',
                'to_gubun' => 'S',
                'filename' => $img_file_name,
                'filename2' => $etc_file_name,
                'regdate' => date("Y-m-d H:i:s"),
                'rsms' => $request['rsms'],
            );


            $result = $this->board_mdl->write_article_toteacher(($article));

            if($result > 0){
                // 글 등록후 에디터에 올려진 이미지(삭제 준비중인 이미지) 삭제
                $matches = common_find_s3_src_from_content($request['content']);
                if(count($matches[1])> 0){
                    foreach($matches[1] as $match)
                    {
                        $this->board_mdl->delete_board_edit_files($match);
                    }
                }
            }

        }
        else if($request['table_code'] == 'request')
        {
            if(!$request['sp_gubun'])
            {
                $return_array['res_code'] = '0400';
                $return_array['msg'] = "분류를 선택해주세요.";
                echo json_encode($return_array);
                exit;
            }

            //이미지 파일 업로드
            if(isset($_FILES["sp_files"]))
            {
                /*
                    파일 업로드 확장자 제한여부
                    null : 제한없음
                    null 아닐시 : 제한
                */
                $upload_limit_size = 5;
                
                $ext_array = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'mp3');
                
                $this->load->library('s3');            
                $res = S3::put_s3_object($this->upload_path_qna, $request["sp_files"], $upload_limit_size, $ext_array);
                
                if($res['res_code'] != '0000')
                {
                    echo json_encode($res);
                    exit;
                }
                
                $sp_file_name = $res['file_name'];
            }

            $article = array(
                'sp_title' => $request['title'],
                'sp_memo' => cut_content($request['content']),
                'uid' => $wiz_member['wm_uid'],
                'wiz_id' => $wiz_member['wm_wiz_id'],
                'name' => $wiz_member['wm_name'],
                'sp_gubun' => $request['sp_gubun'],
                'sp_regdate' => date("Y-m-d H:i:s"),
                'sp_time' => $request['sp_time'],
                'ip' => $_SERVER['REMOTE_ADDR'],
                'filename' => $sp_file_name,
                'editor_yn' => 'y',
            );

            $result = $this->board_mdl->write_article_request(($article));

            if($result > 0){
                // 글 등록후 에디터에 올려진 이미지(삭제 준비중인 이미지) 삭제
                $matches = common_find_s3_src_from_content($request['content']);
                if(count($matches[1])> 0){
                    foreach($matches[1] as $match)
                    {
                        $this->board_mdl->delete_board_edit_files($match);
                    }
                }
            }
        }
        else if($request['table_code'] =='dictation')
        {
            if(!$request['book_uid'])
            {
                $return_array['res_code'] = '0400';
                $return_array['msg'] = "교재를 선택해 주세요.";
                echo json_encode($return_array);
                exit;
            }
            $board_config = $this->board_mdl->row_board_special_config_by_table_code(9002);
            $err_code = board_check_valid_write_page($request['table_code'],$wiz_member, $board_config, array('sc_id'=>$request['sc_id']));

            if(!empty($err_code) && $err_code['err_code'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data'] = $err_code;
                echo json_encode($return_array);
                exit;
            }
            
            $schedule_info = $err_code['schedule'];    // wiz_schedule 데이터

            if(!$schedule_info)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR!";
            }

            switch ($schedule_info['lesson_gubun']){
                case 'M':
                case 'T':
                    $b_kind = 'T';
                    break;
                case 'V': 
                case 'E':
                    $b_kind = 'V';
                    break;
                case 'B':
                    $b_kind = 'T';
                    break;
            }

            // 화상,민트라이브 인경우 mp3 OR 동영상링크 줄중 하나 들어와야한다.
            if($b_kind == 'V' && (strpos($request['vd_url'],'iframe') === false && !$request["files"]))
            {
                $return_array['res_code'] = '0400';
                $return_array['msg'] = "mp3파일을 업로드 혹은 iframe태그를 포함한 화상영어 수업녹화파일 주소를 입력해 주세요.";
                echo json_encode($return_array);
                exit;
            }

            $file_name = '';
            if($request["files"])
            {
                $upload_limit_size = 50;
                
                $ext_array = array('mp3', 'aac');
                
                $this->load->library('s3');            
                $res = S3::put_s3_object($this->upload_path_dictation, $request["files"], $upload_limit_size, $ext_array);
                
                if($res['res_code'] != '0000')
                {
                    echo json_encode($res);
                    exit;
                }
                
                $file_name = $res['file_name'];
            }

            $this->load->model('book_mdl');
            $tu_info = $this->tutor_mdl->get_tutor_info_by_tu_uid($schedule_info['tu_uid']);
            $book_info = $this->book_mdl->row_book_by_id($request['book_uid']);
            $mins = $schedule_info['cl_time'] ? $schedule_info['cl_time']:'10';

            $checkwhere = " WHERE mb.uid = ".$wiz_member['wm_uid'];
            $cafeboard_count = $this->board_mdl->list_count_board_cafeboard($checkwhere);
            $cafeboard_count = $cafeboard_count ? $cafeboard_count['cnt']:0;

            /* 얼철딕 정책 - 1,000회 초과시에는 얼철딕 참여 불가능 */
            if((int)$cafeboard_count >= 1000)
            {
                /* 
                    얼철딕정책
                    - 100회 달성시 얼철딕도우미 뱃지 지급
                    - 딕테이션해결사도우미 뱃지 있는 회원만 딕테이션해결사 참여가능
                    - 1,000회이상 참여자 얼철딕졸업 뱃지지급

                    기존 100회, 1,000회 이상 참여자 뱃지지급 여부 체크
                    - 100회 : Helper 
                    - 1,000회 : Graduation
                */
                
                /*
                //임시로 주석
                member_badge_award($wiz_member['wm_uid'], 'Dictation', 'Helper');
                member_badge_award($wiz_member['wm_uid'], 'Dictation', 'Graduation');

                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0350";
                $return_array['data']['err_msg'] = "얼굴철판딕테이션은 1,000회까지만 참여가능합니다.";
                echo json_encode($return_array);
                exit;
                */
            }
            
            $subject = $request['book_uid']."--".$schedule_info['tu_uid']."--".$cafeboard_count;

            $article = [
                'b_kind'    => $b_kind,
                'uid'       => $wiz_member['wm_uid'],
                'name'      => $wiz_member['wm_name'],
                'ename'     => $wiz_member['wm_ename'],
                'tu_name'   => $tu_info['tu_name'],
                'book_name' => $book_info['book_name'] ? $book_info['book_name']:'',
                'mins'      => $mins,
                'class_date'=> substr($schedule_info['startday'], 0, 10),
                'subject'   => $subject,
                'content'   => cut_content($request['content']),
                'postscript'=> $request['postscript'],
                'filename'  => $file_name,
                'vd_url'    => $request['vd_url'],
                'regdate'   => date('Y-m-d H:i:s'),
                'name_hide' => $request['name_hide'],
                'clip_yn'   => $request['clip_yn'],
            ];

            $dictation_count = $cafeboard_count+1;
            $result = $this->board_mdl->insert_dictation($article, $request['sc_id'],$board_config, $dictation_count);
            //config 함수에 글쓰기 데이터 줘야하네
            
            if($result)
            {

                /*
                    얼철딕정책
                    - 100회 달성시 얼철딕도우미 뱃지 지급
                    - 딕테이션해결사도우미 뱃지 있는 회원만 딕테이션해결사 참여가능
                    - 1,000회이상 참여자 얼철딕졸업 뱃지지급

                    얼철딕 100회, 1,000회이상 참여자 뱃지지급 체크 및 지급
                */
                if((int)$dictation_count >= 100)
                {
                    member_badge_award($wiz_member['wm_uid'], 'Dictation', 'Helper');
                }
                
                if((int)$dictation_count >= 1000)
                {
                    member_badge_award($wiz_member['wm_uid'], 'Dictation', 'Graduation');
                }

                /*
                    얼철딕정책 
                    - 비공개로 얼철딕 등록 시 48시간 이내만 수정 가능하며, 50% 포인트 지급
                    - 100회이하 참여시 기존포인트의 10%만 지급 비공개시 5%포인트 지급
                */
                $add_point = (int)$mins * ((int)($board_config['mbn_cafe_point']/10));

                if($request['name_hide'] == 'Y')
                {
                    $add_point = 0.5 * $add_point;
                }

                $point = array(
                    'uid' => $wiz_member['wm_uid'],
                    'name' => $wiz_member['wm_name'],
                    'point' => $add_point,
                    'pt_name'=> '얼철딕으로 '.$add_point.'포인트 적립', 
                    'kind'=> 't', 
                    'b_kind'=> 'cafe_board', 
                    'co_unq'=> $result, 
                    'showYn'=> 'y', 
                    'regdate' => date("Y-m-d H:i:s")
                );
    
                /* 포인트 내역 입력 및 포인트 추가 */
                $this->point_mdl->set_wiz_point($point);

                if($dictation_count % (int)$board_config['mbn_cafe_count'] == 0)
                {
                    $return_array['res_code'] = '0000';
                    $return_array['msg'] = "얼철딕 ".($dictation_count)."회 등록. 얼철딕 후기를 작성한뒤 계속 진행 가능합니다";
                    $return_array['data']['href'] = '/#/board-write?tc=1111&cafe=true';
                    $return_array['data']['mb_unq'] = $result;
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
        $cur_point = $this->point_mdl->check_current_point($wiz_member['wm_uid']);

        
        /*
            실시간 요청 게시판, 강사와 1:1 게시판은 검색테이블 인설트 제외
        */

        /* 특수게시판 테이블 코드 변환 */
        if($request['table_code'] == 'express')		//이런표현어떻게
        {
            $table_code = "9001";
        }
        else if($request['table_code'] == 'dictation')	//얼철딕
        {
            $table_code = "9002";
        }
        else if($request['table_code'] == 'correction')		//영어첨삭게시판
        {
            $table_code = "9004";
        }
        else
        {
            $table_code = $request['table_code'];
        }
        
        // 특수게시판 검색테이블로 인설트
        if($table_code == '9001' || $table_code == '9002' || $table_code == '9004')
        {
            board_insert_search_boards($table_code, $result);
        }

        //퀘스트
        $q_idx='';
        if($request['table_code'] !='request' && $request['table_code'] != 'toteacher') $q_idx = '12';
        if($request['table_code'] =='express') $q_idx.= '_124';

        if($request['table_code'] == 'toteacher') $q_idx.= '_19';
        elseif($request['table_code'] == 'dictation') $q_idx.= '_39_56'; //얼굴철판 딕테이션
        elseif($request['table_code'] == 'express' && !$request['parent_key']) $q_idx.= '_44';
        elseif($request['table_code'] == 'correction') $q_idx .= '_46_160'; //영어첨삭
        
        MintQuest::request_batch_quest($q_idx, $result.MintQuest::make_quest_subfix($table_code));

        $return_array['data']['wm_point'] = $cur_point['wm_point'];
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "게시물을 등록했습니다.";
        $return_array['data']['mb_unq'] = $result;
        echo json_encode($return_array);
        exit;

    }
    

    public function delete_article_special()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "mb_unq" => trim($this->input->post('mb_unq')),
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

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        /* 회원정보 */
        $this->load->model('member_mdl');
        $wm_point = $wiz_member['wm_point'];


        $article = NULL;
        $article_comment = NULL;
    
        if(strpos($request['table_code'],'dictation') !==false)
        {
            $request['table_code'] = 'dictation';
        }

        /* 게시판정보 */
        $this->load->model('board_mdl');

        if($request['table_code'] == 'express')
        {
            $article = $this->board_mdl->row_article_express_by_mb_uid($request['mb_unq']);
        }
        else if($request['table_code'] == 'toteacher')
        {
            $article = $this->board_mdl->row_article_toteacher_by_to_id($request['mb_unq']);
        }
        else if($request['table_code'] == 'request')
        {
            $article = $this->board_mdl->row_article_request_by_sp_id($request['mb_unq']);
        }
        else if($request['table_code'] == 'correction')
        {
            $article = $this->board_mdl->row_article_wiz_correct_by_w_id($request['mb_unq']);
        }
        else if($request['table_code'] == 'dictation')
        {
            $article = $this->board_mdl->row_article_cafeboard_by_c_uid($request['mb_unq']);
        }


        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($request['table_code'] == 'express')
        {
            $article_comment = $this->board_mdl->checked_article_express_comment_by_e_id($request['mb_unq']);
        }
        else if($request['table_code'] == 'toteacher')
        {
            $article_comment = $article['mb_replydate'] != '0000-00-00 00:00:00';
        }
        else if($request['table_code'] == 'request')
        {
            $article_comment = $article['mb_replydate'] != '0000-00-00 00:00:00';
        }
        else if($request['table_code'] == 'dictation')
        {
            $article_comment = $this->board_mdl->list_count_mint_cafeboard_com($request['mb_unq']);
            $article_comment = $article_comment ? $article_comment['cnt']:0;
        }
        
        if($article_comment)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0320";
            $return_array['data']['err_msg'] =  "답변, 댓글이 있는 글은 삭제 할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article['mb_wiz_id'] != $request['wiz_id'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0321";
            $return_array['data']['err_msg'] = "게시물 삭제 권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($request['table_code'] == 'express' && $article['mb_recom'] > 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0322";
            $return_array['data']['err_msg'] = "추천이 있는 게시물은 삭제할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($request['table_code'] == 'express')
        {
            // 답변글이라면 채택 후 삭제 불가
            if($article['mb_parent_key'])
            {
                $check_adopt = $this->board_mdl->checked_article_adopt_without_type(9001, $article['mb_parent_key'], $request['mb_unq']);   

                if($check_adopt)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0347";
                    $return_array['data']['err_msg'] =  "이미 채택된 답변은 수정/삭제할 수 없습니다.\r
                    답변에 부적절한 내용이 포함된 경우에는 고객센터>실시간요청게시판으로 문의해 주세요.";
                    echo json_encode($return_array);
                    exit;
                }
                
            }
            else
            {
                // 질문글이라면 게시글에 답변이 달렸는지 체크
                $article_solve = $this->board_mdl->checked_knowledge_article_has_anwser_express($request['mb_unq']);

                if($article_solve)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0348";
                    $return_array['data']['err_msg'] =  "게시글에 이미 답변이 등록되어 수정/삭제할 수 없습니다.\r
                    답변 채택 또는 부적절한 답변이 등록된 경우 고객센터>실시간요청게시판으로 문의해 주세요.";
                    echo json_encode($return_array);
                    exit;
                }
            }
        }
        

        if($request['table_code'] == 'correction')
        {
            if($article['mb_w_step'] !='1')
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0330";
                $return_array['data']['err_msg'] = "진행중 혹은 답변완료된 글은 삭제하실 수 없습니다";
                echo json_encode($return_array);
                exit;
            }


            // 본문 내용중 img src 이미지 경로 찾아서 삭제
            $matches = common_find_s3_src_from_content($article['mb_content']);
            if(count($matches[1])> 0){
                foreach($matches[1] as $match)
                {
                    $file_info = array(
                        "file_name" => '',
                        "file_link" => $match,
                        "file_status"=> 1,
                        'regdate' => date("Y-m-d H:i:s"),
                    );
                    $this->board_mdl->insert_board_edit_files($file_info);
                }
            }
            
            if($article['mb_student_upfile'])
            {
                S3::delete_s3_object($this->upload_path_correct, $article['mb_student_upfile']);
            }

            $result = $this->board_mdl->delete_correct($request['mb_unq']);
            $article_result = $wm_point;
        }
        else if($request['table_code'] == 'express')
        {
            // 본문 내용중 img src 이미지 경로 찾아서 삭제
            $matches = common_find_s3_src_from_content($article['mb_title']);
            if(count($matches[1])> 0){
                foreach($matches[1] as $match)
                {
                    $file_info = array(
                        "file_name" => '',
                        "file_link" => $match,
                        "file_status"=> 1,
                        'regdate' => date("Y-m-d H:i:s"),
                    );
                    $this->board_mdl->insert_board_edit_files($file_info);
                }
            }
            
            $result = $this->board_mdl->delete_article_express($article['mb_uid'], $wiz_member['wm_wiz_id']);
            $article_result = $wm_point;
        }
        else if($request['table_code'] == 'toteacher')
        {

            // 본문 내용중 img src 이미지 경로 찾아서 삭제
            $matches = common_find_s3_src_from_content($article['mb_content']);
            if(count($matches[1])> 0){
                foreach($matches[1] as $match)
                {
                    $file_info = array(
                        "file_name" => '',
                        "file_link" => $match,
                        "file_status"=> 1,
                        'regdate' => date("Y-m-d H:i:s"),
                    );
                    $this->board_mdl->insert_board_edit_files($file_info);
                }
            }
            
            // 파일삭제
            if($article['mb_filename'])
            {
                S3::delete_s3_object($this->upload_path_toteacher, $article['mb_filename']);
            }
            if($article['mb_filename2'])
            {
                S3::delete_s3_object($this->upload_path_toteacher, $article['mb_filename2']);
            }
            if($article['mb_filename3'])
            {
                S3::delete_s3_object($this->upload_path_toteacher, $article['mb_filename3']);
            }
            if($article['mb_filename4'])
            {
                S3::delete_s3_object($this->upload_path_toteacher, $article['mb_filename4']);
            }

            $result = $this->board_mdl->delete_article_toteacher($article['mb_to_id'], $wiz_member['wm_wiz_id']);
            $article_result = $wm_point;
        }
        else if($request['table_code'] == 'request')
        {

            // 본문 내용중 img src 이미지 경로 찾아서 삭제
            $matches = common_find_s3_src_from_content($article['mb_content']);
            if(count($matches[1])> 0){
                foreach($matches[1] as $match)
                {
                    $file_info = array(
                        "file_name" => '',
                        "file_link" => $match,
                        "file_status"=> 1,
                        'regdate' => date("Y-m-d H:i:s"),
                    );
                    $this->board_mdl->insert_board_edit_files($file_info);
                }
            }
            
            // 파일삭제
            if($article['mb_filename'])
            {
                S3::delete_s3_object($this->upload_path_qna, $article['mb_filename']);
            }

            $result = $this->board_mdl->delete_article_request($article['mb_sp_id'], $wiz_member['wm_wiz_id']);
            $article_result = $wm_point;
        }
        else if($request['table_code'] == 'dictation')
        {
            // 24시간 지나기 전 수정가능.
            if(time() > strtotime('+1 day',strtotime($article['mb_regdate'])))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0342";
                $return_array['data']['err_msg'] = "글삭제는 24시간 이내에만 가능합니다.";
                echo json_encode($return_array);
                exit;
            }

            $count_solve = $this->board_mdl->list_count_board_solve('1138', $wiz_member['wm_wiz_id'], $request['mb_unq']);
            
            // 딕테이션 해결사가 등록된 글은 삭제불가
            if($count_solve['cnt'] > 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0349";
                $return_array['data']['err_msg'] = "딕테이션 해결사가 등록된 얼철딕은 삭제 할 수 없습니다.";
                echo json_encode($return_array);
                exit;
            }

            // 파일삭제
            if($article['mb_filename'])
            {
                S3::delete_s3_object($this->upload_path_dictation, $article['mb_filename']);
            }

            $this->load->model('point_mdl');
            $result = $this->board_mdl->delete_dictation($request['mb_unq']);
            if($result)
            {
                $where = [
                    'uid'       => $wiz_member['wm_uid'],
                    'co_unq'    => $request['mb_unq'],
                    'kind'      => 't',
                ];
                
                $this->point_mdl->delete_wiz_point($where);
            }

            $cur_point = $this->point_mdl->check_current_point($wiz_member['wm_uid']);
            $article_result = $cur_point['wm_point'];
        }
        
        
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        

        // 특수게시판 테이블 코드 변환
        if($request['table_code'] == 'express')		//이런표현어떻게
        {
            $table_code = "9001";
        }
        else if($request['table_code'] == 'dictation')	//얼철딕
        {
            $table_code = "9002";
        }
        else if($request['table_code'] == 'correction')		//영어첨삭게시판
        {
            $table_code = "9004";
        }
        else
        {
            $table_code = $request['table_code'];
        }


        // 검색 테이블에서 삭제
        board_delete_search_boards($table_code, $request['mb_unq'], $request['wiz_id']);

        //퀘스트 진행 취소
        $q_idx = '';
        if($request['table_code'] !='request' && $request['table_code'] != 'toteacher') $q_idx = '12';
        if($request['table_code'] == 'express') $q_idx.= '_124';

        if($request['table_code'] == 'toteacher') $q_idx.= '_19';
        elseif($request['table_code'] == 'dictation') $q_idx.= '_39_56'; //얼굴철판 딕테이션
        elseif($request['table_code'] == 'express' && !$request['parent_key']) $q_idx.= '_44';
        elseif($request['table_code'] == 'correction') $q_idx .= '_46_160'; //영어첨삭

        MintQuest::request_batch_quest_decrement($q_idx, $request['mb_unq'].MintQuest::make_quest_subfix($request['table_code']));

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "게시물을 삭제했습니다.";
        $return_array['data']['wm_point'] = $article_result;
        echo json_encode($return_array);
        exit;
    
    }


    public function modify_article_special()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim(strtolower($this->input->post('table_code'))),
            "mb_unq" => trim($this->input->post('mb_unq')),
            "title" => trim($this->input->post('title')) ? trim($this->input->post('title')):'',
            "content" => $this->input->post('content'),
            "clip_yn" => ($this->input->post('clip_yn')) ? trim($this->input->post('clip_yn')) : "Y",
            "rsms" => ($this->input->post('rsms')) ? trim($this->input->post('rsms')) : "N",  //express,correction
            "tu_uid" => trim($this->input->post('tu_uid')) ? trim($this->input->post('tu_uid')) : '0',
            "img_files" => isset($_FILES["img_files"]) ? $_FILES["img_files"] : null,
            "etc_files" => isset($_FILES["etc_files"]) ? $_FILES["etc_files"] : null,
            "c_yn" => ($this->input->post('c_yn')) ? trim($this->input->post('c_yn')) : "y", //toteacher
            "sp_gubun" => trim($this->input->post('sp_gubun')),
            "sp_time" => trim($this->input->post('sp_time')),
            "tu_gubun" => trim($this->input->post('tu_gubun')),     // T 라면 강사의 1:1에 답변달기
            "sp_files" => isset($_FILES["sp_files"]) ? $_FILES["sp_files"] : null,

            "kind" => trim($this->input->post('kind')) ? trim($this->input->post('kind')):'',
            "chumchk" => trim($this->input->post('chumchk')) ? trim($this->input->post('chumchk')):'',
            "mp3chk" => trim($this->input->post('mp3chk')) ? trim($this->input->post('mp3chk')):'',
            "add_mp3chk" => 'fast',
            "secret" => trim($this->input->post('secret')) ? trim($this->input->post('secret')):'',
            "files" => isset($_FILES["files"]) ? $_FILES["files"] : null,

            "book_uid" => trim($this->input->post('book_uid')) ? trim($this->input->post('book_uid')):'',
            "name_hide" => trim($this->input->post('name_hide')) ? trim($this->input->post('name_hide')):'N',
            "vd_url" => trim($this->input->post('vd_url')) ? trim($this->input->post('vd_url')):'',
            "postscript" => trim($this->input->post('postscript')) ? trim($this->input->post('postscript')):'',
            //이런표헌어떻게 지식인 관련
            "sim_content3" => $this->input->post('sim_content3') ? $this->input->post('sim_content3') : null,           // 지식인 의뢰글의 요청사항
            "sim_content4" => $this->input->post('sim_content4') ? $this->input->post('sim_content4') : null,           // 지식인 답변글의 주의사항
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

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        /* 회원정보 */
        $this->load->model('member_mdl');
        $wm_point = $wiz_member['wm_point'];

        $this->load->model('board_mdl');
        $article = NULL;
        $article_comment = NULL;

        $img_file_name = NULL;
        $etc_file_name = NULL;
        $sp_file_name = NULL;
        
        if(strpos($request['table_code'],'dictation') !==false)
        {
            $request['table_code'] = 'dictation';
        }

        if($request['table_code'] == 'express')
        {
            $article = $this->board_mdl->row_article_express_by_mb_uid($request['mb_unq']);
        }
        else if($request['table_code'] == 'toteacher')
        {
            $article = $this->board_mdl->row_article_toteacher_by_to_id($request['mb_unq']);
        }
        else if($request['table_code'] == 'request')
        {
            $article = $this->board_mdl->row_article_request_by_sp_id($request['mb_unq']);
        }
        else if($request['table_code'] == 'correction')
        {
            $article = $this->board_mdl->row_article_wiz_correct_by_w_id($request['mb_unq']);
        }
        else if($request['table_code'] == 'dictation')
        {
            $article = $this->board_mdl->row_article_cafeboard_by_c_uid($request['mb_unq']);
        }
        
        if(!$article)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0304";
            $return_array['data']['err_msg'] = "게시물이 존재하지 않습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($request['table_code'] == 'express')
        {
            $article_comment = $this->board_mdl->checked_article_express_comment_by_e_id($request['mb_unq']);
        }
        else if($request['table_code'] == 'toteacher')
        {
            $article_comment = $article['mb_replydate'] != '0000-00-00 00:00:00';
        }
        else if($request['table_code'] == 'request')
        {
            $article_comment = $article['mb_replydate'] != '0000-00-00 00:00:00';
        }
        
        if($article_comment)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0320";
            $return_array['data']['err_msg'] =  "답변, 댓글이 있는 글은 수정 할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($article['mb_wiz_id'] != $request['wiz_id'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0321";
            $return_array['data']['err_msg'] = "게시물 수정 권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($request['table_code'] == 'express' && $article['mb_recom'] > 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0322";
            $return_array['data']['err_msg'] = "추천이 있는 게시물은 수정할 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        if($request['table_code'] == 'express')
        {
            // 답변글이라면 채택 후 삭제 불가
            if($article['mb_parent_key'])
            {
                $check_adopt = $this->board_mdl->checked_article_adopt_without_type(9001, $article['mb_parent_key'], $request['mb_unq']);   

                if($check_adopt)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0347";
                    $return_array['data']['err_msg'] =  "이미 채택된 답변은 수정/삭제할 수 없습니다.\r
                    답변에 부적절한 내용이 포함된 경우에는 고객센터>실시간요청게시판으로 문의해 주세요.";
                    echo json_encode($return_array);
                    exit;
                }
                
            }
            else
            {
                // 질문글이라면 게시글에 답변이 달렸는지 체크
                $article_solve = $this->board_mdl->checked_knowledge_article_has_anwser_express($request['mb_unq']);

                if($article_solve)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0348";
                    $return_array['data']['err_msg'] =  "게시글에 이미 답변이 등록되어 수정/삭제할 수 없습니다.\r
                    답변 채택 또는 부적절한 답변이 등록된 경우 고객센터>실시간요청게시판으로 문의해 주세요.";
                    echo json_encode($return_array);
                    exit;
                }
            }
        }


        $result = NULL;

        // 첨삭 글쓰기
        if($request['table_code'] == 'correction')
        {
            if($article['mb_w_step'] !='1')
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0330";
                $return_array['data']['err_msg'] = "진행중 혹은 답변완료된 글은 수정하실 수 없습니다";
                echo json_encode($return_array);
                exit;
            }

            $correct_kind = ['A','B','C','I','D'];
            if(!in_array($request['kind'],$correct_kind))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0330";
                $return_array['data']['err_msg'] = "첨삭용도를 선택해주세요.";
                echo json_encode($return_array);
                exit;
            }

            if(!$request['chumchk'] && !$request['mp3chk'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0329";
                $return_array['data']['err_msg'] = "첨삭 요청사항을 한개 이상 선택해주세요.";
                echo json_encode($return_array);
                exit;
            }

            $this->load->model('tutor_mdl');
            $this->load->model('point_mdl');

            $need_point = 0;

            // 비공개 등록시 500포인트 소모
            if($article['mb_w_secret'] =='N' && $request['secret'] =='Y')
            {
                $need_point+= 500;
            }

            if($need_point > 0)
            {
                // 현재포인트
                $cur_point = $this->point_mdl->check_current_point($wiz_member['wm_uid']);
                if($need_point > $cur_point['wm_point'])
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0328";
                    $return_array['data']['err_msg'] = "포인트가 부족합니다.";
                    echo json_encode($return_array);
                    exit;
                }
            }
            
            $tu_uid = $request['tu_uid'];
            $tu_name = '';
            if($tu_uid) 
            {
                $check_correct_tutor = $this->tutor_mdl->check_correct_tutor($tu_uid);
                
                if($check_correct_tutor)
                {
                    $tu_name = $check_correct_tutor['tu_name'];
                    // 선택된 강사가 오늘 내일 쉬는날인지 체크
                    if ($this->tutor_mdl->check_tutor_blockdate($tu_uid,date("Y-m-d"),date("Y-m-d", time() + 86400))) {
                        $return_array['res_code'] = '0900';
                        $return_array['msg'] = "프로세스오류";
                        $return_array['data']['err_code'] = "0331";
                        $return_array['data']['err_msg'] = "해당 강사님을 선택할 수 없습니다.";
                        echo json_encode($return_array);
                        exit;
                    }
                } 
                else
                {    
                    $tu_uid = "";
                }
            }

            // 첨삭참고자료
            $file_name = '';
            if($request["files"])
            {
                if($article['mb_student_upfile'])
                {
                    S3::delete_s3_object($this->upload_path_correct, $article['mb_student_upfile']);
                }
                
                $upload_limit_size = 5;
                
                $ext_array = array('jpg', 'jpeg', 'png', 'gif','pdf','doc','docx');
                
                $this->load->library('s3');            
                $res = S3::put_s3_object($this->upload_path_correct, $request["files"], $upload_limit_size, $ext_array);
                
                if($res['res_code'] != '0000')
                {
                    echo json_encode($res);
                    exit;
                }
                
                $file_name = $res['file_name'];
            }

            $content = cut_content($request['content']);
            // 태그제거
            foreach (array('textarea','form','input','a') as $val) 
            {
                $content = preg_replace("/<{$val}[^>]*>/i", '', $content);
                $content = preg_replace("/<\/{$val}>/i", '', $content);
            }
            $content = preg_replace("/<iframe(.*?)<\/iframe>/is","",$content); //iframe 제거
            $content = preg_replace("/<style(.*?)<\/style>/is","",$content); //style 제거

            if($tu_uid) $hopeday = 2;
            else $hopeday = 1;

            $w_hopedate = board_calculate_date_except_holiday($hopeday);

            $w_mp3 = $request['chumchk'] ? 'Y':'N';
            $w_mp3_type = $request['mp3chk'] ? $request['add_mp3chk']:'';

            $update_param = [
                'chk_tu_uid'=> $tu_uid,
                'tu_uid'    => $tu_uid,
                'tu_name'   => $tu_name,
                'w_title'   => $request['title'],
                'w_kind'    => $request['kind'],
                'w_tutor'   => $check_correct_tutor ? $check_correct_tutor['tu_id']:'',
                'w_mp3'     => $w_mp3,
                'w_mp3_type'=> $w_mp3_type,
                'w_memo'    => $content,
                'w_secret'  => $request['secret'] ? $request['secret']:"N", 
                'w_regdate' => ($article['mb_w_mp3'] != $w_mp3 || $article['mb_w_mp3_type'] != $w_mp3_type || $article['mb_tu_uid'] != $tu_uid) ? date('Y-m-d H:i:s') : $article['mb_regdate'], 
                'w_hopedate'=> $w_hopedate,
                'filename2' => $file_name,
                'rsms'      => $request['rsms'],
                'clip_yn'   => $request['clip_yn'],
            ];

            $result = $this->board_mdl->update_correct($update_param,$request['mb_unq']);

            if($result)
            {
                // 글 등록후 에디터에 올려진 이미지(삭제 준비중인 이미지) 삭제
                $matches = common_find_s3_src_from_content($request['content']);
                if(count($matches[1])> 0){
                    foreach($matches[1] as $match)
                    {
                        $this->board_mdl->delete_board_edit_files($match);
                    }
                }
                
                // 공개->비공개로 수정시 500포인트 소모
                if($article['mb_w_secret'] =='N' && $request['secret'] =='Y')
                {
                    $point = array(
                        'uid' => $wiz_member['wm_uid'],
                        'name' => $wiz_member['wm_name'],
                        'point' => -500,
                        'pt_name'=> '[영어첨삭게시판] 비공개 등록 500 포인트 차감', 
                        'kind'=> '1', 
                        'regdate' => date("Y-m-d H:i:s")
                    );
    
                    /* 포인트 내역 입력 및 포인트 추가 */
                    $this->point_mdl->set_wiz_point($point);
                }

                $article = [
                    'w_id'          => $request['mb_unq'],
                    'b_chk_tu_uid'  => $article['mb_chk_tu_uid'],
                    'a_chk_tu_uid'  => $tu_uid,
                    'b_tu_uid'      => $article['mb_tu_uid'],
                    'a_tu_uid'      => $tu_uid,
                    'b_deadline'    => $article['mb_hopedate'],
                    'a_deadline'    => $w_hopedate,
                    'b_mp3'         => $article['mb_w_mp3'].'|'.$article['mb_w_mp3_type'],
                    'a_mp3'         => $w_mp3 .'|'. $w_mp3_type,
                    'memo'          => 'Student Modify',
                    'regdate'       => date('Y-m-d H:i:s'), 
                ];
                $this->board_mdl->insert_correct_log($article);
            }
            else
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }

        }
        elseif($request['table_code'] == 'express')
        {
            $m_name = NULL;
            if($wiz_member['wm_nickname'])
            {
                $m_name = $wiz_member['wm_nickname'];
            }
            else if($wiz_member['wm_ename'])
            {
                $m_name = $wiz_member['wm_ename'];
            }
            else if($wiz_member['wm_name'])
            {
                $m_name = $wiz_member['wm_name'];
            }

            $article = array(
                'subject' => $request['title'],
                'content' => cut_content($request['content']),
                'wiz_id' => $wiz_member['wm_wiz_id'],
                'm_name' => $m_name,
                'clip_yn' => $request['clip_yn'],
                'rsms' => $request['rsms'],
                'sim_content3' => $request['sim_content3'],
                'sim_content4' => $request['sim_content4'],
            );

            $result = $this->board_mdl->update_article_express($article, $request['mb_unq'], $wiz_member['wm_wiz_id']);
            $article_result = $wm_point;

            if($result > 0){
                // 글 등록후 에디터에 올려진 이미지(삭제 준비중인 이미지) 삭제
                $matches = common_find_s3_src_from_content($request['content']);
                if(count($matches[1])> 0){
                    foreach($matches[1] as $match)
                    {
                        $this->board_mdl->delete_board_edit_files($match);
                    }
                }
            }
        }
        else if($request['table_code'] == 'toteacher')
        {
            if($request['to_gubun'] =='T')
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0321";
                $return_array['data']['err_msg'] = "게시물 수정 권한이 없습니다.(2)";
                echo json_encode($return_array);
                exit;
            }

            if(!$request['tu_uid'])
            {
                $return_array['res_code'] = '0400';
                $return_array['msg'] = "tu_uid를 입력해주세요.";
                echo json_encode($return_array);
                exit;
            }

            $this->load->model('tutor_mdl');
            $tutor = $this->tutor_mdl->get_tu_name_by_tu_uid($request['tu_uid']);

            if(!$tutor)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0323";
                $return_array['data']['err_msg'] = "일치하는 선생님 정보가 없습니다.";
                echo json_encode($return_array);
                exit;
            }
            
            if($request['tu_gubun'] =='T')
            {
                $img_file_name = $article['mb_filename3'];
                $etc_file_name = $article['mb_filename4'];
            }
            else
            {
                $img_file_name = $article['mb_filename'];
                $etc_file_name = $article['mb_filename2'];
            }
            
            //이미지 파일 업로드
            if(isset($_FILES["img_files"]))
            {
                if($img_file_name)
                {
                    S3::delete_s3_object($this->upload_path_toteacher, $img_file_name);
                }
                /*
                    파일 업로드 확장자 제한여부
                    null : 제한없음
                    null 아닐시 : 제한
                */
                $upload_limit_size = 5;
                
                $ext_array = array('jpg', 'jpeg', 'png', 'gif');
                
                $this->load->library('s3');            
                $res = S3::put_s3_object($this->upload_path_toteacher, $request["img_files"], $upload_limit_size, $ext_array);
                
                if($res['res_code'] != '0000')
                {
                    echo json_encode($res);
                    exit;
                }
                
                $img_file_name = $res['file_name'];
            }

            //이미지 파일 업로드
            if(isset($_FILES["etc_files"]))
            {
                if($etc_file_name)
                {
                    S3::delete_s3_object($this->upload_path_toteacher, $etc_file_name);
                }
                /*
                    파일 업로드 확장자 제한여부
                    null : 제한없음
                    null 아닐시 : 제한
                */
                $upload_limit_size = 5;
                
                $ext_array = array('pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'mp3');
                
                $this->load->library('s3');            
                $res = S3::put_s3_object($this->upload_path_toteacher, $request["etc_files"], $upload_limit_size, $ext_array);
                
                if($res['res_code'] != '0000')
                {
                    echo json_encode($res);
                    exit;
                }
                
                $etc_file_name = $res['file_name'];
            }

            if($request['tu_gubun'] =='T')
            {
                $article = array(
                    'step' => 'Y',
                    'reply' => cut_content($request['content']),
                    'r_yn' => 'y',
                    'replydate' => date('Y-m-d H:i:s'),
                    'filename3' => $img_file_name,
                    'filename4' => $etc_file_name,
                );
            }
            else
            {
                $article = array(
                    'title' => $request['title'],
                    'memo' => cut_content($request['content']),
                    'uid' => $wiz_member['wm_uid'],
                    'wiz_id' => $wiz_member['wm_wiz_id'],
                    'name' => $wiz_member['wm_name'],
                    'ename' => $wiz_member['wm_ename'],
                    'tu_uid' => $request['tu_uid'],
                    'tu_name' => $tutor['tu_name'],
                    'filename' => $img_file_name,
                    'filename2' => $etc_file_name,
                    'rsms' => $request['rsms'],
                );
            }
            

            $result = $this->board_mdl->update_article_toteacher(($article), $request['mb_unq'], $request['wiz_id']);
            $article_result = $wm_point;

            if($result > 0){

                // 글 등록후 에디터에 올려진 이미지(삭제 준비중인 이미지) 삭제
                $matches = common_find_s3_src_from_content($request['content']);
                if(count($matches[1])> 0){
                    foreach($matches[1] as $match)
                    {
                        $this->board_mdl->delete_board_edit_files($match);
                    }
                }
            }

        }
        else if($request['table_code'] == 'request')
        {
            if(!$request['sp_gubun'])
            {
                $return_array['res_code'] = '0400';
                $return_array['msg'] = "분류를 선택해주세요.";
                echo json_encode($return_array);
                exit;
            }

            $sp_file_name = $article['mb_filename'];

            //이미지 파일 업로드
            if(isset($_FILES["sp_files"]))
            {
                
                /*
                    파일 업로드 확장자 제한여부
                    null : 제한없음
                    null 아닐시 : 제한
                */
                $upload_limit_size = 5;
                if($article['mb_filename'])
                {
                    S3::delete_s3_object($this->upload_path_qna, $article['mb_filename']);
                }

                $ext_array = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'mp3');
                
                $this->load->library('s3');            
                $res = S3::put_s3_object($this->upload_path_qna, $request["sp_files"], $upload_limit_size, $ext_array);
                
                if($res['res_code'] != '0000')
                {
                    echo json_encode($res);
                    exit;
                }
                
                $sp_file_name = $res['file_name'];
            }

            $article = array(
                'sp_title' => $request['title'],
                'sp_memo' => cut_content($request['content']),
                'uid' => $wiz_member['wm_uid'],
                'wiz_id' => $wiz_member['wm_wiz_id'],
                'name' => $wiz_member['wm_name'],
                'sp_gubun' => $request['sp_gubun'],
                'sp_time' => $request['sp_time'],
                'ip' => $_SERVER['REMOTE_ADDR'],
                'filename' => $sp_file_name,
            );

            $result = $this->board_mdl->update_article_request(($article), $request['mb_unq'], $request['wiz_id']);
            $article_result = $wm_point;

            if($result > 0){

                // 글 등록후 에디터에 올려진 이미지(삭제 준비중인 이미지) 삭제
                $matches = common_find_s3_src_from_content($request['content']);
                if(count($matches[1])> 0){
                    foreach($matches[1] as $match)
                    {
                        $this->board_mdl->delete_board_edit_files($match);
                    }
                }
            }
        }
        else if($request['table_code'] =='dictation')
        {
            // 48시간 지나기 전 수정가능.
            if(time() > strtotime('+2 day',strtotime($article['mb_regdate'])))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0341";
                $return_array['data']['err_msg'] = "글수정은 48시간 이내에만 수정 가능합니다.";
                echo json_encode($return_array);
                exit;
            }

            if(!$request['book_uid'])
            {
                $return_array['res_code'] = '0400';
                $return_array['msg'] = "교재를 선택해 주세요.";
                echo json_encode($return_array);
                exit;
            }

            // 화상,민트라이브 인경우 mp3 OR 동영상링크 줄중 하나 들어와야한다.
            if($article['mb_b_kind'] == 'V' && (strpos($request['vd_url'],'iframe') === false && !$article['mb_filename'] && !$request["files"]))
            {
                $return_array['res_code'] = '0400';
                $return_array['msg'] = "mp3파일을 업로드 혹은 iframe태그를 포함한 화상영어 수업녹화파일 주소를 입력해 주세요.";
                echo json_encode($return_array);
                exit;
            }

            $file_name = $article['mb_filename'];
            if($request["files"])
            {
                if($article['mb_filename'])
                {
                    S3::delete_s3_object($this->upload_path_dictation, $article['mb_filename']);
                }

                $upload_limit_size = 50;
                
                $ext_array = array('mp3', 'aac');
                
                $this->load->library('s3');            
                $res = S3::put_s3_object($this->upload_path_dictation, $request["files"], $upload_limit_size, $ext_array);
                
                if($res['res_code'] != '0000')
                {
                    echo json_encode($res);
                    exit;
                }
                
                $file_name = $res['file_name'];
            }
            else{
                // 화상수업이 파일업로드 했다가 링크로 변경할 경우 기존 파일 있으면 삭제
                if($article['mb_b_kind'] == 'V' && strpos($request['vd_url'],'iframe') !== false && $article['mb_filename'])
                {
                    S3::delete_s3_object($this->upload_path_dictation, $article['mb_filename']);
                    $file_name = '';
                }
            }

            $this->load->model('book_mdl');

            $subject = $article['mb_title'];
            $subject = explode('--',$subject);
            $book_info = $this->book_mdl->row_book_by_id($request['book_uid']);
            $subject = $request['book_uid']."--".$subject[1]."--".$subject[2];
            
            $insert_param = [
                'content'   => cut_content($request['content']),
                'postscript'=> $request['postscript'],
                'book_name' => $book_info['book_name'] ? $book_info['book_name']:'',
                'filename'  => $file_name,
                'subject'   => $subject,
                'vd_url'    => $request['vd_url'],
                'name_hide' => $request['name_hide'],
                'clip_yn'   => $request['clip_yn'],
            ];

            $result = $this->board_mdl->update_dictation($insert_param,$request['mb_unq']);
            

            if($result && $request['name_hide'] != $article['mb_name_hide'])
            {
                $this->load->model('point_mdl');

                $registerd_point = $this->point_mdl->checked_point_by_co_unq($wiz_member['wm_uid'],array($request['mb_unq']));
                if($registerd_point)
                {
                    // name_hide 변경 시 포인트 지급 변경
                    if($request['name_hide'] =='N')
                    {
                        $modi_point = (int)$registerd_point['point'] / 0.5;
                    }
                    else
                    {
                        $modi_point = (int)$registerd_point['point'] * 0.5;
                    }
                }
                
                $ptname = '얼철딕으로 '.$modi_point.'포인트 적립 변경';
                $param = [
                    'point'     => $modi_point,
                    'pt_name'   => $ptname
                ];
                $where = [
                    'uid'       => $wiz_member['wm_uid'],
                    'co_unq'    => $request['mb_unq'],
                    'kind'      => 't',
                ];
                
                $this->point_mdl->update_wiz_point($param,$where);
                
                $return_array['res_code'] = '0000';
                $return_array['msg'] = "게시물을 수정했습니다.(".$ptname.")";
                echo json_encode($return_array);
                exit;
            }
            
        }

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        
        /* 특수게시판 테이블 코드 변환 */
        if($request['table_code'] == 'express')		//이런표현어떻게
        {
            $table_code = "9001";
        }
        else if($request['table_code'] == 'dictation')	//얼철딕
        {
            $table_code = "9002";
        }
        else if($request['table_code'] == 'correction')		//영어첨삭게시판
        {
            $table_code = "9004";
        }
        else
        {
            $table_code = $request['table_code'];
        }

        // 특수게시판 검색테이블로 수정
        if($table_code == '9001' || $table_code == '9002' || $table_code == '9004')
        {
            board_insert_search_boards($table_code, $request['mb_unq']);
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "게시물을 수정했습니다.";
        echo json_encode($return_array);
        exit;
    
    }

    /* 실시간 요청게시판 분류값 */
    public function list_select_wiz_speak_sub_()
    {
        $return_array = array();

        $request = array(
            "code" => $this->input->post('table_code'),
        );


        $this->load->model('board_mdl');

        $where = " WHERE length(code)='2' AND use_yn = 'y' ORDER BY sort ";

        if($request['code'])
        {   
            $length = strlen($request['code']) + 2;
            $where = " WHERE length(code)='".$length."' AND code LIKE '".$request['code']."%'  AND use_yn = 'y' ORDER BY sort ";
        }

        $list = $this->board_mdl->list_select_wiz_speak_sub($where);
        $result = board_list_faq_mb_unq($list);
        
        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "목록조회성공";
            $return_array['data']['list'] = $result;
            echo json_encode($return_array);
            exit;
        }
    }
    public function put_editor_images()
    {
        $upload_limit_size = NULL;
        $ext_array = NULL;

        //s3파일 업로드
        if(isset($_FILES["file"]))
        {
            if(!$upload_limit_size)
            {
                $upload_limit_size = 5;
            }
            if(!$ext_array)
            {
                $ext_array = array('xlsx', 'xls', 'doc', 'pdf', 'jpg', 'jpeg', 'png', 'txt', 'bmp');
            }

            $this->load->library('s3');            
            $res = S3::put_s3_object($this->upload_path_summernote, $_FILES["file"], $upload_limit_size, $ext_array);
            
            // 인설트 데이터
            if($res['res_code']=='0000')
            {
                //  edit_files 데이터 추가
                $file_info = array(
                    "file_name" => $res['file_name'],
                    "file_link" => $res['url'],
                    "file_status"=> 1,
                    'regdate' => date("Y-m-d H:i:s"),
                );
    
                $this->load->model('board_mdl');
                $result = $this->board_mdl->insert_board_edit_files($file_info);
    
                if($result < 0)
                {
                    $return_array['res_code'] = '0500';
                    $return_array['msg'] = "DB ERROR";
                    echo json_encode($return_array);
                    exit;
                }
            }

            echo json_encode($res);
            exit;

        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0903";
            $return_array['data']['err_msg'] = "업로드 할 이미지가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

    }


    // 댓글 공지 설정
    public function update_comment_notice()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "co_unq" => trim($this->input->post('co_unq')),
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

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        if(strpos($wiz_member['wm_assistant_code'], '*comment*') === false)
        {
            $return_array['res_code'] = '0308';
            $return_array['msg'] = '댓글공지 권한이 없습니다.';
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('board_mdl');
        $result = $this->board_mdl->row_article_comment_by_co_unq($request['co_unq']);

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        // 1:공지, 2:일반
        $notice_yn = $result['mbc_notice_yn'] == '2' ? '1':'2';

        $result = $this->board_mdl->update_comment_notice($request['co_unq'],$notice_yn);
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "업데이트 성공";
        echo json_encode($return_array);
        exit;
    }

    public function search_boards_list_()
    {
        $this->load->model('board_mdl');
        $list_mint_boards = $this->board_mdl->list_mint_boards_name();
        $special_list_boards = array(
            [
                 'table_code' => 'express',
                 'table_name' => '이런표현어떻게',
            ],
            /* [
                'table_code' => 'dictation.list',
                'table_name' => '얼굴철판딕테이션',
            ],
            [
                'table_code' => 'correction',
                'table_name' => '영어첨삭',
            ], */
        );

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "조회 성공";
        $return_array['data']['list'] = array_merge($special_list_boards,$list_mint_boards);
        echo json_encode($return_array);
        exit;
    }


    
    // 블라인드 설정
    public function set_blind_article()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "table_code" => trim($this->input->post('table_code')),
            "mb_unq" => trim($this->input->post('mb_unq')) ? trim($this->input->post('mb_unq')):0,
            "co_unq" => trim($this->input->post('co_unq')) ? trim($this->input->post('co_unq')):0,
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

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('board_mdl');

        $config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);
        $article = $this->board_mdl->row_article_by_mb_unq($request['table_code'], $request['mb_unq']);

        if($article)
        {
            //검색테이블 업데이트(조회수 증감에 따른)
            $search_params = array(
                'mb_unq' => $request['mb_unq'],
                'hit'    => $article['mb_hit'],
                'recom'  => $article['mb_recom']
            );
            $this->board_mdl->update_search_boards($request['table_code'], $search_params);
        }

        // 글 블라인드 요청 할수있는 상태인지
        if(!$request['co_unq'] && 
        ($article['mb_daum_img'] =='H' || $article['mb_wiz_id'] == $wiz_member['wm_wiz_id'] || $article['mb_noticeYn'] == 'Y' || $article['mb_noticeYn'] == 'A'
        || $config['mbn_write_yn'] == 'n' || $config['mbn_declaration'] != 'Y')
        )
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0321";
            $return_array['data']['err_msg'] = "블라인드 요청 할수 없는 상태입니다.(1)";
            echo json_encode($return_array);
            exit;
        }

        // 댓글 블라인드 요청 할수있는 상태인지
        if($request['co_unq'])
        {
            $article_comment = $this->board_mdl->row_article_comment_by_co_unq($request['co_unq']);

            if(!$article_comment || $article['mb_daum_img'] =='H' || $article_comment['mbc_wiz_id'] == $wiz_member['wm_wiz_id'] 
            || $article_comment['mbc_tu_uid']=='99999' || $article_comment['mbc_notice_yn'] =='1' || $config['mbn_declaration_co'] != 'Y'
            )
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0321";
                $return_array['data']['err_msg'] = "블라인드 요청 할수 없는 상태입니다.(2)";
                echo json_encode($return_array);
                exit;
            }
            
        }

        $cnt = $this->board_mdl->check_cnt_today_blind($wiz_member['wm_uid']);

        if($cnt >= 3)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0319";
            $return_array['data']['err_msg'] = "블라인드는 하루 최대 3회 까지 가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('lesson_mdl');
        //  수업중만 블라인드 가능. 153: 첨삭, 158: 장기연기
        $checkwhere = " AND schedule_ok='Y' AND lesson_list_view='Y' AND tu_uid NOT IN (153, 158) AND endday >= '".date("Y-m-d")."' LIMIT 1";
        $check_valid_class_member = $this->lesson_mdl->check_in_class_member($wiz_member['wm_uid'],$checkwhere);

        if(!$check_valid_class_member)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0322";
            $return_array['data']['err_msg'] = "블라인드 요청은 수강중인 회원만 이용 가능합니다.";
            echo json_encode($return_array);
            exit;
        }
    
        $check_blind = $this->board_mdl->check_already_blinded($request['table_code'],$wiz_member['wm_uid'],$request['mb_unq'],$request['co_unq']);
        
        if(!$check_blind) {
            $params = [
                'uid'           => $wiz_member['wm_uid'],
                'table_code'    => $request['table_code'],
                'mb_unq'        => $request['mb_unq'],
                'co_unq'        => $request['co_unq'],
                'reason_text'   => $wiz_member['wm_wiz_id'],
            ];
            
            $result = $this->board_mdl->insert_boards_hide($params);
            if($result < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0320";
            $return_array['data']['err_msg'] = "이미 블라인드 요청되었습니다.";
            echo json_encode($return_array);
            exit;
        }


        //10회 이상 등록시 숨기기 처리
        $result = $this->board_mdl->check_count_blind($request['table_code'],$request['mb_unq'],$request['co_unq']);

        $this->load->model('member_mdl');
        $target_wiz_member = $this->member_mdl->get_wm_uid_by_wiz_id($article['mb_wiz_id']);
            
        if($result['cnt'] >= 10){

            $params = [
                'table_code' => $request['table_code'],
                'mb_unq' => $request['mb_unq'],
                'co_unq' => $request['co_unq'],
                'target_uid' => ($request['co_unq'] > 0) ? $article_comment['wm_uid'] : $target_wiz_member['wm_uid'],   // 포인트 회수후 포인트 갱신할 uid
            ];

            //댓글 부분 블라인드 처리
            if($request['co_unq'] > 0){
                
                // 구민트에서 1033-[개발팀]회원 삭제요청 게시물 모음, 1356-	MINT ENGLISH CHAT 게시판은 제외된다고 한다..
                if($request['table_code'] != "1356" && $request['table_code'] != "1033"){ 

                    $result = $this->board_mdl->set_blind($params,'comment');
                   
                    // 댓글 작성자에게 추가할 알림 파라메터
                    $notify = array(
                        'uid' => $article_comment['wm_uid'], 
                        'code' => '501', 
                        'message' => '작성하신 게시글이 블라인드 처리되었습니다.', 
                        'table_code' => $request['table_code'], 
                        'user_name' => $wiz_member['wm_name'],
                        'board_name' => $config['mbn_table_name'],
                        'content'=> $article_comment['mbc_comment'], 
                        'mb_unq' => $article_comment['mb_unq'], 
                        'co_unq' => $request['co_unq'],
                        'regdate' => date('Y-m-d H:i:s'),
                    );

                    /* 블라인드 처리된 글쓴이 알림 */
                    $this->load->model('notify_mdl');
                    $notify_result = $this->notify_mdl->insert_notify($notify);

                    if($notify_result < 0)
                    {
                        $return_array['res_code'] = '0500';
                        $return_array['msg'] = "DB ERROR";
                        echo json_encode($return_array);
                        exit;
                    }
                }

            }else{
                
                $result = $this->board_mdl->set_blind($params);
                if($result)
                {
                    //검색테이블 블라인드처리
                    $this->board_mdl->set_search_blind($params);
                }
                   


                // 게시물 작성자에게 추가할 알림 파라메터
                $notify = array(
                    'uid' => $target_wiz_member['wm_uid'], 
                    'code' => '501', 
                    'message' => '작성하신 게시글이 블라인드 처리되었습니다.', 
                    'table_code' => $request['table_code'], 
                    'user_name' => $target_wiz_member['wm_name'], 
                    'board_name' => $config['mbn_table_name'],
                    'content'=> $article['mb_content'], 
                    'mb_unq' => $article['mb_unq'], 
                    'co_unq' => '',
                    'regdate' => date('Y-m-d H:i:s'),
                );
                
                /* 블라인드 처리된 글쓴이 알림 */
                $this->load->model('notify_mdl');
                $notify_result = $this->notify_mdl->insert_notify($notify);

                if($notify_result < 0)
                {
                    $return_array['res_code'] = '0500';
                    $return_array['msg'] = "DB ERROR";
                    echo json_encode($return_array);
                    exit;
                }
            }
            
            if($result < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "블라인드 요청되었습니다";
        echo json_encode($return_array);
        exit;
    }

    /*
        딕테이션 해결사 채택 업데이트 API
        채택후 포인트 지급
    */
    public function update_select_star()
    {
        $return_array = array();
        
        $request = array(
            "table_code" => $this->input->post('table_code') ? $this->input->post('table_code') : NULL,
            "wiz_id" => trim($this->input->post('wiz_id')),                         
            "authorization" => trim($this->input->post('authorization')),
            "mb_unq" => trim($this->input->post('mb_unq')),                                         // 의뢰글의 mb_unq
            "sim_content3" => $this->input->post('sim_content3'),                                   // 답변자에게 남길 코멘트 메시지
            "cl_time" => $this->input->post('cl_time') ? $this->input->post('cl_time') : NULL,      // 얼철딕 수업 시간
            "select_wiz_id" => trim($this->input->post('select_wiz_id')),                           // 도우미 유저의 wiz_id
            "select_key" => trim(strtolower($this->input->post('select_key'))),                     // 도우미 게시물의 mb_unq
            "star" => $this->input->post('star') ? $this->input->post('star') : NULL,               // 도우미 게시물의 별점
            "set_point" => $this->input->post('set_point') ? $this->input->post('set_point') : 0,   // 도우미 게시물의 지급 포인트
            "is_app" => (strtolower($this->input->post('is_app')) == "pc") ? "N" : "Y",   // pc, mobile, app
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

        $wiz_member = base_get_wiz_member();

        // 게시물 조회
        $this->load->model('board_mdl');
        $board = $this->board_mdl->row_article_solution_by_mb_unq($request['mb_unq']);

        if($board['mb_select_key'] != NULL)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0346";
            $return_array['data']['err_msg'] = "이미 채택한 게시물입니다.";
            echo json_encode($return_array);
            exit;
        }

        $datas = array(
            'wiz_id' => $request['wiz_id'],
            'mb_unq' => $request['mb_unq'],
            'select_key' => $request['select_key'],
            'star' => $request['star'],
            'sim_content3' => $request['sim_content3'],
        );

        $result = $this->board_mdl->update_select_star($datas);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        

        /* 채택 유저 정보 */
        $this->load->model('member_mdl');
        $select_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['select_wiz_id']);
        
        /*
            알림시 표기되는 이름 추천회원 닉네임
            우선순위 1. 닉네임 , 2. 영어이름 3. 한글이름
        */
        $display_name = "";
        if($select_member["wm_nickname"])
        {
            $display_name = $select_member["wm_nickname"];
        }
        else
        {
            $display_name = ($select_member['wm_ename']) ? $select_member['wm_ename'] : $select_member['wm_name'];
        }

        /* 게시판설정 확인 */
        $board_config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);

        
        /* 포인트 추가 */
        // 의뢰자가 선택한 포인트의 85%만 지급
        $set_point = ($request['set_point']) * 0.8;


        $pt_name = $board_config['mbn_table_name'].'의 '.$board['wm_nickname'].' 님으로부터 채택받았습니다.'.$set_point.' 포인트 선물 적립';

        $point = array(
            'uid' => $select_member['wm_uid'],
            'name' => $select_member['wm_name'],
            'point' => $set_point,
            'pt_name'=> $pt_name, 
            'kind'=> 'x',                           // x 는 게시물로 얻은 포인트
            'b_kind'=> 'boards',
            'table_code'=> $request['table_code'],
            'co_unq'=> $request['select_key'], 
            'showYn'=> 'y',
            'secret'=> 'N',
            'regdate' => date("Y-m-d H:i:s")
        );

        /* 포인트 내역 입력 및 포인트 추가 */
        $this->load->model('point_mdl');
        $result_point = $this->point_mdl->set_wiz_point($point);

        if($result_point < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        /* 
            알림, 카카오 알림톡 추가
            게시글 작성자, 댓글작성자 차단목록 확인
        */
        
        /* 자식보드(알림톡 보낼 게시물) */
        $board_target = $this->board_mdl->row_article_solution_by_mb_unq($request['select_key']);

        $checked_blcok_list = $this->board_mdl->checked_block_list($wiz_member['wm_uid'], $board_target['wm_uid']);

        // 알림톡 발송
        /* if(!$checked_blcok_list && $board_target['mb_rsms'] =='Y' && $wiz_member['wm_uid'] != $board_target['wm_uid'] )
        {

            $board_link = "http://board.mint05.com/?t=".$request["table_code"]."&m=".$request['mb_unq'];
            $options = array(
                'uid'       => $board_target['wm_uid'],
                'wiz_id'    => $board_target['mb_wiz_id'],
                'name'      => $board_target['wm_name'],
                'board_name'=> $board_target['mbn_table_name'],
                'nickname'  => ($wiz_member['wm_nickname']) ? $wiz_member['wm_nickname'] : $wiz_member['wm_name'],
                'board_link'=> $board_link
            );

            sms::send_atalk($board_target['wm_mobile'], 'MINT06002S', $options);
        } */

        /* 차단목록에 없다면 알림 내가 쓴글에 내가 댓글 달때 제외*/
        if(!$checked_blcok_list && $wiz_member['wm_uid'] != $board_target['wm_uid'])
        {

            $this->load->model('notify_mdl');
            //$dealer = $this->notify_mdl->get_dealer_sms_by_wm_uid($wiz_member['wm_uid']);
            
            /* 게시글 작성자 알림*/
            $notify = array(
                'uid' => $board_target['wm_uid'], 
                'code' => 102, 
                'message' => '작성하신 게시글에 '.$wiz_member['wm_nickname'].' 님으로부터 채택받았습니다.', 
                'table_code' => $request['table_code'], 
                'user_name' => $board_target['name'],
                'board_name' => $board_target['mbn_table_name'], 
                'content'=> $board_target['mb_content'], 
                'mb_unq' => $board_target['mb_mb_unq'], 
                'co_unq' => NULL,
                'regdate' => date('Y-m-d H:i:s'),
            );

            $notify_result = $this->notify_mdl->insert_notify($notify);

            if($notify_result < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }
        }

        /*
            sim_content3 으로 댓글 내용 등록
        */

        $comment = array(
            'mb_unq' => $board_target['mb_mb_unq'],
            'writer_id' => $wiz_member['wm_wiz_id'],
            'writer_name' => $wiz_member['wm_name'],
            'writer_ename' => $wiz_member['wm_ename'],
            'writer_nickname' => $wiz_member['wm_nickname'],
            'comment' => $request['sim_content3'],
            'table_code' => $request['table_code'],
            'notice_yn' => 2,
            'mob' => $request['is_app'],
            'co_thread' => "A",
            'regdate' => date("Y-m-d H:i:s"),
        );

        $comment_result = $this->board_mdl->insert_comment($comment);
        

        if($comment_result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }


        $return_array['res_code'] = '0000';
        $return_array['data']['comment_mb_unq'] = $board_target['mb_mb_unq'];
        $return_array['msg'] = "딕테이션 해결사를 채택하였습니다.";
        echo json_encode($return_array);
        exit;
    }

    /*
        지식인 게시판 채택
    */
    public function adopt_anwser()
    {
        $return_array = array();
        
        $request = array(
            "table_code" => $this->input->post('table_code') ? $this->input->post('table_code') : NULL,
            "wiz_id" => trim($this->input->post('wiz_id')),                         
            "authorization" => trim($this->input->post('authorization')),
            "mb_unq" => trim($this->input->post('mb_unq')),                                         // 의뢰글의 mb_unq
            "sim_content3" => $this->input->post('sim_content3'),                                   // 답변자에게 남길 코멘트 메시지
            "select_key" => trim(strtolower($this->input->post('select_key'))),                     // 도우미 게시물의 mb_unq
            "star" => $this->input->post('star') ? $this->input->post('star') : NULL,               // 도우미 게시물의 별점
            "is_app" => (strtolower($this->input->post('is_app')) == "pc") ? "N" : "Y",   // pc, mobile, app
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

        $wiz_member = base_get_wiz_member();

        $this->load->model('board_mdl');

        $selected_content = '';
        // 질문글, 답변글 불러오기
        if($request['table_code'] =='express')
        {
            $request['table_code'] = '9001';
            //질문자 글
            $article = $this->board_mdl->row_article_express_by_mb_uid($request['mb_unq']);

            /* 자식보드. 채택자 게시물 */
            $board_target = $this->board_mdl->row_article_express_by_mb_uid($request['select_key']);
            $a_name = $board_target['mb_m_name'];

            $selected_content = $board_target['mb_title'];

            $board_config = $this->board_mdl->row_board_special_config_by_table_code($request['table_code']);
        }
        else
        {
            //질문자 글
            $article = $this->board_mdl->row_article_by_mb_unq($request['table_code'], $request['mb_unq']);
            if($article)
            {
                //검색테이블 업데이트(조회수 증감에 따른)
                $search_params = array(
                    'mb_unq' => $request['mb_unq'],
                    'hit'    => $article['mb_hit'],
                    'recom'  => $article['mb_recom']
                );
                $this->board_mdl->update_search_boards($request['table_code'], $search_params);
            }

            /* 자식보드. 채택자 게시물 */
            $board_target = $this->board_mdl->row_article_solution_by_mb_unq($request['select_key']);
            $a_name = $board_target['wm_name'];

            $selected_content = $board_target['mb_content'];
            
            /* 게시판설정 확인 */
            $board_config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);
        }

        if($article['mb_regdate'] <='2021-03-17 10:15:00' && $request['table_code'] !='1138')
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0356";
            $return_array['data']['err_msg'] = "해당 게시물은 채택 불가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        // 질문자가 아니면 팅겨낸다.
        if($article['mb_wiz_id'] != $request['wiz_id'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0318";
            $return_array['data']['err_msg'] = "권한이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        //채택된 답변글 있는지 체크
        $adopt = $this->board_mdl->checked_article_adopt($request['table_code'], $request['mb_unq'], $request['select_key']);

        if($adopt)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0346";
            $return_array['data']['err_msg'] = "이미 채택한 게시물입니다.";
            echo json_encode($return_array);
            exit;
        }

        $limit_over = null;
        //user_adopt_limit 설정되어있으면 채택수가 채택횟수제한에 걸리지 않았는지 체크
        if($board_config['mbn_user_adopt_limit'])
        {
            $limit_over = $this->board_mdl->checked_anwser_article_adopt_limit_over($request['table_code'], $request['mb_unq'], 1, $board_config['mbn_user_adopt_limit']);
        }

        if($limit_over)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0355";
            $return_array['data']['err_msg'] = "채택 할 수있는 최대 갯수는 ".$board_config['mbn_user_adopt_limit'].'개입니다.';
            echo json_encode($return_array);
            exit;
        }

        /* 채택 유저 정보 */
        $select_member = $this->member_mdl->get_wiz_member_by_wiz_id($article['mb_wiz_id']);

        $datas = array(
            'selected_uid'    => $board_target['wm_uid'],
            //'wiz_id' => $request['wiz_id'],
            'mb_unq' => $request['mb_unq'],
            'select_key' => $request['select_key'],
            'table_code' => $request['table_code'],
            'star' => $request['star'],
            'adopt_type' => 1,      // 1:질문자채택, 2:시스템채택
            'sim_content3' => $request['sim_content3'],
        );

        $result = $this->board_mdl->knowledge_adopt_article($datas);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        /*
            딕테이션 해결사 답변 100회 이상 채택인 유저 뱃지 추가
        */
        $badge_award_message = null;
        $dictation_badge = $this->member_mdl->get_badge('dictation', 'solution');
        $member_dictation_badge = $this->member_mdl->get_member_badge($board_target['wm_uid'], $dictation_badge['wb_id']);
        
        if($dictation_badge && !$member_dictation_badge){
            
            //딕테이션 채택을 100회 받았는지 체크/ 뱃지 지급
            $adopt_count = $this->board_mdl->list_count_adopt_by_uid($board_target['wm_uid'], '1138');
            
            $badge_award_message = $dictation_badge['wb_award_message'];

            if($adopt_count['cnt'] >= 50){

                $datas_badge = array(
                    "uid" => $board_target['wm_uid'],
                    "badge_id" => $dictation_badge['wb_id'],
                    "use_yn"=> 'N',
                    'regdate' => date("Y-m-d H:i:s"),
                );
                
                $this->load->model('badge_mdl');
                $result_badge = $this->badge_mdl->insert_badge_message($datas_badge, $badge_award_message);

            }
        }
        
        /*
            알림시 표기되는 이름 추천회원 닉네임
            우선순위 1. 닉네임 , 2. 영어이름 3. 한글이름
        */
        $display_name = "";
        if($select_member["wm_nickname"])
        {
            $display_name = $select_member["wm_nickname"];
        }
        else
        {
            $display_name = ($select_member['wm_ename']) ? $select_member['wm_ename'] : $select_member['wm_name'];
        }
        
        /* 포인트 추가 */
        if($request['table_code'] =='1138')
        {
            $set_point = $article['mb_set_point'] * 0.8;
        }
        else
        {
            $set_point = $article['mbn_user_adopt_reward_point'];
        }

        if($set_point > 0)
        {
            $pt_name = $article['mbn_table_name'].'의 '.$display_name.' 님으로부터 채택받았습니다.'.$set_point.' 포인트 선물 적립';

            $pointparam = array(
                'uid' => $board_target['wm_uid'],
                'point' => $set_point,
                'name' => $a_name,
                'pt_name'=> $pt_name, 
                'kind' => 'kg',
                'b_kind' => 'boards',
                'co_unq'=> $request['select_key'], 
                'showYn'=> 'y',
                'regdate' => date("Y-m-d H:i:s")
            );
    
            /* 포인트 내역 입력 및 포인트 추가 */
            $this->load->model('point_mdl');
            $this->point_mdl->set_wiz_point($pointparam);
        }
        

        /* 
            알림
            게시글 작성자, 댓글작성자 차단목록 확인
        */
        $checked_blcok_list = $this->board_mdl->checked_block_list($wiz_member['wm_uid'], $board_target['wm_uid']);

        /* 차단목록에 없다면 알림 내가 쓴글에 내가 댓글 달때 제외*/
        if(!$checked_blcok_list && $wiz_member['wm_uid'] != $board_target['wm_uid'])
        {

            $this->load->model('notify_mdl');
            //$dealer = $this->notify_mdl->get_dealer_sms_by_wm_uid($wiz_member['wm_uid']);
            
            /* 게시글 작성자 알림*/
            $notify = array(
                'uid' => $board_target['wm_uid'], 
                'code' => 102, 
                'message' => '작성하신 게시글에 '.$wiz_member['wm_nickname'].' 님으로부터 채택받았습니다.', 
                'table_code' => $request['table_code'] == '9001' ? 'express.view':$request['table_code'], 
                'user_name' => $wiz_member['wm_nickname'],
                'board_name' => $board_target['mbn_table_name'], 
                'content'=> $selected_content, 
                'mb_unq' => $request['select_key'], 
                'co_unq' => NULL,
                'parent_key' => $request['mb_unq'],
                'regdate' => date('Y-m-d H:i:s'),
            );

            $notify_result = $this->notify_mdl->insert_notify($notify);

            if($notify_result < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }
        }

        /*
            sim_content3 으로 댓글 내용 등록
        */


        if($request['table_code'] == '9001')
        {
            $comment = array(
                'e_id'   => $board_target['mb_uid'],
                'comment' => $request['sim_content3'],
                'wiz_id' => $wiz_member['wm_wiz_id'],
                'c_name' => $wiz_member['wm_name'],
                'regdate' => date("Y-m-d H:i:s"),
            );

            $comment_result = $this->board_mdl->insert_comment_express($comment);
        }
        else
        {
            $comment = array(
                'mb_unq' => $board_target['mb_mb_unq'],
                'writer_id' => $wiz_member['wm_wiz_id'],
                'writer_name' => $wiz_member['wm_name'],
                'writer_ename' => $wiz_member['wm_ename'],
                'writer_nickname' => $wiz_member['wm_nickname'],
                'comment' => $request['sim_content3'],
                'table_code' => $request['table_code'],
                'notice_yn' => 2,
                'mob' => $request['is_app'],
                'co_thread' => "A",
                'regdate' => date("Y-m-d H:i:s"),
            );

            $comment_result = $this->board_mdl->insert_comment($comment);
        }

        if($comment_result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['data']['comment_mb_unq'] = $board_target['mb_mb_unq'];
        $return_array['msg'] = "채택하였습니다.";
        echo json_encode($return_array);
        exit;
    }

    // 관리자에서 글 등록 후 curl로 받을 api
    public function admin_insert_search_boards()
    {
        
        $return_array = array();
        
        //log_message('error', 'admin_insert_search_boards :'.date('Y-m-d H:i:s'));

        $request = array(
            "table_code" => $this->input->post('table_code') ? $this->input->post('table_code') : NULL,
            "mb_unq" => $this->input->post('mb_unq') ? $this->input->post('mb_unq') : NULL,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 특수게시판 테이블 코드 변환 */
        $this->load->model('board_mdl');

        if($request['table_code'] == '9001')   // 이런표현 어떻게
        {
            $row_board_by_mb_unq = $this->board_mdl->row_article_express_by_uid($request['mb_unq']);
            $row_board_by_mb_unq['mb_ename'] = $row_board_by_mb_unq['wm_ename'];
            $row_board_by_mb_unq['mb_nickname'] = $row_board_by_mb_unq['wm_nickname'];

        }
        else if($request['table_code'] == '9002')  // 얼철딕
        {
            $row_board_by_mb_unq = $this->board_mdl->row_article_cafeboard_by_pk($request['mb_unq']);
            $row_board_by_mb_unq['mb_unq'] = $row_board_by_mb_unq['mb_c_uid'];
            $row_board_by_mb_unq['mb_nickname'] = $row_board_by_mb_unq['wm_nickname'];
        }
        else if($request['table_code'] == '9004')  // 영어 첨삭
        {
            $row_board_by_mb_unq = $this->board_mdl->row_article_wiz_correct_by_pk($request['mb_unq']);
            $row_board_by_mb_unq['mb_unq'] = $row_board_by_mb_unq['mb_w_id'];
            $row_board_by_mb_unq['mb_nickname'] = $row_board_by_mb_unq['wm_nickname'];
        }
        else    // 일반 게시판
        {
            $row_board_by_mb_unq = $this->board_mdl->row_board_by_mb_unq($request['table_code'], $request['mb_unq']);
        }
        
        if(!$row_board_by_mb_unq['mb_unq'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "등록에 실패했습니다";
            echo json_encode($return_array);
            exit;
        }

        $search_boards_params = array(
            'table_code' => $request['table_code'], 
            'wiz_id' => $row_board_by_mb_unq['mb_wiz_id'],
            'name' => $row_board_by_mb_unq['mb_name'],
            'ename' => $row_board_by_mb_unq['mb_ename'],
            'nickname' => $row_board_by_mb_unq['mb_nickname'],
            'noticeYn' => $row_board_by_mb_unq['mb_noticeYn'] ? $row_board_by_mb_unq['mb_noticeYn'] : 'N',
            'title' => $row_board_by_mb_unq['mb_title'],
            'filename' => $row_board_by_mb_unq['mb_filename'],
            'content' => $row_board_by_mb_unq['mb_content'],
            'input_txt' => $row_board_by_mb_unq['mb_input_txt'],
            'hit' => $row_board_by_mb_unq['mb_hit'],
            'comm_hit' => $row_board_by_mb_unq['mb_comm_hit'],
            'regdate' => $row_board_by_mb_unq['mb_regdate'],
            'secret' => $row_board_by_mb_unq['mb_secret'] ? $row_board_by_mb_unq['mb_secret'] : 'n',
            'cafe_unq' => $row_board_by_mb_unq['mb_cafe_unq'] ? $row_board_by_mb_unq['mb_cafe_unq'] : 0,
            'mob' => $row_board_by_mb_unq['mb_mob'] ? $row_board_by_mb_unq['mb_mob'] : 'N',
            'tu_uid' => $row_board_by_mb_unq['mb_tu_uid'],
            'name_hide' => $row_board_by_mb_unq['mb_name_hide'] ? $row_board_by_mb_unq['mb_name_hide'] : 'N',
            'mb_unq' => $row_board_by_mb_unq['mb_unq'],
            'mins' => $row_board_by_mb_unq['mb_mins'],
            'class_date' => $row_board_by_mb_unq['mb_class_date'],
            'tu_name' => $row_board_by_mb_unq['mb_tu_name'],
            'book_name' => $row_board_by_mb_unq['mb_book_name'],
            'w_kind' => $row_board_by_mb_unq['mb_w_kind'],
            'w_mp3' => $row_board_by_mb_unq['mb_w_mp3'],
            'w_mp3_type' => $row_board_by_mb_unq['mb_w_mp3_type'],
            'su' => $row_board_by_mb_unq['mb_su'],
            'vd_url' => $row_board_by_mb_unq['mb_vd_url'],
            'w_step' => $row_board_by_mb_unq['w_step'],
            'recom' => $row_board_by_mb_unq['mb_recom'],
            'certify_view' => $row_board_by_mb_unq['mb_certify_view'],
            'certify_date' => $row_board_by_mb_unq['mb_certify_date'],
            'del_yn' => $row_board_by_mb_unq['mb_del_yn'] ? $row_board_by_mb_unq['mb_del_yn'] : 'N',
            'select_key' => $row_board_by_mb_unq['mb_select_key'],
            'parent_key' => $row_board_by_mb_unq['mb_parent_key'],
            'set_point' => $row_board_by_mb_unq['mb_set_point'] ? $row_board_by_mb_unq['mb_set_point'] : 0,
            'category_code' => $row_board_by_mb_unq['mb_category_code'],
            'anonymous_yn' => $row_board_by_mb_unq['mbn_anonymous_yn'] ? $row_board_by_mb_unq['mbn_anonymous_yn'] : 'N',
        );
        

        /* 데이터 인설트 */
        $result = $this->board_mdl->insert_search_boards($request['table_code'], $search_boards_params);
        

        $return_array['res_code'] = '0900';
        $return_array['msg'] = $result;
        echo json_encode($return_array);
        exit;
        
        if($result > 0)
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "등록에 성공했습니다";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "등록에 실패했습니다";
            echo json_encode($return_array);
            exit;
        }

    }

    // 관리자에서 글 삭제 후 curl로 받을 api
    public function admin_delete_search_boards()
    {
        $return_array = array();
        
        $request = array(
            "table_code" => $this->input->post('table_code') ? $this->input->post('table_code') : NULL,
            "mb_unq" => $this->input->post('mb_unq') ? $this->input->post('mb_unq') : NULL,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }


        $this->load->model('board_mdl');
        $result = $this->board_mdl->delete_search_boards($request['table_code'], $request['mb_unq']);
        
        if($result > 0)
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "삭제에 성공했습니다.";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "삭제에 실패했습니다.";
            echo json_encode($return_array);
            exit;
        }
    }


    /*
        민트영어 수강페이지 
        - 열공관련 게시판 : 총 게시글 수 
        - 얼철딕 , 영어첨삭, NS과제물: 총 게시물수, 최근 게시물 8건
        - 자가 부담금 있는 딜러회원 체크
    */
    public function landing_class()
    {
        $return_array = array();   

        $this->load->model('board_mdl');

        $limit =  "LIMIT 8";

        //얼굴철판딕테이션
        $list_cnt_cafeboard = $this->board_mdl->list_count_board_cafeboard("");
        $list_board_cafeboard = $this->board_mdl->list_board_cafeboard("", "", "ORDER BY mb.c_uid DESC", $limit, "", "INNER JOIN wiz_member wm ON mb.uid = wm.uid");

        //영어첨삭
        $list_cnt_correct = $this->board_mdl->list_count_board_wiz_correct("");
        $list_board_correct = $this->board_mdl->list_board_wiz_correct("", "", "ORDER by mb.w_id DESC", $limit, "");

        //NS과제물게시판 : 1354
        $list_cnt_ns = $this->board_mdl->list_count_board("USE INDEX(idx_list_count)", "WHERE mb.table_code = '1354'");        
        $list_board_ns = $this->board_mdl->list_board("USE INDEX(idx_table_code)", "WHERE mb.table_code = '1354'", "ORDER BY mb.mb_unq DESC", $limit, "");

        /*
            열공게시판 카테고리에 포함 되어있는 일반 게시판 
            - 유용한영어표현 : 1128
            - NS과제물 : 1354
            - [도전]일일영작문 : 1127
            - 딕테이션해결사 : 1138
            - 영어문법질문&답변 : 1120
            - 영어해석커뮤니티 : 1102
            - 수업대본서비스 : 1130
        */
        $where_board = "WHERE mb.table_code IN ('1128','1354','1127','1138','1120','1102','1130')  ";
        $list_cnt_board = $this->board_mdl->list_count_board("USE INDEX(idx_list_count)", $where_board);        

        // 이런표현어떻게
        $list_cnt_express = $this->board_mdl->list_count_board_express("", "");

        // 열공게시판 총 게시글 수 
        $list_cnt = $list_cnt_board['cnt'] + $list_cnt_correct['cnt'] + $list_cnt_cafeboard['cnt'] + $list_cnt_express['cnt'];

        // 자가부담금 있는 딜러있지 체크하기 위해
        //$wiz_member = base_get_wiz_member();
        //wd_has_member_fee
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['study_total_cnt'] = (string)$list_cnt;
        $return_array['data']['ns']['total_cnt'] = $list_cnt_ns['cnt'];
        $return_array['data']['ns']['list'] = $list_board_ns;
        $return_array['data']['correct']['total_cnt'] = $list_cnt_correct['cnt'];
        $return_array['data']['correct']['list'] = $list_board_correct;
        $return_array['data']['cafeboard']['total_cnt'] = $list_cnt_cafeboard['cnt'];
        $return_array['data']['cafeboard']['list'] = $list_board_cafeboard;
        
        echo json_encode($return_array);
        exit;
      
    }

    public function quest_test(){

        $aa = MintQuest::getInstance(base_get_wiz_member()['wm_uid'])->do_quest(23,19226); 
        echo '<xmp>';
        print_r($aa);
        echo '</xmp>';
        exit;
    }

    //AHOP 북마크 등록/해제
    public function ahop_bookmark()
    {
        $return_array = array();

        $request = array(
            'wiz_id'            => trim($this->input->post('wiz_id')) ? trim($this->input->post('wiz_id')) : null,
            "authorization"     => trim($this->input->post('authorization')),
            "table_code"        => $this->input->post('table_code') ? $this->input->post('table_code') : NULL,
            "category"          => $this->input->post('category') ? $this->input->post('category') : NULL,
            "mb_unq"            => $this->input->post('mb_unq') ? $this->input->post('mb_unq') : NULL,
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


        $datas = array(
            "uid" => $wiz_member['wm_uid'],
            "table_code" => $request['table_code'],
            "category"=> $request['category'],
            "comment"=> $request['mb_unq'],
            'regdate' => date("Y-m-d H:i:s"),
        );

        $this->load->model('book_mdl');
        $result = $this->book_mdl->update_ahop_bookmark(array_filter($datas));
        
        
        if($result == 0){
            //삭제
            $return_array['res_code'] = '0000';
            $return_array['msg'] =  "선택된 강좌가 공부완료 처리 해제됐습니다.";
            $return_array['result'] = 'N';
            echo json_encode($return_array);
            exit;
        
        }else if($result < 0) {

            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;

        }else{
    
            //인설트
            $return_array['res_code'] = '0000';
            $return_array['result'] = 'Y';
            $return_array['msg'] = "선택된 강좌가 공부완료 처리 됐습니다.";
            echo json_encode($return_array);
            exit;
        }
    }

}








