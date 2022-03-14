<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Member extends _Base_Controller {
    public $upload_path_member = ISTESTMODE ? 'test_upload/attach/member/':'attach/member/';
    public $upload_path_badge = ISTESTMODE ? 'attach/badge/':'attach/badge/';

    public function __construct()
    {
        parent::__construct();
        //한국 시간 설정
        date_default_timezone_set('Asia/Seoul');
        $this->load->library('form_validation');
    }

    //로그인
    public function login()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "wiz_pw" => trim($this->input->post('wiz_pw')),
            "device" => trim($this->input->post('device')),
            "device_token" => trim($this->input->post('device_token')),

            // 관리자에서 자동로그인 시 추가로 넘겨받는 파라미터. 아래 파라미터들이 있으면 관리자 자동로그인으로 간주
            "aid" => trim($this->input->post('aid')),       // 관리자 아이디
            "ui" => trim($this->input->post('ui')),         // 회원 uid
            "c" => trim($this->input->post('c')),           // 자동로그인 인증코드. 1회용
        );

        $this->load->model('member_mdl');

        $admin_login = false;
        if($request['aid'] && $request['ui'] && $request['c']){
            // 넘겨받은 어드민 로그인 파라미터들이 유효한지 체크
            $al_check = $this->member_mdl->admin_log_check($request['ui'], $request['c']);
            if(!$al_check)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0114";
                $return_array['data']['err_msg'] = "비정상적인 접근입니다.";
                echo json_encode($return_array);
                exit;
            }
            else
            {
                $request['wiz_id'] = $al_check['wiz_id'];
                $request['wiz_pw'] = '123456';  //폼 검증 회피용 임의값
                $admin_login = true;
            }
        }

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        
        // 아이디 유무 체크
        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);
        if(!$wiz_member)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0104";
            $return_array['data']['err_msg'] = "아이디 또는 비밀번호를 확인하세요.";
            echo json_encode($return_array);
            exit;
        }
        
        $icon = member_get_icon($wiz_member);

        /*
            슈퍼로그인 
            로그인 카운트 증가X 
            디바이스 정보 갱신X
        */
        
        if($admin_login === false)
        {
            $login_super = $this->member_mdl->login_super();
            
            // 슈퍼 패스워드 체크
            if($login_super['super_pwd'] == $request['wiz_pw'])
            {
                $this->load->model('board_mdl');
    
                $wiz_member['checked_join_qna'] = member_checked_join_qna($wiz_member['wm_uid']);
                $current_class_status = lesson_current_class_status($wiz_member['wm_uid']);
                $wiz_member['current_class_state'] = $current_class_status['main_lesson_state'];
                $wiz_member['icon'] = $icon['icon'];
                $wiz_member['icon_desc'] = $icon['icon_desc'];
                $assistant_code = member_checked_assistant_code($wiz_member);
                $return_array['res_code'] = '0000';
                $return_array['msg'] = "로그인에 성공하였습니다.";
                $return_array['data']['api_token'] = token_create_member_token($request['wiz_id']);
                $return_array['data']['info'] = $wiz_member;
                $return_array['data']['assistant'] = $assistant_code;
                $return_array['data']['login_encstr'] = member_get_oldmint_autologin_str($wiz_member['wm_uid'],md5($request['wiz_pw']));
                echo json_encode($return_array);
                exit;
            }
        }
        

        /*
            일반로그인
            로그인 카운트 증가O
            디바이스 정보 갱신O
        */
        $md5_pw = $admin_login ? $al_check['wiz_pw']:md5($request['wiz_pw']);
        $result = $this->member_mdl->login($request['wiz_id'], $md5_pw , $request['device'], $request['device_token'], $admin_login);
    
        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0104";
            $return_array['data']['err_msg'] = "아이디 또는 비밀번호를 확인하세요.";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $this->load->model('board_mdl');
            $result['checked_join_qna'] = member_checked_join_qna($result['wm_uid']);

            $current_class_status = lesson_current_class_status($result['wm_uid']);
            $result['current_class_state'] = $current_class_status['main_lesson_state'];
            $result['icon'] = $icon['icon'];
            $result['icon_desc'] = $icon['icon_desc'];
            $assistant_code = member_checked_assistant_code($result);
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "로그인에 성공하였습니다.";
            $return_array['data']['api_token'] = token_create_member_token($result['wm_wiz_id'],$request['aid']);
            $return_array['data']['info'] = $result;
            $return_array['data']['assistant'] = $assistant_code;
            if(!$admin_login)
            {
                $return_array['data']['login_encstr'] = member_get_oldmint_autologin_str($result['wm_uid'],md5($request['wiz_pw']));
            }
            
            echo json_encode($return_array);
            exit;
        }
        
    }

    public function login_sns()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "regi_gubun" => trim(strtolower($this->input->post('regi_gubun'))),
            "device" => trim($this->input->post('device')),
            "device_token" => trim($this->input->post('device_token')),
            "social_email" => trim($this->input->post('social_email')),
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

        /*
            일반로그인
            로그인 카운트 증가O
            디바이스 정보 갱신O
        */

        /*
        lazy777@naver.com       74041   123941  2개
        seoreee7@gmail.com      81128   124019  2개
        una0226sy@gmail.com     82050   124002  2개
        페이스북 키값으로 중복으로 회원가입 회원 
         */
        
        /*
            sns API 리턴받는 고유 키값으로 실제 회원아이디 찾기( 회원 아이디 == 고유키값 + 카운트넘버링 )
            구민트 이전 소셜로그인 삭제 후 다시 가입한 데이터는 카운트가 1부터 시작,
            신민트 소셜로그인 삭제 후 다시 가입한 데이터는 카운트가 2부터 시작
            데이터가 일치하지 않아서 social_id로 검색
        */
        $res = $this->member_mdl->get_wiz_member_by_social_id($request['wiz_id']);

        $wiz_id = ($res['wiz_id']) ? $res['wiz_id'] : $request['wiz_id'];
        
        $result = $this->member_mdl->login_sns($wiz_id, $request['regi_gubun'] , $request['device'], $request['device_token'], $request['social_email']);
    
    

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0105";
            $return_array['data']['err_msg'] = "가입된 정보가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        else
        {
            $this->load->model('board_mdl');
            $icon = member_get_icon($result);
            $result['icon'] = $icon['icon'];
            $result['icon_desc'] = $icon['icon_desc'];
            $current_class_status = lesson_current_class_status($result['wm_uid']);
            $result['current_class_state'] = $current_class_status['main_lesson_state'];

            $assistant_code = member_checked_assistant_code($result);
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "로그인에 성공하였습니다.";
            $return_array['data']['api_token'] = token_create_member_token($result['wm_wiz_id']);
            $return_array['data']['info'] = $result;
            $return_array['data']['assistant'] = $assistant_code;
            $return_array['data']['login_encstr'] = member_get_oldmint_autologin_str($result['wm_uid'],'social');
            echo json_encode($return_array);
            exit;
        }
        

    }

    public function create()
    {
        $return_array = array();

        $request = array(
            "type" => strtolower($this->input->post('type')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "wiz_pw" => $this->input->post('wiz_pw'),
            //"wiz_pw2" => $this->input->post('wiz_pw'),
            "name" => $this->input->post('name'),
            // "nickname" => $this->input->post('nickname'),
            // "ename" => $this->input->post('ename'),
            "birth" => $this->input->post('birth'),
            "regi_area" => $this->input->post('regi_area'),
            "parents" => ($this->input->post('parents')) ? $this->input->post('parents') : '',
            "contact" => $this->input->post('contact'),
            "pcontact" => $this->input->post('pcontact'),
            "sms_ok" => ($this->input->post('sms_ok') == 'Y') ? 'Y' : 'N',
            "email_ok" => ($this->input->post('email_ok') == 'Y') ? 'Y' : 'N',
            "email" => $this->input->post('email'),
            "gender" => $this->input->post('gender'),
            "ip" => $this->input->ip_address(),
            "regi_gubun" => ($this->input->post('regi_gubun')) ? $this->input->post('regi_gubun') : "mint05",
            "social_id" => $this->input->post('social_id'),
            "social_email" => $this->input->post('social_email'),
            "view_boards" => ($this->input->post('view_boards') == "Y") ? 'Y' : 'N',
            "view_online_list" => ($this->input->post('view_online_list') == "Y") ? 'Y' : 'N',
            "view_login_count" => ($this->input->post('view_login_count') == "Y") ? 'Y' : 'N',
            "profile_file" => isset($_FILES["files"]) ? $_FILES["files"] : null,
            "mob" => ($this->input->post('mob') == "M") ? 'Y' : 'N',
            "d_id" => ($this->input->post('d_id')) ? $this->input->post('d_id') : '16',
            "utm" => $this->input->post('utm') ? $this->input->post('utm') : NULL,
            "app_type" => $this->input->post('app_type')        // 앱가입 시 ANDROID_NEW_PACKAGE, IOS_NEW_PACKAGE
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

        $name = common_request_naverapi_kor_to_eng($request['name']);


        if($request['type'] == 'sns')
        {
            $checked_count_wiz_id = $this->member_mdl->checked_count_wiz_id($request['wiz_id']);
            $tmp = $checked_count_wiz_id['cnt'] + 1;
            $request['wiz_id'] = ($checked_count_wiz_id['cnt'] == 0) ? $request['social_id'] : $request['social_id'].$tmp;
            $request['wiz_pw'] = md5(member_generate_random_Password(10));

        }
        else
        {
            $request['wiz_pw'] = md5($request['wiz_pw']);

            //탈퇴한 아이디 있나 체크
            $del_member = $this->member_mdl->get_delete_wiz_member_by_wiz_id($request['wiz_id']);

            if($del_member)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0606";
                $return_array['data']['err_msg'] = "탈퇴한 아이디입니다. 관리자에게 문의해주세요.";
                echo json_encode($return_array);
                exit;
            }
        }

        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);
        
        if($wiz_member)
        {
            
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0605";
            $return_array['data']['err_msg'] = "이미 사용중인 아이디입니다.";
            echo json_encode($return_array);
            exit;
        }

        

        // 닉네임 유무 체크
        // $is_nick = $this->member_mdl->checked_nickname($request['nickname']);
        // 닉네임 유무 제서
        // if($is_nick)
        // {
        //     $return_array['res_code'] = '0900';
        //     $return_array['msg'] = "프로세스오류";
        //     $return_array['data']['err_code'] = "0602";
        //     $return_array['data']['err_msg'] = "이미 사용중인 닉네임입니다.";
        //     echo json_encode($return_array);
        //     exit;
        // }

        $contact = ($request['contact']) ? common_checked_phone_format($request['contact']) : '';
        $contact_type = ($request['contact']) ? common_checked_phone_number_type($contact) : '';
        $tel = ($contact_type == 'T') ? $contact:'';
        $mobile = ($contact_type == 'M') ? $contact:'';
        
        $pcontact = ($request['pcontact']) ? common_checked_phone_format($request['pcontact']) : '';
        $pcontact_type = ($request['pcontact']) ? common_checked_phone_number_type($pcontact) : '';
        $ptel = ($pcontact_type == 'T')? $pcontact:'';
        $pmobile = ($pcontact_type == 'M')? $pcontact:'';
        
        // 핸드폰 번호 중복 체크
        /*
        if($mobile)
        {
            $is_phone = $this->member_mdl->checked_phone_number($mobile);

            if($is_phone)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0603";
                $return_array['data']['err_msg'] = "이미 사용중인 핸드폰 번호입니다.";
                echo json_encode($return_array);
                exit;
            }
        }
        */
        
        $age = date('Y') - substr($request['birth'],0,4);
        $lev_gubun = ($age > 13) ? 'SENIOR' : 'JUNIOR';

        $d_id = $request['d_id'];
        $dealer = $this->member_mdl->get_wiz_dealer($d_id);
        $man_id = $dealer['man_id'];

        $file_name = NULL;

        //s3파일 업로드


        if(isset($request['profile_file']))
        {
            $this->load->library('s3');
            
            $ext_array = array('xlsx', 'xls', 'doc', 'pdf', 'jpg', 'jpeg', 'png', 'txt');
            $upload_limit_size = 5;
            
            $origin_name = $request['profile_file']['name'];
            $upfile_ext = explode('.', $origin_name);
            //. 기준으로 확장자 찾기 위한 배열의 마지막 값
            $upfile_ext = end($upfile_ext);

            $upload_file_name = "profile_".time()."_thumb.".$upfile_ext;
            $thumb_config = array(
                'formfile'=> true,
                'resize_width'=> array(
                    100
                ),
                'newfilename'=>$upload_file_name
            );
            $thumb_result = Thumbnail::create_thumbnail_s3($request['profile_file'],$request['profile_file']['name'],$this->upload_path_member,$thumb_config);
            $file_name = $upload_file_name;
            if(!$thumb_result)
            {
                $res = S3::put_s3_object($this->upload_path_member, $request['profile_file'], $upload_limit_size, $ext_array);
                $file_name = $res['file_name'];
            }
            
        }

        //mob: Y 모바일웹, N:pc, A:안드앱, I:IOS앱
        $mob = $request['mob'];
        if($request['app_type'])
        {
            $mob = $request['app_type'] =='ANDROID_NEW_PACKAGE' ? 'A':'I';
        }

        //인설트 데이터
        $member = array(
            "wiz_id" => $request['wiz_id'],
            "wiz_pw" => $request['wiz_pw'],
            //"wiz_pw2" => $request['wiz_pw2'],
            "social_id" => $request['social_id'],
            "social_email" => $request['social_email'],
            "regi_gubun" => $request['regi_gubun'],
            "regi_area" => $request['regi_area'],
            "lev_gubun" => $lev_gubun,
            "name" => $request['name'],
            "ename" => $name['ename'] ? $name['ename']:'',
            // "nickname" => $request['nickname'],
            "birth" => common_checked_birth_format($request['birth']),
            "parents" => $request['parents'],
            "ptel" => $ptel,
            "pmobile" => $pmobile,
            "tel" => $tel,
            "mobile" => $mobile,
            "sms_ok" => $request['sms_ok'],
            "email" => $request['email'],
            "email_ok" => $request['email_ok'],
            "logview" => 1,
            "lastlogin" => date("Y-m-d H:i:s"),
            "regdate" => date("Y-m-d H:i:s"),
            "man_id" => $man_id,
            "d_id" => $d_id,
            "gender" => $request['gender'],
            "age" => $age,
            "ip" => $request['ip'],
            "mob" => $mob,
            "grade" => "1",
            "update_yn" => "N",
            "point" => 0,
            "view_boards" => $request['view_boards'],
            "view_online_list " => $request['view_online_list'],
            "view_login_count " => $request['view_login_count'],
            "profile"=> isset($file_name) ? $file_name : null,
            "muu_key"=> $request['utm'],
        );
        
        $result = $this->member_mdl->insert_member($member);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);


        if($request['utm'])
        {
            $utm_params = array(
                "muu_key" => $request['utm'],
                "ref_key" => $result,
                "ref_uid" => $result,
                "type" => '2',                                      // 1: 방문자수(1일 1로그), 2: 회원가입, 3: 레벨테스트 신청, 4: 결제, 5: 로그인
                "loc" => ($request['mob'] == 'Y') ? 2 : 1,          // 1: pc, 2: mobile
                "ip" => $request["ip"],
                "regdate" => date("Y-m-d H:i:s"),
            );
            
            $this->load->model('etc_mdl');
            $checked_utm_url = $this->etc_mdl->insert_utm($utm_params);

        }


        $icon = member_get_icon($wiz_member);
        $wiz_member['icon'] = $icon['icon'];
        $wiz_member['icon_desc'] = $icon['icon_desc'];

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "회원가입에 성공하였습니다.";
        $return_array['data']['api_token'] = token_create_member_token($request['wiz_id']);
        $return_array['data']['user_info'] = $wiz_member;
        echo json_encode($return_array);
        exit;

    }

    public function create_level_test()
    {
        $return_array = array();

        $request = array(
            "wm_uid" => $this->input->post('wm_uid'),
            "lesson_gubun" => $this->input->post('lesson_gubun'), 
            "lvt_contact" => $this->input->post('lvt_contact'),
            "hopedate" => $this->input->post('hopedate'),
            "hopetime1" => $this->input->post('hopetime1'),
            "hopetime2" => $this->input->post('hopetime2'),
            "englevel" => $this->input->post('englevel')
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        //희망 시간
        $request['hopetime'] = $request['hopetime1'].":".$request['hopetime2'].":00";

        // 현재 레벨테스트는 V(화상), M(휴대폰) 밖에없다
        $lesson_gubun = $request['lesson_gubun'] == 'V' ? 'V':'M';

        //레슨별 횟수/시간 
        $lesson_term = $request['lesson_gubun'] == 'V' ? 2 : 3;
        $lesson_time = $request['lesson_gubun'] == 'V' ? 20 : 10;

        //첫 테스트 시작/종료 시간
        $request['le_start'] = $request['hopedate']." ".$request['hopetime'];
        $request['le_end'] = date('Y-m-d H:i:s',strtotime("+$lesson_time minutes -1 seconds", strtotime($request['le_start'])));

        //테스트 시간 비교
        $now_time = strtotime("+30 minutes");
        $max_time = strtotime($request['le_start']);

        if($max_time < $now_time)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0604";
            $return_array['data']['err_msg'] = "테스트 시간은 현재시간 기준으로 30분 이후부터 가능합니다.";
            echo json_encode($return_array);
            exit;
        }

		//신청시간이 23:10 을 넘으면 신청불가 처리
        if(sprintf("%02d", $request['hopetime1']) == "02")
        {
            if(sprintf("%02d", $request['hopetime2']) != "00" && sprintf("%02d", $request['hopetime2']) != "10")
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0605";
                $return_array['data']['err_msg'] = "테스트 시작 시간은 오전 2시 10분까지만 신청이 가능합니다.";
                echo json_encode($return_array);
                exit;
			}
        }

        //전화번호 추가
        $lvt_contact = common_checked_phone_format($request['lvt_contact']);
        $lvt_contact_type = common_checked_phone_number_type($request['lvt_contact']);
        $request['tel'] = ($lvt_contact_type == 'T') ? $lvt_contact : '';
        $request['mobile'] = ($lvt_contact_type == 'M') ? $lvt_contact : '';
        

        $this->load->model('member_mdl');
        $wiz_member = $this->member_mdl->get_wiz_member_by_wm_uid($request['wm_uid']);

        $multi_data = array();

        for($i=0; $i < $lesson_term; $i++)
        {
            if($i > 0)
            {
                $request['le_start'] = date('Y-m-d H:i:s', strtotime("+10 minutes +1 seconds", strtotime($request['le_end'])));
                $request['le_end'] = date('Y-m-d H:i:s', strtotime("+$lesson_time minutes -1 seconds", strtotime($request['le_start'])));
            }

            $hopetime = substr($request['le_start'],11);
 
            $level_test_data = array(
                "uid" => $wiz_member['wm_uid'],
                "wiz_id" => $wiz_member['wm_wiz_id'],
                "name" => $wiz_member['wm_name'],
                "ename" => $wiz_member['wm_ename'],
                "lesson_gubun" => $lesson_gubun,
                "tel" => $request['tel'],
                "mobile" => $request['mobile'],
                "englevel" => $request['englevel'],
                "hopedate" => $request['hopedate'],
                "hopetime" => $hopetime,
                "sc_ok" => "N",
                "regdate" => date("Y-m-d H:i:s"),
                "le_start" => $request['le_start'],
                "le_end" => $request['le_end'],
                "mob" => "Y"
            );
            array_push($multi_data, $level_test_data);
        }

        //레벨 테스트 insert
        $result_level_test = $this->member_mdl->insert_level_test($multi_data, $wiz_member['wm_uid']);

        if($result_level_test < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        
        $week = array(" (일) " , " (월) "  , " (화) " , " (수) " , " (목) " , " (금) " ," (토) ") ;
        $weekday = $week[ date('w' , strtotime($request['hopedate']))] ;

        $month = date("m",strtotime($request['hopedate']))."월 ";
        $day = date("d",strtotime($request['hopedate']))."일 ";
        $hour = date("H",strtotime($request['hopetime']));
        $minute = date("i",strtotime($request['hopetime']));
        $hopetime = "";

        if($hour >= 12)
        {
            if($hour > 12)
            {
                $hour = $hour - 12;
            }

            $hopetime = "오후 ".$hour.":".$minute;
        }
        else
        {
            $hopetime = "오전 ".$hour.":".$minute;
        }

        $return_msg = $month.$day.$weekday.$hopetime;
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "레벨 테스트가 신청되었습니다.";
        $return_array['data']['le_fid'] = $result_level_test;
        $return_array['data']['test_date'] = $return_msg;
        echo json_encode($return_array);
        exit;

    }
    

    public function create_member_with_leveltest()
    {
        $return_array = array();

        $request = array(
            "type" => strtolower($this->input->post('type')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "wiz_pw" => $this->input->post('wiz_pw'),
            "name" => $this->input->post('name'),
            "birth" => $this->input->post('birth'),
            "regi_area" => $this->input->post('regi_area'),
            "parents" => ($this->input->post('parents')) ? $this->input->post('parents') : '',
            "contact" => $this->input->post('contact'),
            "pcontact" => $this->input->post('pcontact'),
            "sms_ok" => ($this->input->post('sms_ok') == 'Y') ? 'Y' : 'N',
            "email_ok" => ($this->input->post('email_ok') == 'Y') ? 'Y' : 'N',
            "email" => $this->input->post('email'),
            "gender" => $this->input->post('gender'),
            "ip" => $this->input->ip_address(),
            "regi_gubun" => ($this->input->post('regi_gubun')) ? $this->input->post('regi_gubun') : "mint05",
            "social_id" => $this->input->post('social_id'),
            "social_email" => $this->input->post('social_email'),
            "view_boards" => ($this->input->post('view_boards') == "Y") ? 'Y' : 'N',
            "view_online_list" => ($this->input->post('view_online_list') == "Y") ? 'Y' : 'N',
            "view_login_count" => ($this->input->post('view_login_count') == "Y") ? 'Y' : 'N',
            "profile_file" => isset($_FILES["files"]) ? $_FILES["files"] : null,
            "mob" => ($this->input->post('mob') == "M") ? 'Y' : 'N',
            "d_id" => ($this->input->post('d_id')) ? $this->input->post('d_id') : '16',
            "utm" => $this->input->post('utm') ? $this->input->post('utm') : NULL,
            "lesson_gubun" => $this->input->post('lesson_gubun'), 
            "lvt_contact" => $this->input->post('lvt_contact'),
            "hopedate" => $this->input->post('hopedate'),
            "hopetime1" => $this->input->post('hopetime1'),
            "hopetime2" => $this->input->post('hopetime2'),
            "englevel" => $this->input->post('englevel'),
            "app_type" => $this->input->post('app_type')        // 앱가입 시 ANDROID_NEW_PACKAGE, IOS_NEW_PACKAGE
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

        $name = common_request_naverapi_kor_to_eng($request['name']);

        if($request['type'] == 'sns')
        {
            $checked_count_wiz_id = $this->member_mdl->checked_count_wiz_id($request['wiz_id']);
            $tmp = $checked_count_wiz_id['cnt'] + 1;
            $request['wiz_id'] = ($checked_count_wiz_id['cnt'] == 0) ? $request['social_id'] : $request['social_id'].$tmp;
            $request['wiz_pw'] = md5(member_generate_random_Password(10));

        }
        else
        {
            $request['wiz_pw'] = md5($request['wiz_pw']);

            //탈퇴한 아이디 있나 체크
            $del_member = $this->member_mdl->get_delete_wiz_member_by_wiz_id($request['wiz_id']);

            if($del_member)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0606";
                $return_array['data']['err_msg'] = "탈퇴한 아이디입니다. 관리자에게 문의해주세요.";
                echo json_encode($return_array);
                exit;
            }
        }

        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);
        
        if($wiz_member)
        {
            
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0605";
            $return_array['data']['err_msg'] = "이미 사용중인 아이디입니다.";
            echo json_encode($return_array);
            exit;
        }

        //--START 레벨테스트 유효성 체크
        //희망 시간
        $request['hopetime'] = $request['hopetime1'].":".$request['hopetime2'].":00";

        // 현재 레벨테스트는 V(화상), M(휴대폰) 밖에없다
        $lesson_gubun = $request['lesson_gubun'] == 'E' ? 'E':'M';

        //레슨별 횟수/시간 
        $lesson_term = $request['lesson_gubun'] == 'E' ? 3 : 3;
        $lesson_time = $request['lesson_gubun'] == 'E' ? 10 : 10;

        //첫 테스트 시작/종료 시간
        $request['le_start'] = $request['hopedate']." ".$request['hopetime'];
        $request['le_end'] = date('Y-m-d H:i:s',strtotime("+$lesson_time minutes -1 seconds", strtotime($request['le_start'])));

        // echo $request['le_start'].'/'.$request['le_end'];exit;
        //테스트 시간 비교
        $now_time = strtotime("+30 minutes");
        $max_time = strtotime($request['le_start']);

        if($max_time < $now_time)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0604";
            $return_array['data']['err_msg'] = "테스트 시간은 현재시간 기준으로 30분 이후부터 가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        //--END 레벨테스트 유효성 체크


        /*
            레벨테스트 가능시간
            
            - 평일예약 가능시간: 월요일 새벽1시 ~ 토요일 새벽 00:00 까지
            - 주말예약 가능시간: 토요일 아침6시 ~ 저녁 23:00 / 일요일 아침6시~ 저녁 23:00
        */

        $week = array(" (일) " , " (월) "  , " (화) " , " (수) " , " (목) " , " (금) " ," (토) ") ;
        $weekday = $week[ date('w' , strtotime($request['hopedate']))] ;

        //희망 시간
        $request['hopetime'] = $request['hopetime1'].":".$request['hopetime2'].":00";
        $hope_date = strtotime($request['hopetime']);
        
        $date_01 = strtotime('01:00:00');
        $date_06 = strtotime('06:00:00');
        $date_23 = strtotime('23:00:00');

        if($weekday == ' (월) ' && ($hope_date < $date_01))
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0607";
            $return_array['data']['err_msg'] = "레벨테스트 평일 월요일 새벽1시 ~ 토요일 새벽 00:00 까지 가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        if( (($weekday == ' (토) ' || $weekday == ' (일) ') && ($hope_date < $date_06)) || (($weekday == ' (토) ' || $weekday == ' (일) ') && ($hope_date > $date_23)) )
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0607";
            $return_array['data']['err_msg'] = "레벨테스트는 주말은 아침6시 ~ 저녁 23:00 까지 가능합니다.";
            echo json_encode($return_array);
            exit;
        }

        $contact = ($request['contact']) ? common_checked_phone_format($request['contact']) : '';
        $contact_type = ($request['contact']) ? common_checked_phone_number_type($contact) : '';
        $tel = ($contact_type == 'T') ? $contact:'';
        $mobile = ($contact_type == 'M') ? $contact:'';
        
        $pcontact = ($request['pcontact']) ? common_checked_phone_format($request['pcontact']) : '';
        $pcontact_type = ($request['pcontact']) ? common_checked_phone_number_type($pcontact) : '';
        $ptel = ($pcontact_type == 'T')? $pcontact:'';
        $pmobile = ($pcontact_type == 'M')? $pcontact:'';
        
        $age = date('Y') - substr($request['birth'],0,4);
        $lev_gubun = ($age > 13) ? 'SENIOR' : 'JUNIOR';

        $d_id = $request['d_id'];
        $dealer = $this->member_mdl->get_wiz_dealer($d_id);
        $man_id = $dealer['man_id'];

        $file_name = NULL;

        //s3파일 업로드


        if(isset($request['profile_file']))
        {
            $this->load->library('s3');
            
            $ext_array = array('xlsx', 'xls', 'doc', 'pdf', 'jpg', 'jpeg', 'png', 'txt');
            $upload_limit_size = 5;
            
            $origin_name = $request['profile_file']['name'];
            $upfile_ext = explode('.', $origin_name);
            //. 기준으로 확장자 찾기 위한 배열의 마지막 값
            $upfile_ext = end($upfile_ext);

            $upload_file_name = "profile_".time()."_thumb.".$upfile_ext;
            $thumb_config = array(
                'formfile'=> true,
                'resize_width'=> array(
                    100
                ),
                'newfilename'=>$upload_file_name
            );
            $thumb_result = Thumbnail::create_thumbnail_s3($request['profile_file'],$request['profile_file']['name'],$this->upload_path_member,$thumb_config);
            $file_name = $upload_file_name;
            if(!$thumb_result)
            {
                $res = S3::put_s3_object($this->upload_path_member, $request['profile_file'], $upload_limit_size, $ext_array);
                $file_name = $res['file_name'];
            }
            
        }

        //mob: Y 모바일웹, N:pc, A:안드앱, I:IOS앱
        $mob = $request['mob'];
        if($request['app_type'])
        {
            $mob = $request['app_type'] =='ANDROID_NEW_PACKAGE' ? 'A':'I';
        }
        
        //인설트 데이터
        $member = array(
            "wiz_id" => $request['wiz_id'],
            "wiz_pw" => $request['wiz_pw'],
            //"wiz_pw2" => $request['wiz_pw2'],
            "social_id" => $request['social_id'],
            "social_email" => $request['social_email'],
            "regi_gubun" => $request['regi_gubun'],
            "regi_area" => $request['regi_area'],
            "lev_gubun" => $lev_gubun,
            "name" => $request['name'],
            "ename" => $name['ename'] ? $name['ename']:'',
            // "nickname" => $request['nickname'],
            "birth" => common_checked_birth_format($request['birth']),
            "parents" => $request['parents'],
            "ptel" => $ptel,
            "pmobile" => $pmobile,
            "tel" => $tel,
            "mobile" => $mobile,
            "sms_ok" => $request['sms_ok'],
            "email" => $request['email'],
            "email_ok" => $request['email_ok'],
            "logview" => 1,
            "lastlogin" => date("Y-m-d H:i:s"),
            "regdate" => date("Y-m-d H:i:s"),
            "man_id" => $man_id,
            "d_id" => $d_id,
            "gender" => $request['gender'],
            "age" => $age,
            "ip" => $request['ip'],
            "mob" => $mob,
            "grade" => "1",
            "update_yn" => "N",
            "point" => 0,
            "view_boards" => $request['view_boards'],
            "view_online_list " => $request['view_online_list'],
            "view_login_count " => $request['view_login_count'],
            "profile"=> isset($file_name) ? $file_name : null,
            "muu_key"=> $request['utm'],
        );
        
        $result = $this->member_mdl->insert_member($member);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);

        //--START 레벨테스트 등록

        //전화번호 추가
        $lvt_contact = common_checked_phone_format($request['lvt_contact']);
        $lvt_contact_type = common_checked_phone_number_type($request['lvt_contact']);
        $request['tel'] = ($lvt_contact_type == 'T') ? $lvt_contact : '';
        $request['mobile'] = ($lvt_contact_type == 'M') ? $lvt_contact : '';
        
        $multi_data = array();

        for($i=0; $i < $lesson_term; $i++)
        {
            //$time = ($request['lesson_gubun'] == 'E') ? '' : '+10 minutes';
            $time = '+10 minutes';
            
            if($i > 0)
            {
                $request['le_start'] = date('Y-m-d H:i:s', strtotime("$time +1 seconds", strtotime($request['le_end'])));
                $request['le_end'] = date('Y-m-d H:i:s', strtotime("$lesson_time minutes -1 seconds", strtotime($request['le_start'])));
            }

            $hopetime = substr($request['le_start'],11);
 
            $level_test_data = array(
                "uid" => $wiz_member['wm_uid'],
                "wiz_id" => $wiz_member['wm_wiz_id'],
                "name" => $wiz_member['wm_name'],
                "ename" => $wiz_member['wm_ename'],
                "lesson_gubun" => $lesson_gubun,
                "tel" => $request['tel'],
                "mobile" => $request['mobile'],
                "englevel" => $request['englevel'],
                "hopedate" => $request['hopedate'],
                "hopetime" => $hopetime,
                "sc_ok" => "N",
                "regdate" => date("Y-m-d H:i:s"),
                "le_start" => $request['le_start'],
                "le_end" => $request['le_end'],
                "mob" => "Y"
            );
            array_push($multi_data, $level_test_data);
        }

        //레벨 테스트 insert
        $result_level_test = $this->member_mdl->insert_level_test($multi_data, $wiz_member['wm_uid']);

        if($result_level_test < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        


        $month = date("m",strtotime($request['hopedate']))."월 ";
        $day = date("d",strtotime($request['hopedate']))."일 ";
        $hour = date("H",strtotime($request['hopetime']));
        $minute = date("i",strtotime($request['hopetime']));
        $hopetime = "";

        if($hour >= 12)
        {
            if($hour > 12)
            {
                $hour = $hour - 12;
            }

            $hopetime = "오후 ".$hour.":".$minute;
        }
        else
        {
            $hopetime = "오전 ".$hour.":".$minute;
        }

        $leveltest_date = $month.$day.$weekday.$hopetime;
        //--END 레벨테스트 등록


        if($request['utm'])
        {
            $utm_params = array(
                "muu_key" => $request['utm'],
                "ref_key" => $result,
                "ref_uid" => $result,
                "type" => '2',                                      // 1: 방문자수(1일 1로그), 2: 회원가입, 3: 레벨테스트 신청, 4: 결제, 5: 로그인
                "loc" => ($request['mob'] == 'Y') ? 2 : 1,          // 1: pc, 2: mobile
                "ip" => $request["ip"],
                "regdate" => date("Y-m-d H:i:s"),
            );
            
            $this->load->model('etc_mdl');
            $this->etc_mdl->insert_utm($utm_params);

            $utm_params = array(
                "muu_key" => $request['utm'],
                "ref_key" => $result,
                "ref_uid" => $result,
                "type" => '3',                                      // 1: 방문자수(1일 1로그), 2: 회원가입, 3: 레벨테스트 신청, 4: 결제, 5: 로그인
                "loc" => ($request['mob'] == 'Y') ? 2 : 1,          // 1: pc, 2: mobile
                "ip" => $request["ip"],
                "regdate" => date("Y-m-d H:i:s"),
            );
            
            $this->etc_mdl->insert_utm($utm_params);

        }


        //알림톡 전송
        $CONFIG_ATALK_CODE = $this->config->item('ATALK_CODE');
        $CONFIG_SMS_ID = $this->config->item('SMS_ID');

        /* 비동기 전송시 보낼 알림톡 코드/SMS ID 세팅  */
        if($lesson_gubun == 'M')
        {
            $ATALK_CODE = $CONFIG_ATALK_CODE['APPLY_LEVELTEST_TEL'];
            $SMS_ID = $CONFIG_SMS_ID['APPLY_LEVELTEST_TEL'];
        }
        else if($lesson_gubun == 'E')
        {
            $ATALK_CODE = $CONFIG_ATALK_CODE['APPLY_LEVELTEST_MEL'];
            $SMS_ID = $CONFIG_SMS_ID['APPLY_LEVELTEST_MEL'];
        }

        // 비동기 전송
        notify_send_sms($wiz_member['wm_uid'], $ATALK_CODE, $SMS_ID);

        $icon = member_get_icon($wiz_member);
        $wiz_member['icon'] = $icon['icon'];
        $wiz_member['icon_desc'] = $icon['icon_desc'];

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "회원가입에 성공하였습니다.";
        $return_array['data']['api_token'] = token_create_member_token($request['wiz_id']);
        $return_array['data']['user_info'] = $wiz_member;
        $return_array['data']['le_fid'] = $result_level_test;
        $return_array['data']['test_date'] = $leveltest_date;
        echo json_encode($return_array);
        exit;

    }


    public function checked_id()
    {
        $return_array = array();

        $request = array(
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
        
        $this->load->model('member_mdl');

        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);

        if($wiz_member)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0106";
            $return_array['data']['err_msg'] = "이미 사용중인 이메일입니다.";
            echo json_encode($return_array);
            exit;
        }

        //탈퇴한 아이디 있나 체크
        $del_member = $this->member_mdl->get_delete_wiz_member_by_wiz_id($request['wiz_id']);

        
        if($del_member)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0606";
            $return_array['data']['err_msg'] = "탈퇴한 아이디입니다. 관리자에게 문의해주세요.";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "사용 가능한 이메일입니다.";
        $return_array['data']['wiz_id'] = $request['wm_wiz_id'];
        echo json_encode($return_array);
        exit;
        

    }

    public function checked_phone_number()
    {
        $return_array = array();

        $request = array(
            "contact" => trim(strtolower($this->input->post('contact'))),
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

        $contact = common_checked_phone_format($request['contact']);
        $contact_type = common_checked_phone_number_type($contact);
        $is_phone = ($contact_type == 'M') ? $this->member_mdl->checked_phone_number($contact) : null;

        if($is_phone)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0107";
            $return_array['data']['err_msg'] = "이미 사용중인 핸드폰 번호입니다.";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['data']['msg'] = "사용 가능한 핸드폰 번호입니다.";
        echo json_encode($return_array);
        exit;
    }
   
    public function checked_nickname()
    {
        $return_array = array();

        $request = array(
            "nickname" => trim(strtolower($this->input->post('nickname'))),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['data']['err_msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        $this->load->model('member_mdl');

        $is_nickname = ($this->member_mdl->checked_nickname($request['nickname'])) ? true : null;

        if($is_nickname)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = '프로세스오류';
            $return_array['data']['err_code'] = "0108";
            $return_array['data']['err_msg'] = "이미 사용중인 닉네임 입니다.";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "사용 가능한 닉네임 입니다.";
        echo json_encode($return_array);
        exit;
    }

    public function find_id()
    {
        $return_array = array();

        $request = array(
            "name" => $this->input->post('name'), 
            "birth" => $this->input->post('birth'), 
            "tel" => $this->input->post('tel'),
            "email" => $this->input->post('email'),
            "type" => $this->input->post('type'),
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
        
        /** 생년월일에 하이픈(-) 추가  */
        $birth = ($request['birth']) ? common_checked_birth_format($request['birth']) : '';

        if($request['type'] === 'tel')
        {
            /** 번호에 하이픈(-) 추가  */
            $tel = ($request['tel']) ? common_checked_phone_format($request['tel']) : '';
            

            /** 모바일인지 TEL인지 확인(모바일 : M, TEL : T)*/
            $checked_phone_number_type = common_checked_phone_number_type($request['tel']);

            if($checked_phone_number_type == 'M')
            {
                $result = $this->member_mdl->find_id_mobile($request['name'], $birth, $tel);
            }
            else if($checked_phone_number_type == 'T')
            {
                $result = $this->member_mdl->find_id_tel($request['name'], $birth, $tel);
            }
        }
        else if($request['type'] === 'email')
        {
            $result = $this->member_mdl->find_id_email($request['name'], $birth, $request['email']);
        }

        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스 오류.";
            $return_array['data']['err_code'] = "0109";
            $return_array['data']['err_msg'] = "입력하신 정보와 일치하는 아이디가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "아이디 찾기에 성공하였습니다.";
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
    }

    public function find_pwd()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => $this->input->post('wiz_id'),
            "name" => $this->input->post('name'),
            "tel" => $this->input->post('tel'), 
            "email" => $this->input->post('email'),
            "type" => $this->input->post('type'),
        );

        
        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
        }

        $this->load->model('member_mdl');

        if($request['type'] == 'tel')
        {
            /** 번호에 하이픈(-) 추가  */
            $tel = ($request['tel']) ? common_checked_phone_format($request['tel']) : '';

            /** 모바일인지 TEL인지 확인(모바일 : M, TEL : T)*/
            $checked_phone_number_type = common_checked_phone_number_type($request['tel']);

            if($checked_phone_number_type == 'M')
            {
                $is_find_pwd = $this->member_mdl->find_pwd_mobile($request['wiz_id'], $request['name'], $tel);
            
            }
            else if($checked_phone_number_type == 'T')
            {
                $is_find_pwd = $this->member_mdl->find_pwd_tel($request['wiz_id'], $request['name'], $tel);
            }
        }
        else if($request['type'] == 'email')
        {
            $is_find_pwd = $this->member_mdl->find_pwd_email($request['wiz_id'], $request['name'], $request['email']);
        }

        if(!$is_find_pwd)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스 오류.";
            $return_array['data']['err_code'] = "0110";
            $return_array['data']['err_msg'] = "입력하신 정보와 일치하는 정보를 찾을 수 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "패스워드를 새로 설정해주세요.";
        $return_array['data']['api_token'] = token_create_member_token($request['wiz_id']);
        echo json_encode($return_array);
        exit;
    }

    public function change_english()
    {
        
        $request = array(
            "name" => $this->input->post('name'),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        /*
            host : API주소 
            headers : 인증값 (카카오 개발자 사이트 발급 키값)
            src_lang : 번역 전 언어
            target_lang : 번역 후 언어
            query : 번역할 내용
        */
        $host = 'https://kapi.kakao.com/v1/translation/translate';
        $headers = array('Authorization: KakaoAK 8ab70380c4dcfd7a4bdfc059d6a5cdc2 ');

        $src_lang = "kr";
        $target_lang = "en";
        $query = $request['name'];
        $post_query = '&src_lang='.$src_lang.'&target_lang='.$target_lang.'&query='.urlencode($query);


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_query);
        $result = json_decode(curl_exec($ch),true);
        
        if(!$result)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스 오류.";
            $return_array['data']['err_code'] = "0601";
            $return_array['data']['err_msg'] = "번역에 실패하였습니다.";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "번역에 성공하였습니다.";
        $return_array['data']['translated_text'] = trim($result['translated_text'][0][0]);
        echo json_encode($return_array);        
        exit;
    }

    public function notify_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => $this->input->post('wiz_id'),
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mn.view",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "ASC",
            "sec_order_field" => ($this->input->post('sec_order_field')) ? trim(strtolower($this->input->post('sec_order_field'))) : "mn.idx",
            "sec_order" => $this->input->post('sec_order') ? trim(strtoupper($this->input->post('order'))) : "DESC",
        );

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
        $wiz_member = $this->member_mdl->get_wm_uid_by_wiz_id($request['wiz_id']);

        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $where = "WHERE mn.uid = '".$wiz_member['wm_uid']."' AND mn.removed = 0";

        $list_cnt = $this->member_mdl->list_count_notify($where);


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
        
        $order_tmp = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        $order = ($request['sec_order_field'] && $request['sec_order']) ? sprintf($order_tmp." , %s %s", $request['sec_order_field'], $request['sec_order']) : $order_tmp."";
        
        //알림 내용중 HTML태그 제거
        $result = notify_list_strip_tags($this->member_mdl->list_notify($where, $order, $limit));
        
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
        
    }


    public function control_notify()
    {
        $return_array = array();

        $request = array(
            "authorization" => $this->input->post('authorization'),
            "wiz_id" => $this->input->post('wiz_id'),
            "type" => $this->input->post('type'),
            "idx" => $this->input->post('idx')
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $idx = explode(',',$request['idx']);

        $this->load->model('member_mdl');
        
        if($request['type'] == "view")
        {
            $result = $this->member_mdl->view_notify($idx);
        }
        else
        {
            $result = $this->member_mdl->removed_notify($idx);
        }
      

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        

        $return_array['res_code'] = '0000';
        $return_array['msg'] = ($request['type'] == "view") ? "알림을 읽음 처리하였습니다." : "알림을 삭제하였습니다.";
        echo json_encode($return_array);
        exit;

    }


    public function clip_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => $this->input->post('wiz_id'),
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mcb.cb_unq",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
            "sec_order_field" => trim($this->input->post('sec_order_field')),
            "sec_order" => trim($this->input->post('sec_order')),
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

        $where = "WHERE mcb.reg_wiz_id = '".$request['wiz_id']."'";

        $list_cnt = $this->member_mdl->list_count_clip($where);


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
        
        $order_tmp = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        $order = ($request['sec_order_field'] && $request['sec_order']) ? sprintf($order_tmp." , %s %s", $request['sec_order_field'], $request['sec_order']) : $order_tmp."";
        
        $result = $this->member_mdl->list_clip($where, $order, $limit);
        foreach($result as $key=>$val)
        {
            $result[$key]['title'] = strip_tags($val['title']);
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
        
    }


    public function delete_clip()
    {
        $return_array = array();

        $request = array(
            "authorization" => $this->input->post('authorization'),
            "wiz_id" => $this->input->post('wiz_id'),
            "cb_unq" => $this->input->post('cb_unq')
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }


        $cb_unq = explode(',',$request['cb_unq']);

        $this->load->model('member_mdl');
        
        $result = $this->member_mdl->delete_clip($cb_unq, $request['wiz_id']);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "스크랩된 게시물을 삭제하였습니다.";
        echo json_encode($return_array);
        exit;

    }

    public function article_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => $this->input->post('wiz_id'),
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mb_regdate",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
            "sec_order_field" => trim($this->input->post('sec_order_field')),
            "sec_order" => trim($this->input->post('sec_order')),
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

        /* 회원정보 */
        $this->load->model('member_mdl');

        $where_wiz_id = "WHERE mb.wiz_id = '".$request['wiz_id']."'";

        // $where_mint_board = " AND ((mb.table_code BETWEEN 1100 AND 1137) OR (mb.table_code BETWEEN 1139 AND 1199) OR (mb.table_code BETWEEN 1300 AND 1399) OR (mb.table_code = 1138 AND (mb.parent_key = '0' || mb.parent_key IS NULL )))";
        $where_mint_board = ' AND (mb.table_code BETWEEN 1100 AND 1199 OR mb.table_code BETWEEN 1300 AND 1399)';
        $where_wm_uid = "WHERE mb.uid = '".$wiz_member['wm_uid']."'";

        $list_cnt = $this->member_mdl->list_count_article($where_wiz_id, $where_wm_uid);


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
        
        $order_tmp = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        $order = ($request['sec_order_field'] && $request['sec_order']) ? sprintf($order_tmp." , %s %s", $request['sec_order_field'], $request['sec_order']) : $order_tmp."";
        
        $result = $this->member_mdl->list_article($where_wiz_id, $where_wm_uid, $order, $limit, $where_mint_board);
        foreach($result as $key=>$val)
        {
            $result[$key]['mb_title'] = strip_tags($val['mb_title']);
        }
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;

    }

    public function comment_()
    {

        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => $this->input->post('wiz_id'),
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "regdate",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
            "sec_order_field" => trim($this->input->post('sec_order_field')),
            "sec_order" => trim($this->input->post('sec_order')),
        );

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

        $join_article = " INNER JOIN mint_boards mb ON mbc.mb_unq = mb.mb_unq";
        $where = " WHERE mbc.writer_id = '".$request['wiz_id']."'";
        $where_writer_id = "WHERE mbc.writer_id = '".$request['wiz_id']."'";
        $where_wiz_id = "WHERE mbc.wiz_id = '".$request['wiz_id']."'";
        
        $list_cnt = $this->member_mdl->list_count_article_comment($where_writer_id, $where_wiz_id);


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
        
        $order_tmp = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        $order = ($request['sec_order_field'] && $request['sec_order']) ? sprintf($order_tmp." , %s %s", $request['sec_order_field'], $request['sec_order']) : $order_tmp."";
        
        $list_article_comment = $this->member_mdl->list_article_comment($join_article, $where, $where_writer_id, $where_wiz_id, $order, $limit);

        
        // $this->load->library('CI_Benchmark');
        // $this->benchmark->mark('banner_start');

        $result = board_article_title($list_article_comment);
        
        // $this->benchmark->mark('banner_end');
        // echo 'speed : '.$this->benchmark->elapsed_time('banner_start', 'banner_end').PHP_EOL;
        // exit;

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;

    }

    public function teacher_counseling_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => $this->input->post('wiz_id'),
            "search_keyword" => trim($this->input->post('search_keyword')),
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "wt.to_id",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
            "sec_order_field" => trim($this->input->post('sec_order_field')),
            "sec_order" => trim($this->input->post('sec_order')),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }


        $where = "WHERE wt.wiz_id = '".$request['wiz_id']."'";

        if($request['search_keyword'])
        {
            $where .=  " AND match(wt.title) against ('*".$request['search_keyword']."*' IN BOOLEAN MODE)";
        }
    
        $this->load->model('member_mdl');
        $list_cnt = $this->member_mdl->list_count_teacher_counseling($where);


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
        
        $order_tmp = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        $order = ($request['sec_order_field'] && $request['sec_order']) ? sprintf($order_tmp." , %s %s", $request['sec_order_field'], $request['sec_order']) : $order_tmp."";
        
        $result = $this->member_mdl->list_teacher_counseling($where, $order, $limit);
        
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "목록조회성공";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
    }



    public function info()
    {
        $return_array = $this->member_info();
        echo json_encode($return_array);
        exit;
    }

    private function member_info(){
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run(strtolower(__CLASS__).'/'.__FUNCTION__) == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());

            return $return_array;
        }


        $this->load->model('member_mdl');
        
        // 아이디 유무 체크
        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);

        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }
        
        $icon = member_get_icon($wiz_member);
        $wiz_member['icon'] = $icon['icon'];
        $wiz_member['icon_desc'] = $icon['icon_desc'];
        $current_class_status = lesson_current_class_status($wiz_member['wm_uid']);
        $wiz_member['current_class_state'] = $current_class_status['main_lesson_state'];

        //사용중인 트로피 정보
        $wiz_member['mqut_regdate'] = date('Y-m-d' , strtotime($wiz_member['mqut_regdate'])).' '.getTime_PM_AM($wiz_member['mqut_regdate']);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "회원정보 조회에 성공하였습니다.";
        $return_array['data']['user_info'] = $wiz_member;

        return $return_array;
    }
    
    public function update_info()
    {
        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "wiz_pw" => $this->input->post('wiz_pw'),
            //"wiz_pw2" => $this->input->post('wiz_pw'),
            "name" => $this->input->post('name'),
            "nickname" => $this->input->post('nickname'),
            "ename" => $this->input->post('ename'),
            "birth" => $this->input->post('birth'),
            "regi_area" => $this->input->post('regi_area'),
            "contact" => $this->input->post('contact'),
            "sms_ok" => ($this->input->post('sms_ok') == 'Y') ? 'Y' : 'N',
            "email_ok" => ($this->input->post('email_ok') == 'Y') ? 'Y' : 'N',
            "gender" => $this->input->post('gender'),
            "view_boards" => ($this->input->post('view_boards') == 'Y') ? 'Y' : 'N',
            "view_online_list" => ($this->input->post('view_online_list') == 'Y') ? 'Y' : 'N',
            "view_login_count" => ($this->input->post('view_login_count') == 'Y') ? 'Y' : 'N',
            "profile_file" => isset($_FILES["files"]) ? $_FILES["files"] : null,
            "delete_profile_file" => ($this->input->post('delete_profile_file') == 'Y') ? 'Y' : 'N',
            "tropy_ut_idx" => ($this->input->post('tropy_ut_idx')) ? $this->input->post('tropy_ut_idx') : null,
            "greeting" => ($this->input->post('greeting')) ? $this->input->post('greeting') : null,
        );

        $this->load->library('s3');

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('point_mdl');
        $this->load->model('member_mdl');
        
        // 아이디 유무 체크
        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);

        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        // 닉네임 변경시
        $nick_name_change = FALSE;
        // 변경전 닉네임 - 닉네임 변경 히스토리 처리
        $berfore_nickname = NULL;
        $after_nickname = NULL;
        
        if($request['nickname'] !='' && $wiz_member['wm_nickname'] != $request['nickname'])
        {
            // 닉네임 유무 체크
            $is_nick = $this->member_mdl->checked_nickname($request['nickname']);

            if($is_nick)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0602";
                $return_array['data']['err_msg'] = "이미 사용중인 닉네임입니다.";
                echo json_encode($return_array);
                exit;
            }
            
            // 닉네임 설정 되있으면 추후 변경은 포인트소모
            if($wiz_member['wm_nickname'])
            {
                $nowpoint = $this->point_mdl->check_current_point($wiz_member['wm_uid']);
                if($nowpoint['wm_point'] < 30000)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0604";
                    $return_array['data']['err_msg'] = "닉네임 변경포인트(30,000)가 부족합니다.";
                    echo json_encode($return_array);
                    exit;
                }
    
                $nick_name_change = TRUE;
                $berfore_nickname = $wiz_member['wm_nickname'];
                $after_nickname = $request['nickname'];
            }
            
        }

        $contact = ($request['contact']) ? common_checked_phone_format($request['contact']) : '';
        $contact_type = ($request['contact']) ? common_checked_phone_number_type($contact) : '';
        $tel = ($contact_type == 'T') ? $contact:'';
        $mobile = ($contact_type == 'M') ? $contact:'';

        $age = date('Y') - substr($request['birth'],0,4);
        $lev_gubun = ($age > 13) ? 'SENIOR' : 'JUNIOR';

        
        // if($wiz_member['wm_mobile'] != $mobile)
        // {
        //     // 핸드폰 번호 중복 체크
        //     $contact = common_checked_phone_format($request['contact']);
        //     $contact_type = common_checked_phone_number_type($contact);
        //     $is_phone = ($contact_type == 'M') ? $this->member_mdl->checked_phone_number($contact) : null;
    
        //     if($is_phone)
        //     {
        //         $return_array['res_code'] = '0900';
        //         $return_array['msg'] = "프로세스오류";
        //         $return_array['data']['err_code'] = "0603";
        //         $return_array['data']['err_msg'] = "이미 사용중인 핸드폰 번호입니다.";
        //         echo json_encode($return_array);
        //         exit;
        //     }
        // }

        $file_name = $wiz_member['wm_profile'] ? $wiz_member['wm_profile']:'';

        // 사진삭제 누르고 요청했을시
        if($request['delete_profile_file'] == 'Y')
        {
            S3::delete_s3_object($this->upload_path_member, $wiz_member['wm_profile']);
            $file_name = '';
        }
        
        //s3파일 업로드 - 이미지 수정하고 요청했을시
        if(isset($request['profile_file']))
        {
            $this->load->library('s3');

            $ext_array = array('xlsx', 'xls', 'doc', 'pdf', 'jpg', 'jpeg', 'png', 'txt');
            $upload_limit_size = 5;
            
            if( isset($wiz_member['wm_profile']) )
            {
                S3::delete_s3_object($this->upload_path_member, $wiz_member['wm_profile']);
            }

            $origin_name = $request['profile_file']['name'];
            $upfile_ext = explode('.', $origin_name);
            //. 기준으로 확장자 찾기 위한 배열의 마지막 값
            $upfile_ext = end($upfile_ext);

            $upload_file_name = "profile_".time()."_thumb.".$upfile_ext;
            $thumb_config = array(
                'formfile'=> true,
                'resize_width'=> array(
                    100
                ),
                'newfilename'=>$upload_file_name
            );
            $thumb_result = Thumbnail::create_thumbnail_s3($request['profile_file'],$request['profile_file']['name'],$this->upload_path_member,$thumb_config);
            $file_name = $upload_file_name;
            if(!$thumb_result)
            {
                $res = S3::put_s3_object($this->upload_path_member, $request['profile_file'], $upload_limit_size, $ext_array);
                $file_name = $res['file_name'];
            }

        }

        //회원 트로피 정보 업데이트
        if($request['tropy_ut_idx']){
            $this->member_mdl->update_user_tropy($wiz_member['wm_uid'], $request['tropy_ut_idx']);
        }

        $wiz_pw = ($request['wiz_pw']) ? md5($request['wiz_pw']) : NULL;
        $member = array(
            "wiz_pw" => $wiz_pw,
            //"wiz_pw2" => $request['wiz_pw2'],
            "regi_area" => $request['regi_area'],
            "lev_gubun" => $lev_gubun,
            "name" => $request['name'],
            "ename" => $request['ename'],
            "nickname" => $request['nickname'],
            "birth" => $request['birth'],
            "tel" => $tel,
            "mobile" => $mobile,
            "sms_ok" => $request['sms_ok'],
            "email_ok" => $request['email_ok'],
            "gender" => $request['gender'],
            "age" => $age,
            "update_yn" => "Y",
            "view_boards" => $request['view_boards'],
            "view_online_list " => $request['view_online_list'],
            "view_login_count " => $request['view_login_count'],
            "profile" => isset($file_name) ? $file_name : null,
            // "profile" => '202003t.PNG',
        );

        //인삿말이 있을경우 업데이트
        if($request['greeting']) $member['greeting'] = $request['greeting'];

        $member = array_filter($member);

        $member['profile'] = $file_name;
        //varchar(30);
        
        $result = $this->member_mdl->update_member($member, $request['wiz_id']);
        
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        // 닉네임변경 3만 포인트 소모
        if($nick_name_change)
        {
            // 지급포인트 구분
            $point = array(
                'uid' => $wiz_member['wm_uid'],
                'name' => $wiz_member['wm_name'],
                'point' => common_point_standard('nickname_modi'),
                'pt_name'=> '[회원정보 수정] 닉네임 변경', 
                'kind'=> 'n', 
                'regdate' => date("Y-m-d H:i:s")
            );
            
            $this->point_mdl->set_wiz_point($point);

            // 닉네임 변경 히스토리 입력
            $nickname_log = array(
                'wm_uid' => $wiz_member['wm_uid'],
                'wmnl_before_nickname' => $berfore_nickname,
                'wmnl_after_nickname' => $after_nickname,
                'wmnl_regdate' => date("Y-m-d H:i:s")
            );
            $this->member_mdl->insert_nickname_log($nickname_log);
        }

        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);

        $icon = member_get_icon($wiz_member);
        $wiz_member['icon'] = $icon['icon'];
        $wiz_member['icon_desc'] = $icon['icon_desc'];

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "회원정보를 변경하였습니다.";
        $return_array['data']['user_info'] = $wiz_member;
        echo json_encode($return_array);
        exit;
        
    }

    public function update_nickname()
    {
        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            'wiz_id' => $this->input->post('wiz_id') ? $this->input->post('wiz_id') : null,
            'nickname' => $this->input->post('nickname') ? $this->input->post('nickname') : null
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
        $checked_nickname = $this->member_mdl->checked_nickname($request['nickname']);

        if($checked_nickname)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0602";
            $return_array['data']['err_msg'] = "이미 사용중인 닉네임입니다.";
            echo json_encode($return_array);
            exit;
        }

        $member = array(
            "nickname" => $request['nickname']
        );

        $result = $this->member_mdl->update_member(array_filter($member), $request['wiz_id']);
        
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "닉네임을 등록하였습니다.";
        echo json_encode($return_array);
        exit;
    }

    public function update_password()
    {
        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "wiz_pw" => $this->input->post('wiz_pw'),
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
        
        // 아이디 유무 체크
        $wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['wiz_id']);
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $member = array(
            "wiz_pw" => md5($request['wiz_pw']),
            //"wiz_pw2" => $request['wiz_pw'],
        );
        
        $result = $this->member_mdl->update_member(array_filter($member), $request['wiz_id']);
        
        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "비밀번호를 변경하였습니다.";
        echo json_encode($return_array);
        exit;
    }

    public function leave()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
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


        $this->load->model('member_mdl');
        
        // 아이디 유무 체크
        $wiz_member = $this->member_mdl->get_wm_uid_by_wiz_id($request['wiz_id']);
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }
        
        $member = array(
            "value_ok" => "N",
            "del_yn" => 'd',
            "del_date" => date('Y-m-d H:i:s'),
        );

        $result = $this->member_mdl->update_member($member, $request['wiz_id']);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "탈퇴처리 되었습니다.";
        echo json_encode($return_array);
        exit;
    }
    
    public function product_rand_nickname()
    {
        $return_array = array();

		$firstNick = array("괴로운", "귀여운", "아름다운", "사랑하는", "외로운", "혼자신난", "비싼", "잘나가는", "유능한", "착한", "신난", "혼자잘노는", "탐스러운", "행복한", "화려한", "치킨먹는", "사랑받는", "유쾌한", "똑똑한", "돌아온", "섹시한", "짓궃은", "쌀쌀맞은", "못생긴", "밝은", "나쁜", "따뜻한", "쿨한", "갸날픈", "딱한", "끈기있는", "강한", "약한", "곤란한", "웃긴", "심심한", "배고픈", "운좋은", "현명한", "탐욕스런", "긍정적인", "부정적인", "빛나는", "센스있는", "열공중인");
		$secondNick = array("웨스티", "거북이", "영어", "철수", "영미", "영희", "봄봄", "봄", "사슴", "노루", "여름", "가을", "겨울", "푸들", "허스키", "닥스훈트", "야옹이", "표범", "하이에나", "민트", "소", "고양이", "대머리", "기린", "새우", "닭", "사자", "고슴도치", "송아지", "귀뚜라미", "생쥐", "하마", "바다표범", "강아지", "돌고래", "꿀벌", "고래", "배짱이", "개미", "메뚜기", "비버", "아기고래", "햄스터", "상어", "대머리독수리", "오징어", "꼴뚜기", "냐옹이", "곰", "개냥이", "아깽이", "라마", "코끼리", "악어", "댕댕이", "냥이", "청개구리", "가리비", "스피치", "파랑새", "키위", "사과", "젖소", "게", "자라", "노래", "가수");

        $select_1 = array_rand($firstNick);
        $select_2 = array_rand($secondNick);

        $nickname = $firstNick[$select_1].$secondNick[$select_2];

        $this->load->model('member_mdl');
        $result = $this->member_mdl->checked_nickname($nickname);

        while($result)
        {
            $select_1 = array_rand($firstNick);
            $select_2 = array_rand($secondNick);
            $nickname = $firstNick[$select_1].$secondNick[$select_2].mt_rand(0,100);
            $result = $this->member_mdl->checked_nickname($nickname);
        }
    
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "해당 닉네임은 사용할 수 있습니다.";
        $return_array['data']['nickname'] = $nickname;
        echo json_encode($return_array);
        exit;
    

    }

    public function menu_login_info(){
        $return_array = array();
        
        $return_array = $this->member_info();
        if($return_array['res_code'] !='0000')
        {
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('lesson_mdl');
        $next_schedule = $this->lesson_mdl->checked_nextclass_by_wm_uid($return_array['data']['user_info']['wm_uid']);
        
        $return_array['data']['next_schedule'] = $next_schedule;
        echo json_encode($return_array);
        exit;
    }

    // 회원 차단
    public function regist_member_block()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "blocked_wiz_id" => trim($this->input->post('blocked_wiz_id')),
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

        $this->load->model('member_mdl');

        $blocked_wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['blocked_wiz_id']);

        if(!$blocked_wiz_member)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0105";
            $return_array['data']['err_msg'] = "상대방 회원 정보가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        $blocked_uid = $blocked_wiz_member['wm_uid'];
        $blocker_uid = $wiz_member['wm_uid'];

        $check = $this->member_mdl->checked_block_member($blocker_uid,$blocked_uid);
        
        if(!$check)
        {
            $note_inserted_id = $this->member_mdl->checked_block_note_member($blocker_uid,$blocked_uid);
            if($note_inserted_id)
            {
                $note_inserted_id = $note_inserted_id['id'];
            }
            else
            {
                $params = [
                    'blocker_id' => $blocker_uid,
                    'blocked_id' => $blocked_uid,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                // 쪽지차단
                $note_inserted_id = $this->member_mdl->insert_mint_note_block($params);
            }
            
            // 회원차단
            $params = [
                'blocker_uid' => $blocker_uid,
                'blocked_uid' => $blocked_uid,
                'regdate' => date('Y-m-d H:i:s'),
                'note_block_id' => $note_inserted_id
            ];
            
            $result = $this->member_mdl->insert_wiz_member_block($params);
            
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
            $return_array['data']['err_code'] = "0113";
            $return_array['data']['err_msg'] = "이미 차단된 회원입니다.";
            echo json_encode($return_array);
            exit;
        }


        $return_array['res_code'] = '0000';
        $return_array['msg'] = "차단처리 되었습니다.";
        echo json_encode($return_array);
        exit;
    }


    
    // 회원 차단 해지
    public function delete_member_block()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "blocked_wiz_id" => trim($this->input->post('blocked_wiz_id')),
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

        $this->load->model('member_mdl');

        $blocked_arr = explode(',',$request['blocked_wiz_id']);

        foreach($blocked_arr as $val)
        {
            $blocked_wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($val);

            if(!$blocked_wiz_member)
            {
                continue;
            }
    
            $blocked_uid = $blocked_wiz_member['wm_uid'];
            $blocker_uid = $wiz_member['wm_uid'];
    
            $check = $this->member_mdl->checked_block_member($blocker_uid,$blocked_uid);
            
            if($check)
            {
                $this->member_mdl->update_wiz_member_block($blocker_uid,$blocked_uid);
            }
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "차단해제 되었습니다.";
        echo json_encode($return_array);
        exit;
    }

    
    
    // 회원 차단 리스트
    public function member_block_list_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "nickname" => trim($this->input->post('nickname')) ? trim($this->input->post('nickname')):'',
            'start' => $this->input->post('start') ? $this->input->post('start') :0,
            'limit' => $this->input->post('limit') ? $this->input->post('limit') :10
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

        $this->load->model('member_mdl');
        $where = '';
        if($request['nickname'])
        {
            $where = ' AND wm.nickname LIKE "%'.$request['nickname'].'%" ';
        }
        
        $count = $this->member_mdl->count_member_block($wiz_member['wm_uid'],$where);

        if($count['cnt'] < 1)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        $where.= ' LIMIT '.$request['start'].', '.$request['limit'];
        $list = $this->member_mdl->list_member_block($wiz_member['wm_uid'],$where);

        $return_array['data']['list'] = $list;
        $return_array['data']['total_cnt'] = $count['cnt'];
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        echo json_encode($return_array);
        exit;
    }

    public function update_survey()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "info1" => trim($this->input->post('info1')),
            'info2' => trim($this->input->post('info2')),
            'info3' => trim($this->input->post('info3')),
            'etc' => trim($this->input->post('etc')),           // url
            'device' => trim($this->input->post('device')),
            'chuchun' => trim($this->input->post('chuchun')),
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

        if($request['etc'] == 'http://')
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = '정상적인 URL 주소를 입력해주세요.';
            echo json_encode($return_array);
            exit;
        }

        $check = $this->member_mdl->check_member_survry($wiz_member['wm_uid']);
        if($check['cnt'] > 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0111";
            $return_array['data']['err_msg'] = "이미 작성완료된 회원입니다.";
            echo json_encode($return_array);
            exit;
        }

        //추천인 아이디가 정말 민트회원인지 체크
        if($request['info1'] == '6' && $request['chuchun'])
        {
            $chuchun = $this->member_mdl->check_recommented_member($request['chuchun'], $request['wiz_id']);
            if($chuchun['cnt'] < 1)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0112";
                $return_array['data']['err_msg'] = "유효한 추천인이 아닙니다.";
                echo json_encode($return_array);
                exit;
            }
        }
            
        $insertParam = [
            'uid'   => $wiz_member['wm_uid'],
            'info1' => $request['info1'],
            'info2' => $request['info2'],
            'info3' => $request['info3'],
            'etc'   => $request['etc'],
            'device' => $request['device'],
            'chuchun' => $request['chuchun'],
            'regdate' => date('Y-m-d H:i:s'),
        ];

        $updateMemberParam = [
            'chuchun' => $request['chuchun'],
            'update_yn' => 'Y',
        ];

        $result = $this->member_mdl->insert_wiz_member_info($insertParam,$updateMemberParam, $wiz_member['wm_uid']);
        
        if($result < 1)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        // 지급포인트 구분
        $point = array(
            'uid' => $wiz_member['wm_uid'],
            'point' => common_point_standard('join_survey'),
            'pt_name'=> '회원가입 추가정보 등록 포인트', 
            'kind'=> 'n', 
            'regdate' => date("Y-m-d H:i:s")
        );
        
        $this->load->model('point_mdl');
        $this->point_mdl->set_wiz_point($point);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "설문조사가 성공적으로 등록되었습니다.";
        echo json_encode($return_array);
        exit;
    }

    
    // 타회원 게시물리스트
    public function member_board_list_()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "target_wiz_id" => trim($this->input->post('target_wiz_id')),
            "search_key" => trim($this->input->post('search_key')),
            "search_keyword" => trim($this->input->post('search_keyword')),
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mb.mb_unq",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
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

        $target_wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['target_wiz_id']);
        
        if(!$target_wiz_member)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0110";
            $return_array['data']['err_msg'] = "해당하는 정보를 찾을 수 없습니다,";
            echo json_encode($return_array);
            exit;
        }

      
        /*
            알림시 표기되는 이름 추천회원 닉네임
            우선순위 1. 닉네임 , 2. 영어이름 3. 한글이름
        */
        $target_nickname = "";
        if($wiz_member["wm_nickname"])
        {
            $target_nickname = $target_wiz_member["wm_nickname"];
        }
        else
        {
            $target_nickname = ($target_wiz_member['wm_ename']) ? $target_wiz_member['wm_ename'] : $target_wiz_member['wm_name'];
        }

        
        $this->load->model('board_mdl');
        $search = [];
        if($request['search_key'] && $request['search_keyword'])
        {
            if($request['search_key'] == "mb.content")
            {
                $select_col_content = ", mb.content as mb_content";
            }

            if(strpos($request['search_keyword'],' ') !== false)
            {
               // $index = "USE INDEX(idx_table_code)";
                array_push($search, $request['search_key']." like '%".$request['search_keyword']."%'");
            }
            else
            {
                //$index = "USE INDEX(idx_table_code)";

                if($request['search_key'] == "mb.content")
                {
                    $index = "";
                    $count_index = "";
                }
                array_push($search, "match(".$request['search_key'].") against ('*".$request['search_keyword']."*' IN BOOLEAN MODE)");
            }
        
        }

        $where = " WHERE mb.wiz_id= '".$request['target_wiz_id']."' AND (mb.table_code BETWEEN 1100 AND 1199 OR mb.table_code BETWEEN 1300 AND 1399) AND mbn.anonymous_yn ='N' AND mb.name_hide = 'N'";

        $where_search = "";

        if($search)
        {
            $where_search .= implode(" AND ", $search);
            $where .= sprintf(" AND %s", $where_search);
        }
        
        $count_index .= ' JOIN mint_boards_name mbn ON mbn.table_code=mb.table_code ';
        $list_cnt = $this->board_mdl->list_count_board($count_index, $where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['target_nickname'] = $target_nickname;
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
        
        $order_tmp = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        $order = ($request['sec_order_field'] && $request['sec_order']) ? sprintf($order_tmp." , %s %s", $request['sec_order_field'], $request['sec_order']) : $order_tmp."";
        
        $list_board = $this->board_mdl->list_board($index, $where, $order, $limit, $select_col_content);
        $result = board_list_writer($list_board, ($request['search_key'] == "mb.content" && $request['search_keyword']) ? $request['search_keyword'] : NULL, NULL, NULL, array('content_del'=>true));

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['target_nickname'] = $target_nickname;
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
    }


    
    // 타회원 댓글리스트
    public function member_reply_list_()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "target_wiz_id" => trim($this->input->post('target_wiz_id')),
            "search_keyword" => trim($this->input->post('search_keyword')),
            "start" => trim($this->input->post('start')),
            "limit" => trim($this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "mbc.mb_unq",
            "order" => ($this->input->post('order')) ? trim(strtoupper($this->input->post('order'))) : "DESC",
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

        $target_wiz_member = $this->member_mdl->get_wiz_member_by_wiz_id($request['target_wiz_id']);
        
        if(!$target_wiz_member)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0110";
            $return_array['data']['err_msg'] = "해당하는 정보를 찾을 수 없습니다,";
            echo json_encode($return_array);
            exit;
        }

        /*
            알림시 표기되는 이름 추천회원 닉네임
            우선순위 1. 닉네임 , 2. 영어이름 3. 한글이름
        */
        $target_nickname = "";
        if($wiz_member["wm_nickname"])
        {
            $target_nickname = $target_wiz_member["wm_nickname"];
        }
        else
        {
            $target_nickname = ($target_wiz_member['wm_ename']) ? $target_wiz_member['wm_ename'] : $target_wiz_member['wm_name'];
        }


        
        $this->load->model('board_mdl');
        $search = [];
        if($request['search_keyword'])
        {
            array_push($search, "match(mbc.comment) against ('*".$request['search_keyword']."*' IN BOOLEAN MODE)");
        }

        $where = " WHERE mbc.writer_id= '".$request['target_wiz_id']."' AND (mbc.table_code BETWEEN 1100 AND 1199 OR mbc.table_code BETWEEN 1300 AND 1399) AND mbc.table_code NOT IN (1127, 1129)  AND mbn.anonymous_yn ='N' ";

        $where_search = "";

        if($search)
        {
            $where_search .= implode(" AND ", $search);
            $where .= sprintf(" AND %s", $where_search);
        }
        
        $count_index = ' JOIN mint_boards_name mbn ON mbn.table_code=mbc.table_code ';
        $list_cnt = $this->board_mdl->list_count_comment($count_index, $where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['target_nickname'] = $target_nickname;
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
        
        $order_tmp = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        $order = ($request['sec_order_field'] && $request['sec_order']) ? sprintf($order_tmp." , %s %s", $request['sec_order_field'], $request['sec_order']) : $order_tmp."";
        
        $list_board = $this->board_mdl->list_comment($where, $order, $limit);
        $result = board_list_writer($list_board, ($request['search_key'] == "mb.content" && $request['search_keyword']) ? $request['search_keyword'] : NULL);

        if($result < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['target_nickname'] = $target_nickname;
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
    }

    public function trace()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "is_app" => trim($this->input->post('is_app')),   // pc, mobile, app
            "table_code" => trim($this->input->post('table_code')),
            "p_self" => trim($this->input->post('p_self')),
        );

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('member_mdl');

        $ver = 0;
        if($request['is_app'] == 'mobile') $ver = 1;
        elseif($request['is_app'] == 'app') $ver = 2;

        $admin = base_get_login_admin_id();
        $param = [
            'uid' => $wiz_member['wm_uid'],
            'wiz_id' => $wiz_member['wm_wiz_id'],
            'admin_id' => $admin ? $admin:'',
            'ip' => $_SERVER["REMOTE_ADDR"],
            'is_mobile' => $ver,
            'regdate' => date('Y-m-d H:i:s'),
            'php_self' => '/'.$request["p_self"],
            'table_code' => $request["table_code"],
        ];

        $this->member_mdl->insert_trace_member_log($param);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        echo json_encode($return_array);
        exit;
    }

    public function update_member_token()
    {
        $return_array = array();

        $request = array(
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "is_app" => trim($this->input->post('is_app')),   // ANDROID , IOS, PC, MOBILE
            "token" => trim($this->input->post('token')),
        );

        log_message('error', 'update_member_token: '.$request['wiz_id'].' / '.$request['is_app'].' / '.$request['authorization'].' / '.$request['token']);
     

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('member_mdl');

        $this->member_mdl->update_member_token($wiz_member['wm_uid'], $request['is_app'], $request['token']);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        echo json_encode($return_array);
        exit;

    }


    /* 본인 스케쥴을 조회하는것이 맞는지 체크  */
    public function checked_schedule_by_wiz_member()
    {
        $return_array = array();    

        $request = array(
            'sc_id' => $this->input->post('sc_id') ? $this->input->post('sc_id') : null,         
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
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

        $this->load->model('member_mdl');
        
        $result = $this->member_mdl->checked_schedule_by_wiz_member($request['sc_id'], $wiz_member['wm_uid']);
        
        $now = date('Y-m-d H:i:s');
        $start_day = date("Y-m-d H:i", strtotime("-9 hours -2 minutes", strtotime($result['ws_startday'])));
        $end_day = date("Y-m-d H:i", strtotime("-9 hours +2 minutes", strtotime($result['ws_endday'])));

        $room_start_day = date("Y-m-d H:i", strtotime("-9 hours -5 minutes", strtotime($result['ws_startday'])));
        // room_end_day > 11:59:59 초  형태로 돼있어서 짤라내면 59-2 > 57분
        $room_end_day = date("Y-m-d H:i", strtotime("-9 hours -2 minutes", strtotime($result['ws_endday'])));

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
        $return_array['msg'] = "";
        $return_array['data']['list'] = $result;
        $return_array['data']['list']['start_day'] = $start_day;
        $return_array['data']['list']['end_day'] = $end_day;
        $return_array['data']['list']['room_start_day'] = $room_start_day;
        $return_array['data']['list']['room_end_day'] = $room_end_day;
        echo json_encode($return_array);
        exit;
        
    }

    /**
     * 회원 자신이 획득한 트로피 목록을 가져온다
     */
    public function wiz_member_get_tropy_list_()
    {
        $return_array = array();    

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id"        => trim($this->input->post('wiz_id')),
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

        $this->load->model('member_mdl');
        
        $result = $this->member_mdl->get_trophy_list($wiz_member['wm_uid']);
        if(!$result)
        {
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }

        foreach($result as $key=>$val)
        {
            $result[$key]['mqut_regdate'] = date('Y-m-d' , strtotime($val['mqut_regdate'])).' '.getTime_PM_AM($val['mqut_regdate']);
        }

        $return_array['res_code']     = '0000';
        $return_array['msg']          = "트로피 목록을 불러왔습니다.";
        $return_array['data']['list'] = $result;
        echo json_encode($return_array);
        exit;
    }
    
    /**
     * 마이페이지 회원 정보
     */
    public function usage_user_page()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id"        => $this->input->post('wiz_id'),
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

        $list = array();

        $this->load->model('payment_mdl');
        $this->load->model('coupon_mdl');
        $this->load->model('point_mdl');
        $this->load->model('member_mdl');
        $this->load->model('badge_mdl');
        $this->load->model('leveltest_mdl');
        $this->load->model('mset_mdl');

        // 최근 본 레벨테스트 시험 레벨
        $list['leveltest'] = 0;
        $leveltest = $this->leveltest_mdl->get_leveltest_new_info($wiz_member['wm_uid']);
        if($leveltest)
        {
            $lev_name = explode(' ', $leveltest['wl_lev_name']);
            $list['leveltest'] = $lev_name[1] ? $lev_name[1] : 0;
        }

        // 최근 본 MSET테스트 시험 레벨
        $mset = $this->mset_mdl->get_mset_new_info($wiz_member['wm_uid']);
        $list['mset'] = ($mset) ? $mset['mmr_overall_level'] : 0;

        // 수강 결제 카운트
        $where_lesson = "WHERE wl.uid = '{$wiz_member['wm_uid']}' AND wl.payment!='coupon:'";
        $lesson = $this->payment_mdl->list_count_lesson_pay("", $where_lesson);
        $list['lesson'] = ($lesson) ? $lesson['cnt'] : 0;

        // 보유 수업 변환권 카운트
        $conversion = $this->coupon_mdl->list_count_usage_coupon_by_uid_remain($wiz_member['wm_uid']);
        $list['conversion'] = ($conversion) ? $conversion['cnt'] : 0;

        // 오답지우기 내역
        $where_ahop_remove = "WHERE uid = '{$wiz_member['wm_uid']}' AND used = ''";
        $ahop_remove = $this->member_mdl->list_count_wiz_member_correct_gift($where_ahop_remove);
        $list['ahop_remove'] = ($ahop_remove) ? $ahop_remove['cnt'] : 0;

        // 보유 뱃지 카운트
        $badge = $this->badge_mdl->get_user_badge_count($wiz_member['wm_uid']);
        $list['badge'] = ($badge) ? $badge['cnt'] : 0;
        
        // 보유 트로피 카운트
        $trophy = $this->member_mdl->get_count_trophy_list($wiz_member['wm_uid']);
        $list['trophy'] = ($trophy) ? $trophy['cnt'] : 0;

        $return_array['res_code']     = '0000';
        $return_array['msg']          = "목록조회성공";
        $return_array['data']['info'] = $list;
        echo json_encode($return_array);
        exit;
    }

    public function usage_user_category_list_()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => $this->input->post('wiz_id'),
            "type" => $this->input->post('type'),
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

        $this->load->model('point_mdl');
        $result = $this->point_mdl->list_usage_point_search_category_by_uid($wiz_member['wm_uid']);

        $search_category = array();
        $search_category[''] = '전체';
        foreach($result as $key => $value){
            $search_category[$value['wp_kind']] = set_point_category_name($value['wp_kind']);
        }

        if($result)
        {
            $return_array['res_code']       = '0000';
            $return_array['msg']            = "목록조회성공";
            $return_array['data']['search_category'] = $search_category;
            echo json_encode($return_array);
            exit;

        }else{
            $return_array['res_code']         = '0900';
            $return_array['msg']              = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg']  = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
    }

    /**
     * 마이페이지 정보 리스트 가져오기
     * $request['type']에 따라 가져오는 정보가 다름
     */
    public function usage_list_()
    {
        $retrun_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => $this->input->post('wiz_id'),
            "type" => $this->input->post('type') ? $this->input->post('type') : NULL,
            "search_key" => $this->input->post('search_key') ? $this->input->post('search_key') : '',  
            "search_keyword" => $this->input->post('search_keyword') ? $this->input->post('search_keyword') : '',  
            "start" => trim((int)$this->input->post('start')),  
            "limit" => trim((int)$this->input->post('limit')),
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "",
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

        $wiz_member = base_get_wiz_member();

        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $where = "";
        $limit = "";
        $order = "";

        if($request['type'] == 'conversion')
        {
            // 수업 변환권 사용 내역
            if($request['search_key'] == 'Y')
            {

                // 사용 내역
                $where = "WHERE wclrl.uid = '{$wiz_member['wm_uid']}' AND wclrl.type = '1'";

                $this->load->model('coupon_mdl');
                $count = $this->coupon_mdl->list_count_usage_coupon_by_uid_used($wiz_member['wm_uid']);

                if($count['cnt'] == 0)
                {
                    $return_array['res_code']         = '0900';
                    $return_array['msg']              = "프로세스오류";
                    $return_array['data']['err_code'] = "0201";
                    $return_array['data']['err_msg']  = "수업 변환권 사용 내역이 없습니다.";
                    echo json_encode($return_array);
                    exit;
                }

                
                $request['order_field'] = ($request['order_field']) ? $request['order_field'] : "wclrl.idx";
                
                if($request['limit'] > 0)
                {
                    $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
                }
                
                $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

                $result = $this->coupon_mdl->list_usage_coupon_by_uid_used($where, $order, $limit, $wiz_member);

                if($result)
                {
                    $now = strtotime(date("Y-m-d"));
                    foreach($result as $key=>$value)
                    {
                        // 종료된 강의 체크
                        $result[$key]['finish_lesson'] = false;
                        if($now >= strtotime($value['wl_endday'])) $result[$key]['finish_lesson'] = true;
                    }

                    $return_array['res_code']          = '0000';
                    $return_array['msg']               = "목록조회성공";
                    $return_array['data']['total_cnt'] = $count['cnt'];
                    $return_array['data']['list']      = $result;
                    echo json_encode($return_array);
                    exit;
                }
                
            }
            else if($request['search_key'] == 'N')
            {
                // 미사용 수업변환권 내역
                $this->load->model('coupon_mdl');
                $count = $this->coupon_mdl->list_count_usage_coupon_by_uid_remain($wiz_member['wm_uid']);

                if($count['cnt'] == 0)
                {
                    $return_array['res_code']         = '0900';
                    $return_array['msg']              = "프로세스오류";
                    $return_array['data']['err_code'] = "0201";
                    $return_array['data']['err_msg']  = "수업 변환권 보유 내역이 없습니다.";
                    echo json_encode($return_array);
                    exit;
                }
                
                $where = "WHERE wc.uid='{$wiz_member['wm_uid']}' AND wmc.gubun='2' AND wmc.is_entire='0' AND wclrl.idx IS NULL AND wc.is_delete='0'";

                $request['order_field'] = ($request['order_field']) ? $request['order_field'] : "wc.regdate";

                if($request['limit'] > 0){   
                    $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
                }

                $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

                $result = $this->coupon_mdl->list_usage_coupon_by_uid_remain($where, $order, $limit, $wiz_member);
                if($result)
                {
                    $total_conversion = 0;
                    foreach($result as $value)
                    {
                        //총 수업변환 횟수 구하기
                        $total_conversion = $total_conversion + $value['wmc_release_cnt'];
                    }

                    $return_array['res_code']                 = '0000';
                    $return_array['msg']                      = "목록조회성공";
                    $return_array['data']['total_cnt']        = $count['cnt'];
                    $return_array['data']['total_conversion'] = $total_conversion;
                    $return_array['data']['list']             = $result;
                    echo json_encode($return_array);
                    exit;
                }
            
            }
            else
            {
                $return_array['res_code']         = '0900';
                $return_array['msg']              = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg']  = "잘못된 접근 입니다.";
                echo json_encode($return_array);
                exit;
            }
            
        }
        else if($request['type'] == 'coupon')
        {
            // 출석부 쿠폰 사용 내역
            $this->load->model('coupon_mdl');
            $count = $this->coupon_mdl->list_count_wiz_lesson_coupon_by_uid($wiz_member['wm_uid']);

            if($count['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "출석부 쿠폰 사용 내역이 없습니다.";
                echo json_encode($return_array);
                exit;
            }

            $where = "WHERE wl.uid = '{$wiz_member['wm_uid']}' AND wl.payment LIKE 'coupon%'";
            
            $request['order_field'] = ($request['order_field']) ? $request['order_field'] : "wl.lesson_id";

            if($request['limit'] > 0)
            {
                $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
            }
            
            $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

            $result = $this->coupon_mdl->list_wiz_lesson_coupon_by_uid($where, $order, $limit);
            if($result)
            {
                // 쿠폰 수업 등록 여부 체크
                $now = strtotime(date("Y-m-d"));
                foreach($result as $key=>$value)
                {
                    $result[$key]['register'] = 1;

                    if($value['wl_plandate'] == '0000-00-00 00:00:00' && strtotime(date('Y-m-d')) > strtotime($value['wc_validate']))
                    {
                        //사용하지않고 유효기간이 종료되었을 경우
                        $result[$key]['register'] = 3;
                    }

                    if($value['wl_student_su'] < 3 && $value['wl_pay_ok'] == "Y"
                        && $value['wl_cl_id'] == '0' && $value['wl_payment'] == 'coupon:'
                        && $value['wl_endday'] == "0000-00-00" && strpos($value['wl_cl_name'], "첨삭") === false
                        && $value['wl_plandate'] == '0000-00-00 00:00:00' && $result[$key]['register'] == 1){
                        $result[$key]['register'] = 2;
                    }

                    // 종료된 강의 체크
                    $result[$key]['finish_lesson'] = false;
                    if($now >= strtotime($value['wl_endday'])) $result[$key]['finish_lesson'] = true;
                }

                $return_array['res_code'] = '0000';
                $return_array['msg'] = "목록조회성공";
                $return_array['data']['total_cnt'] = $count['cnt'];
                $return_array['data']['list'] = $result;
                echo json_encode($return_array);
                exit;
            }

        }
        else if($request['type'] == 'point')
        {
            // 포인트 사용 내역
            $this->load->model('point_mdl');

            $where = "WHERE uid = '{$wiz_member['wm_uid']}' AND showYn != 'n' ";
            
            if($request['search_key'] == 'point')
            {
                $inequality = ($request['search_keyword'] == 'save') ? ">" : "<";
                $where .= " AND ".$request['search_key']." ".$inequality." 0 ";
            }
            else if($request['search_key'] && $request['search_keyword'])
            {
                $where .= " AND ".$request['search_key']." LIKE '%{$request['search_keyword']}%' ";
            }

            $count = $this->point_mdl->list_count_wiz_point_by_uid($where);

            if($count['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "포인트 사용 내역이 없습니다.";
                echo json_encode($return_array);
                exit;
            }

            $request['order_field'] = ($request['order_field']) ? $request['order_field'] : "wp.pt_id";
            
            if($request['limit'] > 0){   
                $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
            }
            
            $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
            
            $result = $this->point_mdl->list_usage_point_by_uid($where, $order, $limit);

            // 포인트 총 누적, 사용
            $total_info = $this->point_mdl->total_usage_point_by_uid($wiz_member['wm_uid']);

            if($result)
            {
                $return_array['res_code']           = '0000';
                $return_array['msg']                = "목록조회성공";
                $return_array['data']['total_cnt']  = $count['cnt'];
                $return_array['data']['list']       = $result;
                $return_array['data']['total_info'] = $total_info;
                echo json_encode($return_array);
                exit;
            }

        }else if($request['type'] == 'ahop_remove'){
        
            
            $this->load->library('CI_Benchmark');
            $this->benchmark->mark('banner_start');


            // 오답지우기 내역
            $this->load->model('member_mdl');

            $where = "WHERE uid = '{$wiz_member['wm_uid']}'";
            
            // AND used = 'Y' 
            if($request['search_key']) {
                if($request['search_key'] == 'Y') $where .= " AND used = '{$request['search_key']}' ";
                if($request['search_key'] == 'N') $where .= " AND used = '' ";
            }
            
            if($request['search_keyword']) $where .= " AND comment LIKE '%{$request['search_keyword']}%' ";

            $count = $this->member_mdl->list_count_wiz_member_correct_gift($where);

            if($count['cnt'] == 0)
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0201";
                $return_array['data']['err_msg'] = "오답지우기 내역이 없습니다.";
                echo json_encode($return_array);
                exit;
            }

            // 오답지우기 사용횟수
            // $where_used = "WHERE uid = '{$wiz_member['wm_uid']}' AND wmcg.used = 'Y' ";
            // $count_used = $this->member_mdl->list_count_wiz_member_correct_gift($where_used);

            // 오답지우기 남은횟수
            $where_available = "WHERE uid = '{$wiz_member['wm_uid']}' AND wmcg.used != 'Y' ";
            $count_available = $this->member_mdl->list_count_wiz_member_correct_gift($where_available);

            $request['order_field'] = ($request['order_field']) ? $request['order_field'] : "wmcg.cidx";
            
            if($request['limit'] > 0){   
                $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
            }
            
            $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);

            $result = $this->member_mdl->list_wiz_member_correct_gift($where, $order, $limit);
            
            if($result)
            {
                $return_array['res_code'] = '0000';
                $return_array['msg'] = "목록조회성공";
                $return_array['data']['total_cnt'] = $count['cnt'];
                // $return_array['data']['used_cnt'] = $count_used['cnt'];
                $return_array['data']['available_cnt'] = $count_available['cnt'];
                $return_array['data']['list'] = $result;
                echo json_encode($return_array);
                exit;
            }
        
        }else if($request['type'] == 'trophy_badge'){
            
            // 트로피 리스트 내역
            $this->load->model('member_mdl');
            $count_trophy = $this->member_mdl->get_count_trophy_list($wiz_member['wm_uid']);
            $result_trophy = $this->member_mdl->get_trophy_list($wiz_member['wm_uid']);
            
            // 뱃지 리스트 내역
            $where_badge_count = " WHERE wb.type != 'admin' AND wmb.use_yn IS NOT NULL ORDER BY wb.id";
            $join_where_badge = ' AND uid='.(int)$wiz_member['wm_uid'];

            $this->load->model('badge_mdl');
            $count_badge = $this->badge_mdl->list_count_badge($where_badge_count, $join_where_badge);

            $where_badge = " WHERE wb.type != 'admin' ORDER BY wb.id";
            $result_badge = $this->badge_mdl->list_badge($where_badge, $join_where_badge);
            
            foreach($result_badge as $key=>$val)
            {
                $result_badge[$key]['img'] = Thumbnail::$cdn_default_url . '/' . $this->upload_path_badge . $val['img'];
                $result_badge[$key]['img_big_on'] = Thumbnail::$cdn_default_url . '/' . $this->upload_path_badge . $val['img_big_on'];
                $result_badge[$key]['img_big_off'] = Thumbnail::$cdn_default_url . '/' . $this->upload_path_badge . $val['img_big_off'];
            }

            // 인삿말 줄바꿈
            $wiz_member["wm_greeting_br"] = nl2br($wiz_member['wm_greeting']);

            $return_array['data']['user'] = $wiz_member;
            $return_array['data']['trophy']['list'] = $result_trophy;
            $return_array['data']['trophy']['total_count'] = $count_trophy['cnt'];
            $return_array['data']['badge']['list'] = $result_badge;
            $return_array['data']['badge']['total_count'] = $count_badge['cnt'];
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "뱃지, 트로피 목록을 불러왔습니다.";
            echo json_encode($return_array);
            exit;
            
        }

        echo json_encode($retrun_array);
        exit;
    }

    /**
     * 인삿말 저장
     */
    public function update_greeting()
    {
        $return_array = array();

        $request = array(
            "authorization" => trim($this->input->post('authorization')),
            "wiz_id" => trim(strtolower($this->input->post('wiz_id'))),
            "greeting" => $this->input->post('greeting') ? $this->input->post('greeting') : null,
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


        $member = array(
            "greeting" => $request['greeting'],
        );
        
        $this->load->model('member_mdl');
        $result = $this->member_mdl->update_member($member, $request['wiz_id']);
        
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
            $return_array['msg'] = "인삿말이 변경됐습니다.";
            echo json_encode($return_array);
            exit;
        }
    }
}







