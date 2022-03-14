<?php
defined('BASEPATH') OR exit('No direct script access allowed');


/*
    공지사항 : mb_wiz_id 없음
*/

function board_list_writer($list_board , $search_content = NULL, $search_type = NULL, $wiz_member = NULL, $config = NULL)
{   
    //$knowledge_qna_type_board = ['1120','1102','1337','1141','express'];


    if(!$list_board) return null;
    
    $CI =& get_instance();
    $CI->load->model('board_mdl');

    $MBN_ANONYMOUS_YN =  $CI->config->item('MBN_ANONYMOUS_YN');
    $MBN_KNOWLEDGE_LIST =  $CI->config->item('MBN_KNOWLEDGE_LIST');
    
    $list_writer = NULL;
    $writer_wiz_ids = array();


    for($i=0; $i<sizeof($list_board); $i++)
    {
        //ex)본래 board_list_writer 는 리스트에서 호출하지만 뷰페이지여도 지식인 자식구성하는 곳에서 부르는경우가 있다
        if($config['make_thumb'] === true && array_key_exists('mb_thumb',$list_board[$i])) 
        {
            $list_board[$i]['mb_content'] = Thumbnail::replace_image_thumbnail($list_board[$i]['mb_content'],$list_board[$i]['mb_thumb'],'editor','pc');
            $list_board[$i]['mb_sim_content'] = Thumbnail::replace_image_thumbnail($list_board[$i]['mb_sim_content'],$list_board[$i]['mb_thumb'],'editor','pc');
        }

        if(array_key_exists('mb_thumb',$list_board[$i])) 
        {
        //$list_board[$i]['mb_content'] = Thumbnail::replace_image_thumbnail($list_board[$i]['mb_content'],$list_board[$i]['mb_thumb'],'editor','pc');
        //$list_board[$i]['mb_sim_content'] = Thumbnail::replace_image_thumbnail($list_board[$i]['mb_sim_content'],$list_board[$i]['mb_thumb'],'editor','pc');
            $list_board[$i]['thumbnail'] = Thumbnail::get_preview_img(
                                                $list_board[$i]['mb_thumb'],
                                                $list_board[$i]['mb_content'],
                                                $list_board[$i]['mb_filename'],
                                                Thumbnail::$cdn_default_url.(ISTESTMODE ? '/test_upload':'').'/attach/boards/'
                                            );
        }

        // 리스트는 대게 내용이 필요없기에 해당변수 넘겨받으면 content 삭제한다.
        if($config['content_del'] === true)
        {
            $list_board[$i]['mb_content'] = '';
        }

        if(isset($list_board[$i]['mb_wiz_id']) && $list_board[$i]['mb_wiz_id'] != '') 
        {
            array_push($writer_wiz_ids,"'".$list_board[$i]['mb_wiz_id']."'");
        }

        // 수업대본은 []<<대괄호 안에 있는 텍스트 추출하여 제목가공
        if($list_board[$i]['mb_table_code'] == 1130)
        {
            
            preg_match_all('/\[(.*)\]/Usim',$list_board[$i]['mb_title'],$title_parse);
            if($title_parse[1] && $title_parse[1][0])
            {
                $list_board[$i]['mb_title'] = '['.$title_parse[1][0]. ' 수업내용]';
                $list_board[$i]['class_tu_name'] = $title_parse[1][1];
                $list_board[$i]['class_book_name'] = $title_parse[1][2];
                $list_board[$i]['class_cl_tile'] = $title_parse[1][3];
            }
        }

        // 특수게시판 테이블 명칭 재가공
        if($list_board[$i]['mb_table_code'] > 9000 && $list_board[$i]['mbn_table_name'] =='')
        {
            $list_board[$i]['mbn_table_name'] = common_get_special_tablename($list_board[$i]['mb_table_code']);
            $list_board[$i]['mb_table_code'] = common_get_special_tablecode($list_board[$i]['mb_table_code']);
            $list_board[$i]['mb_noticeYn'] = $list_board[$i]['mb_noticeYn'] == 'n' ? 'N':$list_board[$i]['mb_noticeYn'];
        }
        

        // 공지글같은 경우 wiz_id 가 없어서 list_writer 조건에 걸려 진입 못하는 경우가 있어 여기서 display_name을 세팅해준다.
        $list_board[$i]['display_name'] = member_display_name(
            array(
                'nickname' => $list_board[$i]['mb_nickname'] ,
                'ename' => $list_board[$i]['mb_ename'],
                'name' => $list_board[$i]['mb_name'],
            )
        );
        
    }


    if(sizeof($writer_wiz_ids) > 0)
    {
        $where = "WHERE wm.wiz_id IN (".implode(",",array_filter($writer_wiz_ids)).")";
        $list_writer = $CI->board_mdl->list_writer($where);
    }

    if($list_writer)
    {
        for($i=0; $i<sizeof($list_board); $i++)
        {
            $list_board[$i]['wm_profile'] = "";
            $list_board[$i]['wm_birth'] = "";
            $list_board[$i]['wm_student_class'] = "S";
            $list_board[$i]['wm_grade'] = "";
            $list_board[$i]['wm_nickname'] = "";
            $list_board[$i]['mmg_title'] = "";
            $list_board[$i]['mmg_icon'] = "";
            //$list_board[$i]['mmg_description'] = "";
            $list_board[$i]['mmg_color'] = "";
            $list_board[$i]['mmg_bold'] = "";
            $list_board[$i]['mb_editor_file'] = "N";
            $list_board[$i]['mb_search_include'] = "";
            $list_board[$i]['icon'] ='';
            $list_board[$i]['icon_desc'] = '';

            /*
                새댓글 목록은 title이 없음
            */
            
            if(isset($list_board[$i]['mb_title']))
            {
                if($list_board[$i]['mb_table_code'] =='express')
                {
                    if($config['strip_tag'] !== false)
                    {
                        $list_board[$i]['mb_title'] = strip_tags($list_board[$i]['mb_title']);  //제목은 태그 삭제
                    }
                }
                else
                {
                    $list_board[$i]['mb_title'] = common_input_out($list_board[$i]['mb_title']);
                }
                
            }

            if(isset($list_board[$i]['mb_w_title']))
            {
                $list_board[$i]['mb_w_title'] = common_input_out($list_board[$i]['mb_w_title']);
            }
            /*
                본문용량 데이터가 커 속도 저하를 유발하여 삭제 
                에디터 본문중 파일 첨부 확인을 위해 가져온후 값 체크후 삭제
        
            if(isset($list_board[$i]['mb_content']))
            {
                if(strpos($list_board[$i]['mb_content'], "mint05.com/editor/") !== false 
                    || strpos($list_board[$i]['mb_content'], "http://upfiles.mint05.com/editor/") !== false)
                {
                    $list_board[$i]['mb_editor_file'] = "Y";
                }
                
                unset($list_board[$i]['mb_content']);
            }
            */

            /*
                본문내용중 HTML태그 제거
            */
            if($search_content)
            {
                /* 내용, 제목 자른값 저장 초기화 */
                $mb_elipsis_content = NULL;
                $mb_elipsis_title = NULL;

                /* 내용 스크립트 제거 */
                $list_board[$i]['mb_content'] = strip_tags($list_board[$i]['mb_content']);

                /*
                    검색 우선순위에 따라 자르기
                    - 1. 제목, 2.타이틀
                */
                if(isset($list_board[$i]['mb_title']))
                {
                    $mb_elipsis_title = common_search_ellipsis($list_board[$i]['mb_title'], $search_content, 10);
                }

                if(!$mb_elipsis_title && isset($list_board[$i]['mb_title']))
                {
                    $mb_elipsis_content = common_search_ellipsis($list_board[$i]['mb_content'], $search_content, 100);
                }
                
                /* 검색 우선순위에 따라 검색 결과가 있으면 해당값을 넣어줌 */
                if($mb_elipsis_title)
                {
                    $list_board[$i]['mb_title'] = $mb_elipsis_title;
                    $list_board[$i]['mb_search_include'] = "title";
                }
                else if($mb_elipsis_content)
                {
                    $list_board[$i]['mb_content'] = $mb_elipsis_content;
                    $list_board[$i]['mb_search_include'] = "content";
                }
            }
            else
            {
                /*
                본문내용중 HTML태그 제거
                */
                if(isset($list_board[$i]['mb_content']))
                {
                    // 지식인형태 게시판 child 제외한 게시물 태그 제거
                    if($config['strip_tag'] !== false)
                    {
                        $list_board[$i]['mb_content'] = strip_tags($list_board[$i]['mb_content']);
                    }
                
                    //$list_board[$i]['mb_sim_content'] = '';
                    // SELECT 할떄 content가 없었음. 지금 안쓰이는듯함.
                    // $list_board[$i]['mb_content'] = mb_substr(strstr($list_board[$i]['mb_content'], $search_content), 0 , 100,'utf-8');
                }
            }

            /*
             *  특수게시판 - 게시판 설정정보없음 
             */
            if(!isset($list_board[$i]['mbn_anonymous_yn']))
            {
                for($j=0; $j<sizeof($list_writer); $j++)
                {
                    if($list_board[$i]['mb_wiz_id'] == $list_writer[$j]['wm_wiz_id'])
                    {
                        /*
                            15세 미만이면 쥬니어 클래스
                        */

                        if(isset($list_writer[$i]['wm_birth']) && $list_writer[$i]['wm_birth'] && $list_writer[$i]['wm_birth'] != "--")
                        {
                            $birth_tmp = explode('-' ,$list_writer[$i]['wm_birth']);
                        
                            if(date("Y") - $birth_tmp[0] < 15)
                            {
                                $list_board[$i]['wm_student_class'] = "J";
                            }
                            else
                            {
                                $list_board[$i]['wm_student_class'] = "S";
                            }
                        }
                        
                        $list_board[$i]['wm_profile'] = $list_writer[$j]['wm_profile'];
                        $list_board[$i]['wm_birth'] = $list_writer[$j]['wm_birth'];
                        $list_board[$i]['wm_grade'] = $list_writer[$j]['wm_grade'];
                        $list_board[$i]['wm_nickname'] = $list_writer[$j]['wm_nickname'];
                        $list_board[$i]['wm_ename'] = $list_writer[$j]['wm_ename'];
                        $list_board[$i]['wm_name'] = $list_writer[$j]['wm_name'];
                        $list_board[$i]['mmg_title'] = $list_writer[$j]['mmg_title'];
                        $list_board[$i]['mmg_icon'] = $list_writer[$j]['mmg_icon'];
                        //$list_board[$i]['mmg_description'] = $list_writer[$j]['mmg_description'];
                        $list_board[$i]['mmg_color'] = $list_writer[$j]['mmg_color'];
                        $list_board[$i]['mmg_bold'] = $list_writer[$j]['mmg_bold'];
                        $list_board[$i]['wm_view_boards'] = $list_writer[$j]['wm_view_boards'];
                        $icon = member_get_icon($list_writer[$j]);
                        $list_board[$i]['icon'] = $icon['icon'];
                        $list_board[$i]['icon_desc'] = $icon['icon_desc'];
                        $display_name = member_display_name(
                            array(
                                'nickname' => $list_writer[$j]['wm_nickname'] ,
                                'ename' => $list_writer[$j]['wm_ename'],
                                'name' => $list_writer[$j]['wm_name'],
                            )
                        );
                        $list_board[$i]['display_name'] = $display_name ? $display_name:$list_board[$i]['display_name'];
                    }
                
                }
    
            }

            /*
            일반게시판(익명설정N) 회원정보 노출
            */
            if(isset($list_board[$i]['mbn_anonymous_yn']))
            {
                if($list_board[$i]['mbn_anonymous_yn'] == "N")
                {
                    for($j=0; $j<sizeof($list_writer); $j++)
                    {
                        if($list_board[$i]['mb_wiz_id'] == $list_writer[$j]['wm_wiz_id'])
                        {
                            /*
                                15세 미만이면 쥬니어 클래스
                            */
    
                            if(isset($list_writer[$i]['wm_birth']) && $list_writer[$i]['wm_birth'] != "--")
                            {
                                $birth_tmp = explode('-' ,$list_writer[$i]['wm_birth']);

                                if(date("Y") - $birth_tmp[0] < 15)
                                {
                                    $list_board[$i]['wm_student_class'] = "J";
                                }
                                else
                                {
                                    $list_board[$i]['wm_student_class'] = "S";
                                }
                            
                            }
                            $list_board[$i]['wm_profile'] = $list_writer[$j]['wm_profile'];
                            $list_board[$i]['wm_birth'] = $list_writer[$j]['wm_birth'];
                            $list_board[$i]['wm_grade'] = $list_writer[$j]['wm_grade'];
                            $list_board[$i]['wm_nickname'] = $list_writer[$j]['wm_nickname'];
                            $list_board[$i]['wm_ename'] = $list_writer[$j]['wm_ename'];
                            $list_board[$i]['wm_name'] = $list_writer[$j]['wm_name'];
                            $list_board[$i]['mmg_title'] = $list_writer[$j]['mmg_title'];
                            $list_board[$i]['mmg_icon'] = $list_writer[$j]['mmg_icon'];
                            //$list_board[$i]['mmg_description'] = $list_writer[$j]['mmg_description'];
                            $list_board[$i]['mmg_color'] = $list_writer[$j]['mmg_color'];
                            $list_board[$i]['mmg_bold'] = $list_writer[$j]['mmg_bold'];
                            $list_board[$i]['wm_view_boards'] = $list_writer[$j]['wm_view_boards'];
                            $icon = member_get_icon($list_writer[$j]);
                            $list_board[$i]['icon'] = $icon['icon'];
                            $list_board[$i]['icon_desc'] = $icon['icon_desc'];
                            $display_name = member_display_name(
                                array(
                                    'nickname' => $list_writer[$j]['wm_nickname'] ,
                                    'ename' => $list_writer[$j]['wm_ename'],
                                    'name' => $list_writer[$j]['wm_name'],
                                )
                            );
                            $list_board[$i]['display_name'] = $display_name ? $display_name:$list_board[$i]['display_name'];
                        }
                    
                    }
    
                }
    
            }
            $my_article = $wiz_member['wm_wiz_id'] && $wiz_member['wm_wiz_id'] == $list_board[$i]['mb_wiz_id'] ? 1:0;
            
            /*
                익명게시판(익명설정Y) 회원정보 비노출. 글쓴이가 회원 본인이라면 통과
            */
            if(isset($list_board[$i]['mbn_anonymous_yn']) && !$my_article)
            {
                if($list_board[$i]['mbn_anonymous_yn'] == "Y")
                {
                    $list_board[$i]['mb_wiz_id'] = '';
                    $list_board[$i]['mb_name'] = '';
                    $list_board[$i]['mb_nickname'] = '';
                    $list_board[$i]['wm_nickname'] = '';
                    $list_board[$i]['wm_birth'] = '';
                    $list_board[$i]['display_name'] = '';

                }
            }

            /*
                익명게시판 설정과는 별개로 하드코딩 되있던 것들 예외 처리. 글쓴이가 회원 본인이라면 통과
            */
            if(isset($list_board[$i]['mb_table_code']))
            {
                if(in_array($list_board[$i]["mb_table_code"], $MBN_ANONYMOUS_YN) && !$my_article)
                {
                    $list_board[$i]['mb_wiz_id'] = '';
                    $list_board[$i]['mb_name'] = '';
                    $list_board[$i]['mb_nickname'] = '';
                    $list_board[$i]['wm_nickname'] = '';
                    $list_board[$i]['wm_birth'] = '';
                    $list_board[$i]['display_name'] = '';
                }
            }
            
            
        }

    }
    else
    {
        for($i=0; $i<sizeof($list_board); $i++)
        {
            $list_board[$i]['wm_profile'] = "";
            $list_board[$i]['wm_birth'] = "";
            $list_board[$i]['wm_student_class'] = "";
            $list_board[$i]['wm_grade'] = "";
            $list_board[$i]['wm_nickname'] = "";
            $list_board[$i]['wm_ename'] = "";
            $list_board[$i]['wm_name'] = "";
            $list_board[$i]['mmg_title'] = "";
            $list_board[$i]['mmg_icon'] = "";
            //$list_board[$i]['mmg_description'] = "";
            $list_board[$i]['mmg_color'] = "";
            $list_board[$i]['mmg_bold'] = "";
            $list_board[$i]['mb_editor_file'] = "N";
            $list_board[$i]['mb_search_include'] = "";
            $list_board[$i]['icon'] = '';
            $list_board[$i]['icon_desc'] ='';
            $list_board[$i]['wm_view_boards'] = "N";
            /*
                새댓글 목록은 title이 없음
            */
            if(isset($list_board[$i]['mb_title']))
            {
                $list_board[$i]['mb_title'] = common_input_out($list_board[$i]['mb_title']);
            }

            if(isset($list_board[$i]['mb_w_title']))
            {
                $list_board[$i]['mb_w_title'] = common_input_out($list_board[$i]['mb_w_title']);
            }

            /*
                본문내용중 HTML태그 제거
            */
            if(isset($list_board[$i]['mb_content']) && $config['content_tag'] !== true)
            {
                $list_board[$i]['mb_content'] = strip_tags($list_board[$i]['mb_content']);

                if($search_content)
                {
                    $list_board[$i]['mb_content'] = mb_substr(strstr($list_board[$i]['mb_content'], $search_content), 0 , 100,'utf-8');
                }
                
            }

            // 민트사용설명서는 내용과 함께 전부 리스팅되기에 태그 보존
            if($config['content_tag'])
            {
                $list_board[$i]['mb_content'] = common_textarea_out($list_board[$i]['mb_content']);
                $n_match_array_before = array(
                    '../../daumeditor/',
                    'http://new.mint05.com/daumeditor/',
                    'http://www.youtube.com/embed',
                );
                $n_match_array_after = array(
                    Thumbnail::$cdn_default_url.'/editor/deco_img/daumeditor/',
                    Thumbnail::$cdn_default_url.'/editor/deco_img/daumeditor/',
                    'https://www.youtube.com/embed',
                );
                $list_board[$i]['mb_content'] = str_replace($n_match_array_before,$n_match_array_after,$list_board[$i]['mb_content']);

                if(array_key_exists('mb_thumb',$list_board[$i])) 
                {
                    $list_board[$i]['mb_content'] = Thumbnail::replace_image_thumbnail($list_board[$i]['mb_content'],$list_board[$i]['mb_thumb'],'editor','pc');
                }
            }

            /*
                전체검색시 검색키워드 본문포함 , 타이틀 포함 구분
            */
            if($search_content)
            {
                /* 내용, 제목 자른값 저장 초기화 */
                $mb_elipsis_content = NULL;
                $mb_elipsis_title = NULL;

                /* 내용 스크립트 제거 */
                $list_board[$i]['mb_content'] = strip_tags($list_board[$i]['mb_content']);

                /*
                    검색 우선순위에 따라 자르기
                    - 1. 제목, 2.타이틀
                */
                if(isset($list_board[$i]['mb_title']))
                {
                    $mb_elipsis_title = common_search_ellipsis($list_board[$i]['mb_title'], $search_content, 10);
                }

                if(!$mb_elipsis_title && isset($list_board[$i]['mb_title']))
                {
                    $mb_elipsis_content = common_search_ellipsis($list_board[$i]['mb_content'], $search_content, 100);
                }
                
                /* 검색 우선순위에 따라 검색 결과가 있으면 해당값을 넣어줌 */
                if($mb_elipsis_title)
                {
                    $list_board[$i]['mb_title'] = $mb_elipsis_title;
                    $list_board[$i]['mb_search_include'] = "title";
                }
                else if($mb_elipsis_content)
                {
                    $list_board[$i]['mb_content'] = $mb_elipsis_content;
                    $list_board[$i]['mb_search_include'] = "content";
                }
            }

            $my_article = $wiz_member['wm_wiz_id'] && $wiz_member['wm_wiz_id'] == $list_board[$i]['mb_wiz_id'] ? 1:0;

            if(isset($list_board[$i]['mbn_anonymous_yn'])  && !$my_article)
            {
                if($list_board[$i]['mbn_anonymous_yn'] == "Y")
                {
                    $list_board[$i]['mb_wiz_id'] = '';
                    $list_board[$i]['mb_ename'] = '';
                    $list_board[$i]['mb_name'] = '';
                    $list_board[$i]['mb_nickname'] = '';
                    $list_board[$i]['wm_nickname'] = '';
                    $list_board[$i]['wm_birth'] = '';
                    $list_board[$i]['display_name'] = '';
                    $list_board[$i]['wm_view_boards'] = "N";
                }
            }

            /*
                익명게시판 설정과는 별개로 하드코딩 되있던 것들 예외 처리
            */
            if(isset($list_board[$i]['mb_table_code']))
            {
                if(in_array($list_board[$i]["mb_table_code"], $MBN_ANONYMOUS_YN)  && !$my_article)
                {
                    $list_board[$i]['mb_wiz_id'] = '';
                    $list_board[$i]['mb_ename'] = '';
                    $list_board[$i]['mb_name'] = '';
                    $list_board[$i]['mb_nickname'] = '';
                    $list_board[$i]['wm_nickname'] = '';
                    $list_board[$i]['wm_birth'] = '';
                    $list_board[$i]['display_name'] = '';
                    $list_board[$i]['wm_view_boards'] = "N";
                }
            }
            /*
                본문용량 데이터가 커 속도 저하를 유발하여 삭제 
                에디터 본문중 파일 첨부 확인을 위해 가져온후 값 체크후 삭제

            if(isset($list_board[$i]['mb_content']))
            {
                if(strpos($list_board[$i]['mb_content'], "mint05.com/editor/") !== false 
                    || strpos($list_board[$i]['mb_content'], "http://upfiles.mint05.com/editor/") !== false)
                {
                    $list_board[$i]['mb_editor_file'] = "Y";
                }
                
                unset($list_board[$i]['mb_content']);
            }

            */

        }
    }

    return  $list_board;
}



