<?php

class FC
{
    public function count($array)
    {
        return (is_array($arr) && !empty($arr)) ? count($arr) : 0;
    }

    public function array_merge($array1,$array2)
    {
        if(!is_array($array1)) $array1 = array();
        if(!is_array($array2)) $array2 = array();
        return array_merge($array1,$array2);
    }

    public function array_slice($array,$offset,$length=null,$preserve_keys=false)
    {
        return (is_array($array) && !empty($array)) ? array_slice($array,$offset,$length,$preserve_keys) : array();
    }

    public function enable_error_reporting()
    {
        error_reporting(E_ALL);
        ini_set('display_errors',true);
    }

    public function disable_error_reporting()
    {
        error_reporting(E_COMPILE_ERROR);
        ini_set('display_errors',false);
    }

    public function inet_aton($ip)
    {
        return sprintf("%u",ip2long($ip));
    }

    public function inet_ntoa($long)
    {
        return long2ip($long);
    }

    public function array_to_object($array,$object=false)
    {
        if(!$object) $object = new stdClass();
        if(!is_array($array)) return $array;
        foreach ($array as $k => $v) $object->{$k} = FC::array_to_object($v);
        return $object;
    }


    public function object_to_array($object, $array=false)
    {
        if (!$array) $array = array();
        if (!is_object($object)) return $object;
        foreach ($object as $k => $v) $array[$k] = FC::object_to_array($v);
        return $array;
    }

    public function array_in_array($arr1, $arr2)
    {
        foreach ($arr1 as $a1)
        {
            if ( in_array($a1, $arr2) ) return true;
        }
        return false;
    }

    public function validate_email($email)
    {
        return preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $email);
    }

    public function remove_empty_elements($array)
    {
        $narr = array();
        while(list($key, $val) = each($array))
        {
            if (is_array($val))
            {
                $val = remove_empty_elements($val);
                if (count($val)!=0) $narr[$key] = $val;
            }
            else if ($val != "" || is_int($val)) $narr[$key] = $val;
        }
        unset($array);
        return $narr;
    }

    public function SQLLongDate()
    {
        return 'Y-m-d H:i:s';
    }

    public function hex2bin($data)
    {
        $len = strlen($data);
        return pack("H".$len, $data);
    }

    public function txt2bin($str)
    {
        $text_array = explode("\r\n", chunk_split($str, 1));
        for ($n = 0; $n < count($text_array) - 1; $n++) $newstring .= substr("0000".base_convert(ord($text_array[$n]), 10, 2), -8);
        return $newstring;
    }

    public function bin2txt($str)
    {
        $text_array = explode("\r\n", chunk_split($str, 8));
        for ($n = 0; $n < count($text_array) - 1; $n++) $newstring = $newstring . stripslashes(chr(base_convert($text_array[$n], 2, 10)));
        return $newstring;
    }

    public function curl_response($url,$timeout=15,$debug=false)
    {
        global $_FLITE;
        $debug = $debug || $_FLITE->GetConfig('debug_curl');
        $api_call = curl_init();
        curl_setopt($api_call, CURLOPT_URL, $url);
        curl_setopt($api_call, CURLOPT_HEADER, 0);
        curl_setopt($api_call, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($api_call, CURLOPT_RETURNTRANSFER, true);
        if ($debug) echo 'Calling URL: ' . $url;
        $response = curl_exec($api_call);
        $errno = curl_errno($api_call);
        if ($debug && (!is_string($response) || !strlen($response))) echo( "Failure Contacting Server" );
        if($errno > 0 && $debug) echo 'Error Number:' . $errno;
        curl_close($api_call);
        return $response;
    }

    public function file_size($size,$kb=true)
    {
        $size = floatval($size);
        if($kb) $size = $size * 1024;
        $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
        return $size ? round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 Bytes';
    }

}