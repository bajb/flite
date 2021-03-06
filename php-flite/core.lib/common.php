<?php

class FC
{

  /**
   * Unfortunate oversight by PHP's part meaning that you can't chain an
   * object from it's instantiation;
   * new Foo()->Bar(); //throws an error
   * FC::_(new Foo())->Bar(); //works lovely
   *
   * @static
   *
   * @param $_
   *
   * @return mixed
   */
  public static function _($_)
  {
    return $_;
  }

  /**
   * Returns the public vars for an object
   *
   * @param $object
   *
   * @return array
   */
  public static function get_pubic_vars($object)
  {
    $vars = array();
    foreach($object as $var => $value)
    {
      $vars[$var] = $value;
    }

    return $vars;
  }

  public static function count($arr)
  {
    return (is_array($arr) && !empty($arr)) ? count($arr) : 0;
  }

  public static function array_merge($array1, $array2)
  {
    if(!is_array($array1))
      $array1 = array();
    if(!is_array($array2))
      $array2 = array();

    return array_merge($array1, $array2);
  }

  public static function array_keys($array)
  {
    return is_array($array) ? array_keys($array) : array();
  }

  public static function array_slice($array, $offset, $length = null, $preserve_keys = false)
  {
    return (is_array($array) && !empty($array)) ? array_slice($array, $offset, $length, $preserve_keys) : array();
  }

  /**
   * @static
   *
   * @param array  $array            Array to lookup key in
   * @param string $key              key of array element
   * @param mixed  $default          Default value if key does not exist
   * @param bool   $default_on_empty Default value if key is empty
   *
   * @return mixed
   */
  public static function array_value($array, $key, $default = null, $default_on_empty = false)
  {
    if(is_object($array))
      $array = FC::object_to_array($array);

    return isset($array[$key]) ? ($default_on_empty && empty($array[$key]) ? $default : $array[$key]) : $default;
  }

  /**
   * @static
   *
   * @param object $object           Array to lookup key in
   * @param string $key              key of object
   * @param mixed  $default          Default value if key does not exist
   * @param bool   $default_on_empty Default value if key is empty
   *
   * @return mixed
   */
  public static function object_value($object, $key, $default = null, $default_on_empty = false)
  {
    $object = FC::obj($object);

    return isset($object->$key) ? ($default_on_empty && empty($object->$key) ? $default : $object->$key) : $default;
  }

  public static function serialized($data)
  {
    if(!is_string($data))
      return false;

    $data   = trim($data);
    $length = strlen($data);

    if($length < 4)
      return false;
    elseif(':' !== $data[1])
      return false;
    elseif(';' !== $data[$length - 1])
      return false;
    elseif($data[0] !== 's')
      return false;
    elseif('"' !== $data[$length - 2])
      return false;
    else return true;
  }

  public static function enable_error_reporting($display_errors = true)
  {
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
    ini_set('display_errors', $display_errors);
  }

  public static function disable_error_reporting()
  {
    error_reporting(E_COMPILE_ERROR);
    ini_set('display_errors', false);
  }

  public static function error_report($error, $vars)
  {
    $_FLITE           = Flite::Base();
    $error_log_emails = $_FLITE->GetConfig('error_log_emails', array());
    if(FC::count($error_log_emails) > 0)
    {
      $error_log_email_str = implode(',', $error_log_emails);
      @mail($error_log_email_str, 'MPB Error Report', $error . ': ' . print_r($vars, true));
    }

    @error_log("Title: $error\n" . print_r($vars, true));

    return true;
  }

  public static function is_whitelist_ip($dev_override = true)
  {
    if(FLITE_ENV == 'local')
      return true;
    $_FLITE = Flite::Base();
    $ips    = $_FLITE->GetConfig('whitelist_ips', array());

    return ($dev_override && $_FLITE->GetConfig('is_dev') ?
      true : (is_array($ips) ? (in_array(FC::get_user_ip(), $ips)) : false));
  }

