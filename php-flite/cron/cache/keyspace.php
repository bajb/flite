<?php
require_once (substr(dirname(__FILE__), 0, - 10) . 'boot.php');

$cassandra_clustername = $_FLITE->GetConfig('cassandra_cluster');
if ($cassandra_clustername && is_array($cassandra_clustername))
{
    $process_keyspaces = array();
    foreach ($cassandra_clustername as $keyspace)
    {
        if (is_array($keyspace))
        {
            $process_keyspaces[] = (isset($keyspace['flite_name']) ? $keyspace['flite_name'] : "cassandra_" . $keyspace);
        }
        else
        {
            $process_keyspaces[] = (FC::count($cassandra_clustername) == 1 ? 'cassandra' : "cassandra_" . $keyspace);
        }
    }
error_reporting(E_ALL);
  ini_set("display_errors",true);
    foreach ($process_keyspaces as $flite_name)
    {
        /**
         * Describe Keyspace
         */
        echo $flite_name . "\n";

        $keyspace_description = $_FLITE->$flite_name->describe_keyspace();

        $output = '<?php';
        $output .= "\n" . '$_KEYSPACE_DESCRIPTION = new stdClass();';
        if ($keyspace_description)
        {
            $output .= "\n" . '$_KEYSPACE_DESCRIPTION->name = "' . $keyspace_description->name . '";';
            $output .= "\n" . '$_KEYSPACE_DESCRIPTION->strategy_class = "' . $keyspace_description->strategy_class . '";';
            $output .= "\n" . '$_KEYSPACE_DESCRIPTION->strategy_options = array();';
            foreach ($keyspace_description->strategy_options as $strategy_options_key => $strategy_options_val)
            {
                $output .= "\n" . '$_KEYSPACE_DESCRIPTION->strategy_options["' . $strategy_options_key . '"] = "' .
                         $strategy_options_val . '";';
            }
            $output .= "\n" . '$_KEYSPACE_DESCRIPTION->replication_factor = "' . $keyspace_description->replication_factor . '";';
            $output .= "\n" . '$_KEYSPACE_DESCRIPTION->cf_defs = array();';
            $cf_count = 0;
            foreach ($keyspace_description->cf_defs as $cf_key => $column_family)
            {
                $output .= "\n" . '$_KEYSPACE_DESCRIPTION->cf_defs["' . $cf_count . '"] = new stdClass();';
                foreach ($column_family as $cf_key => $cf_val)
                {
                    if ($cf_key == 'column_metadata')
                    {
                        $output .= "\n" . '$_KEYSPACE_DESCRIPTION->cf_defs["' . $cf_count . '"]->' . $cf_key . ' = array();';
                        foreach ($cf_val as $cm_key => $cm_val)
                        {
                          if(is_scalar($cm_val))
                          {
                            $output .= "\n" . '$_KEYSPACE_DESCRIPTION->cf_defs["' . $cf_count . '"]->' . $cf_key . '[' . $cm_key .
                            '] = "' . $cm_val . '";';
                          }
                          else
                          {
                            foreach($cm_val as $mt_key => $mt_val)
                            {
                              $output .= "\n" . '$_KEYSPACE_DESCRIPTION->cf_defs["' . $cf_count . '"]->' . $cf_key . '[' . $cm_key .
                              ']->'. $mt_key .' = "' . $mt_val . '"; //LOOK';
                            }
                          }
                        }
                    }
                  else
                  {
                    $output .= "\n" . '$_KEYSPACE_DESCRIPTION->cf_defs["' . $cf_count . '"]->' . $cf_key . ' = "' . $cf_val .
                    '";';
                  }

                }
                $cf_count ++;
            }
        }

        $output .= "\n" . 'return $_KEYSPACE_DESCRIPTION;';

        if($flite_name == 'cassandra')
        {
            file_put_contents(FLITE_DIR . '/cache/keyspace_description.php', $output);
        }
        file_put_contents(FLITE_DIR . '/cache/keyspace_'. strtolower($keyspace_description->name) .'_description.php', $output);
    }
}
