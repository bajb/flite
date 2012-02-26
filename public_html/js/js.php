<?php
ini_set('display_errors',false);

$dir = dirname(__FILE__);
$serverparts = explode('.',$_SERVER['HTTP_HOST'],3);

$sub = count($serverparts) == 3 ? $serverparts[0] : '';
$dom = $serverparts[count($serverparts) - 2];
$tld = $serverparts[count($serverparts) - 1];
$protocol = (isset($_SERVER['HTTP_VIA']) && strpos($_SERVER['HTTP_VIA'],'HTTPS') > -1) ? 'https://' : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';

if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && $tld != 'dev') {
    ob_start("ob_gzhandler");
} else {
    ob_start();
}

function compress($buffer)
{
    global $tld;
    if($tld == 'dev' ) return $buffer;
    /* remove comments */
    $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
    /* remove tabs, spaces, newlines, etc. */
    $buffer = str_replace(array("\t"),' ', $buffer);
    $buffer = str_replace(array("\r\n", "\r", "\n", '  ', '    ', '    '), '', $buffer);
    return $buffer;
}

header('Content-type: text/javascript');
header('set Cache-Control "max-age=2419200, public"');

ob_start("compress");

$files = explode('|',urldecode($_SERVER['QUERY_STRING']));
if(!is_array($files)) $files = array($files);

$already_loaded = array();

foreach ($files as $file) {

    if(in_array($file, $already_loaded)) continue;
    $alread_loaded[] = $file;

    if (strpos($file,'/') === false && substr($file, 0, 2) != 'v=' && trim($file) != '')
    {
        $file = str_replace('_','/',$file);

        if( file_exists( $dir . '/_'. $dom .'/'. $file .'.full.js') ) $f = @file_get_contents($dir . '/_'. $dom .'/'. $file .'.full.js');
        else
        {
            $f = @file_get_contents($dir . '/base/'. $file .'.js');
            if( file_exists( $dir . '/_'. $dom .'/'. $file .'.js') ) $f .= "\n" . @file_get_contents($dir . '/_'. $dom .'/'. $file .'.js');
        }

        if($file == 'flite')
        {
            $f = str_replace('##DOMAIN##', $dom, $f);
            $f = str_replace('##TLD##', $tld, $f);
            $f = str_replace('##SUBDOMAIN##', $sub, $f);
            $f = str_replace('##PROTOCOL##', $protocol, $f);
        }
		echo $f;
    }
}
ob_end_flush();