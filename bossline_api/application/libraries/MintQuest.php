<?php

class MintQuest
{
    public static $instance = null;

    private $wiz_member = null;      // 회원정보
    private $q_idx = null;           // 체크해야 할 퀘스트 번호. 
    private $quest_info = null;      // 체크해야 할 퀘스트 정보 
    private $code = null;            // 로그에 저장할 code 
    private $parent_q_idx = null;    // 최상위퀘스트 q_idx. 로그쌓을 테이블명칭을 설정하는데 사용
    private $is_batch = false;       // 누적데이터 쌓기용 배치인지 여부

    public static function getInstance($uid='')
    {
        if(self::$instance == null)
        {
            self::$instance = new MintQuest($uid);
        }

        return self::$instance;
    }

    /**
     * 민트 퀘스트 & 업적
     * 
     * mint_quest                : 민트퀘스트, 업적
     * mint_quest_progress       : 유저 별 퀘스트 별 진행현황
     * mint_quest_progress_log   : 퀘스트 진행 로그
     * mint_quest_release        : 퀘스트 해금 테이블
     * mint_quest_release_member : 회원에게 퀘스트 해금됐는지 저장하는 테이블
     * mint_quest_user_tropy          : 퀘스트 트로피 획득 정보
     * 
     * 1. 현재진행중인 장을 클리어, 보상받기를 해야 다음장의 퀘스트를 진행가능하다.
     *  ㄴ 진행 중 다음 장의 퀘스트 내용을 진행하더라도 무시된다
     *  ㄴ 진행 중인 장 내에서는 순서상관없이 진행가능하다.
     * 2. 보상은 메인, 서브 보상 두 종류가 존재한다. (1뎁스, 2뎁스 보상)
     * 3. 업적은 퀘스트와 동시 진행된다.
     * 4. 업적카운트는 보상을 받지않아도 달성되면 다음 연계 업적 존재할 시 체크해준다. (그래서 연속 보상받기가 가능)
     * 5. 시작은 오픈일을 기점으로 전체회원이 대상이다
     *   ㄴ 오픈 이전에 행한것은 레벨테스트와 같이 1회만 할수있는 특수한 사항을 제외하고는 전부 무시되어 처음부터 시작된다.
     *   ㄴ 다만, 업적의 경우 오픈 이전에 행한것도 인정되므로 배치돌려 데이터를 넣어줘야한다.
     * 6. 퀘스트, 업적으로 획득 한 트로피에 따라 특수효과 있을수 있다. -> 210406 기준 효과 없음
     * 
     * 1뎁스 1장, 2장... (총 보상있음)
     * 2뎁스 세부 퀘스트 (개별 보상있음)
     * 
     * request_batch_quest 는 속도를 위해 넣은것일뿐 정상적인 퀘스트 처리 진입은 do_quest, reward 두개만 한다.
     * 
     */

    /**
     * 퀘스트 처리를 배치로 요청한다.
     * @param $q_idx 퀘스트번호. 밑줄(_)로 구분하여 멀티요청. 누적형일경우 첫번째 하위퀘스트 번호로 요청하면된다
     * @param $wm_uid 회원 UID
     * @param $code 호출하게된 원인. 글쓰기 시 pk, mset의 pk, 뷰페이지의 pk 등등. 글쓰기,답변 횟수 퀘스트 같은경우 여러테이블을 집계하므로 pk가 중복될수 있어서 뒤에 문자열 붙여서 구분
     */ 
    public static function request_batch_quest($q_idx, $code='', $wm_uid=null )
    {
        //지금은 닫아놓음
        //return;
        /*
            퀘스트 진행 비동기처리
        */

        if(!$wm_uid)
        {
            $wiz_member = base_get_wiz_member();
            if($wiz_member) $wm_uid = $wiz_member['wm_uid'];
        }

        if(!$wm_uid || !$q_idx) return;
        
        $REQUEST_URI = $_SERVER['REQUEST_URI'] ? str_replace('/index.php/','',$_SERVER['REQUEST_URI']):'';
        $REQUEST_URI = str_replace('/','__',$REQUEST_URI);
        $code = $code == '' ? 'null':$code; //-배치로 공백을 넘기면 인식이 제대로 안되서 문자열 null 을 기본값으로 넣었다

        $command = "php -f ".$_SERVER['DOCUMENT_ROOT']."/index.php _batch/request_batch_quest '".$q_idx."' '".$wm_uid."' '".$code."' '".($REQUEST_URI)."' > /dev/null 2>/dev/null &";
        //log_message('error', 'test :'.$command);
        exec($command);
    }

    
    /**
     * 퀘스트 차감 처리를 배치로 요청한다. 요청형태는 퀘스트 처리와 같다
     * @param $q_idx 퀘스트번호. 밑줄(_)로 구분하여 멀티요청. 누적형일경우 첫번째 하위퀘스트 번호로 요청하면된다
     * @param $wm_uid 회원 UID
     * @param $code 호출하게된 원인. 글쓰기 시 pk, mset의 pk, 뷰페이지의 pk 등등. 글쓰기,답변 횟수 퀘스트 같은경우 여러테이블을 집계하므로 pk가 중복될수 있어서 뒤에 문자열 붙여서 구분
     */ 
    public static function request_batch_quest_decrement($q_idx, $code='', $wm_uid=null )
    {
        //지금은 닫아놓음
        //return;
        /*
            퀘스트 진행 비동기처리
        */

        if(!$wm_uid)
        {
            $wiz_member = base_get_wiz_member();
            if($wiz_member) $wm_uid = $wiz_member['wm_uid'];
        }

        if(!$wm_uid || !$q_idx) return;
        
        $REQUEST_URI = $_SERVER['REQUEST_URI'] ? str_replace('/index.php/','',$_SERVER['REQUEST_URI']):'';
        $REQUEST_URI = str_replace('/','__',$REQUEST_URI);
        $code = $code == '' ? 'null':$code; //-배치로 공백을 넘기면 인식이 제대로 안되서 문자열 null 을 기본값으로 넣었다

        $command = "php -f ".$_SERVER['DOCUMENT_ROOT']."/index.php _batch/request_batch_quest_decrement '".$q_idx."' '".$wm_uid."' '".$code."' '".($REQUEST_URI)."' > /dev/null 2>/dev/null &";
        //log_message('error', 'test :'.$command);
        exec($command);
    }
    
