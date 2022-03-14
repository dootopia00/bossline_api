<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'models/_Base_Model.php';

class Quest_mdl extends _Base_Model {

    public $quest_log_name = '';

	public function __construct()
	{
		parent::__construct();

    }

    /*
        q_idx 가 넘어오면 업적형퀘스트 이며, 업적형퀘스트는 로그가 업적별로 나뉘어져있다.
        업적 상위퀘스트번호로 로그를 구분하며, 일반퀘스트는 mint_quest_progress_log에 전부 들어간다
    */
    public function set_quest_log_table_name($q_idx)
    {
        if(!$q_idx) $this->quest_log_name = 'db_quest_log.mint_quest_progress_log';
        else $this->quest_log_name = 'db_quest_log.mint_quest_progress_log_'.$q_idx;
    }
    
    // 퀘스트 정보
    public function row_quest_progress_info($q_idx, $wm_uid, $select_col_content=' ')
    {
        //배치에서 슬레이브로만 루프 돌시 커넥션 끊길수있어서 중간에 마스터체크 넣어주기 위해, 해당쿼리는 마스터로 날린다.
        $this->db_connect('master');
        
        $sql = "SELECT mq.q_idx as mq_q_idx, mq.depth as mq_depth, mq.parent_q_idx as mq_parent_q_idx, mq.type as mq_type, mq.title as mq_title, mq.try as mq_try, 
                    mq.reward as mq_reward, mq.reward_desc as mq_reward_desc, mq.next_q_idx as mq_next_q_idx, mq.is_use as mq_is_use,
                    mq.tropy_on as mq_tropy_on, mq.tropy_off as mq_tropy_off, mq.tropy_get_yn as mq_tropy_get_yn, mq.description as mq_description,
                    mq.description_add as mq_description_add,
                    mqp.progress as mqp_progress, mqp.start_date as mqp_start_date, mqp.complete_date as mqp_complete_date, mqp.reward_date as mqp_reward_date
                    ".$select_col_content."
                FROM mint_quest as mq 
                LEFT JOIN mint_quest_progress as mqp ON mqp.q_idx=mq.q_idx AND mqp.uid = ?
                WHERE mq.q_idx = ?";

        $res = $this->db_master()->query($sql, array($wm_uid, $q_idx));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    // 부모 퀘스트 정보. 시작일은 하위퀘스트의 시작일에서 가져온다
    public function row_parent_quest_info($q_idx, $wm_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mq.q_idx as mq_q_idx, mq.depth as mq_depth, mq.parent_q_idx as mq_parent_q_idx, mq.type as mq_type, mq.title as mq_title, mq.try as mq_try, 
                    mq.reward as mq_reward, mq.reward_desc as mq_reward_desc, mq.next_q_idx as mq_next_q_idx, 
                    (
                        SELECT start_date 
                        FROM mint_quest as sub_mq 
                        JOIN mint_quest_progress as sub_mqp ON sub_mq.q_idx=sub_mqp.q_idx 
                        WHERE sub_mq.parent_q_idx = ? AND sub_mqp.uid = ? 
                        ORDER BY p_idx ASC LIMIT 1
                    ) as mqp_start_date
                FROM mint_quest as mq 
                WHERE mq.q_idx = ?";

        $res = $this->db_slave()->query($sql, array($q_idx, $wm_uid, $q_idx));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 해금이 필요한지, 필요하다면 해금되었는지
    public function checked_quest_release($q_idx, $wm_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mql.precede_q_idx as mql_precede_q_idx
                FROM mint_quest_release as mql
                LEFT JOIN mint_quest_release_member as mqlm ON mql.lock_q_idx=mqlm.lock_q_idx AND mql.precede_q_idx=mqlm.precede_q_idx AND mqlm.uid = ?
                WHERE mql.lock_q_idx = ? AND mqlm.rm_idx IS NULL";

        $res = $this->db_slave()->query($sql, array($wm_uid, $q_idx));
        //echo $this->db_slave()->last_query();   
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    // 선행퀘로 등록되어있으나 해당유저에게 해금되지 않은 목록 찾기
    public function find_precede_quest_list($q_idx, $wm_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mql.precede_q_idx as mql_precede_q_idx, mql.lock_q_idx as mql_lock_q_idx, mqlm.regdate as mqlm_regdate 
                FROM mint_quest_release as mql
                LEFT JOIN mint_quest_release_member as mqlm ON mql.precede_q_idx=mqlm.precede_q_idx AND mqlm.uid = ?
                WHERE mql.precede_q_idx = ? AND mqlm.rm_idx IS NULL";

        $res = $this->db_slave()->query($sql, array($wm_uid, $q_idx));
        
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    // 하위퀘스트 진행률 insert 및 update
    public function upsert_quest_progress($q_idx, $wm_uid, $quest_info, $code='', $quest_complete=false)
    {
        $this->db_connect('master');

        $now = date('Y-m-d H:i:s');
        $request_url = array_key_exists('REQUEST_URI',$_SERVER) ? $_SERVER['REQUEST_URI']:$_SERVER['argv'][5]; // 어디로 접근했는지기록. 배치:argv[5], 웹접속:$_SERVER['REQUEST_URI']

        $this->db_master()->trans_start();

        // 진행률 로그
        $this->db_master()->insert($this->quest_log_name,[
            'log_type'      => 1,       // 1:진행률,2:퀘완료,3:보상지급,4:퀘해금,5:진행률차감
            'q_idx'         => $q_idx,
            'uid'           => $wm_uid,
            'request_url'   => $request_url ? $request_url:'',
            'code'          => $code,   // 게시물,시험 등의 pk키. 진행률이 증가하게 된 원인의 pk키
            'regdate'       => $now,
        ]);
        
        // mqp_start_date 있으면 초기값있으므로 업뎃
        if($quest_info['mqp_start_date'])
        {
            // 퀘스트 진행해야하는 횟수와 진행상태가 같아지면 완료일도 업뎃
            if($quest_complete)
            {
                $this->db_master()->set('complete_date',$now);
            }

            $this->db_master()->where('q_idx',$q_idx);
            $this->db_master()->where('uid',$wm_uid);

            //진행률 증가
            $this->db_master()->set('progress','progress+1', false);

            $this->db_master()->update('mint_quest_progress');
        }
        else
        {
            // progress 1 로 초기값넣어준다
            $this->db_master()->insert('mint_quest_progress',[
                'q_idx'         => $q_idx,
                'uid'           => $wm_uid,
                'progress'      => 1,
                'start_date'    => $now,
                'complete_date' => $quest_complete ? $now:null,
            ]);
        }

        //퀘완료시 추가 처리
        if($quest_complete)
        {
            // 퀘완료 로그
            $this->db_master()->insert($this->quest_log_name,[
                'log_type'      => 2,       // 1:진행률,2:퀘완료,3:보상지급,4:퀘해금,5:진행률차감
                'q_idx'         => $q_idx,
                'uid'           => $wm_uid,
                'request_url'   => $request_url ? $request_url:'',
                'code'          => $code,   // 게시물,시험 등의 pk키. 진행률이 증가하게 된 원인의 pk키
                'regdate'       => $now,
            ]);

            // 업적(누적)형 연계퀘스트는 완료 시 다음 퀘 초기 세팅해준다
            // EX) 10 -> 20 -> 30 회의 누적퀘의 경우 10회가 완료되면 20회까지 10회만 더 하면되므로 다음퀘의 진행횟수에 직전퀘의 해야되는횟수(mq_try)를 넣어준다
            // 만약 0을 넣을 시 누적된 로우 진행횟수 총합으로 현재퀘 진행상태를 체크해야하므로..
            if($quest_info['mq_next_q_idx'] && $quest_info['mq_type'] =='2')
            {
                $this->db_master()->insert('mint_quest_progress',[
                    'q_idx'      => $quest_info['mq_next_q_idx'],
                    'uid'        => $wm_uid,
                    'progress'   => $quest_info['mq_try'],
                    'start_date' => $now,
                ]);
            }

        }
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    //현재는 부모퀘 완료 처리시에만 사용
    public function insert_quest_progress($param, $log_type, $code='')
    {
        $this->db_connect('master');

        $request_url = array_key_exists('REQUEST_URI',$_SERVER) ? $_SERVER['REQUEST_URI']:$_SERVER['argv'][5]; // 어디로 접근했는지기록. 배치:argv[5], 웹접속:$_SERVER['REQUEST_URI']
        
        $this->db_master()->trans_start();

        // 부모퀘 완료 로그
        $this->db_master()->insert($this->quest_log_name,[
            'log_type'      => $log_type,       // 1:진행률,2:퀘완료,3:보상지급,4:퀘해금,5:진행률차감
            'q_idx'         => $param['q_idx'],
            'uid'           => $param['uid'],
            'request_url'   => $request_url ? $request_url:'',
            'code'          => $code ? $code:'',   // 게시물,시험 등의 pk키. 진행률이 증가하게 된 원인의 pk키
            'regdate'       => date('Y-m-d H:i:s'),
        ]);
        
        $this->db_master()->insert('mint_quest_progress',$param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }
    
    public function insert_quest_release_member($quest_list, $wm_uid)
    {
        $this->db_connect('master');

        $now = date('Y-m-d H:i:s');

        $this->db_master()->trans_start();

        foreach($quest_list as $row)
        {
            // 진행률 로그
            $this->db_master()->insert($this->quest_log_name,[
                'log_type'      => 4,       // 1:진행률,2:퀘완료,3:보상지급,4:퀘해금,5:진행률차감
                'q_idx'         => $row['mql_lock_q_idx'],      // 해금된 퀘
                'code'          => $row['mql_precede_q_idx'],   // 해금된 퀘의 선행퀘
                'uid'           => $wm_uid,
                'regdate'       => $now,
            ]);

            // 유저별 퀘스트별 해금테이블에 insert
            $this->db_master()->insert('mint_quest_release_member',[
                'precede_q_idx' => $row['mql_precede_q_idx'],
                'lock_q_idx'    => $row['mql_lock_q_idx'],
                'uid'           => $wm_uid,
                'regdate'       => $now,
            ]);
        }
        
        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    public function update_quest_progress($set_param, $where_param, $log_type, $code='')
    {
        $this->db_connect('master');

        $request_url = array_key_exists('REQUEST_URI',$_SERVER) ? $_SERVER['REQUEST_URI']:$_SERVER['argv'][5]; // 어디로 접근했는지기록. 배치:argv[5], 웹접속:$_SERVER['REQUEST_URI']

        $this->db_master()->trans_start();

        // 보상지급 완료 로그
        $this->db_master()->insert($this->quest_log_name,[
            'log_type'      => $log_type,       // 1:진행률,2:퀘완료,3:보상지급,4:퀘해금,5:진행률차감
            'q_idx'         => $where_param['q_idx'],
            'uid'           => $where_param['uid'],
            'request_url'   => $request_url ? $request_url:'',
            'code'          => $code ? $code:'',   // 게시물,시험 등의 pk키. 진행률이 증가하게 된 원인의 pk키
            'regdate'       => date('Y-m-d H:i:s'),
        ]);
        
        $this->db_master()->where($where_param);

        $this->db_master()->update('mint_quest_progress', $set_param);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    
    //트로피 지급
    public function insert_mint_quest_user_tropy($param)
    {
        $this->db_connect('master');
        
        $this->db_master()->trans_start();

        $sql = "SELECT 1 FROM mint_quest_user_tropy WHERE uid = ? AND q_idx= ?";
        $res = $this->db_master()->query($sql,array($param['uid'], $param['highest_q_idx']));
        
        if($res->num_rows() == 0)
        {
            $this->db_master()->insert('mint_quest_user_tropy',[
                'q_idx'     => $param['highest_q_idx'],
                'uid'       => $param['uid'],
                'regdate'   => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    // 하위퀘스트 전부 완료되었는지
    public function checked_subquest_complete_all($parent_q_idx, $wm_uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(*) as cnt
                FROM mint_quest as mq
                LEFT JOIN mint_quest_progress as mqp ON mq.q_idx=mqp.q_idx AND mqp.uid = ? 
                WHERE mq.parent_q_idx = ? AND mqp.complete_date IS NULL AND mq.is_use=1";

        $res = $this->db_slave()->query($sql, array($wm_uid, $parent_q_idx));
        //echo $this->db_slave()->last_query();   
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    
    // 부모퀘스트 번호로 하위 퀘스트 번호 찾기
    public function find_child_q_idx_by_parent_q_idx($mq_parent_q_idx)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT group_concat(q_idx) as q_idx FROM mint_quest WHERE parent_q_idx = ? AND is_use=1";

        $res = $this->db_slave()->query($sql, array($mq_parent_q_idx));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }
    
    // 퀘스트 로그
    public function checked_quest_log_by_code($wm_uid, $q_idx, $code)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(*) as cnt
                FROM ".$this->quest_log_name." as mqpl
                WHERE mqpl.uid = ? AND mqpl.q_idx IN ? AND mqpl.code = ? AND mqpl.log_type=1";

        $res = $this->db_slave()->query($sql, array($wm_uid, $q_idx, $code));

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 퀘스트 로그테이블 create 스키마 조회
    public function schema_quest_log_table()
    {
        $this->db_connect('slave');
        
        $sql = "SHOW CREATE TABLE db_quest_log.mint_quest_progress_log";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    // 퀘스트 업적형 상위퀘스트 전부 조회
    public function list_type2_depth1_quest()
    {
        $this->db_connect('slave');
        
        $sql = "SELECT * FROM mint_quest WHERE type=2 AND depth=1";

        $res = $this->db_slave()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    // 퀘스트 로그테이블 추가 생성
    public function create_quest_log_table($query)
    {
        $this->db_connect('master');

        $this->db_master()->trans_start();

        $this->db_master()->query($query);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }
        
        return 1;
    }

    // 퀘스트 리스트
    public function quest_list($type, $uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mq.q_idx as mq_q_idx, mq.title as mq_title, mq.description as mq_description, mq.tropy_on as mq_tropy_on, mq.tropy_off as mq_tropy_off,
                mqp.complete_date as mqp_complete_date, mqut.ut_idx as mqut_ut_idx,
                (SELECT sum(1) FROM mint_quest as sub_mq WHERE sub_mq.parent_q_idx=mq.q_idx AND sub_mq.is_use=1) as total_subquest_cnt
                FROM mint_quest as mq 
                LEFT JOIN mint_quest_progress as mqp ON mq.q_idx=mqp.q_idx AND mqp.uid = ?
                LEFT JOIN mint_quest_user_tropy as mqut ON mqut.q_idx=mq.q_idx AND mqut.uid = ?
                WHERE mq.type = ? AND mq.depth= 1 AND mq.is_use=1 ORDER BY mq.q_idx ASC";

        $res = $this->db_slave()->query($sql, array($uid,$uid,$type));
        //echo $this->db_slave()->last_query(); 
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    public function subquest_complete_count($q_idx, $uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT count(*) as cnt
                FROM mint_quest_progress as mqp
                WHERE mqp.uid = ? AND mqp.q_idx IN (SELECT q_idx FROM mint_quest WHERE parent_q_idx = ? AND is_use=1) AND mqp.complete_date !=''";

        $res = $this->db_slave()->query($sql, array($uid, $q_idx));
        //echo $this->db_slave()->last_query();  
        return $res->num_rows() > 0 ? $res->row_array() : NULL;
    }

    
    public function subquest_list($q_idx, $uid)
    {
        $this->db_connect('slave');
        
        $sql = "SELECT mq.q_idx as mq_q_idx, mq.title as mq_title, mq.title_front as mq_title_front, mq.description as mq_description, mq.tropy_on as mq_tropy_on, mq.tropy_off as mq_tropy_off,
                mq.try as mq_try, mq.reward as mq_reward, mq.location as mq_location, mq.location_m as mq_location_m, 
                mqp.progress as mqp_progress, mqp.complete_date as mqp_complete_date, mqp.reward_date as mqp_reward_date, mqp.start_date as mqp_start_date
                FROM mint_quest as mq 
                LEFT JOIN mint_quest_progress as mqp ON mq.q_idx=mqp.q_idx AND mqp.uid = ?
                WHERE mq.parent_q_idx = ? AND mq.depth= 2 AND mq.is_use=1 ORDER BY mq.q_idx ASC";

        $res = $this->db_slave()->query($sql, array($uid, $q_idx));

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    //횟수차감
    public function decrement_quest_progress($where, $complete_date_reset, $code)
    {
        $this->db_connect('master');

        $request_url = array_key_exists('REQUEST_URI',$_SERVER) ? $_SERVER['REQUEST_URI']:$_SERVER['argv'][5]; // 어디로 접근했는지기록. 배치:argv[5], 웹접속:$_SERVER['REQUEST_URI']
        
        $this->db_master()->trans_start();
        
        $this->db_master()->where($where);
        $this->db_master()->set('progress','progress-1',false);
        if($complete_date_reset) $this->db_master()->set('complete_date',null);

        $this->db_master()->update('mint_quest_progress');

        // 차감로그
        $this->db_master()->insert($this->quest_log_name,[
            'log_type'      => 5,       // 1:진행률,2:퀘완료,3:보상지급,4:퀘해금,5:진행률차감
            'q_idx'         => $where['q_idx'],
            'uid'           => $where['uid'],
            'request_url'   => $request_url ? $request_url:'',
            'code'          => $code ? $code:'',   // 게시물,시험 등의 pk키. 진행률이 증가하게 된 원인의 pk키
            'regdate'       => date('Y-m-d H:i:s'),
        ]);

        $this->db_master()->trans_complete();

        if ($this->db_master()->trans_status() === FALSE)
        {
            return -1;
        }

        return 1;
    }

    //배치용--------------------------------------------------------------------START

    
    public function set_global_time_out()
    {
        $this->db_connect('master');
        
        $sql = "SET GLOBAL wait_timeout=300;";

        $this->db_master()->query($sql);
    }

    public function list_quest_for_batch()
    {
        $this->db_connect('master');
        
        $sql = "SELECT q_idx,parent_q_idx,title,try FROM mint_quest WHERE type=2 AND parent_q_idx > 0 ORDER BY parent_q_idx,try ASC";

        $res = $this->db_master()->query($sql);
        //echo $this->db_slave()->last_query();   
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    //배치용
    public function list_mset_groupby_uid_quest()
    {
        $this->db_connect('master');
        
        $sql = "SELECT uid, count(*) as cnt, group_concat(idx) as code
                FROM mint_mset_report
                WHERE status = 2 group by uid";

        $res = $this->db_master()->query($sql);
        //echo $this->db_slave()->last_query();   
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }


    //배치용
    public function insert_quest_progress_batch($param)
    {
        $this->db_connect('master');

        $sql = "SELECT 1 FROM mint_quest_progress WHERE q_idx = ? AND uid = ?";

        $res = $this->db_master()->query($sql,array($param['q_idx'], $param['uid']));

        if($res->num_rows() == 0 )
        {
            $this->db_master()->insert('mint_quest_progress',$param);
        }
        
        return 1;
    }

    
    //배치용
    public function insert_quest_progress_log_batch($param)
    {
        $this->db_connect('master');

        $sql = "SELECT 1 FROM ".$this->quest_log_name." WHERE q_idx = ? AND uid = ? AND code = ? AND log_type = ?";

        $res = $this->db_master()->query($sql,array($param['q_idx'], $param['uid'], $param['code'], $param['log_type']));

        if($res->num_rows() == 0 )
        {
            $this->db_master()->insert($this->quest_log_name,$param);
        }
        
        return 1;
    }


    //배치용
    public function list_dictation_groupby_uid_quest()
    {
        $this->db_connect('master');
        
        $sql = "SELECT uid, count(*) as cnt, group_concat(c_uid) as code
                FROM mint_cafeboard
                group by uid";

        $res = $this->db_master()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    //배치용
    public function list_thunder_groupby_uid_quest()
    {
        $this->db_connect('master');
        
        $sql = "SELECT ws.uid, count(*) as cnt, group_concat(ws.sc_id) as code
                FROM wiz_schedule as ws
                JOIN wiz_lesson as wl ON wl.lesson_id=ws.lesson_id
                WHERE ws.present=2 AND ws.kind = 'c'
                group by ws.uid";

        $res = $this->db_master()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    //배치용
    public function list_correction_groupby_uid_quest()
    {
        $this->db_connect('master');
        
        $sql = "SELECT uid, count(*) as cnt, group_concat(w_id) as code
                FROM wiz_correct
                group by uid";

        $res = $this->db_master()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    //배치용
    public function list_script_groupby_uid_quest()
    {
        $this->db_connect('master');
        
        $sql = "SELECT wm.uid, count(*) as cnt, group_concat(mb.mb_unq) as code
                FROM mint_boards as mb
                JOIN wiz_member as wm ON mb.wiz_id=wm.wiz_id
                WHERE mb.table_code=1130
                group by mb.wiz_id";

        $res = $this->db_master()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    //배치용
    public function list_member_groupby_uid_quest()
    {
        $this->db_connect('master');
        
        $sql = "SELECT uid, attendance as cnt
                FROM wiz_member where attendance > 0";

        $res = $this->db_master()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    //배치용
    public function list_board_write_groupby_uid_quest()
    {
        $this->db_connect('master');
        
        //이런표현어떻게? 영어해석커뮤니티, 영어문법&질문, 유용한영어표현, 스터디모집방, 스터디활동방, 선생님자리났어요, 학부모수다방, 영어고민&권태기상담, 주니어모임방, 민트사용노하우 집계
        $sql = "SELECT uid,sum(cnt) as cnt, group_concat(code) as code FROM 
                (
                    SELECT wm.uid, count(*) as cnt, group_concat(mb.mb_unq,'_mb') as code
                    FROM mint_boards as mb
                    JOIN wiz_member as wm ON mb.wiz_id=wm.wiz_id
                    WHERE mb.table_code IN(1102,1120,1128,1125,1126,1388,1383,1337,1353,1350)
                    group by mb.wiz_id

                    UNION ALL

                    SELECT wm.uid, COUNT(*) as cnt, group_concat(me.uid,'_me') as code
                    FROM mint_express AS me
                    JOIN wiz_member as wm ON me.wiz_id=wm.wiz_id
                    group by me.wiz_id
                ) AS tmp GROUP BY uid";

        $res = $this->db_master()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    //배치용
    public function list_board_reply_groupby_uid_quest()
    {
        $this->db_connect('master');
        
        //사용자게시판 전체
        $sql = "SELECT uid,sum(cnt) as cnt, group_concat(code) as code FROM 
                (
                    SELECT wm.uid, count(*) as cnt, group_concat(mbc.co_unq,'_mbc') as code
                    FROM mint_boards_comment as mbc
                    JOIN wiz_member as wm ON mbc.writer_id=wm.wiz_id
                    WHERE (mbc.table_code BETWEEN 1100 AND 1199 OR mbc.table_code BETWEEN 1300 AND 1399) 
                    group by mbc.writer_id

                    UNION ALL

                    SELECT wm.uid, COUNT(*) as cnt, group_concat(mec.uid,'_mec') as code
                    FROM mint_express_com AS mec
                    JOIN wiz_member as wm ON mec.wiz_id=wm.wiz_id
                    group by mec.wiz_id

                    UNION ALL
                    
                    SELECT wm.uid, COUNT(*) as cnt, group_concat(mcc.unq,'_mcc') as code
                    FROM mint_cafeboard_com AS mcc
                    JOIN wiz_member as wm ON mcc.writer_id=wm.wiz_id
                    group by mcc.writer_id

                ) AS tmp GROUP BY uid";

        $res = $this->db_master()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
    
    //배치용
    public function list_done_class_tutor_groupby_uid_quest()
    {
        $this->db_connect('master');
        
        $sql = "SELECT uid,COUNT(*) AS cnt, group_concat(tu_uid) as code FROM 
                (
                    SELECT uid, tu_uid
                    FROM wiz_schedule
                    WHERE present=2 AND startday <= NOW() AND lesson_id NOT IN (100000000,100000001)
                    group by uid, tu_uid
                ) AS tmp GROUP BY uid";

        $res = $this->db_master()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    //배치용
    public function list_review_write_groupby_uid_quest()
    {
        $this->db_connect('master');
        
        $sql = "SELECT wm.uid, count(*) as cnt, group_concat(mb.mb_unq) as code
                FROM mint_boards as mb
                JOIN wiz_member as wm ON mb.wiz_id=wm.wiz_id
                WHERE mb.table_code=1111
                group by mb.wiz_id";

        $res = $this->db_master()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    
    //배치용
    public function list_dictation_anwser_groupby_uid_quest()
    {
        $this->db_connect('master');
        
        $sql = "SELECT wm.uid, count(*) as cnt, group_concat(mb.mb_unq) as code
                FROM mint_boards as mb
                JOIN wiz_member as wm ON mb.wiz_id=wm.wiz_id
                WHERE mb.table_code=1138 AND mb.parent_key > 0
                group by mb.wiz_id";

        $res = $this->db_master()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    //배치용
    public function list_class_attandance_groupby_uid_quest()
    {
        $this->db_connect('master');
        
        $sql = "SELECT uid,COUNT(*) AS cnt, group_concat(startday) as code FROM 
                (
                    SELECT uid, DATE_FORMAT(startday, '%Y-%m-%d') as startday
                    FROM wiz_schedule
                    WHERE present=2 AND startday <= NOW() AND lesson_id NOT IN (100000000,100000001)
                    group by uid, DATE_FORMAT(startday, '%Y-%m-%d')
                ) AS tmp GROUP BY uid";

        $res = $this->db_master()->query($sql);

        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }

    //배치용--------------------------------------------------------------------END

    // 보상을 받지않은 완료 퀘스트 목록
    public function get_complete_quest_list($wm_uid, $limit)
    {
        $this->db_connect('slave');

        $this->db_slave()->trans_start();

        $sql = "SELECT mq.q_idx as mq_q_idx, mq.parent_q_idx as mq_parent_q_idx, mq.title as mq_title, mq.type as mq_type,
                       mqp.progress as mqp_progress, mq.try as mq_try, mq.reward as mq_reward
                FROM mint_quest_progress as mqp
                LEFT JOIN mint_quest as mq ON mq.q_idx = mqp.q_idx
                WHERE mqp.uid = ? AND mqp.complete_date IS NOT NULL AND mqp.reward_date IS NULL AND mq.depth = 2
                ORDER BY FIELD(mqp.q_idx, '4') DESC, mqp.p_idx DESC LIMIT 0,".$limit;

        $res = $this->db_slave()->query($sql, array($wm_uid));  
    
        return $res->num_rows() > 0 ? $res->result_array() : NULL;
    }
}










