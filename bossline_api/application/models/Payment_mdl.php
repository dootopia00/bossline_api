<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Payment_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function list_count_lesson_pay($index='', $where)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) AS cnt
                FROM wiz_pay AS wp
                LEFT JOIN wiz_lesson AS wl ON wl.lesson_id=wp.lesson_id ".$where ;
                
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function list_lesson_pay($index, $where, $order, $limit , $select_col_content = " ")
    {
        
        $this->db_connect('slave');

        $sql = sprintf("SELECT wp.pay_id as wp_pay_id, wl.cl_name as wl_cl_name, wp.pay_tt as wp_pay_tt, wp.pay_ok as wp_pay_ok, wp.refund_ok as wp_refund_ok,
                        wl.payment as wl_payment, wl.lesson_state as wl_lesson_state, wl.schedule_ok as wl_schedule_ok, wl.lesson_id as wl_lesson_id,
                        wp.pay_regdate as wp_pay_regdate, wl.lesson_gubun as wl_lesson_gubun %s
                        FROM wiz_pay AS wp %s
                        LEFT JOIN wiz_lesson AS wl ON wl.lesson_id=wp.lesson_id
                    %s %s %s", $select_col_content , $index, $where, $order, $limit);

        $res = $this->db_slave()->query($sql);  
        //echo $this->db_slave()->last_query();

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    public function row_lesson_pay_info($lesson_id, $uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wp.pay_id as wp_pay_id, wl.cl_name as wl_cl_name, wp.pay_tt as wp_pay_tt, wp.pay_ok as wp_pay_ok, wp.refund_ok as wp_refund_ok, wp.order_no as wp_order_no,
                        wl.payment as wl_payment, wl.lesson_state as wl_lesson_state, wl.schedule_ok as wl_schedule_ok, wp.pay_regdate as wp_pay_regdate,
                        wp.receive_mobile as wp_receive_mobile, wp.bank_number as wp_bank_number, wp.receive_date as wp_receive_date, wp.org_price as wp_org_price,
                        wp.discount_price as wp_discount_price, wl.name as wl_name, wl.mobile as wl_mobile, wl.cl_class as wl_cl_class, wl.cl_time as wl_cl_time,
                        wl.startday as wl_startday, wl.endday as wl_endday, wl.lesson_gubun as wl_lesson_gubun, wl.cl_gubun as wl_cl_gubun, wp.ipdate as wp_ipdate,
                        wl.cl_number as wl_cl_number
                FROM wiz_pay AS wp 
                LEFT JOIN wiz_lesson AS wl ON wl.lesson_id=wp.lesson_id
                WHERE wp.lesson_id = ? AND wl.uid = ?";

        $res = $this->db_slave()->query($sql, array($lesson_id, $uid));  

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    
    public function list_lesson_receipt($lesson_id, $uid, $where)
    {
        $this->db_connect('slave');

        $sql = "SELECT wlr.receipt_idx as split_number, wp.order_no as wp_order_no, wlr.lesson_name as wl_cl_name, wlr.startdate as wl_startday, wlr.enddate as wl_endday,
                    wlr.price as wl_fee, wlr.user_name as wl_name, wl.cl_gubun as wl_cl_gubun, wlr.issuedate as wlr_issuedate
                FROM wiz_lesson_receipt AS wlr
                LEFT JOIN wiz_lesson AS wl ON wl.lesson_id=wlr.lesson_id
                LEFT JOIN wiz_pay AS wp ON wp.lesson_id=wlr.lesson_id
                WHERE wlr.lesson_id = ? AND wl.uid = ? ".$where;

        $res = $this->db_slave()->query($sql, array($lesson_id, $uid));  
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    public function list_lesson_attendance($lesson_id, $uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wla.attendance_idx as split_number, wla.lesson_name as wl_cl_name, 
                    wla.startdate as wl_startday, wla.enddate as wl_endday, wla.user_name as wl_name, wla.starttime as wla_starttime, wla.endtime as wla_endtime,
                    wla.tu_name as wl_tu_name, wla.lesson_total_count as lesson_total, wla.lesson_pass_count as lesson_off, wla.lesson_rest_count as lesson_rest,
                    wla.present_persent as att_rate, wla.present_count as wla_present_count, wla.absent_count as wla_absent_count, wla.add_count as wl_tt_add, wl.cl_gubun as wl_cl_gubun,
                    wla.cancel_count as wl_tt_4, wla.holiday_count as wl_tt_5, wla.postpone_count as wl_tt_6, wla.long_postpone_count as wl_tt_7, wla.issuedate as wla_issuedate
                FROM wiz_lesson_attendance AS wla
                LEFT JOIN wiz_lesson AS wl ON wl.lesson_id=wla.lesson_id
                WHERE wla.lesson_id = ? AND wl.uid = ? ";

        $res = $this->db_slave()->query($sql, array($lesson_id, $uid));  
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function check_paid_history_for_first_pay($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT COUNT(1) as cnt 
                FROM wiz_lesson wl 
                JOIN wiz_pay wp ON wl.lesson_id=wp.lesson_id AND wp.pay_ok='Y' AND wp.pay_tt >=1000 
                WHERE wl.uid= ? and wl.refund_ok != 'Y' AND (wl.e_id=0 OR wl.e_id IS NULL) AND wl.cl_name NOT LIKE '%첨삭%'";

        $res = $this->db_slave()->query($sql, array($uid));  
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function update_sms_promotion_log($param,$where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->update('mint_sms_promotion_log', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }
    
    //수강신청 관련 페이지 들어올때마다 상시 체크되는..쿼리..
    public function check_sms_promotion_valid_cate11($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT sp_list.* FROM mint_sms_promotion_list as sp_list 
                JOIN mint_sms_promotion_log as sp_log ON sp_list.sp_list_id=sp_log.sp_list_id
                WHERE sp_list.sp_category_id=11 AND sp_list.enddate > NOW() AND sp_log.uid= ? AND sp_log.pay_id IS NULL";

        $res = $this->db_slave()->query($sql, array($uid));  
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    //SMS코드를 가지고 진입하면 이 쿼리로 해당코드의 정보가 유효한지 체크한다
    public function check_sms_promotion_valid($uid, $sp_list_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT sp_list.* FROM mint_sms_promotion_list as sp_list 
                JOIN mint_sms_promotion_log as sp_log ON sp_list.sp_list_id=sp_log.sp_list_id
                WHERE sp_list.sp_list_id= ? AND sp_list.enddate > NOW() AND sp_list.state > 1 AND sp_log.uid= ? AND sp_log.pay_id IS NULL";

        $res = $this->db_slave()->query($sql, array($sp_list_id, $uid));  
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function insert_mint_pay_visit_log($uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT lesson_id FROM wiz_lesson WHERE uid = ?";
        $res = $this->db_master()->query($sql, array($uid));  
        $lesson_yn = $res->num_rows() > 0 ? 'Y' : 'N';

        $sql = "SELECT count(mpvl.idx) AS search_cnt, mpvl.count, mpvl.uid FROM mint_pay_visit_log mpvl WHERE mpvl.uid = ?";
        $res = $this->db_master()->query($sql, array($uid));  
        $row = $res->num_rows() > 0 ? $res->row_array() : NULL;

        $date = date("Y-m-d H:i:s");
        $param = [
            'count' => $row ? ($row['count']+1):1,
            'lesson_yn' => $lesson_yn,
            'update_date' => $date,
        ];

        if($row)
        {
            $this->db_master()->where('uid',$uid);
            $this->db_master()->update('mint_pay_visit_log', $param);
        }
        else
        {
            $param['uid'] = $uid;
            $param['regdate'] = $date;
            $this->db_master()->insert('mint_pay_visit_log', $param);
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    
    public function insert_wiz_prepay($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_prepay', $param);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return $insert_id;
    }

    
    public function update_wiz_prepay_lesson_id($prepay_id, $lesson_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('prepay_id', $prepay_id);
        $this->db_master()->set('lesson_id', $lesson_id);
        $this->db_master()->update('wiz_prepay');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }
    
    public function row_prepay_by_prepay_id($prepay_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM wiz_prepay WHERE prepay_id = ?";

        $res = $this->db_slave()->query($sql, array($prepay_id));  
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function row_prepay_by_order_no($order_no)
    {
        $this->db_connect('slave');

        $sql = "SELECT wpp.*, wp.order_no as tno, wp.pay_ok, wp.phon_mny, wp.pay_id FROM wiz_prepay as wpp 
                JOIN wiz_pay as wp ON wpp.lesson_id=wp.lesson_id 
                WHERE wpp.order_no = ?";

        $res = $this->db_slave()->query($sql, array($order_no));  
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function insert_wiz_pg_notification($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_pg_notification', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    
    public function insert_wiz_pay($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_pay', $param);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return $insert_id;
    }

    
    public function delete_wiz_pay($pay_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('pay_id', $pay_id);
        $this->db_master()->delete('wiz_pay');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }
    
    public function update_wiz_pay($pay_id, $param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('pay_id',$pay_id);
        $this->db_master()->update('wiz_pay', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }
    

}










