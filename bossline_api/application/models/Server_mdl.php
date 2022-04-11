<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Server_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();
    }

    public function get_server_list($type)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM bl_server WHERE type = ?";

        $res = $this->db_slave()->query($sql, array($type));   

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function get_server_list_count($type)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) FROM bl_server WHERE type = ?";

        $res = $this->db_slave()->query($sql, array($type));   

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }



}










