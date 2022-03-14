<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/admin/_Admin_Base_Controller.php';

class Log extends _Admin_Base_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }

    /**
     * 전문강사진 퇴사율, 학생 점유율
     */
    public function rate_tutor_resign_and_student_share()
    {
        $return_array = array();

        $request = array(
            "year"        => $this->input->post('year')        ? trim($this->input->post('year'))        : date('Y'),
            "start_month" => $this->input->post('start_month') ? trim($this->input->post('start_month')) : '01',
            "end_month"   => $this->input->post('end_month')   ? trim($this->input->post('end_month'))   : date('m')
        );

        $this->form_validation->set_data($request);
        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg']      = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }
        
        /**
         * 현재 년도일 경우 현재 날짜까지만 데이터를 구한다
         * 점유율을 구해야 하므로 검색 범위에서 1달전을 추가한다
         */
        $yesteryear = date('Y-m', strtotime('-1 month', strtotime($request['year'].'-'.$request['start_month'])));
        $sdate = $yesteryear.'-01';

        // 검색달이 현재달을 넘어갈경우 강제로 현재달로 고정(현재 연도 일때)
        if($request['year'] == date('Y') && $request['end_month'] > date('m')) $request['end_month'] = date('m');
        
        $edate = $request['year'].'-'.$request['end_month'].'-31';
        $end_month = $request['end_month'];

        $this->load->model('admin/log_mdl', 'a_log_mdl');

        $total = array('class_time'=>0,'available_time'=>0,'join'=>0,'resign'=>0);
        $rate = array();

        //날짜별 빈데이터 값 세팅
        for($i=$request['start_month'];$i<=$end_month;$i++)
        {
            if(strlen($i) < 2) $zero = '0';
            else               $zero = '';

            $rate[$request['year'].'.'.$zero.$i]['date']        = $request['year'].'.'.$zero.$i; //날짜
            $rate[$request['year'].'.'.$zero.$i]['class']       = 0; //총 수업시간
            $rate[$request['year'].'.'.$zero.$i]['available']   = 0; //강사 총 가용시간
            $rate[$request['year'].'.'.$zero.$i]['s_rate']      = 0; //학생 점유율
            $rate[$request['year'].'.'.$zero.$i]['a_rate']      = 0; //점유율 등락율
            $rate[$request['year'].'.'.$zero.$i]['join']        = 0; //입사자 수
            $rate[$request['year'].'.'.$zero.$i]['resign']      = 0; //퇴사자 수
            $rate[$request['year'].'.'.$zero.$i]['resign_rate'] = 0; //퇴사율

            // 퇴사하지않은 총 강사 수
            $tutor_total = $this->a_log_mdl->get_count_total_tutor($request['year'].'-'.$zero.$i);
            $rate[$request['year'].'.'.$zero.$i]['tutor_total'] = $tutor_total ? $tutor_total['cnt'] : 0;
        }
        
        //총 수업시간, 총 가용시간
        $result = $this->a_log_mdl->get_class_available_time($sdate, $edate);
        if($result)
        {
            $a_rate = array();
            foreach($result as $value)
            {
                $date = explode('-', $value['date']);
                $prev_date = date('Y-m',strtotime($value['date']." -1 month"));
                $prev_date = explode('-', $prev_date);

                if($value['class_time'] && $value['available_time'])
                {
                    $s_rate = ($value['class_time'] / $value['available_time']) * 100;
                    $s_rate = round($s_rate, 2);
                }
                else
                {
                    $s_rate = 0;
                }

                //전월 데이터는 저장 제외
                if($value['date'] != $yesteryear)
                {
                    $rate[$date[0].'.'.$date[1]]['class']     = $value['class_time'] ? $value['class_time'] : 0;     //총 수업시간
                    $rate[$date[0].'.'.$date[1]]['available'] = $value['available_time'] ? $value['available_time'] : 0; //강사 총 가용시간
                    $rate[$date[0].'.'.$date[1]]['s_rate']    = $s_rate; //학생 점유율
                    $rate[$date[0].'.'.$date[1]]['a_rate']    = round($s_rate - $a_rate[$prev_date[0].'.'.$prev_date[1]], 2); //점유율 등락율

                    $total['class_time'] = $total['class_time'] + $value['class_time']; //총 수업시간 합계
                    $total['available_time'] = $total['available_time'] + $value['available_time']; //강사 총 가용시간 합계
                }
                
                //학생 점유율 저장
                $a_rate[$date[0].'.'.$date[1]] = $s_rate;
            }
        }

        //강사 입사자 수
        $join = $this->a_log_mdl->get_count_tutor_join($sdate, $edate);
        if($join)
        {
            foreach($join as $value)
            {
                $date = explode('-', $value['date']);

                //전월 데이터는 저장 제외
                if($value['date'] != $yesteryear)
                {
                    $rate[$date[0].'.'.$date[1]]['join'] = $value['tu_join'] ? $value['tu_join'] : 0; //입사자 수

                    $total['join'] = $total['join'] + $value['tu_join']; //총 입사자 수
                }
            }
        }

        //강사 퇴사자 수
        $resign = $this->a_log_mdl->get_count_tutor_resign($sdate, $edate);
        if($resign)
        {
            foreach($resign as $value)
            {
                $date = explode('-', $value['date']);

                //전월 데이터는 저장 제외
                if($value['date'] != $yesteryear)
                {
                    if($value['tu_resign'] && $rate[$date[0].'.'.$date[1]]['join'])
                    {
                        $resign_rate = ($value['tu_resign'] / $rate[$date[0].'.'.$date[1]]['join']) * 100;
                    }
                    else
                    {
                        $resign_rate = 0;
                    }

                    $rate[$date[0].'.'.$date[1]]['resign'] = $value['tu_resign']; //퇴사자 수
                    $rate[$date[0].'.'.$date[1]]['resign_rate'] = round($resign_rate, 2); //퇴사율

                    $total['resign'] = $total['resign'] + $value['tu_resign']; //총 퇴사자 수
                }
            }
        }

        // 퇴사율, 점유율 총합
        $total['s_rate'] = round(($total['class_time'] / $total['available_time']) * 100, 2);
        if($total['resign'] && $total['join']) $total['resign_rate'] = round(($total['resign'] / $total['join']) * 100, 2);
        else                                   $total['resign_rate'] = 0;

        $return_array['res_code']      = '0000';
        $return_array['msg']           = "get rate success";
        $return_array['data']['total'] = $total;
        $return_array['data']['rate']  = $rate;
        echo json_encode($return_array);
        exit;
    }


}