function board_article_writer($article)
{
    $CI =& get_instance();
    $CI->load->model('board_mdl');

    $MBN_ANONYMOUS_YN =  $CI->config->item('MBN_ANONYMOUS_YN');
    

    $article_writer = NULL;
    $writer_wiz_ids = array();

    
    $article["wm_profile"] = "";
    $article["wm_birth"] = "";
    $article["wm_grade"] = "";
    $article["wm_nickname"] = "";
    $article["mmg_title"] = "";
    $article["mmg_icon"] = "";
    $article["mmg_color"] = "";
    $article["mmg_bold"] = "";
    

    if(array_key_exists('mb_thumb',$article)) 
    {
        $article['mb_content'] = Thumbnail::replace_image_thumbnail($article['mb_content'],$article['mb_thumb'],'editor','pc');
        $article['mb_sim_content'] = Thumbnail::replace_image_thumbnail($article['mb_sim_content'],$article['mb_thumb'],'editor','pc');
    }

    if(isset($article['mb_wiz_id']))
    {
        $where = "WHERE wm.wiz_id ='".$article['mb_wiz_id']."'";
        
        $article_writer = $CI->board_mdl->list_writer($where);

        $article['display_name'] = member_display_name(
            array(
                'nickname' => $article['mb_nickname'] ,
                'ename' => $article['mb_ename'],
                'name' => $article['mb_name'],
            )
        );

        if($article_writer)
        {
            $article["wm_uid"] = $article_writer[0]['wm_uid'];
            $article["wm_profile"] = $article_writer[0]['wm_profile'];
            $article["wm_birth"] = $article_writer[0]['wm_birth'];
            $article["wm_grade"] = $article_writer[0]['wm_grade'];
            $article["wm_nickname"] = $article_writer[0]['wm_nickname'];
            $article["wm_ename"] = $article_writer[0]['wm_ename'];
            $article["wm_name"] = $article_writer[0]['wm_name'];
            $article["wm_point"] = number_format($article_writer[0]['wm_point']);
            $article["wm_greeting"] = nl2br($article_writer[0]['wm_greeting']);
            $article["wm_age"] = $article_writer[0]['wm_age'];
            $article["mmg_title"] = $article_writer[0]['mmg_title'];
            $article["mmg_icon"] = $article_writer[0]['mmg_icon'];
            $article["mmg_description"] = $article_writer[0]['mmg_description'];
            $article["mmg_color"] = $article_writer[0]['mmg_color'];
            $article["mmg_bold"] = $article_writer[0]['mmg_bold'];
            $article["wm_view_boards"] = $article_writer[0]['wm_view_boards'];

            //트로피 정보
            $article['mq_title'] = $article_writer[0]['mq_title'];
            $article['mq_tropy_on'] = $article_writer[0]['mq_tropy_on'];

            $icon = member_get_icon($article_writer[0]);
            $article['icon'] = $icon['icon'];
            $article['icon_desc'] = $icon['icon_desc'];

            $display_name = member_display_name(
                array(
                    'nickname' => $article_writer[0]['wm_nickname'] ,
                    'ename' => $article_writer[0]['wm_ename'],
                    'name' => $article_writer[0]['wm_name'],
                )
            );

            $article['display_name'] = $display_name ? $display_name:$article['display_name'];

            if(isset($article_writer[0]['wm_birth']) && $article_writer[0]['wm_birth'] != "--")
            {
                // $birth_tmp = explode('-' ,$article_writer[0]['wm_birth']);

                $birth_tmp = substr($article_writer[0]['wm_birth'], 0, 4);

                if(date("Y") - $birth_tmp[0] < 15)
                {
                    $article["wm_student_class"] = $article_writer[0]['wm_student_class'] = "J";
                }
                else
                {
                    $article["wm_student_class"] = $article_writer[0]['wm_student_class'] = "S";
                }
            }

            //이런표헌어떻게는 제목만 있기때문에 content를 강제로 세팅해준다.
            if($article['mb_table_code'] =='express')
            {
                $article['mb_content'] = nl2br($article['mb_title']);       //에디터 없었을때가 있으므로 줄바꿈처리
                $article['mb_title'] = strip_tags($article['mb_title']);    //제목은 태그 삭제
            }
        }


        if(isset($article['mb_table_code']))
        {
            if(in_array($article["mb_table_code"], $MBN_ANONYMOUS_YN))
            {
                $article["wm_profile"] = "";
                $article["wm_birth"] = "";
                $article["wm_grade"] = "";
                $article["wm_nickname"] = "";
                $article["wm_ename"] = "";
                $article["wm_name"] = "";
                $article["mb_nickname"] = "";
                $article["mb_name"] = "";
                $article["mb_ename"] = "";
                $article["mmg_title"] = "";
                $article["mmg_icon"] = "";
                $article["mmg_color"] = "";
                $article["mmg_bold"] = "";
                $article['icon'] = '';
                $article['icon_desc'] = '';
                $article["wm_view_boards"] = "N";
                $article['display_name'] = '';
            }
        }
    }

    return $article;

}


