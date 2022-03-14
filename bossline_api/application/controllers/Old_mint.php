<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

define("GOOGLE_SERVER_KEY", "yourserverkey");

class Old_mint extends _Base_Controller {


    public function __construct()
    {
        parent::__construct();

        date_default_timezone_set('Asia/Seoul');

        $this->load->library('form_validation');
        $this->upload_path = 'test_upload/';
    }
    
	function send_FCM()
	{
        
        $this->load->model('member_mdl');

        $wiz_member = $this->member_mdl->get_wm_uid_by_wiz_id('gjwogur0308@gmail.com');

        $member_token = $this->member_mdl->get_wiz_token($wiz_member['wm_uid']);
        
         $url = 'https://fcm.googleapis.com/fcm/send';
        // $url = 'https://fcm.googleapis.com/v1/projects/myproject-b5ae1/messages:send HTTP/1.1';
        // $url = 'https://fcm.googleapis.com/v1/{parent=projects/*}/messages:send';
        //$url = 'https://fcm.googleapis.com/v1/projects/myproject-b5ae1/messages:send';
        
        $key = "AAAAExEIGRw:APA91bH72vntM_wRvJEcIt-ULZ56k6iLtjXg0Quj-gKDD9zUREVjdWw7kX0NBlv6SlN_5-lRjUEnefsrXamPHe1rBziBQjKEMBYvlybytD8FTbH7uIIFW9IqcNjJuUPD-SuNM0swS9gp";
        $token = $member_token['wmt_token'];
        $headers = array(
            'Authorization:key =' . $key,
            'Content-Type: application/json'
            );


        // 안드로이드
		$fields = array(
                'registration_ids' => $token,
                // 'data' => $message,
                'data' => array('teacherId' => $teacher_id, 'channelId' => $channel_id,),
                'priority' => "high"
            );

        /*
        $fields = array(
            'registration_ids' => $token,
            'priority' => 10,
            // 'priority'=>'high',
            'notification' => array('title' => $title, 'body' => $message ,'sound'=>'Default'),
        );
        */

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);           
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        echo json_encode($result);
        exit;
	}


    public function urlutfchr($text){
        return rawurldecode(preg_replace_callback('/%u([[:alnum:]]{4})/', 
        function ($text) {
            // return iconv('UTF-8', 'EUC-KR', chr(hexdec(substr($text[1], 2, 2))).chr(hexdec(substr($text[1], 0, 2))));
            return iconv('UTF-16LE', 'UTF-8', chr(hexdec(substr($text[1], 2, 2))).chr(hexdec(substr($text[1], 0, 2))));
            // return iconv('UTF-16LE', 'cp949', chr(hexdec(substr($text[1], 2, 2))).chr(hexdec(substr($text[1], 0, 2))));
        },
        $text));
    } 
    
    public function getMessageForList($num = 50)
    {	
		$messageChk = $this->message;
		if((substr($messageChk,0,2) == "%u") || strpos($messageChk,"%u") !== false){
			$messageChk = common_urlutfchr($this->message);
		}else{
			$messageChk = $this->message;
		}
        return mb_substr(strip_tags(str_replace('&nbsp;', ' ', $messageChk)), 0, $num, 'cp949');
    }

    public function Upload()
    {
        /*
            보통 $request['files'][$i]['name'] 로 받는데 
            임시 경로로 오고있어서( ex : file_name.tmp )
            $request['data'][$i]['file_name'] 
            로 받아서 S3로 넘겨서 사용
        */
        $return_array = array();
        $request = array();

        $datas = json_decode($_POST['data'], true);
    

        $upload_limit_size = 5;
        $max_upload_size = 1047576 * $upload_limit_size;   //5MB;
    
        //업로드 양식 체크
        for($i=0; $i<count($datas); $i++)
        {
            $extension = explode('.', $datas[$i]['file_name']);
            $extension = end($extension);
            $ext_array = array('jpg', 'jpeg', 'png','gif', 'mp3', 'mp4', 'doc', 'docx', 'flv', 'xlsx', 'xls', 'pdf', 'txt','pptx','ppt','zip');
    
            //확장자 체크
            if(!in_array(strtolower($extension), $ext_array))
            {
                $ext_text = implode(', ', $ext_array);
                $return_array['res_code'] = '0900';
                $return_array['msg'] = "프로세스오류";
                $return_array['data']['err_code'] = '0901';
                $return_array['data']['err_msg'] = $ext_text. ' 파일 업로드만 가능합니다.';
            
                echo json_encode($return_array);
                return $return_array;
            }
    
            //용량 체크
            // if($_FILES['files']['size'][$i] > $max_upload_size)
            // {
            //     $return_array['res_code'] = '0900';
            //     $return_array['msg'] = "프로세스오류";
            //     $return_array['data']['err_code'] = '0902';
            //     $return_array['data']['err_msg'] = $upload_limit_size. 'MB보다 큰 이미지는 업로드 할 수 없습니다.';
            
            //     return $return_array;
            // }
        }
    

        //파일업로드
        if(isset($datas))
        {
            for($i=0; $i<count($datas); $i++)
            {
                $request[$i]['data']['path'] = $datas[$i]['path'];
                $request[$i]['data']['file_name'] = $datas[$i]['file_name'];
				

				$request[$i]['files']['name'] = $_FILES['files']['name'][$i];
                $request[$i]['files']['type'] = $_FILES['files']['type'][$i];
                $request[$i]['files']['tmp_name'] = $_FILES['files']['tmp_name'][$i];
                $request[$i]['files']['size'] = $_FILES['files']['size'][$i];
				

                if(isset($request[$i]["files"]))
                {
                    $result =  S3::old_mint_put_s3_object($request[$i]['data']['path'], $request[$i]["files"], $request[$i]['data']['file_name']);
    
                    if($result['res_code'] != '0000')
                    {
                        echo json_encode($return_array);
                        exit;
                    }
                }
            }
    
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "파일 업로드에 성공했습니다.";
			echo json_encode($return_array);
            exit;
        }
    
        $return_array['res_code'] = '0900';
        $return_array['msg'] = "프로세스오류";
        $return_array['data']['err_code'] = '0903';
        $return_array['data']['err_msg'] = '파일을 확인해주세요.';
        echo json_encode($return_array);
        exit;

    }

    public function Delete()
    {
        $return_array = array();
        $request = array();

        $datas = json_decode($_POST['data'], true);

        if(isset($datas))
        {
            for($i=0; $i<count($datas); $i++)
            {
                $request[$i]['datas']['path'] = $datas[$i]['path'];
                $request[$i]['datas']['file_name'] = $datas[$i]['file_name'];

                $result = S3::delete_s3_object($request[$i]['datas']['path'], $request[$i]['datas']['file_name']);
                
                if($result['res_code'] != '0000')
                {
                    echo json_encode($result);
                    exit;
                }
            }

            $return_array['res_code'] = '0000';
            $return_array['msg'] = "파일삭제에 성공했습니다.";
            echo json_encode($return_array);
            exit;
        }

        $return_array['res_code'] = '0900';
        $return_array['msg'] = "프로세스오류";
        $return_array['data']['err_code'] = '0903';
        $return_array['data']['err_msg'] = '파일을 확인해주세요.';
        echo json_encode($return_array);
        exit;
        
    }

    
    public function Download()
    {
        S3::get_s3_object($_POST['path'],$_POST['name'],true);
    }

}

