<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Goods_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function list_mint_goods_regular()
    {
        $this->db_connect('slave');

        $sql = "SELECT mg.g_id as mg_g_id, mg.l_gubun as mg_l_gubun, mg.l_name as mg_l_name, mg.l_month as mg_l_month, mg.l_hold as mg_l_hold,
                    mg.l_time as mg_l_time, mg.l_timeS as mg_l_timeS, mg.l_class as mg_l_class, mg.org_price as mg_org_price, mg.price as mg_price
                FROM mint_goods AS mg
                WHERE mg.goods_type = 1 ORDER BY mg.l_gubun, mg.l_month * 1, mg.l_time, mg.l_timeS";

        $res = $this->db_slave()->query($sql);  
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    public function row_mint_goods($g_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM mint_goods WHERE g_id = ?";

        $res = $this->db_slave()->query($sql, array($g_id));  
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function row_custom_goods($g_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wcd.*, wc.cl_time, wc.cl_number, wc.cl_class, wc.cl_month, wc.hold_num 
                FROM wiz_class_direct as wcd
                LEFT JOIN wiz_class as wc ON wcd.cl_id=wc.cl_id 
                WHERE wcd.cl_id = ?";

        $res = $this->db_slave()->query($sql, array($g_id));  
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function get_custom_goods($wiz_id, $today)
    {
        $this->db_connect('slave');

        $sql = "SELECT wcd.cl_id FROM wiz_class_direct as wcd 
                LEFT JOIN wiz_lesson as wl ON wcd.cl_id=wl.cl_id
                WHERE wl.lesson_id IS NULL AND wcd.wiz_id = ? AND wcd.sdate <= ? AND wcd.edate >= ? ORDER BY wcd.class_dir_id DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($wiz_id, $today, $today));  
        //echo $this->db_slave()->last_query();   exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function row_event_goods($g_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT meg.e_id as meg_e_id, meg.g_price as meg_g_price, meg.price as meg_price, meg.event_percent as meg_event_percent, 
                    me.d_id as me_d_id, me.e_kind as me_e_kind, me.e_on as me_e_on, mg.l_gubun as mg_l_gubun, me.e_name as me_e_name,
                    mg.l_timeS as mg_l_timeS, mg.l_month as mg_l_month, mg.l_time as mg_l_time, me.e_use as me_e_use, meg.g_hold as meg_g_hold,
                    mg.l_class as mg_l_class
                FROM mint_goods mg, mint_event me, mint_event_goods meg 
                WHERE mg.g_id = meg.g_id AND me.e_id = meg.e_id AND meg.uid =?";

        $res = $this->db_slave()->query($sql, array($g_id));  
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function row_event_info_by_e_id($e_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT meg.e_id as meg_e_id, me.e_name as me_e_name, me.e_kind as me_e_kind, me.d_id as me_d_id, me.e_use as me_e_use, me.e_on as me_e_on
                FROM mint_event_goods as meg
                LEFT JOIN mint_event as me ON meg.e_id=me.e_id
                WHERE meg.e_id = ? AND meg.g_use = 'y'";

        $res = $this->db_slave()->query($sql, array($e_id));  
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function event_goods_groupby_gubun($e_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM mint_goods as mg
                JOIN mint_event_goods as meg ON mg.g_id=meg.g_id 
                WHERE meg.e_id = ? AND mg.l_gubun != 'T' AND meg.g_use = 'y' 
                GROUP BY mg.l_gubun 
                ORDER BY mg.l_month, meg.uid ASC";

        $res = $this->db_slave()->query($sql, array($e_id));  
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    public function event_goods_list($e_id, $gubun)
    {
        $this->db_connect('slave');

        $sql = "SELECT meg.uid as meg_uid, meg.g_id as meg_g_id, meg.e_id as meg_e_id, meg.g_price as meg_g_price, meg.price as meg_price, meg.g_hold as meg_g_hold,
                    meg.event_percent as meg_event_percent,
                    mg.l_gubun as mg_l_gubun , mg.l_name as mg_l_name, mg.l_month as mg_l_month, mg.l_time as mg_l_time, mg.l_timeS as mg_l_timeS, 
                    mg.l_class as mg_l_class, mg.l_hold as mg_l_hold, mg.l_event as mg_l_event
                FROM mint_goods as mg
                JOIN mint_event_goods as meg ON mg.g_id=meg.g_id 
		        WHERE meg.e_id = ? AND meg.g_use = 'y' AND mg.l_gubun = ?
                ORDER BY mg.sort ASC";

        $res = $this->db_slave()->query($sql, array($e_id, $gubun));  
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
}










