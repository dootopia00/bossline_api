<?php  
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

echo phpinfo();exit;
$config['bossline_url'] = 'https://bossline.gg';


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
            
            // dooropen
            $master_ip = 'dooropen-dev.cx1zesoiaqke.ap-northeast-2.rds.amazonaws.com';
            $slave_ip = 'dooropen-dev.cx1zesoiaqke.ap-northeast-2.rds.amazonaws.com';
            $search_ip = 'dooropen-dev.cx1zesoiaqke.ap-northeast-2.rds.amazonaws.com';
            
            // bossline
            // $master_ip = 'bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
            // $slave_ip =  'bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
            // $search_ip = 'bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';

        break;

        // 마스터 디비
        case "api.bossline.gg":
            $master_ip = 'bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
            $slave_ip =  'bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
            $search_ip = 'bossline-db.ce5gwofdrutx.ap-northeast-2.rds.amazonaws.com';
        break;

    }
        
}

// dooropen
$config['master_db']['addr'] = $master_ip;                  
$config['master_db']['user'] = 'admin';
$config['master_db']['pass'] = 'dooropen2021';

$config['slave_db']['addr'] = $slave_ip;                    
$config['slave_db']['user'] = 'admin';
$config['slave_db']['pass'] = 'dooropen2021';


// bossline
// $config['master_db']['addr'] = $master_ip;                  
// $config['master_db']['user'] = 'bossline_db';
// $config['master_db']['pass'] = 'bosslinedev';

// $config['slave_db']['addr'] = $slave_ip;                    
// $config['slave_db']['user'] = 'bossline_db';
// $config['slave_db']['pass'] = 'bosslinedev';




$config['dsn']=array();
// dooropen
$config['dsn']['master']  = 'MySQLi://'.$config['master_db']['user'].':'.$config['master_db']['pass'].'@'.$config['master_db']['addr'].'/dooropen?charset=utf8&DBCollat=utf8_general_ci&dbdriver=mysqli';
$config['dsn']['slave']   = 'MySQLi://'.$config['slave_db']['user'].':'.$config['slave_db']['pass'].'@'.$config['slave_db']['addr'].'/dooropen?charset=utf8&DBCollat=utf8_general_ci&dbdriver=mysqli';

// bossline
// $config['dsn']['master']  = 'Postgre://'.$config['master_db']['user'].':'.$config['master_db']['pass'].'@'.$config['master_db']['addr'].':5432/postgres?charset=utf8&connect_timeout=5&sslmode=1';
// $config['dsn']['slave']   = 'Postgre://'.$config['slave_db']['user'].':'.$config['slave_db']['pass'].'@'.$config['slave_db']['addr'].':5432/postgres?charset=utf8&connect_timeout=5&sslmode=1';

