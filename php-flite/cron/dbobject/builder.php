<?php
require_once('../../boot.php');

$db_connections = array();
if(is_array($_FLITE->GetConfig('databases')))
{
    $dbs = $_FLITE->GetConfig('databases');
    foreach ($dbs as $db_conf)
    {
        $db_connections[$db_conf['flite_name']] = isset($db_conf['classname_prefix']) ? $db_conf['classname_prefix'] : '';
    }
}

if(empty($db_connections)) $db_connections = array('db' => '');

foreach ($db_connections as $db_connection => $prefix)
{
    if($tables = $_FLITE->{$db_connection}->GetRows("SHOW TABLES"))
    {
        foreach ($tables as $table)
        {
            $cname = '';
            $tbl = $table->{'Tables_in_' . $_FLITE->{$db_connection}->GetDBName()};

            $cname = $prefix . '_' . $tbl;
            if(substr($cname,0,4) == 'tbl_') $cname = substr($cname,4);
            if(substr($cname,strlen($cname) - 3,3) == 'ies') $cname = substr($cname,0,strlen($cname) - 3) . 'y';
            if(substr($cname,strlen($cname) - 2,2) != 'us' && substr($cname,strlen($cname) - 2,2) != 'ss') $cname = rtrim($cname, 's');

            $classname = str_replace(' ','',ucwords(strtolower(str_replace('_',' ',$cname))));
            $filename = strtolower(str_replace('_','',$cname)) . '.php';
            $filename = '../../dblib/' . $filename;

            unset($primary1);
            $contents = $primary = '';
            if($columns = $_FLITE->{$db_connection}->GetRows("SHOW COLUMNS FROM `$tbl`"))
            {

                $contents =  '<?php' . "\n";
                $contents .= '/**
     * This class has been automatically generated by PHP-Flite
     *
     * @author     Brooke Bryan <brooke@bajb.net>
     * @copyright  Copyright (c) 2007 through '. date("Y") .'
     * @version    1.0
     *
     */

    ';
                $contents .=  'class ' . $classname . " extends DatabaseObject\n";
                $contents .=  '{' . "\n";

                $contents .=  "\t" . '/**';
                $primary = $cols = array();
                foreach ($columns as $column)
                {
                    $contents .=  "\n\t" . ' * @public ' . $column->Type . ' $' . $column->Field;

                    if($column->Key == 'PRI')
                    {
                        $primary[] = $column->Field;
                    }
                    $cols[] = $column->Field;
                }

                if(empty($primary))
                {
                    $contents = "";
                    continue;
                }

                $contents .=  "\n\t" . ' */';
                $contents .=  "\n\n\t" . 'public $' . implode(', $',$cols) .";";

                //Constructor
                $contents .=  "\n\n\t" . '/**';
                foreach ($columns as $column)
                {
                    if($column->Key == 'PRI')
                    {
                        $contents .=  "\n\t" . ' * @param ' . $column->Type . ' $' . $column->Field;
                    }
                }
                $contents .=  "\n\t" . '**/';

                $contents .=  "\n\t" . 'public function __construct($'. implode('=null, $',$primary) .'=null)';
                $contents .=  "\n\t" . '{';
                $contents .=  "\n\t\t" . 'parent::__construct(\''. $db_connection .'\',true,\'slave\');';
                $contents .=  "\n\t\t" . '$this->SetPrimaryKeys(array(\''. implode("','",$primary).'\'));';
                $contents .=  "\n\t\t" . '$this->SetAvailableColumns(array(\''. implode("','",$cols).'\'));';
                $contents .=  "\n\t\t" . '$this->SetTable(\''. $tbl .'\');';
                $contents .=  "\n\t\t" . 'unset($this->'. implode(',$this->',$cols) .');';

                foreach ($primary as $pr)
                {
                    $contents .=  "\n\t\t" . 'if(!is_null($'. $pr .')) $this->'. $pr .' = $'. $pr .';';
                }

                $contents .=  "\n\t" . '}';

                //End Constructor


                $contents .=  "\n}";
            }else{
                print 'No Columns Found<br/>';
            }


            $replace = true;
            if(!file_exists($filename) || $replace)
            {
                if($primary != '')
                {
                    @fopen($filename, "wb");
                    if (is_writable($filename))
                    {
                        if (!$handle = fopen($filename, 'a')) {
                            echo "Cannot open file ($filename)<br/>";
                            exit;
                        }

                        // Write $somecontent to our opened file.
                        if (fwrite($handle, $contents) === FALSE) {
                            echo "Cannot write to file ($filename)<br/>";
                            exit;
                        }

                        echo "Success, wrote (".htmlspecialchars($contents).") to file ($filename)<br/>";

                        fclose($handle);

                    } else {
                        echo "The file $filename is not writable<br/>";
                    }
                }
                else
                {
                    echo 'Only tables with primary keys will be processed.<br/>';
                }
            }
            else
            {
                $orig = file_get_contents($filename);
                if($orig == $contents)
                {
                    echo 'Skipping ' . $filename . ' - Same File<br/>';
                }
                else
                {
                    echo 'Skipping ' . $filename . ' - New Content';
                    echo '<pre style="border:1px solid #aaa;background:#000;color:#eee;width:500px;height:440px;overflow:auto;float:left;clear:left;">';
                    echo htmlspecialchars($orig);
                    echo  '</pre>';
                    echo '<pre style="border:1px solid #aaa;background:#000;color:#eee;width:500px;height:440px;overflow:auto;">';
                    echo htmlspecialchars($contents);
                    echo  '</pre><br/><br/>';
                }

            }
        }
    }
    else
    print 'No Tables Found<br/>';


    $errors = $_FLITE->{$db_connection}->GetErrors();
    if(!empty($errors))
    {
        print_nice_array($errors);
    }
}