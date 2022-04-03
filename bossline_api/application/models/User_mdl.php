<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class User_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();
    }

    public function get_user_id($user_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM bl_user WHERE user_id = ?";

        $res = $this->db_slave()->query($sql, array($user_id));   

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function insert_user($user)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('bl_user', $user);

        // echo $this->db_master()->last_query();exit;

        $insert_id = $this->db_master()->insert_id();
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }
    

}










