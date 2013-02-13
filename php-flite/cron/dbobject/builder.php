<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/boot.php');

$dblib = FLITE_DIR . '/dblib/';
$db_connections = array();

if(is_array($_FLITE->GetConfig('databases')))
{
    $dbs = $_FLITE->GetConfig('databases');
    foreach ($dbs as $db_conf)
    {
        if(isset($db_conf['classname_prefix']) && strtoupper($db_conf['classname_prefix']) == 'NULL') continue;
        $db_connections[$db_conf['flite_name']] = isset($db_conf['classname_prefix']) ? $db_conf['classname_prefix'] : '';
        if(isset($db_conf['classname_prefix']))
        {
            $current_path = strtolower($dblib . str_replace('_','/',$db_conf['classname_prefix']));
            if(!file_exists($current_path)) mkdir($current_path,0755,true);
        }
    }
}

if(empty($db_connections)) $db_connections = array('db' => '');

foreach ($db_connections as $db_connection => $prefix)
{
    $tables = $_FLITE->DB($db_connection)->GetRows("SHOW TABLES");
    if($tables)
    {
        foreach ($tables as $table)
        {
            $cname = '';
            $tbl = $table->{'Tables_in_' . $_FLITE->DB($db_connection)->GetDBName()};

            $cname = str_replace('_','|',$prefix) . '_' . $tbl;
            if(substr($cname,0,4) == 'tbl_') $cname = substr($cname,4);
            if(substr($cname,strlen($cname) - 3,3) == 'ies') $cname = substr($cname,0,strlen($cname) - 3) . 'y';
            if(substr($cname,strlen($cname) - 2,2) != 'us' && substr($cname,strlen($cname) - 2,2) != 'ss') $cname = rtrim($cname, 's');

            $classname = str_replace('|','_',str_replace(' ','',ucwords(strtolower(str_replace('_',' ',$cname)))));
            $filename = strtolower(str_replace('_','',$cname)) . '.php';
            $filename = $dblib . str_replace('|','/',$filename);

            unset($primary1);
            $contents = $primary = '';
            $columns = $_FLITE->DB($db_connection)->GetRows("SHOW COLUMNS FROM `$tbl`");
            if($columns)
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
                $contents .=  "\n\t" . ' * @param bool|string|array $preload_keys';
                $contents .=  "\n\t" . ' */';

                $contents .=  "\n\t" . 'public function __construct($'. implode('=null, $',$primary) .'=null,$preload_keys=false)';
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

                $contents .=  "\n\t\t" . 'if($preload_keys) $this->Load($preload_keys);';

                $contents .=  "\n\t" . '}';

                //End Constructor


                $contents .=  "\n}";
            }else{
                print 'No Columns Found\n';
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
                            echo "Cannot open file ($filename)\n";
                            exit;
                        }

                        // Write $somecontent to our opened file.
                        if (fwrite($handle, $contents) === FALSE) {
                            echo "Cannot write to file ($filename)\n";
                            exit;
                        }

                        echo "Success, wrote ($filename)\n";//(".htmlspecialchars($contents).") to file

                        fclose($handle);

                    } else {
                        echo "The file $filename is not writable\n";
                    }
                }
                else
                {
                    echo 'Only tables with primary keys will be processed.\n';
                }
            }
            else
            {
                $orig = file_get_contents($filename);
                if($orig == $contents)
                {
                    echo 'Skipping ' . $filename . ' - Same File\n';
                }
                else
                {
                    echo 'Skipping ' . $filename . ' - New Content';
                    echo '<pre style="border:1px solid #aaa;background:#000;color:#eee;width:500px;height:440px;overflow:auto;float:left;clear:left;">';
                    echo htmlspecialchars($orig);
                    echo  '</pre>';
                    echo '<pre style="border:1px solid #aaa;background:#000;color:#eee;width:500px;height:440px;overflow:auto;">';
                    echo htmlspecialchars($contents);
                    echo  '</pre>\n\n';
                }

            }
        }
    }
    else
    {
        print 'No Tables Found\n';
    }

    $errors = $_FLITE->DB($db_connection)->GetErrors();
    if(!empty($errors)) print_r($errors);
}