    //보통 배치로 들어오기때문에 base_get_wiz_member에 값이 없다.
    public function __construct($uid='')
    {
        if(!$uid) return;
        
        $CI =& get_instance();
        $CI->load->model('quest_mdl');
        $CI->load->model('member_mdl');
        $CI->load->model('lesson_mdl');
        //회원정보
        $this->wiz_member = $CI->member_mdl->get_wiz_member_by_wm_uid($uid);
    }

    //현재는 누적데이터 쌓는 배치에만 사용. 싱글톤유지하면서 퀘스트 하는 회원의 정보 바뀔때 호출
    public function set_wiz_member($uid='')
    {
        if(!$uid) return;

        $CI =& get_instance();
        //회원정보
        $this->wiz_member = $CI->member_mdl->get_wiz_member_by_wm_uid($uid);
    }

    /**
     * 퀘스트 진행.
     * @param $q_idx 퀘스트번호.
     * @param $code 호출하게된 원인. 글쓰기 시 pk, mset의 pk, 뷰페이지의 pk 등등. 글쓰기,답변 횟수 퀘스트 같은경우 여러테이블을 집계하므로 pk가 중복될수 있어서 뒤에 문자열 붙여서 구분
	 */
    public function do_quest($q_idx, $code='')
    {
        
        //if(!$this->wiz_member || $this->wiz_member['wm_d_did'] !='16') return array('state'=>false,'msg'=>'진입불가');
        if(!$this->wiz_member ) return array('state'=>false,'msg'=>'진입불가');

        // 체크할 퀘스트 번호. 연계퀘스트 체크 시 다음퀘스트 번호로 변할 수 있다.
        $this->q_idx = $q_idx;
        $this->code = $code;

        // ***퀘스트 진행 상태 체크. 퀘스트정보, 상위퀘스트번호,해금상태, 연계퀘스트번호 찾기***
        $check = $this->check_quest_progress();
        // 퀘완료되었거나 해금안됐거나 잘못된 퀘스트번호 들어오면 리턴
        if($check['state'] === false) return $check;

        $CI =& get_instance();

        /*
            ----------Warning--------
            로그테이블이 분리되어있어서 퀘스트에 맞는 로그테이블이름을 설정해줘야한다.
            설정전, 여기 위에서 로그테이블을 사용한다면 에러발생확률 매우 높음
        */
        $CI->quest_mdl->set_quest_log_table_name($this->quest_info['mq_type'] =='1' ? '':$this->parent_q_idx);
        
        // ***들어온 퀘스트가 이미 들어온code를 가지고있어서 중복되서 제외할경우, 수업횟수 등등 조건체크가 필요한경우가 있다***
        $check = $this->check_quest_code_duplicate($this->code);
        
        if($check['state'] === false) return $check;


        // 퀘완료 여부. mq_try 완료해야하는 횟수, mqp_progress 지금까지 누적된 횟수
        $quest_complete = $this->quest_info['mq_try'] == ((int)$this->quest_info['mqp_progress'] + 1) ? true:false;

        // ***진행률 업뎃 및 로깅처리***
        $reqult = $CI->quest_mdl->upsert_quest_progress($this->q_idx, $this->wiz_member['wm_uid'], $this->quest_info, $this->code, $quest_complete);

        if($reqult < 0) 
        {
            log_message('error', 'do_quest :'.$q_idx.'|'.$this->q_idx.', member: '.$this->wiz_member['wm_uid']. ', quest_info: '.http_build_query($this->quest_info).', code: '.$this->code);
            return array('state'=>false,'msg'=>'퀘스트 실패');
        }

        // ***퀘완료 시 추가처리***
        if($quest_complete)
        {
            // 요청받은 퀘스트 해금체크 및 처리
            $this->quest_release($this->q_idx);
            //부모퀘 확인하여 완료 및 해금처리
            $this->check_parent_quest($this->quest_info);
        }

        return array('state'=>true);
    }

