<?php
defined("BASEPATH") OR exit("No direct script access allowed");

// 코드형태로 되어있는 결제수단 한글명칭으로 변경
function payment_code_to_str($payment)
{
    $payment = str_replace(':','',$payment);
    $payment_str = '';
    switch($payment)
    {
        case 'vbank':
            $payment_str = '무통장입금(가상계좌)';
            break;
        case 'samsung':
            $payment_str = '삼성페이';
            break;
        case 'hubCard':
        case 'hubcard':
            $payment_str = 'ARS가상번호신용카드';
            break;
        case 'event':
            $payment_str = '이벤트';
            break;
        case 'coupon':
            $payment_str = '쿠폰';
            break;
        case 'cash':
            $payment_str = '무통장입금(일반)';
            break;
        case 'bank':
            $payment_str = '계좌이체';
            break;
        case 'card':
            $payment_str = '신용카드';
            break;
        default :
            $payment_str = '';
            break;
    }

    return $payment_str;
}

// 결제수단에 따른 kcp 연동 코드
function payment_pay_method_kcp_code($method)
{
    $code = [
        'card' => '100000000000',
        'bank' => '010000000000',
        'vbank' => '001000000000',
    ];

    return $code[$method];
}

// 유효할 시 상품정보 리턴
function payment_check_dealer_member_pay_valid($uid, $d_id)
{
	$CI =& get_instance();
    $CI->load->model('lesson_mdl');

    $wiz_dealer = $CI->member_mdl->get_wiz_dealer($d_id);

    // 자가부담금 있는 딜러있지 체크. 결제기간 아니면 팅긴다
    if($wiz_dealer['has_member_fee'])
    {
		if($wiz_dealer['member_fee_use_yn'] !='Y' || !(date('Y-m-d') >= $wiz_dealer['fee_sdate'] && $wiz_dealer['fee_edate'] >= date('Y-m-d')))
		{
			return array('state' => false, 'msg' => '수강신청 기간이 아닙니다');
		}

        /*
            fee_info 데이터 형태: {"m_2_10":"1000","m_3_10":"2000","m_5_10":"3000","m_2_20":"20500","m_3_30":"100000","m_5_30":"200000","e_2_15":"500","e_2_25":"1000"}
            m_2_10 같은 key값은 수업유형(m,e)_주당수업횟수(2,3,5)_수업분수(10,20,30) 를 의미한다. value는 가격이다
        */
		$dealer_fee = json_decode($wiz_dealer['fee_info'], true);

		if(!$dealer_fee)
		{
			return array('state' => false, 'msg' => '결제정보가 잘못되었습니다.');
		}
        
		// 부담금 있는 딜러의 회원은 결제는 1회만 가능하므로 체크한다.
		$where = " WHERE wl.uid=".$uid. " AND wl.pay_ok='Y'";
    	$wiz_lesson = $CI->lesson_mdl->list_count_lesson($where);

		if($wiz_lesson['cnt'] > 0)
		{
			return array('state' => false, 'msg' => '이미 수강신청 하여 더 이상 신청이 불가능합니다.');
		}

		// 상품정보 리턴
		return array('state' => true, 'dealer_fee' => $dealer_fee, 'wiz_dealer' => $wiz_dealer);

    }
	else
	{
		// 딜러회원 아닌 일반 회원
		return array('state' => false, 'msg' => 'common');
	}

}

/*
    wm_uid 있으면 비교하여 code의 uid와 비교체크한다.
    없으면 해당 code가 유효한지만 확인한다.
*/
function payment_sms_promotion_info($wm_uid='', $code=''){
    //echo $str = $enc->encrypt('119003||138'); //u=Y5dtY5JmtOJmlQ%3D%3D

    //코드 넘어오면 코드로 체크
    if($code)
    {
        $enc = new OldEncrypt('(*&DHajaan=f0#)2');
        $str = explode('||', $enc->decrypt($code));
        $uid = $str[0];
        $sp_list_id = $str[1];

        if($wm_uid && $wm_uid != $uid) return null;
    }
    else
    {
        //코드없이 넘어올수도 있다...(sp_category_id=11 휴면회원용 확인)
        $uid = $wm_uid;
        $sp_list_id = '';
    }
    
    if(!$uid) return null;

    $CI =& get_instance();
    $CI->load->model('payment_mdl');

    $row = null;
    if(!$sp_list_id) 
    {
        // sp_category_id=11 은 휴면회원용 계획고유번호
        $row = $CI->payment_mdl->check_sms_promotion_valid_cate11($uid);

        if($row['sp_list_id'])
        {
            $param = [
                'viewdate' => date('Y-m-d')
            ];
            $where = [
                'uid' => $uid,
                'sp_list_id' => $row['sp_list_id'],
            ];
            $CI->payment_mdl->update_sms_promotion_log($param, $where);
        }
        
    }
    else
    {
        $row = $CI->payment_mdl->check_sms_promotion_valid($uid, $sp_list_id);
    }
    
    return $row;
}


