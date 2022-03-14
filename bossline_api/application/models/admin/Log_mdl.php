<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Log_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }


    /**
     * 선택된 연도 날짜내에 수업시간,가용시간을 구한다
     * 총 수업시간 - class_time
     * 강사 총 가용시간 - available_time
     */
    public function get_class_available_time($sdate, $edate)
    {
        $this->db_connect('slave');

        $sql = "SELECT DATE_FORMAT(ws.startday, '%Y-%m') AS date, SUM(IF(ws.present IN ('1','2','3','4'),ws.cl_time,NULL)) AS class_time, SUM(ws.cl_time) AS available_time
                FROM wiz_schedule as ws USE INDEX(idx_resign)
                WHERE ws.lesson_id != 100000001 AND ws.startday >= '".$sdate." 00:00:00' AND ws.startday <= '".$edate." 23:59:59'
                GROUP BY date";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 강사 날짜별 입사자 수
     */
    public function get_count_tutor_join($sdate, $edate)
    {
        $this->db_connect('slave');

        $sql = "SELECT DATE_FORMAT(wt.tu_regdate, '%Y-%m') AS date, COUNT(1) AS tu_join
                FROM wiz_tutor AS wt USE INDEX(resign)
                WHERE wt.tu_regdate >= '".$sdate." 00:00:00' AND wt.tu_regdate <= '".$edate." 23:59:59'
                GROUP BY date";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 강사 날짜별 퇴사자 수
     */
    public function get_count_tutor_resign($sdate, $edate)
    {
        $this->db_connect('slave');

        $sql = "SELECT DATE_FORMAT(wt.del_date, '%Y-%m') AS date, COUNT(IF(wt.del_yn='y', 1, null)) AS tu_resign
                FROM wiz_tutor AS wt USE INDEX(resign)
                WHERE wt.del_date >= '".$sdate." 00:00:00' AND wt.del_date <= '".$edate." 23:59:59'
                GROUP BY date";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    /**
     * 퇴사하지않은 총 강사수
     */
    public function get_count_total_tutor($date)
    {
        $this->db_connect('slave');

        $sql = "SELECT COUNT(1) AS cnt
                FROM wiz_tutor AS wt USE INDEX(resign)
                WHERE wt.tu_regdate <= '".$date."-31 23:59:59' AND (wt.del_date >= '".$date."-31 23:59:59' OR wt.del_date is NULL OR wt.del_date = '0000-00-00 00:00:00')";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

}










