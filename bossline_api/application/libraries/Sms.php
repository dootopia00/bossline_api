<?php

class Sms
{
    public static $callback = '16440512';
    public static $sender_key = '6c2944ad9c8a25274c8d4f2add8f91b8437f5ba0';
    public static $biztalk_sms_file_loc = '/home/emma/attach_file/mmsmt/';		// http://sms.mint05.com/attach/파일명 으로 웹에서 보기가능. 심볼릭링크 걸어놓음
	public static $biztalk_sms_file_url = 'http://sms.mint05.com/sms_file.php';
	public static $biztalk_sms_file_link_path = 'http://sms.mint05.com/attach/';

    /**
     * 알림톡 전송
     * EX) sms::send_atalk('123-1234-1234','MINT06000A', [
     *       'content' => 'TEST',
     *       'sms_push_code' => 123,
     *       'uid' => 119003,
     *       'wiz_id' => 'hjk081212@gmail.com',
     *       'name' =>'TEST',
     *   ]);
	 */
    public static function send_atalk($receiver_phone, $templete_code, $option=array(), $attachment=array())
    {
        if(!$templete_code) return array('state'=>false, 'msg'=>'잘못된 템플릿입니다.');
        if(!$receiver_phone) return array('state'=>false, 'msg'=>'전화번호를 입력해주세요.');
        
        $CI =& get_instance();
        $CI->load->model('sms_mdl');

        // 알림톡 템플릿 조회
        $tpl = $CI->sms_mdl->get_atalk_templete($templete_code);

        if(!$tpl) return array('state'=>false, 'msg'=>'템플릿 정보가 없습니다.');

        // 발신번호 설정
        $callback = $option['callback'] ? $option['callback']:self::$callback;

        $replace_to = array(
            '#{name}',
            '#{time}',
            '#{2ndtime}',
            '#{3rdtime}',
            '#{date}',
            '#{tu_name}',
            '#{cl_time}',
            '#{iPoint}',
            '#{startdate}',
            '#{enddate}',
            '#{vbank_price}',
            '#{bank_number}',
            '#{lastday}',
            '#{board_name}',
            '#{nickname}',
            '#{board_link}',
            '#{class_name}',
            '#{url}',
            '#{lesson_id}',
        );

        $replace_with = array(
            $option['name'],
            $option['time'],
            $option['2ndtime'],
            $option['3rdtime'],
            $option['date'],
            $option['tu_name'],
            $option['cl_time'],
            $option['iPoint'],
            $option['startdate'],
            $option['enddate'],
            $option['vbank_price'],
            $option['bank_number'],
            $option['lastday'],
            $option['board_name'],
            $option['nickname'],
            $option['board_link'],
            $option['class_name'],
            $option['url'],
            $option['lesson_id'],
        );

        // 임의변수 치환
        $tpl['content'] = str_replace($replace_to, $replace_with, $tpl['content']);


        // 다중 수신폰번호 ; 문자로 구분된 문자를 받아서 루프로 전송
        $phone_arr = explode(';',$receiver_phone);

        foreach($phone_arr as $phone)
        {
            $phone = preg_replace('/[^0-9]/','',$phone);

            if(empty($phone)) continue;

            $kko_btn_type = '';
            $kakaoBtn = '';
            if(!empty($attachment))
            {
                //kko_btn_type 문자열포맷-1, json:2, xml:3
                $kakaoBtn = $attachment['kakaoBtn'];
                $kko_btn_type = $attachment['kko_btn_type'];
            }

            $insert_param = [
                'date_client_req' => $option['reserve_date'] ? $option['reserve_date']:date('Y-m-d H:i:s'),     // 전송예약시간
                'template_code'   => $templete_code,    // 템플릿코드
                'content'         => $tpl['content'],   // 템플릿내용
                'recipient_num'   => $phone,            // 수신번호
                'msg_status'      => '1',               // 1-전송대기, 2-결과대기, 3-완료
                'subject'         => $tpl['subject'],   // 템플릿제목
                'callback'        => $callback,         // 발신번호
                'sender_key'      => self::$sender_key, // 카카오톡 알림톡 발신 프로필 키
                'kko_btn_type'    => $kko_btn_type,     // 버튼데이터형식
                'kko_btn_info'    => $kakaoBtn,         // 버튼정보
                'msg_type'        => '1008',            // 메시지 종류 1008-카카오톡 알림톡, 1009-카카오톡 친구톡
            ];

            // index.php 에 설정. 로컬일시 문자보내지않음
            if(ISTESTMODE === false)
            {
                // 비즈톡 디비에 insert
                $result = $CI->sms_mdl->insert_atalk($insert_param);

                if($result > 0)
                {
                    /* 마스터 DB의 알림톡 테이블에 데이터 저장
                    * 이 DB의 데이터를 참조하여 163웹서버에서 크론(alimtalk_log_update) 에서 상태 처리 및 미전송된 알림톡은 SMS로 전송해주기도 한다.
                    */
                    $log_param = [
                        'uid'              => $option['uid'] ? $option['uid']:'0',
                        'mt_pr'            => $result,
                        'wiz_id'           => $option['wiz_id'] ? $option['wiz_id']:'',
                        'name'             => $option['name'] ? $option['name']:'',
                        'memo'             => $tpl['content'],
                        'date_client_req'  => $insert_param['date_client_req'],
                        'template_code'    => $templete_code,
                        'recipient_num'    => $phone,
                        'msg_status'       => '1',
                        'tu_name'          => $option['tu_name'] ? $option['tu_name']:'',
                        'cl_time'          => $option['cl_time'] ? $option['cl_time']:'0',
                        'point'            => $option['point'] ? $option['point']:'0',
                        'startdate'        => $option['startdate'] ? $option['startdate']:'',
                        'enddate'          => $option['enddate'] ? $option['enddate']:'',
                        'regdate'          => date('Y-m-d H:i:s'),
                        'sms_push_yn'      => $option['sms_push_yn'] ? 'Y':'N',      // Y 라면 알림톡 수신 실패 시 문자전송
                        'sms_push_code'    => $option['sms_push_code'],         // 알림톡 수신 실패 시 문자전송 할때 전송할 문자템플릿(mint_sms_conf table의 idx)
                        'sms_term_min'     => $option['sms_term_min'],          // 알림톡 보내고 설정한 spare_term_min분이내 sms를 보낸다. 미설정 시 즉시전송
                        'date'             => $option['date'],
                        'time'             => $option['time'],
                    ];
                    $CI->sms_mdl->insert_atalk_log($log_param);
                }
            }
            

        }   // END foreach

        return array('state'=>true);

    }

