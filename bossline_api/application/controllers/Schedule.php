<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'/controllers/_Base_Controller.php';

class Schedule extends _Base_Controller {


    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }
    

    /* 
        수업 가능 강사 명단 
        - 오늘하루 시간/강사 변경 
        - 벼락치기
        - 완전히 시간/강사 변경
        - 신규수업
    */
    public function classable_tutor_()
    {
        $request = array(
            "number_of_classes" => trim(strtolower($this->input->post('number_of_classes'))),
            "period_of_class" => trim(strtolower($this->input->post('period_of_class'))),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

    }


    /* 강사 수업 가능 스케쥴 */
    public function schedule_()
    {
        $request = array(
            "tu_uid" => trim(strtolower($this->input->post('tu_uid'))),
            "number_of_classes" => trim(strtolower($this->input->post('number_of_classes'))),
            "period_of_class" => trim(strtolower($this->input->post('period_of_class'))),
        );

        $this->form_validation->set_data($request);

        if($this->form_validation->run() == FALSE)
        {
            $return_array['res_code'] = '0400';
            $return_array['msg'] = current($this->form_validation->error_array());
            echo json_encode($return_array);
            exit;
        }

        $this->load->model('tutor_mdl');
        $this->load->model('holiday_mdl');

        /* 강사 스케쥴 */
        $schedule = [];

        /* 수업 조회 기간 */
        $period_of_class = $request['period_of_class'];
        $enddate_of_class = date("Y-m-d", strtotime("+".$period_of_class." days", time()));

        /* 조회 기간 - days */
        $period = 7;
        $startdate = date("Y-m-d");
        $enddate = date("Y-m-d", strtotime("+".$period." days", time()));

        /* 수업 가능한 요일, 시간 */
        $tutor_weekend = $this->tutor_mdl->list_tutor_weekend_by_tu_uid($request['tu_uid']);

        if(!$tutor_weekend)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = "프로세스 오류";
            $return_array['data']['err_code'] = '0406';
            $return_array['data']['err_msg'] = '강사 스케쥴 정보가 존재하지 않습니다.';
            echo json_encode($return_array);
            exit;
        }
 
        /* 공휴일 및 회사휴일 */
        $list_holiday = $this->holiday_mdl->list_holiday($startdate, $enddate);
        
        /* 강사휴일 - 수업 불가능한 날짜 */
        $tutor_blockdate = $this->tutor_mdl->check_tutor_blockdate($request['tu_uid'], $startdate, $enddate);
    
        /* 강사 쉬는시간 - 수업 불가능한 시간 */
        $tutor_breakingtime = $this->tutor_mdl->check_tutor_breakingtime($request['tu_uid'], $startdate, $enddate);


        /* 
            주 수업횟수
            정규수업 (고정수업)
            - 주2회 : 화,목
            - 주3회 : 월,수,금
            - 주5회 : 월,화,수,목,금 
        */
        if($request['number_of_classes'] == "2")
        {
            $day_of_class = "2,4";
        }
        else if($request['number_of_classes'] == "3")
        {
            $day_of_class = "1,3,5";
        }
        else if($request['number_of_classes'] == "5")
        {
            $day_of_class = "1,2,3,4,5";
        }

        /* 강사 기존 스케쥴 */
        $tutor_schedule = $this->tutor_mdl->list_tutor_schedule_by_tu_uid($request['tu_uid'], $startdate, $enddate_of_class, $day_of_class);


        /* 현재일부터 조회기간 날짜 및 스케쥴 구하기 START */
        for($i=0; $i<=$period; $i++)
        {
            $schedule[$i]["date"] =  date("Y-m-d", strtotime("+".$i." days", time()));

            // 0:일, 1:월, 2:화, 3:수, 4:목, 5:금, 6:토
            $tmp_day_of_the_week = date("w",strtotime("+".$i." days", time()));

            // weekend_status - Y:주말, N:평일
            $schedule[$i]["weekend_status"] = ($tmp_day_of_the_week == "0" || $tmp_day_of_the_week == "6") ? "Y" : "N";
            // class_status - Y:수업가능, N:수업불가(공휴일, 회사휴일, 주말, 강사휴일)
            $schedule[$i]["class_status"] = ($schedule[$i]["weekend_status"] == "Y") ? "N" : "Y";


            /* 
                공휴일 및 회사휴일 체크
                - 공휴일이라도 정규수업가능
                : disabled_lesson 0:정규수업가능 , 1:정규수업불가
                - 주말은 정규수업 불가능
                : 벼락치기를 통해 수업은 가능하나 정규수업은 불가능
            */
            if($list_holiday)
            {
                for($lh_idx=0; $lh_idx<=sizeof($list_holiday); $lh_idx++)
                {
                    if($schedule[$i]["date"] == $list_holiday[$lh_idx]['holiday'])
                    {
                        // disabled_lesson 0:정규수업가능 , 1:정규수업불가
                        if($list_holiday[$lh_idx]['disabled_lesson'] == "1")
                        {
                            $schedule[$i]["class_status"] = "N";
                        }

                    }
                }
            }


            /* 강사휴일 - 수업 불가능한 날짜 체크 */
            if($tutor_blockdate)
            {
                for($tbd_idx=0; $tbd_idx<=sizeof($tutor_blockdate); $tbd_idx++)
                {
                    if($schedule[$i]["date"] >= $tutor_blockdate[$tbd_idx]['startday'] || $schedule[$i]["date"] <= $tutor_blockdate[$tbd_idx]['endday'])
                    {
                        // class_status - Y:수업가능, N:수업불가(공휴일, 회사휴일, 주말, 강사휴일)
                        $schedule[$i]["class_status"] = "N";
                    }
                }
            }
        
        
            /* 요일별 수업시간 체크 START */
            if($tutor_weekend)
            {
                /* 요일 체크 FOR문 START */
                for($tw_idx=0; $tw_idx<=sizeof($tutor_weekend); $tw_idx++)
                {
                    /* 요일 체크 START */
                    if($tmp_day_of_the_week == $tutor_weekend[$tw_idx]['week'])
                    {
                        /* 
                            수업시간(시간단위) 가능여부 체크  
                            - 시간단위
                            - 10분단위

                            START
                        */
                        for($h=0; $h<=24; $h++)
                        {
                            // DB 시간 컬럼명이랑 맞춤 t0, t1..., t24 
                            $tmp_col_hour = "t".$h;

                            //해당시간의 수업가능 여부 Y:수업가능, N:수업불가
                            $schedule[$i]["hourly_possible_status"][$tmp_col_hour]['status'] = [];
                            //해당시간의 10분단위 수업가능 여부 Y:수업가능, N:수업불가
                            $schedule[$i]["hourly_possible_status"][$tmp_col_hour]['time'] = [];
                        
                            
                            /*
                                class_status - Y:수업가능, N:수업불가(공휴일,회사휴일, 주말, 강사휴일)
                                주말이거나 강사휴일 일때는 시간별 수업가능시간 모두 불가처리 
                            */
                            $schedule[$i]["hourly_possible_status"][$tmp_col_hour]['status'] = ($schedule[$i]["class_status"] == "N") ? "N" : $tutor_weekend[$tw_idx][$tmp_col_hour];


                            /* 수업시간(10분단위 / 00분~50분) 가능여부 체크 START */
                            for($m=0; $m<=50; $m+=10)
                            {
                                // 시,분,초
                                $tmp_hour = common_zerofill($h, 2);
                                $tmp_min = common_zerofill($m, 2);
                                $tmp_sec = "00";
                                $tmp_time = $tmp_hour.$tmp_min.$tmp_sec;

                                /*
                                    ["hourly_possible_status"][$tmp_col_hour]['status'] - 해당시간대의 수업가능 여부 Y:수업가능, N:수업불가
                                    - 해당시간대가 수업불가 상태이면 하위 속성인 분도 모두 수업불가처리
                                    - 수업불가 상태가 아니라면 수업가능 처리
                                */
                                $schedule[$i]["hourly_possible_status"][$tmp_col_hour]['time'][$tmp_min] = ($schedule[$i]["hourly_possible_status"][$tmp_col_hour]['status'] == "N") ? "N" : "Y";


                                /* 강사 쉬는시간 - 수업 불가능한 시간  체크 */
                                if($tutor_breakingtime)
                                {    
                                    /* 휴식시간과 비교하기 위해 포맷맞춤 */
                                    $tmp_ymdhis = date( "Y-m-d H:i:s", strtotime($schedule[$i]["date"].$tmp_time));
                                

                                    for($tbt_idx=0; $tbt_idx<=sizeof($tutor_breakingtime); $tbt_idx++)
                                    {
                                        // 강사 휴식시간
                                        $tmp_breaktime_ymdhis = $tutor_breakingtime[$tbt_idx]['date']." ".$tutor_breakingtime[$tbt_idx]['time'];

                                        if($tmp_ymdhis == $tmp_breaktime_ymdhis)
                                        {
                                            //강사 휴식시간과 일치할 경우 
                                            $schedule[$i]["hourly_possible_status"][$tmp_col_hour]['time'][$tmp_min] = "N";
                                        
                                        }
                                    }
                                }

                                /* 강사 기존 스케쥴 체크 */
                                if($tutor_schedule)
                                {
                                    for($ts_idx=0; $ts_idx<=sizeof($tutor_schedule); $ts_idx++)
                                    {
                                        if($tutor_schedule[$ts_idx]["startday_date"] == $schedule[$i]["date"] || $tutor_schedule[$ts_idx]["endday_date"] == $schedule[$i]["date"])
                                        {
                                            if($tutor_schedule[$ts_idx]["startday_time"] <= $tmp_time && $tmp_time <= $tutor_schedule[$ts_idx]["endday_time"])
                                            {
                                                $schedule[$i]["hourly_possible_status"][$tmp_col_hour]['time'][$tmp_min] = "N";
                                            }

                                        }

                                    }   
                                }

                            }
                            /* 수업시간(10분단위 / 00분~50분) 가능여부 체크 END */
                            
                            /* 수업가능일이라도 브레이크 타임을 통해 모든 시간대를 휴식시간으로 했을경우 status값을 변경 */
                            $tmp_checked_time = $schedule[$i]["hourly_possible_status"][$tmp_col_hour]['time'];

                            if($schedule[$i]["hourly_possible_status"][$tmp_col_hour]['status'] == "Y" && $tmp_checked_time['00'] == "N" && $tmp_checked_time['10'] == "N" 
                                && $tmp_checked_time['20'] == "N" && $tmp_checked_time['30'] == "N" && $tmp_checked_time['40'] == "N" && $tmp_checked_time['50'] == "N")
                            {
                                $schedule[$i]["hourly_possible_status"][$tmp_col_hour]['status'] = "N";
                            } 

                        }
                        /* 수업시간(시간단위) 가능여부 체크 END */
                    }
                    /* 요일 체크 END */
                }
                /* 요일 체크 FOR문 END */
            }
            /* 요일별 수업시간 체크 END */

            
        }
        /* 현재일부터 조회기간 날짜 및 스케쥴 구하기 END */
        
        $return_array['res_code'] = '0000';
        $return_array['msg'] = "스케쥴조회성공";
        $return_array['data']['schedule'] = $schedule;
        echo json_encode($return_array);
        exit;
    
    
    }



}