/*
    선택된 상품이 유효한지 체크, 유효하다면 가격정보 리턴
    goods_type:        타입별goods_id:
    1:일반상품,                 mint_goods -> g_id
    2:이벤트상품,               mint_event_goods -> uid
    3:맞춤상품.                 wiz_class -> cl_id
    4:딜러회원자가부담금,        wiz_dealer -> fee_info json데이터의 key. goods_id 필드가 varchar인 이유.
    5:자동재수강,               wiz_lesson -> lesson_id
    6:첨삭                      mint_goods -> g_id(56번)
*/
function payment_goods_payinfo($wiz_member, $request)
{
    $CI =& get_instance();
    $CI->load->model('goods_mdl');
    $CI->load->model('payment_mdl');
    $CI->load->model('lesson_mdl');
    $CI->load->model('holiday_mdl');
    
    $lesson_gubun = '';
    $price = 0;
    $origin_price = 0;
    $dc_price = 0;
    $sms_dc_price = 0;
    $cl_name = '';
    $dc_rate = 0;
    $sms_dc_rate = 0;
    //이벤트 추가 할인
    $event_dc_price = 0;

    // true 시 카드결제, 계좌이체만 오픈
    $pay_card_only = false;
    $goods = null;
    $sms_promotion_info = null;
    $retakeLessonInfo = null;
    $special_dc = null;

    //자가부담금 있는 딜러 하위 회원이라면 별도의 상품으로 결제한다. 다른 type을 선택할수 있는 여지가 없다.
    if($wiz_member && $wiz_member['wd_has_member_fee'])
    {
        $check_dealer_valid = payment_check_dealer_member_pay_valid($wiz_member['wm_uid'], $wiz_member['wm_d_did']);

        if($check_dealer_valid['state'] === false && $check_dealer_valid['msg'] !='common')
        {
            return array('state' => false, 'err_code' => '0802', 'err_msg'=> $check_dealer_valid['msg']);
        }
        elseif($check_dealer_valid['state'] === true)
        {
            /*
                fee_info 데이터 형태: {"m_2_10":"1000","m_3_10":"2000","m_5_10":"3000","m_2_20":"20500","m_3_30":"100000","m_5_30":"200000","e_2_15":"500","e_2_25":"1000"}
                $request['goods_id'] 는 m_2_10 같은 key값이다. 수업유형(m,e)_주당수업횟수(2,3,5)_수업분수(10,20,30). value는 가격이다
            */
            $dealer_fee = $check_dealer_valid['dealer_fee'];
            $dealer_g_id = $request['goods_id'];
            $split_dealer_code = explode('_',$dealer_g_id);

            $origin_price = $dealer_fee[strtolower($dealer_g_id)];
            $price = $origin_price;
            $lesson_gubun = strtoupper($split_dealer_code[0]);
            $cl_name = '주 '.$split_dealer_code[1].'회 '.$split_dealer_code[2].'분 단체수강';
            //회원당 1회만 결제시키기 위해 실시간 카드결제만 지원한다. 무통장은 결제시점이 컨트롤되지 않으므로.
            $pay_card_only = true;

            $cl_number = 0;
            if($split_dealer_code[1] =='2'){
                $cl_number = 8 * $check_dealer_valid['wiz_dealer']['class_month'];
            }else if($split_dealer_code[1] =='3'){
                $cl_number = 12 * $check_dealer_valid['wiz_dealer']['class_month'];
            }
            else if($split_dealer_code[1] =='5'){
                $cl_number = 20 * $check_dealer_valid['wiz_dealer']['class_month'];
            }

            $postpone = json_decode($check_dealer_valid['wiz_dealer']['postpone'], true);

            $goods = [
                'cl_time' => $split_dealer_code[2],
                'cl_number' => $split_dealer_code[1],       //주당수업횟수
                'cl_class' => $cl_number,                   //총 수업횟수 = 개월수 * 주당수업횟수 * 4(1달 4주)
                'cl_month' => $check_dealer_valid['wiz_dealer']['class_month'],
                'hold_num' => $postpone[$split_dealer_code[1]],
            ];
        }
        else
        {
            return array('state' => false, 'err_code' => '0802', 'err_msg'=> '결제정보가 없습니다.');
        }
    }
    else
    {
        
        switch($request['goods_type'])
        {
            case '1':   //정규상품-일반(SMS광고 추가 할인 가능. 나머지는 불가)
                //유효한 상품인지 검색
                $goods = $CI->goods_mdl->row_mint_goods($request['goods_id']);
                if(!$goods)
                {
                    return array('state' => false, 'err_code' => '0803', 'err_msg'=> '잘못된 상품유형입니다.(1)');
                }

                $origin_price = $goods['org_price'];
                $price = $goods['price'];
                $dc_price = $origin_price - $price;

                $lesson_gubun = strtoupper($goods['l_gubun']);
                $cl_name = "주".$goods['l_timeS']."회 ".lesson_replace_cl_name_minute($goods['l_time'], $lesson_gubun, true)."분 ".$goods['l_month']."개월";

                if($lesson_gubun =='V')
                {
                    //네오텍 결제기록 없으면 V는 선택 불가
                    $check_exist_neoteck_pay = lesson_check_exist_neoteck_pay($wiz_member['wm_uid']); 
                    if(!$check_exist_neoteck_pay)
                    {
                        return array('state' => false, 'err_code' => '0803', 'err_msg'=> '잘못된 상품유형입니다.(3)');
                    }
                }

                //SMS 광고 할인대상이라면..
                //소스 아래에서 추가 처리한다.
                $sms_promotion_info = payment_sms_promotion_info($wiz_member['wm_uid'], $request['sms_promotion_code']);

                $special_dc = payment_special_dc_event($wiz_member);

                $goods2 = [
                    'cl_time' => $goods['l_time'],
                    'cl_number' => $goods['l_timeS'],       //주당수업횟수
                    'cl_class' => $goods['l_class'],
                    'cl_month' => $goods['l_month'],
                    'hold_num' => $goods['l_hold'],
                ];

                $goods = array_merge($goods, $goods2);

                if(payment_android_addpoint_event()) $pay_card_only = true;
                
                break;
            case '2':   //이벤트상품(쿠팡 등)
                //유효한 상품인지 검색
                $goods = $CI->goods_mdl->row_event_goods($request['goods_id']);
                if(!$goods)
                {
                    return array('state' => false, 'err_code' => '0803', 'err_msg'=> '잘못된 상품유형입니다.(6)');
                }

                //이벤트 상품 사용가능한 상태인지 체크
                $check_err_msg = payment_valid_event_goods($wiz_member, $goods);

                if($check_err_msg != '')
                {
                    return array('state' => false, 'err_code' => '0813', 'err_msg'=> $check_err_msg);
                }

                $origin_price = $goods['meg_g_price'];
                $price = $goods['meg_price'];
                $dc_price = $origin_price - $price;

                $lesson_gubun = strtoupper($goods['mg_l_gubun']);
                $cl_name = '['.$goods['me_e_name']. "] 주".$goods['mg_l_timeS']."회 ".lesson_replace_cl_name_minute($goods['mg_l_time'],$lesson_gubun,  true)."분 ".$goods['mg_l_month']."개월";

                $goods2 = [
                    'cl_time'   => $goods['mg_l_time'],
                    'cl_number' => $goods['mg_l_timeS'],       //주당수업횟수
                    'cl_class'  => $goods['mg_l_class'],
                    'cl_month'  => $goods['mg_l_month'],
                    'hold_num'  => $goods['meg_g_hold'],
                    'e_id'      => $goods['meg_e_id'],
                ];

                $goods = array_merge($goods, $goods2);
                break;

            case '3':   //맞춤상품
                //유효한 상품인지 검색
                $goods = $CI->goods_mdl->row_custom_goods($request['goods_id']);
                if(!$goods)
                {
                    return array('state' => false, 'err_code' => '0803', 'err_msg'=> '잘못된 상품유형입니다.(5)');
                }

                $origin_price = $goods['fee'];
                $price = $goods['fee'];
                $dc_price = $origin_price - $price;

                $lesson_gubun = $goods['lesson_gubun'];
                $cl_name = $goods['cl_name'];

                $goods2 = [
                    'cl_time' => $goods['cl_time'],
                    'cl_number' => $goods['cl_number'],       //주당수업횟수
                    'cl_class' => $goods['cl_class'],
                    'cl_month' => $goods['cl_month'],
                    'hold_num' => $goods['hold_num'],
                    'student_su' => $goods['student_su'],
                    'student_uid' => $goods['student_uid'],
                ];

                $goods = array_merge($goods, $goods2);

                break;
            case '5':   //정규상품-자동재수강
                
                $relec_lesson = $CI->lesson_mdl->row_wiz_lesson_by_lesson_id($request['goods_id'], $wiz_member['wm_uid']);
                
                if(!$relec_lesson) return array('state' => false, 'err_code' => '0805', 'err_msg'=> '해당 출석부가 존재하지 않습니다');
                if($relec_lesson['wl_pay_sum'] <= 1000) return array('state' => false, 'err_code' => '0812', 'err_msg'=> '유료수업만 재수강 할 수 있습니다.');
                if($relec_lesson['wl_newlesson_ok'] != 'Y') return array('state' => false, 'err_code' => '0812', 'err_msg'=> '이미 연장한 출석부입니다.');
                if($relec_lesson['wl_e_id']) return array('state' => false, 'err_code' => '0812', 'err_msg'=> '이벤트 상품은 재수강 하실 수 없습니다.');
                if($relec_lesson['wl_cl_id']) return array('state' => false, 'err_code' => '0812', 'err_msg'=> '맞춤 상품은 재수강 하실 수 없습니다.');
                if($relec_lesson['wl_student_su'] > 2) return array('state' => false, 'err_code' => '0812', 'err_msg'=> '그룹 수업은 재수강 하실 수 없습니다.');
                if($relec_lesson['wl_disabled_extend']) return array('state' => false, 'err_code' => '0812', 'err_msg'=> '해당 출석부는 수강방식을 변경한 이력이 있어 자동재수강 신청이 불가합니다. 수강신청 메뉴에서 재수강하실 수업과정을 직접 선택하여 신청해주세요.');
                if(!$relec_lesson['wl_weekend'] || $relec_lesson['wl_weekend'] == "::::::") return array('state' => false, 'err_code' => '0812', 'err_msg'=> '수업받는 요일이 정확하지 않습니다. 다시한번 확인해주세요.');
                
                $stime2 = date("H:i",$relec_lesson['wl_stime']);
                if(!$stime2 || $stime2 == ":") return array('state' => false, 'err_code' => '0812', 'err_msg'=> '수업받는 시간이 정확하지 않습니다. 다시한번 확인해주세요.');
                
                //자동재수강 시 미래날짜 스케쥴 비엇는지 확인 및 스케쥴 일정 구성
                $check_retake = lesson_check_retake_lesson_isEmpty_schedule($relec_lesson);

                if($check_retake['state'] == false)
                {
                    return array('state' => false, 'err_code' => '0812', 'err_msg'=> '일정중 이미 등록된 수업이 있습니다. 수업을 확인하신후 등록하시기 바랍니다.');
                }

                $retakeLessonInfo = $check_retake['retakeLessonInfo'];

                $lesson_gubun = $relec_lesson['wl_lesson_gubun'];
                $cl_name = $relec_lesson['wl_cl_name'];

                //1,3,6,12개월중에 선택했으면. order_prepay 에서 값이 넘어온다
                if($request['retake_lesson_month'])
                {
                    $selected_info = $retakeLessonInfo['re_pay_info'][$request['retake_lesson_month']];
                    if(strpos($cl_name, '영어첨삭') !== false)
                    {
                        $lesson_gubun = 'W';
                        $cl_name = '영어첨삭지도 1개월';
                    }
                    else
                    {
                        $cl_name = "주".$selected_info['mg_l_timeS']."회 ".lesson_replace_cl_name_minute($selected_info['mg_l_time'], $lesson_gubun, true)."분 ".$selected_info['mg_l_month']."개월";
                    }
                    
                    $price = $selected_info['mg_price'];
                    $origin_price = $selected_info['mg_org_price'];
                    $dc_price = $selected_info['mg_org_price'] - $selected_info['mg_price'];

                    $goods['cl_time'] = $selected_info['mg_l_time'];
                    $goods['cl_number'] = $selected_info['mg_l_timeS'];
                    $goods['cl_class'] = $selected_info['mg_l_class'];
                    $goods['cl_month'] = $selected_info['mg_l_month'];
                    $goods['hold_num'] = $selected_info['mg_l_hold'];
                }
                
                break;
            case '6':   //첨삭
                //유효한 상품인지 검색
                $goods = $CI->goods_mdl->row_mint_goods($request['goods_id']);
                if(!$goods || $request['goods_id'] != 56)
                {
                    return array('state' => false, 'err_code' => '0803', 'err_msg'=> '잘못된 상품유형입니다.(4)');
                }

                $origin_price = $goods['org_price'];
                $price = $goods['price'];
                $dc_price = $origin_price - $price;

                $lesson_gubun = 'W';    //임의 문자
                $cl_name = '영어첨삭지도 1개월';

                $goods2 = [
                    'cl_time' => $goods['l_time'],
                    'cl_number' => $goods['l_timeS'],       //주당수업횟수
                    'cl_class' => $goods['l_class'],
                    'cl_month' => $goods['l_month'],
                    'hold_num' => $goods['l_hold'],
                ];

                $goods = array_merge($goods, $goods2);


                break;
            
            
            default:
                return array('state' => false, 'err_code' => '0803', 'err_msg'=> '잘못된 상품유형입니다.(2)');
        }

        //기본할인률
        $dc_rate = $origin_price? round($dc_price / $origin_price * 100): 0;
        
        //추가 할인. MEL만, 7분과정은 제외
        if($special_dc !== false && is_array($special_dc) && $lesson_gubun=='E' && $goods['cl_time'] !='10' && ($goods['cl_month'] =='6' || $goods['cl_month'] =='12'))
        {
            $pay_card_only = true;

            $cl_name = '[감사제] ' . $cl_name;

            $event_rate = $special_dc[$goods['cl_month']];
            $event_dc_price_add_dc = ceil($origin_price * (($event_rate + $dc_rate)/100));	// 원래 금액에서 할인금액 재계산
            $event_dc_price = $event_dc_price_add_dc - $dc_price;               // (기본할인+이벤트할인) - 기본할인 = 이벤트할인
            $price = $origin_price - $event_dc_price_add_dc;	                    // 원래금액 - 총 할인금액 = 결제금액
        }
        //sms광고 인입 추가 할인 금액 설정
        elseif($sms_promotion_info)
        {
            $pay_card_only = true;
            //할인률 없어도 포인트 지급이 있을 수 있어 따로 분기 해야한다.
            if($sms_promotion_info['discount_rate'])
            {
                $cl_name = '[할인] ' . $cl_name;
                $sms_dc_rate = $sms_promotion_info['discount_rate'];
                $sms_dc_price_add_dc = ceil($origin_price * (($sms_dc_rate + $dc_rate)/100));	// 원래 금액에서 할인금액 재계산. (기본할인+SMS추가할인)
                $sms_dc_price = $sms_dc_price_add_dc - $dc_price;   // (기본할인+SMS추가할인) - 기본할인 = SMS추가할인
                $price = $origin_price - $sms_dc_price_add_dc;	// 원래금액 - (기본할인+SMS추가할인) = 결제금액
            }
            
        }
    }

    //결제정보
    $pay_info = [
        'cl_gubun'          => $request['cl_gubun'],                //자유 or 고정수업
        'lesson_gubun'      => $lesson_gubun,                       //수업방식. M,E,V,T
        'lesson_gubun_str'  => lesson_gubun_to_str($lesson_gubun),  //수업방식. 첨삭, 전화영어, 화상영어, 민트영어Live
        'cl_name'           => $cl_name,                            //수강정보
        'final_price'       => $price,                              //결제금액
        'origin_price'      => $origin_price,                       //정상가
        'total_dc_price'    => $dc_price + $sms_dc_price + $event_dc_price,    //할인금액
        'event_dc_price'    => $event_dc_price,                     //할인금액
        'dc_price'          => $dc_price,                           //할인금액
        'sms_dc_price'      => $sms_dc_price,                       //광고sms 유입 추가 할인금액
        'total_dc_rate'     => $dc_rate + $sms_dc_rate + $event_rate,          //총할인률
        'dc_rate'           => $dc_rate,                            //일반할인률
        'event_rate'        => $event_rate,                         //이벤트할인률
        'sms_dc_rate'       => $sms_dc_rate,                        //SMS광고 추가 할인률
        'pay_card_only'     => $pay_card_only
    ];

    return array('state'=>true, 'payinfo' => $pay_info, 'goods'=>$goods,'sms_promotion_info' => $sms_promotion_info,'retakeLessonInfo'=>$retakeLessonInfo);

    
}

