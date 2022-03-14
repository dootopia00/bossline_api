<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Webrtc_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function get_wiz_schedule_by_sc_id($sc_id, $tu_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT ws.sc_id, ws.lesson_id, ws.lesson_gubun, ws.uid, ws. wiz_id, ws.name, ws.tu_uid, 
        ws.tu_name, ws.cl_time, cl_number, ws.mobile, ws.startday, ws.endday
        FROM wiz_schedule ws
        WHERE sc_id = ? AND tu_uid = ?";

        $res = $this->db_slave()->query($sql, array($sc_id, $tu_uid));       

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_wiz_member_by_wiz_id($wiz_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wm.uid, wm.nickname, wm.name, wm.wiz_id, wm.ename FROM wiz_member wm WHERE wm.wiz_id=?";

        $res = $this->db_slave()->query($sql, array($wiz_id));       

        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function token_count_by_wiz_uid($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(wmk.uid) as cnt FROM wiz_member_token wmk WHERE wmk.uid=?";

        $res = $this->db_slave()->query($sql, array($uid));
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_last_token_by_uid($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wmk.token, wmk.uid, wmk.device FROM wiz_member_token wmk WHERE wmk.uid=? ORDER BY wmk.last_modify_date DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function insert_webrtc_push_log($log)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('webrtc_push_log', $log);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function get_wiz_tutor_by_tu_uid($tu_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT tu_name, tu_pic FROM wiz_tutor WHERE tu_uid=?";

        $res = $this->db_slave()->query($sql, array($tu_uid));       

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function missed_count_by_sc_id($sc_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(wpl_key) AS count FROM webrtc_push_log WHERE sc_id=? AND state = '5'";

        $res = $this->db_slave()->query($sql, array($sc_id));
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_webrtc_push_log_by_sc_id($sc_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wpl.wpl_key, wpl.tu_uid, wpl.uid, wpl.sc_id, wpl.state, wpl.platform, wpl.device, wpl.desc, wpl.regdate, wpl.update_date 
        FROM webrtc_push_log wpl WHERE wpl.sc_id=? ORDER BY regdate DESC;";

        $res = $this->db_slave()->query($sql, array($sc_id));
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function checked_webrtc_push_log_by_wpl_key($wpl_key)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(wpl_key) AS count FROM webrtc_push_log WHERE wpl_key=?";

        $res = $this->db_slave()->query($sql,array($wpl_key));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function update_webrtc_push_log($data, $wpl_key)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('wpl_key', $wpl_key);
        $this->db_master()->update('webrtc_push_log', $data);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function sc_time_checked_webrtc_push_log_by_sc_id($sc_id)
    {
        $this->db_connect('slave');

        //수업 시작 시간 -5분 ~ 수업 끝나는시간 조회시 값이 있는지 체크
        $sql = "SELECT wpl.wpl_key, wpl.sc_id, ws.startday, ws.endday 
        FROM webrtc_push_log wpl
        JOIN wiz_schedule ws ON wpl.sc_id = ws.sc_id
        WHERE wpl.sc_id = ?
        AND ws.startday < DATE_ADD(NOW(), INTERVAL 5 MINUTE)
        AND ws.endday > NOW()";

        $res = $this->db_slave()->query($sql,array($sc_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function over_checked_webrtc_push_log_by_wpl_key($wpl_key)
    {
        $this->db_connect('slave');

        //$wpl_key 보다 큰 wpl_key값이 있는지 조회
        $sql = "SELECT wpl.wpl_key, wpl.state, wpl.sc_id, ws.startday, ws.endday 
        FROM webrtc_push_log wpl
        JOIN wiz_schedule ws ON wpl.sc_id = ws.sc_id 
        WHERE wpl.wpl_key > ?
        AND wpl.state != '1';";

        $res = $this->db_slave()->query($sql,array($wpl_key));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;

    }

    /* 말톡노트 로그 입력 */
    public function insert_maaltalk_note_log($log)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('maaltalk_note_log', $log);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    /*
        말톡노트 - 수업 시작 시간 -2분전부터 강의실 초대 로그 있는지
    */
    public function checked_classroom_invitation($wm_uid)
    {
        $this->db_connect('slave');

        
        $sql = "SELECT mnl.mnl_key, mnl.invitational_url
                FROM maaltalk_note_log mnl
                WHERE mnl.wm_uid = ?
                AND mnl.state = 1
                AND mnl.loc = 1
                AND mnl.class_start_time < DATE_SUB(NOW(), INTERVAL -2 MINUTE)
                AND mnl.class_end_time > NOW()";

        $res = $this->db_slave()->query($sql,array($wm_uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_classroom_invitation_by_sc_id($wm_uid, $sc_id)
    {
        $this->db_connect('slave');

        
        $sql = "SELECT mnl.mnl_key, mnl.invitational_url
                FROM maaltalk_note_log mnl
                WHERE mnl.wm_uid = ? AND mnl.state = 1 AND mnl.loc = 1 AND mnl.sc_id = ?";

        $res = $this->db_slave()->query($sql,array($wm_uid, $sc_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

}










