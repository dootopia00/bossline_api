<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Lesson_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    
    public function checked_holiday($where)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT whc.holiday as whc_holiday, whc.title as whc_title
                        FROM wiz_holiday_control whc 
                        %s", $where);
        $res = $this->db_slave()->query($sql);       
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    // step 1의 f_id로 하위 교재 step2 찾는 함수. 커리큘럼 테이블 생성되도 유지.
    public function checked_class_by_f_id($wm_uid, $f_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wl.lesson_id 
                FROM wiz_book wb
                INNER JOIN wiz_lesson wl ON wb.book_id = wl.book_id
                WHERE 
                    wl.uid = ? AND wb.f_id = ?  AND wl.tu_name != 'postpone' AND wl.endday > CURRENT_DATE()";
        
        $res = $this->db_slave()->query($sql, array($wm_uid, $f_id));       

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_nextclass_by_wm_uid($wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT s.startday FROM wiz_schedule AS s 
                INNER JOIN wiz_lesson AS l USE INDEX(endday) ON (s.lesson_id=l.lesson_id)
                WHERE
                (l.uid = ".$wm_uid." OR l.student_uid LIKE '%".$wm_uid."%')
                AND l.schedule_ok='Y'
                AND l.lesson_list_view='Y'
                AND l.lesson_state = 'in class'
                AND l.endday >= CURRENT_DATE()
                AND l.tu_uid NOT IN (153,158)
                AND s.present=1
                AND s.startday >=NOW()
                ORDER BY s.startday LIMIT 1";
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    

    
    public function lesson_list_by_wm_uid($uid)
    {
        $this->db_connect('slave');

        //속도개선을 위해 uid OR student_uid 이 하나의 쿼리였던걸 두개로 분리함
        $result = [];
        $sql = "SELECT * FROM wiz_lesson
                WHERE
                uid = ".$uid."
                AND lesson_list_view='Y' ORDER BY lesson_id DESC";
        $res1 = $this->db_slave()->query($sql);

        $result = $res1->num_rows() > 0 ? $res1->result_array() : [];

        $sql = "SELECT * FROM wiz_lesson
                WHERE
                student_uid LIKE '%,".$uid.",%'
                AND lesson_list_view='Y' ORDER BY lesson_id DESC";
        $res2 = $this->db_slave()->query($sql);

        $result = $res2->num_rows() > 0 ? array_merge($result,$res2->result_array()) : $result;

        return !empty($result) ? $result : NULL;
    }
    
    
    public function check_in_class_member($uid,$where)
    {
        $this->db_connect('slave');

        $sql = "SELECT lesson_id FROM wiz_lesson
                WHERE (uid = '".$uid."' OR student_uid LIKE '%,".$uid.",%') ".$where;
        $res = $this->db_slave()->query($sql);

        // echo $this->db_slave()->last_query();   
        // exit;
        
        return $res->num_rows() > 0 ? $res->num_rows() : NULL;
    }
    
    
    public function check_ahop_lesson_by_uid($uid)
    {
        $this->db_connect('slave');

        $sql = " SELECT count(*) as cnt FROM wiz_lesson WHERE uid = ? and book_name like 'AHOP%' and schedule_ok = 'Y' ";

        $res = $this->db_slave()->query($sql,array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    

    public function class_list($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wl.lesson_id as wl_lesson_id, wl.cl_name as wl_cl_name, wl.cl_name2 as wl_cl_name2, wl.cl_label as wl_cl_label, wl.startday as wl_startday, 
                wl.endday as wl_endday,wl.lesson_state as wl_lesson_state, wl.cl_gubun as wl_cl_gubun, wl.tu_uid as wl_tu_uid, wl.lesson_gubun as wl_lesson_gubun,
                wl.disabled_extend as wl_disabled_extend, wl.student_su as wl_student_su, wl.pay_sum as wl_pay_sum, wl.e_id as wl_e_id, wl.cl_id as wl_cl_id,
                wl.cl_month as wl_cl_month, wl.cl_time as wl_cl_time, wl.cl_number as wl_cl_number, wl.newlesson_ok AS wl_newlesson_ok
                FROM wiz_lesson as wl
                WHERE (wl.uid = '".$uid."' || wl.student_uid LIKE '%,".$uid.",%')
                AND wl.schedule_ok = 'Y'
                AND wl.lesson_list_view = 'Y'
                AND wl.endday >= CURRENT_DATE() 
                AND wl.refund_ok!='Y' ";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function row_schedule_by_sc_id($sc_id, $uid)
    {
        $this->db_connect('slave');

        $sql = " SELECT ws.sc_id, ws.lesson_id, ws.uid, ws.wiz_id, ws.tu_uid, ws.tu_name, ws.present, ws.startday, ws.endday, ws.cl_time, wl.cl_gubun as wl_cl_gubun, ws.name,
        ws.mobile, ws.weekend,
        ws.lesson_gubun, wt.tu_name, wl.book_id as wl_book_id, wl.book_name as wl_book_name, wl.lesson_state as wl_lesson_state, wl.startday as wl_startday, wl.endday as wl_endday,
        wl.cl_number as wl_cl_number, wl.cl_class as wl_cl_class, wl.tt_add as wl_tt_add, ws.kind as ws_kind, wl.lesson_id as wl_lesson_id
        FROM wiz_schedule ws 
        INNER JOIN wiz_tutor wt ON ws.tu_uid = wt.tu_uid
        INNER JOIN wiz_lesson wl ON ws.lesson_id = wl.lesson_id
        WHERE ws.sc_id = ? AND ws.uid = ?";

        $res = $this->db_slave()->query($sql, array($sc_id, $uid));
        
        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function row_schedule_by_sc_id_and_tu_uid($sc_id, $tu_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.sc_id, ws.lesson_id, ws.uid, ws.wiz_id, ws.tu_uid, ws.tu_name, ws.present, ws.startday, ws.endday, ws.cl_time, ws.ab_ok,
                       ws.mobile as ws_mobile, ws.name as ws_name, wl.mobile as wl_mobile, wl.cl_gubun as wl_cl_gubun,
                       ws.lesson_gubun, wl.book_id as wl_book_id, wl.book_name as wl_book_name, wl.lesson_state as wl_lesson_state,
                       wl.startday as wl_startday, wl.endday as wl_endday
                FROM wiz_schedule ws 
                INNER JOIN wiz_lesson wl ON ws.lesson_id = wl.lesson_id
                WHERE ws.sc_id = ? AND ws.tu_uid = ?";

        $res = $this->db_slave()->query($sql, array($sc_id, $tu_uid));
        
        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function row_wiz_schedule_by_sc_id($sc_id)
    {
        $this->db_connect('slave');

        $sql = " SELECT ws.sc_id, ws.lesson_id, ws.uid, ws.wiz_id, ws.tu_uid, ws.tu_name, ws.present, ws.startday, ws.endday, ws.cl_time, 
                        ws.mobile as ws_mobile, ws.name as ws_name, ws.lesson_gubun as ws_lesson_gubun, ws.kind as ws_kind
                FROM wiz_schedule ws 
                WHERE ws.sc_id = ?";

        $res = $this->db_slave()->query($sql, array($sc_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_schedule_by_lesson_id_sc_id($lesson_id, $sc_id)
    {
        $this->db_connect('slave');
        
        // 프론트 작업시 필요한 컬럼만 세팅하자
        $sql = " SELECT ws.*
        FROM wiz_schedule ws 
        WHERE ws.lesson_id = ? AND ws.sc_id = ?";

        $res = $this->db_slave()->query($sql, array($lesson_id, $sc_id));
        
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_schedule_by_lesson_id($lesson_id)
    {
        $this->db_connect('slave');
        
        // 프론트 작업시 필요한 컬럼만 세팅하자
        $sql = " SELECT ws.*
        FROM wiz_schedule ws 
        WHERE ws.lesson_id = ?";

        $res = $this->db_slave()->query($sql, array($lesson_id));
        
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_count_in_class($where)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(wl.lesson_id) AS cnt
        FROM wiz_lesson wl
        JOIN wiz_book wb ON wl.book_id = wb.book_id ".$where;

        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    
    public function list_count_lesson($where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT count(1) AS cnt
                FROM wiz_lesson wl
                %s", $where);


        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function lesson_count_by_lesson_id($lesson_id)
    {
        $this->db_connect('slave');

        $sql = " SELECT count(1) AS cnt
        FROM wiz_lesson wl
        WHERE wl.lesson_id = ? ";

        $res = $this->db_slave()->query($sql, array($lesson_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_wiz_lesson_by_lesson_id($lesson_id)
    {
        $this->db_connect('slave');

        $sql = " SELECT wl.*
        FROM wiz_lesson wl
        WHERE wl.lesson_id = ? ";

        $res = $this->db_slave()->query($sql, array($lesson_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function schedule_count_by_lesson_id($lesson_id)
    {
        $this->db_connect('slave');

        $sql = " SELECT count(1) AS cnt
        FROM wiz_schedule ws
        WHERE ws.lesson_id = ? ";

        $res = $this->db_slave()->query($sql, array($lesson_id));

        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_lesson($where, $order, $limit, $select_col_content="")
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT wl.lesson_id AS wl_lesson_id, wl.order_gubun AS wl_order_gubun, wl.newlesson_ok AS wl_newlesson_ok, wl.parent_id AS wl_parent_id,
                wl.uid AS wl_uid, wl.wiz_id AS wl_wiz_id, wl.name AS wl_name, wl.ename AS wl_ename, wl.tel AS wl_tel, wl.mobile AS wl_mobile, wl.tu_uid AS wl_tu_uid,
                wl.tu_name AS wl_tu_name, wl.co_uid AS wl_co_uid, wl.co_company AS wl_co_company, wl.ji_uid AS wl_ji_uid, wl.ji_company AS wl_ji_uid, wl.ji_company AS wl_ji_company,
                wl.man_id AS wl_man_id, wl.man_name AS wl_man_name, wl.lev_id AS wl_lev_id, wl.lev_gubun AS wl_lev_gubun, wl.lev_name AS wl_lev_name, wl.book_id AS wl_book_id,
                wl.book_name AS wl_book_name, wl.cl_id AS wl_cl_id, wl.cl_name AS wl_cl_name, wl.cl_name AS wl_cl_name2, wl.cl_label AS wl_cl_label, wl.cl_gubun AS wl_cl_gubun,
                wl.cl_lang AS wl_cl_lang, wl.cl_time AS wl_cl_time, wl.cl_number AS wl_cl_number, wl.origin_cl_class AS wl_origin_class, wl.cl_class AS wl_cl_class, wl.cl_service AS wl_cl_service,
                wl.cl_month AS wl_cl_month, wl.hold_num AS wl_hold_num, wl.paper_ok AS wl_paper_ok, wl.weekend AS wl_weekend, wl.fee AS wl_fee, wl.lesson_gubun AS wl_lesson_gubun, wl.hopedate AS wl_hopedate,
                wl.hopetime AS wl_hopetime, wl.schedule_ok AS wl_schedule_ok, wl.pay_ok AS wl_pay_ok, wl.refund_ok AS wl_refund_ok, wl.payment AS wl_payment, wl.pay_sum AS wl_pay_sum,
                wl.refund_sum AS wl_refund_sum, wl.tt AS wl_tt, wl.tt_1 AS wl_tt_1, wl.tt_2 AS wl_tt_2, wl.tt_3 AS wl_tt_3, wl.tt_3_1 AS wl_tt_3_1, wl.tt_4 AS wl_tt_4, wl.tt_5 AS wl_tt_5, wl.tt_6 AS wl_tt_6, 
                wl.tt_7 AS wl_tt_7, wl.tt_8 AS wl_tt_8, wl.tt_9 AS wl_tt_9, wl.tt_add AS wl_tt_add, wl.tt_holding_count AS wl_tt_holding_count, wl.tt_point_use AS wl_tt_point_use, 
                wl.startday AS wl_startday, wl.endday AS wl_endday, wl.stime AS wl_stime, wl.time_start AS wl_time_start, wl.time_end AS wl_time_end, wl.daytime_ok AS wl_daytime_ok, wl.conti AS wl_conti,
                wl.lesson_memo AS wl_lesson_memo, wl.report_num AS wl_report_num, wl.recall_ok AS wl_recall_ok, wl.consult_ok AS wl_consult_ok, wl.regdate AS wl_regdate,
                wl.plandate AS wl_plandate, wl.relec_id AS wl_relec_id, wl.before_id AS wl_before_id, wl.skype AS wl_skype, wl.lesson_bi_yn AS wl_lesson_bi_yn, wl.lesson_state AS wl_lesson_state,
                wl.stime2 AS wl_stime2, wl.lesson_tcode AS wl_lesson_tcode, wl.lesson_list_view AS wl_lesson_list_view, wl.report_app AS wl_report_app, wl.cons_hope_time AS wl_cons_hope_time,
                wl.cons_hope_time2 AS wl_cons_hope_time2, wl.renewal_ok AS wl_renewal_ok, wl.renewal_reason AS wl_renewal_reason, wl.dealer_pay_ok AS wl_dealer_pay_ok, wl.student_su AS wl_student_su,
                wl.student_uid AS wl_student_uid, wl.e_id AS wl_e_id, wl.invoice AS wl_invoice, wl.disabled_extend AS wl_disabled_extend, wl.lesson_number AS wl_lesson_number, wl.lesson_count AS wl_lesson_count, 
                wl.lesson_total AS wl_lesson_total, wl.lesson_event AS wl_lesson_event, wl.lesson_refund AS wl_lesson_refund, wl.lesson_correction AS wl_lesson_correction, wl.lesson_coupon AS wl_lesson_coupon,
                wl.limit_startday AS wl_limit_startday, wl.release_cnt AS wl_release_cnt, wl.add_class_cnt AS wl_add_class_cnt 
                %s
                FROM wiz_lesson wl
                %s %s %s",  $select_col_content, $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();   exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_count_report($where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT count(1) AS cnt
                FROM wiz_report wr
                %s", $where);


        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_report($where, $order, $limit)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT wr.re_id AS wr_re_id, wr.uid AS wr_uid, wr.wiz_id AS wr_wiz_id, wr.name AS wr_name, wr.ename AS wr_ename, wr.tu_uid AS wr_tu_uid,
                        wr.tu_name AS wr_tu_name, wr.lesson_id AS wr_lesson_id, wr.report_num AS wr_report_num, wr.re_start AS wr_re_start, wr.re_end AS wr_re_end,
                        wr.re_time AS wr_re_time, wr.tt_2 AS wr_tt_2, wr.tt_3 AS wr_tt_3, wr.tt_4 AS wr_tt_4, wr.tt_5 AS wr_tt_5, wr.tt_6 AS wr_tt_6, wr.tt_7 AS wr_tt_7, wr.tt_8 AS wr_tt_8,
                        wr.listening AS wr_listening, wr.speaking AS wr_speaking, wr.pronunciation AS wr_pronunciation, wr.vocabulary AS wr_vocabulary, wr.grammar AS wr_grammar,
                        wr.ev_memo AS wr_ev_memo, wr.gra_memo AS wr_gra_memo, wr.regdate AS wr_regdate, wr.modifydate AS wr_modifydate
                FROM wiz_report wr
                %s %s %s",  $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    public function row_report_by_re_id($re_id)
    {
        $this->db_connect('slave');

        $sql = " SELECT wr.re_id AS wr_re_id, wr.uid AS wr_uid, wr.wiz_id AS wr_wiz_id, wr.name AS wr_name, wr.ename AS wr_ename, wr.tu_uid AS wr_tu_uid,
                        wr.tu_name AS wr_tu_name, wr.lesson_id AS wr_lesson_id, wr.report_num AS wr_report_num, wr.re_start AS wr_re_start, wr.re_end AS wr_re_end,
                        wr.re_time AS wr_re_time, wr.tt_2 AS wr_tt_2, wr.tt_3 AS wr_tt_3, wr.tt_4 AS wr_tt_4, wr.tt_5 AS wr_tt_5, wr.tt_6 AS wr_tt_6, wr.tt_7 AS wr_tt_7, wr.tt_8 AS wr_tt_8,
                        wr.listening AS wr_listening, wr.speaking AS wr_speaking, wr.pronunciation AS wr_pronunciation, wr.vocabulary AS wr_vocabulary, wr.grammar AS wr_grammar,
                        wr.ev_memo AS wr_ev_memo, wr.gra_memo AS wr_gra_memo, wr.regdate AS wr_regdate, wr.modifydate AS wr_modifydate
                FROM wiz_report wr
                WHERE wr.re_id = ?";

        $res = $this->db_slave()->query($sql, array($re_id));

        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function checked_tutor_star_wiz_lesson_wiz_leveltest($uid, $tu_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.tu_uid FROM wiz_schedule ws WHERE uid = '".$uid."' AND tu_uid = '".$tu_uid."'
                UNION ALL
                SELECT wlt.tu_uid FROM wiz_leveltest wlt WHERE uid = '".$uid."' AND tu_uid = '".$tu_uid."'";

        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function checked_prev_class_thunder_tutor($date, $thunder_tutor_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.sc_id, ws.uid FROM wiz_schedule ws
                WHERE tu_uid = ? AND present='1' AND startday BETWEEN '".$date." 00:00:00' AND '".$date." 23:59:59'";

        $res = $this->db_slave()->query($sql,array($thunder_tutor_uid));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    public function update_wiz_long_schedule($delYn, $uid, $lesson_id, $stime, $etime)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('delYn', $delYn);

        $this->db_master()->where('uid', $uid);
        $this->db_master()->where('lesson_id', $lesson_id);
        $this->db_master()->where('startTime >', $stime);
        $this->db_master()->where('startTime <', $etime);

        $this->db_master()->update('wiz_long_schedule');
        //echo $this->db_master()->last_query();   
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    public function insert_wiz_schedule($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_schedule', $param);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return $insert_id;
    }
    
    public function update_wiz_schedule($sc_id,$param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('sc_id', $sc_id);
        $this->db_master()->update('wiz_schedule', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    public function delete_wiz_schedule($sc_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('sc_id', $sc_id);
        $this->db_master()->delete('wiz_schedule');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    
    public function delete_wiz_lesson($lesson_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('lesson_id', $lesson_id);
        $this->db_master()->delete('wiz_lesson');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }
    
    
    public function row_lesson_feedback_by_sc_id($sc_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.sc_id as ws_sc_id, ws.present as ws_present,
                wsr.rating_ls as wsr_rating_ls, wsr.rating_ss as wsr_rating_ss, wsr.rating_pro as wsr_rating_pro, wsr.rating_voc as wsr_rating_voc, wsr.rating_cg as wsr_rating_cg,
                wsr.pronunciation as wsr_pronunciation, wsr.grammar as wsr_grammar, wsr.comment as wsr_comment, wsr.tutor_memo as wsr_tutor_memo, wsr.book_start as wsr_book_start,
                wsr.book_end as wsr_book_end, wsr.topic_previous as wsr_topic_previous, wsr.topic_today as wsr_topic_today, wsr.topic_next as wsr_topic_next, 
                wsr.topic_date as wsr_topic_date, wsr.absent_reason as wsr_absent_reason
                FROM wiz_schedule ws
                JOIN wiz_schedule_result wsr ON wsr.sc_id = ws.sc_id WHERE ws.sc_id = ?";

        $res = $this->db_slave()->query($sql, array($sc_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function insert_wiz_schedule_result($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_schedule_result', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }


    public function update_wiz_schedule_result($sc_id, $param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('sc_id', $sc_id);
        $this->db_master()->update('wiz_schedule_result', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    
    public function update_wiz_lesson($lesson_id, $param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('lesson_id', $lesson_id);
        $this->db_master()->update('wiz_lesson', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    public function checked_tt_by_lesson_id($lesson_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT sum(if(present=1,1,0)) as tt1,
                       sum(if(present=2,1,0)) as tt2,
                       sum(if(present=3,1,0)) as tt3,
                       sum(if(present=4,1,0)) as tt4,
                       sum(if(present=5,1,0)) as tt5,
                       sum(if(present=6,1,0)) as tt6,
                       sum(if(present=7,1,0)) as tt7,
                       sum(if(present=8,1,0)) as tt8
                FROM wiz_schedule WHERE lesson_id = ?";

        $res = $this->db_slave()->query($sql,array($lesson_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_tt_by_where($where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT sum(if(present=1,1,0)) as tt1,
                                sum(if(present=2,1,0)) as tt2,
                                sum(if(present=3,1,0)) as tt3,
                                sum(if(present=4,1,0)) as tt4,
                                sum(if(present=5,1,0)) as tt5,
                                sum(if(present=6,1,0)) as tt6,
                                sum(if(present=7,1,0)) as tt7
                                -- sum(if(present=8,1,0)) as tt8 
                        FROM wiz_schedule %s", $where);
                
        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    
    public function insert_wiz_tutor_change($param,$content)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_tutor_change', $param);
        $insert_id = $this->db_master()->insert_id();

        if($insert_id)
        {
            $this->db_master()->insert('wiz_tutor_change_content', [
                'tt_id' => $insert_id,
                'content' => $content,
            ]);
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }


    public function checked_count_prev_week_free_schedule($startdate, $enddate)
    {
        $this->db_connect('master');

        // count로하면 널값도 cnt에 1로 집계되기 때문에 0인것과 구분하기 위해 sum으로 집계
        $sql = "SELECT wl.lesson_id as wl_lesson_id, wl.uid as wl_uid, wl.wiz_id as wl_wiz_id, wl.name as wl_name, wl.cl_number as wl_cl_number, wl.cl_class as wl_cl_class, wl.tt_add as wl_tt_add, wl.mobile as wl_mobile,
                SUM(case when ws.sc_id IS NOT NULL AND ws.present IN (1,2,3,4) then 1 ELSE 0 END) as cnt
                FROM wiz_lesson as wl
                LEFT JOIN wiz_schedule as ws ON wl.lesson_id=ws.lesson_id AND ws.kind IN ('n','f','t') AND ws.startday BETWEEN '".$startdate."' AND '".$enddate."'
                WHERE wl.cl_gubun='2' AND wl.startday < '".date('Y-m-d')."' AND wl.lesson_state IN ('in class') GROUP BY wl.lesson_id HAVING wl.cl_number != cnt";

        $res = $this->db_master()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function checked_count_spend_free_schedule_this_period($lesson_id, $start_date, $end_date)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(*) as cnt FROM wiz_schedule as ws
                WHERE ws.lesson_id= ".$lesson_id." AND ws.present IN (1,2,3,4) AND ws.kind IN ('n','f','t') AND 
                ws.startday BETWEEN '".$start_date."' AND '".$end_date."'";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function checked_count_spend_schedule($lesson_id)
    {
        $this->db_connect('master');

        /* 
        cnt: 출석부의 총 소진 횟수. present 1:대기, 2:참석, 3:결석, 4:취소.
        ready_cnt: 소진 대기, 
        참석,결석,취소는 소진
        */

        $sql = "SELECT count(*) as cnt, sum(CASE WHEN present=1 THEN 1 ELSE 0 END) as ready_cnt 
                FROM wiz_schedule as ws
                WHERE ws.lesson_id = ? AND present IN (1,2,3,4)";

        $res = $this->db_master()->query($sql, array($lesson_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function checked_count_free_schedule_absent($lesson_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT sum(count) as cnt FROM free_schedule_absent WHERE lesson_id= ?";

        $res = $this->db_slave()->query($sql, array($lesson_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_paid_lesson($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(*) as cnt FROM wiz_lesson as wl WHERE wl.uid= ? AND wl.pay_ok='Y' AND wl.refund_ok='N'";

        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function checked_paid_lesson_dealer_id($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(*) as cnt FROM wiz_lesson as wl WHERE wl.uid= ? AND wl.refund_ok='N'";

        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function insert_free_schedule_absent($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('free_schedule_absent', $param);

        // tt_3_1: 자유수업 결석 횟수 
        $this->db_master()->set('tt_3_1', 'tt_3_1+'.$param['count'], false);
        $this->db_master()->where('lesson_id', $param['lesson_id']);

        $this->db_master()->update('wiz_lesson');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    
    public function list_lesson_by_tu_uid($tu_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wl.lesson_id as wl_lesson_id, wl.lesson_state as wl_lesson_state, wl.cl_number as wl_cl_number
                FROM wiz_lesson as wl WHERE wl.tu_uid= ? ";

        $res = $this->db_slave()->query($sql, array($tu_uid));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    
    public function row_last_class($lesson_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.startday as ws_startday
                FROM wiz_schedule as ws WHERE ws.lesson_id = ? AND present IN ( 1,2,3,4 ) ORDER BY startday DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($lesson_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function row_last_class_present_1_after_1day($lesson_id, $date)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.sc_id as ws_sc_id, ws.tu_uid as ws_tu_uid, ws.startday as ws_startday, ws.cl_time as ws_cl_time, ws.tu_name as ws_tu_name
                FROM wiz_schedule as ws WHERE ws.lesson_id = ? AND present IN ( 1 ) AND kind='n' AND startday > ? 
                ORDER BY startday DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($lesson_id, $date));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_first_class($lesson_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.startday as ws_startday
                FROM wiz_schedule as ws WHERE ws.lesson_id = ? AND present IN (1,2,3,4) ORDER BY startday ASC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($lesson_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function delete_free_tutor_schedule($lesson_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('lesson_id', $lesson_id);
        $this->db_master()->where('tu_uid', 1475);
        $this->db_master()->where('present', 1);
        $this->db_master()->delete('wiz_schedule');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }
    
    public function delete_postpone_schedule_by_lesson_id($lesson_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('lesson_id', $lesson_id);
        $this->db_master()->where('tu_uid', 158);
        $this->db_master()->where('present', 7);
        $this->db_master()->delete('wiz_schedule');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    /*
        회원 현재 수강 종료되지 않은 출석부 목록  
        - 수강 종료되지 않은 출석부
            : 영어첨삭 제외 (tu_uid 153)
    */
    public function list_unfinished_wiz_lesson_by_wm_uid($wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wl.*, wp.order_no as wp_order_no 
                FROM wiz_lesson wl 
                INNER JOIN wiz_pay wp ON wp.lesson_id = wl.lesson_id
                WHERE 
                    wl.uid = ?  AND wl.schedule_ok='Y' AND wl.lesson_list_view='Y' 
                    AND wl.endday >= DATE_FORMAT(NOW(),'%Y-%m-%d') AND wl.tu_uid NOT IN (153) 
                ORDER BY wl.lesson_id DESC";

        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    

    /*
        회원 현재 수강 종료되지 않은 출석부   
        - 수강 종료되지 않은 출석부
            : 영어첨삭 제외 (tu_uid 153)
        - 단일 출석부를 가져오지만 array로 리턴
         : 출석부 포인트 정책 point_policy_wiz_lesson() 헬퍼 공통 사용이 목적
    */
    public function list_unfinished_wiz_lesson_by_lesson_id($lesson_id, $wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wl.*, wp.order_no as wp_order_no 
                FROM wiz_lesson wl 
                INNER JOIN wiz_pay wp ON wp.lesson_id = wl.lesson_id
                WHERE 
                    wl.lesson_id = ? AND wl.uid = ?  AND wl.schedule_ok='Y' AND wl.lesson_list_view='Y' 
                    AND wl.endday >= DATE_FORMAT(NOW(),'%Y-%m-%d') AND wl.tu_uid NOT IN (153)";

        $res = $this->db_slave()->query($sql, array($lesson_id, $wm_uid));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /*
        회원 현재 수강 종료되지 않은 장기연기 확인용 출석부 리스트
    */
    public function postpone_list_unfinished_wiz_lesson_by_wm_uid($wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wl.lesson_id as wl_lesson_id, wl.uid as wl_uid, wl.wiz_id as wl_wiz_id, wl.cl_class as wl_cl_class,
                       wl.cl_gubun as wl_cl_gubun, wl.cl_number as wl_cl_number, wl.tt_add as wl_tt_add, wl.payment as wl_payment,
                       wl.e_id as wl_e_id, wl.cl_id AS wl_cl_id, wl.tu_uid as wl_tu_uid, wl.tu_name as wl_tu_name, wl.cl_name as wl_cl_name, wl.cl_name2 as wl_cl_name2,
                       wl.lesson_gubun as wl_lesson_gubun, wl.startday as wl_startday, wl.endday as wl_endday, wl.cl_label as wl_cl_label, wl.cl_time as wl_cl_time,
                       wl.stime as wl_stime, wl.tt as wl_tt, wl.tt_1 as wl_tt_1, wl.tt_2 as wl_tt_2, wl.tt_3 as wl_tt_3, wl.tt_3_1 as wl_tt_3_1,
                       wl.tt_4 as wl_tt_4, wl.tt_5 as wl_tt_5, wl.tt_6 as wl_tt_6, wl.tt_7 as wl_tt_7, wl.tt_8 as wl_tt_8, wl.tt_9 as wl_tt_9
                FROM wiz_lesson AS wl
                WHERE 
                    (wl.uid = '".$wm_uid."' OR wl.student_uid LIKE '%,".$wm_uid."%') AND wl.schedule_ok='Y' AND wl.lesson_list_view='Y' AND refund_ok!='Y'
                    AND (wl.endday >= DATE_FORMAT(NOW(),'%Y-%m-%d') OR (wl.tt_7 > 0 AND wl.tu_uid = '158'))";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 수업상태 별 수업 정보 가져오기
     */
    public function get_present_schedule_list($lesson_id, $date, $present)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT *
                FROM wiz_schedule
                WHERE present IN (".$present.") AND lesson_id='".$lesson_id."' AND startday >= '".$date."'
                ORDER BY startday DESC";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 휴강, 단기연기 스케쥴 갯수 가져오기
     */
    public function get_count_hyugang_dangi_schedule($lesson_id)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT COUNT(IF(present='5', 1, NULL)) AS hyugang,COUNT(IF(present='6', 1, NULL)) AS dangi
                FROM wiz_schedule
                WHERE lesson_id='".$lesson_id."' AND present IN ('5', '6')";
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 홀딩상태가 아닌 스케쥴 시작,마지막날짜 가져오기
     */
    public function get_not_hold_schedule_startday_endday($lesson_id)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT min(startday) as startday, max(startday) as endday
                FROM wiz_schedule
                WHERE lesson_id='".$lesson_id."' AND present IN (1,2,3,4,7)";
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 배정된 수업있으면 삭제
     */
    public function delete_assign_schedule($lesson_id, $date)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where("present IN ('1','5') AND lesson_id = '".$lesson_id."' AND startday >= '".$date."'");
        $this->db_master()->delete('wiz_schedule');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    /**
     * 설문조사 상세사유 정보 가져오기
     */
    public function get_postpone_survey_reason()
    {
        $this->db_connect('slave');

        $sql = "SELECT idx, reason
                FROM mint_postpone_survey_reason";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 설문조사 등록
     */
    public function insert_mint_postpone_survey($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_postpone_survey', $param);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return $insert_id;
    }

    public function get_wiz_long_schedule($uid, $lesson_id, $sdate, $edate)
    {
        $this->db_connect('slave');

        $sql = "SELECT idx,long_cnt,startTime,lesson_id
                FROM wiz_long_schedule
                WHERE uid = '".$uid."' AND lesson_id = '".$lesson_id."' AND startTime between ".$sdate." AND ".$edate." AND delYn = 'N'
                ORDER BY idx desc limit 1";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function insert_wiz_long_schedule($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_long_schedule', $param);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return $insert_id;
    }

    public function row_wiz_lesson_by_lesson_id($lesson_id, $wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wl.lesson_id as wl_lesson_id, wl.uid as wl_uid, wl.wiz_id as wl_wiz_id, wl.newlesson_ok AS wl_newlesson_ok, 
                    wl.name as wl_name, wl.ename as wl_ename, wl.tel AS wl_tel, wl.mobile as wl_mobile, wl.tu_uid as wl_tu_uid, wl.tu_name as wl_tu_name, 
                    wl.book_id as wl_book_id, wl.book_name as wl_book_name, wl.cl_name as wl_cl_name, wl.cl_name2 as wl_cl_name2, wl.cl_label as wl_cl_label, 
                    wl.cl_gubun as wl_cl_gubun, wl.cl_time as wl_cl_time, wl.cl_number as wl_cl_number, wl.cl_class as wl_cl_class, wl.cl_month as wl_cl_month, 
                    wl.hold_num as wl_hold_num, wl.weekend as wl_weekend, wl.fee as wl_fee, wl.lesson_gubun as wl_lesson_gubun, wl.hopedate as wl_hopedate,
                    wl.hopetime as wl_hopetime, wl.schedule_ok as wl_schedule_ok, wl.pay_ok as wl_pay_ok, wl.refund_ok as wl_refund_ok, wl.payment as wl_payment, 
                    wl.pay_sum as wl_pay_sum, wl.tt as wl_tt, wl.tt_1 as wl_tt_1, wl.tt_2 as wl_tt_2, wl.tt_3 as wl_tt_3, wl.tt_3_1 as wl_tt_3_1, 
                    wl.tt_4 as wl_tt_4, wl.tt_5 as wl_tt_5, wl.tt_6 as wl_tt_6, wl.tt_7 as wl_tt_7, wl.tt_8 as wl_tt_8, wl.tt_9 as wl_tt_9, wl.tt_add as wl_tt_add, 
                    wl.tt_holding_count as wl_tt_holding_count, wl.tt_point_use as wl_tt_point_use, wl.startday as wl_startday, wl.endday as wl_endday, 
                    wl.stime as wl_stime, wl.regdate as wl_regdate, wl.plandate as wl_plandate, wl.relec_id as wl_relec_id, wl.before_id as wl_before_id, 
                    wl.lesson_state as wl_lesson_state, wl.stime2 as wl_stime2, wl.lesson_list_view as wl_lesson_list_view, wl.renewal_ok as wl_renewal_ok, 
                    wl.disabled_extend as wl_disabled_extend, wl.lesson_count AS wl_lesson_count,wl.e_id as wl_e_id, wl.cl_id AS wl_cl_id, wl.student_su as wl_student_su,
                    wp.order_no as wp_order_no, wp.pay_ok as wp_pay_ok, wp.refund_ok as wp_refund_ok, wl.origin_cl_class AS wl_origin_class,
                    wb.new_link AS wb_new_link
                FROM wiz_lesson AS wl
                INNER JOIN wiz_pay AS wp ON wp.lesson_id=wl.lesson_id
                LEFT JOIN wiz_book wb ON wl.book_id = wb.book_id
                WHERE wl.lesson_id = ? AND wl.uid = ? ";

        $res = $this->db_slave()->query($sql, array($lesson_id, $wm_uid)); 

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_wiz_lesson_by_tu_id($lesson_id, $tu_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wl.lesson_id AS wl_lesson_id, wl.order_gubun AS wl_order_gubun, wl.newlesson_ok AS wl_newlesson_ok, wl.parent_id AS wl_parent_id,
                wl.uid AS wl_uid, wl.wiz_id AS wl_wiz_id, wl.name AS wl_name, wl.ename AS wl_ename, wl.tel AS wl_tel, wl.mobile AS wl_mobile, wl.tu_uid AS wl_tu_uid,
                wl.tu_name AS wl_tu_name, wl.co_uid AS wl_co_uid, wl.co_company AS wl_co_company, wl.ji_uid AS wl_ji_uid, wl.ji_company AS wl_ji_uid, wl.ji_company AS wl_ji_company,
                wl.man_id AS wl_man_id, wl.man_name AS wl_man_name, wl.lev_id AS wl_lev_id, wl.lev_gubun AS wl_lev_gubun, wl.lev_name AS wl_lev_name, wl.book_id AS wl_book_id,
                wl.book_name AS wl_book_name, wl.cl_id AS wl_cl_id, wl.cl_name AS wl_cl_name, wl.cl_name AS wl_cl_name2, wl.cl_label AS wl_cl_label, wl.cl_gubun AS wl_cl_gubun,
                wl.cl_lang AS wl_cl_lang, wl.cl_time AS wl_cl_time, wl.cl_number AS wl_cl_number, wl.origin_cl_class AS wl_origin_class, wl.cl_class AS wl_cl_class, wl.cl_service AS wl_cl_service,
                wl.cl_month AS wl_cl_month, wl.hold_num AS wl_hold_num, wl.paper_ok AS wl_paper_ok, wl.weekend AS wl_weekend, wl.fee AS wl_fee, wl.lesson_gubun AS wl_lesson_gubun, wl.hopedate AS wl_hopedate,
                wl.hopetime AS wl_hopetime, wl.schedule_ok AS wl_schedule_ok, wl.pay_ok AS wl_pay_ok, wl.refund_ok AS wl_refund_ok, wl.payment AS wl_payment, wl.pay_sum AS wl_pay_sum,
                wl.refund_sum AS wl_refund_sum, wl.tt AS wl_tt, wl.tt_1 AS wl_tt_1, wl.tt_2 AS wl_tt_2, wl.tt_3 AS wl_tt_3, wl.tt_3_1 AS wl_tt_3_1, wl.tt_4 AS wl_tt_4, wl.tt_5 AS wl_tt_5, wl.tt_6 AS wl_tt_6, 
                wl.tt_7 AS wl_tt_7, wl.tt_8 AS wl_tt_8, wl.tt_9 AS wl_tt_9, wl.tt_add AS wl_tt_add, wl.tt_holding_count AS wl_tt_holding_count, wl.tt_point_use AS wl_tt_point_use, 
                wl.startday AS wl_startday, wl.endday AS wl_endday, wl.stime AS wl_stime, wl.time_start AS wl_time_start, wl.time_end AS wl_time_end, wl.daytime_ok AS wl_daytime_ok, wl.conti AS wl_conti,
                wl.lesson_memo AS wl_lesson_memo, wl.report_num AS wl_report_num, wl.recall_ok AS wl_recall_ok, wl.consult_ok AS wl_consult_ok, wl.regdate AS wl_regdate,
                wl.plandate AS wl_plandate, wl.relec_id AS wl_relec_id, wl.before_id AS wl_before_id, wl.skype AS wl_skype, wl.lesson_bi_yn AS wl_lesson_bi_yn, wl.lesson_state AS wl_lesson_state,
                wl.stime2 AS wl_stime2, wl.lesson_tcode AS wl_lesson_tcode, wl.lesson_list_view AS wl_lesson_list_view, wl.report_app AS wl_report_app, wl.cons_hope_time AS wl_cons_hope_time,
                wl.cons_hope_time2 AS wl_cons_hope_time2, wl.renewal_ok AS wl_renewal_ok, wl.renewal_reason AS wl_renewal_reason, wl.dealer_pay_ok AS wl_dealer_pay_ok, wl.student_su AS wl_student_su,
                wl.student_uid AS wl_student_uid, wl.e_id AS wl_e_id, wl.invoice AS wl_invoice, wl.disabled_extend AS wl_disabled_extend, wl.lesson_number AS wl_lesson_number, wl.lesson_count AS wl_lesson_count, 
                wl.lesson_total AS wl_lesson_total, wl.lesson_event AS wl_lesson_event, wl.lesson_refund AS wl_lesson_refund, wl.lesson_correction AS wl_lesson_correction, wl.lesson_coupon AS wl_lesson_coupon,
                wl.limit_startday AS wl_limit_startday, wl.release_cnt AS wl_release_cnt, wl.add_class_cnt AS wl_add_class_cnt
                FROM wiz_lesson wl
                WHERE wl.lesson_id = ? AND wl.tu_uid = ? ";

        $res = $this->db_slave()->query($sql, array($lesson_id, $tu_uid)); 
        // echo $this->db_slave()->last_query();exit;
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function checked_has_permission_add_class($lesson_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT 1 FROM wiz_member_correct_gift
                WHERE memo = ?";

        $res = $this->db_slave()->query($sql, array($lesson_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function list_tutor_schedule_by_date($tu_uid, $sdate, $edate)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.startday as ws_startday, ws.endday as ws_endday, ws.cl_time as ws_cl_time FROM wiz_schedule as ws 
                WHERE ws.tu_uid = ? AND ws.startday >= ? AND ws.startday < ? AND present IN (1,2,3,4)";

        $res = $this->db_slave()->query($sql, array($tu_uid, $sdate, $edate));
        //echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    // 수업기본횟수 업데이트 해주기 위해 셀렉하기 위한 함수
    public function list_lesson_data_for_cl_class($limit,$offset)
    {
        $this->db_connect('master');
                                                                                           
        $sql = "SELECT wp.lesson_id, wp.pay_name, wl.cl_class FROM wiz_pay as wp JOIN wiz_lesson as wl ON wp.lesson_id=wl.lesson_id ORDER BY wp.pay_id ASC LIMIT ".$offset.", ". $limit;

        $res = $this->db_master()->query($sql);
        //echo $this->db_master()->last_query();  
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    // 수강이력있는지
    public function checked_has_lesson_history($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT lesson_id FROM wiz_lesson USE INDEX(uid)
                WHERE (uid = '".$uid."' OR student_uid LIKE '%,".$uid.",%') AND pay_ok ='Y' AND refund_ok='N' ";
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function insert_wiz_report($param, $first_schedule, $last_schedule)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->set('report_num', '`report_num` + 1', FALSE);
        $this->db_master()->where(array('lesson_id' => $param['lesson_id'], 'report_num'=> '0'));
        $this->db_master()->where(array('startday >=' => $first_schedule, 'startday <=' => $last_schedule));
        $this->db_master()->update('wiz_schedule');

        $this->db_master()->insert('wiz_report', $param);
        $insert_id = $this->db_master()->insert_id();   

        $this->db_master()->set('report_num', '`report_num` + 1', FALSE);
        $this->db_master()->set('report_app', '2', FALSE);
        $this->db_master()->where(array('lesson_id' => $param['lesson_id']));
        $this->db_master()->update('wiz_lesson');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return $insert_id;
    }

    public function update_wiz_report($re_id, $param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('re_id' => $re_id));
        $this->db_master()->update('wiz_report', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    
    // 피드백 안된 수업정보 가져오기
    public function list_schedule_incomplete_feedback($tu_uid, $yesterday, $day, $limit)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.lesson_id as ws_lesson_id, ws.sc_id as ws_sc_id, ws.cl_time as ws_cl_time, ws.startday as ws_startday, ws.kind as ws_kind, wm.ename as wm_ename
                FROM wiz_schedule as ws
                JOIN wiz_member as wm ON ws.uid=wm.uid
                WHERE startday > '".$yesterday." 00:59:59' AND endday < '".$day." 00:59:59' AND tu_uid='".$tu_uid."' AND present=1
                ORDER BY endday DESC 
                LIMIT 0,".$limit;

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    // 해당날짜, 해당강사의 스케쥴 전부뽑는다
    public function list_schedule_by_tu_uid_and_startday($tu_uid, $date)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.lesson_id as ws_lesson_id, ws.sc_id as ws_sc_id, ws.cl_time as ws_cl_time, ws.startday as ws_startday, ws.lesson_gubun as ws_lesson_gubun,
                       ws.present as ws_present, ws.kind as ws_kind, ws.ab_ok as ws_ab_ok, wm.uid as wm_uid, wm.ename as wm_ename, wm.age as wm_age, wm.gender as wm_gender,
                       wml.content as wml_content, wml.greeting_yn as wml_greeting_yn, wml.speed_slowly_yn as wml_speed_slowly_yn, 
                       wml.focus_book_yn as wml_focus_book_yn, wml.feedback_inclass_yn as wml_feedback_inclass_yn,
                       (CASE WHEN ws.lesson_id=100000000 THEN (SELECT order_number FROM wiz_leveltest as wlt WHERE wlt.sc_id=ws.sc_id) ELSE '' END) as order_number,
                       wl.student_su as wl_student_su, wl.lesson_gubun as wl_lesson_gubun
                FROM wiz_schedule as ws
                JOIN wiz_member as wm ON ws.uid=wm.uid
                LEFT JOIN wiz_lesson as wl ON wl.lesson_id=ws.lesson_id
                LEFT JOIN wiz_member_lessontype as wml ON wml.uid=ws.uid AND wml.lesson_id=ws.lesson_id
                WHERE ws.startday BETWEEN '".$date." 00:00:00' AND '".$date." 23:59:59' AND ws.tu_uid='".$tu_uid."' AND ws.present IN (1,2,3,4,5,6)
                ORDER BY ws.startday ASC";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    // 해당날짜(기본 한달 기준), 해당강사의 present별 카운트를 가져온다
    public function count_schedule_present_by_tu_uid($tu_uid, $sdate, $edate)
    {
        $this->db_connect('slave');

        $sql = "SELECT COUNT(IF(ws.present=1, 1, NULL)) as standby, COUNT(IF(ws.present=2, 1, NULL)) as present, COUNT(IF(ws.present=3, 1, NULL)) as absent,
                       COUNT(IF(ws.present=4, 1, NULL)) as cancel, COUNT(IF(ws.present=5, 1, NULL)) as hold_office, COUNT(IF(ws.present=6, 1, NULL)) as hold_student,
                       COUNT(IF(ws.present=5 AND ws.ab_ok='N', 1, NULL)) as tc, COUNT(IF(ws.present=5 AND ws.ab_ok='Y', 1, NULL)) as tp
                FROM wiz_schedule as ws
                WHERE ws.startday BETWEEN '".$sdate." 00:00:00' AND '".$edate." 23:59:59' AND ws.tu_uid='".$tu_uid."' AND ws.present IN (1,2,3,4,5,6)
                ORDER BY ws.startday ASC";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function total_cl_time_group_by_student_su($tu_uid, $sdate, $edate, $lesson_gubun='')
    {
        $this->db_connect('slave');

        $sql = "SELECT wl.student_su, SUM(ws.cl_time) as cl_time 
                FROM wiz_schedule as ws 
                JOIN wiz_lesson as wl ON ws.lesson_id=wl.lesson_id
                WHERE ws.tu_uid= ? AND ws.startday BETWEEN ? AND ? AND ws.present=2 AND ws.lesson_id != 100000000 
                ".$lesson_gubun."
                GROUP BY wl.student_su";
    
        $res = $this->db_slave()->query($sql, array($tu_uid, $sdate, $edate));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function total_cl_time_except_leveltest($tu_uid, $sdate, $edate, $present, $lesson_gubun='')
    {
        $this->db_connect('slave');

        $sql = "SELECT SUM(ws.cl_time) as cl_time 
                FROM wiz_schedule as ws 
                WHERE ws.tu_uid= ? AND ws.startday BETWEEN ? AND ? AND ws.present= ? AND ws.lesson_id != 100000000 ".$lesson_gubun;
    
        $res = $this->db_slave()->query($sql, array($tu_uid, $sdate, $edate, $present));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function total_cl_time_leveltest($tu_uid, $sdate, $edate, $present)
    {
        $this->db_connect('slave');

        $sql = "SELECT SUM(CASE WHEN lesson_gubun='E' AND cl_time=20 THEN cl_time-10 ELSE cl_time END) as cl_time 
                FROM wiz_schedule as ws 
                WHERE ws.tu_uid= ? AND ws.startday BETWEEN ? AND ? AND ws.present= ? AND ws.lesson_id = 100000000";
    
        $res = $this->db_slave()->query($sql, array($tu_uid, $sdate, $edate, $present));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function total_cl_time_groupby_present($tu_uid, $sdate, $edate, $present, $lesson_gubun)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.present, SUM(ws.cl_time) as cl_time 
                FROM wiz_schedule as ws 
                WHERE ws.tu_uid= ? AND ws.startday BETWEEN ? AND ? AND ws.present IN ? AND ws.lesson_gubun IN ? AND ws.lesson_id != 100000000
                GROUP BY ws.present";

        $res = $this->db_slave()->query($sql, array($tu_uid, $sdate, $edate, $present, $lesson_gubun));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    public function list_count_schedule($index, $where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                        FROM wiz_schedule ws %s
                        %s", $index, $where);
                        
        $res = $this->db_slave()->query($sql);
         //echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_schedule($index, $where, $order, $limit , $select_col_content = " ")
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT ws.sc_id as ws_sc_id, ws.name as ws_name, ws.wiz_id as ws_wiz_id, ws.startday as ws_startday, ws.lesson_id as ws_lesson_id, 
                        ws.lesson_gubun as ws_lesson_gubun, ws.cl_time as ws_cl_time, ws.present as ws_present, 
                        wl.startday as wl_startday, wl.endday as wl_endday, wl.tel as wl_tel, wl.mobile as wl_mobile, wl.student_su as wl_student_su %s
                        FROM wiz_schedule ws %s
                        LEFT JOIN wiz_lesson wl ON wl.lesson_id = ws.lesson_id 
                        %s %s %s", $select_col_content , $index, $where, $order, $limit);

        $res = $this->db_slave()->query($sql);  
         //echo $this->db_slave()->last_query();  exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 피드백 상세보기
     */
    public function feedback_info($sc_id, $uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.sc_id as ws_sc_id, ws.lesson_gubun as ws_lesson_gubun, ws.tel as ws_tel, ws.mobile as ws_mobile, ws.skype as ws_skype,
                       ws.startday as ws_startday, ws.endday as ws_endday, ws.present as ws_present, ws.cl_time as ws_cl_time, ws.kind as ws_kind,
                       ws.lesson_id as ws_lesson_id, ws.topic as ws_topic, ws.tu_uid as ws_tu_uid, wm.uid as wm_uid, wm.name as wm_name,
                       wsr.topic_date as wsr_topic_date, wsr.topic_previous as wsr_topic_previous, wsr.topic_today as wsr_topic_today,
                       wsr.topic_next as wsr_topic_next, wsr.stu_info2 as wsr_stu_info2, wsr.absent_reason as wsr_absent_reason,  wsr.rating_ls as wsr_rating_ls,
                       wsr.rating_ss as wsr_rating_ss, wsr.rating_pro as wsr_rating_pro, wsr.rating_voc as wsr_rating_voc, wsr.rating_cg as wsr_rating_cg,
                       wsr.pronunciation as wsr_pronunciation, wsr.grammar as wsr_grammar, wsr.comment as wsr_comment, wsr.scr_id as wsr_scr_id,
                       wl.lev_gubun as wl_lev_gubun, wl.book_id as wl_book_id, wl.book_name as wl_book_name
                FROM wiz_schedule as ws
                LEFT JOIN wiz_member as wm ON wm.uid = ws.uid
                LEFT JOIN wiz_schedule_result as wsr ON wsr.sc_id = ws.sc_id
                LEFT JOIN wiz_lesson as wl ON wl.lesson_id = ws.lesson_id
                WHERE ws.sc_id = '".$sc_id."' AND (wl.uid = '".$uid."' || wl.student_uid LIKE '%,".$uid.",%') ORDER BY ws.startday LIMIT 1";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 말톡 로그 조회
     */
    public function get_maaltalk_note_log($tu_uid, $uid, $sc_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT sc_id, regdate
                FROM maaltalk_note_log
                WHERE tu_uid = '".$tu_uid."' AND wm_uid = '".$uid."' AND sc_id = '".$sc_id."' AND state ='2' ";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    //장기연기 스케줄 개수 체크
    public function chk_postpone_lesson_cnt($lesson_id)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT tt_7 as cnt
                FROM wiz_lesson
                WHERE lesson_id='".$lesson_id."' AND tu_uid=158 AND tu_name='postpone' ";
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_mint_change_for_today_data($date)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT * FROM mint_change_for_today_data WHERE startday BETWEEN '".$date." 00:00:00' AND '".$date." 23:59:59'";
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    
    public function delete_mint_change_for_today_data($where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->delete('mint_change_for_today_data');
        //echo $this->db_master()->last_query();  exit;
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    
    public function get_feedback_by_score_count($where)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT
        CASE WHEN (sr.rating_ls + sr.rating_ss+sr.rating_pro+sr.rating_voc+sr.rating_cg)/5-1 BETWEEN 1 AND  1.99 THEN 1
        WHEN (sr.rating_ls + sr.rating_ss+sr.rating_pro+sr.rating_voc+sr.rating_cg)/5-1 BETWEEN 2 AND 2.99 THEN 2
        WHEN (sr.rating_ls + sr.rating_ss+sr.rating_pro+sr.rating_voc+sr.rating_cg)/5-1 BETWEEN 3 AND 3.99 THEN 3
        WHEN (sr.rating_ls + sr.rating_ss+sr.rating_pro+sr.rating_voc+sr.rating_cg)/5-1 BETWEEN 4 AND 4.99 THEN 4
        WHEN (sr.rating_ls + sr.rating_ss+sr.rating_pro+sr.rating_voc+sr.rating_cg)/5-1 BETWEEN 5 AND 5.99 THEN 5
        WHEN (sr.rating_ls + sr.rating_ss+sr.rating_pro+sr.rating_voc+sr.rating_cg)/5-1 BETWEEN 6 AND 6.99 THEN 6
        WHEN (sr.rating_ls + sr.rating_ss+sr.rating_pro+sr.rating_voc+sr.rating_cg)/5-1 BETWEEN 7 AND 7.99 THEN 7
        END as lv  , count(1) as m_ea
        FROM wiz_schedule_result sr
        INNER JOIN (SELECT  max(sc_id) as s_sc_id  FROM wiz_schedule WHERE ".$where." GROUP BY uid) as s on s.s_sc_id = sr.sc_id
        GROUP BY lv";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    
    public function get_feedback_by_field_count($where, $field)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT sr.".$field.", count(1) as ea
                FROM wiz_schedule_result sr
                INNER JOIN (SELECT  max(sc_id) as s_sc_id FROM wiz_schedule WHERE ".$where." GROUP BY uid) s on s.s_sc_id = sr.sc_id
                GROUP BY ".$field." HAVING ".$field." > '0'";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function get_feedback_stat_two_day_ago()
    {
        $this->db_connect('slave');
    
        $sql = "SELECT * FROM feedback_stat WHERE stat_date = '".date("Y-m-d", strtotime(date('Y-m-d')." -2 day"))."' and type='class'";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function insert_feedback_stat($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('feedback_stat', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    public function get_leveltest_feedback_by_score_count($where)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT
                CASE WHEN (listening + speaking+pronunciation+vocabulary+grammar)/5-1 BETWEEN 1 AND  1.99 THEN 1
                WHEN (listening + speaking+pronunciation+vocabulary+grammar)/5-1 BETWEEN 2 AND 2.99 THEN 2
                WHEN (listening + speaking+pronunciation+vocabulary+grammar)/5-1 BETWEEN 3 AND 3.99 THEN 3
                WHEN (listening + speaking+pronunciation+vocabulary+grammar)/5-1 BETWEEN 4 AND 4.99 THEN 4
                WHEN (listening + speaking+pronunciation+vocabulary+grammar)/5-1 BETWEEN 5 AND 5.99 THEN 5
                WHEN (listening + speaking+pronunciation+vocabulary+grammar)/5-1 BETWEEN 6 AND 6.99 THEN 6
                WHEN (listening + speaking+pronunciation+vocabulary+grammar)/5-1 BETWEEN 7 AND 7.99 THEN 7 END as lv  ,
                count(1) as m_ea
                FROM wiz_leveltest
                WHERE ".$where."
                group by  lv";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function get_leveltest_feedback_by_field_count($where, $field)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT ".$field." , count(1) as ea
                FROM  wiz_leveltest
                WHERE {$where}
                GROUP BY ".$field." having ".$field." > '0'";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function get_leveltest_feedback_stat_two_day_ago()
    {
        $this->db_connect('slave');
    
        $sql = "SELECT * FROM feedback_stat WHERE stat_date = '".date("Y-m-d", strtotime(date('Y-m-d')." -2 day"))."' AND type='leveltest'";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function total_cl_time_by_uid($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT SUM(total_cl_time) as total_cl_time
                FROM
                (
                    SELECT sum(ws.cl_time) as total_cl_time 
                    FROM wiz_schedule as ws 
                    WHERE ws.present=2 AND  ws.uid=? AND ws.lesson_id < 100000000
        
                    UNION ALL

                    SELECT sum(ws.cl_time) as total_cl_time 
                    FROM wiz_schedule as ws 
                    JOIN wiz_lesson as wl ON ws.lesson_id=wl.lesson_id
                    WHERE ws.present=2 AND  wl.student_uid LIKE '%,".$uid.",%' AND ws.lesson_id < 100000000
                ) AS tmp";

        $res = $this->db_slave()->query($sql, array($uid));
        //echo $this->db_slave()->last_query();  exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    

    public function list_class_before_start($time)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT ws.tu_name, ws.uid, ws.wiz_id, wm.name, wm.d_id, ws.startday, ws.mobile, ws.lesson_id 
                FROM wiz_schedule as ws 
                LEFT JOIN wiz_member as wm ON ws.uid=wm.uid 
                WHERE ws.startday BETWEEN '".$time.":00' AND '".$time.":59' AND ws.tu_name!='postpone' AND ws.tu_uid NOT IN ('153','88') 
                AND ws.present='1' AND wm.d_id NOT IN ('96','118') AND ws.lesson_id NOT IN ('100000000')";

        $res = $this->db_slave()->query($sql);
        //echo $this->db_slave()->last_query();  exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function check_relay_schedule($uid,$startday)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT * FROM wiz_schedule USE INDEX(uid) 
                WHERE uid= ? AND startday < '".$startday."' AND tu_name!='postpone' AND startday LIKE '".substr($startday,0,10)."%' ORDER BY startday DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($uid));
        //echo $this->db_slave()->last_query();  exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_lesson_endday_comming($sDate,$d_id)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT wl.mobile, wl.uid, wl.name, wl.wiz_id, wl.tu_uid, wl.e_id, wl.weekend, wl.endday, wl.stime, mc.coupon_type
                FROM wiz_lesson wl
                JOIN wiz_member wm ON wl.uid=wm.uid
                LEFT JOIN wiz_class wc ON wl.cl_id=wc.cl_id
                LEFT JOIN wiz_mcoupon mc ON wc.coupon_id=mc.coupon_id
                WHERE wl.endday IN ? AND wm.d_id NOT IN ? AND wl.refund_ok='N' 
                AND wl.newlesson_ok='Y' AND wl.uid !='33512' AND wl.tu_name NOT IN('postpone')";

        $res = $this->db_slave()->query($sql, array($sDate, $d_id));
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    //같은상품있는지를 체크하여 재수강했는지 여부 확인(사실 이거도 좀 부정확하다)
    public function check_same_type_goods_exist($uid,$tu_uid,$weekend,$endday,$stime)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT COUNT(1) as cnt FROM wiz_lesson
                WHERE uid= ? AND tu_uid= ? AND weekend= ? AND startday > ? AND FROM_UNIXTIME(stime, '%H:%i')='".date("H:i", $stime)."'";
                                                                            
        $res = $this->db_slave()->query($sql, array($uid,$tu_uid,$weekend,$endday));
        //echo $this->db_slave()->last_query();  exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function check_coupon_lesson_regist($where)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT wl.regdate, wm.uid, wm.wiz_id, wm.name, wm.mobile, mc.coupon_type, mc.validate
                FROM wiz_lesson wl
                JOIN wiz_pay wp ON wl.lesson_id=wp.lesson_id
                JOIN wiz_coupon wc ON wp.coupon_num=wc.cp_id
                JOIN wiz_mcoupon mc ON wc.coupon_id=mc.coupon_id AND mc.coupon_type IS NOT NULL ".$where." 
                JOIN wiz_member wm ON wl.uid=wm.uid AND wm.del_yn=''
                WHERE wl.payment='coupon:' AND wl.plandate='0000-00-00 00:00:00' AND wl.uid !='33512'";

        $res = $this->db_slave()->query($sql);
        //echo $this->db_slave()->last_query();  exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function check_new_class($chk_date)
    {
        $this->db_connect('slave');

        $sql = "SELECT uid,name FROM wiz_lesson WHERE lesson_state = 'in class' AND conti = 1 AND tu_uid != 158 AND startday = '".$chk_date."' ORDER BY lesson_id DESC";

        $res = $this->db_slave()->query($sql);
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function check_exist_neoteck_pay($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT 1 FROM wiz_lesson as wl
                JOIN wiz_pay as wp ON wl.lesson_id=wp.lesson_id 
                WHERE wl.uid= ? AND wp.pay_name like '%Video%' LIMIT 1";

        $res = $this->db_slave()->query($sql, array($uid));
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function check_schedule_book_link($lesson_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wb.book_id, wl.lesson_id, wb.book_link, wb.new_link, 
                (SELECT book_page FROM mint_class_book WHERE book_id = wb.book_id AND lesson_id = wl.lesson_id) AS last_page
                FROM wiz_lesson wl 
                LEFT OUTER JOIN wiz_book wb ON wl.book_id = wb.book_id
                WHERE lesson_id = ?
                ";

        $res = $this->db_slave()->query($sql, array($lesson_id));
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_lesson_endday_desc_limit1($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT endday FROM wiz_lesson WHERE uid = ? ORDER BY endday DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($uid));
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 종료된 강의 리스트
     */
    public function list_lesson_finish_count($where1, $where2)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT SUM(cnt) as cnt FROM (
                SELECT count(1) as cnt
                FROM wiz_lesson as wl
                ".$where1."
                UNION ALL
                SELECT count(1) as cnt
                FROM wiz_lesson as wl
                ".$where2."
                ) ad";
                        
        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function list_lesson_finish($where1, $where2, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT * FROM (
                SELECT (SELECT wr.re_id FROM wiz_report wr WHERE wr.lesson_id = wl.lesson_id ORDER BY wr.re_id DESC LIMIT 1) AS wr_re_id,
                        wl.lesson_id as wl_lesson_id, wl.refund_ok as wl_refund_ok, wl.payment as wl_payment, wl.startday as wl_startday, wl.endday as wl_endday,
                        wl.student_su as wl_student_su, wl.cl_label as wl_cl_label, wl.cl_name as wl_cl_name, wl.cl_name2 as wl_cl_name2, wl.cl_time as wl_cl_time, wl.cl_gubun as wl_cl_gubun,
                        wl.cl_class as wl_cl_class, wl.cl_service as wl_cl_service, wl.tu_uid as wl_tu_uid, wl.tu_name as wl_tu_name, wl.lesson_gubun as wl_lesson_gubun,
                        wl.daytime_ok as wl_daytime_ok, wl.book_name as wl_book_name, wl.stime as wl_stime, wl.newlesson_ok as wl_newlesson_ok, wl.fee as wl_fee,
                        wl.tt AS wl_tt, wl.tt_1 AS wl_tt_1, wl.tt_2 AS wl_tt_2, wl.tt_3 AS wl_tt_3, wl.tt_3_1 AS wl_tt_3_1, wl.tt_4 AS wl_tt_4, wl.tt_5 AS wl_tt_5,
                        wl.tt_6 AS wl_tt_6, wl.tt_7 AS wl_tt_7, wl.tt_8 AS wl_tt_8, wl.tt_9 AS wl_tt_9, wl.tt_add AS wl_tt_add, wl.tt_holding_count AS wl_tt_holding_count,
                        wla.lesson_id as wla_lesson_id
                FROM wiz_lesson as wl
                LEFT JOIN wiz_lesson_allclear as wla ON wla.lesson_id = wl.lesson_id
                ".$where1."
                UNION ALL
                SELECT (SELECT wr.re_id FROM wiz_report wr WHERE wr.lesson_id = wl.lesson_id ORDER BY wr.re_id DESC LIMIT 1) AS wr_re_id,
                        wl.lesson_id as wl_lesson_id, wl.refund_ok as wl_refund_ok, wl.payment as wl_payment, wl.startday as wl_startday, wl.endday as wl_endday,
                        wl.student_su as wl_student_su, wl.cl_label as wl_cl_label, wl.cl_name as wl_cl_name, wl.cl_name2 as wl_cl_name2, wl.cl_time as wl_cl_time, wl.cl_gubun as wl_cl_gubun,
                        wl.cl_class as wl_cl_class, wl.cl_service as wl_cl_service, wl.tu_uid as wl_tu_uid, wl.tu_name as wl_tu_name, wl.lesson_gubun as wl_lesson_gubun,
                        wl.daytime_ok as wl_daytime_ok, wl.book_name as wl_book_name, wl.stime as wl_stime, wl.newlesson_ok as wl_newlesson_ok, wl.fee as wl_fee,
                        wl.tt AS wl_tt, wl.tt_1 AS wl_tt_1, wl.tt_2 AS wl_tt_2, wl.tt_3 AS wl_tt_3, wl.tt_3_1 AS wl_tt_3_1, wl.tt_4 AS wl_tt_4, wl.tt_5 AS wl_tt_5,
                        wl.tt_6 AS wl_tt_6, wl.tt_7 AS wl_tt_7, wl.tt_8 AS wl_tt_8, wl.tt_9 AS wl_tt_9, wl.tt_add AS wl_tt_add, wl.tt_holding_count AS wl_tt_holding_count,
                        wla.lesson_id as wla_lesson_id
                FROM wiz_lesson as wl
                LEFT JOIN wiz_lesson_allclear as wla ON wla.lesson_id = wl.lesson_id
                ".$where2."
                ) list
                ".$order." ".$limit;

        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 개근상 수령 여부
     */
    public function is_take_all_clear_point($lesson_id)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT count(1) as cnt
                FROM wiz_lesson_allclear as wla
                WHERE lesson_id = '".$lesson_id."'";
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    /**
     * 개근상 수령 여부 기록
     */
    public function insert_wiz_lesson_allclear($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_lesson_allclear', $param);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return $insert_id;
    }

    /**
     * 수강평가표 상세보기
     */
    public function lesson_evaluation_article($re_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wl.lesson_id as wl_lesson_id, wl.startday as wl_startday, wl.student_su as wl_student_su, wl.cl_number as wl_cl_number, wl.cl_time as wl_cl_time,
                       wl.cl_month as wl_cl_month, wl.lesson_gubun as wl_lesson_gubun, wl.name as wl_name, wl.stime as wl_stime, wl.tu_name as wl_tu_name, wl.tu_uid as wl_tu_uid,
                       wl.cl_label as wl_cl_label, wl.cl_name as wl_cl_name, wl.cl_name2 as wl_cl_name2, wl.cl_time as wl_cl_time, wl.daytime_ok as wl_daytime_ok, wl.payment as wl_payment,
                       wl.tt_1 as wl_tt_1, wl.cl_gubun as wl_cl_gubun, wl.cl_class as wl_cl_class, wl.cl_service as wl_cl_service, wl.lev_gubun AS wl_lev_gubun,
                       wl.tt_2 as wl_tt_2, wl.tt_3 as wl_tt_3, wl.tt_3_1 as wl_tt_3_1, wl.tt_4 as wl_tt_4, wl.tt_5 as wl_tt_5, wl.tt_6 as wl_tt_6, wl.tt_7 as wl_tt_7, wl.tt_add as wl_tt_add,
                       wr.re_id as wr_re_id, wr.re_start as wr_re_start, wr.re_end as wr_re_end, wr.ev_memo as wr_ev_memo, wr.gra_memo as wr_gra_memo,
                       wr.pronunciation as wr_pronunciation, wr.vocabulary as wr_vocabulary, wr.speaking as wr_speaking, wr.listening as wr_listening, wr.grammar as wr_grammar,
                       wbh.book_name as wbh_book_name, wl.book_id as wl_book_id, wb.new_link AS wb_new_link
                FROM wiz_report as wr
                LEFT JOIN wiz_lesson as wl ON wr.lesson_id = wl.lesson_id
                LEFT JOIN wiz_bookhistory as wbh ON wbh.lesson_id = wr.lesson_id
                LEFT JOIN wiz_book wb ON wl.book_id = wb.book_id
                WHERE wr.re_id = '".$re_id."'";

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 민트영어 회원 평균 레벨
     */
    public function get_average_level_to_feedback_stat($startday)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT pro_avg+pro_avg_add as pro_avg, voc_avg+voc_avg_add as voc_avg, ss_avg+ss_avg_add as ss_avg,
                       ls_avg+ls_avg_add as ls_avg, cg_avg+cg_avg_add as cg_avg
                FROM feedback_stat
                WHERE stat_date = '".$startday."' and type='class'";

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 수강평가표 상세보기 - 이전,다음 수강평가표 정보
     * TODO: 확인필요함 전체적인 수강평가표 이동하는지? (회원이 받은 전체 수강평가표)
     * TODO: 아니면 해당 수업안에서 수강평가표가 이동되는지? (선택된 수업의 수강평가표)
     */
    public function prev_next_evaluation($where)
    {
        $this->db_connect('slave');

        $sql = "SELECT re_id
                FROM wiz_report
                WHERE ".$where." LIMIT 1";
    
        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 지난강의 내역 리스트
     */
    public function list_past_lesson_schedule_count($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                        FROM wiz_schedule as ws
                        %s", $where);
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function list_past_lesson_schedule($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT ws.sc_id as ws_sc_id, ws.lesson_gubun as ws_lesson_gubun, ws.present as ws_present, ws.startday as ws_startday, ws.endday as ws_endday,
                               ws.mobile as ws_mobile, ws.tu_name as ws_tu_name, ws.tu_uid as ws_tu_uid
                        FROM wiz_schedule as ws
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 수업과 연결된 게시물 정보
     */
    public function get_wiz_schedule_board_pivot($where, $order)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT wsbp.board_id as wsbp_board_id
                        FROM wiz_schedule_board_pivot as wsbp
                        %s %s", $where, $order);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function insert_wiz_lesson($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_lesson', $param);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return $insert_id;
    }

    //수업 번호(학생의 수업번호중 가장 큰 로우 리턴)
    public function get_lesson_number($uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT lesson_number FROM wiz_lesson as wl WHERE uid= ? AND lesson_state IN ('holding', 'in class') ORDER BY lesson_number DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($uid));
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    

    public function find_relec_id($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT lesson_id FROM wiz_lesson WHERE uid = ? AND schedule_ok='Y' ORDER BY lesson_id DESC";
        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function get_wiz_lesson_text($lesson_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wlt.content as wlt_content FROM wiz_lesson_text as wlt WHERE lesson_id= ?";
        $res = $this->db_slave()->query($sql, array($lesson_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function insert_wiz_lesson_text($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_lesson_text', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    
    public function all_wiz_lesson_schedule_ok_by_uid($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM wiz_lesson WHERE uid = ? AND schedule_ok = 'Y' ORDER BY lesson_id";
        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    public function check_empty_time($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM wiz_lesson WHERE uid = ? AND schedule_ok = 'Y' ORDER BY lesson_id";
        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    public function check_exist_schedule_by_date($uid, $sdate, $edate)
    {
        $this->db_connect('slave');

        $sql = "SELECT 1
                FROM wiz_schedule as ws 
                WHERE ws.uid = ? AND ws.startday >= ? AND ws.startday < ? AND present IN (1,2,3,4)";

        $res = $this->db_slave()->query($sql, array($uid, $sdate, $edate));
        //echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function insert_wiz_class_extension($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_class_extension', $param);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return $insert_id;
    }

    public function row_class_extension_by_idx($idx)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM wiz_class_extension WHERE idx = ?";

        $res = $this->db_slave()->query($sql, array($idx));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function row_class_extension_by_sc_id($sc_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM wiz_class_extension WHERE sc_id = ? ORDER BY idx DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($sc_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function get_lesson_start_end_day($lesson_id)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT min(startday) as startday, max(startday) as endday FROM wiz_schedule WHERE lesson_id = ? AND present IN (1,2,3,4)";

        $res = $this->db_slave()->query($sql, array($lesson_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function update_wiz_class_extension($idx, $param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('idx', $idx);
        $this->db_master()->update('wiz_class_extension', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    //현수업의 다음수업을 찾는다. (자동재수강으로 연결된 수업찾기)
    public function find_next_auto_retake_lesson($except_search_lesson_id, $uid, $tu_uid, $date, $time)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT ws.lesson_id 
                FROM wiz_schedule as ws
                WHERE ws.lesson_id NOT IN ? AND ws.uid = ? AND ws.tu_uid = ? AND ws.kind='n' AND ws.tu_uid NOT IN (153,158) AND ws.startday > ? AND date_format(ws.startday, '%H:%i') = ?
                ORDER BY ws.startday ASC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($except_search_lesson_id, $uid, $tu_uid, $date, $time));
        //echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    //중복수업 검색
    public function find_duplicate_class_in_lesson($lesson_id)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT startday, tu_uid, COUNT(*) AS cnt, group_concat(sc_id) as sc_ids
                FROM wiz_schedule
                WHERE lesson_id= ? AND startday >='".date("Y-m-d",strtotime('+1 day'))."' AND present='1' AND kind='n'
                GROUP BY startday, tu_uid
                HAVING cnt > 1";

        $res = $this->db_slave()->query($sql, array($lesson_id));
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    public function row_schedule_by_startday($uid, $lesson_id, $startday)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT wm.pmobile AS wm_pmobile, ws.mobile AS ws_mobile, ws.startday AS ws_startday, ws.endday AS ws_endday, wl.student_su,
                        wm.uid AS wm_uid, wm.wiz_id AS wm_wiz_id, wm.name AS wm_name, wm.ename AS wm_ename, wl.student_uid AS wl_student_uid, ws.lesson_id as ws_lesson_id
                FROM wiz_schedule ws
                LEFT OUTER JOIN wiz_lesson wl ON ws.lesson_id = wl.lesson_id
                LEFT OUTER JOIN wiz_member wm ON ws.uid = wm.uid 
                WHERE ws.uid = ? AND ws.lesson_id = ? AND ws.startday = ?";

        $res = $this->db_slave()->query($sql, array($uid, $lesson_id, $startday));
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function check_class_extension_now($uid)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT * FROM wiz_class_extension
                WHERE uid = ? AND limit_date > NOW() AND approval_date IS NULL AND is_deny=0";

        $res = $this->db_slave()->query($sql, array($uid));
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

}










