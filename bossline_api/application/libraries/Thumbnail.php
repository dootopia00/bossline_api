<?php
class Thumbnail
{
    public static $cdn_default_url = 'https://cdn.mintspeaking.com';
    public static $cdn_default_url_PCRE = 'https\:\/\/cdn\.mintspeaking\.com';
    public static $s3_thumbnail_loc = ISTESTMODE ? 'test_upload/thumbnail/' : 'thumbnail/';
    public static $s3_thumbnail_url = ISTESTMODE ? 'https://cdn.mintspeaking.com/test_upload/thumbnail/':'https://cdn.mintspeaking.com/thumbnail/';

    /*
        폼파일의 섬네일 생성
    */
    public static function create_thumbnail_formfile($file,$filename,$fieldname='filename',$config=array())
    {
        
        if(!$filename || !$file['tmp_name']) return false;

        // 기본적으로 리사이징 두개 생성
        if(empty($config) || !array_key_exists('resize_width',$config))
        {
            $config['resize_width'] = array(
                100,
                400,
                740
            );
        }
        $config['formfile'] = true;

        $filenames_arr = self::create_thumbnail_s3($file, $filename, self::$s3_thumbnail_loc,$config);

        $return_array = array();
        if($filenames_arr)
        {
            $return_array[$fieldname] = $filenames_arr;
            $return_array[$fieldname]['origin'] = $config['ori_path'].$filename;
        }	

        return $return_array;
    }
    
    /*
        에디터 섬네일 생성
    */
    public static function create_thumbnail_parse_content($content,$thumb=array(),$config=array(),$pre_content=''){
        $CI =& get_instance();
        $CI->load->model('board_mdl');
        
        // 기본적으로 리사이징 두개 생성
        if(empty($config) || !array_key_exists('resize_width',$config))
        {
            $config['resize_width'] = array(
                100,
                400,
                740
            );
        }

        $matches = common_find_s3_src_from_content($content);

        $thumbnail_info = array();
        $exist_file = array();
        if(count($matches[1])> 0)
        {
            $i = 0;

            foreach($matches[1] as $match)
            {
                $new_save = true;
                $uri = str_replace(self::$cdn_default_url.'/','',$match);
                $uri_arr = explode('/',$uri);
                $filename = $uri_arr[count($uri_arr)-1];
                $path = str_replace($filename,'',$uri);
                $ori_filename = explode('_',$filename);
                $exist_file[] = $ori_filename[count($ori_filename)-1];

                //섬네일링크 생성되있는 경우. 찾아서 치환만 한다.
                if(strpos($uri,'thumb') !== false && $thumb) 
                {
                    // 이미 섬네일 만들어져있는 파일인지 체크
                    foreach($thumb[$config['type']] as $f)
                    {
                        $ori_filename = explode('/',$f['origin']);
                        $ori_filename = $ori_filename[count($ori_filename)-1];

                        // 찾음
                        if(strpos($filename,$ori_filename) !== false) 
                        {
                            $new_save = false;
                            $thumbnail_info[$i] = $f;
                            $content = str_replace($match,'{{'.$i.'}}',$content);
                            $i++;
                            break;
                        }
                    }   
                    
                }

                // 섬네일 없으면 생성
                if($new_save)
                {
                    $filenames_arr = self::create_thumbnail_s3(array('tmp_name'=>$path),$filename, self::$s3_thumbnail_loc,$config);

                    if($filenames_arr)
                    {
                        $filenames_arr['origin'] = $uri;
                        $thumbnail_info[$i] = $filenames_arr;
                        $content = str_replace($match,'{{'.$i.'}}',$content);
                        $i++;
                    }	

                    $CI->board_mdl->delete_board_edit_files($match);
                }
                
            }
        }

        self::find_deleted_editor_image($pre_content,$thumb,$exist_file);

        return array(
            'content' => $content,
            'thumbnail_info' => $thumbnail_info,
        );

    }


