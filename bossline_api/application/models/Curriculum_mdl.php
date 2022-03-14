<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Curriculum_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function list_curriculum($where, $order='', $limit='')
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT mc.mc_key as mc_mc_key, mc.table_code as mc_table_code, mc.name as mc_name, mc.ename as mc_ename, mc.recommend_level as mc_recommend_level, 
                            mc.course_type as mc_course_type, mc.use_yn as mc_use_yn, mc.sorting as mc_sorting, mc.image as mc_image,mc.course_age as mc_course_age,
                            mc.introduction as mc_introduction 
                        FROM mint_curriculum mc

                        %s %s %s", $where, $order, $limit);
                        

        $res = $this->db_slave()->query($sql);
 
        //echo $this->db_slave()->last_query(); exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_count_curriculum($where)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT count(mc.mc_key) AS cnt
                        FROM mint_curriculum mc
                        %s", $where);
                        

        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 커리큘럼 테이블의 키로 하위 교재들 검색하는 함수
    public function list_book_by_mc_key($mc_key)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wb.book_id as wb_book_id,wb.book_name as wb_book_name, wb.main_img as wb_main_img, wb.pdf as wb_pdf, 
                wb.book_link as wb_book_link, wb.new_link as wb_new_link
                FROM wiz_book wb
                WHERE wb.mc_key = ? AND wb.useyn='Y' ORDER BY wb.book_id ASC";

        $res = $this->db_slave()->query($sql, array($mc_key));
        
        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    public function row_curriculum_by_mc_key($mc_key)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mc.mc_key as mc_mc_key,mc.table_code as mc_table_code, mc.name as mc_name, mc.ename as mc_ename, mc.recommend_level as mc_recommend_level,
                mc.course_age as mc_course_age,mc.course_type as mc_course_type, mc.use_yn as mc_use_yn, mc.sorting as mc_sorting, mc.image as mc_image
                FROM mint_curriculum mc
                WHERE mc.mc_key= ?";
                
        $res = $this->db_slave()->query($sql,array($mc_key));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function row_curriculum_by_table_code($table_code)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mc.mc_key as mc_mc_key,mc.table_code as mc_table_code, mc.name as mc_name, mc.ename as mc_ename, mc.recommend_level as mc_recommend_level,
                mc.course_age as mc_course_age,mc.course_type as mc_course_type, mc.use_yn as mc_use_yn, mc.sorting as mc_sorting, mc.image as mc_image
                FROM mint_curriculum mc
                WHERE mc.table_code= ?";
                
        $res = $this->db_slave()->query($sql,array($table_code));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function insert_consult($info)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_consult', $info);
        
        $insert_id = $this->db_master()->insert_id();
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    
    public function check_ticket_count($uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(cidx) as cnt 
                FROM wiz_member_correct_gift 
                WHERE uid = ? AND used = '' AND use_datetime = '0000-00-00 00:00:00' AND use_startdate <= DATE_FORMAT(now(), '%Y-%m-%d') ";
                
        $res = $this->db_slave()->query($sql,array($uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function list_english_article_topic($where)
    {
        $this->db_connect('slave');

        $sql = "SELECT wb.booK_id AS wb_book_id, wb.book_name AS wb_book_name
                FROM wiz_book wb 
                WHERE wb.f_id = ? AND wb.book_step = '2' AND useyn = 'y'
                ORDER BY sort ASC";

        $res = $this->db_slave()->query($sql,array($where));

        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_english_article_count($where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT COUNT(mb.uid) as cnt
                FROM mint_book mb
                INNER JOIN wiz_book wb ON mb.book_id = wb.book_id
                %s ", $where);

        $res = $this->db_slave()->query($sql);

        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_english_article($where, $order, $limit)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mb.uid AS mb_uid, mb.book_id AS mb_book_id, mb.subject AS mb_subject, mb.img AS mb_img, mb.mp3 AS mb_mp3, 
                        mb.explain_doc AS mb_explain_doc, mb.regdate AS mb_regdate, mb.content AS mb_content, 
                        (SELECT count(mbc.b_uid) FROM mint_book_comment mbc WHERE mbc.b_uid = mb.uid) AS com_cnt, wb.book_name AS wb_book_name
                        FROM mint_book mb
                        INNER JOIN wiz_book wb ON mb.book_id = wb.book_id
                        %s %s %s ", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);

        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function row_english_article($f_id, $book_id, $uid)
    {
        $this->db_connect('slave');
        // SELECT mb.uid AS mb_uid, mb.book_id AS mb_book_id, mb.subject AS mb_subject, mb.img AS mb_img
        $sql = "SELECT mb.uid AS mb_uid, mb.subject AS mb_subject, mb.img AS mb_img, mb.align AS mb_align, mb.content  AS mb_content, mb.mp3 AS mb_mp3, mb.explain_doc AS mb_explain_doc, mb.regdate AS mb_regdate
                FROM mint_book mb
                INNER JOIN wiz_book wb ON mb.book_id = wb.book_id
                WHERE mb.use_yn = 'Y' AND wb.f_id = ?  AND mb.book_id = ? AND mb.uid = ?
                ";

        $res = $this->db_slave()->query($sql,array($f_id, $book_id, $uid));

        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_english_article_comment($uid)
    {
        $this->db_connect('slave');
        // SELECT mb.uid AS mb_uid, mb.book_id AS mb_book_id, mb.subject AS mb_subject, mb.img AS mb_img
        $sql = "SELECT mbc.co_unq AS mbc_co_unq, mbc.comment AS mbc_comment, mbc.writer_id aS mbc_writer_id, mbc.regdate AS mbc_regdate
                FROM mint_book_comment mbc
                WHERE mbc.b_uid = ?
                ";

        $res = $this->db_slave()->query($sql, array($uid));

        // echo $this->db_slave()->last_query();
        // exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_count_english_article_comment($b_uid)
    {
        $this->db_connect('slave');
        // SELECT mb.uid AS mb_uid, mb.book_id AS mb_book_id, mb.subject AS mb_subject, mb.img AS mb_img
        $sql = "SELECT COUNT(1) AS cnt
                FROM mint_book_comment mbc
                WHERE mbc.b_uid = ?
                ";

        $res = $this->db_slave()->query($sql, array($b_uid));

        // echo $this->db_slave()->last_query();
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function checked_count_english_article_comment_by_wiz_id($b_uid, $wiz_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT COUNT(1) AS cnt
                FROM mint_book_comment mbc
                WHERE mbc.b_uid = ? AND writer_id = ?
                ";

        $res = $this->db_slave()->query($sql, array($b_uid, $wiz_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function insert_comment_english_article($comment)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_book_comment', $comment);

        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function row_comment_english_article($co_unq, $uid)
    {
        $this->db_connect('slave');
        // SELECT mb.uid AS mb_uid, mb.book_id AS mb_book_id, mb.subject AS mb_subject, mb.img AS mb_img
        $sql = "SELECT mbc.writer_id AS mbc_writer_id
                FROM mint_book_comment mbc
                WHERE mbc.co_unq = ? AND mbc.b_uid = ?
                ";

        $res = $this->db_slave()->query($sql, array($co_unq, $uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function update_comment_english_article($comment, $co_unq, $wiz_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('co_unq' => $co_unq, 'writer_id' => $wiz_id));
        $this->db_master()->update('mint_book_comment', $comment);
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function delete_comment_english_article($co_unq, $uid, $wiz_id, $wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('co_unq' => $co_unq, 'writer_id' => $wiz_id));
        $this->db_master()->delete('mint_book_comment');
    
        $this->db_master()->set('del_regate', 'now()', FALSE);
        $this->db_master()->set('showYn', 'd');
        $this->db_master()->where(array('co_unq' => $co_unq, 'table_code' => $uid,'b_kind'=>'boards'));
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

    // 특정 제목 교재 정보
    public function get_book_with_book_exam($type)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wb.book_name as wb_book_name, wb.main_img as wb_main_img, wb.pic as wb_pic, wb.ch_id as wb_ch_id,
                       wbe.ex_id as wbe_ex_id, wbe.comment as wbe_comment, wb.book_id AS wb_book_id
                FROM wiz_book as wb
                LEFT JOIN wiz_book_exam as wbe ON wb.book_id = wbe.book_id
                WHERE wb.book_name LIKE '%".$type."%' AND wbe.qno = '0' AND wbe.step = 'In'
                ORDER BY wb.sort ASC";

        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function get_count_book_with_book_exam($type)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(1) AS cnt
                FROM wiz_book as wb
                LEFT JOIN wiz_book_exam as wbe ON wb.book_id = wbe.book_id
                WHERE wb.book_name LIKE '%".$type."%' AND wbe.qno = '0' AND wbe.use_yn = 'y' AND wbe.step = 'In'
                ORDER BY wb.sort ASC";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    // 나의 진행 챕터 ExId 구하기
    public function get_progress_chapter($type, $uid, $book_ex_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT el.ex_no, el.reply_name, el.regdate, el.review_id, el.ex_id, el.book_id, el.exam_time, mb.mb_unq, mb.recom
                FROM wiz_book_exam_log as el
                LEFT JOIN mint_boards as mb ON el.review_id = mb.mb_unq
                WHERE el.book_name LIKE '%".$type."%' AND el.uid = '".$uid."' AND el.ex_no IN (".$book_ex_id.")
                group by el.ex_no, el.reply_name
                ORDER BY el.ex_id asc";

        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function get_count_exam_log_by_uid($ex_id, $uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(1) AS cnt
                FROM wiz_book_exam_log as el
                WHERE el.ex_id = '{$ex_id}' AND el.uid = '{$uid}' ";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_exam_log_by_uid($ex_id, $uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT el.ex_no AS el_ex_no, el.reply_name AS el_reply_name, el.regdate AS el_regdate, el.review_id AS el_review_id, 
                        el.ex_id AS el_ex_id, el.book_id AS el_book_id, el.exam_time AS el_exam_time, el.book_name AS el_book_name
                FROM wiz_book_exam_log as el
                WHERE el.ex_id = '{$ex_id}' AND el.uid = '{$uid}' ";

        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function get_exam_point_by_book_id($book_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wb.exam_point AS wb_exam_point
                FROM wiz_book as wb
                WHERE wb.book_id = '{$book_id}'";

        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 최초 시험 시작일 계산
    public function get_exam_start_day($book_id, $uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT DATE_FORMAT(wbl.regdate, '%Y-%m-%d') AS startday
                FROM wiz_book_exam_log wbl
                WHERE wbl.book_id = '{$book_id}' AND wbl.uid = '{$uid}' AND reply_name = 'START'";

        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    

    public function update_ahop_exam_log($ex_id, $uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('reply_name', 'COMPLETE');
        $this->db_master()->where(array('ex_id' => $ex_id, 'uid' => $uid, 'reply_name' => 'FINISH'));
        $this->db_master()->update('wiz_book_exam_log');
        // echo $this->db_master()->last_query();exit;

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    
    public function checked_ahop_exam($book_id, $uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT * FROM wiz_book_exam_log WHERE uid = '{$uid}' AND book_id = '{$book_id}' AND reply_name = 'COMPLETE'";

        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }



    /**
     * AHOP 시험 정보
     */
    public function ahop_exam_info($ex_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wbe.*
                FROM wiz_book_exam as wbe
                WHERE wbe.ex_id = ?
                ";

        $res = $this->db_slave()->query($sql, array($ex_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * AHOP 챕터별 문항 갯수
     */
    public function ahop_exam_total_count($ex_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt
                FROM wiz_book_exam as wbe
                WHERE wbe.parent_id = ? AND wbe.qno != 0";

        $res = $this->db_slave()->query($sql, array($ex_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * AHOP 시험 리스트(메인 타이틀 리스트)
     */
    public function get_ahop_exam_list_($book_id, $f_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT wbe.ex_id as wbe_ex_id, wbe.step as wbe_step, wbe.chapter as wbe_chapter, wbe.comment as wbe_comment,
                       wbe.book_id as wbe_book_id, wbe.book_name as wbe_book_name
                FROM wiz_book_exam as wbe
                WHERE wbe.qno = '0' AND wbe.book_id = ? AND wbe.f_id = ? AND wbe.use_yn = 'y'
                ORDER BY wbe.chapter asc";

        $res = $this->db_slave()->query($sql, array($book_id, $f_id));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    /**
     * AHOP 시험 리스트(해당 챕터의 시험리스트)
     * 시험 순서는 랜덤하게 섞어서 보여준다
     */
    public function get_ahop_exam_chapter_list_($ex_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT GROUP_CONCAT(wbe.ex_id ORDER BY RAND()) AS ex_list, COUNT(1) AS cnt
                FROM wiz_book_exam as wbe
                WHERE wbe.qno != '0' AND wbe.parent_id = ? AND wbe.use_yn = 'y' AND wbe.chapter = ''
                ORDER BY wbe.chapter asc";

        $res = $this->db_slave()->query($sql, array($ex_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * AHOP 테스트 시험 리스트(테스트시험 후 오답리스트를 정보를 다 불러올때)
     */
    public function get_test_ahop_exam_wrong_answer_list_($ex_id_list)
    {
        $this->db_connect('slave');

        $sql = "SELECT wbe.*
                FROM wiz_book_exam as wbe
                WHERE wbe.ex_id IN (".$ex_id_list.") AND wbe.use_yn = 'y' AND wbe.chapter = ''
                ORDER BY wbe.chapter asc";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    /**
     * 시험지 정보(시험지,유저 별)
     */
    public function get_exam_log_by_ex_no_to_uid($ex_no, $uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT el.ex_no AS el_ex_no, el.reply_name AS el_reply_name, el.regdate AS el_regdate, el.review_id AS el_review_id, 
                       el.ex_id AS el_ex_id, el.book_id AS el_book_id, el.exam_time AS el_exam_time, el.book_name AS el_book_name,
                       el.o_total AS el_o_total, el.q_total AS el_q_total, el.my_exam AS el_my_exam, el.my_answers AS el_my_answers, el.my_ox AS el_my_ox,
                       wbe.remain_time as wbe_remain_time, wbe.chapter as wbe_chapter, wbe.book_id as wbe_book_id
                FROM wiz_book_exam_log as el
                LEFT JOIN wiz_book_exam as wbe ON wbe.ex_id = el.ex_no
                WHERE el.ex_no = '{$ex_no}' AND el.uid = '{$uid}'
                ORDER BY el.ex_id DESC
                limit 1";

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 시험지 정보(시험지,유저 별)
     * 완료되지 않은 시험 체크
     */
    public function get_not_completed_exam_log($ex_no, $uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT el.ex_no AS el_ex_no, el.reply_name AS el_reply_name, el.regdate AS el_regdate, el.review_id AS el_review_id, 
                       el.ex_id AS el_ex_id, el.book_id AS el_book_id, el.exam_time AS el_exam_time, el.book_name AS el_book_name,
                       el.o_total AS el_o_total, el.q_total AS el_q_total, el.my_exam AS el_my_exam, el.my_answers AS el_my_answers, el.my_ox AS el_my_ox
                FROM wiz_book_exam_log as el
                WHERE el.ex_no = '{$ex_no}' AND el.uid = '{$uid}' AND examdate = '0000-00-00' AND reply_name = ''
                ORDER BY el.regdate DESC
                limit 1";

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * AHOP 시험 로그 정보
     */
    public function get_wiz_book_exam_log_by_ex_id($ex_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT el.ex_no AS el_ex_no, el.reply_name AS el_reply_name, el.regdate AS el_regdate, el.review_id AS el_review_id, 
                       el.ex_id AS el_ex_id, el.book_id AS el_book_id, el.exam_time AS el_exam_time, el.book_name AS el_book_name,
                       el.o_total AS el_o_total, el.q_total AS el_q_total, el.my_exam AS el_my_exam, el.my_answers AS el_my_answers, el.my_ox AS el_my_ox,
                       wbe.remain_time as wbe_remain_time, wbe.chapter as wbe_chapter, wbe.book_id as wbe_book_id
                FROM wiz_book_exam_log as el
                LEFT JOIN wiz_book_exam as wbe ON wbe.ex_id = el.ex_no
                WHERE el.ex_id = '{$ex_id}'";

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * 오늘 무료시험 진행 여부 체크(시험별)
     */
    public function chk_free_exam_log_by_ex_no_to_uid($book_id, $uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT el.ex_id AS el_ex_id
                FROM wiz_book_exam_log as el
                LEFT JOIN wiz_book_exam as wbe ON wbe.ex_id = el.ex_no
                WHERE el.book_id = '{$book_id}' AND el.uid = '{$uid}' AND el.examdate > '".date('Y-m-d')." 00:00:00'
                ORDER BY el.ex_id DESC
                limit 1";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    /**
     * AHOP 시험 챕터별 로그 입력
     */
    public function insert_wiz_book_exam_log($params)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_book_exam_log', $params);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    /**
     * AHOP 시험 챕터별 로그 업데이트
     */
    public function update_wiz_book_exam_log($params, $where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->update('wiz_book_exam_log', $params);
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    /**
     * AHOP 시험 로그 삭제
     */
    public function delete_wiz_book_exam_log($where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->delete('wiz_book_exam_log');
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    /**
     * 오답지우기 티켓 사용
     */
    public function use_wrong_answer_ticket($wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT cidx 
                FROM wiz_member_correct_gift
                WHERE uid = ? AND used = '' AND use_datetime = '0000-00-00 00:00:00' AND use_startdate <= DATE_FORMAT(now(), '%Y-%m-%d')
                limit 1";
        $tmp = $this->db_master()->query($sql, array($wm_uid));    
        $gift = $tmp->num_rows() > 0 ? $tmp->row_array() : NULL;
        if(!$gift) return -1;

        $this->db_master()->where(array('cidx'=>$gift['cidx']));
        $this->db_master()->update('wiz_member_correct_gift', array('used'=>'Y','use_datetime'=>date('Y-m-d H:i:s')));
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

}
