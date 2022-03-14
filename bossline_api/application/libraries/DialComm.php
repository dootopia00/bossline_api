<?php
class DialComm
{
    //다이얼 녹화파일 주소
    public static $recorderServerIP = 'https://edusub.maaltalk.com';
    public static $recorderPage = 'ListenRecord.php';
    public static $companyID = 'eds';
    public static $companyPW = 'eds1491';
    //말톡API 주소정보
    public static $maaltalk_api_url = 'http://ext-api.maaltalk.com/mint/api/main/query-call-history.php';
    public static $maaltalk_api_key = 'e4954680-0e9b-11eb-8b6f-0800200c9a66';

    public static function record_list($lesson_gubun, $param)
    {
        if($lesson_gubun == 'M' || $lesson_gubun == 'T')
        {
            $list = self::record_list_mobile($param['date'], $param['tel'], $param['tel2']);
        }
        else
        {
            $list = self::record_list_maaltalk($param['sc_id'],$param['wm_uid']);
        }

        return $list;
    }

    /*
        전화녹화 리스트 가져오기
        callDate=20210504&callNumber=01075281450&callNumber2=010-7528-1450&CompanyID=eds&CompanyPW=eds1491
    */
    public static function record_list_mobile($date, $tel, $tel2='')
    {
        $url = self::$recorderServerIP.'/'.self::$recorderPage;
        $param = [
            'CompanyID'     => self::$companyID,
            'CompanyPW'     => self::$companyPW,
            'callDate'      => str_replace('-','',$date),
            'callNumber'    => $tel,   // 하이픈(-) 없는 전화번호
            'callNumber2'   => $tel2,  // 하이픈(-) 있는 전화번호
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        $html = curl_exec($ch);
        curl_close($ch);

        //링크
        preg_match_all('/class="down.*href="(.*\.mp3)".*download>/Usim',$html,$matchs);
        $link = $matchs[1];
        
        $list = [];
        if($link && count($link) > 0)
        {
            //재생시간
            preg_match_all('/<tr>\s*<td>(.*)<\/td>/Usim',$html,$matchs);
            $running = $matchs[1];
            
            //통화시작시간
            preg_match_all('/btn_download\.gif"\/>\s*<\/a>\s*<\/td>\s*<td>(.*)<\/td>/Usim',$html,$matchs);
            $call_start_time = $matchs[1];

            foreach($link as $key=>$l)
            {
                $list[] = [
                    'link' => self::$recorderServerIP.$l,
                    'running' => $running[$key],
                    'call_start_time' => $call_start_time[$key],
                ];
            }
        }

        return $list;
    }

    /*
        말톡 녹화파일 조회
    */
    public static function record_list_maaltalk($sc_id, $wm_uid)
    {
        $list = [];

        $CI = & get_instance();
        $CI->load->model('member_mdl');
        $CI->load->model('lesson_mdl');
        
        $result = $CI->member_mdl->checked_schedule_by_wiz_member($sc_id, $wm_uid);        
        $maaltalk_log = $CI->lesson_mdl->get_maaltalk_note_log($result['ws_tu_uid'], $wm_uid, $sc_id);

        //2주전 날짜
        $weekdate = strtotime(date('Y-m-d')." -2 week");
        $logdate  = strtotime($maaltalk_log['regdate']);

        //정보가 있을 경우에만 노출
        //파일 보관기간이 지났을 경우 노출 제외
        if($maaltalk_log['sc_id'] && ($weekdate <= $logdate)){
            $ws_tu_uid = 'wt_'.$result['ws_tu_uid'];
            $room_start_day = date("Y-m-d H:i", strtotime("-9 hours -5 minutes", strtotime($result['ws_startday'])));
            $room_end_day = date("Y-m-d H:i", strtotime("-9 hours -2 minutes", strtotime($result['ws_endday'])));
            
            $url = self::$maaltalk_api_url;
            $param = [
                'fields'       => ["host","url","state","participants_no","participants_id","start_time","end_time","duration","rec_file"],
                "search"       => "participants_id LIKE '%".$ws_tu_uid."%' AND rec_file <> '' AND (start_time BETWEEN '".$room_start_day."' AND '".$room_end_day."') AND host = '".$ws_tu_uid."'",
                'service_type' => 'mint',
                'apikey'       => self::$maaltalk_api_key
            ];
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
            $response = curl_exec($ch);
            curl_close($ch);
    
            $result = json_decode(urldecode($response), true);
            if($result['code'] == 0)
            {
                foreach($result['message'] as $key=>$value)
                {
                    $list[] = [
                        'link' => $value['rec_file']
                    ];
                }
            }
        }
        
        return $list;
    }
    


    public static function get_absence_list($i)
    {
        $param = [
            'CompanyID'  => self::$companyID,
            'CompanyPW'  => self::$companyPW,
            'process'    => 0,
            'start'      => $i,
        ];

        $url = self::$recorderServerIP.'/intergration_pack/getAbsence.php?'.http_build_query($param);
        
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output=curl_exec($ch);
        curl_close($ch);

        return $output;
    }
}