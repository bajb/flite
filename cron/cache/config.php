<?php
$dir = substr(dirname(__FILE__),0,-10);
$final = '<?php';
$final .= incFile('config.php');

function incFile($file)
{
    global $dir;
    $final = "\n\n/* $file Start */\n\n";
    $phpscript = file($dir . $file);
    $pcount = count($phpscript);
    foreach ($phpscript as $line_num => $line)
    {
        if(substr(trim($line),0,42) == 'include_once($this->GetConfig(\'site_root\')')
        {
            $incfile = explode("'",$line);
            if(count($incfile) == 5)
            {
                if(substr($incfile[3],0,5) == 'flite')
                {
                    $line = incFile(substr($incfile[3],6));
                }
            }
        }

        if($line_num > 0) $final .= $line;
        if($line_num == $pcount && $line == '?>') break;
    }
    $final .= "\n\n/* $file End */\n\n";

    return $final;
}

file_put_contents($dir . 'cache/config.php',$final);