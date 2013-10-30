<?php

class DBConnection
{
	private $dbname; //Database Name
	public $dbhost = 'localhost'; //Database Server
	private $dbuser; //Database Username
	private $dbpass; //Database Password
	public $conn; //Connection Resource
	private $connected = false; //Was connection established
	private $attempt_connect; //Attempted Connections

	private $serverpool = array(); //Array of Logins

	private $queries = 0; //How many queries executed on this connection
	public  $store_log = false; //Set to true to store all queries
	private $query_log = array(); //Array for queries

	private $numrows; //Number of rows in last query
	private $affectedRows; //Number of rows affected by last query
	private $errors = array(); //Array of errors

	private $connect_attempts = 5; //Times to try to connect
	private $connect_wait = 50; //Milliseconds to wait between connect attempts
    private $deadlock_retries = 2; //Times to retry a deadlocked query

	public function DBConnection($dbhost='',$dbuser='',$dbpass='',$dbname='')
	{
	    if(!is_array($dbhost)) $dbhost = array($dbhost);

		foreach ($dbhost as $i => $hostname)
		{
		    if($i == 0) $response = $this->SetupConnection($hostname,$dbuser,$dbpass,$dbname);
		    else $this->AddServer($hostname);
		}

		return $response;
	}

	public function SetupConnection($dbhost,$dbuser,$dbpass,$dbname='')
	{
		$this->dbname = $dbname;
		$this->dbhost = $dbhost;
		$this->dbuser = $dbuser;
		$this->dbpass = $dbpass;
		$this->AddServer($dbhost);
		return true;
	}

	public function AddServer($dbhost)
	{
		$this->serverpool[] = $dbhost;
		return true;
	}

	public function GetDBName()
	{
		return $this->dbname;
	}

	//Open Database Connection
	public function Connect()
	{
	    if(!$this->connected)
		{
		    $usehost = '';
    	    if(is_array($this->serverpool))
    	    {
    	        shuffle($this->serverpool);
    	        $usehost = array_pop($this->serverpool);
    	    }

    	    if(empty($usehost)) $usehost = $this->dbhost;

			$this->conn = @mysql_connect($usehost,$this->dbuser,$this->dbpass,true);
			if($this->conn)
			{
			    $this->attempt_connect = 0;
				$this->connected = true;
				$this->ChangeDB($this->dbname);
			}
			else
			{
				if($this->attempt_connect < $this->connect_attempts)
				{
					$this->attempt_connect++;
					$this->SendError('Connection Attempt ' . $this->attempt_connect . ' failed to ' . $usehost);
					usleep($this->connect_wait);
				}
				else
				{
					return false;
				}
			}
		}
		else
		{
			return true;
		}
	}

	private function SendError($error,$query='')
	{
		if($this->conn)
		{
			$this->errors[] = array(
				'time' => time(),
				'message' => $error,
				'query' => $query,
				'mysql_error' => mysql_error($this->conn),
				'mysql_errno' => mysql_errno($this->conn)
			);

			if(mysql_errno($this->conn) == 2006)
			{
			    $this->attempt_connect = 0;
			    $this->connected = false;
			    $this->Connect();
			}

			if(!in_array(mysql_errno($this->conn),array(1062)))
			{
			    error_log("Failed to connect: " . print_r($_SERVER['PHP_SELF'],true) . print_r($this->dbhost,true) . print_r($this->errors[count($this->errors) - 1],true));
			}
		}
		else
		{
			$this->errors[] = array(
				'time' => time(),
				'message' => $error,
				'query' => $query,
				'extended' => 'No MySQL Connection',
				'mysql_error' => mysql_error(),
				'mysql_errno' => mysql_errno()
			);

			if(mysql_errno() == 2006)
			{
			    $this->attempt_connect = 0;
			    $this->connected = false;
			    $this->Connect();
			}

			if(!in_array(mysql_errno(),array(1062)))
			{
			     error_log("Failed to connect: " . print_r($_SERVER['PHP_SELF'],true) . print_r($this->dbhost,true) . print_r($this->errors[count($this->errors) - 1],true));
			}
		}

		return true;
	}

