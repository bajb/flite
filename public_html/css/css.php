<?php
ini_set('display_errors',false);
$dir = realpath(dirname(__FILE__));
$serverparts = explode('.',$_SERVER['HTTP_HOST'],3);
$presub = $sub = count($serverparts) == 3 ? $serverparts[0] : '';
$predom = $dom = $serverparts[count($serverparts) - 2];
$pretld = $tld = $serverparts[count($serverparts) - 1];
$protocol = (isset($_SERVER['HTTP_VIA']) && strpos($_SERVER['HTTP_VIA'],'HTTPS') > -1) ? 'https://' : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';

if($tld === 'local')
{
    $dir = getcwd() .'/';
}

@include_once('../domain.fiddle.php');

if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])
    && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')
    && !in_array($tld, array('dev','local'))) {
    ob_start("ob_gzhandler");
} else {
    ob_start();
}

$max_cache_hour = 0;

if(stristr($_SERVER['QUERY_STRING'],';v='))
{
    list(,$cache_hour) = explode(';v=',$_SERVER['QUERY_STRING']);
    $cache_hour = intval($cache_hour);
    if($cache_hour > 0) $max_cache_hour = $cache_hour;
}

if(stristr($_SERVER['QUERY_STRING'],';tpl='))
{
    list(,$template) = explode(';tpl=',$_SERVER['QUERY_STRING']);
    if(stristr($template,';')) list($template,) = explode(';',$template);
}

function compress($buffer)
{
    global $tld;
    if(in_array($tld, array('dev','local'))) return $buffer;
    /* remove comments */
    $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
    /* remove tabs, spaces, newlines, etc. */
    $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
    return $buffer;
}

header('Content-type: text/css');
header('Cache-Control: public');

$files = explode(';',urldecode($_SERVER['QUERY_STRING']));
if(!is_array($files)) $files = array($files);

$already_loaded = $load_files = array();

foreach ($files as $file)
{
    if (strpos($file,'/') === false  && trim($file) != '' && substr($file,0,2) != 'v=' && substr($file,0,4) != 'tpl=')
    {
        $file = str_replace('_','/',$file);

        if(file_exists($dir.'/_'.$dom.'/'.$file.'.full.css'))
        {
            $check_time = filemtime($dir.'/_'.$dom.'/'.$file.'.full.css');
            if($check_time > $max_cache_hour) $max_cache_hour = $check_time;
            $load_files[] = $dir.'/_'.$dom.'/'.$file.'.full.css';
        }
        else if(isset($template) && !empty($template) && file_exists($dir.'/_'.$template.'/'.$file.'.full.css'))
        {
            $check_time = filemtime($dir.'/_'.$template.'/'.$file.'.full.css');
            if($check_time > $max_cache_hour) $max_cache_hour = $check_time;
            $load_files[] = $dir.'/_'.$template.'/'.$file.'.full.css';
        }
        else
        {
            if(file_exists($dir.'/base/'.$file.'.css'))
            {
                $check_time = filemtime($dir.'/base/'.$file.'.css');
                if($check_time > $max_cache_hour) $max_cache_hour = $check_time;
                $load_files[] = $dir.'/base/'.$file.'.css';
            }

            if(file_exists($dir.'/_'.$dom.'/'.$file.'.css'))
            {
                $check_time = filemtime($dir.'/_'.$dom.'/'.$file.'.css');
                if($check_time > $max_cache_hour) $max_cache_hour = $check_time;
                $load_files[] = $dir.'/_'.$dom.'/'.$file.'.css';
            }
            else if(isset($template) && !empty($template) && file_exists($dir.'/_'.$template.'/'.$file.'.css'))
            {
                $check_time = filemtime($dir.'/_'.$template.'/'.$file.'.css');
                if($check_time > $max_cache_hour) $max_cache_hour = $check_time;
                $load_files[] = $dir.'/_'.$template.'/'.$file.'.css';
            }
        }
    }
}

header("Last-Modified: ".gmdate("D, d M Y H:i:s", $max_cache_hour)." GMT");

if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $max_cache_hour)
{
       /*header("HTTP/1.1 304 Not Modified");
       exit;*/
}

ob_start("compress");

foreach ($load_files as $file) {
    if(in_array($file, $already_loaded)) continue;
    $alread_loaded[] = $file;
    echo str_replace('{{URL}}',$protocol.$presub.'.'.$predom.'.'.$pretld.'/',@file_get_contents($file));
}

ob_end_flush();
