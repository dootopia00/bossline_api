<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class _git extends _Base_Controller {

    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('Asia/Seoul');
        $this->load->library('form_validation');
    }

    /**
     * GIT PULL 함수
     */
    public function git_pull()
    {
        $return_array = array();

        $request = array(
            // "authorization" => trim($this->input->post('authorization')),
            // "wiz_id"        => trim($this->input->post('wiz_id')),
            "code"          => trim($this->input->post('code'))
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        if (!function_exists("ssh2_connect"))
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = "function ssh2_connect doesn't exist";
            echo json_encode($return_array);
            exit;
        }

        /**
         * 개발서버(신민트) : 211.252.87.110 ('root','63wuqMJBPaA3')
         *  ㄴ API : cd /var/www/shell_script/shell_api.sh
         *  ㄴ mobile_client : cd /var/www/shell_script/shell_mobile_client.sh
         * 
         * 개발서버(구민트) : 14.63.170.244 ('root','T7vVyYew9hjW')
         *  ㄴ OLD_ADMIN : cd /var/www/shell_script/shell_admin.sh
         *  ㄴ OLD_client : cd /var/www/shell_script/shell_client.sh
         */

        $ip   = "localhost";
        $pass = "";
        $shell= "";
        switch($request['code'])
        {
            case 'api' : $ip   = "localhost";
                         $pass = "63wuqMJBPaA3";
                         $shell= "shell_api";
                         break;
            case 'mobile_client' : 
                         $ip   = "localhost";
                         $pass = "63wuqMJBPaA3";
                         $shell= "shell_mobile_client";
                         break;
            case 'old_admin' : 
                         $ip   = "14.63.170.244";
                         $pass = "T7vVyYew9hjW";
                         $shell= "shell_admin";
                         break;
            case 'old_client' : 
                         $ip   = "14.63.170.244";
                         $pass = "T7vVyYew9hjW";
                         $shell= "shell_client";
                         break;
            default :    $return_array['res_code'] = '0400';
                         $return_array['msg'] = "code failed";
                         echo json_encode($return_array);
                         exit;
                         break;
        }
        
        $msg = "";

        $methods = array(
            'kex' => 'diffie-hellman-group1-sha1',
            'client_to_server' => array(
                'crypt' => '3des-cbc',
                'comp' => 'none'),
            'server_to_client' => array(
                'crypt' => 'aes256-cbc,aes192-cbc,aes128-cbc',
                'comp' => 'none'));
        
        //접속시작
        $connect = ssh2_connect($ip, 22, $methods);
        if (!$connect)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = "Connection failed";
            echo json_encode($return_array);
            exit;
        }
        
        if (ssh2_auth_password($connect,'root',$pass) != true)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = "[".date('Ymd His')."] 연결 실패...";
            echo json_encode($return_array);
            exit;
        }
        $msg .= "[".date('Ymd His')."] 연결 성공!!!!...\n";
        
        $command = "cd /var/www/shell_script && sh ".$shell;
        
        $stream = ssh2_exec($connect, $command);
        
        stream_set_blocking($stream, true);
        
        $msg .= stream_get_contents($stream);
        
        //접속종료
        ssh2_exec($connect,'echo "EXITING" && exit;');
        unset($connect);
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = $msg;
        echo json_encode($return_array);
        exit;
    }
}