	private function WriteQueryLog($query,$callee,$time=0)
	{
		$this->queries++;

		if($this->store_log)
		{
			$this->query_log[] = array(
				'query' => $query,
				'callee' => $callee,
				'time' => time(),
				'exec' => round($time * 1000,2)
			);
		}

		return $this->store_log;
	}

	public function GetErrors()
	{
		return $this->errors;
	}

	public function GetQueries()
	{
		return $this->query_log;
	}

	public function QueryCount()
	{
		return $this->queries;
	}

	public function IsConnected()
	{
		$connected = $this->connected;
		if(!$this->connected && $this->attempt_connect < 2)
		{
		    $this->Connect();
		    $this->attempt_connect++;
		    $this->IsConnected();
		}
		return $this->connected;
	}

	//Change Selected Database
	public function ChangeDB($dbname='')
	{
	    $start = microtime(true);
		$this->Connect();
		if($this->IsConnected())
		{
			if(!empty($dbname)) $this->dbname = $dbname;
			$db_changed = mysql_select_db($this->dbname,$this->conn);

			if(mysql_errno($this->conn) == 2006 || mysql_errno() == 2006)
			{
			    $this->attempt_connect = 0;
			    $this->connected = false;
			    $this->Connect();
			    $db_changed = mysql_select_db($this->dbname,$this->conn);
			}

			if(function_exists("mysql_set_charset"))
			{
				mysql_set_charset('utf8',$this->conn);
			}

			if(!$db_changed)
			{
				$this->SendError('Change Database to '. $this->dbname .' Failed');
				return false;
			}
			else
			{
				$this->WriteQueryLog('use ' . $this->dbname . ';','ChangeDB');
				return true;
			}
		}
	}

	//Return Changed ID If INSERT else Boolean
	public function RunQuery($query = "")
	{
	    $start = microtime(true);
		$this->Connect();
		if($this->IsConnected())
		{
		    $query_pass = mysql_query($query,$this->conn);

		    if(mysql_errno($this->conn) == 2006 || mysql_errno() == 2006)
			{
			    $this->attempt_connect = 0;
			    $this->connected = false;
			    $this->Connect();
			    $query_pass = mysql_query($query,$this->conn);
			}

            $query_pass = $this->DeadlockCheck($query_pass, $query);

			if ( $query_pass )
			{
				$this->WriteQueryLog($query,'RunQuery',microtime(true) - $start);

				if ( strpos($query, "INSERT") === 0)
				{
					return mysql_insert_id($this->conn);
				}
				else
				{
					$this->affectedRows = mysql_affected_rows($this->conn);
					return true;
				}
			}
			else
			{
				$this->SendError('Query Failed',$query);
			}
		}
		return false;
	}

	public function GetAffectedRows()
	{
		return $this->affectedRows;
	}

	//Array of results
	public function GetRows($query = "",$cache_time=false,$cache_key=null)
	{
	    if($cache_time)
	    {
            $cache = $this->GetCache($query,'GetRows',$cache_key);
            if($cache) return $cache;
	    }

	    $start = microtime(true);
		$this->Connect();
		if($this->IsConnected())
		{
			$result = @mysql_query($query,$this->conn);

			if(mysql_errno($this->conn) == 2006 || mysql_errno() == 2006)
			{
			    $this->attempt_connect = 0;
			    $this->connected = false;
			    $this->Connect();
			    $result = @mysql_query($query,$this->conn);
			}

            $result = $this->DeadlockCheck($result, $query);

			if(@mysql_error($this->conn))
			{
				$this->SendError('Query Failed',$query);
			}
			else
			{
				$this->WriteQueryLog($query,'GetRows',microtime(true) - $start);
			}

			if ($result)
			{
				$data = array();
				while($obj = mysql_fetch_object($result))
				{
					$data[] = $obj;
					if(memory_get_usage() > 980217728)
					{
					    error_log('High Memory Usage: ' . $query);
					    return $data;
					}
				}
                mysql_free_result($result);
				if($cache_time) $this->SetCache($query,$data,$cache_time,'GetRows',$cache_key);
				return $data;
			}
		}
		return false;
	}

