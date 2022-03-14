<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class _Batch extends CI_Controller{
    public $upload_path_boards = null;
    public $upload_path_summernote = null;

    public function __construct()
    {

        //점검시간 체크
        $siteCheck_nowTime = time();
        $siteCheck_startTime = strtotime("2021-02-14 00:30:00");
        $siteCheck_endTime   = strtotime("2021-02-14 01:00:00");
        $site_check_allow_ips = array(
            /* 	'210.121.223.211',
                '210.121.177.54',
                '118.219.254.162',
                '118.219.254.163',
                '218.237.65.31',
                '118.219.254.165',
                '121.125.68.113',
                '121.125.71.200',
                '121.125.68.93',
                '192.168.0.11',
                '192.168.0.35',
                '127.0.0.1',
                '210.121.177.47', */
            );
            
        if($siteCheck_startTime <= $siteCheck_nowTime && $siteCheck_endTime >= $siteCheck_nowTime && !in_array($_SERVER['REMOTE_ADDR'],$site_check_allow_ips)) {
            exit;
        }
        
        parent::__construct();
        $this->load->library('form_validation');
        date_default_timezone_set('Asia/Seoul');
        $this->upload_path_boards = ISTESTMODE ? 'test_upload/attach/boards/':'attach/boards/';
        $this->upload_path_summernote = 'summernote/';
        
    }

    /*
    베스트글 복사
    - 일반 게시판 
        게시글번호 , 추천수, 익명여부
    */
    public function checked_best_article($table_code, $mb_unq)
    {
        $request = array(
            "table_code" => trim($table_code),
            "mb_unq" => trim($mb_unq),
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
        $this->load->model('member_mdl');

        $board_config = $this->board_mdl->row_board_config_by_table_code($request['table_code']);
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

        /* 익명게시판 예외처리 */
        $MBN_ANONYMOUS_YN =  $this->config->item('MBN_ANONYMOUS_YN');

        /* 
            익명게시판 여부 
            - config 설정 or 날코딩
        */
        $anonymous = ($board_config['mbn_anonymous_yn'] == "Y" || in_array($request["table_code"], $MBN_ANONYMOUS_YN)) ? "Y" : "N";
        $anonymous = $article['mb_name_hide'] =='Y' ? 'Y':$anonymous;

        $mbn_copy_msg = NULL;

        if($board_config['mbn_copy_yn'] == "Y")
        {
            
            if($article['mb_recom'] >= $board_config['mbn_copy_move_ea'])
            {
              
                /* 게시글 복사시 */
                $checked_best = $this->board_mdl->checked_article_best_copy($request['mb_unq']);
                                
                /* 베스트글 게시판에 해당 게시글이 없으면 복사*/
                if($checked_best['cnt'] == 0)
                {
                  
                    if($board_config['mbn_copy_move_point'] > 0)
                    {
                        $mbn_copy_msg = $board_config['mbn_table_name']."에 작성하신 글이 베스트글에 선정이 되어 ".number_format($board_config['mbn_copy_move_point'])."포인트 적립 축하 *^^*";
                    }
                    
                    $copy_article = $this->board_mdl->row_copy_article($request['mb_unq']);
                    $tmp_copy_article_wm_uid = $this->member_mdl->get_wm_uid_by_wiz_id($copy_article['wiz_id']);
                    $copy_article_wm_uid = $tmp_copy_article_wm_uid['wm_uid'];

                    
                    
                    /* 베스트글 게시판으로 복사 */
                    $copy = array(
                        'sim_content' => $copy_article['sim_content'],
                        'sim_content2' => $copy_article['sim_content2'],
                        'sim_content3' => '/board_view.php?table_code='.$copy_article['table_code'].'&mb_unq='.$copy_article['mb_unq'],
                        'sim_content4' => $copy_article['table_code'].",".$copy_article['mb_unq'],
                        'table_code' => '1347',
                        'wiz_id' => $copy_article['wiz_id'],
                        'name' => $copy_article['name'],
                        'ename' => $copy_article['ename'],
                        'nickname' => $copy_article['nickname'],
                        'title' => $copy_article['title'],
                        'filename' => $copy_article['filename'],
                        'editor_file' => $copy_article['editor_file'],
                        'content' => $copy_article['content'],
                        'input_txt' => $copy_article['input_txt'],
                        'regdate' => date('Y-m-d H:i:s'),
                        'secret' => $copy_article['secret'],
                        'c_yn' => $copy_article['c_yn'],
                        'pwd' => $copy_article['pwd'],
                        'tu_uid' => $copy_article['tu_uid'],
                        'daum_img' => $copy_article['daum_img'],
                        'showdate' => $copy_article['showdate'],
                        'name_hide' => $anonymous,
                        'table_unq' => $board_config['mbn_unq'],
                        'thumb' => $copy_article['thumb'],
                    );

                    
                    $copy_result = $this->board_mdl->write_article($copy);
                    if($copy_result < 0)
                    {
                        $return_array['res_code'] = '0500';
                        $return_array['msg'] = "DB ERROR";
                        echo json_encode($return_array);
                        exit;
                    }
                    else
                    {
                        
                        $this->load->model('notify_mdl');
                        /* 알림*/
                        $notify = array(
                            'uid' => $copy_article_wm_uid, 
                            'code' => 142,
                            'message' => '작성하신 글이 베스트글에 선정되었습니다.',
                            'table_code' => $copy_article['table_code'], 
                            'user_name' => 'SYSTEM',
                            'content'=> $board_config['mbn_table_name'], 
                            'mb_unq'=> $copy_article['mb_unq'], 
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
        else if($board_config['mbn_copy_yn'] == "N" && $article['mb_recom'] >= $board_config['mbn_copy_move_ea'] && $board_config['mbn_copy_move_point'] > 0)
        {
            /* 게시글 복사는 하지 않으나 목표추천수에 도달했을때 */
            $mbn_copy_msg = $board_config['mbn_table_name']."에 작성하신 글이 추천을 많이 받아 축하의 ".number_format($board_config['mbn_copy_move_point'])."포인트 적립*^^*";
            $copy_article = $this->board_mdl->row_copy_article($request['mb_unq']);
            $tmp_copy_article_wm_uid = $this->member_mdl->get_wm_uid_by_wiz_id($copy_article['wiz_id']);
            $copy_article_wm_uid = $tmp_copy_article_wm_uid['wm_uid'];
        }

        /* 베스트글 포인트 지급 */
        if($mbn_copy_msg && $copy_article && $copy_article_wm_uid)
        {
            if($copy_article_wm_uid != '' && $copy_article_wm_uid != '0')
            {
                $point = array(
                    'uid' => $copy_article_wm_uid,
                    'name' => $copy_article['name'],
                    'point' => $board_config['mbn_copy_move_point'],
                    'pt_name'=> $mbn_copy_msg, 
                    'kind'=> 'R', 
                    'b_kind'=> 'boards',
                    'table_code'=> $copy_article['table_code'],
                    'co_unq'=> $copy_article['mb_unq'], 
                    'showYn'=> 'y',
                    'secret'=>  $anonymous,
                    'regdate' => date("Y-m-d H:i:s")
                );

                /* 포인트 내역 입력 및 포인트 추가 */
                $this->load->model('point_mdl');
                $this->point_mdl->set_wiz_point($point);
            }
        }
    }


    /*
        특수게시판 
        - 이런표현어떻게
        - 얼굴철판딕테이션
        댓글입력 알림
    */
    public function comment_special_insert_notify($table_code, $mb_unq, $co_unq, $wm_uid)
    {

        $request = array(
            "table_code" => trim($table_code),
            "mb_unq" => trim($mb_unq),
            "co_unq" => trim($co_unq),
            "wm_uid" => trim($wm_uid),
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
        $wiz_member = $this->member_mdl->get_wiz_member_by_wm_uid($request['wm_uid']);
        /* 회원 보유포인트 */
        $wm_point = $wiz_member['wm_point'];

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

        $board_config = NULL;
        $article = NULL;
        $tmp_article_wm_uid = NULL;
        $article_wm_uid = NULL;
        $notify_table_code = NULL;

        $this->load->model('board_mdl');
      
        if($request['table_code'] == 'express')
        {
            /* 이련표현어떻게 답변글쓰기 권한체크 */
            if(false === stripos($wiz_member['wm_assistant_code'], "*express*"))
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0308";
                $return_array['data']['err_msg'] = "답변글 쓰기 권한이 없습니다.";
                echo json_encode($return_array);
                exit;
            } 
            
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

        $tmp_article_wm_uid = $this->member_mdl->get_wm_uid_by_wiz_id($article['mb_wiz_id']);
        $article_wm_uid = $tmp_article_wm_uid['wm_uid'];

        if(!$article_wm_uid)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0399";
            $return_array['data']['err_msg'] = "게시물 작성자의 아이디가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        /* 게시글 작성자, 댓글작성자 차단목록 확인*/
        $checked_blcok_list = $this->board_mdl->checked_block_list($wiz_member['wm_uid'], $article_wm_uid);
        
        /* 차단목록에 없다면 알림 */
        if(!$checked_blcok_list)
        {

            $this->load->model('notify_mdl');
            //$dealer = $this->notify_mdl->get_dealer_sms_by_wm_uid($wiz_member['wm_uid']);

            /* 게시글 작성자 알림*/
            $notify = array(
                'uid' => $article_wm_uid, 
                'code' => 102, 
                'message' => '작성하신 게시글에 '.$display_name.'님의 댓글이 등록되었습니다.', 
                'table_code' => $notify_table_code, 
                'user_name' => $display_name,
                'board_name' => $board_config['mbn_table_name'], 
                'content'=> $article['mb_content'], 
                'mb_unq' => $request['mb_unq'], 
                'co_unq' => $request['co_unq'], 
                'parent_key' => $article['mb_parent_key'], 
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


    /*
        일반게시판
        댓글입력 알림
    */
    public function comment_insert_notify($table_code, $mb_unq, $co_unq, $wm_uid, $co_fid = NULL)
    {

        $request = array(
            "table_code" => trim($table_code),
            "mb_unq" => trim($mb_unq),
            "co_unq" => trim($co_unq),
            "wm_uid" => trim($wm_uid),
            "co_fid" => trim($co_fid),
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
        $wiz_member = $this->member_mdl->get_wiz_member_by_wm_uid($request['wm_uid']);
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

        if(!$article_wm_uid)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0399";
            $return_array['data']['err_msg'] = "게시물 작성자의 아이디가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        /*
            알림시 표기되는 이름 추천회원 닉네임
            우선순위 1. 닉네임 , 2. 영어이름 3. 한글이름
        */
        $display_name = "";
        // 학부모 게시판은 익명글 설정 시 댓글도 익명으로 보여진다
        if($request["table_code"] =='1383' && $article['mb_name_hide'] =='Y')
        {
            $display_name = '익명 회원';
        }
        elseif($wiz_member["wm_nickname"])
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

        /* 게시글 작성자, 댓글작성자 차단목록 확인 */
        $checked_blcok_list = $this->board_mdl->checked_block_list($wiz_member['wm_uid'], $article_wm_uid);

        // 알림톡 발송
        if(!$checked_blcok_list && $article['mb_rsms'] =='Y' && $wiz_member['wm_uid'] != $article_wm_uid )
        {
            $board_link = board_make_viwe_link($request["table_code"], $request['mb_unq']);
            $options = array(
                'uid'       => $article_wm_uid,
                'wiz_id'    => $article['mb_wiz_id'],
                'name'      => $article['mb_name'],
                'board_name'=> $board_config['mbn_table_name'],
                'nickname'  => $display_name,
                'board_link'=> $board_link
            );

            sms::send_atalk($tmp_article_wm_uid['wm_mobile'],'MINT06002S',$options);
        }
        
        /* 차단목록에 없다면 알림 내가 쓴글에 내가 댓글 달때 제외*/
        if(!$checked_blcok_list && $wiz_member['wm_uid'] != $article_wm_uid)
        {

            $this->load->model('notify_mdl');
            //$dealer = $this->notify_mdl->get_dealer_sms_by_wm_uid($wiz_member['wm_uid']);

            /* 게시글 작성자 알림*/
            $notify = array(
                'uid' => $article_wm_uid, 
                'code' => 102, 
                'message' => '작성하신 게시글에 '.$display_name.'님의 댓글이 등록되었습니다.', 
                'table_code' => $notify_table_code, 
                'user_name' => $display_name,
                'board_name' => $board_config['mbn_table_name'], 
                'content'=> $article['mb_content'], 
                'mb_unq' => $request['mb_unq'], 
                'co_unq' => $request['co_unq'],
                'parent_key' => $article['mb_parent_key'], 
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

        /* 대댓글 일시 부모댓글 작성자에게 알림 */
        if($co_fid)
        {
            $comment_parent = $this->board_mdl->comment_parent_wm_uid_by_co_fid($co_fid);
            if($comment_parent['wm_uid'])
            {
                /* 부모댓글 작성자, 댓글작성자 차단목록 확인*/
                $checked_blcok_list = $this->board_mdl->checked_block_list($wiz_member['wm_uid'], $comment_parent['wm_uid']);   
                            
                // 내가쓴 댓글에 답글달때는 제외
                if(!$checked_blcok_list && $wiz_member['wm_uid'] != $comment_parent['wm_uid'])
                {
                    $this->load->model('notify_mdl');
                    //$dealer = $this->notify_mdl->get_dealer_sms_by_wm_uid($wiz_member['wm_uid']);

                    /* 게시글 작성자 알림*/
                    $notify = array(
                        'uid' => $comment_parent['wm_uid'], 
                        'code' => 102, 
                        'message' => '작성하신 댓글에 '.$display_name.'님의 댓글이 등록되었습니다.', 
                        'table_code' => $notify_table_code, 
                        'user_name' => $display_name,
                        'board_name' => $board_config['mbn_table_name'], 
                        'content'=> $article['mb_content'], 
                        'mb_unq' => $request['mb_unq'], 
                        'co_unq' => $request['co_unq'],
                        'parent_key' => $article['mb_parent_key'], 
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

    /* public function delete_board_edit_files($table_code, $mb_unq)
    {
        $this->load->model('board_mdl');
        $article = $this->board_mdl->row_article_by_mb_unq($table_code, $mb_unq);

        // 첨부파일 filename 삭제, 섬네일필드에 들어있는 것들 삭제, content에 s3 이미지경로 들어있으면 삭제
        board_delete_files($article['mb_filename'],$this->upload_path_boards,$article['mb_content'],json_decode($article['mb_thumb'],true));
    } */

    //summernote 이미지 업로드 완료되지 않은 게시물 배치로 삭제(삭제 기준 : file_status = 1, 등록일 기준 1일 지난 게시물)
    // 일반게시물 삭제 시 mint_board_files 테이블에 넣어서 같이 삭제되게끔 추가-200814홍장기
    public function delete_edit_files_incomplete()
    {
        $this->load->model('board_mdl');
        $list_edit_files_incomplete = $this->board_mdl->list_edit_files_incomplete();

        $file_name_arr = array();

        if($list_edit_files_incomplete)
        {
            for($i=0; $i<count($list_edit_files_incomplete); $i++)
            {   
                //s3 업로드 돼있는 파일 삭제
                $s3_result = S3::delete_s3_object('','', str_replace(Thumbnail::$cdn_default_url.'/','',$list_edit_files_incomplete[$i]['file_link']));

                if($s3_result['res_code'] == '0000')
                {
                    array_push($file_name_arr, $list_edit_files_incomplete[$i]['mbf_key']);
                }
            }
        }

        // 삭제될 데이터 확인위해 디비 삭제 제거
        //DB로우 삭제
        if($file_name_arr)
        {
            $result = $this->board_mdl->delete_edit_files_incomplete($file_name_arr);
        
            if($result < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR";
                echo json_encode($return_array);
                exit;
            }
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "데이터 삭제를 완료했습니다.";
        echo json_encode($return_array);
        exit;
    }

    //강사 해시태그 테이블 갱신
    public function set_tutor_hashtag_count()
    {
        $start = common_get_time();

        $return_array = array();
        $datas = array();
        
        $this->load->model('tutor_mdl');
        
        $list_tutor_hashtag_log = $this->tutor_mdl->list_tutor_hashtag_log_batch();

        //초기 데이터가 있을때
        if($list_tutor_hashtag_log)
        {
            $where =  " WHERE ts.item1 is not null AND ts.regdate > (SELECT bul.update_date FROM _batch_update_log bul WHERE bul.type = 'TUTOR_HASHTAG' LIMIT 1)";

            //마지막 업데이트날짜보다 나중에 등록된 강사평가서 조회
            $list_tutor_star = $this->tutor_mdl->list_tutor_star($where);

            if($list_tutor_star)
            {
                for($i=0; $i<count($list_tutor_star); $i++ )
                {
                    $insert_data = array();
                    $update_data = array();
                    
                    $hashtag_datas = explode(',', $list_tutor_star[$i]['item1']);
                    
                    for($j=0; $j<count($hashtag_datas); $j++)
                    {
                        $insert = array();
                        $update = array();
                        
                        $get_tutor_hashtag_log = $this->tutor_mdl->get_tutor_hashtag_log_by_tu_uid($list_tutor_star[$i]['tu_uid'], $hashtag_datas[$j]);

                        //없을때 인설트
                        if(!$get_tutor_hashtag_log)
                        {
                            $insert = array(
                                "tu_uid" => $list_tutor_star[$i]['tu_uid'],
                                "it_uid" => $hashtag_datas[$j],
                                "count" => '1',
                            );
                            
                            array_push($insert_data, $insert);
                        }
                        //있을때 업데이트
                        else
                        {
                            //pk key 셀렉트
                            $update = array(
                                'thl_key'=>$get_tutor_hashtag_log['thl_key'],
                                'count'=>$get_tutor_hashtag_log['count'] + 1,
                            );
                            
                            array_push($update_data, $update);
                        }
                    }

                    //강사 평가서 row 강 insert/update
                    $result_insert = $this->tutor_mdl->insert_batch_tutor_hashtag($insert_data);  
                    $result_update = $this->tutor_mdl->update_batch_tutor_hashtag($update_data);
        
                    if($result_insert < 0)
                    {
                        $return_array['res_code'] = '0500';
                        $return_array['msg'] = "DB ERROR INSERT";
                        echo json_encode($return_array);
                        exit;
                    }

                    if($result_update < 0)
                    {
                        $return_array['res_code'] = '0500';
                        $return_array['msg'] = "DB ERROR UPDATE";
                        echo json_encode($return_array);
                        exit;
                    }
                }
            }
        }
        // 초기 데이터 없을때
        else
        {
            $where = NULL;
            $order = NULL;
            $limit = NULL;
            
            //선생님 리스트
            $list_tutor = $this->tutor_mdl->list_tutor($where, $order, $limit);

            for($i=0; $i<count($list_tutor); $i++)
            {
                $where = "WHERE ts.tu_uid = '".$list_tutor[$i]['tu_uid']."' AND ts.item1 is not null";
                //선생님 id로 강사평가서 조회
                $list_tutor_star = $this->tutor_mdl->list_tutor_star($where);
                
                if($list_tutor_star)
                {
                    $item_array = array();
                    $total_array = array();
                    $data = array();

                    for($j=0; $j<count($list_tutor_star); $j++)
                    {
                        $hashtag_datas = explode(',', $list_tutor_star[$j]['item1']);

                        for($k=0; $k<count($hashtag_datas); $k++)
                        {
                            array_push($item_array, $hashtag_datas[$k]);
                        }
                    }

                    $result = array_count_values($item_array);   //value => count
                    
                    foreach($result as $key => $value)
                    {
                        $data = array(
                            "tu_uid" => $list_tutor[$i]['tu_uid'],
                            "it_uid" => $key,
                            "count" => $value,
                        );
                        
                        array_push($total_array, $data);
                    }
                    
                    $result = $this->tutor_mdl->insert_batch_tutor_hashtag($total_array);    //데이터 세팅

                    if($result < 0)
                    {
                        $return_array['res_code'] = '0500';
                        $return_array['msg'] = "DB ERROR UPDATE_DATE";
                        echo json_encode($return_array);
                        exit;
                    }
                }
            }
        }

        $end = common_get_time();
        $time = $end - $start;
        $type = 'TUTOR_HASHTAG';

        $date = date("Y-m-d H:i:s");
        $update_date = $this->tutor_mdl->update_batch_update_log($date, $time, $type);    //업데이트 로그 시간 갱신

        if($update_date < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR UPDATE_DATE";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['data']['time'] = $time;
        $return_array['msg'] = "해시태그 집계가 완료됐습니다.";
        echo json_encode($return_array);
        exit;
        
    }

    //강사 평점 테이블 갱신
    public function set_tutor_star_average()
    {
        $start = common_get_time();
        $return_array = array();

        $where = NULL;
        $order = NULL;
        $limit = NULL;
        
        $this->load->model('tutor_mdl');
        $list_tutor = $this->tutor_mdl->list_tutor($where, $order, $limit);

        if($list_tutor)
        {
            for($i=0; $i<count($list_tutor); $i++)
            {
                $data = array();
                $average_total = NULL;

                $where = "";
                $order = NULL;
                $limit = NULL;

                // 12개월 평균
                for($j=1; $j<=12; $j++)
                {
                    /*
                        PHP 유동변수 선언
                        참조 : https://www.jynote.net/772
                        ex) 변수선언 average_1 , average_2 .... average_12
                    */
                    ${"average_".($j)} = NULL;

                    // 월별 평균치
                    $where = ' WHERE ts.tu_uid ='.$list_tutor[$i]['tu_uid'].' AND ts.regdate BETWEEN DATE_ADD(NOW(),INTERVAL -'.($j).' MONTH ) AND NOW() AND ts.item1 IS NOT NULL';               
                    $get_tutor_star_month_average = $this->tutor_mdl->get_tutor_star_average($where, $order, $limit);
                    
                    // 해당 기간 평균치 없을시 average : NULL , join_count : 0 리턴
                    ${"average_".($j)} = $get_tutor_star_month_average['average'];
                }

                // 전체기간 평균
                $where = ' WHERE ts.tu_uid ='.$list_tutor[$i]['tu_uid'].' AND ts.ts_star IS NOT NULL';
                $get_tutor_star_total_average = $this->tutor_mdl->get_tutor_star_average($where, $order, $limit);
                
                $average_total = $get_tutor_star_total_average['average'];

                
                if($get_tutor_star_total_average['tu_uid'])
                {
                    $data = array(
                        'tu_uid'=> ($get_tutor_star_total_average['tu_uid']) ? $get_tutor_star_total_average['tu_uid'] : NULL,
                        'average_1'=> ($average_1) ? round($average_1, 2) : NULL,
                        'average_2'=> ($average_2) ? round($average_2, 2) : NULL,
                        'average_3'=> ($average_3) ? round($average_3, 2) : NULL,
                        'average_4'=> ($average_4) ? round($average_4, 2) : NULL,
                        'average_5'=> ($average_5) ? round($average_5, 2) : NULL,
                        'average_6'=> ($average_6) ? round($average_6, 2) : NULL,
                        'average_7'=> ($average_7) ? round($average_7, 2) : NULL,
                        'average_8'=> ($average_8) ? round($average_8, 2) : NULL,
                        'average_9'=> ($average_9) ? round($average_9, 2) : NULL,
                        'average_10'=> ($average_10) ? round($average_10, 2) : NULL,
                        'average_11'=> ($average_11) ? round($average_11, 2) : NULL,
                        'average_12'=> ($average_12) ? round($average_12, 2) : NULL,
                        'average_total'=> ($average_total) ? round($average_total, 2) : NULL,
                    );

                    $update_tutor_star = $this->tutor_mdl->update_tutor_star_log($data, $get_tutor_star_total_average['tu_uid']);
                    
                    if($update_tutor_star < 0)
                    {
                        $return_array['res_code'] = '0500';
                        $return_array['msg'] = "DB ERROR";
                        echo json_encode($return_array);
                        exit;
                    }
                }
            }

            $end = common_get_time();
            $time = $end - $start;
            $type = 'TUTOR_STAR';

            $date = date("Y-m-d H:i:s");
            $update_date = $this->tutor_mdl->update_batch_update_log($date, $time, $type);    //업데이트 로그 시간 갱신

            $return_array['res_code'] = '0000';
            $return_array['data']['time'] = $time;
            $return_array['msg'] = "선생님 평점 집계가 완료됐습니다.";
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

    //강사 추천 테이블 갱신
    public function set_tutor_recommend()
    {
        $start = common_get_time();
        $return_array = array();

        $where = NULL;
        $order = NULL;
        $limit = NULL;
        
        $this->load->model('tutor_mdl');

        $list_tutor = $this->tutor_mdl->list_tutor($where, $order, $limit);

        if($list_tutor)
        {
            for($i=0; $i<count($list_tutor); $i++)
            {
                // 추천연령 (S/J) 카운팅
                // 글 작성 당시의 작성자 나이(S/J) 카운팅

                $where = "WHERE ts.tu_uid = '".$list_tutor[$i]['tu_uid']."'";
                
                //선생님 id로 강사평가서 조회
                $list_tutor_star_user_info = $this->tutor_mdl->list_tutor_star_user_info($where);
                
                if($list_tutor_star_user_info)
                {
                    $item_array = array();
                    $data = array();
                    $user_s_count = 0;
                    $user_j_count = 0;
                    $total_count = count($list_tutor_star_user_info);
                    
                    for($j=0; $j<count($list_tutor_star_user_info); $j++)
                    {
                        $recommend_datas = explode(',', $list_tutor_star_user_info[$j]['item1']);

                        for($k=0; $k<count($recommend_datas); $k++)
                        {
                            array_push($item_array, $recommend_datas[$k]);
                        }

                        $regdate = $list_tutor_star_user_info[$j]['regdate'];
                        $birth = common_checked_birth_format($list_tutor_star_user_info[$j]['birth']);
                        
                        $age = (int)(substr($regdate, 0, 4)) - (int)(substr($birth, 0, 4)) - 1;   // 나이 계산때문에 -1 더
                        
                        /*
                            15세 이상이면 시니어 클래스
                        */
                        if($age > 14) $user_s_count += 1;
                        else $user_j_count += 1;
                    }
                    
                    $recommend_count = array_count_values($item_array);   //value => count
                    // $recommend_count['28'] == 주니어 
                    // $recommend_count['39'] == 성인
                    
                    isset($recommend_count['39']) ? $recommend_count['S'] = $recommend_count['39'] : $recommend_count['S'] = 0;  //성인
                    isset($recommend_count['28']) ? $recommend_count['J'] = $recommend_count['28'] : $recommend_count['J'] = 0;  //주니어

                    $recommend_remain_count  = $total_count - ( $recommend_count['S'] + $recommend_count['J'] );
                    
                    $data = array(  
                        "tu_uid" => $list_tutor[$i]['tu_uid'],
                        "total_count" => $total_count,
                        "recommend_s_count" => $recommend_count['S'],                                                       //유저가 추천한 시니어 카운트 합계
                        "recommend_j_count" => $recommend_count['J'],                                                       //유저가 추천한 주니어 카운트 합계
                        "recommend_remain_count" => $recommend_remain_count,                                                //유저가 추천한 추천연령 중 S/J 둘다 누르지 않은 카운트 합계
                        "recommend_s_per" => round((((int)$recommend_count['S'] / $total_count) * (int)100), 2),            //유저가 추천한 시니어 퍼센트
                        "recommend_j_per" => round((((int)$recommend_count['J'] / $total_count) * (int)100), 2),            //유저가 추천한 주니어 퍼센트
                        "recommend_remain_per" => round((((int)$recommend_remain_count / $total_count) * (int)100), 2),     //유저가 추천한 추천연령 중 S/J 둘다 누르지 않은 퍼센트
                        "user_s_count" => $user_s_count ? $user_s_count : 0,                                                //평가서 작성 당시의 작성자의 연령(시니어/주니어) 합계 카운트
                        "user_j_count" => $user_j_count ? $user_j_count : 0,                                                //평가서 작성 당시의 작성자의 연령(시니어/주니어) 합계 카운트
                        "user_s_per" => round((((int)$user_s_count / $total_count) * (int)100), 2),                         //평가서 작성 당시 유저의 연령 시니어 퍼센트
                        "user_j_per" => round((((int)$user_j_count / $total_count) * (int)100), 2),                         //평가서 작성 당시 유저의 연령 주니어 퍼센트
                    );

                    $result = $this->tutor_mdl->update_tutor_recommend_log($data, $list_tutor[$i]['tu_uid']);    //데이터 세팅

                    if($result < 0)
                    {
                        $return_array['res_code'] = '0500';
                        $return_array['msg'] = "DB ERROR";
                        echo json_encode($return_array);
                        exit;
                    }
                }
            }

            $end = common_get_time();
            $time = $end - $start;
            $type = 'TUTOR_RECOMMEND';

            $date = date("Y-m-d H:i:s");
            $update_date = $this->tutor_mdl->update_batch_update_log($date, $time, $type);    //업데이트 로그 시간 갱신

            $return_array['res_code'] = '0000';
            $return_array['data']['time'] = $time;
            $return_array['msg'] = "선생님 추천 집계가 완료됐습니다.";
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


    public function thumbnail_create()
    {
        $create_size = 100; // 추가 생성할 섬네일 사이즈
        $limit_date = date('Y-m-d H:i:s',strtotime('-1 month'));    // 생성할 데이터 제한
        $config = array(
            'resize_width'=>array($create_size)
        );
        $this->load->model('board_mdl');
        $data = $this->board_mdl->exist_thumb_rows($limit_date);

        if($data)
        {
            foreach($data as $val)
            {
                $mb_unq = $val['mb_unq'];
                $thumb = json_decode($val['thumb'],true);
                $editor_arr = [];
                $form_arr = [];
                $new_thumb_info = [];
                // 에디터 섬네일
                if($thumb['editor'])
                {
                    // 배열
                    foreach($thumb['editor'] as $e_key=>$e_val)
                    {
                        // 추가생성할 섬네일 사이즈가 이미있으면 패스
                        if($e_val[$create_size]) continue;
                        $origin = $e_val['origin'];
                        $filename = explode('/',$origin);
                        $filename = $filename[count($filename)-1];
                        $path = str_replace($filename,'',$origin);
                        $new_thumb = Thumbnail::create_thumbnail_s3(array('tmp_name'=>$path),$filename,Thumbnail::$s3_thumbnail_loc,$config);
                        
                        if(is_array($new_thumb))
                        {
                            $editor_arr[$e_key] = $new_thumb + $e_val;
                        }
                    }
                }
                
                // 폼파일 섬네일
                if($thumb['form'])
                {
                    // 폼파일명으로 구분되있음
                    foreach($thumb['form'] as $f_key=>$f_val)
                    {
                        // 추가생성할 섬네일 사이즈가 이미있으면 패스
                        if($f_val[$create_size]) continue;
                        $origin = $f_val['origin'];
                        $filename = explode('/',$origin);
                        $filename = $filename[count($filename)-1];
                        $path = str_replace($filename,'',$origin);
                        $new_thumb = Thumbnail::create_thumbnail_s3(array('tmp_name'=>$path),$filename,Thumbnail::$s3_thumbnail_loc,$config);
                        
                        if(is_array($new_thumb))
                        {
                            $form_arr[$f_key] = $new_thumb + $f_val;
                        }
                    }
                }

                if($editor_arr) $new_thumb_info['editor'] = $editor_arr;
                if($form_arr) $new_thumb_info['form'] = $form_arr;

                // 새로 만들어졌으면 업데이트한다
                if($new_thumb_info)
                {
                    $thumb_json = json_encode($new_thumb_info);
                    $this->board_mdl->update_thumb_info($thumb_json,$mb_unq);
                }

                echo $mb_unq.PHP_EOL;
                flush();
                
            }
        }

        
        
    }


    /**
    *    벼락용강사로 지정된 수업은 0시가 되기전에 강사 지정해서 수업들어야한다.
    *    만약 강사지정하지 않고 0시가 지나면 어제자 스케쥴은 결석처리 시켜야한다.
    **/
    public function thunder_tutor_absence()
    {
        // 이제 안씀
        exit;
        $this->load->model('lesson_mdl');

        $target_date = date('Y-m-d',strtotime('-1 day'));    // 대상은 진행하지 않은 하루전 수업
        // 벼락전용강사 pk
        $thunder_tutor_uid = $this->config->item('thunder_tutor_uid');

        $data = $this->lesson_mdl->checked_prev_class_thunder_tutor($target_date,$thunder_tutor_uid);

        if($data)
        {
            foreach($data as $schedule)
            {
                lesson_schedule_state_change($schedule['sc_id'],[
                    'uid'           => $schedule['uid'],
                    'present'       => 3,
                    'topic'         => '-',
                    'topic_date'    => $target_date,
                    'absent_reason' => 'unassigned class in time ',
                    'is_cron'       => true,
                ]);

            }

        }
        
    }


    /**
     * 자유수업은 언제든지 수업들을수 있는 주당 횟수가 지정되어있다.(2,3,5회)
     * 토 06시 ~ 토 01시 까지 스케쥴을 지정해야하며, 미지정된 남은 횟수는 토요일 24시 05분에 날려버리는 처리를 한다.
     * 자유수업 구분은(cl_gubun)) 2.
     * 토요일 0~1시까지는 수업배정을 못하므로, 부여된 횟수만큼 금요일까지 무조건 다 배정된 상태여야한다.
     * 배정되지 않은 나머지 갯수는 결석처리된다.
     * ------------------------------------
     * 주기변경
     * 결석 크론 도는 시간 월요일 0시 5분으로 변경
     * 월 ~ 일
     */
    public function free_lesson_absent()
    {
        $this->load->model('lesson_mdl');

        //월~일이 주기 마감
        // 주기변경으로  1월 2일 06시~ 1월 10일 24시까지 특수주기 적용
        if(date('Y-m-d') <= '2021-01-11')
        {
            $start_date = '2021-01-02 06:00:00';
            $end_date = '2021-01-10 23:59:59';
        }
        else
        {
            // 기본 주기 월~일
            $start_date = date('Y-m-d 00:00:00',strtotime('-7 day')); // 지난 주의 월요일
            $end_date = date('Y-m-d 23:59:59',strtotime('-1 day')); // 일요일

            // 일 : 0 / 월 : 1 / 화 : 2 / 수 : 3 / 목 : 4 / 금 : 5 / 토 : 6
            // 계산된 주기가 시작일은 월요일, 종료일은 일요일이 아니라면 즉시 종료
            if(date('w',strtotime($start_date)) != 1 || date('w',strtotime($end_date)) != 0) exit;

        }

        $start_day = substr($start_date,0,10);
        $end_day = date('Y-m-d',strtotime('-1 day',time()));

        //$start_date = '2020-12-19';
        //$end_date = '2020-12-25';

        // 이전 주기 자유 수업한 유저별 수업한 갯수 카운트 구한다. kind : f, n, t
        // wiz_lesson에 startday가 미래날짜라면 아직 자유수업 출석부가 수업 의무횟수를 부여하지 않았으므로 결석대상에서 제외해야한다.
        $data = $this->lesson_mdl->checked_count_prev_week_free_schedule($start_date, $end_date);

        if($data)
        {
            // lesson 별 해야하는 수업갯수, 실제로 진행한 수업갯수 데이터로 결석처리
            foreach($data as $val)
            {
                
                // 출석부의 토탈 수업 횟수. 정규횟수 + 포인트 추가 횟수
                // tt는 믿으면 안되는 데이터. tt_add만 증감해주는곳도 있고 tt와 tt_add둘다 증감되는 곳이 있어서 불확실한데이터.
                // 그래서 tt 대신 기본 수업갯수인 cl_class 데이터를 사용
                $total_class_cnt = $val['wl_cl_class'] + $val['wl_tt_add'];

                // cnt: 스케쥴 배정된 총 수업갯수
                $done_class_total_cnt = $this->lesson_mdl->checked_count_spend_schedule($val['wl_lesson_id']);
                
                // 소진못한 누적 자유수업결석 분
                $absent_total_cnt = $this->lesson_mdl->checked_count_free_schedule_absent($val['wl_lesson_id']);

                // 소진하지않은 총 수업갯수. 수업대기나, 출석했거나, 배정했는데 결석처리 분과 배정못하고 소진못한 결석 분을 같이 빼줘야한다.
                $remain_class_total_cnt = (int)$total_class_cnt - (int)$done_class_total_cnt['cnt'] - (int)$absent_total_cnt['cnt'];

                // 가지고 있는 총 수업갯수를 다 소모했으면 결석처리 하지 않는다.
                // 여기에 걸리는 경우는 토요일 0~1시 에 배정된 수업이 마지막 수업이 있을때 걸리게된다.
                if($remain_class_total_cnt < 1) continue;

                // 부여횟수 - 배정횟수 = 미배정갯수(결석처리할 횟수)
                $absent_cnt = $val['wl_cl_number'] - $val['cnt'];

                if($absent_cnt > 0)
                {
                    $param = [
                        'uid'       => $val['wl_uid'],
                        'lesson_id' => $val['wl_lesson_id'],
                        'count'     => $absent_cnt,
                        'cl_number' => $val['wl_cl_number'],
                        'startday'  => $start_day,
                        'endday'    => $end_day,
                        'logdate'   => date('Y-m-d H:i:s'),
                    ];

                    $this->lesson_mdl->insert_free_schedule_absent($param);

                    // 만약 이번 결석 처리로 총 수업횟수가 0회로 떨어졌다면 수업 종료된것 이므로 lesson_state=finished 해준다.
                    if($remain_class_total_cnt-$absent_cnt < 1)
                    {
                        $update_param = [
                            'lesson_state' => 'finished'
                        ];

                        $this->lesson_mdl->update_wiz_lesson($val['wl_lesson_id'], $update_param);
                    }
                }
            }

        }
        
    }

    /* 자유수업 이번주기 소모안했으면 소모하라고 문자 날려주는 함수
       free_lesson_absent 함수와 프로세스 거의 동일하다. 마지막에 결석테이블에 insert 대신 문자 전송해준다.
       매주 토요일에 크론에서 실행된다.
       ------------------------------------
       주기변경
       월 ~ 일
    */
    public function send_sms_this_week_free_class_remain()
    {
        $this->load->model('lesson_mdl');

        //월~일이 주기 마감
        // 주기변경으로  1월 2일 06시~ 1월 10일 24시까지 특수주기 적용
        if(date('Y-m-d') < '2021-01-11')
        {
            $start_date = '2021-01-02 06:00:00';
            $end_date = '2021-01-10 23:59:59';
        }
        else
        {
            // 기본 주기 월~일
            $start_date = date('Y-m-d 00:00:00',strtotime('-5 day')); // 지난 주의 월요일
            $end_date = date('Y-m-d 23:59:59',strtotime('+1 day')); // 일요일

            // 일 : 0 / 월 : 1 / 화 : 2 / 수 : 3 / 목 : 4 / 금 : 5 / 토 : 6
            // 계산된 주기가 시작일은 월요일, 종료일은 일요일이 아니라면 즉시 종료
            if(date('w',strtotime($start_date)) != 1 || date('w',strtotime($end_date)) != 0) exit;
        }

        //$start_date = '2020-12-26 06:00:00';
        //$end_date = '2020-12-29 00:59:59';

        // 이전 주기 자유 수업한 유저별 수업한 갯수 카운트 구한다. kind : f
        // wiz_lesson에 startday가 미래날짜라면 아직 자유수업 출석부가 수업 의무횟수를 부여하지 않았으므로 결석대상에서 제외해야한다.
        $data = $this->lesson_mdl->checked_count_prev_week_free_schedule($start_date, $end_date);

        $sended_phone = array();
        if($data)
        {
            foreach($data as $val)
            {
                if(in_array($val['wl_mobile'],$sended_phone)) continue;

                // 출석부의 토탈 수업 횟수. 정규횟수 + 포인트 추가 횟수
                // tt는 믿으면 안되는 데이터. tt_add만 증감해주는곳도 있고 tt와 tt_add둘다 증감되는 곳이 있어서 불확실한데이터.
                // 그래서 tt 대신 기본 수업갯수인 cl_class 데이터를 사용

                /*
                주기 변경으로 일요일 자정에 주기가 종료되므로 아래소스는 필요없을것

                $total_class_cnt = $val['wl_cl_class'] + $val['wl_tt_add'];

                // cnt: 스케쥴 배정된 총 수업갯수
                 $done_class_total_cnt = $this->lesson_mdl->checked_count_spend_schedule($val['wl_lesson_id']);
                
                // 소진못한 누적 자유수업결석 분
                $absent_total_cnt = $this->lesson_mdl->checked_count_free_schedule_absent($val['wl_lesson_id']);

                // 소진하지않은 총 수업갯수. 수업대기나, 출석했거나, 배정했는데 결석처리 분과 배정못하고 소진못한 결석 분을 같이 빼줘야한다.
                $remain_class_total_cnt = (int)$total_class_cnt - (int)$done_class_total_cnt['cnt'] - (int)$absent_total_cnt['cnt'];

                // 가지고 있는 총 수업갯수를 다 소모했으면 SMS문자 보내지 않는다
                // 여기에 걸리는 경우는 토요일 0~1시 에 배정된 수업이 마지막 수업이 있을때 걸리게된다.
                if($remain_class_total_cnt < 1) continue;
                */

                // 부여횟수 - 배정횟수 = 미배정갯수(결석처리할 횟수)
                $absent_cnt = $val['wl_cl_number'] - $val['cnt'];

                if($absent_cnt > 0)
                {
                    // 얼람톡 전송
                    $option = [
                        'uid'       => $val['wl_uid'],
                        'wiz_id'    => $val['wl_wiz_id'],
                        'name'      => $val['wl_name'],
                        'man_name'  => '__SYSTEM__',
                        'sms_push_yn' => 'Y',
                        'sms_push_code' => '327',
                    ];

                    //echo $val['wl_mobile'].PHP_EOL;continue;
                    //sms::send_sms($val['wl_mobile'], '327', $option);
                    // 문자대신 알림톡으로 변경
                    sms::send_atalk($val['wl_mobile'], 'MINT06004M', $option);
                    $sended_phone[] = $val['wl_mobile'];
                }
            }

        }
        
    }


    /**
     * 자유수업 버전1-> 버전2 전환용 배치소스
     */
    public function free_lesson_version_trans()
    {
        // 반영했으므로 이제 안씀
        exit;
        $this->load->model('lesson_mdl');

        $target_tu_uid = 1475;

        // wiz_lesson 에 1475=자유강사 지정되어있는 출석부 가져온다
        $data = $this->lesson_mdl->list_lesson_by_tu_uid($target_tu_uid);

        if($data)
        {
            $total = count($data);
            echo 'total : '. $total;
            $process_cnt = 1;
            foreach($data as $lesson)
            {
                $lesson_id = $lesson['wl_lesson_id'];
                echo 'lesson_id :'.$lesson_id.' START'.PHP_EOL;

                $endday = '';
                // wiz_lesson에 업데이트 해줄 변수 초기화
                $update_param = [
                    'cl_gubun' => 2,
                    'tu_uid' => 0,
                    'tu_name' => '',
                ];
                
                // 미래수업데이터 전부 삭제. endday 구해야함
                if($lesson['wl_lesson_state'] =='in class')
                {
                    // 마지막 수업의 해당 주 금요일이 마지막 요일
                    $last_class = $this->lesson_mdl->row_last_class($lesson_id);

                    echo $last_class['ws_startday']. ' ->   ';
                    if($last_class)
                    {
                        $endday = substr($last_class['ws_startday'],0,10);
                        if(date('w', strtotime($endday)) == 6)
                        {
                            $endday = date('Y-m-d', strtotime('-1 day', strtotime($endday)));
                            
                        }
                        else
                        {
                            for($i=0;$i<8;$i++)
                            {
                                // 오늘부터 하루씩 증가시켜서 금요일인지 확인
                                if(date('w',strtotime($endday)) == 5) break;
                                else $endday = date('Y-m-d', strtotime('+1 day', strtotime($endday) ));
                            }
                            
                        }

                        echo $endday.PHP_EOL;
                        
                        $update_param['endday'] = $endday;
                    }

                    // 1475(자유강사uid)로 잡혀있는 스케쥴 전부 삭제
                    $this->lesson_mdl->delete_free_tutor_schedule($lesson_id);

                }
                // 연기상태인 수업데이터 tu_uid 158, postpone 전부 삭제
                elseif($lesson['wl_lesson_state'] =='holding')
                {
                    $this->lesson_mdl->delete_postpone_schedule_by_lesson_id($lesson_id);
                }
                // 수업끝난건 그냥 cl_gubun 만 2로 바꿔주자
                elseif($lesson['wl_lesson_state'] =='finished')
                {
                    // 여기서 해줄것없음
                }
                // 이런경우는 없을거 같은데 이거도 cl_gubun 만 2로 바꿔주자
                elseif($lesson['wl_lesson_state'] =='')
                {
                    // 여기서 해줄것없음
                }

                //wiz_lesson 업뎃
                $this->lesson_mdl->update_wiz_lesson($lesson_id, $update_param);

                echo 'lesson_id :'.$lesson_id. ' END '. $process_cnt.'/'.$total.PHP_EOL;
                $process_cnt++;
                
            }

        }
        
    }

    /* 
        비동기 sms 보내기
        우선순위
        1. 카카오 알림톡 전송
        2. 카카오 알림톡 실패시 sms 전송
    */
    function notify_send_sms($wm_uid, $atalk_code, $sms_id)
    {
        $request = array(
            "wm_uid" => trim($wm_uid),
            "atalk_code" => trim($atalk_code),
            "sms_id" => trim($sms_id),
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

        $this->load->model('member_mdl');        

        $wiz_member = $this->member_mdl->get_wiz_member_by_wm_uid($request['wm_uid']);
        
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
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

        // 카카오 알림톡 옵션
        $options = NULL;
        $send_atalk = NULL;
        $send_sms = NULL;
        
        // 카카오 알림톡 코드 설정값    
        $CONFIG_ATALK_CODE = $this->config->item('ATALK_CODE');
        $CONFIG_SMS_ID = $this->config->item('SMS_ID');
        
        /*
            옵션 세팅
            $CONFIG_ATALK_CODE['APPLY_LEVELTEST_TEL'] == 레벨테스트 신청완료
            $CONFIG_ATALK_CODE['APPLY_LEVELTEST_MEL'] == 레벨테스트 신청완료
            $CONFIG_SMS_ID['APPLY_LEVELTEST_TEL'] == 레벨테스트 신청완료
            $CONFIG_SMS_ID['APPLY_LEVELTEST_MEL'] == 레벨테스트 신청완료
        */
        if($request['atalk_code'] == $CONFIG_ATALK_CODE['APPLY_LEVELTEST_TEL'] || $request['atalk_code'] == $CONFIG_ATALK_CODE['APPLY_LEVELTEST_MEL'] 
        || $request['sms_id'] == $CONFIG_SMS_ID['APPLY_LEVELTEST_TEL'] || $request['sms_id'] == $CONFIG_SMS_ID['APPLY_LEVELTEST_MEL'] )
        {
            $this->load->model('leveltest_mdl');
            $leveltest = $this->leveltest_mdl->check_leveltest_exist_asc($request['wm_uid']);

            $options = array(
                'uid' => $wiz_member['wm_uid'],
                'wiz_id' => $wiz_member['wm_wiz_id'],
                'name' => $display_name,
                'date' => substr($leveltest['le_start'], 0, 10),
                'time' => substr($leveltest['le_start'], 11, -3),
            );
        }

        // 카카오 알림톡 발송 및 발송 이력 저장
        if($wiz_member['wm_mobile'] && $options)
        {
            $send_atalk = sms::send_atalk($wiz_member['wm_mobile'], $request['atalk_code'], $options);

            // 알림톡 전송 실패시 SMS 전송
            if($send_atalk['state'] != TRUE)
            {
                $send_sms = sms::send_sms($wiz_member['wm_mobile'], $request['sms_id'], $options);
            }
        }
    }



    
    //추가된 뱃지 -> 조건에 맞는 유저에게 지급
    public function set_badge_in_user()
    {
        
        $return_array = array();

        $request = array(
            "count" => $this->input->post('count') ? $this->input->post('count') : 0,
            "badge_id" => $this->input->post('badge_id') ? $this->input->post('badge_id') : NULL,
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

        $this->load->model('badge_mdl');

        $list = $this->badge_mdl->check_count_cafeboards($request['count']);

        if($list)
        {
            $multi_data = array();
            $now = date('Y-m-d H:i:s');

            for($i=0; $i<count($list); $i++)
            {
                $data = array(
                    'uid' => $list[$i]['uid'],
                    'badge_id' => $request['badge_id'],
                    'use_yn' => 'N',
                    'regdate' => $now
                );

                array_push($multi_data, $data);
            }
            
            $result = $this->badge_mdl->insert_batch_badge($multi_data);
            
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
                $return_array['msg'] = "뱃지 추가를 성공했습니다.";
                echo json_encode($return_array);
                exit;
            }
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

    }

    public function board_list_count_update()
    {
        $this->load->model('board_mdl');
        
        $update_list_count_board_certify = $this->board_mdl->update_list_count_board_certify();
        $update_list_count_board_hot = $this->board_mdl->update_list_count_board_hot();
        $update_list_count_board_new = $this->board_mdl->update_list_count_board_new();
        $update_list_count_board_notice = $this->board_mdl->update_list_count_board_notice();

        exit;
    }

    public function board_comment_list_count_update()
    {

        $this->load->model('board_mdl');
        $update_list_count_comment = $this->board_mdl->update_list_count_comment();

        exit;
    }

    public function board_delete_search_boards($table_code, $mb_unq)
    {
        $this->load->model('board_mdl');
        $result = $this->board_mdl->delete_search_boards($table_code, $mb_unq);
        exit;
    }

    public function board_insert_search_boards($table_code, $mb_unq)
    {
        /* 특수게시판 테이블 코드 변환 */
        $this->load->model('board_mdl');

        //log_message('error', 'board_insert_search_boards start :'.date('Y-m-d H:i:s'));

        if($table_code == '9001')   // 이런표현 어떻게
        {
            $row_board_by_mb_unq = $this->board_mdl->row_article_express_by_uid($mb_unq);
            $row_board_by_mb_unq['mb_ename'] = $row_board_by_mb_unq['wm_ename'];
            $row_board_by_mb_unq['mb_nickname'] = $row_board_by_mb_unq['wm_nickname'];

        }
        else if($table_code == '9002')  // 얼철딕
        {
            $row_board_by_mb_unq = $this->board_mdl->row_article_cafeboard_by_pk($mb_unq);
            $row_board_by_mb_unq['mb_unq'] = $row_board_by_mb_unq['mb_c_uid'];
            $row_board_by_mb_unq['mb_nickname'] = $row_board_by_mb_unq['wm_nickname'];
        }
        else if($table_code == '9004')  // 영어 첨삭
        {
            $row_board_by_mb_unq = $this->board_mdl->row_article_wiz_correct_by_pk($mb_unq);
            $row_board_by_mb_unq['mb_unq'] = $row_board_by_mb_unq['mb_w_id'];
            $row_board_by_mb_unq['mb_nickname'] = $row_board_by_mb_unq['wm_nickname'];
            $row_board_by_mb_unq['mb_secret'] = $row_board_by_mb_unq['mb_w_secret'];
        }
        else    // 일반 게시판
        {
            // $row_search_boards_by_mb_unq = $this->board_mdl->row_search_boards_by_mb_unq($table_code, $mb_unq);
            
            $row_board_by_mb_unq = $this->board_mdl->row_board_by_mb_unq($table_code, $mb_unq);

        }

        if(!$row_board_by_mb_unq['mb_unq'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "등록에 실패했습니다";
            echo json_encode($return_array);
            exit;
        }

        $search_boards_params = array(
            'table_code' => $table_code, 
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
        
        //log_message('error', 'board_insert_search_boards end :'.date('Y-m-d H:i:s'));

        /* 데이터 인설트 */
        $result = $this->board_mdl->insert_search_boards($table_code, $search_boards_params);
        
        if($result > 0)
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "data success";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "data fail";
            echo json_encode($return_array);
            exit;
        }

    }

    // 카테고리 코드category_code와 익명여부 anonymous_yn 업뎃
    public function update_search_boards()
    {
        $this->load->model('board_mdl');

        $annoy = [ '1343', '1134', '1336', '1335', '1366', '1367'];

        $limit = 1000;
        $offset = 0;

        $data = null;
        do
        {
            echo 'start: '.$offset.PHP_EOL;

            $data = $this->board_mdl->list_cate_anony_boards_data($limit, $offset);


            if($data)
            {

                echo 'count: '.count($data).PHP_EOL;


                foreach($data as $row)
                {
                    $update = [];

                    if(in_array($row['table_code'],$annoy) || $row['anonymous_yn'] == 'Y')
                    {
                        $update['anonymous_yn'] = 'Y';
                    }

                    if($row['category_code'])
                    {
                        $update['category_code'] = $row['category_code'];
                    }

                    if(!empty($update))
                    {
                        $this->board_mdl->update_search_db($update, $row['table_code'], $row['mb_unq']);
                    }
                }
            }

            

            echo 'end: '.$offset.PHP_EOL;
            echo 'end_mb_unq: '.$row['mb_unq'].PHP_EOL;


            $offset = $offset + $limit;

        }
        while($data != null);
        

        echo 'COMPLETE';
        exit;
    }



    public function update_origin_cl_class()
    {

        $this->load->model('lesson_mdl');

        $limit = 10000;
        $offset = 0;

        $data = null;
        do
        {
            echo 'start: '.$offset.PHP_EOL;

            $data = $this->lesson_mdl->list_lesson_data_for_cl_class($limit, $offset);

            if($data)
            {

                echo 'count: '.count($data).PHP_EOL;


                foreach($data as $row)
                {
                    //$row['pay_name'] = '주3회 10분 12개월 (Phone) 수강신청';
                    //주2회 20분 3개월 (Phone) 수강신청
                    preg_match('/주([0-9]+)회\s*[0-9]+분\s*([0-9]+)개월/Usim',$row['pay_name'],$matches);
                    //echo $row['pay_name'].':';
                    if($matches[1] && $matches[2])
                    {
                        $update['origin_cl_class'] = (int)$matches[1] * (int)$matches[2] * 4;
                        //echo $update['origin_cl_class'].PHP_EOL;
                        
                    }
                    else
                    {
                        $update['origin_cl_class'] = $row['cl_class'];
                    }
                    
                    
                    $this->lesson_mdl->update_wiz_lesson($row['lesson_id'], $update);
                }
            }

            

            echo 'end: '.$offset.PHP_EOL;
            echo 'end_lesson: '.$row['lesson_id'].PHP_EOL;


            $offset = $offset + $limit;

        }
        while($data != null);
        

        echo 'COMPLETE';
        exit;
    }

    
    
    /*
        딕테이션 해결사 답변들 중 1주일 지난 이후에도 채택되지 않은 글이 있으면 먼저 달린 답변으로 자동 채택        
    */
    public function dictation_solution_child_select()
    {
        // 지식인 게시판과 병합으로 본 함수는 쓰지 않는다.
        exit;
        $this->load->model('board_mdl');

        // 딕테이션 해결사 부모 글중 아직 채택이 안된 글들만 조회
        $parent_lists = $this->board_mdl->list_dictation_solution_parents();
        $count = 0;
        $str = '';

        if($parent_lists)
        {
            for($i=0; $i<count($parent_lists); $i++)
            {
                // 채택이 안된 글중 자식 답변 글의 등록 날짜가 1주일이상 지난 목록중 가장 빨리 답글을 단 게시물 조회
                $child_lists = $this->board_mdl->min_regdate_dictation_solution_child_by_parent_key($parent_lists[$i]['mb_mb_unq']);

                if($child_lists['min_mb_unq'])
                {
                    $message = '작성하신 게시글에 '.$parent_lists[$i]['mb_nickname'].' 님으로부터 자동 채택됐습니다.';
                    
                    // 최소값이 조회되면 업데이트(부모글에 select_key 업데이트, 자식글에 sim_content3(메시지), 별점추가)
                    $result = $this->board_mdl->update_dictation_solution_parent_select_key($parent_lists[$i]['mb_mb_unq'], $child_lists['min_mb_unq'], $message);

                    /* 게시글 작성자 알림 */
                    $notify = array(
                        'uid' => $child_lists['wm_uid'],
                        'code' => 102, 
                        'message' => $message, 
                        'table_code' => $child_lists['mb_table_code'],
                        'user_name' => $child_lists['wm_name'],
                        'board_name' => $child_lists['mbn_table_name'], 
                        'content'=> $child_lists['mb_content'], 
                        'mb_unq' => $child_lists['min_mb_unq'],
                        'co_unq' => NULL,
                        'regdate' => date('Y-m-d H:i:s'),
                    );

                    $this->load->model('notify_mdl');
                    $notify_result = $this->notify_mdl->insert_notify($notify);
        

                    /* 
                        포인트 추가 
                        유저가 선택한 포인트의 80%만 지급
                    */

                    $set_point = ($parent_lists[$i]['mb_set_point']) * 0.8;
                    $pt_name = $parent_lists[$i]['mbn_table_name'].'의 '.$parent_lists[$i]['mb_name'].' 님으로부터 채택받았습니다.'.$set_point.' 포인트 선물 적립';

                    $point = array(
                        'uid' => $child_lists['wm_uid'],
                        'name' => $child_lists['wm_name'],
                        'point' => $set_point,
                        'pt_name'=> $pt_name, 
                        'kind'=> 'x',                                   // x 는 게시물로 얻은 포인트
                        'b_kind'=> 'boards',
                        'table_code'=> $parent_lists[$i]['mb_table_code'],
                        'co_unq'=> $child_lists['min_mb_unq'],          // kind가 x일때 co_unq 는 mb_unq
                        'showYn'=> 'y',
                        'secret'=> 'N',
                        'regdate' => date("Y-m-d H:i:s")
                    );

                    /* 포인트 내역 입력 및 포인트 추가 */
                    $this->load->model('point_mdl');
                    $point = $this->point_mdl->set_wiz_point($point);

                    
                    //
                    if($result > 0)
                    {
                        $count++;
                        $str .= $parent_lists[$i]['mb_mb_unq'].' / ';
                    }
                } 
                else
                {
                    continue;
                }
                
            }
        }

        log_message('error', 'update_count :'.$count);
        log_message('error', 'update_str :'.$str);
        echo 'COMPLETE';
        exit;

    }


    /*
        답변들 중 1주일 지난 이후에도 채택되지 않은 글이 있으면 먼저 달린 답변으로 자동 채택        
    */
    public function auto_adopt_after_a_week()
    {
        // 지식인형태 게시판코드 + 9001(이런표현어떻게는 list_article_unadopted_mint_board 모델에 하드코딩되어있다.)
        $knowledge_qna_type_board = ['1120','1102','1337','1141','1138'];

        // 해당날짜 이전꺼는 채택안해준다.
        $min_date = '2021-03-17 10:15:00';

        $this->load->model('board_mdl');
        $this->load->model('member_mdl');
        $this->load->model('notify_mdl');
        $this->load->model('point_mdl');

        //지식인 부모 글중 아직 채택이 안된 글들만 조회
        $mb_list = $this->board_mdl->list_article_unadopted_mint_board($knowledge_qna_type_board, $min_date);

        log_message('error', 'auto_adopt_after_a_week_count :'.( $mb_list ? count($mb_list):0));

        $complete_cnt = 0;

        if($mb_list)
        {
            foreach($mb_list as $row)
            {
                // 답변 안달렸으면 패스
                if($row['a_mb_unq'] =='') continue;

                // 답변글 정보 불러오기
                $content = '';
                if($row['mb_table_code'] =='9001')
                {
                    $q_article = $this->board_mdl->row_article_express_by_mb_uid($row['mb_unq']);       // 질문자 글
                    $a_article = $this->board_mdl->row_article_express_by_mb_uid($row['a_mb_unq']);     // 답변자 글
                    $content = $a_article['mb_title'];
                    $a_name = $a_article['mb_m_name'];
                }
                else
                {
                    $q_article = $this->board_mdl->row_article_by_mb_unq($row['mb_table_code'], $row['mb_unq']);        // 질문자 글
                    $a_article = $this->board_mdl->row_article_by_mb_unq($row['mb_table_code'], $row['a_mb_unq']);      // 답변자 글
                    $content = $a_article['mb_content'];
                    $a_name = $a_article['mb_name'];
                }
            
                if($q_article)
                {
                    $q_article = board_article_writer($q_article);
                }
                else
                {
                    continue;
                }

                //답변달린지 1주일 지난 건만 채택
                if($a_article['mb_regdate'] > date('Y-m-d H:i:s',strtotime('-7 day')))
                {
                    continue;
                }

                $message = '작성하신 답변글이 '.($q_article['display_name'] ? $q_article['display_name']:'익명').' 님으로부터 자동 채택됐습니다.';

                // 채택받을 회원 정보
                $tmp_wm = $this->member_mdl->get_wm_uid_by_wiz_id($a_article['mb_wiz_id']);
                $adopted_wm_uid = $tmp_wm['wm_uid'];    

                $datas = array(
                    'selected_uid'    => $adopted_wm_uid,  //채택된유저 UID
                    'mb_unq'          => $row['mb_unq'],
                    'select_key'      => $row['a_mb_unq'],
                    'table_code'      => $row['mb_table_code'],
                    'star'            => '5',
                    'adopt_type'      => 1,      // 1:질문자채택, 2:시스템채택
                    'sim_content3'    => $message,
                );
        
                $result = $this->board_mdl->knowledge_adopt_article($datas);

                if($result > 0)
                {
                    
                    /*
                        딕테이션 해결사 답변 100회 이상 채택인 유저 뱃지 추가
                    */
                    $badge_award_message = null;
                    $dictation_badge = $this->member_mdl->get_badge('dictation', 'solution');
                    $member_dictation_badge = $this->member_mdl->get_member_badge($adopted_wm_uid, $dictation_badge['wb_id']);

                    if($dictation_badge && !$member_dictation_badge){
                        
                        //딕테이션 채택을 100회 받았는지 체크/ 뱃지 지급
                        $adopt_count = $this->board_mdl->list_count_adopt_by_uid($adopted_wm_uid, '1138');
                        
                        $badge_award_message = $dictation_badge['wb_award_message'];

                        if($adopt_count['cnt'] >= 50){

                            $datas = array(
                                "uid" => $adopted_wm_uid,
                                "badge_id" => $dictation_badge['wb_id'],
                                "use_yn"=> 'N',
                                'regdate' => date("Y-m-d H:i:s"),
                            );
                            
                            $result_badge = $this->badge_mdl->insert_badge_message($datas, $badge_award_message);
                        }
                    }
                    
                    
                    if($row['mb_table_code'] =='1138')
                    {
                        $set_point = $row['mb_set_point'] * 0.8;
                    }
                    else
                    {
                        $set_point = $q_article['mbn_user_adopt_reward_point'];
                    }

                    if($set_point > 0)
                    {
                        //유저채택 보상 포인트
                        $pointparam = array(
                            'uid' => $adopted_wm_uid,
                            'point' => $set_point,
                            'name' => $a_name,
                            'pt_name'=> ($q_article['display_name'] ? $q_article['display_name']:'익명').' 님으로 부터의 자동채택 포인트보상', 
                            'kind' => 'kg',
                            'b_kind' => 'boards',
                            'co_unq'=> $row['a_mb_unq'], 
                            'showYn'=> 'y',
                            'regdate' => date("Y-m-d H:i:s")
                        );
                        /* 포인트 내역 입력 및 포인트 추가 */
                        $this->point_mdl->set_wiz_point($pointparam);
                    }

                    /* 답변 게시글 작성자 알림 */
                    $notify = array(
                        'uid'   => $adopted_wm_uid,
                        'code'  => 102, 
                        'message' => $message, 
                        'table_code' => $a_article['mb_table_code'] =='express' ? 'express.view':$a_article['mb_table_code'],
                        'user_name' => '__SYSTEM__',
                        'board_name' => $a_article['mbn_table_name'], 
                        'content'=> $content, 
                        'mb_unq' => $row['a_mb_unq'],
                        'co_unq' => NULL,
                        'parent_key' => $row['mb_unq'], 
                        'regdate' => date('Y-m-d H:i:s'),
                    );
                    
                    $this->notify_mdl->insert_notify($notify);

                    $complete_cnt++;
                }
                


            }
        }

        log_message('error', 'auto_adopt_after_a_week_update_END :'.date('Y-m-d H:i:s'). '/ cnt: '. $complete_cnt);

        echo 'COMPLETE';
        exit;

    }
    
    //민트 업적 누적형 이전에 글쓰고 로그인한것들 인정해주기 위해 갯수 넘은 업적은 강제로 완료 시켜준다
    public function mint_quest_forced_complete_past_done()
    {
        set_time_limit(0);
        ini_set('memory_limit','-1');

        $this->load->model('quest_mdl');
        //$this->quest_mdl->set_global_time_out();

        //********회원가입은 아래 쿼리를 날려서 일괄로 넣어준다********
        //INSERT INTO mint_quest_progress (q_idx, uid, progress, start_date, complete_date) SELECT 4,uid,1,NOW(),NOW() FROM wiz_member 

        //회원리스트.
        //$mb_list = $this->board_mdl->list_article_unadopted_mint_board($knowledge_qna_type_board, $min_date);
        echo 'START :'.date('Y-m-d H:i:s').PHP_EOL;
        
        //누적시켜줘야하는 퀘스트
        $quest_list = $this->quest_mdl->list_quest_for_batch();

        //업적별 하위퀘스트 정리
        $quest_list_group = [];
        foreach($quest_list as $row)
        {
            $quest_list_group[$row['parent_q_idx']][] = $row;
        }


        //엠셋
        /*  $userlist = $this->quest_mdl->list_mset_groupby_uid_quest();
        $code = 20; //엠셋 상위퀘번호
        foreach($userlist as $row)
        {
            MintQuest::quest_batch($quest_list_group[$code], $row, $code);
        }

        echo 'MSET COMPLETE:'.date('Y-m-d H:i:s').PHP_EOL;
        

        //얼철딕
        $userlist = $this->quest_mdl->list_dictation_groupby_uid_quest();
        $code = 53; //얼철딕 상위퀘번호
        foreach($userlist as $row)
        {
            MintQuest::quest_batch($quest_list_group[$code], $row, $code);
        }

        echo 'dictation COMPLETE:'.date('Y-m-d H:i:s').PHP_EOL;

        
        //wiz_schedule kind: c 벼락치기

        //벼락치기 수업
        $userlist = $this->quest_mdl->list_thunder_groupby_uid_quest();
        $code = 99; // 벼락치기 상위퀘번호
        foreach($userlist as $row)
        {
            MintQuest::quest_batch($quest_list_group[$code], $row, $code);
        }

        echo 'thunder COMPLETE:'.date('Y-m-d H:i:s').PHP_EOL;

        //첨삭
        $userlist = $this->quest_mdl->list_correction_groupby_uid_quest();
        $code = 158; //첨삭 상위퀘번호
        foreach($userlist as $row)
        {
            MintQuest::quest_batch($quest_list_group[$code], $row, $code);
        }

        echo 'correct COMPLETE:'.date('Y-m-d H:i:s').PHP_EOL;
         
        
        //수업대본
        $userlist = $this->quest_mdl->list_script_groupby_uid_quest();
        $code = 191; //수업대본 상위퀘번호
        foreach($userlist as $row)
        {
            MintQuest::quest_batch($quest_list_group[$code], $row, $code);
        }

        echo 'script COMPLETE:'.date('Y-m-d H:i:s').PHP_EOL; 
        
        
        //로그잇횟수
        $userlist = $this->quest_mdl->list_member_groupby_uid_quest();
        $code = 110; //로그인 횟수 상위퀘번호
        foreach($userlist as $row)
        {
            MintQuest::quest_batch($quest_list_group[$code], $row, $code);
        }

        echo 'login COMPLETE:'.date('Y-m-d H:i:s').PHP_EOL;
        

        //게시글작성.
        $userlist = $this->quest_mdl->list_board_write_groupby_uid_quest();
        $code = 122; //커뮤니티 게시글 작성하기 상위퀘번호
        foreach($userlist as $row)
        {
            MintQuest::quest_batch($quest_list_group[$code], $row, $code);
        }

        echo 'community COMPLETE:'.date('Y-m-d H:i:s').PHP_EOL;

        

        //댓글작성. 사용자 게시판 집계. 얼철딕포함
        $userlist = $this->quest_mdl->list_board_reply_groupby_uid_quest();
        $code = 145; //댓글작성 상위퀘번호
        foreach($userlist as $row)
        {
            MintQuest::quest_batch($quest_list_group[$code], $row, $code);
        }

        echo 'community reply COMPLETE:'.date('Y-m-d H:i:s').PHP_EOL;

        

        
        //다양한선생님과 수업해보기
        $userlist = $this->quest_mdl->list_done_class_tutor_groupby_uid_quest();
        $code = 215; //다양한선생님 상위퀘번호
        foreach($userlist as $row)
        {
            MintQuest::quest_batch($quest_list_group[$code], $row, $code);
        }

        echo 'variable tutor COMPLETE:'.date('Y-m-d H:i:s').PHP_EOL;

        

        //수업체험후기에 글쓰기
        $userlist = $this->quest_mdl->list_review_write_groupby_uid_quest();
        $code = 235; //수업체험후기에 글쓰기 상위퀘번호
        foreach($userlist as $row)
        {
            MintQuest::quest_batch($quest_list_group[$code], $row, $code);
        }

        echo 'review write COMPLETE:'.date('Y-m-d H:i:s').PHP_EOL;
        
        
        //딕테이션에 답변글 달기
        $userlist = $this->quest_mdl->list_dictation_anwser_groupby_uid_quest();
        $code = 213; //딕테이션에 답변글 상위퀘번호
        foreach($userlist as $row)
        {
            MintQuest::quest_batch($quest_list_group[$code], $row, $code);
        }

        echo 'dictation write COMPLETE:'.date('Y-m-d H:i:s').PHP_EOL;

        */

        //수업 출석일수
        $userlist = $this->quest_mdl->list_class_attandance_groupby_uid_quest();
        $code = 181; //수업 출석일수 상위퀘번호
        foreach($userlist as $row)
        {
            MintQuest::quest_batch($quest_list_group[$code], $row, $code);
        }

        echo 'class attandance COMPLETE:'.date('Y-m-d H:i:s').PHP_EOL;
        
        /* 불가목록
        -브레인워시
        -커리큘럼정복
        -게시글공유 */

        echo 'COMPLETE :'.date('Y-m-d H:i:s').PHP_EOL;
        exit;

    }

    //배치로 받아 퀘스트 실행
    public function request_batch_quest($requested_q_idx, $uid, $code='',$REQUEST_URI='')
    {
        if(!$requested_q_idx) exit;

        $code = $code == 'null' ? '':$code;

        //로그저장용 변수 세팅
        $_SERVER['REQUEST_URI'] = str_replace('__','/',$REQUEST_URI);
        
        $q_idx_arr = explode('_',$requested_q_idx);

        if($q_idx_arr)
        {
            foreach($q_idx_arr as $q_idx)
            {
                if(!$q_idx) continue;

                $aa = MintQuest::getInstance($uid)->do_quest($q_idx, $code);
                //log_message('error', 'test :'.http_build_query($aa));
            }
        }
        
    }
        
    //배치로 받아 퀘스트 차감 실행
    public function request_batch_quest_decrement($requested_q_idx, $uid, $code='',$REQUEST_URI='')
    {
        if(!$requested_q_idx) exit;

        $code = $code == 'null' ? '':$code;

        //로그저장용 변수 세팅
        $_SERVER['REQUEST_URI'] = str_replace('__','/',$REQUEST_URI);
        
        $q_idx_arr = explode('_',$requested_q_idx);

        if($q_idx_arr)
        {
            foreach($q_idx_arr as $q_idx)
            {
                if(!$q_idx) continue;
                
                $aa = MintQuest::getInstance($uid)->quest_decrement($q_idx, $code);
                //log_message('error', 'request_batch_quest_decrement :'.http_build_query($aa));
            }
        }
    }
    
    /*
        누적형퀘스트 1뎁스인것들 전부 조회해서 테이블 없으면 만든다
    */
    public function create_quest_log_table()
    {
        $this->load->model('quest_mdl');

        $schema = $this->quest_mdl->schema_quest_log_table();
        if(!$schema) 
        {
            echo 'Fail';
            exit;
        }
        $schema = $schema['Create Table'];
        
        //누적형업적 1뎁스인것들 전부 조회
        $list = $this->quest_mdl->list_type2_depth1_quest();

        foreach($list as $row)
        {
            $create_code = str_replace('CREATE TABLE','CREATE TABLE IF NOT EXISTS',$schema);
            $create_code = str_replace('`mint_quest_progress_log`','db_quest_log.`mint_quest_progress_log_'.$row['q_idx'].'`',$create_code);
            $create_code = preg_replace('/AUTO_INCREMENT=.*DEFAULT/','AUTO_INCREMENT=1 DEFAULT',$create_code);
            
            $result = $this->quest_mdl->create_quest_log_table($create_code);

            if($result < 0)
            {
                echo $row['q_idx'].$row['title'].' ERROR';
            }
        }

        echo 'COMPLETE';
        exit;
    }

    // 네이트온 점심멤버 API
    public function etc_lunch_member()
    {
        //  0 11 * * 1,2,3,4,5 apache /usr/bin/php /var/www/mint_api/index.php _batch/etc_lunch_member > /dev/null 2>&1

        $members = [
            '정은옥', 
            '이기범', 
            '김선아', 
            '고은정', 
            '이두리', 
            '홍장기', 
            '한새싹', 
            '홍채린', 
            '이성재', 
            '이내린',
            '김창수',
            '정혜정',
            '함성은',
            '신종민',
            '강지예',
        ];

        shuffle($members);

        $team1 = array();
        $team2 = array();
        $team3 = array();
        $team4 = array();

        for($i=0; $i<count($members); $i++)
        {
            if($i<3){
                array_push($team1, $members[$i]);
            }else if($i>=3 && $i<7){
                array_push($team2, $members[$i]);
            }else if($i>=7 && $i<11){
                array_push($team3, $members[$i]);
            }else{
                array_push($team4, $members[$i]);
            }
        }

        $msg ='';
        $msg .= "[".date('Y-m-d')."]"."\n";
        $msg .= "1조 : ".$team1[0].", ".$team1[1].", ".$team1[2]."\n";
        $msg .= "2조 : ".$team2[0].", ".$team2[1].", ".$team2[2].", ".$team2[3]."\n";
        $msg .= "3조 : ".$team3[0].", ".$team3[1].", ".$team3[2].", ".$team3[3]."\n";
        $msg .= "4조 : ".$team4[0].", ".$team4[1].", ".$team4[2].", ".$team4[3]."\n\n";


        // $webhook = 'https://teamroom.nate.com/api/webhook/28e40709/ZATyKUJzCr2xz9WsLyrjR4qI';           // 테스트
        $webhook = 'https://teamroom.nate.com/api/webhook/6b2e858e/MRoUrZ1Ozll1R6riDniZOROn';        // 점심 방


        $kakao_channel = 'https://pf-wapi.kakao.com/web/profiles/_nttgxb';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $kakao_channel);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if($response){
            
            $res = (array)json_decode($response);
            $profile = (array)$res['profile'];
            $lunch_menu = (array)$profile['profile_image'];
            
            $msg .= $lunch_menu['medium_url']."\n";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhook); // Webhook URL
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'content='.urlencode($msg)); // 메시지
            $return = curl_exec($ch);
            curl_close($ch);

        }else{

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhook); // Webhook URL
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'content='.urlencode($msg)); // 메시지
            $return = curl_exec($ch);
            curl_close($ch);
            
        }   
        
    }
    
    // 네이트온 주번 API
    public function etc_cafe_manager()
    {
        //  0 16 * * 5 apache /usr/bin/php /var/www/mint_api/index.php _batch/etc_cafe_manager > /dev/null 2>&1
        
        $members = [
            '고은정', 
            '김선아', 
            '김진수', 
            '김창수', 
            '김태성', 
            '박종호', 
            '안민아', 
            '이기범', 
            '이내린', 
            '이동렬',
            '이두리',
            '이성재',
            '정은옥',
            '정혜정',
            '조은혜',
            '한새싹',
            '함성은',
            '홍장기',
            '홍채린',
            '신종민',
            '강지예',
        ];

        shuffle($members);
        
        $count = 1;
        $time = time();
        $monday = date('Y-m-d', strtotime("next Monday", $time));
        $friday = date('Y-m-d', strtotime("next Friday", $time));

        $msg = '';
        $msg .= $monday.' ~ '.$friday ."\n\n";

        for($i=0; $i<count($members); $i++)
        {
            if($count < 10){
                $msg .= "[0".$count."]".$members[$i]."\r";
            }else{
                $msg .= "[".$count."]".$members[$i]."\r";
            }

            if($count % 4 == 0){
                $msg .="\n";
            }

            $count++;
        }

        // $webhook = 'https://teamroom.nate.com/api/webhook/28e40709/ZATyKUJzCr2xz9WsLyrjR4qI';           // 테스트
        $webhook = 'https://teamroom.nate.com/api/webhook/07fc183f/1iaZSXyfdo8vqEyxtrQn2KBX';           // 주번 방
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $webhook); // Webhook URL
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'content='.urlencode($msg)); // 메시지
        $return = curl_exec($ch);
        curl_close($ch);

    }
    public function s3_old_data_delete()
    {
        // $s3 = S3::s3_old_data_delete();
        // $path = 'test_upload/test/';
        $this->load->library('CI_Benchmark');
        $this->benchmark->mark('start');



        $path = 'attach/dictation/';

        $s3 = S3::s3_old_data_delete_limit($path, '2000');

        $this->benchmark->mark('end');
        echo 'banner : '.$this->benchmark->elapsed_time('start', 'end').PHP_EOL;

        $return_array['data'] = $s3;
        echo json_encode($return_array);
        exit;


    }

    /*
        강사의 급여타입(wiz_tutor.pay_type)을 
        히스토리형 급여타입 테이블(mint_tutor_pay.pay_type)에서 오늘 날짜에 해당하는 정보로 업데이트하는 처리
    */
    public function cron_update_wiz_tutor_pay_type()
    {
        $today = date("Y-m-d");
        $aPayType = array('a'=>'고정급', 'b'=>'변동A', 'c'=>'변동B', 'd'=>'성과급');

        $this->load->model('tutor_mdl');
        $tutor_list = $this->tutor_mdl->all_tutor();

        if($tutor_list)
        {
            foreach($tutor_list as $tutor)
            {
                $pay_config = tutor_pay_config($tutor['wt_tu_uid'], $today);

                $sNewPayType = $pay_config['pay_type'];
                switch ($sNewPayType) {
                    case 'z': $sNewPayType = 'd';	//'기본보장 성과급'은 보이기에 '성과급'으로 처리
                        break;
                    default: $sNewPayType = 'a';	//값이 없으면 '기본급'으로 처리
                    case 'a':
                        break;
                    case 'b':
                    case 'c':
                    case 'd':
                        break;
                }

                if ($tutor['wt_tu_id'] == 'postpone' || $tutor['wt_pay_type'] == $sNewPayType) continue;

                $param = array(
                    'pay_type' => $sNewPayType
                );
                $where = array(
                    'tu_uid' => $tutor['wt_tu_uid']
                );
        
                $this->tutor_mdl->update_tutor($param, $where);

                log_message('error', __FUNCTION__.' :'.$tutor['wt_tu_uid'].' '. $tutor['wt_pay_type']. ' -> '.$sNewPayType);
            }
        }

    }

    
    /*
        MSET 하루평균 집계
        구민트는 mint_mset_score_addon 테이블의 가상데이터를 집계시켰으나 신민트에서는 제외시킴
    */
    public function cron_update_mset_summary()
    {
        $this->load->model('mset_mdl');
        //어제의 엠셋 평균데이터를 뽑는다
        $sDate = date("Y-m-d", time() - 86400);
        
        $where = [
            'regdate' => $sDate
        ];
        //다시 집계해서 넣을것이므로 어제날짜 데이터 있으면 삭제.
        $this->mset_mdl->delete_mint_mset_report_summary($where);

        $score_list = $this->mset_mdl->get_mset_score_by_uid_recent($sDate);
        $iUserCount = count($score_list);

        //skill 항목별 평균치 계산
		$aTotal = array();
		$aScore = array();

        foreach($score_list as $score)
        {
            foreach ($score as $sKey=>$sVal) $aTotal[$sKey] += $sVal;

			//총점별 분포도 계산
			if ($score['overall_score']) $aScore[$score['overall_score']]++;
        }

        $aAVG = array(
            'pronunciation' =>$aTotal['pronunciation_level'],
            'fluency'       =>$aTotal['fluency_level'],
            'vocabulary'    =>$aTotal['vocabulary_level'],
            'speaking'      =>$aTotal['speaking_level'],
            'grammar'       =>$aTotal['grammar_level'],
            'listening'     =>$aTotal['listening_level'],
            'function'      =>$aTotal['function_level']
        );

        if (!function_exists('setAVG')) {
			function setAVG (&$piTotal, $psKey, $piCount) { $piTotal = $piTotal / $piCount; $piTotal = (int)($piTotal * 10) / 10; }
		}
		array_walk($aAVG, 'setAVG', $iUserCount);

        //Max 값 계산
		$iMaxScore = 0;
		foreach ($aScore as $x) { if ($iMaxScore < $x) $iMaxScore = $x; }
		$aScore['max'] = $iMaxScore;

        $average = array('avg'=>$aAVG, 'score'=>$aScore, 'total'=>$aTotal, 'count'=>$iUserCount);

        //10~100까지 키 세팅. 유저점수에 없는 점수구간이 있을수 있어 강제로 넣어준다
        for($i=10;$i<=100;$i++)
        {
            if(!array_key_exists($i,$average['score']))
            {
                $average['score'][$i] = 0;
            }
        }

        //무슨용도인지는 불명. 가상데이터에 있던 키값 강제 삽입
        $average['avg']['overall'] = 2;

        foreach ($average['avg'] as $sKey=>$x) {

			//데이터 보정
			if ($x < 2) $x = 2;
			if ($x > 9) $x = 9;

            $this->mset_mdl->insert_mint_mset_report_summary([
                'regdate' =>$sDate, 
                'key'     =>$sKey, 
                'value'   => $x
            ]);
		}

		foreach ($average['score'] as $sKey=>$x) {

			//데이터 보정
			if ($x < 0) $x = 0;

			$this->mset_mdl->insert_mint_mset_report_summary([
                'regdate' =>$sDate, 
                'key'     =>$sKey, 
                'value'   => $x
            ]);
		}

    }

    
    /*
        오늘하루수업변경(mint_change_for_today_data)으로 변경한 수업(wiz_schedule)들의 수업코드 n에서 t로 변경
    */
    public function cron_changetoday_kindcode()
    {
        $this->load->model('lesson_mdl');

        $today = date("Y-m-d");
        
        //다시 집계해서 넣을것이므로 어제날짜 데이터 있으면 삭제.
        $list = $this->lesson_mdl->get_mint_change_for_today_data($today);

        if($list)
        {
            foreach($list as $row)
            {
                //해당 스케줄이 변경된 상태 그대로인지 체크
                $sc = $this->lesson_mdl->row_wiz_schedule_by_sc_id($row['sc_id']);
                
                //스케줄이 존재하지 않거나 마지막 변경기록과 다르면 패스
                if (!$sc || $sc['ws_kind'] !='n' || $row['startday'] != $sc['startday'] || $row['tu_uid'] != $sc['tu_uid']) continue;	

                $kind = 't';
                //수업코드 업데이트
                $update = [
                    'kind' => $kind,
                ];
                $this->lesson_mdl->update_wiz_schedule($row['sc_id'], $update);
            }

            $where = [
                'startday >=' => $today.' 00:00:00',
                'startday <=' => $today.' 23:59:59',
            ];
            //삭제
            $this->lesson_mdl->delete_mint_change_for_today_data($where);
        }
    }

    
    /*
        오늘 기준 주니어 -> 시니어로 변경
    */
    public function cron_lev_gubun_change()
    {
        $this->load->model('member_mdl');

        //니어가 15세보다 미만을 기준으로 오늘보다 15년전 하루전날을 기준으로 한다.
        $LastDay = date('Y-m-d',strtotime("-15year -1day",time()));
        
        //다시 집계해서 넣을것이므로 어제날짜 데이터 있으면 삭제.
        $list = $this->member_mdl->junior_member_list_for_senior($LastDay);

        if($list)
        {
            foreach($list as $row)
            {
                if($row['birth']!='')
                {
                    $is_solar = 'Y';//lunar calendar : 음력 = N ,solar calendar : 양력 =Y
                    unset($birth_arr);
                    $birth_arr = explode('-',$row['birth']);
                    // 1 -> 01 앞에 0 붙여주는 처리
                    $birth = $birth_arr[0]."-".str_pad($birth_arr[1],2,0,STR_PAD_LEFT)."-".str_pad($birth_arr[2],2,0,STR_PAD_LEFT);
            
                    if($birth_arr[3]=="음력") $is_solar = 'N';

                    $update = [
                        'birth' => $birth,
                        'is_solar' => $is_solar,
                        'lev_gubun' => 'SENIOR',
                    ];
                    $this->member_mdl->update_member($update, $row['wiz_id']);

                }
                else if($row['lev_gubun']=='')
                {
                    $update = [
                        'lev_gubun' => 'SENIOR',
                    ];
                    $this->member_mdl->update_member($update, $row['wiz_id']);
                }
            }

        }
    }
    
    
    /*
        한달 전 ns 수업 파일 삭제
    */
    public function cron_ns_file_delete()
    {
        $this->load->model('board_mdl');

        $date = date('Y-m-d', strtotime('-1 month'));
        $checkStr = 'file_del_ok';
        //다시 집계해서 넣을것이므로 어제날짜 데이터 있으면 삭제.
        $list = $this->board_mdl->find_ns_article_for_delete($date, $checkStr);

        if($list)
        {
            foreach($list as $row)
            {
                $result = S3::delete_s3_object($this->upload_path_boards, $row['filename'], '' , true);

                if($result['res_code'] =='0000')
                {
                    $update = [
                        'sim_content2' => $row['sim_content2'].'|'.$checkStr
                    ];

                    $this->board_mdl->update_article($update, $row['mb_unq'], $row['wiz_id']);
                }
            }

        }
    }

    /*
        회원들의 가장최근 수업에 대한 평균 피드백 점수
    */
    public function cron_feedback_stat()
    {
        set_time_limit(0);
        $this->load->model('lesson_mdl');
        $date = date("Y-m-d 00:00:00");

        $where = "present = '2'
        and lesson_gubun in('M','S','T','V','E')
        and startday < '".$date."'
        and lesson_id <100000000
        and uid !='33512'
        and tu_uid NOT IN ('88', '153', '158') ";

        $list = $this->lesson_mdl->get_feedback_by_score_count($where);

        $total = 0;
        $total_ls = 0;
        $total_ss = 0;
        $total_pro = 0;
        $total_voc = 0;
        $total_cg = 0;
        $arr_mem = null;
        $arr_ls = null;
        $arr_ss = null;
        $arr_pro = null;
        $arr_voc = null;
        $arr_cg = null;
        if($list)
        {
            foreach($list as $row)
            {
                if($row['lv'] < 1) continue;
	            $arr_mem[$row['lv']] = $row['m_ea'];
	            $total += $row['m_ea'];
            }

            $field = 'rating_ls';
            $list = $this->lesson_mdl->get_feedback_by_field_count($where, $field);

            if($list)
            {
                foreach($list as $row)
                {
                    $arr_ls[$row[$field]] = $row['ea'];
	                $total_ls += $row['ea'];
                }
            }

            $field = 'rating_ss';
            $list = $this->lesson_mdl->get_feedback_by_field_count($where, $field);

            if($list)
            {
                foreach($list as $row)
                {
                    $arr_ss[$row[$field]] = $row['ea'];
	                $total_ss += $row['ea'];
                }
            }

            
            $field = 'rating_pro';
            $list = $this->lesson_mdl->get_feedback_by_field_count($where, $field);

            if($list)
            {
                foreach($list as $row)
                {
                    $arr_pro[$row[$field]] = $row['ea'];
	                $total_pro += $row['ea'];
                }
            }

            
            $field = 'rating_voc';
            $list = $this->lesson_mdl->get_feedback_by_field_count($where, $field);

            if($list)
            {
                foreach($list as $row)
                {
                    $arr_voc[$row[$field]] = $row['ea'];
	                $total_voc += $row['ea'];
                }
            }

            
            $field = 'rating_cg';
            $list = $this->lesson_mdl->get_feedback_by_field_count($where, $field);

            if($list)
            {
                foreach($list as $row)
                {
                    $arr_cg[$row[$field]] = $row['ea'];
	                $total_cg += $row['ea'];
                }
            }

            $pro_avg = round(($arr_pro['1']*1+$arr_pro['2']*2+$arr_pro['3']*3+$arr_pro['4']*4+$arr_pro['5']*5+$arr_pro['6']*6+$arr_pro['7']*7)/$total_pro,2);
            $voc_avg = round(($arr_voc['1']*1+$arr_voc['2']*2+$arr_voc['3']*3+$arr_voc['4']*4+$arr_voc['5']*5+$arr_voc['6']*6+$arr_voc['7']*7)/$total_voc,2);
            $ss_avg = round(($arr_ss['1']*1+$arr_ss['2']*2+$arr_ss['3']*3+$arr_ss['4']*4+$arr_ss['5']*5+$arr_ss['6']*6+$arr_ss['7']*7)/$total_ss,2);
            $cg_avg = round(($arr_cg['1']*1+$arr_cg['2']*2+$arr_cg['3']*3+$arr_cg['4']*4+$arr_cg['5']*5+$arr_cg['6']*6+$arr_cg['7']*7)/$total_cg,2);
            $ls_avg = round(($arr_ls['1']*1+$arr_ls['2']*2+$arr_ls['3']*3+$arr_ls['4']*4+$arr_ls['5']*5+$arr_ls['6']*6+$arr_ls['7']*7)/$total_ls,2);

            $row_sel = $this->lesson_mdl->get_feedback_stat_two_day_ago();

            $insertParam = [
                'lv1' => $arr_mem['1'],
                'lv2' => $arr_mem['2'],
                'lv3' => $arr_mem['3'],
                'lv4' => $arr_mem['4'],
                'lv5' => $arr_mem['5'],
                'lv6' => $arr_mem['6'],
                'lv7' => $arr_mem['7'],
                'lv_total' => $total,
                'pro_lv1' => $arr_pro['1'],
                'pro_lv2' => $arr_pro['2'],
                'pro_lv3' => $arr_pro['3'],
                'pro_lv4' => $arr_pro['4'],
                'pro_lv5' => $arr_pro['5'],
                'pro_lv6' => $arr_pro['6'],
                'pro_lv7' => $arr_pro['7'],
                'pro_total' => $total_pro,
                'pro_avg' => $pro_avg,
                'pro_avg_add' => $row_sel['pro_avg_add'],
                'voc_lv1' => $arr_voc['1'],
                'voc_lv2' => $arr_voc['2'],
                'voc_lv3' => $arr_voc['3'],
                'voc_lv4' => $arr_voc['4'],
                'voc_lv5' => $arr_voc['5'],
                'voc_lv6' => $arr_voc['6'],
                'voc_lv7' => $arr_voc['7'],
                'voc_total' => $total_voc,
                'voc_avg' => $voc_avg,
                'voc_avg_add' => $row_sel['voc_avg_add'],
                'ss_lv1' => $arr_ss['1'],
                'ss_lv2' => $arr_ss['2'],
                'ss_lv3' => $arr_ss['3'],
                'ss_lv4' => $arr_ss['4'],
                'ss_lv5' => $arr_ss['5'],
                'ss_lv6'=> $arr_ss['6'],
                'ss_lv7' => $arr_ss['7'],
                'ss_total' => $total_ss,
                'ss_avg' => $ss_avg,
                'ss_avg_add' => $row_sel['ss_avg_add'],
                'cg_lv1' => $arr_cg['1'],
                'cg_lv2' => $arr_cg['2'],
                'cg_lv3' => $arr_cg['3'],
                'cg_lv4' => $arr_cg['4'],
                'cg_lv5' => $arr_cg['5'],
                'cg_lv6' => $arr_cg['6'],
                'cg_lv7' => $arr_cg['7'],
                'cg_total' => $total_cg,
                'cg_avg' => $cg_avg,
                'cg_avg_add' => $row_sel['cg_avg_add'],
                'ls_lv1' => $arr_ls['1'],
                'ls_lv2' => $arr_ls['2'],
                'ls_lv3' => $arr_ls['3'],
                'ls_lv4' => $arr_ls['4'],
                'ls_lv5' => $arr_ls['5'],
                'ls_lv6' => $arr_ls['6'],
                'ls_lv7' => $arr_ls['7'],
                'ls_total' => $total_ls,
                'ls_avg' => $ls_avg,
                'ls_avg_add' => $row_sel['ls_avg_add'],
                'stat_date' => date("Y-m-d", strtotime(date('Y-m-d')." -1 day")),
                'type' => 'class',
                'regdate' => date("Y-m-d H:i:s")
            ];

            $this->lesson_mdl->insert_feedback_stat($insertParam);
        }

        // 레벨테스트 건수별 통계
        $total = 0;
        $total_ls = 0;
        $total_ss = 0;
        $total_pro = 0;
        $total_voc = 0;
        $total_cg = 0;
        $arr_mem = null;
        $arr_ls = null;
        $arr_ss = null;
        $arr_pro = null;
        $arr_voc = null;
        $arr_cg = null;

        $where = " uid !='33512' and tu_uid NOT IN ('88', '153', '158') and resultdate < '".date("Y-m-d 00:00:00")." 00:00:00' and le_step='3' and lesson_gubun in ('M','S','T','V','E')";

        $list = $this->lesson_mdl->get_leveltest_feedback_by_score_count($where);

        if($list)
        {
            foreach($list as $row)
            {
                if($row['lv'] < 1) continue;
	            $arr_mem[$row['lv']] = $row['m_ea'];
	            $total += $row['m_ea'];
            }

            $field = 'listening';
            $list = $this->lesson_mdl->get_leveltest_feedback_by_field_count($where, $field);

            if($list)
            {
                foreach($list as $row)
                {
                    $arr_ls[$row[$field]] = $row['ea'];
	                $total_ls += $row['ea'];
                }
            }

            $field = 'speaking';
            $list = $this->lesson_mdl->get_leveltest_feedback_by_field_count($where, $field);

            if($list)
            {
                foreach($list as $row)
                {
                    $arr_ss[$row[$field]] = $row['ea'];
	                $total_ss += $row['ea'];
                }
            }

            
            $field = 'pronunciation';
            $list = $this->lesson_mdl->get_leveltest_feedback_by_field_count($where, $field);

            if($list)
            {
                foreach($list as $row)
                {
                    $arr_pro[$row[$field]] = $row['ea'];
	                $total_pro += $row['ea'];
                }
            }

            
            $field = 'vocabulary';
            $list = $this->lesson_mdl->get_leveltest_feedback_by_field_count($where, $field);

            if($list)
            {
                foreach($list as $row)
                {
                    $arr_voc[$row[$field]] = $row['ea'];
	                $total_voc += $row['ea'];
                }
            }

            
            $field = 'grammar';
            $list = $this->lesson_mdl->get_leveltest_feedback_by_field_count($where, $field);

            if($list)
            {
                foreach($list as $row)
                {
                    $arr_cg[$row[$field]] = $row['ea'];
	                $total_cg += $row['ea'];
                }
            }

            $pro_avg = round(($arr_pro['1']*1+$arr_pro['2']*2+$arr_pro['3']*3+$arr_pro['4']*4+$arr_pro['5']*5+$arr_pro['6']*6+$arr_pro['7']*7)/$total_pro,2);
            $voc_avg = round(($arr_voc['1']*1+$arr_voc['2']*2+$arr_voc['3']*3+$arr_voc['4']*4+$arr_voc['5']*5+$arr_voc['6']*6+$arr_voc['7']*7)/$total_voc,2);
            $ss_avg = round(($arr_ss['1']*1+$arr_ss['2']*2+$arr_ss['3']*3+$arr_ss['4']*4+$arr_ss['5']*5+$arr_ss['6']*6+$arr_ss['7']*7)/$total_ss,2);
            $cg_avg = round(($arr_cg['1']*1+$arr_cg['2']*2+$arr_cg['3']*3+$arr_cg['4']*4+$arr_cg['5']*5+$arr_cg['6']*6+$arr_cg['7']*7)/$total_cg,2);
            $ls_avg = round(($arr_ls['1']*1+$arr_ls['2']*2+$arr_ls['3']*3+$arr_ls['4']*4+$arr_ls['5']*5+$arr_ls['6']*6+$arr_ls['7']*7)/$total_ls,2);

            $row_sel = $this->lesson_mdl->get_leveltest_feedback_stat_two_day_ago();

            $insertParam = [
                'lv1' => $arr_mem['1'],
                'lv2' => $arr_mem['2'],
                'lv3' => $arr_mem['3'],
                'lv4' => $arr_mem['4'],
                'lv5' => $arr_mem['5'],
                'lv6' => $arr_mem['6'],
                'lv7' => $arr_mem['7'],
                'lv_total' => $total,
                'pro_lv1' => $arr_pro['1'],
                'pro_lv2' => $arr_pro['2'],
                'pro_lv3' => $arr_pro['3'],
                'pro_lv4' => $arr_pro['4'],
                'pro_lv5' => $arr_pro['5'],
                'pro_lv6' => $arr_pro['6'],
                'pro_lv7' => $arr_pro['7'],
                'pro_total' => $total_pro,
                'pro_avg' => $pro_avg,
                'pro_avg_add' => $row_sel['pro_avg_add'],
                'voc_lv1' => $arr_voc['1'],
                'voc_lv2' => $arr_voc['2'],
                'voc_lv3' => $arr_voc['3'],
                'voc_lv4' => $arr_voc['4'],
                'voc_lv5' => $arr_voc['5'],
                'voc_lv6' => $arr_voc['6'],
                'voc_lv7' => $arr_voc['7'],
                'voc_total' => $total_voc,
                'voc_avg' => $voc_avg,
                'voc_avg_add' => $row_sel['voc_avg_add'],
                'ss_lv1' => $arr_ss['1'],
                'ss_lv2' => $arr_ss['2'],
                'ss_lv3' => $arr_ss['3'],
                'ss_lv4' => $arr_ss['4'],
                'ss_lv5' => $arr_ss['5'],
                'ss_lv6'=> $arr_ss['6'],
                'ss_lv7' => $arr_ss['7'],
                'ss_total' => $total_ss,
                'ss_avg' => $ss_avg,
                'ss_avg_add' => $row_sel['ss_avg_add'],
                'cg_lv1' => $arr_cg['1'],
                'cg_lv2' => $arr_cg['2'],
                'cg_lv3' => $arr_cg['3'],
                'cg_lv4' => $arr_cg['4'],
                'cg_lv5' => $arr_cg['5'],
                'cg_lv6' => $arr_cg['6'],
                'cg_lv7' => $arr_cg['7'],
                'cg_total' => $total_cg,
                'cg_avg' => $cg_avg,
                'cg_avg_add' => $row_sel['cg_avg_add'],
                'ls_lv1' => $arr_ls['1'],
                'ls_lv2' => $arr_ls['2'],
                'ls_lv3' => $arr_ls['3'],
                'ls_lv4' => $arr_ls['4'],
                'ls_lv5' => $arr_ls['5'],
                'ls_lv6' => $arr_ls['6'],
                'ls_lv7' => $arr_ls['7'],
                'ls_total' => $total_ls,
                'ls_avg' => $ls_avg,
                'ls_avg_add' => $row_sel['ls_avg_add'],
                'stat_date' => date("Y-m-d", strtotime(date('Y-m-d')." -1 day")),
                'type' => 'leveltest',
                'regdate' => date("Y-m-d H:i:s")
            ];

            $this->lesson_mdl->insert_feedback_stat($insertParam);

        }

    }

    
    /*
        회원의 birth와 age가 안맞을 경우 update
    */
    public function cron_update_member_age_levgubun()
    {
        $this->load->model('member_mdl');
        $this->member_mdl->update_member_age_levgubun();
    }



    public function cron_alim_test()
    {
        $lesson_id = '107151';
        $this->load->model('lesson_mdl');
        $book_info = $this->lesson_mdl->check_schedule_book_link($lesson_id);
        
        $book_info['last_page'] ? $book_info['last_page'] : '1';

        $kko_btn_info= array();

        $btn1= array(
            'name' => '수업스타일',
            'type' => 'AL',
            'scheme_android' => 'http://m.mint05.com/mypage/my_lessontype.php?lesson_id='.$book_info['lesson_id'],
            'url_mobile' => 'http://m.mint05.com/mypage/my_lessontype.php?lesson_id='.$book_info['lesson_id'],
            'url_pc' => 'http://www.mint05.com/pubhtml/mypage/my_lessontype.php?lesson_id='.$book_info['lesson_id'],
            'target' =>'out',
        );
        $btn2= array(
            'name' => 'LIVE교재',
            'type' => 'AL',
            'scheme_android' => 'https://story.mint05.com/#/popup-mint-book?bi='.$book_info['book_id'].'&li='.$book_info['lesson_id'].'&bp='.$book_info['last_page'].'&url='.base64_encode($book_info['new_link']),
            'url_mobile' => 'https://story.mint05.com/#/popup-mint-book?bi='.$book_info['book_id'].'&li='.$book_info['lesson_id'].'&bp='.$book_info['last_page'].'&url='.base64_encode($book_info['new_link']),
            'url_pc' => 'https://story.mint05.com/#/popup-mint-book?bi='.$book_info['book_id'].'&li='.$book_info['lesson_id'].'&bp='.$book_info['last_page'].'&url='.base64_encode($book_info['new_link']),
            'target' =>'out',
        );
        $btn3= array(
            'name' => '나의출석부',
            'type' => 'AL',
            'scheme_android' => 'http://go.mint05.com/schedule',
            'url_mobile' => 'http://go.mint05.com/schedule',
            'url_pc' => 'http://go.mint05.com/schedule',
            'target' =>'out',
        );

        array_push($kko_btn_info, $btn1);
        array_push($kko_btn_info, $btn2);
        array_push($kko_btn_info, $btn3);

        $btn_info = array(
            'button' => $kko_btn_info
        );

        $attachment = array(
            'kko_btn_type'=>'2',
            'kakaoBtn'=> json_encode($btn_info)
        );

        $options = array(
            'name'      =>'이두리', 
            'wiz_id'    =>'dootopia00@gmail.com', 
            'uid'       =>'112202',
            'time'      =>'12:00', 
            'tu_name'   =>'system_test', 
            'lesson_id' =>'107151', 
            'sendonly'  =>true,
        );

        // print_r($options);exit;
        //알림톡 전송
        #   MINT06005F
        #   MINT06005H
        $res = sms::send_atalk('01089509715', 'MINT06005H', $options, $attachment);
        print_r($res);exit;
    }


    /*
        1) 수업시작, 레벨테스트, MSET 테스트 30분전 알림톡, 푸시 발송
        2) 수업시작, 레벨테스트 10분전 알림톡, 푸시 발송
        3)Missed Call 데이터 파싱
    */
    public function cron_alim_before_class_start()
    {
        $this->load->model('lesson_mdl');
        $this->load->model('sms_mdl');
        $this->load->model('leveltest_mdl');
        $this->load->model('etc_mdl');
        $this->load->model('member_mdl');

        $Time_30 = date('Y-m-d H:i',strtotime('+30 minutes'));
        $Time_10 = date('Y-m-d H:i',strtotime('+10 minutes'));

        // 30분후 체크시간
        $Time_30_arr = explode(' ',$Time_30);
        // 10분후 체크시간
        $Time_10_arr = explode(' ',$Time_10);

        // 30분 전 수업 알림톡,푸시 발송 체크
        $list = $this->lesson_mdl->list_class_before_start($Time_30);

        if($list)
        {
            foreach($list as $row)
            {
                //MSET
                if ($row['lesson_id'] == '100000001') 
                {
                    $start_day = explode(" ", $row['startday']);
                    $options = array(
                        'args'      =>'class',
                        'time'      =>substr($start_day[1],0,-3),
                        'date'      =>$start_day[0],
                        'name'      =>$row['name'],
                        'uid'       =>$row['uid'],
                        'wiz_id'    =>$row['wiz_id'], 
                        'sms_push_yn'     =>'Y', 
                        'sms_push_code'=>'199', 
                        'sms_term_min'=>'5'
                    );

                    //알림톡 전송
                    sms::send_atalk($row['mobile'],'MINT06001E',$options);

                    // 알림톡 템플릿내용으로 푸시발송
                    $tpl = $this->sms_mdl->get_atalk_templete('MINT06001E');    
                    $pInfo = array(
                        "member"=>$row['name'], 
                        "date"=>$Time_30_arr[0],
                        "time" => $Time_30_arr[1], 
                        "atk_content"=> $tpl['content']
                    );
                    AppPush::send_push($row['uid'], "1301", $pInfo);

                    continue;
                }
                else
                {   
                    //아마 연속수업일경우 알림보내지 않는거같다. 25분 이상의 텀이 있어야만 보낸다.(수업중 알림받지않으려고)
                    $check = $this->lesson_mdl->check_relay_schedule($row['uid'], $row['startday']);

                    if($check)
                    {
                        $end_date_make = strtotime($row['startday']);
                        $start_date_make = strtotime($check['endday']);
                        $result_date = intval(($end_date_make - $start_date_make) / 60);
                        if($result_date < 25) continue;
                    }

                    if($row['d_id'] == '404'){
                        
                        //북팡 404 은 알림톡 LIVE교재 버튼 제외
                        $attachment = array(
                            'kko_btn_type'=>'1',
                            'kakaoBtn'=> '수업스타일^WL^http://m.mint05.com/mypage/my_lessontype.php?lesson_id='.$row['lesson_id'].'^http://www.mint05.com/pubhtml/mypage/my_lessontype.php?lesson_id='.$row['lesson_id'].'|'.
                                        '나의출석부^WL^http://go.mint05.com/schedule^http://go.mint05.com/schedule',
                        );
                        $options = array(
                            'name'      =>$row['name'], 
                            'wiz_id'    =>$row['wiz_id'], 
                            'uid'       =>$row['uid'],
                            'time'      =>substr($row['startday'],11,5), 
                            'tu_name'   =>$row['tu_name'], 
                            'lesson_id' =>$row['lesson_id'], 
                            'sendonly'  =>true
                        );

                        //알림톡 전송
                        sms::send_atalk($row['mobile'], 'MINT06005F', $options, $attachment);   // 버튼 2개 알림톡

                    }else{

                        $book_info = $this->lesson_mdl->check_schedule_book_link($row['lesson_id']);
                        $book_info['last_page'] ? $book_info['last_page'] : '1';
                        $kko_btn_info= array();

                        $btn1= array(
                            'name' => '수업스타일',
                            'type' => 'AL',
                            'scheme_android' => 'http://m.mint05.com/mypage/my_lessontype.php?lesson_id='.$book_info['lesson_id'],
                            'url_mobile' => 'http://m.mint05.com/mypage/my_lessontype.php?lesson_id='.$book_info['lesson_id'],
                            'url_pc' => 'http://www.mint05.com/pubhtml/mypage/my_lessontype.php?lesson_id='.$book_info['lesson_id']
                        );
                        $btn2= array(
                            'name' => 'LIVE교재',
                            'type' => 'AL',
                            'scheme_android' => 'https://story.mint05.com/#/popup-mint-book?bi='.$book_info['book_id'].'&li='.$book_info['lesson_id'].'&bp='.$book_info['last_page'].'&url='.base64_encode($book_info['new_link']),
                            'url_mobile' => 'https://story.mint05.com/#/popup-mint-book?bi='.$book_info['book_id'].'&li='.$book_info['lesson_id'].'&bp='.$book_info['last_page'].'&url='.base64_encode($book_info['new_link']),
                            'url_pc' => 'https://story.mint05.com/#/popup-mint-book?bi='.$book_info['book_id'].'&li='.$book_info['lesson_id'].'&bp='.$book_info['last_page'].'&url='.base64_encode($book_info['new_link']),
                        );
                        $btn3= array(
                            'name' => '나의출석부',
                            'type' => 'AL',
                            'scheme_android' => 'http://go.mint05.com/schedule',
                            'url_mobile' => 'http://go.mint05.com/schedule',
                            'url_pc' => 'http://go.mint05.com/schedule'
                        );

                        array_push($kko_btn_info, $btn1);
                        array_push($kko_btn_info, $btn2);
                        array_push($kko_btn_info, $btn3);

                        $btn_info = array(
                            'button' => $kko_btn_info
                        );

                        $attachment = array(
                            'kko_btn_type'=>'2',
                            'kakaoBtn'=> json_encode($btn_info)
                        );

                        $options = array(
                            'name'      =>$row['name'], 
                            'wiz_id'    =>$row['wiz_id'], 
                            'uid'       =>$row['uid'],
                            'time'      =>substr($row['startday'],11,5), 
                            'tu_name'   =>$row['tu_name'], 
                            'lesson_id' =>$row['lesson_id'], 
                            'sendonly'  =>true
                        );

                        //알림톡 전송
                        sms::send_atalk($row['mobile'], 'MINT06005H', $options, $attachment);   // 버튼 3개 알림톡
                        
                    }
                    
                }
            }

            
        }   // END 30분 전 수업 알림톡,푸시 발송 체크

        // 10분 전 수업 알림톡,푸시 발송 체크
        $list = $this->lesson_mdl->list_class_before_start($Time_10);

        if($list)
        {
            foreach($list as $row)
            {
                //MSET
                if ($row['lesson_id'] == '100000001') 
                {
                    // 알림톡 템플릿내용으로 푸시발송
                    $tpl = $this->sms_mdl->get_atalk_templete('MINT06001E');    
                    $pInfo = array(
                        "member"=>$row['name'], 
                        "date"=>$Time_10_arr[0],
                        "time" => $Time_10_arr[1], 
                        "atk_content"=> $tpl['content']
                    );
                    AppPush::send_push($row['uid'], "1302", $pInfo);

                    continue;
                }
                else
                {   
                    //아마 연속수업일경우 알림보내지 않는거같다. 25분 이상의 텀이 있어야만 보낸다.(수업중 알림받지않으려고)
                    $check = $this->lesson_mdl->check_relay_schedule($row['uid'], $row['startday']);

                    if($check)
                    {
                        $end_date_make = strtotime($row['startday']);
		                $start_date_make = strtotime($check['endday']);
		                $result_date = intval(($end_date_make - $start_date_make) / 60);
                        if($result_date < 0) continue;
                    }

                    $pInfo = array(
                        "member"  => $row['name'], 
                        "time"    => substr($row['startday'],11,5),
                        "teacher" => $row['tu_name'], 
                    );
                    AppPush::send_push($row['uid'], "1101", $pInfo);
                }
            }

            
        }   // END 10분 전 수업 알림톡,푸시 발송 체크
 

        //레벨테스트 30분전 알림
        $list = $this->leveltest_mdl->list_leveltest_before_start($Time_30_arr[0], $Time_30_arr[1]);

        if($list)
        {
            foreach($list as $row)
            {
                // 30분전 보낸 내역이 있으면 건너띔
                $check = $this->leveltest_mdl->check_relay_leveltest($row['le_fid'], 30);
                if($check) continue;

                $lt = $this->leveltest_mdl->list_leveltest_by_le_fid($row['le_fid']);

                $push_code = '';
                if($lt)
                {
                    foreach($lt as $key=>$test)
                    {
                        if($test['le_start']){
                            if($key==0){
                                $time = substr($test['le_start'],11,5);
                            }else if($key==1){
                                $two_ndtime = substr($test['le_start'],11,5);
                            }else if($key==2){
                                $three_rdtime = substr($test['le_start'],11,5);
                            }
                        }
                    }

                    if($row['lesson_gubun']=='M' || $row['lesson_gubun']=='T')
                    {
                        $template_code = 'MINT06001B';
                        $options = array(
                            'args'=>'leveltest',
                            'name'=>$row['name'],
                            'time'=>$time,
                            '2ndtime'=>$two_ndtime,
                            '3rdtime'=>$three_rdtime,
                            'uid'=>$row['uid'],
                            'wiz_id'=>$row['wiz_id']
                        );
                        $push_code = "1004";
                    }
                    else if($row['lesson_gubun']=='V')
                    {
                        $template_code = 'MINT06000Q';
                        $options = array(
                            'args'=>'leveltest',
                            'name'=>$row['name'],
                            'time'=>$time,
                            '2ndtime'=>$two_ndtime,
                            'uid'=>$row['uid'],
                            'wiz_id'=>$row['wiz_id']
                        );
                        $push_code = "1005";
                    }
                    else if($row['lesson_gubun']=='E')
                    {
                        $template_code = 'MINT06004H';
                        $options = array(
                            'args'=>'leveltest',
                            'name'=>$row['name'],
                            'time'=>$time,
                            '2ndtime'=>$two_ndtime,
                            '3rdtime'=>$three_rdtime,
                            'uid'=>$row['uid'],
                            'wiz_id'=>$row['wiz_id']
                        );
                        $push_code = "1005";
                    }

                    //알림톡 전송
                    sms::send_atalk($row['mobile'], $template_code, $options);

                    $inset_param = [
                        'le_fid' => $row['le_fid'],
                        'time_type' => 30,
                        'regdate' => date('Y-m-d H:i:s'),
                    ];
                    $this->leveltest_mdl->insert_wiz_leveltest_resultatk($inset_param);

                    $pInfo = array(
                        "member"=> $row['name'],
                        "date"  =>$Time_30_arr[0], 
                        "time"  => $time, 
                        "2ndtime"=> $two_ndtime, 
                        "3rdtime"=> $three_rdtime
                    );
                    AppPush::send_push($row['uid'], $push_code, $pInfo);

                }
            }

        }   // END 레벨테스트 30분전 알림


        //레벨테스트 10분전 알림
        $list = $this->leveltest_mdl->list_leveltest_before_start($Time_10_arr[0], $Time_10_arr[1]);

        if($list)
        {
            foreach($list as $row)
            {
                // 30분전 보낸 내역이 있으면 건너띔
                $check = $this->leveltest_mdl->check_relay_leveltest($row['le_fid'], 10);
                if($check) continue;

                if($row['lesson_gubun']=='M' || $row['lesson_gubun']=='T')
                {
                    $template_code = 'MINT06000F';
		            $push_code = "1006";
                }
                else if($row['lesson_gubun']=='V')
                {
                    $template_code = 'MINT06000F';
		            $push_code = "1007";
                }
                else if($row['lesson_gubun']=='E')
                {
                    $template_code = 'MINT06000F';
		            $push_code = "1007";
                }

                $options = array(
                    'args'=>'leveltest',
                    'name'=>$row['name'],
                    'time'=>$row['lv_time'],
                    'uid'=>$row['uid'],
                    'wiz_id'=>$row['wiz_id']
                );

                //알림톡 전송
                sms::send_atalk($row['mobile'], $template_code, $options);

                $inset_param = [
                    'le_fid' => $row['le_fid'],
                    'time_type' => 10,
                    'regdate' => date('Y-m-d H:i:s'),
                ];
                $this->leveltest_mdl->insert_wiz_leveltest_resultatk($inset_param);

                $pInfo = array(
                    "member"=> $row['name'], 
                    "date"  =>$Time_10_arr[0], 
                    "time"  => $row['lv_time']
                );
                AppPush::send_push($row['uid'], $push_code, $pInfo);

            }

        }   // END 레벨테스트 10분전 알림


        // Missed Call 데이터 파싱 - 알림톡과는 무관함
        $start_arr = array(0,1,2);
        foreach($start_arr as $i)
        {
            $output = DialComm::get_absence_list($i);
            $data_arr = json_decode($output,true);
            
            if($data_arr)
            {
                foreach($data_arr['rows'] as $val)
                {
                    $check = $this->etc_mdl->checked_exist_wiz_speak_call_by_aid($val['aid']);
                    if($check) continue;

                    unset($r1);
                    unset($temp_s1); // 탈퇴한 회원정보 담는 변수
                    unset($array_del_yn); //전화번호로 찾은 회원이 모두 탈퇴인지 아닌지 확인하는 변수
                    unset($r2);
                    unset($temp_s2);
                    unset($array_del_yn2);
                    $insert_param = [];

                    $account_list = $this->member_mdl->checked_every_phone_number($val['cid']);
                    if($account_list)
                    {
                        foreach($account_list as $result_s1)
                        {
                            if($result_s1['del_yn']=='d')
                            {
                                $temp_s1[] = $result_s1;
                                $array_del_yn[] = 'd';
                            } 
                            else 
                            {
                                $r1 =  $result_s1;
                                $array_del_yn[] = '';
                                break;
                            }
                        }
                    }

                    if(is_array($array_del_yn) && !in_array('',$array_del_yn)) $member_del = 'Y';
                    if($member_del=='Y') $r1= $temp_s1[0];
                    if($r1['uid'])
                    {
                        $insert_param = [
                            'uid' => $r1['uid'],
                            'wiz_id' => $r1['wiz_id'],
                            'name' => $r1['name'],
                        ];
                    }

                    if($val['callback_number'] !='' && $val['callback_number'] !='member')
                    {
                        $account_list = $this->member_mdl->checked_every_phone_number($val['callback_number']);
                        if($account_list)
                        {
                            foreach($account_list as $result_s2)
                            {
                                if($result_s2['del_yn']=='d')
                                {
                                    $temp_s2[] = $result_s2;
                                    $array_del_yn2[] = 'd';
                                } 
                                else 
                                {
                                    $r2 =  $result_s2;
                                    $array_del_yn2[] = '';
                                    break;
                                }
                            }
                        }

                        if(is_array($array_del_yn2) && !in_array('',$array_del_yn2)) $member_del2 = 'Y';
				        if($member_del2=='Y') $r2= $temp_s2[0];
                        if($r2['uid'])
                        {
                            $insert_param['callback_uid'] = $r2['uid'];
                            $insert_param['callback_wiz_id'] = $r2['wiz_id'];
                            $insert_param['callback_name'] = $r2['name'];
                        }

                    }

                    $insert_param['sp_regdate'] = date('Y-m-d H:i:s');
                    $insert_param['aid'] = $val['aid'];
                    $insert_param['time'] = $val['time'];
                    $insert_param['cid'] = $val['cid'];
                    $insert_param['callback_number'] = $val['callback_number'];

                    $this->etc_mdl->insert_wiz_speak_call($insert_param);

                    $pInfo = array(
                        "member"=> $r1['name'], 
                    );
                    AppPush::send_push($r1['uid'], '2100', $pInfo);
                }
            }
            
        }
    }

    /*
        1) 수업종료 7일전 알림
        2) 당일 재수강 알림
        3) 무료수업 등록안내
        4)이벤트 수업 등록안내
    */
    public function cron_sms_alim_lesson_expire_n_regist()
    {
        $this->load->model('lesson_mdl');
        $this->load->model('member_mdl');
        
        $d_list = $this->member_mdl->list_dealer_with_sms_ok();
        $d_id = [];
        foreach($d_list as $row)
        {
            $d_id[] = $row['d_id'];
        }

        ## 수업 종료 7일전, 당일 알림
        $sDate7 = date('Y-m-d', time() + (86400 * 7));
        $sDate = date('Y-m-d', time() + (86400 * 1)); //하루전
        $target_date = [
            date('Y-m-d', time() + (86400 * 7)),
            date('Y-m-d', time() + (86400 * 1)),
        ];

        $list = $this->lesson_mdl->list_lesson_endday_comming($target_date, $d_id);

        $aSendList = [];
        if($list)
        {
            foreach($list as $row)
            {
                
                //이후날짜로 같은 상품이 있는 경우는 알림 패스->재수강했으면 같은 상품이 있을테니
                $check = $this->lesson_mdl->check_same_type_goods_exist($row['uid'], $row['tu_uid'], $row['weekend'], $row['endday'], $row['stime']);
                if($check['cnt'] > 0) continue;

                //쿠폰수업 등록안내 문자 발송 - 고유번호 188
                if ($row['coupon_type'] != '') 
                { 
                    $iSMSNo = 188;
                    if ($aSendList[$row['uid']][$iSMSNo]) continue;	//이미 발송한 회원 & 문자내용이면 패스

                    $options = array(
                        'name'     =>$row['name'], 
                        'wiz_id'        =>$row['wiz_id'], 
                        'uid'           =>$row['uid'], 
                        'coupon_type'   =>$row['coupon_type'], 
                        'man_name'  =>'SYSTEM'
                    );
                    //SMS 전송
                    sms::send_sms($row['mobile'], $iSMSNo, $options);
                } 
                //이벤트수업 등록안내 문자 발송 - 고유번호 158
                elseif ($row['e_id'] > 0) 
                { 
                    $iSMSNo = 158;
                    if ($aSendList[$row['uid']][$iSMSNo]) continue;	//이미 발송한 회원 & 문자내용이면 패스

                    $options = array(
                        'name'     =>$row['name'], 
                        'wiz_id'        =>$row['wiz_id'], 
                        'uid'           =>$row['uid'], 
                        'coupon_type'   =>$row['coupon_type'], 
                        'man_name'  =>'SYSTEM'
                    );
                    //SMS 전송
                    sms::send_sms($row['mobile'], $iSMSNo, $options);
                } 
                else 
                {
                    //일반수업 만료일(7일) 안내 문자 발송 - 고유번호 90
                    if ($row['endday'] == $sDate7) 
                    { 
                        $iSMSNo = 90;
                    } 
                    //일반수업 수강만료일 안내 문자 발송 - 고유번호 17
                    elseif ($row['endday'] == $sDate) 
                    { 
                        $iSMSNo = 17;
                    }
                    
                    if ($aSendList[$row['uid']][$iSMSNo]) continue;	//이미 발송한 회원 & 문자내용이면 패스

                    $options = array(
                        'name'     =>$row['name'], 
                        'wiz_id'        =>$row['wiz_id'], 
                        'uid'           =>$row['uid'], 
                        'man_name'  =>'SYSTEM'
                    );
                    //SMS 전송
                    sms::send_sms($row['mobile'], $iSMSNo, $options);

                }

                $aSendList[$row['uid']][$iSMSNo] = true;
            }
        }

    }

    
    /*
        위 함수와 다르게 앱푸시, 웹사이트 알림만 처리한다
        1) 수업 종료 7일전 알림, 수업 종료 하루 전, 당일 알림
        2) 쿠폰수업 등록하고 강사지정을 안 한 경우 3일에 한번씩 알림
        3) 쿠폰수업 등록하고 강사지정을 안 한 상태로 쿠폰의 만료일이 된 경우 알림
    */
    public function cron_push_alim_lesson_expire_n_regist()
    {
        $this->load->model('lesson_mdl');
        $this->load->model('member_mdl');
        $this->load->model('notify_mdl');

        $sDate7 = date('Y-m-d', time() + (86400 * 7));
        $sDate = date('Y-m-d', time() + (86400 * 1)); //하루전
        $sDateToday = date('Y-m-d');

        ## 수업 종료 7일전, 하루전, 당일 알림
        $target_date = [
            date('Y-m-d', time() + (86400 * 7)),
            date('Y-m-d', time() + (86400 * 1)),
            date('Y-m-d')
        ];
 
        $list = $this->lesson_mdl->list_lesson_endday_comming($target_date, ['null']);

        // 수업 종료 7일전 알림, 수업 종료 당일 알림
        $aSendList = [];
        
        if($list)
        {
            foreach($list as $row)
            {
                //이후날짜로 같은 상품이 있는 경우는 알림 패스->재수강했으면 같은 상품이 있을테니
                $check = $this->lesson_mdl->check_same_type_goods_exist($row['uid'], $row['tu_uid'], $row['weekend'], $row['endday'], $row['stime']);
                if($check['cnt'] > 0) continue;

                //일반수업 만료일(7일) 안내
                if ($row['endday'] == $sDate7) 
                { 
                    $push_No = 1400;
                    $noti_No = 301;
                    $msg = '회원님의 수강상품들 중에 종료일까지 7일 남은 수업이 있습니다.';
                } 
                //일반수업 수강만료일 안내
                elseif ($row['endday'] == $sDateToday) 
                { 
                    $push_No = 1402;
                    $noti_No = 302;
                    $msg = '회원님의 수강상품들 중에 오늘 종료되는 수업이 있습니다.';
                }
                //일반수업 //하루전 수강만료일 안내
                elseif ($row['endday'] == $sDate) 
                { 
                    $push_No = 1401;
                    $noti_No = 0;       //하루전은 공지노티가 없음
                }

                //이미 발송한 회원이면 패스
	            if (isset($aSendList[$row['uid']][$push_No]) && $aSendList[$row['uid']][$push_No]) continue;

                if($noti_No)
                {
                    $notify = array(
                        'uid' => $row['uid'], 
                        'code' => $noti_No, 
                        'message' => $msg, 
                        'board_name' => '수강상품 종료임박 알림', 
                        'user_name' => '', 
                        'go_url' => 'https://story.mint05.com/#/payment-landing-class', 
                        'regdate' => date('Y-m-d H:i:s'),
                    );
    
                    $this->notify_mdl->insert_notify($notify);
                }

                AppPush::send_push($row['uid'], $push_No);

                $aSendList[$row['uid']][$push_No] = true;
            }
        } // END수업 종료 7일전 알림, 수업 종료 당일 알림

         
        // 쿠폰수업 등록하고 강사지정을 안 한 경우 3일에 한번씩 알림
        $where = " AND mc.validate > '".date('Y-m-d')."'";
        $list = $this->lesson_mdl->check_coupon_lesson_regist($where);
        $iTime = time();
        $aSendList = [];

        if($list)
        {
            foreach($list as $row)
            {
                //이미 발송한 회원이면 패스
	            if (isset($aSendList[$row['uid']]) && $aSendList[$row['uid']]) continue;

                //등록한지 3일씩 되었는지 체크. 계산된 날 수가 3의 배수가 아니면 패스
                $iPassedDay = ceil(($iTime - strtotime($row['regdate'])) / 86400);
                if ($iPassedDay > 0 && $iPassedDay % 3 > 0) continue;

                $notify = array(
                    'uid' => $row['uid'], 
                    'code' => 303, 
                    'message' => '회원님의 수강상품들 중에 수업등록을 하지 않은 수강상품이 있습니다.', 
                    'board_name' => '쿠폰 수강상품 수업 미등록 알림', 
                    'user_name' => '', 
                    'go_url' => 'http://www.mint05.com/pubhtml/mypage/coupon.php', 
                    'regdate' => date('Y-m-d H:i:s'),
                );

                $this->notify_mdl->insert_notify($notify);

                AppPush::send_push($row['uid'], 2400);
                $aSendList[$row['uid']] = true;
            }
        }
        
        // 쿠폰수업 등록하고 강사지정을 안 한 상태로 쿠폰의 만료일이 된 경우 알림
        $where = " AND mc.validate = '".date('Y-m-d')."'";
        $list = $this->lesson_mdl->check_coupon_lesson_regist($where);
        $aSendList = [];

        if($list)
        {
            foreach($list as $row)
            {
                //이미 발송한 회원이면 패스
	            if (isset($aSendList[$row['uid']]) && $aSendList[$row['uid']]) continue;

                $notify = array(
                    'uid' => $row['uid'], 
                    'code' => 304, 
                    'message' => '회원님의 수강상품들 중에 오늘까지만 수업등록이 가능한 쿠폰 수강상품이 있습니다.', 
                    'board_name' => '쿠폰의 만료일 임박 알림', 
                    'user_name' => '', 
                    'go_url' => 'http://www.mint05.com/pubhtml/mypage/coupon.php', 
                    'regdate' => date('Y-m-d H:i:s'),
                );

                $this->notify_mdl->insert_notify($notify);

                AppPush::send_push($row['uid'], 2401);
                $aSendList[$row['uid']] = true;
            }
        }

        
    }

    /**
     * 1. 전날 AHOP 시험 기록이 있는 학생목록 출력
     * 2. 유효성 검사
     * 2-1. 발송일 기준 시험을 치룬 이력이 있는지 여부 (subquery)
     * 2-2. 시험이 정상적으로 진행 중인지 (reply_name = 'START'가 존재하는지 여부) (subuery)
     * 3. 회원에게 알림발송 (PC알림, push)
     * 스케쥴러 등록
     */
    public function cron_notify_exam_free()
    {
        $this->load->model('notify_mdl');
        $this->load->model('book_mdl');

        $subjects = Array('Science', 'Social', 'Math');
    
        foreach ($subjects as $subject)
        {
            $exam_log = $this->book_mdl->check_wiz_book_exam_log($subject);
            if($exam_log)
            {
                foreach($exam_log as $row)
                {
                    //푸시발송
                    $push_data = array("subject" => $subject);
                    AppPush::send_push($row['uid'], '2205', $push_data);
                    
                    //알림발송
                    $notify_result = array(
                        'uid'       => $row['uid'],
                        'code'      => '321',
                        'message'   => 'AHOP '.$subject.'과목의 시험을 무료응시 할 수 있습니다!',
                        'user_name' => 'SYSTEM',
                        'go_url'    => 'http://www.mint05.com/pubhtml/boards/curriculum_list.php?table_code=1367&sub='.$subject.'&tab=3',
                        'regdate'   => date('Y-m-d H:i:s'),
                    );
                    $this->notify_mdl->insert_notify($notify_result);
                }
            }
        }
    }

    /**
     * 본 프로그램의 처리 내용 (191031 기준)
     * 첨삭게시판의 수정 기록을 삭제 (최근 30일 데이터만 보유)
     * 현재 학생이 수정한 로그만 기록되고 있음.
     * 추후 관리자에서도 사용 가능함.
     */
    public function cron_delete_correct_log()
    {
        $this->load->model('board_mdl');
        $this->board_mdl->delete_correct_log_month();

        echo 'ok';
    }

    /**
     * 최초 수업 등록 후 일주일 경과 되면 푸시 알림 발송
     */
    public function cron_new_class_push()
    {
        $this->load->model('lesson_mdl');

        $chk_date = date("Y-m-d", time()-86400*7);
        $happycall = $this->lesson_mdl->check_new_class($chk_date);
        if($happycall)
        {
            foreach($happycall as $row)
            {
                // 푸시 알림 발송
                $push_data = array("member" => $row['name']);
                AppPush::send_push($row['uid'], '2501', $push_data);
            }
        }
    }

    /**
     * 매일 12시 30분에 가상계좌 내일까지 입금 완료인데 처리 되지 않으면 알림(SMS, 푸시) 발송
     */
    public function cron_alimtalk_vbank()
    {
        $this->load->model('payment_mdl');

        // 내일까지 입금 완료 해야 하는 가상계좌 리스트
        $index = "";
        $limit = "";
        $select_col_content = ", wl.uid as wl_uid, wl.wiz_id as wl_wiz_id, wp.bank_number as wp_bank_number,
                                 wp.receive_date as wp_receive_date, wp.receive_name as wp_receive_name, wp.receive_mobile as wp_receive_mobile ";
        $where = "WHERE wp.pay_vbank > 0 and wp.pay_ok = 'N' and wp.receive_date = '".date("Y-m-d", time()+86400)."'";
        $order = "ORDER BY wp.pay_id DESC";
        $list = $this->payment_mdl->list_lesson_pay($index, $where, $order, $limit, $select_col_content);
        if($list)
        {
            foreach($list as $row)
            {
                $uid         = $row['wl_uid'];
                $wiz_id      = $row['wl_wiz_id'];
                $orderName   = $row['wp_receive_name'];   //입금자
                $price       = $row['wp_pay_tt'];         //입금금액
                $bank_number = $row['wp_bank_number'];    //입금계좌
                $mobile      = $row['wp_receive_mobile']; //입금자 연락처
                $lastday     = $row['wp_receive_date'];   //입금기한

                // 푸시 알림 발송
                $push_data = array("member" => $orderName, "w_uid" => $uid);
                AppPush::send_push($uid, '3001', $push_data);

                //알림톡 전송
                $options = array(
                    'name'        => $orderName,
                    'uid'         => $uid,
                    'wiz_id'      => $wiz_id,
                    'vbank_price' => $price,
                    'bank_number' => $bank_number,
                    'lastday'     => $lastday
                );
                sms::send_atalk($mobile, "MINT06002D", $options);
            }
        }
    }

    /**
     * 알림톡 로그테이블에 알림톡 결과여부 업데이트
     */
    public function cron_alimtalk_log_update()
    {
        $this->load->model('sms_mdl');

        //$log_file = dirname(dirname(__FILE__))."/logs/ata/".date("Ymd"); //TODO: 저장위치 확인필요
        //$fp = @fopen($log_file, "a");
        // 임시저장테이블 추출
        //@fwrite($fp, "\n 시작".date('Y-m-d H:i:s')." |");

        $i = 0;
        //알림톡 로그테이블에서 결과여부가 없는것만을 추출
        $list = $this->sms_mdl->get_atalk_log();
        if($list)
        {
            foreach($list as $row)
            {
                // 해당값으로 알림톡 결과 로그테이블에서 가져와서 업데이트
                $params = array(
                    'date'          => str_replace('-','',substr($row['regdate'],0,7)),
                    'mt_pr'         => $row['mt_pr'],
                    'recipient_num' => $row['recipient_num'],
                    'template_code' => $row['template_code']
                );
                $mmt_log = $this->sms_mdl->row_ata_mmt_log($params);

                $date1 = strtotime($row['regdate']);
                $date2 = strtotime(date('Y-m-d H:i:s'));
                $diff = $date2 - $date1;

                if($mmt_log['report_code'] == '' || $mmt_log['msg_status'] == '')
                {
                    if($diff > 600)
                    {
                        //알림톡 보낸시점에서 10분이 지나도 응답이 없다면 오류코드 3056(메시지 전송결과를 찾을 수 없음), 메시지 상태정보를 2(대기)로 설정한다.
                        $this->sms_mdl->update_atalk_log($row['al_uid'], '3056', '2');
                    }
                    continue;
                }
                $this->sms_mdl->update_atalk_log($row['al_uid'], $mmt_log['report_code'], $mmt_log['msg_status']);

                //만약 결과값이 실패이면서, sms 여부가 Y이면 sms으로 보내준다.
                if($mmt_log['report_code'] != '1000' && $row['sms_push_yn'] == 'Y' && $row['sms_push_code'] != '')
                {
                    $options = array(
                        'name'      => $row['name'],
                        'wiz_id'    => $row['wiz_id'],
                        'uid'       => $row['uid'],
                        'man_name'  => '',
                        'date'      => $row['date'],
                        'time'      => $row['time'],
                        'tu_name'   => $row['tu_name'],
                        'cl_time'   => $row['cl_time'],
                        'point'     => $row['point'],
                        'startdate' => $row['startdate'],
                        'enddate'   => $row['enddate']
                    );

                    if($row['sms_term_min'] > 0)
                    {
                        //알림톡 보내고 설정한 분이내 sms를 보낸다.
                        $term_time = 60 * $row['sms_term_min'];
                        if($diff < $term_time)
                        {
                            $send_sms = sms::send_sms($row['recipient_num'], $row['sms_push_code'], $options);
                            //if($send_sms['state']) @fwrite($fp, "sms1 : ".$row['recipient_num']."-".$row['sms_push_code']."-".$diff."-".$term_time." |");
                        }
                    }
                    else
                    {
                        //설정한 값이 없으면 무조건 보냄
                        $send_sms = sms::send_sms($row['recipient_num'], $row['sms_push_code'], $options);
                        //if($send_sms['state']) @fwrite($fp, "sms2 : ".$row['recipient_num']."-".$row['sms_push_code']." |");
                    }
                }
                $i++;
            }
        }

        log_message('error', 'cron_alimtalk_log_update :'.$i);
        //@fwrite($fp, "총 :".$i."건 끝".date('Y-m-d H:i:s'));
    }

    /**
     * 쿠폰을 등록한 후 수업등록을 하지 않은 채로 쿠폰의 유효기간이 다 된 경우의 회원에게 SMS 발송(유효기간이 당일인 경우)
     */
    public function cron_sms_for_coupon_lesson_without_regist_by_last_day()
    {
        $this->load->model('lesson_mdl');
        $this->load->model('notify_mdl');

        $today = date("Y-m-d");
        $sendList = array();

        $where = " AND mc.validate = '".$today."'";
        $list = $this->lesson_mdl->check_coupon_lesson_regist($where);
        if ($list)
        {
            foreach ($list as $row)
            {
                //휴대폰번호 없으면 패스
                if (!$row['mobile']) continue;
    
                //이미 발송한 휴대폰번호와 쿠폰종류는 다시 발송안함
                if (isset($sendList[$row['mobile']][$row['coupon_type']]) && $sendList[$row['mobile']][$row['coupon_type']]) continue;
    
                $options = array(
                    'name'        => $row['name'],
                    'wiz_id'      => $row['wiz_id'],
                    'uid'         => $row['uid'],
                    'coupon_type' => $row['coupon_type'],
                    'man_name'    => 'SYSTEM'
                );
                //쿠폰수업 유효기간 만료안내 문자 발송 - 고유번호 185
                sms::send_sms($row['mobile'], 185, $options);
    
                //쿠폰수업 등록안내 문자 발송 - 고유번호 186
                sms::send_sms($row['mobile'], 186, $options);
    
                //발송기록에 저장
                $sendList[$row['mobile']][$row['coupon_type']] = true;
    
                //알림 등록
                $notify_result = array(
                    'uid'        => $row['uid'],
                    'code'       => '304',
                    'message'    => '회원님의 수강상품들 중에 오늘까지만 수업등록이 가능한 쿠폰 수강상품이 있습니다.',
                    'board_name' => '쿠폰의 만료일 임박 알림',
                    'user_name'  => 'SYSTEM',
                    'go_url'     => 'http://www.mint05.com/pubhtml/mypage/coupon.php',
                    'regdate'    => date('Y-m-d H:i:s'),
                );
                $this->notify_mdl->insert_notify($notify_result);
            }
        }
    }

    /**
     * 쿠폰을 등록한 후 수업등록을 하지 않는 회원에게 SMS 발송
     */
    public function cron_sms_for_coupon_lesson_without_regist_by_once_on_3days()
    {
        $this->load->model('lesson_mdl');

        $today = date("Y-m-d");
        $iTime = time();
        $sendList = array();

        $where = " AND mc.validate > '".$today."'";
        $list = $this->lesson_mdl->check_coupon_lesson_regist($where);
        if ($list)
        {
            foreach ($list as $row)
            {
                //휴대폰번호 없으면 패스
                if (!$row['mobile']) continue;

                //오늘이 쿠폰 만료일이면 패스 : 이 경우는 다른 곳에서 따로 처리된다
                if ($today == $row['validate']) continue;

                //등록한지 3일씩 되었는지 체크. 계산된 날 수가 3의 배수가 아니면 패스
                $iPassedDay = ceil(($iTime - strtotime($row['regdate'])) / 86400);
                if ($iPassedDay > 0 && $iPassedDay % 3 > 0) continue;

                //이미 발송한 휴대폰번호와 쿠폰종류는 다시 발송안함
                if (isset($sendList[$row['mobile']][$row['coupon_type']]) && $sendList[$row['mobile']][$row['coupon_type']]) continue;

                //쿠폰수업 등록안내 문자 발송 - 고유번호 186
                $options = array(
                    'name'        => $row['name'],
                    'wiz_id'      => $row['wiz_id'],
                    'uid'         => $row['uid'],
                    'coupon_type' => $row['coupon_type'],
                    'man_name'    => 'SYSTEM'
                );
                sms::send_sms($row['mobile'], 186, $options);

                //발송기록에 저장
                $sendList[$row['mobile']][$row['coupon_type']] = true;
            }
        }
        
    }

    public function push_test()
    {
        $pInfo = array(
            "member"  => '테스트', 
            "time"    => '13:00',
            "teacher" => 'systemtest', 
        );
        AppPush::send_push(119003, "1101", $pInfo);
        
    }
    
}








