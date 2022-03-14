<?php
defined("BASEPATH") OR exit("No direct script access allowed");


function msg_checked_encode_message($msg)
{
    
    if($msg)
    {
        for($i=0; $i<sizeof($msg); $i++)
        {
            if((substr($msg[$i]['message'],0,2) == "%u") || strpos($msg[$i]['message'],"%u") !== false)
            {
               
                $tmp_msg = NULL;
                preg_match('/^([\x00-\x7e]|.{2})*/', common_urlutfchr($msg[$i]['message']),$tmp_msg);
                $msg[$i]['message'] =  $tmp_msg[0];
                
              
            }
            
        }
    }

    return $msg;
}



?>