    /**
     * 퀘스트 진행 체크. 
     * 체크하는부분 
     *  1) 연계퀘스트 확인, 맞으면서 완료됐다면 다음퀘스트 번호자동으로 찾는다
     *  2) 처리할 퀘스트 번호가 완료인지 체크한다.
     *  3) 해금이 필요한 퀘스트인지, 맞다면 해금되었는지 체크한다.
     *  4) 연계퀘스트 체크 함수에서는 해당퀘스트 사용하는 퀘인지 체크, 해금체크 함수에서는 해당퀘의 부모퀘가 사용하는 퀘인지 체크(is_use)
	 */
    private function check_quest_progress()
    {
        if(!$this->q_idx) return array('state'=>false,'msg'=>'잘못된 퀘스트입니다.');

        // 연계퀘스트인지 체크하고 맞다면 해야하는 퀘스트 번호찾는다.
        $check_next_quest = $this->check_link_quest($this->q_idx);

        if($check_next_quest['state'] === false) return $check_next_quest;

        // 퀘스트가 완료된 상태라면 바로 리턴
        if($this->quest_info['mqp_complete_date'])
        {
            return array('state'=>false,'msg'=>'퀘스트가 완료처리되었습니다.');
        }

        // 해야하는 퀘스트가 해금필요한지, 필요하다면 해금되어 진행 가능한지
        $is_release = $this->check_quest_release($this->q_idx, $this->quest_info);

        if($is_release['state'] === false) return $is_release;

        return array('state'=>true);
    }

    /**
     * 재귀함수
     * 퀘스트 연계퀘스트인지 체크하고 맞다면 해야하는 퀘스트 번호찾는다
	 */
    private function check_link_quest($q_idx)
    {
        $CI =& get_instance();

        /* 
            요청받은 퀘스트 번호로 $this->q_idx와 $this->quest_info 설정 
            연계퀘스트 체크로 재귀진입할때마다 위 두 변수는 재설정된다.
        */
        
        // 퀘스트 정보
        $this->quest_info = $CI->quest_mdl->row_quest_progress_info($q_idx, $this->wiz_member['wm_uid']);
        $this->q_idx = $q_idx;

        // 퀘스트가 없으면
        if(!$this->quest_info)
        {
            return array('state'=>false,'msg'=>'잘못된 퀘스트입니다.(1)');
        }
        //퀘 사용 여부
        if(!$this->quest_info['mq_is_use'])
        {
            return array('state'=>false,'msg'=>'사용불가능한 퀘스트입니다.(1)');
        }

        // 퀘스트가 누적형이고 완료된상태라면 다음연계퀘스트로 자동진행해야하기 때문에 다음퀘스트 번호로 재체크
        if($this->quest_info['mq_type'] == '2' && $this->quest_info['mqp_complete_date'] && $this->quest_info['mq_next_q_idx'])
        {
            $next_quest = $this->check_link_quest($this->quest_info['mq_next_q_idx']);
            if($next_quest['state'] === false)
            {
                //에러리턴
                return $next_quest;
            }
        }

        // 연계퀘스트가 아니라면 mq_next_q_idx값이 비어있어 바로 리턴된다

        return array('state'=>true);
    }