    /**
     * 문자 전송
     * templete code로 DB에 저장된 템플릿내용을 전송하거나, $option['content']에 내용을 직접 전송
	 */
    public static function send_sms($receiver_phone, $templete_code='', $option=array())
    {
        
        // index.php 에 설정. 로컬일시 문자보내지않음
        if(ISTESTMODE) return array('state'=>true);

        if(!$templete_code && !$option['content']) return array('state'=>false, 'msg'=>'전송할 내용이 없습니다.');
        if(!$receiver_phone) return array('state'=>false, 'msg'=>'전화번호를 입력해주세요.');
        
        $CI =& get_instance();
        $CI->load->model('sms_mdl');

        // 템플릿코드 있으면 조회
        if($templete_code)
        {
            $tpl = $CI->sms_mdl->get_sms_templete($templete_code);

            if(!$tpl) return array('state'=>false, 'msg'=>'문자템플릿 정보가 없습니다.');

            $content = $tpl['sms_content']; // 문자내용
            $callback = $tpl['sms_return_no'] ? $tpl['sms_return_no']:self::$callback;  //발신번호
        }
        else
        {
            $content = $option['content'];  // 문자내용
            $callback = $option['callback'] ? $option['callback']:self::$callback;  //발신번호
        }

        $replace_to = array(
            '#{name}',
            '#{time}',
            '#{2ndtime}',
            '#{3rdtime}',
            '#{date}',
            '#{tu_name}',
            '#{cl_time}',
            '#{iPoint}',
            '#{startdate}',
            '#{enddate}',
            '#{vbank_price}',
            '#{bank_number}',
            '#{lastday}',
            '#{board_name}',
            '#{nickname}',
            '#{board_link}',
            '#{class_name}',
            '%%',
            '[{price}]',
            '[{bank_number}]',
            '[{lastday}]',
            '#{week_name}',
            '#{cl_month}',
            '#{cl_number}',
            '#{user_name}',
            '#{link}',
        );

        $replace_with = array(
            $option['name'],
            $option['time'],
            $option['2ndtime'],
            $option['3rdtime'],
            $option['date'],
            $option['tu_name'],
            $option['cl_time'],
            $option['iPoint'],
            $option['startdate'],
            $option['enddate'],
            $option['vbank_price'],
            $option['bank_number'],
            $option['lastday'],
            $option['board_name'],
            $option['nickname'],
            $option['board_link'],
            $option['class_name'],
            $option['name'],
            $option['price'],
            $option['bank_number'],
            $option['lastday'],
            $option['week_name'],
            $option['cl_month'],
            $option['cl_number'],
            $option['user_name'],
            $option['link'],
        );

        // 임의변수 치환
        $content = str_replace($replace_to, $replace_with, $content);

        if($option['coupon_type'])
        {
            $aCouponTypeList = array('default'=>'일반', 'wemake'=>'위메프');
            $content = str_replace('###', $aCouponTypeList[$option['coupon_type']], $content);
        }

        $str_check = iconv('UTF-8', 'EUC-KR', $content); // EUC-KR
        
        $byte_count = 0;

        /* 
            biztalk service_type (0-SMS MT, 1-CALLBACK URL, 2-MMS MT, 3-LMS, 4–SMS MO, 5-MMS MO)
            0: SMS
            2: MMS
            3: LMS
        */

        // mms 에서 이미지 보낼시에 문자모듈이 참조할수 있는 경로에 파일이 있어야한다.
        if($option['image_name'])
        {
            $service_type = '2';    // 반드시 첨부파일 있어야한다.
        }
        else
        {
            /* 바이트 수 체크 */
            $a = unpack('C*', $str_check);
            foreach ($a as $v)
            {
                ++$byte_count;
            }
            
            $service_type = $byte_count > 90 ? '3':'0';
        }

        $callback = preg_replace('/[^0-9]/','',$callback);

        // 다중 수신폰번호 ; 문자로 구분된 문자를 받아서 루프로 전송
        $phone_arr = explode(';',$receiver_phone);

        $attach_file_group_key = 0;
		//파일 있으면 첨부파일 정보 인서트. 이 함수에 진입전 미리 upload_attach_file함수를 이용하여 파일을 sms서버에 올려놔야한다.
		if($service_type =='2')
		{
            /*  
                MMS 발송 시 첨부 파일 SIZE는 50K이하, 1개 파일에 대한 전송을 권장한다.
                MMS 발송 시 첨부 파일 SIZE가 총 1MB 이상이거나 파일 1개당 300K 이상인 경우, 실패 처리되므로 주의 바란다. (결과코드 ‘3016: 첨부화일 사이즈 제한 실패’)
                em_mmt_file
                attach_file_group_key 필드값으로 첨부파일을 em_mmt_tran과 연결해줘야한다.
            */
            
            $max_group_key = $CI->sms_mdl->row_em_mmt_file_desc();
            $max_group_key = $max_group_key['attach_file_group_key'];

            $attach_file_group_key = (int)$max_group_key + 1;
            
            $file_insert_result = $CI->sms_mdl->insert_biztalk_em_mmt_file(array(
                'attach_file_group_key' => $attach_file_group_key,
                'attach_file_group_seq' => '1',
                'attach_file_seq'       => '1',
                'attach_file_subpath'   => null,
                'attach_file_name'      => $option['image_name'],
            ));

            if($file_insert_result < 0) return array('state'=>false, 'msg'=>'파일첨부에 실패하였습니다.');
        }
        
        $insert_param = [
            'date_client_req' => $option['reserve_date'] ? $option['reserve_date']:date('Y-m-d H:i:s'), // 전송예약시간     
            'callback'        => $callback,   // 발신번호
            'content'         => $content,   
            'service_type'    => $service_type, 
            'broadcast_yn'    => 'N',         // 동보메시지 여부: 같은 메시지를 보낼때 사용. 일단 사용안함으로 연동
            'msg_status'      => '1',         // 1 (전송요구), 2 (큐에 적재, 결과대기 중), 3 (송수신완료)
        ];

        foreach($phone_arr as $phone)
        {
            $insert_id = 0;
            $phone = preg_replace('/[^0-9]/','',$phone);

            if(empty($phone)) continue;

            $insert_param['recipient_num'] = $phone;

            // 2:mms, 3:lms
            if($service_type =='2' || $service_type =='3')
            {
                $insert_param['subject'] = $option['sms_subject'] ? $option['sms_subject']:'민트영어';      // 제목
                $insert_param['attach_file_group_key'] = $attach_file_group_key;
                
                $insert_id = $CI->sms_mdl->insert_biztalk_mms($insert_param);
                $type = $service_type =='2' ? 'mms':'lms';
                
            }
            else
            {
                // sms
                $insert_id = $CI->sms_mdl->insert_biztalk_sms($insert_param);
                $type = 'sms';
            }

            // 메인디비 wiz_call 테이블에 로그 INSERT
            $insert_log_param = [
                'uid'     => $option['uid'] ? $option['uid']:0,
                'wiz_id'  => $option['wiz_id'] ? $option['wiz_id']:'',
                'name'    => $option['name'] ? $option['name']:'',
                'man_id'  => $option['man_id'] ? $option['man_id']:'',
                'man_name'=> $option['man_name'] ? $option['man_name']:'',
                'memo'    => $content,
                'phone'   => $phone,
                'type'    => $type,
                'tran_key'=> $insert_id,
                'regdate' => date('Y-m-d H:i:s'),
                'callback' => $callback,
            ];

            $CI->sms_mdl->insert_sms_log($insert_log_param);
            

        }   // END foreach

        return array('state'=>true);

    }

