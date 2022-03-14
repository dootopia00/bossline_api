<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Board_mdl extends _Base_Model {


	public function __construct()
	{
		parent::__construct();

    }

    public function list_writer($where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT wm.wiz_id as wm_wiz_id, wm.birth as wm_birth, wm.grade as wm_grade,  wm.nickname as wm_nickname, wm.ename as wm_ename, wm.name as wm_name, wm.age as wm_age,
                    mmg.title as mmg_title, mmg.icon as mmg_icon, mmg.color as mmg_color, wm.profile as wm_profile, wm.age as wm_age, wm.point as wm_point, wm.uid as wm_uid,
                    mmg.bold as mmg_bold,mmg.description as mmg_description,
                    mq.title as mq_title,mq.tropy_on as mq_tropy_on,
                    wmb.badge_id as wmb_badge_id,wb.title as wb_title,wb.description as wb_description,wb.img as wb_img,wb.img_big_on as wb_img_big_on,wb.img_big_off as wb_img_big_off,
                    wm.view_boards AS wm_view_boards, wm.regi_gubun AS wm_regi_gubun, wm.email AS wm_email, wm.social_email AS wm_social_email, wm.greeting as wm_greeting
                FROM wiz_member wm
                LEFT OUTER JOIN mint_member_grade mmg ON wm.grade = mmg.id 
                LEFT OUTER JOIN wiz_member_badge wmb ON wmb.uid = wm.uid AND wmb.use_yn='Y'
                LEFT OUTER JOIN mint_quest_user_tropy mqut ON mqut.uid = wm.uid AND mqut.use_yn='Y'
                LEFT OUTER JOIN mint_quest mq ON mq.q_idx = mqut.q_idx
                LEFT OUTER JOIN wiz_badge wb ON wb.id = wmb.badge_id
                    %s", $where);
    
        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_theme($index, $where, $order, $limit)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT  mb.mb_unq as mb_mb_unq, mb.table_code as mb_table_code, mb.wiz_id as mb_wiz_id, mb.content as mb_content, mb.nickname as mb_nickname, mb.ename as mb_ename,
                            mb.noticeYn as mb_noticeYn, mb.title as mb_title, mb.filename as mb_filename, mb.certify_date as mb_certify_date,
                            mb.hit as mb_hit, mb.comm_hit as mb_comm_hit, mb.regdate as mb_regdate, mb.mob as mb_mob, mbn.table_name as mbn_table_name,
                            mb.secret as mb_secret, mb.certify_view as mb_certify_view, mb.name as mb_name,mb.recom as mb_recom,
                            mbn.certify_yn as mbn_certify_yn, mbn.anonymous_yn as mbn_anonymous_yn, mbn.secret_yn as mbn_secret_yn, mbn.list_hit as mbn_list_hit,
                            mbn.view_login as mbn_view_login,mb.thumb as mb_thumb, mb.name_hide as mb_name_hide, mb.parent_key as mb_parent_key, mb.select_key as mb_select_key, mb.set_point as mb_set_point,
                            mb.category_code as mb_category_code, mb.category_title as mb_category_title,
                            (SELECT 'N' FROM mint_boards_adopt WHERE q_mb_unq = mb_parent_key LIMIT 1) AS mb_anonymous
                        FROM mint_boards mb %s
                        INNER JOIN mint_boards_name mbn ON mb.table_code = mbn.table_code  AND mbn.list_show = 'Y'
                    %s %s %s",$index, $where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        //echo $this->db_slave()->last_query();  exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    

    public function list_board($index, $where, $order, $limit , $select_col_content = " ")
    {
        
        $this->db_connect('slave');

        $sql = sprintf("SELECT  mb.mb_unq as mb_mb_unq, mb.table_code as mb_table_code, mb.wiz_id as mb_wiz_id, 
                            mb.noticeYn as mb_noticeYn, mb.title as mb_title, mb.filename as mb_filename, mb.certify_date as mb_certify_date, 
                            mb.recom as mb_recom, mb.clip_ea as mb_clip_ea,  mb.cafe_unq as mb_cafe_unq, mb.content as mb_content,
                            mb.sim_content as mb_sim_content, mb.sim_content2 as mb_sim_content2, mb.work_state as mb_work_state, mb.star as mb_star,
                            mb.hit as mb_hit, mb.comm_hit as mb_comm_hit, mb.regdate as mb_regdate, mb.showdate as mb_showdate,
                            mb.sim_content3 as mb_sim_content3, mb.sim_content4 as mb_sim_content4, mb.select_key as mb_select_key, mb.parent_key as mb_parent_key, mb.set_point as mb_set_point,
                            mb.mob as mb_mob, mbn.table_name as mbn_table_name, 
                            mb.secret as mb_secret, mb.work_state as mb_work_state, mb.certify_view as mb_certify_view, mb.name as mb_name, mb.category_code as mb_category_code, mb.daum_img as mb_daum_img,
                            mbn.certify_yn as mbn_certify_yn, mbn.anonymous_yn as mbn_anonymous_yn, mbn.secret_yn as mbn_secret_yn, mbn.list_hit as mbn_list_hit, mb.nickname as mb_nickname,
                            mbn.view_login as mbn_view_login, mb.category_title as mb_category_title,mb.thumb as mb_thumb, mb.name_hide as mb_name_hide, mb.tu_uid as mb_tu_uid %s
                        FROM mint_boards mb %s
                        INNER JOIN mint_boards_name mbn ON mb.table_code = mbn.table_code 
                    %s %s %s", $select_col_content , $index, $where, $order, $limit);

        $res = $this->db_slave()->query($sql);  
        // echo $this->db_slave()->last_query();  exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_board_helper($index, $where, $order, $limit , $select_col_content = " ")
    {
        
        $this->db_connect('slave');

        $sql = sprintf("SELECT  mb.mb_unq as mb_mb_unq, mb.mb_unq as mb_unq, mb.table_code as mb_table_code, mb.wiz_id as mb_wiz_id, 
                            mb.noticeYn as mb_noticeYn, mb.title as mb_title, mb.filename as mb_filename, mb.certify_date as mb_certify_date, 
                            mb.recom as mb_recom, mb.clip_ea as mb_clip_ea,  mb.cafe_unq as mb_cafe_unq, mb.content as mb_content,
                            mb.sim_content as mb_sim_content, mb.sim_content2 as mb_sim_content2, mb.work_state as mb_work_state, mb.star as mb_star,
                            mb.hit as mb_hit, mb.comm_hit as mb_comm_hit, mb.regdate as mb_regdate, 
                            mb.sim_content3 as mb_sim_content3, mb.sim_content4 as mb_sim_content4, mb.select_key as mb_select_key, mb.parent_key as mb_parent_key,
                            mb.mob as mb_mob, mbn.table_name as mbn_table_name,
                            mb.secret as mb_secret, mb.work_state as mb_work_state, mb.certify_view as mb_certify_view, mb.name as mb_name, mb.category_code as mb_category_code, mb.daum_img as mb_daum_img,
                            mbn.certify_yn as mbn_certify_yn, mbn.anonymous_yn as mbn_anonymous_yn, mbn.secret_yn as mbn_secret_yn, mbn.list_hit as mbn_list_hit, mb.nickname as mb_nickname,
                            mbn.view_login as mbn_view_login, mb.category_title as mb_category_title,mb.thumb as mb_thumb, mb.name_hide as mb_name_hide, mb.tu_uid as mb_tu_uid,
                            group_concat(mba.type) as mba_type %s
                        FROM mint_boards mb %s
                        INNER JOIN mint_boards_name mbn ON mb.table_code = mbn.table_code 
                        LEFT JOIN mint_boards_adopt as mba ON mba.table_code=mb.table_code AND mba.a_mb_unq=mb.mb_unq
                    %s %s %s", $select_col_content , $index, $where, $order, $limit);

        $res = $this->db_slave()->query($sql);  
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_board_express($index, $where, $order, $limit,$select_col_content='', $inner_table='')
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT 'express' as mb_table_code, '이런표현어떻게' as mbn_table_name,
                        (select count(1) from  mint_express_com mbc WHERE mb.uid = mbc.e_id) as mb_comm_hit,
                            mb.uid as mb_uid, mb.content as mb_title, mb.regdate as mb_regdate, mb.hit as mb_hit, mb.m_name as mb_m_name,
                            mb.wiz_id as mb_wiz_id, mb.recom as mb_recom, mb.parent_key as mb_parent_key, mb.star as mb_star %s
                        FROM mint_express mb %s %s
                    %s %s %s",$select_col_content, $inner_table, $index, $where, $order, $limit);

        $res = $this->db_slave()->query($sql);  

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_board_cafeboard($index, $where, $order, $limit, $select_col_content =" ", $inner_table = " ")
    { 
        $this->db_connect('slave');

        $sql = sprintf("SELECT %s (select count(1) from  mint_cafeboard_com mbc WHERE mb.c_uid = mbc.c_uid) as mb_comm_hit,
                            mb.c_uid as mb_c_uid, mb.b_kind as mb_b_kind,
                            mb.uid as mb_uid, mb.name as mb_name, mb.notice_yn as mb_notice_yn,
                            mb.subject as mb_title, mb.regdate as mb_regdate, mb.view as mb_hit,
                            mb.clip_ea as mb_clip_ea, mb.recom as mb_recom, mb.decl as mb_decl, mb.name_hide as mb_name_hide,
                            mb.mins as mb_mins, mb.tu_name as mb_tu_name, mb.book_name as mb_book_name, mb.class_date as mb_class_date,
                            wm.wiz_id as mb_wiz_id
                        FROM mint_cafeboard mb %s %s
                    %s %s %s",$select_col_content, $index, $inner_table ,$where, $order, $limit);

        $res = $this->db_slave()->query($sql);  

        //echo $this->db_slave()->last_query();   exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_board_cafeboard_notice($index, $where, $order, $limit, $select_col_content =" ")
    { 
        $this->db_connect('slave');

        $sql = sprintf("SELECT %s (select count(1) from  mint_cafeboard_com mbc WHERE mb.c_uid = mbc.c_uid) as mb_comm_hit,
                            mb.c_uid as mb_c_uid, mb.uid as mb_uid, mb.name as mb_name, mb.notice_yn as mb_notice_yn,
                            mb.subject as mb_title, mb.regdate as mb_regdate, mb.view as mb_hit,
                            mb.clip_ea as mb_clip_ea, mb.recom as mb_recom, mb.decl as mb_decl
                        FROM mint_cafeboard mb %s
                    %s %s %s",$select_col_content, $index, $where, $order, $limit);

        $res = $this->db_slave()->query($sql);  

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_board_wiz_speak($index, $where, $order, $limit)
    {
        $this->db_connect('slave');

        $sql = "SELECT mb.sp_id as mb_sp_id, mb.wiz_id as mb_wiz_id, mb.name as mb_name, 
                    mb.sp_step as mb_sp_step, mb.sp_regdate as mb_sp_regdate, mb.sp_replydate as mb_sp_replydate,
                    wss.code_name as wss_code_name, TIMESTAMPDIFF(second, mb.sp_regdate, mb.sp_replydate) AS time_diff_second
                FROM wiz_speak mb ".$index."
                LEFT OUTER JOIN wiz_speak_sub wss ON mb.sp_gubun = wss.code".$where." ".$order." ".$limit;
#echo $sql;exit;
        $res = $this->db_slave()->query($sql);  


        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_board_wiz_correct($index, $where, $order, $limit , $inner_table = " ")
    {
        $this->db_connect('slave');


        
        $sql = sprintf("SELECT 
                            mb.w_id as mb_w_id, mb.wiz_id as mb_wiz_id, mb.name as mb_name, 
                            mb.w_step as mb_w_step, mb.w_regdate as mb_w_regdate, mb.w_replydate as mb_w_replydate,
                            mb.w_title as mb_title, mb.w_kind as mb_w_kind, mb.w_mp3 as mb_w_mp3, mb.w_tutor as mb_w_tutor, 
                            mb.w_mp3_type as mb_w_mp3_type, mb.su as mb_su, mb.clip_ea as mb_clip_ea, mb.recom as mb_recom,
                            mb.tu_name as mb_tu_name, (CASE WHEN mb.w_secret='Y' THEN 'y' ELSE 'n' END) as mb_secret , mb.star as mb_star, mb.tu_uid as mb_tu_uid
                        FROM wiz_correct mb %s
                        %s
                    %s %s %s",$index, $inner_table, $where, $order, $limit);

        $res = $this->db_slave()->query($sql);  

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_count_board($index, $where)
    {
        $this->db_connect('slave');
    
        /*
        $sql = sprintf("SELECT count(1) as cnt
                FROM mint_boards mb 
                %s AND EXISTS(select 1 from mint_boards_name mbn where   mb.table_code = mbn.table_code AND mbn.list_show = 'Y')", $where);
        */
        $sql = sprintf("SELECT count(1) as cnt
                        FROM mint_boards mb %s
                        %s", $index, $where);
                        
        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_count_board_theme($table_code)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT mbtr.total_rows AS cnt FROM mint_boards_total_rows mbtr WHERE mbtr.table_code = ?";

        $res = $this->db_slave()->query($sql, $table_code);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_count_board_wiz_speak($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
        FROM wiz_speak mb 
        %s", $where);
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_count_board_wiz_correct($where, $inner_table = ' ')
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
        FROM wiz_correct mb %s %s", $inner_table, $where);
        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_count_board_express($where, $inner_table='')
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt FROM mint_express mb  %s %s", $inner_table, $where);
        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_count_board_cafeboard($where, $inner_table = ' ')
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt FROM mint_cafeboard mb %s %s", $inner_table,$where);
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_comment($where, $order, $limit, $index='')
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mbc.writer_id as mb_wiz_id ,mbc.regdate as mbc_regdate, mbc.comment as mbc_comment, mbc.table_code as mbc_table_code, 
                        mbc.writer_nickname as mbc_writer_nickname, mbc.mb_unq as mb_mb_unq, mbc.co_unq as mbc_co_unq, mb.title as mb_title,
                        mbn.anonymous_yn as mbn_anonymous_yn, mbn.table_name as mbn_table_name,mb.name_hide as mb_name_hide, mb.parent_key as mb_parent_key, mb.select_key as mb_select_key, mb.set_point as mb_set_point
                        FROM mint_boards_comment mbc %s
                        INNER JOIN mint_boards mb ON mbc.mb_unq = mb.mb_unq 
                        INNER JOIN mint_boards_name mbn ON mbc.table_code = mbn.table_code 
                    %s %s %s",$index,$where, $order, $limit);

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function row_article_wiz_correct_by_pk($mb_unq)
    {
        $this->db_connect('slave');

        $this->db_slave()->trans_start();
                                                                                                                                                
        $sql = " SELECT mb.w_id as mb_w_id, mb.sc_id as mb_sc_id, mb.uid as mb_uid, mb.wiz_id as mb_wiz_id,
                mb.tu_uid as mb_tu_uid, mb.tu_name as mb_tu_name, mb.w_title as mb_title, mb.w_kind as mb_w_kind,
                mb.w_mp3 as mb_w_mp3, mb.w_tutor as mb_w_tutor, mb.w_memo as mb_content, mb.w_reply as mb_reply,
                mb.clip_yn as mb_config_clip_yn, mb.chk_tu_uid as mb_chk_tu_uid, mb.w_mp3_type as mb_w_mp3_type,
                mb.w_step as mb_w_step, mb.w_secret as mb_w_secret, mb.w_view as mb_hit, mb.w_regdate as mb_regdate,
                mb.w_hopedate as mb_hopedate, mb.w_replydate as mb_replydate, mb.filename as mb_tutor_upfile, mb.filename2 as mb_student_upfile,
                mb.mob as mb_mob, mb.rsms as mb_rsms, mb.star as mb_star, mb.su as mb_su, mb.recom as mb_recom ,mb.name as mb_name, mb.ename as mb_ename,
                wm.nickname as wm_nickname, wm.mobile as wm_mobile, wm.uid as wm_uid, wm.regi_gubun AS wm_regi_gubun, wm.email AS wm_email, wm.social_email AS wm_social_email 
                FROM wiz_correct mb 
                LEFT OUTER JOIN wiz_member wm ON mb.wiz_id = wm.wiz_id 
                WHERE mb.w_id = ?";

        $res = $this->db_slave()->query($sql, array($mb_unq));
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_article_wiz_correct_by_w_id($mb_unq)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
                                                                                                                                                
        $sql = " SELECT mb.w_id as mb_w_id, mb.sc_id as mb_sc_id, mb.uid as mb_uid, mb.wiz_id as mb_wiz_id,
                mb.tu_uid as mb_tu_uid, mb.tu_name as mb_tu_name, mb.w_title as mb_title, mb.w_kind as mb_w_kind,
                mb.w_mp3 as mb_w_mp3, mb.w_tutor as mb_w_tutor, mb.w_memo as mb_content, mb.w_reply as mb_reply,
                mb.clip_yn as mb_config_clip_yn, mb.chk_tu_uid as mb_chk_tu_uid, mb.w_mp3_type as mb_w_mp3_type,
                mb.w_step as mb_w_step, mb.w_secret as mb_w_secret, mb.w_view as mb_hit, mb.w_regdate as mb_regdate,
                mb.w_hopedate as mb_hopedate, mb.w_replydate as mb_replydate, mb.filename as mb_tutor_upfile, mb.filename2 as mb_student_upfile,
                mb.mob as mb_mob, mb.rsms as mb_rsms, mb.star as mb_star, mb.su as mb_su, mb.recom as mb_recom ,mb.name as mb_name
                FROM wiz_correct mb 
                WHERE mb.w_id = ?";

        $res = $this->db_master()->query($sql, array($mb_unq));


        $article = $res->row_array();

        if($article)
        {
            $this->db_master()->set('w_view', $article['mb_hit'] + 1, FALSE);
            $this->db_master()->where('w_id', $mb_unq);           
            $this->db_master()->update('wiz_correct');
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return NULL;
        }

        return $res->num_rows() > 0 ? $article : NULL;
    }

    public function row_article_cafeboard_by_c_uid($mb_unq)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
                                                                                                                                                
        $sql = " SELECT 
                    mb.c_uid as mb_c_uid , mb.uid as mb_uid, mb.name as mb_name, mb.notice_yn as mb_notice_yn,
                    mb.subject as mb_title, mb.regdate as mb_regdate, mb.view as mb_hit, mb.b_kind as mb_b_kind,
                    mb.clip_ea as mb_clip_ea, mb.recom as mb_recom, mb.decl as mb_decl, mb.name_hide as mb_name_hide,
                    mb.mins as mb_mins, mb.tu_name as mb_tu_name, mb.book_name as mb_book_name, mb.class_date as mb_class_date,
                    mb.clip_yn as mb_config_clip_yn,mb.vd_url as mb_vd_url,
                    mb.content as mb_content , mb.content2 as mb_content2, mb.postscript as mb_postscript,
                    mb.filename as mb_filename, mb.filename2 as mb_filename2,
                    wm.wiz_id as mb_wiz_id, wm.grade as wm_grade, mmg.icon as mmg_icon, mmg.description as mmg_description
                FROM mint_cafeboard mb             
                LEFT OUTER JOIN wiz_member wm ON wm.uid = mb.uid 
                LEFT OUTER JOIN mint_member_grade mmg ON wm.grade = mmg.id
                WHERE mb.c_uid = ?";

        $res = $this->db_master()->query($sql, array($mb_unq));

        $article = $res->row_array();

        if($article)
        {
            $this->db_master()->set('view', $article['mb_hit'] + 1, FALSE);
            $this->db_master()->where('c_uid', $mb_unq);           
            $this->db_master()->update('mint_cafeboard');
        }

        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return NULL;
        }

        return $res->num_rows() > 0 ? $article : NULL;
    }
    
    public function row_article_cafeboard_by_pk($mb_unq)
    {
        $this->db_connect('slave');

        $sql = " SELECT 
                    mb.c_uid as mb_c_uid , mb.uid as mb_uid, mb.name as mb_name, mb.ename as mb_ename, mb.notice_yn as mb_notice_yn,
                    mb.subject as mb_title, mb.regdate as mb_regdate, mb.view as mb_hit, mb.b_kind as mb_b_kind,
                    mb.clip_ea as mb_clip_ea, mb.recom as mb_recom, mb.decl as mb_decl, mb.name_hide as mb_name_hide,
                    mb.mins as mb_mins, mb.tu_name as mb_tu_name, mb.book_name as mb_book_name, mb.class_date as mb_class_date,
                    mb.clip_yn as mb_config_clip_yn,mb.vd_url as mb_vd_url, mb.del_yn as mb_del_yn,
                    mb.content as mb_content , mb.content2 as mb_content2, mb.postscript as mb_postscript,
                    mb.filename as mb_filename, mb.filename2 as mb_filename2,
                    wm.wiz_id as mb_wiz_id, wm.grade as wm_grade, wm.nickname as wm_nickname
                FROM mint_cafeboard mb             
                LEFT OUTER JOIN wiz_member wm ON mb.uid = wm.uid 
                WHERE mb.c_uid = ?";

        $res = $this->db_slave()->query($sql, array($mb_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /*
        상세보기용 (조회수 증가)
    */
    public function row_article_by_mb_unq($table_code, $mb_unq)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
                                                                                                                                                
        $sql = " SELECT mb.mb_unq as mb_unq, mb.table_code as mb_table_code, mb.wiz_id as mb_wiz_id,
                    mb.noticeYn as mb_noticeYn, mb.title as mb_title, mb.filename as mb_filename,
                    mb.hit as mb_hit, mb.comm_hit as mb_comm_hit, mb.regdate as mb_regdate, mb.mob as mb_mob, mbn.table_name as
                    mbn_table_name, mb.nickname as mb_nickname, mb.name as mb_name, mb.ename as mb_ename, mb.star as mb_star, mb.work_state as mb_work_state,
                    mb.content as mb_content, mb.secret as mb_secret, mb.certify_view as mb_certify_view, mbn.recom_yn as mbn_recom_yn,
                    mb.recom as mb_recom, mb.clip_yn as mb_config_clip_yn ,mb.clip_ea as mb_clip_ea, mbn.mobile_yn as mbn_mobile_yn, mbn.view_login
                    as mbn_view_login, mc.b_kind as mc_b_kind, mb.c_yn as mb_c_yn, mb.input_txt as mb_input_txt,mb.daum_img as mb_daum_img,
                    mb.sim_content as mb_sim_content, mb.sim_content2 as mb_sim_content2,
                    mb.sim_content3 as mb_sim_content3, mb.sim_content4 as mb_sim_content4 , mb.category_code as mb_category_code, mb.category_title as mb_category_title,
                    mb.thumb as mb_thumb, mb.name_hide as mb_name_hide, mb.rsms as mb_rsms, mb.tu_uid as mb_tu_uid, mb.cafe_unq as mb_cafe_unq, 
                    mb.select_key as mb_select_key, mb.parent_key as mb_parent_key, mb.set_point as mb_set_point, mb.showdate as mb_showdate,
                    mbn.user_adopt_reward_point as mbn_user_adopt_reward_point, mbn.recom_adopt_reward_point as mbn_recom_adopt_reward_point
                FROM mint_boards mb 
                INNER JOIN mint_boards_name mbn  ON mb.table_code = mbn.table_code 
                LEFT OUTER JOIN mint_cafeboard mc ON mc.c_uid = mb.cafe_unq
                WHERE mb.table_code = ? AND mb.mb_unq = ?";

        $res = $this->db_master()->query($sql, array($table_code, $mb_unq));

        $article = $res->row_array();

        if($article)
        {
            $this->db_master()->set('hit', $article['mb_hit'] + 1, FALSE);
            $this->db_master()->where('mb_unq', $mb_unq);           
            $this->db_master()->update('mint_boards');
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return NULL;
        }

        return $res->num_rows() > 0 ? $article : NULL;
    }

    public function row_board_by_mb_unq($table_code, $mb_unq)
    {
        $this->db_connect('slave');

        $sql = " SELECT mb.mb_unq as mb_unq, mb.table_code as mb_table_code, mb.wiz_id as mb_wiz_id,
                    mb.noticeYn as mb_noticeYn, mb.title as mb_title, mb.filename as mb_filename,
                    mb.hit as mb_hit, mb.comm_hit as mb_comm_hit, mb.regdate as mb_regdate, mb.mob as mb_mob, mbn.table_name as
                    mbn_table_name, mbn.anonymous_yn as mbn_anonymous_yn, mb.nickname as mb_nickname, mb.name as mb_name, mb.ename as mb_ename, mb.star as mb_star, mb.work_state as mb_work_state,
                    mb.content as mb_content, mb.secret as mb_secret, mb.certify_view as mb_certify_view, mbn.recom_yn as mbn_recom_yn,
                    mb.recom as mb_recom, mb.clip_yn as mb_config_clip_yn ,mb.clip_ea as mb_clip_ea, mbn.mobile_yn as mbn_mobile_yn, mbn.view_login
                    as mbn_view_login, mc.b_kind as mc_b_kind, mc.vd_url as mc_vd_url, mc.filename as mc_filename, mb.c_yn as mb_c_yn, mb.input_txt as mb_input_txt,mb.daum_img as mb_daum_img,
                    mb.sim_content as mb_sim_content, mb.sim_content2 as mb_sim_content2,
                    mb.sim_content3 as mb_sim_content3, mb.sim_content4 as mb_sim_content4 , mb.category_code as mb_category_code, mb.category_title as mb_category_title,
                    mb.thumb as mb_thumb, mb.name_hide as mb_name_hide, mb.rsms as mb_rsms, mb.tu_uid as mb_tu_uid, mb.cafe_unq as mb_cafe_unq, 
                    mb.select_key as mb_select_key, mb.parent_key as mb_parent_key, mb.set_point as mb_set_point,
                    wm.uid as wm_uid, wm.regi_gubun as wm_regi_gubun, wm.social_email as wm_social_email, wm.ename AS wm_ename
                FROM mint_boards mb 
                INNER JOIN mint_boards_name mbn  ON mb.table_code = mbn.table_code 
                LEFT OUTER JOIN wiz_member wm ON mb.wiz_id = wm.wiz_id
                LEFT OUTER JOIN mint_cafeboard mc ON mc.c_uid = mb.cafe_unq
                WHERE mb.table_code = ? AND mb.mb_unq = ?";

        $res = $this->db_slave()->query($sql, array($table_code, $mb_unq));
        // echo $this->db_slave()->last_query();exit;
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_article_express_by_uid($mb_unq)
    {
        $this->db_connect('slave');

        $sql = " SELECT 'express' as mb_table_code, '이런표현어떻게' as mbn_table_name,
                (select count(1) from  mint_express_com mbc WHERE mb.uid = mbc.e_id) as mb_comm_hit, mb.parent_key AS mb_parent_key,
                        mb.uid as mb_unq, mb.subject as mb_title, mb.content as mb_content, mb.regdate as mb_regdate, mb.hit as mb_hit, mb.m_name as mb_name,
                        mb.wiz_id as mb_wiz_id, mb.recom as mb_recom, wm.grade as wm_grade, mmg.icon as mmg_icon,mmg.description as mmg_description, wm.nickname as wm_nickname, wm.ename as wm_ename,
                        mb.clip_ea as mb_clip_ea, mb.clip_yn as mb_config_clip_yn, mb.certify_view as mb_certify_view, mb.certify_date as mb_certify_date, mb.rsms as mb_rsms, mb.mob as mb_mob
                FROM mint_express mb 
                LEFT OUTER JOIN wiz_member wm ON wm.wiz_id = mb.wiz_id 
                LEFT OUTER JOIN mint_member_grade mmg ON wm.grade = mmg.id
                WHERE mb.uid = ?";

        $res = $this->db_slave()->query($sql, array($mb_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function row_article_express_by_mb_uid($mb_unq)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
                                                                                                                                                
        $sql = " SELECT 'express' as mb_table_code, '이런표현어떻게' as mbn_table_name,
                (select count(1) from  mint_express_com mbc WHERE mb.uid = mbc.e_id) as mb_comm_hit,
                        mb.uid as mb_uid, mb.content as mb_title, mb.regdate as mb_regdate, mb.hit as mb_hit, mb.m_name as mb_m_name,mb.parent_key AS mb_parent_key,
                        mb.wiz_id as mb_wiz_id, mb.recom as mb_recom, wm.grade as wm_grade, mmg.icon as mmg_icon,mmg.description as mmg_description, wm.nickname as wm_nickname,
                        mb.clip_ea as mb_clip_ea, mb.clip_yn as mb_config_clip_yn, mb.certify_view as mb_certify_view, mb.rsms as mb_rsms, wm.uid AS wm_uid, wm.name AS wm_name,
                        mb.sim_content3 as mb_sim_content3, mb.sim_content4 as mb_sim_content4, mb.star as mb_star,
                        mbn.user_adopt_reward_point as mbn_user_adopt_reward_point, mbn.recom_adopt_reward_point as mbn_recom_adopt_reward_point,
                        wm.mobile AS wm_mobile
                FROM mint_express mb 
                LEFT OUTER JOIN mint_etcboards_set mbn ON mbn.table_code = 9001
                LEFT OUTER JOIN wiz_member wm ON wm.wiz_id = mb.wiz_id 
                LEFT OUTER JOIN mint_member_grade mmg ON wm.grade = mmg.id
                WHERE mb.uid = ?";

        $res = $this->db_master()->query($sql, array($mb_unq));
        // echo $this->db_master()->last_query();exit;
        $article = $res->row_array();

        if($article)
        {
            $this->db_master()->set('hit', $article['mb_hit'] + 1, FALSE);
            $this->db_master()->where('uid', $mb_unq);           
            $this->db_master()->update('mint_express');
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return NULL;
        }

        return $res->num_rows() > 0 ? $article : NULL;
    }
    
    public function row_article_request_by_sp_id($mb_unq)
    {
        $this->db_connect('slave');

        $sql = " SELECT 'request' as mb_table_code, '실시간요청게시판' as mbn_table_name, mb.sp_id as mb_sp_id,
                        mb.uid as mb_uid,  mb.wiz_id as mb_wiz_id, mb.name as mb_name, 
                        mb.sp_gubun as mb_gubun, mb.sp_title as mb_title, mb.sp_memo as mb_content,
                        mb.editor_yn as mb_editor_yn, mb.sp_pic as mb_pic, mb.sp_reply as mb_reply,
                        mb.sp_step as mb_step, mb.sp_regdate as mb_regdate, mb.sp_replydate as mb_replydate, mb.man_id as mb_manager_id,
                        mb.man_name as mb_manager_name, mb.sp_time as mb_sp_time, mb.sp_header, mb.sp_bottom, mb.sp_etc as mb_sp_etc, 
                        mb.filename as mb_filename, mb.mob as mb_mob, mb.ip as mb_ip, 
                        wm.grade as wm_grade, mmg.icon as mmg_icon,mmg.description as mmg_descriptionmm,
                        (SELECT code_name FROM wiz_speak_sub WHERE code = LEFT(mb.sp_gubun,2)) as mb_gubun_name1,
                        (SELECT code_name FROM wiz_speak_sub WHERE code = mb.sp_gubun) as mb_gubun_name2
                FROM wiz_speak mb 
                LEFT OUTER JOIN wiz_member wm ON wm.wiz_id = mb.wiz_id 
                LEFT OUTER JOIN mint_member_grade mmg ON wm.grade = mmg.id
                WHERE mb.sp_id = ?";

        $res = $this->db_slave()->query($sql, array($mb_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_article_toteacher_by_to_id($mb_unq)
    {
        $this->db_connect('slave');

        $sql = " SELECT 'toteacher' as mb_table_code, '강사와1:1게시판' as mbn_table_name, mb.to_id as mb_to_id,
                        mb.uid as mb_uid,  mb.wiz_id as mb_wiz_id, mb.name as mb_name, mb.ename as mb_ename,
                        mb.tu_uid as mb_tu_uid, mb.tu_name as mb_tu_name, mb.title as mb_title, mb.memo as mb_content, mb.reply as mb_reply,
                        mb.step as mb_step, mb.to_gubun as mb_to_gubun, mb.c_yn as mb_c_yn, mb.r_yn as mb_r_yn, mb.regdate as mb_regdate, 
                        mb.replydate as mb_replydate,
                        mb.filename as mb_filename, mb.filename2 as mb_filename2, mb.filename3 as mb_filename3, mb.filename4 as mb_filename4,
                        wm.grade as wm_grade, mmg.icon as mmg_icon,mmg.description as mmg_description
                FROM wiz_toteacher mb 
                LEFT OUTER JOIN wiz_member wm ON wm.wiz_id = mb.wiz_id 
                LEFT OUTER JOIN mint_member_grade mmg ON wm.grade = mmg.id
                WHERE mb.to_id = ?";

        $res = $this->db_slave()->query($sql, array($mb_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_pre_article_by_mb_unq($mb_unq , $where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mb.mb_unq as mb_mb_unq, mb.title as mb_title
                        FROM mint_boards mb 
                        WHERE mb.mb_unq < ? 
                        %s
                        ORDER BY mb.mb_unq DESC LIMIT 1", $where);

        $res = $this->db_slave()->query($sql, array($mb_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_pre_article_request_by_wiz_id($mb_unq , $wiz_id)
    {
        $this->db_connect('slave');

        $sql = " SELECT 'request' as mb_table_code, '실시간요청게시판' as mbn_table_name, mb.sp_id as mb_sp_id,
                    mb.uid as mb_uid,  mb.wiz_id as mb_wiz_id, mb.name as mb_name, 
                    mb.sp_regdate as mb_regdate,
                    mb.sp_gubun as mb_gubun, 
                    (SELECT code_name FROM wiz_speak_sub WHERE code = LEFT(mb.sp_gubun,2)) as mb_gubun_name1,
                    (SELECT code_name FROM wiz_speak_sub WHERE code = mb.sp_gubun) as mb_gubun_name2
                FROM wiz_speak mb 
                INNER JOIN wiz_member wm ON wm.wiz_id = mb.wiz_id 
                WHERE mb.wiz_id = ? AND mb.sp_id < ? ORDER BY mb.sp_id DESC LIMIT 1";


        $res = $this->db_slave()->query($sql, array($wiz_id, $mb_unq));

    
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_pre_article_toteacher_by_wiz_id($mb_unq , $wiz_id)
    {
        $this->db_connect('slave');

        $sql = " SELECT 'toteacher' as mb_table_code, '강사와1:1게시판' as mbn_table_name, mb.to_id as mb_to_id,
                        mb.uid as mb_uid,  mb.wiz_id as mb_wiz_id, mb.name as mb_name, mb.ename as mb_ename,
                        mb.tu_uid as mb_tu_uid, mb.tu_name as mb_tu_name, mb.title as mb_title, mb.regdate as mb_regdate
                FROM wiz_toteacher mb 
                INNER JOIN wiz_member wm ON wm.wiz_id = mb.wiz_id 
                WHERE mb.wiz_id = ? AND mb.to_id < ? ORDER BY mb.to_id DESC LIMIT 1";


        $res = $this->db_slave()->query($sql, array($wiz_id, $mb_unq));

    
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_next_article_by_mb_unq($mb_unq , $where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mb.mb_unq as mb_mb_unq, mb.title as mb_title
                        FROM mint_boards mb 
                        WHERE mb.mb_unq > ? 
                        %s
                        ORDER BY mb.mb_unq ASC LIMIT 1", $where);

        $res = $this->db_slave()->query($sql, array($mb_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function row_next_article_request_by_wiz_id($mb_unq , $wiz_id)
    {
        $this->db_connect('slave');

        $sql = " SELECT 'request' as mb_table_code, '실시간요청게시판' as mbn_table_name, mb.sp_id as mb_sp_id,
                    mb.uid as mb_uid,  mb.wiz_id as mb_wiz_id, mb.name as mb_name, 
                    mb.sp_regdate as mb_regdate,
                    mb.sp_gubun as mb_gubun, 
                    (SELECT code_name FROM wiz_speak_sub WHERE code = LEFT(mb.sp_gubun,2)) as mb_gubun_name1,
                    (SELECT code_name FROM wiz_speak_sub WHERE code = mb.sp_gubun) as mb_gubun_name2
                FROM wiz_speak mb 
                INNER JOIN wiz_member wm ON wm.wiz_id = mb.wiz_id 
                WHERE mb.wiz_id = ? AND mb.sp_id > ? ORDER BY mb.sp_id ASC LIMIT 1";


        $res = $this->db_slave()->query($sql, array($wiz_id, $mb_unq));

      
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_next_article_toteacher_by_wiz_id($mb_unq , $wiz_id)
    {
        $this->db_connect('slave');

        $sql = " SELECT 'toteacher' as mb_table_code, '강사와1:1게시판' as mbn_table_name, mb.to_id as mb_to_id,
                        mb.uid as mb_uid,  mb.wiz_id as mb_wiz_id, mb.name as mb_name, mb.ename as mb_ename,
                        mb.tu_uid as mb_tu_uid, mb.tu_name as mb_tu_name, mb.title as mb_title, mb.regdate as mb_regdate
                FROM wiz_toteacher mb 
                INNER JOIN wiz_member wm ON wm.wiz_id = mb.wiz_id 
                WHERE mb.wiz_id = ? AND mb.to_id > ? ORDER BY mb.to_id ASC LIMIT 1";


        $res = $this->db_slave()->query($sql, array($wiz_id, $mb_unq));
 
      
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function update_count_article_comment($mb_unq)
    {
        $this->db_connect('master');
        
        $this->db_master()->trans_start();
    
        $sql = "SELECT count(1) as cnt
        FROM mint_boards_comment mbc
        WHERE mbc.mb_unq = ?";

        $tmp = $this->db_master()->query($sql, array($mb_unq));
        $comment = $tmp->row_array();

        $this->db_master()->set('comm_hit', $comment['cnt']);
        $this->db_master()->where_in('mb_unq', $mb_unq);
        $this->db_master()->update('mint_boards');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $comment['cnt'];
        
    }

    public function list_article_comment($mb_unq, $select, $join_table, $order)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mbc.writer_id as mbc_wiz_id ,mbc.regdate as mbc_regdate, mbc.comment as mbc_comment, mbc.table_code as mbc_table_code, 
                            mbc.writer_nickname as mbc_writer_nickname,mbc.co_thread as mbc_co_thread , mbc.mb_unq as mb_mb_unq, mbc.co_unq as mbc_co_unq, 
                            mbc.recom as mbc_recom, mbc.notice_yn as mbc_notice_yn, mbc.co_fid as mbc_co_fid,mbc.tu_uid as mbc_tu_uid, mbc.writer_name as mbc_writer_name, mbc.writer_ename as mbc_writer_ename,
                            mbn.anonymous_yn as mbn_anonymous_yn, mbn.table_name as mbn_table_name,mb.name_hide as mb_name_hide %s
                        FROM mint_boards_comment mbc
                        INNER JOIN mint_boards_name mbn ON mbc.table_code = mbn.table_code
                        INNER JOIN mint_boards as mb ON mb.mb_unq = mbc.mb_unq
                        %s      
                        WHERE mbc.mb_unq = ? %s ", $select, $join_table, $order);

        $res = $this->db_slave()->query($sql,array($mb_unq));
        
        //echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_article_cafeboard_comment($mb_unq, $select, $join_table, $order)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mbc.unq as mbc_unq, mbc.c_uid  as mbc_c_uid, mbc.comment as mbc_comment, 
                                mbc.writer_id as mbc_writer_id, mbc.writer_name as mbc_writer_name, mbc.regdate as mbc_regdate,
                                mbc.recom as mbc_recom %s
                        FROM mint_cafeboard_com mbc
                        %s
                        WHERE mbc.c_uid = ? %s ", $select, $join_table, $order);

        $res = $this->db_slave()->query($sql,array($mb_unq));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_article_express_comment($mb_unq, $select, $join_table, $order)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT 'express' as mb_table_code, '이런표현어떻게' as mbn_table_name, 
                            mbc.e_id as mbc_e_id,  mbc.uid as mbc_co_unq, mbc.comment as mbc_comment,
                            mbc.regdate as mbc_regdate, mbc.c_name as mbc_writer_nickname, mbc.wiz_id as mbc_wiz_id,
                            mbc.recom as mbc_recom %s
                        FROM mint_express_com mbc
                        %s
                        WHERE mbc.e_id = ? %s ", $select, $join_table, $order);

        $res = $this->db_slave()->query($sql,array($mb_unq));
// echo $this->db_slave()->last_query();
        // exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function row_board_special_config_by_table_code($table_code)
    {
        $this->db_connect('slave');
                                                                                                                                                
        $sql = "SELECT 
                    'N' as mbn_anonymous_yn, mbn.table_name as mbn_table_name,
                    mbn.table_code as mbn_table_code,  mbn.limit as mbn_recom_limit, mbn.rpoint as mbn_recom_rpoint,
                    mbn.wpoint as mbn_recom_wpoint, mbn.use_yn as mbn_recom_yn, 
                    mbn.certify_yn as mbn_certify_yn, mbn.certify_move_point as mbn_certify_move_point,  mbn.certify_move_ea as mbn_certify_move_ea, 
                    mbn.use_yn_inclass as mbn_recom_yn_inclass, mbn.recom_inclass_re as mbn_recom_inclass_re,
                    mbn.review_recomm_count as mbn_review_recomm_count,
                    mbn.cafe_point as mbn_cafe_point, mbn.cafe_count as mbn_cafe_count, mbn.cafe_day_limit as mbn_cafe_day_limit,
                    mbn.recom_ea as mbn_recom_ea, mbn.recom_ea_re as mbn_recom_ea_re, mbn.adopt_ea as mbn_adopt_ea,
                    mbn.copy_yn as mbn_copy_yn, mbn.category_yn as mbn_category_yn,  mbn.copy_move_point as mbn_copy_move_point,  mbn.copy_move_ea as mbn_copy_move_ea, 
                    mbn.limit_re as mbn_recom_limit_re, mbn.wpoint_re as mbn_recom_wpoint_re, mbn.rpoint_re as mbn_recom_rpoint_re, 
                    mbn.we_attach as mbn_we_attach, mbn.edit_yn as mbn_edit_yn, mbn.user_adopt_limit as mbn_user_adopt_limit, mbn.recom_adopt_limit as mbn_recom_adopt_limit,
                    mbn.user_adopt_reward_point as mbn_user_adopt_reward_point, mbn.recom_adopt_reward_point as mbn_recom_adopt_reward_point,
                    mbn.tip_guide as mbn_tip_guide, mbn.tip_guide_yn as mbn_tip_guide_yn
                FROM mint_etcboards_set mbn 
                WHERE mbn.table_code = ? ";

        $res = $this->db_slave()->query($sql, array($table_code));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function row_board_config_by_table_code($table_code)
    {
        $this->db_connect('slave');
                                                                                                                                                
        $sql = "SELECT  mbn.unq as mbn_unq,  mbn.table_code as mbn_table_code,  mbn.table_name as mbn_table_name,  mbn.center_gubun as mbn_center_gubun,
                    mbn.loc_gubun as mbn_loc_gubun,  mbn.write_yn as mbn_write_yn,  mbn.write_login as mbn_write_login,  mbn.reply_yn as mbn_reply_yn, 
                    mbn.comment_yn as mbn_comment_yn,  mbn.comment_yn_inclass as mbn_comment_yn_inclass, 
                    mbn.comment_login as mbn_comment_login,  mbn.secret_yn as mbn_secret_yn,  mbn.file_yn as mbn_file_yn,  mbn.file_ext as mbn_file_ext,
                    mbn.list_type as mbn_list_type,  mbn.list_method as mbn_list_method,  mbn.list_show as mbn_list_show,  mbn.edit_yn as mbn_edit_yn,
                    mbn.text_su as mbn_text_su,  mbn.input_yn as mbn_input_yn,  mbn.mobile_yn as mbn_mobile_yn,  mbn.titleonly as mbn_titleonly, 
                    mbn.hide_yn as mbn_hide_yn,  mbn.list_hit as mbn_list_hit,  mbn.list_date as mbn_list_date,  mbn.search_yn as mbn_search_yn,
                    mbn.bookmark_yn as mbn_bookmark_yn,  mbn.recom_yn as mbn_recom_yn,  mbn.recom_rpoint as mbn_recom_rpoint,  
                    mbn.recom_wpoint as mbn_recom_wpoint,  mbn.recom_limit as mbn_recom_limit,  mbn.recom_rpoint_re as mbn_recom_rpoint_re, 
                    mbn.recom_wpoint_re as mbn_recom_wpoint_re,  mbn.recom_limit_re as mbn_recom_limit_re,  mbn.copy_yn as mbn_copy_yn, 
                    mbn.category_yn as mbn_category_yn,  mbn.copy_move_point as mbn_copy_move_point,  mbn.copy_move_ea as mbn_copy_move_ea, 
                    mbn.limit_yn as mbn_limit_yn,  mbn.wpoint as mbn_wpoint, mbn.wpoint_limit as mbn_wpoint_limit,  mbn.board_del_yn as mbn_board_del_yn,  
                    mbn.anonymous_yn as mbn_anonymous_yn,  mbn.recom_ea as mbn_recom_ea, mbn.adopt_ea as mbn_adopt_ea,
                    mbn.recom_ea_re as mbn_recom_ea_re,  mbn.image_required as mbn_image_required,  mbn.certify_yn as mbn_certify_yn,  
                    mbn.certify_move_point as mbn_certify_move_point,  mbn.certify_move_ea as mbn_certify_move_ea, 
                    mbn.write_yn_inclass as mbn_write_yn_inclass,  mbn.recom_yn_inclass as mbn_recom_yn_inclass, 
                    mbn.recom_inclass_re as mbn_recom_inclass_re,  mbn.view_login as mbn_view_login,  mbn.pre_title as mbn_pre_title,  
                    mbn.pre_title_lock as mbn_pre_title_lock,  mbn.pre_content as mbn_pre_content,  mbn.pre_comment as mbn_pre_comment,  
                    mbn.we_attach as mbn_we_attach,  mbn.declaration as mbn_declaration,  mbn.declaration_co as mbn_declaration_co, 
                    mbn.declaration_no as mbn_declaration_no,  mbn.check_inclass as mbn_check_inclass,  mbn.check_holding as mbn_check_holding,
                    mbn.user_adopt_limit as mbn_user_adopt_limit, mbn.recom_adopt_limit as mbn_recom_adopt_limit,
                    mbn.user_adopt_reward_point as mbn_user_adopt_reward_point, mbn.recom_adopt_reward_point as mbn_recom_adopt_reward_point,
                    mbn.tip_guide as mbn_tip_guide, mbn.tip_guide_yn as mbn_tip_guide_yn
                FROM mint_boards_name mbn 
                WHERE  mbn.table_code = ? ";

        $res = $this->db_slave()->query($sql, array($table_code));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_board_category_by_table_code($table_code,$where='')
    {
        $this->db_connect('slave');

        $sql = "SELECT mbc.bc_unq as mbc_bc_unq, mbc.table_code as mbc_table_code, mbc.title as mbc_title
                FROM mint_boards_category mbc
                WHERE mbc.table_code = ? ".$where;

        $res = $this->db_slave()->query($sql, array($table_code));
        // echo $this->db_slave()->last_query();
        // exit;
        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    
    public function list_board_category_by_bc_unq($bc_unq)
    {
        $this->db_connect('slave');

        $sql = "SELECT mbc.bc_unq as mbc_bc_unq, mbc.table_code as mbc_table_code, mbc.title as mbc_title
                FROM mint_boards_category mbc
                WHERE mbc.bc_unq = ? ";

        $res = $this->db_slave()->query($sql, array($bc_unq));

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }


    public function list_mint_boards_new_checked($where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mbn.table_code,
                            (CASE WHEN mbn.regdate >= (CURDATE()-INTERVAL 2 DAY) 
                                THEN 'Y'
                                ELSE 'N'
                            END) AS 'new',
                            conf.check_inclass, conf.check_holding
                        FROM mint_boards_new mbn
                        INNER JOIN mint_boards_name conf ON mbn.table_code = conf.table_code
                        %s", $where);
        
        $res = $this->db_slave()->query($sql);

    
        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function list_count_mint_cafeboard_checked($b_kind)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt
                FROM mint_cafeboard mc
                WHERE mc.b_kind = ? AND mc.regdate >= (CURDATE()-INTERVAL 2 DAY)";
                
        $res = $this->db_slave()->query($sql, array($b_kind));
    
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function list_count_wiz_correct_checked()
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt
                FROM wiz_correct wc
                WHERE wc.w_regdate >= (CURDATE()-INTERVAL 2 DAY)";
                
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function list_count_mint_express_checked()
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt
                FROM mint_express me
                WHERE me.regdate >= (CURDATE()-INTERVAL 2 DAY)";
                
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function list_count_mint_news_letter_checked()
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt
                FROM mint_news_letter mnl
                WHERE mnl.regdate >= (CURDATE()-INTERVAL 2 DAY)";
                
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function list_count_wiz_speak_checked()
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt
                FROM wiz_speak ws
                WHERE ws.sp_regdate >= (CURDATE()-INTERVAL 2 DAY)";
                
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function list_boards_bookmark_by_wm_uid($wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT mbb.unq as mbb_unq, mbb.code as mbb_table_code, 
                    case mbb.code 
                        when '9001' then '이런표현어떻게' 
                        when '9002' then '얼굴철판딕테이션'
                        when '9003' then '얼굴철판딕테이션'
                        when '9004' then '영어첨삭게시판'
                        when '9999' then '실시간요청게시판'
                        else mbn.table_name end as mbn_table_name
                FROM mint_boards_bookmark mbb
                LEFT OUTER JOIN mint_boards_name mbn ON mbb.code = mbn.table_code  
                WHERE mbb.uid = ? AND mbb.del_yn = 'N' ORDER BY mbn.unq";

        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function update_boards_bookmark($bookmark)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT mbb.order as mbb_max FROM mint_boards_bookmark mbb WHERE mbb.uid = ? order by mbb.unq DESC LIMIT 1";
        $res = $this->db_master()->query($sql, array($bookmark['uid']));

        $wm_bookmark = $res->row_array();

        if (!$wm_bookmark)
        {
            $this->db_master()->set('order', 1);
            $this->db_master()->insert('mint_boards_bookmark', $bookmark);
        }
        else
        {
        
            $sql = "SELECT 1 FROM mint_boards_bookmark mbb WHERE mbb.uid = ? AND mbb.code = ?";
            $res = $this->db_master()->query($sql, array($bookmark['uid'], $bookmark['code']));

            if ($res->num_rows() == 0)
            {
                $this->db_master()->set('order', $wm_bookmark['mbb_max'] + 1);
                $this->db_master()->insert('mint_boards_bookmark', $bookmark);
            }
            else
            {
                $this->db_master()->where(array('uid' => $bookmark['uid'], 'code' => $bookmark['code']));
                $this->db_master()->update('mint_boards_bookmark', $bookmark);
            }
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    

    public function bookmark_checked_by_wiz_id($wiz_id, $table_code)
    {
        $this->db_connect('slave');

        $sql = "SELECT mbb.unq as mbb_unq
                FROM mint_boards_bookmark mbb
                WHERE mbb.wiz_id = ? AND mbb.code = ? AND mbb.del_yn = 'N'";
                
        $res = $this->db_slave()->query($sql, array($wiz_id, $table_code));
        
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }
    
    public function row_article_comment_by_co_unq($co_unq)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    mbc.writer_id as mbc_wiz_id ,mbc.regdate as mbc_regdate, mbc.comment as mbc_comment, mbc.table_code as mbc_table_code, 
                    mbc.writer_nickname as mbc_writer_nickname,mbc.co_thread as mbc_co_thread , mbc.mb_unq as mb_unq, mbc.co_unq as mbc_co_unq, 
                    mbc.recom as mbc_recom, mbc.notice_yn as mbc_notice_yn, wm.uid as wm_uid, wm.name as wm_name, mbc.co_fid as mbc_co_fid,mbc.tu_uid as mbc_tu_uid
                FROM mint_boards_comment mbc
                LEFT OUTER JOIN wiz_member wm ON mbc.writer_id = wm.wiz_id      
                WHERE mbc.co_unq = ?";

        $res = $this->db_slave()->query($sql,array($co_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_count_today_recommend($wiz_uid, $table_code, $today)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    count(1) as cnt
                FROM mint_recommend mr 
                where mr.send_uid = ? and mr.table_code = ?  and left(mr.regdate,10)= ? and mr.co_unq > 0";

        $res = $this->db_slave()->query($sql,array($wiz_uid, $table_code, $today));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_count_comment_recommend($wiz_uid, $co_unq)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    count(1) as cnt
                FROM mint_recommend mr 
                where mr.send_uid = ? and mr.co_unq = ?";

        $res = $this->db_slave()->query($sql,array($wiz_uid, $co_unq));
    
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function recommend_article_commend($recommend,$special_table='',$wiz_member=array(),$board_config=array(),$recommend_key=0)
    {
        $this->db_connect('master');
        
        /* 게시판 댓글당 추천 제한여부 */
        if(false === stripos($wiz_member['wm_assistant_code'], "*recomm*"))
        {
            if($board_config['mbn_recom_ea_re'] > 0)
            {
                /* 해당 댓글 추천 수 */
                $sql = "SELECT count(1) as cnt FROM mint_recommend mr where mr.send_uid = ? and mr.co_unq = ?";
                $res = $this->db_master()->query($sql,array($wiz_member['wm_uid'], $recommend_key));
                $recommend_comment_history = $res->num_rows() > 0 ? $res->row_array() : NULL;

                if($board_config['mbn_recom_ea_re'] <= $recommend_comment_history['cnt'])
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0307";
                    $return_array['data']['err_msg'] = $board_config['mbn_table_name']." 게시판의 댓글은 댓글당 ".$board_config['mbn_recom_ea_re']."회 까지 추천이 가능합니다.";
                    return $return_array;
                }
            }

            /* 게시판 1일 댓글 추천 제한여부 */
            if($board_config['mbn_recom_limit_re'] > 0)
            {
                /* 금일 해당 게시판 댓글 추천 수 */
                $today = date('Y-m-d');
                $sql = "SELECT count(1) as cnt FROM mint_recommend mr where mr.send_uid = ? and mr.table_code = ?  and left(mr.regdate,10)= ? and mr.co_unq > 0";
                $res = $this->db_master()->query($sql,array($wiz_member['wiz_uid'], $recommend['table_code'], $today));
                $recommend_today_history = $res->num_rows() > 0 ? $res->row_array() : NULL;

                if($board_config['mbn_recom_limit_re'] <= $recommend_today_history['cnt'])
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0306";
                    $return_array['data']['err_msg'] = $board_config['mbn_table_name']." 게시판의 댓글 하루 추천 제한 수를 초과하여 ".$board_config['mbn_table_name']." 게시판의 댓글은 오늘 하루 동안 추천이 불가능합니다.";
                    return $return_array;
                }
            }
        }

        $this->db_master()->trans_start();
        $this->db_master()->insert('mint_recommend', $recommend);

        $this->db_master()->set('recom', '`recom` + 1', FALSE);

        if($special_table =='cafeboard')
        {
            $this->db_master()->where('unq', $recommend['co_unq']);
            $this->db_master()->update('mint_cafeboard_com');
        }
        elseif($special_table =='express')
        {
            $this->db_master()->where('uid', $recommend['co_unq']);
            $this->db_master()->update('mint_express_com');
        }
        else
        {
            $this->db_master()->where('co_unq', $recommend['co_unq']);
            $this->db_master()->update('mint_boards_comment');
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        else
        {
            if($special_table =='cafeboard')
            {
                $sql = "SELECT mbc.recom as mbc_recom FROM mint_cafeboard_com mbc WHERE mbc.unq = ? ";
            }
            elseif($special_table =='express')
            {
                $sql = "SELECT mbc.recom as mbc_recom FROM mint_express_com mbc WHERE mbc.uid = ? ";
            }
            else
            {
                $sql = "SELECT mbc.recom as mbc_recom FROM mint_boards_comment mbc WHERE mbc.co_unq = ? ";
            }

            $res = $this->db_master()->query($sql,array($recommend['co_unq']));
            return $res->num_rows() > 0 ? $res->row_array() : NULL;
        }

    }

    public function recommend_article_express_commend($recommend)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->insert('mint_recommend', $recommend);

        $this->db_master()->set('recom', '`recom` + 1', FALSE);
        $this->db_master()->where('uid', $recommend['co_unq']);
        $this->db_master()->update('mint_express_com');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        else
        {
            $sql = "SELECT mbc.recom as mbc_recom FROM mint_express_com mbc WHERE mbc.uid = ? ";
            $res = $this->db_master()->query($sql,array($recommend['co_unq']));
    
            return $res->num_rows() > 0 ? $res->row_array() : NULL;
        }

    }

    public function recommend_article_cafeboard_commend($recommend)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->insert('mint_recommend', $recommend);

        $this->db_master()->set('recom', '`recom` + 1', FALSE);
        $this->db_master()->where('unq', $recommend['co_unq']);
        $this->db_master()->update('mint_cafeboard_com');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        else
        {
            $sql = "SELECT mbc.recom as mbc_recom FROM mint_cafeboard_com mbc WHERE mbc.unq = ? ";
            $res = $this->db_master()->query($sql,array($recommend['co_unq']));
    
            return $res->num_rows() > 0 ? $res->row_array() : NULL;
        }

    }

    public function row_article_express_comment_by_uid($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT 'express' as mb_table_code, '이런표현어떻게' as mbn_table_name,
                    mbc.e_id as mbc_e_id,  mbc.uid as mbc_co_unq, mbc.comment as mbc_comment,
                    mbc.regdate as mbc_regdate, mbc.c_name as mbc_writer_nickname, mbc.wiz_id as mbc_wiz_id,
                    mbc.recom as mbc_recom
                FROM mint_express_com mbc
                WHERE mbc.uid = ?";

        $res = $this->db_slave()->query($sql,array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_article_cafeboard_comment_by_unq($unq)
    {
        $this->db_connect('slave');

        $sql = "SELECT mbc.unq as mbc_unq, mbc.c_uid  as mbc_c_uid, mbc.comment as mbc_comment, 
                        mbc.writer_id as mbc_wirter_id, mbc.writer_name as mbc_writer_name, mbc.regdate as mbc_regdate,
                        mbc.recom as mbc_recom
                FROM mint_cafeboard_com mbc
                WHERE mbc.unq = ?";

        $res = $this->db_slave()->query($sql,array($unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_count_today_article($wiz_uid, $table_code, $today)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    count(1) as cnt
                FROM mint_recommend mr 
                where mr.send_uid = ? and mr.table_code = ?  and left(mr.regdate,10)= ? and mr.co_unq = 0";

        $res = $this->db_slave()->query($sql,array($wiz_uid, $table_code, $today));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_count_article_recommend($wiz_uid, $mb_unq)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    count(1) as cnt
                FROM mint_recommend mr 
                WHERE mr.send_uid = ? and mr.mb_unq = ? AND mr.co_unq = 0";

        $res = $this->db_slave()->query($sql,array($wiz_uid, $mb_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function recommend_article($recommend,$wiz_member=array(),$board_config=array(),$mb_unq=0)
    {
        $this->db_connect('master');

        /* 게시판지기 추천무제한 아니면 1일 추천 제한 */
        if(false === stripos($wiz_member['wm_assistant_code'], "*recomm*"))
        {
            /* 금일 해당 게시판 추천 수 */
            if($board_config['mbn_recom_limit'] > 0)
            {
                $today = date('Y-m-d');
                $sql = "SELECT count(1) as cnt FROM mint_recommend mr 
                        where mr.send_uid = ? and mr.table_code = ?  and left(mr.regdate,10)= ? and mr.co_unq = 0";

                $res = $this->db_master()->query($sql,array($wiz_member['wm_uid'], $recommend['table_code'], $today));
                $recommend_today_history = $res->num_rows() > 0 ? $res->row_array() : NULL;
                
                if($board_config['mbn_recom_limit'] <= $recommend_today_history['cnt'])
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0306";
                    $return_array['data']['err_msg'] = $board_config['mbn_table_name']." 게시판의 하루 추천 제한 수를 초과하여 ".$board_config['mbn_table_name']." 게시판은 오늘 하루 동안 추천이 불가능합니다.";
                    return $return_array;
                }
            }
            /* 게시판 게시글당 추천 제한여부 */
            if($board_config['mbn_recom_ea'] > 0)
            {
                $sql = "SELECT count(1) as cnt FROM mint_recommend mr WHERE mr.send_uid = ? and mr.mb_unq = ? AND mr.co_unq = 0";
                $res = $this->db_master()->query($sql,array($wiz_member['wm_uid'], $mb_unq));

                $recommend_article_history_cnt = $res->num_rows() > 0 ? $res->row_array() : NULL;

                if($board_config['mbn_recom_ea'] <= $recommend_article_history_cnt['cnt'])
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = "0307";
                    $return_array['data']['err_msg'] = $board_config['mbn_table_name']." 게시판의 추천은 게시글당 ".$board_config['mbn_recom_ea']."회 까지 추천이 가능합니다.";
                    return $return_array;
                }
            }

        }

        $this->db_master()->trans_start();
        $this->db_master()->insert('mint_recommend', $recommend);

        $this->db_master()->set('recom', '`recom` + 1', FALSE);
        $this->db_master()->where('mb_unq', $recommend['mb_unq']);
        $this->db_master()->update('mint_boards');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        else
        {
            $sql = "SELECT mb.recom as mb_recom FROM mint_boards mb WHERE mb.mb_unq = ? ";
            $res = $this->db_master()->query($sql,array($recommend['mb_unq']));
    
            return $res->num_rows() > 0 ? $res->row_array() : NULL;
        }

    }

    public function recommend_article_express($recommend)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->insert('mint_recommend', $recommend);

        $this->db_master()->set('recom', '`recom` + 1', FALSE);
        $this->db_master()->where('uid', $recommend['mb_unq']);
        $this->db_master()->update('mint_express');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        else
        {
            $sql = "SELECT mb.recom as mb_recom FROM mint_express mb WHERE mb.uid = ? ";
            $res = $this->db_master()->query($sql,array($recommend['mb_unq']));
    
            return $res->num_rows() > 0 ? $res->row_array() : NULL;
        }

    }

    public function recommend_article_correct($recommend)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->insert('mint_recommend', $recommend);

        $this->db_master()->set('recom', '`recom` + 1', FALSE);
        $this->db_master()->where('w_id', $recommend['mb_unq']);
        $this->db_master()->update('wiz_correct');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        else
        {
            $sql = "SELECT mb.recom as mb_recom FROM wiz_correct mb WHERE mb.w_id = ? ";
            $res = $this->db_master()->query($sql,array($recommend['mb_unq']));
    
            return $res->num_rows() > 0 ? $res->row_array() : NULL;
        }

    }

    public function recommend_article_cafeboard($recommend, $recommend_type)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->insert('mint_recommend', $recommend);

        if($recommend_type == 'recom')
        {
            $this->db_master()->set('recom', '`recom` + 1', FALSE);
        }
        else
        {
            $this->db_master()->set('decl', '`decl` + 1', FALSE);
        }

        $this->db_master()->where('c_uid', $recommend['mb_unq']);
        $this->db_master()->update('mint_cafeboard');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        else
        {
            $sql = "SELECT mb.recom as mb_recom, mb.decl as mb_decl FROM mint_cafeboard mb WHERE mb.c_uid = ? ";
            $res = $this->db_master()->query($sql,array($recommend['mb_unq']));
    
            return $res->num_rows() > 0 ? $res->row_array() : NULL;
        }
    }

    
    public function certify_article_correct($w_id, $regdate)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('certify_date', $regdate);
        $this->db_master()->set('certify_view', 'Y');
        $this->db_master()->where('w_id', $w_id);
        $this->db_master()->update('wiz_correct');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    public function certify_article_express($uid, $regdate)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('certify_date', $regdate);
        $this->db_master()->set('certify_view', 'Y');
        $this->db_master()->where('uid', $uid);
        $this->db_master()->update('mint_express');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    public function certify_article_cafeboard($c_uid, $regdate)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
    
        $this->db_master()->set('certify_date', $regdate);
        $this->db_master()->set('certify_view', 'Y');
        $this->db_master()->where('c_uid', $c_uid);
        $this->db_master()->update('mint_cafeboard');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;

    }

    public function certify_article($mb_unq, $regdate)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
    
        $this->db_master()->set('certify_date', $regdate);
        $this->db_master()->set('certify_view', 'Y');
        $this->db_master()->where('mb_unq', $mb_unq);
        $this->db_master()->update('mint_boards');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;

    }

    public function checked_article_best_copy($mb_unq)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(1) as cnt FROM mint_boards mb WHERE mb.table_code = '1347' AND  (SUBSTRING_INDEX(`sim_content4`,',',-1) = ? OR mb.mb_unq = ?)";
        $res = $this->db_slave()->query($sql, array($mb_unq, $mb_unq));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function copy_article_best($copy)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        $this->db_master()->insert('mint_boards', $copy);
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
            
    }

    public function row_copy_article($mb_unq)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT
                    `table_code`, `mb_unq`, 
                    `wiz_id`,`name`,`ename`,`nickname`,`title`,`filename`,`editor_file`,`content`,`sim_content`,`sim_content2`,`input_txt`
                    ,`secret`,`c_yn`,`pwd`,`tu_uid`,`daum_img`,`showdate`, `name_hide`, `thumb`
                FROM mint_boards 
                WHERE mb_unq = ?";
        $res = $this->db_slave()->query($sql, array($mb_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    

    public function checked_approval_cafaboard($mca_where)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mca.id as mca_id, mca.uid as mca_uid, mca.created_at as mca_created_at FROM mint_cafeboard_approval mca ".$mca_where;
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_approval_cafaboard_waiting($wiz_id,$approval_date)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT * FROM mint_boards WHERE table_code = '1111' AND wiz_id = '".$wiz_id."' AND regdate >= '".$approval_date."' AND category_title like '%얼철딕%'";
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function approval_cafaboard($mb_unq,$writer_wm_uid)
    {
        $this->db_connect('master');

        /* 해당 게시글이 현재 비승인 상태인지 확인*/
        $sql = "SELECT id,uid FROM mint_cafeboard_approval WHERE uid = ".$writer_wm_uid." AND board_id = ".$mb_unq." AND approval = 'N'";
        $res = $this->db_master()->query($sql);
        $approval = $res->row_array();

        if(!$approval) return NULL;

        $this->db_master()->trans_start();

        $this->db_master()->set('approval', 'Y');
        $this->db_master()->where('id', $approval['id']);
        $this->db_master()->update('mint_cafeboard_approval');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        else
        {
            $sql = "SELECT count(1) as cnt FROM mint_cafeboard mb WHERE mb.uid = ? ";
            $res = $this->db_master()->query($sql,array($approval['uid']));
    
            return $res->num_rows() > 0 ? $res->row_array() : NULL;
        }
    }

    public function update_approval_cafaboard_board_id($board_id,$mca_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('board_id', $board_id);
        $this->db_master()->where('id', $mca_id);
        $this->db_master()->update('mint_cafeboard_approval');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }
    
    public function checked_approval_cafaboard_result_array($mca_where)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mca.id as mca_id, mca.uid as mca_uid, mca.created_at as mca_created_at FROM mint_cafeboard_approval mca ".$mca_where;
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function clip_article($clip)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT mcb.cb_unq FROM mint_clip_boards mcb WHERE mcb.mb_unq = ? AND mcb.reg_wiz_id = ? ";
        $res = $this->db_master()->query($sql, array($clip['mb_unq'], $clip['reg_wiz_id']));
        
    
        if ($res->num_rows() == 0)
        {
            $this->db_master()->insert('mint_clip_boards', $clip);
        }
        else
        {
            $this->db_master()->where(array('mb_unq' => $clip['mb_unq'], 'reg_wiz_id' => $clip['reg_wiz_id']));
            $this->db_master()->update('mint_clip_boards', $clip);
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    public function insert_comment($comment)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_boards_comment', $comment);
        $insert_id = $this->db_master()->insert_id();

        if($comment['co_fid'] == NULL)
        {
            $this->db_master()->set('co_fid',  $insert_id);
            $this->db_master()->where('co_unq', $insert_id);           
            $this->db_master()->update('mint_boards_comment');    
        }

        $this->db_master()->set('comm_hit', '`comm_hit` + 1', FALSE);
        $this->db_master()->where('mb_unq', $comment['mb_unq']);           
        $this->db_master()->update('mint_boards');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function insert_comment_cafeboard($comment)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_cafeboard_com', $comment);
        $insert_id = $this->db_master()->insert_id();
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function insert_comment_express($comment)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_express_com', $comment);
        $insert_id = $this->db_master()->insert_id();
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function comment_child($co_fid, $co_thread)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mbc.co_thread as mbc_co_thread, right(mbc.co_thread,1) as mbc_right_co_thread 
                FROM mint_boards_comment mbc
                WHERE mbc.co_fid = ? AND length(mbc.co_thread)=length(?)+1 AND locate(?,mbc.co_thread)=1 ORDER BY mbc.co_thread DESC LIMIT 1";
        $res = $this->db_slave()->query($sql, array($co_fid, $co_thread, $co_thread));
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function checked_block_list($wm_uid1, $wm_uid2)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wmb.id
                FROM wiz_member_block wmb 
                WHERE wmb.candate IS NULL AND 
                ( (wmb.blocker_uid = ? AND wmb.blocked_uid = ?))";
        $res = $this->db_slave()->query($sql, array($wm_uid2, $wm_uid1));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function comment_parent_wm_uid_by_co_fid($co_fid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wm.uid as wm_uid 
                FROM mint_boards_comment mbc 
                LEFT OUTER JOIN wiz_member wm ON mbc.writer_id = wm.wiz_id  
                WHERE mbc.co_unq= ? LIMIT 1";
        $res = $this->db_slave()->query($sql, array($co_fid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_article_comment_express_by_uid($uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mbc.uid as mbc_uid, mbc.e_id as mbc_e_id, mbc.comment as mbc_comment, mbc.recom as mbc_recom, mbc.wiz_id as mbc_wiz_id
                FROM mint_express_com mbc 
                WHERE mbc.uid= ?";
        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_article_comment_cafeboard_by_unq($unq)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mbc.unq as mbc_unq, mbc.c_uid as mbc_c_uid, mbc.comment as mbc_comment, mbc.recom as mbc_recom, mbc.writer_id as mbc_wiz_id
                FROM mint_cafeboard_com mbc 
                WHERE mbc.unq= ?";
        $res = $this->db_slave()->query($sql, array($unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function update_comment_cafeboard($comment, $unq, $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('unq' => $unq, 'writer_id' => $wiz_id));
        $this->db_master()->update('mint_cafeboard_com', $comment);
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_comment_express($comment, $uid, $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('uid' => $uid, 'wiz_id' => $wiz_id));
        $this->db_master()->update('mint_express_com', $comment);
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function delete_comment_cafeboard($unq, $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('unq' => $unq, 'writer_id' => $wiz_id));
        $this->db_master()->delete('mint_cafeboard_com');
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function delete_comment_express($uid, $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('uid' => $uid , 'wiz_id' => $wiz_id));
        $this->db_master()->delete('mint_express_com');
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function checked_comment_co_thread($mb_unq, $co_fid, $co_thread)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT co_unq
                FROM mint_boards_comment mbc 
                WHERE mbc.mb_unq= ? AND mbc.co_fid = ? AND mbc.co_thread > ? ";
        $res = $this->db_slave()->query($sql, array($mb_unq, $co_fid, $co_thread));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function update_comment($comment, $co_unq, $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('co_unq' => $co_unq, 'writer_id' => $wiz_id));
        $this->db_master()->update('mint_boards_comment', $comment);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    public function delete_comment($mb_unq, $co_unq, $wiz_id, $wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('co_unq' => $co_unq, 'writer_id' => $wiz_id));
        $this->db_master()->delete('mint_boards_comment');

        $sql = "SELECT mbc.co_unq as mbc_co_unq FROM mint_boards_comment mbc WHERE mbc.mb_unq = ? AND mbc.writer_id = ? LIMIT 1";
        $tmp = $this->db_master()->query($sql, array($mb_unq, $wiz_id));       
        $comment = $tmp->row_array();

        if($comment)
        {
            $this->db_master()->set('co_unq', $comment['mbc_co_unq']);
            $this->db_master()->where(array('co_unq' => $co_unq, 'b_kind'=>'boards'));
            $this->db_master()->update('wiz_point');
        }
        else
        {
            $this->db_master()->set('del_regate', 'now()', FALSE);
            $this->db_master()->set('showYn', 'd');
            $this->db_master()->where(array('co_unq' => $co_unq, 'b_kind'=>'boards'));
            $this->db_master()->update('wiz_point');
        }

        $sql = "SELECT SUM(point) as wm_point FROM wiz_point wm WHERE wm.uid = ? AND wm.showYn = 'y'";
        $tmp = $this->db_master()->query($sql, array($wm_uid));    
        $wiz_member = $tmp->row_array();

        $this->db_master()->set('point', $wiz_member['wm_point']);
        $this->db_master()->where('uid', $wm_uid);
        $this->db_master()->update('wiz_member');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $wiz_member['wm_point'];
    }

    public function list_count_mint_cafeboard_com($c_uid)
    {
        $this->db_connect('slave');
     
        $sql = "SELECT count(1) as cnt
        FROM mint_cafeboard_com mbc
        WHERE mbc.c_uid = ?";

        $res = $this->db_slave()->query($sql, array($c_uid));


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
        
    }

    public function list_count_mint_express_com($e_id)
    {
        $this->db_connect('slave');
     
        $sql = "SELECT count(1) as cnt
        FROM mint_express_com mbc
        WHERE mbc.e_id = ?";

        $res = $this->db_slave()->query($sql, array($e_id));


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
        
    }

    public function checked_article_recommend_by_wm_uid($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT mr.re_unq,mr.send_uid,mr.receive_uid
                FROM mint_recommend mr
                %s", $where);

        $res = $this->db_slave()->query($sql);


        return $res->num_rows() > 0 ? $res->row_array() : NULL;
        
    }

    public function row_article_recom_by_mb_unq($mb_unq, $wiz_id)
    {
        $this->db_connect('slave');

        $sql = " SELECT mb.recom as mb_recom
                FROM mint_boards mb 
                WHERE mb.mb_unq = ? AND mb.wiz_id = ?";

        $res = $this->db_slave()->query($sql, array($mb_unq, $wiz_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_article_title_by_mb_unq($mb_unq)
    {
        $this->db_connect('slave');

        $sql = " SELECT mb.title as mb_title, mbn.table_name as mbn_table_name, mb.wiz_id as mb_wiz_id, mb.tu_uid as mb_tu_uid, mb.table_code as mb_table_code
                FROM mint_boards mb 
                INNER JOIN mint_boards_name mbn ON mb.table_code = mbn.table_code
                WHERE mb.mb_unq = ?";

        $res = $this->db_slave()->query($sql, array($mb_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_article_table_code_by_mb_unq($mb_unq)
    {
        $this->db_connect('slave');

        $sql = " SELECT  mb.table_code as mb_table_code
                FROM mint_boards mb 
                WHERE mb.mb_unq = ?";

        $res = $this->db_slave()->query($sql, array($mb_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_article_solution_by_mb_unq($mb_unq)
    {
        $this->db_connect('slave');

        $sql = " SELECT mb.mb_unq AS mb_mb_unq, mb.title AS mb_title, mb.content AS mb_content, mb.rsms AS mb_rsms, mb.select_key AS mb_select_key,
                mbn.table_name AS mbn_table_name, 
                wm.uid AS wm_uid, wm.wiz_id AS wm_wiz_id, wm.name AS wm_name, wm.ename AS wm_ename, wm.nickname AS wm_nickname, wm.mobile AS wm_mobile
                FROM mint_boards mb 
                INNER JOIN mint_boards_name mbn ON mb.table_code = mbn.table_code
                LEFT OUTER JOIN wiz_member wm ON mb.wiz_id = wm.wiz_id
                WHERE mb.mb_unq = ?";

        $res = $this->db_slave()->query($sql, array($mb_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function row_article_express_title_by_uid($uid)
    {
        $this->db_connect('slave');

        $sql = " SELECT mb.content as mb_title, '이런표현어떻게' as mbn_table_name
                FROM mint_express mb 
                WHERE mb.uid = ?";

        $res = $this->db_slave()->query($sql, array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_article_cafeboard_title_by_c_uid($c_uid)
    {
        $this->db_connect('slave');

        $sql = " SELECT mb.subject as mb_title, '얼굴철판딕테이션' as mbn_table_name
                FROM mint_cafeboard mb 
                WHERE mb.c_uid = ?";

        $res = $this->db_slave()->query($sql, array($c_uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_1130_by_cafe_unq($cafe_unq)
    {
        $this->db_connect('slave');

        $sql = " SELECT 
                    mb.b_kind as mb_b_kind , mb.vd_url as mb_vd_url, mb.tu_name as mb_tu_name, mb.mins as mb_mins, mb.subject as mb_subject, mb.class_date as mb_class_date,
                    mb.content as mb_content, mb.filename as mb_filename, mb.name_hide as mb_name_hide
                FROM mint_cafeboard mb             
                WHERE mb.c_uid = ?";

        $res = $this->db_slave()->query($sql, array($cafe_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function update_star_wiz_correct($wm_uid, $w_id, $star)
    {
        $this->db_connect('master');

        $this->db_master()->set('star', $star);
        $this->db_master()->where(array('w_id' => $w_id, 'uid' => $wm_uid));
        $this->db_master()->update('wiz_correct');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_star($wiz_id, $mb_unq, $star)
    {
        $this->db_connect('master');

        $this->db_master()->set('star', $star);
        $this->db_master()->where(array('mb_unq' => $mb_unq, 'wiz_id' => $wiz_id));
        $this->db_master()->update('mint_boards');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function checked_count_day_write_article($wiz_id, $table_code, $days)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    count(1) as cnt
                FROM mint_boards mb 
                WHERE mb.wiz_id = ? and mb.table_code = ?  AND mb.regdate > '".date('Y-m-d 00:00:00',strtotime('-'.$days.' day'))."'";

        $res = $this->db_slave()->query($sql,array($wiz_id, $table_code));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function checked_count_today_write_article($wiz_id, $table_code, $today)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    count(1) as cnt
                FROM mint_boards mb 
                WHERE mb.wiz_id = ? and mb.table_code = ?  and left(mb.regdate,10)= ?";

        $res = $this->db_slave()->query($sql,array($wiz_id, $table_code, $today));

        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 딕테이션 해결사 답변글 카운트 체크
    public function checked_count_today_write_1138_child($wiz_id, $table_code, $today)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    count(1) as cnt
                FROM mint_boards mb 
                WHERE mb.wiz_id = ? and mb.table_code = ?  and left(mb.regdate,10)= ? AND mb.parent_key IS NOT NULL";

        $res = $this->db_slave()->query($sql,array($wiz_id, $table_code, $today));
        
        // echo $this->db_slave()->last_query();
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 딕테이션 해결사 의뢰글 카운트 체크
    public function checked_count_today_write_1138_parent($wiz_id, $table_code, $today)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    count(1) as cnt
                FROM mint_boards mb 
                WHERE mb.wiz_id = ? and mb.table_code = ?  and left(mb.regdate,10)= ? AND mb.parent_key IS NULL";

        $res = $this->db_slave()->query($sql,array($wiz_id, $table_code, $today));
        
        // echo $this->db_slave()->last_query();
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 해당 얼철딕에 딕테이션 해결사 의뢰글이 달렸는지 체크
    public function checked_1138_first_board($cafe_unq)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) AS cnt FROM mint_boards WHERE table_code = 1138 AND cafe_unq = ? AND parent_key IS NULL";

        $res = $this->db_slave()->query($sql,array($cafe_unq));
        
        // echo $this->db_slave()->last_query();
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function write_article($article)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_boards', $article);

        $insert_id = $this->db_master()->insert_id();
        
        if($article['table_code'])
        {
            $sql = "SELECT table_code FROM mint_boards_new WHERE table_code = ".$article['table_code'];
            $tmp = $this->db_master()->query($sql);
            $mint_boards_new = $tmp->row_array();
            
            if($mint_boards_new)
            {
                $this->db_master()->where('table_code',$article['table_code']);
                $this->db_master()->set('regdate',date("Y-m-d H:i:s"));
                $this->db_master()->update('mint_boards_new');
            }
            else
            {
                $this->db_master()->insert('mint_boards_new', [
                    'table_code' => $article['table_code'],
                    'regdate' => date("Y-m-d H:i:s"),
                ]);
            }
        }
        
        $this->db_master()->trans_complete();
        // echo $this->db_master()->last_query();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }
    
    public function write_article_express($article)
    {
        $this->db_connect('master');

        $this->db_master()->insert('mint_express', $article);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        // echo $this->db_master()->last_query();
        // exit;
        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function write_article_request($article)
    {
        $this->db_connect('master');

        $this->db_master()->insert('wiz_speak', $article);
        $insert_id = $this->db_master()->insert_id();
        $this->db_master()->trans_complete();

        // echo $this->db_master()->last_query();
        // exit;
        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function write_article_toteacher($article)
    {
        $this->db_connect('master');

        $this->db_master()->insert('wiz_toteacher', $article);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        // echo $this->db_master()->last_query();
        // exit;
        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function checked_article_clip_by_wiz_id($where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT mcb.cb_unq
                FROM mint_clip_boards mcb
                %s ",$where);

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_article_comment_by_mb_unq($mb_unq)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    mbc.mb_unq
                FROM mint_boards_comment mbc 
                WHERE mbc.mb_unq = ? LIMIT 0,1";

        $res = $this->db_slave()->query($sql,array($mb_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function delete_article($mb_unq,  $wiz_id, $wm_uid, $table_code)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('mb_unq' => $mb_unq, 'wiz_id' => $wiz_id));
        $this->db_master()->delete('mint_boards');
    
        if($table_code == '1130')
        {
            $this->db_master()->where('table_code', '1130');
            $this->db_master()->where('board_id', $mb_unq);
            $this->db_master()->delete('wiz_schedule_board_pivot');
        }

        if($table_code == '1138')
        {
            $this->db_master()->where('table_code', '1138');
            $this->db_master()->where('board_id', $mb_unq);
            $this->db_master()->delete('wiz_schedule_board_pivot');

            $this->db_master()->set('del_regate', 'now()', FALSE);
            $this->db_master()->set('showYn', 'd');
            //임시로 solver로 해놓음
            $this->db_master()->where(array('co_unq' => $mb_unq, 'b_kind'=>'dictation', 'kind'=>'sl'));
            $this->db_master()->update('wiz_point');
    
        }

        $this->db_master()->set('del_regate', 'now()', FALSE);
        $this->db_master()->set('showYn', 'd');
        $this->db_master()->where(array('co_unq' => $mb_unq, 'b_kind'=>'boards'));
        $this->db_master()->update('wiz_point');

        $sql = "SELECT SUM(point) as wm_point FROM wiz_point wm WHERE wm.uid = ? AND wm.showYn = 'y'";
        $tmp = $this->db_master()->query($sql, array($wm_uid));    
        $wiz_member = $tmp->row_array();

        $this->db_master()->set('point', $wiz_member['wm_point']);
        $this->db_master()->where('uid', $wm_uid);
        $this->db_master()->update('wiz_member');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $wiz_member['wm_point'];

    }

    public function update_article($article, $mb_unq, $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('mb_unq' => $mb_unq, 'wiz_id' => $wiz_id));
        $this->db_master()->update('mint_boards', $article);
        // echo $this->db_master()->last_query();exit;

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    
    public function checked_count_today_write_article_express($wiz_id, $today, $addwhere='')
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    count(1) as cnt
                FROM mint_express mb 
                WHERE mb.wiz_id = ?  and left(mb.regdate,10)= ? ".$addwhere;

        $res = $this->db_slave()->query($sql,array($wiz_id, $today));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_article_express_comment_by_e_id($mb_unq)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    mbc.uid
                FROM mint_express_com mbc 
                WHERE mbc.e_id = ? LIMIT 0,1";

        $res = $this->db_slave()->query($sql,array($mb_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function delete_article_express($mb_unq,  $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('uid' => $mb_unq, 'wiz_id' => $wiz_id));
        $this->db_master()->delete('mint_express');
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function delete_article_toteacher($mb_unq,  $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('to_id' => $mb_unq, 'wiz_id' => $wiz_id));
        $this->db_master()->delete('wiz_toteacher');
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function delete_article_request($mb_unq,  $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('sp_id' => $mb_unq, 'wiz_id' => $wiz_id));
        $this->db_master()->delete('wiz_speak');
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function delete_search_boards($table_code, $mb_unq)
    {
        $this->db_connect('search');

        $this->db_search()->trans_start();

        $this->db_search()->where(array('table_code' => $table_code, 'mb_unq' => $mb_unq));
        $this->db_search()->delete('search_boards');
    
        $this->db_search()->trans_complete();

        if ($this->db_search()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_article_express($article, $mb_unq, $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('uid' => $mb_unq, 'wiz_id' => $wiz_id));
        $this->db_master()->update('mint_express', $article);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_article_request($article, $mb_unq, $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('sp_id' => $mb_unq, 'wiz_id' => $wiz_id));
        $this->db_master()->update('wiz_speak', $article);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_article_toteacher($article, $mb_unq, $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('to_id' => $mb_unq, 'wiz_id' => $wiz_id));
        $this->db_master()->update('wiz_toteacher', $article);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function list_select_wiz_speak_sub($where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT * FROM wiz_speak_sub 
                %s", $where);

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_faq_mb_unq($mb_unqs)
    {
        $this->db_connect('slave');

        $sql = "SELECT mb.mb_unq, mb.title as mb_title, mb.table_code FROM mint_boards mb WHERE mb.mb_unq IN (".$mb_unqs.")";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function insert_schedule_board_pivot($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_schedule_board_pivot', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function insert_board_edit_files($file_info)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_boards_files', $file_info);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_board_edit_files($file_info, $file_link)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('file_link' => $file_link));
        $this->db_master()->update('mint_boards_files', $file_info);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function delete_board_edit_files($file_link)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('file_link', $file_link);
        $this->db_master()->delete('mint_boards_files');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function list_article_edit_files($table_code, $article_key)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    mbf.mbf_key, mbf.table_code, mbf.article_key, mbf.file_status, mbf.file_link, mbf.file_name, mbf.regdate
                FROM mint_boards_files mbf 
                WHERE mbf.table_code = ? AND mbf.article_key = ? ";

        $res = $this->db_slave()->query($sql, array($table_code, $article_key));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function delete_edit_files_incomplete($mbf_key)
    {
        $this->db_connect('master');
        
        $this->db_master()->trans_start();

        $this->db_master()->where(array('file_status' => 1));

        $this->db_master()->where_in('mbf_key', $mbf_key);

        $this->db_master()->delete('mint_boards_files');
        
        $this->db_master()->trans_complete();
        // echo $this->db_master()->last_query();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    public function list_edit_files_incomplete()
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    mbf.mbf_key, mbf.table_code, mbf.article_key, mbf.file_status, mbf.file_link, mbf.file_name, mbf.regdate
                FROM mint_boards_files mbf 
                WHERE mbf.file_status = 1 AND regdate <= date_add(NOW(), interval -1 day);";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_mint_boards_name()
    {
        $this->db_connect('slave');

        $sql = "SELECT table_code, table_name FROM mint_boards_name WHERE (table_code BETWEEN 1100 AND 1199 OR table_code BETWEEN 1300 AND 1399) and search_yn='Y'  ORDER BY table_name ASC;";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_mint_search_boards($where, $order, $limit, $select_col_content='')
    {
        $this->db_connect('search');

        $sql = sprintf("SELECT mb.bs_unq AS mb_bs_unq, mb.table_code AS mb_table_code, mb.wiz_id AS mb_wiz_id, mb.name AS mb_name, mb.ename AS mb_ename, 
                mb.nickname AS mb_nickname, mb.noticeYn AS mb_noticeYn, mb.title AS mb_title, mb.filename AS mb_filename, mb.content AS mb_content, 
                mb.input_txt AS mb_input_txt, mb.hit AS mb_hit, mb.comm_hit AS mb_comm_hit, mb.comm_date AS mb_comm_date, mb.regdate AS mb_regdate, 
                mb.secret AS mb_secret, mb.cafe_unq AS mb_cafe_unq, mb.d_id AS mb_d_id, mb.mob AS mb_mob, mb.tu_uid AS mb_tu_uid, mb.showdate AS mb_showdate,
                mb.name_hide AS mb_name_hide, mb.mb_unq AS mb_mb_unq, mb.del_yn AS mb_del_yn, mb.mins AS mb_mins, mb.class_date AS mb_class_date, 
                mb.tu_name AS mb_tu_name, mb.book_name AS mb_book_name, mb.w_kind AS mb_w_kind, mb.w_mp3 AS mb_w_mp3, mb.su AS mb_su, mb.vd_url AS mb_vd_url, 
                mb.w_step AS mb_w_step, mb.recom AS mb_recom, mb.certify_view AS mb_certify_view, mb.certify_date AS mb_certify_date,
                mb.name_hide as mb_name_hide,mb.w_mp3_type as mb_w_mp3_type, mb.parent_key as mb_parent_key %s
                FROM search_boards mb 
                %s %s %s", $select_col_content, $where, $order, $limit);

        $res = $this->db_search()->query($sql);
        // echo $this->db_search()->last_query();   exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_count_mint_search_boards($where)
    {
        $this->db_connect('search');

        $sql = sprintf("SELECT count(1) AS cnt
                FROM search_boards mb 
                %s", $where);

        $res = $this->db_search()->query($sql);
        // echo $this->db_search()->last_query();   exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function update_comment_notice($co_unq,$notice_yn)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('notice_yn', $notice_yn);
        $this->db_master()->where('co_unq', $co_unq);
        $this->db_master()->update('mint_boards_comment');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function list_count_comment($index, $where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                        FROM mint_boards_comment mbc %s
                        %s", $index, $where);
                        
        $res = $this->db_slave()->query($sql);
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    
    public function checked_article_brilliant_copy($mb_unq)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(1) as cnt FROM mint_boards WHERE table_code = '1118' and  (SUBSTRING_INDEX(`sim_content4`,',',-1)='$mb_unq' or mb_unq = '$mb_unq')";
        $res = $this->db_slave()->query($sql, array($mb_unq, $mb_unq));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function list_wiz_schedule_board_pivot($where)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wsbp.board_id as wsbp_board_id FROM wiz_schedule_board_pivot as wsbp WHERE ".$where;
        $res = $this->db_slave()->query($sql);       
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function checked_wiz_schedule_board_pivot($where)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wsbp.board_id as wsbp_board_id, wsbp.schedule_id as wsbp_schedule_id FROM wiz_schedule_board_pivot as wsbp WHERE ".$where;
        $res = $this->db_slave()->query($sql);       
        
        // echo $this->db_slave()->last_query();   exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function check_cnt_today_blind($uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT 1 FROM `mint_boards_hide` WHERE `uid` = ? AND `insert_time` > '".date("Y-m-d 00:00:00")."' ";
        $res = $this->db_slave()->query($sql, array($uid));       
        
        return $res->num_rows();
    }


    public function check_already_blinded($table_code,$uid,$mb_unq,$co_unq)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT 1 FROM `mint_boards_hide` 
                WHERE `table_code`= ? and `mb_unq` = ? and `co_unq` = ? and `uid` = ?  ";
        $res = $this->db_slave()->query($sql, array($table_code,$mb_unq,$co_unq,$uid));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    

    public function insert_boards_hide($params)
    {
        $this->db_connect('master');

        $sql = "SELECT 1 FROM `mint_boards_hide` 
                WHERE `table_code`= ? and `mb_unq` = ? and `co_unq` = ? and `uid` = ?  ";
        $res = $this->db_master()->query($sql, array($params['table_code'],$params['mb_unq'],$params['co_unq'],$params['uid']));       
        
        if($res->num_rows() > 0) return 1;

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_boards_hide',$params);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    

    public function check_count_blind($table_code,$mb_unq,$co_unq)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(*) as cnt FROM `mint_boards_hide` WHERE `table_code`= ? and `mb_unq` = ? and `co_unq` = ? and `uid` > 0   ";
        $res = $this->db_slave()->query($sql, array($table_code,$mb_unq,$co_unq));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }



    public function set_blind($params,$type='')
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        if($type == 'comment')
        {
            $this->db_master()->set('tu_uid', '99999');

            $this->db_master()->where('table_code', $params['table_code']);
            $this->db_master()->where('mb_unq', $params['mb_unq']);
            $this->db_master()->where('co_unq', $params['co_unq']);

            $this->db_master()->update('mint_boards_comment');

            // 포인트 차감
            $this->db_master()->set('del_regate', 'now()', FALSE);
            $this->db_master()->set('showYn', 'd');
            $this->db_master()->where(array('co_unq' => $params['co_unq'], 'b_kind'=>'boards', 'kind'=>'l'));
            $this->db_master()->update('wiz_point');
        }
        else
        {
            // 민트보드 블라인드
            $this->db_master()->set('daum_img', 'H');

            $this->db_master()->where('table_code', $params['table_code']);
            $this->db_master()->where('mb_unq', $params['mb_unq']);

            $this->db_master()->update('mint_boards');

            //서치보드 블라인드
            $this->db_master()->set('vd_url', 'H');

            $this->db_master()->where('table_code', $params['table_code']);
            $this->db_master()->where('mb_unq', $params['mb_unq']);

            $this->db_master()->update('db_search.search_boards');
            //echo $this->db_master()->last_query();   

            // 포인트 차감
            $this->db_master()->set('del_regate', 'now()', FALSE);
            $this->db_master()->set('showYn', 'd');
            $this->db_master()->where(array('co_unq' => $params['mb_unq'], 'b_kind'=>'boards', 'kind'=>'x'));
            $this->db_master()->update('wiz_point');
        }

        // 블라인드 처리 된 글 작성자 포인트 갱신
        $sql = "SELECT SUM(point) as wm_point FROM wiz_point wm WHERE wm.uid = ? AND wm.showYn = 'y'";
        $tmp = $this->db_master()->query($sql, array($params['target_uid']));    
        $wiz_member = $tmp->row_array();

        $this->db_master()->set('point', $wiz_member['wm_point']);
        $this->db_master()->where('uid', $params['target_uid']);
        $this->db_master()->update('wiz_member');


        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function set_search_blind($params)
    {
        $this->db_connect('search');

        $this->db_search()->trans_start();

         //서치보드 블라인드
        $this->db_search()->set('vd_url', 'H');

        $this->db_search()->where('table_code', $params['table_code']);
        $this->db_search()->where('mb_unq', $params['mb_unq']);

        $this->db_search()->update('db_search.search_boards');
        
        $this->db_search()->trans_complete();

        if ($this->db_search()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function row_mint_boards_notice_sim_content($table_code)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mb_unq,sim_content FROM mint_boards WHERE table_code= ? and  noticeYn='A' and sim_content!='' order by mb_unq desc limit 1";

        $res = $this->db_slave()->query($sql,array($table_code));
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function list_count_mint_boards_comment($mb_unq,$comm_where='')
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(1) as cnt FROM mint_boards_comment WHERE mb_unq = ? ". $comm_where;

        $res = $this->db_slave()->query($sql,array($mb_unq));
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function check_write_mint_boards_comment_by_mb_unq($mb_unq,$wm_wiz_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mb_unq FROM mint_boards_comment WHERE mb_unq IN ?  AND writer_id= ? GROUP BY mb_unq";

        $res = $this->db_slave()->query($sql,array($mb_unq,$wm_wiz_id));
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function get_thumbnail_field($mb_unqs)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mb_unq,thumb FROM mint_boards WHERE mb_unq IN ? ";

        $res = $this->db_slave()->query($sql,array($mb_unqs));
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function exist_thumb_rows($limit_date)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mb_unq,thumb FROM mint_boards WHERE regdate >'".$limit_date."' AND thumb !=''";
        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    public function update_thumb_info($thumb,$mb_unq)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('thumb', $thumb);
        $this->db_master()->where('mb_unq', $mb_unq);
        $this->db_master()->update('mint_boards');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function insert_correct($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_correct', $param);
        $insert_id = $this->db_master()->insert_id();
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function update_correct($param,$mb_unq)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('w_id', $mb_unq);
        $this->db_master()->update('wiz_correct', $param);

        // echo $this->db_master()->last_query();   
        // exit;

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function delete_correct($mb_unq)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        // 현재는 데이터 안 쌓기에 주석
        //$this->db_master()->where('w_id', $mb_unq);
        //$this->db_master()->delete('wiz_correct_comment');
        
        $this->db_master()->where('w_id', $mb_unq);
        $this->db_master()->delete('wiz_correct');
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    
    public function insert_correct_log($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_correct_log', $param);
        $insert_id = $this->db_master()->insert_id();
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function delete_correct_log_month()
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->where('regdate < DATE_ADD(NOW(), INTERVAL -1 MONTH)');
        $this->db_master()->delete('wiz_correct_log');
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function insert_dictation($param,$sc_id,$board_config,$dic_count)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_cafeboard', $param);
        $insert_id = $this->db_master()->insert_id();

        if($insert_id)
        {
            // 수업과 얼철딕 연결 
            $this->db_master()->insert('wiz_schedule_board_pivot', [
                    'uid'           => $param['uid'],
                    'schedule_id'   => $sc_id,
                    'table_code'    => 9002,
                    'board_id'      => $insert_id,
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);

            // 얼철딕 100회마다 후기 써야지 다음 진행가능하므로 컨트롤 할 데이터 insert
            if((int)$dic_count % (int)$board_config['mbn_cafe_count'] == 0)
            {
                $this->db_master()->insert('mint_cafeboard_approval', [
                    'uid'       => $param['uid'],
                    'num'       => $dic_count,
                    'cafe_id'   => $insert_id,
                    'created_at'=> date('Y-m-d H:i:s'),
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


    public function update_dictation($param,$c_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('c_uid', $c_uid);
        $this->db_master()->update('mint_cafeboard', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function delete_dictation($c_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->where('c_uid', $c_uid);
        $this->db_master()->delete('mint_cafeboard');
        
        $this->db_master()->where('table_code', '9002');
        $this->db_master()->where('board_id', $c_uid);
        $this->db_master()->delete('wiz_schedule_board_pivot');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    
    public function list_article_solver($where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT 
                        mb.mb_unq as mb_mb_unq, mb.reply_key AS mb_reply_key
                        FROM mint_boards mb 
                        %s", $where);

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function checked_board_solve_wrote($table_code, $cafe_unq, $wiz_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT
                mb.mb_unq as mb_mb_unq, mb.select_key AS mb_select_key, mb.*
                FROM mint_boards mb    
                WHERE mb.table_code = ? AND mb.cafe_unq = ? AND wiz_id = ? AND mb.select_key IS NULL
                ";

        $res = $this->db_slave()->query($sql, array($table_code, $cafe_unq, $wiz_id));
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    function checked_count_solve_wrote($table_code, $wiz_id, $cafe_unq)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT count(1) as cnt
                FROM mint_boards mb    
                WHERE mb.table_code = ? AND mb.cafe_unq = ? AND wiz_id = ? AND mb.select_key IS NULL
                ";

        $res = $this->db_slave()->query($sql, array($table_code, $wiz_id, $cafe_unq));
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function checked_board_solve_cafe($table_code, $cafe_unq)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                mb.mb_unq as mb_mb_unq, mb.select_key AS mb_select_key
                FROM mint_boards mb  
                WHERE mb.table_code = ? AND mb.cafe_unq = ? AND mb.parent_key IS NULL
                ";

        $res = $this->db_slave()->query($sql, array($table_code, $cafe_unq));
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function checked_board_solve_select($table_code, $wiz_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                mb.mb_unq as mb_mb_unq, mb.select_key AS mb_select_key
                FROM mint_boards mb  
                WHERE mb.table_code = ? AND mb.wiz_id = ? AND parent_key IS NULL AND mb.select_key IS NULL
                ";

        $res = $this->db_slave()->query($sql, array($table_code, $wiz_id));

        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function checked_count_board_solve_select($table_code, $wiz_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt
                FROM mint_boards mb  
                WHERE mb.table_code = ? AND mb.wiz_id = ? AND parent_key IS NULL AND mb.select_key IS NULL
                ";

        $res = $this->db_slave()->query($sql, array($table_code, $wiz_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    function list_count_board_solve($table_code, $wiz_id, $cafe_unq)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT count(1) as cnt
                FROM mint_boards mb 
                WHERE mb.table_code = ? AND mb.wiz_id = ? AND cafe_unq = ?
                ";

        $res = $this->db_slave()->query($sql, array($table_code, $wiz_id, $cafe_unq));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function list_dictation_solution_parents()
    {
        $this->db_connect('slave');

        $sql = "SELECT mb.mb_unq AS mb_mb_unq, mb.select_key AS mb_select_key, mb.parent_key AS mb_parent_key,
                mb.nickname AS mb_nickname, mb.name AS mb_name, mb.table_code AS mb_table_code, 
                mb.content AS mb_content, mb.set_point AS mb_set_point,
                wm.uid AS wm_uid,
                mbn.table_name AS mbn_table_name
                FROM mint_boards mb 
                LEFT OUTER JOIN wiz_member wm ON mb.wiz_id = wm.wiz_id  
                INNER JOIN mint_boards_name mbn ON mb.table_code = mbn.table_code
                WHERE mb.table_code = '1138' AND parent_key IS NULL AND mb.select_key IS NULL
                ";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function min_regdate_dictation_solution_child_by_parent_key($parent_key)
    {
        $this->db_connect('slave');

        $sql = "SELECT mb_unq AS min_mb_unq, mb.content AS mb_content, mb.table_code AS mb_table_code,
                wm.name AS wm_name, wm.uid AS wm_uid,
                mbn.table_name AS mbn_table_name
                FROM mint_boards mb
                LEFT OUTER JOIN wiz_member wm ON mb.wiz_id = wm.wiz_id 
                INNER JOIN mint_boards_name mbn ON mb.table_code = mbn.table_code
                WHERE mb.table_code = '1138' 
                AND parent_key = ?
                AND DATE_FORMAT(mb.regdate, '%Y-%m-%d')  <= (DATE_FORMAT(NOW(), '%Y-%m-%d') - INTERVAL 7 DAY) 
                ORDER BY mb_unq ASC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($parent_key));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function update_dictation_solution_parent_select_key($parent_mb_unq, $select_mb_unq, $message)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        // 부모글 업데이트
        $this->db_master()->set('select_key', $select_mb_unq);
        $this->db_master()->where('mb_unq', $parent_mb_unq);
        $this->db_master()->update('mint_boards');
        

        $sql_child = "SELECT mb.mb_unq FROM mint_boards mb WHERE mb.mb_unq = ?";
        $res_child = $this->db_master()->query($sql_child, array($select_mb_unq));
        $article_child = $res_child->row_array();

        
        //자식글의 별점 5점, 코멘트 업데이트
        if($article_child)
        {
            $this->db_master()->set('star', '5');
            $this->db_master()->set('sim_content3', $message);
            $this->db_master()->where('mb_unq', $select_mb_unq);
            $this->db_master()->update('mint_boards');
        }

        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    // $type 빈값: 딕테이션, 그 외 지식인 게시판 adopt
    public function update_select_star($datas, $type='')
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        

        if($type =='')
        {
            $sql = "SELECT mb.mb_unq FROM mint_boards mb WHERE mb.mb_unq = ? AND mb.wiz_id = ? ";
            $res = $this->db_master()->query($sql, array($datas['mb_unq'], $datas['wiz_id']));
            
            $article = $res->row_array();

            if($article)
            {
                $this->db_master()->set('select_key', $datas['select_key']);
                $this->db_master()->where('mb_unq', $datas['mb_unq']);
                $this->db_master()->where('wiz_id', $datas['wiz_id']);
                $this->db_master()->update('mint_boards');
            }
            
        }
        
        //부모글의 채택컬럼 업데이트
        // if($article)
        // {
        //     $this->db_master()->where(array('mb_unq' => $datas['mb_unq'], 'wiz_id' => $datas['wiz_id']));
        //     $this->db_master()->update('mint_boards', $datas);
        // }

        $sql_child = "SELECT mb.mb_unq FROM mint_boards mb WHERE mb.mb_unq = ?";
        
        $res_child = $this->db_master()->query($sql_child, array($datas['select_key']));

        $article_child = $res_child->row_array();

        
        //자식글의 별점, 코멘트 업데이트
        if($article_child)
        {
            $this->db_master()->set('star', $datas['star']);
            $this->db_master()->set('sim_content3', $datas['sim_content3']);
            $this->db_master()->where('mb_unq', $datas['select_key']);
            $this->db_master()->update('mint_boards');

            // 지식인 게시판 채택 처리
            if($type =='adopt')
            {
                $adopt_params = [
                    'a_uid'      => $datas['selected_uid'],
                    'table_code' => $datas['table_code'],
                    'q_mb_unq'   => $datas['mb_unq'],
                    'a_mb_unq'   => $datas['select_key'],
                    'type'       => $datas['adopt_type'],
                    'regdate'    => date('Y-m-d H:i:s'),
                ];
                $this->db_master()->insert('mint_boards_adopt', $adopt_params);
            }
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function knowledge_adopt_article($datas)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('star', $datas['star']);
        $this->db_master()->set('sim_content3', $datas['sim_content3']);
        
        if($datas['table_code'] == '9001')
        {
            $this->db_master()->where('uid', $datas['select_key']);
            $this->db_master()->update('mint_express');
        }
        else
        {
            $this->db_master()->where('mb_unq', $datas['select_key']);
            $this->db_master()->update('mint_boards');
        }

        $adopt_params = [
            'a_uid'      => $datas['selected_uid'],
            'table_code' => $datas['table_code'],
            'q_mb_unq'   => $datas['mb_unq'],
            'a_mb_unq'   => $datas['select_key'],
            'type'       => $datas['adopt_type'],
            'regdate'    => date('Y-m-d H:i:s'),
        ];

        $this->db_master()->insert('mint_boards_adopt', $adopt_params);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function list_article_solve_by_parentkey($table_code, $mb_unq)
    {
        $this->db_connect('slave');

        $this->db_slave()->trans_start();
                                                                                                                                                
        $sql = " SELECT mb.mb_unq as mb_unq, mb.select_key as mb_select_key, mb.parent_key as mb_parent_key
                FROM mint_boards mb 
                INNER JOIN mint_boards_name mbn  ON mb.table_code = mbn.table_code 
                LEFT OUTER JOIN mint_cafeboard mc ON mc.c_uid = mb.cafe_unq
                WHERE mb.table_code = ? AND mb.parent_key = ?";

        $res = $this->db_slave()->query($sql, array($table_code, $mb_unq));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function update_list_count_board_certify()
    {
        $this->db_connect('master');
        
        $this->db_master()->trans_start();
    
        $sql = "SELECT count(1) as cnt
                FROM mint_boards mb
                WHERE (mb.table_code BETWEEN 1100 AND 1199 OR mb.table_code BETWEEN 1300 AND 1399)
                AND (mb.table_code NOT IN ('1131', '1356','1354','1381') OR (mb.table_code = '1356' AND mb.wiz_id != ''))
                AND mb.certify_view ='Y' AND ( mb.daum_img IS NULL OR mb.daum_img <> 'H' ) 
                AND EXISTS(SELECT 1 FROM mint_boards_name mbn WHERE mb.table_code = mbn.table_code AND mbn.list_show = 'Y')
                ";

        $tmp = $this->db_master()->query($sql);
        $certify = $tmp->row_array();

        $now = date('Y-m-d H:i:s');

        $this->db_master()->set('total_rows', $certify['cnt']);
        $this->db_master()->set('update_date', $now);
        $this->db_master()->where('table_code', 'certify');
        $this->db_master()->update('mint_boards_total_rows');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_list_count_board_hot()
    {
        $this->db_connect('master');
        
        $this->db_master()->trans_start();
    
        $sql = "SELECT count(1) as cnt
                FROM mint_boards mb
                WHERE mb.hit >= 100 AND mb.comm_hit > 10 AND (mb.table_code!='1356' || (mb.table_code='1356' AND mb.tu_uid IS NULL))
                AND (mb.table_code BETWEEN 1100 AND 1199 OR mb.table_code BETWEEN 1300 AND 1399) 
                AND mb.table_code NOT IN (1380, 1381)
                AND ( mb.daum_img IS NULL OR
                mb.daum_img <> 'H' )
                AND EXISTS(SELECT 1 FROM mint_boards_name mbn WHERE mb.table_code = mbn.table_code AND mbn.list_show = 'Y')
                ";

        $tmp = $this->db_master()->query($sql);
        $hot = $tmp->row_array();

        $now = date('Y-m-d H:i:s');

        $this->db_master()->set('total_rows', $hot['cnt']);
        $this->db_master()->set('update_date', $now);
        $this->db_master()->where('table_code', 'hot');
        $this->db_master()->update('mint_boards_total_rows');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_list_count_board_new()
    {
        $this->db_connect('master');
        
        $this->db_master()->trans_start();
    
        $sql = "SELECT count(1) as cnt
                FROM mint_boards mb
                WHERE mb.hit <= 1000 AND mb.showdate <= date_format(now(), '%Y-%d-%m') AND mb.noticeYn !='A' 
                AND ( mb.daum_img IS NULL OR mb.daum_img <> 'H' )
                AND (mb.table_code BETWEEN 1100 AND 1199 OR mb.table_code BETWEEN 1300 AND 1399) 
                AND (mb.table_code NOT IN ('1356') OR (mb.table_code = '1356' AND wiz_id != ''))
                AND EXISTS(SELECT 1 FROM mint_boards_name mbn WHERE mb.table_code = mbn.table_code AND mbn.list_show = 'Y')
                ";

        $tmp = $this->db_master()->query($sql);
        $new = $tmp->row_array();

        $now = date('Y-m-d H:i:s');

        $this->db_master()->set('total_rows', $new['cnt']);
        $this->db_master()->set('update_date', $now);
        $this->db_master()->where('table_code', 'new');
        $this->db_master()->update('mint_boards_total_rows');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_list_count_board_notice()
    {
        $this->db_connect('master');
        
        $this->db_master()->trans_start();
    
        $sql = "SELECT count(1) as cnt
                FROM mint_boards mb
                WHERE mb.noticeYn = 'A' 
                AND (mb.table_code BETWEEN 1100 AND 1199 OR mb.table_code BETWEEN 1300 AND 1399) 
                AND EXISTS(SELECT 1 FROM mint_boards_name mbn WHERE mb.table_code = mbn.table_code AND mbn.list_show = 'Y')
                ";

        $tmp = $this->db_master()->query($sql);
        $notice = $tmp->row_array();

        $now = date('Y-m-d H:i:s');

        $this->db_master()->set('total_rows', $notice['cnt']);
        $this->db_master()->set('update_date', $now);
        $this->db_master()->where('table_code', 'notice');
        $this->db_master()->update('mint_boards_total_rows');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_list_count_comment()
    {
        $this->db_connect('master');
        
        $this->db_master()->trans_start();
                
        $sql = "SELECT count(1) as cnt
                FROM mint_boards_comment mbc USE INDEX(idx_recom)
                WHERE mbc.table_code NOT IN (1127, 1129, 1356, 1380, 1381)
                AND (mbc.table_code BETWEEN 1100 AND 1199 OR mbc.table_code BETWEEN 1300 AND 1399) 
                AND ( mbc.tu_uid IS NULL OR mbc.tu_uid <> '99999' )
                AND EXISTS(SELECT 1 FROM mint_boards_name mbn WHERE mbc.table_code = mbn.table_code AND mbn.list_show = 'Y')
                ";

        $tmp = $this->db_master()->query($sql);
        $comment = $tmp->row_array();

        $now = date('Y-m-d H:i:s');

        $this->db_master()->set('total_rows', $comment['cnt']);
        $this->db_master()->set('update_date', $now);
        $this->db_master()->where('table_code', 'comment');
        $this->db_master()->update('mint_boards_total_rows');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    public function list_cate_anony_boards_data($limit,$offset)
    {
        $this->db_connect('master');
                                                                                           
        $sql = "SELECT mb.category_code, mbn.anonymous_yn, mb.mb_unq, mb.table_code FROM mint_boards as mb 
                LEFT JOIN mint_boards_name as mbn ON mb.table_code=mbn.table_code ORDER BY mb.mb_unq ASC LIMIT ".$offset.", ". $limit;

        $res = $this->db_master()->query($sql);
        //echo $this->db_master()->last_query();  
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function update_search_db($update, $table_code, $mb_unq)
    {
        $this->db_connect('search');
        
        $this->db_search()->trans_start();

        $this->db_search()->where('table_code', $table_code);
        $this->db_search()->where('mb_unq', $mb_unq);
        $this->db_search()->update('search_boards', $update);

        $this->db_search()->trans_complete();

        if ($this->db_search()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function row_search_boards_by_mb_unq($table_code, $mb_unq)
    {
        $this->db_connect('search');

        $sql = " SELECT sb.bs_unq AS sb_bs_unq, sb.table_code AS sb_table_code, sb.wiz_id AS sb_wiz_id, sb.name AS sb_name, sb.ename AS sb_ename, sb.nickname AS sb_nickname, sb.noticeYn AS sb_noticeYn, 
                        sb.title AS sb_title, sb.filename AS sb_filename, sb.content AS sb_content, sb.content2 AS sb_content2, sb.input_txt AS sb_input_txt, sb.hit AS sb_hit, sb.comm_hit AS sb_comm_hit,
                        sb.comm_date AS sb_comm_date, sb.regdate AS sb_regdate, sb.secret AS sb_secret, sb.cafe_unq AS sb_cafe_unq, sb.d_id AS sb_d_id, sb.mob AS sb_mob, sb.tu_uid AS sb_tu_uid, 
                        sb.showdate as sb_showdate, sb.name_hide AS sb_name_hide, sb.mb_unq AS sb_mb_unq, sb.del_yn AS sb_del_yn, sb.mins AS sb_mins, sb.class_date AS sb_class_date, sb.tu_name AS sb_tu_name,
                        sb.book_name AS sb_book_name, sb.w_kind AS sb_w_kind, sb.w_mp3 AS sb_w_mp3, sb.w_mp3_type AS sb_w_mp3_type, sb.su AS sb_su, sb.vd_url AS sb_vd_url, sb.w_step AS sb_w_step, 
                        sb.recom AS sb_recom, sb.certify_view AS sb_certify_view, sb.certify_date AS sb_certify_date, sb.select_key AS sb_select_key, sb.parent_key AS sb_parent_key, sb.set_point AS sb_set_point, 
                        sb.category_code AS sb_category_code, sb.anonymous_yn AS sb_anonymous_yn
                FROM search_boards sb 
                WHERE sb.table_code = ? AND sb.mb_unq = ?";

        $res = $this->db_search()->query($sql, array($table_code, $mb_unq));
        // echo $this->db_search()->last_query();
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function insert_search_boards($table_code, $params)
    {
        if(!$params['mb_unq']) return -1;

        $this->db_connect('search');

        $sql = "SELECT * FROM search_boards
                WHERE `table_code`= ? AND `mb_unq` = ?";
        
        $res = $this->db_search()->query($sql, array($table_code, $params['mb_unq']));     
        
        $article = $res->row_array();

        $this->db_search()->trans_start();

        if($article)
        {
            //업데이트
            $this->db_search()->where(array('table_code' => $table_code, 'mb_unq' => $params['mb_unq']));
            $this->db_search()->update('search_boards', $params); 
        }
        else
        {
            //인설트
            $this->db_search()->insert('search_boards', $params);
            $insert_id = $this->db_search()->insert_id();
        }
        
        // return $this->db_search()->last_query();

        $this->db_search()->trans_complete();

        if ($this->db_search()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;

    }

    public function update_search_boards($table_code, $params)
    {
        $this->db_connect('search');

        $this->db_search()->trans_start();

        $this->db_search()->where(array('table_code' => $table_code, 'mb_unq' => $params['mb_unq']));
        $this->db_search()->update('search_boards', $params);
    
        $this->db_search()->trans_complete();

        if ($this->db_search()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;

    }

    //유저채택 체크
    public function checked_article_adopt($table_code, $parent_key, $child_key)
    {
        $this->db_connect('slave');

        $sql = "SELECT mba.a_mb_unq as mba_a_mb_unq
                FROM mint_boards_adopt mba 
                WHERE mba.q_mb_unq = ? AND mba.a_mb_unq = ? AND mba.table_code = ? AND mba.type =1";

        $res = $this->db_slave()->query($sql, array($parent_key, $child_key, $table_code));
        //echo $this->db_slave()->last_query();   
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    
    public function list_count_adopt_by_uid($uid, $table_code)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) AS cnt FROM mint_boards_adopt
                WHERE a_uid = ? AND table_code = ?";

        $res = $this->db_slave()->query($sql, array($uid, $table_code));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    //채택 체크
    public function checked_article_adopt_without_type($table_code, $parent_key, $child_key)
    {
        $this->db_connect('slave');

        $sql = "SELECT 1
                FROM mint_boards_adopt mba 
                WHERE mba.q_mb_unq = ? AND mba.a_mb_unq = ? AND mba.table_code = ?";

        $res = $this->db_slave()->query($sql, array($parent_key, $child_key, $table_code));
        //echo $this->db_slave()->last_query();   
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    //채택된 자식게시물
    public function row_find_child_article_adopt($table_code, $parent_key)
    {
        $this->db_connect('slave');

        $sql = "SELECT group_concat(mba.a_mb_unq) as mba_a_mb_unq
                FROM mint_boards_adopt mba 
                WHERE mba.q_mb_unq = ? AND mba.table_code = ?";

        $res = $this->db_slave()->query($sql, array($parent_key, $table_code));
        //echo $this->db_slave()->last_query();   
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    //채택된 답변 글 갯수가 리미트 초과되었는지 체크
    public function checked_anwser_article_adopt_limit_over($table_code, $parent_key, $type, $limit)
    {
        $this->db_connect('slave');

        $sql = "SELECT 1 FROM 
                    (
                        SELECT count(*) as cnt
                        FROM mint_boards_adopt mba 
                        WHERE mba.q_mb_unq = ? AND mba.table_code = ? AND mba.type = ?
                    ) as tmp
                WHERE tmp.cnt >= ?";

        $res = $this->db_slave()->query($sql, array($parent_key, $table_code, $type, $limit));
        //echo $this->db_slave()->last_query();   
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    // 미채택된 지식인 글 리스트
    public function list_article_unadopted_mint_board($knowledge_qna_type_board, $min_date)
    {
        $this->db_connect('slave');

        $sql = "SELECT mb.table_code as mb_table_code, mb.mb_unq as mb_unq, mb.set_point as mb_set_point,
                    (SELECT mb_unq FROM mint_boards WHERE parent_key=mb.mb_unq AND wiz_id != mb.wiz_id ORDER BY mb_unq ASC LIMIT 1) as a_mb_unq
                FROM mint_boards as mb 
                LEFT JOIN mint_boards_adopt as mba ON mb.table_code=mba.table_code AND mb.mb_unq=mba.q_mb_unq AND mba.type=1
                WHERE mb.regdate > ? AND mb.table_code IN ? AND mb.parent_key IS NULL AND mba.idx IS NULL
                UNION ALL
                SELECT 9001 as mb_table_code, me.uid as mb_unq, 0 as mb_set_point,
                    (SELECT uid FROM mint_express WHERE parent_key=me.uid AND wiz_id != me.wiz_id ORDER BY uid ASC LIMIT 1) as a_mb_unq
                FROM mint_express as me
                LEFT JOIN mint_boards_adopt as mba ON mba.table_code=9001 AND me.uid=mba.q_mb_unq AND mba.type=1
                WHERE me.regdate > ? AND me.parent_key IS NULL AND mba.idx IS NULL
                ";

        $res = $this->db_slave()->query($sql, array($min_date, $knowledge_qna_type_board, $min_date));
        //echo $this->db_slave()->last_query();  
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    // 로그인한 회원이 질문글에 답변썻는지 체크
    public function checked_knowledge_article_anwsered($table_code, $parent_key, $wiz_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT 1
                FROM mint_boards as mb
                WHERE mb.parent_key = ? AND mb.table_code = ? AND mb.wiz_id= ?";

        $res = $this->db_slave()->query($sql, array($parent_key, $table_code, $wiz_id));
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    // 로그인한 회원이 질문글에 답변썻는지 체크(이런표현어떻게)
    public function checked_knowledge_article_anwsered_express($parent_key, $wiz_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT 1
                FROM mint_express as mb
                WHERE mb.parent_key = ? AND mb.wiz_id= ?";

        $res = $this->db_slave()->query($sql, array($parent_key, $wiz_id));
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    // 해당글에 답변이 달렸는지 체크
    public function checked_knowledge_article_has_anwser($parent_key)
    {
        $this->db_connect('slave');

        $sql = "SELECT 1
                FROM mint_boards as mb
                WHERE mb.parent_key = ? ";

        $res = $this->db_slave()->query($sql, array($parent_key));
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    // 해당글에 답변이 달렸는지 체크
    public function checked_knowledge_article_has_anwser_express($parent_key)
    {
        $this->db_connect('slave');

        $sql = "SELECT 1
                FROM mint_express as mb
                WHERE mb.parent_key = ? ";

        $res = $this->db_slave()->query($sql, array($parent_key));
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    //작성자 획득트로피, 완료퀘스트, 받은 추천수
    public function cnt_tropy_quest_recommend($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT tropy.cnt AS tropy_cnt, quest.cnt AS quest_cnt, recommend.cnt AS recommend_cnt FROM
                ( SELECT COUNT(1) AS cnt FROM mint_quest_user_tropy WHERE uid=".$uid.") tropy,
                ( SELECT COUNT(1) AS cnt FROM mint_quest_progress WHERE uid=".$uid." AND complete_date IS NOT NULL ) quest,
                ( SELECT COUNT(1) AS cnt FROM mint_recommend WHERE receive_uid =".$uid." ) recommend";

        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    // 1달전 ns 파일 지우기 위해 해당 게시글 찾기
    public function find_ns_article_for_delete($date, $checkStr)
    {
        $this->db_connect('slave');

        $sql = "SELECT filename, mb_unq, sim_content2, wiz_id
                FROM mint_boards 
                WHERE table_code = '1354' AND (sim_content2 NOT LIKE '%".$checkStr."%' OR sim_content2 = '') 
                AND filename != '' AND regdate > '2019-07-02' AND date_format(regdate, '%Y-%m-%d') < '".$date."'  ";

        $res = $this->db_slave()->query($sql);
        //echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function row_article_comment_by_mbunq_writer($mb_unq,$wiz_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT 
                    mbc.writer_id as mbc_wiz_id ,mbc.regdate as mbc_regdate, mbc.comment as mbc_comment, mbc.table_code as mbc_table_code, 
                    mbc.writer_nickname as mbc_writer_nickname,mbc.co_thread as mbc_co_thread , mbc.mb_unq as mb_unq, mbc.co_unq as mbc_co_unq, 
                    mbc.recom as mbc_recom, mbc.notice_yn as mbc_notice_yn, mbc.memo as mbc_memo
                FROM mint_boards_comment mbc
                WHERE mbc.mb_unq = ? AND mbc.writer_id = ?";

        $res = $this->db_slave()->query($sql,array($mb_unq,$wiz_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


}