    /**
     * 재귀함수
     * 퀘스트 해금 체크
	 */
    private function check_quest_release($q_idx, $quest_info)
    {
        $CI =& get_instance();

        // 해금필요한지, 필요하다면 해금되어 진행 가능한지
        $check = $CI->quest_mdl->checked_quest_release($q_idx, $this->wiz_member['wm_uid']);

        if($check) 
        {
            /*
                해금안됐지만 완료는 되어 있으면 해금 처리 해준다.
                이 경우는 새로운 메인퀘스트 다음장이 생성될때 해당될 수 있다.
                EX)210510 기준 메인퀘가 3장까지 있으나 4장퀘가 중간에 생긴다면, 4장의 선행퀘가 3장이 될텐데
                4장이 생기기 전에 3장을 완료하게 되면 4장 해금처리를 할수가 없다.(4장 데이터가 그 시점에서는 없으므로)
            */
            foreach($check as $q)
            {
                $check_quest_complete = $CI->quest_mdl->row_quest_progress_info($q['mql_precede_q_idx'], $this->wiz_member['wm_uid']);

                //위와 같은 경우는 메인퀘스트일때만 해당하므로 메인퀘만 체크. 만약 업적도 해금조건에 포함되도록 변경된다면 추가 처리를 해줘야한다.(mq_type=1 조건 풀고 로그네임만 찾으면 된다)
                if($check_quest_complete['mqp_complete_date'] && $check_quest_complete['mq_type'] =='1')
                {
                    $CI->quest_mdl->set_quest_log_table_name('');
                    // 요청받은 퀘스트 해금체크 및 처리
                    $this->quest_release($q['mql_precede_q_idx']);
                }
                else
                {
                    return array('state'=>false, 'msg' => '먼저 완료가 필요한 선행퀘스트가 존재합니다.');
                }
            }
        }

        // 부모 퀘스트 해금체크
        if($quest_info['mq_parent_q_idx'])
        {
            //부모퀘 정보조회
            $parent_quest_info = $CI->quest_mdl->row_quest_progress_info($quest_info['mq_parent_q_idx'], $this->wiz_member['wm_uid']);
            if(!$parent_quest_info) return array('state'=>false, 'msg' => '잘못된 퀘스트 정보');

            //부모퀘 사용 여부
            if(!$parent_quest_info['mq_is_use'])
            {
                return array('state'=>false,'msg'=>'사용불가능한 퀘스트입니다.(2)');
            }

            //최상위퀘스트번호설정. 아래에서 재귀진입할때마다 상위퀘스트 번호가 있다면 재설정될것이다
            $this->parent_q_idx = $quest_info['mq_parent_q_idx'];

            //부모퀘정보로 해금체크 재귀 진입
            $check_parent = $this->check_quest_release($quest_info['mq_parent_q_idx'], $parent_quest_info);
            if($check_parent['state'] === false) return $check_parent;
        }
        
        return array('state'=>true);
    }

    /**
     * 퀘스트 해금 처리
	 */
    public function quest_release($q_idx)
    {
        $CI =& get_instance();

        // 해당퀘스트가 선행퀘로 등록되어있으나 해당유저에게 해금되지 않은 목록 찾기
        $list = $CI->quest_mdl->find_precede_quest_list($q_idx, $this->wiz_member['wm_uid']);

        if($list)
        {
            $CI->quest_mdl->insert_quest_release_member($list, $this->wiz_member['wm_uid']);
        }

        return array('state'=>true);
    }

    
    /**
     * 재귀함수
     * 하위 퀘스트 완료 시 부모퀘스트도 완료할지 체크해준다.
	 */
    private function check_parent_quest($quest_info)
    {
        //부모퀘 없으면 패스
        if(!$quest_info['mq_parent_q_idx']) return array('state'=>true);

        $CI =& get_instance();
        
        // 부모퀘의 하위퀘가 전부 완료되었는지 확인하기 위해 미완료 하위퀘 있는지 조회
        $check = $CI->quest_mdl->checked_subquest_complete_all($quest_info['mq_parent_q_idx'], $this->wiz_member['wm_uid']);

        // 하위퀘 아직 전부 완료되지 않으면 리턴
        if($check['cnt'] > 0) return array('state'=>false);

        // 부모 퀘스트 정보
        $parent_quest_info = $CI->quest_mdl->row_parent_quest_info($quest_info['mq_parent_q_idx'], $this->wiz_member['wm_uid']);
        if(!$parent_quest_info) return array('state'=>false);
        
        // 하위퀘가 전부 완료되었므로 부모퀘 완료 처리
        $param = [
            'q_idx'         => $quest_info['mq_parent_q_idx'],
            'uid'           => $this->wiz_member['wm_uid'],
            'progress'      => 1,
            'start_date'    => $parent_quest_info['mqp_start_date'],    // 하위퀘스트의 시작일에서 가져온다
            'complete_date' => date('Y-m-d H:i:s'),
        ];
        $CI->quest_mdl->insert_quest_progress($param, 2);

        // 부모퀘가 완료되었으면 부모퀘스트 번호로 해금된 퀘스트 테이블에 등록
        $this->quest_release($quest_info['mq_parent_q_idx']);
        
        // 부모퀘 완료 시 부모퀘의 부모퀘....가 있는지 재귀 호출하여 체크
        $this->check_parent_quest($parent_quest_info);

        return array('state'=>true);
    }
    
    
    /**
     * 재귀함수
     * 퀘스트 해금 체크
	 */
    private function find_highest_q_idx($quest_info)
    {
        $CI =& get_instance();

        $q_idx = $quest_info['mq_q_idx'];

        // 부모 퀘스트 번호 있으면 재귀체크
        if($quest_info['mq_parent_q_idx'])
        {
            //부모퀘 정보조회
            $parent_quest_info = $CI->quest_mdl->row_quest_progress_info($quest_info['mq_parent_q_idx'], $this->wiz_member['wm_uid']);

            $q_idx = $this->find_highest_q_idx($parent_quest_info);
        }
        
        return $q_idx;
    }
    