/*
    주문 진행. 구민트에 pp_cli_hub 파일 역할. 이곳에서 kcp 결제연동검증을 통과하지 않으면 결제가 되지 않는다.
    $post: 결제모듈에 요청한 폼+모듈에서 리턴받은 폼 값을 합친 파라미터들.
    $pay_config: kcp 연동 정보
*/
function payment_order_progress($post, $pay_config)
{
    try
    {
        $CI =& get_instance();
        $CI->load->model('goods_mdl');
        $CI->load->model('payment_mdl');
        $CI->load->model('lesson_mdl');
        $CI->load->model('member_mdl');
        $CI->load->model('sms_mdl');
        $CI->load->model('tutor_mdl');
        $CI->load->model('book_mdl');
        $CI->load->model('point_mdl');
        
        log_message('error', 'payment_order_progress :'.http_build_query($post));
        
        //kcp 결제연동검증 라이브러리 인클루드
        require_once $pay_config['include_kcp_pp'];
        
        $g_conf_gw_url = $pay_config['g_conf_gw_url'];
        $g_wsdl = $pay_config['g_wsdl'];
        $g_conf_site_cd = $pay_config['g_conf_site_cd'];
        $g_conf_site_key = $pay_config['g_conf_site_key'];
        $g_conf_home_dir = $pay_config['g_conf_home_dir'];
        $g_conf_log_level = $pay_config['g_conf_log_level'];
        $g_conf_gw_port = $pay_config['g_conf_gw_port'];
        $module_type = $pay_config['module_type'];
    
        /* ============================================================================== */
        /* =   01. 지불 요청 정보 설정                                                  = */
        /* = -------------------------------------------------------------------------- = */
        $req_tx         = $post[ "req_tx"         ]; // 요청 종류
        $tran_cd        = $post[ "tran_cd"        ]; // 처리 종류
        /* = -------------------------------------------------------------------------- = */
        $cust_ip        = getenv( "REMOTE_ADDR"    ); // 요청 IP
        $ordr_idxx      = $post[ "ordr_idxx"      ]; // 쇼핑몰 주문번호
        $good_name      = $post[ "good_name"      ]; // 상품명
        /* = -------------------------------------------------------------------------- = */
        $res_cd         = "";                         // 응답코드
        $res_msg        = "";                         // 응답메시지
        $res_en_msg     = "";                         // 응답 영문 메세지
        $tno            = $post[ "tno"            ]; // KCP 거래 고유 번호
        /* = -------------------------------------------------------------------------- = */
        $buyr_name      = $post[ "buyr_name"      ]; // 주문자명
        $buyr_tel1      = $post[ "buyr_tel1"      ]; // 주문자 전화번호
        $buyr_tel2      = $post[ "buyr_tel2"      ]; // 주문자 핸드폰 번호
        $buyr_mail      = $post[ "buyr_mail"      ]; // 주문자 E-mail 주소
        /* = -------------------------------------------------------------------------- = */
        $use_pay_method = $post[ "use_pay_method" ]; // 결제 방법
        $bSucc          = "";                         // 업체 DB 처리 성공 여부
        /* = -------------------------------------------------------------------------- = */
        $app_time       = "";                         // 승인시간 (모든 결제 수단 공통)
        $amount         = "";                         // KCP 실제 거래 금액
        $total_amount   = 0;                          // 복합결제시 총 거래금액
        $coupon_mny     = "";                         // 쿠폰금액
        /* = -------------------------------------------------------------------------- = */
        $card_cd        = "";                         // 신용카드 코드
        $card_name      = "";                         // 신용카드 명
        $app_no         = "";                         // 신용카드 승인번호
        $noinf          = "";                         // 신용카드 무이자 여부
        $quota          = "";                         // 신용카드 할부개월
        $partcanc_yn    = "";                         // 부분취소 가능유무
        $card_bin_type_01 = "";                       // 카드구분1
        $card_bin_type_02 = "";                       // 카드구분2
        $card_mny       = "";                         // 카드결제금액
        /* = -------------------------------------------------------------------------- = */
        $bank_name      = "";                         // 은행명
        $bank_code      = "";                         // 은행코드
        $bk_mny         = "";                         // 계좌이체결제금액
        /* = -------------------------------------------------------------------------- = */
        $bankname       = "";                         // 입금할 은행명
        $depositor      = "";                         // 입금할 계좌 예금주 성명
        $account        = "";                         // 입금할 계좌 번호
        $va_date        = "";                         // 가상계좌 입금마감시간
        /* = -------------------------------------------------------------------------- = */
        $pnt_issue      = "";                         // 결제 포인트사 코드
        $pnt_amount     = "";                         // 적립금액 or 사용금액
        $pnt_app_time   = "";                         // 승인시간
        $pnt_app_no     = "";                         // 승인번호
        $add_pnt        = "";                         // 발생 포인트
        $use_pnt        = "";                         // 사용가능 포인트
        $rsv_pnt        = "";                         // 총 누적 포인트
        /* = -------------------------------------------------------------------------- = */
        $commid         = "";                         // 통신사 코드
        $mobile_no      = "";                         // 휴대폰 번호
        /* = -------------------------------------------------------------------------- = */
        $shop_user_id   = $post[ "shop_user_id"   ]; // 가맹점 고객 아이디
        $tk_van_code    = "";                         // 발급사 코드
        $tk_app_no      = "";                         // 상품권 승인 번호
        /* = -------------------------------------------------------------------------- = */
        $cash_yn        = $post[ "cash_yn"        ]; // 현금영수증 등록 여부
        $cash_authno    = "";                         // 현금 영수증 승인 번호
        $cash_tr_code   = $post[ "cash_tr_code"   ]; // 현금 영수증 발행 구분
        $cash_id_info   = $post[ "cash_id_info"   ]; // 현금 영수증 등록 번호
        $cash_no        = "";                         // 현금 영수증 거래 번호    
    
        //값있으면 실패
        $errMsg = '';
        $pay_success = false;
        $lesson_id =null;
        /* ============================================================================== */
    
        /* ============================================================================== */
        /* =   02. 인스턴스 생성 및 초기화                                              = */
        /* = -------------------------------------------------------------------------- = */
        /* =       결제에 필요한 인스턴스를 생성하고 초기화 합니다.                     = */
        /* = -------------------------------------------------------------------------- = */
        $c_PayPlus = new C_PP_CLI;
    
        $c_PayPlus->mf_clear();
        /* ------------------------------------------------------------------------------ */
        /* =   02. 인스턴스 생성 및 초기화 END                                          = */
        /* ============================================================================== */
    
    
        /* ============================================================================== */
        /* =   03. 처리 요청 정보 설정 및 승인요청 전 기본검증                             = */
        /* = -------------------------------------------------------------------------- = */

        $prepay_id = (new OldEncrypt('(*&DHajaan=f0#)2'))->decrypt($post['prepay_id']);
        //임시 결제정보저장 테이블에서 데이터 가져오기
        $prepay = $CI->payment_mdl->row_prepay_by_prepay_id($prepay_id);

        //$ordr_chk = explode('|',$post['ordr_chk']); //오더번호|금액

        if(!$prepay)
        {
            $errMsg = '잘못된 주문프로세스입니다.';
			$bSucc = "false"; 
        }
        elseif($prepay['order_no'] != $ordr_idxx)
        {
            $errMsg = '주문번호가 잘못되었습니다.';
			$bSucc = "false"; 
        }
        elseif($prepay['lesson_id'])
        {
            $errMsg = '이미 처리된 주문입니다.';
			$bSucc = "false"; 
        }
        //자동재수강 스케쥴 잡힌거 있는지 체크
        elseif($prepay['goods_type'] == '5')
        {
            $relec_lesson = $CI->lesson_mdl->row_wiz_lesson_by_lesson_id($prepay['goods_id'], $prepay['uid']);
            if($relec_lesson)
            {
                $check_retake = lesson_check_retake_lesson_isEmpty_schedule($relec_lesson, $prepay['cl_month']);

                if($check_retake['state'] == false)
                {
                    $errMsg = '일정중 이미 등록된 수업이 있습니다. 수업을 확인하신후 등록하시기 바랍니다.';
                    $bSucc = "false"; 
                }
            }
            else
            {
                $errMsg = '자동재수강 정보가 없습니다.';
			    $bSucc = "false"; 
            }
            
        }

        if($bSucc =='false')
        {
            $CI->payment_mdl->insert_wiz_pg_notification([
                'order_name' => $prepay['uid'].$errMsg,
                'payinfo' => http_build_query($post),
            ]);
            
            return [
                'state' => 0,
                'msg' => $errMsg,
            ];
        }

        //결제검증할 금액
        $amount_should_be_paid = $prepay['total_price'];  

        $wiz_member = $CI->member_mdl->get_wiz_member_by_wm_uid($prepay['uid']);

        
        /* = -------------------------------------------------------------------------- = */
        /* =   03-1. 승인 요청                                                          = */
        /* = -------------------------------------------------------------------------- = */
    
        if ( $req_tx == "pay" )
        {
                /* 1 원은 실제로 업체에서 결제하셔야 될 원 금액을 넣어주셔야 합니다. 결제금액 유효성 검증 */
                $c_PayPlus->mf_set_ordr_data( "ordr_mony",  $amount_should_be_paid );                                   
    
                $c_PayPlus->mf_set_encx_data( $post[ "enc_data" ], $post[ "enc_info" ] );
        }
        /* ------------------------------------------------------------------------------ */
        /* =   03.  처리 요청 정보 설정 END                                             = */
        /* ============================================================================== */
    
        /* ============================================================================== */
        /* =   04. 실행                                                                 = */
        /* = -------------------------------------------------------------------------- = */
        if ( $tran_cd != "" )
        {
            //이부분 주석하면 결제되지 않는다
            $c_PayPlus->mf_do_tx( "", $g_conf_home_dir, $g_conf_site_cd, $g_conf_site_key, $tran_cd, "",
                                  $g_conf_gw_url, $g_conf_gw_port, "payplus_cli_slib", $ordr_idxx,
                                  $cust_ip, $g_conf_log_level, 0, 0, $g_conf_log_path ); // 응답 전문 처리
    
            $res_cd  = $c_PayPlus->m_res_cd;  // 결과 코드
            $res_msg = $c_PayPlus->m_res_msg; // 결과 메시지
            /* $res_en_msg = $c_PayPlus->mf_get_res_data( "res_en_msg" );  // 결과 영문 메세지 */ 
        }
        else
        {
            $c_PayPlus->m_res_cd  = "9562";
            $c_PayPlus->m_res_msg = "연동 오류 tran_cd값이 설정되지 않았습니다.";
        }
    
        log_message('error', 'payprocess-' . $prepay_id . ':('.$c_PayPlus->m_res_cd.')'.$c_PayPlus->m_res_ms);
        /* = -------------------------------------------------------------------------- = */
        /* =   04. 실행 END                                                             = */
        /* ============================================================================== */
    
    
        /* ============================================================================== */
        /* =   05. 승인 결과 값 추출                                                    = */
        /* = -------------------------------------------------------------------------- = */
        if ( $req_tx == "pay" )
        {
            if( $res_cd == "0000" )
            {
                $tno       = $c_PayPlus->mf_get_res_data( "tno"       ); // KCP 거래 고유 번호
                $amount    = $c_PayPlus->mf_get_res_data( "amount"    ); // KCP 실제 거래 금액
                $pnt_issue = $c_PayPlus->mf_get_res_data( "pnt_issue" ); // 결제 포인트사 코드
                $coupon_mny = $c_PayPlus->mf_get_res_data( "coupon_mny" ); // 쿠폰금액
    
        /* = -------------------------------------------------------------------------- = */
        /* =   05-1. 신용카드 승인 결과 처리                                            = */
        /* = -------------------------------------------------------------------------- = */
                if ( $use_pay_method == "100000000000" )
                {
                    $card_cd   = $c_PayPlus->mf_get_res_data( "card_cd"   ); // 카드사 코드
                    $card_name = $c_PayPlus->mf_get_res_data( "card_name" ); // 카드 종류
                    $app_time  = $c_PayPlus->mf_get_res_data( "app_time"  ); // 승인 시간
                    $app_no    = $c_PayPlus->mf_get_res_data( "app_no"    ); // 승인 번호
                    $noinf     = $c_PayPlus->mf_get_res_data( "noinf"     ); // 무이자 여부 ( 'Y' : 무이자 )
                    $quota     = $c_PayPlus->mf_get_res_data( "quota"     ); // 할부 개월 수
                    $partcanc_yn = $c_PayPlus->mf_get_res_data( "partcanc_yn" ); // 부분취소 가능유무
                    $card_bin_type_01 = $c_PayPlus->mf_get_res_data( "card_bin_type_01" ); // 카드구분1
                    $card_bin_type_02 = $c_PayPlus->mf_get_res_data( "card_bin_type_02" ); // 카드구분2
                    $card_mny = $c_PayPlus->mf_get_res_data( "card_mny" ); // 카드결제금액
    
                    /* = -------------------------------------------------------------- = */
                    /* =   05-1.1. 복합결제(포인트+신용카드) 승인 결과 처리               = */
                    /* = -------------------------------------------------------------- = */
                    if ( $pnt_issue == "SCSK" || $pnt_issue == "SCWB" )
                    {
                        $pnt_amount   = $c_PayPlus->mf_get_res_data ( "pnt_amount"   ); // 적립금액 or 사용금액
                        $pnt_app_time = $c_PayPlus->mf_get_res_data ( "pnt_app_time" ); // 승인시간
                        $pnt_app_no   = $c_PayPlus->mf_get_res_data ( "pnt_app_no"   ); // 승인번호
                        $add_pnt      = $c_PayPlus->mf_get_res_data ( "add_pnt"      ); // 발생 포인트
                        $use_pnt      = $c_PayPlus->mf_get_res_data ( "use_pnt"      ); // 사용가능 포인트
                        $rsv_pnt      = $c_PayPlus->mf_get_res_data ( "rsv_pnt"      ); // 총 누적 포인트
                        $total_amount = $amount + $pnt_amount;                          // 복합결제시 총 거래금액
                    }
                }
    
        /* = -------------------------------------------------------------------------- = */
        /* =   05-2. 계좌이체 승인 결과 처리                                            = */
        /* = -------------------------------------------------------------------------- = */
                if ( $use_pay_method == "010000000000" )
                {
                    $app_time  = $c_PayPlus->mf_get_res_data( "app_time"   );  // 승인 시간
                    $bank_name = $c_PayPlus->mf_get_res_data( "bank_name"  );  // 은행명
                    $bank_code = $c_PayPlus->mf_get_res_data( "bank_code"  );  // 은행코드
                    $bk_mny = $c_PayPlus->mf_get_res_data( "bk_mny" ); // 계좌이체결제금액
                }
    
        /* = -------------------------------------------------------------------------- = */
        /* =   05-3. 가상계좌 승인 결과 처리                                            = */
        /* = -------------------------------------------------------------------------- = */
                if ( $use_pay_method == "001000000000" )
                {
                    $bankname  = $c_PayPlus->mf_get_res_data( "bankname"  ); // 입금할 은행 이름
                    $depositor = $c_PayPlus->mf_get_res_data( "depositor" ); // 입금할 계좌 예금주
                    $account   = $c_PayPlus->mf_get_res_data( "account"   ); // 입금할 계좌 번호
                    $va_date   = $c_PayPlus->mf_get_res_data( "va_date"   ); // 가상계좌 입금마감시간
                }
    
        /* = -------------------------------------------------------------------------- = */
        /* =   05-4. 포인트 승인 결과 처리 사용 안함                                              = */
        /* = -------------------------------------------------------------------------- = */
                if ( $use_pay_method == "000100000000" )
                {
                    $pnt_amount   = $c_PayPlus->mf_get_res_data( "pnt_amount"   ); // 적립금액 or 사용금액
                    $pnt_app_time = $c_PayPlus->mf_get_res_data( "pnt_app_time" ); // 승인시간
                    $pnt_app_no   = $c_PayPlus->mf_get_res_data( "pnt_app_no"   ); // 승인번호 
                    $add_pnt      = $c_PayPlus->mf_get_res_data( "add_pnt"      ); // 발생 포인트
                    $use_pnt      = $c_PayPlus->mf_get_res_data( "use_pnt"      ); // 사용가능 포인트
                    $rsv_pnt      = $c_PayPlus->mf_get_res_data( "rsv_pnt"      ); // 적립 포인트
                }
    
        /* = -------------------------------------------------------------------------- = */
        /* =   05-5. 휴대폰 승인 결과 처리  사용 안함                                            = */
        /* = -------------------------------------------------------------------------- = */
                if ( $use_pay_method == "000010000000" )
                {
                    $app_time  = $c_PayPlus->mf_get_res_data( "hp_app_time"  ); // 승인 시간
                    $commid    = $c_PayPlus->mf_get_res_data( "commid"       ); // 통신사 코드
                    $mobile_no = $c_PayPlus->mf_get_res_data( "mobile_no"    ); // 휴대폰 번호
                }
    
        /* = -------------------------------------------------------------------------- = */
        /* =   05-6. 상품권 승인 결과 처리  사용 안함                                            = */
        /* = -------------------------------------------------------------------------- = */
                if ( $use_pay_method == "000000001000" )
                {
                    $app_time    = $c_PayPlus->mf_get_res_data( "tk_app_time"  ); // 승인 시간
                    $tk_van_code = $c_PayPlus->mf_get_res_data( "tk_van_code"  ); // 발급사 코드
                    $tk_app_no   = $c_PayPlus->mf_get_res_data( "tk_app_no"    ); // 승인 번호
                }
    
        /* = -------------------------------------------------------------------------- = */
        /* =   05-7. 현금영수증 결과 처리                                               = */
        /* = -------------------------------------------------------------------------- = */
                $cash_authno  = $c_PayPlus->mf_get_res_data( "cash_authno"  ); // 현금 영수증 승인 번호
                $cash_no      = $c_PayPlus->mf_get_res_data( "cash_no"      ); // 현금 영수증 거래 번호       
            }
            else
            {
                $errMsg = '('.$c_PayPlus->m_res_cd.')'.iconv('euc-kr','utf-8',$c_PayPlus->m_res_ms);
            }
        }
        /* = -------------------------------------------------------------------------- = */
        /* =   05. 승인 결과 처리 END                                                   = */
        /* ============================================================================== */
    
        /* ============================================================================== */
        /* =   06. 승인 및 실패 결과 DB처리                                             = */
        /* = -------------------------------------------------------------------------- = */
        /* =       결과를 업체 자체적으로 DB처리 작업하시는 부분입니다.                 = */
        /* = -------------------------------------------------------------------------- = */
    
        
        if ( $req_tx == "pay" )
        {
            if( $res_cd == "0000" )
            {
                // 06-1-1. 신용카드
                if( $use_pay_method == "100000000000" )
                {	
                    $halbu_month = $quota;
                }
                // 06-1-2. 계좌이체
                elseif( $use_pay_method == "010000000000" )
                {
                    $bank_number = $bank_name; // 입금 계좌 실시간
                }
                // 06-1-3. 가상계좌
                elseif( $use_pay_method == "001000000000" )
                {
                    $bank_number = $bankname." ".$account; // 입금 가상계좌
                    $va_date2 = substr($va_date,0,4)."-".substr($va_date,4,2)."-".substr($va_date,6,2); // 문자 알림톡용 입금 기한
                }

                $bank_number = iconv('euc-kr','utf-8',$bank_number);
                $card_name = iconv('euc-kr','utf-8',$card_name);

                if($bSucc != 'false')
                {
                    $wiz_pay_param = [
                        'va_date'       => $va_date2,
                        'bank_number'   => $bank_number,
                        'card_agreeno'  => $app_no,
                        'card_date'     => $app_time,
                        'card_orderno'  => $tno,
                        'card_code'     => $card_cd,
                        'card_name'     => $card_name,
                        'halbu_month'   => $halbu_month,
                    ];
                    
                    //wiz_lesson 삽입
                    $lesson_pay_id = payment_insert_lesson_pay($prepay, $wiz_member, $wiz_pay_param);

                    if($lesson_pay_id['state'] ==0)
                    {
                        $bSucc = 'false';
                        $errMsg = '출석부 생성 실패-DB ERROR';
                    }
                    else
                    {
                        $lesson_id = $lesson_pay_id['lesson_id'];
                        $pay_id = $lesson_pay_id['pay_id'];

                        //SMS광고 프로모션 혜택 체크
                        $sms_promotion_info = payment_sms_promotion_info($prepay['uid'], $prepay['sms_promotion_code']);
                        if($sms_promotion_info && ($sms_promotion_info['addpoint'] || $prepay['sms_dc_price']) && $lesson_pay_id['lesson_param']['pay_ok']=='Y'){
                            //포인트 지급
                            payment_set_promotion_benefit($wiz_member,$sms_promotion_info,$prepay['total_price'],$lesson_id,$prepay['cl_class'],$prepay['cl_time']);
                            $param = [
                                'pay_id' => $pay_id
                            ];
                            $where = [
                                'uid' => $prepay['uid'],
                                'sp_list_id' => $sms_promotion_info['sp_list_id'],
                            ];
                            //1회성혜택이므로 받았다고 표기
                            $CI->payment_mdl->update_sms_promotion_log($param, $where);
                        }

                        //자동재수강일때 스케쥴 삽입+포인트적립+강사인센티브
                        if($prepay['goods_type'] =='5')
                        {
                            //가상계좌 아닐때 = 결제완료되었을때 스케쥴 삽입
                            if($lesson_pay_id['lesson_param']['pay_ok']=='Y') 
                            {
                                lesson_insert_schedule_retake($relec_lesson, $check_retake, $prepay, $wiz_member, $lesson_id);
                            }

                        }
                        //안드어플 첫수강결제라면
                        elseif($prepay['goods_type'] =='8')
                        {
                            //분수 별 포인트 지급양
                            $timeToPoint = array(
                                '10'=>5000,
                                '20'=>10000,
                                '30'=>15000,
                            );
                            $addpoint = $prepay['cl_class'] * $timeToPoint[$prepay['cl_time']];

                            $point = array(
                                'uid' => $wiz_member['wm_uid'],
                                'name' => $wiz_member['wm_name'],
                                'point' => $addpoint,
                                'pt_name'=> '안드로이드 APP 1+1 이벤트 적립', 
                                'lesson_id'=> $lesson_id, 
                                'kind'=> 'k', 
                                'showYn'=> 'y',
                                'regdate' => date("Y-m-d H:i:s")
                            );

                            /* 포인트 내역 입력 및 포인트 추가 */
                            $CI->point_mdl->set_wiz_point($point);
                        }

                        //간편등록으로 진입 시 자유수업일때 교재 지정해준다. 강사선택한 수강신청이면 스케쥴 삽입***********************************************
                        
                        //문자보내기
                        $mobile_number = $lesson_pay_id['lesson_param']['mobile'] ? $lesson_pay_id['lesson_param']['mobile']:$prepay['mobile'];
                        if($prepay['goods_type'] =='6') //첨삭
                        {
                            if($prepay['pay_method'] == 'vbank')
                            {
                                $mobile_number = $prepay['receive_mobile'];
                                $template_code = 'MINT06002B';
                                $sms_templete_code = 264;
                                $push_No = 3000;

                                $pInfo = array("member"=>$wiz_member['wm_name'], "w_uid" => $wiz_member['wm_uid']);
                            }
                            else
                            {
                                $template_code = 'MINT06002H';
                                $sms_templete_code = 258;
                                $push_No = 3004;

                                $tpl = $CI->sms_mdl->get_atalk_templete('MINT06002F');    
                                $pInfo = array("member"=>$wiz_member['wm_name'],"atk_content"=> $tpl['content']);
                            }
                        }
                        elseif($prepay['goods_type'] =='5') //자동재수강
                        {
                            if($prepay['pay_method'] == 'vbank')
                            {
                                $mobile_number = $prepay['receive_mobile'];
                                $template_code = 'MINT06002A';
                                $sms_templete_code = 265;
                                $push_No = 3000;

                                $pInfo = array("member"=>$wiz_member['wm_name'], "w_uid" => $wiz_member['wm_uid']);
                            }
                            else
                            {
                                $template_code = 'MINT06002G';
                                $sms_templete_code = '263';
                                $push_No = 3005;

                                $tpl = $CI->sms_mdl->get_atalk_templete($template_code);    
                                $pInfo = array("member"=>$wiz_member['wm_name'],"atk_content"=> $tpl['content']);
                            }
                        }
                        else
                        {
                            if($prepay['pay_method'] == 'vbank')
                            {
                                $mobile_number = $prepay['receive_mobile'];
                                $template_code = 'MINT06002C';
                                $sms_templete_code = 266;
                                $push_No = 3000;

                                $pInfo = array("member"=>$wiz_member['wm_name'], "w_uid" => $wiz_member['wm_uid']);
                            }
                            else
                            {
                                $template_code = $prepay['cl_gubun'] == '2' ? 'MINT06004G':'MINT06002F';
                                $sms_templete_code = $prepay['cl_gubun'] == '2' ? '328':'261';
                                $push_No = 3004;

                                $tpl = $CI->sms_mdl->get_atalk_templete($template_code);    
                                $pInfo = array("member"=>$wiz_member['wm_name'],"atk_content"=> $tpl['content']);
                            }
                        }
                        
                        $sms_options = array(
                            'bank_number'   =>$bank_number,
                            'lastday'       =>$va_date2,
                            'price'         =>$prepay['total_price'],
                            'wiz_id'        =>$wiz_member['wm_wiz_id'],
                            'uid'           =>$wiz_member['wm_uid'],
                            'name'          =>$wiz_member['wm_name'],
                        );
                        
                        //SMS 전송
                        sms::send_sms($mobile_number, $sms_templete_code, $sms_options);

                        $atalk_options = array(
                            'bank_number'   =>$bank_number,
                            'lastday'       =>$va_date2,
                            'vbank_price'   =>$prepay['total_price'],
                            'wiz_id'        =>$wiz_member['wm_wiz_id'],
                            'uid'           =>$wiz_member['wm_uid'],
                            'name'          =>$wiz_member['wm_name'],
                        );
        
                        //알림톡 전송
                        sms::send_atalk($mobile_number, $template_code, $atalk_options);

                        //푸시
                        AppPush::send_push($row['uid'], $push_No, $pInfo);

                        

                        $pay_success = true;
                    }
                    
                }
    
            } // END 결제 성공
            /* = -------------------------------------------------------------------------- = */
            /* =   06. 승인 및 실패 결과 DB처리                                             = */
            /* ============================================================================== */
            else
            {
                $errMsg = "[res_cd (".$res_cd.")][res_cd (0000) 아님] 결제에 실패 하였습니다.". $errMsg;
                $CI->payment_mdl->insert_wiz_pg_notification([
                    'imp_uid' => $tno,
                    'order_id' => $OrdNo,
                    'order_status' => 'fail',
                    'order_amount' => $price,
                    'order_method' => $payment,
                    'order_card_code' => $card_cd,
                    'order_card' => $card_name,
                    'order_name' => $prepay['uid'].$errMsg,
                    'payinfo' => http_build_query($post),
                    'order_buyer' => $buyr_name,
                    'reg_date' => date('Y-m-d H:i:s'),
                ]);
                
            }
    
    
        }

    
    
        /* ============================================================================== */
        /* =   07. 승인 결과 DB처리 실패시 : 자동취소                                   = */
        /* = -------------------------------------------------------------------------- = */
        /* =         승인 결과를 DB 작업 하는 과정에서 정상적으로 승인된 건에 대해      = */
        /* =         DB 작업을 실패하여 DB update 가 완료되지 않은 경우, 자동으로       = */
        /* =         승인 취소 요청을 하는 프로세스가 구성되어 있습니다.                = */
        /* =                                                                            = */
        /* =         DB 작업이 실패 한 경우, bSucc 라는 변수(String)의 값을 "false"     = */
        /* =         로 설정해 주시기 바랍니다. (DB 작업 성공의 경우에는 "false" 이외의 = */
        /* =         값을 설정하시면 됩니다.)                                           = */
        /* = -------------------------------------------------------------------------- = */
            
    
        /* = -------------------------------------------------------------------------- = */
        /* =   07-1. DB 작업 실패일 경우 자동 승인 취소                                 = */
        /* = -------------------------------------------------------------------------- = */
        if ( $req_tx == "pay" )
        {
            if( $res_cd == "0000" )
            {
                if ( $bSucc == "false" )
                {
                    $c_PayPlus->mf_clear();

                    $tran_cd = "00200000";
    
                    $c_PayPlus->mf_set_modx_data( "tno",      $tno                         );  // KCP 원거래 거래번호
                    $c_PayPlus->mf_set_modx_data( "mod_type", "STSC"                       );  // 원거래 변경 요청 종류
                    $c_PayPlus->mf_set_modx_data( "mod_ip",   $cust_ip                     );  // 변경 요청자 IP
                    $c_PayPlus->mf_set_modx_data( "mod_desc", "결과 처리 오류 - 자동 취소" );  // 변경 사유
    
                    $c_PayPlus->mf_do_tx( "", $g_conf_home_dir, $g_conf_site_cd, $g_conf_site_key, $tran_cd, "",
                                  $g_conf_gw_url, $g_conf_gw_port, "payplus_cli_slib", $ordr_idxx,
                                  $cust_ip, $g_conf_log_level, 0, 0, $g_conf_log_path ); // 응답 전문 처리
    
                    $res_cd  = $c_PayPlus->m_res_cd;
                    $res_msg = $c_PayPlus->m_res_msg;

                    $CI->payment_mdl->insert_wiz_pg_notification([
                        'imp_uid' => $tno,
                        'order_id' => $OrdNo,
                        'order_status' => 'fail',
                        'order_amount' => $price,
                        'order_method' => $payment,
                        'order_card_code' => $card_cd,
                        'order_card' => $card_name,
                        'order_name' => $prepay['uid'].$errMsg,
                        'payinfo' => http_build_query($post),
                        'order_buyer' => $buyr_name,
                        'reg_date' => date('Y-m-d H:i:s'),
                    ]);

                }
    
            }
    
        }
    
        /* ============================================================================== */
        /* =   						utm_log 삽입                                        = */
        /* ============================================================================== */
        if($wiz_member['muu_key']){
    
            $CI->load->model('etc_mdl');
            $request = array(
                'muu_key' => $wiz_member['muu_key'],
                'ref_key' => $pay_id,
                'ref_uid' => $wiz_member['uid'],
                'type' => '4',						// 1: 방문자수(1일 1로그), 2: 회원가입, 3: 레벨테스트 신청, 4: 결제, 5: 방문횟수(로그제한 없음)
                'loc' => '1',						// 1: pc, 2:mobile
                'ip' => $_SERVER["REMOTE_ADDR"],
                'regdate' => date("Y-m-d H:i:s"),
            );
    
            $CI->etc_mdl->insert_utm($request);
        }
    
        //////////////////////////////////////utm_log 삽입
    
        /* ============================================================================== */
        /* =   08. 폼 구성 및 결과페이지 호출                                           = */
        /* ============================================================================== */
    
        //결제성공시 퀘스트호출
        if($pay_success && $lesson_pay_id['lesson_param']['pay_ok']=='Y' && $prepay['goods_type'] != '6')
        {
            MintQuest::request_batch_quest('6', $lesson_id, $wiz_member['wm_uid']);
        }
    
        //결과 리턴해준다.
        return [
            'state' => $pay_success != true ? 0:1,
            'msg' => $errMsg,
            'lesson_id' => $lesson_id
        ];
    }
    catch(exception $e)
    {
        log_message('error', 'payment_order_progress_exception :'.$e->getMessage());
        return [
            'state' => 0,
            'msg' => $e->getMessage(),
        ];
    }
    
}

