<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Tutor extends _Base_Controller {


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
            'start' => $this->input->post('start') ? $this->input->post('start') :0,
            'limit' => $this->input->post('limit') ? $this->input->post('limit') :'10',
            'order_field' => trim($this->input->post('order_field')) ? trim($this->input->post('order_field')) :'tu_name',
            'order' => $this->input->post('order') ? $this->input->post('order') :'asc',
            'sec_order_field' => trim($this->input->post('sec_order_field')) ? trim($this->input->post('sec_order_field')) :'',
            'sec_order' => $this->input->post('sec_order') ? $this->input->post('sec_order') :'asc',
            'tu_name' => $this->input->post('tu_name') ? strtolower(trim($this->input->post('tu_name'))) :'',
            'abletime' => trim($this->input->post('abletime')) ? trim($this->input->post('abletime')) :'',  # NT:66,AM:1,PM:18
            'hashtag' => trim($this->input->post('hashtag')) ? trim($this->input->post('hashtag')) :'',     # 강사특징
            'textbook' => trim($this->input->post('textbook')) ? trim($this->input->post('textbook')) :'',  # 교재
            'ielts' => trim($this->input->post('ielts')) ? trim($this->input->post('ielts')) :'',
            'usphiluk' => trim($this->input->post('usphiluk')) ? trim($this->input->post('usphiluk')) :'',
            'mintbee' => trim($this->input->post('mintbee')) ? trim($this->input->post('mintbee')) :'',
            'thunder' => trim($this->input->post('thunder')) ? trim($this->input->post('thunder')) :'',
            "start_hashtag" => ($this->input->post('start_hashtag')) ? trim($this->input->post('start_hashtag')) : 0,
            "limit_hashtag" => ($this->input->post('limit_hashtag')) ? trim($this->input->post('limit_hashtag')) : 5,
            'addbooklist' => trim($this->input->post('addbooklist')),
            'addhashtaglist' => trim($this->input->post('addhashtaglist')),
            'wiz_id' => trim($this->input->post('wiz_id')),
            'authorization' => trim($this->input->post('authorization')),
            #"order_field_hashtag" => ($this->input->post('order_field_hashtag')) ? trim(strtolower($this->input->post('order_field_hashtag'))) : "thl.count",
            #"order_hashtag" => ($this->input->post('order_hashtag')) ? trim(strtoupper($this->input->post('order_hashtag'))) : "DESC",
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        $this->load->model('tutor_mdl');
        $this->load->model('book_mdl');
        $this->load->helper('tutor');

        /*
        28,29 그룹은
        갓 등록된 신규강사거나, 평가중인 강사거나, 부득이한 이유로 수업을 진행하기 어려운 모니터링이 필요한 강사들.
        학생들이 평가를 해주는 평가이벤트에 참여하는 모니터대상인 신규 강사도 포함되어 있고, 홈페이지에서 스케줄 예약이 가능한 상태이기도 하다.
        1462 : 테스트계정
        */
        $where = " WHERE wt.state=1 AND wt.del_yn='n' AND wt.group_id NOT IN (28,29) AND wt.tu_uid!=1462 ";

        if($request['tu_name'])
        {    
            $where.=" AND LOWER(wt.tu_name) like '%".$request['tu_name']."%'";
        }

        if($request['abletime'])
        {    
            $where.=" AND wt.f_id IN(".$request['abletime'].")";
        }

        if($request['textbook'])
        {
            //추후커리큘럼테이블 변경
            $book_list = $this->book_mdl->list_book_by_id($request['textbook']);

            if(count($book_list) > 0)
            {
                $book_like = [];
                foreach($book_list as $v)
                {
                    $book_like[] = " wt.pre_pro LIKE '%".$v['book_name']."%' ";
                }

                $where.= sprintf(' AND ( %s )',implode(' OR ',$book_like));
            }
        }

        if($request['hashtag'])
        {
            $hash_tutor_list = tutor_major_hashtag(5,$request['hashtag']);
            if(count($hash_tutor_list) > 0)
            {
                $hash_tu_uids = array_keys($hash_tutor_list);

                $where.= sprintf(' AND wt.tu_uid IN(%s) ', implode(',',$hash_tu_uids));
            }
        }

        if($request['ielts'])
        {
            $where.= " AND wt.tropy LIKE '%".$request['ielts']."%'";
        }

        if($request['usphiluk'])
        {
            $where.= " AND wt.tropy LIKE '%".$request['usphiluk']."%'";
        }
        
        if($request['mintbee'])
        {
            $where.= " AND wt.tropy LIKE '%".$request['mintbee']."%'";
        }

        if($request['thunder'] == "Y")
        {
            $where.= " AND wt.byorachiki_only  = 'Y'";
        }


        if($request['order_field'] =='star')
        {
            $orderby = ' ORDER BY tsl.average_total '.$request['order'];
        }
        else
        {
            $orderby = sprintf(' ORDER BY wt.%s %s',$request['order_field'],$request['order']);
        }

        if($request['sec_order_field'] && $request['sec_order_field'] !='star')
        {
            $orderby = $orderby . sprintf(', wt.%s %s',$request['sec_order_field'],$request['sec_order']);
        }

        $tutor_list_count = $this->tutor_mdl->row_table_count('wiz_tutor AS wt',$where);
        $return_array['data']['total_cnt'] = $tutor_list_count['cnt'];

        $join = '';
        $select_col_content = '';
        if($request['wiz_id'])
        {
            $wiz_member = base_get_wiz_member();
            if(!$wiz_member)
            {
                echo json_encode(base_get_err_auth_check_msg());
                exit;
            }

            $join = ' LEFT JOIN tutor_like AS tl ON tl.tu_uid = wt.tu_uid AND tl.uid='.$wiz_member['wm_uid'];
            $select_col_content = ", (CASE WHEN tl.uid IS NULL then 'Y' ELSE  'N' END) AS tutor_like_del ";
        }        

        $where.= $orderby. sprintf(' LIMIT %s , %s',$request['start'], $request['limit']);
        $tutor_list = $this->tutor_mdl->list_tutor_join_star($where, $join, $select_col_content);

        // 교재필터에 뿌려줄 교재 리스트
        $book_list = null;
        if($request['addbooklist'])
        {
            $book_list = $this->book_mdl->list_main_book();        //추후커리큘럼테이블 변경

            if($book_list){
                foreach($book_list as $key=>$book){
                    $book_list[$key]['wb_book_name'] = str_replace('★','',$book['wb_book_name']);
                }
            }
        }

        // 강사특징 필터에 뿌려줄 리스트
        $hashtag_list = null;
        if($request['addhashtaglist'])
        {
            $hashtag_list = tutor_get_list_star_item_type(true);
        }
    
        $return_array['book_list'] = $book_list;
        $return_array['hashtag_list'] = tutor_hashtag_array_to_str($hashtag_list);

        if($tutor_list)
        {
            $return_array['data']['list'] = tutor_merge_list_addinfo($tutor_list);
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

    public function list_tutor_individual_()
    {
        $return_array = array();

        $request = array(
            'wiz_id' => trim($this->input->post('wiz_id')),
            'authorization' => trim($this->input->post('authorization')),
            "is_app" => trim($this->input->post('is_app')),   // pc, mobile
            'start_recently' => $this->input->post('start_recently') ? $this->input->post('start_recently') :'0',
            'limit_recently' => $this->input->post('limit_recently') ? $this->input->post('limit_recently') :'6',
            'order_field_recently' => trim($this->input->post('order_field_recently')) ? trim($this->input->post('order_field_recently')) :'startday',
            'order_recently' => $this->input->post('order') ? $this->input->post('order') :'DESC',
            'start_like' => $this->input->post('start_like') ? $this->input->post('start_like') :'0',
            'limit_like' => $this->input->post('limit_like') ? $this->input->post('limit_like') :'6',
            'order_field_like' => trim($this->input->post('order_field_like')) ? trim($this->input->post('order_field_like')) :'regdate',
            'order_like' => $this->input->post('order') ? $this->input->post('order') :'DESC',
            // 'type' => trim($this->input->post('type')) ? $this->input->post('type') :'recently',       //  recently/like
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
        
        $where_recently = null;
        $group_recently = null;
        $order_recently = null;
        $limit_recently = null;

        $where_like = null;
        $order_like = null;
        $limit_like = null;
        
        if($request['is_app']=='mobile')
        {
            $order_recently = sprintf("ORDER BY %s %s", $request['order_field_recently'], $request['order_recently']);
            $limit_recently = sprintf("LIMIT %s , %s", $request['start_recently'], $request['limit_recently']);

            $order_like = sprintf("ORDER BY %s %s", $request['order_field_like'], $request['order_like']);
            $limit_like = sprintf("LIMIT %s , %s", $request['start_like'], $request['limit_like']);
        }

        $this->load->model('tutor_mdl');
        
        //최근수업내역 강사 정보
        $now = date('Y-m-d h:i:s');
        //lesson_id 100000000 이상이 레벨테스트
        $where_recently = " WHERE ws.uid = '".$wiz_member['wm_uid']."' AND ws.startday < '".$now."' AND ws.present = '2' AND ws.lesson_id < '100000000'";
        $group_recently = " GROUP BY tu_uid";
        $tutor_list_recently = $this->tutor_mdl->list_schedule_by_uid($where_recently, $group_recently, $order_recently, $limit_recently);
        
        //최근수업내역 강사 총 카운트
        $recently_count = $this->tutor_mdl->list_count_schedule($where_recently, $group_recently);

        //내가 좋아요 누른 강사 정보
        $where_like = " WHERE tl.uid = '".$wiz_member['wm_uid']."'";
        $tutor_list_like = $this->tutor_mdl->list_tutor_like($where_like, $order_like, $limit_like);

        //내가 좋아요 누른 강사 총 카운트
        $like_count = $this->tutor_mdl->list_count_tutor_like_by_uid($wiz_member['wm_uid']);

        $return_array['data']['recently']['list'] = $tutor_list_recently;
        $return_array['data']['recently']['total_cnt'] = $recently_count['cnt'];
        $return_array['data']['like']['list'] = $tutor_list_like;
        $return_array['data']['like']['total_cnt'] = $like_count['cnt'];
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        echo json_encode($return_array);
        exit;

    }

    public function info()
    {
        $return_array = array();

        $request = array(
            'tu_uid' => trim($this->input->post('tu_uid')),
            'commute_limit' => trim($this->input->post('commute_limit')) ? trim($this->input->post('commute_limit')):5,
            'evaluation_start' => trim($this->input->post('evaluation_start')) ? trim($this->input->post('evaluation_start')):0,
            'evaluation_limit' => trim($this->input->post('evaluation_limit')) ? trim($this->input->post('evaluation_limit')):1,
            'special_start' => trim($this->input->post('special_start')) ? trim($this->input->post('special_start')):0,
            'special_limit' => trim($this->input->post('special_limit')) ? trim($this->input->post('special_limit')):3,
            'special_order_field' => trim($this->input->post('special_order_field')) ? trim($this->input->post('special_order_field')):'mb_c_uid',
            'special_order' => trim($this->input->post('special_order')) ? trim($this->input->post('special_order')):'desc',
            'wiz_id' => trim($this->input->post('wiz_id')) ,
            'authorization' => trim($this->input->post('authorization')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        $this->load->model('tutor_mdl');
        // 강사 정보
        $tutor = $this->tutor_mdl->get_tutor_info_by_tu_uid($request['tu_uid']);
        
        if($tutor)
        {
            if($request['wiz_id'])
            {
                $wiz_member = base_get_wiz_member();
                if(!$wiz_member)
                {
                    echo json_encode(base_get_err_auth_check_msg());
                    exit;
                }

                $tutor_like = $this->tutor_mdl->tutor_like_info($request['tu_uid'],$wiz_member['wm_uid']);
                $tutor['tutor_like_del'] = $tutor_like['cnt'] ? 'N':'Y';
            }        

            $request['tu_name'] = $tutor['tu_name'];
            $tutor_add_info = tutor_add_detail_info($request);

            //unset($tutor_add_info['analyzed_hashtag'][2]);  // 추천연령은 따로 recomment_log 에 있어서 제외
            $tutor['web_time_str'] = common_web_time_to_str($tutor['web_time'],$tutor['web_av_time']);
            
            /* 
                강사 특징
                - 벼락치기 전용강사 여부 트로피 추가
                - 벼락치기 전용강사 (byorachiki_only 컬럼), 그외 (tropy 컬럼)
                - 프론트 >  tropy_arr 값을 반복문으로 그림
            */
            $tropy_arr = tutor_tropys_replace_to_str($tutor['tropy']);

            if($tutor['byorachiki_only'] == "Y")
            {
                if($tropy_arr)
                {
                    array_push($tropy_arr, array("thunder" => "벼락치기 전용 강사"));
                }
                else
                {
                    $tropy_arr = array();
                    array_push($tropy_arr, array("thunder" => "벼락치기 전용 강사"));
                }
            }

            MintQuest::request_batch_quest('10', $request['tu_uid']);

            $tutor['tropy_arr'] = $tropy_arr;
            $return_array['data']['info'] = $tutor;
            $return_array['data']['star'] = $tutor_add_info['t_star'];
            $return_array['data']['commute_log'] = $tutor_add_info['commute_log'];
            $return_array['data']['evaluation'] = $tutor_add_info['evaluation'];
            #$return_array['data']['recomment_log'] = $tutor_add_info['recomment_log'];
            $return_array['data']['analyzed_hashtag'] = $tutor_add_info['analyzed_hashtag'];
            $return_array['data']['special_board'] = $tutor_add_info['special_board'];
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


    public function list_tutor_evaluation_()
    {
        $return_array = array();

        $request = array(
            'wiz_id' => trim($this->input->post('wiz_id')) ,
            'authorization' => trim($this->input->post('authorization')),
            'own' => trim($this->input->post('own')),
            'tu_uid' => trim($this->input->post('tu_uid')),
            'start' => $this->input->post('start') ? $this->input->post('start'):0,
            'limit' => $this->input->post('limit') ? $this->input->post('limit'):5,
            'order_field' => trim($this->input->post('order_field')) ? trim($this->input->post('order_field')):'ts_uid',
            'order' => $this->input->post('order') ? $this->input->post('order'):'desc',
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('tutor_mdl');


        $where = sprintf(" WHERE ts.tu_uid=%d ", $request['tu_uid']);
        if($request['own'] && $request['wiz_id'])
        {
            $wiz_member = base_get_wiz_member();
            if(!$wiz_member)
            {
                echo json_encode(base_get_err_auth_check_msg());
                exit;
            }

            $where.= sprintf(" AND ts.uid= %d ", $wiz_member['wm_uid']);
        }

        $tutor_list_count = $this->tutor_mdl->row_table_count('tutor_star AS ts',$where);
        $return_array['data']['total_cnt'] = $tutor_list_count['cnt'];

        $tutor_star_count = $this->tutor_mdl->count_tutor_star_by_tu_uid($request['tu_uid']);
        $return_array['data']['tutor_star_cnt'] = $tutor_star_count['cnt'];

        $where.= sprintf(" ORDER BY ts.%s %s LIMIT %s , %s", $request['order_field'], $request['order'], $request['start'], $request['limit']);
        
        $list = tutor_list_evaluation($where);

        if($list)
        {
            $return_array['data']['list'] = $list;
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

    public function list_star_item_type()
    {
        $list = tutor_get_list_star_item_type(true);
        
        if($list)
        {
            $return_array['data']['list'] = tutor_hashtag_array_to_str($list);
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

    public function regist_tutor_evaluation()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            'tu_uid' => trim($this->input->post('tu_uid')),
            'star' => trim($this->input->post('star')),
            'review' => trim($this->input->post('review')),
            'item' => trim($this->input->post('item')),
            'byte' => trim($this->input->post('byte')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /* 회원토큰 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        // if(strlen($request['review']) < 300 || strlen($request['review']) > 600)
        if($request['byte'] < 300 || $request['byte'] > 600)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스 오류";
            $return_array['data']['err_code'] = '0401';
            $return_array['data']['err_msg'] = '강사평가 글은 300~600자 까지 입력바랍니다.';
            echo json_encode($return_array);
            exit;
        }

        $result = tutor_regist_evaluation($request);

        if($result['state'])
        {
            //퀘스트
            MintQuest::request_batch_quest('66', $result['insert_id']);

            $return_array['res_code'] = '0000';
            $return_array['msg'] = "등록되었습니다.";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = $result['res_code'];
            $return_array['msg'] = $result['msg'];
            $return_array['data']['err_code'] = $result['err_code'];
            $return_array['data']['err_msg'] = $result['err_msg'];
            echo json_encode($return_array);
            exit;
        }
    }


    public function modify_tutor_evaluation()
    {
        $return_array = array();
        
        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "ts_uid" => trim(strtolower($this->input->post('ts_uid'))),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            'tu_uid' => trim($this->input->post('tu_uid')),
            'star' => trim($this->input->post('star')),
            'review' => trim($this->input->post('review')),
            'item' => trim($this->input->post('item')),
            'byte' => trim($this->input->post('byte')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;  
        }

        
        // if(strlen($request['review']) < 300 || strlen($request['review']) > 600)
        if($request['byte'] < 300 || $request['byte'] > 600)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스 오류";
            $return_array['data']['err_code'] = '0401';
            $return_array['data']['err_msg'] = '강사평가 글은 300~600자 까지 입력바랍니다.';
            echo json_encode($return_array);
            exit;
        }

        $result = tutor_modify_evaluation($request);

        if($result['state'])
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "수정되었습니다.";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = $result['res_code'];
            $return_array['msg'] = $result['msg'];
            $return_array['data']['err_code'] = $result['err_code'];
            $return_array['data']['err_msg'] = $result['err_msg'];
            echo json_encode($return_array);
            exit;
        }
    }


    public function delete_tutor_evaluation()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "ts_uid" => trim(strtolower($this->input->post('ts_uid'))),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }


        $result = tutor_delete_evaluation($request);

        if($result['state'])
        {
            //퀘스트취소
            MintQuest::request_batch_quest_decrement('66', $request['ts_uid']);
            
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "삭제되었습니다.";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = $result['res_code'];
            $return_array['msg'] = $result['msg'];
            $return_array['data']['err_code'] = $result['err_code'];
            $return_array['data']['err_msg'] = $result['err_msg'];
            echo json_encode($return_array);
            exit;
        }
    }


    public function tutor_like()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "tu_uid" => trim(strtolower($this->input->post('tu_uid'))),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "del_yn" => trim(strtolower($this->input->post('del_yn'))),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('tutor_mdl');
        $this->load->model('member_mdl');
        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);

        $param = [
            'tu_uid' => $request['tu_uid'],
            'uid' => $wiz_member['wm_uid'],
        ];
        
        if($request['del_yn'] == 'n')
        {
            $param['regdate'] = date('Y-m-d H:i:s');
            $result = $this->tutor_mdl->insert_tutor_like($param);
        }
        else{
            $result = $this->tutor_mdl->delete_tutor_like($param);
        }
        

        if($result)
        {
            $return_array['res_code'] = '0000';
            $return_array['msg'] = $request['del_yn'] == 'n' ? "즐겨찾기가 등록되었습니다.":"즐겨찾기가 해제되었습니다.";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = 'DB ERROR';
            echo json_encode($return_array);
            exit;
        }
    }

    
    public function select_tutor()
    {
        $request = array(
            "table_code" => trim(strtolower($this->input->post('table_code'))),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('tutor_mdl');
        $this->load->model('board_mdl');

        $wiz_member = base_get_wiz_member();
        $inclass_tutor = [];
        $list = [];
        if($request['table_code'] == 'request' || $request['table_code'] =='toteacher')
        {
            
            if($wiz_member)
            {
                // 수업중인선생님
                $list = $this->tutor_mdl->select_tutors_inclass($wiz_member['wm_uid']);
                if($list)
                {
                    foreach($list as $val)
                    {
                        $inclass_tutor = [
                            'tu_uid' => $val['tu_uid'],
                            'tu_name' => $val['tu_name'],
                        ];
                    }
                }
            }

            // 모든선생님
            $list = $this->tutor_mdl->select_tutors($wiz_member['wm_uid']);
        }
        else
        {
            //강사평가서, 미국 vs 영국 vs 필리핀-> 관리자에서 전체 공지사항으로 선택된 강사들만 호출
            $row = $this->board_mdl->row_mint_boards_notice_sim_content($request['table_code']);

            if(!$row || $row['sim_content'] =='')
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스 오류";
                $return_array['data']['err_code'] = '0401';
                $return_array['data']['err_msg'] = '강사정보가 존재하지 않습니다.';
                echo json_encode($return_array);
                exit;
            }

            $tu_uid_arr = explode(",",$row['sim_content']);
            $list = $this->tutor_mdl->get_tu_name_in_tu_uid($tu_uid_arr);
            
        }
        
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = '';
        $return_array['data']['inclass_tutor'] = $inclass_tutor;
        $return_array['data']['list'] = $list;
        echo json_encode($return_array);
        exit;
    }


    public function checked_write_tutor_star()
    {
        $return_array = array();

        $request = array(
            'wiz_id' => trim($this->input->post('wiz_id')),
            'authorization' => trim($this->input->post('authorization')),
            'tu_uid' => $this->input->post('tu_uid'),
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


        // 오늘 등록된 평가서 있는지 체크
        $where = " WHERE uid = '".$wiz_member['wm_uid']."' AND DATE_FORMAT(regdate, '%Y-%m-%d') = CURDATE()";

        $this->load->model('tutor_mdl');
        $list_today = $this->tutor_mdl->list_tutor_star($where, '');

        if($list_today)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0601";
            $return_array['data']['err_msg'] = "강사평가서는 하루에 한번만 참여 가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        // wiz_lesson, wiz_leveltest 에 과거
        $this->load->model('lesson_mdl');
        $checked = $this->lesson_mdl->checked_tutor_star_wiz_lesson_wiz_leveltest($wiz_member['wm_uid'], $request['tu_uid']);

        if(!$checked)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0602";
            $return_array['data']['err_msg'] = "수업 또는 레벨테스트를 받은 강사에게만 참여 가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        $where2 = " WHERE uid = '".$wiz_member['wm_uid']."' AND regdate > date_add(now(),interval -7 day) AND tu_uid ='".$request['tu_uid']."'";
        $list_7days = $this->tutor_mdl->list_tutor_star($where2, '');

        if($list_7days)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0603";
            $return_array['data']['err_msg'] = "해당강사는 일주일 안에 평가에 참여했습니다.";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "강사평가가 가능합니다.";
        echo json_encode($return_array);
        exit;
    }



}