    /**
     * 퀘스트 보상 지급
	 */
    public function reward($q_idx)
    {
        $CI =& get_instance();
        $quest_info = $CI->quest_mdl->row_quest_progress_info($q_idx, $this->wiz_member['wm_uid']);
        
        if(!$quest_info || !$quest_info['mqp_complete_date']) return array('state'=>false, 'err_code'=> '0503','msg'=>'퀘스트가 완료되지 않았습니다.');
        if(!$quest_info['mq_reward']) return array('state'=>false, 'err_code'=> '0504','msg'=>'보상이 없습니다.');
        if($quest_info['mqp_reward_date']) return array('state'=>false, 'err_code'=> '0505','msg'=>'이미 보상을 받았습니다.');
        
        $CI->load->model('point_mdl');
        $CI->load->model('member_mdl');

        $highest_q_idx = '';
        if($quest_info['mq_type'] =='2')
        {
            $highest_q_idx = $this->find_highest_q_idx($quest_info);
            if(!$highest_q_idx) return array('state'=>false, 'err_code'=> '0506', 'msg'=>'잘못된 퀘스트입니다.');
        }

        //로그테이블 설정
        $CI->quest_mdl->set_quest_log_table_name($highest_q_idx);

        // 포인트|수업변환횟수
        $reward = explode('|',$quest_info['mq_reward']);

        $pt_name = '['.$quest_info['mq_title'].'] 퀘스트 완료 보상';
        $get_reward = false;
        // 포인트 지급설정시
        if($reward[0] && $reward[0] > 0)
        {
            // 보상 지급. 
            $point = array(
                'uid'     => $this->wiz_member['wm_uid'],
                'name'    => $this->wiz_member['wm_name'],
                'point'   => $reward[0],
                'pt_name' => $pt_name, 
                'kind'    => 'qu', 
                'b_kind'  => 'quest',
                'co_unq'  => $q_idx, 
                'showYn'  => 'y',
                'secret'  => 'N',
                'regdate' => date("Y-m-d H:i:s")
            );

            /* 포인트 내역 입력 및 포인트 추가 */
            $rpoint = $CI->point_mdl->set_wiz_point($point);

            if($rpoint < 0)
            {
                return array('state'=>false, 'res_code'=>'0500', 'msg'=>'DB ERROR');
            }

            $get_reward = true;
        }

        // 수업변환권 지급설정시. 유효기간어떻게하지?
        if($reward[1] && $reward[1] > 0)
        {
            $get_reward = true;
        }

        if($get_reward === false)
        {
            return array('state'=>false, 'err_code'=> '0507','msg'=>'받을 수있는 보상이 없습니다.');
        }
        
        $trophy = 0;
        //트로피 있으면 트로피도 지급
        if($quest_info['mq_tropy_get_yn'] =='Y')
        {
            $param = [
                'highest_q_idx' => $highest_q_idx,
                'uid'   => $this->wiz_member['wm_uid'],
            ];

            $result = $CI->quest_mdl->insert_mint_quest_user_tropy($param);

            if($result < 0)
            {
                return array('state'=>false, 'res_code'=>'0500', 'msg'=>'DB ERROR(2)');
            }

            $trophy = 1;
        }

        $param = [
            'q_idx' => $q_idx,
            'uid'   => $this->wiz_member['wm_uid'],
        ];
        $set_param = [
            'reward_date'=>date('Y-m-d H:i:s'),
        ];
        //보상받았다고 업뎃
        $result = $CI->quest_mdl->update_quest_progress($set_param, $param, 3);
        if($result < 0)
        {
            return array('state'=>false, 'res_code'=>'0500', 'msg'=>'DB ERROR(3)');
        }

        return array('state'=>true, 'point'=> $reward[0],'trophy'=>$trophy);
    }
    