    // 알림톡 에러코드
    public static function getAtalkStatus($key=''){

		$status_arr = array(
			'1000'=>'성공',
			'2000'=>'전송시간초과',
			'2001'=>'메시지 전송 불가(예기치 않은 오류 발생)',
			'3009'=>'메시지 형식 오류',
			'3014'=>'알수 없는 메시지 상태',
			'3015'=>'msg_type 오류(1008 또는 1009 가 아닌경우)',
			'3023'=>'메시지 문법 오류(JSON형식오류)',
			'3024'=>'발신 프로필 키가 유효하지 않음',
			'3025'=>'메시지 전송 실패(테스트 시,친구 관계가 아닌 경우)',
			'3026'=>'메시지와 템플릿의 일치성 확인시 오류 발생',
			'3027'=>'카카오톡을 사용하지 않는 사용자(전화번호 오류 / 050 안심번호)',
			'3029'=>'메시지가 존재하지 않음',
			'3030'=>'메시지 일련번호가 중복됨',
			'3031'=>'메시지가 비어 있음',
			'3032'=>'메시지 길이 제한 오류(공백 포함1000자)',
			'3033'=>'템플릿을 찾을수 없음',
			'3034'=>'메시지가 템플릿과 일치하지 않음',
            '3036'=>'버튼내용이 등록한 템플릿과 일치 하지 않음',
            '3037'=>'메시지 강조 표기 타이틀이 템플릿과 일치하지 않음',
            '3038'=>'No MatchedTemplate With MessageTypeException',
			'3040'=>'허브 파트너 키가 유효하지 않음',
			'3041'=>'Request Body에서 Name을 찾을수 없음',
			'3042'=>'발신 프로필을 찾을수 없음',
			'3043'=>'삭제된 발신 프로필',
			'3044'=>'차단 상태의 발신 프로필',
			'3045'=>'차단 상태의 옐로아이디',
			'3046'=>'닫힘 상태의 옐로 아이디',
			'3047'=>'삭제된 옐로우 아이디',
			'3048'=>'계약 정보를 찾을수 없음',
			'3049'=>'내부 시스템 오류로 메시지 전송 실패',
			'3050'=>'카카오톡을 사용하지 않는 사용자 72시간 이내에 카카오톡 사용 이력이 없는 사용자 알림톡 차단을 선택한 사용자 친구톡의 경우 친구가 아닌경우',
			'3051'=>'메시지가 발송되지 않은 상태',
			'3052'=>'메시지 확인 정보를 찾을수 없음',
			'3054'=>'메시지 발송 가능한 시간이 아님',
			'3055'=>'메시지 그룹 정보를 찾을 수 없음',
			'3056'=>'메시지 전송 결과를 찾을 수 없음',
			'3060'=>'사용자에게 발송하였으나 수신여부 불투명(polling)',
            '3063'=>'잘못된 파라메터 요청',
            '3064'=>'메시지에 포함된 이미지를 전송할 수 없음(친구톡)',
            '3065'=>'잘못된 파라메터 요청(컨텐츠 내용 깨짐)',
			'9998'=>'시스템 문제가 발생하여 담당자가 확인중(현재 서비스 제공중이 아님)',
			'9999'=>'시스템에 문제가 발생하여 담당자가 확인중(시스템에 알수 없는 오류 발견)',
			'1001'=>'Server Busy(RS 내부 저장 Queue Full)',
			'1002'=>'수신번호 형식 오류',
			'1003'=>'회신번호 형식 오류',
			'1009'=>'CLIENT_MSG_KEY 없음',
			'1010'=>'CLIENT 없음',
			'1012'=>'RECIPIENT_INFO 없음',
			'1013'=>'SUBJECT 없음',
			'1018'=>'전송 권한 없음',
			'1019'=>'TTL 초과',
			'1020'=>'charset conversion error',
			'1099'=>'인증실패',
			'E901'=>'수신번호가 없는 경우',
			'E903'=>'제목 없는 경우',
			'E904'=>'메시지가 없는 경우',
			'E905'=>'회신번호가 없는 경우',
			'E906'=>'메시지키가 없는 경우',
			'E915'=>'중복 메시지',
			'E916'=>'인증서버 차단번호',
			'E917'=>'고객DB차단번호',
			'E918'=>'USER CALLBACK FAIL',
			'E919'=>'발송 제한 시간인 경우,메시지 재발송 처리가 금지 된 경우',
			'E920'=>'서비스 타입이 알림톡인 경우,메시지 테이블에 파일그룹키가 있는 경우',
            'E921'=>'버튼템플릿을 보낼 경우, 전송방식이 매칭되지 않는 경우',
            'E999'=>'기타오류'
        );

        if(!$key) return $status_arr;

        return $status_arr[$key];
    }
    
