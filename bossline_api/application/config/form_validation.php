<?php

$config = array(

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

    "clan/clan_list" => array(
        
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type 을 확인해주세요.'
            )
        ) 
    ),

    "clan/clan_insert" => array(
        
        array(
            'field' => 'user_id',
            'label' => 'user_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'user_id 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'clan_name',
            'label' => 'clan_name',
            'rules' => 'required',
            'errors' => array(
                'required' => 'clan_name 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'recruit',
            'label' => 'recruit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'recruit 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'server',
            'label' => 'server',
            'rules' => 'required',
            'errors' => array(
                'required' => 'server 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'clan_level',
            'label' => 'clan_level',
            'rules' => 'required',
            'errors' => array(
                'required' => 'clan_level 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'level',
            'label' => 'level',
            'rules' => 'required',
            'errors' => array(
                'required' => 'level 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'defense',
            'label' => 'defense',
            'rules' => 'required',
            'errors' => array(
                'required' => 'defense 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'job',
            'label' => 'job',
            'rules' => 'required',
            'errors' => array(
                'required' => 'job 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'description',
            'label' => 'description',
            'rules' => 'required',
            'errors' => array(
                'required' => 'description 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'welfare',
            'label' => 'welfare',
            'rules' => 'required',
            'errors' => array(
                'required' => 'welfare 을 확인해주세요.'
            )
        ),
    ),

    "clan/get_clan_info" => array(
        
        array(
            'field' => 'clan_pk',
            'label' => 'clan_pk',
            'rules' => 'required',
            'errors' => array(
                'required' => 'clan_pk 을 확인해주세요.'
            )
        ) 
    ),

    "clan/clan_modify" => array(
        
        array(
            'field' => 'user_id',
            'label' => 'user_id',
            'rules' => 'required',
            'errors' => array(
                'required' => 'user_id 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'authorization',
            'label' => 'authorization',
            'rules' => 'required',
            'errors' => array(
                'required' => 'authorization 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'clan_pk',
            'label' => 'clan_pk',
            'rules' => 'required',
            'errors' => array(
                'required' => 'clan_pk 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'clan_name',
            'label' => 'clan_name',
            'rules' => 'required',
            'errors' => array(
                'required' => 'clan_name 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'recruit',
            'label' => 'recruit',
            'rules' => 'required',
            'errors' => array(
                'required' => 'recruit 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'server',
            'label' => 'server',
            'rules' => 'required',
            'errors' => array(
                'required' => 'server 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'type',
            'label' => 'type',
            'rules' => 'required',
            'errors' => array(
                'required' => 'type 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'clan_level',
            'label' => 'clan_level',
            'rules' => 'required',
            'errors' => array(
                'required' => 'clan_level 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'level',
            'label' => 'level',
            'rules' => 'required',
            'errors' => array(
                'required' => 'level 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'defense',
            'label' => 'defense',
            'rules' => 'required',
            'errors' => array(
                'required' => 'defense 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'job',
            'label' => 'job',
            'rules' => 'required',
            'errors' => array(
                'required' => 'job 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'description',
            'label' => 'description',
            'rules' => 'required',
            'errors' => array(
                'required' => 'description 을 확인해주세요.'
            )
        ),
        array(
            'field' => 'welfare',
            'label' => 'welfare',
            'rules' => 'required',
            'errors' => array(
                'required' => 'welfare 을 확인해주세요.'
            )
        ),
    ),

    "user/get_user_info" => array(
        
        array(
            'field' => 'user_pk',
            'label' => 'user_pk',
            'rules' => 'required',
            'errors' => array(
                'required' => 'user_pk 을 확인해주세요.'
            )
        ) 
    ),
);