function board_comment_writer($comment)
{
    $CI =& get_instance();
    $CI->load->model('board_mdl');

    $MBN_ANONYMOUS_YN =  $CI->config->item('MBN_ANONYMOUS_YN');
    
    $comment_writer = NULL;
    $writer_wiz_ids = array();

 
    for($i=0; $i<sizeof($comment); $i++)
    {
        if(isset($comment[$i]['mbc_wiz_id']) && $comment[$i]['mbc_wiz_id'] != '') 
        {
            array_push($writer_wiz_ids,"'".$comment[$i]['mbc_wiz_id']."'");
        }
        else if(isset($comment[$i]['mbc_writer_id']) && $comment[$i]['mbc_writer_id'] != '')
        {
            array_push($writer_wiz_ids,"'".$comment[$i]['mbc_writer_id']."'");
        }

        $comment[$i]['display_name'] = member_display_name(
            array(
                'nickname' => $comment[$i]['mbc_writer_nickname'] ,
                'ename' => $comment[$i]['mbc_writer_ename'],
                'name' => $comment[$i]['mbc_writer_name'],
            )
        );
    }
    
    if(sizeof($writer_wiz_ids) > 0)
    {
        $where = "WHERE wm.wiz_id IN (".implode(",",array_filter($writer_wiz_ids)).")";
        $comment_writer = $CI->board_mdl->list_writer($where);
    }

    if($comment_writer)
    {
        for($i=0; $i<sizeof($comment); $i++)
        {

            $comment[$i]['wm_profile'] = "";
            $comment[$i]['wm_birth'] = "";
            $comment[$i]['wm_grade'] = "";
            $comment[$i]['wm_nickname'] = "";
            $comment[$i]['wm_ename'] = "";
            $comment[$i]['wm_name'] = "";
            $comment[$i]['mmg_title'] = "";
            $comment[$i]['mmg_icon'] = "";
            $comment[$i]['mmg_color'] = "";
            $comment[$i]['mmg_bold'] = "";
            $comment[$i]['icon'] = "";
            $comment[$i]['icon_desc'] = "";
            $comment[$i]['wm_view_boards'] = "";
        
            if(isset($comment['mbc_table_code']))
            {
                if(in_array($comment[$i]["mbc_table_code"], $MBN_ANONYMOUS_YN))
                {
                    $comment[$i]["wm_profile"] = "";
                    $comment[$i]["wm_birth"] = "";
                    $comment[$i]["wm_grade"] = "";
                    $comment[$i]["wm_nickname"] = "";
                    $comment[$i]['wm_ename'] = "";
                    $comment[$i]['wm_name'] = "";
                    $comment[$i]["mmg_title"] = "";
                    $comment[$i]["mmg_icon"] = "";
                    $comment[$i]["mmg_color"] = "";
                    $comment[$i]["mmg_bold"] = "";
                    $comment[$i]['icon'] = "";
                    $comment[$i]['icon_desc'] = "";
                    $comment[$i]['wm_view_boards'] = "";
                    $comment[$i]['display_name'] = "";
                }
            }
            else
            {
                for($j=0; $j<sizeof($comment_writer); $j++)
                {
                    if(isset($comment[$i]['mbc_wiz_id']))
                    {
                        if($comment[$i]['mbc_wiz_id'] == $comment_writer[$j]['wm_wiz_id'])
                        {
                            $comment[$i]['wm_profile'] = $comment_writer[$j]['wm_profile'];
                            $comment[$i]['wm_birth'] = $comment_writer[$j]['wm_birth'];
                            $comment[$i]['wm_grade'] = $comment_writer[$j]['wm_grade'];
                            $comment[$i]['wm_nickname'] = $comment_writer[$j]['wm_nickname'];
                            $comment[$i]['wm_ename'] = $comment_writer[$j]['wm_ename'];
                            $comment[$i]['wm_name'] = $comment_writer[$j]['wm_name'];
                            $comment[$i]['mmg_title'] = $comment_writer[$j]['mmg_title'];
                            $comment[$i]['mmg_icon'] = $comment_writer[$j]['mmg_icon'];     
                            $comment[$i]['mmg_color'] = $comment_writer[$j]['mmg_color'];
                            $comment[$i]['mmg_bold'] = $comment_writer[$j]['mmg_bold'];
                            $comment[$i]['wm_view_boards'] = $comment_writer[$j]['wm_view_boards'];

                            $icon = member_get_icon($comment_writer[$j]);
                            $comment[$i]['icon'] = $icon['icon'];
                            $comment[$i]['icon_desc'] = $icon['icon_desc'];

                            $display_name = member_display_name(
                                array(
                                    'nickname' => $comment_writer[$j]['wm_nickname'] ,
                                    'ename' => $comment_writer[$j]['wm_ename'],
                                    'name' => $comment_writer[$j]['wm_name'],
                                )
                            );
                            $comment[$i]['display_name'] = $display_name ? $display_name:$comment[$i]['display_name'];
                        }
                    }
                    else if(isset($comment[$i]['mbc_writer_id']))
                    {
                        if($comment[$i]['mbc_writer_id'] == $comment_writer[$j]['wm_wiz_id'])
                        {
                            $comment[$i]['wm_profile'] = $comment_writer[$j]['wm_profile'];
                            $comment[$i]['wm_birth'] = $comment_writer[$j]['wm_birth'];
                            $comment[$i]['wm_grade'] = $comment_writer[$j]['wm_grade'];
                            $comment[$i]['wm_nickname'] = $comment_writer[$j]['wm_nickname'];
                            $comment[$i]['wm_ename'] = $comment_writer[$j]['wm_ename'];
                            $comment[$i]['wm_name'] = $comment_writer[$j]['wm_name'];
                            $comment[$i]['mmg_title'] = $comment_writer[$j]['mmg_title'];
                            $comment[$i]['mmg_icon'] = $comment_writer[$j]['mmg_icon'];     
                            $comment[$i]['mmg_color'] = $comment_writer[$j]['mmg_color'];
                            $comment[$i]['mmg_bold'] = $comment_writer[$j]['mmg_bold'];
                            $comment[$i]['wm_view_boards'] = $comment_writer[$j]['wm_view_boards'];
                            $icon = member_get_icon($comment_writer[$j]);
                            $comment[$i]['icon'] = $icon['icon'];
                            $comment[$i]['icon_desc'] = $icon['icon_desc'];

                            

                            $display_name = member_display_name(
                                array(
                                    'nickname' => $comment_writer[$j]['wm_nickname'] ,
                                    'ename' => $comment_writer[$j]['wm_ename'],
                                    'name' => $comment_writer[$j]['wm_name'],
                                )
                            );

                            $comment[$i]['display_name'] = $display_name ? $display_name:$comment[$i]['display_name'];
                        }
                    }
                
                }
    
            }


        }
    }

    return $comment;

}


function board_checked_best_article($table_code, $mb_unq)
{
    /*
        CLI API 호출
        비동기처리
    */
    $command = "php -f ".$_SERVER['DOCUMENT_ROOT']."/index.php _batch/checked_best_article \"".$table_code."\" \"".$mb_unq."\" > /dev/null 2>/dev/null &";

    exec($command);
}

/*
    mint_boards_total_rows 카운트 갱신
    트리거에 있었는데 비동기로 변경.
*/
function board_list_count_update()
{
    /*
        CLI API 호출
        비동기처리
    */
    $command = 'php -f '.$_SERVER['DOCUMENT_ROOT'].'/index.php _batch/board_list_count_update > /dev/null 2>/dev/null &';

    exec($command);
}

/*
    mint_boards_total_rows comment 카운트 갱신
    트리거에 있었는데 비동기로 변경
*/
function board_comment_list_count_update()
{
    /*
        CLI API 호출
        비동기처리
    */
    $command = 'php -f '.$_SERVER['DOCUMENT_ROOT'].'/index.php _batch/board_comment_list_count_update > /dev/null 2>/dev/null &';

    exec($command);
}


function board_checked_brilliant_article($table_code, $mb_unq, $board_config)
{
    $CI =& get_instance();
    $CI->load->model('board_mdl');

    $checked = $CI->board_mdl->checked_article_brilliant_copy($mb_unq);

    if(!$checked['cnt'])
    {
        $copy_article = $CI->board_mdl->row_copy_article($mb_unq);

        $MBN_ANONYMOUS_YN =  $CI->config->item('MBN_ANONYMOUS_YN');

        $anonymous = ($board_config['mbn_anonymous_yn'] == "Y" || in_array($table_code, $MBN_ANONYMOUS_YN)) ? "Y" : "N";
        $anonymous = $copy_article['name_hide'] =='Y' ? 'Y':$anonymous;

        /* 민트를 빛낸 회원 게시판으로 복사 */
        $copy = array(
            'sim_content' => $copy_article['sim_content'],
            'sim_content2' => $copy_article['sim_content2'],
            'sim_content3' => '/board_view.php?table_code='.$copy_article['table_code'].'&mb_unq='.$copy_article['mb_unq'],
            'sim_content4' => $copy_article['table_code'].",".$copy_article['mb_unq'],
            'table_code' => '1118',
            'wiz_id' => $copy_article['wiz_id'],
            'name' => $copy_article['name'],
            'ename' => $copy_article['ename'],
            'nickname' => $copy_article['nickname'],
            'title' => $copy_article['title'],
            'filename' => $copy_article['filename'],
            'editor_file' => $copy_article['editor_file'],
            'content' => $copy_article['content'],
            'input_txt' => $copy_article['input_txt'],
            'regdate' => date('Y-m-d H:i:s'),
            'secret' => $copy_article['secret'],
            'c_yn' => $copy_article['c_yn'],
            'pwd' => $copy_article['pwd'],
            'tu_uid' => $copy_article['tu_uid'],
            'daum_img' => $copy_article['daum_img'],
            'showdate' => $copy_article['showdate'],
            'name_hide' => $anonymous,
            'table_unq' => $board_config['mbn_unq'],
            'thumb' => $copy_article['thumb'],
        );

        $CI->board_mdl->write_article($copy);
    }
}

function board_article_title($comment)
{
    $CI =& get_instance();
    $CI->load->model('board_mdl');

    if($comment)
    {
        for($i=0; $i<sizeof($comment); $i++)
        {
            $mb_unq =  $comment[$i]['mb_unq'];
            $mb_table_code = $comment[$i]['mb_table_code'];
            $tmp_comment = $comment[$i]['comment'];
            
            $comment[$i]['comment'] = strip_tags(common_textarea_out(str_replace("&nbsp;"," ",$tmp_comment)));

            if($mb_table_code != 'dictation.list' && $mb_table_code != 'express')
            {
                /* 일반게시판 댓글 */
                $article = $CI->board_mdl->row_article_title_by_mb_unq($mb_unq);
            }
            else if($mb_table_code == 'express')
            {
                /* 이런표현어떻게 */ 
                $article = $CI->board_mdl->row_article_express_title_by_uid($mb_unq);
            }
            else if($mb_table_code == 'dictation.list')
            { 
                 /* 얼굴철판딕테이션 */
                $article = $CI->board_mdl->row_article_cafeboard_title_by_c_uid($mb_unq);
            }

            if($article)
            {
                $comment[$i]['mb_title'] = strip_tags($article['mb_title']);
                $comment[$i]['mbn_table_name'] = $article['mbn_table_name'];
                
            }
        }
    }

    return $comment;
}

