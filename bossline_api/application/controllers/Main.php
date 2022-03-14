<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Main extends _Base_Controller {


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
            "wiz_id" => trim($this->input->post('wiz_id')),                 // 좋아요 체크. 필수아님
            "authorization" => trim($this->input->post('authorization')),   // 좋아요 체크. 필수아님
            //배너
            "banner_start" => trim($this->input->post('banner_start')),
            "banner_limit" => trim($this->input->post('banner_limit')),
            "banner_order_field" => ($this->input->post('banner_order_field')) ? trim(strtolower($this->input->post('banner_order_field'))) : "mp.nidx",
            "banner_order" => ($this->input->post('banner_order')) ? trim(strtoupper($this->input->post('banner_order'))) : "DESC",

            //강사
            "tutor_start" => trim($this->input->post('tutor_start')),
            "tutor_limit" => trim($this->input->post('tutor_limit')),
            "tutor_order_field" => ($this->input->post('tutor_order_field')) ? trim(strtolower($this->input->post('tutor_order_field'))) : "tsl.average_total", //tsl.average_total
            "tutor_order" => ($this->input->post('tutor_order')) ? trim(strtoupper($this->input->post('tutor_order'))) : "DESC",
        
            //얼철딕
            "special_start" => trim($this->input->post('special_start')),
            "special_limit" => trim($this->input->post('special_limit')),
            "special_order_field" => trim(strtolower($this->input->post('special_order_field'))) ? trim(strtolower($this->input->post('special_order_field'))) : "mb_c_uid",
            "special_order" => ($this->input->post('special_order')) ? trim(strtoupper($this->input->post('special_order'))) : "DESC",

            //커리큘럼
            "curriculum_start" => trim($this->input->post('curriculum_start')),
            "curriculum_limit" => trim($this->input->post('curriculum_limit')),
            "curriculum_order_field" => ($this->input->post('curriculum_order_field')) ? trim(strtolower($this->input->post('curriculum_order_field'))) : "mc.sorting",
            "curriculum_order" => ($this->input->post('curriculum_order')) ? trim(strtoupper($this->input->post('curriculum_order'))) : "ASC",
            
            //커뮤니티 인기글
            "community_start" => trim($this->input->post('community_start')),
            "community_limit" => trim($this->input->post('community_limit')),
            "community_order_field" => ($this->input->post('community_order_field')) ? trim(strtolower($this->input->post('community_order_field'))) : "mb.mb_unq",
            "community_order" => ($this->input->post('community_order')) ? trim(strtoupper($this->input->post('community_order'))) : "DESC",


            //커뮤니티 최신글
            "recent_community_start" => trim($this->input->post('recent_community_start')) ? trim($this->input->post('recent_community_start')):0,
            "recent_community_limit" => trim($this->input->post('recent_community_limit')) ? trim($this->input->post('recent_community_limit')):1,
            "recent_community_order_field" => ($this->input->post('recent_community_order_field')) ? trim(strtolower($this->input->post('recent_community_order_field'))) : "mb.mb_unq",
            "recent_community_order" => ($this->input->post('recent_community_order')) ? trim(strtoupper($this->input->post('recent_community_order'))) : "DESC",


            "is_app" => trim($this->input->post('is_app')),   // pc, mobile
            "app_type" => trim($this->input->post('app_type')),   // 어플접속일시 ANDROID_NEW_PACKAGE, IOS_NEW_PACKAGE

            // PC 버전에서 쓰이는 파라미터들. 공지, 현재접속자, 금일 누적접속자 추가리턴

            //공지
            "notice_start" => trim($this->input->post('notice_start')) ? trim($this->input->post('notice_start')):0,
            "notice_limit" => trim($this->input->post('notice_limit')) ? trim($this->input->post('notice_limit')):1,
            "notice_order_field" => ($this->input->post('notice_order_field')) ? trim(strtolower($this->input->post('notice_order_field'))) : "mb.mb_unq",
            "notice_order" => ($this->input->post('notice_order')) ? trim(strtoupper($this->input->post('notice_order'))) : "DESC",
            
            
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

        $IS_PC = $request['is_app'] == 'pc' ? true:false;

        /* 배너 시작 */
        
        //$this->load->library('CI_Benchmark');
        //$this->benchmark->mark('banner_start');

        $current_time = strtotime(date('Y-m-d H:i:s'));
        $this->load->model('banner_mdl');

        $where_banner = " WHERE nstartdate <= '".$current_time."' AND nenddate >= '".$current_time."' ";

        //안드로이드 민트어플에서 비로그인, 결제 기록없는 계정만 특별팝업 노출
        $special_popup_display = false;
        if($request['app_type'] == 'ANDROID_NEW_PACKAGE')
        {
            $this->load->model('payment_mdl');
            if($wiz_member)
            {
                $check_paid_history = $this->payment_mdl->check_paid_history_for_first_pay($wiz_member['wm_uid']);
                if($check_paid_history['cnt'] < 1)
                {
                    $special_popup_display = true;
                }
            }
            else
            {
                $special_popup_display = true;
            }
        }

        if($special_popup_display)
        {
            $where_banner .= " AND mp.category!=2 AND (mp.szview_mobile_main = 'Y' OR mp.nidx=364)";
        }
        else
        {
            $where_banner .= " AND mp.category!=2 AND mp.szview_mobile_main = 'Y'";
        }
        
        $order_banner = "";
        $limit_banner = "";

        if($request['banner_limit'] > 0)
        {   
            $limit_banner = sprintf("LIMIT %s , %s", $request['banner_start'], $request['banner_limit']);
        }
        
        $order_banner = sprintf("ORDER BY %s %s", $request['banner_order_field'], $request['banner_order']);

        // 모바일 버전 배너
        $result_banner_mo = $this->banner_mdl->list_banner($where_banner, $order_banner, $limit_banner);

        $where_banner = " WHERE nstartdate <= '".$current_time."' AND nenddate >= '".$current_time."' ";
        $where_banner .= " AND mp.category=2 AND mp.banner_location=2 AND mp.szview_pc = 'Y'";

        //pc 버전 배너
        $result_banner_pc = $this->banner_mdl->list_banner($where_banner, $order_banner, $limit_banner);
        
        if($result_banner_mo || $result_banner_pc)
        {
            $return_array['data']['banner']['list'] = $IS_PC ? $result_banner_pc:$result_banner_mo;
            $return_array['data']['banner']['list_pc'] = $result_banner_pc;
            $return_array['data']['banner']['list_mo'] = $result_banner_mo;
            $return_array['data']['banner']['res_code'] = '0000';
            $return_array['data']['banner']['msg'] = "배너 목록 조회 성공";
        }
        else
        {
            $return_array['data']['banner']['err_code'] = "0201";
            $return_array['data']['banner']['err_msg'] = "[banner] 등록된 데이터가 없습니다.";
        }

        //$this->benchmark->mark('banner_end');
        //echo 'banner : '.$this->benchmark->elapsed_time('banner_start', 'banner_end').PHP_EOL;

        /* 배너 끝 */


        /* 강사 시작 */
        //$this->benchmark->mark('tutor_start');

        $this->load->model('tutor_mdl');

        //정책이 픽스되지 않아서 별점, 강사 이미지 필수값 처리
        $where_tutor = " WHERE wt.del_yn = 'n' AND wt.group_id !='28' AND wt.group_id !='29' AND wt.state=1 AND tsl.average_total IS NOT NULL AND wt.tu_pic IS NOT NULL AND wt.tu_pic != '' ";
        $join = '';
        $select_col_content = '';
        $now_hour = date('H');
        
        if($now_hour >= 1 && $now_hour < 6)
        {
            //NT
            $where_tutor.= " AND wt.f_id ='66'";
        }
        elseif($now_hour >= 6 && $now_hour < 12)
        {
            //AM
        $where_tutor.= " AND wt.f_id ='1' ";
        }
        else
        {
            //PM
            $where_tutor.= " AND wt.f_id ='18'";
        }

        if($request['wiz_id'] && $wiz_member)
        {
            
            $join = ' LEFT JOIN tutor_like AS tl ON tl.tu_uid = wt.tu_uid AND tl.uid='.$wiz_member['wm_uid'];
            $select_col_content = ", (CASE WHEN tl.uid IS NULL then 'Y' ELSE 'N' END) AS tutor_like_del ";
        } 

        $order_tutor = ' GROUP BY wt.tu_uid ';
        if($IS_PC)
        {
            $order_tutor .= sprintf(" ORDER BY %s %s ", $request['tutor_order_field'], $request['tutor_order']);
        }
        else
        {
            $order_tutor .= sprintf(" ORDER BY rand()");
        }

        $where_tutor.= $order_tutor. sprintf(' LIMIT %s , %s',$request['tutor_start'], $request['tutor_limit']);

        $tutor_list = $this->tutor_mdl->list_tutor_join_star($where_tutor,$join,$select_col_content);
        
        if($tutor_list)
        {
            $return_array['data']['tutor']['list'] = tutor_merge_list_addinfo($tutor_list);
            $return_array['data']['tutor']['res_code'] = '0000';
            $return_array['data']['tutor']['msg'] = "강사 목록 조회 성공";
        }
        else
        {
            $return_array['data']['tutor']['err_code'] = "0201";
            $return_array['data']['tutor']['err_msg'] = "[tutor] 등록된 데이터가 없습니다.";
        }

        //$this->benchmark->mark('tutor_end');
        //echo 'tutor : '.$this->benchmark->elapsed_time('tutor_start', 'tutor_end').PHP_EOL;
        // print_r($tutor_list);exit;

        /* 강사 끝 */


        /* 특수게시판(얼철딕) 시작 */
        //$this->benchmark->mark('dic_start');

        $inner_table = "";
        $index_special = "";
        $limit_special = "";
        $order_special = "";
        $where_special = "";
        
        $search_speicial = array();

        array_push($search_speicial, "mb.notice_yn ='N' AND mb.del_yn ='N'");

        $where_search = "";
        $where_search .= implode(" AND ", $search_speicial);

        if($where_search != "")
        {
            $where_special = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('board_mdl');

        $table_name = "얼굴철판딕테이션";

        if($request['special_limit'] > 0)
        {   
            $limit_special = sprintf("LIMIT %s , %s", $request['special_start'], $request['special_limit']);
        }
        
        $order_special = sprintf("ORDER BY %s %s", $request['special_order_field'], $request['special_order']);
        $list_board = NULL;
        

        if($inner_table == "")
        {
            $inner_table = "INNER JOIN wiz_member wm ON mb.uid = wm.uid";
        }
        
        $select_col_content = "";
        $select_col_content = " 'dictation.list' as mb_table_code, '얼굴철판딕테이션' as mbn_table_name,";

        $list_board = $this->board_mdl->list_board_cafeboard($index_special, $where_special, $order_special, $limit_special, $select_col_content, $inner_table);
        $result_special = board_list_writer($list_board,NULL,NULL,NULL,array('content_del'=>true));
        
        if($result_special)
        {
            $return_array['data']['special']['list'] = $result_special;
            $return_array['data']['special']['res_code'] = '0000';
            $return_array['data']['special']['msg'] = "스페셜 목록 조회 성공";
        }
        else
        {
            $return_array['data']['special']['err_code'] = "0201";
            $return_array['data']['special']['err_msg'] = "[special] 등록된 데이터가 없습니다.";
        }
        

        //$this->benchmark->mark('dic_end');
        //echo 'dic : '.$this->benchmark->elapsed_time('dic_start', 'dic_end').PHP_EOL;
        
        /* 특수게시판(얼철딕) 끝 */


        /* 커리큘럼 시작 */
        $where_curriculum = " WHERE mc.use_yn = 'Y'";
        $order_curriculum = "";
        $limit_curriculum = "";
        
        if($request['curriculum_limit'] > 0)
        {   
            $limit_curriculum = sprintf("LIMIT %s , %s", $request['curriculum_start'], $request['curriculum_limit']);
        }

        $order_curriculum = sprintf("ORDER BY %s %s", $request['curriculum_order_field'], $request['curriculum_order']);

        $this->load->model('curriculum_mdl');
        $result_curriculum = $this->curriculum_mdl->list_curriculum($where_curriculum, $order_curriculum, $limit_curriculum); 


        if($result_curriculum)
        {
            $return_array['data']['curriculum']['list'] = $result_curriculum;
            $return_array['data']['curriculum']['res_code'] = '0000';
            $return_array['data']['curriculum']['msg'] = "커리큘럼 목록 조회 성공";
        }
        else
        {
            $return_array['data']['curriculum']['err_code'] = "0201";
            $return_array['data']['curriculum']['err_msg'] = "[curriculum] 등록된 데이터가 없습니다.";
        }
     
        
        /* 커뮤니티 인기글 시작 */

        $index_community = ""; 
        $where_community = "";
        $order_community = "";
        $limit_community = "";

        $search_community = array();
        $index_community = "USE INDEX(PRIMARY)";

        array_push($search_community, "mb.hit >= 100 AND mb.comm_hit > 10 AND (mb.table_code!='1356' || (mb.table_code='1356' AND mb.tu_uid IS NULL)) AND mb.table_code NOT IN ('1380')
        AND (mb.table_code BETWEEN 1100 AND 1199 OR mb.table_code BETWEEN 1300 AND 1399) AND ( mb.daum_img IS NULL OR mb.daum_img <> 'H' ) AND mb.noticeYn ='N'");
    
        $where_search = "";
        $where_search .= implode(" AND ", $search_community);

        if($where_search != "")
        {
            $where_community = sprintf(" WHERE %s", $where_search);
        }
        
        $this->load->model('board_mdl');
        
        if($request['community_limit'] > 0)
        {   
            $limit_community = sprintf("LIMIT %s , %s", $request['community_start'], $request['community_limit']);
        }

        $order_community = sprintf("ORDER BY %s %s", $request['community_order_field'], $request['community_order']);
        
        $list_board = $this->board_mdl->list_theme($index_community, $where_community, $order_community, $limit_community);
        $result_community = board_list_writer($list_board,NULL,NULL,NULL,array('content_del'=>true));

        if($result_community)
        {
            $return_array['data']['community']['list'] = $result_community;
            $return_array['data']['community']['res_code'] = '0000';
            $return_array['data']['community']['msg'] = "커뮤니티 목록 조회 성공";
        }
        else
        {
            $return_array['data']['community']['err_code'] = "0201";
            $return_array['data']['community']['err_msg'] = "[community] 등록된 데이터가 없습니다.";
        }
    
        
        //$this->benchmark->mark('pop_end');
        //echo 'pop : '.$this->benchmark->elapsed_time('pop_start', 'pop_end').PHP_EOL;

       /* 커뮤니티 최신글 시작 */

       //$this->benchmark->mark('new_start');

        $where_community = "";
        $limit_community = "";

        $search_community = array();
        $index_community = "USE INDEX(PRIMARY)";

        array_push($search_community, "mb.showdate <= '".date("Y-m-d")."' AND mb.noticeYn ='N'
        AND ( mb.daum_img IS NULL OR mb.daum_img <> 'H' )
        AND (mb.table_code NOT IN ('1131', '1356','1354','1380') OR (mb.table_code = '1356' AND mb.wiz_id != ''))
        AND (mb.table_code BETWEEN 1100 AND 1199 OR mb.table_code BETWEEN 1300 AND 1399)");

        $where_search = "";
        $where_search .= implode(" AND ", $search_community);

        if($where_search != "")
        {
            $where_community = sprintf(" WHERE %s", $where_search);
        }

        $this->load->model('board_mdl');
        
        if($request['recent_community_limit'] > 0)
        {   
            $limit_community = sprintf("LIMIT %s , %s", $request['recent_community_start'], $request['recent_community_limit']);
        }

        $order_community = sprintf("ORDER BY %s %s", $request['recent_community_order_field'], $request['recent_community_order']);

        $list_board = $this->board_mdl->list_theme($index_community, $where_community, $order_community, $limit_community);
        $result_community = board_list_writer($list_board,NULL,NULL,NULL,array('content_del'=>true));

        if($result_community)
        {
            $return_array['data']['recent_community']['list'] = $result_community;
            $return_array['data']['recent_community']['res_code'] = '0000';
            $return_array['data']['recent_community']['msg'] = "최신글 목록 조회 성공";
        }
        else
        {
            $return_array['data']['recent_community']['err_code'] = "0201";
            $return_array['data']['recent_community']['err_msg'] = "[recent_community] 등록된 데이터가 없습니다.";
        }

       //$this->benchmark->mark('new_end');
        //echo 'new : '.$this->benchmark->elapsed_time('new_start', 'new_end').PHP_EOL;
       /* 커뮤니티 최신글 끝 */


        /* 커뮤니티 인기글 끝 */

        //$this->benchmark->mark('notice_start');

        /* 공지 시작 */
        $where = ' WHERE mb.table_code = 1113';
        $order = sprintf("ORDER BY %s %s", $request['notice_order_field'], $request['notice_order']);
        $limit = sprintf('LIMIT %s , %s', $request['notice_start'], $request['notice_limit']);

        $list_notice = $this->board_mdl->list_board('', $where, $order, $limit);
        $result_notice = board_list_writer($list_notice,NULL,NULL,NULL,array('content_del'=>true));
        
        if($result_notice)
        {
            $return_array['data']['notice']['list'] = $result_notice;
            $return_array['data']['notice']['res_code'] = '0000';
            $return_array['data']['notice']['msg'] = "공지 목록 조회 성공";
        }
        else
        {
            $return_array['data']['notice']['err_code'] = "0201";
            $return_array['data']['notice']['err_msg'] = "[notice] 등록된 데이터가 없습니다.";
        }
        
        //$this->benchmark->mark('notice_end');
        //echo 'notice : '.$this->benchmark->elapsed_time('notice_start', 'notice_end').PHP_EOL;
        /* 공지 끝 */

        /* PC 버전 현재접속자, 금일 누적접속자 추가리턴 */

        if($IS_PC)
        {
            
            //$this->benchmark->mark('conn_start');
            /* 최근접속자 */
            // 최근접속자 매 API 호출할때 마다 set_last_connect 함수에서 갱신
            $connect_cnt = member_get_last_connect_count();

            $return_array['data']['connect_cnt']['currect_cnt'] = $connect_cnt['currect_cnt'];
            $return_array['data']['connect_cnt']['today_cnt'] = $connect_cnt['today_cnt'];
            $return_array['data']['connect_cnt']['res_code'] = '0000';
            $return_array['data']['connect_cnt']['msg'] = "접속자 조회 성공";
            
            //$this->benchmark->mark('conn_end');
            //echo 'conn : '.$this->benchmark->elapsed_time('conn_start', 'conn_end').PHP_EOL;
        }
        
        /* PC 버전 추가리턴 끝 */

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        echo json_encode($return_array);
        exit;

    }

}








