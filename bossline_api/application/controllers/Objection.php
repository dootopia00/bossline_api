<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Objection extends _Base_Controller {
    public $upload_path_objection = ISTESTMODE ? 'test_upload/attach/objection/':'attach/objection/';
    
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }
    
    // 사유 리스트
    public function reason_list_()
    {
        $return_array = array();

        $request = array(
            "type" => trim($this->input->post('type')) ,    // 클레임타입(class, mset, 등등). all = 통째로 리턴
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('objection_mdl');
        
        $where = '';
        if($request['type'] != 'all')
        {
            $where = ' WHERE type = "'.$request['type'].'"';
        }
        $list = $this->objection_mdl->list_objection_reason($where);

        if(!$list)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0201';
            $return_array['data']['err_msg'] = '등록된 데이터가 없습니다.';
            echo json_encode($return_array);
            exit;
        }

        $return_list = [];

        // 클레임타입끼리 정리해서 리턴
        foreach($list as $row)
        {
            $return_list[$row['type']][] = $row;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['list'] = $return_list;
        echo json_encode($return_array);
        exit;
    }

    public function regist_report()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "type" => trim($this->input->post('type')) ,    // 클레임타입(class, mset, 등등)
            "claim" => $this->input->post('claim'),         // 클레임유형 번호. 콤마로 여러개 구분
            "claims_etc" => $this->input->post('claims_etc') ? $this->input->post('claims_etc'):'', // 클레임 항목 외에 수기작성한 불만내용
            "file" => isset($_FILES["file"]) ? $_FILES["file"] : null,
            "movie_url" => $this->input->post('movie_url') ? $this->input->post('movie_url'):'',
            "file_memo" => $this->input->post('file_memo') ? $this->input->post('file_memo'):'',
            "receive_sms" => $this->input->post('receive_sms') ? '1':'0',
            "is_app" => trim($this->input->post('is_app')),         // pc, mobile
            "code" => trim($this->input->post('code')),             // 리포트할 번호. code 유형은 아래 주석과 같이 존재한다.
            /* 
                "mset_idx" => trim($this->input->post('mset_idx')),                 // mset 번호
                "ex_log_id" => trim($this->input->post('ex_log_id')),               // exam 번호
                "mb_unq" => trim($this->input->post('mb_unq')),                     // 게시글 번호
                "correction_idx" => trim($this->input->post('correction_idx')),     // 첨삭번호
                "sc_id" => trim($this->input->post('sc_id')),                       // 수업번호 
                -->code 로 통일
            */
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

        $this->load->model('objection_mdl');
        $this->load->model('lesson_mdl');
        $this->load->model('board_mdl');
        $this->load->model('mset_mdl');
        $this->load->model('book_mdl');

        $claim_tutor_uid = 0;
        $add_insert_param = [];
        $where = '';
        // 체크 및 파라미터 설정
        switch($request['type'])
        {
            // 피드백
            case 'class':
                $where = ' sc_id=';
                $check = $this->lesson_mdl->row_schedule_by_sc_id($request['code'], $wiz_member['wm_uid']);
                if(!$check || !$check['tu_uid'])
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = '0702';
                    $return_array['data']['err_msg'] = '데이터가 없습니다.(1)';
                    echo json_encode($return_array);
                    exit;
                }

                $claim_tutor_uid = $check['tu_uid'];
                $add_insert_param = [
                    'lesson_id' => $check['lesson_id'],
                    'sc_id'     => $request['code'],
                ];

                break;
            // 첨삭
            case 'gc':
                $where = ' correction_idx=';
                $check = $this->board_mdl->row_article_wiz_correct_by_pk($request['code']);
                if(!$check || !$check['mb_tu_uid'] || $check['mb_uid'] != $wiz_member['wm_uid'])
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = '0702';
                    $return_array['data']['err_msg'] = '데이터가 없습니다.(2)';
                    echo json_encode($return_array);
                    exit;
                }

                $claim_tutor_uid = $check['mb_tu_uid'];
                $add_insert_param = [
                    'correction_idx'     => $request['code'],
                ];

                break;
            case 'transcription':   // 수업대본
            case 'nsboard':         // ns 과제
            case 'ieltsboard':
            case 'mset':            // MSET 평가게시판 - 종료
            case 'ahophomework':    // ahop 과제물
            case 'ahoptest':        // ahop 시험- 종료
                $where = ' mb_unq=';
                $check = $this->board_mdl->row_article_title_by_mb_unq($request['code']);
                if(!$check || !$check['mb_tu_uid'] || $check['mb_wiz_id'] != $wiz_member['wm_wiz_id'])
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = '0702';
                    $return_array['data']['err_msg'] = '데이터가 없습니다.(3)';
                    echo json_encode($return_array);
                    exit;
                }

                $claim_tutor_uid = $check['mb_tu_uid'];
                $add_insert_param = [
                    'table_code' => $check['mb_table_code'],
                    'mb_unq'     => $request['code'],
                ];

                break;
            //mset 평가 결과보기 불만족
            case 'mset_result':         
                $where = ' mset_idx=';
                $check = $this->mset_mdl->row_mset_apply($request['code']);
                if(!$check || !$check['mmr_tu_uid'] || $check['mmr_uid'] != $wiz_member['wm_uid'])
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = '0702';
                    $return_array['data']['err_msg'] = '데이터가 없습니다.(4)';
                    echo json_encode($return_array);
                    exit;
                }
                
                $claim_tutor_uid = $check['mmr_tu_uid'];
                $add_insert_param = [
                    'mset_idx'     => $request['code'],
                ];

                break;
            // ahop 시험
            case 'exam':
                $where = ' ex_log_id=';
                $check = $this->book_mdl->check_exam_log_by_ex_id($wiz_member['wm_uid'], $request['code']);
                if(!$check)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = '0702';
                    $return_array['data']['err_msg'] = '데이터가 없습니다.(5)';
                    echo json_encode($return_array);
                    exit;
                }

                $add_insert_param = [
                    'ex_log_id'     => $request['code'],
                ];

                break;
            default:
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = '0703';
                $return_array['data']['err_msg'] = '잘못된타입의 리포트입니다.';
                echo json_encode($return_array);
                exit;
        }

        //리포트 중복 등록체크
        $where = ' WHERE '.$where.$request['code'];
        $checked = $this->objection_mdl->count_objection($where);
        
        if($checked['cnt'] > 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0704';
            $return_array['data']['err_msg'] = '이미 불만족 리포트가 등록되어 있습니다.';
            echo json_encode($return_array);
            exit;
        }

        $file_name = '';
        if($request['file'])
        {
            /*
                파일 업로드 확장자 제한여부
                null : 제한없음
                null 아닐시 : 제한
            */
            $upload_limit_size = 5;
            $ext_array = array('jpg', 'gif', 'png', 'zip');

            $res = S3::put_s3_object($this->upload_path_objection, $request['file'], $upload_limit_size, $ext_array);

            if($res['res_code'] != '0000')
            {
                echo json_encode($res);
                exit;
            }

            $file_name = $res['file_name'];
        }

        $claims = explode(',',$request['claim']);

        //타입별 제목매칭하여 생성
        $title_match = [
            'class'         => 'CLASS COMPLAINT (수업 불만족)',
            'gc'            => 'GC COMPLAINT (첨삭 불만족)',
            'transcription' => 'TRANSCRIPTION COMPLAINT (받아쓰기 불만족)',
            'nsboard'       => 'NS COMPLAINT (NS 불만족)',
            'ieltsboard'    => 'IELTS COMPLAINT (IELTS 불만족)',
            'mset'          => 'MSET COMPLAINT (MSET 불만족)',
            'mset_result'   => 'MSET RESULT COMPLAINT (MSET 결과 불만족)',
            'ahophomework'  => 'AHOP HOMEWORK COMPLAINT (AHOP 불만족)',
            'ahoptest'      => 'AHOP TEST COMPLAINT (AHOP 불만족)',
            'exam'          => 'AHOP TEST COMPLAINT (AHOP 불만족)',
        ];

        $insert_param = [
            'uid'               => $wiz_member['wm_uid'],
            'type'              => $request['type'],
            'claims_etc'        => $request['claims_etc'],
            'receive_sms'       => $request['receive_sms'],
            'is_mobile'         => $request['is_app'] == 'pc' ? 0:1,
            'movie_url'         => $request['movie_url'] ? $request['movie_url']:'',
            'claims_cnt'        => count($claims),
            'confirm_admin_id'  => '',
            'regdate'           => date('Y-m-d H:i:s'),
            'complete_date'     => '0000-00-00 00:00:00',
            'file_path'         => $file_name,                  //실제로 업로드된 파일명
            'file_name'         => $request['file'] ? $request['file']['name']:null,    // 원래 파일명
            'file_size'         => $request['file'] ? $request['file']['size']:null,
            'file_memo'         => $request['file_memo'],
            'claim_tutor_uid'   => $claim_tutor_uid,
            'title'             => $title_match[$request['type']],
        ];

        if($add_insert_param)
        {
            $insert_param = array_merge($insert_param, $add_insert_param);
        }

        $this->objection_mdl->insert_objection($insert_param, $claims);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "불만족 리포트가 등록되었습니다.";
        echo json_encode($return_array);
        exit;
    }

    
    // 작성한 불만족 리스트
    public function list_()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "search_key" => trim($this->input->post('search_key')) ? trim($this->input->post('search_key')):'',
            "search_keyword" => trim($this->input->post('search_keyword')) ? trim($this->input->post('search_keyword')):'',
            "start" => trim($this->input->post('start')) ? trim($this->input->post('start')):'0',
            "limit" => trim($this->input->post('limit')) ? trim($this->input->post('limit')):'10',
            "order_field" => trim($this->input->post('order_field')) ? trim($this->input->post('order_field')):'mo.ob_idx',
            "order" => trim($this->input->post('order')) ? trim($this->input->post('order')):'desc',
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

        $this->load->model('objection_mdl');
        
        $where = ' WHERE mo.uid='.$wiz_member['wm_uid'];

        //검색
        if($request['search_keyword'] && $request['search_key'])
        {
            $where .= " AND ".$request['search_key'].' LIKE "%'.$request['search_keyword'].'%"';
        }
        
        $count = $this->objection_mdl->count_objection($where);
        $total_cnt = $count ? $count['cnt']:0;

        $orderby = ' ORDER BY '.$request['order_field'].' '.$request['order'];
        $limit = ' LIMIT '.$request['start'].', '.$request['limit'];

        $list = $this->objection_mdl->list_objection($where, $orderby, $limit);

        if(!$list)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0201';
            $return_array['data']['err_msg'] = '등록된 데이터가 없습니다.';
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['total_cnt'] = $total_cnt;
        $return_array['data']['list'] = $list;
        echo json_encode($return_array);
        exit;
    }
        
    
    // 작성한 불만족 상세보기   
    public function view()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "mo_ob_idx" => trim($this->input->post('mo_ob_idx')),
            "is_app" => trim($this->input->post('is_app')),         // pc, mobile
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

        $this->load->model('objection_mdl');
        
        $objection = $this->objection_mdl->row_objection($request['mo_ob_idx']);

        if(!$objection || $objection['mo_uid'] != $wiz_member['wm_uid'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0201';
            $return_array['data']['err_msg'] = '등록된 데이터가 없습니다.';
            echo json_encode($return_array);
            exit;
        }

        if($objection['mo_file_path'])
        {
            $objection['mo_file_path'] = Thumbnail::$cdn_default_url.'/'.$this->upload_path_objection.$objection['mo_file_path'];
        }
        
        //수정용 원본 백업
        $objection['mo_claims_etc_nl2br'] = nl2br($objection['mo_claims_etc']);
        $objection['mo_content'] = nl2br($objection['mo_content']);

        //다음 리포트가 있는지
        $next = $this->objection_mdl->checked_next_report($request['mo_ob_idx'], $wiz_member['wm_uid']);
        $objection['next'] = $next ? $next:'';
        //이전 리포트가 있는지
        $prev = $this->objection_mdl->checked_prev_report($request['mo_ob_idx'], $wiz_member['wm_uid']);
        $objection['prev'] = $prev ? $prev:'';

        //선택된 불만 리스트
        $claims = $this->objection_mdl->selected_objection_reason($request['mo_ob_idx']);

        $old_mint_url =  $this->config->item('mint_url');
        $web_domain =  $this->config->item('web_domain');

        $old_platform_link = ($request['is_app'] =='pc' ? ($old_mint_url['old_pc'].'/pubhtml'):$old_mint_url['old_m']);

        //불만 원본 링크, 라벨구성
        $origin_link = array();
        $origin_link_label = '';
        switch ($objection['mo_type']) {
            case 'class':
                $origin_link_label = "원본 수업/피드백 보기";
                $origin_link['url'] = $web_domain.'/#/popup-feedback?no='.$objection['mo_sc_id'];
                $origin_link['state'] = "popup-feedback";
                $origin_link['hash']['no'] = $objection['mo_sc_id'];
                break;
            case 'gc':
                $origin_link_label = "원본 영어첨삭 보기";
                $origin_link['url'] = $web_domain.'/#/correction-view?tc=correction&mu='.$objection['mo_correction_idx'];
                $origin_link['state'] = "correction-view";
                $origin_link['hash']['tc'] = "correction";
                $origin_link['hash']['mu'] = $objection['mo_correction_idx'];
                break;
            case 'transcription':
                $origin_link_label = "원본 수업대본 보기";
                $origin_link['url'] = $web_domain.'/#/board-view?tc=1130&mu='.$objection['mo_mb_unq'];
                $origin_link['state'] = "board-view";
                $origin_link['hash']['tc'] = "1130";
                $origin_link['hash']['mu'] = $objection['mo_mb_unq'];
                break;
            case 'nsboard':
                $origin_link_label = "원본 NS 과제물 보기";
                $origin_link['url'] = $web_domain.'/#/board-view?tc=1354&mu='.$objection['mo_mb_unq'];
                $origin_link['state'] = "board-view";
                $origin_link['hash']['tc'] = "1354";
                $origin_link['hash']['mu'] = $objection['mo_mb_unq'];
                break;
            case 'mset_result':
                $origin_link_label = "원본 MSET 결과 보기";
                if($request['is_app'] =='pc')
                {
                    $origin_link['url'] = $web_domain.'/#/popup-mset-result?idx='.$objection['mo_mset_idx'];
                    $origin_link['state'] = "popup-mset-result";
                    $origin_link['hash']['idx'] = $objection['mo_mset_idx'];
                }
                else
                {
                    $origin_link['url'] = $web_domain.'/#/mset-details?mc='.$objection['mo_mset_idx'];
                    $origin_link['state'] = "mset-details";
                    $origin_link['hash']['mc'] = $objection['mo_mset_idx'];
                }
                
                break;
            case 'ahophomework':
                $origin_link_label = "원본 AHOP 과제물 보기";
                $origin_link['url'] = $web_domain.'/#/board-view?tc=1366&mu='.$objection['mo_mb_unq'];
                $origin_link['state'] = "board-view";
                $origin_link['hash']['tc'] = "1366";
                $origin_link['hash']['mu'] = $objection['mo_mb_unq'];
                break;
        }

        $objection['origin_link_label'] = $origin_link_label;
        $objection['origin_link'] = $origin_link;

        //수정을 위한 추가 정보값

        //선택된 사유 정보 저장
        $claim_idx = [];
        foreach($claims as $row)
        {
            $claim_idx[] = $row['moc_claims_idx'];
        }

        $objection['mo_rsms'] = $objection['mo_rsms'] ? 'Y' : 'N';

        //사유 기타 체크
        $objection['claim_0'] = in_array('0', $claim_idx)? '1' : '0';

        //선택된 타입의 전체 사유 리스트 가져오기
        $where = ' WHERE type = "'.$objection['mo_type'].'"';
        $list = $this->objection_mdl->list_objection_reason($where);

        if(!$list)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0201';
            $return_array['data']['err_msg'] = '등록된 데이터가 없습니다.';
            echo json_encode($return_array);
            exit;
        }

        $return_list = [];

        foreach($list as $row)
        {
            //선택된 사유인지 확인
            if(in_array($row['idx'], $claim_idx))
                $row['checked'] = $row['idx'];
            else
                $row['checked'] = '';

            $return_list[$row['type']][] = $row;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['info'] = $objection;
        $return_array['data']['claims'] = $claims;
        $return_array['data']['list'] = $return_list;
        echo json_encode($return_array);
        exit;
    }

    // 작성한 불만족 수정 수정하기
    public function modify_objection()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "mo_ob_idx" => trim($this->input->post('mo_ob_idx')),
            "claim" => $this->input->post('claim'),         // 클레임유형 번호. 콤마로 여러개 구분
            "claims_etc" => $this->input->post('claims_etc') ? $this->input->post('claims_etc'):'', // 클레임 항목 외에 수기작성한 불만내용
            "file" => isset($_FILES["file"]) ? $_FILES["file"] : null,
            "movie_url" => $this->input->post('movie_url') ? $this->input->post('movie_url'):'',
            "file_memo" => $this->input->post('file_memo') ? $this->input->post('file_memo'):'',
            "file_delete" => $this->input->post('file_delete') ? '1':'0',
            "receive_sms" => $this->input->post('receive_sms') ? '1':'0',
            "is_app" => trim($this->input->post('is_app')),         // pc, mobile
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

        $this->load->model('objection_mdl');
        $objection = $this->objection_mdl->row_objection($request['mo_ob_idx']);

        if(!$objection || $objection['mo_uid'] != $wiz_member['wm_uid'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0201';
            $return_array['data']['err_msg'] = '등록된 데이터가 없습니다.';
            echo json_encode($return_array);
            exit;
        }

        if($objection['mo_state'] != '0')
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0712';
            $return_array['data']['err_msg'] = '수정 가능한 상태가 아닙니다.';
            echo json_encode($return_array);
            exit;
        }

        $claims = explode(',',$request['claim']);

        // 파일 업로드
        $upload_file = array();
        $file_name = '';
        if($request['file'] || $request['file_delete'])
        {
            /*
                파일 업로드 확장자 제한여부
                null : 제한없음
                null 아닐시 : 제한
            */

            // 기존파일 있으면 삭제
            if($objection['mo_file_path'])
            {
                S3::delete_s3_object($this->upload_path_objection, $objection['mo_file_path']);
            }

            if(!$request['file_delete'])
            {
                $upload_limit_size = 5;
                $ext_array = array('jpg', 'gif', 'png', 'zip');

                $res = S3::put_s3_object($this->upload_path_objection, $request['file'], $upload_limit_size, $ext_array);

                if($res['res_code'] != '0000')
                {
                    echo json_encode($res);
                    exit;
                }
            }

            $file_name = $res['file_name'];
            $upload_file = array(
                'file_path'         => $file_name,                  //실제로 업로드된 파일명
                'file_name'         => $request['file'] ? $request['file']['name']:null,    // 원래 파일명
                'file_size'         => $request['file'] ? $request['file']['size']:null,
                'file_memo'         => $request['file_memo']
            );
        }

        //수정된 정보 저장
        $objection = array(
            'claims_etc' => $request['claims_etc'],
            'claims_cnt' => count($claims),
            'receive_sms' => $request['receive_sms'],
            'movie_url' => $request['movie_url']
        );

        // 업로드 파일이 있으면 배열을 합침
        if(count($upload_file) > 0) $objection = array_merge($objection, $upload_file);

        // DB UPDATE
        $result = $this->objection_mdl->update_objection(($objection), $request['mo_ob_idx'], $claims);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "수정되었습니다.";
        echo json_encode($return_array);
        exit;
    }

    
    // 작성한 불만족 삭제
    public function delete()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "mo_ob_idx" => trim($this->input->post('mo_ob_idx')),
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

        $this->load->model('objection_mdl');
        
        $objection = $this->objection_mdl->row_objection($request['mo_ob_idx']);

        if(!$objection || $objection['mo_uid'] != $wiz_member['wm_uid'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0201';
            $return_array['data']['err_msg'] = '등록된 데이터가 없습니다.';
            echo json_encode($return_array);
            exit;
        }

        if($objection['mo_state'] != '0')
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0712';
            $return_array['data']['err_msg'] = '삭제가능한 상태가 아닙니다.';
            echo json_encode($return_array);
            exit;
        }

        if($objection['mo_file_path'])
        {
            S3::delete_s3_object($this->upload_path_objection, $objection['mo_file_path']);
        }
        
        $result = $this->objection_mdl->delete_objection($request['mo_ob_idx']);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "삭제되었습니다.";
        echo json_encode($return_array);
        exit;
    }

}








