<?php

include_once 'aws/aws-autoloader.php';

define('S3_KEY', 'AKIAWC3MP6X6SW7JSW4G');
define('S3_SECRET_KEY', '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');
define('BUCKET', 'cdn-mintspeaking-com');
// define('BUCKET_TEST', 'cdn-mint-summernote');

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class S3{

    function __construct()
    {

    }
    public static function put_s3_object($upload_path, $file, $upload_limit_size, $ext_array = array(),$file_name='')
    {
        $return_array = array();

        try
        {
            if(isset($file))
            {
                
                $tmp_file = $file['tmp_name'];
                $origin_name = $file['name'];
                $type = $file['type'];
                $extension = explode('.', $origin_name);
                //. 기준으로 확장자 찾기 위한 배열의 마지막 값
                $extension = end($extension);
                
                $size = $file['size'];
                $max_upload_size = 1047576 * $upload_limit_size;   // default 1MB;

                if(!in_array(strtolower($extension), $ext_array))
                {
                    $ext_text = implode(', ', $ext_array);
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = '0901';
                    $return_array['data']['err_msg'] = $ext_text. ' 파일 업로드만 가능합니다.';
                
                    return $return_array;
                }

                if($size > $max_upload_size)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = '0902';
                    $return_array['data']['err_msg'] = $upload_limit_size. 'MB보다 큰 이미지는 업로드 할 수 없습니다.';
                
                    return $return_array;
                }

                $c_time = date("YmdHis");
                $rand_num = rand(0,1000);
                
                if(!$file_name) $file_name = $c_time. $rand_num.'.'.$extension;
                
                $s3_file_path = $upload_path.$file_name;    //s3 파일 저장 경로
        
                $s3Client = S3Client::factory(array(
                    'region' => 'ap-northeast-2',
                    'version' => 'latest',
                    'signature' => 'v4',
                    'credentials' => array(
                        'key'    => S3_KEY,
                        'secret' => S3_SECRET_KEY
                    )
                ));
        
                $file_data = file_get_contents($tmp_file);
                
                if($extension == 'mp3')
                {
					$ContentType = 'audio/mpeg';
                }
                elseif($extension == 'mp4')
                {
					$ContentType = 'video/mp4';
                }
                else
                {
					$ContentType = $file['type'];
                }

                $s3Client->putObject(array(
                    'Bucket' => BUCKET,
                    'Key'    => $s3_file_path,   //Key -> bucket 안에 파일 저장할 경로
                    'Body'   => $file_data,
                    'ACL'    => 'public-read',
                    'ContentType' => $ContentType,
                ));
        
                $return_array['res_code'] = '0000';
                $return_array['msg'] = 's3업로드에 성공하였습니다.';
                $return_array['file_name'] = $file_name;
                $return_array['file_name_origin'] = $origin_name;
                $return_array['url'] = 'https://cdn.mintspeaking.com/'.$upload_path.$file_name;
        
                return $return_array;
            }
        }
        catch(Exception $e)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = 's3업로드에 실패하였습니다.';
            return $return_array;
        }
    }

    
    static function delete_s3_object($path='', $file_name='', $full_path_file='', $forced_del=false)
    {
        $s3Client = S3Client::factory(array(
            'region' => 'ap-northeast-2',
            'version' => 'latest',
            'signature' => 'v4',
            'credentials' => array(
                'key'    => S3_KEY,
                'secret' => S3_SECRET_KEY
            )
        ));
        try
        {
            if( (isset($file_name) && $file_name != '') || $full_path_file !='')
            {
                $full_path_file = $full_path_file ? $full_path_file : $path.$file_name;

                /* 
                    mint_boards_files 테이블안에 파일이 두가지 형태로 저장됨
                        -> ex) 1. https://cdn.mintspeaking.com/thumbnail/thumb_100_20210118143729422.jpg
                        -> ex) 2. editor/summernote/20210118143729422.jpg

                    삭제할 데이터가 s3의 풀경로이고 이미지가 editor or thumbnail 폴더에 있을때만 삭제
                    아닌 경우에는 editor 일때만 삭제
                    assets, attach 안에 파일들은 공통 파일로 쓰이는 경우도 있다.
                */
                if(strpos($full_path_file, "https://cdn.mintspeaking.com/") !== false)
                {
                    $s3_url = explode("https://cdn.mintspeaking.com/", $full_path_file);
                    $directory = explode("/", $s3_url[1]);

                    if($forced_del === true || ($directory[0] == 'test_upload' || $directory[0] == 'editor' || $directory[0] == 'thumbnail'))
                    {
                        $delete = $s3Client->deleteObject([
                            'Bucket' => BUCKET,
                            'Key'    => $full_path_file,
                        ]);
                        
                        $return_array['res_code'] = '0000';
                        $return_array['msg'] = $file_name.'삭제에 성공하였습니다.';
                        return $return_array;
                    }
                    else
                    {
                        $return_array['res_code'] = '0000';
                        $return_array['msg'] = '삭제에 실패하였습니다. 해당 경로는 삭제 할 수 없는 경로입니다.';
                        return $return_array;
                    }
                }
                else
                {
                    $directory = explode("/", $full_path_file);

                    if($forced_del === true || ($directory[0] == 'test_upload' || $directory[0] == 'editor' || $directory[0] == 'thumbnail'))
                    {
                        $delete = $s3Client->deleteObject([
                            'Bucket' => BUCKET,
                            'Key'    => $full_path_file,
                        ]);
                        
                        $return_array['res_code'] = '0000';
                        $return_array['msg'] = $file_name.'삭제에 성공하였습니다.';
                        return $return_array;
                    }
                    else
                    {
                        $return_array['res_code'] = '0000';
                        $return_array['msg'] = '삭제에 실패하였습니다. 해당 경로는 삭제 할 수 없는 경로입니다.';
                        return $return_array;
                    }

                }

            }
        }
        catch(Exception $e)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = '파일 삭제에 실패하였습니다.';
            return $return_array;
        }
    }

    //origin_name 있으면 origin으로 업로드, 없으면 date("YmdHis") + rand(0,1000) 로 변환
    public static function old_mint_put_s3_object($upload_path, $file, $origin_name)
    {
        $return_array = array();

        try
        {
            if(isset($file))
            {
                /* if(!is_dir('../upload_files/'))
                {
                    mkdir('../upload_files/', 0777, true);
                } */
                
                $tmp_file = $file['tmp_name'];
                $type = $file['type'];
                $extension = explode('.', $origin_name);
                //. 기준으로 확장자 찾기 위한 배열의 마지막 값
                $extension = end($extension);
                
                $c_time = date("YmdHis");
                $rand_num = rand(0,1000);
                
                $file_name = $c_time. $rand_num.'.'.$extension;

                if($origin_name != null)
                {
                    $file_name = $origin_name;
                }
                
                //$path = $_SERVER['DOCUMENT_ROOT'].'/upload_files/'.$file_name;
                //move_uploaded_file($tmp_file, $path);
                
                //$local_file_path = $_SERVER['DOCUMENT_ROOT'].'/upload_files/'.$file_name;   //서버 파일 저장 경로
                $s3_file_path = $upload_path.$file_name;    //s3 파일 저장 경로
        
                $s3Client = S3Client::factory(array(
                    'region' => 'ap-northeast-2',
                    'version' => 'latest',
                    'signature' => 'v4',
                    'credentials' => array(
                        'key'    => S3_KEY,
                        'secret' => S3_SECRET_KEY
                    )
                ));


				if($extension == 'mp3'){
					$ContentType = 'audio/mpeg';
				}
				elseif($extension == 'mp4'){
					$ContentType = 'video/mp4';
				}else{
					$ContentType = $file['type'];
				}
        
                $file_data = file_get_contents($tmp_file);
                
                $s3Client->putObject(array(
                    'Bucket' => BUCKET,
                    'Key'    => $s3_file_path,   //Key -> bucket 안에 파일 저장할 경로
                    'Body'   => $file_data,
                    'ACL'    => 'public-read',
                    'ContentType' => $ContentType,
                ));
        
                $return_array['res_code'] = '0000';
                $return_array['msg'] = 's3업로드에 성공하였습니다.';
                $return_array['file_name'] = $file_name;
                $return_array['file_name_origin'] = $origin_name;
                $return_array['url'] = 'https://cdn.mintspeaking.com/'.$upload_path.$file_name;

                /* if($return_array['res_code'] == '0000')
                {
                    unlink($local_file_path);   //로컬 파일 삭제
                } */
        
                return $return_array;
            }
        }
        catch(Exception $e)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = 's3업로드에 실패하였습니다.';
            return $return_array;
        }
    }

    public static function put_kinesis_s3_object($upload_path, $file, $upload_limit_size, $count, $ext_array = array())
    {
        $return_array = array();

        try
        {
            if(isset($file))
            {
                if(!is_dir('upload_files/'))
                {
                    mkdir('upload_files/', 0777, true);
                }
                
                $tmp_file = $file['tmp_name'];
                $origin_name = $file['name'];
                $type = $file['type'];
                $extension = explode('.', $origin_name);
                //. 기준으로 확장자 찾기 위한 배열의 마지막 값
                $extension = end($extension);
                
                $file_name = str_replace('.'.$extension, '', $origin_name);
                $file_name = $file_name.'_'.$count.'.'.$extension;

                $size = $file['size'];
                $max_upload_size = 1047576 * $upload_limit_size;   //5MB;

                if(!in_array(strtolower($extension), $ext_array))
                {
                    $ext_text = implode(', ', $ext_array);
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = '0901';
                    $return_array['data']['err_msg'] = $ext_text. ' 파일 업로드만 가능합니다.';
                
                    return $return_array;
                }

                if($size > $max_upload_size)
                {
                    $return_array['res_code'] = '0900';
                    $return_array['msg'] = "프로세스오류";
                    $return_array['data']['err_code'] = '0902';
                    $return_array['data']['err_msg'] = $upload_limit_size. 'MB보다 큰 이미지는 업로드 할 수 없습니다.';
                
                    return $return_array;
                }

                $c_time = date("YmdHis");
                $rand_num = rand(0,1000);
                
                // $file_name = $c_time. $rand_num.'.'.$extension;
                // $file_name = $origin_name.'_'.$count;
                
                $path = $_SERVER['DOCUMENT_ROOT'].'/upload_files/'.$file_name;
                move_uploaded_file($tmp_file, $path);
                
                
                $local_file_path = $_SERVER['DOCUMENT_ROOT'].'/upload_files/'.$file_name;   //서버 파일 저장 경로
                $s3_file_path = $upload_path.$file_name;    //s3 파일 저장 경로
        
                $s3Client = S3Client::factory(array(
                    'region' => 'ap-northeast-2',
                    'version' => 'latest',
                    'signature' => 'v4',
                    'credentials' => array(
                        'key'    => S3_KEY,
                        'secret' => S3_SECRET_KEY
                    )
                ));
        
                $file_data = file_get_contents($path);
                
                $s3Client->putObject(array(
                    'Bucket' => BUCKET,
                    'Key'    => $s3_file_path,   //Key -> bucket 안에 파일 저장할 경로
                    'Body'   => $file_data,
                    'ACL'    => 'public-read',
                    'ContentType' => $file['type'],
                ));
        
                $return_array['res_code'] = '0000';
                $return_array['msg'] = 's3업로드에 성공하였습니다.';
                $return_array['file_name'] = $file_name;
                $return_array['file_name_origin'] = $origin_name;
                $return_array['url'] = 'https://cdn.mintspeaking.com/'.$upload_path.$file_name;

                if($return_array['res_code'] == '0000')
                {
                    unlink($local_file_path);   //로컬 파일 삭제
                }
        
                return $return_array;
            }
        }
        catch(Exception $e)
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = 's3업로드에 실패하였습니다.';
            return $return_array;
        }
    }



    public static function get_s3_object($path, $name, $return_str=false)
    {
        $s3Client = S3Client::factory(array(
            'region' => 'ap-northeast-2',
            'version' => 'latest',
            'signature' => 'v4',
            'credentials' => array(
                'key'    => S3_KEY,
                'secret' => S3_SECRET_KEY
            )
        ));

        try
        {
            if( isset($path) && $path != '' &&  isset($name) && $name != '')
            {
                $result = $s3Client->getObject([
                    'Bucket' => BUCKET,
                    'Key'    => $path . $name,   // $path + $file_name
                ]);

                if($return_str)
                {
                    header('Content-Type: text/plain');
                    echo $result['Body'];
                    exit;
                }
                else
                {
                    $return_array['res_code'] = '0000';
                    $return_array['object'] = $result;
                    return $return_array;
                }
                
				 
            }
        }
        catch(Exception $e)
        {
        }

        if($return_str)
        {
            echo 'ERROR';
		    exit;
        }
        else
        {
            $return_array['res_code'] = '0900';
            $return_array['msg'] = '이미지 파일 불러오기 실패';
            return $return_array;
        }
        
    }

    // 1000개씩 삭제
    public static function s3_old_data_delete($path='')
    {

        // https://docs.aws.amazon.com/code-samples/latest/catalog/php-s3-s3-listing-object-keys.php.html
        $path = 'test_upload/test/';

        // Instantiate the client.
        $s3 = S3Client::factory(array(
            'region' => 'ap-northeast-2',
            'version' => 'latest',
            'signature' => 'v4',
            'credentials' => array(
                'key'    => S3_KEY,
                'secret' => S3_SECRET_KEY
            )
        ));

        try {

            // Use the plain API (returns ONLY up to 1000 of your objects).
            $results = $s3->listObjectsV2([
                'Bucket' => BUCKET,
                'Prefix' => $path,
                // 'MaxKeys'=> '2',
            ]);

            $count = 0;
            $now = date('Y-m-d H:i:s');
            $time = time();
            // echo $time;exit;
            // $old_date = date("Y-m-d H:i:s",strtotime("-1 year", strtotime($now))); 
            $compare_date = strtotime("-1 year", strtotime($now)); 

            
            // foreach ($results->search('Contents[].Key') as $key) {
            
            // }

            foreach ($results['Contents']  as $object) {
                echo $object['Key'] . PHP_EOL;
            }
            exit;

        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        
    }

    public static function s3_old_data_delete_limit($path='', $limit)
    {
        /*
            https://docs.aws.amazon.com/code-samples/latest/catalog/php-s3-s3-listing-object-keys.php.html
            AWS 문서
        */

        // Instantiate the client.
        $s3Client = S3Client::factory(array(
            'region' => 'ap-northeast-2',
            'version' => 'latest',
            'signature' => 'v4',
            'credentials' => array(
                'key'    => S3_KEY,
                'secret' => S3_SECRET_KEY
            )
        ));

        // Use the high-level iterators (returns ALL of your objects).
        try {
            $results = $s3Client->getPaginator('ListObjects', [
                'Bucket' => BUCKET,
                'Prefix' => $path,
                'MaxKeys'=> '1000',
            ]);
                        
            $count = 0;
            $now = date('Y-m-d H:i:s');
            $time = time();
            $one_years_date = strtotime("-11 month", strtotime($now));  // 1590300153 1년 전 날짜
            // $one_years_date = strtotime("-1 year", strtotime($now));  // 1590300153 1년 전 날짜
            // $one_years_date = strtotime("-1 seconds", strtotime($now));  // 1590300153 1시간 전 날짜

            /*
                $results 는 1000개씩 리턴 -> 'MaxKeys'=> '200'로 limit 가능 최대 1000
            */

            // foreach ($results as $result) {
                
            //     // print_r($result['Contents']);exit;

            //     foreach ($result['Contents'] as $object) {
            //         // print_r(count($object));exit;
            //         $count++;

            //     }
            // }

            
            /*
                리턴 형태
                Array
                (
                    [Key] => test_upload/test/
                    [LastModified] => Aws\Api\DateTimeResult Object
                        (
                            [date] => 2021-05-24 03:24:03.000000
                            [timezone_type] => 2
                            [timezone] => Z
                        )

                    [ETag] => "d41d8cd98f00b204e9800998ecf8427e"
                    [Size] => 0
                    [StorageClass] => STANDARD
                    [Owner] => Array
                        (
                            [ID] => afca00f46358017a69d632c5b7ea31d45a86451cc7ff31a1c869a68b5de6f12f
                        )
                )
            */
            // foreach ($results->search('Contents[].Key') as $key) {
            // }
            foreach ($results->search('Contents[]') as $object) {
                
                $last_modify_date = strtotime($object['LastModified']);  // 마지막 업데이트 날짜
                
                
                if($count >= $limit) break;
                else{

                    // 첫번쨰 리턴값이 path 경로가 옴
                    if($object['Key'] == $path){
                        continue;
                    }else{

                        if($one_years_date > $last_modify_date){

                            echo 'delete : '.$count." /". $object['Key']."\n";
                            
                            // 파일 삭제
                            // $delete = $s3Client->deleteObject([
                            //     'Bucket' => BUCKET,
                            //     'Key'    => $object['Key'],
                            // ]);
                        
                            $count++;
                        
                        }
                    }
                    // $count++;
                }
            }
                        
            $return_array['res_code'] = '0000';
            $return_array['msg'] = $count.'개 파일 삭제에 성공하였습니다.';
            return $return_array;


        } catch (S3Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

}