/* 수업대본서비스 예외처리 */
function board_exception_1130($list_board)
{
    $CI =& get_instance();
    $CI->load->model('board_mdl');
    $CI->load->model('tutor_mdl');

    if($list_board)
    {
        for($i=0; $i<sizeof($list_board); $i++)
        {
            $mb_cafe_unq =  $list_board[$i]['mb_cafe_unq'];
            $mb_sim_content = $list_board[$i]['mb_sim_content'];
            $mb_sim_content2 = $list_board[$i]['mb_sim_content2'];
            $mb_tu_uid = $list_board[$i]['mb_tu_uid'];
        
            $article = $CI->board_mdl->get_1130_by_cafe_unq($mb_cafe_unq);
            $tutor = $CI->tutor_mdl->get_tu_name_by_tu_uid($mb_tu_uid);
            
            if($mb_cafe_unq) 
            {
                $list_board[$i]['mb_b_kind'] = $article['mb_b_kind'];
                $list_board[$i]['mb_vd_url'] = common_textarea_out($article['mb_vd_url']);
                $list_board[$i]['mb_tu_name'] = $tutor['tu_name'];
            } 
            else 
            {
                $sim_content2 = explode("__",$mb_sim_content2);
                $list_board[$i]['mb_b_kind'] = $sim_content2[0];
                $list_board[$i]['mb_vd_url'] = common_textarea_out($mb_sim_content);
                $list_board[$i]['mb_tu_name'] = $tutor['tu_name'];
            }
        }
    }

    return $list_board;
}

/* 예외처리 일일 도전 영작문. 해당글에 내가 댓글을 썻는지 체크 */
function board_exception_1127($list_board,$wm_wiz_id)
{
    $CI =& get_instance();
    $CI->load->model('board_mdl');

    if($list_board)
    {
        $mb_unq = array_column($list_board,'mb_mb_unq');
        $comm = $CI->board_mdl->check_write_mint_boards_comment_by_mb_unq($mb_unq,$wm_wiz_id);
        
        if($comm)
        {
            $comm_mb = array_column($comm,'mb_unq');
            $comm_mb = array_flip($comm_mb);

            foreach($list_board as $key=>$board)
            {
                $list_board[$key]['reply_exist'] = array_key_exists($board['mb_mb_unq'],$comm_mb) ? 1:0;
            }
        }
        
    }

    return $list_board;
}

function board_delete_board_edit_files($table_code, $mb_unq)
{
    /*
        CLI API 호출
        비동기처리
    */
    $command = 'php -f '.$_SERVER['DOCUMENT_ROOT'].'/index.php _batch/delete_board_edit_files "'.$table_code.'" "'.$mb_unq.'" > /dev/null 2>/dev/null &';

    exec($command);
}


/*
    이곳에서는 임시이미지저장 테이블에 삭제할 데이터 insert 만 시키고 
    실제 이미지 삭제는 임시이미지 삭제하는 크론에서 배치로 같이 삭제된다.
*/
function board_delete_files($filename, $s3path, $content='', $thumb=array())
{
    $CI = & get_instance();
    $CI->load->model('board_mdl');

    if($thumb)
    {
        // 폼파일의 섬네일 삭제
        if($thumb['form'])
        {
            foreach($thumb['form'] as $file_arr)
            {
                foreach($file_arr as $key=>$file)
                {
                    if($key == 'origin') continue;  // 첨부파일 원본은 밑에서 삭제하니 패스..
                    //S3::delete_s3_object(Thumbnail::$s3_thumbnail_loc, $file);
                    $file_info = array(
                        "file_name" => '',
                        "file_link" => Thumbnail::$s3_thumbnail_url . $file,
                        "file_status"=> 1,
                        'regdate' => date("Y-m-d H:i:s"),
                    );
                    $CI->board_mdl->insert_board_edit_files($file_info);
                }
            }
        }

        // 에디터의 섬네일 삭제
        if($thumb['editor'])
        {
            foreach($thumb['editor'] as $file_arr)
            {
                foreach($file_arr as $key=>$file)
                {
                    //S3::delete_s3_object(Thumbnail::$s3_thumbnail_loc, $file, $key == 'origin' ? $file:'');
                    $file_info = array(
                        "file_name" => '',
                        "file_link" => $key == 'origin' ? $file:(Thumbnail::$s3_thumbnail_url . $file),
                        "file_status"=> 1,
                        'regdate' => date("Y-m-d H:i:s"),
                    );
                    $CI->board_mdl->insert_board_edit_files($file_info);
                }
            }
        }

    }

    // 첨부파일 원본 삭제
    if($filename && $s3path)
    {
        //S3::delete_s3_object($s3path, $filename);
        $file_info = array(
            "file_name" => '',
            "file_link" => $s3path . $filename,
            "file_status"=> 1,
            'regdate' => date("Y-m-d H:i:s"),
        );
        $CI->board_mdl->insert_board_edit_files($file_info);
    }

    // content에 s3 이미지경로 들어있으면 삭제
    if($content)
    {
        $matches = common_find_s3_src_from_content($content);
        
        /*
            Array(
                [0] => Array
                    (
                        [0] => <img src="https://cdn.mintspeaking.com/assets/icon/exam/icon_exam_notice.png"
                    )
                [1] => Array
                    (
                        [0] => https://cdn.mintspeaking.com/assets/icon/exam/icon_exam_notice.png
                    )
            )
        */
        
        // 섬네일이 없는 경우 {{0}} 같은 임의변수화 처리가 되지 않아서 여기로 들어올 수 있음. 찾아서 삭제
        if(count($matches[1])> 0)
        {
            foreach($matches[1] as $match)
            {
                /* $uri = str_replace(Thumbnail::$cdn_default_url.'/','',$match);
                $uri_arr = explode('/',$uri);
                $filename = $uri_arr[count($uri_arr)-1];
                $path = str_replace($filename,'',$uri); */

                //S3::delete_s3_object($path, $filename);

                $file_info = array(
                    "file_name" => '',
                    "file_link" => $match,
                    "file_status"=> 1,
                    'regdate' => date("Y-m-d H:i:s"),
                );
                $CI->board_mdl->insert_board_edit_files($file_info);
                
            }
        }
    }
    
}