	//Array of results
	public function GetKeyedRows($query = "",$cache_time=false,$cache_key=null)
	{
	    if($cache_time)
	    {
            $cache = $this->GetCache($query,'GetKeyedRows',$cache_key);
            if($cache) return $cache;
	    }

	    $start = microtime(true);
		$this->Connect();
		if($this->IsConnected())
		{
			$result = @mysql_query($query,$this->conn);

			if(mysql_errno($this->conn) == 2006 || mysql_errno() == 2006)
			{
			    $this->attempt_connect = 0;
			    $this->connected = false;
			    $this->Connect();
			    $result = @mysql_query($query,$this->conn);
			}

            $result = $this->DeadlockCheck($result, $query);

			if(@mysql_error($this->conn))
			{
				$this->SendError('Query Failed',$query);
			}
			else
			{
				$this->WriteQueryLog($query,'GetKeyedRows',microtime(true) - $start);
			}

			if ($result)
			{
			    $keyfield = $value_key = null;
			    $value_as_array = true;
				$data = array();
				while($obj = mysql_fetch_assoc($result))
				{
				    if($keyfield == null)
				    {
				        $keyfield = array_keys($obj);
				        if(count($keyfield) == 2)
				        {
				            $value_as_array = false;
				            $value_key = $keyfield[1];
				        }
				        $keyfield = $keyfield[0];
				    }
					$data[$obj[$keyfield]] = !$value_as_array && !empty($value_key) ? $obj[$value_key] : $obj;
					if(memory_get_usage() > 980217728)
					{
					    error_log($query);
					    return $data;
					}
				}
                mysql_free_result($result);
				if($cache_time) $this->SetCache($query,$data,$cache_time,'GetKeyedRows',$cache_key);
				return $data;
			}
		}
		return false;
	}

	//Array of cols
	public function GetCols($query = "",$cache_time=false,$cache_key=null)
	{
	    if($cache_time)
	    {
            $cache = $this->GetCache($query,'GetCols',$cache_key);
            if($cache) return $cache;
	    }

	    $start = microtime(true);
		$this->Connect();
		if($this->IsConnected())
		{
			$result = @mysql_query($query,$this->conn);

			if(mysql_errno($this->conn) == 2006 || mysql_errno() == 2006)
			{
			    $this->attempt_connect = 0;
			    $this->connected = false;
			    $this->Connect();
			    $result = @mysql_query($query,$this->conn);
			}

            $result = $this->DeadlockCheck($result, $query);

			if(@mysql_error($this->conn))
			{
				$this->SendError('Query Failed',$query);
			}
			else
			{
				$this->WriteQueryLog($query,'GetCols',microtime(true) - $start);
			}

			if ($result)
			{
				$data = array();
				while ($value = mysql_fetch_array($result))
				{
					$data[] = $value[0];
				}
                mysql_free_result($result);
				if($cache_time) $this->SetCache($query,$data,$cache_time,'GetCols',$cache_key);
				return $data;
			}
		}
		return false;
	}

  /**
   * @param string   $query
   * @param bool     $cache_time
   * @param null|int $cache_key
   * @return string|int|float|bool
   */
  public function GetField($query = "",$cache_time=false,$cache_key=null)
	{
	    if($cache_time)
	    {
            $cache = $this->GetCache($query,'GetField',$cache_key);
            if($cache) return $cache;
	    }

	    $start = microtime(true);
		$this->Connect();
		if($this->IsConnected())
		{
			$result = @mysql_query($query,$this->conn);

			if(mysql_errno($this->conn) == 2006 || mysql_errno() == 2006)
			{
			    $this->attempt_connect = 0;
			    $this->connected = false;
			    $this->Connect();
			    $result = @mysql_query($query,$this->conn);
			}

            $result = $this->DeadlockCheck($result, $query);

			if(@mysql_error($this->conn))
			{
				$this->SendError('Query Failed',$query);
			}
			else
			{
				$this->WriteQueryLog($query,'GetField',microtime(true) - $start);
			}

			if ($result)
			{
				$row = mysql_fetch_array($result);
                mysql_free_result($result);
				if($row && $cache_time) $this->SetCache($query,$row[0],$cache_time,'GetField',$cache_key);
				return $row ? $row[0] : false;
			}
		}
		return false;
	}

