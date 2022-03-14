<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Objection_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    function list_objection_reason($where='')
    {
        $this->db_connect('slave');
    
        $sql = "SELECT * FROM mint_objection_reason_list ".$where;

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    function count_objection($where='')
    {
        $this->db_connect('slave');
    
        $sql = "SELECT count(*) as cnt FROM mint_objection as mo ".$where;

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function insert_objection($param, $claims)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_objection', $param);

        $insert_id = $this->db_master()->insert_id();

        if($insert_id && is_array($claims))
        {
            foreach($claims as $idx)
            {
                $this->db_master()->insert('mint_objection_claims', [
                    'ob_idx'     => $insert_id,
                    'claims_idx' => $idx,
                ]);
            }
        }
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function list_objection($where, $order, $limit, $select_col_content='')
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mo.ob_idx as mo_ob_idx, mo.type as mo_type, mo.state as mo_state, mo.title as mo_title, mo.regdate as mo_regdate, 
                        mo.complete_date as mo_complete_date %s
                        FROM mint_objection as mo 
                %s %s %s", $select_col_content, $where, $order, $limit);

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function update_objection($objection, $ob_idx, $claims)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('ob_idx' => $ob_idx));
        $this->db_master()->update('mint_objection', $objection);

        if($ob_idx && is_array($claims))
        {
            $this->db_master()->where(array('ob_idx' => $ob_idx));
            $this->db_master()->delete('mint_objection_claims');

            foreach($claims as $idx)
            {
                $this->db_master()->insert('mint_objection_claims', [
                    'ob_idx'     => $ob_idx,
                    'claims_idx' => $idx,
                ]);
            }
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function row_objection($idx)
    {
        $this->db_connect('slave');

        $sql = "SELECT mo.ob_idx as mo_ob_idx, mo.uid as mo_uid, mo.type as mo_type, mo.state as mo_state, mo.title as mo_title, mo.regdate as mo_regdate, 
                        mo.complete_date as mo_complete_date, mo.content as mo_content, mo.claims_etc as mo_claims_etc, mo.lesson_id as mo_lesson_id,
                        mo.sc_id as mo_sc_id, mo.mset_idx as mo_mset_idx, mo.table_code as mo_table_code, mo.mb_unq as mo_mb_unq, mo.correction_idx as mo_correction_idx,
                        mo.movie_url as mo_movie_url, mo.file_path as mo_file_path, mo.file_name as mo_file_name, mo.receive_sms as mo_rsms
                FROM mint_objection as mo
                WHERE mo.ob_idx = ?";

        $res = $this->db_slave()->query($sql, array($idx));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    function selected_objection_reason($idx)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT morl.type as morl_type, morl.korean as morl_korean, morl.english as morl_english, morl.idx as morl_idx, moc.claims_idx as moc_claims_idx
                FROM mint_objection_claims as moc
                LEFT JOIN mint_objection_reason_list as morl ON moc.claims_idx=morl.idx 
                WHERE moc.ob_idx = ? ORDER BY morl.idx DESC";

        $res = $this->db_slave()->query($sql, array($idx));
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    public function checked_next_report($cur_idx, $wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT ob_idx,regdate,title FROM mint_objection WHERE ob_idx > ? AND uid = ? ORDER BY ob_idx ASC Limit 1";

        $res = $this->db_slave()->query($sql, array($cur_idx, $wm_uid));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function checked_prev_report($cur_idx, $wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT ob_idx,regdate,title FROM mint_objection WHERE ob_idx < ? AND uid = ? ORDER BY ob_idx DESC Limit 1";

        $res = $this->db_slave()->query($sql, array($cur_idx, $wm_uid));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function delete_objection($idx)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('ob_idx', $idx);
        $this->db_master()->delete('mint_objection');

        $this->db_master()->where('ob_idx', $idx);
        $this->db_master()->delete('mint_objection_claims');
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    
}