/*
    글쓰기 권한 체크
    $config : 권한 체크 하기 위해 임의의 값이 필요할때 넘겨받아 사용
*/
function board_check_valid_write_page($table_code, $wiz_member, $board_config=array(), $config=array())
{
    if($table_code =='request' || $table_code =='custom' || $table_code == 'toteacher') return array();

    $CI = & get_instance();
    $CI->load->model('board_mdl');
    $CI->load->model('book_mdl');
    $CI->load->model('lesson_mdl');
    $CI->load->model('point_mdl');
    $today = date('Y-m-d');

    $var_conf = board_knowledge_var_conf($table_code);

    $limit_oneday_count_parent = $var_conf['limit_oneday_count_parent'];
    $limit_oneday_count_child  = $var_conf['limit_oneday_count_child'];
    $limit_reply_count_solver = $var_conf['limit_reply_count_solver'];

    /*
        회원 블랙리스트 여부
        - 블랙리스트 회원은 포인트 수업 변환 불가
        - blacklist 
            :NULL: 차단, 차단해제 이력없음 
            :NULL이 아닌경우 : 차단, 차단해제 이력있음
        - kind : (Y: 블랙리스트 등록, N: 블랙리스트 해제)
    */
    $CI->load->model('member_mdl');
    $blacklist = $CI->member_mdl->blacklist_by_wm_uid($wiz_member['wm_uid']);

    if($blacklist)
    {
        if($blacklist['kind'] == "Y")
        {
            return array(
                'err_code' => '0360',
                'err_msg' => '글쓰기 권한이 없습니다. 고객센터 실시간요청게시판으로 문의하세요.',
            );
        }
    }

    /*
        특수게시판 체크
        english_article : 영자신문 테이블코드(영자신문 해석 api 권한체크)
    */
    if($table_code == 'express' || $table_code == 'dictation' || $table_code == 'correction' || $table_code == 'english_article')
    {
        if($table_code == 'express')
        {
            // 2021-01-13 이기범과장님 요청으로 in class만 체크하여 글쓰기 허용으로 변경
            $checkwhere = " AND lesson_state in ('in class')";
            $check_valid_class_member = $CI->lesson_mdl->check_in_class_member($wiz_member['wm_uid'], $checkwhere);
            
            if(!$check_valid_class_member)
            {
                return array(
                    'err_code' => '0313',
                    'err_msg' => '해당 게시판의 글쓰기 권한이 없습니다.(수업 중인 회원만 접근할 수 있습니다.)<br><br>
                    수업 중이 아닌 경우에는 아래 게시판만 이용 가능합니다.<br>
                    고객센터>실시간요청게시판<br>
                    커뮤니티>영어고민&권태기상담',
                );
            }

            // 지식인 게시판 답변글 횟수 체크
            if($config['parent_key'])
            {
                //질문글에 1회 답변가능
                $isset_board_solve = $CI->board_mdl->checked_knowledge_article_anwsered_express($config['parent_key'], $wiz_member['wm_wiz_id']);
                if($isset_board_solve)
                {
                    return array(
                        'err_code' => '0206',
                        'err_msg' => '이미 답변을 했습니다.',
                    );
                }

                $addwhere = ' AND mb.parent_key IS NOT NULL';
                $checked_count_today = $CI->board_mdl->checked_count_today_write_article_express($wiz_member['wm_wiz_id'], $today, $addwhere);
                if($checked_count_today['cnt'] >= $limit_oneday_count_child)
                {
                    return array(
                        'err_code' => '0207',
                        'err_msg' => '답변글은 1일 '.$limit_oneday_count_child.' 회까지 가능합니다',
                    );
                }

                //질문글에 $limit_reply_count_solver회까지 답변글 달수있다.
                $reply_board_count = $CI->board_mdl->list_count_board_express(" WHERE mb.parent_key = '".$config['parent_key']."'");

                if($reply_board_count['cnt'] >= $limit_reply_count_solver)
                {
                    return array(
                        'err_code' => '0344',
                        'err_msg' => '답변은 질문글 당 '.$limit_reply_count_solver.' 개 까지만 등록 가능합니다.',
                    );
                }
            }
            // 지식인 게시판 질문글 횟수 체크
            else
            {
                $addwhere = ' AND mb.parent_key IS NULL';
                $checked_count_today = $CI->board_mdl->checked_count_today_write_article_express($wiz_member['wm_wiz_id'], $today, $addwhere);
                if($checked_count_today['cnt'] >= $limit_oneday_count_parent)
                {
                    return array(
                        'err_code' => '0314',
                        'err_msg' => '이런표현어떻게 게시판은 1일 1회 작성가능합니다.',
                    );
                }
            }


        }
        elseif($table_code == 'correction')
        {
            $checkwhere = " AND tu_name != 'postpone' AND (lesson_state='in class' || lesson_state='finished') AND startday<='".$today."' AND endday >= '".$today."'";
            $check_valid_class_cnt = $CI->lesson_mdl->check_in_class_member($wiz_member['wm_uid'],$checkwhere);
            if(!$check_valid_class_cnt)
            {
                return array(
                    'err_code' => '0313',
                    'err_msg' => '해당 게시판의 글쓰기 권한이 없습니다.(수업 중인 회원만 접근할 수 있습니다.)<br><br>
                    수업 중이 아닌 경우에는 아래 게시판만 이용 가능합니다.<br>
                    고객센터>실시간요청게시판<br>
                    커뮤니티>영어고민&권태기상담',
                );
            }

            $where = " WHERE mb.uid= ".$wiz_member['wm_uid']." AND mb.w_regdate BETWEEN '".$today." 00:00:00' AND '".$today." 23:59:59' ";
            $list_count_board_wiz_correct = $CI->board_mdl->list_count_board_wiz_correct($where);
            $list_count_board_wiz_correct = $list_count_board_wiz_correct ? $list_count_board_wiz_correct['cnt']:0;

            // 출석부 갯수만큼 무료이용가능. 이후는 5천포인트소모. 이때 포인트 부족한지 체크
            if($list_count_board_wiz_correct >= $check_valid_class_cnt)
            {
                $cur_point = $CI->point_mdl->check_current_point($wiz_member['wm_uid']);
                if($cur_point < 5000)
                {
                    return array(
                        'err_code' => '0328',
                        'err_msg' => '포인트가 부족합니다',
                        'write_over' => 1,
                    );
                }
                else
                {
                    return array(
                        'write_over' => 1,      // 해당값이 리턴되면 글작성 시 5천포인트 소모될것이라고 문구띄워줘야함
                    );
                }
                
            }
        }
        elseif($table_code == 'dictation')
        {
            if(!$config['sc_id'])
            {
                return array(
                    'err_code' => '0336',
                    'err_msg'  => '일치하는 수업 정보를 찾을수 없습니다.',
                );
            }

            // 100회 단위 얼철딕작성 후 승인안된 얼철딕 후기있는지 체크
            $check_where = " WHERE mca.uid = '".$wiz_member['wm_uid']."' AND mca.approval = 'N' ";
            $approval_cafaboard = $CI->board_mdl->checked_approval_cafaboard($check_where);
            if($approval_cafaboard)
            {
                // 후기를 작성한 상태라면
                if($CI->board_mdl->checked_approval_cafaboard_waiting($wiz_member['wm_wiz_id'],$approval_cafaboard['mca_created_at']))
                {
                    return array(
                        'err_code' => '0332',
                        'err_msg'  => '얼철딕후기 승인 대기중인 상태입니다.',
                    );
                }
                else
                {
                    return array(
                        'err_code'  => '0333',
                        'err_msg'   => '얼철딕후기를 작성한 후 진행가능합니다.',
                        'href'      => '/#/board-write?tc=1111&cafe=true',
                    );
                }
            }
            
            // 출석부 갯수
            $checkwhere = " AND tu_name != 'postpone' AND (lesson_state='in class' || lesson_state='finished') AND startday<='".$today."' AND endday >= '".$today."'";
            $check_valid_class_cnt = $CI->lesson_mdl->check_in_class_member($wiz_member['wm_uid'],$checkwhere);
            $check_valid_class_cnt = $check_valid_class_cnt ? $check_valid_class_cnt:0;

            // 금일 작성한 얼철딕 갯수 체크
            $checkwhere = " WHERE mb.uid = ".$wiz_member['wm_uid']." AND mb.regdate BETWEEN '".$today." 00:00:00' AND '".$today." 23:59:59' ";
            $cafeboard_count = $CI->board_mdl->list_count_board_cafeboard($checkwhere);
            $cafeboard_count = $cafeboard_count ? $cafeboard_count['cnt']:0;

            if ($check_valid_class_cnt <= $cafeboard_count) 
            {
                return array(
                    'err_code' => '0334',
                    'err_msg'  => '하루에 진행 중인 출석부 갯수만큼만 얼철딕 참여가 가능합니다.',
                );
            }

            
            if(!$board_config) $board_config = $CI->board_mdl->row_board_special_config_by_table_code(9002);
            
            // 얼철딕 하루 작성 수 제한. 2020-10-16 DB데이터 기준 cafe_day_limit 값이 0이라 실질적으론 사용안함
            if($board_config['mbn_cafe_day_limit'] >= 1)
            {
                if($cafeboard_count >= $board_config['mbn_cafe_day_limit']) 
                { 
                    return array(
                        'err_code' => '0335',
                        'err_msg'  => '하루에 '.$board_config['mbn_cafe_day_limit'].'번만 글등록이 가능합니다.',
                    );
                }
            }

            // 수업 검증 START
            $SC = $CI->lesson_mdl->row_schedule_by_sc_id($config['sc_id'],$wiz_member['wm_uid']);
            if(!$SC) 
            {
                return array(
                    'err_code' => '0336',
                    'err_msg'  => '일치하는 수업 정보를 찾을수 없습니다.(2)',
                );
            }

            if($SC['present'] != '2')
            {
                return array(
                    'err_code' => '0337',
                    'err_msg'  => '출석한 수업만 가능합니다.',
                );
            }
        
            if(substr($SC['startday'], 0, 10) <= date('Y-m-d', strtotime('-31 days')) )
            {
                return array(
                    'err_code' => '0338',
                    'err_msg'  => '1개월 이내의 수업만 가능합니다.',
                );
            }
        
            $where = ' uid = '.$wiz_member['wm_uid'].' AND schedule_id = '.$config['sc_id'].' AND table_code = 9002';
            $check = $CI->board_mdl->checked_wiz_schedule_board_pivot($where);
            if($check)
            {
                return array(
                    'err_code' => '0339',
                    'err_msg'  => '이미 얼철딕을 한 수업입니다.',
                );
            }

            $where = ' uid = '.$wiz_member['wm_uid'].' AND schedule_id = '.$config['sc_id'].' AND table_code = 1130';
            $check = $CI->board_mdl->checked_wiz_schedule_board_pivot($where);
            if($check)
            {
                return array(
                    'err_code' => '0340',
                    'err_msg'  => '1개월 이내의 수업만 가능합니다.',
                );
            }

            return array(
                'schedule' => $SC,
            );
        }   //얼철딕 검증 END 
        
        // 영자신문 해석하기 
        else if($table_code == 'english_article')
        {
            $checkwhere = " AND lesson_state in ('in class')";
            $check_valid_class_member = $CI->lesson_mdl->check_in_class_member($wiz_member['wm_uid'], $checkwhere);
            
            if(!$check_valid_class_member)
            {
                return array(
                    'err_code' => '0313',
                    'err_msg' => '영자신문 글쓰기 권한이 없습니다.(수업 중인 회원만 접근할 수 있습니다.)',
                );
            }
        }
    }
    else    
    {
        // 홈페이지 노출게시판이 아니라면 접근불가.
        if(!(($table_code >= 1100 && $table_code <= 1199) || ($table_code >= 1300 && $table_code <= 1399)))
        {
            return array(
                'err_code' => '0313',
                'err_msg' => '권한이 없습니다.',
            );
        }

        // 일반게시판 체크
        if(!$board_config)
        {
            $board_config = $CI->board_mdl->row_board_config_by_table_code($table_code);
        }

        //딕테이션 질문글 권한 체크
        if($table_code == '1138' && $config['co_unq'])
        {

            $checked_board = $CI->board_mdl->checked_1138_first_board($config['co_unq']);

            if($checked_board['cnt'] > 0)
            {
                return array(
                    'err_code' => '0346',
                    'err_msg' => '해당 글은 이미 딕테이션 해결사를 요청하였습니다.',
                );
            }

        }
        if($table_code == '1138' && $config['parent_key'])
        {
            // 딕테이션 해결사 권한 or 게시판 지기 권한 체크 변수
            $is_auth_solve = "N";

            $type = 'Dictation';
            $type2 = 'Helper';

            $badge_solver = member_checked_badge($wiz_member['wm_uid'], $type, $type2);
            
            if($badge_solver)
            {
                $is_auth_solve ="Y";
            }
            
            //딕테이션 해결사 solver로 가칭 등록(게시판 지기 활동여부)
            if(false !== stripos($wiz_member['wm_assistant_code'], "*solver*"))
            {
                $is_auth_solve ="Y";
            }

            if($is_auth_solve == 'N')
            {
                return array(
                    'err_code' => '0345',
                    'err_msg' => '딕테이션 해결사 답변은 얼굴철판딕테이션 100회를 달성한 회원분만 등록할 수 있습니다.
                    얼굴철판딕테이션 100회 달성 후 많은 참여 바랍니다.',
                );
            }
        }

        if(in_array($table_code,$config['knowledge_qna_type_board']))
        {
            // 지식인 게시판 답변글 횟수 체크
            if($config['parent_key'])
            {
                //질문글에 1회 답변가능
                $isset_board_solve = $CI->board_mdl->checked_knowledge_article_anwsered($table_code, $config['parent_key'], $wiz_member['wm_wiz_id']);
                if($isset_board_solve)
                {
                    return array(
                        'err_code' => '0206',
                        'err_msg' => '이미 '.$board_config['mbn_table_name'].'에 답변을 했습니다.',
                    );
                }

                $wrote_board_count_child = $CI->board_mdl->checked_count_today_write_1138_child($wiz_member['wm_wiz_id'], $table_code, $today);

                if($wrote_board_count_child['cnt'] >= $limit_oneday_count_child)
                {
                    return array(
                        'err_code' => '0207',
                        'err_msg' => $board_config['mbn_table_name'].' 답변글은 1일 '.$limit_oneday_count_child.' 회까지 가능합니다',
                    );
                }

                //질문글에 $limit_reply_count_solver회까지 답변글 달수있다.
                $reply_board_count = $CI->board_mdl->list_count_board('', " WHERE mb.parent_key = '".$config['parent_key']."'");

                if($reply_board_count['cnt'] >= $limit_reply_count_solver)
                {
                    return array(
                        'err_code' => '0344',
                        'err_msg' => $board_config['mbn_table_name'].'의 답변은 질문글 당 '.$limit_reply_count_solver.' 개 까지만 등록 가능합니다.',
                    );
                }
            }
            // 지식인 게시판 질문글 횟수 체크
            else
            {
                $wrote_board_count_parent = $CI->board_mdl->checked_count_today_write_1138_parent($wiz_member['wm_wiz_id'], $table_code, $today);

                if($wrote_board_count_parent['cnt'] >= $limit_oneday_count_parent)
                {
                    return array(
                        'err_code' => '0208',
                        'err_msg' => $board_config['mbn_table_name'].' 의뢰글은 1일 '.$limit_oneday_count_parent.' 회까지 가능합니다',
                    );
                }
            }

        }
        // 지식인 게시판은 위 if에서 따로 체크한다
        elseif($board_config['mbn_limit_yn'] =='Y')
        {
            $checked_count_today = $CI->board_mdl->checked_count_today_write_article($wiz_member['wm_wiz_id'], $table_code, $today);
            if($checked_count_today['cnt'] > 0)
            {
                return array(
                    'err_code' => '0315',
                    'err_msg' => '1일 1회 글쓰기 가능한 게시판입니다.',
                );
            }
        }
        
        //영어권태기/고민상담 5일 1회 글작성 가능
        /* if($table_code == '1337')
        {
            $checked_count = $CI->board_mdl->checked_count_day_write_article($wiz_member['wm_wiz_id'], $table_code, 4);
            if($checked_count['cnt'] > 0)
            {
                return array(
                    'err_code' => '0315',
                    'err_msg' => '영어고민&권태기상담 게시판은 5일에 1회 글쓰기 가능한 게시판입니다.',
                );
            }
        } */
        //민트건의&비판 게시판 글 5일에 1번씩만
        if($table_code == '1381')
        {
            $checked_count = $CI->board_mdl->checked_count_day_write_article($wiz_member['wm_wiz_id'], $table_code, 4);
            if($checked_count['cnt'] > 0)
            {
                return array(
                    'err_code' => '0315',
                    'err_msg' => '민트건의&비판 게시판은 5일에 1회 글쓰기 가능한 게시판입니다.',
                );
            }
        }
        //
        else if($table_code == '1130')
        {
            /*
            1130: 수업대본 서비스
            mbn_write_yn -> 구민트/신민트 컬럼 사용 용도가 다름
            신민트 -> mbn_write_yn == Y 는 글쓰기 버튼 노출 여부
            구민트 -> mbn_write_yn == Y 는 API에서 글쓰기 등록 yn 여부            
            지금은 1130 을 mbn_write_yn == N로 변경시키면 신민트에서는 글쓰기 버튼이 노출됨.
            추후에 게시판 전부 신민트로 바꾸고 안쓰는 게시판 board_config 한번에 수정해야함
            */
            
        }
        // 1133: 미국vs영국vs필리핀
        else if($table_code == '1133' && strtoupper($board_config['mbn_write_yn']) != "Y")   
        {
            return array(
                'err_code' => '0301',
                'err_msg' => $board_config["mbn_table_name"]." 게시판은 글쓰기가 제한되어 있습니다.",
            );
        }


        $age = "16";
        if(substr($wiz_member['wm_birth'],0,4) != '')
        {
            $age = date("Y") - substr($wiz_member['wm_birth'],0,4);
        } 
        else if(substr($wiz_member['wm_jumin1'],0,4) != '')
        {
            $age = date("Y") - substr($wiz_member['wm_jumin1'],0,4);
        } 

        if(($table_code == '1340' || $table_code == '1350') && $age <= 15)
        {
            return array(
                'err_code' => '0316',
                'err_msg' => $board_config["mbn_table_name"]." 게시판은 시니어 회원님만 사용가능합니다.\\n15세 이하인 주니어 회원께서는 [이야기]주니어모임방에 글을 남겨주시면 500 포인트를 선물로 적립 해 드립니다.",
            );
        }
        else if($table_code == '1353' && $age > 15)
        {
            return array(
                'err_code' => '0317',
                'err_msg' => '나이가 15세 이하인 경우에만 글 등록이 가능한 게시판입니다.',
            );
        }

        // 교육 과정과 상관없이 수업중일때만(수업중: in class, 장기휴재: holding) 글쓸수 있는 게시판에는 회원이 수업중인지 체크
        if($board_config['mbn_write_yn_inclass'] =='Y')
        {
            // 2021-01-13 이기범과장님 요청으로 in class, holding 체크에서 in class만 체크로 변경
            $checkwhere = " AND lesson_state in ('in class')";
            $check_valid_class_member = $CI->lesson_mdl->check_in_class_member($wiz_member['wm_uid'], $checkwhere);
            
            if(!$check_valid_class_member)
            {
                return array(
                    'err_code' => '0313',
                    'err_msg' => '해당 게시판의 글쓰기 권한이 없습니다.(수업 중인 회원만 접근할 수 있습니다.) <br><br>
                    수업 중이 아닌 경우에는 아래 게시판만 이용 가능합니다.<br>
                    고객센터>실시간요청게시판<br>
                    커뮤니티>영어고민&권태기상담',
                );
            }
        }
        
        //  해당 교육과정을 듣고 있는지 체크(글쓰기 버튼으로 접근시)
        //  1354: NS과제물 게시판
        if($table_code == '1354')   
        {
            $datas = array();

            $now = date('Y-m-d');
            $index = null;
            // 해당일 1354 테이블 코드에 글쓴 횟수 조회
            $where = " WHERE table_code = '".$table_code."' AND wiz_id = '".$wiz_member['wm_wiz_id']."' AND date_format(regdate,'%Y-%m-%d') = '".$now."'";
            $CI->load->model('board_mdl');
            $mint_boards_1354 = $CI->board_mdl->list_count_board($index, $where);
            
            if($mint_boards_1354['cnt'] > 0)
            {
                $datas['write_over'] = 1;
            }

            //390 (NS 수업 관련 교재)로 수업했던 이력이 있는지 조회
            $where = " WHERE wb.f_id = '390' AND wl.uid = '".$wiz_member['wm_uid']."'";
            $check_valid_write_article = $CI->book_mdl->check_in_class_write_article($where);

            if(!$check_valid_write_article)
            {
                $datas['err_code'] = '0320';
                $datas['err_msg'] =  $board_config["mbn_table_name"].'은 NS수업을 수강했던 회원만 작성 가능합니다.';

                return $datas;
            }

            // 현재 수업중인지 조회
            $where = " WHERE wl.lesson_state = 'in class' AND wl.uid = '".$wiz_member['wm_uid']."'";
            $in_class = $CI->lesson_mdl->list_count_in_class($where);

            if($in_class['cnt'] < 1)
            {
                $datas['err_code'] = '0321';
                $datas['err_msg'] =  $board_config["mbn_table_name"].'은 수업 중인 회원만 작성 가능합니다.';

                return $datas;
            }

            return $datas;
        }
    }

    return array();
    
}

