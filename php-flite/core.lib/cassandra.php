<?php

use phpcassa\ColumnFamily;
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

    public function __construct($cf,$connection_name='cassandra',$autopack_names=true,$autopack_values=true,$read_consistency_level=ConsistencyLevel::QUORUM,
    $write_consistency_level=ConsistencyLevel::QUORUM,$buffer_size=1024)
    {
        $_FLITE = Flite::Base();
        $this->cassandra_connection = $connection_name;
        $this->columnFamily = $cf;
        try { $this->CFConnection = new ColumnFamily($_FLITE->{$this->cassandra_connection}, $this->columnFamily,$autopack_names,$autopack_values,$read_consistency_level,$write_consistency_level,$buffer_size); }
        catch (Exception $e){ $_FLITE->Exception('CassandraObject','__construct',$e); }
        if(!$this->CFConnection) return false;
    }

    public function GetData($key,$columns=null,$return_object=false)
    {
        try
        {
            $data = $this->CFConnection->get($key,null,$columns);
            if(FC::count($columns) == 1 && $data) return $data[$columns[0]];
            if($return_object && $data) return FC::array_to_object($data);
            else if($data) return $data;
            else throw new Exception('Key {'.$key.'} Not Found',404);
        }
        catch (Exception $e){ $_FLITE = Flite::Base(); $_FLITE->Exception('CassandraObject','GetData',$e); return false; }
    }

    public function GetRange($start,$end,$count,$columns)
    {
        try
        {
            $data = $this->CFConnection->get_range($start,$end,$count,null,$columns);
            if($data) return $data;
            else throw new Exception('Range {'. $start.' - '. $end .'} Failed',404);
        }
        catch (Exception $e){ $_FLITE = Flite::Base(); $_FLITE->Exception('CassandraObject','GetRange',$e); return false; }
    }

    public function SetData($key,$data,$ttl=null)
    {
        $insertdata = array();
        foreach ($data as $k => $v)
        {
            $insertdata[$k] = is_null($v) ? "" : $v;
        }
        try { $this->CFConnection->insert($key,$insertdata,null,$ttl); }
        catch (Exception $e){ $_FLITE = Flite::Base(); $_FLITE->Exception('CassandraObject','SetData',$e); return false; }
        return true;
    }

    public function GetColumns($key,$start_column = "",$end_column = "",$reverse_columns = false,$count=10,$return_object=false)
    {
        try
        {
            $columnslice = new SliceRange($start_column,$end_column,$count,$reverse_columns);
            $data = $this->CFConnection->get($key,$columnslice);
            if($return_object && $data) return FC::array_to_object($data);
            else if($data) return $data;
            else throw new Exception('Key {'.$key.'} Not Found',404);
        }
        catch (Exception $e){ $_FLITE = Flite::Base(); $_FLITE->Exception('CassandraObject','GetColumns',$e); return false; }
    }

    public function Delete($key,$columns=null)
    {
        try { $this->CFConnection->remove($key,$columns); }
        catch (Exception $e){ $_FLITE = Flite::Base(); $_FLITE->Exception('CassandraObject','Delete',$e); return false; }
        return true;
    }

    public function GetMulti($keys,$columns=null,$column_start="",$column_finish="",$reverse_order=false,$column_count=100)
    {
        try
        {
            $columnslice = new SliceRange($column_start,$column_finish,$column_count,$reverse_order);
            $data = $this->CFConnection->multiget($keys,$columnslice,$columns);
            if($data) return $data;
            else throw new Exception('MultiGet Failed',404);
        }
        catch (Exception $e){ $_FLITE = Flite::Base(); $_FLITE->Exception('CassandraObject','GetMulti',$e); return false; }
    }

    public function Increment($key,$column,$increase_by=1)
    {
        try { $this->CFConnection->add($key,$column,$increase_by,null,ConsistencyLevel::ONE); }
        catch (Exception $e){ $_FLITE = Flite::Base(); $_FLITE->Exception('CassandraObject','Increment',$e); return false; }
        return true;
    }

    public function Decrement($key,$column,$reduce_by=1)
    {
        if($reduce_by > 0) $reduce_by = -1 * $reduce_by;
        try { $this->CFConnection->add($key,$column,$increase_by,null,ConsistencyLevel::ONE); }
        catch (Exception $e){ $_FLITE = Flite::Base(); $_FLITE->Exception('CassandraObject','Decrement',$e); return false; }
        return true;
    }

    public function __call($method, $args)
    {
        if($method != 'remove') FC::error_report("Direct PhpCassa Call",array("Method" => $method, "Args" => $args));
        if(method_exists($this->CFConnection,$method)) return call_user_func_array(array($this->CFConnection,$method),$args);
        throw new ErrorException ('Call to Undefined Method/Class Function', 0, E_ERROR);
    }
}