    /**
     * 중복된 행위인지 체크
     * 들어온 퀘스트가 이미 처리된 code를 가지고있어서 중복되서 제외할경우(예를들면 커리큘럼 조회, 수업횟수 등)
	 */
    private function check_quest_code_duplicate($code)
    {
        $CI =& get_instance();
        $return = array('state'=>true);

        //중복체크할 코드 없으면 바로 리턴
        if(!$code) return $return;

        //일반퀘스트라면
        if($this->quest_info['mq_type'] == '1')
        {
            switch($this->q_idx)
            {
                case 5:     //레벨테스트 1회 완료
                case 6:     //민트영어 수강신청 완료하기
                case 10:    //전문강사진 페이지에서 5명의 강사 프로필 확인하기
                case 11:    //MSET 최초 1회 시험 완료하기
                case 13:    //게시글에 추천 3회 하기
                case 15:    //즐겨찾는 게시판 등록해보기
                case 17:    //수업 10회 완료하기
                case 18:    //선호수업 방식 요청하기
                case 21:    //잔여수업 앞당겨서 벼락치기 수업 2회 완료하기
                case 22:    //잔여수업 앞당겨서 벼락치기 수업 4회 완료하기
                case 24:    //오늘 하루만 시간 / 선생님 변경하기 1회 완료하기
                case 25:    //전화수업, 화상수업(민트영어라이브) 각각 수업들어보기
                case 30:    //얼굴철판딕테이션 이벤트 상세페이지 확인하기
                case 31:    //버프이벤트 상세페이지 확인하기
                case 32:    //브레인워시 상세이벤트 확인하기
                case 33:    //그 외 이벤트 3가지 상세페이지 확인하기
                case 34:    //[민트사용노하우] 게시글 3회 읽어보기
                case 35:    //[수업체험후기] 게시글 3회 읽어보기
                case 36:    //[민트에서빛난회원들] 게시글 3회 읽어보기
                case 37:    //[베스트글모음방] 게시글 3회 읽어보기
                case 50:    //수업 20회 완료하기
                case 51:    //[왕초보옹알이강좌] 영상 강좌 10회 듣기
                case 52:    //[영문법아작내기] 영상 강좌 5회 듣기
                case 54:    //[오늘의영어한마디] 영상 강좌 5회 듣기
                case 55:    //MSET 3회 시험 완료하기

                    //바로 리턴안하고 같은코드로 다시 들어온건지 중복 체크해야된다.
                    break;
                case 7:     //민트영어 대표 커리큘럼 (AHOP, NS, IELTS) 상세페이지 확인하기
                    if($code !='1358' && $code !='1374' && $code !='1364' && $code !='1365' && $code !='1366' && $code !='1367') return array('state'=>false, 'msg'=>'잘못된 형태의 퀘스트요청(1)');
                    break;
                case 8:     //커리큘럼 페이지에서 3가지 커리큘럼 상세페이지 확인하기
                case 9:     //커리큘럼 페이지에서 3가지 커리큘럼 교재 확인하기
                    if($code =='1358' || $code =='1374' || $code =='1364' || $code =='1365' || $code =='1366' || $code =='1367') return array('state'=>false, 'msg'=>'잘못된 형태의 퀘스트요청(2)');
                    break;
                default:
                    // 체크할 필요없는 퀘스트는 바로 리턴
                    return $return;
                    break;
            }

            $check_q_idx = array($this->q_idx);
        }
        else
        {
            //누적형이면 부모퀘스트 코드 번호로 하위퀘스트번호 전부 찾아서 로그체크한다
            $child = $CI->quest_mdl->find_child_q_idx_by_parent_q_idx($this->quest_info['mq_parent_q_idx']);
            if(!$child) return array('state'=>false, 'msg'=>'퀘스트 그룹이 없음');

            $check_q_idx = explode(',',$child['q_idx']);
        }

        /*
            수업 하나만 완료해도 다양하게 퀘스트를 체크해야되서 받은code로 수업조회해서 알맞는 중복체크 코드값으로 바꿔 넣어준다
            215:다양한 선생님과 수업해보기
            181:수업 출석 일수
            25:전화수업, 화상수업(민트영어라이브) 각각 수업들어보기
        */
        if(($this->q_idx == '25' || $this->quest_info['mq_parent_q_idx'] =='181' || $this->quest_info['mq_parent_q_idx']=='215') && $this->is_batch === false)
        {
            $SC = $CI->lesson_mdl->row_schedule_by_sc_id($code, $this->wiz_member['wm_uid']);
            if(!$SC) return array('state'=>false, 'msg'=>'잘못된 형태의 퀘스트요청(3)');

            if($this->q_idx == '25') 
            {
                $code = $SC['lesson_gubun'];   //전화수업(M), 화상수업(민트영어라이브)(E) 각각 수업들어보기니까 구분으로체크
                if($code !='M' && $code !='E') return array('state'=>false, 'msg'=>'잘못된 형태의 퀘스트요청');
            }
            elseif($this->quest_info['mq_parent_q_idx'] =='181') $code = substr($SC['startday'],0,10);   //출석일수 므로 날짜로 중복체크
            elseif($this->quest_info['mq_parent_q_idx'] =='215') $code = $SC['tu_uid'];                     //강사uid로 중복체크
        }

        // $check_q_idx:체크할 퀘스트번호들, $code: 중복 체크해야하는 값
        $check = $CI->quest_mdl->checked_quest_log_by_code($this->wiz_member['wm_uid'], $check_q_idx, $code);

        if($check['cnt'] > 0) return array('state'=>false, 'msg'=>'중복');
        //위에서 찾은 code로 덮어씌운다. 이후 로그저장할때 사용
        $this->code = $code;

        return $return;
    }

    
    /**
     * 퀘스트 횟수 차감
     * 커뮤니티 글, 댓글 삭제 할 시 증가시켜준 퀘스트 횟수를 다시 차감한다
     * 커뮤니티 같은 글쓰기 후 글삭제를 반복하여 퀘스트 어뷰징을 발생시킬수 있는 퀘스트만 호출하도록 한다
	 */
    public function quest_decrement($q_idx, $code)
    {
        $CI =& get_instance();

        //누적퀘스트라면 현재진행 퀘스트번호 찾고 그에 해당하는 퀘정보설정
        $check_next_quest = $this->check_link_quest($q_idx);
        if($check_next_quest['state'] === false) return $check_next_quest;

        //메인퀘스트인데 보상받았으면 차감하는 의미가 없으므로 리턴
        if($this->quest_info['mq_type'] =='1' && $this->quest_info['mqp_reward_date']) return array('state'=>false,'msg'=>'잘못된 퀘스트입니다.(1)');

        $highest_q_idx = '';
        if($this->quest_info['mq_type'] =='2')
        {
            $highest_q_idx = $this->find_highest_q_idx($this->quest_info);
            if(!$highest_q_idx) return array('state'=>false,'msg'=>'잘못된 퀘스트입니다.(2)');
        }

        //로그테이블 설정
        $CI->quest_mdl->set_quest_log_table_name($highest_q_idx);

        //code가 로그테이블에 없다면 퀘스트 진행한 건이 아니므로 리턴
        $check = $CI->quest_mdl->checked_quest_log_by_code($this->wiz_member['wm_uid'], array($this->q_idx), $code);
        if($check['cnt'] == 0) return array('state'=>false,'msg'=>'잘못된 퀘스트입니다.(3)');

        //메인퀘스트인데 보상받지 않은 완료 상태라면 완료날짜 삭제한다
        //업적퀘스트라면 현재진행중인 퀘스트의 횟수 카운트를 까기만한다
        $complete_date_reset = $this->quest_info['mq_type'] =='1' && $this->quest_info['mqp_complete_date'] ? 1:0;

        $where = [
            'uid'   => $this->wiz_member['wm_uid'],
            'q_idx' => $this->q_idx
        ];

        //횟수 차감
        $CI->quest_mdl->decrement_quest_progress($where, $complete_date_reset, $code);

        return array('state'=>true);
    }

