<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Badge_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function list_badge($where,$join_where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT wb.*,wmb.use_yn as wmb_use_yn,wmb.regdate as wmb_regdate FROM wiz_badge AS wb
                    LEFT JOIN wiz_member_badge AS wmb ON wb.id=wmb.badge_id %s %s",$join_where, $where);
                
        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function list_count_badge($where,$join_where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT count(1) AS cnt FROM wiz_badge AS wb
                    LEFT JOIN wiz_member_badge AS wmb ON wb.id=wmb.badge_id %s %s",$join_where, $where);
                
        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function get_user_badge_count($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) AS cnt
                FROM wiz_member_badge wmb
                INNER JOIN wiz_badge wb ON wmb.badge_id = wb.id 
                WHERE uid = ".$uid;
                
        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }
    
    public function get_badge($where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT wb.id AS wb_id, wb.title AS wb_title, wb.description AS wb_description
                        FROM wiz_badge wb
                        %s", $where);
                
        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function get_user_badge($where, $join_where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT wmb.badge_id, wmb.use_yn as wmb_use_yn,wmb.regdate as wmb_regdate, wb.description, wb.type, wb.type2
                        FROM wiz_member_badge AS wmb 
                        INNER JOIN wiz_badge AS wb ON wmb.badge_id = wb.id
                        %s %s",$join_where, $where);
                
        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function update_use_badge($badge_id, $uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('uid', $uid);
        $this->db_master()->set('use_yn', 'N');
        $this->db_master()->update('wiz_member_badge');

        if($badge_id){
            $this->db_master()->where('uid', $uid);
            $this->db_master()->where('badge_id', $badge_id);
            $this->db_master()->set('use_yn', 'Y');
            $this->db_master()->update('wiz_member_badge');
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        $wiz_member = $this->member_mdl->get_wiz_member_by_wm_uid($uid);

        return $wiz_member;
    }
    

    public function row_badge_info($type, $type2)
    {
        $this->db_connect('slave');

        $sql = "SELECT wb.id as wb_id, wb.title as wb_title, wb.description as wb_description, wb.award_message as wb_award_message 
                FROM wiz_badge wb
                WHERE wb.type = ? AND wb.type2 = ? ";

        $res = $this->db_slave()->query($sql, array($type, $type2));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function check_count_cafeboards($count)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM (
            SELECT count(mc.uid) AS cnt, uid FROM mint_cafeboard mc GROUP BY uid 
            ) AS tbl WHERE tbl.cnt > ? ";

        $res = $this->db_slave()->query($sql, array($count));       
        
        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    public function insert_batch_badge($datas)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->insert_batch('wiz_member_badge', $datas);
        // echo $this->db_master()->last_query();
        // exit;
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function insert_badge($datas)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->insert('wiz_member_badge', $datas);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    
    public function insert_badge_message($datas, $badge_award_message)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->insert('wiz_member_badge', $datas);

        // 뱃지지급시 알림메시지 있는 경우만 알림 전송
        if($badge_award_message)
        {
            // 뱃지지급 알림
            $notify = array(
                'uid' => $datas['uid'], 
                'code' => 500,
                'user_name' => 'SYSTEM',
                'message' => $badge_award_message,  
                'regdate' => date('Y-m-d H:i:s'),
            );

            $this->db_master()->insert('mint_notify', $notify);  
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

}










