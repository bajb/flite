<?php

class DatabaseObject
{
    protected $dbobject_primary_keys = array();
    protected $dbobject_primary_key_data = array();
    protected $dbobject_table_name = '';
    protected $dbobject_connection;
    protected $dbobject_slave_append = 'slave';
    protected $dbobject_allow_slave = true;
    protected $dbobject_available_columns = array();
    protected $dbobject_row_exists = false;
    protected $changed_fields;
    protected $dbobject_data = array();

    public function __construct($flitedb='db',$allow_slave=true,$slave_append='slave')
    {
        $_FLITE = Flite::Base();
        $this->dbobject_connection = $flitedb;
        if($allow_slave && !empty($slave_append) && !isset($_FLITE->{$flitedb . $slave_append})) $allow_slave = false;
        $this->dbobject_allow_slave = $allow_slave;
        $this->dbobject_slave_append = $slave_append;
    }

    public function SetTable($table)
    {
        $this->dbobject_table_name = $table;
    }

    public function SetPrimaryKeys($keys)
    {
        $this->dbobject_primary_keys = $keys;
    }

    public function SetAvailableColumns($columns)
    {
        $this->dbobject_available_columns = $columns;
    }

    public function GetAvailableColumns()
    {
        if(empty($this->dbobject_available_columns))
        {
            $_FLITE = Flite::Base();
            $columns = $_FLITE->{$this->dbobject_connection}->GetRows("SHOW COLUMNS FROM `$this->dbobject_table_name`");
            foreach ($columns as $col)
            {
                $this->dbobject_available_columns[] = $col->field;
            }
        }
        return $this->dbobject_available_columns;
    }

    //Do not attempt to insert
    public function UpdateOnSave()
    {
        $this->dbobject_row_exists = true;
    }

    public function GetPrimaryKeys()
    {
        return $this->dbobject_primary_keys;
    }

    public function GetWhere()
    {
        $where = " WHERE ";
        $keys = array("1=1");

        if(!empty($this->dbobject_primary_keys))
        {
            $keys = array();
            foreach ($this->GetPrimaryKeys() as $pkey)
            {
                $keys[] = " `$pkey` = '". $this->GetPrimaryData($pkey) ."' ";
            }
        }

        $where .= implode(' AND ',$keys);

        return $where;
    }

    public function GetPrimaryData($pkey)
    {
        $_FLITE = Flite::Base();
        if(isset($this->dbobject_data[$pkey])) return $_FLITE->{$this->dbobject_connection}->Escape($this->GetValue($pkey));
        else if(isset($this->dbobject_primary_key_data[$pkey])) return $_FLITE->{$this->dbobject_connection}->Escape($this->dbobject_primary_key_data[$pkey]);
        else if(isset($this->dbobject_primary_keys[$pkey])) return $_FLITE->{$this->dbobject_connection}->Escape($this->dbobject_primary_keys[$pkey]);
        else return "";
    }

    public function GetChanges()
    {
        return $this->changed_fields;
    }

    public function ResetChanges()
    {
        $this->changed_fields = array();
    }

    public function __set($key,$value)
    {
        return $this->SetValue($key,$value);
    }

    public function SetValue($key,$value)
    {
        $oldval = isset($this->dbobject_data[$key]) ? $this->dbobject_data[$key] : null;
        if($value !== $oldval)
        {
            $this->dbobject_data[$key] = $value;
            if(!in_array($key,$this->dbobject_primary_keys)) $this->changed_fields[] = $key;
        }
        return;
    }

    public function __get($key)
    {
        return $this->GetValue($key);
    }

    public function GetValue($key)
    {
        return $this->dbobject_data[$key];
    }

    public function SaveChanges()
    {
        $_FLITE = Flite::Base();
        $set = "";
        $keys = $sets = $values = array();

        foreach ($this->GetPrimaryKeys() as $pkey)
        {
            $keys[] = $pkey;
            $values[] = $this->GetPrimaryData($pkey);
        }

        foreach ($this->GetChanges() as $change)
        {
            if(in_array($change,$this->GetAvailableColumns()))
            {
                $keys[] = $change;
                $values[] = $_FLITE->{$this->dbobject_connection}->Escape($this->GetValue($change));
                $sets[] = "`$change` = '". $_FLITE->{$this->dbobject_connection}->Escape($this->GetValue($change)) ."'";
            }
        }

        if(empty($sets)) return true;
        $set = implode(', ',$sets);

        if($this->dbobject_row_exists)
        {
            //UPDATE
            $sql = "UPDATE `$this->dbobject_table_name` SET $set " . $this->GetWhere();
        }
        else
        {
            //INSERT || UPDATE ON DUPLICATE
            $sql = "INSERT INTO `$this->dbobject_table_name` (`". implode('`,`',$keys) ."`) VALUES ('". implode("','",$values) ."') ON DUPLICATE KEY UPDATE $set";
        }

        $runquery = $_FLITE->{$this->dbobject_connection}->RunQuery($sql);

        if(!is_bool($runquery) && $runquery > 0 && FC::count($this->dbobject_primary_keys) == 1)
        {
            $this->SetValue($this->dbobject_primary_keys[0],$runquery);
        }

        if($runquery)
        {
            $this->dbobject_row_exists = true;
        }

        $this->ResetChanges();
    }

    public function Delete()
    {
        $_FLITE = Flite::Base();
        $_FLITE->{$this->dbobject_connection}->RunQuery("DELETE FROM `$this->dbobject_table_name` " . $this->GetWhere());
        $this->ResetChanges();
    }

    public function Load($columns=null)
    {
        $_FLITE = Flite::Base();
        $colsql = '*';
        if(!is_array($columns) && !is_null($columns)) $columns = explode(',',$columns);
        if(is_array($columns)) $colsql = '`' . implode('`,`',$columns) . '`';
        else if(!empty($columns)) $colsql = '`' . $columns . '`';


        $row = $_FLITE->{$this->dbobject_connection . ($this->dbobject_allow_slave ? $this->dbobject_slave_append : '')}->GetRow("SELECT $colsql FROM `$this->dbobject_table_name` " . $this->GetWhere());
        if($row)
        {
            $this->dbobject_row_exists = true;
            foreach ($row as $key => $val)
            {
                $this->SetValue($key,$val);
            }
        }

        $this->ResetChanges();
    }

    public function GetLoadedData()
    {
        return $this->dbobject_data;
    }
}
