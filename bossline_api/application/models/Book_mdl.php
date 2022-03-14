<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Book_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    
    public function list_select_step1()
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wb.f_id as wb_f_id , wb.book_id as wb_book_id , wb.book_name as wb_book_name,  wb.book_step as wb_book_step 
                FROM wiz_book wb 
                WHERE wb.book_step = '1' AND wb.useyn = 'y' AND wb.select_yn = 'Y' AND wb.d_id = '16' 
                ORDER BY wb.sort ASC";
        $res = $this->db_slave()->query($sql);       
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_select_step2($f_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wb.f_id as wb_f_id ,wb.book_id as wb_book_id , wb.book_name as wb_book_name,  wb.book_step as wb_book_step, wb.book_ebook as wb_book_ebook
                FROM wiz_book wb WHERE wb.book_step = '2' AND wb.f_id = ? AND wb.useyn = 'y' AND wb.select_yn = 'Y'
                ORDER BY sort ASC";
        $res = $this->db_slave()->query($sql, array($f_id));       
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_wiz_book_ahop_result($type, $where)
    {
        $this->db_connect('slave');

        $sql = "SELECT * 
                        FROM wiz_book 
                        WHERE book_name LIKE '%{$type}%' AND book_step = '2' ".$where;
                
        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function list_count_wiz_book_ahop_result($type, $where)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) AS cnt 
                        FROM wiz_book 
                        WHERE book_name LIKE '%{$type}%' AND book_step = '2' ".$where;
                
        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function list_count_ahop_wiz_book_exam_log($wiz_member, $where)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) AS cnt FROM wiz_book_exam_log wbel
                WHERE wbel.uid = '{$wiz_member['wm_uid']}' AND wbel.reply_name = 'COMPLETE' ".$where;
                
        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function list_select_step2_by_f_id_in($f_ids)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wb.f_id as wb_f_id ,wb.book_id as wb_book_id , wb.book_name as wb_book_name,  wb.book_step as wb_book_step, wb.book_ebook as wb_book_ebook
                FROM wiz_book wb WHERE wb.book_step = '2' AND wb.f_id IN ? AND wb.useyn = 'y' AND wb.select_yn = 'Y'
                ORDER BY f_id,sort ASC";
        $res = $this->db_slave()->query($sql, array($f_ids));   
            
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_main_book($where='')
    {
        $this->db_connect('slave');

        $sql = "SELECT wb.book_id as wb_book_id, wb.book_name as wb_book_name,wb.main_img as wb_main_img, wb.book_link2 as wb_book_link2
                FROM wiz_book as wb 
                WHERE wb.book_step = '1' AND wb.useyn = 'y' AND wb.mainyn = 'y' ".$where;
                
        $res = $this->db_slave()->query($sql);       
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    public function list_book()
    {
        $this->db_connect('slave');

        $sql = "SELECT wb.f_id as wb_f_id,wb.book_id as wb_book_id,wb.book_name as wb_book_name ,wb.book_step as wb_book_step
                FROM wiz_book as wb
                WHERE book_step IN ('1','2') AND useyn = 'y' AND select_yn = 'Y' AND d_id = '16' ORDER BY book_step,sort ";
                
        $res = $this->db_slave()->query($sql);       
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    public function list_count_wiz_book_by_book_id($id)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) as cnt FROM wiz_book WHERE book_id = ? ";
        $res = $this->db_slave()->query($sql, array($id));       
        
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_wiz_book_by_book_id($book_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT book_id AS wb_book_id, book_name AS wb_book_name, f_id AS wb_f_id,
                (SELECT wb2.book_name FROM wiz_book wb2 WHERE book_id = wb_f_id) AS f_book_name,
                (SELECT wb2.book_id FROM wiz_book wb2 WHERE book_id = wb_f_id) AS f_book_id
                FROM wiz_book wb
                WHERE book_id = ? ";
        $res = $this->db_slave()->query($sql, array($book_id));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_book_by_id($id)
    {
        $this->db_connect('slave');

        $sql = "SELECT book_id, book_name FROM wiz_book WHERE book_id = ? ";
        $res = $this->db_slave()->query($sql, array($id));       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function row_book_by_sub_query($book_id)
    {
        $this->db_connect('slave');

        $sql = "SELECT book_id,book_name,book_link2 FROM wiz_book WHERE book_id = (SELECT f_id FROM wiz_book WHERE book_id=".$book_id.")";
        $res = $this->db_slave()->query($sql);       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_book_by_id($id)
    {
        $this->db_connect('slave');

        $sql = "SELECT book_id,book_name FROM wiz_book WHERE book_id in ? ";
        $res = $this->db_slave()->query($sql, array(explode(',',$id)));       
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    public function row_bookhistory_by_info($info)
    {
        $this->db_connect('slave');

        $sql = "SELECT wbh.bh_id, wbh.lesson_id, wbh.book_id, wbh.book_name, wbh.book_date, wbh.man_id, wbh.man_name, wbh.regdate, wbh.w_gubun, wl.uid
                FROM  wiz_bookhistory wbh 
                INNER JOIN  wiz_lesson wl ON wbh.lesson_id = wl.lesson_id                
                WHERE wbh.lesson_id = ? AND wbh.book_id = ? ORDER BY wbh.bh_id DESC LIMIT 1";
            
        $res = $this->db_slave()->query($sql, array($info['lesson_id'], $info['book_id']));
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_last_class_book_page($request)
    {
        $this->db_connect('slave');

        $sql = "SELECT mcb.book_page
                FROM  mint_class_book mcb
                WHERE mcb.tu_uid = ? AND mcb.lesson_id = ? AND mcb.book_id = ?";
            
        $res = $this->db_slave()->query($sql, array($request['tu_uid'], $request['lesson_id'], $request['book_id']));

        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_class_book_by_info($join, $where, $order, $limit, $select_col)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT wbh.bh_id, wbh.lesson_id, wbh.book_id, wbh.book_name, wbh.book_date, wbh.man_id, wbh.man_name, wbh.regdate, wbh.w_gubun %s
                        FROM  wiz_bookhistory wbh 
                        %s %s %s %s
                        ", $select_col, $join, $where, $order, $limit);

        $res = $this->db_slave()->query($sql);

        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_book_bookmark($uid, $bookhistory_id, $order, $limit)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mbb.mbb_key, mbb.uid, mbb.bookhistory_id, mbb.book_id, mbb.bookmark_chapter_name, mbb.bookmark_lesson_name, mbb.bookmark_page, mbb.regdate
                        FROM mint_book_bookmark mbb 
                        WHERE mbb.uid = %s AND mbb.bookhistory_id = %s
                        %s %s", $uid, $bookhistory_id, $order, $limit);

        $res = $this->db_slave()->query($sql);       
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_bookmark_by_book_id($uid, $book_id)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT mbb.mbb_key, mbb.uid, mbb.bookhistory_id, mbb.book_id, mbb.bookmark_chapter_name, mbb.bookmark_lesson_name, mbb.bookmark_page, mbb.regdate
                        FROM mint_book_bookmark mbb 
                        WHERE mbb.uid = %s AND mbb.book_id = %s ORDER BY bookmark_page ASC", $uid, $book_id);

        $res = $this->db_slave()->query($sql);       
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    public function list_bookmark_count_by_book_id($uid, $book_id)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT count(mbb.mbb_key) AS cnt
                        FROM mint_book_bookmark mbb 
                        WHERE mbb.uid = %s AND mbb.book_id = %s ORDER BY bookmark_page ASC", $uid, $book_id);

        $res = $this->db_slave()->query($sql);       
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function row_uid_by_wiz_lesson($lesson_id)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT wl.uid AS wl_uid, wl.book_id AS wl_book_id, wl.book_name AS wl_book_name, wl.cl_name AS wl_cl_name
                        FROM wiz_lesson wl
                        WHERE wl.lesson_id = %s", $lesson_id);

        $res = $this->db_slave()->query($sql);       
        
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function update_book_bookmark($bookmark_info)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT mbb.mbb_key FROM mint_book_bookmark mbb WHERE mbb.uid = ? AND mbb.book_id = ? AND mbb.bookmark_page = ?";

        $res = $this->db_master()->query($sql, array($bookmark_info['uid'], $bookmark_info['book_id'], $bookmark_info['bookmark_page']));
        
        $bookmark_log = $res->row_array();
        
        if (!$bookmark_log)
        {
            $this->db_master()->insert('mint_book_bookmark', $bookmark_info);
            $insert_id = $this->db_master()->insert_id();
        }
        else
        {
            $this->db_master()->where(array('uid' => $bookmark_info['uid'], 'book_id' => $bookmark_info['book_id'],  'bookmark_page' => $bookmark_info['bookmark_page']));
            $this->db_master()->delete('mint_book_bookmark');
            $insert_id = 0;
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }


    public function update_class_book($info)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT mcb.mcb_key FROM mint_class_book mcb WHERE mcb.uid = ? AND mcb.bookhistory_id = ?";
        
        $res = $this->db_master()->query($sql, array($info['uid'], $info['bookhistory_id']));
        $class_book_log = $res->row_array();
        
        if (!$class_book_log)
        {
            $this->db_master()->insert('mint_class_book', $info);
        }
        else
        {
            $this->db_master()->where(array('uid' => $info['uid'], 'bookhistory_id' => $info['bookhistory_id']));
            $this->db_master()->update('mint_class_book', $info);
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    public function check_exam_log_by_review_id($review_id, $uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT * FROM wiz_book_exam_log WHERE review_id = ? AND uid = ? AND reply_name = 'FINISH'";

        $res = $this->db_slave()->query($sql,array($review_id,$uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function check_exam_log_by_ex_no($uid, $ex_no)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT ex_id, ex_no, book_name FROM wiz_book_exam_log 
                WHERE uid = ? AND ex_no = ? AND reply_name = 'FINISH' AND reply_uid = 'STATUS' LIMIT 0, 1 ";

        $res = $this->db_slave()->query($sql,array($uid,$ex_no));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function check_exam_log_by_ex_id($uid, $ex_id)
    {
        $this->db_connect('slave');
        
        $sql = " SELECT ex_id, ex_no, book_name, book_id FROM wiz_book_exam_log 
            WHERE uid = ? AND ex_id = ? ";

        $res = $this->db_slave()->query($sql,array($uid,$ex_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }


    public function check_exam_by_titlename($tileName)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT * FROM wiz_book_exam WHERE book_name like '%".$tileName."%' AND step = 'In' AND qno = 0 AND use_yn = 'y' order by book_name asc ";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function check_exam_log_by_ex_no_book_name($uid,$titleName,$steps)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT * FROM wiz_book_exam_log 
                WHERE uid = '".$uid."' and book_name like '%".$titleName."%' AND examdate = '0000-00-00 00:00:00' AND ex_no IN ( ".$steps." ) 
                ORDER BY book_name ASC";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function check_exam_log_finish_date($uid,$book_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT examdate FROM wiz_book_exam_log 
                WHERE uid = ? and book_id= ? 
                ORDER BY examdate DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($uid, $book_id));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function update_book_exam_log_review_id($uid,$mb_unq,$book_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('review_id', $mb_unq);

        $this->db_master()->where('book_id', $book_id);
        $this->db_master()->where('uid', $uid);
        $this->db_master()->where('reply_name', 'FINISH');
        $this->db_master()->where('examdate', '0000-00-00 00:00:00');

        $this->db_master()->update('wiz_book_exam_log');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function row_bookhistory_by_schedule_id($where, $order, $limit)
    {
        $this->db_connect('slave');
        
        $sql = sprintf("SELECT wbh.bh_id, wbh.lesson_id, wbh.book_id, wbh.book_name, wbh.book_date, wbh.man_id, wbh.man_name, wbh.regdate, wbh.w_gubun
                        FROM  wiz_bookhistory wbh 
                        %s %s %s
                        ", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);

        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function check_in_class_write_article($where)
    {
        $this->db_connect('slave');

        $sql = "SELECT wl.uid, wb.f_id, wbh.bh_id, wbh.lesson_id, wbh.book_name, wbh.regdate
        FROM wiz_bookhistory wbh
        JOIN wiz_lesson wl ON wbh.lesson_id = wl.lesson_id
        JOIN wiz_book wb ON wbh.book_id= wb.book_id ".$where;

        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();   
        // exit;
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function check_wiz_book_exam_log($subject)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT el.* FROM wiz_book_exam_log el
                WHERE DATE_FORMAT(el.regdate, '%Y-%m-%d') = DATE_ADD(DATE_FORMAT(now(), '%Y-%m-%d'), INTERVAL -1 DAY) AND el.book_name like '%".$subject."%'
                AND el.uid not in (SELECT uid FROM wiz_book_exam_log sub WHERE DATE_FORMAT(sub.regdate, '%Y-%m-%d') = DATE_FORMAT(now(), '%Y-%m-%d') AND sub.book_name like '%".$subject."%')
                AND el.uid in (SELECT uid FROM wiz_book_exam_log sub WHERE reply_name = 'START' AND sub.book_name like '%".$subject."%')
                GROUP BY el.uid;";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function list_wiz_book_vd_pass_by_uid($uid, $table_code)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wbvp.comment AS wbvp_mb_unq
                -- ,wbvp.regdate AS wbvp_regdate, wbvp.category AS wbvp_category 
                FROM wiz_book_vd_pass wbvp WHERE wbvp.uid = ? AND wbvp.table_code = ?";

        $res = $this->db_slave()->query($sql,array($uid, $table_code));
        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function update_ahop_bookmark($datas)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT wbvp.pidx FROM wiz_book_vd_pass wbvp WHERE wbvp.uid = ? AND wbvp.table_code = ? AND wbvp.category = ? AND wbvp.comment = ?";

        $res = $this->db_master()->query($sql, array($datas['uid'], $datas['table_code'], $datas['category'], $datas['comment']));
        
        $log = $res->row_array();
        
        if($log)
        {
            $this->db_master()->where(array('uid' => $datas['uid'], 'table_code' => $datas['table_code'], 'category' => $datas['category'], 'comment' => $datas['comment']));
            $this->db_master()->delete('wiz_book_vd_pass');
            $insert_id = 0;
        }
        else
        {
            $this->db_master()->insert('wiz_book_vd_pass', $datas);
            $insert_id = $this->db_master()->insert_id();
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function insert_wiz_bookhistory($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_bookhistory', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    

}










