<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Member_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }


    public function login_super()
    {
        $this->db_connect('slave');
        
        $sql = "SELECT super_pwd , super_id, super_jumin FROM mint_sp_pw LIMIT 0,1";
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /* 탈퇴시 del_yn ='d' , value_ok ='N' */
    public function get_wiz_member_by_wiz_id($wiz_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wm.uid as wm_uid, wm.wiz_id as wm_wiz_id, wm.name as wm_name, wm.ename as wm_ename, wm.nickname as wm_nickname, wm.d_id as wm_d_did, wm.jumin1 as wm_jumin1,
                    wm.social_email as wm_social_email, wm.assistant_code as wm_assistant_code, wm.point as wm_point, wm.lev_gubun as wm_lev_gubun, wm.profile as wm_profile, wm.regi_gubun as wm_regi_gubun, 
                    wm.sms_ok as wm_sms_ok, wm.email as wm_email, wm.email_ok as wm_email_ok, wm.mobile as wm_mobile, wm.gender as wm_gender, wm.regi_area as wm_regi_area, wm.birth as wm_birth,
                    wm.view_boards as wm_view_boards, wm.view_online_list as wm_view_online_list, wm.view_login_count as wm_view_login_count,wm.age as wm_age,wm.update_yn as wm_update_yn,
                    wm.logview  as wm_logview, wm.last_attendance as wm_last_attendance, wm.attendance as wm_attendance,wm.last_mset_date as wm_last_mset_date, wm.muu_key as wm_muu_key, wm.greeting as wm_greeting,
                    wl.le_step as wl_le_step, wl.check_yn as wl_check_yn, 
                    wm.grade as wm_grade, wm.grade_standby as wm_grade_standby, mmg.icon as mmg_icon, mmg.description as mmg_description, mmg.title as mmg_title,
                    wmb.badge_id as wmb_badge_id, wmb.regdate as wmb_regdate, wb.title as wb_title,wb.description as wb_description,wb.img as wb_img,wb.img_big_on as wb_img_big_on,wb.img_big_off as wb_img_big_off,
                    mqut.ut_idx as mqut_ut_idx,mqut.regdate as mqut_regdate,mq.title as mq_title,mq.tropy_on as mq_tropy_on,
                    wd.long_postpone_yn as wd_long_postpone_yn, wd.point_addclass_yn as wd_point_addclass_yn, wd.point_postpone_yn as wd_point_postpone_yn, wd.change_ea_yn as wd_change_ea_yn,
                    wd.has_member_fee as wd_has_member_fee, wd.gaegeun_yn as wd_gaegeun_yn
                FROM wiz_member wm
                LEFT OUTER JOIN mint_member_grade mmg ON wm.grade = mmg.id 
                LEFT OUTER JOIN wiz_leveltest wl ON wl.wiz_id = wm.wiz_id 
                LEFT OUTER JOIN wiz_member_badge wmb ON wmb.uid = wm.uid AND wmb.use_yn='Y'
                LEFT OUTER JOIN wiz_badge wb ON wb.id = wmb.badge_id
                LEFT OUTER JOIN mint_quest_user_tropy mqut ON mqut.uid = wm.uid AND mqut.use_yn='Y'
                LEFT OUTER JOIN mint_quest mq ON mq.q_idx = mqut.q_idx
                LEFT OUTER JOIN wiz_dealer wd ON wd.d_id = wm.d_id
                WHERE wm.wiz_id = ? AND wm.value_ok = 'Y' LIMIT 1";
        $res = $this->db_slave()->query($sql, array($wiz_id));       
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_wiz_member_by_where($select_col_content='', $join='', $where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT wm.uid, wm.wiz_id, wm.value_ok, wm.del_yn %s
                        FROM wiz_member wm %s
                %s", $select_col_content, $join, $where);

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    /* 탈퇴시 del_yn ='d' , value_ok ='N' */
    /* 동일 id중에 탈퇴한 id가 있는지 체크 */
    public function get_delete_wiz_member_by_wiz_id($wiz_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wm.wiz_id, wm.value_ok, wm.del_yn
        FROM wiz_member wm
        WHERE wm.wiz_id = ? AND wm.value_ok = 'N' AND wm.del_yn = 'd' 
        ORDER BY wm.uid DESC";

        $res = $this->db_slave()->query($sql, array($wiz_id));      

        // echo $this->db_slave()->last_query();   
        // exit;
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_wiz_member_by_social_id($social_id)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT wm.wiz_id, wm.value_ok, wm.del_yn
                FROM wiz_member wm  
                WHERE wm.social_id = ? AND wm.value_ok = 'Y' 
                ORDER BY wm.uid DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($social_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function get_wiz_member_by_wm_uid($wm_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wm.uid as wm_uid, wm.wiz_id as wm_wiz_id, wm.name as wm_name, wm.ename as wm_ename, wm.nickname as wm_nickname, wm.d_id as wm_d_did, wm.jumin1 as wm_jumin1,wm.tel as wm_tel,
                    wm.social_email as wm_social_email, wm.assistant_code as wm_assistant_code, wm.point as wm_point, wm.lev_gubun as wm_lev_gubun, wm.profile as wm_profile, wm.regi_gubun as wm_regi_gubun, 
                    wm.sms_ok as wm_sms_ok, wm.email as wm_email, wm.email_ok as wm_email_ok, wm.mobile as wm_mobile, wm.gender as wm_gender, wm.regi_area as wm_regi_area, wm.birth as wm_birth,
                    wm.view_boards as wm_view_boards, wm.view_online_list as wm_view_online_list, wm.view_login_count as wm_view_login_count,wm.age as wm_age,wm.update_yn as wm_update_yn,
                    wm.logview  as wm_logview, wm.last_attendance as wm_last_attendance, wm.attendance as wm_attendance,wm.last_mset_date as wm_last_mset_date, wm.muu_key as wm_muu_key,
                    wl.le_step as wl_le_step, wl.check_yn as wl_check_yn, 
                    wm.grade as wm_grade, wm.grade_standby as wm_grade_standby, mmg.icon as mmg_icon, mmg.description as mmg_description, mmg.title as mmg_title,
                    wmb.badge_id as wmb_badge_id,wb.title as wb_title,wb.description as wb_description,wb.img as wb_img,wb.img_big_on as wb_img_big_on,wb.img_big_off as wb_img_big_off
                FROM wiz_member wm
                LEFT OUTER JOIN mint_member_grade mmg ON wm.grade = mmg.id 
                LEFT OUTER JOIN wiz_leveltest wl ON wm.wiz_id = wl.wiz_id
                LEFT OUTER JOIN wiz_member_badge wmb ON wmb.uid = wm.uid AND wmb.use_yn='Y'
                LEFT OUTER JOIN wiz_badge wb ON wb.id = wmb.badge_id
                WHERE wm.uid = ? AND wm.value_ok = 'Y' LIMIT 1";
        $res = $this->db_slave()->query($sql, array($wm_uid));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }



    public function checked_nickname($nickname)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT uid, wiz_id, name, ename, nickname, d_id, assistant_code, point, lev_gubun, profile, regi_gubun, logview, grade
        FROM wiz_member
        WHERE nickname = ? AND value_ok = 'Y' AND del_yn != 'd' LIMIT 1";
        $res = $this->db_slave()->query($sql, array($nickname));       
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_phone_number($phone_number)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT uid, wiz_id, name, ename, nickname, d_id, assistant_code, point, lev_gubun, profile, regi_gubun, logview, grade
        FROM wiz_member
        WHERE mobile = ? LIMIT 1";
        $res = $this->db_slave()->query($sql, array($phone_number));       
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function login($wiz_id, $wiz_pw, $device, $device_token, $admin_login=false)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT  wm.uid as wm_uid, wm.wiz_id as wm_wiz_id, wm.name as wm_name, wm.ename as wm_ename, wm.nickname as wm_nickname, wm.d_id as wm_d_did,wm.age as wm_age,
        wm.social_email as wm_social_email, wm.assistant_code as wm_assistant_code, wm.point as wm_point, wm.lev_gubun as wm_lev_gubun, wm.profile as wm_profile, wm.regi_gubun as wm_regi_gubun,
        wm.regdate as wm_regdate, wm.logview  as wm_logview, wm.mobile as wm_mobile, wl.le_step as wl_le_step, wl.check_yn as wl_check_yn,wm.update_yn as wm_update_yn,
        wm.grade as wm_grade, mmg.icon as mmg_icon, mmg.description as mmg_description, mmg.title as mmg_title,
        wmb.badge_id as wmb_badge_id,wb.title as wb_title,wb.description as wb_description,wb.img as wb_img,wb.img_big_on as wb_img_big_on,wb.img_big_off as wb_img_big_off
        FROM wiz_member wm 
        LEFT OUTER JOIN mint_member_grade mmg ON wm.grade = mmg.id 
        LEFT OUTER JOIN wiz_leveltest wl ON wm.wiz_id = wl.wiz_id
        LEFT OUTER JOIN wiz_member_badge wmb ON wmb.uid = wm.uid AND wmb.use_yn='Y'
        LEFT OUTER JOIN wiz_badge wb ON wb.id = wmb.badge_id
        WHERE wm.wiz_id = ? AND wm.wiz_pw = ? AND wm.value_ok = 'Y' AND wm.del_yn != 'd'";
        $res = $this->db_master()->query($sql, array($wiz_id, $wiz_pw));
        $wiz_member = $res->row_array();

        // echo $this->db_master()->last_query();   
        // exit;

        if($wiz_member && $admin_login === false)
        {
            $this->db_master()->set('logview', $wiz_member['wm_logview'] + 1, FALSE);
            $this->db_master()->set('lastlogin', 'now()', FALSE);
            $this->db_master()->where('wiz_id', $wiz_id);
            $this->db_master()->update('wiz_member');

            if($device && $device_token)
            {
                $this->db_master()->set('uid', $wiz_member['wm_uid'], FALSE);
                $this->db_master()->set('device', $device, FALSE);
                $this->db_master()->set('last_modify_date', 'now()', FALSE);
                $this->db_master()->where('token',  $device_token);
                $this->db_master()->update('wiz_member_token');
            }
        }
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return NULL;
        }

        return $wiz_member ? $wiz_member : NULL;
    }
    
    public function login_sns($wiz_id, $regi_gubun, $device, $device_token, $social_email)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT wm.uid as wm_uid, wm.wiz_id as wm_wiz_id, wm.name as wm_name, wm.ename as wm_ename, wm.nickname as wm_nickname, wm.d_id as wm_d_did,wm.age as wm_age,
        wm.social_email as wm_social_email, wm.assistant_code as wm_assistant_code, wm.point as wm_point, wm.lev_gubun as wm_lev_gubun, wm.profile as wm_profile, wm.regi_gubun as wm_regi_gubun,
        wm.logview  as wm_logview, wl.le_step as wl_le_step, wl.check_yn as wl_check_yn,
        wm.grade as wm_grade, mmg.icon as mmg_icon, mmg.description as mmg_description, mmg.title as mmg_title,
        wmb.badge_id as wmb_badge_id,wb.title as wb_title,wb.description as wb_description,wb.img as wb_img,wb.img_big_on as wb_img_big_on,wb.img_big_off as wb_img_big_off
            FROM wiz_member wm 
            LEFT OUTER JOIN mint_member_grade mmg ON wm.grade = mmg.id 
            LEFT OUTER JOIN wiz_leveltest wl ON wm.wiz_id = wl.wiz_id
            LEFT OUTER JOIN wiz_member_badge wmb ON wmb.uid = wm.uid AND wmb.use_yn='Y'
            LEFT OUTER JOIN wiz_badge wb ON wb.id = wmb.badge_id
            WHERE wm.wiz_id = ? AND wm.regi_gubun = ? AND wm.value_ok = 'Y' AND wm.del_yn != 'd' ";
        $res = $this->db_master()->query($sql, array($wiz_id, $regi_gubun));

        $wiz_member = $res->row_array();


        if($wiz_member)
        {
            $this->db_master()->set('logview', $wiz_member['wm_logview'] + 1, FALSE);
            $this->db_master()->set('lastlogin', 'now()', FALSE);
            $this->db_master()->where('wiz_id', $wiz_member['wm_wiz_id']);
            $this->db_master()->update('wiz_member');

            if($device && $device_token)
            {
                $this->db_master()->set('uid', $wiz_member['wm_uid'], FALSE);
                $this->db_master()->set('device', $device, FALSE);
                $this->db_master()->set('last_modify_date', 'now()', FALSE);
                $this->db_master()->where('token',  $device_token);
                $this->db_master()->update('wiz_member_token');
            }

            if($wiz_member['wm_social_email'] == 'undefined')
            {
                $this->db_master()->set('social_email', $social_email);
                $this->db_master()->where('wiz_id', $wiz_member['wm_wiz_id']);
                $this->db_master()->update('wiz_member');
            }
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return NULL;
        }

        return $wiz_member ? $wiz_member : NULL;
    }

    public function get_wm_uid_by_wiz_id($wiz_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wm.uid as wm_uid, wm.mobile as wm_mobile, wm.name as wm_name
        FROM wiz_member wm
        WHERE wm.wiz_id = ?";
        $res = $this->db_slave()->query($sql, array($wiz_id));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_wm_point_by_wiz_id($wiz_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wm.point  as wm_point
        FROM wiz_member wm
        WHERE wm.wiz_id = ?";
        $res = $this->db_slave()->query($sql, array($wiz_id));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function checked_inclass($where)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT 
                            count(1) as cnt
                        FROM wiz_lesson wl
                        %s", $where);

        $res = $this->db_slave()->query($sql); 

        
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function checked_block_member($blocker_uid, $blocked_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wmb.* FROM wiz_member_block wmb 
                WHERE wmb.candate IS NULL AND wmb.blocker_uid = ? AND wmb.blocked_uid = ? ";
        $res = $this->db_slave()->query($sql, array($blocker_uid,$blocked_uid));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function checked_block_note_member($blocker_uid, $blocked_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mnb.* FROM mint_note_block mnb 
                WHERE mnb.canceled_at IS NULL AND mnb.blocker_id = ? AND mnb.blocked_id = ? ";
        $res = $this->db_slave()->query($sql, array($blocker_uid,$blocked_uid));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    

    public function get_wiz_dealer($d_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT * FROM wiz_dealer WHERE d_id = ? LIMIT 1";
        $res = $this->db_slave()->query($sql, array($d_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function insert_member($member)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_member', $member);


        $insert_id = $this->db_master()->insert_id();
        // echo $this->db_master()->last_query();exit;
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function insert_level_test($level_test_data, $uid)
    {

        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert_batch('wiz_leveltest', $level_test_data);

        $sql = "SELECT MIN(le_id) AS min_id FROM wiz_leveltest WHERE uid = ? ";
        $res = $this->db_master()->query($sql, array($uid));
        $tmp = $res->row_array();
        
        $this->db_master()->set('le_fid', $tmp['min_id'], FALSE);
        $this->db_master()->where('uid', $uid);
        $this->db_master()->update('wiz_leveltest');
        
        $this->db_master()->set('leveltest_state', 'Y');
        $this->db_master()->where('uid', $uid);
        $this->db_master()->update('wiz_member');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $tmp['min_id'];
    }

    public function insert_correct_gift($gift_data)
    {

        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert_batch('wiz_member_correct_gift', $gift_data);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    
    public function find_id_mobile($name, $birth, $tel)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wm.wiz_id as wm_wiz_id, wm.regi_gubun as wm_regi_gubun, wm.social_email as wm_social_email, wm.mobile as wm_mobile 
        FROM wiz_member wm WHERE wm.name = ? AND wm.birth = ? AND wm.mobile = ? AND wm.value_ok = 'Y'";
        $res = $this->db_slave()->query($sql, array($name, $birth, $tel));       
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function find_id_tel($name, $birth, $tel)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wm.wiz_id as wm_wiz_id, wm.regi_gubun as wm_regi_gubun, wm.social_email as wm_social_email, wm.mobile as wm_mobile 
        FROM wiz_member wm WHERE wm.name = ? AND wm.birth = ? AND wm.tel = ? AND wm.value_ok = 'Y'";
        
        $res = $this->db_slave()->query($sql, array($name, $birth, $tel));     
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function find_id_email($name, $birth, $email)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wm.wiz_id as wm_wiz_id, wm.regi_gubun as wm_regi_gubun, wm.social_email as wm_social_email, wm.mobile as wm_mobile 
        FROM wiz_member wm WHERE wm.name = ? AND wm.birth = ? AND wm.email = ? AND wm.value_ok = 'Y'";
        
        $res = $this->db_slave()->query($sql, array($name, $birth, $email));     

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    // update password
    public function find_pwd_mobile($wiz_id, $name, $tel)
    {
        $this->db_connect('slave');

        $sql = "SELECT wm.uid FROM wiz_member wm WHERE wm.wiz_id = ? AND wm.name = ? AND wm.mobile = ? LIMIT 1";
        $res = $this->db_slave()->query($sql, array($wiz_id, $name, $tel));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function find_pwd_tel($wiz_id, $name, $tel)
    {
        $this->db_connect('slave');

        $sql = "SELECT wm.uid FROM wiz_member wm WHERE wm.wiz_id = ? AND wm.name = ? AND wm.tel = ? LIMIT 1";
        $res = $this->db_slave()->query($sql, array($wiz_id, $name, $tel));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }    

    public function find_pwd_email($wiz_id, $name, $email)
    {
        $this->db_connect('slave');

        $sql = "SELECT wm.uid FROM wiz_member wm WHERE wm.wiz_id = ? AND wm.name = ? AND wm.email = ? LIMIT 1";
        $res = $this->db_slave()->query($sql, array($wiz_id, $name, $email));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }    

    public function list_count_notify($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                FROM mint_notify mn
                %s", $where);

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_notify($where, $order, $limit)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mn.*, IFNULL(mn.parent_key, mb.parent_key) as parent_key, mbn.table_name
                        FROM mint_notify mn
                        LEFT OUTER JOIN mint_boards mb ON mn.mb_unq = mb.mb_unq
                        LEFT OUTER JOIN mint_boards_name mbn ON mb.table_code = mbn.table_code  

                %s %s %s",$where, $order, $limit);

        $res = $this->db_slave()->query($sql);

        // echo $this->db_slave()->last_query();   
        // exit;
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function removed_notify($idx)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->set('removed', 1);
        $this->db_master()->where_in('idx', $idx);
        $this->db_master()->update('mint_notify');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function view_notify($idx)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->set('view', 1);
        $this->db_master()->where_in('idx', $idx);
        $this->db_master()->update('mint_notify');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function list_count_clip($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                FROM mint_clip_boards mcb
                %s", $where);

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_clip($where, $order, $limit)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mcb.cb_unq , mcb.mb_unq, mcb.table_code, mcb.wiz_id, mcb.url, mcb.title, mcb.input_txt, mcb.regdate, mb.set_point,
                            case mcb.table_code 
                            when '9001' then '이런표현어떻게' 
                            when '9002' then '얼굴철판딕테이션'
                            when '9003' then '얼굴철판딕테이션'
                            when '9004' then '영어첨삭게시판'
                            when '9999' then '실시간요청게시판'
                            else mbn.table_name end as mbn_table_name
                        FROM mint_clip_boards mcb
                        LEFT OUTER JOIN mint_boards_name mbn ON mcb.table_code = mbn.table_code  
                        INNER JOIN mint_boards mb ON mcb.mb_unq = mb.mb_unq 
                %s %s %s",$where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();   
        // exit;
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function delete_clip($cb_unq, $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('reg_wiz_id', $wiz_id);
        $this->db_master()->where_in('cb_unq', $cb_unq);
        $this->db_master()->delete('mint_clip_boards');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function list_article($where_wiz_id, $whre_wm_uid, $order, $limit, $where_mint_board='')
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mb.table_code as mb_table_code , mbn.table_name as mbn_table_name, mb.mb_unq as mb_unq, mb.title as mb_title,  mb.regdate as mb_regdate, mb.parent_key as mb_parent_key, mb.set_point as mb_set_point    
                            FROM mint_boards mb INNER JOIN mint_boards_name mbn ON mb.table_code = mbn.table_code  %s %s
                        UNION ALL
                        SELECT 'toteacher' as mb_table_code, '강사와1:1게시판' as mbn_table_name, mb.to_id as mb_unq, mb.title as mb_title, mb.regdate as mb_regdate, 0 as mb_parent_key, 0 as mb_set_point
                            FROM wiz_toteacher mb %s 
                        UNION ALL
                        SELECT 'express' as mb_table_code, '이런표현어떻게' as mbn_table_name, mb.uid as mb_unq, mb.content as mb_title, mb.regdate as mb_regdate, mb.parent_key as mb_parent_key, 0 as mb_set_point
                            FROM mint_express mb %s 
                        UNION ALL 
                        SELECT 'dictation.list' as mb_table_code, '얼굴철판딕테이션' as mbn_table_name, mb.c_uid as mb_unq, mb.subject as mb_title, mb.regdate as mb_regdate, 0 as mb_parent_key, 0 as mb_set_point
                            FROM mint_cafeboard mb %s 
                        UNION ALL 
                        SELECT 'correction' as mb_table_code, '영어첨삭게시판' as mbn_table_name, mb.w_id as mb_unq, mb.w_title as mb_title, mb.w_regdate as mb_regdate, 0 as mb_parent_key, 0 as mb_set_point
                            FROM wiz_correct mb %s 
                        UNION ALL 
                        SELECT 'request' as mb_table_code, '실시간요청게시판' as mbn_table_name, mb.sp_id as mb_unq, mb.sp_title as mb_title, mb.sp_regdate as mb_regdate, 0 as mb_parent_key, 0 as mb_set_point
                            FROM wiz_speak mb %s 
                %s %s",$where_wiz_id, $where_mint_board, $where_wiz_id, $where_wiz_id, $whre_wm_uid, $whre_wm_uid, $whre_wm_uid, $order, $limit);


        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function list_count_article($where_wiz_id, $whre_wm_uid)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT sum(cnt) as cnt FROM
                        (
                        SELECT count(1) as cnt  
                            FROM mint_boards mb %s 
                        UNION ALL 
                        SELECT count(1) as cnt   
                            FROM wiz_toteacher mb %s 
                        UNION ALL 
                        SELECT count(1) as cnt  
                            FROM mint_express mb %s 
                        UNION ALL 
                        SELECT count(1) as cnt  
                            FROM mint_cafeboard mb %s 
                        UNION ALL 
                        SELECT count(1) as cnt  
                            FROM wiz_correct mb %s 
                        UNION ALL 
                        SELECT count(1) as cnt  
                            FROM wiz_speak mb %s
                        ) A ",$where_wiz_id, $where_wiz_id, $where_wiz_id, $whre_wm_uid, $whre_wm_uid, $whre_wm_uid);

        $res = $this->db_slave()->query($sql);


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_article_comment($join_article, $where, $where_writer_id, $where_wiz_id, $order, $limit)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT  mbc.table_code as mb_table_code, mbc.mb_unq, mbc.co_unq, mbc.comment, mbc.regdate, mb.parent_key AS mb_parent_key, mb.set_point AS mb_set_point
                            FROM db_acephone.mint_boards_comment mbc %s %s 
                        UNION ALL
                            SELECT 'dictation.list' as mb_table_code,  mbc.c_uid as mb_unq,  mbc.unq as co_unq, mbc.comment, mbc.regdate, 0 as mb_parent_key, 0 as mb_set_point
                            FROM db_acephone.mint_cafeboard_com mbc %s
                        UNION ALL
                            SELECT 'express' as mb_table_code, mbc.e_id as mb_unq, mbc.uid as co_unq, mbc.comment, mbc.regdate, me.parent_key as mb_parent_key, 0 as mb_set_point
                            FROM mint_express_com mbc 
                            JOIN mint_express me ON mbc.e_id=me.uid
                        %s
                %s %s",$join_article, $where, $where_writer_id, $where_wiz_id, $order, $limit);


        $res = $this->db_slave()->query($sql);

        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_count_article_comment($where_writer_id, $where_wiz_id)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT sum(cnt) as cnt FROM
                        (
                        SELECT count(1) as cnt 
                            FROM db_acephone.mint_boards_comment mbc %s
                        UNION ALL 
                        SELECT count(1) as cnt
                            FROM db_acephone.mint_cafeboard_com mbc %s
                        UNION ALL 
                        SELECT count(1) as cnt 
                            FROM mint_express_com mbc %s
                        ) A ",$where_writer_id, $where_writer_id, $where_wiz_id);

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_count_teacher_counseling($where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT count(1) as cnt
                FROM wiz_toteacher wt
                %s", $where);

        $res = $this->db_slave()->query($sql);


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_teacher_counseling($where, $order, $limit)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT wt.to_id, wt.uid, wt.wiz_id, wt.tu_uid, wt.tu_name, wt.title, wt.regdate, wt.step, wt.to_gubun
                        FROM wiz_toteacher wt 
                %s %s %s",$where, $order, $limit);


        $res = $this->db_slave()->query($sql);

    
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function checked_count_wiz_id($wiz_id)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT count(1) as cnt
                FROM wiz_member wm
                WHERE  wm.social_id = ? AND value_ok = 'N' AND del_yn ='d' ";

        $res = $this->db_slave()->query($sql,array($wiz_id));


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function update_member($wiz_member, $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('wiz_id', $wiz_id);
        $this->db_master()->update('wiz_member', $wiz_member);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function get_wiz_token($wiz_uid){
        $this->db_connect('slave');

        $sql = "SELECT wmt.token as wmt_token FROM wiz_member_token wmt WHERE wmt.uid = ?";

        $res = $this->db_slave()->query($sql, array($wiz_uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_last_token_by_uid($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wmt.token AS wmt_token, wmt.uid AS wmt_uid, wmt.device AS wmt_device 
                FROM wiz_member_token wmt WHERE wmt.uid=? 
                ORDER BY wmt.last_modify_date DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($uid));
        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function insert_mint_note_block($params)
    {
        $this->db_connect('master');

        $sql = "SELECT mnb.* FROM mint_note_block mnb 
                WHERE mnb.canceled_at IS NULL AND mnb.blocker_id = ? AND mnb.blocked_id = ? ";
        $res = $this->db_master()->query($sql, array($params['blocker_id'],$params['blocked_id']));       
        $result = $res->row_array();
        if($result) return $result['id'];

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_note_block', $params);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    
    public function insert_wiz_member_block($params)
    {
        $this->db_connect('master');

        $sql = "SELECT wmb.* FROM wiz_member_block wmb WHERE wmb.candate IS NULL AND wmb.blocker_uid = ? AND wmb.blocked_uid = ? ";
        $res = $this->db_master()->query($sql, array($params['blocker_uid'],$params['blocked_uid']));       
        $result = $res->row_array();

        if($result) return $result['id'];

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_member_block', $params);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    public function update_wiz_member_block($blocker_uid,$blocked_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('candate', date('Y-m-d H:i:s'));
        $this->db_master()->where('blocker_uid', $blocker_uid);
        $this->db_master()->where('blocked_uid', $blocked_uid);

        $this->db_master()->update('wiz_member_block');

        
        $this->db_master()->set('canceled_at', date('Y-m-d H:i:s'));
        $this->db_master()->where('blocker_id', $blocker_uid);
        $this->db_master()->where('blocked_id', $blocked_uid);

        $this->db_master()->update('mint_note_block');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function count_member_block($uid,$where){
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt  FROM wiz_member_block as wmb 
            INNER JOIN wiz_member as wm ON wmb.blocked_uid=wm.uid
            WHERE wmb.blocker_uid = ? AND candate IS NULL ".$where;

        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function list_member_block($uid,$where=''){
        $this->db_connect('slave');

        $sql = "SELECT wmb.regdate as wmb_regdate,wm.uid as wm_uid,wm.nickname as wm_nickname,wm.wiz_id as wm_wiz_id  FROM wiz_member_block as wmb 
            INNER JOIN wiz_member as wm ON wmb.blocked_uid=wm.uid
            WHERE wmb.blocker_uid = ? AND candate IS NULL ".$where;

        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    // 회원 차단 여부 조회
    public function check_member_block($blocker_uid, $blocked_wiz_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wmb.id  FROM wiz_member_block as wmb 
            INNER JOIN wiz_member as wm ON wmb.blocked_uid=wm.uid
            WHERE wmb.blocker_uid = ? AND wm.wiz_id = ?  AND candate IS NULL ";

        $res = $this->db_slave()->query($sql, array($blocker_uid, $blocked_wiz_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;

    }


    
    public function check_member_survry($uid){
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt FROM wiz_member_info WHERE uid= ? ";

        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function check_recommented_member($id,$wiz_id){
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt FROM wiz_member WHERE (wiz_id= ? or social_email = ?) AND wiz_id != ? ";

        $res = $this->db_slave()->query($sql, array($id,$id,$wiz_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function insert_wiz_member_info($insertParam,$updateMemberParam,$uid)
    {
        $this->db_connect('master');

        $sql = "SELECT 1 FROM wiz_member_info WHERE uid= ? ";
        $res = $this->db_master()->query($sql, array($uid));

        if($res->num_rows() > 0) return 1;

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_member_info',$insertParam);
 
        $this->db_master()->where('uid',$uid);
        $this->db_master()->set($updateMemberParam);
        $this->db_master()->update('wiz_member');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    

    public function replace_last_connect($Param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->replace('mint_member_lastconnect',$Param);
        if (rand(0, 300) == 0) 
        {
            $this->db_master()->where('regdate < ',date("Y-m-d 00:00:00"));
            $this->db_master()->delete('mint_member_lastconnect');
        }
 
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function replace_last_connect_guest($Param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->replace('mint_member_lastconnect_guest',$Param);
 
        if (rand(0, 300) == 0) 
        {
            $this->db_master()->where('regdate < ',date("Y-m-d 00:00:00"));
            $this->db_master()->delete('mint_member_lastconnect_guest');
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function count_current_connect($where=''){
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt FROM mint_member_lastconnect ". $where;

        $res = $this->db_slave()->query($sql);
        $cnt = $res->num_rows() > 0 ? $res->row_array() : NULL;
        $cnt = $cnt ? $cnt['cnt']:0;

        $sql = "SELECT count(1) as cnt FROM mint_member_lastconnect_guest ". $where;

        $res = $this->db_slave()->query($sql);
        $cnt2 = $res->num_rows() > 0 ? $res->row_array() : NULL;
        $cnt2 = $cnt2 ? $cnt2['cnt']:0;

        return $cnt + $cnt2;
    }


    public function checked_join_qna($uid)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT count(1) as cnt
                FROM wiz_member_info wmi
                WHERE  wmi.uid = ?";

        $res = $this->db_slave()->query($sql,array($uid));


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function admin_log_check($wm_uid, $auth_code)
    {
        $this->db_connect('master');

        $sql = "SELECT `au_id` FROM `auth_data` WHERE `code`='".$auth_code."' AND `type`='member_login' AND `disabled`=0";
        $res = $this->db_master()->query($sql);
        $row = $res->num_rows() > 0 ? $res->row_array() : NULL;

        if(!$row) return false;

        $sql = "SELECT `uid`, `wiz_id`,`wiz_pw` FROM wiz_member WHERE uid=".$wm_uid;
        $res = $this->db_master()->query($sql);
        $wm_row = $res->num_rows() > 0 ? $res->row_array() : NULL;

        if(!$wm_row) return false;

        $this->db_master()->trans_start();

        // disabled 0->1 신민트 로그인, 1->2 구민트 로그인
        $this->db_master()->where('au_id',$row['au_id']);
        $this->db_master()->set('disabled',1 );
        $this->db_master()->update('auth_data');  

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return false;
        }

        return $wm_row;
    }

    
    public function insert_trace_member_log($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('trace_member_log',$param);  

        if (rand(0, 1000) == 0) 
        {
            $this->db_master()->where('regdate < ',date("Y-m-d 00:00:00",strtotime('-1 month')));
            $this->db_master()->delete('trace_member_log');
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    public function update_member_token($wm_uid, $is_app, $token)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT wmt.uid FROM wiz_member_token wmt WHERE wmt.token = ?";

        $res = $this->db_master()->query($sql, array($token));      
        
        if($res->num_rows() == 0)
        {
            //등록된 토큰정보가 없을때 인설트
            $member_token = array(
                "uid" => $wm_uid,
                "device" => $is_app,
                "token" => $token,
                "regdate" => date('Y-m-d H:i:s')
            );
            
            $this->db_master()->insert('wiz_member_token',$member_token);  

           
        }
        else
        {
            $row = $res->row_array();
            if($row['uid'] != $wm_uid)
            {
                //등록된 토큰정보가 있으면 토큰에 해당하는 UID로 업뎃
                $this->db_master()->set('uid', $wm_uid);
                $this->db_master()->where('token', $token);
                $this->db_master()->update('wiz_member_token');  
            }
            
        }

      
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    /* 본인 스케쥴을 조회하는것이 맞는지 체크  */
    public function checked_schedule_by_wiz_member($sc_id, $wm_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT 
                    ws.sc_id AS ws_sc_id, ws.startday AS ws_startday, ws.endday AS ws_endday, ws.mobile as ws_mobile, 
                    wl.mobile as wl_mobile, wm.mobile as wm_mobile, wm.ename as wm_ename, ws.tu_uid AS ws_tu_uid
                FROM wiz_schedule ws
                LEFT OUTER JOIN wiz_lesson wl ON ws.lesson_id = wl.lesson_id
                INNER JOIN wiz_member wm ON ws.uid = wm.uid
                WHERE ws.sc_id = ? AND ws.uid = ?";
                
        $res = $this->db_slave()->query($sql, array($sc_id, $wm_uid));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    /* 본인 스케쥴을 조회하는것이 맞는지 체크  */
    public function checked_schedule_date_data_by_wiz_member($sc_id, $wm_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT ws.sc_id AS ws_sc_id, ws.startday AS ws_startday, ws.endday AS ws_endday FROM wiz_schedule ws 
                WHERE ws.sc_id = ? AND ws.uid = ?";
        $res = $this->db_slave()->query($sql, array($sc_id, $wm_uid));       
        
        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_member_badge($wm_uid, $badge_id)
    {
        $this->db_connect('slave');

        $this->db_slave()->trans_start();

        $sql = "SELECT wmb.uid
                FROM wiz_member_badge wmb
                WHERE wmb.uid = ? AND wmb.badge_id = ?";

        $res = $this->db_slave()->query($sql, array($wm_uid, $badge_id));  
        
        // echo $this->db_slave()->last_query();
        // exit;
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_badge($type1, $type2)
    {
        $this->db_connect('slave');

        $this->db_slave()->trans_start();

        $sql = "SELECT wb.id AS wb_id, wb.award_message AS wb_award_message
                FROM wiz_badge wb
                WHERE wb.type = ? AND wb.type2 = ?";

        $res = $this->db_slave()->query($sql, array($type1, $type2));  
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /* 
        회원 뱃지 지급여부 체크 및 지급 
        $badge = badge_mdl/row_badge_info() 
    */
    public function checked_member_badge_award($wm_uid, $badge)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT wmb.uid
                FROM wiz_member_badge wmb
                WHERE wmb.uid = ? AND wmb.badge_id = ?";

        $res = $this->db_master()->query($sql, array($wm_uid, $badge['wb_id']));       
        
        // 뱃지가 지급 안되어있을때 뱃지 지급
        if($res->num_rows() == 0)
        {
            $wiz_member_badge = array(
                'uid' => $wm_uid,
                'badge_id' => $badge['wb_id'],
                'use_yn' => 'N',
                'regdate' => date('Y-m-d H:i:s')
            );
                
            $this->db_master()->insert('wiz_member_badge', $wiz_member_badge);  

            // 뱃지지급시 알림메시지 있는 경우만 알림 전송
            if($badge['wb_award_message'])
            {
                // 뱃지지급 알림
                $notify = array(
                    'uid' => $wm_uid, 
                    'code' => 500,
                    'user_name' => 'SYSTEM',
                    'message' => $badge['wb_award_message'],  
                    'regdate' => date('Y-m-d H:i:s'),
                );

                $this->db_master()->insert('mint_notify', $notify);  
            }

        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    // 회원 최근 블랙리스트 이력  
    public function blacklist_by_wm_uid($wm_uid)
    {
        $this->db_connect('slave');

        $this->db_slave()->trans_start();

        $sql = "SELECT kind 
                FROM mint_blacklist 
                WHERE uid = ? 
                ORDER BY b_uid DESC 
                LIMIT 1";

        $res = $this->db_slave()->query($sql, array($wm_uid));  
    
        return $res->num_rows() > 0 ? $res->row_array() : NULL;

    }

    // 회원 닉네임 변경 이력
    public function insert_nickname_log($nickname_log)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_member_nickname_log', $nickname_log);
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;

    }

    // 회원 획득한 트로피 카운트
    public function get_count_trophy_list($wm_uid)
    {
        $this->db_connect('slave');

        $this->db_slave()->trans_start();

        $sql = "SELECT count(1) AS cnt
                FROM mint_quest_user_tropy as mqut
                LEFT JOIN mint_quest as mq ON mq.q_idx = mqut.q_idx
                WHERE mqut.uid = ?
                ORDER BY mqut.regdate DESC";

        $res = $this->db_slave()->query($sql, array($wm_uid));  
        // echo $this->db_slave()->last_query();exit;
    
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 회원 획득한 트로피 목록
    public function get_trophy_list($wm_uid)
    {
        $this->db_connect('slave');

        $this->db_slave()->trans_start();

        $sql = "SELECT mqut.ut_idx as mqut_ut_idx,mqut.regdate as mqut_regdate,
                       mq.title as mq_title, mq.tropy_on as mq_tropy_on
                FROM mint_quest_user_tropy as mqut
                LEFT JOIN mint_quest as mq ON mq.q_idx = mqut.q_idx
                WHERE mqut.uid = ?
                ORDER BY mqut.regdate DESC";

        $res = $this->db_slave()->query($sql, array($wm_uid));  
    
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    // 트로피 사용설정 업데이트
    public function update_user_tropy($wm_uid, $ut_idx)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        //트로피 선택 정보 초기화
        $this->db_master()->set('use_yn', 'N');
        $this->db_master()->where('uid', $wm_uid);
        $this->db_master()->update('mint_quest_user_tropy');

        //선택된 트로피 활성화
        if($ut_idx)
        {
            $this->db_master()->set('use_yn', 'Y');
            $this->db_master()->where('ut_idx', $ut_idx);
            $this->db_master()->update('mint_quest_user_tropy');
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    
    
    //시니어되기 직전인 주니어 목록
    public function junior_member_list_for_senior($date)
    {
        $this->db_connect('slave');

        $sql = "SELECT uid, birth, lev_gubun, wiz_id
                FROM wiz_member as wm
                where lev_gubun = 'JUNIOR' AND date_format(from_days(to_days(birth)),'%Y-%m-%d') <='".$date."'";

        $res = $this->db_slave()->query($sql);  
    
        return $res->num_rows() > 0 ? $res->result_array() : NULL;

    }

    public function update_member_age_levgubun()
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "UPDATE wiz_member AS m, (
                    SELECT TIMESTAMPDIFF(YEAR, birth, curdate()) as age, uid FROM wiz_member WHERE age <> TIMESTAMPDIFF(YEAR, birth, curdate())
                ) AS B
                SET 
                m.age = B.age
                WHERE 
                m.uid = B.uid";

        $this->db_master()->query($sql);  

        $sql = "UPDATE wiz_member AS m, (
                    SELECT 'SENIOR' as lev_gubun, uid FROM wiz_member wm WHERE wm.age > 15 AND lev_gubun = 'JUNIOR'
                ) AS B
                SET 
                m.lev_gubun = B.lev_gubun
                WHERE 
                m.uid = B.uid";

        $this->db_master()->query($sql);  

        $sql = "UPDATE wiz_member AS m, (
                    SELECT 'JUNIOR' as lev_gubun, uid FROM wiz_member wm WHERE wm.age < 15 AND lev_gubun = 'SENIOR'
                ) AS B
                SET 
                m.lev_gubun = B.lev_gubun
                WHERE 
                m.uid = B.uid";

        $this->db_master()->query($sql);  

        $this->db_master()->trans_complete();

        return 1;
    }

    
    public function checked_every_phone_number($phone)
    {
        $this->db_connect('slave');

        $sql = "SELECT uid, wiz_id, name, del_yn 
                FROM wiz_member 
                WHERE replace(mobile,'-','')= ? or replace(tel,'-','')= ? or replace(pmobile,'-','')=? ORDER BY uid DESC";

        $res = $this->db_slave()->query($sql,array($phone,$phone,$phone));  
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;

    }

    public function list_dealer_with_sms_ok()
    {
        $this->db_connect('slave');

        $sql = "SELECT d_id FROM wiz_dealer WHERE d_id != '' AND d_sms = 'N'";

        $res = $this->db_slave()->query($sql);  
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;

    }

    /**
     * mint_log 남기기
     * 민트 로그 작성
     * 필수==> uid, type, content, regdate
     */
    public function insert_mint_log($log)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_log', $log);

        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function list_count_wiz_member_correct_gift($where)
    {
        $this->db_connect('slave');
                                                                                                                                                
        $sql = sprintf("SELECT count(1) AS cnt
                        FROM wiz_member_correct_gift wmcg
                        %s", $where);


        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_wiz_member_correct_gift($where, $order, $limit)
    {
        $this->db_connect('slave');


        $sql = sprintf("SELECT wmcg.cidx AS wmcg_cidx, wmcg.uid AS wmcg_uid, wmcg.to_uid AS wmcg_to_uid, wmcg.pay AS wmcg_pay, wmcg.price AS wmcg_price, wmcg.comment AS wmcg_comment,
                        wmcg.use_startdate AS wmcg_use_startdate, wmcg.use_enddate AS wmcg_use_enddate, wmcg.used AS wmcg_used, wmcg.use_datetime AS wmcg_use_datetime,
                        wmcg.etc AS wmcg_etc, wmcg.memo AS wmcg_memo
                        FROM wiz_member_correct_gift wmcg 
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


}










