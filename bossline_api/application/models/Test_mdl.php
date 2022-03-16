<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Test_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function get_test_table()
    {

        $this->db_connect('slave');
        
        $sql = "SELECT * FROM test_table ";
    
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
}