    /**
     * 퀘스트 로그에 넣어줄 code에 subfix 붙여주기. 
     * 게시판 테이블이 분리되어있으므로 pk키 만으로는 중복될수 있어서 처리
     * type: article, comm
	 */
    public static function make_quest_subfix($table_code, $type='article')
    {
        $subfix = [];
        if($table_code == 'express' || $table_code == '9001')
        {
            $subfix = [
                'article' => '_me',
                'comm' => '_mec',
            ];
        }
        else if($table_code == 'correction' || $table_code == '9004')
        {
            $subfix = [
                'article' => '_wc',
                'comm' => '_wcc',
            ];
        }
        else if(strpos($table_code,'dictation') !==false || $table_code == '9002')
        {
            $subfix = [
                'article' => '_mc',
                'comm' => '_mcc',
            ];
        }
        else if($table_code =='toteacher' || $table_code == '9998')
        {
            $subfix = [
                'article' => '_wtt',
            ];
        }
        else if($table_code =='request' || $table_code == '9999')
        {
            $subfix = [
                'article' => '_ws',
            ];
        }
        else
        {
            $subfix = [
                'article' => '_mb',
                'comm' => '_mbc',
            ];
        }

        return $subfix[$type];

    }


    
    /**
     * 초기누적데이터 세팅 배치용..
	 */
    public static function insert_batch($quest,$user_info,$parent_q_idx)
    {
        //완료일에 찍어줄 날짜
        $date = '2021-04-01 00:00:00';

        $CI =& get_instance();

        $insert_cnt = 0;
        $complete_q_idx = [];
        $codes = $user_info['code'] ? explode(',',$user_info['code']):[];
        $prev_offset = 0;

        $CI->quest_mdl->set_quest_log_table_name($parent_q_idx);

        foreach($quest as $quest_row)
        {
            //퀘스트 횟수보다 유저가 과거 시행한갯수가 같거나 크면 완료처리
            if($user_info['cnt'] >=$quest_row['try'])
            {
                $slice_code = array_slice($codes, $prev_offset, $quest_row['try']-$prev_offset);

                //퀘완료 처리
                $CI->quest_mdl->insert_quest_progress_batch([
                    'q_idx'          => $quest_row['q_idx'],
                    'uid'            => $user_info['uid'],
                    'progress'       => $quest_row['try'],
                    'start_date'     => $date,
                    'complete_date'  => $date,
                ]);

                $insert_cnt++;
                $complete_q_idx[] = $quest_row['q_idx'];
                $prev_offset = $quest_row['try'];

                //group_concat으로 모아온 pk키들을 루프돌려 진행로그넣어주자. 퀘스트 진행할때 중복pk 걸러내는 용도로 사용하기 위함
                foreach($slice_code as $c)
                {
                    $CI->quest_mdl->insert_quest_progress_log_batch([
                        'log_type'  => 1,
                        'q_idx'     => $quest_row['q_idx'],
                        'uid'       => $user_info['uid'],
                        'code'      => $c,
                        'regdate'   => $date,
                    ]);
                }
            }
            else
            {
                $slice_code = array_slice($codes, $prev_offset, $user_info['cnt']-$prev_offset);

                //시행횟수가 완료횟수에 미달했으면 진행중으로 insert
                $CI->quest_mdl->insert_quest_progress_batch([
                    'q_idx'          => $quest_row['q_idx'],
                    'uid'            => $user_info['uid'],
                    'progress'       => $user_info['cnt'],
                    'start_date'     => $date,
                ]);

                //group_concat으로 모아온 pk키들을 루프돌려 진행로그넣어주자. 퀘스트 진행할때 중복pk 걸러내는 용도로 사용하기 위함
                foreach($slice_code as $c)
                {
                    $CI->quest_mdl->insert_quest_progress_log_batch([
                        'log_type'  => 1,
                        'q_idx'     => $quest_row['q_idx'],
                        'uid'       => $user_info['uid'],
                        'code'      => $c,
                        'regdate'   => $date,
                    ]);
                }

                break;
            }
            
        }

        //하위퀘 전부 완료되었으면 상위퀘도 완료
        if(count($quest) == $insert_cnt)
        {
            $CI->quest_mdl->insert_quest_progress_batch([
                'q_idx'          => $parent_q_idx,
                'uid'            => $user_info['uid'],
                'progress'       => 1,
                'start_date'     => $date,
                'complete_date'  => $date,
            ]);
        }


        //해당유저가 시행한횟수랑 코드갯수가 안맞는경우가 있을까해서 로그남겨봄. group_concat_max_len->1048576
        if(!empty($codes) && count($codes) != $user_info['cnt'])
        {
            log_message('error', 'group_concat_max_len :'.http_build_query($user_info));
        }

        //완료된 퀘는 따로 모아서 완료 로그 찍어준다
        if($complete_q_idx)
        {
            foreach($complete_q_idx as $q_idx)
            {
                $CI->quest_mdl->insert_quest_progress_log_batch([
                    'log_type'  => 2,
                    'q_idx'     => $q_idx,
                    'uid'       => $user_info['uid'],
                    'code'      => '',
                    'regdate'   => $date,
                ]);
            }
        }

    }

