<?php

// define('sendURL', 'https://fcm.googleapis.com/fcm/send');
// define('AuthorizationKey', 'AAAAv376yBE:APA91bErk6oCPnq_0gb-DEoAuIFZJdNpK8g5hrk_aKeM8QxHt27JB8KIrixmyIsn0UfGPuB3ZfJCAVzY2aZssx9mG7mcl7za8M3CRLvTaE4lIzaOgikwKUboCSyVRJsnCA8vINPZUq9x');

class AppPush{

    public static $sendURL = 'https://fcm.googleapis.com/fcm/send';
    public static $AuthorizationKey = 'AAAAv376yBE:APA91bErk6oCPnq_0gb-DEoAuIFZJdNpK8g5hrk_aKeM8QxHt27JB8KIrixmyIsn0UfGPuB3ZfJCAVzY2aZssx9mG7mcl7za8M3CRLvTaE4lIzaOgikwKUboCSyVRJsnCA8vINPZUq9x';

    function __construct()
    {
        
    }
    
    public static function send_push_android($tokens, $pData)
	{
        $priority = "high";
        if ($pData['priority'])    $priority = $pData['priority'];
        
        $androidFields = array(
            "registration_ids" => $tokens,
            "data" => $pData,
            "priority" => "{$priority}"
        );
        if ($pData['ttl'] === 0 || $pData['ttl'] != null) {
            $androidFields['ttl'] = $pData['ttl'];
        }
        
        $state = "success";
        
        $headers = array(
            'Authorization:key =' .static::$AuthorizationKey,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, static::$sendURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($androidFields));
        $result = curl_exec($ch);
        
        if ($result === FALSE) {
            $state = "fail";
            die('Curl failed: ' . curl_error($ch));
        }
        curl_close($ch);

		return $state;
    }

	public static function send_push_ios($tokens, $pushData)
	{
		$result = self::send_curl_ios($tokens, $pushData);

		if($result=='success'){
			$push_result['result'] = 'success';
			$push_result['msg'] = "푸시 보내기에 성공하였습니다.";
		}else{
			$push_result['result'] = 'fail';
			$push_result['msg'] = "푸시 보내기에 실패하였습니다.";
		}

		return $push_result;
    }
    
    public static function send_curl_android($tokens, $pData)
    {
        $priority = "high";
        if ($pData['priority'])    $priority = $pData['priority'];
        
        $androidFields = array(
            "registration_ids" => $tokens,
            "data" => $pData,
            "priority" => "{$priority}"
        );
        if ($pData['ttl'] === 0 || $pData['ttl'] != null) {
            $androidFields['ttl'] = $pData['ttl'];
        }
        
        $state = "success";
        
        $headers = array(
            'Authorization:key =' .self::$AuthorizationKey,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$sendURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($androidFields));
        $result = curl_exec($ch);
        
        if ($result === FALSE) {
    
            die('Curl failed: ' . curl_error($ch));
        }


        curl_close($ch);
        
        return $result;
    }
    
