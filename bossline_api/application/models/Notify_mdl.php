<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Notify_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }


    public function get_dealer_sms_by_wm_uid($wm_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT d.d_sms FROM wiz_member wm JOIN wiz_dealer wd ON wm.d_id = wd.d_id WHERE wm.uid= ? LIMIT 1";
        $res = $this->db_slave()->query($sql, array($wm_uid));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;

    }

    public function insert_notify($notify)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->insert('mint_notify', $notify);
        // echo $this->db_master()->last_query();exit;

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function disabled_notify($notify, $where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
 
        $this->db_master()->where($where);
        $this->db_master()->update('mint_notify', $notify);
        //echo $this->db_master()->last_query();exit;
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    
    public function disabled_notify_where_in($notify, $where, $where_in)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->where_in($where_in);
        $this->db_master()->update('mint_notify', $notify);


        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    public function checked_notify_by_wm_uid($wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt
                FROM mint_notify mn
                WHERE mn.uid = ? AND mn.removed = 0 AND mn.view = 0";

      
        $res = $this->db_slave()->query($sql, array($wm_uid));

        

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function checked_msg_by_wm_uid($wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt, mn.sender_nickname
                FROM mint_note mn
                WHERE mn.receiver_uid = ? AND mn.receiver_type = 'MEMBER' AND mn.read_at IS NULL AND mn.receiver_del_at IS NULL ORDER BY mn.id desc";

      
        $res = $this->db_slave()->query($sql, array($wm_uid));


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }



}










