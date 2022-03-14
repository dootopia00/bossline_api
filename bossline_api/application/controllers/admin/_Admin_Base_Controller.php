<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class _Admin_Base_Controller extends CI_Controller {

        //어드민 관리자 인증 정보
        private $MINT_ADMIN_DATA = NULL;
        //어드민 인증 오류시 메시지
        private $ERR_AUTH_CHECK_MSG = '로그인 정보가 없습니다.';
        
	public function __construct()
	{
          parent::__construct();
                
                $request_headers        = apache_request_headers();
                $request_agent =  explode( '/', $request_headers['User-Agent']);
                date_default_timezone_set('Asia/Seoul');

                header("Access-Control-Allow-Origin: *");
                header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
                header("Access-Control-Allow-Headers: X-Requested-With, Authorization, Develop, Content-Type");
                header('Content-Type: application/json');
                
                //admin_base_init();
        }
        

}
