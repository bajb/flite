<?php
ini_set('display_errors', false);
$dir         = dirname(__FILE__);
$serverparts = explode('.', $_SERVER['HTTP_HOST'], 3);
$sub         = count($serverparts) == 3 ? $serverparts[0] : '';
$dom         = $serverparts[count($serverparts) - 2];
$tld         = $serverparts[count($serverparts) - 1];
$protocol    = (isset($_SERVER['HTTP_VIA']) && strpos($_SERVER['HTTP_VIA'], 'HTTPS') > -1)
    ? 'https://' : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
if($tld === 'local')
{
    $dir = getcwd() . '/';
}
@include_once(dirname(dirname(__FILE__)) . '/domain.fiddle.php');
if(isset($_SERVER['HTTP_ACCEPT_ENCODING'])
    && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')
    && !in_array($tld, array('dev', 'local'))
)
{
    ob_start("ob_gzhandler");
}
else
{
    ob_start();
}
$max_cache_hour = 0;
if(stristr($_SERVER['QUERY_STRING'], ';v='))
{
    list(, $cache_hour) = explode(';v=', $_SERVER['QUERY_STRING']);
    $cache_hour = intval($cache_hour);
    if($cache_hour > 0) $max_cache_hour = $cache_hour;
}
if(stristr($_SERVER['QUERY_STRING'], ';tpl='))
{
    list(, $template) = explode(';tpl=', $_SERVER['QUERY_STRING']);
    if(stristr($template, ';')) list($template,) = explode(';', $template);
}
function compress($buffer)
{
    global $tld;
    if(in_array($tld, array('dev', 'local'))) return $buffer;
    /* remove comments */
    $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
    $buffer = preg_replace('!^([\t ]+)?\/\/.+$!m', '', $buffer);
    /* remove tabs, spaces, newlines, etc. */
    $buffer = str_replace(array("\t"), ' ', $buffer);
    $buffer = str_replace(array("\r\n", "\r", "\n", '  ', '    ', '    '), '', $buffer);

    return $buffer;
}

header('Content-type: text/javascript');
header('set Cache-Control "max-age=2419200, public"');
$files = explode(';', urldecode($_SERVER['QUERY_STRING']));
if(!is_array($files)) $files = array($files);
$already_loaded = $load_files = array();
foreach($files as $file)
{
    if(strpos($file, '/') === false && substr($file, 0, 2) != 'v=' && substr($file, 0, 4) != 'tpl=' && trim($file) != '')
    {
        $file = str_replace('_', '/', $file);
        if(file_exists($dir . '/_' . $dom . '/' . $file . '.full.js'))
        {
            $check_time = filemtime($dir . '/_' . $dom . '/' . $file . '.full.js');
            if($check_time > $max_cache_hour) $max_cache_hour = $check_time;
            $load_files[] = $dir . '/_' . $dom . '/' . $file . '.full.js';
        }
        else if(isset($template) && !empty($template) && file_exists($dir . '/_' . $template . '/' . $file . '.full.js'))
        {
            $check_time = filemtime($dir . '/_' . $template . '/' . $file . '.full.js');
            if($check_time > $max_cache_hour) $max_cache_hour = $check_time;
            $load_files[] = $dir . '/_' . $template . '/' . $file . '.full.js';
        }
        else
        {
            if(file_exists($dir . '/base/' . $file . '.js'))
            {
                $check_time = filemtime($dir . '/base/' . $file . '.js');
                if($check_time > $max_cache_hour) $max_cache_hour = $check_time;
                $load_files[] = $dir . '/base/' . $file . '.js';
            }
            if(file_exists($dir . '/_' . $dom . '/' . $file . '.js'))
            {
                $check_time = filemtime($dir . '/_' . $dom . '/' . $file . '.js');
                if($check_time > $max_cache_hour) $max_cache_hour = $check_time;
                $load_files[] = $dir . '/_' . $dom . '/' . $file . '.js';
            }
            else if(isset($template) && !empty($template) && file_exists($dir . '/_' . $template . '/' . $file . '.js'))
            {
                $check_time = filemtime($dir . '/_' . $template . '/' . $file . '.js');
                if($check_time > $max_cache_hour) $max_cache_hour = $check_time;
                $load_files[] = $dir . '/_' . $template . '/' . $file . '.js';
            }
        }
    }
}
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $max_cache_hour) . " GMT");
if(@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $max_cache_hour)
{
    header("HTTP/1.1 304 Not Modified");
    exit;
}
ob_start("compress");
foreach($load_files as $file)
{
    if(in_array($file, $already_loaded)) continue;
    $alread_loaded[] = $file;
    $f = @file_get_contents($file);
    if(substr($file, -8) == 'flite.js')
    {
        $f = str_replace('##DOMAIN##', $dom, $f);
        $f = str_replace('##TLD##', $tld, $f);
        $f = str_replace('##SUBDOMAIN##', $sub, $f);
        $f = str_replace('##PROTOCOL##', $protocol, $f);
        $f = str_replace('##TEMPLATE##', isset($template) ? $template : '', $f);
    }
    echo $f . ";\n";
}
ob_end_flush();
