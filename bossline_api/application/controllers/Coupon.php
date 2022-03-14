<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Coupon extends _Base_Controller {

    public $wemake = array('request'=>array(
                               'input'=>array('url'=>'http://www.wemakeprice.com/company/','type'=>array('json'=>'request/','xml'=>'request_xml/')),
                               'list'=>array('info'=>'req_info_new','process'=>'req_process_new','cancel'=>'req_cancel_new')
                           ),
                           'user'=>array('cid'=>'mint05com'));


    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }


    /*
     * 구민트에서 호출 할것이므로 authorization를 이용한 로직은 사용 못한다.
     * 따라서 form_validation과 base_get_wiz_member는 생략한다.
    **/
    public function config()
    {
        $return_array = array();    

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            'cp_id' => $this->input->post('cp_id'),
            'old_mint_call' => $this->input->post('old_mint_call'),
        );

        if($request['old_mint_call'])
        {
            $this->load->model('member_mdl');
            $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);

            if(!$wiz_member)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0109";
                $return_array['data']['err_msg'] = "해당하는 아이디가 없습니다.";
                echo json_encode($return_array);
                exit;
            }
            
        }
        else
        {
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
        }
        

        $this->load->model('coupon_mdl');
        $coupon_config = $this->coupon_mdl->row_coupon_config_by_cp_id($request['cp_id']);

        //쿠폰 사용여부 체크
        if(!$coupon_config)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0622";
            $return_array['data']['err_msg'] = "잘못된 쿠폰입니다.";
            echo json_encode($return_array);
            exit;
        }

        if($coupon_config['wc_is_delete'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0623";
            $return_array['data']['err_msg'] = "삭제된 쿠폰입니다.";
            echo json_encode($return_array);
            exit;
        }
        

        /* 
           개인 지정 쿠폰 체크
            - wmc_is_entire : 1:전체용,0:개인용
        */
        if($coupon_config['wmc_gubun'] == "2")
        {
            if($coupon_config['wmc_is_entire'] == "0" && $coupon_config['wc_uid'] != $wiz_member['wm_uid'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0624";
                $return_array['data']['err_msg'] = "사용할 수 있는 쿠폰이 아닙니다.";
                echo json_encode($return_array);
                exit;
            }

            // 전체쿠폰이면 딜러사용제한 걸려있는지 확인. 걸려있으면 회원 d_id와 같은지 체크해야한다
            if($coupon_config['wmc_is_entire'] == "1" && $coupon_config['wmc_d_id'] && $coupon_config['wmc_d_id'] != $wiz_member['wm_d_did'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0624";
                $return_array['data']['err_msg'] = "사용할 수 있는 쿠폰이 아닙니다.(2)";
                echo json_encode($return_array);
                exit;
            }

            //쿠폰 사용여부 체크. 로그가 존재하면 사용한것.
            if($coupon_config['wclrl_idx'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0621";
                $return_array['data']['err_msg'] = "이미 사용한 쿠폰입니다.";
                echo json_encode($return_array);
                exit;
            }

        }

        //현재시간
        $now = strtotime(date("Y-m-d")); 

        /*
            쿠폰 사용 유효기간 체크
            - wmc_validate_s : 유효기간 시작일 (Y-m-d)
            - wmc_validate : 유효기간 종료일 (Y-m-d)
        */
        if(strtotime($coupon_config['wmc_validate_s']) > $now || strtotime($coupon_config['wmc_validate']) < $now)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0622";
            $return_array['data']['err_msg'] = "사용 유효기간이 지난 쿠폰입니다.";
            echo json_encode($return_array);
            exit;
        }

        $result_lesson = null;

        // 수업횟수제한 해제쿠폰일때 출석부 리스트 리턴
        if($coupon_config['wmc_gubun'] == "2")
        {
            /*
                수업추가 출석부 정보 확인
                - 단일 출석부를 가져오지만 array로 리턴
                : 출석부 포인트 정책 point_policy_wiz_lesson() 헬퍼 공통 사용이 목적
            */
            $this->load->model('lesson_mdl');
            $list_lesson = $this->lesson_mdl->list_unfinished_wiz_lesson_by_wm_uid($wiz_member['wm_uid']);

            /* 출석부 정보가 없을 경우 */
            if(!$list_lesson)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0611";
                $return_array['data']['err_msg'] = "수업중인 출석부가 없습니다.";
                echo json_encode($return_array);
                exit;
            }

            /*
                회원 현재 수강 종료되지 않은 출석부 
                - 수강 종료되지 않은 출석부
                : 영어첨삭 제외 
                - 포인트 수업 연장 가능 여부 
                : 쿠폰 설정
                : 딜러 설정
                : 장기연기중인 경우 변환 불가
                : 환불이 완료된 경우 변환 불가
                : 현재 수업중인 경우만 수업 추가 가능
                : 1-3개월 출석부 예외처리
                : 블랙리스트 회원
            */
            $result_lesson = point_policy_wiz_lesson($list_lesson, $wiz_member);
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "쿠폰 설정 정보 조회";
        $return_array['data']['config'] = $coupon_config;
        $return_array['data']['lesson'] = $result_lesson;
        echo json_encode($return_array);
        exit;
        
    }

    
    /*
     * 구민트에서 호출 할것이므로 authorization를 이용한 로직은 사용 못한다.
     * 따라서 form_validation과 base_get_wiz_member는 생략한다.
    **/
    public function increase_limit()
    {
        $return_array = array();    

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            'cp_id' => $this->input->post('cp_id'),     
            'lesson_id' => $this->input->post('lesson_id'),      
            'old_mint_call' => $this->input->post('old_mint_call'),
        );

        if($request['old_mint_call'])
        {
            $this->load->model('member_mdl');
            $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);

            if(!$wiz_member)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0109";
                $return_array['data']['err_msg'] = "해당하는 아이디가 없습니다.";
                echo json_encode($return_array);
                exit;
            }
            
        }
        else
        {
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
        }
        

        /*
            수업추가 출석부 정보 확인
            - 단일 출석부를 가져오지만 array로 리턴
              : 출석부 포인트 정책 point_policy_wiz_lesson() 헬퍼 공통 사용이 목적
        */
        $this->load->model('lesson_mdl');
        $list_lesson = $this->lesson_mdl->list_unfinished_wiz_lesson_by_lesson_id($request['lesson_id'], $wiz_member['wm_uid']);

        /* 출석부 정보가 없을 경우 */
        if(!$list_lesson)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0611";
            $return_array['data']['err_msg'] = "수업중인 출석부가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        /*
            회원 현재 수강 종료되지 않은 출석부 
            - 수강 종료되지 않은 출석부
              : 영어첨삭 제외 
            - 포인트 수업 연장 가능 여부 
              : 쿠폰 설정
              : 딜러 설정
              : 장기연기중인 경우 변환 불가
              : 환불이 완료된 경우 변환 불가
              : 현재 수업중인 경우만 수업 추가 가능
              : 1-3개월 출석부 예외처리
              : 블랙리스트 회원
        */
        $tmp_lesson = point_policy_wiz_lesson($list_lesson, $wiz_member);
        $result_lesson = $tmp_lesson[0];

        /*
            해당 출석부 포인트로 수업추가 가능여부 확인
            - point_addclass_yn : 출석부 포인트로 수업추가 가능여부 (Y: 가능, N: 불가)
            - point_addclass_yn_desc : 출석부 포인트로 수업추가 불가 이유 
        */
        if($result_lesson['point_addclass_yn'] == "N")
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0610";
            $return_array['data']['err_msg'] = $result_lesson['point_addclass_yn_desc'];
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('coupon_mdl');
        $coupon_config = $this->coupon_mdl->row_coupon_config_by_cp_id($request['cp_id']);

        //쿠폰 사용여부 체크
        if(!$coupon_config)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0622";
            $return_array['data']['err_msg'] = "잘못된 쿠폰입니다.";
            echo json_encode($return_array);
            exit;
        }

        if($coupon_config['wc_is_delete'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0623";
            $return_array['data']['err_msg'] = "삭제된 쿠폰입니다.";
            echo json_encode($return_array);
            exit;
        }

        // 전체쿠폰이면 딜러사용제한 걸려있는지 확인. 걸려있으면 회원 d_id와 같은지 체크해야한다
        if($coupon_config['wmc_is_entire'] == "1" && $coupon_config['wmc_d_id'] && $coupon_config['wmc_d_id'] != $wiz_member['wm_d_did'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0624";
            $return_array['data']['err_msg'] = "사용할 수 있는 쿠폰이 아닙니다.(2)";
            echo json_encode($return_array);
            exit;
        }
        
        //쿠폰 사용여부 체크
        if($coupon_config['wclrl_idx'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0621";
            $return_array['data']['err_msg'] = "이미 사용한 쿠폰입니다.";
            echo json_encode($return_array);
            exit;
        }

        //현재시간
        $now = strtotime(date("Y-m-d")); 

        /*
            쿠폰 사용 유효기간 체크
            - wmc_validate_s : 유효기간 시작일 (Y-m-d)
            - wmc_validate : 유효기간 종료일 (Y-m-d)
        */
        if(strtotime($coupon_config['wmc_validate_s']) > $now || strtotime($coupon_config['wmc_validate']) < $now)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0622";
            $return_array['data']['err_msg'] = "사용 유효기간이 지난 쿠폰입니다.";
            echo json_encode($return_array);
            exit;
        }


        /* 
            쿠폰 사용 가능한 출석부 종류 체크
            - wmc_gubun : 1:출석부등록, 2: 포인트->수업변환횟수제한 해제 
        */
        if($coupon_config['wmc_gubun'] != "2")
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0623";
            $return_array['data']['err_msg'] = "수업 변환 횟수 제한에 사용할 수 있는 쿠폰 종류가 아닙니다.";
            echo json_encode($return_array);
            exit;
        }


        /* 
           개인 지정 쿠폰 체크
            - wmc_is_entire : 1:전체용,0:개인용
        */
        if($coupon_config['wmc_is_entire'] == "0")
        {
            if($coupon_config['wc_uid'] != $wiz_member['wm_uid'])
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0624";
                $return_array['data']['err_msg'] = "사용할 수 있는 쿠폰이 아닙니다.";
                echo json_encode($return_array);
                exit;
            }
        }

        /*
            기존 제한 횟수 : $result_lesson['release_cnt']
            추가 제한 횟수 : $coupon_config['wmc_release_cnt']
        */
        $after_release_cnt = (int)$result_lesson['release_cnt']+(int)$coupon_config['wmc_release_cnt'];
        
        $coupon_log = array(
            'uid' => $wiz_member['wm_uid'],
            'lesson_id' => $result_lesson['lesson_id'],
            'type' => '1',   
            'code' => $coupon_config['wc_cp_id'],   
            'content' => '수업변환횟수 '.$coupon_config['wmc_release_cnt'].'회',
            'regdate' => date("Y-m-d H:i:s"),
        );

        if($coupon_config['wmc_point'])
        {
            $point = array(
                'uid' => $wiz_member['wm_uid'],
                'name' => $wiz_member['wm_name'],
                'point' => $coupon_config['wmc_point'],
                'pt_name'=> '쿠폰사용 포인트 지급 '.$coupon_config['wc_cp_id'], 
                'kind'=> 'cp', 
                'b_kind'=> 'coupon',
                'showYn'=> 'y',
                'regdate' => date("Y-m-d H:i:s")
            );
    
            /* 포인트 내역 입력 및 포인트 추가 */
            $this->load->model('point_mdl');
            $rpoint = $this->point_mdl->set_wiz_point($point);

            if($rpoint < 0)
            {
                $return_array['res_code'] = '0500';
                $return_array['msg'] = "DB ERROR!";
                echo json_encode($return_array);
                exit;
            }

            $coupon_log['content'].= ', 추가포인트 '.number_format($coupon_config['wmc_point']);
        }

        //포인트->수업변환횟수제한 해제 쿠폰 사용
        $result = $this->coupon_mdl->coupon_increase_limit($coupon_log, $after_release_cnt);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = $coupon_log['content'];
        echo json_encode($return_array);
        exit;
    }


    /**
     * 출석부 쿠폰 등록
     * - 쿠폰 유효성 체크하는것이 많다
    **/
    public function register_class_coupon()
    {
        $return_array = array();

        $request = array(
            "wiz_id"        => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            'cp_id'         => $this->input->post('cp_id'),
            'old_mint_call' => $this->input->post('old_mint_call'),
        );

        if($request['old_mint_call'])
        {
            //테스트용
            //어차피 신민트로 이전할 예정이기에 구민트에서 요청할일이 없다
            //나중에 삭제
            $this->load->model('member_mdl');
            $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);

            if(!$wiz_member)
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0109";
                $return_array['data']['err_msg']  = "해당하는 아이디가 없습니다.";
                echo json_encode($return_array);
                exit;
            }
        }
        else
        {
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
        }

        $this->load->model('coupon_mdl');

        /** ======================== 쿠폰 유효성 검사 시작 ======================== */

        // 쿠폰 기본정보, 유효한 쿠폰인지 검사, 사용된, 유효기간 지난 쿠폰인지도 여기서 검사한다.
        $now = date('Y-m-d');
        $cp_data = $this->coupon_mdl->chk_valid_coupon($request['cp_id'], $now);
        if(!$cp_data)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0622";
            $return_array['data']['err_msg']  = "사용되었거나 유효기간이 지나 사용불가능한 쿠폰입니다.";
            echo json_encode($return_array);
            exit;
        }

        $err_msg = "";

        // 쿠폰 사용 횟수 검사
        // 그룹 쿠폰 일 경우
        if($cp_data['wmc_coupon_group'] != '')
        {
            // 그룹 사용횟수
            $group_coupon_info = $this->coupon_mdl->group_coupon_info($cp_data['wmc_coupon_group']);
            if($group_coupon_info)
            {
                // 그룹당 회원 사용 횟수
                $group_use_cnt = $this->coupon_mdl->group_coupon_use_count($cp_data['wmc_coupon_group'], $wiz_member['wm_uid']);
                if($group_use_cnt['cnt'] >= $group_coupon_info['wcg_group_use_cnt'] && $group_coupon_info['wcg_group_use_cnt'] != 0)
                {
                    $err_msg = "해당 그쿠폰 그룹상품의 이용 가능 횟수를 초과하였습니다.";
                }
            }
            else
            {
                $err_msg = "쿠폰그룹 정보를 찾을수 없습니다.";
            }
        }
        // 일반 쿠폰 일 경우
        $use_count = $this->coupon_mdl->coupon_use_count($cp_data['wmc_coupon_id'], $wiz_member['wm_uid']);
        if($cp_data['wmc_coupon_use_cnt'] <= $use_count['cnt'] && $cp_data['wmc_coupon_use_cnt'] != '0')
        {
            $err_msg = "쿠폰 사용가능 횟수를 초과 하였습니다";
        }

        //에러 메세지가 있을 경우 유효성을 통과하지 못한것이므로
        if($err_msg)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0625";
            $return_array['data']['err_msg']  = $err_msg;
            echo json_encode($return_array);
            exit;
        }

        // 쿠폰 사용 유효성 검사
        if($cp_data['wmc_e_kind'] != 'N-N-N-N-N' && $cp_data['wmc_e_kind'] != '')
        {
            //신규 체크 (수업을 한번도 들은적이 없는)
            $new_lesson = $this->coupon_mdl->chk_lesson($wiz_member['wm_uid'], "ORDER BY endday DESC LIMIT 1");
            //재강 체크 (수업중인 회원)
            $ing_lesson = $this->coupon_mdl->chk_lesson($wiz_member['wm_uid'], "AND startday <= '".date("Y-m-d")."' AND endday >= '".date("Y-m-d")."'");

            /**
             * 현재 쿠폰 사용타입 (e_kind)
             * 0 : 신규 , 1 : 재강 , 2 : 종료 , 3 : 지점회원 , 4 : 이벤트신규?(확인필요)
             */
            $e_kind = explode("-",$cp_data['wmc_e_kind']);

            if($e_kind[0] == 'Y' && $e_kind[1] == 'N' && $e_kind[2] == 'N')
            {
                if($new_lesson)                                              $err_msg = "수업중인 회원이거나 한번 본페이지를 이용하신분은 결제하실 수 없습니다.";
            }
            else if($e_kind[0] == 'Y' && $e_kind[1] == 'Y' && $e_kind[2] == 'N')
            {
                if($new_lesson || $ing_lesson)                               $err_msg = "신규회원 또는 수업중인 회원만 결제하실 수 있습니다.";
            }
            else if($e_kind[0] == 'Y' && $e_kind[1] == 'N' && $e_kind[2] == 'Y')
            {
                if($new_lesson && $new_lesson['wl_endday'] >= date('Y-m-d')) $err_msg = "신규회원 또는 수업종료된 회원만 결제하실 수 있습니다.";
            }
            else if($e_kind[0] == 'N' && $e_kind[1] == 'Y' && $e_kind[2] == 'N')
            {
                if($ing_lesson)                                              $err_msg = "수업중인 회원만 결제하실 수 있습니다.";
            }
            else if($e_kind[0] == 'N' && $e_kind[1] == 'Y' && $e_kind[2] == 'Y')
            {
                if(!$new_lesson)                                             $err_msg = "수업을 한번이라도 받으셨던 회원만 결제하실 수 있습니다.";
            }
            else if($e_kind[0] == 'N' && $e_kind[1] == 'N' && $e_kind[2] == 'Y')
            {
                if($new_lesson && $new_lesson['wl_endday'] >= date('Y-m-d')) $err_msg = "수업종료된 회원만 결제하실 수 있습니다.";
            }

            if($e_kind[4] == 'Y')
            {
                //이벤트 신규 인데 e_id를 들고오는곳이 없다 사용하지않는것으로 확인된다
            }

            //딜러가 설정되어있고 지점신규회원일 경우
            if($cp_data['dealer_id'] && $cp_data['dealer_id'] != $wiz_member['wm_d_did'] && $e_kind[3] == 'Y')
            {
                $this->load->model('member_mdl');
                $dealer = $this->member_mdl->get_wiz_dealer($cp_data['dealer_id']);
                $err_msg = $dealer['d_name'].'회원만 이용하실수 있습니다.';
            }

            //에러 메세지가 있을 경우 유효성을 통과하지 못한것이므로
            if($err_msg)
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0625";
                $return_array['data']['err_msg']  = $err_msg;
                echo json_encode($return_array);
                exit;
            }
        }

        // 쿠폰 수업 정보
        $cp_class = $this->coupon_mdl->get_coupon_class($cp_data['wmc_coupon_id']);
        if(!$cp_class)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0625";
            $return_array['data']['err_msg']  = "일치하는 쿠폰수업 정보를 찾을수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        // 쿠폰을 등록하고 수업을 미등록한 경우가 있는지 체크
        $lesson_unregistered = $this->coupon_mdl->chk_lesson_unregistered($now, $wiz_member['wm_uid']);
        if($lesson_unregistered['cnt'])
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0625";
            $return_array['data']['err_msg']  = "이전 등록하신 쿠폰에 수업을 등록하지 않은 쿠폰이 있습니다. 수업을 모두 등록후 쿠폰등록이 가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        // 쿠폰 타입에 따라 유효성 검증
        if($cp_data['wmc_coupon_type'] == 'default')
        {
            // 쿠폰 타입 디폴트일 경우 > 통과
        }
        else if($cp_data['wmc_coupon_type'] == 'wemake')
        {
            // 위메프 유효성 검증 쿠폰정보 요청
            $params = array('cp_number'=>$request['cp_id'],'req'=>'info','type'=>'json');
            $row_data = wemake_request($params, $cp_data, $this->wemake);

            // 쿠폰정보 조회시 쿠폰상태가 0발급이 아니면 에러처리
            if($row_data->coupon_status != '0')
            {
                $err_msg = "";
                if($row_data->coupon_status == '1')
                    $err_msg = "이미 사용이 완료된 쿠폰 입니다.";
                else if($row_data->coupon_status == '2')
                    $err_msg = "일부사용 된 쿠폰입니다.";
                else if($row_data->coupon_status == '3')
                    $err_msg = "환불요청된 쿠폰 입니다.";
                else if($row_data->coupon_status == '4')
                    $err_msg = "환불된 쿠폰 입니다.";
                else
                    $err_msg = iconv('utf-8','euc-kr',$row_data->message);
                
                // 쿠폰상태가 사용될수 없는경우 로그
                wemake_log_add($row_data,$cp_data,$wiz_member,'',$err_msg);
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0625";
                $return_array['data']['err_msg']  = $err_msg;
                echo json_encode($return_array);
                exit;
            }
            else
            {
                // 0이면 발급된상태 사용가능 쿠폰 요청처리
                // 쿠폰정보를 log에 insert
                wemake_log_add($row_data,$cp_data,$wiz_member);
                $params_proc = array('cp_number'=>$request['cp_id'],'req'=>'process','type'=>'json','cnt'=>'1');
                $row_data_proc = wemake_request($params_proc, $cp_data, $this->wemake);
                if($row_data_proc->result=='1')
                {
                    // 사용요청 성공 업데이트
                    wemake_log_add($row_data_proc,$cp_data,$wiz_member,'process');
                }
                else
                {
                    $err_msg = iconv('utf-8','euc-kr',$row_data_proc->message);
                    // 사용요청 실패 업데이트
                    wemake_log_add($row_data_proc,$cp_data,$wiz_member,'process');
                    $return_array['res_code']         = '0900';
                    $return_array['msg']              = "프로세스오류";
                    $return_array['data']['err_code'] = "0625";
                    $return_array['data']['err_msg']  = $err_msg;
                    echo json_encode($return_array);
                    exit;
                }
            }
        }
        else
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0625";
            $return_array['data']['err_msg']  = "쿠폰타입을 찾을수 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        /** ======================== 쿠폰 유효성 검사 끝 ======================== */

        // 쿠폰 수업 등록
        // 임시 결제 정보 저장 테이블
        $prepay = array(
            'lesson_gubun' => $cp_class['wcd_lesson_gubun'],
            'pay_method'   => 'coupon:',
            'uid'          => $wiz_member['wm_uid'],
            'goods_type'   => '1',
            'goods_id'     => $cp_class['wc_cl_id'],
            'cl_name'      => $cp_class['wcd_cl_name'],
            'cl_gubun'     => '1',
            'cl_time'      => $cp_class['wc_cl_time'],
            'cl_number'    => $cp_class['wc_cl_number'],
            'cl_class'     => $cp_class['wc_cl_class'],
            'cl_month'     => $cp_class['wc_cl_month'],
            'hold_num'     => $cp_class['wc_hold_num'],
            'total_price'  => 0,
            'hopedate'     => '',
            'hopetime'     => '',
            'lesson_memo'  => '',
            'relec_id'     => 0,
            'student_su'   => $cp_class['wc_student_su'] ? $cp_class['wc_student_su'] : 2,
            'student_uid'  => $cp_class['wc_student_uid'] ? $cp_class['wc_student_uid'] : '',
            'e_id'         => $cp_data['wmc_coupon_id'],
            'order_no'     => '',
            'org_price'    => 0,
            'sms_dc_price' => 0,
            'dc_price'     => 0,
            'goods_id'     => 0,
            'goods_type'   => 0,
            'tel'          => $wiz_member['wm_tel'],
            'mobile'       => $wiz_member['wm_mobile'],
        );
        $wiz_pay_param = array(
            'va_date'    => "0000-00-00",
            'coupon_num' => $request['cp_id'],
        );
        $lesson_pay_id = payment_insert_lesson_pay($prepay, $wiz_member, $wiz_pay_param);
        if($lesson_pay_id['state'] ==0)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = $lesson_pay_id['msg'];
            echo json_encode($return_array);
            exit;
        }

        // 쿠폰수업 등록안내 문자 발송 - 고유번호 186
        $options = array(
            'name'        => $wiz_member['wm_name'], 
            'wiz_id'      => $wiz_member['wm_wiz_id'], 
            'uid'         => $wiz_member['wm_uid'], 
            'coupon_type' => $cp_data['wmc_coupon_type'], 
            'man_name'    => 'SYSTEM'
        );
        sms::send_sms($wiz_member['wm_mobile'], 186, $options);

        // 쿠폰 유효기간 확인 : 유효기간 만료일에 쿠폰을 등록한 경우 만료일 안내 문자 발송 - 고유번호 185
        if(date("Y-m-d") == $cp_data['wmc_validate'])
        {
            sms::send_sms($wiz_member['wm_mobile'], 185, $options);

            // 알림 등록
            $notify_result = array(
                'uid'        => $wiz_member['wm_uid'],
                'code'       => '304',
                'message'    => '회원님의 수강상품들 중에 오늘까지만 수업등록이 가능한 쿠폰 수강상품이 있습니다.',
                'board_name' => '쿠폰의 만료일 임박 알림',
                'user_name'  => 'SYSTEM',
                'go_url'     => 'http://www.mint05.com/pubhtml/mypage/coupon.php',
                'regdate'    => date('Y-m-d H:i:s'),
            );
            $this->load->model('notify_mdl');
            $this->notify_mdl->insert_notify($notify_result);
        }

        // 포인트 지급
        if($cp_data['wmc_point'] > 0)
        {
            $point = array(
                'uid'     => $wiz_member['wm_uid'],
                'name'    => $wiz_member['wm_name'],
                'point'   => $cp_data['wmc_point'],
                'pt_name' => '쿠폰등록 지급 포인트.', 
                'kind'    => 'q', 
                'showYn'  => 'y',
                'regdate' => date("Y-m-d H:i:s")
            );

            /* 포인트 내역 입력 및 포인트 추가 */
            $this->load->model('point_mdl');
            $this->point_mdl->set_wiz_point($point);
        }

        // 쿠폰 사용 업데이트
        $params = array(
            'uid'      => $wiz_member['wm_uid'],
            'use_ok'   => 'Y',
            'senddate' => date("Y-m-d H:i:s")
        );
        $this->coupon_mdl->update_wiz_coupon($params,$request['cp_id']);

        $return_array['res_code'] = '0000';
        $return_array['msg']      = "쿠폰 수강신청이 정상적으로 접수 되었습니다";
        echo json_encode($return_array);
        exit;
    }

}