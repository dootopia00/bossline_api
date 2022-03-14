<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Msg_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }


    public function list_count_receive_msg_by_wm_uid($wm_uid, $search)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT count(1) as cnt
                FROM mint_note mn
                WHERE mn.receiver_uid = ? AND mn.receiver_type = 'MEMBER' AND mn.receiver_save_at IS NULL AND mn.receiver_del_at IS NULL %s", $search);
                
        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }


    public function list_count_send_msg_by_wm_uid($wm_uid, $search)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT count(1) as cnt
                FROM mint_note mn
                WHERE mn.sender_uid = ? AND mn.sender_type = 'MEMBER'  AND mn.sender_save_at IS NULL AND mn.sender_del_at IS NULL %s", $search);
                

        $res = $this->db_slave()->query($sql, array($wm_uid));
    
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;

    }

    public function list_count_save_msg_by_wm_uid($wm_uid, $search)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT count(1) as cnt
                FROM mint_note mn
                WHERE 
                (mn.receiver_uid = ? AND mn.receiver_type = 'MEMBER'  AND mn.receiver_save_at IS NOT NULL AND mn.receiver_del_at IS NULL)
                OR 
                (mn.sender_uid = ? AND mn.sender_type = 'MEMBER'  AND mn.sender_save_at IS NOT NULL AND mn.sender_del_at IS NULL)  %s", $search);
                
        $res = $this->db_slave()->query($sql, array($wm_uid, $wm_uid));
    
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function list_count_block_by_wm_uid($wm_uid, $search)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT count(1) as cnt
                FROM mint_note_block mnb
                INNER JOIN wiz_member wm ON mnb.blocked_id = wm.uid %s
                WHERE mnb.blocker_id = ?  AND mnb.canceled_at IS NULL", $search);

        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function list_receive_msg_by_wm_uid($wm_uid, $order, $limit, $search)
    {

        $this->db_connect('slave');

        $sql = sprintf("SELECT mn.id as idx, mn.message, mn.created_at, mn.sender_nickname, mn.read_at, mn.sender_id, mn.receiver_id 
                FROM mint_note mn
                WHERE mn.receiver_uid = ? AND mn.receiver_type = 'MEMBER'  AND mn.receiver_save_at IS NULL AND mn.receiver_del_at IS NULL %s %s %s",$search, $order, $limit);

        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function count_receive_msg_by_admin($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(*) as cnt
                FROM mint_note as mn 
                LEFT JOIN mint_note_read as mtr ON mtr.note_id = mn.id AND mtr.user_id = ?
                WHERE mn.receiver_type = 'MEMBER' AND mtr.created_at IS NULL AND mn.is_notice = '1' AND mn.is_readable = '1'";

        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_receive_msg_by_admin($uid)
    {

        $this->db_connect('slave');

        $sql = "SELECT mn.id as idx, mn.message, mn.created_at, mn.sender_nickname, mn.receiver_id, 1 as notice,
                (SELECT created_at from mint_note_read WHERE user_id = ? AND note_id = idx) as read_at
                FROM mint_note as mn WHERE receiver_type = 'MEMBER' AND is_notice = '1' AND is_readable = '1'";

        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_send_msg_by_wm_uid($wm_uid, $order, $limit, $search)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mn.id as idx, mn.message, mn.created_at, mn.receiver_nickname, mn.read_at, mn.sender_id, mn.receiver_id 
                FROM mint_note mn
                WHERE mn.sender_uid = ? AND mn.sender_type = 'MEMBER'  AND mn.sender_save_at IS NULL AND mn.sender_del_at IS NULL %s %s %s",$search, $order, $limit);
                
        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function list_save_msg_by_wm_uid($wm_uid, $order, $limit, $search)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mn.id as idx, mn.message, mn.created_at, mn.sender_nickname, mn.receiver_nickname, mn.read_at, mn.sender_id, mn.receiver_id 
                FROM mint_note mn
                WHERE 
                (mn.receiver_uid = ? AND mn.receiver_type = 'MEMBER'  AND mn.receiver_save_at IS NOT NULL AND mn.receiver_del_at IS NULL)
                OR 
                (mn.sender_uid = ? AND mn.sender_type = 'MEMBER'  AND mn.sender_save_at IS NOT NULL AND mn.sender_del_at IS NULL) %s %s %s",$search, $order, $limit);

        $res = $this->db_slave()->query($sql, array($wm_uid,$wm_uid));

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function list_block_by_wm_uid($wm_uid, $order, $limit, $search)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mnb.id as idx, mnb.blocked_id, mnb.created_at, wm.nickname as wm_nickname
                FROM mint_note_block mnb
                INNER JOIN wiz_member wm ON mnb.blocked_id = wm.uid %s
                WHERE mnb.blocker_id = ? AND mnb.canceled_at IS NULL %s %s",$search, $order, $limit);
                
        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function canceled_block($idx, $wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->set('canceled_at', date('Y-m-d H:i:s'));
        $this->db_master()->where('blocker_id', $wm_uid);
        $this->db_master()->where_in('id', $idx);
        $this->db_master()->update('mint_note_block');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        return 1;
    }

    public function delete_send_msg($idx, $wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->set('sender_del_at', date('Y-m-d H:i:s'));
        $this->db_master()->where('sender_uid', $wm_uid);
        $this->db_master()->where_in('id', $idx);
        $this->db_master()->update('mint_note');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function delete_receive_msg($idx, $wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->set('receiver_del_at', date('Y-m-d H:i:s'));
        $this->db_master()->where('receiver_uid', $wm_uid);
        $this->db_master()->where('is_notice', 0);
        $this->db_master()->where_in('id', $idx);
        $this->db_master()->update('mint_note');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function delete_save_receive_msg($idx, $wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->set('receiver_del_at', date('Y-m-d H:i:s'));
        $this->db_master()->where('receiver_uid', $wm_uid);
        $this->db_master()->where_in('id', $idx);
        $this->db_master()->update('mint_note');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    public function delete_save_send_msg($idx, $wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->set('sender_del_at', date('Y-m-d H:i:s'));
        $this->db_master()->where('sender_uid', $wm_uid);
        $this->db_master()->where_in('id', $idx);
        $this->db_master()->update('mint_note');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function row_msg_by_wm_uid($idx, $wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $sql = " SELECT *
                FROM mint_note mn
                WHERE mn.id = ?";

        $res = $this->db_master()->query($sql, array($idx));
        $row = $res->num_rows() > 0 ? $res->row_array() : NULL;

        // 공지쪽지 일때
        if($row['is_notice'])
        {
            $sql = "SELECT * FROM mint_note_read WHERE user_id = ? AND note_id = ?";

            $res = $this->db_master()->query($sql, array($wm_uid, $idx));
            $read_check = $res->num_rows() > 0 ? $res->row_array() : NULL;

            if(!$read_check)
            {
                $this->db_master()->insert('mint_note_read', [
                    'user_id' => $wm_uid,
                    'note_id' => $idx,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $row['read_at'] = date('Y-m-d H:i:s');
            }
            else
            {
                $row['read_at'] = $read_check['created_at'];
            }
            

        }
        else
        {

            if(!$row['read_at'])
            {
                // 일반쪽지
                $this->db_master()->set('read_at', date('Y-m-d H:i:s'));     
                $this->db_master()->where('receiver_uid', $wm_uid);      
                $this->db_master()->where('id', $idx);    
                $this->db_master()->update('mint_note');
            }
                                                                                                                                                    
            $sql = " SELECT *
                    FROM mint_note mn
                    WHERE mn.id = ? AND (mn.sender_uid = ? OR mn.receiver_uid = ?)";

            $res = $this->db_master()->query($sql, array($idx, $wm_uid, $wm_uid));
        }
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return NULL;
        }

        if($row['is_notice'])
        {
            return $row;
        }
        else
        {
            return $res->num_rows() > 0 ? $res->row_array() : NULL;
        }
        
    }

    public function update_save_receive_msg($idx, $wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->set('receiver_save_at', date('Y-m-d H:i:s'));
        $this->db_master()->where('receiver_uid', $wm_uid);
        $this->db_master()->where('id', $idx);
        $this->db_master()->update('mint_note');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_unsave_receive_msg($idx, $wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->set('receiver_save_at', NULL);
        $this->db_master()->where('receiver_uid', $wm_uid);
        $this->db_master()->where('id', $idx);
        $this->db_master()->update('mint_note');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_save_send_msg($idx, $wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->set('sender_save_at', date('Y-m-d H:i:s'));
        $this->db_master()->where('sender_uid', $wm_uid);
        $this->db_master()->where('id', $idx);
        $this->db_master()->update('mint_note');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_unsave_send_msg($idx, $wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->set('sender_save_at', NULL);
        $this->db_master()->where('sender_uid', $wm_uid);
        $this->db_master()->where('id', $idx);
        $this->db_master()->update('mint_note');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function blocked_wiz_member($blocked)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        //이전에 동일한 사람 차단한적잇으면 다시 업데이트 
        $sql = "SELECT mnb.id FROM mint_note_block mnb WHERE mnb.blocked_id = ? AND mnb.blocker_id = ?";
        $tmp = $this->db_master()->query($sql, array($blocked['blocked_id'], $blocked['blocker_id']));       
        $history = $tmp->row_array();
       
        if($history)
        {
            $this->db_master()->set('created_at', date('Y-m-d H:i:s'));
            $this->db_master()->set('canceled_at', NULL);
            $this->db_master()->where('id', $history['id']);
            $this->db_master()->update('mint_note_block');
        }
        else
        {
            $this->db_master()->insert('mint_note_block', $blocked);
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    public function chekced_block($where)
    {
        $this->db_connect('slave');

        $sql =  sprintf("SELECT mnb.id
                FROM mint_note_block mnb
                %s", $where);
                
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function send_message($msg)
    {
        
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_note', $msg);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }



}