    public static function find_deleted_editor_image($pre_content,$thumb,$exist_file=array())
    {

        // 없앤 에디터 이미지 찾아서 삭제처리
        if($pre_content)
        {
            $matches = common_find_s3_src_from_content($pre_content);
            // 섬네일이 없는 경우 {{0}} 같은 임의변수화 처리가 되지 않아서 여기로 들어올 수 있음
            if(count($matches[1])> 0)
            {
                foreach($matches[1] as $match)
                {
                    $uri = str_replace(self::$cdn_default_url.'/','',$match);
                    $uri_arr = explode('/',$uri);
                    $filename = $uri_arr[count($uri_arr)-1];
                    $path = str_replace($filename,'',$uri);

                    // 수정전 content 내용에 cdn URL이 존재하는데, 수정 후 content 내용에 해당 경로가 없으면 삭제
                    if(!in_array($filename,$exist_file))
                    {
                        S3::delete_s3_object($path, $filename);
                    }
                }
            }

            if($thumb['editor'])
            {
                foreach($thumb['editor'] as $f)
                {
                    $filename = explode('/',$f['origin']);
                    $filename = $filename[count($filename)-1];

                    if(!in_array($filename,$exist_file))
                    {
                        foreach($f as $k=>$v)
                        {
                            S3::delete_s3_object(self::$s3_thumbnail_loc,$v, $k == 'origin' ? $v:'');
                        }
                        
                    }
                }
            }
        }

    }

    /*
        섬네일 링크 치환
    */
    public static function replace_image_thumbnail($content,$thumb='',$type='editor',$width='',$field='')
    {

        if(!$thumb) return $content;

        $size = array(
            'profile' => 110,
            'list' => 100,
            'mobile' => 400,
            'pc' => 740,
        );

        $width = $size[$width] ? $size[$width]:740;
        $thumb = json_decode($thumb,true);

        if(!$thumb || !$thumb[$type]) return $content;

        // 에디터 내용 치환
        if($type == 'editor')
        {
            $replace_str = array();
            $before_str = array();
            foreach($thumb[$type] as $key=>$val)
            {
                $before_str[] = '{{'.$key.'}}';
                $replace_str[] = $val[$width] ? (self::$s3_thumbnail_url.$val[$width]):(self::$cdn_default_url.'/'.$val['origin']);
            }
        
            if(!empty($before_str))
            {
                $content = str_replace($before_str,$replace_str,$content);
            }
        }
        else
        {
            // 일반첨부파일 치환
            $info = $thumb[$type][$field];
            $content = $info[$width] ? (self::$s3_thumbnail_url.$info[$width]):(self::$cdn_default_url.'/'.$info['origin']);
        }

        return $content;
    }


    /* 섬네일 배열 형태
    {
        "editor":[
            {
                "400":"thumb_400_1594779920_3436.png",
                "740":"thumb_740_1594779920_3436.png",
                "origin":"test_upload/editor/daumeditor/1594779920_3436.png"
            },
            {
                "400":"thumb_400_1594779924_6200.png",
                "740":"thumb_740_1594779924_6200.png",
                "origin":"test_upload/editor/daumeditor/1594779924_6200.png"
            }
        ],
        "form":{
            "filename":{
                "400":"thumb_400_1594779929_119003.png",
                "740":"thumb_740_1594779929_119003.png",
                "origin":"test_upload/attach/boards/1594779929_119003.png"
            },
            "filename2":{
                "400":"thumb_400_1594779929_119003.png",
                "740":"thumb_740_1594779929_119003.png",
                "origin":"test_upload/attach/boards/1594779929_119003.png"
            }
        }
    }
    첫번째 섬네일 정보 리턴해준다.
    */
    public static function get_first_thumbnail($thumb)
    {

        if(empty($thumb)) return '';

        $thumb = json_decode($thumb,true);
        if(!$thumb) return '';

        $return_url = '';
        if($thumb['form'])
        {
            $file_arr = array_shift($thumb['form']);
            $filename = $file_arr ? array_shift($file_arr):'';
            $return_url = $filename ? self::$s3_thumbnail_url.$filename:'';
        }
        elseif($thumb['editor'])
        {
            $file_arr = array_shift($thumb['editor']);
            $filename = $file_arr ? array_shift($file_arr):'';
            $return_url = $filename ? self::$s3_thumbnail_url.$filename:'';
        }
        else
        {
            $return_url = '';
        }

        return $return_url;
    }