function board_get_point($board_id, $uid, $type = 'N')
{
    $CI = & get_instance();
    $CI->load->model('board_mdl');

    if($board_id)
    {
        $mca_where = " WHERE mca.uid = '{$uid}' AND mca.board_id = '{$board_id}' AND mca.approval = '{$type}'";
    }
    else
    {
        $mca_where = " WHERE mca.uid = '{$uid}' AND mca.approval = 'N' ORDER BY mca.id DESC LIMIT 1";
    }

    $approval = $CI->board_mdl->checked_approval_cafaboard($mca_where);
    if(!$approval['mca_id'])
    {
        return false;
    }

    $mca_where2 = " WHERE mca.uid = '{$uid}' AND mca.created_at <= '{$approval['mca_created_at']}'
                    ORDER BY mca.created_at DESC LIMIT 2 ";
    $query = $CI->board_mdl->checked_approval_cafaboard_result_array($mca_where2);

    $cafeIds = array();
    $startDate = null;
    $endDate = null;
    if(count($query) == 1)
    {
        $end = $query[0];
        $endDate =  $end['mca_created_at'];
    }
    else if(count($query) == 2)
    {
        $end = $query[0];
        $start = $query[1];

        $startDate = $start['mca_created_at'];
        $endDate =  $end['mca_created_at'];
    }
    else
    {
        return false;
    }

    $wsbp_where = " wsbp.uid = '{$uid}' AND wsbp.table_code = '9002' AND wsbp.created_at <= '{$endDate}' ";
    if($startDate)
    {
        $wsbp_where .= " AND wsbp.created_at > '{$startDate}' ";
    }
    $wsbp_list = $CI->board_mdl->list_wiz_schedule_board_pivot($wsbp_where);

    $cafeIds = [];
    foreach($wsbp_list as $row)
    {
        $cafeIds[] = $row['wsbp_board_id'];
    }

    $CI->load->model('point_mdl');
    $result = $CI->point_mdl->checked_point_by_co_unq($uid,$cafeIds);

    return $result ? (int)$result['point']:0;
}

function board_make_ahop_pre_content($wiz_member,$ex_no='',$ex_id='')
{
    if($ex_no =='' && $ex_id =='') return '';

    $CI = & get_instance();
    $CI->load->model('lesson_mdl');
    $CI->load->model('book_mdl');

    $uid = $wiz_member['wm_uid'];

    //$uid = 63110;
    $ahop_lesson = $CI->lesson_mdl->check_ahop_lesson_by_uid($uid);

    if(!$ahop_lesson) return '';

    $pre_content = "<B>1. AHOP과정을 시작한 이유</B><br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
<B>2. 내가 생각하는 AHOP과정</B><br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
<B>3. AHOP과정 중 가장 힘들었던 기억</B><br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
<B>4. AHOP과정 중 가장 행복했던 기억</B><br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
<B>5. AHOP과정 합격자로서의 조언</B><br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
<B>6. 기타 하고싶은 말 또는 개선사항</B><br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;";

    # 시험 정보 확인 / 반영
    if($ex_no)
    {
        $checkExams = $CI->book_mdl->check_exam_log_by_ex_no($uid,$ex_no);
    }
    else
    {
        $checkExams = $CI->book_mdl->check_exam_log_by_ex_id($uid,$ex_id);
    }

    $reportTitle = "";
    $typeC = explode(" ", $checkExams['book_name']);
    if(trim($typeC[2]) == "Math"){ 		$cateFix = "127"; $reportTitle = "Math"; $tileName = "Math"; }
    if(trim($typeC[2]) == "Science"){ 	$cateFix = "128"; $reportTitle = "Science"; $tileName = "Science";  }
    if(trim($typeC[2]) == "Social"){ 	$cateFix = "129"; $reportTitle = "Social Studies"; $tileName = "Social";  }
    $title = $checkExams['book_name']." 시험 후기";

    $pre_content .= '
    <img src="'.Thumbnail::$cdn_default_url.'/assets/icon/exam/icon_exam_notice.png" style="border:0px;"><span style="font-size:20px; color:#9e95f0; font-family:Noto Sans KR, Apple SD Gothic Neo, Malgun Gothic, Nanum Gothic; padding-left:4px;">MY REPORT</span><br/>
    <div style="width:500px; height:660px; padding:0px; margin:0px; border:0px; background:url('.Thumbnail::$cdn_default_url.'/assets/icon/exam/img_report_bg.jpg); background-size:cover; overflow:hidde;">
    <div style="padding:75px 30px 5px 30px; font-family:Noto Sans KR, Apple SD Gothic Neo, Malgun Gothic, Nanum Gothic; ">
        <span style="font-size:38px; color:#222; font-family:Noto Sans KR, Apple SD Gothic Neo, Malgun Gothic, Nanum Gothic; font-weight:600; letter-spacing:-0.05em; ">'.$reportTitle.'</span>
        <div style="float:right; margin-right:1px; margin-top:26px;">
            <span style="font-size:11px; color:#222; font-family:Noto Sans KR, Apple SD Gothic Neo, Malgun Gothic, Nanum Gothic; font-weight:300; ">Name:</span>
            <span style="font-size:12px; color:#2b8ced; font-family:Noto Sans KR, Apple SD Gothic Neo, Malgun Gothic, Nanum Gothic; font-weight:600; ">'.$wiz_member['wm_nickname'].'</span>
        </div>
    </div>
    <div style="padding:0px 0px 15px 25px;">';

    // AHOP교재에서 변수 받은 교재의 이니셜 시험 배열로 받기
    $steps = "";
    $SQLLesson   = $CI->book_mdl->check_exam_by_titlename($tileName);
    foreach($SQLLesson as $rowL)
    {
        if($steps){ $steps .= ","; }
        $steps .= $rowL['ex_id'];
    }

    // AHOP교재로 수업한 정보 불러오기
    $completeStep = "";
    $completeDate = "";
    
    $exam = $CI->book_mdl->check_exam_log_by_ex_no_book_name($uid,$tileName,$steps);
    
    foreach($exam as $rowL) 
    {
        if($rowL['reply_name'] == "COMPLETE" || $rowL['reply_name'] == "FINISH")
        {
            if($completeStep){ $completeStep .= ","; }
            $completeStep .= $rowL['ex_no'];

            if($completeDate){ $completeDate .= ","; }
            $completeDate .= $rowL['regdate'];
        }
    }

    //단계 완료 스탬프
    $passArray = explode(",",$completeStep);
    $passDateArray = explode(",",$completeDate);
    $stepList = explode(",",$steps);

    for($i=0;$i<count($stepList);$i++)
    {
        $nox = $i + 1;
        $pass = "";
        if(in_array($stepList[$i], $passArray))
        { 
            $pass = "_pass"; 
        }
        $pre_content .= '<div style="width:153px; height:150px; display:inline-block; background:url('.Thumbnail::$cdn_default_url.'/assets/icon/exam/icon_report_'.strtolower($tileName).'_'.$nox.$pass.'.png) 0px 10px no-repeat; ">&nbsp;</div>';
    }
    $pre_content .= '
    </div>

    <table width="95%" cellpadding="0" cellspacing="0" style="border-bottom:0px;">
    <tr>
    <td align="center" valign="middle" width="35%" style="padding:0px; margin:0px; border:0px;">
        <span style="font-size:32px; line-height:34px; color:#222; font-family:Noto Sans KR, Apple SD Gothic Neo, Malgun Gothic, Nanum Gothic; font-weight:500; letter-spacing:-0.02em;">
        Pass<br/>
        Records
    </td>
    <td>
        <table cellpadding="0" cellspacing="0" width="100%" style="width:100%; table-layout:fixed; border:2px solid #e2e2e2; border-radius:1px;">';

    for($i=0;$i<count($stepList);$i++)
    {
        $pre_content .= '<tr>
        <th style=" padding:2px 0px; width:28%; border-right:1px solid #e2e2e2; text-align:center; color:#222; border-bottom:1px solid #e2e2e2; font-size:10px; font-family:Noto Sans KR, Apple SD Gothic Neo, Malgun Gothic, Nanum Gothic; color:#767676; ">Step '.($i+1).'</th>
        <td style=" padding:2px 0px; text-align:left; color:#222; padding-left:20px; border-bottom:1px solid #e2e2e2; font-size:10px; font-family:Noto Sans KR, Apple SD Gothic Neo, Malgun Gothic, Nanum Gothic; color:#767676; ">'.substr($passDateArray[$i],0,10).'
        &nbsp;</td>
        </tr>';
    }

    $pre_content .= '
        </table>
    </td>
    </tr>
    </table>
</div>';
                

    return array(
        'ex_id' => $checkExams['ex_id'],
        'category_num' => $cateFix,
        'category_name' => $tileName,
        'pre_title' => $title,
        'pre_content' => $pre_content,
    );
}

function board_make_ahop_pre_content_new($wiz_member,$ex_no='',$ex_id='')
{
    if($ex_no =='' && $ex_id =='') return '';

    $CI = & get_instance();
    $CI->load->model('lesson_mdl');
    $CI->load->model('book_mdl');

    $uid = $wiz_member['wm_uid'];

    //$uid = 63110;
    $ahop_lesson = $CI->lesson_mdl->check_ahop_lesson_by_uid($uid);

    if(!$ahop_lesson) return '';

    $pre_content = "<B>1. AHOP과정을 시작한 이유</B><br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
<B>2. 내가 생각하는 AHOP과정</B><br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
<B>3. AHOP과정 중 가장 힘들었던 기억</B><br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
<B>4. AHOP과정 중 가장 행복했던 기억</B><br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
<B>5. AHOP과정 합격자로서의 조언</B><br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
<B>6. 기타 하고싶은 말 또는 개선사항</B><br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;";

    # 시험 정보 확인 / 반영
    if($ex_no)
    {
        $checkExams = $CI->book_mdl->check_exam_log_by_ex_no($uid,$ex_no);
    }
    else
    {
        $checkExams = $CI->book_mdl->check_exam_log_by_ex_id($uid,$ex_id);
    }

    $reportTitle = "";
    $typeC = explode(" ", $checkExams['book_name']);
    if(trim($typeC[2]) == "Math"){ 		$cateFix = "127"; $reportTitle = "Math"; $tileName = "Math"; }
    if(trim($typeC[2]) == "Science"){ 	$cateFix = "128"; $reportTitle = "Science"; $tileName = "Science";  }
    if(trim($typeC[2]) == "Social"){ 	$cateFix = "129"; $reportTitle = "Social Studies"; $tileName = "Social";  }
    $title = $checkExams['book_name']." 시험 후기";

    $pre_content .= '
    <div class="pop-ahop-exam-report">
	<p class="review-title">
		<img src="'.Thumbnail::$cdn_default_url.'/assets/icon/exam/icon_exam_notice.png">
		<span>MY REPORT</span>
	</p>
    
	<div class="pop-content">
		<div class="pop-header">
			<span>'.$tileName.'</span>
			<div class="member-info">
				<span>Name:</span>
				<span class="mamber-nickname">'.$wiz_member['wm_nickname'].'</span>
			</div>
        </div>
        
        <div class="exam-list">';

    // AHOP교재에서 변수 받은 교재의 이니셜 시험 배열로 받기
    $steps = "";
    $SQLLesson   = $CI->book_mdl->check_exam_by_titlename($tileName);
    foreach($SQLLesson as $rowL)
    {
        if($steps){ $steps .= ","; }
        $steps .= $rowL['ex_id'];
    }

    // AHOP교재로 수업한 정보 불러오기
    $completeStep = "";
    $completeDate = "";
    
    $exam = $CI->book_mdl->check_exam_log_by_ex_no_book_name($uid,$tileName,$steps);
    
    foreach($exam as $rowL) 
    {
        if($rowL['reply_name'] == "COMPLETE" || $rowL['reply_name'] == "FINISH")
        {
            if($completeStep){ $completeStep .= ","; }
            $completeStep .= $rowL['ex_no'];

            if($completeDate){ $completeDate .= ","; }

            // 종료일을 찾아야한다.
            $finish = $CI->book_mdl->check_exam_log_finish_date($uid,$rowL['book_id']);
            $completeDate .= $finish['examdate'];
        }
    }

    //단계 완료 스탬프
    $passArray = explode(",",$completeStep);
    $passDateArray = explode(",",$completeDate);
    $stepList = explode(",",$steps);

    for($i=0;$i<count($stepList);$i++)
    {
        $nox = $i + 1;
        $pass = "";
        if(in_array($stepList[$i], $passArray))
        { 
            $pass = "_pass"; 
        }
        $pre_content .= '<div class="book-info" style="background-image:url('.Thumbnail::$cdn_default_url.'/assets/icon/exam/icon_report_'.strtolower($tileName).'_'.$nox.$pass.'.png); "></div>';
    }
    $pre_content .= '
    </div>

    <table class="pass-records-list" cellpadding="0" cellspacing="0">
    <tr>
        <td class="pass-records-list-title">
            <span>Pass Records</span>
        </td>
        <td>
            <table class="recordTable" cellpadding="0" cellspacing="0" width="100%">';

    for($i=0;$i<count($stepList);$i++)
    {
        $pre_content .= '
        <tr>
        <th>Step '.($i+1).'</th>
        <td>'.substr($passDateArray[$i],0,10).'</td>
        </tr>';
    }

    $pre_content .= '
    </table>
    </td>
</tr>
</table>
</div>
</div>';
                

    return array(
        'ex_id' => $checkExams['ex_id'],
        'category_num' => $cateFix,
        'category_name' => $tileName,
        'pre_title' => $title,
        'pre_content' => $pre_content,
    );
}

