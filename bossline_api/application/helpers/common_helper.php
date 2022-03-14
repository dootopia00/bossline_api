<?php
defined("BASEPATH") OR exit("No direct script access allowed");



function common_input_out($text_str) 
{
    $text_str = str_replace("\'","'",$text_str);
    $text_str = stripslashes($text_str);
    return $text_str;
}


function common_textarea_out($text_str) 
{ // textarea 
    $text_str = str_replace("&quot;","\"",$text_str);
    $text_str = str_replace("&#39;","\'",$text_str);
    $text_str = str_replace("&amp;","&",$text_str);
    $text_str = stripslashes($text_str);
    return $text_str;
}

function common_textarea_in($text_str) { // textarea 에서 등록시
    $text_str = str_replace("\"","&quot;",$text_str);
    $text_str = str_replace("\'","&#39;",$text_str);
    $text_str = str_replace("&amp;#","&#",$text_str);
    $text_str = str_replace("…","'",$text_str);
    $text_str = str_replace("¨","\"",$text_str);
    $text_str = str_replace("〃","\"",$text_str);
    $text_str = str_replace("꺶","<li>",$text_str);
    $text_str = str_replace("벩","벡",$text_str);
    $text_str = str_replace("뷁","벨",$text_str);
    $text_str = str_replace("뵼","복",$text_str);
    $text_str = addslashes($text_str);
    return $text_str;
}


function common_checked_phone_format($number)
{
    $number = preg_replace("/[^0-9]*/s","",$number);

    if (substr($number,0,2) == '02')
    {
        return preg_replace("/([0-9]{2})([0-9]{3,4})([0-9]{4})$/","\\1-\\2-\\3", $number);
    }  
    else if(substr($number,0,2) == '8' && substr($number,0,2) == '15' || substr($number,0,2) == '16' ||  substr($number,0,2) == '18')
    {
        return preg_replace("/([0-9]{4})([0-9]{4})$/","\\1-\\2",$number);  
    }
    else
    {
        return preg_replace("/([0-9]{3})([0-9]{3,4})([0-9]{4})$/","\\1-\\2-\\3" ,$number);
    }
}


function common_checked_phone_number_type($number)
{
    $number = preg_replace('/-/', '', $number);
    $number_type = (preg_match('/^01[016789]{1}-?([0-9]{3,4})-?[0-9]{4}$/', $number))? 'M' : 'T';

    return $number_type;
}

function common_checked_birth_format($birth)
{
    $birth = preg_replace('/(\d{4})(\d{2})(\d{2})/', '$1-$2-$3', $birth);

    return $birth;
}
function common_urlutfchr($text){
    return rawurldecode(preg_replace_callback('/%u([[:alnum:]]{4})/', 
    function ($text) {

        return iconv('UTF-16LE', 'UTF-8', chr(hexdec(mb_substr($text[1], 2, 2,'utf-8'))).chr(hexdec(mb_substr($text[1], 0, 2,'utf-8'))));
    },
    $text));
}

//이미지 src 경로 얻기
function common_get_editor_image($contents)
{
    if(!$contents) return false;
    // $pattern = "/<img[^>]*src=[\'\"]?([^>\'\"]+)[\'\"]?[^>]*>/";
    $pattern = "/<img[^>]*src=[\'\"]?([^>\'\"]+[^>\'\"]+)[\'\"]?[^>]*>/i";
    preg_match_all($pattern, $contents, $matchs);

    return $matchs;
}

function common_get_time() 
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

// 강사 근무시간(web_time) 코드를 한글로 치환
function common_web_time_to_str($web_time_code,$web_av_time='')
{
    $web_time = [
        'A' => '6:00 - 15:00',
        'B' => '15:00 - 24:00',
        'C' => '6:00-10:00',
        'D' => '20:00 - 24:00',
        'E' => 'web_av_time',
    ];

    return $web_time_code != 'E' ? $web_time[$web_time_code]:$web_av_time;
}

// 포인트 지급 유형별
function common_point_standard($type)
{
    $point = [
        'tutor_evaluation'      => 500,  #강사평가

        'join_survey'           => 5000, #회원가입 후 설문조사

        'common_comment'        => 200,  #댓글달기 기본포인트
        'mint_manual_comment'   => 5000, #댓글달기 민트사용설명서
        'today_a_word_comment'  => 500,  #댓글달기 오늘의 영어한마디
        'azak_comment'          => 200,  #댓글달기 영문법아작내기
        'etc_comment'           => 100,  #댓글달기 

        'nickname_modi'         => -30000,  #닉네임변경
        'mset'                  => 30000,         #mset 신청

    ];

    return $point[$type] ? $point[$type] : 0;
}