    // 비즈톡 SMS 에러내용
    public static function sms_error_code($code='')
	{
		$error_code = array(
			'1000' => '성공',
			'2000' => '전송 시간 초과',
			'2001' => '전송 실패 (무선망단)',
			'2002' => '전송 실패 (무선망 -> 단말기단)',
			'2003' => '단말기 전원 꺼짐',
			'2004' => '단말기 메시지 버퍼 풀',
			'2005' => '음영지역',
			'2006' => '메시지 삭제됨',
			'2007' => '일시적인 단말 문제',
			'3000' => '전송할 수 없음',
			'3001' => '가입자 없음',
			'3002' => '성인 인증 실패',
			'3003' => '수신번호 형식 오류',
			'3004' => '단말기 서비스 일시 정지',
			'3005' => '단말기 호 처리 상태',
			'3006' => '착신 거절',
			'3007' => 'Callback URL을 받을 수 없는 폰',
			'3008' => '기타 단말기 문제',
			'3009' => '메시지 형식 오류',
			'3010' => 'MMS 미지원 단말',
			'3011' => '통신사 서버 오류',
			'3012' => '스팸',
			'3013' => '서비스 거부',
			'3014' => '기타',
			'3015' => '전송 경로 없음',
			'3016' => '첨부파일 사이즈 제한 실패',
			'3017' => '발신번호 변작방지 세칙위반',
			'3018' => '휴대폰 가입 이동통신사를 통해 발신번호 변작방지 부가 서비스에 가입된 번호를 발신번호로 사용한 MT 전송 시',
			'3019' => 'KISA or 미래부에서 모든 고객사에 대하여 차단 처리 요청 번호를 발신 번호로 사용한 MT 전송시',
			'1001' => 'Server Busy (RS 내부 저장 Queue Full)',
			'1002' => '수신번호 형식 오류',
			'1003' => '발신번호 형식 오류 (발신번호 변작 방지 세칙 위반)',
			'1004' => 'SPAM',
			'1005' => '사용 건수 초과',
			'1006' => '첨부 파일 없음',
			'1007' => '첨부 파일 있음',
			'1008' => '첨부 파일 저장 실패',
			'1009' => 'CLIENT_MSG_KEY 없음',
			'1010' => 'CONTENT 없음',
			'1011' => 'CALLBACK 없음',
			'1012' => 'RECIPIENT_INFO 없음',
			'1013' => 'SUBJECT 없음',
			'1014' => '첨부 파일 KEY 없음',
			'1015' => '첨부 파일 NAME 없음',
			'1016' => '첨부 파일 크기 없음',
			'1017' => '첨부 파일 Content 없음',
			'1018' => '전송 권한 없음',
			'1019' => 'TTL 초과',
			'1020' => 'charset conversion error',
			'1022' => '발신번호 사전등록제 관련 미등록 발신번호 사용',
			'E900' => '전송키가 없는 경우',
			'E901' => '수신번호가 없는 경우',
			'E902' => '(동보인 경우) 수신번호순번이 없는 경우',
			'E903' => '제목 없는 경우',
			'E904' => '메시지가 없는 경우',
			'E905' => '회신번호가 없는 경우 ',
			'E906' => '메시지키가 없는 경우',
			'E907' => '동보 여부가 없는 경우',
			'E908' => '서비스 타입이 없는 경우',
			'E909' => '전송요청시각이 없는 경우',
			'E910' => 'TTL 타임이 없는 경우',
			'E911' => '서비스 타입이 MMS MT인 경우, 첨부파일 확장자가 없는 경우',
			'E912' => '서비스 타입이 MMS MT인 경우, attach_file 폴더에 첨부파일이 없는 경우',
			'E913' => '서비스 타입이 MMS MT인 경우, 첨부파일 사이즈가 0인 경우',
			'E914' => '서비스 타입이 MMS MT인 경우, 메시지 테이블에는 파일그룹키가 있는데 파일 테이블에 데이터가 없는 경우',
			'E915' => '중복메시지',
			'E916' => '인증서버 차단번호',
			'E917' => '고객DB 차단번호',
			'E918' => 'USER CALLBACK FAIL',
			'E919' => '발송 제한 시간인 경우, 메시지 재발송 처리가 금지 된 경우',
			'E920' => '서비스 타입이 LMS MT인 경우, 메시지 테이블에 파일그룹키가 있는 경우',
			'E921' => '서비스 타입이 MMS MT인 경우, 메시지 테이블에 파일그룹키가 없는 경우',
			'E922' => '동보단어 제약문자 사용 오류',
			'E999' => '기타오류',
		);

		return $code == '' ? $error_code:$error_code[$code];

	}

