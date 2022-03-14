<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Tutor_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function get_wiz_tutor_by_tu_id($tu_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wt.tu_uid as wt_tu_uid, wt.tu_id as wt_tu_id, wt.company as wt_company, wt.tu_rank as wt_tu_rank, wt.f_id as wt_f_id, wt.group_id as wt_group_id, wt.group_name as wt_group_name,
                    wt.group_id2 as wt_group_id2, wt.tu_name as wt_tu_name, wt.tu_fullname as wt_tu_fullname, wt.tu_email as wt_tu_email, wt.tu_gender as wt_tu_gender, wt.tu_duty as wt_tu_duty,
                    wt.profile as wt_profile, wt.major as wt_major, wt.career as wt_career, wt.correct_yn as wt_correct_yn, wt.tu_logview as wt_tu_logview, wt.tu_lastlogin as wt_tu_lastlogin,
                    wt.tu_modifydate as wt_tu_modifydate, wt.t0 as wt_t0, wt.t1 as wt_t1, wt.t2 as wt_t2, wt.t3 as wt_t3, wt.t4 as wt_t4, wt.t5 as wt_t5, wt.t6 as wt_t6, wt.t7 as wt_t7, 
                    wt.t8 as wt_t8, wt.t9 as wt_t9, wt.t10 as wt_t10, wt.t11 as wt_t11, wt.t12 as wt_t12, wt.t13 as wt_t13, wt.t14 as wt_t14, wt.t15 as wt_t15, wt.t16 as wt_t16, wt.t17 as wt_t17, wt.t18 as wt_t18, 
                    wt.t19 as wt_t19, wt.t20 as wt_t20, wt.t21 as wt_t21, wt.t22 as wt_t22, wt.t23 as wt_t23, wt.t24 as wt_t24, wt.tt as wt_tt, wt.tt_level as wt_tt_level, wt.tt_type as wt_tt_type,
                    wt.tu_regdate as wt_tu_regdate, wt.hire_date as wt_hire_date, wt.pay_type as wt_pay_type, wt.tu_pic_main as wt_tu_pic_main, wt.tu_pic as wt_tu_pic, wt.con_pic as wt_con_pic,
                    wt.tu_movie as wt_tu_movie, wt.tu_mpf as wt_tu_mpf, wt.tu_rec as wt_tu_rec, wt.tu_ielts as wt_tu_ielts, wt.web_av_time as wt_web_av_time, wt.web_profile as wt_web_profile, wt.pre_pro as wt_pre_pro,
                    wt.state as wt_state, wt.prt_order as wt_prt_order, wt.tu_movie_url as wt_tu_movie_url, wt.sche_view as wt_sche_view, wt.del_yn as wt_del_yn, wt.del_date as wt_del_date,
                    wt.evaluation as wt_evaluation, wt.max_student as wt_max_student, wt.mint_tutor as wt_mint_tutor, wt.avoid_lt as wt_avoid_lt, wt.sss_no as wt_sss_no, wt.tin_no as wt_tin_no,
                    wt.tax_code as wt_tax_code, wt.web_time as wt_web_time, wt.title as wt_title, wt.tl_opinion as wt_tl_opinion, wt.tropy as wt_tropy, wt.mset_possible as wt_mset_possible,
                    wt.mset_pronunciation as wt_mset_pronunciation, wt.mset_fluency as wt_mset_fluency, wt.mset_vocabulary as wt_mset_vocabulary, wt.mset_speaking as wt_mset_speaking, wt.mset_grammar as wt_mset_grammar,
                    wt.mset_listening as wt_mset_listening, wt.mset_function as wt_mset_function, wt.mset_total as wt_mset_total, wt.mset_level as wt_mset_level, wt.byorachiki_only as wt_byorachiki_only,
                    wt.parallax as wt_parallax, mc.nationAs as mc_nationAs
                FROM wiz_tutor wt
                LEFT JOIN mint_country AS mc ON wt.country_key = mc.idx
                WHERE wt.tu_id = ?  AND wt.del_yn = 'n' LIMIT 1";

        $res = $this->db_slave()->query($sql, array($tu_id));   
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function login($tu_id, $tu_pw)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT wt.tu_uid as wt_tu_uid, wt.tu_id as wt_tu_id, wt.company as wt_company, wt.tu_rank as wt_tu_rank, wt.f_id as wt_f_id, wt.group_id as wt_group_id, wt.group_name as wt_group_name,
                    wt.group_id2 as wt_group_id2, wt.tu_name as wt_tu_name, wt.tu_fullname as wt_tu_fullname, wt.tu_email as wt_tu_email, wt.tu_gender as wt_tu_gender, wt.tu_duty as wt_tu_duty,
                    wt.profile as wt_profile, wt.major as wt_major, wt.career as wt_career, wt.correct_yn as wt_correct_yn, wt.tu_logview as wt_tu_logview, wt.tu_lastlogin as wt_tu_lastlogin,
                    wt.tu_modifydate as wt_tu_modifydate, wt.t0 as wt_t0, wt.t1 as wt_t1, wt.t2 as wt_t2, wt.t3 as wt_t3, wt.t4 as wt_t4, wt.t5 as wt_t5, wt.t6 as wt_t6, wt.t7 as wt_t7, 
                    wt.t8 as wt_t8, wt.t9 as wt_t9, wt.t10 as wt_t10, wt.t11 as wt_t11, wt.t12 as wt_t12, wt.t13 as wt_t13, wt.t14 as wt_t14, wt.t15 as wt_t15, wt.t16 as wt_t16, wt.t17 as wt_t17, wt.t18 as wt_t18, 
                    wt.t19 as wt_t19, wt.t20 as wt_t20, wt.t21 as wt_t21, wt.t22 as wt_t22, wt.t23 as wt_t23, wt.t24 as wt_t24, wt.tt as wt_tt, wt.tt_level as wt_tt_level, wt.tt_type as wt_tt_type,
                    wt.tu_regdate as wt_tu_regdate, wt.hire_date as wt_hire_date, wt.pay_type as wt_pay_type, wt.tu_pic_main as wt_tu_pic_main, wt.tu_pic as wt_tu_pic, wt.con_pic as wt_con_pic,
                    wt.tu_movie as wt_tu_movie, wt.tu_mpf as wt_tu_mpf, wt.tu_rec as wt_tu_rec, wt.tu_ielts as wt_tu_ielts, wt.web_av_time as wt_web_av_time, wt.web_profile as wt_web_profile, wt.pre_pro as wt_pre_pro,
                    wt.state as wt_state, wt.prt_order as wt_prt_order, wt.tu_movie_url as wt_tu_movie_url, wt.sche_view as wt_sche_view, wt.del_yn as wt_del_yn, wt.del_date as wt_del_date,
                    wt.evaluation as wt_evaluation, wt.max_student as wt_max_student, wt.mint_tutor as wt_mint_tutor, wt.avoid_lt as wt_avoid_lt, wt.sss_no as wt_sss_no, wt.tin_no as wt_tin_no,
                    wt.tax_code as wt_tax_code, wt.web_time as wt_web_time, wt.title as wt_title, wt.tl_opinion as wt_tl_opinion, wt.tropy as wt_tropy, wt.mset_possible as wt_mset_possible,
                    wt.mset_pronunciation as wt_mset_pronunciation, wt.mset_fluency as wt_mset_fluency, wt.mset_vocabulary as wt_mset_vocabulary, wt.mset_speaking as wt_mset_speaking, wt.mset_grammar as wt_mset_grammar,
                    wt.mset_listening as wt_mset_listening, wt.mset_function as wt_mset_function, wt.mset_total as wt_mset_total, wt.mset_level as wt_mset_level, wt.byorachiki_only as wt_byorachiki_only,
                    wt.parallax as wt_parallax, mc.nationAs as mc_nationAs
                FROM wiz_tutor AS wt
                LEFT JOIN mint_country AS mc ON wt.country_key = mc.idx
                WHERE wt.tu_id = ? AND wt.tu_pw = ? AND wt.del_yn = 'n'";
        $res = $this->db_master()->query($sql, array($tu_id, $tu_pw));

        
        $wiz_tutor = $res->row_array();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return NULL;
        }

        return $wiz_tutor ? $wiz_tutor : NULL;
    }

    public function log_login($log)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->insert('wiz_tutor_log_check', $log);
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
            
    }

    public function list_select_ditaction()
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wt.tu_uid as wt_tu_uid, wt.tu_name as wt_tu_name
                FROM wiz_tutor wt USE INDEX(idx_tu_name)
                WHERE wt.tu_name !='test_good' AND wt.tu_name != 'postpone' AND wt.tu_name != 'corrections' AND wt.tu_uid not in('905','906')  AND del_yn = 'N'
                ORDER BY wt.tu_name ASC";
        $res = $this->db_slave()->query($sql);       
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function list_select_correction($join='',$where='')
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT wt.tu_uid as wt_tu_uid, wt.tu_name as wt_tu_name, wt.correct_yn as wt_correct_yn, 0 as recent
                FROM wiz_tutor wt %s
                WHERE wt.del_yn = 'n' AND wt.tu_name NOT IN ('test_good', 'postpone','corrections')
                %s
                ORDER BY wt.tu_name ASC",$join,$where);
                
        $res = $this->db_slave()->query($sql);       
        

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function get_tu_name_by_tu_uid($tu_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wt.tu_name
        FROM wiz_tutor wt
        WHERE wt.tu_uid = ?";
        $res = $this->db_slave()->query($sql, array($tu_uid));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_tutor_info_by_tu_uid($tu_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT tu_id, tu_name, tu_fullname, tu_gender, profile, major, career, web_time, web_av_time, web_profile, tropy, pre_pro, tu_pic,
                con_pic, tu_movie, tu_mpf, tu_rec, pay_type, del_yn, group_id2, tu_pic_main, sche_view, byorachiki_only
        FROM wiz_tutor wt
        WHERE wt.tu_uid = ?";
        $res = $this->db_slave()->query($sql, array($tu_uid));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_tutor_hashtag_log($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT thl.thl_key, thl.tu_uid, thl.it_uid, thl.count, tsi.it_name ,tsi.parent_uid
                        FROM tutor_hashtag_log  thl
                        JOIN tutor_star_item tsi ON thl.it_uid = tsi.it_uid
                    %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function list_tutor($where, $order, $limit)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT wt.tu_uid, wt.tu_id, wt.tu_name, wt.tu_fullname, wt.con_pic, wt.web_profile 
                        FROM wiz_tutor wt %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);       
        // echo $this->db_slave()->last_query();
        // exit;
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_tutor_join_star($where,$join='',$select_col_content='')
    {
        $this->db_connect('slave');

        $sql = "SELECT wt.tu_uid, wt.tu_id, wt.tu_name, wt.tu_fullname, wt .tu_pic_main, wt.con_pic, wt.tu_pic, tsl.average_total, wt.web_profile, wt.f_id, wt.byorachiki_only
                 ".$select_col_content."
                FROM wiz_tutor AS wt 
                LEFT JOIN tutor_star_log AS tsl ON tsl.tu_uid = wt.tu_uid
                " .$join.$where;

        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function tutor_major_hashtag($count,$where,$subwhere)
    {
        $this->db_connect('slave');

        // 서브쿼리로 먼저 가장 많이 선택된 특성순으로 정렬함
        $sql = "SELECT * FROM (
            SELECT tu_uid,CONCAT(',',GROUP_CONCAT(it_uid ORDER BY COUNT DESC LIMIT ".$count."),',') AS it_uid
            FROM tutor_hashtag_log  ".$subwhere." group by tu_uid 
        ) AS tmp " . $where;
        
        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();
        // exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_tutor_star($where,$select_col_content='')
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT ts.ts_uid, ts.tu_uid, ts.uid, ts.item1, ts.regdate, ts.item2 %s
                FROM tutor_star ts
                %s", $select_col_content, $where);

        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();
        // exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_star_item()
    {
        $this->db_connect('slave');

        $sql = "SELECT it_uid, it_name, parent_uid, bt_type FROM tutor_star_item WHERE parent_uid != 0 AND use_yn = 'Y'";
        
        $res = $this->db_slave()->query($sql);       
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_star_item_type()
    {
        $this->db_connect('slave');

        $sql = "SELECT it_uid, it_name, bt_type, slimit, elimit FROM tutor_star_item WHERE parent_uid = 0 AND use_yn = 'Y'";
        
        $res = $this->db_slave()->query($sql);       
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_tutor_hashtag_log_batch()
    {
        $this->db_connect('slave');

        $sql = "SELECT thl.thl_key, thl.tu_uid, thl.it_uid, thl.count 
                FROM tutor_hashtag_log thl";
        
        $res = $this->db_slave()->query($sql);       
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    // public function list_tutor_recommend_log_batch()
    // {
    //     $this->db_connect('slave');

    //     $sql = "SELECT trl.trl_key, trl.tu_uid, trl.recommend_s_count, trl.recommend_j_count, trl.recommend_total,
    //                     trl.user_s_count, trl.user_j_count, trl.recommend_s_per, trl.recommend_j_per, trl.user_s_per, trl.user_j_per
    //             FROM tutor_recommend_log trl";
        
    //     $res = $this->db_slave()->query($sql);       
        
    //     return $res->num_rows() > 0 ? $res->result_array() : NULL;
    // }
    
    public function insert_tutor_hashtag($tutor_hashtag)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->insert('tutor_hashtag_log', $tutor_hashtag);

        // $insert_id = $this->db_master()->insert_id();
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    
    public function insert_batch_tutor_hashtag($data)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->insert_batch('tutor_hashtag_log', $data);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_tutor_hashtag($tu_uid, $it_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('count', '`count` + 1', FALSE);
        $this->db_master()->where(array('tu_uid' => $tu_uid, 'it_uid'=> $it_uid));
        
        $this->db_master()->update('tutor_hashtag_log');
    
        $this->db_master()->trans_complete();


        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    
    public function update_batch_tutor_hashtag($data)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->update_batch('tutor_hashtag_log', $data, 'thl_key');
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_batch_update_log($date, $time, $type)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT bul.update_date FROM _batch_update_log bul WHERE bul.type='$type'";
    
        $res = $this->db_master()->query($sql);
        $tmp = $res->row_array();

        if($tmp)
        {
            $this->db_master()->where('type', $type);
            $this->db_master()->set('update_date', $date);
            $this->db_master()->set('end_time', $time);
            $this->db_master()->set('type', $type);
            $this->db_master()->update('_batch_update_log');
        }
        else
        {
            $this->db_master()->set('update_date', $date);
            $this->db_master()->set('end_time', $time);
            $this->db_master()->set('type', $type);
            $this->db_master()->insert('_batch_update_log');
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function get_tutor_hashtag_log_by_tu_uid($tu_uid, $it_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT thl.thl_key, thl.tu_uid, thl.it_uid, thl.count FROM tutor_hashtag_log thl 
        WHERE thl.tu_uid = ? AND thl.it_uid = ?";

        $res = $this->db_slave()->query($sql, array($tu_uid, $it_uid));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_tutor_star_item()
    {
        $this->db_connect('slave');
        
        $sql = "SELECT tsi.it_uid, tsi.it_name FROM tutor_star_item tsi WHERE tsi.use_yn='Y' AND tsi.parent_uid !=0;";
        $res = $this->db_slave()->query($sql);       
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_tutor_star_log($where)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT tsl.tsl_key, tsl.tu_uid
                FROM tutor_star_log tsl
                %s", $where);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function get_tutor_star_average($where, $order, $limit)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT ts.ts_uid, ts.tu_uid, sum(ts.ts_star) AS star_total, count(ts.ts_uid) AS join_count, (sum(ts.ts_star)/count(ts.ts_uid)) AS average
                        FROM tutor_star ts
                        %s %s %s",
                        $where, $order, $limit);

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function update_tutor_star_log($tutor_star, $tu_uid)
    {
        $this->db_connect('master');
        $this->db_master()->trans_start();

        $sql = "SELECT tsl.tsl_key, tsl.tu_uid FROM tutor_star_log tsl 
        WHERE tsl.tu_uid = ?";

        $res = $this->db_master()->query($sql, array($tu_uid));   
        $tmp = $res->row_array();

        if($tmp)
        {
            $this->db_master()->where('tu_uid', $tu_uid);
            $this->db_master()->update('tutor_star_log', $tutor_star);
        }
        else
        {
            $this->db_master()->insert('tutor_star_log', $tutor_star);
        }
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function row_tutor_star_log($tu_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT tsl.average_1, tsl.average_2, tsl.average_3, tsl.average_4, tsl.average_5, tsl.average_6, tsl.average_7, 
                        tsl.average_8, tsl.average_9, tsl.average_10, tsl.average_11, tsl.average_12, tsl.average_total
                FROM tutor_star_log tsl WHERE tsl.tu_uid = ?";

        $res = $this->db_slave()->query($sql, $tu_uid);
        
        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function row_tutor_recommend_log($tu_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT trl.total_count, trl.recommend_s_count, trl.recommend_j_count, trl.recommend_remain_count, 
                        trl.recommend_s_per, trl.recommend_j_per, trl.recommend_remain_per, 
                        trl.user_s_count, trl.user_j_count, trl.user_s_per, trl.user_j_per
                FROM tutor_recommend_log trl WHERE trl.tu_uid = ?";

        $res = $this->db_slave()->query($sql, $tu_uid);
        // echo $this->db_slave()->last_query();
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function update_tutor_recommend_log($tutor_recommend, $tu_uid)
    {
        $this->db_connect('master');
        $this->db_master()->trans_start();

        $sql = "SELECT trl.trl_key, trl.tu_uid, trl.total_count, trl.recommend_s_count, trl.recommend_j_count, trl.recommend_remain_count, 
                        trl.user_s_count, trl.user_j_count, trl.recommend_s_per, trl.recommend_j_per, trl.recommend_remain_per, trl.user_s_per, trl.user_j_per
                FROM tutor_recommend_log trl
                WHERE trl.tu_uid = ?";

        $res = $this->db_master()->query($sql, array($tu_uid));   
        $tmp = $res->row_array();

        if($tmp)
        {
            $this->db_master()->where('tu_uid', $tu_uid);
            $this->db_master()->update('tutor_recommend_log', $tutor_recommend);
        }
        else
        {
            $this->db_master()->insert('tutor_recommend_log', $tutor_recommend);
        }
        
        // $insert_id = $this->db_master()->insert_id();
        // echo $this->db_slave()->last_query();
        // exit;
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function list_tutor_star_user_info($where)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT ts.ts_uid, ts.tu_uid, ts.uid, ts.item1, ts.regdate, ts.ts_star, ts.item2,ts.ts_content, wm.birth, wm.name, wm.nickname, wm.ename,
                        (SELECT count(distinct(uid)) AS cnt FROM tutor_star WHERE tu_uid = ts.tu_uid) AS ts_count
                        FROM tutor_star ts
                        INNER JOIN wiz_member wm ON ts.uid = wm.uid 
                %s", $where);

        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function count_tutor_star_by_tu_uid($tu_uid)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT count(distinct(uid)) AS cnt 
                        FROM tutor_star 
                        WHERE tu_uid = ? ");

        $res = $this->db_slave()->query($sql, array($tu_uid));
        
        // echo $this->db_slave()->last_query();
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_tutor_commute_log($tu_uid,$where='')
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT `mb_unq`, `sim_content` AS tu_uid, `title`, `regdate` 
                        FROM `mint_boards` 
                        WHERE `table_code`='1134' AND `sim_content`= '%s'  %s", $tu_uid, $where);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function row_table_count($table, $where=''){
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT count(1) AS cnt FROM %s %s ",$table, $where);
        //echo $sql;exit;
        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function insert_tutor_evaluation($param,$checkpass=false)
    {
        $this->db_connect('master');

        if(!$checkpass)
        {
            $sql = "SELECT 1 FROM wiz_point wp
            WHERE wp.uid = ? AND wp.kind = ? AND (wp.regdate >= ? AND wp.regdate <= ?) AND wp.showYn = 'y'";
            $res = $this->db_master()->query($sql, array($param['uid'], 'm', date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')));

            if($res->num_rows() > 0) return ['state'=>false, 'msg' => '프로세스 오류', 'err_msg' => '하루에 한번 등록하실수 있습니다.', 'res_code'=>"0900", 'err_code'=>'0402'];
        }

        $this->db_master()->trans_start();
        $this->db_master()->insert('tutor_star', $param);

        $insert_id = $this->db_master()->insert_id();
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    
    public function insert_tutor_incentive($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->insert('mint_incentive', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function update_tutor_incentive($param, $where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->where($where);
        $this->db_master()->update('mint_incentive', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function delete_tutor_incentive($where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->delete('mint_incentive', $where);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    public function update_tutor_evaluation($update_param,$where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->update('tutor_star', $update_param, $where);
        //echo $this->db_master()->last_query();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    public function delete_tutor_evaluation($where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->delete('tutor_star',$where);
        //echo $this->db_master()->last_query();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    public function insert_tutor_like($param)
    {
        $this->db_connect('master');
        
        $this->db_master()->trans_start();

        $sql = "SELECT * FROM tutor_like WHERE tu_uid = ? AND uid = ?";
        $res = $this->db_master()->query($sql, array($param['tu_uid'], $param['uid']));   
        if($res->num_rows() > 0) return 1;

        $this->db_master()->insert('tutor_like',$param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }
    

    public function delete_tutor_like($where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->delete('tutor_like',$where);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    public function tutor_like_info($tu_uid,$uid)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT count(1) as cnt 
                        FROM `tutor_like` 
                        WHERE tu_uid = %d AND uid= %d", $tu_uid, $uid);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function select_tutors_inclass($uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT tu_uid,tu_name FROM wiz_lesson 
            WHERE tu_name NOT IN ('corrections','postpone') AND '".date("Y-m-d")."'  BETWEEN startday AND endday AND uid = ? GROUP BY tu_uid";

        $res = $this->db_slave()->query($sql,array($uid));
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function select_tutors()
    {
        $this->db_connect('slave');
        
        $sql = "SELECT tu_uid,tu_name FROM wiz_tutor 
            WHERE del_yn = 'n' AND tu_name NOT IN ('test_good', 'postpone', 'corrections') AND tu_uid NOT IN (905, 906) ORDER BY tu_name ASC";

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function select_tutors_sim_content($table_code)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mb_unq,sim_content FROM mint_boards WHERE table_code= ? and  noticeYn='A' and sim_content!='' order by mb_unq desc limit 1";

        $res = $this->db_slave()->query($sql,array($table_code));
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }



    public function get_tu_name_in_tu_uid($tu_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wt.tu_name,wt.tu_uid FROM wiz_tutor wt
        WHERE wt.tu_uid IN ?";
        $res = $this->db_slave()->query($sql, array($tu_uid));       
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    public function check_tutor_star_evaluated($uid, $tu_uid, $mb_unq)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT 1 FROM tutor_star WHERE uid= ? AND tu_uid = ? AND mb_unq = ? ";

        $res = $this->db_slave()->query($sql,array($uid, $tu_uid, $mb_unq));
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function check_tutor_star_evaluated_by_mb_unq_board($uid, $mb_unq_board)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT 1 FROM tutor_star WHERE uid= ? AND mb_unq_board = ? ";

        $res = $this->db_slave()->query($sql,array($uid, $mb_unq_board));
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function check_correct_tutor($tu_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT * FROM wiz_tutor WHERE tu_uid = ? AND del_yn = 'n' AND correct_yn != 'N' ";

        $res = $this->db_slave()->query($sql,array($tu_uid));
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /* 강사 - 수업 불가능한 일자 체크 */
    public function check_tutor_blockdate($tu_uid, $startdate, $enddate)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT tu_uid, startday, endday, memo FROM wiz_tutor_blockdate WHERE tu_uid = ? AND startday >= ? AND endday <= ?";

        $res = $this->db_slave()->query($sql,array($tu_uid, $startdate, $enddate));
        //echo $this->db_slave()->last_query();
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /* 강사 - 수업 불가능한 일자 체크2
        date가 휴가 시작일, 종료일 사이에 껴있는지 체크
    */
    public function check_tutor_blockdate_day($tu_uid, $date)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT tu_uid, startday, endday, memo FROM wiz_tutor_blockdate WHERE tu_uid = ? AND startday <= ? AND endday >= ? limit 1";

        $res = $this->db_slave()->query($sql,array($tu_uid, $date, $date));
        //echo $this->db_slave()->last_query();
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /* 강사 - 수업 불가능한 시간 체크 */
    public function check_tutor_breakingtime($tu_uid, $startdate, $enddate)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wtb.* FROM wiz_tutor_breakingtime wtb WHERE wtb.tu_uid = ? AND wtb.date >= ? AND wtb.date <= ?";

        $res = $this->db_slave()->query($sql,array($tu_uid, $startdate, $enddate));
        //echo $this->db_slave()->last_query();
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    /* 강사 - 수업 가능한 요일, 시간 */
    public function list_tutor_weekend_by_tu_uid($tu_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wtw.* FROM wiz_tutor_weekend wtw WHERE wtw.tu_uid = ? ORDER BY wtw.week ASC";

        $res = $this->db_slave()->query($sql,array($tu_uid));
    
        return $res->num_rows() > 0 ? $res->result_array() : NULL;

    }
    
    /* 강사 - 특정 기간 스케쥴 */
    public function list_tutor_schedule_by_tu_uid($tu_uid, $startdate, $enddate, $day_of_class)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT 
                    SUBSTRING_INDEX(ws.startday, ' ', 1) as startday_date,
                    SUBSTRING_INDEX(ws.startday, ' ', -1) as startday_time,
                    SUBSTRING_INDEX(ws.endday, ' ', 1) as endday_date,
                    SUBSTRING_INDEX(ws.endday, ' ', -1) as endday_time,
                    weekday(ws.startday) as weekday,
                    ws.* 
                FROM wiz_schedule ws 
                WHERE 
                    ws.tu_uid = ? 
                    AND ws.startday BETWEEN ? AND ? 
                    AND weekday(ws.startday) IN (?)
                ORDER BY ws.startday ASC";

        $res = $this->db_slave()->query($sql,array($tu_uid, $startdate, $enddate, $day_of_class));

     

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_schedule_by_uid($where, $group, $order, $limit)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT ws.sc_id, ws.lesson_id, ws.present, min(ws.startday) AS startday, 
                        wt.tu_uid, wt.tu_name, wt.tu_fullname, wt.web_profile, wt.tu_pic_main, wt.tu_pic, wt.con_pic, wt.del_yn
                        FROM wiz_schedule ws
                        INNER JOIN wiz_tutor wt ON ws.tu_uid = wt.tu_uid
                        %s %s %s %s", $where, $group, $order, $limit);

        $res = $this->db_slave()->query($sql);       
        // echo $this->db_slave()->last_query();
        // exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_count_schedule($where, $group)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT count(sc_id) as cnt FROM (
                            SELECT ws.sc_id, ws.lesson_id, wt.tu_uid
                            FROM wiz_schedule ws
                            INNER JOIN wiz_tutor wt ON ws.tu_uid = wt.tu_uid
                            %s %s 
                        ) as tbl
                        ", $where, $group);

        $res = $this->db_slave()->query($sql);       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 강사 스케쥴 상세보기 - 수업 정보
     */
    public function detail_schedule_lesson($lesson_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wm.wiz_id as wm_wiz_id, wm.regi_gubun as wm_regi_gubun, wm.social_email as wm_social_email, wm.uid as wm_uid, 
                       wm.ename as wm_ename, wm.name as wm_name, wm.mobile as wm_mobile, wm.pmobile as wm_pmobile, wm.d_id as wm_d_id,
                       wm.gender as wm_gender, wm.birth as wm_birth,
                       wlt.content as wlt_content, wd.d_ename as wm_d_ename,
                       wl.lesson_id as wl_lesson_id, wl.lesson_gubun as wl_lesson_gubun, wl.tel as wl_tel, wl.mobile as wl_mobile, wl.skype as wl_skype,
                       wl.student_su as wl_student_su, wl.student_uid as wl_student_uid, wl.cl_name as wl_cl_name, wl.cl_number as wl_cl_number,
                       wl.lev_gubun as wl_lev_gubun, wl.lev_name as wl_lev_name, wl.renewal_ok as wl_renewal_ok, wl.renewal_reason as wl_renewal_reason,
                       wl.weekend as wl_weekend, wl.startday as wl_startday, wl.endday as wl_endday, wl.conti as wl_conti,
                       wml.greeting_yn as wml_greeting_yn, wml.speed_slowly_yn as wml_speed_slowly_yn, wml.focus_book_yn as wml_focus_book_yn,
                       wml.feedback_inclass_yn as wml_feedback_inclass_yn, wml.content as wml_content,
                       wl.tt_1 as wl_tt_1, wl.tt_2 as wl_tt_2, wl.tt_3 as wl_tt_3, wl.tt_4 as wl_tt_4, wl.tt_5 as wl_tt_5, wl.tt_6 as wl_tt_6,
                       wl.tt_7 as wl_tt_7, wl.tt_8 as wl_tt_8, wl.tt_add as wl_tt_add, wl.cl_class as wl_cl_class, wl.cl_service as wl_cl_service,
                       wl.refund_ok as wl_refund_ok, wl.lesson_state as wl_lesson_state, wl.schedule_ok as wl_schedule_ok, wl.newlesson_ok as wl_newlesson_ok
                FROM wiz_lesson as wl
                LEFT JOIN wiz_lesson_text as wlt ON wlt.lesson_id = wl.lesson_id
                LEFT JOIN wiz_member as wm ON wm.uid = wl.uid
                LEFT JOIN wiz_member_lessontype as wml ON wml.uid = wl.uid AND wml.lesson_id = wl.lesson_id
                LEFT JOIN wiz_dealer as wd ON wd.d_id=wm.d_id
                WHERE wl.lesson_id = ". $lesson_id;
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    /**
     * 강사 스케쥴 상세보기 - 스케쥴 정보
     */
    public function detail_schedule($lesson_id, $sc_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.sc_id as ws_sc_id, ws.lesson_gubun as ws_lesson_gubun, ws.tel as ws_tel, ws.mobile as ws_mobile, ws.skype as ws_skype,
                       ws.startday as ws_startday, ws.endday as ws_endday, ws.present as ws_present, ws.cl_time as ws_cl_time, ws.kind as ws_kind,
                       ws.topic as ws_topic, ws.tu_uid as ws_tu_uid, wt.tu_name as ws_tu_name, ws.ab_ok as ws_ab_ok,
                       wsr.topic_date as wsr_topic_date, wsr.topic_previous as wsr_topic_previous, wsr.topic_today as wsr_topic_today,
                       wsr.topic_next as wsr_topic_next, wsr.stu_info2 as wsr_stu_info2, wsr.absent_reason as wsr_absent_reason,  wsr.rating_ls as wsr_rating_ls,
                       wsr.rating_ss as wsr_rating_ss, wsr.rating_pro as wsr_rating_pro, wsr.rating_voc as wsr_rating_voc, wsr.rating_cg as wsr_rating_cg,
                       wsr.pronunciation as wsr_pronunciation, wsr.grammar as wsr_grammar, wsr.comment as wsr_comment, wsr.scr_id as wsr_scr_id
                FROM wiz_schedule as ws
                LEFT JOIN wiz_tutor as wt ON wt.tu_uid = ws.tu_uid
                LEFT JOIN wiz_schedule_result as wsr ON wsr.sc_id = ws.sc_id
                WHERE ws.sc_id = '".$sc_id."' AND ws.lesson_id = ". $lesson_id." AND ws.present Not IN (7,8,9) ORDER BY ws.startday LIMIT 1";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 강사 스케쥴 상세보기 - 이전,다음 수업 정보
     */
    public function prev_next_schedule($where)
    {
        $this->db_connect('slave');

        $sql = "SELECT sc_id, topic
                FROM wiz_schedule
                WHERE ".$where." LIMIT 1";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 강사 스케쥴 상세보기 - 클래스메이트 정보 가져오기
     */
    public function classmate_info($group_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT uid as cm_uid, wiz_id as cm_wiz_id, name as cm_name, ename as cm_ename, tel as cm_tel, mobile as cm_mobile
                FROM wiz_member
                WHERE uid IN ($group_uid)";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 강사 스케쥴 상세보기 - 민트영어라이브 미팅 키 가져오기 //사용하지않음
     */
    public function webex_meeting_key($sc_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT host_url, meeting_key
                FROM wiz_schedule_webex
                WHERE sc_id = ".$sc_id." order by sc_cnt_idx desc LIMIT 1";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 강사 스케쥴 상세보기 - 교재 정보 가져오기
     */
    public function schedule_book_info($lesson_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wbh.book_id as wb_book_id, wbh.book_name as wb_book_name,
                       wb.book_link as wb_book_link, wb.book_link3 as wb_book_link3, wb.new_link as wb_new_link,
                       mcb.book_page AS mcb_last_class_page
                FROM wiz_bookhistory as wbh
                LEFT JOIN wiz_book as wb ON wb.book_id = wbh.book_id
                LEFT JOIN mint_class_book as mcb ON wbh.bh_id = mcb.bookhistory_id
                WHERE wbh.lesson_id = ".$lesson_id." ORDER BY wbh.regdate DESC LIMIT 1";
    
        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 강사 스케쥴 상세보기 - 말톡 녹화파일 조회
     */
    public function checked_maalk_history_result($sc_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT 
                    ws.sc_id AS ws_sc_id, ws.startday AS ws_startday, ws.endday AS ws_endday, ws.mobile as ws_mobile, 
                    wl.mobile as wl_mobile, wm.mobile as wm_mobile, wm.ename as wm_ename, ws.tu_uid AS ws_tu_uid
                FROM wiz_schedule ws
                LEFT OUTER JOIN wiz_lesson wl ON ws.lesson_id = wl.lesson_id
                INNER JOIN wiz_member wm ON ws.uid = wm.uid
                WHERE ws.sc_id = ?";
                
        $res = $this->db_slave()->query($sql, array($sc_id));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_schedule_info_for_chk($sc_id,$lesson_id,$startday)
    {
        $this->db_connect('slave');

        $sql = "SELECT sc_id
                FROM wiz_schedule
                WHERE sc_id <> ".$sc_id." AND lesson_id = ".$lesson_id." AND startday > '".$startday."' AND present != '8'
                ORDER BY startday LIMIT 1";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function get_schedule_info_for_startday_chk($lesson_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT min(startday) as min_startday, max(startday) as max_startday
                FROM wiz_schedule
                WHERE lesson_id = ".$lesson_id." AND present IN (1,2,3,4,7)";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function get_maaltalk_note_log_for_chk($sc_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT mnl_key
                FROM maaltalk_note_log
                WHERE state = 2 AND sc_id = ".$sc_id;
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 강사 스케쥴 상세보기 업데이트 - 수업상태 변경 로그 남기기
     */
    public function insert_maaltalk_note_log($article)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert("maaltalk_note_log", $article);

        // echo $this->db_master()->last_query();exit;
        $insert_id = $this->db_master()->insert_id();
        

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    /**
     * SMS 전송에 필요한 정보 가져오기 - 회원정보, 딜러정보
     */
    public function get_wiz_member_by_wiz_dealer($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wm.d_id as wm_d_id, wm.pmobile as wm_pmobile,
                       wd.schedule_yn as wd_schedule_yn, wd.d_name as wd_d_name, wd.sms_receive as wd_sms_receive, wd.dea_tel as wd_dea_tel
                FROM wiz_member as wm
                LEFT JOIN wiz_dealer as wd ON wd.d_id=wm.d_id
                WHERE wm.uid = '".$uid."'";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    /**
     * SMS 회신번호 가져오기
     */
    public function get_wiz_config()
    {
        $this->db_connect('slave');

        $sql = "SELECT send_number
                FROM wiz_config
                LIMIT 1";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_tutor_like($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT tl.like_id, tl.tu_uid, tl.uid, tl.regdate, tsl.average_total, 
                wt.tu_name, wt.tu_fullname, wt.web_profile, wt.tu_pic_main, wt.tu_pic, wt.con_pic, wt.del_yn
                FROM tutor_like tl 
                INNER JOIN wiz_tutor wt ON tl.tu_uid = wt.tu_uid
                LEFT OUTER JOIN tutor_star_log tsl ON tl.tu_uid = tsl.tu_uid
                %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);       

        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_count_tutor_like_by_uid($uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(tl.like_id) as cnt
                FROM tutor_like tl 
                WHERE tl.uid = ?";

        $res = $this->db_slave()->query($sql,array($uid));
        // echo $this->db_slave()->last_query();
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function check_tutor_absent_date($tu_uid, $date)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT 1 FROM wiz_tutor_absent WHERE tu_uid= ? AND absent_date=? ";

        $res = $this->db_slave()->query($sql,array($tu_uid, $date));
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function check_tutor_working_hour($tu_uid, $date_w, $date_w_tomorrow)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wtw.t0,wtw.t1,wtw.t2,wtw.t3,wtw.t4,wtw.t5,wtw.t6,wtw.t7,wtw.t8,wtw.t9,wtw.t10,wtw.t11,wtw.t12,wtw.t13,wtw.t14,
                wtw.t15,wtw.t16,wtw.t17,wtw.t18,wtw.t19,wtw.t20,wtw.t21,wtw.t22,wtw.t23, tt.t24 as t24 
                FROM wiz_tutor_weekend wtw JOIN wiz_tutor wt ON wtw.tu_uid=wt.tu_uid
                LEFT OUTER JOIN (select t0 as t24, tu_uid, week from wiz_tutor_weekend ) tt ON tt.tu_uid=wt.tu_uid and tt.week= ?
                where wtw.tu_uid= ? and wtw.week= ?";

        $res = $this->db_slave()->query($sql,array($date_w_tomorrow, $tu_uid, $date_w));
        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /*
        alldays
        0: 특정일 브레이크, 그 외는 상시브레이크
        1: 평일, 4:공휴일  -> 1,4는 안쓰는것으로 확인
        5:월, 6:화, 7:수, 8:목, 9:금, 2:토, 3:일
        상시와 특정일 브레이크 둘다 구한다.
        특정일 브레이크는 내일 0시것도 구해야 하기 때문에 date 를 두개 받는다
    */
    public function list_tutor_breaking($tu_uid, $date, $date2)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT tu_uid, date, time, alldays FROM wiz_tutor_breakingtime WHERE tu_uid = ? AND ( (alldays=0 AND (date= ? OR date= ?) ) OR alldays IN (5,6,7,8,9,2,3))";

        $res = $this->db_slave()->query($sql,array($tu_uid, $date, $date2));
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 강사 공지사항 리스트
     */
    public function list_tutor_notice_count($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                        FROM mint_notice_boards as mnb
                        %s", $where);
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function list_notice_board($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT mnb.nb_unq as mnb_nb_unq, mnb.tu_id as mnb_tu_id, mnb.title as mnb_title, mnb.filedir as mnb_filedir,
                               mnb.filename as mnb_filename, mnb.content as mnb_content, mnb.hit as mnb_hit, mnb.regdate as mnb_regdate
                        FROM mint_notice_boards as mnb
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    /**
     * 강사 공지사항 정보
     */
    public function writer_notice_board($nb_unq)
    {
        $this->db_connect('slave');

        $sql = "SELECT mnb.nb_unq as mnb_nb_unq, mnb.tu_id as mnb_tu_id, mnb.title as mnb_title, mnb.writer_name as mnb_writer_name, mnb.filedir as mnb_filedir,
                       mnb.filename as mnb_filename, mnb.content as mnb_content, mnb.hit as mnb_hit, mnb.regdate as mnb_regdate
                FROM mint_notice_boards as mnb
                WHERE mnb.nb_unq = ". $nb_unq;
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 강사와 1:1게시판 리스트
     */
    public function list_tutor_toteacher_count($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                        FROM wiz_toteacher as wt
                        %s", $where);
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function list_toteacher_board($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT wt.to_id as wt_to_id, wt.uid as wt_uid, wt.name as wt_name, wt.ename as wt_ename, wt.tu_uid as wt_tu_uid, 
                               wt.title as wt_title, wt.filename as wt_filename, wt.filename2 as wt_filename2, wt.filename3 as wt_filename3, wt.filename4 as wt_filename4,
                               wt.step as wt_step, wt.to_gubun as wt_to_gubun, wt.regdate as wt_regdate, wt.replydate as wt_replydate
                        FROM wiz_toteacher as wt
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    /**
     * 강사와 1:1게시판 정보
     */
    public function writer_toteacher_board($tu_uid, $to_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wt.to_id as wt_to_id, wt.uid as wt_uid, wt.name as wt_name, wt.ename as wt_ename, wt.tu_uid as wt_tu_uid, wt.c_yn as wt_c_yn, wt.r_yn as wt_r_yn,
                       wt.title as wt_title, wt.filename as wt_filename, wt.filename2 as wt_filename2, wt.filename3 as wt_filename3, wt.filename4 as wt_filename4,
                       wt.memo as wt_memo, wt.reply as wt_reply, wt.step as wt_step, wt.to_gubun as wt_to_gubun, wt.regdate as wt_regdate, wt.replydate as wt_replydate,
                       wm.wiz_id as wt_wiz_id, wm.mobile as wt_mobile, wm.regi_gubun as wt_regi_gubun, wm.social_email as wt_social_email
                FROM wiz_toteacher as wt
                LEFT JOIN wiz_member as wm ON wt.uid = wm.uid
                WHERE wt.tu_uid = ".$tu_uid." AND wt.to_id = ".$to_id;
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 강사와 매니저 리스트
     */
    public function list_tutor_mantutor_count($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                        FROM wiz_mantutor mt left join wiz_schedule s on s.sc_id=mt.sc_id
                        %s", $where);
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function list_mantutor_board($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT ifnull(s.startday, mt.viewdate) as view_date , mt.to_id as mt_to_id, mt.tu_uid as mt_tu_uid, mt.tu_name as mt_tu_name,
                               mt.man_id as mt_man_id, mt.man_ename as mt_man_ename, mt.writer_gubun as mt_writer_gubun, mt.title as mt_title, mt.memo as mt_memo,
                               mt.reply as mt_reply, mt.replydate as mt_replydate, mt.filename as mt_filename, mt.regdate as mt_regdate
                        FROM wiz_mantutor mt left join wiz_schedule s on s.sc_id=mt.sc_id
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 강사와 매니저 정보
     */
    public function writer_mantutor_board($tu_uid, $to_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT ifnull(s.startday, mt.viewdate) as view_date , mt.to_id as mt_to_id, mt.tu_uid as mt_tu_uid, mt.tu_name as mt_tu_name,
                       mt.man_id as mt_man_id, mt.man_ename as mt_man_ename, mt.writer_gubun as mt_writer_gubun, mt.title as mt_title, mt.memo as mt_memo,
                       mt.reply as mt_reply, mt.replydate as mt_replydate, mt.filename as mt_filename, mt.regdate as mt_regdate, mt.view as mt_view,
                       mt.sc_id as mt_sc_id, s.lesson_id as mt_lesson_id
                FROM wiz_mantutor as mt
                LEFT JOIN wiz_schedule as s on s.sc_id=mt.sc_id
                WHERE mt.tu_uid = ".$tu_uid." AND mt.to_id = ".$to_id;
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 강사 비밀번호를 가져온다
     */
    public function get_tutor_pw($tu_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT tu_pw as wt_tu_pw
                FROM wiz_tutor as wt
                WHERE wt.tu_uid = ".$tu_uid;
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /*
        강사 정보 수정
    */
    public function update_tutor($comment, $where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->update("wiz_tutor", $comment);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    /**
     * 회원 정보 가져오기
     */
    public function get_member($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wm.uid as wm_uid, wm.wiz_id as wm_wiz_id, wm.name as wm_name, wm.ename as wm_ename, wm.mobile as wm_mobile
                FROM wiz_member as wm
                WHERE uid = ".$uid;
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /*
        강사와 1:1 게시판 메세지 등록
    */
    public function write_message($comment)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert("wiz_toteacher", $comment);

        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    /**
     * 강사와 1:1 게시판 전송 학생 목록 가져오기
     */
    public function list_message_student($where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT wl.uid as wl_uid
                        FROM wiz_lesson as wl
                        %s", $where);
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /*
        강사와 1:1 게시판 메세지 수정
    */
    public function update_message($comment, $where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->update("wiz_toteacher", $comment);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    /**
     * 강사와 1:1 게시판 메세지 정보 가져오기
     */
    public function writer_message($to_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT t.*, m.mobile
                FROM wiz_toteacher t, wiz_member m
                WHERE t.uid = m.uid  AND t.to_id = ".$to_id;
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /*
        강사와 매니저 글 등록
    */
    public function write_mantutor($comment)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert("wiz_mantutor", $comment);

        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    /*
        강사와 매니저 수정
    */
    public function update_mantutor($comment, $where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->update("wiz_mantutor", $comment);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    /**
     * 공지사항 댓글 정보
     */
    public function list_notice_comment($nb_unq, $tu_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT mnb.nb_sub_unq as mnb_nb_sub_unq, mnb.title as mnb_title, mnb.comment as mnb_comment
                FROM mint_notice_boards_sub as mnb
                WHERE mnb.nb_unq = ".$nb_unq." AND mnb.tu_id = '".$tu_id."'";

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /*
        공지사항 댓글 등록
    */
    public function insert_notice_comment($comment)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert("mint_notice_boards_sub", $comment);

        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }
    /*
        공지사항 댓글 삭제
    */
    public function delete_notice_comment($where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->delete('mint_notice_boards_sub', $where);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    /*
        공지사항 댓글 수정
    */
    public function update_notice_comment($comment, $where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->update("mint_notice_boards_sub", $comment);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    /**
     * 학생 목록을 가져온다
     */
    public function list_student_count($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                        FROM wiz_lesson as wl
                        %s", $where);
                                
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function list_student($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT wl.name as wl_name, wl.ename as wl_ename, wl.uid as wl_uid, wl.wiz_id as wl_wiz_id, wl.tel as wl_tel, wl.mobile as wl_mobile, 
                               wl.uid as wl_uid, wl.lev_gubun as wl_lev_gubun, wl.lev_name as wl_lev_name, wl.startday as wl_startday, wl.endday as wl_endday,
                               wl.stime as wl_stime, wl.tt as wl_tt, wl.tt_2 as wl_tt_2, wl.tt_3 as wl_tt_3, wl.tt_4 as wl_tt_4,
                               wl.lesson_id as wl_lesson_id, wl.book_name as wl_book_name,
                               wm.regi_gubun as wl_regi_gubun, wm.social_email as wl_social_email
                        FROM wiz_lesson as wl
                        LEFT JOIN wiz_member as wm ON wm.uid = wl.uid
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
                                                                                                                                                                                       
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 학생 목록을 가져온다
     * select box 용
     */
    public function seletcbox_list_student($tu_uid)
    {
        $this->db_connect('slave');
        
        $now = date('Y-m-d');
        $sql = "SELECT wl.name as wl_name, wl.ename as wl_ename, wl.uid as wl_uid, wl.wiz_id as wl_wiz_id
                FROM wiz_lesson as wl
                WHERE wl.tu_uid = ".$tu_uid." AND '".$now."' BETWEEN wl.startday AND wl.endday 
                GROUP BY wl.uid";

        $res = $this->db_slave()->query($sql);
                                                                                                                                                                                       
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 교재 목록을 가져온다
     */
    public function list_textbooks($where)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT wb.book_id as wb_book_id, wb.book_step as wb_book_step, wb.book_name as wb_book_name, wb.f_id as wb_f_id,
                               wb.manual as wb_manual, wb.sample_mp3 as wb_sample_mp3, wb.book_link as wb_book_link, wb.book_link3 as wb_book_link3
                        FROM wiz_book as wb
                        %s
                        ORDER BY wb.book_step,wb.sort ASC", $where);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    /**
     * 인센티브 리스트
     */
    public function list_incentive_count($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT COUNT(1) as cnt
                        FROM mint_incentive AS mi
                        LEFT JOIN wiz_member as wm ON mi.uid = wm.uid
                        %s", $where);
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function list_incentive($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT mi.in_kind as mi_in_kind, mi.kind as mi_kind, mi.money as mi_money, mi.regdate as mi_regdate,
                               wt.tu_id as mi_tu_id, wt.tu_name as mi_tu_name,
                               wm.wiz_id as mi_wiz_id, wm.ename as mi_ename, wm.regi_gubun as mi_regi_gubun, wm.social_email as mi_social_email
                        FROM mint_incentive AS mi 
                        LEFT JOIN wiz_tutor as wt ON mi.tu_uid = wt.tu_uid
                        LEFT JOIN wiz_member as wm ON mi.uid = wm.uid
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 강사 Student Count
     */
    public function list_student_change_count($where, $tu_uid)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT COUNT(1) as total_cnt, 
                               COUNT(CASE WHEN wtc.b_tuid=".$tu_uid." THEN 1 END) AS in_cnt,
                               COUNT(CASE WHEN wtc.a_tuid=".$tu_uid." THEN 1 END) AS out_cnt
                        FROM wiz_tutor_change as wtc
                        %s", $where);
                        
        $res = $this->db_slave()->query($sql);
        //echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function list_student_change($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT wl.ename as wtc_ename, wl.wiz_id as wtc_wiz_id, wmm.regi_gubun as wtc_regi_gubun, wmm.social_email as wtc_social_email,
                               wtc.a_tutor as wtc_a_tutor, wtc.b_tutor as wtc_b_tutor,
                               wtc.startday as wtc_startday, wtc.endday as wtc_endday, wm.man_ename as wtc_man_ename, wtc.man_name as wtc_man_name,
                               wtc.class_su as wtc_class_su, wtc.kind as wtc_kind, wtc.regdate as wtc_regdate
                        FROM wiz_tutor_change as wtc
                        LEFT JOIN wiz_manager as wm ON wtc.man_id = wm.man_id
                        LEFT JOIN wiz_lesson as wl ON wtc.lesson_id = wl.lesson_id
                        LEFT JOIN wiz_member as wmm ON wl.wiz_id = wmm.wiz_id
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    /**
     * 강사 학생 변경 리스트 - 총학생수
     */
    public function lesson_student_count($tu_uid)
    {
        $this->db_connect('slave');
    
        $now = date("Y-m-d");
        $sql = "SELECT COUNT(1) AS cnt
                        FROM wiz_lesson
                        WHERE tu_uid=? AND cl_gubun='1' AND endday>='".$now."' AND refund_ok='N'";
                        
        $res = $this->db_slave()->query($sql, array($tu_uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * mset history list
     */
    public function list_mset_history_count($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                        FROM wiz_tutor_mset m LEFT JOIN wiz_tutor t ON m.tu_uid=t.tu_uid
                        %s", $where);
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function list_mset_history($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT SQL_CALC_FOUND_ROWS m.*, t.tu_name
                        FROM wiz_tutor_mset m LEFT JOIN wiz_tutor t ON m.tu_uid=t.tu_uid
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    public function checked_tutor_break_temp($tu_uid, $day, $time)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(*) as cnt
                FROM wiz_tutor_breakingtime 
                WHERE tu_uid = ? AND alldays=0 AND date = ? AND time = ?";

        $res = $this->db_slave()->query($sql,array($tu_uid, $day, $time));
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function insert_wiz_tutor_breakingtime($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert("wiz_tutor_breakingtime", $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function delete_wiz_tutor_breakingtime($where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->delete("wiz_tutor_breakingtime");

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    //강사가 브레이크 걸 시간에 스케쥴 이미 잡혀있는지 확인
    public function checked_tutor_break_possible_time($tu_uid, $datetime)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(*) as cnt
                FROM wiz_schedule 
                WHERE tu_uid = ? AND present IN (1, 2) AND ? >= startday AND ? <= endday LIMIT 1";

        $res = $this->db_slave()->query($sql,array($tu_uid, $datetime, $datetime));
        //echo $this->db_slave()->last_query();
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 월간 보고서 - DUE
     */
    public function list_monthly_reports_due_count($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                        FROM wiz_lesson as wl
                        %s", $where);
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function list_monthly_reports_due($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT wm.wiz_id as wl_wiz_id, wm.regi_gubun as wl_regi_gubun, wm.social_email as wl_social_email,
                               wl.lesson_id as wl_lesson_id, wl.ename as wl_ename, wl.startday as wl_startday, wl.endday as wl_endday, wl.stime as wl_stime 
                        FROM wiz_lesson as wl
                        LEFT JOIN wiz_member as wm ON wm.uid = wl.uid
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 월간 보고서 - DUE 상세보기
     */
    public function writer_monthly_reports_due($lesson_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wm.wiz_id as wl_wiz_id, wm.regi_gubun as wl_regi_gubun, wm.social_email as wl_social_email, wm.uid as wl_uid, wm.name as wl_name,
                       wl.lesson_id as wl_lesson_id, wl.cl_name as wl_cl_name, wl.cl_number as wl_cl_number, wl.cl_time as wl_cl_time, wl.lesson_gubun as wl_lesson_gubun, wl.tu_name as wl_tu_name,
                       wl.stime as wl_stime, wl.report_num as wl_report_num,
                       wl.lev_gubun as wl_lev_gubun, wl.lev_name as wl_lev_name, wl.ename as wl_ename, wl.tel as wl_tel, wl.mobile as wl_mobile, wl.book_name as wl_book_name, wl.conti as wl_conti
                FROM wiz_lesson as wl
                LEFT JOIN wiz_member as wm ON wm.uid = wl.uid
                WHERE wl.lesson_id = ". $lesson_id;
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    /**
     * 월간 보고서 - DUE report 등록
     */
    public function insert_monthly_reports_due($param, $lesson_id, $startDay, $endDay, $report_num)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->insert('wiz_report', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        else
        {
            //정상적으로 등록되었을경우
            $this->db_master()->trans_start();
            
            // 스케쥴에서 report_num ++
            $this->db_master()->set('report_num', $report_num, FALSE);
            $this->db_master()->where("lesson_id = ".$lesson_id." AND startday BETWEEN '".$startDay."' AND '".$endDay."'");
            $this->db_master()->update("wiz_schedule");

            // 강의정보에서 report_num + 1
            $this->db_master()->set('report_num', '`report_num` + 1', FALSE);
            $this->db_master()->set('report_app', '2', FALSE);
            $this->db_master()->where('lesson_id', $lesson_id);
            $this->db_master()->update("wiz_lesson");

            $this->db_master()->trans_complete();
        }

        return 1;
    }

    /**
     * 월간 보고서 - DUE , 첫번째평가일, 마지막평가일을 가져온다
     */
    public function reports_evaluation_period($lesson_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT MIN(startday) AS sp, MAX(startday) AS ep
                       FROM wiz_schedule
                       WHERE lesson_id = ".$lesson_id." AND present BETWEEN 2 AND 4 ORDER BY startday LIMIT 1";

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 월간 보고서 - DUE , 스케쥴에서 해당 기간동안의 출석정보를 뽑아온다
     */
    public function reports_present_rate($lesson_id, $startDay, $endDay)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT sum(IF(present='2', 1, 0)) as wl_present, sum(IF(present='3', 1, 0)) as wl_absent, sum(IF(present='4', 1, 0)) as wl_cancel,
                       sum(IF(present='5', 1, 0)) as wl_no_class, sum(IF(present='6', 1, 0)) as wl_hold, sum(IF(present='7', 1, 0)) as wl_long_hold
                       FROM wiz_schedule as ws
                       WHERE ws.lesson_id = ".$lesson_id." AND ws.startday BETWEEN '".$startDay."' AND '".$endDay."'";

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    /**
     * 월간 보고서 - COMPLETE
     */
    public function list_monthly_reports_complete_count($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                        FROM wiz_report as wr
                        %s", $where);
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function list_monthly_reports_complete($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT wm.wiz_id as wr_wiz_id, wm.regi_gubun as wr_regi_gubun, wm.social_email as wr_social_email,
                               wr.re_id as wr_re_id, wr.ename as wr_ename, wr.re_start as wr_re_start, wr.re_end as wr_re_end, wr.regdate as wr_regdate, wr.modifydate as wr_modifydate
                        FROM wiz_report as wr
                        LEFT JOIN wiz_member as wm ON wm.uid = wr.uid
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 월간 보고서 - COMPLETE 상세보기
     */
    public function writer_monthly_reports_complete($re_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wm.wiz_id as wl_wiz_id, wm.regi_gubun as wl_regi_gubun, wm.social_email as wl_social_email,
                       wr.tt_2 as wl_present, wr.tt_3 as wl_absent, wr.tt_4 as wl_cancel, wr.tt_5 as wl_no_class, wr.tt_6 as wl_hold, wr.tt_7 as wl_long_hold,
                       wr.lesson_id as wl_lesson_id, wr.re_start as wl_startday, wr.re_end as wl_endday, wr.uid as wl_uid,
                       wr.listening as wl_listening, wr.speaking as wl_speaking, wr.pronunciation as wl_pronunciation, wr.vocabulary as wl_vocabulary, wr.grammar as wl_grammar,
                       wr.ev_memo as wl_ev_memo, wr.gra_memo as wl_gra_memo,
                       wl.cl_name as wl_cl_name, wl.cl_number as wl_cl_number, wl.cl_time as wl_cl_time, wl.lesson_gubun as wl_lesson_gubun, wl.tu_name as wl_tu_name,
                       wl.lev_gubun as wl_lev_gubun, wl.lev_name as wl_lev_name, wl.ename as wl_ename, wl.tel as wl_tel, wl.mobile as wl_mobile, wl.book_name as wl_book_name, wl.conti as wl_conti
                FROM wiz_report as wr
                LEFT JOIN wiz_member as wm ON wm.uid = wr.uid
                LEFT JOIN wiz_lesson as wl ON wl.lesson_id = wr.lesson_id
                WHERE wr.re_id = ". $re_id;
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function tutor_list_board_wiz_correct($index, $where, $order, $limit , $inner_table = " ")
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT 
                            mb.w_id as mb_w_id, mb.sc_id as mb_sc_id, mb.uid as mb_uid, mb.wiz_id as mb_wiz_id, mb.name as mb_name, mb.ename as mb_ename,
                            mb.tu_uid as mb_tu_uid, mb.tu_name as mb_tu_name, mb.w_title as mb_w_title, mb.w_kind as mb_w_kind,
                            mb.w_mp3 as mb_w_mp3, mb.w_tutor as mb_w_tutor, mb.w_memo as mb_content, mb.w_reply as mb_reply,
                            mb.clip_yn as mb_config_clip_yn, mb.chk_tu_uid as mb_chk_tu_uid, mb.w_mp3_type as mb_w_mp3_type,
                            mb.w_step as mb_w_step, mb.w_secret as mb_w_secret, mb.w_view as mb_hit, mb.w_regdate as mb_w_regdate,
                            mb.w_hopedate as mb_w_hopedate, mb.w_replydate as mb_w_replydate, mb.filename as mb_tutor_upfile, mb.filename2 as mb_student_upfile,
                            mb.mob as mb_mob, mb.rsms as mb_rsms, mb.star as mb_star, mb.su as mb_su, mb.recom as mb_recom ,mb.name as mb_name, mb.ename as mb_ename,
                            wm.regi_gubun AS wm_regi_gubun, wm.email AS wm_email, wm.social_email AS wm_social_email
                        FROM wiz_correct mb %s
                        LEFT OUTER JOIN wiz_member wm ON mb.uid = wm.uid
                        %s
                    %s %s %s",$index, $inner_table, $where, $order, $limit);

        $res = $this->db_slave()->query($sql);  
        
        // echo $this->db_slave()->last_query();exit;
        

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    public function all_mint_tutor_pay($tu_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM mint_tutor_pay as mtp WHERE tu_uid = ? ORDER BY app_date ASC";
    
        $res = $this->db_slave()->query($sql, array($tu_uid));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    public function sum_tutor_incentive($tu_uid, $sdate, $edate, $addwhere='')
    {
        $this->db_connect('slave');

        $sql = "SELECT SUM(money) as money, COUNT(*) as cnt
                FROM mint_incentive
                WHERE in_gubun='T' AND tu_uid= ? AND in_yn='y' AND regdate BETWEEN ? AND ? ".$addwhere;
    
        $res = $this->db_slave()->query($sql, array($tu_uid, $sdate, $edate));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    

    /**
     * 월간 보고서 - COMPLETE report 수정
     */
    public function update_monthly_reports_complete($param, $where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->update("wiz_report", $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function maaltalk_tutor_url_info($tu_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mntu.tutor_url as mntu_tutor_url FROM maaltalk_note_tutor_url as mntu WHERE mntu.tu_uid = ?";

        $res = $this->db_slave()->query($sql, array($tu_uid));  
        // echo $this->db_slave()->last_query(); exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    /**
     * 강사 스케쥴 캘린더 - 수업 정보
     */
    public function calendar_lesson($lesson_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wm.wiz_id as wl_wiz_id, wm.social_email as wl_social_email, wm.regi_gubun as wl_regi_gubun,
                       wl.cl_name as wl_cl_name, wl.cl_number as wl_cl_number, wl.cl_time as wl_cl_time, wl.lesson_gubun as wl_lesson_gubun,
                       wl.ename as wl_ename, wl.tu_name as wl_tu_name, wl.book_name as wl_book_name, wl.weekend as wl_weekend,
                       wl.startday as wl_startday, wl.endday as wl_endday, wl.lev_gubun as wl_lev_gubun, wl.lev_name as wl_lev_name
                FROM wiz_lesson as wl
                LEFT JOIN wiz_member as wm ON wm.uid = wl.uid
                WHERE wl.lesson_id = ". $lesson_id;
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 강사 스케쥴 캘린더 - 스케쥴 정보
     */
    public function calendar_schedule($lesson_id, $tu_uid, $startDay, $endDay)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.startday as ws_startday, ws.endday as ws_endday, ws.present as ws_present, ws.sc_id as ws_sc_id,
                       ws.tu_name as ws_tu_name, ws.bogang_ok as ws_bogang_ok
                FROM wiz_schedule as ws
                WHERE tu_uid = '".$tu_uid."' AND ws.lesson_id = ". $lesson_id." AND ws.startday between '".$startDay."' AND '".$endDay."' AND present Not IN (8,9)";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * MSET 평가 보기
     */
    public function row_mset_report($sc_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT ws.uid as ws_uid, ws.startday as ws_startday, ws.endday as ws_endday, ws.present as ws_present, wt.tu_id as wt_tu_id, wt.tu_name as wt_tu_name,
                       mr.idx as mr_idx, mr.sc_id as mr_sc_id, mr.examiner_job as mr_examiner_job, mr.mset_gubun as mr_mset_gubun, mr.examiner_name as mr_examiner_name, mr.tel as mr_tel, mr.mobile as mr_mobile, mr.english_name as mr_english_name,
                       mr.pronunciation_level as mr_pronunciation_level, mr.pronunciation_description as mr_pronunciation_description, mr.pronunciation_description_add as mr_pronunciation_description_add, mr.pronunciation_advice as mr_pronunciation_advice, mr.pronunciation_advice_add as mr_pronunciation_advice_add, mr.pronunciation_comment as mr_pronunciation_comment,
                       mr.fluency_level as mr_fluency_level, mr.fluency_description as mr_fluency_description, mr.fluency_description_add as mr_fluency_description_add, mr.fluency_advice as mr_fluency_advice, mr.fluency_advice_add as mr_fluency_advice_add, mr.fluency_comment as mr_fluency_comment,
                       mr.vocabulary_level as mr_vocabulary_level, mr.vocabulary_description as mr_vocabulary_description, mr.vocabulary_description_add as mr_vocabulary_description_add, mr.vocabulary_advice as mr_vocabulary_advice, mr.vocabulary_advice_add as mr_vocabulary_advice_add, mr.vocabulary_comment as mr_vocabulary_comment,
                       mr.speaking_level as mr_speaking_level, mr.speaking_description as mr_speaking_description, mr.speaking_description_add as mr_speaking_description_add, mr.speaking_advice as mr_speaking_advice, mr.speaking_advice_add as mr_speaking_advice_add, mr.speaking_comment as mr_speaking_comment,
                       mr.grammar_level as mr_grammar_level, mr.grammar_description as mr_grammar_description, mr.grammar_description_add as mr_grammar_description_add, mr.grammar_advice as mr_grammar_advice, mr.grammar_advice_add as mr_grammar_advice_add, mr.grammar_comment as mr_grammar_comment,
                       mr.listening_level as mr_listening_level, mr.listening_description as mr_listening_description, mr.listening_description_add as mr_listening_description_add, mr.listening_advice as mr_listening_advice, mr.listening_advice_add as mr_listening_advice_add, mr.listening_comment as mr_listening_comment,
                       mr.function_level as mr_function_level, mr.function_description as mr_function_description, mr.function_description_add as mr_function_description_add, mr.function_advice as mr_function_advice, mr.function_advice_add as mr_function_advice_add, mr.function_comment as mr_function_comment,
                       mr.overall_score as mr_overall_score, mr.overall_total as mr_overall_total, mr.overall_level_message as mr_overall_level_message, mr.overall_level as mr_overall_level, mr.status as mr_status,
                       mr.overall_level_message as mr_overall_level_message, mr.overall_description as mr_overall_description, mr.overall_description_add as mr_overall_description_add, mr.overall_comment as mr_overall_comment,
                       wm.wiz_id as wm_wiz_id, wm.name as wm_name, wm.mobile as wm_mobile, wm.uid as wm_uid, wm.d_id as wm_d_id, wm.email as wm_email, wm.gender as wm_gender, wm.lev_gubun as wm_lev_gubun, wm.birth as wm_birth,
                       mep.tutor_pdf as mep_tutor_pdf, mep.student_pdf as mep_student_pdf, mep.student_jpg as mep_student_jpg, mep.exam_paper_name as mep_exam_paper_name, wd.d_ename as wd_d_ename
                FROM mint_mset_report as mr
                LEFT JOIN wiz_schedule as ws ON ws.sc_id=mr.sc_id AND ws.lesson_id=100000001
                LEFT JOIN wiz_tutor as wt ON wt.tu_uid=mr.tu_uid
                LEFT JOIN wiz_member as wm ON wm.uid=mr.uid
                LEFT JOIN mint_mset_exam_paper as mep ON mep.idx=mr.exam_idx
                LEFT JOIN wiz_dealer as wd ON wd.d_id=wm.d_id
                WHERE mr.sc_id=".$sc_id;
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * MSET 평가정보 목록 추출
     */
    public function get_result_list_mest()
    {
        $this->db_connect('slave');

        $sql = "SELECT *
                FROM mint_mset_evaluation";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * MSET 평가 정보 업데이트시 회원 레벨정보 업데이트
     */
    public function update_mset_level_for_member($uid, $level)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        //마지막 무료 MSET 신청/완료 날짜 추출
        $sql = "SELECT startday FROM mint_mset_report WHERE uid='".$uid."' AND status IN ('0','1','2','5') AND use_point='0' ORDER BY startday DESC LIMIT 1";
        $tmp = $this->db_master()->query($sql);       
        $startDay = $tmp->row_array();

        //무료 MSET 완료횟수 추출
        $sql = "SELECT COUNT(*) as cnt FROM mint_mset_report WHERE uid='".$uid."' AND status IN ('2', '5') AND use_point='0'";
        $tmp = $this->db_master()->query($sql);       
        $iCount = $tmp->row_array();

        //업데이트
        $article = array(
            'last_mset_date'  => $startDay['startday'] ? substr($startDay['startday'], 0, 10) : '0000-00-00',
            'free_mset_count' => $iCount['cnt'] ? $iCount['cnt'] : 0
        );
        if($level) $article['mset'] = $level;

        $this->db_master()->where(array('uid'=>$uid));
        $this->db_master()->update('wiz_member', $article);
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }
    public function row_schedule_by_sc_id($sc_id)
    {
        $this->db_connect('slave');

        $sql = " SELECT wm.pmobile AS wm_pmobile, ws.mobile AS ws_mobile, ws.startday AS ws_startday, ws.endday AS ws_endday, wl.student_su,
                wm.uid AS wm_uid, wm.wiz_id AS wm_wiz_id, wm.name AS wm_name, wm.ename AS wm_ename, wl.student_uid AS wl_student_uid, ws.lesson_id as ws_lesson_id
                FROM wiz_schedule ws
                LEFT OUTER JOIN wiz_lesson wl ON ws.lesson_id = wl.lesson_id
                LEFT OUTER JOIN wiz_member wm ON ws.uid = wm.uid 
                WHERE sc_id = ?";

        $res = $this->db_slave()->query($sql, array($sc_id));
        
        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function get_wiz_member_by_wm_uid($wm_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wm.uid AS wm_uid, wm.wiz_id as wm_wiz_id, wm.mobile AS wm_mobile, wm.ename AS wm_ename, wm.name AS wm_name
                FROM wiz_member wm
                WHERE wm.uid = ?";
        $res = $this->db_slave()->query($sql, array($wm_uid));       
        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    /**
     * 레벨테스트 리스트
     */
    public function list_wiz_leveltest($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wlt.le_start as wlt_le_start, wlt.lev_gubun as wlt_lev_gubun, wlt.lev_name as wlt_lev_name,
                       wlt.le_step as wlt_le_step, wlt.sc_id as wlt_sc_id
                FROM wiz_leveltest as wlt
                WHERE wlt.uid='".$uid."'
                ORDER BY wlt.le_id DESC";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    public function tutor_admin_log_check($tu_uid, $auth_code)
    {
        $this->db_connect('master');

        $sql = "SELECT `au_id` FROM `auth_data` WHERE `code`='".$auth_code."' AND `type`='teacher_login' AND `disabled`=0";
        $res = $this->db_master()->query($sql);
        $row = $res->num_rows() > 0 ? $res->row_array() : NULL;

        if(!$row) return false;

        $sql = "SELECT tu_id, tu_pw FROM wiz_tutor WHERE tu_uid= ?";
        $res = $this->db_master()->query($sql, array($tu_uid));
        $tu_row = $res->num_rows() > 0 ? $res->row_array() : NULL;

        if(!$tu_row) return false;

        $this->db_master()->trans_start();

        // disabled 0->1 신강사 로그인
        $this->db_master()->where('au_id',$row['au_id']);
        $this->db_master()->set('disabled',1 );
        $this->db_master()->update('auth_data');  

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return false;
        }

        return $tu_row;
    }

    
    public function all_tutor()
    {
        $this->db_connect('slave');

        $sql = "SELECT wt.tu_uid as wt_tu_uid, wt.tu_id as wt_tu_id, wt.pay_type as wt_pay_type
                FROM wiz_tutor as wt WHERE wt.del_yn='n'";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function insert_trace_tutor_log($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('trace_tutor_log',$param);  

        /* if (rand(0, 1000) == 0) 
        {
            $this->db_master()->where('regdate < ',date("Y-m-d 00:00:00",strtotime('-1 month')));
            $this->db_master()->delete('trace_tutor_log');
        } */

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function check_mint_incentive_by_tuuid_uid_kind_inyn($tu_uid, $uid, $kind, $in_yn)
    {
        $this->db_connect('slave');

        $sql = "SELECT 1 FROM mint_incentive WHERE tu_uid= ? AND uid= ? AND in_kind= ? AND in_yn= ? ";
    
        $res = $this->db_slave()->query($sql, array($tu_uid, $uid, $kind, $in_yn));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    /**
     * 북미 강사 DRB 리스트
     */
    public function list_drb_count($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                        FROM mint_drb as md
                        %s", $where);
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function list_drb($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT md.*, wc.co_company
                        FROM mint_drb as md
                        LEFT JOIN wiz_company as wc ON wc.co_uid = md.md_company
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    public function list_drb_comment_count($md_id)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT count(1) as cnt
                FROM mint_drb
                WHERE md_parents_id='".$md_id."' AND md_is_comment='1'";
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 북미 강사 DRB 정보
     */
    public function writer_drb($tu_uid, $md_id)
    {
        $this->db_connect('master');

        $sql = "SELECT md.*, wc.co_company
                FROM mint_drb as md
                LEFT JOIN wiz_company as wc ON wc.co_uid = md.md_company
                WHERE (md.md_receiver_uid = ".$tu_uid." OR md.md_receiver_uid = 0 OR md.md_writer_uid = ".$tu_uid.") AND md_is_comment='0' AND md.md_id = ".$md_id;
    
        $res = $this->db_master()->query($sql);

        $article = $res->row_array();

        if($article)
        {
            $this->db_master()->set('md_hit', $article['md_hit'] + 1, FALSE);
            $this->db_master()->where('md_id', $md_id);           
            $this->db_master()->update('mint_drb');
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return NULL;
        }

        return $res->num_rows() > 0 ? $article : NULL;
    }

    /**
     * 북미 강사 DRB 코맨트 리스트
     */
    public function list_drb_comment($md_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT md.*
                FROM mint_drb as md
                WHERE md_parents_id='".$md_id."' AND md_is_comment='1' ORDER BY md_id ASC";

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * 북미 강사 DRB 글쓰기
     */
    public function write_drb($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert("mint_drb", $param);

        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    /**
     * 북미 강사 DRB 글수정
     */
    public function update_drb($param, $where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->update("mint_drb", $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    /**
     * 북미 강사 DRB 글삭제
     */
    public function delete_drb($tu_uid, $md_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('md_id' => $md_id, 'md_writer_uid'=>$tu_uid));
        $this->db_master()->delete('mint_drb');
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

}