function board_make_request_pre_content_for_pass()
{
    $content = '';
    $content.= "<p style='text-align:center'><strong style='font-size:14px'>합격을 축하합니다!</strong><br>";
    $content.= "합격 선물 이벤트로 청사 금석과 해마타이트로 제작한 <u>'절대팔찌'</u>를 드립니다.<br>";
    $content.= "상품을 수령할 주소와 팔찌 사이즈를 입력해 주세요.";
    $content.= "</p>";
    $content.= "<br>";
    $content.= "<hr/>";
    $content.= "<br>";
    $content.= "<p>1. 합격한 과정 : </p>";
    $content.= "<br>";
    $content.= "<p>2. 주소 : </p>";
    $content.= "<br>";
    $content.= "<p>3. 우편번호 : </p>";
    $content.= "<br>";
    $content.= "<p>4. 택배 받으실분 성함 : </p>";
    $content.= "<br>";
    $content.= "<p>5. 택배 받으실분 연락처 : </p>";
    $content.= "<br>";
    $content.= "<p>6. 팔찌 사이즈 XS(주니어 추천) / S(여성분 추천) / M(기본 사이즈) / L(남성분 추천) : <br></p>";
    $content.= "<br><br>";
    $content.= "<p>※ 답변은 상품 배송 후 운송장 번호와 함께 남겨드립니다.</p>";

    return $content;
}

/* 실시간 요청게시판 분류에 따른 FAQ 게시글 가져오기 */
function board_list_faq_mb_unq($list_board)
{
    if(!$list_board) return null;
    
    $CI =& get_instance();
    $CI->load->model('board_mdl');

    for($i=0; $i<sizeof($list_board); $i++)
    {
        if($list_board[$i]['faq_mbunq'])
        {
            $list_board[$i]['faq'] =$CI->board_mdl->list_faq_mb_unq($list_board[$i]['faq_mbunq']);
        }
        
    }

    return $list_board;
}

/* 주말, 공휴일을 제외한 +n일에 대한 영업일 기준일자 가져온다 */
function board_calculate_date_except_holiday($workday=0)
{
    $CI =& get_instance();
    $CI->load->model('holiday_mdl');

    //$workday= 3;
    $add_day_result = 0;    // 루프 중단시켜줄 변수. 주말,공휴일이 아니라면 증가하는 숫자 ,strtotime(date('Y-m-17'))
    $add_day = 0;   // 공휴일, 주말이 끼었다면 +n일에 더해줄날짜
    while(1)
    {
        $day = date('w',strtotime('+'.($add_day_result+$add_day).' day'));
        $date = date('Y-m-d',strtotime('+'.($add_day_result+$add_day).' day'));
//echo $date;
        if($day == 0 || $day == 6) //0: 일, 6:토
        {
            $add_day++;
        }
        else
        {
            // 디비에 지정된 공휴일인지 체크. 정규수업 가능일시 첨삭도 가능
            $is_holiday = $CI->holiday_mdl->check_holiday($date);
            if($is_holiday['disabled_lesson'])
            {
                $add_day++;
            }
            else
            {
                $add_day_result++;
            }
        }

        //echo 'add:'.$add_day.PHP_EOL;
        //echo 'result:'.$add_day_result.PHP_EOL;

        if($add_day_result > $workday) break;
        
    }
    
    return date('Y-m-d H:i:s',strtotime('+'.($workday+$add_day).' day'));

}

/* 상세보기에 딕테이션 해결사 게시글에 댓글 게시물 추가 */
function board_article_dictation_solution_add_child($article)
{
    $CI =& get_instance();
    $CI->load->model('board_mdl');

    $article_writer = NULL;

    $article["child"] = NULL;

    if(!$article['mb_parent_key'])
    {

        $select_col_content = ", mb.parent_key as mb_parent_key";
        $index = "";
        $where = "WHERE mb.table_code = '".$article['mb_table_code']."' AND parent_key ='".$article['mb_unq']."'";
        $order = " ORDER BY mb_mb_unq DESC";
        $limit = "";

        $list_board_child = $CI->board_mdl->list_board($index, $where, $order, $limit, $select_col_content);

        if($list_board_child)
        {
            for($i=0; $i<count($list_board_child); $i++)
            {
                // 댓글 확인
                $recoomend_join_table = "";
                $recoomend_select = "";
                
                // $order = " ORDER BY mbc.mb_unq ASC";
                $order = " GROUP BY mbc.co_unq ORDER BY mbc.notice_yn ASC, mbc.co_fid DESC, mbc.co_thread ASC";
                $comment = $CI->board_mdl->list_article_comment($list_board_child[$i]['mb_mb_unq'], $recoomend_select, $recoomend_join_table, $order);
                
                if($comment)
                {
                    $comment_cnt = sizeof($comment);
                    $comment = board_comment_writer(array_splice($comment,0,5));
                }
                
                $article["child"][$i]['info'] = $list_board_child[$i];
                $article["child"][$i]['comment'] = $comment;
                $article["child"][$i]['comment_cnt'] = $comment_cnt;
            }
        }

    }
    return $article;
}


/* 딕테이션 해결사 게시글 리스트에 보드(자식) 게시물 추가 */
function board_list_dictation_solution_add_child($list, $wiz_member)
{
    $CI =& get_instance();
    $CI->load->model('board_mdl');

    if(!$list) return NULL;
    
    for($i=0; $i<count($list); $i++)
    {
        $list[$i]["child"] = NULL;

        $select_col_content = ", mb.parent_key AS mb_parent_key";
        $index = "";
        $where = "WHERE mb.table_code = '".$list[$i]['mb_table_code']."' AND mb.parent_key ='".$list[$i]['mb_mb_unq']."'";
        $order = " ORDER BY mb_mb_unq DESC";
        $limit = "";

        // 자식 게시물 검색
        $list_board_child = $CI->board_mdl->list_board_helper($index, $where, $order, $limit, $select_col_content);
        
        if($list_board_child)
        {
            for($j=0; $j<count($list_board_child); $j++)
            {
                // 댓글 확인
                $recoomend_join_table = "";
                $recoomend_select = "";
                
                $order = " ORDER BY mbc.mb_unq ASC";
                $comment = $CI->board_mdl->list_article_comment($list_board_child[$j]['mb_mb_unq'], $recoomend_select, $recoomend_join_table, $order);
                
                if($comment)
                {
                    $comment_cnt = sizeof($comment);
                    $comment = board_comment_writer(array_splice($comment,0,5));
                }
                
                $list[$i]["child"][$j]['info'] = $list_board_child[$j];
                $list[$i]["child"][$j]['comment'] = $comment;
                $list[$i]["child"][$j]['comment_cnt'] = $comment_cnt;
            }
        }

    }

    for($i=0; $i<count($list); $i++)
    {
        // 얼철딕을 썼는지 체크
        if($list[$i]['mb_cafe_unq'])
        {
            $CI->load->model('board_mdl');
            $mint_cafe = $CI->board_mdl->get_1130_by_cafe_unq($list[$i]['mb_cafe_unq']);
            
            $subject = explode("--", $mint_cafe['mb_subject']);

            /*
                $subject == 247--1328--4697
                [0] == book_id
                [1] == tu_uid
            */

            /* 부모글 딕테이션 해결사 정보 세팅 */
            $CI->load->model('book_mdl');
            $book = $CI->book_mdl->row_book_by_id($subject[0]);
            
            $CI->load->model('tutor_mdl');
            $tutor = $CI->tutor_mdl->get_tu_name_by_tu_uid($subject[1]);

            // 얼철딕 카운트
            $where_dictation = " WHERE mb.uid='".$wiz_member['wm_uid']."'";
            $list_cnt_dic = $CI->board_mdl->list_count_board_cafeboard($where_dictation);
            
            // $title = "[".$mint_cafe['mb_class_date']."]에 [".$tutor['tu_name']."]강사님과 [".$book['book_name']."]교재로 [".$mins."분]수업한 내용";
            $title = "[".$list_cnt_dic['cnt']."]번째 [얼철딕]";

            $list[$i]['mbn_mins'] = $mint_cafe['mb_mins'];
            $list[$i]['mbn_pre_content'] = $mint_cafe['mb_content'];
            $list[$i]['mbn_sim_content'] = $mint_cafe['mb_vd_url'];
            $list[$i]['mbn_filename'] = $mint_cafe['mb_filename'];
            $list[$i]['mbn_book_id'] = $subject[0];
            $list[$i]['mbn_book_name'] = $book['book_name'];
            $list[$i]['mbn_tu_uid'] = $subject[1];
            $list[$i]['mbn_tu_name'] = $tutor['tu_name'];
            $list[$i]['mbn_class_date'] = $mint_cafe['mb_class_date'];
            $list[$i]['mbn_title'] = $title;
        }

    }
    
    return $list;
}


/** 
 * 지식인 게시판 게시글 리스트에 보드(자식) 게시물 추가 
 * $mb_unq_var  mint_express와 mint_boards의 pk 키가 상이하므로 통일해서 사용하기 위한 변수
 */ 
