<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class User_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();
    }

    public function get_user_id($user_id, $email)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM bl_user WHERE user_id = ? AND email = ?";

        $res = $this->db_slave()->query($sql, array($user_id, $email));   

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function insert_user($user)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('bl_user', $user);

        $insert_id = $this->db_master()->insert_id();
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        $sql = "SELECT * FROM bl_user WHERE id = ?";
        $res = $this->db_master()->query($sql, array($insert_id));
        
        // echo $this->db_master()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function update_user($user)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('tu_uid', '99999');

        $this->db_master()->where('user_id', $user['user_id']);
        $this->db_master()->where('email', $user['email']);
        $this->db_master()->update('bl_user');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

}










