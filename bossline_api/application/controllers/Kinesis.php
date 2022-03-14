<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Kinesis extends _Base_Controller {


    public function __construct()
    {
        parent::__construct();

        $this->upload_path = 'attach/webrtc/';
        $this->load->library('form_validation');
    }

    public function get_channel_list()
    {
        $return_array = array();

        $request = array(
            "channel_name" => $this->input->post('channel_name') ? trim(strtolower($this->input->post('channel_name'))) : null,
            "count" => $this->input->post('count') ? trim(strtolower($this->input->post('count'))) : 10,
        );

        $res = KinesisVideo::get_channel_list($request['channel_name'], $request['count']);

        if(isset($res[0])){
            
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "채널 조회을 성공했습니다.";
            $return_array['data']['list'] = $res;
            echo json_encode($return_array);
            exit;

        }else{
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "채널목록이 없습니다.";
            echo json_encode($return_array);
            exit;
        }
    }

    public function create_channel()
    {

        $return_array = array();

        $request = array(
            "channel_name" => $this->input->post('channel_name') ? trim(strtolower($this->input->post('channel_name'))) : null,
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $res = KinesisVideo::create_channel($request['channel_name']);
        
        if($res['statusCode'] == '200'){
            
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "채널 생성을 성공했습니다.";
            echo json_encode($return_array);
            exit;
        }else{
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = $res;
            $return_array['data']['err_msg'] = $res;
            echo json_encode($return_array);
            exit;

        }


    }

    public function delete_channel()
    {
        $return_array = array();

        $request = array(
            "channel_name" => $this->input->post('channel_name') ? trim(strtolower($this->input->post('channel_name'))) : null,
        );
        
        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        // $channel_name = 'asdasdasd';
        // $channel_array = array('test','sfsfsf','2211test');

        $res = KinesisVideo::delete_channel($request['channel_name']);

        if($res['statusCode'] == '200'){
            
            $return_array['res_code'] = '0000';
            $return_array['msg'] = "채널 삭제를 성공했습니다.";
            echo json_encode($return_array);
            exit;
        }else{
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스오류";
            $return_array['data']['err_code'] = $res;
            $return_array['data']['err_msg'] = $res;
            echo json_encode($return_array);
            exit;

        }
        
    }

    public function kinesis_file_upload()
    {
        $return_array = array();

        $request = array(
            "files" => isset($_FILES["files"]) ? $_FILES["files"] : null,
            "sc_id" => $this->input->post('sc_id') ? trim(strtolower($this->input->post('sc_id'))) : null,
            // "type" => $this->input->post('type') ? $this->input->post('type') : null,       /* 정상적인 경우 : 1(connected), 닫았을 때 : 2(closed), 나갔을때 : 3(disconnected), 나갔을때 : 4(failed) */
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        if($request['files'] == null){

            $return_array['res_code'] = '0400';
            $return_array['msg'] = '파일이 존재하지 않습니다.';
            echo json_encode($return_array);
            exit;
        }
        else
        {
            
            /*
                파일 업로드 확장자 제한여부
                null : 제한없음
                null 아닐시 : 제한
            */
            $upload_limit_size = 20;
            $ext_array = array('mp3', 'mp4');
            $this->load->model('kinesis_mdl');
            
            $where = " WHERE sc_id = '{$request['sc_id']}'";
            
            $result = $this->kinesis_mdl->list_count_mint_webrtc_recoding($where);
            if($result['cnt'] == '0') $count = '1';
            else $count = $result['cnt'];
            
            $this->load->library('s3');            
            $res = S3::put_kinesis_s3_object($this->upload_path, $request['files'], $upload_limit_size, $count, $ext_array);
            
            // 인설트 데이터
            if($res['res_code']=='0000')
            {
                $file_info = array(
                    "sc_id" => $request['sc_id'],
                    // "wr_type" => $request['type'],      /* 정상적인 경우 : 1(connected), 닫았을 때 : 2(closed), 나갔을때 : 3(disconnected), 나갔을때 : 4(failed) */          
                    "wr_url" => $res['url'],
                    "wr_regdate" => date("Y-m-d H:i:s"),
                );
    
                $result = $this->kinesis_mdl->insert_kinesis_files($file_info);
    
                if($result < 0)
                {
                    $return_array['res_code'] = '0500';
                    $return_array['msg'] = "DB ERROR";
                    echo json_encode($return_array);
                    exit;
                }
            }

            echo json_encode($res);
            exit;
                
        }
    }
}








