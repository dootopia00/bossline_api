<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Holiday_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    // 해당일자가 휴일인지 체크
    public function check_holiday($psHoliday)
    {
        $this->db_connect('slave');

        $sql = "SELECT c.*, IF(h.holiday IS NULL, 0, 1) as isholiday 
                FROM wiz_holiday_control c 
                LEFT JOIN wiz_holiday h ON c.holiday=h.holiday
                WHERE c.holiday='".$psHoliday."'";
                
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    /* 휴일 목록 */
    public function list_holiday($startdate, $enddate)
    {
        $this->db_connect('slave');

        $sql = "SELECT whc.*, IF(wh.holiday IS NULL, 0, 1) as isholiday 
                FROM wiz_holiday_control whc 
                LEFT OUTER JOIN wiz_holiday wh ON whc.holiday = wh.holiday 
                WHERE whc.holiday >= ? AND whc.holiday <= ? ";
                
        $res = $this->db_slave()->query($sql, array($startdate, $enddate));
        //echo $this->db_slave()->last_query(); exit;

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }


    public function count_holiday($date)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(*) as cnt FROM wiz_holiday WHERE holiday= ?";
                
        $res = $this->db_slave()->query($sql, array($date));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    /**
     * 특정 기간 이후 휴일 전체 불러오기
     */
    public function list_holiday_all($date)
    {
        $this->db_connect('slave');

        $sql = "SELECT whc.*
                FROM wiz_holiday_control whc 
                WHERE whc.holiday >= ?
                ORDER BY whc.holiday ASC";
                
        $res = $this->db_slave()->query($sql, array($date));

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }


    /* public function update_save_send_msg($idx, $wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->set('sender_save_at', date('Y-m-d H:i:s'));
        $this->db_master()->where('sender_uid', $wm_uid);
        $this->db_master()->where('id', $idx);
        $this->db_master()->update('mint_note');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    } */


}


