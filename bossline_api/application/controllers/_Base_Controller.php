<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class _Base_Controller extends CI_Controller {

        //회원 인증 정보
        private $WIZ_MEMBER_DATA = NULL;
        //회원 인증 오류시 메시지
        private $ERR_AUTH_CHECK_MSG = '로그인 정보가 없습니다.';
        //회원계정 관리자 로그인시 관리자 아이디
        private $LOGIN_ADMIN_ID = NULL;
        
	public function __construct()
	{
                parent::__construct();
                
                $request_headers        = apache_request_headers();
                $request_agent =  explode( '/', $request_headers['User-Agent']);
                date_default_timezone_set('Asia/Seoul');
                /*
                if($request_agent[0] != "PostmanRuntime")
                {
                        $http_origin            = $request_headers['Origin'];
        
                        $allowed_http_origins   = array(
                                                        "http://localhost:8001"   ,
                                                        "http://localhost:8002"   ,
                                                        "http://dev-m.mint05.com"   ,
                                                        "http://dev-pc.mint05.com"   ,
                                                );
        
                        if (in_array($http_origin, $allowed_http_origins))
                        {  
                                header("Access-Control-Allow-Origin: " . $http_origin);
                        }
        
        
                        header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
                        header("Access-Control-Allow-Headers: X-Requested-With, Authorization, Develop, Content-Type");
                }
                */



                header("Access-Control-Allow-Origin: *");
                header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
                header("Access-Control-Allow-Headers: X-Requested-With, Authorization, Develop, Content-Type");
                header('Content-Type: application/json');
                
                // Access-Control-Allow-Headers: Origin,Accept,X-Requested-With,Content-Type,Access-Control-Request-Method,Access-Control-Request-Headers,Authorization
                base_init();

        }
        

}