//최대한 이곳에서만 wiz_lesson, wiz_pay를 생성하도록 한다.
function payment_insert_lesson_pay($prepay,$wiz_member,$pay_param=array())
{
    try
    {
        $lesson_id = 0;
        $pay_id = 0;

        $CI =& get_instance();
        $CI->load->model('goods_mdl');
        $CI->load->model('payment_mdl');
        $CI->load->model('lesson_mdl');
        $CI->load->model('member_mdl');
        $CI->load->model('tutor_mdl');
        $CI->load->model('point_mdl');
    
        if(!$wiz_member['wm_tel'] && $prepay['tel'])
        {
            $CI->member_mdl->update_member(array('tel' => $prepay['tel']), $wiz_member['wm_wiz_id']);
        }
    
        $before_id = 0;
        $tu_uid = 0;
        $tu_name = '';
        $relec_lesson = null;
        $lesson_count = 1;  //수업 자동재수강 카운트 - 자동재수강을 한 경우 이 수치가 1 증가함

        //자동재수강일 시
        if($prepay['goods_type'] =='5')
        {
            $relec_lesson = $CI->lesson_mdl->row_wiz_lesson_by_lesson_id($prepay['goods_id'], $wiz_member['wm_uid']);
            $lesson_count = $relec_lesson ? $relec_lesson['wl_lesson_count']+1:1;
            $parent_id = $prepay['goods_id'];
            $before_id = $prepay['goods_id'];

            $tu_uid = $relec_lesson['wl_tu_uid'];
            $tu_name = $relec_lesson['wl_tu_name'];

            $ymd = $relec_lesson['wl_endday'];
            $stime2 = date("H:i",$relec_lesson['wl_stime']);
            $TM = explode(":",$stime2);
            if($TM[0] == "00" || $TM[0]=="01") 
            { 
                $hday = "2"; 
                $START =  mktime($TM[0],$TM[1],$TM[2],substr($ymd,5,2),substr($ymd,8,2),substr($ymd,0,4)) + 172800; 
            }
            else 
            { 
                $hday = "1"; 
                $START =  mktime($TM[0],$TM[1],$TM[2],substr($ymd,5,2),substr($ymd,8,2),substr($ymd,0,4)) + 86400; 
            }

            $prepay['hopedate'] = date("Y-m-d",$START);
            $prepay['hopetime'] = date("H:i",$START);
        }
        else
        {
            $parent_id = 0;
        }

        $relec_id = $CI->lesson_mdl->find_relec_id($prepay['uid']);
        $relec_id = $relec_id ? $relec_id['lesson_id']:0;
    
        $cl_name = $prepay['cl_name'];
        if(strpos($cl_name,'첨삭') !== false)
        {
            $prepay['lesson_gubun'] = 'W';
        }

        if($prepay['lesson_gubun'] =='T') $cl_name.=' (Tel)';
        elseif($prepay['lesson_gubun'] =='M') $cl_name.=' (Phone)';
        elseif($prepay['lesson_gubun'] =='V') $cl_name.=' (Video)';
        elseif($prepay['lesson_gubun'] =='E') $cl_name.=' (MintEnglishLive)';
        elseif($prepay['lesson_gubun'] =='B') $cl_name.=' (민트비 Live)';
    
        $disabled_extend = 0;
        if($prepay['sms_dc_price'] > 0 || $prepay['event_dc_price'] > 0)
        {
            $disabled_extend = 1;   //수업연장 불가옵션
        }
    
        $pay_ok = 'N';
        if($prepay['pay_method'] == 'card') $pay_ok = 'Y';
        elseif($prepay['pay_method'] == 'samsung') $pay_ok = 'Y';
        elseif($prepay['pay_method'] == 'bank') $pay_ok = 'Y';  //계좌이체
        elseif($prepay['pay_method'] == 'coupon:') $pay_ok = 'Y';  //쿠폰
    
        //수업 번호(본 수업의 수업번호) - 새로 시작하는 수강의 경우 가장 큰 수업번호 + 1
        $lesson_number = $CI->lesson_mdl->get_lesson_number($wiz_member['wm_uid']);
        $lesson_number = $lesson_number ? ($lesson_number['lesson_number'] +1):1;
    
        //전체 수업을 들은 횟수(본 수업정보 포함)
        $where = " WHERE wl.uid=".$wiz_member['wm_uid']." AND wl.pay_ok='Y' AND wl.schedule_ok = 'Y'";
        $lesson_total_count = $CI->lesson_mdl->list_count_lesson($where);
    
        //이벤트 수업을 들은 횟수 - 쿠폰수업은 이벤트로 처리하지 않음
        $where = " WHERE wl.uid=".$wiz_member['wm_uid']." AND wl.pay_ok='Y' AND wl.e_id > 0 AND wl.payment NOT LIKE '%coupon%'";
        $lesson_event_total_count = $CI->lesson_mdl->list_count_lesson($where);
        if($prepay['e_id'] && $prepay['pay_method'] != 'coupon:') $lesson_event_total_count['cnt']++;
        
        //환불한 횟수
        $where = " WHERE wl.uid=".$wiz_member['wm_uid']." AND wl.refund_ok='Y'";
        $lesson_refund_count = $CI->lesson_mdl->list_count_lesson($where);
    
        //영어첨삭 횟수
        $where = " WHERE wl.uid=".$wiz_member['wm_uid']." AND wl.pay_ok='Y' AND wl.schedule_ok = 'Y' AND wl.tu_uid='153' AND (wl.e_id=0 OR wl.e_id IS NULL) AND wl.payment NOT LIKE '%coupon%'";
        $lesson_correct_count = $CI->lesson_mdl->list_count_lesson($where);
        if (false !== strpos($cl_name, '첨삭') && !$prepay['e_id']) $lesson_correct_count['cnt']++;
    
        //쿠폰수업 횟수
        $where = " WHERE wl.uid=".$wiz_member['wm_uid']." AND wl.pay_ok='Y' AND wl.payment LIKE '%coupon%'";
        $lesson_coupon_count = $CI->lesson_mdl->list_count_lesson($where);
    
        $receive_mobile = $prepay['mobile'] ? $prepay['mobile']:$wiz_member['wm_mobile'];
        $pay_method = $prepay['pay_method'];

        if($prepay['pay_method'] == "vbank") 
        {
            $receive_date = $pay_param['va_date'];
            $ipdate = "0000-00-00";
            $pay_cash = 0;
            $pay_card = 0;
            $pay_bank = 0;
            $pay_ars = 0;
            $pay_vbank = $prepay['total_price'];
        }
        elseif($prepay['pay_method'] == "card") 
        {
            $receive_date = "0000-00-00";
            $ipdate = date("Y-m-d");
            $pay_cash = 0;
            $pay_card = $prepay['total_price'];
            $pay_bank = 0;
            $pay_ars = 0;
            $pay_vbank = 0;
        }
        elseif($prepay['pay_method'] == "samsung") 
        {
            $receive_date = "0000-00-00";
            $ipdate = date("Y-m-d");
            $pay_cash = 0;
            $pay_card = $prepay['total_price'];
            $pay_bank = 0;
            $pay_ars = 0;
            $pay_vbank = 0;
        }
        elseif($prepay['pay_method'] == "bank") 
        {
            $receive_date = "0000-00-00";
            $ipdate = date("Y-m-d");
            $pay_cash = 0;
            $pay_card = 0;
            $pay_bank = $prepay['total_price'];
            $pay_ars = 0;
            $pay_vbank = 0;
        }
        elseif($prepay['pay_method'] == "coupon:") 
        {
            $receive_date = "0000-00-00";
            $ipdate = date("Y-m-d");
            $pay_cash = 0;
            $pay_card = 0;
            $pay_bank = 0;
            $pay_ars = 0;
            $pay_vbank = 0;
        }
        elseif($prepay['pay_method'] == "hubcard") {
            $receive_date = "0000-00-00";
            $ipdate = "0000-00-00";
            $pay_ok='N';
            $pay_cash = 0;
            $pay_card = 0;
            $pay_bank = 0;
            $pay_ars = $prepay['total_price'];
            $pay_vbank = 0;

            $receive_mobile = $prepay['ars_mobile_number'] ?  $prepay['ars_mobile_number']:$receive_mobile;

            $pay_method = 'hubCard';
        }
        elseif($prepay['pay_method'] == "cash") {
            $receive_date = $prepay['receive_date'];
            $ipdate = "0000-00-00";
            $pay_ok='N';
            $pay_cash = $prepay['total_price'];
            $pay_card = 0;
            $pay_bank = 0;
            $pay_ars = 0;
            $pay_vbank = 0;
        }

        if($pay_param['card_name'] && $prepay['pay_method'] == "samsung")
        {
            $pay_param['card_name'] = $pay_param['card_name'].'(samsungPay)';
        }

        $pay_method = str_replace(':','',$pay_method).':';

        $receive_mobile = common_checked_phone_format($receive_mobile);
        
        $lesson_param = [
            'parent_id'         => $parent_id,
            'order_gubun'       => 1,
            'newlesson_ok'      => 'Y',
            'uid'               => $prepay['uid'],
            'wiz_id'            => $wiz_member['wm_wiz_id'],
            'name'              => $wiz_member['wm_name'] ? $wiz_member['wm_name']:'',
            'ename'             => $wiz_member['wm_ename'] ? $wiz_member['wm_ename']:'',
            'tel'               => $prepay['tel'] ? $prepay['tel']:'',
            'mobile'            => $receive_mobile ? $receive_mobile:'',
            'co_uid'            => 0,
            'co_company'        => '',
            'ji_uid'            => 0,
            'ji_company'        => '',
            'lev_id'            => 0,
            'lev_gubun'         => $wiz_member['wm_lev_gubun'],
            'lev_name'          => '',
            'cl_id'             => ($prepay['goods_type'] == '3' || $prepay['pay_method'] == 'coupon:') ? $prepay['goods_id']:0,
            'cl_gubun'          => $prepay['cl_gubun'] ? $prepay['cl_gubun']:1,
            'cl_name'           => $cl_name ? $cl_name:'',
            'cl_lang'           => '영어',
            'cl_time'           => $prepay['cl_time'],
            'cl_number'         => $prepay['cl_number'],
            'origin_cl_class'   => $prepay['cl_class'],
            'cl_class'          => $prepay['cl_class'],
            'cl_service'        => 0,
            'cl_month'          => $prepay['cl_month'],
            'hold_num'          => $prepay['hold_num'],
            'time_start'        => 0,
            'time_end'          => 23,
            'daytime_ok'        => '',
            'fee'               => $prepay['total_price'],
            'lesson_gubun'      => $prepay['lesson_gubun'],
            'hopedate'          => $prepay['hopedate'],
            'hopetime'          => $prepay['hopetime'],
            'schedule_ok'       => 'N',
            'payment'           => $pay_method,
            'pay_ok'            => $pay_ok,
            'pay_sum'           => $prepay['total_price'],
            'lesson_memo'       => $prepay['lesson_memo'],
            'regdate'           => date('Y-m-d H:i:s'),
            'relec_id'          => $relec_id,
            'skype'             => '',
            'lesson_state'      => '',
            'lesson_list_view'  => $pay_ok == 'N' ? 'N':'Y',
            'student_su'        => $prepay['student_su'],
            'student_uid'       => $prepay['student_uid'],
            'e_id'              => $prepay['e_id'],
            'lesson_number'     => $lesson_number,
            'lesson_count'      => $lesson_count,
            'lesson_total'      => $lesson_total_count['cnt']+1,
            'lesson_event'      => $lesson_event_total_count['cnt'],
            'lesson_refund'     => $lesson_refund_count['cnt'],
            'lesson_correction' => $lesson_correct_count['cnt'],
            'lesson_coupon'     => $lesson_coupon_count['cnt'],
            'disabled_extend'   => $disabled_extend,
            'tu_uid'            => $tu_uid ? $tu_uid:0,
            'tu_name'           => $tu_name ? $tu_name:'',
            'before_id'         => $before_id ? $before_id:0,
        ];
    
        $lesson_id = $CI->lesson_mdl->insert_wiz_lesson($lesson_param);

        if($lesson_id < 0)
        {
            return ['state'=>0];
        }
    
        $wiz_pay_param = [
            'pay_name'      => $cl_name.' 수강신청',
            'lesson_id'     => $lesson_id,
            'ji_uid'        => 0,
            'pay_ok'        => $pay_ok,
            'pay_tt'        => $prepay['total_price'],
            'pay_cash'      => $pay_cash,
            'pay_bank'      => $pay_bank,
            'pay_card'      => $pay_card,
            'pay_ars'       => $pay_ars,
            'pay_vbank'     => $pay_vbank,
            'bank_number'   => $pay_param['bank_number'] ? $pay_param['bank_number']:'',
            'receive_name'  => $wiz_member['wm_name'],
            'receive_date'  => $receive_date,
            'receive_mobile'=> $receive_mobile,
            'ipdate'        => $ipdate,
            'pay_regdate'   => date('Y-m-d H:i:s'),
            'order_no'      => $prepay['order_no'],
            'card_agreeno'  => $pay_param['card_agreeno'] ? $pay_param['card_agreeno']:'',
            'card_date'     => $pay_param['card_date'] ? $pay_param['card_date']:'',
            'card_orderno'  => $pay_param['card_orderno'] ? $pay_param['card_orderno']:'',
            'card_code'     => $pay_param['card_code'] ? $pay_param['card_code']:'',
            'card_name'     => $pay_param['card_name'] ? $pay_param['card_name']:'',
            'halbu_month'   => $pay_param['halbu_month'] ? $pay_param['halbu_month']:'0',
            'coupon_num'    => $pay_param['coupon_num'] ? $pay_param['coupon_num']:'',
            'org_price'     => $prepay['org_price'],
            'discount_price'=> $prepay['sms_dc_price'] + $prepay['dc_price'] + $prepay['event_dc_price'],
            'goods_num'     => $prepay['goods_id'],
            'goods_type'    => $prepay['goods_type'],
        ];

        $pay_id = $CI->payment_mdl->insert_wiz_pay($wiz_pay_param);

        if($pay_id < 0)
        {
            if($lesson_id > 0)
            {
                $CI->lesson_mdl->delete_wiz_lesson($lesson_id);
            }
            return ['state'=>0];
        }

        //wiz_prepay에 lesson_id 업뎃
        $CI->payment_mdl->update_wiz_prepay_lesson_id($prepay['prepay_id'], $lesson_id);

        if($prepay['goods_type'] =='5') 
        {
            if($pay_ok =='Y') 
            {
                $showYn = "y";
                $in_yn = "y";
            } 
            else 
            {
                $showYn = "n";
                $in_yn = "n";
            }

            //강사인센티브 (기본급 강사는 인센티브를 받지 않음)
            $wiz_tutor = $CI->tutor_mdl->get_tutor_info_by_tu_uid($relec_lesson['wl_tu_uid']);

            if($wiz_tutor['pay_type'] != 'a') 
            {
                if($wiz_tutor['group_id2']=="48" || $wiz_tutor['group_id2']=="52") $money = "200"; 
                else $money = "100";

                $incentive_param = [
                    'tu_uid'     => $relec_lesson['wl_tu_uid'],
                    'tu_id'      => $wiz_tutor['tu_id'],
                    'tu_name'    => $wiz_tutor['tu_name'],
                    'lesson_id'  => $lesson_id,
                    'uid'        => $wiz_member['wm_uid'],
                    'name'       => $wiz_member['wm_name'],
                    'money'      => $money,
                    'in_kind'    => '1',
                    'in_yn'      => $in_yn,
                    'regdate'    => date("Y-m-d H:i:s")
                ];
                $CI->tutor_mdl->insert_tutor_incentive($incentive_param);
            }

            //자동재수강 포인트 지급.
            $point = array(
                'uid'       => $wiz_member['wm_uid'],
                'name'      => $wiz_member['wm_name'],
                'point'     => $prepay['total_price'] / 10,
                'pt_name'   => '자동 재수강10% 적립 이벤트', 
                'lesson_id' => $prepay['goods_id'], 
                'kind'      => 'c', 
                'showYn'    => $showYn,
                'regdate'   => date("Y-m-d H:i:s")
            );

            $CI->point_mdl->set_wiz_point($point);

             // 자동재수강일 시만 메모 넣는다. CS 팀에서 해당 수업에 대해 기록하는 메모 정보 테이블. 
            $lesson_text = $CI->lesson_mdl->get_wiz_lesson_text($prepay['goods_id']);
            if($lesson_text)
            {
                $CI->lesson_mdl->insert_wiz_lesson_text([
                    'lesson_id' => $lesson_id,
                    'wiz_id' => $wiz_member['wm_wiz_id'],
                    'content' => $lesson_text['wlt_content'],
                ]);
            }

            //자동재수강 더이상 불가능하도록 업데이트
            $CI->lesson_mdl->update_wiz_lesson($prepay['goods_id'], [
                'newlesson_ok' => 'N',
                'order_gubun'  => '2',
            ]);
        }
        
        return ['state'=>1 ,'lesson_id'=>$lesson_id, 'pay_id'=>$pay_id, 'lesson_param' => $lesson_param];
    }
    catch(exception $e)
    {
        log_message('error', 'payment_insert_lesson_pay_exception :'.$e->getMessage());
        if($lesson_id > 0)
        {
            $CI->lesson_mdl->delete_wiz_lesson($lesson_id);
        }
        if($pay_id > 0)
        {
            $CI->payment_mdl->delete_wiz_pay($pay_id);
        }

        return [
            'state' => 0,
            'msg' => $e->getMessage(),
        ];
    }
    
}


