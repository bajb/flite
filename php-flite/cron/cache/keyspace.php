<?php
require_once(substr(dirname(__FILE__),0,-10) . 'boot.php');

/**
 * Describe Keyspace
 */
$keyspace_description = $_FLITE->cassandra->describe_keyspace();
$output = '<?php';
$output .= "\n".'$_KEYSPACE_DESCRIPTION = new stdClass();';
if($keyspace_description)
{
    $output .= "\n".'$_KEYSPACE_DESCRIPTION->name = "'.$keyspace_description->name.'";';
    $output .= "\n".'$_KEYSPACE_DESCRIPTION->strategy_class = "'.$keyspace_description->strategy_class.'";';
    $output .= "\n".'$_KEYSPACE_DESCRIPTION->strategy_options = array();';
    foreach($keyspace_description->strategy_options as $strategy_options_key => $strategy_options_val)
    {
        $output .= "\n".'$_KEYSPACE_DESCRIPTION->strategy_options["'.$strategy_options_key.'"] = "'.$strategy_options_val.'";';
    }
    $output .= "\n".'$_KEYSPACE_DESCRIPTION->replication_factor = "'.$keyspace_description->replication_factor.'";';
    $output .= "\n".'$_KEYSPACE_DESCRIPTION->cf_defs = array();';
    $cf_count = 0;
	foreach($keyspace_description->cf_defs as $cf_key => $column_family)
	{
        $output .= "\n".'$_KEYSPACE_DESCRIPTION->cf_defs["'.$cf_count.'"] = new stdClass();';
	    foreach($column_family as $cf_key => $cf_val)
	    {
	        if($cf_key == 'column_metadata')
	        {
	            $output .= "\n".'$_KEYSPACE_DESCRIPTION->cf_defs["'.$cf_count.'"]->'.$cf_key.' = array();';
	            foreach($cf_val as $cm_key => $cm_val)
	            {
	                $output .= "\n".'$_KEYSPACE_DESCRIPTION->cf_defs["'.$cf_count.'"]->'.$cf_key.'['.$cm_key.'] = "'.$cm_val.'";';
	            }
	            continue;
	        }
	        $output .= "\n".'$_KEYSPACE_DESCRIPTION->cf_defs["'.$cf_count.'"]->'.$cf_key.' = "'.$cf_val.'";';
	    }
        $cf_count++;
	}
}

$output .= "\n" . 'return $_KEYSPACE_DESCRIPTION;';

file_put_contents($_FLITE->GetConfig('site_root') . 'php-flite/cache/keyspace_description.php',$output);