    /*
        에디터에서 이미지 있는지 찾는다.
    */
    public static function get_img_link_from_content($content)
    {
        $content = common_textarea_out($content);

        // 1차로 cdn 주소에서 찾는다
        preg_match('/<img.*src=\\\?["|\']('.self::$cdn_default_url_PCRE.'[^"|\']+)\\\?[\"|\']/Usim',$content,$matches);
        
        if($matches[1]) return $matches[1];
        //$matches = null;
        // 2차로 민트로컬서버 주소에서 찾는다.
        //preg_match('/<img.*src=\\\?["|\'](http.*mint05\.com\/[^"|\']+)\\\?[\"|\']/Usim',$content,$matches);

        //return $matches[1];
        return '';
    }

    /*
        에디터, 섬네일 필드에서 이미지 링크 하나 찾아서 리턴해준다.
    */
    public static function get_preview_img($thumb,$content,$formfilename='',$formfile_url='')
    {
        // $content에 {{1}} 과 같은 임의변수를 치환하지않아도 임의변수지정 되어있으면 thumb에 정보가 있으므로 get_first_thumbnail 에서 가져온다.
        $thumb = self::get_first_thumbnail($thumb);

        // 섬네일 없으면 폼파일 체크
        if(!$thumb && $formfilename)
        {
            $extension = explode(".",$formfilename);
            $extension = count($extension) > 1 ? strtolower($extension[count($extension)-1]):'';
        
            $ext_array = array('jpg', 'jpeg', 'png', 'gif');
            if($extension && in_array($extension,$ext_array))
            {
                $thumb = $formfile_url.$formfilename;
            }
        }
        
        // 그래도 없으면 에디터에서 이미지 파일 체크
        if(!$thumb) $thumb = self::get_img_link_from_content($content);

        return $thumb;
    }



