<?php
$dir = substr(dirname(__FILE__),0,-10);
$final = '<?php
';
$directory = $dir . 'core.lib/';
$handle = opendir($directory);
if ($handle)
{
    while (false !== ($file = readdir($handle)))
    {
        if(substr($file,-3) == 'php'){
            $final .= "\n\n/* $file Start */\n\n";
            $phpscript = file($directory . $file);
            $pcount = count($phpscript);
            foreach ($phpscript as $line_num => $line)
            {
                if($line_num > 0) $final .= $line;
                if($line_num == $pcount && $line == '?>') break;
            }
            $final .= "\n\n/* $file End */\n\n";
        }
    }
    closedir($handle);
}


file_put_contents($dir . 'cache/core.lib.php',$final);