  public static function is_development_ip($dev_override = true)
  {
    if(FLITE_ENV == 'local')
      return true;
    $_FLITE = Flite::Base();
    $ips    = $_FLITE->GetConfig('development_ips', array());

    return ($dev_override && $_FLITE->GetConfig('is_dev') ?
      true : (is_array($ips) ? (in_array(FC::get_user_ip(), $ips)) : false));
  }

  public static function inet_aton($ip)
  {
    return sprintf("%u", ip2long($ip));
  }

  public static function inet_ntoa($long)
  {
    return long2ip($long);
  }

  public static function obj($input)
  {
    if(is_object($input))
      return $input;
    if(is_array($input))
      return FC::array_to_object($input);

    return new stdClass();
  }

  public static function arr($input)
  {
    if(is_null($input) || $input === false)
      return array();
    if(is_array($input))
      return $input;
    if(is_object($input))
      return FC::object_to_array($input);
    if(is_string($input) && stristr($input, ','))
      return explode(',', $input);

    return array($input);
  }

  public static function array_to_object($array, $object = false)
  {
    if(!is_array($array))
      return $array;
    if(!$object)
      $object = new stdClass();
    foreach($array as $k => $v)
    {
      $object->{$k} = FC::array_to_object($v);
    }

    return $object;
  }


  public static function object_to_array($object, $array = false)
  {
    if(!is_object($object))
      return $object;
    if(!$array)
      $array = array();
    foreach($object as $k => $v)
    {
      $array[$k] = FC::object_to_array($v);
    }

    return $array;
  }

  public static function object_keys($object)
  {
    $keys = array();
    foreach($object as $k => $v)
    {
      $keys[] = $k;
    }

    return $keys;
  }

  public static function array_in_array($arr1, $arr2)
  {
    foreach($arr1 as $a1)
    {
      if(in_array($a1, $arr2))
        return true;
    }

    return false;
  }

  public static function is_json($string)
  {
    if(substr($string, 0, 1) != '{')
      return false;
    json_decode($string);

    return (json_last_error() == JSON_ERROR_NONE);
  }

  public static function validate_email($email, $dns = true)
  {
    if(!filter_var($email, FILTER_VALIDATE_EMAIL))
      return false;
    if(!$dns)
      return true;
    list(, $host) = explode('@', $email, 2);

    return checkdnsrr($host) || FLITE_ENV !== 'live';
  }

  public static function ascii_encode_email($str_email, $str_display = '', $bln_create_link = true)
  {
    if(empty($str_display))
      $str_display = $str_email;
    $str_mailto        = "&#109;&#097;&#105;&#108;&#116;&#111;&#058;";
    $str_encoded_email = '';
    for($i = 0; $i < strlen($str_email); $i++)
    {
      $str_encoded_email .= "&#" . ord(substr($str_email, $i)) . ";";
    }
    if(strlen(trim($str_display)) > 0)
    {
      $str_display = $str_display;
    }
    else
    {
      $str_display = $str_encoded_email;
    }
    if($bln_create_link)
    {
      return "<a href=\"" . $str_mailto . $str_encoded_email . "\">" .
        ($str_display == $str_email ? $str_encoded_email : $str_display) . "</a>";
    }
    else
    {
      return empty($str_display) || $str_display == $str_email ? $str_encoded_email : $str_display;
    }
  }

  public static function remove_empty_elements($array)
  {
    if(empty($array) || !is_array($array))
      return $array;
    $narr = array();
    foreach($array as $key => $val)
    {
      if(is_array($val))
      {
        $val = FC::remove_empty_elements($val);
        if(count($val) != 0)
          $narr[$key] = $val;
      }
      else if($val != "" || is_int($val))
        $narr[$key] = $val;
    }
    unset($array);

    return $narr;
  }

  public static function SQLLongDate()
  {
    return 'Y-m-d H:i:s';
  }

  public static function hex2bin($data)
  {
    $len = strlen($data);

    return pack("H" . $len, $data);
  }

