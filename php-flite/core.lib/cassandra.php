<?php

class CassandraObject
{
    private $columnFamily = '';
    private $CFConnection;
    private $cassandra_connection = 'cassandra';

    public function __construct($cf,$connection_name='cassandra',$autopack_names=true,$autopack_values=true,$read_consistency_level=cassandra_ConsistencyLevel::QUORUM,
    $write_consistency_level=cassandra_ConsistencyLevel::QUORUM,$buffer_size=1024)
    {
        global $_FLITE;
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
            $data = $this->CFConnection->get($key,$columns);
            if(FC::count($columns) == 1 && $data) return $data[$columns[0]];
            if($return_object && $data) return FC::array_to_object($data);
            else if($data) return $data;
            else throw new Exception('Key {'.$key.'} Not Found',404);
        }
        catch (Exception $e){ global $_FLITE; $_FLITE->Exception('CassandraObject','GetData',$e); return false; }
    }

    public function SetData($key,$data)
    {
        try { $this->CFConnection->insert($key,$data); }
        catch (Exception $e){ global $_FLITE; $_FLITE->Exception('CassandraObject','SetData',$e); return false; }
        return true;
    }

    public function __call($method, $args)
    {
        if(method_exists($this->CFConnection,$method)) return call_user_method_array($method,$this->CFConnection,$args);
        throw new ErrorException ('Call to Undefined Method/Class Function', 0, E_ERROR);
    }
}