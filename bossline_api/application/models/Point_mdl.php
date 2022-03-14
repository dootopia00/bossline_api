<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Point_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function row_total_point()
    {
        $this->db_connect('slave');

        $sql = "SELECT SUM(point) as total_point FROM wiz_point wm WHERE wm.showYn = 'y'";
        $res = $this->db_slave()->query($sql, array());

        return $res->num_rows() > 0 ? $res->row_array() : NULL;

    }

    public function set_wiz_point($point)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->insert('wiz_point', $point);
        $insert_id = $this->db_master()->insert_id();

        $sql = "SELECT SUM(point) as wm_point FROM wiz_point wm WHERE wm.uid = ? AND wm.showYn = 'y'";
        $tmp = $this->db_master()->query($sql, array($point['uid']));       
        $wiz_member = $tmp->row_array();
    
        $this->db_master()->set('point', $wiz_member['wm_point']);
        $this->db_master()->where('uid', $point['uid']);
        $this->db_master()->update('wiz_member');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id ;
    }



    public function update_wiz_point($update_param,$where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->update('wiz_point', $update_param, $where);
        
        $sql = "SELECT SUM(point) as wm_point FROM wiz_point wm WHERE wm.uid = ? AND wm.showYn = 'y'";
        $tmp = $this->db_master()->query($sql, array($where['uid']));       
        $wiz_member = $tmp->row_array();
        
        $this->db_master()->set('point', $wiz_member['wm_point']);
        $this->db_master()->where('uid', $where['uid']);
        $this->db_master()->update('wiz_member');
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    public function delete_wiz_point($where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->delete('wiz_point', $where);
        
        $sql = "SELECT SUM(point) as wm_point FROM wiz_point wm WHERE wm.uid = ? AND wm.showYn = 'y'";
        $tmp = $this->db_master()->query($sql, array($where['uid']));       
        $wiz_member = $tmp->row_array();
    
        $this->db_master()->set('point', $wiz_member['wm_point']);
        $this->db_master()->where('uid', $where['uid']);
        $this->db_master()->update('wiz_member');
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function check_current_point($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT SUM(point) as wm_point FROM wiz_point wm WHERE wm.uid = ? AND wm.showYn = 'y'";
        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_point_comment_limit_one_by_table_code_kind($wm_uid, $table_code, $kind)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wp.pt_id as wp_pt_id 
                FROM wiz_point wp
                WHERE wp.uid = ? AND wp.table_code = ? AND wp.kind = ? AND wp.showYn = 'y'";
        $res = $this->db_slave()->query($sql, array($wm_uid, $table_code, $kind));


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_point_comment_limit_day_by_table_code_kind($wm_uid, $table_code, $kind, $s_date, $e_date)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wp.pt_id as wp_pt_id  
                FROM wiz_point wp
                WHERE wp.uid = ? AND wp.table_code = ? AND wp.kind = ? AND (wp.regdate >= ? AND wp.regdate <= ?) AND wp.showYn = 'y'";
        $res = $this->db_slave()->query($sql, array($wm_uid, $table_code, $kind, $s_date, $e_date));


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function count_point_comment_limit_day_by_kind_table_code($wm_uid, $kind, $table_code, $s_date, $e_date)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(wp.pt_id) as cnt  
                FROM wiz_point wp
                WHERE wp.uid = ? AND wp.kind = ? AND wp.table_code = ? AND (wp.regdate >= ? AND wp.regdate <= ?) AND wp.showYn = 'y'";
        $res = $this->db_slave()->query($sql, array($wm_uid, $kind, $table_code, $s_date, $e_date));

        // echo $this->db_slave()->last_query();
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function count_point_comment_limit_day_by_kind_except_1127($wm_uid, $kind, $s_date, $e_date)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(wp.pt_id) as cnt  
                FROM wiz_point wp
                WHERE wp.uid = ? AND wp.kind = ? AND wp.table_code != '1127' AND (wp.regdate >= ? AND wp.regdate <= ?) AND wp.showYn = 'y'";
        $res = $this->db_slave()->query($sql, array($wm_uid, $kind, $s_date, $e_date));


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function count_point_comment_limit_day_by_kind($wm_uid, $kind, $s_date, $e_date)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(wp.pt_id) as cnt  
                FROM wiz_point wp
                WHERE wp.uid = ? AND wp.kind = ? AND (wp.regdate >= ? AND wp.regdate <= ?) AND wp.showYn = 'y'";
        $res = $this->db_slave()->query($sql, array($wm_uid, $kind, $s_date, $e_date));


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_point_comment_by_mb_unq($mb_unq, $wm_wiz_id, $kind,$uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mbc.co_unq as mbc_co_unq 
                FROM mint_boards_comment mbc 
                INNER JOIN wiz_point wp ON (mbc.co_unq = wp.co_unq AND wp.uid = ?)
                WHERE mbc.mb_unq = ? AND mbc.writer_id = ? AND wp.kind = ? AND wp.showYn = 'y'";
        $res = $this->db_slave()->query($sql, array($uid,$mb_unq, $wm_wiz_id, $kind));


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_point_today_comment_by_table_code($table_code, $wm_wiz_id, $uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mbc.co_unq as mbc_co_unq 
                FROM mint_boards_comment mbc 
                INNER JOIN wiz_point wp ON (mbc.co_unq = wp.co_unq AND wp.uid = ?)
                WHERE mbc.table_code = ? AND mbc.writer_id = ? AND wp.showYn = 'y' AND wp.regdate > '".date('Y-m-d 00:00:00')."'";
        $res = $this->db_slave()->query($sql, array($uid, $table_code, $wm_wiz_id));


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function checked_point_by_co_unq($uid, $co_unq)
    {
        $this->db_connect('slave');
        
        $sql = " SELECT sum(point) as point FROM wiz_point 
                WHERE uid = ? AND showYn = 'y' AND kind = 't' AND co_unq in ? ";
        $res = $this->db_slave()->query($sql, array($uid, $co_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /* 
        회원 현재 포인트 현황
        - totla_point : 총 누적 포인트 
        - class_conversion_point : 총 수업 변환 포인트
        - use_point : 총 사용 포인트
        - current_point : 현재 보유 포인트 
    */
    public function point_current_situation_by_wm_uid($wm_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT 
                    (current_point - use_point) as total_point, current_point, use_point, class_conversion_point
                FROM (
                    SELECT 
                        (SELECT IF(sum(point) IS NULL, 0, sum(point)) FROM wiz_point WHERE uid = ? AND showYn = 'y') as current_point,
                        (SELECT IF(sum(point) IS NULL, 0, sum(point)) FROM wiz_point WHERE uid = ? AND showYn = 'y' AND point <= 0) as use_point,
                        (SELECT IF(sum(point) IS NULL, 0, sum(point)) FROM wiz_point WHERE uid = ? AND showYn = 'y' AND point <= 0 AND kind = 2) as class_conversion_point
                    FROM wiz_point wp 
                    WHERE 
                        wp.uid = ?
                    GROUP BY wp.uid
                ) as temp";

        $res = $this->db_slave()->query($sql, array($wm_uid, $wm_uid, $wm_uid, $wm_uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;

    }

    /* 회원 포인트 내역 */
    public function list_point_by_wm_uid($wm_uid, $order, $limit)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT wp.* 
                FROM wiz_point wp 
                WHERE wp.uid = ? AND showYn != 'n' 
                %s %s", $order, $limit);

        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

	/* 수업에 설정된 쿠폰 정보 */
    public function row_class_coupon_by_cl_id($cl_id)
    {
        $now = date('Y-m-d');
        
        $this->db_connect('slave');

        $sql = "SELECT wmc.*
                FROM wiz_class wc
                INNER JOIN wiz_mcoupon wmc ON wc.coupon_id = wmc.coupon_id
                WHERE wc.cl_id = ?";

        $res = $this->db_slave()->query($sql, array($cl_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_usage_point_by_uid($where, $order, $limit)
    {
        $this->db_connect('slave');


        $sql = sprintf("SELECT wp.pt_id AS wp_pt_id, wp.uid AS wp_uid, wp.name AS wp_name, wp.pt_name AS wp_pt_name, wp.point AS wp_point, wp.man_id AS wp_man_id,
                        wp.man_name AS wp_man_name, wp.kind AS wp_kind, wp.b_kind AS wp_b_kind, wp.co_unq AS wp_co_unq, wp.lesson_id AS wp_lesson_id, wp.regdate AS wp_regdate,
                        wp.del_manid AS wp_del_manid, wp.del_manname AS wp_del_manname, wp.del_regate AS wp_del_regate, wp.showYn AS wp_showYn, wp.table_code AS wp_table_code, wp.secret AS wp_secret
                        FROM wiz_point wp 
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 해당 유저의 총 누적, 사용 포인트 조회
     */
    public function total_usage_point_by_uid($uid)
    {
        $this->db_connect('slave');


        $sql = "SELECT SUM(IF(wp.point > 0, wp.point, NULL)) AS save, SUM(IF(wp.point < 0, wp.point, NULL)) AS spend
                FROM wiz_point AS wp
                WHERE wp.uid = ".$uid." AND wp.showYn = 'y' ";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_usage_point_search_category_by_uid($uid)
    {
        $this->db_connect('slave');


        $sql = sprintf("SELECT 
                        wp.kind AS wp_kind
                        FROM wiz_point wp 
                        WHERE uid = %s AND showYn != 'n'
                        GROUP BY wp.kind ORDER BY wp_kind ASC", $uid);

        $res = $this->db_slave()->query($sql);

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_count_wiz_point_by_uid($where)
    {
        $this->db_connect('slave');
                                                                                                                                                
        $sql = sprintf("SELECT count(1) AS cnt
                        FROM wiz_point wp 
                        %s", $where);


        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function check_point_received_by_lesson_id($lesson_id, $uid, $kind)
    {
        $this->db_connect('slave');
                                                                                                                                                
        $sql = "SELECT * FROM wiz_point WHERE uid = ? AND lesson_id = ? AND kind = ?";

        $res = $this->db_slave()->query($sql, array($uid, $lesson_id, $kind));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    
}