    // s3에 이미지 파일 섬네일 생성
    // $file : 폼업로드라면 경로+파일명, s3라면 경로만
    public static function create_thumbnail_s3($file, $file_name,$newpath, $config=array())
    {
        $extension = explode(".",$file_name);
        $extension = count($extension) > 1 ? strtolower($extension[count($extension)-1]):'';
        $path = $file['tmp_name'];
        $origin_size = $file['size'];
        
        $ext_array = array('jpg', 'jpeg', 'png','gif','bmp');
        if(!in_array($extension,$ext_array))
        {
            return false;
        }

        $quality = 100;
        if($config['formfile'] === true)
        {
            // 폼업로드된 파일.
            list($src, $origin_width, $origin_height) = self::get_image_resource_from_file ($path);
            if(empty($src)) return false;
        }
        else
        {
            // s3에 있는 파일 가져온다.
            $body = s3::get_s3_object($path, $file_name);
           
            if($body['res_code'] !='0000'|| empty($body['object']))
            {
                return false;
            }

            $origin_size = $body['object']['@metadata']['headers']['content-length'];
            $body = (string)$body['object']['Body'];

            // 파일리소스 생성.
            $src = imagecreatefromstring($body);
            $origin_width = imagesx($src);
            $origin_height = imagesy($src);
        }

        $newfilename_arr = array(); // 만들어진 s3섬네일 파일명

        // 생성할 섬네일 크기. 기본은 100,400,700
        foreach($config['resize_width'] as $width)
        {
            // 원본이 섬네일 크기보다 작으면 만들지않음
            if($origin_width <= $width) continue;

            $dst = self::get_image_resize($src, $origin_width, $origin_height, $width);
            if (empty($dst)) continue;
        
            $path_temp_dir = sys_get_temp_dir();
            $path_src_file = tempnam($path_temp_dir, "RI");
            //확장자에 따라 이미지 저장 처리
            switch($extension)  
            {
                case 'gif' :
                    $result_save = @imagegif($dst,$path_src_file);
                    break;
        
                case 'jpg' :
                case 'jpeg' :
                    $result_save = @imagejpeg($dst, $path_src_file, $quality);
                    break;
        
                default : //확장자 png 또는 확장자가 없는 경우, 정의되지 않는 확장자인 경우는 모두 png로 저장
                    $result_save = @imagepng($dst,$path_src_file);
            }
            
            if(!$result_save) continue;

            // 만든 섬네일이 원본 용량보다 크면 패스. 적용 시 주석풀어야함
            if($origin_size < filesize($path_src_file))
            {
                @unlink($path_src_file);
                break;
            }
            
            if($config['newfilename'])
            {
                $newfilename = $config['newfilename'];
            }
            else
            {
                $newfilename = 'thumb_'.$width.'_'.$file_name;
            }
            
            $path_src_file = [
                'tmp_name' => $path_src_file,
                'name' => $newfilename,
                'type' => image_type_to_mime_type(exif_imagetype($path_src_file))
            ];
            
            $res = S3::put_s3_object($newpath, $path_src_file, 5, $ext_array,$newfilename);

            @unlink($path_src_file);
            if($res['res_code'] =='0000')
            {
                $newfilename_arr[$width] = $newfilename;
            }
        }

        @imagedestroy($src);
        @imagedestroy($dst);
        
        return $newfilename_arr;
    }



    public static function get_image_resize($src, $src_w, $src_h, $dst_w, $dst_h=0)
    {

        if (empty($src))    {//원본의 리소스 id 가 빈값일 경우
            return false;
        }

        //정수형이 아니라면 정수형으로 강제 형변환
        if (!is_int($src_w)) settype($src_w, 'int');
        if (!is_int($src_h)) settype($src_h, 'int');
        if (!is_int($dst_w)) settype($dst_w, 'int');
        if (!is_int($dst_h)) settype($dst_h, 'int');

        if ($src_w < 1 || $src_h < 1)
        {//원본의 너비와 높이가 둘중에 하나라도 0보다 큰 정수가 아닐경우

            //$GLOBALS['errormsg'] = "원본의 너비와 높이가 0보다 큰 정수가 아닙니다. ($src_w, $src_h)";

            return false;
        }

        if (empty($dst_w) && empty($dst_h)) 
        {//썸네일의 너비와 높이 둘다 없을 경우

            //$GLOBALS['errormsg'] = '썸네일의 너비와 높이는 둘중에 하나는 반듯이 있어야 합니다.';

            return false;
        }

        if (!empty($dst_w) && $dst_w < 1)
        {//썸네일의 너비가 존재하는데 0보다 큰 정수가 아닐경우

            //$GLOBALS['errormsg'] = "썸네일의 너비가 0보다 큰 정수가 아닙니다. ($dst_w)";

            return false;
        }

        if (!empty($dst_h) && $dst_h < 1)
        {//썸네일의 높이가 존재하는데 0보다 큰 정수가 아닐경우

            //$GLOBALS['errormsg'] = "썸네일의 높이가 0보다 큰 정수가 아닙니다. ($dst_h)";

            return false;
        }


        //썸네일의 너비와 높이가 둘중에 하나가 없는 경우에는 정비율을 의미하며, 비율데로 너비와 높이를 결정한다.
        if (empty($dst_w) || empty($dst_h)) 
        {

            if (empty($dst_h)) $dst_h = self::get_size_by_rule($src_w, $src_h, $dst_w, 'width');
            else $dst_w = self::get_size_by_rule($src_w, $src_h, $dst_h, 'height');
        }


        //$dst_w , $dst_h 크기의 썸네일 리소스를 생성한다.
        $dst = @imagecreatetruecolor ($dst_w , $dst_h);
        if ($dst === false) 
        {

            //$GLOBALS['errormsg'] = "$dst_w , $dst_h 크기의 썸네일 리소스를 생성하지 못했습니다.";

            return false;
        }


        //리사이즈 처리
        $result_resize = imagecopyresampled ($dst , $src , 0 , 0 , 0 , 0 , $dst_w , $dst_h , $src_w , $src_h );
        if ($result_resize === false) 
        {

            //$GLOBALS['errormsg'] = "$dst_w , $dst_h 크기로 리사이즈에 실패하였습니다.";

            return false;
        }

        return $dst;
    }


