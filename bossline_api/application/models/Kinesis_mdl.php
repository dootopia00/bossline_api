<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Kinesis_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function list_count_mint_webrtc_recoding($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                        FROM mint_webrtc_recoding mwr %s
                        ", $where);

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function insert_kinesis_files($file_info)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_webrtc_recoding', $file_info);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
}