function payment_set_promotion_benefit($wiz_member,$promotion_info,$payprice,$lesson_id=0,$cl_class=0,$cl_time=0){
    // 추가포인트 설정했으면 지급
    if($wiz_member['wm_uid'] && $promotion_info['addpoint'] > 0){
        $CI =& get_instance();

        //분수 별 포인트 지급양
        $timeToPoint = array(
            '10'=>5000,
            '20'=>10000,
            '30'=>15000,
        );
            
        //가격의 %
        if($promotion_info['point_type'] == 'per')
        {
            $addpoint = ($promotion_info['addpoint']/100) * $payprice;
        }
        //수업변환에 필요한 포인트의 %
        elseif($promotion_info['point_type'] == 'perclass')
        {
            $addpoint = $cl_class * $timeToPoint[$cl_time] * ($promotion_info['addpoint']/100);
        }
        else
        {
            $addpoint = (int)$promotion_info['addpoint'];
        }

        if($addpoint > 0){
            $content = $promotion_info['point_content'] ? $promotion_info['point_content']:'프로모션 인입 이벤트 적립';

            $point = array(
                'uid' => $wiz_member['wm_uid'],
                'name' => $wiz_member['wm_name'],
                'point' => $addpoint,
                'pt_name'=> $content, 
                'lesson_id'=> $lesson_id, 
                'kind'=> 'k', 
                'showYn'=> 'y',
                'regdate' => date("Y-m-d H:i:s")
            );

            /* 포인트 내역 입력 및 포인트 추가 */
            $CI->load->model('point_mdl');
            $CI->point_mdl->set_wiz_point($point);
        }
        
    }
}

