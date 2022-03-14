<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Payment extends _Base_Controller {
    //무통장 번호
    public $mint_bank_number = '농협 188-01-039626';
    public $kcp_g_wsdl = APPPATH.'/third_party/kcp/real_KCPPaymentService.wsdl';
    public $g_conf_gw_url = 'paygw.kcp.co.kr';
    public $g_conf_home_dir = APPPATH.'/third_party/kcp';
    public $g_conf_gw_port = '8090';
    public $module_type = '01';

    //KCP 연동계정
    public $kcp_mint_account_kv = [
        'IP01C' => '07MF2BdQ1xgic-fboeQ.gOS__',
        'IP03X' => '32SIkQbIVnJGodJqew2bEQ6__',
        'IP01D' => '3nOW7OelGY9at0lYQ37ihTl__',
        'IP04O' => '2hbBULtUhzk2QEcYOCkL1O1__',
    ];
    //KCP 연동계정
    public $kcp_mint_account = [
        //카드연동 시
        'card' => [
            'first_pay' => 'IP01C', //첫결제 mint05
            'common_pay' => 'IP03X' //첫결제 아닐때 mint05_2
        ],
        //ARS 연동 시
        'ars' => [
            'first_pay' => 'IP01D', // mint05_ars1
            'common_pay' => 'IP04O' // mint05_ars12
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }


    public function testpg()
    {
        $this->load->model('payment_mdl');
        $aa= $this->payment_mdl->testpg();
        setlocale(LC_CTYPE, 'ko_KR.euc-kr');
        echo '<xmp>';
        print_r(iconv('euc-kr','utf-8',($aa['payinfo'])));
        echo '</xmp>';
        exit;
    }


    // 결제 내역 리스트
    public function list_()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "start" => trim($this->input->post('start')) ? trim($this->input->post('start')):0,
            "limit" => trim($this->input->post('limit')) ? trim($this->input->post('limit')):5,
            "order_field" => ($this->input->post('order_field')) ? trim(strtolower($this->input->post('order_field'))) : "wp.pay_id",
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

        /* 회원 확인 */
        $wiz_member = base_get_wiz_member();
        if(!$wiz_member)
        {
            echo json_encode(base_get_err_auth_check_msg());
            exit;
        }

        $this->load->model('payment_mdl');

        $count_index = ""; 
        $where = "WHERE wl.uid = ".$wiz_member['wm_uid']." AND wl.payment!='coupon:'";
        
        $list_cnt = $this->payment_mdl->list_count_lesson_pay($count_index, $where);

        if($list_cnt['cnt'] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
            echo json_encode($return_array);
            exit;
        }
        
        $index = "";
        $limit = "";
        $select_col_content = '';
        
        if($request['limit'] > 0)
        {   
            $limit = sprintf("LIMIT %s , %s", $request['start'], $request['limit']);
        }
        
        $order = sprintf("ORDER BY %s %s", $request['order_field'], $request['order']);
        
        $list = $this->payment_mdl->list_lesson_pay($index, $where, $order, $limit, $select_col_content);

        foreach($list as $key=>$val)
        {
            if(strpos($val['wl_cl_name'], "첨삭") !== false)
            {
                $list[$key]['wl_lesson_gubun'] = 'W';
            }
            
            // 코드형태로 되어있는 결제수단 한글명칭으로 변경
            $list[$key]['pay_method'] = payment_code_to_str($val['wl_payment']);
            // 수강명칭 분수 치환
            $list[$key]['wl_cl_name'] = lesson_replace_cl_name_minute($val['wl_cl_name'], $val['wl_lesson_gubun']);
            // 출석부를 등록했는지, 등록전인지, 종료되었는지. 한글로 변환
            $list[$key]['lesson_regist_state'] = lesson_regist_state_to_str($val['wl_schedule_ok'], $val['wl_lesson_state']);
            // 입금상태
            $list[$key]['lesson_pay_state'] = lesson_pay_state_to_str($val['wp_pay_ok'], $val['wp_refund_ok']);
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['total_cnt'] = $list_cnt['cnt'];
        $return_array['data']['list'] = $list;
        echo json_encode($return_array);
        exit;
        
    }

    // 결제 내역 상세
    public function info()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "lesson_id" => trim($this->input->post('lesson_id')),
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

        $this->load->model('payment_mdl');
        
        $info = $this->payment_mdl->row_lesson_pay_info($request['lesson_id'], $wiz_member['wm_uid']);
        if($info)
        {
            if(strpos($info['wl_cl_name'], "첨삭") !== false)
            {
                $info['wl_lesson_gubun'] = 'W';
            }
            $info['lesson_gubun_str'] = lesson_gubun_to_str($info['wl_lesson_gubun']);
            // 코드형태로 되어있는 결제수단 한글명칭으로 변경
            $info['pay_method'] = payment_code_to_str($info['wl_payment']);
            // 수강명칭 분수 치환
            $info['wl_cl_name'] = lesson_replace_cl_name_minute($info['wl_cl_name'], $info['wl_lesson_gubun']);
            $info['wl_cl_time'] = lesson_replace_cl_name_minute($info['wl_cl_time'], $info['wl_lesson_gubun'], true);
            // 영어첨삭인지 확인
            $info['is_correction'] = strpos($info['wl_cl_name'], "첨삭") !== false ? 1:0;
            // 출석부를 등록했는지, 등록전인지, 종료되었는지. 한글로 변환
            $info['lesson_regist_state'] = lesson_regist_state_to_str($info['wl_schedule_ok'], $info['wl_lesson_state']);
            // 입금상태
            $info['lesson_pay_state'] = lesson_pay_state_to_str($info['wp_pay_ok'], $info['wp_refund_ok']);
            // 입금정보 분할
            if($info['wp_bank_number'])
            {
                $bank_info = explode(' ', $info['wp_bank_number']);
                $info['split_bank_name'] = $bank_info[0];
                $info['split_bank_number'] = $bank_info[1];
            }

            $where = ' AND wlr.checked_certificate=1 ';     // 수강증
            // 수강증 있는지 체크. 없으면 프론트에서 수강증 버튼 보여주지 말아야한다.
            $splitinfo = $this->payment_mdl->list_lesson_receipt($request['lesson_id'], $wiz_member['wm_uid'], $where);

            $info['has_receipt']= $splitinfo ? 1:0;

            $return_array['res_code'] = '0000';
            $return_array['msg'] = "";
            $return_array['data']['info'] = $info;
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
        }
        
        echo json_encode($return_array);
        exit;
    }
    

    /* 
        영수증, 수강증 -> 둘다 같은 내용을 보여준다.(분할 테이블 공유)
        출석증은 다른내용으로 리턴(분할 테이블이 위와는 별개)
    */
    public function receipt()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "lesson_id" => trim($this->input->post('lesson_id')),
            "type" => trim($this->input->post('type')),     // 1:영수증 , 2:수강증
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

        $this->load->model('payment_mdl');
        $this->load->model('lesson_mdl');

        $where = '';
        $is_split = '1';

        // 출석증 정보
        if($request['type'] =='3')
        {
            // 분할 출석증 있는지 체크
            $info = $this->payment_mdl->list_lesson_attendance($request['lesson_id'], $wiz_member['wm_uid']);
            
            // 분할된게 없으면 wiz_lesson 정보롤 사용한다.
            if(!$info)
            {
                $info = null;
                $lesson_info = $this->lesson_mdl->row_wiz_lesson_by_lesson_id($request['lesson_id'], $wiz_member['wm_uid']);
                if($lesson_info){
                    $lesson_stats = lesson_progress_rate($lesson_info);
                    $lesson_info = array_merge($lesson_info, $lesson_stats);
                    
                    // 수강명칭 분수 치환
                    $lesson_info['wl_cl_name'] = lesson_replace_cl_name_minute($lesson_info['wl_cl_name'], $lesson_info['wl_lesson_gubun']);
                    // 분할테이블의 필드명과 맞춰주기 위한 작업
                    $lesson_info['wla_present_count'] = $lesson_info['wl_tt_2'] .'/'. $lesson_stats['lesson_off'];
                    $lesson_info['wla_absent_count'] = $lesson_info['wl_tt_3'] + $lesson_info['wl_tt_3_1'];

                    // 수업 시작시간, 종료시간 구하기
                    if($lesson_info['wl_cl_gubun'] !='2')
                    {
                        $lesson_info['wla_starttime'] = $lesson_info['wl_stime2'];
                        $cl_time = lesson_replace_cl_name_minute($lesson_info['wl_cl_time'], $lesson_info['wl_lesson_gubun'],true);
                        $lesson_info['wla_endtime'] = date('H:i',strtotime('+'.$cl_time.' minutes', strtotime(date('Y-m-d ').$lesson_info['wl_stime2'])));
                    }

                    // 분할인 경우와 리턴 형태 맞춰주기 위해 배열로 담는다.
                    if($lesson_info) $info[] = $lesson_info;
                }
                
                $is_split = 0;
            }
        }
        else
        {
            if($request['type'] =='1')      
            {
                $where = ' AND wlr.checked_receipt=1 ';     // 영수증
            }
            else        
            {
                $where = ' AND wlr.checked_certificate=1 ';     // 수강증
            }
            
            // 분할 영수증, 수강증 있는지 체크
            $info = $this->payment_mdl->list_lesson_receipt($request['lesson_id'], $wiz_member['wm_uid'], $where);
    
            // 분할된게 없으면 wiz_lesson 정보롤 사용한다.
            if(!$info)
            {
                $info = null;
                $lesson_info = $this->lesson_mdl->row_wiz_lesson_by_lesson_id($request['lesson_id'], $wiz_member['wm_uid']);
                if($lesson_info){
                    // 전체 수강증 정보는 수업이 종료되야 보여줘야한다.
                    if($request['type'] =='2' && ($lesson_info['wl_endday'] > date('Y-m-d') || $lesson_info['wl_endday'] == '0000-00-00'))
                    {
                        $return_array['res_code'] = '0900';
                        $return_array['msg'] = "프로세스오류";
                        $return_array['data']['err_code'] = "0801";
                        $return_array['data']['err_msg'] = "수업이 종료되지 않아 수강증을 조회할 수 없습니다.";
                        echo json_encode($return_array);
                        exit;
                    }
                    // 수강명칭 분수 치환
                    $lesson_info['wl_cl_name'] = lesson_replace_cl_name_minute($lesson_info['wl_cl_name'], $lesson_info['wl_lesson_gubun']);

                    // 분할인 경우와 리턴 형태 맞춰주기 위해 배열로 담는다.
                    if($lesson_info) $info[] = $lesson_info;
                }
                
                $is_split = 0;
            }
        }
        

        if($info)
        {        
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "";
            $return_array['data']['is_split'] = $is_split;
            $return_array['data']['info'] = $info;
            $return_array['data']['eduserve'] = common_eduserve_info();     // 에듀서브 정보
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = "0201";
            $return_array['data']['err_msg'] = "등록된 데이터가 없습니다.";
        }
        
        echo json_encode($return_array);
        exit;
    }

    
    /* 
        수강상품 페이지 정보
    */
    public function order_goods()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "sms_promotion_code" => trim($this->input->post('sms_promotion_code')),
        );

        $wiz_member = base_get_wiz_member();

        $this->load->model('goods_mdl');
        $this->load->model('lesson_mdl');
        $this->load->model('payment_mdl');

        $dealer_fee_info = null;
        $wiz_dealer = null;
        $goods_list = null;
        $active_lesson_list = null;
        $today = date('Y-m-d');
        $custom_goods = null;
        $special_dc = null;

        //자가부담금 있는 딜러 하위 회원이라면 별도의 상품으로 결제한다.
        if($wiz_member && $wiz_member['wd_has_member_fee'])
        {
            $check_dealer_valid = payment_check_dealer_member_pay_valid($wiz_member['wm_uid'], $wiz_member['wm_d_did']);

            if($check_dealer_valid['state'] === false && $check_dealer_valid['msg'] !='common')
            {
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = "0802";
                $return_array['data']['err_msg'] = $check_dealer_valid['msg'];
                echo json_encode($return_array);
                exit;
            }
            elseif($check_dealer_valid['state'] === true)
            {
                $wiz_dealer = $check_dealer_valid['wiz_dealer'];
                $dealer_fee_info = array(
                    'class_month'=> $wiz_dealer['class_month']
                );

                // 1차: 폰 or mel, 2차: 분, 3차: 횟수 으로 배열 구성. {"m_2_10":"1000","m_2_20":"2000","e_3_15":"5000","e_3_25":"10000"}
                foreach($check_dealer_valid['dealer_fee'] as $key=>$val)
                {
                    $split_key = explode('_',$key);
                    $dealer_fee_info['goods'][strtoupper($split_key[0])][$split_key[2]][$split_key[1]] = $val;
                }
            }

        }
        else
        {
            //SMS광고 프로모션 인입 체크
            $sms_promotion_info = payment_sms_promotion_info($wiz_member['wm_uid'], $request['sms_promotion_code']);
            
            //네오텍 결제기록 없으면 V는 노출시키지 말아야한다.
            $list = $this->goods_mdl->list_mint_goods_regular();
            $check_exist_neoteck_pay = lesson_check_exist_neoteck_pay($wiz_member['wm_uid']); 

            $special_dc = payment_special_dc_event($wiz_member);

            $goods_list = array();
            //상품리스트 루프 돌려 정리
            foreach($list as $row)
            {
                if(!$check_exist_neoteck_pay && $row['mg_l_gubun'] =='V') continue;
                $mg_l_time = $row['mg_l_time'];

                //첨삭
                if($row['mg_g_id'] =='56')
                {
                    //임의로 W 지정
                    $row['mg_l_gubun'] = 'W';

                    $row['final_price'] = $row['final_price'] ? $row['final_price']:$row['mg_price'];
                    $goods_list[$row['mg_l_gubun']] = $row;
                }
                //정규수업상품
                else
                {
                    $dc_rate = $row['mg_org_price'] ? round(($row['mg_org_price']-$row['mg_price']) / $row['mg_org_price'] * 100): 0;

                    //추가 할인. MEL만, 7분과정은 제외
                    if($special_dc !== false && is_array($special_dc) && $row['mg_l_gubun']=='E' && $mg_l_time !='10')
                    {
                        $event_rate = $special_dc[$row['mg_l_month']];
                        $add_dc_price = ceil($row['mg_org_price'] * (($event_rate + $dc_rate)/100));	// 원래 금액에서 할인금액 재계산
                        $row['final_price'] = $row['mg_org_price'] - $add_dc_price;	                    // 원래금액 - 할인금액 = 결제금액
                    }
                    //sms광고 인입 추가 할인 금액 설정
                    elseif($sms_promotion_info && $sms_promotion_info['discount_rate'])
                    {
                        $sms_dc_rate = $sms_promotion_info['discount_rate'];
                        $sms_dc_price = ceil($row['mg_org_price'] * (($sms_dc_rate + $dc_rate)/100));	// 원래 금액에서 할인금액 재계산
                        $row['final_price'] = $row['mg_org_price'] - $sms_dc_price;	                    // 원래금액 - 할인금액 = 결제금액 
                    }
                    
                    $row['final_price'] = $row['final_price'] ? $row['final_price']:$row['mg_price'];
                    $goods_list[$row['mg_l_gubun']][$row['mg_l_month']][$mg_l_time][] = $row;
                }
                
            }
            
            if($wiz_member)
            {
        
                //재수강신청하기, 자동재수강 신청하기, 재강불가, 연장완료 버튼 생성을 위한 정보도 가져와야한다(나의 수강상품 목록)
                $list = $this->lesson_mdl->class_list($wiz_member['wm_uid']);
                if($list)
                {
                    $later =  date("Y-m-d", time() + 86400*7);

                    foreach($list as $row)
                    {
                        //자유수업 출석부는 목록에서 제외
                        if($row['wl_cl_gubun'] =='2') continue;

                        $row['label'] = $row['wl_cl_name2'] ? $row['wl_cl_name2']:$row['wl_cl_name'];

                        if($row['wl_tu_uid'] =='158')
                        {
                            $row['label'] = "장기연기";
                        }
                        elseif($row['wl_tu_uid'] =='153' || strpos($row['label'], "영어첨삭") !== false )
                        {
                            $row['label'] = "영어첨삭지도";
                            $row['wl_lesson_gubun'] = 'W';
                        }
                        else
                        {
                            $row['label'] = lesson_replace_cl_name_minute($row['label'], $row['wl_lesson_gubun']);
                        }

                        //$row['label2'] = $row['label']." [".$row['wl_startday']."~".$row['wl_endday']."]";
                        
                        $row['extent_btn'] = '';
                        //수업형태 변경이력있으면
                        if($row['wl_disabled_extend']) $row['extent_btn'] = '재강불가';
                        //자동재수강 완료 시
                        elseif($row['wl_newlesson_ok'] =='N') $row['extent_btn'] = '연장완료';
                        //그룹수업은 불가
                        elseif($row['wl_student_su'] <= 2) 
                        {
                            //무료 및 이벤트, 일반상품
                            if($row['wl_pay_sum'] <= 1000 || $row['wl_e_id'] || $row['wl_cl_id']) 
                            {
                                $row['extent_btn'] = '재강불가';
                            }
                            else
                            {
                                //7일남았으면 자동재수강 신청버튼 오픈
                                if(($row['wl_lesson_state'] =='finished' || $row['wl_lesson_state'] =='in class') && $row['wl_endday'] <=$later)
                                {
                                    $row['extent_btn'] = '자동재수강 신청하기';
                                }
                                else
                                {
                                    //재수강신청은 별기능없이 order 페이지에서 자동선택해주는것뿐이다.
                                    $row['extent_btn'] = '재수강 신청하기';
                                }
                            }
                        }
                        
                        $active_lesson_list[] = $row;
                    }
                    
                }

                //맞춤상품리스트
                $custom_goods = $this->goods_mdl->get_custom_goods($wiz_member['wm_wiz_id'], $today, $today);

            }

        }

        //첫결제인지
        $check_first_pay = $this->payment_mdl->check_paid_history_for_first_pay($wiz_member['wm_uid']);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        //정규상품(goods_list) OR 자가부담금 있는 딜러 회원 상품(dealer_fee_info) 둘중 하나만 세팅되어있을것이며, 하나만 사용해야한다. 둘다 사용하는 경우는 없다.
        $return_array['data']['goods_list'] = $goods_list;        
        $return_array['data']['dealer_fee_info'] = $dealer_fee_info;
        $return_array['data']['my_active_lesson'] = $active_lesson_list;
        $return_array['data']['sms_promotion_info'] = $sms_promotion_info;
        $return_array['data']['custom_goods'] = $custom_goods;
        $return_array['data']['is_first_payment'] = $check_first_pay['cnt'] > 0 ? 0:1;
        $return_array['data']['special_dc'] = $special_dc;
        $return_array['data']['android_app_event'] = payment_android_addpoint_event();
        
        echo json_encode($return_array);
        exit;
    }

    /* 
        선택한 상품 확인하는 부분. 결제 전 확인 페이지
        아래와 같은 상품들이 존재한다.
        goods_type:        타입별goods_id:
        1:일반상품,                 mint_goods -> g_id
        2:이벤트상품,               mint_event_goods -> uid
        3:맞춤상품.                 wiz_class -> cl_id
        4:딜러회원자가부담금,        wiz_dealer -> fee_info json데이터의 key. goods_id 필드가 varchar인 이유.
        5:자동재수강,               wiz_lesson -> lesson_id
        6:첨삭                      mint_goods -> g_id(56번)
        7:감사제 이벤트 일반상품     mint_goods -> g_id(db에 체크만. 프로그램 로직은 1번)
        8:안드어플 이벤트 일반상품     mint_goods -> g_id(db에 체크만. 프로그램 로직은 1번)
    */
    public function order_goods_confirm()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "sms_promotion_code" => trim($this->input->post('sms_promotion_code')),     //광고SMS 코드
            "goods_id" => trim($this->input->post('goods_id')),                         //상품 고유id.
            "goods_type" => trim($this->input->post('goods_type')),                     //함수 주석과 같은 타입들이 존재
            "cl_gubun" => trim($this->input->post('cl_gubun')) =='free' ? 2:1, //2:자유수업,1:고정수업
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

        $admin = base_get_login_admin_id();

        $this->load->model('goods_mdl');
        $this->load->model('payment_mdl');
        $this->load->model('lesson_mdl');

        //선택된 상품번호로 결제정보 정리해서 가져온다.
        $info = payment_goods_payinfo($wiz_member, $request);

        if($info['state'] === false)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = $info['err_code'];
            $return_array['data']['err_msg'] = $info['err_msg'];
            echo json_encode($return_array);
            exit;
        }

        //결제정보
        $pay_info = $info['payinfo'];
        //카드결제만 허용할지 여부
        $pay_card_only = $info['payinfo']['pay_card_only'];

        //첫결제 인지 아닌지 확인. 첫결제는 무이자 12개월할부가 가능하다.
        $check = $this->payment_mdl->check_paid_history_for_first_pay($wiz_member['wm_uid']);
        $pay_info['is_first_payment'] = $check['cnt'] > 0 ? 0:1;
        $pay_info['apply_date'] = date('Y-m-d');

        $pay_method = payment_pay_method_open($pay_card_only, $admin);
        
        //결제페이지 방문로그
        $this->payment_mdl->insert_mint_pay_visit_log($wiz_member['wm_uid']);

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['pay_info'] = $pay_info;
        $return_array['data']['retakeLessonInfo'] = $info['retakeLessonInfo'];
        $return_array['data']['pay_method'] = $pay_method;

        echo json_encode($return_array);
        exit;
    }


    /*
        주문확인 페이지에서 입력 다하고 결제 누르면 일단 호출.
        입력한 내용이 유효한지 체크하고 정상이라면 임시주문 테이블에 관련 내용INSERT.
        그리고 insert key를 리턴 시킨다. 해당키는 결제연동에 사용된다.
        goods_type 1:일반상품,2:이벤트상품,3:맞춤상품.4:딜러회원자가부담금,5:자동재수강,6:첨삭,7:감사제 이벤트 일반상품,8:안드어플 이벤트 일반상품

        주문 시 무조건 호출하여, 이곳에서 검증한 후 wiz_prepay테이블에 필요한 정보를 넣어놓는다.
        주문완료 시 wiz_lesson, wiz_pay에 insert시, wiz_prepay에 들어있는 검증된 데이터를 최대한 사용한다.
        대부분의 상황에서는 prepay에 미리 검증해놓은 데이터 그대로 프리패스하여 사용해도 되지만,

        만약 강사,시간 지정 후 결제와 같은 경우 수강신청과 결제텀이 길수록 중간에 다른사람이 선점했을 수 있으니 해당 강사 해당시간에 비어있는지는 체크해야한다.
        그러나 이경우 특정 결제시간동안은 다른사람이 못들어오게 막아놓는다면 해당시간이 비어있는지 체크할 필요성은 현저히 줄어즌다. ->특정결제 시간 이후에 결제 안됐으면 막아놓은것 풀어야함
    */
    public function order_prepay()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "sms_promotion_code" => trim($this->input->post('sms_promotion_code')),     //광고SMS 코드
            "goods_id" => trim($this->input->post('goods_id')),                         //상품 고유id
            "goods_type" => trim($this->input->post('goods_type')),                     //order_goods_confirm 의 type과 동일한 값
            "cl_gubun" => trim($this->input->post('cl_gubun')) == 'free' ? 2:1,          //1:고정수업,2:자유수업
            "pay_method" => trim($this->input->post('pay_method')),                     //결제수단
            "mobile" => trim($this->input->post('mobile')),                             //연락처
            "tel" => trim($this->input->post('tel')),                                   //연락처
            "lesson_memo" => trim($this->input->post('lesson_memo')),                          //특이사항
            "receive_mobile" => trim($this->input->post('receive_mobile')),             //입금자 연락처
            "receive_name" => trim($this->input->post('receive_name')),
            "receive_date" => trim($this->input->post('receive_date')),
            "bank_number" => trim($this->input->post('bank_number')),
            "tu_uid" => trim($this->input->post('tu_uid')),
            "book_id" => trim($this->input->post('book_id')),
            "send_sms_cash" => trim($this->input->post('send_sms_cash')),               //휴대폰으로 무통장 입금 계좌번호 전송받기
            "ars_mobile_number" => trim($this->input->post('ars_mobile_number')),       //ars결제요청 받은 핸드폰번호
            "ars_order_name" => trim($this->input->post('ars_order_name')),             //ars결제요청 주문자명
            "ars_request_price" => trim($this->input->post('ars_request_price')),       //ars결제요청 받은 금액(분할로 요청할수 있다)
            "retake_lesson_month" => trim($this->input->post('retake_lesson_month')),   //자동재수강 시 결제할 개월
            "hopedate" => trim($this->input->post('hopedate')),                 //선택한 수업희망일
            "hopetime" => trim($this->input->post('hopetime')),                         //선택한 수업희망시간
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

        $admin = base_get_login_admin_id();

        $this->load->model('goods_mdl');
        $this->load->model('payment_mdl');
        $this->load->model('lesson_mdl');

        //선택된 상품번호로 결제정보 정리해서 가져온다.
        $info = payment_goods_payinfo($wiz_member, $request);

        if($info['state'] === false)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = $info['err_code'];
            $return_array['data']['err_msg'] = $info['err_msg'];
            echo json_encode($return_array);
            exit;
        }

        //결제정보
        $pay_info = $info['payinfo'];
        //결제정보
        $goods = $info['goods'];

        //카드결제만 허용할지 여부
        $pay_card_only = $info['payinfo']['pay_card_only'];

        //첫결제 인지 아닌지 확인. 첫결제는 무이자 12개월할부가 가능하다.
        $check = $this->payment_mdl->check_paid_history_for_first_pay($wiz_member['wm_uid']);
        $pay_info['is_first_payment'] = $check['cnt'] > 0 ? 0:1;
        $pay_info['skin_indx'] = $pay_info['is_first_payment'] ? 8:6;

        $pay_method = payment_pay_method_open($pay_card_only, $admin);
        if($pay_method[$request['pay_method']] == 0)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0804';
            $return_array['data']['err_msg'] = '선택할 수 없는 결제수단입니다.';
            echo json_encode($return_array);
            exit;
        }

        if($pay_info['event_dc_price'])
        {
            $request['goods_type'] = 7;
        }
        elseif(payment_android_addpoint_event())
        {
            $request['goods_type'] = 8;
        }

        //주문번호 생성
        $OrderNo = substr(date("Y") , 2 ,2).date("md-Hi").sprintf("%03d",substr($wiz_member['wm_uid'],-3)).sprintf("%02d", rand(0,99));

        $mobile = common_checked_phone_format($request['mobile']);
        $insert_prepay = [
            'uid'           => $wiz_member['wm_uid'],
            'cl_gubun'      => $request['cl_gubun'],
            'goods_id'      => $request['goods_id'],
            'goods_type'    => $request['goods_type'] ? $request['goods_type']:0,
            'pay_method'    => $request['pay_method'],
            'mobile'        => $mobile,
            'tel'           => $request['tel'] ? $request['tel']:'',
            'lesson_gubun'  => $pay_info['lesson_gubun'] ? $pay_info['lesson_gubun']:'',
            'dc_price'      => $pay_info['dc_price'] ? $pay_info['dc_price']:0,
            'sms_dc_price'  => $pay_info['sms_dc_price'] ? $pay_info['sms_dc_price']:0,
            'event_dc_price'=> $pay_info['event_dc_price'] ? $pay_info['event_dc_price']:0,
            'total_price'   => $pay_info['final_price'] ? $pay_info['final_price']:0,
            'org_price'     => $pay_info['origin_price'] ? $pay_info['origin_price']:0,
            'cl_name'       => $pay_info['cl_name'] ? $pay_info['cl_name']:'',
            'lesson_memo'   => $request['lesson_memo'] ? $request['lesson_memo']:'',
            'tu_uid'        => $request['tu_uid'] ? $request['tu_uid']:0,
            'book_id'       => $request['book_id'] ? $request['book_id']:0,
            'regdate'       => date('Y-m-d H:i:s'),
            'order_no'      => $OrderNo,
            'man_id'        => $admin ? $admin:'',
            'hopedate'      => $request['hopedate'] ? $request['hopedate']:'0000-00-00',
            'hopetime'      => $request['hopetime'] ? $request['hopetime']:'',
            'cl_time'       => $goods['cl_time'],
            'cl_number'     => $goods['cl_number'],
            'cl_class'      => $goods['cl_class'],
            'cl_month'      => $goods['cl_month'],
            'hold_num'      => $goods['hold_num'],
            'student_su'    => $goods['student_su'] ? $goods['student_su']:2,
            'student_uid'   => $goods['student_uid'] ? $goods['student_uid']:'',
            'e_id'          => $goods['e_id'] ? $goods['e_id']:'',      //이벤트 상품 의 e_id
            'sms_promotion_code'  => $request['sms_promotion_code'],
        ];

        if($request['pay_method'] =='cash')
        {
            $insert_prepay['receive_name'] = $request['receive_name'] ? $request['receive_name']:'';
            $insert_prepay['receive_date'] = $request['receive_date'] ? $request['receive_date']:'';
            $insert_prepay['bank_number'] = $request['bank_number'] ? $request['bank_number']:'';
        }
        elseif($request['pay_method'] =='hubcard')
        {
            $insert_prepay['ars_mobile_number'] = $request['ars_mobile_number'] ? $request['ars_mobile_number']:'';
            $insert_prepay['ars_order_name'] = $request['ars_order_name'] ? $request['ars_order_name']:'';
            $insert_prepay['ars_request_price'] = $request['ars_request_price'] ? $request['ars_request_price']:'';
        }
        elseif($request['pay_method'] =='vbank')
        {
            $insert_prepay['receive_mobile'] = $request['receive_mobile'] ? $request['receive_mobile']:'';
        }
        
        $insert_id = $this->payment_mdl->insert_wiz_prepay($insert_prepay);

        if($insert_id < 0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "pay - DB ERROR";
            echo json_encode($return_array);
            exit;
        }

        $pay_info['pay_method_kcp_code'] = payment_pay_method_kcp_code($request['pay_method']);
        $pay_info['site_cd'] = $this->kcp_mint_account[$request['pay_method'] == 'hubcard' ? 'ars':'card'][$pay_info['is_first_payment'] ? 'first_pay':'common_pay'];
        $pay_info['wm_email'] = $wiz_member['wm_email'];

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['pay_info'] = $pay_info;        
        $return_array['data']['prepay_id'] = (new OldEncrypt('(*&DHajaan=f0#)2'))->encrypt($insert_id);
        $return_array['data']['OrderNo'] = $OrderNo;

        echo json_encode($return_array);
        exit;
    }

    /*
        KCP 스크립트 모듈에서 결제 후 제공받은 결제정보로 서버단에서 결제 검증 후 디비 처리 해야한다.
        이곳에 들어오면 일단 결제진행중 이라는 뜻이니 최소한 로그처리는 해야하므로 폼검증은 제외
        카드결제, 계좌이체, 가상계좌만 들어온다.
        가상계좌가 결제되면 common_return으로 따로 결제완료가 들어온다.
    */
    public function order_pay()
    {
        $return_array = array();    

        //KCP 결제 검증 기본설정
        $pay_config = [
            'g_conf_gw_url'     => $this->g_conf_gw_url,
            'g_wsdl'            => $this->kcp_g_wsdl,
            'g_conf_site_cd'    => $this->input->post('site_cd'),
            'g_conf_site_key'   => $this->kcp_mint_account_kv[$this->input->post('site_cd')],
            'g_conf_home_dir'   => $this->g_conf_home_dir, //BIN 절대경로 입력 (bin전까지)
            'g_conf_log_level'  => '3',
            'g_conf_gw_port'    => $this->g_conf_gw_port,
            'module_type'       => $this->module_type,
            'include_kcp_pp'    => APPPATH.'/third_party/kcp/pp_cli_hub.php',   //kcp 결제연동 라이브러리. 인클루드해서 사용해야한다.
        ];

        $result = payment_order_progress($this->input->post(), $pay_config);

        if(!$result['state'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0810';
            $return_array['data']['err_msg'] = $result['msg'];
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['lesson_id'] = $result['lesson_id'];

        echo json_encode($return_array);
        exit;
    }

    
    /*
        모바일 페이지에서 KCP 결제창을 띄우기 위한 데이터를 리턴
    */
    public function order_pay_mobile_ready()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "site_cd" => trim($this->input->post('site_cd')),
            "prepay_id" => trim($this->input->post('prepay_id')),
            "pay_method" => trim($this->input->post('pay_method')),
            "escw_used" => trim($this->input->post('escw_used')) ? trim($this->input->post('escw_used')):'N',
            "Ret_URL" => trim($this->input->post('Ret_URL')),
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

        $this->load->model('payment_mdl');
        
        $prepay_id = (new OldEncrypt('(*&DHajaan=f0#)2'))->decrypt($request['prepay_id']);
        $prepay = $this->payment_mdl->row_prepay_by_prepay_id($prepay_id);

        $err_Msg = '';
        $err_code = '';
        if(!$prepay)
        {
            $errMsg = '잘못된 주문프로세스입니다.';
            $err_code = '0806';
        }
        elseif($wiz_member['wm_uid'] != $prepay['uid'])
        {
            $errMsg = '잘못된 접근입니다. 잠시 후 다시 시도바랍니다.';
            $err_code = '0807';
        }
        elseif($prepay['lesson_id'])
        {
            $errMsg = '이미 처리된 주문입니다.';
            $err_code = '0808';
        }

        if($errMsg)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = $err_code;
            $return_array['data']['err_msg'] = $err_Msg;
            echo json_encode($return_array);
            exit;
        }
        
        $g_wsdl = $this->kcp_g_wsdl;

        // 쇼핑몰 페이지에 맞는 문자셋을 지정해 주세요.
        $charSetType      = "utf-8";             // UTF-8인 경우 "utf-8"로 설정
        
        $siteCode         = $request["site_cd"];
        $orderID          = $prepay["order_no"];
        $paymentMethod    = $request["pay_method"];
        $escrow           = ( $request[ "escw_used"   ] == "Y" ) ? true : false;
        $productName      = $prepay["cl_name"];

        $paymentAmount    = $prepay["total_price"]; // 결제 금액
        $returnUrl        = $request["Ret_URL"];

        if(strpos($returnUrl,'/views/contents/order/order.pay.bridge.php') === false)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '95XX';
            $return_array['data']['err_msg'] = '잘못된 파라미터 요청(1)';
            echo json_encode($return_array);
        }

        // Access Credential 설정
        $accessLicense    = "";
        $signature        = "";
        $timestamp        = "";

        // Base Request Type 설정
        $detailLevel      = "0";
        $requestApp       = "WEB";
        $requestID        = $orderID;
        $userAgent        = $_SERVER['HTTP_USER_AGENT'];
        $version          = "0.1";

        try
        {
            include APPPATH.'/third_party/kcp/KCPComLibrary.php';

            $payService = new PayService( $g_wsdl );

            $payService->setCharSet( $charSetType );
            
            $payService->setAccessCredentialType( $accessLicense, $signature, $timestamp );
            $payService->setBaseRequestType( $detailLevel, $requestApp, $requestID, $userAgent, $version );
            $payService->setApproveReq( $escrow, $orderID, $paymentAmount, $paymentMethod, $productName, $returnUrl, $siteCode );

            $approveRes = $payService->approve();
                    
            /* printf( "%s,%s,%s,%s", $payService->resCD,  $approveRes->approvalKey,
                                $approveRes->payUrl, $payService->resMsg ); */

        }
        catch (SoapFault $ex )
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '95XX';
            $return_array['data']['err_msg'] = '연동 오류 (PHP SOAP 모듈 설치 필요)';
            echo json_encode($return_array);
            exit;
        }

        if($payService->resCD != '0000')
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = $payService->resCD;
            $return_array['data']['err_msg'] = $payService->resMsg;
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['approvalKey'] = $approveRes->approvalKey;
        $return_array['data']['payUrl'] = $approveRes->payUrl;

        echo json_encode($return_array);
        exit;
    }

    //ars결제요청. 결제되면 common_return으로 따로 결제완료가 들어온다.
    public function order_hubcard_call()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "prepay_id" => trim($this->input->post('prepay_id')),
            "site_cd" => trim($this->input->post('site_cd')),
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

        $this->load->model('payment_mdl');

        $prepay_id = (new OldEncrypt('(*&DHajaan=f0#)2'))->decrypt($request['prepay_id']);
        //임시 결제정보저장 테이블에서 데이터 가져오기
        $prepay = $this->payment_mdl->row_prepay_by_prepay_id($prepay_id);

        $err_Msg = '';
        $err_code = '';
        if(!$prepay)
        {
            $errMsg = '잘못된 주문프로세스입니다.';
            $err_code = '0806';
        }
        elseif($wiz_member['wm_uid'] != $prepay['uid'])
        {
            $errMsg = '잘못된 접근입니다. 잠시 후 다시 시도바랍니다.';
            $err_code = '0807';
        }
        elseif($prepay['lesson_id'])
        {
            $errMsg = '이미 처리된 주문입니다.';
            $err_code = '0808';
        }

        if($errMsg)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = $err_code;
            $return_array['data']['err_msg'] = $err_Msg;
            echo json_encode($return_array);
            exit;
        }

        //KCP 결제 검증 기본설정
        $pay_config = [
            'g_conf_gw_url'     => $this->g_conf_gw_url,
            'g_wsdl'            => $this->kcp_g_wsdl,
            'g_conf_site_cd'    => $this->input->post('site_cd'),
            'g_conf_site_key'   => $this->kcp_mint_account_kv[$this->input->post('site_cd')],
            'g_conf_home_dir'   => $this->g_conf_home_dir, //BIN 절대경로 입력 (bin전까지)
            'g_conf_log_level'  => '3',
            'g_conf_gw_port'    => $this->g_conf_gw_port,
            'module_type'       => $this->module_type,
            'include_kcp_pp'    => APPPATH.'/third_party/kcp/pp_cli_hub_lib_hubcard.php',   //ars kcp 결제연동 라이브러리. 인클루드해서 사용해야한다.
        ];

        $result = payment_order_hubcard_call($this->input->post(), $pay_config);

        //정상처리 되면 출석부 미입금상태로 생성 order_cash 와 같은 처리
        //구민트는 결제문자전송과 출석부 생성 액션이 나눠져 있었으나 이과장님과 상의하에 합침
        if(!$result['state'])
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = '0809';
            $return_array['data']['err_msg'] = 'ARS 요청 실패('.$result['msg'].')';
            echo json_encode($return_array);
            exit;
        }

        $lesson_pay_id = payment_insert_lesson_pay($prepay, $wiz_member);

        if($lesson_pay_id['state'] ==0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "출석부 생성 실패-DB ERROR";
            echo json_encode($return_array);
            exit;   
        }
        
        $lesson_id = $lesson_pay_id['lesson_id'];
        $pay_id = $lesson_pay_id['pay_id'];
        $mobile_number = $lesson_pay_id['lesson_param']['mobile'] ? $lesson_pay_id['lesson_param']['mobile']:$prepay['mobile'];

        //문자, 푸시보내기
        if($prepay['total_price'] <= 1000)
        {
            $sms_templete_code = $prepay['goods_type'] == '6' ? 256:259;
            
            $sms_options['wiz_id'] = $wiz_member['wm_wiz_id'];
            $sms_options['uid'] = $wiz_member['wm_uid'];
            $sms_options['name'] = $wiz_member['wm_name'];
            //SMS 전송
            sms::send_sms($mobile_number, $sms_templete_code, $sms_options);
            
        }
        $push_No = 3003;
        $pInfo = array("member"=>$wiz_member['wm_name'], "w_uid" => $wiz_member['wm_uid']);

        //푸시
        AppPush::send_push($wiz_member['wm_uid'], $push_No, $pInfo);
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['lesson_id'] = $lesson_id;
        echo json_encode($return_array);
        exit;
    }

    //무통장 주문
    public function order_cash()
    {
        $return_array = array();    

        $request = array(    
            "wiz_id" => trim($this->input->post('wiz_id')),
            "authorization" => trim($this->input->post('authorization')),
            "prepay_id" => trim($this->input->post('prepay_id')),
            "sms_ok" => trim($this->input->post('sms_ok')),
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

        $this->load->model('payment_mdl');

        $prepay_id = (new OldEncrypt('(*&DHajaan=f0#)2'))->decrypt($request['prepay_id']);
        //임시 결제정보저장 테이블에서 데이터 가져오기
        $prepay = $this->payment_mdl->row_prepay_by_prepay_id($prepay_id);

        $err_Msg = '';
        $err_code = '';
        if(!$prepay)
        {
            $errMsg = '잘못된 주문프로세스입니다.';
            $err_code = '0806';
        }
        elseif($wiz_member['wm_uid'] != $prepay['uid'])
        {
            $errMsg = '잘못된 접근입니다. 잠시 후 다시 시도바랍니다.';
            $err_code = '0807';
        }
        elseif($prepay['lesson_id'])
        {
            $errMsg = '이미 처리된 주문입니다.';
            $err_code = '0808';
        }

        if($errMsg)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = $err_code;
            $return_array['data']['err_msg'] = $err_Msg;
            echo json_encode($return_array);
            exit;
        }

        //출석부 생성
        $lesson_pay_id = payment_insert_lesson_pay($prepay, $wiz_member);

        if($lesson_pay_id['state'] ==0)
        {
            $return_array['res_code'] = '0500';
            $return_array['msg'] = "출석부 생성 실패-DB ERROR";
            echo json_encode($return_array);
            exit;   
        }
        
        $lesson_id = $lesson_pay_id['lesson_id'];
        $pay_id = $lesson_pay_id['pay_id'];

        $mobile_number = $lesson_pay_id['lesson_param']['mobile'] ? $lesson_pay_id['lesson_param']['mobile']:$prepay['mobile'];

        if($prepay['goods_type'] == '5')
        {
            if($request['sms_ok']=='1')
            {
                $sms_templete_code = 262;

                $sms_options = array(
                    'bank_number'   =>$prepay['bank_number'],
                    'price'         =>$prepay['total_price'],
                    'wiz_id'        =>$wiz_member['wm_wiz_id'],
                    'uid'           =>$wiz_member['wm_uid'],
                    'name'          =>$wiz_member['wm_name'],
                );

                //SMS 전송
                sms::send_sms($mobile_number, $sms_templete_code, $sms_options); 
            }
        }
        else
        {
            //문자, 푸시보내기
            if($prepay['total_price'] <= 1000)
            {
                $sms_templete_code = $prepay['goods_type'] == '6' ? 256:259;
                
                $sms_options['wiz_id'] = $wiz_member['wm_wiz_id'];
                $sms_options['uid'] = $wiz_member['wm_uid'];
                $sms_options['name'] = $wiz_member['wm_name'];

                //SMS 전송
                sms::send_sms($mobile_number, $sms_templete_code, $sms_options);  
            }
            elseif($request['sms_ok']=='1')
            {
                $sms_templete_code = $prepay['goods_type'] == '6' ? 257:260;

                $sms_options = array(
                    'bank_number'   =>$prepay['bank_number'],
                    'price'         =>$prepay['total_price'],
                    'wiz_id'        =>$wiz_member['wm_wiz_id'],
                    'uid'           =>$wiz_member['wm_uid'],
                    'name'          =>$wiz_member['wm_name'],
                );

                //SMS 전송
                sms::send_sms($mobile_number, $sms_templete_code, $sms_options);  
            }
        }
        
    
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "";
        $return_array['data']['lesson_id'] = $lesson_id;
        echo json_encode($return_array);
        exit;
    }

    
    /*
        가상계좌, ARS 결제완료 시 KCP에서 호출한다. 구민트의 common_return.php 페이지의 역할이다
        예외적으로 html문자를 리턴한다.
    */
    public function common_return()
    {
        header('Content-Type: text/html');

        $this->load->model('payment_mdl');
        $this->load->model('lesson_mdl');
        $this->load->model('sms_mdl');
        $this->load->model('point_mdl');
        $this->load->model('tutor_mdl');
        $this->load->model('member_mdl');
        
        
        
        /* ============================================================================== */
        /* =   02. 공통 통보 데이터 받기                                                = */
        /* = -------------------------------------------------------------------------- = */
        $site_cd      = $this->input->post("site_cd");                 // 사이트 코드
        $tno          = $this->input->post("tno");                 // KCP 거래번호
        $order_no     = $this->input->post("order_no");                 // 주문번호
        $tx_cd        = $this->input->post("tx_cd");                 // 업무처리 구분 코드
        $tx_tm        = $this->input->post("tx_tm");                 // 업무처리 완료 시간
        /* = -------------------------------------------------------------------------- = */
        $res_cd       = "";                                    // 결과코드
        $res_msg      = "";                                    // 결과메세지
        /* = -------------------------------------------------------------------------- = */
        $ars_tx_key   = "";                                    // ARS결제 시퀀스 번호
        $phon_mny     = "";                                    // 결제 금액
        $phon_no      = "";                                    // 휴대폰/유선전화 번호
        $order_nm     = "";                                    // 주문자명
        /* = -------------------------------------------------------------------------- = */
        $card_no      = "";                                    // 카드번호
        $card_cd      = "";                                    // 카드발급사 코드
        $card_name    = "";                                    // 카드발급사 명
        $acqu_cd      = "";                                    // 매입사 코드
        $acqu_name    = "";                                    // 매입사 명
        $app_no       = "";                                    // 승인번호
        $bizx_numb    = "";                                    // 가맹점번호
        $noinf        = "";                                    // 무이자 구분 플래그
        $card_quota   = "";                                    // 할부개월 수
        /* = -------------------------------------------------------------------------- = */

        /* = -------------------------------------------------------------------------- = */
        /* =   02-1. 모바일PG/ARS 결제 통보 데이터 받기                                 = */
        /* = -------------------------------------------------------------------------- = */
        if ( $tx_cd == "TX09" )
        {
            //header('Content-Type:text/html;charset=euc-kr');
            setlocale(LC_CTYPE, 'ko_KR.utf-8'); 
            //setlocale(LC_CTYPE, 'ko_KR.euc-kr');

            log_message('error', 'common_return order_nm: '.$this->input->post("order_nm"));
            log_message('error', 'common_return order_nm: '.iconv('euc-kr','utf-8',$this->input->post( "order_nm"   )));
            log_message('error', 'common_return order_nm: '.iconv('utf-8','euc-kr',$this->input->post( "order_nm"   )));

            log_message('error', 'common_return order_nm: '.$this->input->post( "card_cd"   ));
            log_message('error', 'common_return order_nm: '.iconv('euc-kr','utf-8',$this->input->post( "card_cd"   )));
            log_message('error', 'common_return order_nm: '.iconv('utf-8','euc-kr',$this->input->post( "card_cd"   )));

            $res_cd     = $this->input->post( "res_cd"     );                // 결과코드
            $res_msg    = $this->input->post( "res_msg"    );                // 결과메세지
            $ars_tx_key = $this->input->post( "ars_tx_key" );                // ARS결제 시퀀스 번호
            $phon_mny   = $this->input->post( "phon_mny"   );                // 결제 금액
            $phon_no    = $this->input->post( "phon_no"    );                // 휴대폰/유선전화 번호
            $order_nm   = $this->input->post( "order_nm"   );                // 주문자명
            $card_no    = $this->input->post( "card_no"    );                // 카드번호
            $card_cd    = $this->input->post( "card_cd"    );                // 카드발급사 코드
            $card_name  = $this->input->post( "card_name"  );                // 카드발급사 명
            $acqu_cd    = $this->input->post( "acqu_cd"    );                // 매입사 코드
            $acqu_name  = $this->input->post( "acqu_name"  );                // 매입사 명
            $app_no     = $this->input->post( "app_no"     );                // 승인번호
            $bizx_numb  = $this->input->post( "bizx_numb"  );                // 가맹점번호
            $noinf      = $this->input->post( "noinf"      );                // 무이자 구분 플래그
            $card_quota = $this->input->post( "card_quota" );                // 할부개월 수
        }
        /* = -------------------------------------------------------------------------- = */
        /* =   02-1. 가상계좌 입금 통보 데이터 받기                                     = */
        /* = -------------------------------------------------------------------------- = */
        elseif ( $tx_cd == "TX00" )
        {
            $ipgm_name = $this->input->post( "ipgm_name" );                // 주문자명
            $remitter  = $this->input->post( "remitter"  );                // 입금자명
            $ipgm_mnyx = $this->input->post( "ipgm_mnyx" );                // 입금 금액
            $bank_code = $this->input->post( "bank_code" );                // 은행코드
            $account   = $this->input->post( "account"   );                // 가상계좌 입금계좌번호
            $op_cd     = $this->input->post( "op_cd"     );                // 처리구분 코드
            $noti_id   = $this->input->post( "noti_id"   );                // 통보 아이디
            $cash_a_no = $this->input->post( "cash_a_no" );                // 현금영수증 승인번호
            $cash_a_dt = $this->input->post( "cash_a_dt" );                // 현금영수증 승인시간
            $cash_no   = $this->input->post( "cash_no"   );                // 현금영수증 거래번호
        }

        log_message('error', 'common_return :'.http_build_query($this->input->post()));

        $prepay = $this->payment_mdl->row_prepay_by_order_no($order_no);

        $errMsg = '';
        $err_code = '';
        if(!$prepay)
        {
            if($tx_cd == "TX09")
            {
                $errMsg = '수기결제';
                $err_code = '0806';
            }
            else
            {
                $errMsg = '잘못된 주문프로세스입니다.';
                $err_code = '0806';
            }
            
        }
        elseif($prepay['pay_ok'] =='Y')
        {
            $errMsg = '이미 처리된 주문입니다.';
            $err_code = '0808';
        }
        elseif($prepay['tno'] == $tno)
        {
            $errMsg = '잘못된 결제정보입니다.';
            $err_code = '0811';
        }
        //자동재수강 스케쥴 잡힌거 있는지 체크
        elseif($prepay['goods_type'] == '5')
        {
            $relec_lesson = $this->lesson_mdl->row_wiz_lesson_by_lesson_id($prepay['goods_id'], $prepay['uid']);
            if($relec_lesson)
            {
                $check_retake = lesson_check_retake_lesson_isEmpty_schedule($relec_lesson, $prepay['cl_month']);

                if($check_retake['state'] == false)
                {
                    $errMsg = '일정중 이미 등록된 수업이 있습니다. 수업을 확인하신후 등록하시기 바랍니다.';
                    $err_code = '0812';
                }
            }
            else
            {
                $errMsg = '자동재수강 정보가 없습니다.';
			    $err_code = '0812';
            }
            
        }

        $this->payment_mdl->insert_wiz_pg_notification([
            'imp_uid' => $tno,
            'order_id' => $order_no,
            'order_status' => $errMsg == '' ? 'success':'fail',
            'order_amount' => $tx_cd == "TX00" ? $ipgm_mnyx:$phon_mny,
            'order_buyer' => $tx_cd == "TX00" ? $ipgm_name:$order_nm,
            'order_name' => '('.$tx_cd.')'.$errMsg,
            'payinfo' => http_build_query($this->input->post()),
            'reg_date' => date('Y-m-d H:i:s'),
        ]);

        if($errMsg !='')
        {
            echo '<html><body><form><input type="hidden" name="result" value="'.$err_code.'"></form></body></html>';
            exit;
        }

        $wiz_member = $this->member_mdl->get_wiz_member_by_wm_uid($prepay['uid']);

        //가상계좌 통보 처리
        if($prepay['pay_method'] =='vbank' && $tx_cd == "TX00")
        {
            $param = [
                'pay_ok' => 'Y',
                'pay_discount' => '0',
                'receive_name' => $remitter,
                'ipdate' => date("Y-m-d"),
                'card_date' => $tx_tm,
                'ars_fail' => '가상계좌 결제가 정상 처리 되었습니다.',
            ];
            $this->payment_mdl->update_wiz_pay($prepay['pay_id'], $param);

            $l_param = [
                'pay_ok' => 'Y',
                'lesson_list_view' => 'Y',
            ];
            $this->lesson_mdl->update_wiz_lesson($prepay['lesson_id'], $l_param);

            $lesson = $this->lesson_mdl->row_wiz_lesson_by_lesson_id($prepay['lesson_id'], $prepay['uid']);

            //자동재수강 추가처리
            if($prepay['goods_type'] == '5')
            {
                lesson_insert_schedule_retake($relec_lesson, $check_retake, $prepay, $wiz_member, $prepay['lesson_id'],'Y');
            }
            
            $mobile_number = $prepay['receive_mobile'] ? $prepay['receive_mobile']:$prepay['mobile'];

            //문자,알림톡,푸시 보내기
            if($prepay['goods_type'] != '5')  //신규 일반결제
            {
                $template_code = strpos($prepay['cl_name'],"첨삭") !== false ? 'MINT06002E':'MINT06001Y';
                $sms_templete_code = strpos($prepay['cl_name'],"첨삭") !== false ? 258:261;
                $push_No = 3002;
            }
            else            //자동재수강
            {
                $template_code = 'MINT06001Z';
                $sms_templete_code = 263;
                $push_No = 3005;
            }

            $sms_options['wiz_id'] = $wiz_member['wm_wiz_id'];
            $sms_options['uid'] = $wiz_member['wm_uid'];
            $sms_options['name'] = $wiz_member['wm_name'];
            //SMS 전송
            sms::send_sms($mobile_number, $sms_templete_code, $sms_options);

            $atalk_options['name'] = $wiz_member['wm_name'];
            $atalk_options['uid'] = $wiz_member['wm_uid'];
            $atalk_options['wiz_id'] = $wiz_member['wm_wiz_id'];

            //알림톡 전송
            sms::send_atalk($mobile_number, $template_code, $atalk_options);

            $tpl = $this->sms_mdl->get_atalk_templete($template_code);    
            $pInfo = array("member"=>$wiz_member['wm_name'],"atk_content"=> $tpl['content']);
            AppPush::send_push($prepay['uid'], $push_No, $pInfo);

        }
        //ARS 결제 통보 처리
        elseif($prepay['pay_method'] =='hubcard' && $tx_cd == "TX09")
        {

            $after_phon_mny = $prepay['phon_mny'] + $phon_mny;
            if($prepay['total_price'] > $after_phon_mny)
            {
                $param = [
                    //'receive_name' => $order_nm,
                    'receive_date' => date("Y-m-d"),
                    'ipdate' => date("Y-m-d"),
                    'card_code' => $card_cd,
                    'card_name' => $card_name,
                    'card_agreeno' => $app_no,
                    'card_date' => $tx_tm,
                    'ars_fail' => '결제금액이 상품금액과 다릅니다.',
                    'halbu_month' => $card_quota,
                    'phon_mny' => $after_phon_mny,
                ];
                $this->payment_mdl->update_wiz_pay($prepay['pay_id'], $param);
            }
            elseif($prepay['total_price'] < $after_phon_mny)
            {
                $param = [
                    'ars_fail' => '결제금액이 상품금액 보다 큽니다.',
                ];
                $this->payment_mdl->update_wiz_pay($prepay['pay_id'], $param);
            }
            else
            {
                $param = [
                    'pay_ok' => 'Y',
                    'pay_discount' => '0',
                    //'receive_name' => $order_nm,
                    'receive_date' => date("Y-m-d"),
                    'ipdate' => date("Y-m-d"),
                    'card_code' => $card_cd,
                    'card_name' => $card_name,
                    'card_agreeno' => $app_no,
                    'card_date' => $tx_tm,
                    'ars_fail' => '결제가 정상 처리 되었습니다.',
                    'halbu_month' => $card_quota,
                ];
                $this->payment_mdl->update_wiz_pay($prepay['pay_id'], $param);

                $l_param = [
                    'pay_ok' => 'Y',
                    'lesson_list_view' => 'Y',
                ];
                $this->lesson_mdl->update_wiz_lesson($prepay['lesson_id'], $l_param);

                $lesson = $this->lesson_mdl->row_wiz_lesson_by_lesson_id($prepay['lesson_id'], $prepay['uid']);
                
                //자동재수강 추가처리
                if($prepay['goods_type'] == '5')
                {
                    lesson_insert_schedule_retake($relec_lesson, $check_retake, $prepay, $wiz_member, $prepay['lesson_id'],'Y');
                }

                //문자,알림톡,푸시 보내기
                if($prepay['goods_type'] == '5')  //신규 일반결제
                {
                    //푸시 보내고 끝
                    $tpl = $this->sms_mdl->get_atalk_templete('MINT06002G');   
                    $pcode = '3005';
                }
                else
                {
                    //푸시 보내고 끝
                    $tpl = $this->sms_mdl->get_atalk_templete('MINT06002F');   
                    $pcode = '3004';
                }

                $pInfo = array("member"=>$lesson['wl_name'], "atk_content"=> $tpl[0]);
                AppPush::send_push($lesson['wl_uid'], $pcode, $pInfo);

            }
        }
        else
        {
            $this->payment_mdl->insert_wiz_pg_notification([
                'imp_uid' => $tno,
                'order_id' => $order_no,
                'order_status' => '-',
                'order_name' => $tx_cd. ($tx_cd == "TX10" ? 'ARS결제취소':''),
                'payinfo' => http_build_query($this->input->post()),
                'reg_date' => date('Y-m-d H:i:s'),
            ]);
        }

        //결제처리 완료, 첨삭아닐때 퀘스트요청
        if(($tx_cd == "TX09" || $tx_cd == "TX00") || false === strpos($prepay['cl_name'], '첨삭'))
        {
            MintQuest::request_batch_quest('6', $prepay['lesson_id'], $prepay['uid']);
        }

        echo '<html><body><form><input type="hidden" name="result" value="0000"></form></body></html>';
        exit;
    }

}








