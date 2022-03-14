<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Etc_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }
    

    function checked_count_utm_url($muu_key)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT count(1) AS cnt
                FROM mint_utm_url     
                WHERE muu_key = ?";

        $res = $this->db_slave()->query($sql, array($muu_key));
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    function checked_today_log($muu_key, $ref_key, $loc, $ip)
    {
        $date = date('Y-m-d');

        $this->db_connect('slave');
    
        $sql = "SELECT mul_key
                FROM mint_utm_log     
                WHERE muu_key = ? AND ref_key = ? AND type ='1' 
                AND loc = ? AND ip = ? AND regdate BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59'";

        $res = $this->db_slave()->query($sql, array($muu_key, $ref_key, $loc, $ip));

        // echo $this->db_slave()->last_query();
        // exit;
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // function checked_count_leveltest_log_by_uid($uid)
    // {
    //     $this->db_connect('slave');
    
    //     $sql = "SELECT count(mul.mul_key) AS cnt FROM mint_utm_log mul WHERE mul.type = 3 AND mul.ref_uid = ?";

    //     $res = $this->db_slave()->query($sql, array($uid));
        
    //     return $res->num_rows() > 0 ? $res->row_array() : NULL;
    // }

    public function insert_utm($utm)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_utm_log', $utm);

        $insert_id = $this->db_master()->insert_id();
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }


    function checked_exist_wiz_speak_call_by_aid($aid)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT 1 FROM wiz_speak_call WHERE aid= ?";

        $res = $this->db_slave()->query($sql, array($aid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function insert_wiz_speak_call($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_speak_call', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    

}