    /**
     * 초기누적데이터 세팅 배치용. 라이브에서 배치돌리는중에 데이터 들어왔을때도 문제없도록 한 버전.
     * 단순히 누적횟수만큼 루프 돌면서 퀘스트 실행시켜준다.
	 */
    public static function quest_batch($quest,$user_info,$parent_q_idx)
    {

        $CI =& get_instance();
        $CI->quest_mdl->set_quest_log_table_name($parent_q_idx);
        $codes = $user_info['code'] ? explode(',',$user_info['code']):[];

        $request_q_idx = $quest[0]['q_idx'];
        $i = 0;
        //$MQ = new MintQuest($user_info['uid']);

        //데이터 쌓아줄 회원설정
        MintQuest::getInstance($user_info['uid'])->set_wiz_member($user_info['uid']);
        //실제퀘스트로 진입 했을때와 배치로 돌렸을때의 데이터형태가 약간 다르다.(code 값)
        //배치 시 일부퀘스트에서 속도단축시키기 위해.
        MintQuest::getInstance()->is_batch = true;

        foreach($quest as $quest_row)
        {
            //유저시행횟수만큼 루프돌되, 누적퀘스트 한계치가 도달하면 false 되어 루프 벗어난다
            while($quest_row['try'] > $i && $user_info['cnt'] > $i)
            {
                $a = MintQuest::getInstance()->do_quest($request_q_idx, $codes[$i] ? $codes[$i]:'');
                //$MQ->do_quest($request_q_idx, $codes[$i] ? $codes[$i]:'');
                $i++;  
            }
            
        }

        //unset($MQ);
        //$MQ = null;
    }

}