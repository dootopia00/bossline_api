<?php  
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// $config['bossline_url'] = 'https://bossline.gg';


/*
|----------------------------------
| Database configuration 
|---------------------------------- 
*/
$master_ip = NULL;
$slave_ip = NULL;
$search_ip = NULL;

// 일반 웹접속일때 체크
if($_SERVER['HTTP_HOST'])
{
    switch($_SERVER['HTTP_HOST'])
    {

        // 개발 디비
        case "localhost:9000":
            
            // bossline maria db
            $master_ip = 'bossline-maria-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
            $slave_ip =  'bossline-maria-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
            $search_ip = 'bossline-maria-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
            
            // bossline postgresql
            // $master_ip = 'bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
            // $slave_ip =  'bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
            // $search_ip = 'bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';

        break;

        // 마스터 디비
        case "api.bossline.gg":
            
            // bossline maria db
            $master_ip = 'bossline-maria-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
            $slave_ip =  'bossline-maria-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
            $search_ip = 'bossline-maria-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';

            // bossline postgresql
            // $master_ip = 'bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
            // $slave_ip =  'bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
            // $search_ip = 'bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
        break;

    }
        
}

// bossline maria db
$config['master_db']['addr'] = $master_ip;                  
$config['master_db']['user'] = 'admin';
$config['master_db']['pass'] = 'bosslinedev';

$config['slave_db']['addr'] = $slave_ip;                    
$config['slave_db']['user'] = 'admin';
$config['slave_db']['pass'] = 'bosslinedev';

// bossline postgresql
// $config['master_db']['addr'] = $master_ip;                  
// $config['master_db']['user'] = 'bossline_db';
// $config['master_db']['pass'] = 'bosslinedev';

// $config['slave_db']['addr'] = $slave_ip;                    
// $config['slave_db']['user'] = 'bossline_db';
// $config['slave_db']['pass'] = 'bosslinedev';




$config['dsn']=array();

######### dbdriver 는 소문자여야함 mysqli/postgre

// bossline maria db
$config['dsn']['master']  = 'mysqli://'.$config['master_db']['user'].':'.$config['master_db']['pass'].'@'.$config['master_db']['addr'].'/bossline_maria_db?charset=utf8&DBCollat=utf8_general_ci&dbdriver=mysqli';
$config['dsn']['slave']   = 'mysqli://'.$config['slave_db']['user'].':'.$config['slave_db']['pass'].'@'.$config['slave_db']['addr'].'/bossline_maria_db?charset=utf8&DBCollat=utf8_general_ci&dbdriver=mysqli';  

// bossline postgresql 
// $config['dsn']['master']  = 'postgre://'.$config['master_db']['user'].':'.$config['master_db']['pass'].'@'.$config['master_db']['addr'].':5432/postgres?charset=utf8&connect_timeout=5&sslmode=1';
// $config['dsn']['slave']   = 'postgre://'.$config['slave_db']['user'].':'.$config['slave_db']['pass'].'@'.$config['slave_db']['addr'].':5432/postgres?charset=utf8&connect_timeout=5&sslmode=1';

// postgre://bossline_db:bosslinedev@bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com:5432/postgres
// postgresql://bossline_db:bosslinedev@bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com:5432/postgres