//ars 요청
function payment_order_hubcard_call($post, $pay_config)
{
    //없으면 kcp모듈이 iconv utf8->euckr 해도 한글 전부 날려버리고 kcp에 값을 넘긴다.
    setlocale(LC_CTYPE, 'ko_KR.euc-kr');

    $CI =& get_instance();
    $CI->load->model('goods_mdl');
    $CI->load->model('payment_mdl');
    $CI->load->model('lesson_mdl');
    $CI->load->model('member_mdl');
    $CI->load->model('sms_mdl');
    
    //kcp 결제연동검증 라이브러리 인클루드
    require_once $pay_config['include_kcp_pp'];
    
    $g_conf_gw_url = $pay_config['g_conf_gw_url'];
    $g_wsdl = $pay_config['g_wsdl'];
    $g_conf_site_cd = $pay_config['g_conf_site_cd'];
    $g_conf_site_key = $pay_config['g_conf_site_key'];
    $g_conf_home_dir = $pay_config['g_conf_home_dir'];
    $g_conf_log_level = $pay_config['g_conf_log_level'];
    $g_conf_gw_port = $pay_config['g_conf_gw_port'];

    $prepay_id = (new OldEncrypt('(*&DHajaan=f0#)2'))->decrypt($post['prepay_id']);
    //임시 결제정보저장 테이블에서 데이터 가져오기
    $prepay = $CI->payment_mdl->row_prepay_by_prepay_id($prepay_id);

    if(!$prepay)
    {
        return [
            'state' => 0,
            'msg' => '잘못된 주문프로세스입니다.',
        ];
    }

    $wiz_member = $CI->member_mdl->get_wiz_member_by_wm_uid($prepay['uid']);

    $payDate = time()+86400;
	$payDate = date("YmdHis",$payDate);
    /* ============================================================================== */
    /* =   01. 지불 요청 정보 설정                                                  = */
    /* = -------------------------------------------------------------------------- = */
	$pay_method       = $post[ "pay_method"       ];       // 결제 방법
    $ordr_idxx        = $prepay[ "order_no"        ];       // 주문 번호
    $phon_mny         = $prepay[ "ars_request_price"         ];       // 결제 금액
    /* = -------------------------------------------------------------------------- = */
    $good_name        = iconv('utf-8','euc-kr',$prepay["cl_name"]);       // 상품 정보
	$buyr_name        = iconv('utf-8','euc-kr',$prepay["ars_order_name"]);       // 주문자 이름
    /* = -------------------------------------------------------------------------- = */
    $req_tx           = 'pay';       // 요청 종류
	/* = -------------------------------------------------------------------------- = */
    $comm_id          = 'SKT';       // 이동통신사코드
    $phon_no          = str_replace('-','',$prepay[ "ars_mobile_number"          ]);       // 전화번호
    $expr_dt          = $payDate;       // 결제 유효기간
    /* = -------------------------------------------------------------------------- = */
    $tx_cd            = "";                                 // 트랜잭션 코드
    /* = -------------------------------------------------------------------------- = */
	$res_cd           = "";                                 // 결과코드
    $res_msg          = "";                                 // 결과메시지
	$ars_trade_no     = "";                                 // 결제거래번호
    $app_time         = "";                                 // 처리시간
	/* = -------------------------------------------------------------------------- = */
    $card_no          = "";                                 // 카드번호
	$card_expiry      = "";                                 // 카드유효기간
    $card_quota       = "";                                 // 카드할부개월
	/* = -------------------------------------------------------------------------- = */
    $ars_tx_key       = "";                                 // ARS주문번호(ARS거래키)
	$ordr_nm          = "";                                 // 요청자 이름
    $site_nm          = "";                                 // 요청 사이트명
    /* = -------------------------------------------------------------------------- = */
    $cert_flg         = 'Y';      // 인증 비인증 구분
    $sig_flg          = "";                                 // 호전환 구분
    $vnum_no          = "";                                 // ARS 결제요청 전화번호
    /* = -------------------------------------------------------------------------- = */
	$ars_trade_no     = $post[ "ars_trade_no"      ];      // ARS 등록 거래번호
    /* ============================================================================== */
	
    /* ============================================================================== */
    /* =   02. 인스턴스 생성 및 초기화                                              = */
    /* = -------------------------------------------------------------------------- = */
    $c_PayPlus  = new C_PAYPLUS_CLI;
    $c_PayPlus->mf_clear();
    /* ============================================================================== */


    /* ============================================================================== */
    /* =   03. 처리 요청 정보 설정, 실행                                            = */
    /* = -------------------------------------------------------------------------- = */

    $result = ['state' => 0];

    /* = -------------------------------------------------------------------------- = */
    /* =   03-1. 승인 요청                                                          = */
    /* = -------------------------------------------------------------------------- = */
    // 업체 환경 정보
    $cust_ip    = getenv( "REMOTE_ADDR" );


    // 거래 등록 요청 시
    if ( $req_tx == "pay"  )
    {
        $tx_cd = "00100700";
        

        // 공통 정보
        $common_data_set = "";
    
    	$common_data_set .= $c_PayPlus->mf_set_data_us( "amount"   , $phon_mny    );
    	$common_data_set .= $c_PayPlus->mf_set_data_us( "cust_ip"  , $cust_ip  );

		$c_PayPlus->mf_add_payx_data( "common", $common_data_set );

        // 주문 정보

        $c_PayPlus->mf_set_ordr_data( "ordr_idxx",  $ordr_idxx );  // 주문 번호
        $c_PayPlus->mf_set_ordr_data( "good_name",  $good_name );  // 상품 정보
        $c_PayPlus->mf_set_ordr_data( "buyr_name",  $buyr_name );  // 주문자 이름

        // 요청 정보
     	$phon_data_set  = "";

        $phon_data_set .= $c_PayPlus->mf_set_data_us( "phon_mny", $phon_mny );  // 요청금액
        $phon_data_set .= $c_PayPlus->mf_set_data_us( "phon_no",  $phon_no  );  // 요청 전화번호
        $phon_data_set .= $c_PayPlus->mf_set_data_us( "comm_id",  $comm_id  );  // 이동통신사 코드

        if (!$expr_dt == "")
		{
			$phon_data_set .= $c_PayPlus->mf_set_data_us( "expr_dt",  $expr_dt  );  // 결제 유효기간
		}

        $phon_data_set .= $c_PayPlus->mf_set_data_us( "phon_txtype",  "11600000"  );  // 결제수단 설정
        $phon_data_set .= $c_PayPlus->mf_set_data_us( "cert_flg",  $cert_flg      );  // 인증, 비인증 설정
		
		$c_PayPlus->mf_add_payx_data( "phon",  $phon_data_set );
		
	}


    /* ============================================================================== */


    /* ============================================================================== */
    /* =   03-3. 실행                                                               = */
    /* ------------------------------------------------------------------------------ */
    if ( strlen($tx_cd) > 0 )
    {
        $c_PayPlus->mf_do_tx( "",                $g_conf_home_dir, $g_conf_site_cd,
                              $g_conf_site_key,  $tx_cd,           "",
                              $g_conf_gw_url,    $g_conf_gw_port,  "payplus_cli_slib",
                              $ordr_idxx,        $cust_ip,         "3",
                              "",                "0" );
    }
    else
    {
        $c_PayPlus->m_res_cd  = "9562";
        $c_PayPlus->m_res_msg = "연동 오류";
    }
    $res_cd  = $c_PayPlus->m_res_cd;                      // 결과 코드
    $res_msg = $c_PayPlus->m_res_msg;                     // 결과 메시지
   /* ============================================================================== */

    /* ============================================================================== */
    /* =   04. 승인 결과 처리                                                       = */
    /* = -------------------------------------------------------------------------- = */
    if ( $req_tx ==  "pay"  )
    {
        if ( $res_cd == "0000"  )
        {
    /* = -------------------------------------------------------------------------- = */
    /* =   04-1. 요청 결과 추출                                                     = */
    /* = -------------------------------------------------------------------------- = */
            $ars_trade_no  = $c_PayPlus->mf_get_res_data( "ars_trade_no"  );    // ARS 등록번호
            $app_time      = $c_PayPlus->mf_get_res_data( "app_time"      );    // 요청 시간
            $phon_mny      = $c_PayPlus->mf_get_res_data( "phon_mny"      );    // 요청 금액
			$phon_no       = $c_PayPlus->mf_get_res_data( "phon_no"       );    // 요청 전화 or 핸드폰 번호
            $expr_dt       = $c_PayPlus->mf_get_res_data( "expr_dt"       );    // 결제 유효기간

            $ordr_idxx     = $c_PayPlus->mf_get_res_data( "ordr_idxx"     );    // 가맹점 주문번호
			$good_name     = $c_PayPlus->mf_get_res_data( "good_name"     );    // 상품명
            $ordr_nm       = $c_PayPlus->mf_get_res_data( "ordr_nm"       );    // 요청자 이름

            $site_nm       = $c_PayPlus->mf_get_res_data( "site_nm"       );    // 가맹점 사이트 명

			$cert_flg      = $c_PayPlus->mf_get_res_data( "cert_flg"      );    // 인증 or 비인증 구분
            $sig_flg       = $c_PayPlus->mf_get_res_data( "sig_flg"       );    // 호전환 구분
            $vnum_no       = $c_PayPlus->mf_get_res_data( "vnum_no"       );    // ARS 결제요청 전화번호

            $result = ['state' => 1];
        }    // End of [res_cd = "0000"]

    /* = -------------------------------------------------------------------------- = */
    /* =   04-2. 승인 실패를 업체 자체적으로 DB 처리 작업하시는 부분입니다.         = */
    /* = -------------------------------------------------------------------------- = */
        else
        {
            $result['msg'] = '('.$res_cd.')'. iconv('euc-kr','utf-8',$res_msg);
        }
    }
    /* ============================================================================== */
    
    log_message('error', 'ars :'.iconv('euc-kr','utf-8',$res_msg).$buyr_name);
    /* ============================================================================== */
    /* =   05. 폼 구성 및 결과페이지 호출                                           = */
    /* ============================================================================== */


    return $result;

}

