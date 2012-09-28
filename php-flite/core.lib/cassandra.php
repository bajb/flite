<?php
use phpcassa\ColumnFamily;
use phpcassa\SuperColumnFamily;
use phpcassa\ColumnSlice;
use phpcassa\SystemManager;
use phpcassa\Schema\StrategyClass;
use cassandra;
use cassandra\SliceRange;
use cassandra\ConsistencyLevel;

class CassandraObject
{
    private $columnFamily = '';
    private $CFConnection;
    private $cassandra_connection = 'cassandra';
    private $is_super = false;
    private $read_consistency_level = ConsistencyLevel::QUORUM;
    private $write_consistency_level = ConsistencyLevel::QUORUM;
    private $buffer_size = 1024;
    private $autopack_names = true;
    private $autopack_values = true;
    private $initiated = false;
    private $insert_format = false;
    private $return_format = false;

    public function __construct ($cf, $connection_name = 'cassandra', $autopack_names = true, $autopack_values = true,
                                $read_consistency_level = ConsistencyLevel::QUORUM, $write_consistency_level = ConsistencyLevel::QUORUM, $buffer_size = 1024)
    {
        $this->cassandra_connection = $connection_name;
        $this->columnFamily = $cf;
        $this->autopack_names = $autopack_names;
        $this->autopack_values = $autopack_values;
        $this->read_consistency_level = $read_consistency_level;
        $this->write_consistency_level = $write_consistency_level;
        $this->buffer_size = $buffer_size;
        $this->initiated = false;
    }

    public function Connected()
    {
        return $this->initiated;
    }

    public function Connect()
    {
        if($this->initiated) return true;

        $_FLITE = Flite::Base();
        $keyspace_description = $_FLITE->{$this->cassandra_connection}->describe_keyspace();
        if ($keyspace_description && isset($keyspace_description->cf_defs))
        {
            foreach ($keyspace_description->cf_defs as $coldef)
            {
                if(isset($coldef->name) && $coldef->name == $this->columnFamily)
                {
                    $this->is_super = (isset($coldef->column_type) && $coldef->column_type == "Super");
                }
            }
        }

        try
        {
            if ($this->is_super)
            {
                $this->CFConnection = new SuperColumnFamily($_FLITE->{$this->cassandra_connection}, $this->columnFamily,
                    $this->autopack_names, $this->autopack_values, $this->read_consistency_level, $this->write_consistency_level,
                    $this->buffer_size);
            }
            else
            {
                $this->CFConnection = new ColumnFamily($_FLITE->{$this->cassandra_connection}, $this->columnFamily,
                    $this->autopack_names, $this->autopack_values, $this->read_consistency_level, $this->write_consistency_level,
                    $this->buffer_size);
            }
        }
        catch (Exception $e)
        {
            $_FLITE->Exception('CassandraObject', '__construct', $e);
        }

        $this->initiated = !(!$this->CFConnection);

        if($this->return_format !== false) $this->ReturnFormat($this->return_format);
        if($this->insert_format !== false) $this->InsertFormat($this->insert_format);

    }

    public function InsertFormat($format=ColumnFamily::ARRAY_FORMAT)
    {
        $this->insert_format = $format;
        if($this->Connected())
        {
            $this->CFConnection->insert_format = $format;
        }
    }

    public function ReturnFormat($format=ColumnFamily::ARRAY_FORMAT)
    {
        $this->return_format = $format;
        if($this->Connected())
        {
            $this->CFConnection->return_format = $format;
        }
    }

    public function GetData ($key, $columns = null, $return_object = false,$single_return=true)
    {
        if(file_exists(FLITE_DIR . '/.cassandra.debug'))
        {
            error_log("Getting key '$key' in $this->columnFamily");
        }
        $this->Connect();
        try
        {
            $data = $this->CFConnection->get($key, null, $columns);
            if ($single_return && FC::count($columns) == 1 && $data) return $data[$columns[0]];
            if ($return_object && $data)
                return FC::array_to_object($data);
            else if ($data)
                return $data;
            else throw new Exception('Key {' . $key . '} Not Found', 404);
        }
        catch (Exception $e)
        {
            $_FLITE = Flite::Base();
            $_FLITE->Exception('CassandraObject', 'GetData', $e);
            return false;
        }
    }

    public function GetRange ($start, $end, $count, $columns)
    {
        $this->Connect();
        try
        {
            $data = $this->CFConnection->get_range($start, $end, $count, null, $columns);
            if ($data)
                return $data;
            else throw new Exception('Range {' . $start . ' - ' . $end . '} Failed', 404);
        }
        catch (Exception $e)
        {
            $_FLITE = Flite::Base();
            $_FLITE->Exception('CassandraObject', 'GetRange', $e);
            return false;
        }
    }

