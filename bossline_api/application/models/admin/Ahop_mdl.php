<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Ahop_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }


    public function list_count_exam($where, $order='', $limit='')
    {
        
        $this->db_connect('slave');

        $sql = sprintf("SELECT count(1) AS cnt
                        FROM wiz_book_exam wbe
                    %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);  
        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_exam($where, $order='', $limit='')
    {
        
        $this->db_connect('slave');

        $sql = sprintf("SELECT wbe.ex_id AS wbe_ex_id, wbe.parent_id AS wbe_parent_id, wbe.book_id AS wbe_book_id, wbe.f_id AS wbe_f_id, wbe.book_top AS wbe_book_top,
                                wbe.book_name AS wbe_book_name, wbe.step AS wbe_step, wbe.chapter AS wbe_chapter, wbe.unit AS wbe_unit, wbe.atxt AS wbe_atxt,
                                wbe.qno AS wbe_qno, wbe.qtitle AS wbe_qtitle, wbe.option AS wbe_option, wbe.a1 AS wbe_a1, wbe.a2 AS wbe_a2, wbe.a3 AS wbe_a3, wbe.a4 AS wbe_a4,
                                wbe.answer AS wbe_answer, wbe.answer_align AS wbe_answer_align, wbe.remain_time AS wbe_remain_time, wbe.answer_all AS wbe_answer_all,
                                wbe.use_yn AS wbe_use_yn, wbe.comment AS wbe_comment,
                                (SELECT count(1) FROM wiz_book_exam wbe2 WHERE wbe2.parent_id = wbe.ex_id AND wbe2.qno > 0) AS wbe_exam_cnt
                        FROM wiz_book_exam wbe
                    %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);  
        // echo $this->db_slave()->last_query();  exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function get_exam($where)
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT qtitle AS wbe_qtitle, book_id AS wbe_book_id, step AS wbe_step, chapter AS wbe_chapter, ex_id AS wbe_ex_id, atxt AS wbe_atxt, unit AS wbe_unit,
                        lesson AS wbe_lesson, remain_time AS wbe_remain_time, wbe.comment AS wbe_comment,
                        (SELECT book_name FROM wiz_book WHERE book_id = wbe.book_id) AS wbe_book_name 
                        FROM wiz_book_exam wbe
                    %s", $where);

        $res = $this->db_slave()->query($sql);  
        // echo $this->db_slave()->last_query();  exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_exam_category()
    {
        
        $this->db_connect('slave');

        $sql = sprintf("SELECT wb.book_id AS wb_book_id, wb.book_name AS wb_book_name 
                        FROM wiz_book wb 
                        WHERE book_step = '2' AND useyn = 'y' AND exam != '' ORDER BY book_id ASC");

        $res = $this->db_slave()->query($sql);  

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function update_ahop_exam($datas)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->update_batch('wiz_book_exam', $datas, 'ex_id');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function update_ahop_exam_use($ex_id, $use_yn)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('ex_id', $ex_id);
        $this->db_master()->set('use_yn', $use_yn);
        $this->db_master()->update('wiz_book_exam');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function list_count_wiz_book()
    {
        $this->db_connect('slave');

        $sql = "SELECT count(1) AS cnt FROM wiz_book wb
                WHERE book_step = '1' AND useyn = 'y' AND d_id = '16'";
                    
        $res = $this->db_slave()->query($sql);  
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_wiz_book($where, $order='', $limit='')
    {
        $this->db_connect('slave');

        $sql = sprintf("SELECT f_id AS wb_f_id, book_id AS wb_book_id, book_name AS wb_book_name, exam AS wb_exam, exam_point AS wb_exam_point
                        FROM wiz_book wb
                        %s %s %s", $where, $order, $limit);

        $res = $this->db_slave()->query($sql);  
        // echo $this->db_slave()->last_query();exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    public function update_wiz_book($datas)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->update_batch('wiz_book', $datas, 'book_id');
        
        // echo $this->db_master()->last_query();exit;

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function list_ahop_exam_select_info($book_id)
    {
        
        $this->db_connect('slave');

        $sql = 
        "
        (
            SELECT wbe.ex_id AS wbe_ex_id, wbe.chapter AS wbe_step
            FROM wiz_book_exam wbe
            WHERE (chapter != '') AND book_id = '{$book_id}'
            )
        UNION ALL 
        (
            SELECT wbe.ex_id AS wbe_ex_id, wbe.step AS wbe_step
            FROM wiz_book_exam wbe
            WHERE (step = 'In') AND book_id = '{$book_id}'
            )
        ";

        $res = $this->db_slave()->query($sql);  
        // echo $this->db_slave()->last_query();exit;

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function insert_wiz_book_exam($datas)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->insert('wiz_book_exam', $datas);
        
        $insert_id = $this->db_master()->insert_id();

        if($insert_id){

            $parent_id = array('parent_id' => $insert_id);
            $this->db_master()->where(array('ex_id' => $insert_id));
            $this->db_master()->update('wiz_book_exam', $parent_id);
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function update_wiz_book_exam($datas)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->set('qtitle', $datas['qtitle']);
        $this->db_master()->set('remain_time', $datas['remain_time']);
        $this->db_master()->set('comment', $datas['comment']);
        $this->db_master()->set('step', $datas['step']);
        $this->db_master()->set('chapter', $datas['chapter']);
        $this->db_master()->set('qno', $datas['qno']);
        $this->db_master()->set('a1', $datas['a1']);
        $this->db_master()->set('a2', $datas['a2']);
        $this->db_master()->set('a3', $datas['a3']);
        $this->db_master()->set('a4', $datas['a4']);
        $this->db_master()->set('answer', $datas['answer']);
        $this->db_master()->set('atxt', $datas['atxt']);
        $this->db_master()->set('book_id', $datas['book_id']);
        $this->db_master()->set('book_name', $datas['book_name']);
        $this->db_master()->set('f_id', $datas['f_id']);
        $this->db_master()->set('book_top', $datas['book_top']);
        $this->db_master()->set('use_yn', $datas['use_yn']);
        $this->db_master()->where('ex_id', $datas['ex_id']);

        $this->db_master()->update('wiz_book_exam');
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    public function insert_batch_wiz_book_exam($datas)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();
        
        $this->db_master()->insert_batch('wiz_book_exam', $datas);
        
        $insert_id = $this->db_master()->insert_id();
        // echo $this->db_master()->last_query();exit;

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $insert_id;
    }

    public function delete_wiz_book_exam($ex_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where(array('parent_id' => $ex_id));
        $this->db_master()->delete('wiz_book_exam');
    
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


}