//이벤트 상품 사용가능한 상태인지 체크
function payment_valid_event_goods($wiz_member, $goods)
{
    if(!$wiz_member['wm_uid']) return '';
    if(!$goods || $goods['me_e_use'] != 'y') return '종료된 이벤트입니다.';

    $CI =& get_instance();
    $CI->load->model('goods_mdl');
    $CI->load->model('lesson_mdl');
    $CI->load->model('member_mdl');

    $where = ' WHERE wl.uid = '.$wiz_member['wm_uid'];

    $check = $CI->lesson_mdl->list_count_lesson($where);
    $leCnt1 = $check['cnt'];

    $check = $CI->lesson_mdl->list_count_lesson($where." AND wl.startday <='".date('Y-m-d')."' AND wl.endday >='".date('Y-m-d')."'");
    $leCnt2 = $check['cnt'];

    $leCnt3 = $CI->lesson_mdl->get_lesson_endday_desc_limit1($wiz_member['wm_uid']);
    
    $e_kind_arr = explode('-',$goods['me_e_kind']);
    $eKind = substr($goods['me_e_kind'],0,5);

    if($eKind == "Y-N-N") 
    {
        if($leCnt1 > 0) return '수업중인 회원이거나 한번 본페이지를 이용하신분은 결제하실 수 없습니다.';
    }
    elseif($eKind == "Y-Y-N") 
    {   // 신규+재강
        if($leCnt1 == 0 || $leCnt2 > 0) $leCnt = 0;
        else $leCnt = 1;
        if($leCnt > 0) return '신규회원 또는 수업중인 회원만 결제하실 수 있습니다.';
    }
    elseif($eKind == "Y-N-Y") 
    {   // 신규+종료
        if($leCnt1 > 0 && ($leCnt3['endday'] && $leCnt3['endday'] >= date("Y-m-d")) ) $leCnt = 1;
        else $leCnt = 0;
        if($leCnt > 0) return '신규회원 또는 수업종료된 회원만 결제하실 수 있습니다.';
    }
    elseif($eKind == "N-Y-N") 
    {   // 재강
        if($leCnt2 > 0) $leCnt = 0;
        else $leCnt = 1;
        if($leCnt > 0) return '수업중인 회원만 결제하실 수 있습니다.';
    }
    elseif($eKind == "N-Y-Y") 
    {   // 재강 + 종료
        if($leCnt1 > 0) $leCnt = 0;
        else $leCnt = 1;
        if($leCnt > 0) return '수업을 한번이라도 받으셨던 회원만 결제하실 수 있습니다.';
    }
    elseif($eKind == "N-N-Y") 
    {   // 종료
        if($leCnt3['endday'] && $leCnt3['endday'] >= date("Y-m-d")) $leCnt = 1;
        else $leCnt = 0;
        if($leCnt > 0) return '수업종료된 회원만 결제하실 수 있습니다.';
    }

    if($e_kind_arr[4] =='Y')
    {
        $check = $CI->lesson_mdl->list_count_lesson($where." AND wl.e_id = ".$goods['meg_e_id']."");
        $leCnt4 = $check['cnt'];

        if($leCnt4 > 0) $leCnt = 1;
        else $leCnt = "0";
        if($leCnt > 0) return '이벤트 이용은 한번만 가능합니다.';
    }

    // 딜러가 설정되어있을때
    if($goods['me_d_id'] && $e_kind_arr[3] =='Y')
    {
        $wiz_dealer = $CI->member_mdl->get_wiz_dealer($goods['me_d_id']);
        if($goods['me_d_id'] != $wiz_member['wm_d_did'])
        {
            return $wiz_dealer['d_name']. ' 회원만 이용하실수 있습니다.';
        }
    }

    return '';
}
 
