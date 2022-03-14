<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Quest extends _Base_Controller {

    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('Asia/Seoul');
        $this->load->library('form_validation');
    }

    /**
     * 공용 퀘스트 체크 함수 q_idx 필수
     * API소스에 없는 처리를 받기위한 함수이다. EX) 공유하기나 구민트에서 완료되는 퀘스트가 들어온다.
     */
    public function quest()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id"        => trim($this->input->post('wiz_id')),
            "uid"           => trim($this->input->post('uid')),
            "q_idx"         => trim($this->input->post('q_idx')),
            "code"         => trim($this->input->post('code')),
            "old_mint_call" => trim($this->input->post('old_mint_call')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //구민트에서 호출시에는 인증키가 없으므로..
        if($request['old_mint_call'])
        {
            $uid = $request['uid'];
        }
        else
        {
            $wiz_member = base_get_wiz_member();
            $uid = $wiz_member ? $wiz_member['wm_uid']:'';
        }

        if(!$uid)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0400";
            $return_array['data']['err_msg'] = "필수값이 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        MintQuest::request_batch_quest($request['q_idx'], $request['code'], $uid);
        //log_message('error', 'test :'.http_build_query($request));
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "퀘스트 정보 넘기기 성공";
        echo json_encode($return_array);
        exit;
    }
    
    //상위퀘스트 목록
    public function quest_list()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id"        => trim($this->input->post('wiz_id')),
            "type"        => trim($this->input->post('type')),
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

        $this->load->model('quest_mdl');

        //상위퀘스트 가져오기
        $list = $this->quest_mdl->quest_list($request['type'], $wiz_member['wm_uid']);

        if($request['type'] =='1')
        {
            foreach($list as $key=>$row)
            {
                $check = $this->quest_mdl->checked_quest_release($row['mq_q_idx'], $wiz_member['wm_uid']);
                $is_lock = $check ? 1:0;
                /*
                    해금안됐지만 완료는 되어 있으면 해금 처리 해준다.
                    이 경우는 새로운 메인퀘스트 다음장이 생성될때 해당될 수 있다.
                    EX)210510 기준 메인퀘가 3장까지 있으나 4장퀘가 중간에 생긴다면, 4장의 선행퀘가 3장이 될텐데
                    4장이 생기기 전에 3장을 완료하게 되면 4장 해금처리를 할수가 없다.(4장 데이터가 그 시점에서는 없으므로)
                */
                $all_complete = true;
                if($wiz_member && $is_lock)
                {
                    foreach($check as $q)
                    {
                        $check_quest_complete = $this->quest_mdl->row_quest_progress_info($q['mql_precede_q_idx'], $wiz_member['wm_uid']);
    
                        //위와 같은 경우는 메인퀘스트일때만 해당하므로 메인퀘만 체크. 만약 업적도 해금조건에 포함되도록 변경된다면 추가 처리를 해줘야한다.(mq_type=1 조건 풀고 로그네임만 찾으면 된다)
                        if($check_quest_complete['mqp_complete_date'] && $check_quest_complete['mq_type'] =='1')
                        {
                            $this->quest_mdl->set_quest_log_table_name('');
                            // 요청받은 퀘스트 해금체크 및 처리
                            MintQuest::getInstance($wiz_member['wm_uid'])->quest_release($q['mql_precede_q_idx']);
                        }
                        else
                        {
                            $all_complete = false;
                            break;
                        }
                    }

                    //전부 해금처리 되었으면 is_lock = 0
                    if($all_complete)
                    {
                        $is_lock = 0;
                    }
                }

                //퀘해금여부 설정
                $list[$key]['is_lock'] = $is_lock;

                if(!$list[$key]['is_lock'])
                {
                    //서브 퀘스트 완료갯수
                    $complete_cnt = $wiz_member ? $this->quest_mdl->subquest_complete_count($row['mq_q_idx'], $wiz_member['wm_uid']):array('cnt'=>0);
                    $list[$key]['complete_subquest_cnt'] = $complete_cnt['cnt'];
                    //메인퀘스트 진행률
                    $list[$key]['progress_rate'] = $complete_cnt['cnt'] == 0 ? $complete_cnt['cnt']:(int)(($complete_cnt['cnt']/$row['total_subquest_cnt'])*100);
                }
                else
                {
                    $list[$key]['complete_subquest_cnt'] = 0;
                    $list[$key]['progress_rate'] = 0;
                }
                
            }
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['list'] = $list;
        echo json_encode($return_array);
        exit;
    }

    //하위퀘스트 목록
    public function subquest_list()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id"        => trim($this->input->post('wiz_id')),
            "q_idx"        => trim($this->input->post('q_idx')),
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

        $this->load->model('quest_mdl');

        //q_idx에 딸린 하위퀘스트 총 갯수
        $select_col_content = ", (SELECT sum(1) FROM mint_quest as sub_mq WHERE sub_mq.parent_q_idx=mq.q_idx) as total_subquest_cnt";
        //해당 퀘스트에서 트로피를 받았는지 여부
        $select_col_content .= ", (SELECT ut_idx FROM mint_quest_user_tropy as mqut WHERE mqut.q_idx=mq.q_idx AND mqut.uid = ".(int)$wiz_member['wm_uid'].") as mqut_ut_idx";
        //퀘스트 정보 조회
        $quest_info = $this->quest_mdl->row_quest_progress_info($request['q_idx'], $wiz_member['wm_uid'], $select_col_content);

        $quest_info['mq_description'] = nl2br($quest_info['mq_description']);
        $quest_info['mq_description_add'] = nl2br($quest_info['mq_description_add']);
        //서브 퀘스트 완료갯수
        $complete_cnt = $wiz_member ? $this->quest_mdl->subquest_complete_count($request['q_idx'], $wiz_member['wm_uid']):array('cnt'=>0);
        $quest_info['complete_subquest_cnt'] = (int)$complete_cnt['cnt'];
        //메인퀘스트 진행률
        $quest_info['progress_rate'] = $quest_info['complete_subquest_cnt'] == 0 ? $quest_info['complete_subquest_cnt']:(int)(($quest_info['complete_subquest_cnt']/$quest_info['total_subquest_cnt'])*100);

        //하위퀘스트 가져오기
        $subquest_list = $this->quest_mdl->subquest_list($request['q_idx'], $wiz_member['wm_uid']);

        $progress_quest = null;
        $reward_ready = '';
        if($subquest_list)
        {
            $chk_quest_view_stop = false;
            foreach($subquest_list as $key=>$row)
            {
                $subquest_list[$key]['mq_description'] = nl2br($subquest_list[$key]['mq_description']);
                $subquest_list[$key]['mq_title_front'] = nl2br($subquest_list[$key]['mq_title_front']);
                //진행중 퀘스트 이후 퀘스트 보여주지않음(5개이상일때)
                if($chk_quest_view_stop)
                {
                    unset($subquest_list[$key]);
                    continue;
                }

                $reward = explode('|',$row['mq_reward']);
                //보상
                $subquest_list[$key]['reward'] = [
                    'point' =>$reward[0],
                    'coupon' =>$reward[1],
                ];

                //진행중인 업적퀘스트
                if($progress_quest == null && !$row['mqp_complete_date'] && $quest_info['mq_type'] =='2')
                {
                    $progress_quest = $subquest_list[$key];
                    //몇번째 누적퀘 진행중인지
                    $progress_quest['ongoing'] = $key;
                }

                //퀘스트 바로가기 주소 처리
                $subquest_list[$key]['mq_location'] = set_new_or_old_url($row['mq_location']);
                $subquest_list[$key]['mq_location_m'] = $row['mq_location_m'] ? set_new_or_old_url($row['mq_location_m'],true):$subquest_list[$key]['mq_location'];

                //완료되었으나 보상받지 않은것이 있다면 보상 받아야되는 번호 리턴
                if(!$reward_ready && $row['mqp_complete_date'] && !$row['mqp_reward_date'])
                {
                    $reward_ready = $row['mq_q_idx'];
                }

                //업적 진행중 퀘스트 이후 퀘스트 보여주지않음(5개이상일때)
                if(count($subquest_list) > 5 && $key > 3 && $progress_quest != null)
                {
                    $chk_quest_view_stop = true;
                }
            }

            //전부 완료해서 진행중인거 없으면 마지막 퀘정보로
            if($progress_quest == null && $quest_info['mq_type'] =='2')
            {
                $progress_quest = $subquest_list[count($subquest_list)-1];
                $progress_quest['ongoing'] = count($subquest_list)-1;
            }
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['subquest_list'] = $subquest_list;
        $return_array['data']['quest_info'] = $quest_info;
        $return_array['data']['progress_quest'] = $progress_quest;
        $return_array['data']['reward_ready'] = $reward_ready;
        echo json_encode($return_array);
        exit;
    }

    //보상수령
    public function get_reward()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id"        => trim($this->input->post('wiz_id')),
            "q_idx"        => trim($this->input->post('q_idx')),
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

        $this->load->model('quest_mdl');
        $quest_info = $this->quest_mdl->row_quest_progress_info($request['q_idx'], $wiz_member['wm_uid']);

        //보상받기
        $result = MintQuest::getInstance($wiz_member['wm_uid'])->reward($request['q_idx']);

        if($result['state'] === false)
        {
            if($result['res_code'])
            {
                $return_array['res_code'] = $result['res_code'];
                $return_array['msg'] = $result['msg'];
            }
            else
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = $result['err_code'];
                $return_array['data']['err_msg'] = $result['msg'];
            }
            echo json_encode($return_array);
            exit;
        }

        $parent_quest_info = $this->quest_mdl->row_quest_progress_info($quest_info['mq_parent_q_idx'], $wiz_member['wm_uid']);

        $tmp_point = $this->member_mdl->get_wm_point_by_wiz_id($wiz_member['wm_wiz_id']);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['quest_info'] = $quest_info;
        $return_array['data']['parent_quest_info'] = $parent_quest_info;
        $return_array['data']['reward_point'] = $result['point'];
        $return_array['data']['reward_trophy'] = $result['trophy'];
        $return_array['data']['wm_point'] = $tmp_point['wm_point'];
        echo json_encode($return_array);
        exit;
    }

    /**
     * 회원의 완료된 퀘스트 목록을 불러온다
     */
    public function complete_quest_()
    {
        $return_array = array();    

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id"        => trim($this->input->post('wiz_id')),
            "limit"         => $this->input->post('limit') ? $this->input->post('limit') : 5,
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

        $this->load->model('quest_mdl');
        
        $result = $this->quest_mdl->get_complete_quest_list($wiz_member['wm_uid'],$request['limit']);
        if(!$result)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
    
        $first = null;
        foreach($result as $key=>$val)
        {
            // 완료 퀘스트 목록중 첫번째 퀘스트(회원가입)이 있을 경우
            if($val['mq_q_idx'] == 4)
            {
                // 포인트|수업변환횟수
                $reward = explode('|',$val['mq_reward']);
                $first = $result[$key];
                $first['reward_point'] = $reward[0] ? $reward[0] : 0;
                unset($result[$key]);
                $result = array_values($result);
            }
        }

        $return_array['res_code']      = '0000';
        $return_array['msg']           = "퀘스트 완료 목록을 불러왔습니다.";
        $return_array['data']['list']  = $result;
        $return_array['data']['first'] = $first;
        echo json_encode($return_array);
        exit;
    }

}