function board_list_knowledge_add_child($list, $table_code='', $mb_unq_var='', $add_comment=false, $is_list=true )
{
    $CI =& get_instance();
    $CI->load->model('board_mdl');
    $CI->load->model('book_mdl');
    $CI->load->model('tutor_mdl');

    if(!$list) return NULL;

    $mb_unq_list = [];
    $limit = "";
    $index = "";

    // 자식게시물 일괄검색, 딕테이션은 관련정보 붙여준다.
    foreach($list as $key=>$row)
    {
        $mb_unq_list[] = $row[$mb_unq_var];

        if($row['mb_table_code'] =='1138' && $row['mb_cafe_unq'])
        {
            $mint_cafe = $CI->board_mdl->get_1130_by_cafe_unq($row['mb_cafe_unq']);
            
            $subject = explode("--", $mint_cafe['mb_subject']);

            /*
                $subject == 247--1328--4697
                [0] == book_id
                [1] == tu_uid
            */

            /* 부모글 딕테이션 해결사 정보 세팅 */
            
            $book = $CI->book_mdl->row_book_by_id($subject[0]);
            
            $tutor = $CI->tutor_mdl->get_tu_name_by_tu_uid($subject[1]);

            // 얼철딕 카운트
            //$where_dictation = " WHERE mb.uid='".$wiz_member['wm_uid']."'";
            //$list_cnt_dic = $CI->board_mdl->list_count_board_cafeboard($where_dictation);
            
            // $title = "[".$mint_cafe['mb_class_date']."]에 [".$tutor['tu_name']."]강사님과 [".$book['book_name']."]교재로 [".$mins."분]수업한 내용";
            //$title = "[".$list_cnt_dic['cnt']."]번째 [얼철딕]";

            $list[$key]['mbn_mins'] = $mint_cafe['mb_mins'];
            $list[$key]['mbn_pre_content'] = $mint_cafe['mb_content'];
            $list[$key]['mbn_sim_content'] = $mint_cafe['mb_vd_url'];
            $list[$key]['mbn_filename'] = $mint_cafe['mb_filename'];
            $list[$key]['mbn_book_id'] = $subject[0];
            $list[$key]['mbn_book_name'] = $book['book_name'];
            $list[$key]['mbn_tu_uid'] = $subject[1];
            $list[$key]['mbn_tu_name'] = $tutor['tu_name'];
            $list[$key]['mbn_class_date'] = $mint_cafe['mb_class_date'];
            //$list[$key]['mbn_title'] = $title;
        }
        
    }
    
    $where = "WHERE mb.parent_key IN (".implode(',',$mb_unq_list).")";
    // group_concat 때문에 GROUP BY넣음
    $order = " GROUP BY ".$mb_unq_var." ORDER BY ".$mb_unq_var." DESC";

    if($table_code =='express')
    {
        $select_col_content = ', group_concat(mba.type) as mba_type';
        $inner_table = 'LEFT JOIN mint_boards_adopt as mba ON mba.table_code=9001 AND mba.a_mb_unq=mb.uid';
        $list_board_child = $CI->board_mdl->list_board_express($index, $where, $order, $limit, $select_col_content, $inner_table);
    }
    else
    {
        $list_board_child = $CI->board_mdl->list_board_helper($index, $where, $order, $limit);
    }

    $child_sort = [];
    // 검색된 자식글(답변글)이 있을시
    if($list_board_child)
    {
        //리스트와 뷰 모두 자식을 붙여주려고 이 함수를 거친다. 리스트는 content를 삭제한다.
        $list_board_child = board_list_writer($list_board_child,NULL,NULL,NULL,array('content_del'=> $is_list,'strip_tag'=>false,'make_thumb'=> true));

        // mb_parent_key로 자식글들을 묶는다
        foreach($list_board_child as $row)
        {
            //채택된 답변글 있는지 체크
            //$adopt = $CI->board_mdl->checked_article_adopt($table_code =='express' ? 9001:$table_code, $row['mb_parent_key'], $row[$mb_unq_var]);
            //$row['is_adopt'] = $adopt ? 1:0;
            $row['is_adopt'] = strpos($row['mba_type'],'1') !==false ? 1:0;   //type 1 유저채택일시 채택여부 1로 설정
            $child_sort[$row['mb_parent_key']][] = $row;
        }

        for($i=0; $i<count($list); $i++)
        {
            $list[$i]['is_adopt'] = 0;
            $list_board_child = null;
            //mb_unq가 child_sort변수에 키로 설정되어있으면 자식게시물이 존재하므로
            if($child_sort[$list[$i][$mb_unq_var]])
            {
                $child = $child_sort[$list[$i][$mb_unq_var]];

                for($j=0; $j<count($child); $j++)
                {
                    
                    //댓글 필요하면 붙여준다
                    if($add_comment)
                    {
                        $recoomend_join_table = "";
                        $recoomend_select = "";

                        $child[$j]['comment'] = null;
                        $child[$j]['comment_cnt'] = 0;
                        
                        if($table_code =='express')
                        {
                            $order = " ORDER BY mbc.uid ASC";
                            $comment = $CI->board_mdl->list_article_express_comment($child[$j][$mb_unq_var], $recoomend_select, $recoomend_join_table, $order);
                        }
                        else
                        {
                            $order = " ORDER BY mbc.mb_unq ASC";
                            
                            $comment = $CI->board_mdl->list_article_comment($child[$j][$mb_unq_var], $recoomend_select, $recoomend_join_table, $order);
                        }

                        if($comment)
                        {
                            $comment_cnt = sizeof($comment);                                // 총 댓글갯수
                            $comment = board_comment_writer(array_splice($comment,0,5));    // 기본5개

                            $child[$j]['comment'] = $comment;
                            $child[$j]['comment_cnt'] = $comment_cnt;
                        }
                            
                    }

                    if($child[$j]['is_adopt'] == 1 && $list[$i]['is_adopt'] ==0)
                    {
                        $list[$i]['is_adopt'] = 1;
                    }
                }
                
                
                $list[$i]["child"] = $child;
            }
            else
            {
                $list[$i]["child"] = NULL;
            }

        }
    }
    
    return $list;
}

function board_list_add_ahop_bookmark($list, $ahop_list, $ahop_type = null)
{
    /*
        ahop 리스트는 이미지를 디비에서 가져오는것이 아니라서 포문으로 이미지를 세팅해줘야한다
        그리고 PC/MOBILE 리스트 형태도 달라서 리턴형태가 다름. $ahop_type == ahop 은 모바일 서브리스트 용도
    */

    if(!$list) return NULL;

    $lists = array();
    
    for($i=0; $i<count($list); $i++)
    {
        
        if($ahop_list){

            for($j=0; $j<count($ahop_list); $j++)
            {
                if($list[$i]['mb_mb_unq'] == $ahop_list[$j]['wbvp_mb_unq'])
                {
                    $list[$i]['mb_ahop_bookmark_yn'] = 'Y';
                    break;
                }else{
                    $list[$i]['mb_ahop_bookmark_yn'] = 'N';
                    continue;
                }
            }
        
        }else{
            // $ahop_list 이 없을시 디폴트로 N 세팅
            $list[$i]['mb_ahop_bookmark_yn'] = 'N';
        }

        
        if(strpos($list[$i]['mb_title'],'Chapter 1') !== false){
            $lists[0][] = $list[$i];
        }else if(strpos($list[$i]['mb_title'],'Chapter 2') !== false){
            $lists[1][] = $list[$i];
        }else if(strpos($list[$i]['mb_title'],'Chapter 3') !== false){
            $lists[2][] = $list[$i];
        }else if(strpos($list[$i]['mb_title'],'Chapter 4') !== false){
            $lists[3][] = $list[$i];
        }else if(strpos($list[$i]['mb_title'],'Chapter 5') !== false){
            $lists[4][] = $list[$i];
        }else if(strpos($list[$i]['mb_title'],'Chapter 6') !== false){
            $lists[5][] = $list[$i];
        }else if(strpos($list[$i]['mb_title'],'Chapter 7') !== false){
            $lists[6][] = $list[$i];
        }else if(strpos($list[$i]['mb_title'],'Chapter 8') !== false){
            $lists[7][] = $list[$i];
        }else if(strpos($list[$i]['mb_title'],'Chapter 9') !== false){
            $lists[8][] = $list[$i];
        }

    }
    
    if($ahop_type == 'ahop') return $list;
    else return $lists;
}

function board_insert_search_boards($table_code, $mb_unq)
{
    /*
        CLI API 호출
        비동기처리
    */
    $command = "php -f ".$_SERVER['DOCUMENT_ROOT']."/index.php _batch/board_insert_search_boards \"".$table_code."\" \"".$mb_unq."\" > /dev/null 2>/dev/null &";

    exec($command);
}

function board_delete_search_boards($table_code, $mb_unq, $wiz_id)
{
    /*
        CLI API 호출
        비동기처리
    */
    $command = "php -f ".$_SERVER['DOCUMENT_ROOT']."/index.php _batch/board_delete_search_boards \"".$table_code."\" \"".$mb_unq."\" \"".urlencode($wiz_id)."\" > /dev/null 2>/dev/null &";

    log_message('error', 'command :'.$command);


    exec($command);
}

//알림전송용 보드 링크만들기
function board_make_viwe_link($table_code, $mb_unq)
{
    $CI =& get_instance();
    $MBN_KNOWLEDGE_LIST =  $CI->config->item('MBN_KNOWLEDGE_LIST');
    
    if($_SERVER['HTTP_HOST'] == 'localhost:8000' || $_SERVER['HTTP_HOST'] =='dsapi.mintspeaking.com')
    {
        $domain = 'https://dsm.mintspeaking.com';
    }
    else
    {
        $domain = 'https://story.mint05.com';
    }

    if(in_array($table_code, $MBN_KNOWLEDGE_LIST))
    {
        $state = '/#/knowledge-view';
    }
    else
    {
        $state = '/#/board-view';
    }

    if($table_code == '9001') $table_code = 'express';

    return $domain . $state. '?tc='.$table_code.'&mu='.$mb_unq;
}


// 지식인게시판 글쓰기 관련 제한 횟수
function board_knowledge_var_conf($table_code)
{
    return [
        'limit_oneday_count_parent' => $table_code == '1138' ? 5:1,     //1일 의뢰글 글쓰기 횟수 
        'limit_oneday_count_child' => 5,                                //지식인 게시판 1일 답변글 글쓰기 횟수
        'limit_reply_count_solver' => 3,                                //지식인 게시판 한 게시물에 대한 답변이 달린 횟수 
    ];
}

//데이터에 따로 wiz_member 정보를 넣어주고 싶을때 사용한다.
function board_list_add_wizmember_info($list, $wiz_id_field_name)
{
    if(empty($list)) return null;
    
    $CI =& get_instance();
    $CI->load->model('member_mdl');

    $list_wiz_ids = array();
    if(array_key_exists($wiz_id_field_name, $list[0])) $list_wiz_ids = array_column($list, $wiz_id_field_name);
    
    if(!empty($list_wiz_ids))
    {
        //회원들 한번에 셀렉
        $where = "WHERE wm.wiz_id IN ('".implode("','",array_filter($list_wiz_ids))."')";
        $list_writer = $CI->board_mdl->list_writer($where);

        if($list_writer)
        {
            $wiz_member_list = [];
            //wiz_id를 키값으로 재배열화
            foreach($list_writer as $row)
            {
                $wiz_member_list[$row['wm_wiz_id']] = $row; 
            }

            foreach($list as $key=>$val)
            {
                $this_member = $wiz_member_list[$val[$wiz_id_field_name]];

                //원하는 추가정보 삽입
                $list[$key]['wm_regi_gubun'] = $this_member['wm_regi_gubun'];
                $list[$key]['wm_email'] = $this_member['wm_email'];
                $list[$key]['wm_social_email'] = $this_member['wm_social_email'];
                $list[$key]['wm_ename'] = $this_member['wm_ename'];
            }
        }
    }

    return $list;
}

/**
 * 작성자 네임카드용 정보 추출
 * 설정된 트로피, 노출 뱃지, 닉네임, 인삿말은 글정보에서 추출할수있으므로 제외
 * 글정보에서 못가져오는 정보들을 따로 여기서 추출한다.
 */
function get_name_card_data($uid, $param)
{
    $info = array();
    $upload_path_badge = ISTESTMODE ? 'test_upload/assets/badge/':'assets/badge/';

    //회원정보 없을 경우 리턴
    if(!$uid) return $info;

    $CI =& get_instance();
    $CI->load->model('board_mdl');
    $CI->load->model('lesson_mdl');

    //작성자 획득트로피, 완료퀘스트, 받은 추천수
    $cnt = $CI->board_mdl->cnt_tropy_quest_recommend($uid);
    $info['tropy_cnt']     = $cnt['tropy_cnt'] ? number_format($cnt['tropy_cnt']) : 0;
    $info['quest_cnt']     = $cnt['quest_cnt'] ? number_format($cnt['quest_cnt']) : 0;
    $info['recommend_cnt'] = $cnt['recommend_cnt'] ? number_format($cnt['recommend_cnt']) : 0;

    //총 수업시간
    //$total_cl_time = $CI->lesson_mdl->total_cl_time_by_uid($uid);
    //$info['total_cl_time'] = $total_cl_time ? number_format($total_cl_time['total_cl_time']):0;
    
    //작성자 뱃지리스트 가져오기
    $CI->load->model('badge_mdl');
    $where = " WHERE wb.type != 'admin' AND wmb.uid='".(int)$uid."' ORDER BY wb.id limit 0,20";
    $badge = $CI->badge_mdl->list_badge($where,'');
    
    $badge_list = array();
    $rank = $junior = true;
    for($i=0;$i<20;$i++)
    {
        if($badge[$i]['title'])
        {
            $badge_list[$i]['title'] = $badge[$i]['title'];
            $badge_list[$i]['img']   = Thumbnail::$cdn_default_url . '/' . $upload_path_badge . $badge[$i]['img'];
        }
        // 뱃지 더 가져오기 (주니어,회원등급)
        // 주니어 회원 아이콘 
        else if($junior && $param['wm_age'] <= 15)
        {
            $badge_list[$i]['title'] = '우리나라 청소년 만세!';
            $badge_list[$i]['img']   = Thumbnail::$cdn_default_url . '/assets/icon/junior/junior_s.png';
            $junior = false;
        }
        // 회원 등급에 따른 아이콘
        else if($rank && $param['mmg_icon'])
        {
            $badge_list[$i]['title'] = $param['mmg_description'];
            $badge_list[$i]['img']   = Thumbnail::$cdn_default_url . '/' . (ISTESTMODE ? 'test_upload/' : '') . 'attach/member/'.$param['mmg_icon'];
            $rank = false;
        }
        else
        {
            $badge_list[$i]['title'] = '';
        }
    }

    $info['badge_list'] = $badge_list;

    return $info;
}

//내용 저장시 불필요한 부분 제거
function cut_content($content, $table_code = null){
    //script 제거
    $content = preg_replace("!<script(.*?)<\/script>!is","",$content);
    //position: absolute 제거
    $content = preg_replace("/position:(\s|)absolute(\;|)/is","",$content);

    //class 제거, 1111 == 수업체험후기 (수업체험후기는report 만들어서 줌)
    if($table_code != 1111){
        $content = preg_replace("/class=(\"|\')?([^\"\']+)(\"|\')?/","",$content);
    }

    return $content;
}
