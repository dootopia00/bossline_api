<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Banner_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function list_banner($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT mp.nidx, mp.szmemo, mp.popup_link, mp.popup_target, mp.nstartdate, mp.nenddate, mp.nday, mp.category,
                                mp.banner_location, mp.img_class, mp.img_alt, mp.szview_pc, mp.szview_mobile
                        FROM mint_popup mp
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;

    }

    public function insert_banner_click_count($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->insert('mint_popup_click',$param);
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    
}










