<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Point extends _Base_Controller {


    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }


    /* 
        회원 현재 포인트 현황
            - 총 누적 포인트
            - 총 수업 변환 포인트
            - 총 사용 포인트
            - 현재 보유 포인트
            - 수업 변환 가능 포인트 (예정)
            - 수업 변환 가능 총 한도 (예정)
        회원 현재 수강 종료되지 않은 출석부 목록  
            - 수강 종료되지 않은 출석부
            : 영어첨삭 제외 
            - 포인트 수업 연장 가능 여부 
            : 쿠폰 설정
            : 딜러 설정
            : 장기연기중인 경우 변환 불가
            : 환불이 완료된 경우 변환 불가
            : 현재 수업중인 경우만 포인트로 수업추가 가능
            : 1-3개월 출석부 예외처리
        회원 포인트 내역
    */
    public function current_situation()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            //포인트내역
            "point_list_start" => trim($this->input->post('point_list_start')) ? trim($this->input->post('point_list_start')):0,
            "point_list_limit" => trim($this->input->post('point_list_limit')) ? trim($this->input->post('point_list_limit')):10,
            "point_list_order_field" => ($this->input->post('point_list_order_field')) ? trim(strtolower($this->input->post('point_list_order_field'))) : "wp.pt_id",
            "point_list_order" => ($this->input->post('point_list_order')) ? trim(strtoupper($this->input->post('point_list_order'))) : "DESC",
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

        /* 
            회원 현재 포인트 현황
            - 총 누적 포인트
            - 총 수업 변환 포인트
            - 총 사용 포인트
            - 현재 보유 포인트
            - 수업 변환 가능 포인트 (예정)
            - 수업 변환 가능 총 한도 (예정)
        */
        $this->load->model('point_mdl');
        $result_point = $this->point_mdl->point_current_situation_by_wm_uid($wiz_member['wm_uid']);

        /*
            회원 현재 수강 종료되지 않은 출석부 목록  
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
        $this->load->model('lesson_mdl');
        $result_lesson = point_policy_wiz_lesson($this->lesson_mdl->list_unfinished_wiz_lesson_by_wm_uid($wiz_member['wm_uid']), $wiz_member);


        /* 회원 포인트 내역 */
        $order = sprintf("ORDER BY %s %s", $request['point_list_order_field'], $request['point_list_order']);
        $limit = sprintf('LIMIT %s , %s', $request['point_list_start'], $request['point_list_limit']);

        $result_point_list = $this->point_mdl->list_point_by_wm_uid($wiz_member['wm_uid'], $order, $limit);
        
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "회원 현재 포인트 현황 조회";
        $return_array['data']['point'] = $result_point;
        $return_array['data']['point_list'] = $result_point_list;
        $return_array['data']['lesson'] = $result_lesson;
        echo json_encode($return_array);
        exit;
        
    }
    
    /*
        출석부 포인트로 수업추가
    */
    public function class_conversion()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "lesson_id" => trim($this->input->post('lesson_id')),
            "number_of_classes_to_convert" => trim($this->input->post('number_of_classes_to_convert')),
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


        /* 
            회원 현재 포인트 현황
            - 총 누적 포인트
            - 총 수업 변환 포인트
            - 총 사용 포인트
            - 현재 보유 포인트
            - 수업 변환 가능 포인트 (예정)
            - 수업 변환 가능 총 한도 (예정)
        */
        $this->load->model('point_mdl');
        $result_point = $this->point_mdl->point_current_situation_by_wm_uid($wiz_member['wm_uid']);

        //수업 변환시 필요한 포인트
        $required_point = ($result_lesson['cl_time'] * 500) * $request['number_of_classes_to_convert'];

        if($result_point['current_point'] < $required_point)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0612";
            $return_array['data']['err_msg'] = "보유 포인트가 부족합니다.";
            echo json_encode($return_array);
            exit;

        }


        /*  
            출석부타입(cl_gubun)
            - 1: 고정출석부 , 2:자유수업출석부
        */
        if($result_lesson['cl_gubun'] == "1")
        {
            schedule_tutor_check_regular_class_extension($result_lesson, $request['number_of_classes_to_convert']);

        }
        else if($result_lesson['cl_gubun'] == "2")
        {

        }

    }


    /*
        민트영어 소개페이지 - 현재까지 지급된 포인트 총합
    */
    public function total()
    {
        $return_array = array();   

        $this->load->model('point_mdl');
        $result = $this->point_mdl->row_total_point();

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "포인트 지급 현황 조회";
        $return_array['data']['point'] = $result['total_point'];
      
        echo json_encode($return_array);
        exit;
    }


}








