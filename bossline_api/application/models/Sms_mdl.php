<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Sms_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function get_atalk_templete($code)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM atalk_templete WHERE code = ?";
        $res = $this->db_slave()->query($sql, array($code));

        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    
    public function insert_atalk($param)
    {
        $this->db_connect('atalk');

        $this->db_atalk()->trans_start();
        
        $this->db_atalk()->insert('ata_mmt_tran', $param);
        $insert_id = $this->db_atalk()->insert_id();

        // echo $this->db_atalk()->last_query();   
        // exit;

        $this->db_atalk()->trans_complete();

        if ($this->db_atalk()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }


    public function insert_atalk_log($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->insert('wiz_ata_log', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    public function get_sms_templete($code)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM mint_sms_conf WHERE sms_id = ?";
        $res = $this->db_slave()->query($sql, array($code));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function get_all_sms_templete()
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM mint_sms_conf ORDER BY sms_sort ASC";
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function insert_sms($param)
    {
        $this->db_connect('sms');

        $this->db_sms()->trans_start();
        
        $this->db_sms()->insert('MSG_DATA', $param);

        $this->db_sms()->trans_complete();

        if ($this->db_sms()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function insert_sms_content($param)
    {
        $this->db_connect('sms');

        $this->db_sms()->trans_start();
        
        $this->db_sms()->insert('MMS_CONTENTS_INFO', $param);
        $insert_id = $this->db_sms()->insert_id();

        $this->db_sms()->trans_complete();

        if ($this->db_sms()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function insert_sms_log($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->insert('wiz_call', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    
    public function insert_biztalk_sms($param)
    {
        $this->db_connect('biztalk');

        $this->db_biztalk()->trans_start();
        
        $this->db_biztalk()->insert('em_smt_tran', $param);
        $insert_id = $this->db_biztalk()->insert_id();

        $this->db_biztalk()->trans_complete();

        if ($this->db_biztalk()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    
    public function insert_biztalk_mms($param)
    {
        $this->db_connect('biztalk');

        $this->db_biztalk()->trans_start();
        
        $this->db_biztalk()->insert('em_mmt_tran', $param);
        $insert_id = $this->db_biztalk()->insert_id();

        $this->db_biztalk()->trans_complete();

        if ($this->db_biztalk()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    
    public function row_em_mmt_file_desc()
    {
        $this->db_connect('biztalk');

        $sql = "SELECT attach_file_group_key FROM em_mmt_file ORDER BY attach_file_group_key DESC LIMIT 1";
        $res = $this->db_biztalk()->query($sql);

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    
    public function insert_biztalk_em_mmt_file($param)
    {
        $this->db_connect('biztalk');

        $this->db_biztalk()->trans_start();
        
        $this->db_biztalk()->insert('em_mmt_file', $param);

        $this->db_biztalk()->trans_complete();

        if ($this->db_biztalk()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function get_atalk_log()
    {
        $this->db_connect('slave');

        $sql = "SELECT wal.*
                FROM wiz_ata_log as wal
                WHERE wal.report_code='0000'
                ORDER BY wal.al_uid DESC LIMIT 100";
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function update_atalk_log($al_uid, $report_code, $msg_status)
    {
        $this->db_connect('master');

        $this->db_master()->set('report_code', $report_code);
        $this->db_master()->set('msg_status', $msg_status);
        $this->db_master()->where(array('al_uid' => $al_uid));
        $this->db_master()->update('wiz_ata_log');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function row_ata_mmt_log($param)
    {
        $this->db_connect('atalk');

        $sql = "SELECT report_code, msg_status
                FROM ata_mmt_log_".$param['date']."
                WHERE mt_pr='".$param['mt_pr']."' AND recipient_num='".$param['recipient_num']."' AND template_code = '".$param['template_code']."'
                LIMIT 1";
        $res = $this->db_atalk()->query($sql);

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }


}










