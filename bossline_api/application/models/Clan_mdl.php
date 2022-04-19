<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Clan_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();
    }

    public function get_clan_list($type, $where)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    bc.type, bc.clan_name, bc.clan_level, bc.recruit_type, bc.server, 
                    bc.job, bc.level, bc.defense, bc.description, bc.welfare, bc.reg_date,
                    bs.name AS server_name
                FROM bl_clan bc
                LEFT OUTER JOIN bl_server bs ON bc.server = bs.id
                WHERE bc.type = ? AND pay_yn = 'N' ".$where;

        $res = $this->db_slave()->query($sql, array($type));   

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->result() : NULL;
    }

    public function get_clan_list_count($type, $where)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) as count FROM bl_clan WHERE type = ? AND pay_yn = 'N' ".$where;

        $res = $this->db_slave()->query($sql, array($type));   
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_clan_pay_list($type)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    bc.type, bc.clan_name, bc.clan_level, bc.recruit_type, bc.server, 
                    bc.job, bc.level, bc.defense, bc.description, bc.welfare, bc.reg_date,
                    bs.name AS server_name
                FROM bl_clan bc 
                LEFT OUTER JOIN bl_server bs ON bc.server = bs.id
                WHERE bc.type = ? AND pay_yn = 'Y'";

        $res = $this->db_slave()->query($sql, array($type));   

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function get_clan_pay_list_count($type)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) as count FROM bl_clan WHERE type = ? AND pay_yn = 'Y'";

        $res = $this->db_slave()->query($sql, array($type));   

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    public function insert_clan($clan)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('bl_clan', $clan);

        // echo $this->db_master()->last_query();exit;
        
        $insert_id = $this->db_master()->insert_id();
        
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function get_clan_info_by_request($request)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM bl_clan WHERE user_id = ? AND email = ? AND type = ? ";
        
        $res = $this->db_slave()->query($sql, array($request['user_pk'], $request['email'], $request['type']));   
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function modify_clan($clan)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('clan_name', $clan['clan_name']);
        $this->db_master()->set('clan_level', $clan['clan_level']);
        $this->db_master()->set('recruit_type', $clan['recruit_type']);
        $this->db_master()->set('server', $clan['server']);
        $this->db_master()->set('job', $clan['job']);
        $this->db_master()->set('level', $clan['level']);
        $this->db_master()->set('defense', $clan['defense']);
        $this->db_master()->set('description', $clan['description']);
        $this->db_master()->set('welfare', $clan['welfare']);
        $this->db_master()->set('recruit_yn', $clan['recruit_yn']);

        $this->db_master()->where('user_id', $clan['user_id']);
        $this->db_master()->where('email', $clan['email']);
        $this->db_master()->where('type', $clan['type']);

        $this->db_master()->update('bl_clan');

        $this->db_master()->trans_complete();

        // echo $this->db_master()->last_query();exit;

        if ($this->db_master()->trans_status() === FALSE)
        {
            return NULL;
        }

        return 1;

    }
    
    


}










