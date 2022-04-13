<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Clan_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();
    }

    public function get_clan_list($type)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM bl_clan WHERE type = ?";

        $res = $this->db_slave()->query($sql, array($type));   

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_clan_list_count($type)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) FROM bl_clan WHERE type = ?";

        $res = $this->db_slave()->query($sql, array($type));   

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function insert_clan($clan)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('bl_clan', $clan);

        print_r($this->db_master()->last_query());exit;
        echo $this->db_master()->last_query();exit;
        
        $insert_id = $this->db_master()->insert_id();
        
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function get_clan_info_by_pk($pk)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM bl_clan WHERE id = ?";

        $res = $this->db_slave()->query($sql, array($pk));   

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function modify_clan($request)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('clan_name', $request['clan_name']);
        $this->db_master()->set('clan_level', $request['clan_level']);
        $this->db_master()->set('recruit', $request['recruit']);
        $this->db_master()->set('server', $request['server']);
        $this->db_master()->set('level', $request['level']);
        $this->db_master()->set('defense', $request['defense']);
        $this->db_master()->set('job', $request['job']);
        $this->db_master()->set('description', $request['description']);
        $this->db_master()->set('welfare', $request['welfare']);
        $this->db_master()->where('id', $request['clan_pk']);
        $this->db_master()->update('bl_clan');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return NULL;
        }

        return 1;

    }
    
    


}










