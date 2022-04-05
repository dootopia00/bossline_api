<?php

$config = array(
    "member/create" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => '아이디를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_pw',
            'label' => 'wiz_pw',
            'rules' => 'required|trim|min_length[6]|max_length[16]',
            'errors' => array(
                'required' => '패스워드를 입력해주세요.',
                'trim' => '패스워드에는 공백을 사용할 수 없습니다.',
                'min_length' => '패스워드는 6글자에서 16글자까지 입력 가능합니다.',
                'max_length' => '패스워드는 6글자에서 16글자까지 입력 가능합니다.'
            )
        ),
        // array(
        //     'field' => 'nickname',
        //     'label' => 'nickname',
        //     'rules' => 'required|trim|min_length[2]|max_length[16]',
        //     'errors' => array(
        //         'required' => '닉네임을 입력해주세요.',
        //         'trim' => '닉네임에는 공백을 사용할 수 없습니다.',
        //         'min_length' => '닉네임은 2글자에서 16글자까지 입력 가능합니다.',
        //         'max_length' => '닉네임은 2글자에서 16글자까지 입력 가능합니다.'
        //     )
        // ),
        array(
            'field' => 'name',
            'label' => 'name',
            'rules' => 'required|trim|min_length[2]|max_length[16]',
            'errors' => array(
                'required' => '이름을 입력해주세요.',
                'trim' => '이름에는 공백을 사용할 수 없습니다.',
                'min_length' => '이름은 2글자에서 16글자까지 입력 가능합니다.',
                'max_length' => '이름은 2글자에서 16글자까지 입력 가능합니다.'
            )
        ),
        // array(
        //     'field' => 'ename',
        //     'label' => 'ename',
        //     'rules' => 'required|trim|min_length[2]|max_length[16]',
        //     'errors' => array(
        //         'required' => '영어 이름을 입력해주세요.',
        //         'trim' => '영어 이름에는 공백을 사용할 수 없습니다.',
        //         'min_length' => '영어 이름은 2글자에서 16글자까지 입력 가능합니다.',
        //         'max_length' => '영어 이름은 2글자에서 16글자까지 입력 가능합니다.'
        //     )
        // ),
        array(
            'field' => 'birth',
            'label' => 'birth',
            'rules' => 'required',
            'errors' => array(
                'required' => '생년월일을 입력해주세요.'
            )
        ),
        array(
            'field' => 'gender',
            'label' => 'gender',
            'rules' => 'required',
            'errors' => array(
                'required' => '성별을 선택해주세요.'
            )
        ),
        array(
            'field' => 'regi_area',
            'label' => 'regi_area',
            'rules' => 'required',
            'errors' => array(
                'required' => '거주지를 선택해주세요.'
            )
        ),
        array(
            'field' => 'contact',
            'label' => 'contact',
            'rules' => 'required',
            'errors' => array(
                'required' => '연락처를 입력해주세요.'
            )
        )
    ),
    "member/create_level_test" => array(
        array(
            'field' => 'wm_uid',
            'label' => 'wm_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => '회원 정보가 없습니다.'
            )   
        ),
        array(
            'field' => 'lesson_gubun',
            'label' => 'lesson_gubun',
            'rules' => 'required',
            'errors' => array(
                'required' => '테스트 방식을 선택해주세요.'
            )
        ),
        array(
            'field' => 'lvt_contact',
            'label' => 'lvt_contact',
            'rules' => 'required',
            'errors' => array(
                'required' => '연락처를 입력해주세요.'
            )
        ),
        array(
            'field' => 'hopedate',
            'label' => 'hopedate',
            'rules' => 'required',
            'errors' => array(
                'required' => '예약 일자를 선택해주세요.'
            )
        ),
        array(
            'field' => 'hopetime1',
            'label' => 'hopetime1',
            'rules' => 'required',
            'errors' => array(
                'required' => '예약 시간을 선택해주세요.'
            )
        ),
        array(
            'field' => 'hopetime2',
            'label' => 'hopetime2',
            'rules' => 'required',
            'errors' => array(
                'required' => '예약 시간을 선택해주세요.'
            )
        ),
        array(
            'field' => 'englevel',
            'label' => 'englevel',
            'rules' => 'required',
            'errors' => array(
                'required' => '내가 생각하는 영어 실력을 선택해주세요.'
            )
        )
    ),
    "member/checked_phone_number" => array(
        array(
            'field' => 'contact',
            'label' => 'contact',
            'rules' => 'required',
            'errors' => array(
                'required' => '연락처를 입력해주세요.'
            )
        )
    ),
    "member/checked_nickname" => array(
        array(
            'field' => 'nickname',
            'label' => 'nickname',
            'rules' => 'required|trim|min_length[2]|max_length[16]',
            'errors' => array(
                'required' => '닉네임을 입력해주세요.',
                'trim' => '닉네임에는 공백을 사용할 수 없습니다.',
                'min_length' => '닉네임은 2글자에서 16글자까지 입력 가능합니다.',
                'max_length' => '닉네임은 2글자에서 16글자까지 입력 가능합니다.'
            )
        )
    ),
    "member/login" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => '아이디를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_pw',
            'label' => 'wiz_pw',
            'rules' => 'required|trim|min_length[6]|max_length[16]',
            'errors' => array(
                'required' => '패스워드를 입력해주세요.',
                'trim' => '패스워드에는 공백을 사용할 수 없습니다.',
                'min_length' => '패스워드는 6글자에서 16글자까지 입력 가능합니다.',
                'max_length' => '패스워드는 6글자에서 16글자까지 입력 가능합니다.'
            )
        )
    ),
    "member/checked_id" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => '아이디를 입력해주세요.'
            )
        ),
    ),
    "member/login_sns" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => '아이디를 입력해주세요.'
            )
        ),
    ),
    "member/find_id" => array(
        array(
            'field' => 'name',
            'label' => 'name',
            'rules' => 'required|trim|min_length[2]|max_length[16]',
            'errors' => array(
                'required' => '이름을 입력해주세요.',
                'trim' => '이름에는 공백을 사용할 수 없습니다.',
                'min_length' => '이름은 2글자에서 16글자까지 입력 가능합니다.',
                'max_length' => '이름은 2글자에서 16글자까지 입력 가능합니다.'
            )
        ),
        array(
            'field' => 'birth',
            'label' => 'birth',
            'rules' => 'required',
            'errors' => array(
                'required' => '생년월일을 입력해주세요.'
            )
        ),
    ),
    
    "member/find_pwd" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => '아이디를 입력해주세요.'
            )
        ),
        array(
            'field' => 'name',
            'label' => 'name',
            'rules' => 'required|trim|min_length[2]|max_length[16]',
            'errors' => array(
                'required' => '이름을 입력해주세요.',
                'trim' => '이름에는 공백을 사용할 수 없습니다.',
                'min_length' => '이름은 2글자에서 16글자까지 입력 가능합니다.',
                'max_length' => '이름은 2글자에서 16글자까지 입력 가능합니다.'
            )
        ),
    ),

    "member/update_nickname" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),        
    ),
    "member/update_member_token" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'is_app',
            'label' => 'is_app',
            'rules' => 'required',
            'errors' => array(
                'required' => 'is_app 을 입력해주세요.'
            )
        ),
        array(
            'field' => 'token',
            'label' => 'token',
            'rules' => 'required',
            'errors' => array(
                'required' => 'token 을 입력해주세요.'
            )
        ),
        
    ),
    "member/checked_schedule_by_wiz_member" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id 를 입력해주세요.'
            )
        ),        
    ),
    "member/wiz_member_get_tropy_list_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
    ),
    "board/theme_" => array(
        array(
            'field' => 'board_type',
            'label' => 'board_type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'board_type을 입력해주세요.'
            )
        ),
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),
    "board/list_" => array(
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code을 입력해주세요.'
            )
        ),
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),
    "board/search_old" => array(
        array(
            'field' => 'search_type',
            'label' => 'search_type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'search_type을 입력해주세요.'
            )
        ),
        array(
            'field' => 'search_keyword',
            'label' => 'search_keyword',
            'rules' => 'required',
            'errors' => array(
                'required' => 'search_keyword을 입력해주세요.'
            )
        ),
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),
    "board/search_" => array(
        array(
            'field' => 'search_type',
            'label' => 'search_type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'search_type을 입력해주세요.'
            )
        ),
        array(
            'field' => 'search_keyword',
            'label' => 'search_keyword',
            'rules' => 'required',
            'errors' => array(
                'required' => 'search_keyword을 입력해주세요.'
            )
        ),
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),

    "board/comment_" => array(
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),
    "board/special_" => array(
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code을 입력해주세요.'
            )
        ),
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),
    "board/article" => array(
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
    ),
    "board/article_comment_" => array(
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        /*
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
        */
    ),
    "board/config" => array(
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
    ),
    "board/special_config" => array(
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
    ),
    "board/list_select_wiz_speak_sub_mb_content_" => array(
        array(
            'field' => 'code',
            'label' => 'code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'code를 입력해주세요.'
            )
        ),
    ),
    "board/bookmark" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'del_yn',
            'label' => 'del_yn',
            'rules' => 'required',
            'errors' => array(
                'required' => 'del_yn를 입력해주세요.'
            )
        ),
    ),
    "board/bookmark_checked" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
    ),
    "board/recommend_comment" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'recommend_key',
            'label' => 'recommend_key',
            'rules' => 'required',
            'errors' => array(
                'required' => 'recommend_key를 입력해주세요.'
            )
        ),
    ),
    "board/recommend_article" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'recommend_key',
            'label' => 'recommend_key',
            'rules' => 'required',
            'errors' => array(
                'required' => 'recommend_key를 입력해주세요.'
            )
        ),
    ),
    "board/recommend_article_special" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'recommend_key',
            'label' => 'recommend_key',
            'rules' => 'required',
            'errors' => array(
                'required' => 'recommend_key를 입력해주세요.'
            )
        ),
    ),

    "board/update_star" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'article_key',
            'label' => 'article_key',
            'rules' => 'required',
            'errors' => array(
                'required' => 'article_key를 입력해주세요.'
            )
        ),
        array(
            'field' => 'star',
            'label' => 'star',
            'rules' => 'required',
            'errors' => array(
                'required' => 'star를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
    ),

    "_batch/checked_best_article" => array(
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
    ),
    
    "_batch/notify_send_sms" => array(
        array(
            'field' => 'wm_uid',
            'label' => 'wm_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wm_uid를 입력해주세요.'
            )
        ),
        array(
            'field' => 'atalk_code',
            'label' => 'atalk_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'atalk_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'sms_id',
            'label' => 'sms_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sms_id를 입력해주세요.'
            )
        ),
    ),
    "_batch/comment_special_insert_notify" => array(
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'co_unq',
            'label' => 'co_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'co_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wm_uid',
            'label' => 'wm_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wm_uid를 입력해주세요.'
            )
        ),
       
    ),
    "_batch/comment_insert_notify" => array(
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'co_unq',
            'label' => 'co_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'co_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wm_uid',
            'label' => 'wm_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wm_uid를 입력해주세요.'
            )
        ),
    ),
    
    "_batch/set_badge_in_user" => array(
        array(
            'field' => 'count',
            'label' => 'count',
            'rules' => 'required',
            'errors' => array(
                'required' => 'count 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'badge_id',
            'label' => 'badge_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'badge_id 를 입력해주세요.'
            )
        ),
    ),

    "board/clip" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
    ),
    "board/comment" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'comment',
            'label' => 'comment',
            'rules' => 'required',
            'errors' => array(
                'required' => '댓글 내용을 입력해주세요.'
            )
        ),
        array(
            'field' => 'mob',
            'label' => 'mob',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mob 입력해주세요.'
            )
        ),
       
    ),
    "board/comment_special" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'comment',
            'label' => 'comment',
            'rules' => 'required',
            'errors' => array(
                'required' => '댓글 내용을 입력해주세요.'
            )
        ),
    ),
    "board/modify_comment_special" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'comment',
            'label' => 'comment',
            'rules' => 'required',
            'errors' => array(
                'required' => '댓글 내용을 입력해주세요.'
            )
        ),
        array(
            'field' => 'co_unq',
            'label' => 'co_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'co_unq를 입력해주세요.'
            )
        ),
    ),
    "board/delete_comment_special" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'co_unq',
            'label' => 'co_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'co_unq를 입력해주세요.'
            )
        ),
    ),
    "board/modify_comment" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'comment',
            'label' => 'comment',
            'rules' => 'required',
            'errors' => array(
                'required' => '댓글 내용을 입력해주세요.'
            )
        ),
        array(
            'field' => 'co_unq',
            'label' => 'co_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'co_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mob',
            'label' => 'mob',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mob를 입력해주세요.'
            )
        ),
    ),
    "board/delete_comment" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'co_unq',
            'label' => 'co_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'co_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ), 
    ),

    "board/admin_insert_search_boards" => array(
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
    ),

    "board/admin_delete_search_boards" => array(
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
    ),

    "member/change_english" => array(
        array(
            'field' => 'name',
            'label' => 'name',
            'rules' => 'required',
            'errors' => array(
                'required' => 'name를 입력해주세요.'
            )
        ),
    ),
    "member/notify_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),
    "member/control_notify" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type를 입력해주세요.'
            )
        ),
        array(
            'field' => 'idx',
            'label' => 'idx',
            'rules' => 'required',
            'errors' => array(
                'required' => 'idx를 입력해주세요.'
            )
        ),
    ),
    "member/clip_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),
    "member/delete_control" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'cb_unq',
            'label' => 'cb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'cb_unq를 입력해주세요.'
            )
        ),
    ),
    "member/delete_clip" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'cb_unq',
            'label' => 'cb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'cb_unq를 입력해주세요.'
            )
        ),
    ),
    "member/article_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),
    "member/comment_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
        
    ),
    "member/teacher_counseling_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),
    "msg/list_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type를 입력해주세요.'
            )
        ),
    ),
    "msg/delete_list" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type를 입력해주세요.'
            )
        ),
        array(
            'field' => 'idx',
            'label' => 'idx',
            'rules' => 'required',
            'errors' => array(
                'required' => 'idx를 입력해주세요.'
            )
        ),
    ),
    "msg/view" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'idx',
            'label' => 'idx',
            'rules' => 'required',
            'errors' => array(
                'required' => 'idx를 입력해주세요.'
            )
        ),
    ),
    "msg/save" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'idx',
            'label' => 'idx',
            'rules' => 'required',
            'errors' => array(
                'required' => 'idx를 입력해주세요.'
            )
        ),
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type을 입력해주세요.'
            )
        ),
        array(
            'field' => 'save_type',
            'label' => 'save_type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'save_type을 입력해주세요.'
            )
        ),
    ),
    "msg/block" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'idx',
            'label' => 'idx',
            'rules' => 'required',
            'errors' => array(
                'required' => 'idx를 입력해주세요.'
            )
        ),
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type를 입력해주세요.'
            )
        ),
    
    ),
    "member/member_info" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
    ),
    "member/update_info" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
    ),
    "member/update_password" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_pw',
            'label' => 'wiz_pw',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_pw를 입력해주세요.'
            )
        ),
    ),
    "member/leave" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
    ),
    "msg/send" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'receive_id',
            'label' => 'receive_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'receive_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'message',
            'label' => 'message',
            'rules' => 'required',
            'errors' => array(
                'required' => 'message를 입력해주세요.'
            )
        ),
    ),
    "board/write_article" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),

    ),
    "board/modify_article" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq 입력해주세요.'
            )
        ),
    ),

    "board/checked_count_today_write_article" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
    ),
    "board/delete_article" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ), 
    ),
    "board/delete_article_special" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ), 
    ),
    "board/write_article_special" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ), 
        array(
            'field' => 'content',
            'label' => 'content',
            'rules' => 'required',
            'errors' => array(
                'required' => 'content를 입력해주세요.'
            )
        ), 
    ),
    "board/modify_article_special" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ), 
        array(
            'field' => 'content',
            'label' => 'content',
            'rules' => 'required',
            'errors' => array(
                'required' => 'content를 입력해주세요.'
            )
        ), 
    ),
    "tutor/list_" => array(
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),

    "tutor/list_tutor_individual_" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => '알수없는 회원입니다.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'is_app',
            'label' => 'is_app',
            'rules' => 'required',
            'errors' => array(
                'required' => 'is_app을 입력해주세요.'
            )
        ),
    ),

    "tutor/schedule_" => array(
        array(
            'field' => 'tu_uid',
            'label' => 'tu_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_uid 입력해주세요.'
            )
        ),
        array(
            'field' => 'number_of_classes',
            'label' => 'number_of_classes',
            'rules' => 'required|in_list[2,3,5]',
            'errors' => array(
                'required' => 'number_of_classes 을 확인해주세요.',
                'in_list' => 'number_of_classes 값은 [2,3,5] 만 가능합니다.'
            )
        ),

        
    ),
    
    "book/list_main_book_" => array(
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),

    
    "book/list_book_step2_" => array(
        array(
            'field' => 'f_id',
            'label' => 'f_id',
            'rules' => 'required',
            'errors' => array(
                'required' => '교재를 선택해주세요.'
            )
        ),
    ),


    "book/list_bookmark_bookhistory_id" => array(
        array(
            'field' => 'uid',
            'label' => 'uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'uid 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id 를 입력해주세요.'
            )
        ),
    ),

    "book/class_info_bookmark_lesson" => array(
        array(
            'field' => 'uid',
            'label' => 'uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'uid 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),
        
    ),

    "book/book_bookmark" => array(
        array(
            'field' => 'uid',
            'label' => 'uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'uid 를 입력해주세요.'
            )
        ),
        // array(
        //     'field' => 'lesson_id',
        //     'label' => 'lesson_id',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'lesson_id 를 입력해주세요.'
        //     )
        // ),
        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),
        // array(
        //     'field' => 'bookmark_chapter_name',
        //     'label' => 'bookmark_chapter_name',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'bookmark_chapter_name 를 입력해주세요.'
        //     )
        // ),
        // array(
        //     'field' => 'bookmark_lesson_name',
        //     'label' => 'bookmark_lesson_name',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'bookmark_lesson_name 를 입력해주세요.'
        //     )
        // ),
        array(
            'field' => 'bookmark_page',
            'label' => 'bookmark_page',
            'rules' => 'required',
            'errors' => array(
                'required' => 'bookmark_page 를 입력해주세요.'
            )
        ),
        
    ),

    "book/class_book_update" => array(

        // array(
        //     'field' => 'uid',
        //     'label' => 'uid',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'uid 를 입력해주세요.'
        //     )
        // ),
        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id 를 입력해주세요.'
            )
        ),
        
        array(
            'field' => 'book_page',
            'label' => 'book_page',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_page 를 입력해주세요.'
            )
        ),
        
    ),

    "book/get_inclass_book_info" => array(

        array(
            'field' => 'uid',
            'label' => 'uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'uid 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),
    ),

    "book/get_last_class_book_page" => array(

        array(
            'field' => 'tu_uid',
            'label' => 'tu_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_uid 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id 를 입력해주세요.'
            )
        ),
        
    ),

    "banner/list_" => array(
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),

    "curriculum/list_" => array(
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),

    "curriculum/insert_consult" => array(

        array(
            'field' => 'kind',
            'label' => 'kind',
            'rules' => 'required',
            'errors' => array(
                'required' => 'kind 입력해주세요.'
            )
        ),
        array(
            'field' => 'com_name',
            'label' => 'com_name',
            'rules' => 'required',
            'errors' => array(
                'required' => 'com_name 입력해주세요.'
            )
        ),
        array(
            'field' => 'user_name',
            'label' => 'user_name',
            'rules' => 'required',
            'errors' => array(
                'required' => 'user_name 입력해주세요.'
            )
        ),
        array(
            'field' => 'hope_date',
            'label' => 'hope_date',
            'rules' => 'required',
            'errors' => array(
                'required' => 'hope_date 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'hope_time',
            'label' => 'hope_time',
            'rules' => 'required',
            'errors' => array(
                'required' => 'hope_time 입력해주세요.'
            )
        ),
        array(
            'field' => 'email',
            'label' => 'email',
            'rules' => 'required',
            'errors' => array(
                'required' => 'email 입력해주세요.'
            )
        ),
        array(
            'field' => 'check_ok',
            'label' => 'check_ok',
            'rules' => 'required',
            'errors' => array(
                'required' => 'check_ok 입력해주세요.'
            )
        ),
    ),
    
    "main/list_" => array(
        array(
            'field' => 'banner_start',
            'label' => 'banner_start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'banner_start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'banner_limit',
            'label' => 'banner_limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'banner_limit 입력해주세요.'
            )
        ),

        array(
            'field' => 'tutor_start',
            'label' => 'tutor_start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tutor_start 입력해주세요.'
            )
        ),
        array(
            'field' => 'tutor_limit',
            'label' => 'tutor_limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tutor_limit 입력해주세요.'
            )
        ),

        array(
            'field' => 'curriculum_start',
            'label' => 'curriculum_start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'curriculum_start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'curriculum_limit',
            'label' => 'curriculum_limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'curriculum_limit를 입력해주세요.'
            )
        ),
        array(
            'field' => 'special_start',
            'label' => 'special_start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'special_start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'special_limit',
            'label' => 'special_limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'special_limit를 입력해주세요.'
            )
        ),

        array(
            'field' => 'community_start',
            'label' => 'community_start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'community_start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'community_limit',
            'label' => 'community_limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'community_limit를 입력해주세요.'
            )
        ),
    ),
    

    "tutor/info" => array(
        array(
            'field' => 'tu_uid',
            'label' => 'tu_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => '선생님을 선택해주세요.'
            )
        ),
    ),

    

    "tutor/list_tutor_evaluation_" => array(
        array(
            'field' => 'tu_uid',
            'label' => 'tu_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => '선생님을 선택해주세요.'
            )
        ),
    ),


    "tutor/regist_tutor_evaluation" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_uid',
            'label' => 'tu_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => '선생님을 선택해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => '알수없는 회원입니다.'
            )
        ),
        array(
            'field' => 'star',
            'label' => 'star',
            'rules' => 'required',
            'errors' => array(
                'required' => '평점을 입력해주세요.'
            )
        ),
        array(
            'field' => 'review',
            'label' => 'review',
            'rules' => 'required',
            'errors' => array(
                'required' => '리뷰 내용을 입력해주세요.'
            )
        ),
        array(
            'field' => 'item',
            'label' => 'item',
            'rules' => 'required',
            'errors' => array(
                'required' => '선생님 특징을 선택해주세요.'
            )
        ),

    ),

    "tutor/modify_tutor_evaluation" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_uid',
            'label' => 'tu_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => '선생님을 선택해주세요.'
            )
        ),
        array(
            'field' => 'ts_uid',
            'label' => 'ts_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => '수정 할 글을 선택해주세요'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => '알수없는 회원입니다.'
            )
        ),
        array(
            'field' => 'star',
            'label' => 'star',
            'rules' => 'required',
            'errors' => array(
                'required' => '평점을 입력해주세요.'
            )
        ),
        array(
            'field' => 'review',
            'label' => 'review',
            'rules' => 'required',
            'errors' => array(
                'required' => '리뷰 내용을 입력해주세요.'
            )
        ),
        array(
            'field' => 'item',
            'label' => 'item',
            'rules' => 'required',
            'errors' => array(
                'required' => '선생님 특징을 선택해주세요.'
            )
        ),

    ),
    "tutor/delete_tutor_evaluation" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ts_uid',
            'label' => 'ts_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => '수정 할 글을 선택해주세요'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => '알수없는 회원입니다.'
            )
        ),

    ),

    "tutor/tutor_like" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_uid',
            'label' => 'tu_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => '선생님을 선택해주세요'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => '알수없는 회원입니다.'
            )
        ),
        array(
            'field' => 'del_yn',
            'label' => 'del_yn',
            'rules' => 'required',
            'errors' => array(
                'required' => '잘못된 액션입니다.'
            )
        ),
    ),

    "tutor/checked_write_tutor_star" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => '알수없는 회원입니다.'
            )
        ),
        array(
            'field' => 'tu_uid',
            'label' => 'tu_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_uid 을 입력해주세요.'
            )
        ),
    ),

    "kinesis/get_channel_list" => array(
        array(
            'field' => 'channel_name',
            'label' => 'channel_name',
            'rules' => 'required',
            'errors' => array(
                'required' => 'channel_name 을 입력해주세요.'
            )
        ),
    ),
    
    "kinesis/create_channel" => array(
        array(
            'field' => 'channel_name',
            'label' => 'channel_name',
            'rules' => 'required',
            'errors' => array(
                'required' => 'channel_name 을 입력해주세요.'
            )
        ),
    ),

    "kinesis/delete_channel" => array(
        array(
            'field' => 'channel_name',
            'label' => 'channel_name',
            'rules' => 'required',
            'errors' => array(
                'required' => 'channel_name 을 입력해주세요.'
            )
        ),        
    ),

    "kinesis/kinesis_file_upload" => array(
        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id 를 입력해주세요.'
            )
        ),
    ),

    "webrtc/send_push" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 을 입력해주세요.'
            )
        ),
        array(
            'field' => 'p_type',
            'label' => 'p_type',
            'rules' => 'required|in_list[webrtc_video,webrtc_voice]',
            'errors' => array(
                'required' => 'p_type 을 확인해주세요.',
                'in_list' => 'p_type 값은 [webrtc_video,webrtc_voice] 만 가능합니다.'
            )
        ),
        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id 을 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_uid',
            'label' => 'tu_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_uid 을 입력해주세요.'
            )
        ),
        array(
            'field' => 'channel_name',
            'label' => 'channel_name',
            'rules' => 'required',
            'errors' => array(
                'required' => 'channel_name 을 입력해주세요.'
            )
        ),
    ),

    "webrtc/receive_push_log" => array(
        
        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id 을 입력해주세요.'
            )
        ),
    ),

    "webrtc/receive_schedule_info" => array(
        
        array(
            'field' => 'tu_uid',
            'label' => 'tu_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_uid 을 입력해주세요.'
            )
        ),

        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id 을 입력해주세요.'
            )
        ),
    ),

    "webrtc/receive_push_device" => array(
        
        array(
            'field' => 'wpl_pk_key',
            'label' => 'wpl_pk_key',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wpl_pk_key 을 입력해주세요.'
            )
        ),

        array(
            'field' => 'device',
            'label' => 'device',
            'rules' => 'required',
            'errors' => array(
                'required' => 'device 을 입력해주세요.'
            )
        ),

        array(
            'field' => 'state',
            'label' => 'state',
            'rules' => 'required',
            'errors' => array(
                'required' => 'state 을 입력해주세요.'
            )
        ),

    ),
    "webrtc/send_push_check" => array(
        
        array(
            'field' => 'wpl_pk_key',
            'label' => 'wpl_pk_key',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wpl_pk_key 을 입력해주세요.'
            )
        ),

        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id 을 입력해주세요.'
            )
        ),

    ),

    "webrtc/insert_maaltalk_note_log" => array(
        
        array(
            'field' => 'tu_uid',
            'label' => 'tu_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_uid 을 입력해주세요.'
            )
        ),
        array(
            'field' => 'wm_uid',
            'label' => 'wm_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wm_uid 을 입력해주세요.'
            )
        ),
        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id 을 입력해주세요.'
            )
        ),
        array(
            'field' => 'state',
            'label' => 'state',
            'rules' => 'required',
            'errors' => array(
                'required' => 'state 을 입력해주세요.'
            )
        ),
        array(
            'field' => 'invitational_url',
            'label' => 'invitational_url',
            'rules' => 'required',
            'errors' => array(
                'required' => 'invitational_url 을 입력해주세요.'
            )
        ),
        array(
            'field' => 'msg_type',
            'label' => 'msg_type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'msg_type 을 입력해주세요.'
            )
        ),
        array(
            'field' => 'receipt_number',
            'label' => 'receipt_number',
            'rules' => 'required',
            'errors' => array(
                'required' => 'receipt_number 을 입력해주세요.'
            )
        ),
        array(
            'field' => 'loc',
            'label' => 'loc',
            'rules' => 'required',
            'errors' => array(
                'required' => 'loc 을 입력해주세요.'
            )
        ),
    ),

    "board/update_comment_notice" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'co_unq',
            'label' => 'co_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => '댓글을 선택해주세요.'
            )
        ),

    ),

    
    "badge/change_badge" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        // array(
        //     'field' => 'badge_id',
        //     'label' => 'badge_id',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => '뱃지를 선택해주세요.'
        //     )
        // ),

    ),

    
    "badge/change_trophy" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        // array(
        //     'field' => 'tropy_ut_idx',
        //     'label' => 'tropy_ut_idx',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => '트로피를 선택해주세요.'
        //     )
        // ),

    ),


    "badge/insert_ahop_badge" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type 를 선택해주세요.'
            )
        ),

    ),

    "board/check_valid_write_page" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 선택해주세요.'
            )
        ),

    ),

    
    "board/set_blind_article" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 선택해주세요.'
            )
        ),

        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 선택해주세요.'
            )
        ),
        
        array(
            'field' => 'co_unq',
            'label' => 'co_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'co_unq를 선택해주세요.'
            )
        ),
    ),

    "board/make_class_script_title" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id를 입력해주세요.'
            )
        ),
    ),

    "board/ahop_bookmark" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'category',
            'label' => 'category',
            'rules' => 'required',
            'errors' => array(
                'required' => 'category를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq를 입력해주세요.'
            )
        ),
    ),

    "board/update_select_star" => array(
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq 입력해주세요.'
            )
        ),
        array(
            'field' => 'sim_content3',
            'label' => 'sim_content3',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sim_content3 입력해주세요.'
            )
        ),
        array(
            'field' => 'cl_time',
            'label' => 'cl_time',
            'rules' => 'required',
            'errors' => array(
                'required' => 'cl_time 입력해주세요.'
            )
        ),
        array(
            'field' => 'select_key',
            'label' => 'select_key',
            'rules' => 'required',
            'errors' => array(
                'required' => 'select_key 입력해주세요.'
            )
        ),
        array(
            'field' => 'select_wiz_id',
            'label' => 'select_wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'select_wiz_id 입력해주세요.'
            )
        ),
        array(
            'field' => 'star',
            'label' => 'star',
            'rules' => 'required',
            'errors' => array(
                'required' => 'star 입력해주세요.'
            )
        ),
    ),

    "member/regist_member_block" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'blocked_wiz_id',
            'label' => 'blocked_wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'blocked_wiz_id 를 선택해주세요.'
            )
        ),

    ),

    "member/delete_member_block" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'blocked_wiz_id',
            'label' => 'blocked_wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'blocked_wiz_id를 선택해주세요.'
            )
        ),


    ),
    
    "member/member_block_list_" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start 를 입력해주세요.'
            )
        ),

        
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit 를 입력해주세요.'
            )
        ),

    ),
    
    "tutor/select_tutor" => array(
        
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code 를 입력해주세요.'
            )
        ),


    ),


    "member/update_survey" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),


        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'info1',
            'label' => 'info1',
            'rules' => 'required',
            'errors' => array(
                'required' => 'info1 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'info2',
            'label' => 'info2',
            'rules' => 'required',
            'errors' => array(
                'required' => 'info2 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'info3',
            'label' => 'info3',
            'rules' => 'required',
            'errors' => array(
                'required' => 'info3 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'etc',
            'label' => 'etc',
            'rules' => 'required',
            'errors' => array(
                'required' => 'etc 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'device',
            'label' => 'device',
            'rules' => 'required',
            'errors' => array(
                'required' => 'device 를 입력해주세요.'
            )
        ),


    ),

    
    "curriculum/view" => array(
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code 를 입력해주세요.'
            )
        ),

    ),

    "etc/insert_utm" => array(
        array(
            'field' => 'muu_key',
            'label' => 'muu_key',
            'rules' => 'required',
            'errors' => array(
                'required' => 'muu_key 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type 을 입력해주세요.'
            )
        ),
    ),
    
    "member/member_board_list_" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),


        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'target_wiz_id',
            'label' => 'target_wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'target_wiz_id 를 입력해주세요.'
            )
        ),
    ),

    
    "member/member_reply_list_" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),


        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'target_wiz_id',
            'label' => 'target_wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'target_wiz_id 를 입력해주세요.'
            )
        ),
    ),

    "lesson/class_list_" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),


        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

    ),
    "event/list_" => array(
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start를 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit를 입력해주세요.'
            )
        ),
    ),
    "event/view" => array(
        array(
            'field' => 'e_id',
            'label' => 'e_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'e_id 입력해주세요.'
            )
        ),
    ),
    "event/beta_maaltalk_note" => array(
        array(
            'field' => 'wm_uid',
            'label' => 'wm_uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wm_uid 입력해주세요.'
            )
        ),
    ),
    "banner/clickcount" => array(
        
        array(
            'field' => 'popup_nidx',
            'label' => 'popup_nidx',
            'rules' => 'required',
            'errors' => array(
                'required' => 'popup_nidx 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'is_app',
            'label' => 'is_app',
            'rules' => 'required',
            'errors' => array(
                'required' => 'is_app 를 입력해주세요.'
            )
        ),

    ),
    "member/update_token" => array(
        array(
            'field' => 'callback',
            'label' => 'callback',
            'rules' => 'required',
            'errors' => array(
                'required' => 'callback 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'social',
            'label' => 'social',
            'rules' => 'required',
            'errors' => array(
                'required' => 'social 를 입력해주세요.'
            )
        ),
    ),

    "member/usage_list_" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type 를 입력해주세요.'
            )
        ),
    ),

    "member/usage_user_page" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
    ),

    "member/usage_user_category_list_" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
    ),


    "curriculum/english_article_topic_list_" => array(
        array(
            'field' => 'lev_gubun',
            'label' => 'lev_gubun',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lev_gubun 를 입력해주세요.'
            )
        ),
    ),
    "curriculum/english_article_list_" => array(
        array(
            'field' => 'lev_gubun',
            'label' => 'lev_gubun',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lev_gubun 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),

    ),
    "curriculum/english_article" => array(
        array(
            'field' => 'lev_gubun',
            'label' => 'lev_gubun',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lev_gubun 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'uid',
            'label' => 'uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'uid 를 입력해주세요.'
            )
        ),
    ),
    "curriculum/ahop_info" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ahop_type',
            'label' => 'ahop_type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ahop_type 를 입력해주세요.'
            )
        ),
    ),

    "curriculum/ahop_reward" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ex_id',
            'label' => 'ex_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ex_id 를 입력해주세요.'
            )
        ),
    ),

    "curriculum/checked_ahop_exam" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ahop_type',
            'label' => 'ahop_type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ahop_type 를 입력해주세요.'
            )
        ),
        // array(
        //     'field' => 'book_id',
        //     'label' => 'book_id',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'book_id 를 입력해주세요.'
        //     )
        // ),
        // array(
        //     'field' => 'ex_id',
        //     'label' => 'ex_id',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'ex_id 를 입력해주세요.'
        //     )
        // ),
    ),
    "curriculum/ahop_exam_list_" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ex_id',
            'label' => 'ex_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ex_id 를 입력해주세요.'
            )
        ),
    ),
    "curriculum/start_ahop_exam" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ex_id',
            'label' => 'ex_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ex_id 를 입력해주세요.'
            )
        ),
    ),
    "curriculum/ahop_exam_in_progress_info" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ex_id',
            'label' => 'ex_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ex_id 를 입력해주세요.'
            )
        ),
    ),
    "curriculum/ahop_exam_reset" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),
    ),
    "curriculum/ahop_exam_grade" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ex_id',
            'label' => 'ex_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ex_id 를 입력해주세요.'
            )
        ),
    ),
    "curriculum/ahop_exam_hint" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ex_id',
            'label' => 'ex_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ex_id 를 입력해주세요.'
            )
        ),
    ),
    
    "curriculum/english_article_comment_list_" => array(
        array(
            'field' => 'uid',
            'label' => 'uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'uid 를 입력해주세요.'
            )
        ),
    ),
    "curriculum/insert_english_article_comment" => array(
        array(
            'field' => 'uid',
            'label' => 'uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'uid 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lev_gubun',
            'label' => 'lev_gubun',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lev_gubun 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'comment',
            'label' => 'comment',
            'rules' => 'required',
            'errors' => array(
                'required' => 'comment 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),
    ),
    "curriculum/modify_english_article_comment" => array(
        array(
            'field' => 'uid',
            'label' => 'uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'uid 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'co_unq',
            'label' => 'co_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'co_unq 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lev_gubun',
            'label' => 'lev_gubun',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lev_gubun 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'comment',
            'label' => 'comment',
            'rules' => 'required',
            'errors' => array(
                'required' => 'comment 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),
    ),
    "curriculum/delete_english_article_comment" => array(
        array(
            'field' => 'uid',
            'label' => 'uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'uid 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'co_unq',
            'label' => 'co_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'co_unq 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),
    ),
    
    "leveltest/leveltest_" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
    ),

    "leveltest/detailed_result" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'le_id',
            'label' => 'le_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'le_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'le_fid',
            'label' => 'le_fid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'le_fid를 입력해주세요.'
            )
        ),
    ),
    
    "leveltest/checked_progress_leveltest" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'le_fid',
            'label' => 'le_fid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'le_fid를 입력해주세요.'
            )
        ),
    ),

    
    "leveltest/checked_leveltest_le_step" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
    ),
    "leveltest/delete_leveltest" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'le_fid',
            'label' => 'le_fid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'le_fid를 입력해주세요.'
            )
        ),
        array(
            'field' => 'is_app',
            'label' => 'is_app',
            'rules' => 'required',
            'errors' => array(
                'required' => 'is_app를 입력해주세요.'
            )
        ),
    ),
    "leveltest/apply_leveltest" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_gubun',
            'label' => 'lesson_gubun',
            'rules' => 'required',
            'errors' => array(
                'required' => '테스트 방식을 선택해주세요.'
            )
        ),
        array(
            'field' => 'lvt_contact',
            'label' => 'lvt_contact',
            'rules' => 'required',
            'errors' => array(
                'required' => '연락처를 입력해주세요.'
            )
        ),
        array(
            'field' => 'hopedate',
            'label' => 'hopedate',
            'rules' => 'required',
            'errors' => array(
                'required' => '예약 일자를 선택해주세요.'
            )
        ),
        array(
            'field' => 'hopetime1',
            'label' => 'hopetime1',
            'rules' => 'required',
            'errors' => array(
                'required' => '예약 시간을 선택해주세요.'
            )
        ),
        array(
            'field' => 'hopetime2',
            'label' => 'hopetime2',
            'rules' => 'required',
            'errors' => array(
                'required' => '예약 시간을 선택해주세요.'
            )
        ),
        array(
            'field' => 'englevel',
            'label' => 'englevel',
            'rules' => 'required',
            'errors' => array(
                'required' => '내가 생각하는 영어 실력을 선택해주세요.'
            )
        ),
        array(
            'field' => 're_apply',
            'label' => 're_apply',
            'rules' => 'required',
            'errors' => array(
                'required' => 're_apply를 입력해주세요.'
            )
        ),
        array(
            'field' => 'is_app',
            'label' => 'is_app',
            'rules' => 'required',
            'errors' => array(
                'required' => 'is_app를 입력해주세요.'
            )
        ),
    ),
    "point/current_situation" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
    ),

    "payment/list_" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
    ),

    
    "payment/info" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id를 입력해주세요.'
            )
        ),
    ),

    
    
    "payment/receipt" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type를 입력해주세요.'
            )
        ),
    ),

    
    "lesson/info" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id를 입력해주세요.'
            )
        ),
    ),
    "lesson/feedback_info" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id를 입력해주세요.'
            )
        ),
    ),
    "lesson/class_list_postpone" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
    ),
    "lesson/class_postpone_apply" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'reason_kind',
            'label' => 'reason_kind',
            'rules' => 'required',
            'errors' => array(
                'required' => '장기연기 신청 이유를 작성해주세요.'
            )
        ),
        array(
            'field' => 'reason_detail',
            'label' => 'reason_detail',
            'rules' => 'required',
            'errors' => array(
                'required' => '장기연기 신청 이유를 작성해주세요.'
            )
        ),
    ),
    "lesson/lesson_finish_list_" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        )
    ),
    "lesson/get_all_clear_point" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id를 입력해주세요.'
            )
        )
    ),
    "lesson/lesson_evaluation" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 're_id',
            'label' => 're_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 're_id를 입력해주세요.'
            )
        )
    ),
    "lesson/lesson_evaluation_list_" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id를 입력해주세요.'
            )
        )
    ),
    "lesson/past_lesson_schedule_list_" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id를 입력해주세요.'
            )
        )
    ),
    "lesson/lesson_article" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id를 입력해주세요.'
            )
        )
    ),
    "coupon/config" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'cp_id',
            'label' => 'cp_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'cp_id를 입력해주세요.'
            )
        ),
    ),
    "coupon/increase_limit" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'cp_id',
            'label' => 'cp_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'cp_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id를 입력해주세요.'
            )
        ),
    ),
    "coupon/register_class_coupon" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'cp_id',
            'label' => 'cp_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'cp_id를 입력해주세요.'
            )
        )
    ),

    "mset/check_mset_regist_possible_date" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
    ),
    

    "mset/regist_mset" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_gubun',
            'label' => 'lesson_gubun',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_gubun 입력해주세요.'
            )
        ),
        array(
            'field' => 'tel',
            'label' => 'tel',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tel 입력해주세요.'
            )
        ),
        array(
            'field' => 'date',
            'label' => 'date',
            'rules' => 'required',
            'errors' => array(
                'required' => 'date 입력해주세요.'
            )
        ),
        array(
            'field' => 'time',
            'label' => 'time',
            'rules' => 'required',
            'errors' => array(
                'required' => 'time 입력해주세요.'
            )
        ),
    ),


    "mset/mset_apply_list_" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'start',
            'label' => 'start',
            'rules' => 'required',
            'errors' => array(
                'required' => 'start 입력해주세요.'
            )
        ),
        array(
            'field' => 'limit',
            'label' => 'limit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'limit 입력해주세요.'
            )
        ),
        array(
            'field' => 'order_field',
            'label' => 'order_field',
            'rules' => 'required',
            'errors' => array(
                'required' => 'order_field 입력해주세요.'
            )
        ),
        array(
            'field' => 'order',
            'label' => 'order',
            'rules' => 'required',
            'errors' => array(
                'required' => 'order 입력해주세요.'
            )
        ),
    ),


    "mset/mset_cancel" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'idx',
            'label' => 'idx',
            'rules' => 'required',
            'errors' => array(
                'required' => 'idx 입력해주세요.'
            )
        ),
    ),


    "mset/mset_graph" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
    ),

    "mset/mset_result" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'idx',
            'label' => 'idx',
            'rules' => 'required',
            'errors' => array(
                'required' => 'idx를 입력해주세요.'
            )
        ),
    ),

    
    "board/adopt_anwser" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'table_code 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mb_unq 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'sim_content3',
            'label' => 'sim_content3',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sim_content3 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'select_key',
            'label' => 'select_key',
            'rules' => 'required',
            'errors' => array(
                'required' => 'select_key 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'star',
            'label' => 'star',
            'rules' => 'required',
            'errors' => array(
                'required' => 'star 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'is_app',
            'label' => 'is_app',
            'rules' => 'required',
            'errors' => array(
                'required' => 'is_app 를 입력해주세요.'
            )
        ),
    ),


    "objection/regist_report" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'claim',
            'label' => 'claim',
            'rules' => 'required',
            'errors' => array(
                'required' => 'claim 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'is_app',
            'label' => 'is_app',
            'rules' => 'required',
            'errors' => array(
                'required' => 'is_app 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'code',
            'label' => 'code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'code 를 입력해주세요.'
            )
        ),
    ),

    "objection/reason_list_" => array(
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type 를 입력해주세요.'
            )
        ),
    ),

    
    "objection/list_" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
    ),

    "objection/view" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mo_ob_idx',
            'label' => 'mo_ob_idx',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mo_ob_idx를 입력해주세요.'
            )
        ),
        array(
            'field' => 'is_app',
            'label' => 'is_app',
            'rules' => 'required',
            'errors' => array(
                'required' => 'is_app를 입력해주세요.'
            )
        ),
    ),

    "objection/delete" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mo_ob_idx',
            'label' => 'mo_ob_idx',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mo_ob_idx를 입력해주세요.'
            )
        ),
    ),

    "objection/modify_objection" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization를 입력해주세요.'
            )
        ),
        array(
            'field' => 'mo_ob_idx',
            'label' => 'mo_ob_idx',
            'rules' => 'required',
            'errors' => array(
                'required' => 'mo_ob_idx 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'claim',
            'label' => 'claim',
            'rules' => 'required',
            'errors' => array(
                'required' => 'claim 를 입력해주세요.'
            )
        ),
    ),

    
    // 강사 API 
    "_tutor/login" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID'
            )
        ),
        array(
            'field' => 'tu_pw',
            'label' => 'tu_pw',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write tu_pw'
            )
        ),
    ),
    "_tutor/special_" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write authorization'
            )
        ),
        array(
            'field' => 'table_code',
            'label' => 'table_code',
            'rules' => 'required|in_list[correction,1130]',
            'errors' => array(
                'required' => 'Write correct table_code'
            )
        ),
    ),

    "_tutor/article_special" => array(
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write mb_unq'
            )
        ),
        // array(
        //     'field' => 'authorization',
        //     'label' => 'authorization',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'authorization 를 입력해주세요.'
        //     )
        // ),
        // array(
        //     'field' => 'table_code',
        //     'label' => 'table_code',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'Write table_code'
        //     )
        // ),
    ),
    
    "_tutor/modify_article_special" => array(
        // array(
        //     'field' => 'tu_id',
        //     'label' => 'tu_id',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'Write tu_id'
        //     )
        // ),
        array(
            'field' => 'mb_unq',
            'label' => 'mb_unq',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write mb_unq'
            )
        ),
        // array(
        //     'field' => 'authorization',
        //     'label' => 'authorization',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'Write authorization'
        //     )
        // ),
        // array(
        //     'field' => 'table_code',
        //     'label' => 'table_code',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'Write table_code'
        //     )
        // ),
    ),
    
    "_tutor/tutor_info" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write authorization'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID'
            )
        ),
    ),

    "_tutor/tutor_modify" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write authorization'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID'
            )
        ),
        array(
            'field' => 'man_pw',
            'label' => 'man_pw',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write man_pw'
            )
        ),
    ),

    "_tutor/board_notice_list_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),
    "_tutor/board_notice_article" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no를 확인해주세요.'
            )
        ) 
    ),
    "_tutor/board_toteacher_list_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),
    "_tutor/board_toteacher_article" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no를 확인해주세요.'
            )
        ) 
    ),
    "_tutor/board_mantutor_list_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),
    "_tutor/board_mantutor_article" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no를 확인해주세요.'
            )
        ) 
    ),
    "_tutor/board_message_write" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
            ),
        array(
            'field' => 'uid',
            'label' => 'uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'uid을 입력해주세요.'
            )
        )
    ),
    "_tutor/board_message_reply_modify" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no 번호를 확인해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),
    "_tutor/board_message_content_modify" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no 번호를 확인해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),
    "_tutor/board_mantutor_write" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),
    "_tutor/board_mantutor_modify" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no번호를 확인해주세요.'
            )
        )
    ),
    "_tutor/notice_article_comment" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no를 확인해주세요.'
            )
        ) 
    ),
    "_tutor/notice_modify_comment" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no를 확인해주세요.'
            )
        ) 
    ),
    "_tutor/notice_delete_comment" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        ),
        array(
            'field' => 'sub_no',
            'label' => 'sub_no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sub_no를 확인해주세요.'
            )
        ) 
    ),
    "_tutor/student_list_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),
    "_tutor/selectbox_student_list_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),
    "_tutor/textbooks_list_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),

    "_tutor/monthly_reports" => array(
        
        // array(
        //     'field' => 'tu_id',
        //     'label' => 'tu_id',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'Write ID, PASSWORD'
        //     )
        // ),

        // array(
        //     'field' => 'authorization',
        //     'label' => 'authorization',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'authorization 입력해주세요.'
        //     )
        // ),

        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type 입력해주세요.'
            )
        ),
        
    ),

    "_tutor/report_view" => array(
        
        // array(
        //     'field' => 'tu_id',
        //     'label' => 'tu_id',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'Write ID, PASSWORD'
        //     )
        // ),

        // array(
        //     'field' => 'authorization',
        //     'label' => 'authorization',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'authorization 입력해주세요.'
        //     )
        // ),

        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type 입력해주세요.'
            )
        ),
        
    ),

    "_tutor/report_update" => array(
        // array(
        //     'field' => 'tu_id',
        //     'label' => 'tu_id',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'Write ID, PASSWORD'
        //     )
        // ),

        // array(
        //     'field' => 'authorization',
        //     'label' => 'authorization',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'authorization 입력해주세요.'
        //     )
        // ),
        array(
            'field' => 'listening',
            'label' => 'listening',
            'rules' => 'required',
            'errors' => array(
                'required' => 'listening 입력해주세요.'
            )
        ),
        array(
            'field' => 'speaking',
            'label' => 'speaking',
            'rules' => 'required',
            'errors' => array(
                'required' => 'speaking 입력해주세요.'
            )
        ),
        array(
            'field' => 'pronunciation',
            'label' => 'pronunciation',
            'rules' => 'required',
            'errors' => array(
                'required' => 'pronunciation 입력해주세요.'
            )
        ),
        array(
            'field' => 'vocabulary',
            'label' => 'vocabulary',
            'rules' => 'required',
            'errors' => array(
                'required' => 'vocabulary 입력해주세요.'
            )
        ),
        array(
            'field' => 'grammar',
            'label' => 'grammar',
            'rules' => 'required',
            'errors' => array(
                'required' => 'grammar 입력해주세요.'
            )
        ),
        array(
            'field' => 'ev_memo',
            'label' => 'ev_memo',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ev_memo 입력해주세요.'
            )
        ),
        array(
            'field' => 'gra_memo',
            'label' => 'gra_memo',
            'rules' => 'required',
            'errors' => array(
                'required' => 'gra_memo 입력해주세요.'
            )
        ),
        
        
    ),

    // 퀘스트 API
    "quest/quest" => array(
        array(
            'field' => 'q_idx',
            'label' => 'q_idx',
            'rules' => 'required',
            'errors' => array(
                'required' => 'q_idx를 확인해주세요'
            )
        )
    ),

    // 강사사이트 로그인
    "_tutor/login" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Please enter your ID.'
            )
        ),
        array(
            'field' => 'tu_pw',
            'label' => 'tu_pw',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Please enter your password.',
            )
        )
    ),

    // 인센티브 리스트
    "_tutor/incentive_list_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),
    // 학생 수업 변경 리스트
    "_tutor/student_change_list_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),
    //mset history
    "_tutor/mset_history_list_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),

    
    "_tutor/schedule_" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),
        array(
             'field' => 'day',
             'label' => 'day',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'day 입력해주세요.'
             )
        ),
        
    ),
    
    "_tutor/set_break" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),
        array(
             'field' => 'break_time',
             'label' => 'break_time',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'break_time 입력해주세요.'
             )
        ),
        array(
             'field' => 'break_set',
             'label' => 'break_set',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'break_set 입력해주세요.'
             )
        ),
        
    ),

    "_tutor/schedule_board_list_" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),        
    ),

    "_tutor/monthly_reports_due_count" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),
    ),
    "_tutor/monthly_reports_due_list_" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),
    ),
    "_tutor/monthly_reports_due_article" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),        
    ),
    "_tutor/monthly_reports_due_update" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),        
    ),
    "_tutor/monthly_reports_complete_update" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),        
    ),
    "_tutor/monthly_reports_complete_list_" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),        
    ),
    "_tutor/monthly_reports_complete_article" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),        
    ),

    "_git/git_pull" => array(
        // array(
        //     'field' => 'wiz_id',
        //     'label' => 'wiz_id',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'wiz_id 입력해주세요.'
        //     )
        // ),
        // array(
        //      'field' => 'authorization',
        //      'label' => 'authorization',
        //      'rules' => 'required',
        //      'errors' => array(
        //          'required' => 'authorization 입력해주세요.'
        //      )
        // ),
        array(
            'field' => 'code',
            'label' => 'code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'code 입력해주세요.'
            )
        ),        
    ),

    
    "_tutor/schedule_article" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),
        array(
             'field' => 'lesson_id',
             'label' => 'lesson_id',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'lesson_id를 입력해주세요.'
             )
        ),
    ),
    "_tutor/modify_schedule" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),
        array(
             'field' => 'lesson_id',
             'label' => 'lesson_id',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'lesson_id를 입력해주세요.'
             )
        ),
        array(
             'field' => 'sc_id',
             'label' => 'sc_id',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'sc_id를 입력해주세요.'
             )
        ),
    ),

    "_tutor/modify_schedule_new" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),
        array(
             'field' => 'lesson_id',
             'label' => 'lesson_id',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'lesson_id를 입력해주세요.'
             )
        ),
        array(
             'field' => 'sc_id',
             'label' => 'sc_id',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'sc_id를 입력해주세요.'
             )
        ),
    ),

    "_tutor/checked_maalk_history_result" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),
        array(
             'field' => 'sc_id',
             'label' => 'sc_id',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'sc_id를 입력해주세요.'
             )
        ),
    ),
    "_tutor/get_member_with_sms_templete" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),
        array(
             'field' => 'uid',
             'label' => 'uid',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'uid를 입력해주세요.'
             )
        ),
    ),
    "_tutor/tutor_send_sms" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),
        array(
             'field' => 'mobile',
             'label' => 'mobile',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'mobile를 입력해주세요.'
             )
        ),
        array(
             'field' => 'content',
             'label' => 'content',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'content를 입력해주세요.'
             )
        ),
    ),
    "_tutor/dpr" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),     
        array(
             'field' => 'sdate',
             'label' => 'sdate',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'sdate 입력해주세요.'
             )
        ),  
        array(
             'field' => 'edate',
             'label' => 'edate',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'edate 입력해주세요.'
             )
        ), 
    ),

    
    "_tutor/dpr_schedule_list_" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),     
        array(
             'field' => 'sdate',
             'label' => 'sdate',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'sdate 입력해주세요.'
             )
        ),  
        array(
             'field' => 'edate',
             'label' => 'edate',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'edate 입력해주세요.'
             )
        ), 
        array(
             'field' => 'order',
             'label' => 'order',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'order 입력해주세요.'
             )
        ), 
        array(
             'field' => 'order_field',
             'label' => 'order_field',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'order_field 입력해주세요.'
             )
        ), 
        array(
             'field' => 'start',
             'label' => 'start',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'start 입력해주세요.'
             )
        ), 
        array(
             'field' => 'limit',
             'label' => 'limit',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'limit 입력해주세요.'
             )
        ), 
    ),

    "_tutor/schedule_calendar" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),     
        array(
             'field' => 'lesson_id',
             'label' => 'lesson_id',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'lesson_id 입력해주세요.'
             )
        ), 
    ),

    
    
    "_tutor/sendsms_missed_call" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),     
        array(
             'field' => 'sc_id',
             'label' => 'sc_id',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'sc_id 입력해주세요.'
             )
        ),  
    ),

    "_tutor/mset_report" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),     
        array(
             'field' => 'sc_id',
             'label' => 'sc_id',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'sc_id 입력해주세요.'
             )
        ),  
    ),
    
    "_tutor/update_mset_report" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
             'field' => 'authorization',
             'label' => 'authorization',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'authorization 입력해주세요.'
             )
        ),     
        array(
             'field' => 'sc_id',
             'label' => 'sc_id',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'sc_id 입력해주세요.'
             )
        ),
        array(
             'field' => 'mset_idx',
             'label' => 'mset_idx',
             'rules' => 'required',
             'errors' => array(
                 'required' => 'mset_idx 입력해주세요.'
             )
        ),
    ),

    "_tutor/popup_leveltest_list" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
        'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'uid',
            'label' => 'uid',
            'rules' => 'required',
            'errors' => array(
                'required' => 'uid 입력해주세요.'
            )
        ), 
    ),
    "_tutor/popup_leveltest_view" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
        'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),     
        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id 입력해주세요.'
            )
        ),  
        // array(
        //     'field' => 'uid',
        //     'label' => 'uid',
        //     'rules' => 'required',
        //     'errors' => array(
        //         'required' => 'uid 입력해주세요.'
        //     )
        // ), 
    ),
    "_tutor/update_popup_leveltest" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
        'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),     
        array(
            'field' => 'le_id',
            'label' => 'le_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'le_id 입력해주세요.'
            )
        ),  
        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id 입력해주세요.'
            )
        ),  
        array(
            'field' => 'present',
            'label' => 'present',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Attendance error 1 present'
            )
        ),
    ),
    
    "_tutor/join_mint_english" => array(
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 입력해주세요.'
            )
        ),
        array(
        'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),     
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type 입력해주세요.'
            )
        ),  
        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id 입력해주세요.'
            )
        ),  
    ),

    "member/update_greeting" => array(
        
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),

        array(
            'field' => 'greeting',
            'label' => 'greeting',
            'rules' => 'required',
            'errors' => array(
                'required' => '소개말을 입력해주세요.'
            )
        ),

    ),

    "member/create_member_with_leveltest" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => '아이디를 입력해주세요.'
            )
        ),
        // array(
        //     'field' => 'wiz_pw',
        //     'label' => 'wiz_pw',
        //     'rules' => 'required|trim|min_length[6]|max_length[16]',
        //     'errors' => array(
        //         'required' => '패스워드를 입력해주세요.',
        //         'trim' => '패스워드에는 공백을 사용할 수 없습니다.',
        //         'min_length' => '패스워드는 6글자에서 16글자까지 입력 가능합니다.',
        //         'max_length' => '패스워드는 6글자에서 16글자까지 입력 가능합니다.'
        //     )
        // ),
        array(
            'field' => 'name',
            'label' => 'name',
            'rules' => 'required|trim|min_length[2]|max_length[16]',
            'errors' => array(
                'required' => '이름을 입력해주세요.',
                'trim' => '이름에는 공백을 사용할 수 없습니다.',
                'min_length' => '이름은 2글자에서 16글자까지 입력 가능합니다.',
                'max_length' => '이름은 2글자에서 16글자까지 입력 가능합니다.'
            )
        ),
        array(
            'field' => 'birth',
            'label' => 'birth',
            'rules' => 'required',
            'errors' => array(
                'required' => '생년월일을 입력해주세요.'
            )
        ),
        array(
            'field' => 'gender',
            'label' => 'gender',
            'rules' => 'required',
            'errors' => array(
                'required' => '성별을 선택해주세요.'
            )
        ),
        array(
            'field' => 'regi_area',
            'label' => 'regi_area',
            'rules' => 'required',
            'errors' => array(
                'required' => '거주지를 선택해주세요.'
            )
        ),
        array(
            'field' => 'contact',
            'label' => 'contact',
            'rules' => 'required',
            'errors' => array(
                'required' => '연락처를 입력해주세요.'
            )
        ),
        array(
            'field' => 'email',
            'label' => 'email',
            'rules' => 'required',
            'errors' => array(
                'required' => 'email 를 선택해주세요.'
            )
        ),
        array(
            'field' => 'lesson_gubun',
            'label' => 'lesson_gubun',
            'rules' => 'required',
            'errors' => array(
                'required' => '테스트 방식을 선택해주세요.'
            )
        ),
        array(
            'field' => 'lvt_contact',
            'label' => 'lvt_contact',
            'rules' => 'required',
            'errors' => array(
                'required' => '레벨테스트 연락처를 입력해주세요.'
            )
        ),
        array(
            'field' => 'hopedate',
            'label' => 'hopedate',
            'rules' => 'required',
            'errors' => array(
                'required' => '예약 일자를 선택해주세요.'
            )
        ),
        array(
            'field' => 'hopetime1',
            'label' => 'hopetime1',
            'rules' => 'required',
            'errors' => array(
                'required' => '예약 시간을 선택해주세요.'
            )
        ),
        array(
            'field' => 'hopetime2',
            'label' => 'hopetime2',
            'rules' => 'required',
            'errors' => array(
                'required' => '예약 시간을 선택해주세요.'
            )
        ),
        array(
            'field' => 'englevel',
            'label' => 'englevel',
            'rules' => 'required',
            'errors' => array(
                'required' => '내가 생각하는 영어 실력을 선택해주세요.'
            )
        )
    ),

    "quest/quest_list" => array(
        array(
            'field' => 'type',
                'label' => 'type',
                'rules' => 'required',
                'errors' => array(
                    'required' => 'type 입력해주세요.'
                )
            ),
    ),

    "quest/subquest_list" => array(
        array(
            'field' => 'q_idx',
                'label' => 'q_idx',
                'rules' => 'required',
                'errors' => array(
                    'required' => 'q_idx 입력해주세요.'
                )
            ),
    ),

    "quest/get_reward" => array(
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 입력해주세요.'
            )
        ),
        array(
        'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'q_idx',
                'label' => 'q_idx',
                'rules' => 'required',
                'errors' => array(
                    'required' => 'q_idx 입력해주세요.'
                )
            ),
    ),

    "quest/complete_quest_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
    ),

    
    "payment/order_goods_confirm" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'goods_id',
            'label' => 'goods_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'goods_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'goods_type',
            'label' => 'goods_type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'goods_type 를 입력해주세요.'
            )
        ),
    ),

    
    "payment/order_prepay" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'goods_id',
            'label' => 'goods_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'goods_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'goods_type',
            'label' => 'goods_type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'goods_type 를 입력해주세요.'
            )
        ),
    ),

    "payment/order_hubcard_call" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'prepay_id',
            'label' => 'prepay_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'prepay_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'site_cd',
            'label' => 'site_cd',
            'rules' => 'required',
            'errors' => array(
                'required' => 'site_cd 를 입력해주세요.'
            )
        ),
    ),

    
    "payment/order_cash" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'prepay_id',
            'label' => 'prepay_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'prepay_id 를 입력해주세요.'
            )
        ),
    ),

    "event/sms_promotion_check" => array(
        array(
            'field' => 'code',
            'label' => 'code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'code 를 입력해주세요.'
            )
        ),
    ),

    "event/event_goods" => array(
        array(
            'field' => 'event_code',
            'label' => 'event_code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'event_code 를 입력해주세요.'
            )
        ),
    ),


    "lesson/start_free_lesson_class" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'lesson_id',
            'label' => 'lesson_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'lesson_id 를 입력해주세요.'
            )
        ),
    ),

    
    

    "payment/order_pay_mobile_ready" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'wiz_id',
            'label' => 'wiz_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'wiz_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'site_cd',
            'label' => 'site_cd',
            'rules' => 'required',
            'errors' => array(
                'required' => 'site_cd 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'prepay_id',
            'label' => 'prepay_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'prepay_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'pay_method',
            'label' => 'pay_method',
            'rules' => 'required',
            'errors' => array(
                'required' => 'pay_method 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'Ret_URL',
            'label' => 'Ret_URL',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Ret_URL 를 입력해주세요.'
            )
        ),
    ),


    "_tutor/check_possible_extend_class" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id 를 입력해주세요.'
            )
        ),
    ),

    "_tutor/send_request_extend_class" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'tu_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'sc_id',
            'label' => 'sc_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'sc_id 를 입력해주세요.'
            )
        ),
    ),
    
    "lesson/extend_class" => array(
        array(
            'field' => 'code',
            'label' => 'code',
            'rules' => 'required',
            'errors' => array(
                'required' => 'code 를 입력해주세요.'
            )
        ),
    ),
    

    
    // admin API 
    "admin/manager/login" => array(
        array(
            'field' => 'a_id',
            'label' => 'a_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID'
            )
        ),
        array(
            'field' => 'a_pw',
            'label' => 'a_pw',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write PW'
            )
        ),
    ),

    "admin/ahop/list_" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        )        
        
    ),

    "admin/ahop/ahop_exam_change_time" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'exam_idxs[]',
            'label' => 'exam_idxs[]',
            'rules' => 'required',
            'errors' => array(
                'required' => 'exam_idxs[] 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'select_minute',
            'label' => 'select_minute',
            'rules' => 'required',
            'errors' => array(
                'required' => 'select_minute 를 입력해주세요.'
            )
        ),
    ),

    "admin/ahop/ahop_exam_change_use" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ex_id',
            'label' => 'ex_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ex_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'use_yn',
            'label' => 'use_yn',
            'rules' => 'required',
            'errors' => array(
                'required' => 'use_yn 를 입력해주세요.'
            )
        ),
    ),

    "admin/ahop/view_ahop_exam" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ex_id',
            'label' => 'ex_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ex_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'parent_id',
            'label' => 'parent_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'parent_id 를 입력해주세요.'
            )
        ),
    ),

    "admin/ahop/delete_ahop_exam" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ex_id',
            'label' => 'ex_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ex_id 를 입력해주세요.'
            )
        ),
    ),

    "admin/ahop/ahop_curriculum_option" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        
    ),

    "admin/ahop/get_ahop_chapter_info" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),
    ),
    
    "admin/ahop/write_ahop_exam" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),
    ),

    "admin/ahop/modify_ahop_exam" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'book_id',
            'label' => 'book_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'book_id 를 입력해주세요.'
            )
        ),
    ),

    "admin/ahop/ahop_curriculum_option_save" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
    ),

    "admin/ahop/list_category" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        
    ),
    "admin/ahop/test_ahop_exam" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ex_id',
            'label' => 'ex_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ex_id 를 입력해주세요.'
            )
        )
    ),
    "admin/ahop/test_ahop_exam_in_progress_info" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ex_id',
            'label' => 'ex_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ex_id 를 입력해주세요.'
            )
        )
    ),
    "admin/ahop/test_ahop_exam_grade" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ex_id',
            'label' => 'ex_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ex_id 를 입력해주세요.'
            )
        )
    ),
    "admin/ahop/test_ahop_exam_hint" => array(
        array(
            'field' => 'manager_id',
            'label' => 'manager_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'manager_id 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 를 입력해주세요.'
            )
        ),
        array(
            'field' => 'ex_id',
            'label' => 'ex_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'ex_id 를 입력해주세요.'
            )
        )
    ),

    "admin/log/rate_tutor_resign_and_student_share" => array(
        array(
            'field' => 'year',
            'label' => 'year',
            'rules' => 'required',
            'errors' => array(
                'required' => 'year'
            )
        ),
        
    ),

    "_tutor/drb_list_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),
    "_tutor/drb_article" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no를 확인해주세요.'
            )
        ) 
    ),
    "_tutor/drb_comment_list_" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no를 확인해주세요.'
            )
        ) 
    ),
    "_tutor/drb_write" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        )
    ),
    "_tutor/drb_modify" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no를 확인해주세요.'
            )
        ) 
    ),
    "_tutor/drb_delete" => array(
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 입력해주세요.'
            )
        ),
        array(
            'field' => 'tu_id',
            'label' => 'tu_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'Write ID, PASSWORD'
            )
        ),
        array(
            'field' => 'no',
            'label' => 'no',
            'rules' => 'required',
            'errors' => array(
                'required' => 'no를 확인해주세요.'
            )
        ) 
    ),










    "test/get_dooropen" => array(
        
        array(
            'field' => 'order',
            'label' => 'order',
            'rules' => 'required',
            'errors' => array(
                'required' => 'order 를 확인해주세요.'
            )
        ) 
    ),

    "test/get_bossline" => array(
        
        array(
            'field' => 'order',
            'label' => 'order',
            'rules' => 'required',
            'errors' => array(
                'required' => 'order 를 확인해주세요.'
            )
        ) 
    ),

    "user/sign_in" => array(
        
        array(
            'field' => 'user_id',
            'label' => 'user_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'user_id 를 확인해주세요.'
            )
        ) 
    ),
    "server/server_list" => array(
        
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type 을 확인해주세요.'
            )
        ) 
    ),


);