    public static function get_size_by_rule($src_w, $src_h, $dst_size, $rule='width')
    {

        //정수형이 아니라면 정수형으로 강제 형변환
        if (!is_int($src_w)) settype($src_w, 'int');
        if (!is_int($src_h)) settype($src_h, 'int');
        if (!is_int($dst_size)) settype($dst_size, 'int');

        if ($src_w < 1 || $src_h < 1)
        {//원본의 너비와 높이가 둘중에 하나라도 0보다 큰 정수가 아닐경우

            //$GLOBALS['errormsg'] = "원본의 너비와 높이가 0보다 큰 정수가 아닙니다. ($src_w, $src_h)";

            return false;
        }

        if ($dst_size < 1)
        {//리사이즈 될 사이즈가 0보다 큰 정수가 아닐경우

            //$GLOBALS['errormsg'] = "리사이즈될 사이즈가 0보다 큰 정수가 아닙니다. ($dst_size)";

            return false;
        }

        if ($rule != 'height') 
        {//기준값이 너비일 경우, 값이 height 가 아니면 전부 width 로 판단

            return ceil($dst_size / $src_w * $src_h);
        }
        else 
        {//기준값이 높이일 경우

            return ceil($dst_size / $src_h * $src_w);
        }
    }

    public static function get_image_resource_from_file ($path_file)
    {

        if (!is_file($path_file)) 
        {//파일이 아니라면

           // $GLOBALS['errormsg'] = $path_file . '은 파일이 아닙니다.';

            return Array();
        }

        $size = @getimagesize($path_file);
        if (empty($size[2])) 
        {//이미지 타입이 없다면

            //$GLOBALS['errormsg'] = $path_file . '은 이미지 파일이 아닙니다.';

            return Array();
        }

        if ($size[2] != 1 && $size[2] != 2 && $size[2] != 3) 
        {//지원하는 이미지 타입이 아니라면

            //$GLOBALS['errormsg'] = $path_file . '은 gif 나 jpg, png 파일이 아닙니다.';

            return Array();
        }

        switch($size[2])
        {//image type에 따라 이미지 리소스를 생성한다.

            case 1 : //gif

                $im = @imagecreatefromgif($path_file);
                break;

            case 2 : //jpg

                $im = @imagecreatefromjpeg($path_file);
                break;

            case 3 : //png

                $im = @imagecreatefrompng($path_file);
                break;
        }

        if ($im === false) 
        {//이미지 리소스를 가져오기에 실패하였다면

            //$GLOBALS['errormsg'] = $path_file . ' 에서 이미지 리소스를 가져오는 것에 실패하였습니다.';

            return Array();
        }
        else 
        {//이미지 리소스를 가져오기에 성공하였다면

            $return = $size;
            $return[0] = $im;
            $return[1] = $size[0];//너비
            $return[2] = $size[1];//높이
            $return[3] = $size[2];//이미지타입
            $return[4] = $size[3];//이미지 attribute

            return $return;
        }
    }
}