	/* 
	 * 비즈톡 mms 전송서버로 첨부파일 전송
	 * $FILES: $_FILES 변수에 담긴 파일정보
	**/
	public static function upload_attach_file($FILES)
	{
		$ext_check = explode('.',$FILES['name']);

		$allow_ext_img = array('jpg');
		if(!in_array(strtolower($ext_check[count($ext_check)-1]),$allow_ext_img))
		{
			return array('state'=>false,'msg'=>'jpg 형식 이미지만 첨부해주세요.');
		}
	
		if($FILES['size'] > 300000)
		{
			return array('state'=>false,'msg'=> '300kb 이하의 이미지를 첨부해주세요. 초과 시 전송실패합니다. 권장사항은 50kb 입니다.');
		}
	
		$filename = date('YmdHis') .'_'. rand(0,1000) .'.'. $ext_check[1];
	
		$curlParam_array['upfile'] = '@'.$FILES['tmp_name'];
		$curlParam_array['filename'] = $filename;
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::$biztalk_sms_file_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 60);
		curl_setopt($ch, CURLOPT_TIMEOUT , 60);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlParam_array);
	
		$result = curl_exec($ch);
		curl_close($ch);

		if($result == 'success')
		{
			return array('state'=>true,'filename'=>$filename);
		}

		return array('state'=>false,'msg'=>$result);
	}
    
    
}