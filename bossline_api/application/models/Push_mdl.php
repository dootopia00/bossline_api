<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Push_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }


    public function push_conf_by_push_code($push_code)
    {
        $this->db_connect('slave');

        $sql = 'SELECT * FROM wiz_push_conf WHERE push_code = ? ORDER BY push_id DESC LIMIT 1';
                
        $res = $this->db_slave()->query($sql, array($push_code));

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }


    
    public function get_member_push_token($uid,$push_gubun,$pushCode)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM wiz_member_token 
                WHERE uid = ".$uid." AND ".$push_gubun."_receive = 'Y' AND (block_code is null OR block_code not like '%".$pushCode."%') 
                ORDER BY last_modify_date DESC, regdate DESC";
                
        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function insert_wiz_push_result($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_push_result',$param);
        // echo $this->db_master()->last_query();exit;
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

}










