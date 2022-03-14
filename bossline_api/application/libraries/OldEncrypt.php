<?php
class OldEncrypt
{
    var $salt;
    var $lenght;
    function __construct($salt='')
    {
        if($salt){
            $this->salt = md5($salt);
            $this->length = strlen($this->salt);
        }
    }
    
    function encrypt($str)
    {
        $length = strlen($str);
        $result = '';
        for($i=0; $i<$length; $i++) {
            $char    = substr($str, $i, 1);
            $keychar = substr($this->salt, ($i % $this->length) - 1, 1);
            $char    = chr(ord($char) + ord($keychar));
            $result .= $char;
        }
        return base64_encode($result);
    }
    
    function decrypt($str) {
        $result = '';
        $str    = base64_decode($str);
        $length = strlen($str);
        for($i=0; $i<$length; $i++) {
            $char    = substr($str, $i, 1);
            $keychar = substr($this->salt, ($i % $this->length) - 1, 1);
            $char    = chr(ord($char) - ord($keychar));
            $result .= $char;
        }
        return $result;
    }
}