// 트로피 치환
function common_tropy_to_str($tropy,$onlystr=false)
{
    $tropy_type = [
        0 => [
            'ns',
            'Natural Speech (발음수업) 가능한 선생님'
        ],
        1 => [
            'ielts',
            'IELTS(아이엘츠) 고득점 선생님'
        ],
        2 => [
            'upu',
            'US vs PHIL vs UK'
        ],
        3 => [
            'ahop_social',
            '미국교과과정 AHOP Social'
        ],
       /*  4 => [
            'mintbee',
            '민트비라이브 수업 가능'
        ], */
        5 => [
            'ahop_science',
            '미국교과과정 AHOP Science'
        ],
        6 => [
            'ahop_math',
            '미국교과과정 AHOP Math'
        ],
        7 => [
            'meg',
            'MEG영문법'
        ],
    ];

    return $onlystr ? $tropy_type[$tropy][1]:$tropy_type[$tropy];
}


//네이버 한글 영어로 변환 API
function common_request_naverapi_kor_to_eng($name){

    // 네이버 한글인명-로마자변환 Open API 예제
    $client_id = "LOJZznDBfSbpeiwFVvMC"; // 네이버 개발자센터에서 발급받은 CLIENT ID
    $client_secret = "OQsw_UJQMS";// 네이버 개발자센터에서 발급받은 CLIENT SECRET
    $encText = urlencode($name);

    $url = "https://openapi.naver.com/v1/krdict/romanization?query=".$encText;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = array();
    $headers[] = "X-Naver-Client-Id: ".$client_id;
    $headers[] = "X-Naver-Client-Secret: ".$client_secret;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec ($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close ($ch);
    if($status_code == 200) {
        $result = json_decode($response,true);
        return array('state'=> 1, 'ename'=>$result['aResult'][0]['aItems'][0]['name']);
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec ($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close ($ch);
        if($status_code == 200) {
            $result = json_decode($response,true);
            return array('state'=> 1, 'ename'=>$result['aResult'][0]['aItems'][0]['name']);
        } else{
            return array('state'=> 0, 'errmsg'=>$response);
        }
    }
}


/*
    common_find_s3_src_from_content
    ->
    Array(
        [0] => Array
            (
                [0] => <img src="https://cdn.mintspeaking.com/assets/icon/exam/icon_exam_notice.png"
            )
        [1] => Array
            (
                [0] => https://cdn.mintspeaking.com/assets/icon/exam/icon_exam_notice.png
            )
    )
*/
function common_find_s3_src_from_content($content)
{
    preg_match_all('/<img.*src=\\\?["|\']('.Thumbnail::$cdn_default_url_PCRE.'[^"|\']+)\\\?[\"|\']/Usim',$content,$matches);
    return $matches;
}


function common_get_special_tablename($code)
{
    $table_code = [
        '9001'=>'이런표현어떻게', 
        '9002'=>'얼굴철판딕테이션', 
        '9004'=>'영어첨삭',
        '9999'=>'실시간요청게시판'
    ];

    return $table_code[$code];
}

function common_get_special_tablecode($code)
{
    $table_code = [
        '9001'=>'express', 
        '9002'=>'dictation.list', 
        '9004'=>'correction',
        '9999'=>'request'
    ];

    return $table_code[$code];
}

/* 
    부족한 자릿수를 앞에서부터 0으로 채움
    data = 데이터
    num = 채울 자리수
*/
function common_zerofill($data, $num) 
{    
    return  sprintf('%0'.$num.'d', $data);
}

function common_checked_byte($content)
{
    $content = iconv('UTF-8', 'EUC-KR', $content); // EUC-KR
    //str_hex_dump($s1); // EA B0 80 (3 bytes)
    //str_hex_dump($s2); // B0 A1 (2 bytes)

    $a = unpack('C*', $content);
    $i = 0;
    foreach ($a as $v)
    {
        $h = strtoupper(dechex($v));
        if (strlen($h)<2) $h = '0'.$h;
        echo $h.' ';
        ++$i;
    }

    return $i;
}


function common_eduserve_info()
{
    $info = [
        'name'   => '(주)에듀서브',
        'owner'  => '손영희',
        'addr1'  => '서울시 구로구 디지털로',
        'addr2'  => '243, 1808호',
        'addr3'  => '(구로동, 지하이시티)',
        'number' => '123-86-15116',
        'type'   => '교육서비스업',
        'item'   => '외국어교육',
    ];

    return $info;
}


//검색어를 중앙에 두고 앞뒤로 자르기, 맨앞 문자열의 경우 온전히 출력 하도록
function common_search_ellipsis($content, $keyword, $ellipsis_len)
{
    //키워드 길이
    $keyword_strlen = mb_strlen($keyword, "utf-8");

    //$content에서 키워드 위치
    $keyword_index = mb_strpos($content, $keyword, 0, "utf-8");
    //키워드 위치가 없으면 빈값을 반환
    if($keyword_index === false) return '';

    //자를 문자열 길이 = (자를길이 * 2(=앞뒤자를길이)) + 키워드 길이
    $str_num = ($ellipsis_len * 2) + $keyword_strlen;

    //자를 문자열 시작점 = 키워드 위치 - 자를길이
    $start_index = $keyword_index - $ellipsis_len;

    //문자열 시작점이 마이너스일 경우 0으로 고정
    if ($start_index < 0)
        $start_index = 0;
    
    //자른 결과값을 ' '로 잘라 배열 저장
    $array_ellipsis = explode(' ', mb_substr($content, $start_index, $str_num, "utf-8"));

    //자른 맨앞 키워드가 있을 경우에만 비교하고 치환한다
    if($array_ellipsis[0] != '')
    {
        //해당 범위를 ' '로 잘라 배열 저장
        $array_content = explode(' ', $content);

        //기존 키워드와 자른 키워드를 비교한다
        foreach($array_content as $key => $value)
        {
            //완전히 같을 경우 루프 종료
            if($value == $array_ellipsis[0]) break;
    
            //첫번째 문자열 과 비슷한 문자열이 있을 경우
            //두번째 문자열까지 완전히 같다면 치환하고 루프를 종료
            if(mb_strpos($value, $array_ellipsis[0], 0, "utf-8") && $array_content[$key+1] == $array_ellipsis[1])
            {
                $array_ellipsis[0] = $value;
                break;
            }
        }
    }

    return implode(' ', $array_ellipsis);
}


function common_social_icon($regi_gubun='')
{
    $icon = [
        "mint05"    => "",
        "naver"     => "<img src='https://cdn.mintspeaking.com/adm_assets/image/login_with_naver.gif' alt='naver'>",
        "google"    => "<img src='https://cdn.mintspeaking.com/adm_assets/image/login_with_google.gif' alt='google'>",
        "facebook"  => "<img src='https://cdn.mintspeaking.com/adm_assets/image/login_with_facebook.gif' alt='facebook'>",
        "kakao"     => "<img src='https://cdn.mintspeaking.com/adm_assets/image/login_with_kakao.gif' alt='facebook'>",
    ];

    return array_key_exists($regi_gubun, $icon) ? $icon[$regi_gubun]:$icon;
}

//현재 주소 및 가져온 주소를 바탕으로 (구민트,신민트 - 로컬,개발,실서버) 주소를 완성시켜준다
function set_new_or_old_url($url, $is_mobile=false)
{
    $domain = "";

    //풀주소일 경우 그대로 리턴
    if(strpos($url,"https://") !== false || strpos($url,"http://") !== false) return $url;

    //url이 빈값일 경우 메인으로
    if($url == '') $url = '/#/main';

    if($_SERVER['HTTP_HOST'] == 'localhost:8000')
    {
        if(strpos($url,"/#/") !== false)
            $domain = 'http://localhost:8001';
        else
            $domain = $is_mobile ? 'http://localhost:8005':'http://localhost:8003';
    }
    else if($_SERVER['HTTP_HOST'] =='dsapi.mintspeaking.com')
    {
        if(strpos($url,"/#/") !== false)
            $domain = 'https://dsm.mintspeaking.com';
        else
            $domain = $is_mobile ? 'http://dold.mintspeaking.com/mobile':'http://dold.mintspeaking.com';
    }
    else
    {
        if(strpos($url,"/#/") !== false)
            $domain = 'https://story.mint05.com';
        else
            $domain = $is_mobile ? 'http://m.mint05.com':'http://www.mint05.com';
    }

    return $domain.$url;
}

function common_is_app($app='')
{
    $ios = strpos($_SERVER['HTTP_USER_AGENT'], 'IOS_NEW_PACKAGE') !== false;
    $android = strpos($_SERVER['HTTP_USER_AGENT'], 'ANDROID_NEW_PACKAGE') !== false;

    if($app == 'android')
    {
        return $android ? true:false;
    }
    elseif($app == 'ios')
    {
        return $ios ? true:false;
    }
    else
    {
        return $ios || $android ? true:false;
    }
}
