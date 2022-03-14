<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Schedule_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();
    }

    /**
     * 스케쥴 정보 업데이트
     */
    public function update_wiz_schedule($sc_id, $param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where_in('sc_id', $sc_id);
        $this->db_master()->update('wiz_schedule', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    /**
	 * 스케쥴 정보
	 */
	public function article_schedule($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT ws.*
                        FROM wiz_schedule as ws
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
	 * 마지막 스케쥴정보를 가져온다
	 */
    public function row_last_schedule($lesson_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.sc_id as ws_sc_id, ws.startday as ws_startday, ws.present as ws_present
                FROM wiz_schedule as ws WHERE ws.lesson_id = ? AND ws.present NOT IN ( 7,8 ) ORDER BY ws.startday DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($lesson_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

	/**
	 * 조회한 레슨의 마지막 수업을 주 단위로 가져옴
	 */
	public function get_last_schedule_data($lesson_id)
	{
		$this->db_connect('slave');

		$sql = "SELECT DATE(ws.startday) as ws_startday
                FROM wiz_schedule as ws WHERE ws.lesson_id = ? AND ws.kind='n' ORDER BY ws.startday DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($lesson_id));

		$sql = "SELECT ws.startday as ws_startday
                FROM wiz_schedule as ws WHERE ws.lesson_id = ? AND ws.kind='n'
					 AND ws.startday between date_sub('".$res['ws_startday']."', INTERVAL 2 WEEK) and '".$res['ws_startday']." 23:59:59'
				ORDER BY ws.startday DESC LIMIT 1";

        $startday = $this->db_slave()->query($sql, array($lesson_id));

		return $startday->num_rows() > 0 ? $startday->result_array() : NULL;
	}

	/**
	 * 스케쥴 리스트
	 */
	public function list_schedule($join, $where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT ws.*
                        FROM wiz_schedule as ws
                        %s %s %s %s", $join, $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    

	public function insert_wiz_schedule_out($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_schedule_out', $param);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return $insert_id;
    }


}










