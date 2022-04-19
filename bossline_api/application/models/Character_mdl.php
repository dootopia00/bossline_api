<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Character_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();
    }

    
    public function get_character_info_by_request($request)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM bl_character WHERE user_id = ? AND email = ? AND type = ? ";
        
        $res = $this->db_slave()->query($sql, array($request['user_pk'], $request['email'], $request['type']));   
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    


    public function modify_clan($character)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT * FROM bl_character WHERE user_id = ? AND email = ? AND type = ?";

        $res = $this->db_slave()->query($sql, array($character['user_id'], $character['email'], $character['type']));   
        
        $champ = $res->num_rows() > 0 ? $res->row_array() : NULL;

        if($champ == null){
        
            // insert
            
            $this->db_master()->insert('bl_character', $character);
            $insert_id = $this->db_master()->insert_id();

        }else{
            
            //update
            $this->db_master()->set('clan_name', $character['clan_name']);
            $this->db_master()->set('defense', $character['defense']);
            $this->db_master()->set('level', $character['level']);
            $this->db_master()->set('job', $character['job']);
            $this->db_master()->set('change', $character['change']);
    
            $this->db_master()->where('user_id', $character['user_id']);
            $this->db_master()->where('email', $character['email']);
            $this->db_master()->where('type', $character['type']);
    
            $this->db_master()->update('bl_character');
    
            $this->db_master()->trans_complete();
        } 

        // echo $this->db_master()->last_query();exit;

        if ($this->db_master()->trans_status() === FALSE)
        {
            return NULL;
        }

        return 1;

    }
    

}










