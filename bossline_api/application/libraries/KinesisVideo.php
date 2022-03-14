<?php

include_once 'aws/aws-autoloader.php';

//index html id
define('KEY', 'AKIAJV4AJHN2YK4YH27Q');
define('SECRET_KEY', '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');

use Aws\Kinesis\KinesisClient;
use Aws\Exception\AwsException;
use Aws\KinesisAnalytics\KinesisAnalyticsClient;
use Aws\KinesisAnalyticsV2\KinesisAnalyticsV2Client;
use Aws\KinesisVideo\KinesisVideoClient;
use Aws\KinesisVideoArchivedMedia\KinesisVideoArchivedMediaClient;
use Aws\KinesisVideoMedia\KinesisVideoMediaClient;
use Aws\KinesisVideoSignalingChannels\KinesisVideoSignalingChannelsClient;

class KinesisVideo{

    function __construct()
    {

    }

    public static function create_channel($channel_name)
    {
        //키네시스 비디오 생성
        $kinesisClient = new KinesisVideoClient([
            // 'profile' => 'default',
            // 'version' => '2017-09-30',
            'version' => 'latest',
            'region' => 'ap-northeast-2',
            'credentials' => array(
                'key'    => KEY,
                'secret' => SECRET_KEY
            )
        ]);

        try{
            //생성 API
            $result = $kinesisClient->createSignalingChannel([
                'ChannelName' => $channel_name,     // REQUIRED
                'ChannelType' => 'SINGLE_MASTER',   //작성중인 신호 채널의 유형입니다. 현재 SINGLE_MASTER유일하게 지원되는 채널 유형입니다.
                'SingleMasterConfiguration' => [
                    'MessageTtlSeconds' => 5,       //신호 채널이 폐기되기 전에 미달 된 메시지를 유지하는 기간.
            ],
                // 'Tags' => [                      //이 채널과 연결하려는 태그 세트 (키-값 쌍)입니다.
                //     [
                //         'Key' => 'key1',         // REQUIRED     지정된 신호 채널과 연관된 태그의 키입니다.
                //         'Value' => 'value1',     // REQUIRED     지정된 신호 채널과 연관된 태그의 값입니다.
                //     ],
                //     // ...
                // ],
            ]);

            return ($result['@metadata']);

        } catch (AwsException $e) {

            echo $e->getMessage();
            echo "\n";
        }
    

    }

    public static function get_channel_list($channel_name, $count)
    {
        $kinesisClient = new KinesisVideoClient([
            'version' => 'latest',
            'region' => 'ap-northeast-2',
            'credentials' => array(
                'key'    => KEY,
                'secret' => SECRET_KEY
            )
        ]);
        
        try{
            if(isset($channel_name)){
                //리스트 API
                $result = $kinesisClient->listSignalingChannels([
                    // 검색 -> ComparisonValue 검색어
                    'ChannelNameCondition' => [
                        'ComparisonOperator' => 'BEGINS_WITH',
                        'ComparisonValue' => $channel_name,
                    ],
                    'MaxResults' => $count,
                    // 'NextToken' => 'next_token',
                ]);
            }else{
                //리스트 API
                $result = $kinesisClient->listSignalingChannels([
                    'MaxResults' => $count,
                    // 'NextToken' => 'next_token',
                ]);
            }

            return ($result['ChannelInfoList']);
        }
        catch (AwsException $e) {

            echo $e->getMessage();
            echo "\n";
        }
        
    }

    public static function delete_channel($channel_name)
    {

        $kinesisClient = new KinesisVideoClient([
            // 'profile' => 'default',
            // 'version' => '2017-09-30',
            'version' => 'latest',
            'region' => 'ap-northeast-2',
            'credentials' => array(
                'key'    => KEY,
                'secret' => SECRET_KEY
            )
        ]);
        
        if(gettype($channel_name)=='string'){

            try{
                // describeSignalingChannel = 채널정보 리턴(arn/channel이름 둘중 하나)
                $channel_info = $kinesisClient->describeSignalingChannel([
                    // 'ChannelARN'=> 'arn:aws:kinesisvideo:ap-northeast-2:418449978877:channel/channel_create_test3/1594265202574',
                    'ChannelName'=> $channel_name,
                ]);

                // print_r($channel_info);exit;
    
                $result= $kinesisClient-> deleteSignalingChannel ([
                    'ChannelARN'=> $channel_info['ChannelInfo']['ChannelARN'], // 필수
                    'CurrentVersion'=> $channel_info['ChannelInfo']['Version'],
                ]);
    
                return ($result['@metadata']);
                // return print_r($result['@metadata']['statusCode']);
            }
    
            catch (AwsException $e) {
    
                echo $e->getMessage();
                echo "\n";
            }

        }else if(gettype($channel_name)=='array'){

            $result_array = array();
            for($i=0; $i<count($channel_name); $i++){

                try{
                    // describeSignalingChannel = 채널정보 리턴(arn/channel이름 둘중 하나)
                    $channel_info = $kinesisClient->describeSignalingChannel([
                        // 'ChannelARN'=> 'arn:aws:kinesisvideo:ap-northeast-2:418449978877:channel/channel_create_test3/1594265202574',
                        'ChannelName'=> $channel_name[$i],
                    ]);
        
                    // print_r($result_search);
        
                    $result= $kinesisClient-> deleteSignalingChannel ([
                        'ChannelARN'=> $channel_info['ChannelInfo']['ChannelARN'],
                        'CurrentVersion'=> $channel_info['ChannelInfo']['Version'],
                    ]);
        
                    array_push($result_array, $result);
                    // print_r($result);
                }
        
                catch (AwsException $e) {
        
                    echo $e->getMessage();
                    echo "\n";
                }
            }

            return ($result_array);
        }
        
    }

}



        //키네시스 생성
        // $kinesisClient = new Aws\Kinesis\KinesisClient([
        //     // 'profile' => 'default',
        //     'version' => '2013-12-02',
        //     'region' => 'ap-southeast-1',
        //     'credentials' => array(
        //         'key'    => KEY,
        //         'secret' => SECRET_KEY
        //     )
        // ]);

        // $shardCount = 2;
        // $name = "my_stream_name";
        
        // try {

        //     $result = $kinesisClient->createStream([
        //         'ShardCount' => $shardCount,
        //         'StreamName' => $name,
        //     ]);
        //     var_dump($result);
        // } catch (AwsException $e) {
        //     // output error message if fails
        //     echo $e->getMessage();
        //     echo "\n";
        // }

        
        //키네시스 리스트
        // $kinesisClient = new Aws\Kinesis\KinesisClient([
        //     // 'profile' => 'default',
        //     'credentials' => array(
        //         'key'    => KEY,
        //         'secret' => SECRET_KEY
        //     ),
        //     'version' => '2013-12-02',
        //     // 'region' => 'us-east-2'
        //     'region' => 'ap-northeast-2',
        // ]);
        //     //KinesisVideoClient
        //     //
        // try {
        //     $result = $kinesisClient->listStreams([
        //     ]);
        //     var_dump($result);
        // } catch (AwsException $e) {
        // // output error message if fails
        // echo $e->getMessage();
        // echo "\n";
        // }