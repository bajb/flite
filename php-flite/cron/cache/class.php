<?php
/**
 * User: brooke.bryan
 * Date: 07/09/12
 * Time: 14:30
 * Description: Generate class cache
 */

ini_set('memory_limit', '512M');

function GetClasses($flite_dir, $directory)
{
    $class_lookup = array();
    $handle       = opendir($directory);
    if($handle)
    {
        while(false !== ($file = readdir($handle)))
        {
            if(in_array($file, array('.', '..'))) continue;
            $filepath = $directory . $file;
            if(is_dir($filepath)) $class_lookup = array_merge($class_lookup, GetClasses($flite_dir, $filepath . '/'));
            if(substr($file, -3) == 'php')
            {
                $tokens      = token_get_all(file_get_contents($filepath));
                $class_token = false;
                foreach($tokens as $token)
                {
                    if(is_array($token))
                    {
                        if($token[0] == T_CLASS)
                        {
                            $class_token = true;
                        }
                        else if($class_token && $token[0] == T_STRING)
                        {
                            $class_lookup[$token[1]] = str_replace($flite_dir, '', $filepath);
                            $class_token             = false;
                        }
                    }
                }

            }
        }
        closedir($handle);
    }

    return $class_lookup;
}

$flite_dir    = dirname(dirname(dirname(__FILE__)));
$class_lookup = $dirs = array();
$dirs[]       = $flite_dir . '/dblib/';
$dirs[]       = $flite_dir . '/lib/';
foreach($dirs as $directory) $class_lookup = array_merge($class_lookup, GetClasses($flite_dir, $directory));

$cached = '[classes]';
foreach($class_lookup as $class => $path) $cached .= "\n$class=$path";
file_put_contents($flite_dir . '/cache/class.ini', $cached);

echo number_format(count($class_lookup), 0) .
" class references cached in " . number_format(strlen($cached), 0) . " bytes\n";
