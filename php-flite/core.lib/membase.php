<?php

class Membase
{
    private $connection;

    public function __construct()
    {
        $this->connection = new Memcache;
    }

    public function addServer($host,$port=11211,$persistent=true)
    {
        $this->connection->addServer($host,$port,$persistent);
    }

    public function GenerateID($resource_type,$resource_key)
    {
        return $resource_type . '-' . md5($resource_key) . '-' . $resource_key;
    }

    public function StoreRecord($key,$value)
    {
        $compress = is_bool($value) || is_int($value) || is_float($value) ? false : MEMCACHE_COMPRESSED;
        return $this->connection->set($key,$value,$compress);
    }

    public function GetRecord($key)
    {
        return $this->connection->get($key);
    }

    public function GetRecords($keys)
    {
        if(is_array($keys))
        {
            return $this->connection->get($keys);
        }
        else
        {
            return false;
        }
    }

    public function DeleteRecord($key)
    {
        return $this->connection->delete($key);
    }

    public function __destruct()
    {
        $this->connection->close();
    }
}