    public function SetData ($key, $data, $ttl = null)
    {
        if(file_exists(FLITE_DIR . '/.cassandra.debug'))
        {
            error_log("Inserting data @ key '$key' in $this->columnFamily");
        }
        $this->Connect();
        $insertdata = array();
        foreach ($data as $k => $v)
        {
            $insertdata[$k] = is_null($v) ? "" : $v;
        }
        try
        {
            $this->CFConnection->insert($key, $insertdata, null, $ttl);
        }
        catch (Exception $e)
        {
            $_FLITE = Flite::Base();
            $_FLITE->Exception('CassandraObject', 'SetData', $e);
            return false;
        }
        return true;
    }

    public function GetColumns ($key, $start_column = "", $end_column = "", $reverse_columns = false, $count = 10,
                                $return_object = false)
    {
        if(file_exists(FLITE_DIR . '/.cassandra.debug'))
        {
            error_log("Getting columns for key '$key' in $this->columnFamily");
        }
        $this->Connect();
        try
        {
            $columnslice = new ColumnSlice($start_column, $end_column, $count, $reverse_columns);
            $data = $this->CFConnection->get($key, $columnslice);
            if ($return_object && $data)
                return FC::array_to_object($data);
            else if ($data)
                return $data;
            else throw new Exception('Key {' . $key . '} Not Found', 404);
        }
        catch (Exception $e)
        {
            $_FLITE = Flite::Base();
            $_FLITE->Exception('CassandraObject', 'GetColumns', $e);
            return false;
        }
    }

    public function GetSlice($key,ColumnSlice $slice,$return_object=false)
    {
        $this->Connect();
        try
        {
            $data = $this->CFConnection->get($key, $slice);
            if ($return_object && $data)
                return FC::array_to_object($data);
            else if ($data)
                return $data;
            else throw new Exception('Key {' . $key . '} Not Found', 404);
        }
        catch (Exception $e)
        {
            $_FLITE = Flite::Base();
            $_FLITE->Exception('CassandraObject', 'GetSlice', $e);
            return false;
        }
    }

    public function Delete ($key, $columns = null)
    {
        $this->Connect();
        try
        {
            $this->CFConnection->remove($key, $columns);
        }
        catch (Exception $e)
        {
            $_FLITE = Flite::Base();
            $_FLITE->Exception('CassandraObject', 'Delete', $e);
            return false;
        }
        return true;
    }

    public function GetMulti ($keys, $columns = null, $column_start = "", $column_finish = "", $reverse_order = false,
                            $column_count = 100)
    {
        $this->Connect();
        try
        {
            $columnslice = new ColumnSlice($column_start, $column_finish, $column_count, $reverse_order);
            $data = $this->CFConnection->multiget($keys, $columnslice, $columns);
            if ($data)
                return $data;
            else throw new Exception('MultiGet Failed', 404);
        }
        catch (Exception $e)
        {
            $_FLITE = Flite::Base();
            $_FLITE->Exception('CassandraObject', 'GetMulti', $e);
            return false;
        }
    }

    public function Increment ($key, $column, $increase_by = 1)
    {
        $this->Connect();
        try
        {
            $this->CFConnection->add($key, $column, $increase_by, null, ConsistencyLevel::ONE);
        }
        catch (Exception $e)
        {
            $_FLITE = Flite::Base();
            $_FLITE->Exception('CassandraObject', 'Increment', $e);
            return false;
        }
        return true;
    }

    public function Decrement ($key, $column, $reduce_by = 1)
    {
        $this->Connect();
        if ($reduce_by > 0) $reduce_by = - 1 * $reduce_by;
        try
        {
            $this->CFConnection->add($key, $column, $reduce_by, null, ConsistencyLevel::ONE);
        }
        catch (Exception $e)
        {
            $_FLITE = Flite::Base();
            $_FLITE->Exception('CassandraObject', 'Decrement', $e);
            return false;
        }
        return true;
    }

    public function RemoveCounter ($key, $column,$consistency_level=null)
    {
        $this->Connect();
        try
        {
            $this->CFConnection->remove_counter($key,$column,$consistency_level);
        }
        catch (Exception $e)
        {
            $_FLITE = Flite::Base();
            $_FLITE->Exception('CassandraObject', 'RemoveCounter', $e);
            return false;
        }
        return true;
    }

    public function BatchInsert($rows,$ttl=null)
    {
        $this->Connect();
        try
        {
            $this->CFConnection->batch_insert($rows,null,$ttl);
            return true;
        }
        catch (Exception $e)
        {
            $_FLITE = Flite::Base();
            $_FLITE->Exception('CassandraObject', 'BatchInsert', $e);
            return false;
        }
        return true;
    }

    public function __call ($method, $args)
    {
        $this->Connect();
        if ($method != 'remove') FC::error_report("Direct PhpCassa Call", array("Method" => $method,"Args" => $args));
        if (method_exists($this->CFConnection, $method)) return call_user_func_array(array($this->CFConnection,$method),
                $args);
        throw new ErrorException('Call to Undefined Method/Class Function', 0, E_ERROR);
    }
}