  public static function txt2bin($str)
  {
    $text_array = explode("\r\n", chunk_split($str, 1));
    $newstring  = '';
    for($n = 0; $n < count($text_array) - 1; $n++)
    {
      $newstring .= substr("0000" . base_convert(ord($text_array[$n]), 10, 2), -8);
    }

    return $newstring;
  }

  public static function bin2txt($str)
  {
    $text_array = explode("\r\n", chunk_split($str, 8));
    $newstring  = '';
    for($n = 0; $n < count($text_array) - 1; $n++)
    {
      $newstring = $newstring . stripslashes(chr(base_convert($text_array[$n], 2, 10)));
    }

    return $newstring;
  }

  public static function curl_response($url, $timeout = 15, $debug = false)
  {
    $_FLITE   = Flite::Base();
    $debug    = $debug || $_FLITE->GetConfig('debug_curl');
    $api_call = curl_init();
    curl_setopt($api_call, CURLOPT_URL, $url);
    curl_setopt($api_call, CURLOPT_HEADER, 0);
    curl_setopt($api_call, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($api_call, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($api_call, CURLOPT_RETURNTRANSFER, true);
    if($debug)
      echo 'Calling URL: ' . $url;
    $response = curl_exec($api_call);
    $errno    = curl_errno($api_call);
    if($debug && (!is_string($response) || !strlen($response)))
      echo("Failure Contacting Server");
    if($errno > 0 && $debug)
      echo 'Error Number:' . $errno;
    curl_close($api_call);

    return $response;
  }

  public static function file_size($size, $kb = true)
  {
    $size = floatval($size);
    if($kb)
      $size = $size * 1024;
    $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");

    return $size ?
      round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[intval($i)] : '0 Bytes';
  }

  public static function short_string()
  {
    return base_convert(rand(10000, 9999999), 20, 36);
  }

  public static function get_user_ip()
  {
    $retip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] :
      (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1');
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
      $retip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    if(isset($_SERVER['HTTP_X_REAL_FORWARDED_FOR']))
      $retip = $_SERVER['HTTP_X_REAL_FORWARDED_FOR'];
    if(isset($retip) && stristr($retip, ','))
    {
      list($retip,) = explode(',', $retip, 2);
      $retip = trim($retip);
    }

    return $retip;
  }

  public static function time_difference($seconds, $string = true, $string_seconds = true, $limit_units = 2)
  {
    $days    = floor($seconds / 86400);
    $seconds = $seconds % 86400;

    $hours   = floor($seconds / 3600);
    $seconds = $seconds % 3600;

    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;

    if(!$string)
      return array('days' => $days, 'hours' => $hours, 'minutes' => $minutes, 'seconds' => $seconds);
    else
    {
      $out = array();
      if($days > 0)
        $out[] = "$days Days";
      if($hours > 0 && count($out) < $limit_units)
        $out[] = "$hours Hrs";
      if($minutes > 0 && count($out) < $limit_units)
        $out[] = "$minutes Mins";
      if($seconds > 0 && $string_seconds && count($out) < $limit_units)
        $out[] = "$seconds seconds";

      if(!$string_seconds && ($days + $hours + $minutes) == 0)
        $out = array('&lt;1 min');

      return implode(', ', $out);
    }
  }

  public static function day_diff($timestamp1, $timestamp2)
  {
    if($timestamp1 < $timestamp2)
    {
      $holder     = $timestamp1;
      $timestamp1 = $timestamp2;
      $timestamp2 = $holder;
    }
    $seconds = $timestamp1 - $timestamp2;

    return ceil($seconds / 86400);
  }

  public static function month_diff($timestamp1, $timestamp2 = null)
  {
    if(is_null($timestamp2))
      $timestamp2 = time();

    return (date("m", $timestamp2) - date("m", $timestamp1))
      + (12 * (date("Y", $timestamp2) - date("Y", $timestamp1)));
  }


  /**
   * @static
   * @return string username of the process calling php e.g. apache
   */
  public static function get_current_user()
  {
    if(function_exists("posix_getpwuid"))
    {
      $usr = posix_getpwuid(posix_geteuid());

      return $usr['name'];
    }
    else return "nobody";
  }
}
