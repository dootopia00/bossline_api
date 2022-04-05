<?php
require_once APPPATH . '/libraries/JWT/JWT.php';
require_once APPPATH . '/libraries/JWT/ExpiredException.php';
require_once APPPATH . '/libraries/JWT/BeforeValidException.php';
require_once APPPATH . '/libraries/JWT/SignatureInvalidException.php';
use \Firebase\JWT\JWT;



function token_create_member_token($id, $admin_id='')
{
    $id = strtolower($id);
    $date = new DateTime();
    /*
     * Claim Set
     * iss: 토큰을 발급한 발급자(Issuer)
        sub: Claim의 주제(Subject)로 토큰이 갖는 문맥을 의미한다.
        aud: 이 토큰을 사용할 수신자(Audience)
        exp: 만료시간(Expiration Time)은 만료시간이 지난 토큰은 거절해야 한다.
        nbf: Not Before의 의미로 이 시간 이전에는 토큰을 처리하지 않아야 함을 의미한다.
        iat: 토큰이 발급된 시간(Issued At)
        jti: JWT ID로 토큰에 대한 식별자이다.
     */

    $iat =  $date->getTimestamp();
    $exp =  $date->getTimestamp() + 60*60*240000;
    $key = $exp;
    for($i = 0; $i < strlen($id); $i++)
    {

        if($i % 2 == 1)
        {
            $key += ord($id[$i]);
        }
        elseif($i% 2 == 0)
        {
            $key -= ord($id[$i]);
        }

    }

    $key = $key * $iat;

    $payload = array(
        "iss" => CLIENT_DOMAIN,
        "iat" => $iat,
        "exp" => $exp,
        "data" => array(
            "id" => $id,
            "key" => $key
        )
    );

    if($admin_id)
    {
        $payload['data']['aid'] = $admin_id;
    }

    $JWT = JWT::encode($payload, CLIENT_SECRET_KEY , CLIENT_SECRET_ALGORITHM);

    return $JWT;
}

/*
 * request 헤더정보로 token확인
 */
function token_user_token_validation($req_id, $authorization = null){

    $req_id = strtolower($req_id);
    $return_array = array();
    $return_array['res_code'] = "0401";
    $return_array['msg'] = '잘못된 접근입니다.';
    $return_array['token'] = null;

    $request_token = ($authorization) ? ($authorization) : null;
    $request_debug = null;
    $request_debug_param = null;

    try {
        $headers = apache_request_headers();
        
        $return_array['token'] = $headers;
        foreach ($headers as $header => $value) 
        {
            if (strtolower($header) == CLIENT_TOKEN_KEY) 
            {
                $request_token = $value;
            }

            if (strtolower($header) == CLIENT_DEBUG_KEY) 
            {
                $request_debug = $value;
            }

            if (strtolower($header) == CLIENT_DEBUG_PARAM_KEY) 
            {
                $request_debug_param = $value;
            }
        }

        if(!$request_debug || ($request_debug != CLIENT_DEBUG_VALUE) ) 
        {

            if($request_token) 
            {
                $decoded = JWT::decode($request_token, CLIENT_SECRET_KEY, array(CLIENT_SECRET_ALGORITHM));

                if($decoded) {
                    //발급자가 일치하는지 확인 발급기간 체크

                    $iat =  $decoded->iat;
                    $exp =  $decoded->exp;
                    $data=(array)$decoded->data;

                    $auth_key = $data['key'];
                    $key = $exp;
                    $id = strtolower($data['id']);
                    for($i = 0; $i < strlen($id); $i++)
                    {
                        if($i % 2 == 1)
                        {
                            $key += ord($id[$i]);
                        }
                        elseif($i% 2 == 0)
                        {
                            $key -= ord($id[$i]);
                        }
                    }

                    $key = $key * $iat;

                    if($decoded->iss == CLIENT_DOMAIN && $key == $auth_key && $id == $req_id) 
                    {
                        $return_array['res_code'] = "0000";
                        $return_array['msg'] = "";
                        $return_array['aid'] = $data['aid'] ? $data['aid']:null;
                        $return_array['token'] = $request_token;
                    }
                }
            }
        }
        else if($request_debug == CLIENT_DEBUG_VALUE)
        {
            $return_array['res_code'] = "0000";
            $return_array['msg'] = "";
            $return_array['token'] = $request_token;
        }
    }catch (Exception $e){
        $return_array['msg'] = $e->getMessage();
        return $return_array;
    }
    return $return_array;

}