	//Gets Number of rows in query
	public function NumRows($query = "",$cache_time=false,$cache_key=null)
	{
	    if($cache_time)
	    {
            $cache = $this->GetCache($query,'NumRows',$cache_key);
            if($cache) return $cache;
	    }

	    $start = microtime(true);
		$this->Connect();
		if($this->IsConnected())
		{
			$result = @mysql_query($query,$this->conn);

			if(mysql_errno($this->conn) == 2006 || mysql_errno() == 2006)
			{
			    $this->attempt_connect = 0;
			    $this->connected = false;
			    $this->Connect();
			    $result = @mysql_query($query,$this->conn);
			}

            $result = $this->DeadlockCheck($result, $query);

			if(@mysql_error($this->conn))
			{
				$this->SendError('Query Failed',$query);
			}
			else
			{
				$this->WriteQueryLog($query,'NumRows',microtime(true) - $start);
			}

			if ($result)
			{
				$data = mysql_num_rows($result);
                mysql_free_result($result);
				if($cache_time) $this->SetCache($query,$data,$cache_time,'NumRows',$cache_key);
				return $data;
			}
		}
		return 0;
	}

	//Get single row from database
	public function GetRow($query = "",$cache_time=false,$cache_key=null)
	{
	    if($cache_time)
	    {
            $cache = $this->GetCache($query,'GetRow',$cache_key);
            if($cache) return $cache;
	    }

	    $start = microtime(true);
		$this->Connect();
		if($this->IsConnected())
		{
			$result = @mysql_query($query,$this->conn);

			if(mysql_errno($this->conn) == 2006 || mysql_errno() == 2006)
			{
			    $this->attempt_connect = 0;
			    $this->connected = false;
			    $this->Connect();
			    $result = @mysql_query($query,$this->conn);
			}

            $result = $this->DeadlockCheck($result, $query);

			if(@mysql_error($this->conn))
			{
				$this->SendError('Query Failed',$query);
			}
			else
			{
				$this->WriteQueryLog($query,'GetRow',microtime(true) - $start);
			}

			if ($result)
			{
			    $data = mysql_fetch_object($result);
                mysql_free_result($result);
			    if($cache_time) $this->SetCache($query,$data,$cache_time,'GetRow',$cache_key);
			    return $data;
			}
		}
		return false;
	}

	//Format string input as not to destroy a SQL Query
	public function FormatInput($value = "",$maxlen = 0)
	{
        $this->Connect();
        if($this->IsConnected()) $out = mysql_real_escape_string($value,$this->conn);
        else $out = mysql_real_escape_string($value);
		if($maxlen != 0)
		{
			$out = substr($out,0,$maxlen);
		}
		return $out;
	}

	//MySQL Escape String
	public function Escape($string)
	{
	    $this->Connect();
		if($this->IsConnected()) return mysql_real_escape_string($string,$this->conn);
		else return mysql_real_escape_string($string);
	}

	public function CacheKey($sql,$source='')
	{
	    return "SQLCACHE:$source:" . md5($sql);
	}

	private function GetCache($sql,$source='',$cache_key=null)
	{
	    if(isset($_GET['rebuild-db-cache'])) return false;
	    $_FLITE = Flite::Base();
        $cache_key = !is_null($cache_key) ? $cache_key : $this->CacheKey($sql,$source);
	    $data = $_FLITE->memcache->get($cache_key);
	    if(empty($data) || !$data) return false;
	    else return $data;
	}

	private function SetCache($sql,$value,$timeout=600,$source='',$cache_key)
	{
	    $_FLITE = Flite::Base();
	    if(!$value) return false;
        $cache_key = !is_null($cache_key) ? $cache_key : $this->CacheKey($sql,$source);
	    $_FLITE->memcache->set($cache_key, $value, MEMCACHE_COMPRESSED,$timeout);
	}

    private function DeadlockCheck($result, $query)
    {
        $tries = 0;
        while(mysql_errno($this->conn) == 1213 && $tries++ < $this->deadlock_retries)
        {
            $result = @mysql_query($query,$this->conn);
        }
        return $result;
    }

	//Close Database Connection
	public function Disconnect()
	{
	    $this->connected = false;
		@mysql_close($this->conn);
	}

	//Object Destruction
	//Ensure DB Closes
	function __destruct()
	{
	    $this->connected = false;
		@mysql_close($this->conn);
	}
}
