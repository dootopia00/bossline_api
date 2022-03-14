<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Grade_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }


    public function list_grade($where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT * FROM mint_member_grade %s", $where);
                
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    
    public function insert_member_grade_history($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_member_grade_history', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

}










