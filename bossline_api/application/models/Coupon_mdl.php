<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Coupon_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    /*
        쿠폰 정보 조회 sql이지만 쿠폰 사용유무가 포함되어 있어 masterDB에서 조회
        - slave에서 조회시 master > slave로 정보 전달하기 까지 사이 간극이 발생해 어뷰징 사례 있을수 있음
    */
    public function row_coupon_config_by_cp_id($cp_id)
    {
        $this->db_connect('master');
                                                                                                                                                
        $sql = "SELECT  wc.cp_id as wc_cp_id, wc.uid as wc_uid, wc.is_delete as wc_is_delete,
                        wmc.coupon_id as wmc_coupon_id, wmc.coupon_group as wmc_coupon_group, wmc.gubun as wmc_gubun, wmc.is_entire as wmc_is_entire,
                        wmc.release_cnt as wmc_release_cnt, wmc.d_id as wmc_d_id, wmc.title as wmc_title, wmc.onoff as wmc_onoff, wmc.validate_s as wmc_validate_s,
                        wmc.validate as wmc_validate, wmc.price as wmc_price, wmc.point as wmc_point, wmc.point_use as wmc_point_use, wmc.point_useN_apply_all as wmc_point_useN_apply_all,
                        wmc.postpone_use as wmc_postpone_use, wmc.num as wmc_num, wmc.e_kind as wmc_e_kind, wmc.coupon_use_cnt as wmc_coupon_use_cnt,
                        wmc.coupon_type as wmc_coupon_type, wmc.regdate as wmc_regdate, wclrl.idx as wclrl_idx
                FROM wiz_coupon wc
                INNER JOIN wiz_mcoupon wmc ON wc.coupon_id = wmc.coupon_id 
                LEFT JOIN wiz_class_limit_release_log as wclrl ON wclrl.code = wc.cp_id 
                WHERE  wc.cp_id = ? ";

        $res = $this->db_master()->query($sql, array($cp_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    //포인트->수업변환횟수제한 해제 쿠폰 사용
    public function coupon_increase_limit($log, $after_release_cnt)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        //쿠폰사용 로그입력
        $this->db_master()->insert('wiz_class_limit_release_log', $log);

        //출석부 제한해제 변경
        $this->db_master()->set('release_cnt', $after_release_cnt, FALSE);
        $this->db_master()->where('lesson_id', $log['lesson_id']);
        $this->db_master()->update('wiz_lesson');
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function list_count_usage_coupon_by_uid_used($uid)
    {
        $this->db_connect('slave');
                                                                                                                                                
        $sql = "SELECT count(1) AS cnt 
                FROM wiz_class_limit_release_log AS wclrl USE INDEX(idx_uid_type)
                WHERE wclrl.uid = ? AND wclrl.type = '1' ";

        $res = $this->db_slave()->query($sql, array($uid));

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function list_usage_coupon_by_uid_used($where, $order, $limit, $wiz_member)
    {
        $this->db_connect('slave');


        $sql = sprintf("SELECT wclrl.idx AS wclrl_idx, wclrl.uid wclrl_uid, wclrl.lesson_id AS wclrl_lesson_id, wclrl.code AS wclrl_code, 
                        wclrl.content AS wclrl_content, wclrl.regdate AS wclrl_regdate, wl.cl_name AS wl_cl_name, wl.endday as wl_endday, wc.is_delete AS wc_is_delete,
                        wmc.title AS wmc_title, wmc.validate AS wmc_validate, wmc.release_cnt AS wmc_release_cnt, wmc.point AS wmc_point, wc.cp_id AS wc_cp_id, 
                        wl.lesson_gubun AS wl_lesson_gubun, wl.cl_label AS wl_cl_label
                        FROM wiz_class_limit_release_log AS wclrl USE INDEX(idx_uid_type)
                        INNER JOIN wiz_lesson AS wl ON wclrl.lesson_id = wl.lesson_id 
                        INNER JOIN wiz_coupon AS wc ON wclrl.code = wc.cp_id AND wc.uid = '{$wiz_member['wm_uid']}'
                        INNER JOIN wiz_mcoupon AS wmc ON wc.coupon_id = wmc.coupon_id 
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_count_usage_coupon_by_uid_remain($uid)
    {
        $this->db_connect('slave');
                                                                                                                                                
        $sql = "SELECT count(1) as cnt FROM wiz_coupon as wc 
                INNER JOIN wiz_mcoupon as wmc ON wc.coupon_id=wmc.coupon_id 
                LEFT OUTER JOIN wiz_class_limit_release_log as wclrl ON wc.cp_id = wclrl.code AND wclrl.uid = ?
                WHERE wc.uid = ? AND wmc.gubun = '2' AND wmc.is_entire = '0' AND wclrl.idx IS NULL AND wc.is_delete = '0' ";

        $res = $this->db_slave()->query($sql, array($uid, $uid));

        // echo $this->db_slave()->last_query();exit;

        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_usage_coupon_by_uid_remain($where, $order, $limit, $wiz_member)
    {
        $this->db_connect('slave');


        $sql = sprintf("SELECT wmc.title AS wmc_title, wmc.validate AS wmc_validate, wmc.release_cnt AS wmc_release_cnt, wmc.point AS wmc_point, wc.cp_id AS wc_cp_id,
                        wclrl.idx AS wclrl_idx, wclrl.uid wclrl_uid, wclrl.lesson_id AS wclrl_lesson_id, wclrl.code AS wclrl_code, 
                        wclrl.content AS wclrl_content, wclrl.regdate AS wclrl_regdate
                        FROM wiz_coupon as wc USE INDEX(PRIMARY)
                        INNER JOIN wiz_mcoupon AS wmc ON wc.coupon_id=wmc.coupon_id 
                        LEFT OUTER JOIN wiz_class_limit_release_log AS wclrl ON wc.cp_id=wclrl.code AND wclrl.uid='{$wiz_member['wm_uid']}' 
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);

        // echo $this->db_slave()->last_query();exit;


        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    // 수업변환권 사용+보유 내역 // 사용하지않음
    public function list_count_usage_coupon_by_uid_total($uid)
    {
        $this->db_connect('slave');
                                                                                                                                                
        $sql = "SELECT count(1) as cnt FROM wiz_coupon as wc 
                INNER JOIN wiz_mcoupon as wmc ON wc.coupon_id=wmc.coupon_id 
                LEFT OUTER JOIN wiz_class_limit_release_log as wclrl ON wc.cp_id = wclrl.code AND wclrl.uid = ?
                WHERE wc.uid = ? AND wmc.gubun = '2' AND wmc.is_entire = '0' AND wc.is_delete = '0'
                ";

        $res = $this->db_slave()->query($sql, array($uid, $uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    // 수업변환권 사용+보유 내역 // 사용하지않음
    public function list_usage_coupon_by_uid_total($where, $order, $limit, $wiz_member)
    {
        $this->db_connect('slave');


        $sql = sprintf("SELECT wmc.title AS wmc_title, wmc.validate AS wmc_validate, wmc.release_cnt AS wmc_release_cnt, wmc.point AS wmc_point, wc.cp_id AS wc_cp_id,
                        wclrl.idx AS wclrl_idx, wclrl.uid wclrl_uid, wclrl.lesson_id AS wclrl_lesson_id, wclrl.code AS wclrl_code, 
                        wclrl.content AS wclrl_content, wclrl.regdate AS wclrl_regdate, wl.cl_name AS wl_cl_time, wc.is_delete AS wc_is_delete
                        FROM wiz_coupon AS wc USE INDEX(PRIMARY)
                        INNER JOIN wiz_mcoupon AS wmc ON wc.coupon_id=wmc.coupon_id 
                        LEFT OUTER JOIN wiz_class_limit_release_log as wclrl ON wc.cp_id=wclrl.code AND wclrl.uid='{$wiz_member['wm_uid']}' 
                        LEFT OUTER JOIN wiz_lesson AS wl ON wclrl.lesson_id = wl.lesson_id 
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function list_count_wiz_lesson_coupon_by_uid($uid)
    {
        $this->db_connect('slave');
                                                                                                                                                
        $sql = "SELECT count(1) AS cnt FROM wiz_lesson AS wl
                WHERE wl.uid = ? AND wl.payment LIKE 'coupon%'";

        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_wiz_lesson_coupon_by_uid($where, $order, $limit)
    {
        $this->db_connect('slave');


        $sql = sprintf("SELECT wc.senddate AS wc_senddate, wc.validate AS wc_validate, wc.cp_id AS wc_cp_id, wmc.title AS wmc_title, wmc.point AS wmc_point,
                        wl.endday AS wl_endday, wl.cl_id AS wl_cl_id, wl.plandate AS wl_plandate, wl.lesson_id AS wl_lesson_id, wl.student_su AS wl_student_su,
                        wl.pay_ok AS wl_pay_ok, wl.cl_name AS wl_cl_name, wl.payment AS wl_payment, wl.plandate AS wl_plandate, wc.use_ok AS wc_use_ok
                        FROM wiz_lesson AS wl
                        LEFT OUTER JOIN wiz_pay wp ON wl.lesson_id = wp.lesson_id
                        LEFT OUTER JOIN wiz_coupon wc ON wp.coupon_num = wc.cp_id
                        LEFT OUTER JOIN wiz_mcoupon wmc ON wc.coupon_id = wmc.coupon_id
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    

    /**
     * 유효한 쿠폰인지 체크
     * 쿠폰이 있는지
     * 사용하기전 쿠폰인지 (use_ok='N')
     * 유효기간이 지나지 않은 쿠폰인지 (validate_s ~ validate)
     */
    public function chk_valid_coupon($cp_id, $date, $regist='')
    {
        $this->db_connect('slave');

        if($regist == 'lesson')
        {
            //TODO: 사용처가 아직 불분명함 사용할때 한번더 체크해보아야됨
            //여기선 쿠폰 사용여부는 체크안한다
            //wiz_coupon의 cp_id값이여야만 정확한 사용여부 체크가 가능
            $sql = "SELECT wl.lesson_id as wl_lesson_id,
                           wmc.coupon_id as wmc_coupon_id, wmc.validate as wmc_validate, wmc.d_id as dealer_id, wmc.e_kind as wmc_e_kind, wmc.coupon_group as wmc_coupon_group,
                           wmc.coupon_use_cnt as wmc_coupon_use_cnt, wmc.coupon_type as wmc_coupon_type, wmc.point as wmc_point
                    FROM wiz_lesson as wl
                    JOIN wiz_class as wc ON wl.cl_id=wc.cl_id
                    JOIN wiz_mcoupon as wmc ON wmc.coupon_id=wc.coupon_id
                    WHERE wl.payment='coupon:' AND wl.lesson_id=? AND wl.plandate='0000-00-00 00:00:00'
                          AND wmc.validate_s <= '".$date."' AND wmc.validate >= '".$date."'
                    LIMIT 1";
        }
        else
        {
            $sql = "SELECT wmc.coupon_id as wmc_coupon_id, wmc.validate as wmc_validate, wmc.d_id as dealer_id, wmc.e_kind as wmc_e_kind, wmc.coupon_group as wmc_coupon_group,
                           wmc.coupon_use_cnt as wmc_coupon_use_cnt, wmc.coupon_type as wmc_coupon_type, wmc.point as wmc_point, wcp.cp_id as wcp_cp_id
                    FROM wiz_mcoupon as wmc
                    JOIN wiz_coupon as wcp ON wmc.coupon_id=wcp.coupon_id
                    WHERE wcp.cp_id=? AND wmc.validate_s != '0000-00-00' AND wcp.use_ok='N'
                          AND wmc.validate_s <= '".$date."' AND wmc.validate >= '".$date."' ";
        }

        $res = $this->db_slave()->query($sql, array($cp_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 쿠폰 상품 수업 정보
    public function get_coupon_class($cp_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wc.cl_id as wc_cl_id, wc.tel_fee as wc_tel_fee, wc.cl_time as wc_cl_time, wc.cl_number as wc_cl_number,
                       wc.cl_class as wc_cl_class, wc.cl_month as wc_cl_month, wc.hold_num as wc_hold_num, wc.cl_service as wc_cl_service,
                       wc.student_su as wc_student_su, wc.student_uid as wc_student_uid, wc.time_start as wc_time_start, wc.time_end as wc_time_end,
                       wcd.cl_name as wcd_cl_name, wcd.lesson_gubun as wcd_lesson_gubun, wcd.fee as wcd_fee
                FROM wiz_class as wc
                LEFT JOIN wiz_class_direct as wcd ON wc.cl_id = wcd.cl_id
                WHERE wc.coupon_id = ? ";

        $res = $this->db_slave()->query($sql, array($cp_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 회원이 수업을 들은적이있는지 체크한다
     * 값이 없다면 신규
     */
    public function chk_lesson($wm_uid, $where='')
    {
        $this->db_connect('slave');

        $sql = "SELECT wl.endday as wl_endday
                FROM wiz_lesson as wl
                WHERE wl.uid = '".$wm_uid."' ".$where;

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 그룹 쿠폰 정보 조회
    public function group_coupon_info($group_key)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT wcg.group_use_cnt as wcg_group_use_cnt
                FROM wiz_coupon_group as wcg
                WHERE group_key = '".$group_key."'";
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 그룹 쿠폰 사용 횟수 조회
    public function group_coupon_use_count($coupon_group, $wm_uid)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT count(1) as cnt
                FROM wiz_mcoupon as wmc
                JOIN wiz_coupon as wcp ON wmc.coupon_id=wcp.coupon_id
                WHERE wmc.coupon_group='".$coupon_group."' AND wcp.uid='".$wm_uid."'";
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 쿠폰 사용 횟수 조회
    public function coupon_use_count($coupon_id, $wm_uid)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT count(1) as cnt
                FROM wiz_coupon as wcp
                WHERE wcp.coupon_id='".$coupon_id."' AND wcp.uid='".$wm_uid."'";
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 회원 쿠폰수업 미등록 수업 체크
    public function chk_lesson_unregistered($date, $wm_uid)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT count(1) as cnt
                FROM wiz_lesson as wl
                JOIN wiz_class as wc ON wl.cl_id=wc.cl_id
                JOIN wiz_mcoupon as wmc ON wc.coupon_id=wmc.coupon_id
                WHERE wl.payment='coupon:' AND wl.plandate='0000-00-00 00:00:00' AND wl.uid='".$wm_uid."'
                      AND wmc.validate_s <= '".$date."' AND wmc.validate >='".$date."' AND wmc.validate_s!='0000-00-00'";
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    //쿠폰 로그 등록
    public function insert_coupon_log($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_coupon_log', $param);
        $insert_id = $this->db_master()->insert_id();
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    //쿠폰 로그 수정
    public function update_wiz_coupon_log($param,$wm_uid,$cp_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('uid', $wm_uid);
        $this->db_master()->where('mint_coupon_number', $cp_id);
        $this->db_master()->update('wiz_coupon_log', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    //쿠폰 수정
    public function update_wiz_coupon($param,$cp_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('cp_id', $cp_id);
        $this->db_master()->update('wiz_coupon', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

}