function payment_pay_method_open($pay_card_only, $admin)
{
    //$admin = 1;
    //$pay_card_only = 1;
    //선택가능한 결제 방법
    $pay_method = [
        'card' => 1,    //신용카드
        'bank' => 1,    //계좌이체
        'samsung' => 0,
        'vbank' => !$pay_card_only ? 1:0,   //가상계좌
        'hubcard'   => !$pay_card_only && $admin ? 1:0,    //ARS-관리자가 로그인하여 접근했을때만 오픈
        'cash'  => !$pay_card_only && $admin ? 1:0,        //무통장-관리자가 로그인하여 접근했을때만 오픈
    ];

    /* $pay_method['vbank'] = 0;
    $pay_method['hubcard'] = 0;

    $wiz_member = base_get_wiz_member();
    if($wiz_member['wm_wiz_id'] == 'hjk081212@gmail.com' || $wiz_member['wm_wiz_id'] == 'kibum360@gmail.com'){
        $pay_method['vbank'] = 1;
        $pay_method['hubcard'] = 1;
    } */

    return $pay_method;
}

function payment_default_dc_rate()
{
    return [
        1 => 0,
        3 => 5,
        6 => 10,
        12 => 40,
    ];
}

/*
    감사제 할인 이벤트 체크. 7월8일-7월18일
    -6개월 45%, 12개월 65% 할인가 적용 (1개월, 3개월 상품은 할인대상에서 제외)
    -민트영어의 모든 수강상품 중 1건 이상 구매이력이 있는 계정의 회원 (1계정 당 1번만 할인가 적용)
    -환불 이력 있을 시 감사제 이벤트 할인가 수강신청 불가
    
    추가할인률을 리턴한다.
*/
function payment_special_dc_event($wiz_member)
{
    return false;

    $allow_wiz_id = [
        //'hjk081212@gmail.com',
        //'kibum360@gmail.com',
    ];
    
    if(!(date('Y-m-d H:i:s') > '2021-07-01 00:00:00' && date('Y-m-d H:i:s') < '2021-07-12 23:59:59') && !in_array($wiz_member['wm_wiz_id'], $allow_wiz_id)) return false;

    if(!$wiz_member['wm_uid']) return false;
    //본사, 구민트 딜러만 허용
    $allow_d_id = [
        '16',
        '17',
        '356',
        '352',
        '316',
        '25',
        '242',
        '241',
        '238',
        '177',
        '82',
        '79',
        '26',
        '23',
    ];

    if(!in_array($wiz_member['wm_d_did'], $allow_d_id)) return false;

    $CI =& get_instance();
    $CI->load->model('payment_mdl');

    //본 이벤트로 이미 혜택을 받았는지 확인 wiz_pay goods_type: 7
    $where = " WHERE wl.uid=".$wiz_member['wm_uid']." AND wp.goods_type=7 AND wp.pay_ok='Y'";
    $check = $CI->payment_mdl->list_count_lesson_pay('',$where);

    if($check['cnt'] > 0) return false;

    //환불내역 있는지 확인
    $where = " WHERE wl.uid=".$wiz_member['wm_uid']." AND wp.refund_ok='Y'";
    $check = $CI->payment_mdl->list_count_lesson_pay('',$where);
    
    if($check['cnt'] > 0) return false;

    //정규상품(첨삭제외) 결제내역 있어야 이벤트 혜택받는다
    $check = $CI->payment_mdl->check_paid_history_for_first_pay($wiz_member['wm_uid']);

    if($check['cnt'] == 0) return false;

    $rate = [
        1 => 0,
        3 => 0,
        6 => 35,
        12 => 25,
    ];

    return $rate;
}

/*
    수업변환 100% 변환할수 있는 포인트 지급
    안드로이드 어플로 첫결제시에만 지급
*/
function payment_android_addpoint_event()
{
    //return false;
    $CI =& get_instance();
    $CI->load->model('payment_mdl');

    $wiz_member = base_get_wiz_member();

    if(!$wiz_member) return false;

    /* $allow_wiz_id = [
        'hjk081212@gmail.com',
        'kibum360@gmail.com',
    ]; */
    
    //if(in_array($wiz_member['wm_wiz_id'], $allow_wiz_id)) return true;


    if(!common_is_app('android')) return false;

    //테스트중 허용아이디 아니면 진입금지
    //if(!in_array($wiz_member['wm_wiz_id'], $allow_wiz_id)) return false;

    //수강기록 있으면 안된다
    $check_paid_history = $CI->payment_mdl->check_paid_history_for_first_pay($wiz_member['wm_uid']);
    
    return $check_paid_history['cnt'] < 1 ? true:false;
}

?>
