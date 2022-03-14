<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Mset_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function check_is_freetest($wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT startday FROM mint_mset_report WHERE uid= ? AND status IN ('2','3','5') AND use_point='0' ORDER BY startday DESC";
        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }


    public function check_ongoing_mset($wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT 1 FROM mint_mset_report WHERE uid= ? AND status IN (0, 1)";
        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function check_run_mset_thisweek($wm_uid, $monday, $friday)
    {
        $this->db_connect('slave');

        $sql = "SELECT 1 FROM mint_mset_report WHERE uid= ? AND status=2 AND startday BETWEEN '".$monday."' AND '".$friday."'";
        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    
    public function list_mset_tutor()
    {
        $this->db_connect('slave');

        $sql = "SELECT wt.tu_uid as wt_tu_uid, wt.tu_id as wt_tu_id, wt.tu_name as wt_tu_name, wt.tropy as wt_tropy, wt.pre_pro as wt_pre_pro 
                FROM wiz_tutor as wt
                WHERE wt.mset_possible=1 AND wt.del_yn='n'
                AND (
                    wt.group_id NOT IN (28,29)
                    OR (wt.group_id IN (44, 60) AND wt.tt_type!='none')
                    OR (wt.group_id2 IN (44, 60) AND wt.tt_type!='none')
                ) 
                ORDER BY wt.tu_name ASC";
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }


    public function count_mset_complete($wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT COUNT(*) as cnt FROM mint_mset_report WHERE uid=?  AND status=2";
        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function check_mset_paper_idx($date)
    {
        $this->db_connect('slave');

        $sql = "SELECT idx FROM mint_mset_exam_paper
                WHERE startday <= ? AND ? <= endday AND disabled=0 
                ORDER BY startday DESC, endday DESC, idx DESC LIMIT 1";

        $res = $this->db_slave()->query($sql, array($date, $date));

        if($res->num_rows() == 0)
        {
            $sql = "SELECT idx FROM mint_mset_exam_paper
                    WHERE startday <= ? AND endday <= ? AND disabled=0 
                    ORDER BY startday DESC, endday DESC, idx DESC LIMIT 1";

            $res = $this->db_slave()->query($sql, array($date, $date));
        }

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }


    public function insert_mset($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_mset_report', $param);
        $insert_id = $this->db_master()->insert_id();

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return $insert_id;
    }
    
    public function update_mset($param,$where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->update('mint_mset_report', $param);
        //echo $this->db_master()->last_query();
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    //MSET 회원 정보 업데이트
    public function update_member_last_msetdate($wm_uid)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $sql = "SELECT * FROM mint_mset_report WHERE uid = ? AND status IN ('0','1','2','5') AND use_point='0' ORDER BY startday DESC";

        $res = $this->db_master()->query($sql, array($wm_uid));
        $result = $res->row_array();
        //최근 무료mset 날짜
        $last_mset_date = $result ? $result['startday']:'0000-00-00';

        $sql = "SELECT count(*) as cnt FROM mint_mset_report WHERE uid = ? AND status IN ('2','5') AND use_point='0'";

        $res = $this->db_master()->query($sql, array($wm_uid));
        $result = $res->row_array();
        //무료MSET 완료횟수
        $mset_count = $result ? (int)$result['cnt']:0;

        $param = [
            'last_mset_date' => $last_mset_date,
            'free_mset_count' => $mset_count,
        ];

        $this->db_master()->where('uid', $wm_uid);
        $this->db_master()->update('wiz_member', $param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    
    public function list_count_mset_apply($where)
    {
        $this->db_connect('slave');

        $sql = "SELECT count(*) as cnt
                FROM mint_mset_report as mmr WHERE ".$where;

        $res = $this->db_slave()->query($sql);
        //echo $this->db_slave()->last_query();
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }
    
    public function list_mset_apply($where, $orderby, $limit)
    {
        $this->db_connect('slave');

        $sql = "SELECT mmr.idx as mmr_idx, mmr.startday as mmr_startday, mmr.endday as mmr_endday, mmr.mset_gubun as mmr_mset_gubun, mmr.status as mmr_status, mmr.tel as mmr_tel,
                mmr.overall_level as mmr_overall_level, mmr.use_point as mmr_use_point, mmr.mobile as mmr_mobile, mmep.student_jpg as mmep_student_jpg, mmep.student_pdf as mmep_student_pdf
                FROM mint_mset_report as mmr 
                LEFT JOIN mint_mset_exam_paper mmep ON mmr.exam_idx=mmep.idx AND mmep.disabled=0
                WHERE ".$where.$orderby.$limit;

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function row_mset_apply($idx)
    {
        $this->db_connect('slave');

        $sql = "SELECT mmr.idx as mmr_idx, mmr.status as mmr_status, mmr.uid as mmr_uid, mmr.sc_id as mmr_sc_id, mmr.startday as mmr_startday,mmr.korean_name as mmr_korean_name,
                mmr.description_mint_level as mmr_description_mint_level, mmr.overall_score as mmr_overall_score, mmr.overall_total as mmr_overall_total, mmr.overall_level as mmr_overall_level,
                mmr.overall_level_message as mmr_overall_level_message, mmr.overall_description as mmr_overall_description, mmr.overall_description_add as mmr_overall_description_add,
                mmr.overall_comment as mmr_overall_comment, mmr.pronunciation_level as mmr_pronunciation_level, mmr.fluency_level as mmr_fluency_level, mmr.vocabulary_level as mmr_vocabulary_level,
                mmr.speaking_level as mmr_speaking_level, mmr.grammar_level as mmr_grammar_level, mmr.listening_level as mmr_listening_level, mmr.function_level as mmr_function_level,
                mmr.pronunciation_description as mmr_pronunciation_description, mmr.pronunciation_description_add as mmr_pronunciation_description_add, 
                mmr.pronunciation_advice as mmr_pronunciation_advice, mmr.pronunciation_advice_add as mmr_pronunciation_advice_add, mmr.pronunciation_comment as mmr_pronunciation_comment,
                mmr.fluency_description as mmr_fluency_description, mmr.fluency_description_add as mmr_fluency_description_add, mmr.fluency_advice as mmr_fluency_advice,
                mmr.fluency_advice_add as mmr_fluency_advice_add, mmr.fluency_comment as mmr_fluency_comment, mmr.vocabulary_description as mmr_vocabulary_description,
                mmr.vocabulary_description_add as mmr_vocabulary_description_add, mmr.vocabulary_advice as mmr_vocabulary_advice, mmr.vocabulary_advice_add as mmr_vocabulary_advice_add,
                mmr.vocabulary_comment as mmr_vocabulary_comment, mmr.speaking_description as mmr_speaking_description, mmr.speaking_description_add as mmr_speaking_description_add,
                mmr.speaking_advice as mmr_speaking_advice, mmr.speaking_advice_add as mmr_speaking_advice_add, mmr.speaking_comment as mmr_speaking_comment,
                mmr.grammar_description as mmr_grammar_description, mmr.grammar_description_add as mmr_grammar_description_add, mmr.grammar_advice as mmr_grammar_advice,
                mmr.grammar_advice_add as mmr_grammar_advice_add, mmr.grammar_comment as mmr_grammar_comment, mmr.listening_description as mmr_listening_description,
                mmr.listening_description_add as mmr_listening_description_add, mmr.listening_advice as mmr_listening_advice, mmr.listening_advice_add as mmr_listening_advice_add,
                mmr.listening_comment as mmr_listening_comment, mmr.function_description as mmr_function_description, mmr.function_description_add as mmr_function_description_add,
                mmr.function_advice as mmr_function_advice, mmr.function_advice_add as mmr_function_advice_add, mmr.function_comment as mmr_function_comment, mmr.mset_gubun as mmr_mset_gubun,
                mmr.mobile as mmr_mobile, mmr.tel as mmr_tel, mmr.tu_uid as mmr_tu_uid
                FROM mint_mset_report as mmr 
                WHERE mmr.idx = ?";

        $res = $this->db_slave()->query($sql, array($idx));
        
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function mset_level_data($where)
    {
        $this->db_connect('slave');

        $sql = "SELECT overall_level, pronunciation_level, fluency_level, vocabulary_level, speaking_level, grammar_level, listening_level, function_level, startday, overall_score
                FROM mint_mset_report
                WHERE ".$where;

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function mset_score_comparison($level)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM mint_mset_score_comparison WHERE level = ?";

        $res = $this->db_slave()->query($sql, array($level));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }


    public function mset_score_summary($date='')
    {
        $this->db_connect('slave');

        if($date =='')
        {
            $sql = "SELECT `key`, `value` FROM mint_mset_report_summary 
                    WHERE regdate = (
                            SELECT regdate FROM mint_mset_report_summary ORDER BY regdate DESC LIMIT 1
                        )";

            $res = $this->db_slave()->query($sql);
        }
        else
        {
            $sql = "SELECT `key`, `value` FROM mint_mset_report_summary WHERE regdate = ?";

            $res = $this->db_slave()->query($sql, array($date));
        }
        
        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    
    public function checked_mset_next_report($cur_idx, $wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM mint_mset_report WHERE idx > ? AND status=2 AND uid = ? ORDER BY idx ASC Limit 1";

        $res = $this->db_slave()->query($sql, array($cur_idx, $wm_uid));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function checked_mset_prev_report($cur_idx, $wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM mint_mset_report WHERE idx < ? AND status=2 AND uid = ? ORDER BY idx DESC Limit 1";

        $res = $this->db_slave()->query($sql, array($cur_idx, $wm_uid));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    
    public function delete_mint_mset_report_summary($where)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where($where);
        $this->db_master()->delete('mint_mset_report_summary');
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    
    public function insert_mint_mset_report_summary($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('mint_mset_report_summary', $param);
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    //유저별 해당날짜 이전의 가장 최근 엠셋 점수 추출
    public function get_mset_score_by_uid_recent($date)
    {
        $this->db_connect('slave');

        $sql = "SELECT pronunciation_level, fluency_level, vocabulary_level, speaking_level, grammar_level, listening_level, function_level, overall_score
                FROM `mint_mset_report` WHERE 
                idx IN 
                (
                    SELECT max(idx) AS idx
                        FROM `mint_mset_report` WHERE 
                    status=2 AND startday <'".$date." 23:59:59' GROUP BY uid
                )";
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    /**
     * 가장 최근 엠셋 시험 정보 조회
     * 엠셋 시험 완료된 정보에서 레벨정보만 추출
     */
    public function get_mset_new_info($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT mmr.overall_level as mmr_overall_level
                FROM mint_mset_report as mmr
                WHERE mmr.uid = '".$uid."' AND mmr.status = '2'
                ORDER BY mmr.idx DESC LIMIT 1";
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

}