    public static function send_curl_ios($tokens, $pData)
    {
        $priority = "high";
        $mutable_content = false;
        
        if ($pData['imgURL'] != "")    $mutable_content = true;
        if ($pData['priority'])        $priority = $pData['priority'];
        
        $iosFields = array(
            "registration_ids" => $tokens,
            //"to" => $token,
            "notification" => array("title"=>"{$pData['title']}", "body"=>"{$pData['message']}", "sound"=>"default", "mutable_content"=>$mutable_content),
            "data" => array("dl" => "{$pData['link']}" . "&device=IOS_NEW_PACKAGE", "url" => "{$pData['imgURL']}"),
            "priority" => "{$priority}"
        );
        if ($pData['ttl'] === 0 || $pData['ttl'] != null) {
            $iosFields['ttl'] = $pData['ttl'];
        }
        
        $state = "success";
        
        $header = array(
            "authorization: key=".self::$AuthorizationKey,
            "cache-control: no-cache",
            "content-type: application/json"
        );
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$sendURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($iosFields),
            CURLOPT_HTTPHEADER => $header,
        ));
        
        $response = curl_exec($curl);
        //log_message('error', 'ios push  :'.$response);
        //$err = curl_error($curl);
        curl_close($curl);
        
        //if ($err) $state = "fail";
        
        return $response;
    }
    

    public static function send_push($uid, $pushCode = "", $pInfo = array())
    {
        //$pInfo = array("table_code"=>$code, "mb_unq" => $mb_unq, "subject" => $exam_log['book_name']);
        //    $appPush->send_push_uid($get['row']['w_uid'], "2204", $pInfo);
        $CI = & get_instance();
        $CI->load->model('push_mdl');

        $push_result = array();
        $push_result['state'] = false;
        $push_result['res_code'] = '0900';
        $push_result['msg'] = "Error!";
        
        $pData = array();
        
        if ($pushCode == "" || $pushCode == null){
            $push_result['state'] = false;
            $push_result['res_code'] = '0900';
            $push_result['msg'] = "push 전송 데이터가 없습니다.";
            return $push_result;
        }
        if (!$uid){
            $push_result['result'] = false;
            $push_result['res_code'] = '0900';
            $push_result['msg'] = "회원 번호가 없습니다.";
            return $push_result;
        }
        
        $conf = $CI->push_mdl->push_conf_by_push_code($pushCode);

        if (!$conf) {
            $push_result['result'] = false;
            $push_result['res_code'] = '0900';
            $push_result['msg'] = "잘못된 템플릿 코드입니다.";
            return $push_result;
        }
        
        // push전송번호 생성 (push 읽음처리용 임의의 랜덤 변수)
        $pNum = time() . rand(0,99999);
        
        // 푸시데이터 형식 변환
        $pData['message'] = self::push_format($conf['push_message'], $pInfo);
        $pData['link'] = self::link_format($conf['push_link'], $pNum, $pInfo);
        $pData['atk_content'] = self::push_format($pInfo['atk_content'], $pInfo);
        
        $pData['pType'] = $conf['push_type'];
        $pData['push_gubun'] = $conf['push_gubun'];
        $pData['imgURL'] = $conf['push_img'];
        //$pData['title'] = iconv("UHC", "UTF8", $conf['push_title']);
        //$pData['message'] = iconv("UHC", "UTF8", $pData['message']);
        $pData['title'] =  $conf['push_title'];
        $pData['message'] = $pData['message'];
        $pData['body'] = $pData['message'];
        $pData['mb_unq'] = $pInfo['mb_unq'];
        $pData['send_name'] = "자동 발송";

        // 발송 구분(시스템 자동발송)
        $pData['send_gubun'] = "S";
        // 우선순위 설정
        $pData['priority'] = $conf['push_priority'] != null || $conf['push_priority'] != "" ? $conf['push_priority'] : "high";
        // 메시지 수명 설정
        $pData['ttl'] = $conf['push_ttl'];

        $push_token = $CI->push_mdl->get_member_push_token($uid, $pData['push_gubun'], $pushCode);
        if (!$push_token) {
            $push_result['result'] = false;
            $push_result['res_code'] = '0900';
            $push_result['msg'] = "회원 정보가 없거나 수신거부 상태 입니다.";
            return $push_result;
        }
        
        $androidTokens = array();
        $iosTokens = array();
        foreach($push_token as $token_info)
        {
            if ($token_info['device'] == "ANDROID_NEW_PACKAGE") 
            {
                array_push($androidTokens, $token_info['token']);
            } 
            else if ($token_info['device'] == "IOS_NEW_PACKAGE") 
            {
                array_push($iosTokens, $token_info['token']);
            }
        }
        // array_push($androidTokens, 'cPbrpEDFhJc8Bhc6mFoxPu:APA91bFVm9d0WcWfaAxE2MNQhaHSy8-02FkxraPcDWFuu3uPqAnLb4nFM7Uyysf_Kr_QkZBTxcBrhn3ASK3ybp18C2eunBlUs0UcFYxqj6D9cj-lqEWSL-XdQmnXIFXhW1aHdTM9ogeg');
        // array_push($androidTokens, 'aaaaaaaaxzczxczxczxczc');


        /*
            $res_object 형태
            {multicast_id: 950597094026825500, success: 1, failure: 2, canonical_ids: 0,…}
            canonical_ids: 0
            failure: 2
            multicast_id: 950597094026825500
            results: [{message_id: "0:1620886646456145%7a74197bf9fd7ecd"}, {error: "NotRegistered"},…]
            0: {message_id: "0:1620886646456145%7a74197bf9fd7ecd"}
            1: {error: "NotRegistered"}
            2: {error: "InvalidRegistration"}
            success: 1

            성공시 results에 message_id 리턴
            실패시 results에 error 리턴
        */

        $result_push_array = array();

        if(count($iosTokens) > 0)
        {
            $res_object_ios = self::send_curl_ios($iosTokens, $pData);

            if(!$res_object_ios){
                $push_result['res_code'] = '0900';
                $push_result['msg'] = 'curl IOS 푸시 보내기에 실패하였습니다.';
            }else{
                $res_push = json_decode($res_object_ios, true)['results'];
    
                for($i=0; $i<count($res_push); $i++){
                    // 푸시 보낸 여러개의 토큰들에 대한 결과값 가공
                    $res_push[$i] = (array)$res_push[$i];
                    
                    $key = array_keys($res_push[$i]);
                    $value = array_values($res_push[$i]);
                    $pData['etc'] = $key[0].'-'.$value[0];
    
                    // 결과값에 따른 로그 insert
                    if($key[0] == 'message_id'){
                        
                        // 성공 db 인설트
                        $pData['resultState'] = "success";
                        self::insert_push_result($token_info, $pData, $pNum);
    
                    }else{
    
                        // 실패 DB 인설트
                        $pData['resultState'] = "fail";
                        self::insert_push_result($token_info, $pData, $pNum);
                    }
    
                    array_push($result_push_array, $key[0]);
                }
    
                if(in_array('message_id', $result_push_array)){
                    // 한 계정에 등록된 토큰으로 전송된 푸시중에 성공한 값이 있는지 체크
                    // 성공시 message_id
                    $push_result['res_code'] = '0000';
                    $push_result['msg'] = 'IOS 푸시 보내기에 성공하였습니다.';
                }else{
                    // 실패시 error
                    $push_result['res_code'] = '0900';
                    $push_result['msg'] = 'IOS 푸시 보내기에 실패하였습니다.';
                }
            }

        }
        
        if(count($androidTokens) > 0)
        {
            $res_object = self::send_curl_android($androidTokens, $pData);

            if(!$res_object){
                $push_result['res_code'] = '0900';
                $push_result['msg'] = 'curl Android 푸시 보내기에 실패하였습니다.';
            
            }else{
    
                $res_push = json_decode($res_object, true)['results'];
    
                for($i=0; $i<count($res_push); $i++){
                    
                    // 푸시 보낸 여러개의 토큰들에 대한 결과값 가공
                    $res_push[$i] = (array)$res_push[$i];
                    
                    $key = array_keys($res_push[$i]);
                    $value = array_values($res_push[$i]);
                    $pData['etc'] = $key[0].'-'.$value[0];
    
                    // 결과값에 따른 로그 insert
                    if($key[0] == 'message_id'){
                        
                        // 성공 db 인설트
                        $pData['resultState'] = "success";
                        self::insert_push_result($token_info, $pData, $pNum);
    
                    }else{
    
                        // 실패 DB 인설트
                        $pData['resultState'] = "fail";
                        self::insert_push_result($token_info, $pData, $pNum);
                    }
    
                    array_push($result_push_array, $key[0]);
                }
    
                if(in_array('message_id', $result_push_array)){
                    // 한 계정에 등록된 토큰으로 전송된 푸시중에 성공한 값이 있는지 체크
                    // 성공시 message_id
                    $push_result['res_code'] = '0000';
                    $push_result['msg'] = 'Android 푸시 보내기에 성공하였습니다.';
                }else{
                    // 실패시 error
                    $push_result['res_code'] = '0900';
                    $push_result['msg'] = 'Android 푸시 보내기에 실패하였습니다.';
                }
            }
        }


        return $push_result;
    }

    /**
	 * 푸시내용,링크 내용 치환 (변수 값 변환)
	 * @param string $content
	 * @param string $pInfo
	 * @return string
	 */
	public static function push_format($content = "", $pInfo = array()) {
	    

	    if ($pInfo['member'] != '')    {
	        $content = str_replace('#{member}', $pInfo['member'], $content);
	        $content = str_replace('#{name}', $pInfo['member'], $content);
	    }
	    if ($pInfo['teacher'] != ''){
	        $content = str_replace('#{teacher}', $pInfo['teacher'], $content);
	        $content = str_replace('#{tu_name}', $pInfo['teacher'], $content);
	    }
	    if ($pInfo['date'] != '')      $content = str_replace('#{date}', $pInfo['date'], $content);
	    if ($pInfo['startdate'] != '') $content = str_replace('#{startdate}', $pInfo['startdate'], $content);
	    if ($pInfo['enddate'] != '')   $content = str_replace('#{enddate}', $pInfo['enddate'], $content);
	    
	    if ($pInfo['time'] != '')      $content = str_replace('#{time}', $pInfo['time'], $content);
	    if ($pInfo['2ndtime'] != '')   $content = str_replace('#{2ndtime}', $pInfo['2ndtime'], $content);
	    if ($pInfo['3rdtime'] != '')   $content = str_replace('#{3rdtime}', $pInfo['3rdtime'], $content);
	    if ($pInfo['cl_time'] != '')   $content = str_replace('#{cl_time}', $pInfo['cl_time'], $content);
	    
	    if ($pInfo['board'] != '')     $content = str_replace('#{board}', $pInfo['board'], $content);
	    if ($pInfo['notice'] != '')    $content = str_replace('#{notice}', $pInfo['notice'], $content);
	    if ($pInfo['event'] != '')     $content = str_replace('#{event}', $pInfo['event'], $content);
	    if ($pInfo['subject'] != '')   $content = str_replace('#{subject}', $pInfo['subject'], $content);
	    
	    if ($pInfo['view_lesson'] != '')$content = str_replace('#{view_lesson}', $pInfo['view_lesson'], $content);
	    if ($pInfo['no'] != '')         $content = str_replace('#{no}', $pInfo['no'], $content);
	    if ($pInfo['w_uid'] != '')      $content = str_replace('#{w_uid}', $pInfo['w_uid'], $content);
	    if ($pInfo['mb_unq'] != '')     $content = str_replace('#{mb_unq}', $pInfo['mb_unq'], $content);
	    if ($pInfo['table_code'] != '') $content = str_replace('#{table_code}', $pInfo['table_code'], $content);
	    
	    if ($pInfo['vbank_price'] != '') $content = str_replace('#{vbank_price}', $pInfo['vbank_price'], $content);
	    if ($pInfo['bank_number'] != '') $content = str_replace('#{bank_number}', $pInfo['bank_number'], $content);
	    if ($pInfo['lastday'] != '')   $content = str_replace('#{lastday}', $pInfo['lastday'], $content);
	    
	    if ($pInfo['url'] != '')   $content = str_replace('#{url}', $pInfo['url'], $content);

        return $content;
	}
    
    /**
	 * 푸시링크 처리 (http://, push_num 추가)
	 * @param string $url
	 * @param string $htype
	 * @return string
	 */
	public static function link_format($url_before, $pNum, $data = array()) {
	    
        $url = self::push_format($url_before, $data);
        

        if($url != "") {
    	    if (strpos($url, "vc.dial070.co.kr") !== false) {
                return $url;
            }
            // push전송번호 get방식 전송
    	    if (strpos($url, "http://m.mint05.com/") !== false) {
    	        if(strpos($url, "?") !== false){
    	            $url .= "&push_num=".$pNum;
    	        } else {
    	            $url .= "?push_num=".$pNum;
    	        }
    	    }
    	    if (strpos($url, "http://") === false && strpos($url, "https://") === false) {
    	        $url = "http://".$url;
    	    }
	    }

	    return $url;
	}

    /**
	 * 결과데이터 저장  
	 * 
	 * @param array $row       : deviceToken에 대한 정보 
	 * @param array $pushData  : push 전송 데이터
	 * @param string $pNum     : 전송번호
	 */
	public static function insert_push_result($row, $pushData){
	    
	    //$title = iconv("UTF8", "UHC", $pushData['title']);
	    //$message = iconv("UTF8", "UHC", $pushData['message']);
        $title = $pushData['title'];
        $message = $pushData['message'];

	    $pushData['atk_content'] = str_replace("'", "\'", $pushData['atk_content']);
        
        //push전송번호 생성 (push 읽음처리용 임의의 랜덤 변수)
	    $p_num = time() . rand(0,99999);

        $insertParam = [
            'uid' => $row['uid'],
            'token' => $row['token'],
            'device' => $row['device'],
            'ptype' => $pushData['pType'],
            'title' => $title,
            'message' => $message,
            'link' => $pushData['link'],
            'img' => $pushData['imgURL'],
            'state' => $pushData['resultState'],
            'push_num' => $p_num,
            'atk_content' => $pushData['atk_content'],
            'push_gubun' => $pushData['push_gubun'],
            'push_target' => $pushData['push_target'],
            'class' => $pushData['class'],
            'send_gubun' => $pushData['send_gubun'],
            'send_name' => $pushData['send_name'],
            'push_repeat' => $pushData['push_repeat'],
            'nday' => $pushData['nday'],
            'etc' => $pushData['etc'],      // 안쓰던 컬럼 변경 -> fcm 결과 리턴된 오브젝트의 key-value 저장
            'mb_unq' => $pushData['mb_unq'],
            'regdate' => date('Y-m-d H:i:s'),

        ];

        $CI = & get_instance();
        $CI->load->model('push_mdl');

        $CI->push_mdl->insert_wiz_push_result($insertParam);
    }
    

}