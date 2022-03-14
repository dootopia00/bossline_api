<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Leveltest_mdl extends _Base_Model {

	public function __construct()
	{
		parent::__construct();

    }

    public function check_leveltest_exist($wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM `wiz_leveltest` WHERE `uid`= ? ORDER BY le_id DESC LIMIT 1";
        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function check_leveltest_exist_asc($wm_uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT * FROM `wiz_leveltest` WHERE `uid`= ? ORDER BY le_id ASC LIMIT 1";
        $res = $this->db_slave()->query($sql, array($wm_uid));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    /* public function update_save_send_msg($idx, $wm_uid)
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
    } */


    
    public function list_count_leveltest($uid)
    {
        $this->db_connect('slave');
                        
        $sql = "SELECT count(1) as cnt
                FROM wiz_leveltest wl
                WHERE uid =  ? ";
        
        $res = $this->db_slave()->query($sql, $uid);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_count_leveltest_by_lefid($le_fid, $uid)
    {
        $this->db_connect('slave');
                        
        $sql = "SELECT count(1) as cnt
                FROM wiz_leveltest wl
                WHERE le_fid =  ? AND uid = ?";
        
        $res = $this->db_slave()->query($sql, array($le_fid, $uid));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_count_leveltest_by_lefid_lestep($le_fid, $uid)
    {
        $this->db_connect('slave');
                        
        $sql = "SELECT count(1) as cnt
                FROM wiz_leveltest wl
                WHERE le_fid =  ? AND uid = ? AND le_step != '1'";
        
        $res = $this->db_slave()->query($sql, array($le_fid, $uid));

        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    public function list_count_leveltest_where($index, $where)
    {
        $this->db_connect('slave');
    
        $sql = sprintf("SELECT count(1) as cnt
                        FROM wiz_leveltest wl %s
                        %s", $index, $where);
                        
        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    public function list_leveltest_by_uid($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wl.le_id, wl.le_fid, wl.uid, wl.wiz_id, wl.name, wl.tu_uid, wl.tu_name, wl.mobile, wl.sc_id, wl.lesson_gubun,
                wl.englevel, wl.hopeclass, wl.le_start, wl.le_end, wl.book_id, wl.book_name, wl.lev_id, wl.lev_name, wl.lev_gubun, wl.le_step, wl.consult_ok, wl.regdate,
                wl.listening, wl.speaking, wl.pronunciation, wl.vocabulary, wl.grammar, wl.ev_memo
                FROM wiz_leveltest wl 
                WHERE uid =  ? ORDER BY regdate DESC";

        $res = $this->db_slave()->query($sql, array($uid));
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function list_leveltest_by_lefid($le_fid, $uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wl.le_id, wl.le_fid, wl.uid, wl.wiz_id, wl.name, wl.tu_uid, wl.tu_name, wl.mobile, wl.sc_id, wl.lesson_gubun,
                wl.englevel, wl.le_start, wl.le_end, wl.book_id, wl.book_name, wl.lev_id, wl.lev_gubun, wl.le_step, wl.consult_ok, wl.regdate
                FROM wiz_leveltest wl 
                WHERE le_fid =  ? AND uid = ? AND le_step = '1'";

        $res = $this->db_slave()->query($sql, array($le_fid, $uid));
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    /* 
        회원 레벨테스트 진행도 체크 
        - wl.le_step : 레벨테스트 진행도 (1:준비, 2:결석, 3:완료)
    
        1. 시작 30분전 삭제 / 변경 가능(기존 신청 내역 삭제)
        2. 시작 30분 이내~테스트 시간 모두 종료전까지 삭제 / 변경 불가
        3. 마지막 테스트 종료시간 지난 후 테스트들 중에 결석이 하나라도 있으면 테스트 재신청 가능(기존 테스트 내역은 유지한 상태로 추가 신청)
        4. 마지막 테스트 종료시간 지난 후 테스트들이 모두 출석이면 재신청 불가.

        total_cnt : 레벨테스트 총횟수
        attendance_cnt : 진행완료된 레벨테스트 갯수
        schedule_cnt : 진행예정인 레벨테스트 갯수
        change_restriction_cnt : 변경 불가능한 (시작 30분전) 레벨테스트 갯수

        $req_le_fid : 레벨테스트 그룹번호 - 레벨테스트 변경요청시 해당 레벨테스트 그룹번호 
    */
    public function check_member_leveltest_progress($wm_uid, $req_le_fid)
    {
        $this->db_connect('slave');

        $le_fid = $req_le_fid;        
        
        // 레벨테스트 변경요청이 아닐때에는 가장 최근 레벨테스트 정보를 기준으로 조회
        if(!$req_le_fid)
        {
            $sql = "SELECT wl.le_fid FROM wiz_leveltest wl WHERE wl.uid = ? ORDER BY wl.le_id DESC LIMIT 1";
            $tmp_res = $this->db_slave()->query($sql, array($wm_uid));
            $tmp = $tmp_res->row_array();
            
            $le_fid = $tmp['le_fid'];
        }
                        
        $sql = "SELECT 
                    (SELECT count(le_id) FROM wiz_leveltest WHERE le_fid = ?) as total_cnt,
                    (SELECT count(le_id) FROM wiz_leveltest WHERE le_fid = ? AND le_step = 3) as attendance_cnt,
                    (SELECT count(le_id) FROM wiz_leveltest WHERE le_fid = ? AND le_step = 1  AND le_end > now()) as schedule_cnt,
                    (SELECT count(le_id) FROM wiz_leveltest WHERE le_fid = ? AND ( (le_step = 1  AND TIMESTAMPDIFF(MINUTE ,NOW(), le_start) BETWEEN 0 AND 30) OR (le_step <> 1) ) ) as change_restriction_cnt
                FROM wiz_leveltest wl 
                WHERE 
                    wl.le_fid = ?
                GROUP BY le_fid ";
        
        $res = $this->db_slave()->query($sql, array($le_fid, $le_fid, $le_fid, $le_fid, $le_fid));
        // echo $this->db_slave()->last_query();   
        // exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;

    }

    /* 
        레벨테스트 신청

        $req_le_fid : 레벨테스트 그룹번호 - 레벨테스트 변경요청시 해당 레벨테스트 그룹번호 
        레벨테스트 변경요청시에는 기존 레벨테스트 삭제후 새로 등록
    */
    public function apply_leveltest($data_leveltest, $data_memo, $wm_uid, $wm_muu_key, $req_le_fid)
    {

        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert_batch('wiz_leveltest', $data_leveltest);

        $sql = "SELECT MIN(wl.le_id) AS min_id FROM wiz_leveltest wl WHERE wl.uid = ? AND le_fid IS NULL";
        $res = $this->db_master()->query($sql, array($wm_uid));
        
        $tmp = $res->row_array();
        
        $this->db_master()->set('le_fid', $tmp['min_id'], FALSE);
        $this->db_master()->where('uid', $wm_uid);
        $this->db_master()->where('le_fid', NULL);
        $this->db_master()->update('wiz_leveltest');

        $this->db_master()->set('leveltest_state', 'Y');
        $this->db_master()->where('uid', $wm_uid);
        $this->db_master()->update('wiz_member');

        /* 요청사항있을시 */
        if($data_memo['memo'])
        {
            $sql_memo = "SELECT count(mh.unq) cnt FROM member_howman mh WHERE mh.uid = ? ";
            $res_memo = $this->db_master()->query($sql_memo, array($wm_uid));
            $tmp_memo = $res_memo->num_rows() > 0 ? $res_memo->row_array() : NULL;

            if($tmp_memo)
            {
                $this->db_master()->set('memo', $data_memo['memo']);
                $this->db_master()->where('uid', $wm_uid);
                $this->db_master()->update('member_howman');
            }
            else
            {
                $this->db_master()->insert('member_howman', $data_memo);
            }

        }

        /* 레벨테스트 변경시 기존 레벨테스트 삭제 */
        if($req_le_fid)
        {
            // 레벨 테스트 스케쥴 삭제
            $sql = "DELETE FROM wiz_schedule WHERE uid = ? AND sc_id IN (SELECT sc_id FROM wiz_leveltest WHERE uid = ? AND le_fid = ? )";
            $this->db_master()->query($sql, array($wm_uid, $wm_uid, $req_le_fid));

            // 레벨 테스트 삭제
            $this->db_master()->where('le_fid', $req_le_fid);
            $this->db_master()->where('uid', $wm_uid);
            $this->db_master()->delete('wiz_leveltest');
        }

        /* UTM */
        if($wm_muu_key != 0)
        {
            $data_utm = array(
                'muu_key' => $wm_muu_key,
				'ref_key' => $tmp['min_id'],
				'ref_uid' => $wm_uid,
				'type' => '3',						                            // 1: 방문자수(1일 1로그), 2: 회원가입, 3: 레벨테스트 신청, 4: 결제, 5: 방문횟수(로그제한 없음)
				'loc' => ($data_leveltest[0]['mob'] == 'N') ? 1 : 2,				// 1: pc, 2:mobile
				'ip' => $_SERVER["REMOTE_ADDR"],
				'regdate' => date("Y-m-d H:i:s"),
            );

            $utm_log_sql = "SELECT count(mul.mul_key) AS cnt FROM mint_utm_log mul WHERE mul.type = 3 AND mul.ref_uid = ? ";
            $utm_log_res = $this->db_master()->query($utm_log_sql, array($wm_uid));
            $utm_log = $utm_log_res->row_array();
            
            if($utm_log['cnt'] == 0)
            {
                $this->db_master()->insert('mint_utm_log', $data_utm);
            }
            
        }


        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return $tmp['min_id'];
    }

    /* 
        레벨테스트 삭제(취소)
        - 레벨테스트 스케쥴 삭제
        - 레벨테스트 삭제
        - 회원 레벨테스트 여부 N으로 변경  
    */
    public function delete_leveltest($wm_uid, $le_fid)
    {

        $this->db_connect('master');

        $this->db_master()->trans_start();

        // 레벨 테스트 스케쥴 삭제
        $sql = "DELETE FROM wiz_schedule WHERE uid = ? AND sc_id IN (SELECT sc_id FROM wiz_leveltest WHERE uid = ? AND le_fid = ? )";
        $this->db_master()->query($sql, array($wm_uid, $wm_uid, $le_fid));

        // 레벨 테스트 삭제
        $this->db_master()->where('le_fid', $le_fid);
        $this->db_master()->where('uid', $wm_uid);
        $this->db_master()->delete('wiz_leveltest');

        // 회원정보 수정
        $this->db_master()->set('leveltest_state', 'N');
        $this->db_master()->where('uid', $wm_uid);
        $this->db_master()->update('wiz_member');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    /* 레벨테스트 상세결과 */
    public function row_leveltest_by_le_id($wm_uid, $le_id, $le_fid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT 
                    (SELECT le_id FROM wiz_leveltest WHERE le_id > ? AND le_fid = ?  ORDER BY le_id ASC limit 1) as next_le_id,
                    (SELECT le_id FROM wiz_leveltest WHERE le_id < ? AND le_fid = ?  ORDER BY le_id DESC limit 1) as pre_le_id,
                    wl.*, wt.tu_uid AS wt_tu_uid, wt.tu_name AS wt_tu_name, wt.tu_pic AS wt_tu_pic, wt.tu_pic_main AS wt_tu_pic_main,  tsl.average_total AS tsl_tu_star, 
                    (SELECT mle.description AS mle_description FROM mint_leveltest_evaluation mle WHERE category='listening' AND level=wl.listening) AS student_listening_description,
                    (SELECT mle.description AS mle_description FROM mint_leveltest_evaluation mle WHERE category='pronunciation' AND level=wl.pronunciation) AS student_pronunciation_description,
                    (SELECT mle.description AS mle_description FROM mint_leveltest_evaluation mle WHERE category='speaking' AND level=wl.speaking) AS student_speaking_description,
                    (SELECT mle.description AS mle_description FROM mint_leveltest_evaluation mle WHERE category='vocabulary' AND level=wl.vocabulary) AS student_vocabulary_description,
                    (SELECT mle.description AS mle_description FROM mint_leveltest_evaluation mle WHERE category='grammar' AND level=wl.grammar) AS student_grammar_description
                FROM wiz_leveltest wl 
                INNER JOIN wiz_tutor wt ON wl.tu_uid = wt.tu_uid
                LEFT OUTER JOIN tutor_star_log tsl ON wl.tu_uid = tsl.tu_uid
                WHERE wl.uid = ? AND wl.le_id = ? AND wl.le_fid = ? ";

        $res = $this->db_slave()->query($sql, array($le_id, $le_fid, $le_id, $le_fid, $wm_uid, $le_id, $le_fid));

        // echo $this->db_slave()->last_query();   
        // exit;
        
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    /* 레벨테스트 상세결과 */
    public function row_leveltest_by_sc_id($sc_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT
                wl.le_id AS wl_le_id, wl.le_fid AS wl_le_fid, wl.uid AS wl_uid, wl.wiz_id AS wl_wiz_id, wl.name AS wl_name, wl.tu_uid AS wl_tu_uid, wl.tu_name AS wl_tu_name, 
                wl.mobile AS wl_mobile, wl.sc_id AS wl_sc_id, wl.lesson_gubun AS wl_lesson_gubun, wl.englevel AS wl_englevel, wl.hopeclass AS wl_hopeclass, wl.le_start AS wl_le_start, 
                wl.le_end AS wl_le_end, wl.book_id AS wl_book_id, wl.book_name AS wl_book_name, wl.lev_id AS wl_lev_id, wl.lev_name AS wl_lev_name, wl.lev_gubun AS wl_lev_gubun, wl.repclass AS wl_repclass,
                wl.listening AS wl_listening, wl.speaking AS wl_speaking, wl.pronunciation AS wl_pronunciation, wl.vocabulary AS wl_vocabulary, wl.grammar AS wl_grammar, 
                wl.ev_memo AS wl_ev_memo, wl.le_step AS wl_le_step, wl.consult_ok AS wl_consult_ok, wl.regdate AS wl_regdate, wl.resultdate AS wl_resultdate, wl.hopedate AS wl_hopedate,
                wl.order_number AS wl_order_number, wl.skype AS wl_skype,
                ws.startday AS ws_startday, ws.endday AS ws_endday, ws.lesson_gubun AS ws_lesson_gubun, ws.present AS ws_present,
                wm.wiz_id AS wm_wiz_id, wm.uid AS wm_uid, wm.name AS wm_name, wm.ename AS wm_ename, wm.d_id AS wm_d_id, wm.lev_gubun AS wm_lev_gubun, wm.gender AS wm_gender,
                wm.mobile AS wm_mobile, wm.pmobile AS wm_pmobile, wm.ptel AS wm_ptel, wm.tel AS wm_tel, wm.birth AS wm_birth, wm.jumin1 AS wm_jumin1, wm.jumin_middle AS wm_jumin_middle,
                wd.d_ename AS wd_d_ename
                FROM wiz_leveltest wl 
                LEFT OUTER JOIN wiz_schedule ws ON wl.sc_id = ws.sc_id
                LEFT OUTER JOIN wiz_member wm ON wl.uid = wm.uid
                LEFT OUTER JOIN wiz_dealer wd ON wd.d_id = wm.d_id
                WHERE wl.sc_id =  ?";

        $res = $this->db_slave()->query($sql, array($sc_id));
        // echo $this->db_slave()->last_query();exit;
        
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function list_recomended_course()
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wr.unq AS wr_unq, wr.repclass_kor AS wr_repclass_ko, wr.repclass_eng AS wr_repclass_eng
                FROM wiz_repclass wr
                ORDER BY unq ASC
                ";

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    
    public function list_recomended_level()
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wl.lev_id AS wl_lev_id, wl.lev_gubun AS wl_lev_gubun, wl.lev_name AS wl_lev_name, wl.lev_title AS wl_lev_titls, wl.lev_self AS wl_lev_self,
                wl.lev_result AS wl_lev_result, wl.lev_direct AS wl_lev_direct, wl.modtime AS wl_modtime, wl.lev_sort AS wl_lev_sort
                FROM wiz_level wl ORDER BY lev_name ASC
                ";

        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function list_textbook()
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wb.f_id AS wb_f_id, wb.book_name AS wb_book_name FROM wiz_book wb WHERE book_step = '1' AND useyn = 'y' ORDER BY sort";

        $res = $this->db_slave()->query($sql);
        // echo $this->db_slave()->last_query();exit;
        
        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }
    
    public function list_recomended_textbook($f_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wb.book_id AS wb_book_id, wb.book_name AS wb_book_name FROM wiz_book wb WHERE book_step = '2' AND useyn = 'y' AND f_id = ? ORDER BY sort";

        $res = $this->db_slave()->query($sql, array($f_id));
        // echo $this->db_slave()->last_query();exit;
        
        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    public function row_member_howman_by_uid($uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mh.unq AS mh_unq, mh.uid AS mh_uid, mh.wiz_id AS mh_wiz_id, mh.memo AS mh_memo 
                FROM member_howman mh WHERE uid = ? ORDER BY unq DESC";

        $res = $this->db_slave()->query($sql, array($uid));
        // echo $this->db_slave()->last_query();exit;
        
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function row_wiz_book_by_book_id($book_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wb.book_name AS wb_book_name FROM wiz_book wb WHERE book_id = ?";

        $res = $this->db_slave()->query($sql, array($book_id));
        // echo $this->db_slave()->last_query();exit;
        
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function row_wiz_level_by_lev_id($lev_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT wl.lev_name AS wl_lev_name, wl.lev_gubun AS wl_lev_gubun FROM wiz_level wl WHERE lev_id = ?";

        $res = $this->db_slave()->query($sql, array($lev_id));
        // echo $this->db_slave()->last_query();exit;
        
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function update_leveltest($params, $le_id, $present, $sc_id)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->where('le_id', $le_id);
        $this->db_master()->update('wiz_leveltest', $params);

        $this->db_master()->set('present', $present);
        $this->db_master()->where('sc_id', $sc_id);
        $this->db_master()->update('wiz_schedule');

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    /* 민트 레벨테스트 레벨별 정보 */
    public function row_mint_level_evaluation($params)
    {
        $this->db_connect('slave');
        
        $sql = "(SELECT mle.category AS mle_category, mle.level AS mle_level, mle.description AS mle_description 
                FROM mint_leveltest_evaluation mle WHERE category='listening' AND level='{$params['listening_level']}') UNION
                (SELECT mle.category AS mle_category, mle.level AS mle_level, mle.description AS mle_description 
                FROM mint_leveltest_evaluation mle WHERE category='speaking' AND level='{$params['speaking_level']}') UNION
                (SELECT mle.category AS mle_category, mle.level AS mle_level, mle.description AS mle_description 
                FROM mint_leveltest_evaluation mle WHERE category='pronunciation' AND level='{$params['pronunciation_level']}') UNION
                (SELECT mle.category AS mle_category, mle.level AS mle_level, mle.description AS mle_description 
                FROM mint_leveltest_evaluation mle WHERE category='vocabulary' AND level='{$params['vocabulary_level']}') UNION
                (SELECT mle.category AS mle_category, mle.level AS mle_level, mle.description AS mle_description 
                FROM mint_leveltest_evaluation mle WHERE category='grammar' AND level='{$params['grammar_level']}')";

        $res = $this->db_slave()->query($sql);
        
        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ?  $res->result_array() : NULL;
    }

    /* 교재 추천레벨 */
    public function row_curriculum_by_book_id($book_id)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mc.recommend_level, mc.table_code, mc.image
        FROM mint_curriculum mc
        LEFT JOIN wiz_book wb ON mc.mc_key = wb.mc_key
        WHERE wb.book_id = ?";

        $res = $this->db_slave()->query($sql, array($book_id));

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    

    /* 레벨테스트 평균데이터 */
    public function row_leveltest_user_avergae($le_start)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT fs.pro_avg AS avg_pronunciation , fs.voc_avg AS avg_vocabulary, fs.ss_avg AS avg_speaking, fs.ls_avg AS avg_listening, fs.cg_avg AS avg_grammar
                FROM feedback_stat fs
                WHERE stat_date = '".date("Y-m-d", strtotime($le_start." -1 day"))."' AND type ='leveltest'";

        $res = $this->db_slave()->query($sql, array());

        // echo $this->db_slave()->last_query();   
        // exit;
        
        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    /* 진행중인 레벨테스트 있는지 체크 */
    public function chcked_progress_leveltest($uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(le_id) AS cnt FROM wiz_leveltest 
                WHERE uid = ? AND le_step = 1  AND le_end > now() 
                ";

        $res = $this->db_slave()->query($sql, array($uid));
        
        // echo $this->db_slave()->last_query();   
        // exit;

        return $res->num_rows() > 0 ?  $res->row_array() : NULL;
    }

    public function check_relay_leveltest($le_fid,$time_type)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT idx FROM wiz_leveltest_resultatk WHERE le_fid=".$le_fid." AND time_type='".$time_type."' LIMIT 1";

        $res = $this->db_slave()->query($sql);
        //echo $this->db_slave()->last_query();  exit;
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function list_leveltest_by_le_fid($le_fid)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT le_start FROM wiz_leveltest WHERE le_fid= ? ORDER BY le_id ASC";

        $res = $this->db_slave()->query($sql, array($le_fid));
        //echo $this->db_slave()->last_query();  exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    
    public function list_leveltest_before_start($date,$time)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT wl.*,substr(hopetime,1,5) as lv_time 
                FROM wiz_leveltest as wl
                LEFT JOIN wiz_member as wm ON wl.uid=wm.uid 
                WHERE wl.hopedate='".$date."' AND wl.hopetime BETWEEN '".$time.":00' AND '".$time.":59' AND wl.le_step='1' AND wm.d_id NOT IN ('96','118','190')
                ORDER BY wl.wiz_id,wl.le_id ASC";

        $res = $this->db_slave()->query($sql);
        //echo $this->db_slave()->last_query();  exit;
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function insert_wiz_leveltest_resultatk($param)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->insert('wiz_leveltest_resultatk',$param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }


    /**
     * 가장 최근 레벨테스트 정보 조회
     * 레벨테스트가 완료된 정보에서 레벨정보만 추출
     */
    public function get_leveltest_new_info($uid)
    {
        $this->db_connect('slave');

        $sql = "SELECT wl.lev_name as wl_lev_name
                FROM wiz_leveltest as wl 
                WHERE wl.uid = '".$uid."' AND wl.le_step = '3'
                ORDER BY wl.le_id DESC LIMIT 1";
        $res = $this->db_slave()->query($sql);
        
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    //5월 10일 이후 레벨테스트 받은 기록있는지 확인, 해당 강사가 성과급(piece Rate) pay_type=d 여야한다.
    public function get_member_did_leveltest_paytype_d($uid)
    {
        $this->db_connect('slave');
    
        $sql = "SELECT wt.tu_uid, wt.tu_id, wt.tu_name, wlt.le_id
                FROM wiz_leveltest as wlt 
                JOIN wiz_tutor as wt ON wlt.tu_uid=wt.tu_uid
                WHERE wlt.uid= ? AND wlt.le_step=3 AND wlt.le_start >= '2021-05-10 00:00:00' AND wt.pay_type='d' 
                GROUP BY tu_uid";

        $res = $this->db_slave()->query($sql, array($uid));
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

}










