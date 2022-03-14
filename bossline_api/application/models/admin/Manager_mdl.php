<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Manager_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function login($admin_id, $admin_pw)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT wma.man_id as wma_man_id, wma.man_uid as wma_man_uid, wma.man_lev as wma_man_lev, wma.man_name as wma_man_name, wma.man_ename as wma_man_ename, 
                    wma.man_email as wma_man_email, wma.man_tel as wma_man_tel, wma.man_mobile as wma_man_mobile, wma.logdate as wma_logdate, wma.man_nickname as wma_man_nickname 
                FROM wiz_manager as wma
                WHERE wma.man_id = ? AND wma.man_pw = ? AND wma.del_yn = 'N'";
        $res = $this->db_master()->query($sql, array($admin_id, $admin_pw));
        $wiz_admin = $res->row_array();

        if($wiz_admin)
        {
            $this->db_master()->set('logdate', 'now()', FALSE);
            $this->db_master()->where('man_id', $wiz_admin['wma_man_id']);
            $this->db_master()->update('wiz_manager');
            
            $this->db_master()->set('lesson_state', 'finished');
            $this->db_master()->where('endday <', date('Y-m-d'));
            $this->db_master()->where('lesson_state ', 'in class');
            $this->db_master()->update('wiz_lesson');
            
            $this->db_master()->set('report_app', '1');
            $this->db_master()->where('((cl_number * 4) * (report_num+1)) <=', 'tt_2+tt_3+tt_4', FALSE);
            $this->db_master()->where('report_app is null');
            $this->db_master()->update('wiz_lesson');

            $this->db_master()->where('log_date_time <', time() - (86400*365));
            $this->db_master()->where('man_id', $wiz_admin['wma_man_id']);
            $this->db_master()->delete('wiz_manager_log');
            
            $this->db_master()->insert('wiz_manager_log',[
                'man_id'        => $wiz_admin['wma_man_id'],
                'log_ip'        => $_SERVER["REMOTE_ADDR"],
                'log_date_time' => time(),
                'log_date_ymd'  => date('Y-m-d H:i:s'),
                'reg_date'      => date('Y-m-d'),
            ]);
        }
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return NULL;
        }

        return $wiz_admin ? $wiz_admin : NULL;
    }

}










