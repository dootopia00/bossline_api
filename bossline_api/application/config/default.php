<?php  
if ( ! defined('BASEPATH')) exit('No direct script access allowed');



$config['mint_url'] = [
    'book'      => 'http://mintbook.mint05.com',
    'old_pc'    => 'http://www.mint05.com',
    'old_m'     => 'http://m.mint05.com',
];

$config['web_domain'] = 'https://story.mint05.com';


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
        /* 로컬환경일때 > 개발DB연결(공인IP) */
        case "localhost:8000":
            // // dev DB
            // $master_ip = '211.252.87.110';
            // $slave_ip = '211.252.87.110';
            // $search_ip = '211.252.87.110';
            
            // live DB
            $master_ip = '211.43.14.34';
            $slave_ip = '211.43.14.34';
            $search_ip = '211.253.38.236';
        break;
        /* 개발서버일때 > 개발DB연결(내부IP) */
        case "dsapi.mintspeaking.com":
            $master_ip = 'localhost';
            $slave_ip = 'localhost';
            $search_ip = 'localhost';
            // $master_ip = '172.27.0.136';
            // $slave_ip = '172.27.0.136'; 
        break;
        /* 운영서버일때 > 라이브DB연결(내부IP) */
        case "api.mint05.com":
            $master_ip = '172.27.0.136';
            // $slave_ip = '172.27.0.136';
            //slave 프록시서버 내부IP
            $slave_ip = '172.27.0.136';
            $search_ip = '172.27.0.106';
        break;
    }
        
}
// 크론처럼 쉘에서 직접 명령어 쳤을때
else
{
    $server_host_name = $_SERVER['HOSTNAME'] ? $_SERVER['HOSTNAME']:gethostname();
    // batch 같은 쉘로 돌아갈 경우 HTTP_HOST가 없어서 HOSTNAME으로 구분
    // 2020-10-05 기준 edusub-API는 하나뿐인 라이브 API서버
    switch($server_host_name)
    {
        /* 개발서버일때 > 개발DB연결(내부IP) */
        case "edusub-DB":
            $master_ip = 'localhost';
            $slave_ip = 'localhost';
            $search_ip = 'localhost';
        break;
        /* 운영서버일때 > 라이브DB연결(내부IP) */
        case "edusub-API":
            $master_ip = '172.27.0.136';
            $slave_ip = '172.27.0.136';
            $search_ip = '172.27.0.106';
        break;
        /* 그외 로컬환경일때 > 개발DB연결(공인IP) */
        default:
            $master_ip = '211.252.87.110';
            $slave_ip = '211.252.87.110';
            $search_ip = '211.252.87.110';
        break;
    }

}

// $config['master_db']['addr'] = '211.43.14.34';          //마스터 디비 공인IP
// $config['master_db']['addr'] = '172.27.0.136';          //마스터 디비 내부IP
$config['master_db']['addr'] = $master_ip;     //개발 디비
$config['master_db']['user'] = 'root';
$config['master_db']['pass'] = '9a8mN6mXPXXU';


// $config['slave_db']['addr'] = '211.43.14.34';           //마스터 디비 공인IP
// $config['slave_db']['addr'] = '172.27.0.136';           //마스터 디비 내부IP
$config['slave_db']['addr'] = $slave_ip;      //개발 디비
$config['slave_db']['user'] = 'root';
$config['slave_db']['pass'] = '9a8mN6mXPXXU';



$config['dsn']=array();
$config['dsn']['master']  = 'mysql://'.$config['master_db']['user'].':'.$config['master_db']['pass'].'@'.$config['master_db']['addr'].'/db_acephone?char_set=euckr&dbcollat=euckr_korean_ci&dbdriver=mysqli';
$config['dsn']['slave']   = 'mysql://'.$config['slave_db']['user'].':'.$config['slave_db']['pass'].'@'.$config['slave_db']['addr'].'/db_acephone?char_set=euckr&dbcollat=euckr_korean_ci&dbdriver=mysqli';