/*
    강사 사이트 토큰
*/
function token_create_tutor_token($id,$admin_id='')
{
    $id = strtolower($id);
    $date = new DateTime();
    /*
     * Claim Set
     * iss: 토큰을 발급한 발급자(Issuer)
        sub: Claim의 주제(Subject)로 토큰이 갖는 문맥을 의미한다.
        aud: 이 토큰을 사용할 수신자(Audience)
        exp: 만료시간(Expiration Time)은 만료시간이 지난 토큰은 거절해야 한다.
        nbf: Not Before의 의미로 이 시간 이전에는 토큰을 처리하지 않아야 함을 의미한다.
        iat: 토큰이 발급된 시간(Issued At)
        jti: JWT ID로 토큰에 대한 식별자이다.
     */

    $iat =  $date->getTimestamp();
    $exp =  $date->getTimestamp() + 60*60*240000;
    $key = $exp;
    for($i = 0; $i < strlen($id); $i++)
    {

        if($i % 2 == 1)
        {
            $key += ord($id[$i]);
        }
        elseif($i% 2 == 0)
        {
            $key -= ord($id[$i]);
        }

    }

    $key = $key * $iat;

    $payload = array(
        "iss" => TUTOR_CLIENT_DOMAIN,
        "iat" => $iat,
        "exp" => $exp,
        "data" => array(
            "id" => $id,
            "key" => $key
        )
    );

    if($admin_id)
    {
        $payload['data']['aid'] = $admin_id;
    }

    $JWT = JWT::encode($payload, TUTOR_CLIENT_SECRET_KEY , TUTOR_CLIENT_SECRET_ALGORITHM);

    return $JWT;
}

/*
 * request 헤더정보로 token확인
 */
function token_tutor_token_validation($req_id, $authorization = null){

    $req_id = strtolower($req_id);
    $return_array = array();
    $return_array['res_code'] = "0401";
    $return_array['msg'] = '잘못된 접근입니다.';
    $return_array['token'] = null;

    $request_token = ($authorization) ? ($authorization) : null;
    $request_debug = null;
    $request_debug_param = null;

    try {
        $headers = apache_request_headers();
        
        $return_array['token'] = $headers;
        foreach ($headers as $header => $value) 
        {
            if (strtolower($header) == TUTOR_CLIENT_TOKEN_KEY) 
            {
                $request_token = $value;
            }

            if (strtolower($header) == TUTOR_CLIENT_DEBUG_KEY) 
            {
                $request_debug = $value;
            }

            if (strtolower($header) == TUTOR_CLIENT_DEBUG_PARAM_KEY) 
            {
                $request_debug_param = $value;
            }
        }

        if(!$request_debug || ($request_debug != TUTOR_CLIENT_DEBUG_VALUE) ) 
        {

            if($request_token) 
            {
                $decoded = JWT::decode($request_token, TUTOR_CLIENT_SECRET_KEY, array(TUTOR_CLIENT_SECRET_ALGORITHM));

                if($decoded) {
                    //발급자가 일치하는지 확인 발급기간 체크

                    $iat =  $decoded->iat;
                    $exp =  $decoded->exp;
                    $data=(array)$decoded->data;

                    $auth_key = $data['key'];
                    $key = $exp;
                    $id = strtolower($data['id']);
                    for($i = 0; $i < strlen($id); $i++)
                    {
                        if($i % 2 == 1)
                        {
                            $key += ord($id[$i]);
                        }
                        elseif($i% 2 == 0)
                        {
                            $key -= ord($id[$i]);
                        }
                    }

                    $key = $key * $iat;

                    if($decoded->iss == TUTOR_CLIENT_DOMAIN && $key == $auth_key && $id == $req_id) 
                    {
                        $return_array['res_code'] = "0000";
                        $return_array['msg'] = "";
                        $return_array['aid'] = $data['aid'] ? $data['aid']:null;
                        $return_array['token'] = $request_token;
                    }
                }
            }
        }
        else if($request_debug == TUTOR_CLIENT_DEBUG_VALUE)
        {
            $return_array['res_code'] = "0000";
            $return_array['msg'] = "";
            $return_array['token'] = $request_token;
        }
    }catch (Exception $e){
        $return_array['msg'] = $e->getMessage();
        return $return_array;
    }
    return $return_array;

}



?>
