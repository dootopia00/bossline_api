<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Event_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function list_event($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT e.e_id, e.e_banner, e.e_name, e.e_con, e.e_on, e.e_kind, e.e_detail_content, e.e_link
                        FROM mint_event e
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function view_event($where)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT e.e_id, e.e_banner, e.e_name, e.e_con, e.e_on, e.e_kind, e.e_detail_content, e.e_link
                        FROM mint_event e
                        %s", $where);

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /* 말톡노트 베타테스트 참여 신청 */
    public function beta_maaltalk_note($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('beta_maaltalk_note', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;

    }
}










