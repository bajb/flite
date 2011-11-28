<?php


/* cassandra.php Start */


class CassandraObject
{
    private $columnFamily = '';
    private $CFConnection;

    public function __construct($cf,$autopack_names=true,$autopack_values=true,$read_consistency_level=cassandra_ConsistencyLevel::QUORUM,
    $write_consistency_level=cassandra_ConsistencyLevel::QUORUM,$buffer_size=self::DEFAULT_BUFFER_SIZE)
    {
        global $_FLITE;
        $this->columnFamily = $cf;
        try { $this->CFConnection = new ColumnFamily($_FLITE->cassandra, $this->columnFamily,$autopack_names,$autopack_values,$read_consistency_level,$write_consistency_level,$buffer_size); }
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
}

/* cassandra.php End */



/* common.php Start */


class FC
{
    public function count($arr)
    {
        return (is_array($arr) && !empty($arr)) ? count($arr) : 0;
    }

    public function array_merge($array1,$array2)
    {
        if(!is_array($array1)) $array1 = array();
        if(!is_array($array2)) $array2 = array();
        return array_merge($array1,$array2);
    }

    public function array_slice($array,$offset,$length=null,$preserve_keys=false)
    {
        return (is_array($array) && !empty($array)) ? array_slice($array,$offset,$length,$preserve_keys) : array();
    }

    public function enable_error_reporting()
    {
        error_reporting(E_ALL);
        ini_set('display_errors',true);
    }

    public function disable_error_reporting()
    {
        error_reporting(E_COMPILE_ERROR);
        ini_set('display_errors',false);
    }

    public function inet_aton($ip)
    {
        return sprintf("%u",ip2long($ip));
    }

    public function inet_ntoa($long)
    {
        return long2ip($long);
    }

    public function array_to_object($array,$object=false)
    {
        if(!$object) $object = new stdClass();
        if(!is_array($array)) return $array;
        foreach ($array as $k => $v) $object->{$k} = FC::array_to_object($v);
        return $object;
    }


    public function object_to_array($object, $array=false)
    {
        if (!$array) $array = array();
        if (!is_object($object)) return $object;
        foreach ($object as $k => $v) $array[$k] = FC::object_to_array($v);
        return $array;
    }

    public function array_in_array($arr1, $arr2)
    {
        foreach ($arr1 as $a1)
        {
            if ( in_array($a1, $arr2) ) return true;
        }
        return false;
    }

    public function validate_email($email)
    {
        return preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $email);
    }

    public function remove_empty_elements($array)
    {
        $narr = array();
        while(list($key, $val) = each($array))
        {
            if (is_array($val))
            {
                $val = remove_empty_elements($val);
                if (count($val)!=0) $narr[$key] = $val;
            }
            else if ($val != "" || is_int($val)) $narr[$key] = $val;
        }
        unset($array);
        return $narr;
    }

    public function SQLLongDate()
    {
        return 'Y-m-d H:i:s';
    }

    public function hex2bin($data)
    {
        $len = strlen($data);
        return pack("H".$len, $data);
    }

    public function txt2bin($str)
    {
        $text_array = explode("\r\n", chunk_split($str, 1));
        for ($n = 0; $n < count($text_array) - 1; $n++) $newstring .= substr("0000".base_convert(ord($text_array[$n]), 10, 2), -8);
        return $newstring;
    }

    public function bin2txt($str)
    {
        $text_array = explode("\r\n", chunk_split($str, 8));
        for ($n = 0; $n < count($text_array) - 1; $n++) $newstring = $newstring . stripslashes(chr(base_convert($text_array[$n], 2, 10)));
        return $newstring;
    }

    public function curl_response($url,$timeout=15,$debug=false)
    {
        global $_FLITE;
        $debug = $debug || $_FLITE->GetConfig('debug_curl');
        $api_call = curl_init();
        curl_setopt($api_call, CURLOPT_URL, $url);
        curl_setopt($api_call, CURLOPT_HEADER, 0);
        curl_setopt($api_call, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($api_call, CURLOPT_RETURNTRANSFER, true);
        if ($debug) echo 'Calling URL: ' . $url;
        $response = curl_exec($api_call);
        $errno = curl_errno($api_call);
        if ($debug && (!is_string($response) || !strlen($response))) echo( "Failure Contacting Server" );
        if($errno > 0 && $debug) echo 'Error Number:' . $errno;
        curl_close($api_call);
        return $response;
    }

    public function file_size($size,$kb=true)
    {
        $size = floatval($size);
        if($kb) $size = $size * 1024;
        $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
        return $size ? round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 Bytes';
    }

}

/* common.php End */



/* database.sqlbuilder.php Start */


class SQLBuilder
{
	private $cols; //Data columns, used in INSERT and UPDATE queries
	private $wcols; //Where Columns, used in SELECT queries
	private $queryType; //Query Type (SELECT,INSERT,UPDATE,DELETE)
	
	private $where;
	private $groupBy;
	private $orderBy;
	private $having;
	private $limit;
	
	private $table;
	
	public function SQLBuilder($qType = 'SELECT')
	{
	  	$this->cols = array();
		$this->wcols = array();
		$this->queryType = $qType;
	}
	
	public function SetTable($table = '')
	{
	  	$this->table = $table;
	}
	
	public function SetQueryType($qType = 'SELECT')
	{
		$this->queryType = strtoupper($qType);
	}
	
	public function SetWhere($where)
	{
		$this->where = $where;
	}
	
	public function SetGroupBy($groupby = '')
	{
		$this->groupBy = $groupby;
	}
	
	public function SetOrderBy($orderby = '')
	{
		$this->orderBy = $orderby;
	}
	
	public function SetHaving($having = '')
	{
		$this->having = $having;
	}
	
	public function SetLimit($limit)
	{
		$this->limit = $limit;
	}
	
	public function AddStringColumn($colname='',$value='',$limit=0)
	{
		if($limit > 0 && strlen($value) > $limit)
		{
			$value =  substr($value,0,$limit);
		}
		$newColumn = array('name' => $colname,'value' => mysql_real_escape_string($value),'type' => 'str');
		array_push($this->cols,$newColumn);
	}
	
	public function AddIntColumn($colname='',$value=0)
	{
		$newColumn = array('name' => $colname,'value' => $value,'type' => 'int');
		array_push($this->cols,$newColumn);
	}
	
	public function AddFunctionColumn($colname='',$value='')
	{
		$newColumn = array('name' => $colname,'value' => $value,'type' => 'function');
		array_push($this->cols,$newColumn);
	}
	
	public function AddDateColumn($colname='',$value='',$format = 'Y-m-d')
	{
		$newColumn = array('name' => $colname,'value' => $value,'type' => 'date','format' => $format);
		array_push($this->cols,$newColumn);
	}
	
	public function AddWhereColumn($colname = '')
	{
		array_push($this->wcols,$colname);
	}
	
	public function BuildVal($col)
	{
	  	$out = '';
		if($col['type'] == 'int')
		{
		  	$out = ($col['value'] == "" ? 0 : floatval($col['value']));
		}
		else if($col['type'] == 'function')
		{
			$out = $col['value'];
		}
		else if($col['type'] == 'date')
		{
			$out = "'". date($col['format'],strtotime($col['value'])) ."'";
		}
		else
		{
			$out = "'". $col['value'] ."'";
		}
		return $out;
	}
	
	public function GetColumnNames()
	{
	  	$names = array();
	  	foreach($this->cols as $col)
	  	{
		 	array_push($names,'`' . $col['name'] . '`');
		}
		return $names;
	}
	
	public function GetColumnValues($formatted = true)
	{
	  	$values = array();
	  	foreach($this->cols as $col)
	  	{
		 	array_push($values,$this->BuildVal($col));
		}
		return $values;
	}
	
	public function GetColumns()
	{
		return $this->cols;
	}
	
	public function BuildQuery()
	{
	  	$out = '';
		switch($this->queryType)
		{
			case "SELECT":
				$out .= "SELECT ";
				$out .= implode(', ',$this->wcols);
				$out .= " FROM `{$this->table}`";
				
				if ($this->where)   $out.= " WHERE $this->where"; 
                if ($this->groupBy) $out.= " GROUP BY $this->groupBy"; 
                if ($this->having)  $out.= " HAVING $this->having"; 
                if ($this->orderBy) $out.= " ORDER BY $this->orderBy"; 
                if ($this->limit)   $out.= " LIMIT $this->limit";

				break;
			case "UPDATE":

                $out.= "UPDATE `{$this->table}` SET "; 
                 
                $noColumns = count($this->cols); 
                for ($i=0;$i<$noColumns;$i++) { 
                    $out.= "`" . $this->cols[$i]['name'] . "` = ". $this->BuildVal($this->cols[$i]); 
                    if ($i < $noColumns-1) $out.= ", "; 
                } 
                
                if ($this->where) $out.= " WHERE $this->where"; 
                if ($this->limit) $out.= " LIMIT $this->limit"; 
                
				break;
			case "INSERT":
			case "REPLACE":
				
                $out.= $this->queryType . " INTO `{$this->table}` "; 
                 
                $out.= "(";
                $out.= implode(", ", $this->GetColumnNames()); 
                $out.= ") "; 
                 
                $out.= "VALUES"; 
                 
                $out.= " ("; 
                $out.= implode(", ", $this->GetColumnValues()); 
                $out.= ")"; 

				break;
			case "DELETE":
				
				$out.= "DELETE FROM `{$this->table}` ";
                if ($this->where) $out.= "WHERE $this->where";
                
				break;
		}
		$out .= ';';
		return $out;
	}
}

/* database.sqlbuilder.php End */



/* html.php Start */

/**
 * A helper class for generating standard HTML elements and links quicker
 */

class Html {

    /**
     * Document type definitions
     *
     * @var array
     * @access private
     *
     */
    private $docTypes = array(
            'html4-strict'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
            'html4-trans'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
            'html4-frame'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
            'xhtml-strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
            'xhtml-trans' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
            'xhtml-frame' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
            'xhtml11' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
            'html5' => '<!DOCTYPE HTML>'
    );

    /**
     * Store the doctype for the whole helper
     *
     * @var string The shorthand doc type
     */
    private $doctype = 'xhtml-strict';

    /**
     * Store the preferred closing tag for this DTD
     *
     * @var string The closing tag type
     */
    private $closetag = '/>';

    /**
     * Append to css style tag
     * Allows for css to be fetched following updates
     *
     * @var string
     */
    private $cssVersion = '|v=1320855700';

    /**
     * Append to js script tag
     * Allows for js to be fetched following updates
     *
     * @var unknown_type
     */
    private $jsVersion = '|v=1320855700';

    /**
     * Set the default doc type for the html helper
     *
     * @param string $dtd A valid shorthand DTD
     */
    function  __construct($dtd) {
        if(isset($this->docTypes[$dtd])){
            $this->doctype = $dtd;
        }else{
            $this->doctype = 'xhtml-strict';
        }

        $this->setCloseTag();
    }

    /**
     * Read the DTD set in the class and set the correct closing tag
     */
    private function setCloseTag(){
        if(substr($this->doctype, 0, 5) == 'xhtml'){
            $this->closetag = " />";
        }else{
            $this->closetag = ">";
        }
    }

    /**
     * Returns a doctype string.
     *
     * Possible doctypes:
     *
     *  - html4-strict:  HTML4 Strict.
     *  - html4-trans:  HTML4 Transitional.
     *  - html4-frame:  HTML4 Frameset.
     *  - xhtml-strict: XHTML1 Strict.
     *  - xhtml-trans: XHTML1 Transitional.
     *  - xhtml-frame: XHTML1 Frameset.
     *  - xhtml11: XHTML1.1.
     *
     * @param string $type Doctype to use.
     * @return string Doctype string
     *
     */
    public function docType($dtd = '') {
        if(empty($dtd)){
            $dtd = $this->doctype;
        }
        if (isset($this->docTypes[$dtd])) {
                return $this->docTypes[$dtd];
        }
        return null;
    }

    /**
     * Create a <meta> tag to set the document encoding type - http://www.w3.org/TR/html4/charset.html#encodings
     * NB, If you change the charset from UTF-8 you should include an XML tag - http://www.w3.org/TR/xhtml1/#normative
     *
     * @param string $charset The character encoding you want to use
     * @return string The HTML snippet meta tag
     */
    public function charset($charset = 'utf-8'){
        $meta = "<meta http-equiv='content-type' content='text/html; charset=$charset'".$this->closetag;
        return $meta;
    }

    /**
     * Function to return a set of meta tags for various things, focused on returning tags based on name/content pairs
     *
     * @param array $tags An array of name and content pairs
     * @return string A formatted HTML snippet of <meta> tags
     */
    public function meta($tags){
        if(!is_array($tags)){
            return false;
        }

        $meta = '';
        foreach($tags as $name => $content){
            $meta .= "<meta name='$name' content='$content'".$this->closetag." \n";
        }

        return $meta;
    }

    /**
     * Create a DTD dependant doctype link
     *
     * @global array $_FCONF The global config settings array
     * @param string $path The css path from your site root, eg. css/style.css  !Remember no preceeding slash!
     * @param bool $ie6 True/False to wrap the css in IE6 specific tags
     * @return string The HTML css link snippet
     */
    public function css($path, $ie6 = false){
        global $_FCONF;

        $link = '';

        if($ie6){
            $link .= "<!--[if IE 6]>";
        }

        $link .= "<link type='text/css' rel='stylesheet' href='{$_FCONF['static_domain']}/css/{$path}{$this->cssVersion}'".$this->closetag;

        if($ie6){
            $link .= "<![endif]-->";
        }

        return $link;
    }

    /**
     * Create a valid link to a javascript file
     *
     * @global array $_FCONF The global config settings array
     * @param string $path The js path from your site root, eg. js/common.js  !Remember no preceeding slash!
     * @return string The JS link snippet
     */
    public function js($path){
        global $_FCONF;
        $link = "<script type='text/javascript' src='{$_FCONF['static_domain']}/js/{$path}{$this->jsVersion}'></script>";
        return $link;
    }

    /**
     * Return a link to a popular GoogleCode hosted framework
     *
     * @param string $fw The name of the framework
     * @return string The JS link snippet
     *
     * TODO: Convert this to have an array parameter of frameworks such as array('jquery','jqueryui')
     */
    public function framework($fw){
    	global $_FCONF;
    	$proto = isset($_FCONF['PROTOCOL']) ? $_FCONF['PROTOCOL'] : 'http://';
        switch ($fw){
            case 'chrome':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/chrome-frame/1.0.2/CFInstall.min.js'></script>";
                break;
            case 'dojo':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/dojo/1.5/dojo/dojo.xd.js'></script>";
                break;
            case 'extcore':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/ext-core/3.1.0/ext-core.js'></script>";
                break;
            case 'jquery':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js'></script>";
                break;
            case 'jqueryui':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/jquery-ui.min.js'></script>";
                break;
            case 'mootools':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/mootools/1.2.4/mootools-yui-compressed.js'></script>";
                break;
            case 'prototype':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/prototype/1.6.1.0/prototype.js'></script>";
                break;
            case 'scriptaculous':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/scriptaculous/1.8.3/scriptaculous.js'></script>";
                break;
            case 'swfobject':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js'></script>";
                break;
            case 'yui':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/yui/2.8.1/build/yuiloader/yuiloader-min.js'></script>";
                break;
            case 'webfont':
                $link = "<script type='text/javascript' src='{$proto}ajax.googleapis.com/ajax/libs/webfont/1.0.4/webfont.js'></script>";
                break;
        }

        return $link;
    }

    /**
     * Function for generating float clear div's
     *
     * @param string $type A type of clear to create, both, left or right
     * @return string The HTML snippet clear div
     */
    public function clear($type = 'both'){
        switch ($type){
            case 'both':
            default:
                $clr = "<div class='clear-div' style='clear:both'><!--blank--></div>";
                break;
            case 'left':
                $clr = "<div style='clear:left'><!--blank--></div>";
                break;
            case 'right':
                $clr = "<div style='clear:right'><!--blank--></div>";
                break;
        }

        return $clr;
    }

    /**
     * Function for creating slugs from strings
     * Stolen from: http://stackoverflow.com/questions/2580581/best-way-to-escape-and-create-a-slug
     *
     * @param string $string The string to sluggify
     * @param string $space The character to replace spaces with
     * @return string The sluggified string
     */
     public function toSlug($string,$space="-") {
        if (function_exists('iconv')) {
            $string = @iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        }
        $string = preg_replace("/[^a-zA-Z0-9 -]/", "", $string);
        $string = strtolower($string);
        $string = str_replace(" ", $space, $string);

        return $string;
     }

     /**
      * Function to encode an email all into entities to better prevent robots and spiders picking it up
      *
      * @param string $email The email address to encode
      * @return string An encoded email address
      */
     public function encodeEmail($email){
         return htmlentities($email, ENT_COMPAT, 'UTF-8', false);
     }

     /**
      * Function to generate a compatible XML style root tag
      *
      * @return <type>
      */
     public function roottag(){
         if(substr($this->doctype, 0, 5) == 'xhtml'){
             return "<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>";
         }else{
             return "<html>";
         }
     }

}

/* html.php End */



/* database.mysql.php Start */


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
	public function GetRows($query = "")
	{
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
					    error_log($query);
					    return $data;
					}
				}
				return $data;
			}
		}
		return false;
	}

	//Array of cols
	public function GetCols($query = "")
	{
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
				return $data;
			}
		}
		return false;
	}

	public function GetField($query = "",$offset=0)
	{
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
				if($row)
				{
					return $row[$offset];
				}
			}
		}
		return false;
	}

	//Gets Number of rows in query
	public function NumRows($query = "")
	{
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
				return mysql_num_rows($result);
			}
		}
		return 0;
	}

	//Get single row from database
	public function GetRow($query = "")
	{
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

			if(@mysql_error($this->conn))
			{
				$this->SendError('Query Failed',$query);
			}
			else
			{
				$this->WriteQueryLog($query,'GetRow',microtime(true) - $start);
			}

			if ($result) return mysql_fetch_object($result);
		}
		return false;
	}

	//Format string input as not to destroy a SQL Query
	public function FormatInput($value = "",$maxlen = 0)
	{
		$out = mysql_escape_string($value);
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
		else return mysql_escape_string($string);
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

/* database.mysql.php End */



/* database.php Start */


class DatabaseObject
{
    private $dbobject_primary_keys = array();
    private $dbobject_table_name = '';
    private $dbobject_columns = array();
    private $dbobject_connection = DBConnection;
    private $changed_fields = array();

    public function __construct()
    {
        global $_FLITE;
        $this->dbobject_connection = $_FLITE->db;
    }

    public function AddColumn($name,$size,$type,$default_value='')
    {
    }

    public function GetChanges()
    {
        return $this->changed_fields;
    }

    public function __set($key,$value)
    {
        $this->$key = $value;
        $this->changed_fields[] = $key;
        return;
    }

    public function __get($key)
    {
        return $this->$key;
    }

    public function SaveChanges()
    {
        $this->changed_fields = array();
    }

    public function Delete()
    {
        $this->changed_fields = array();
    }

    public function Load($columns=null)
    {
        if($columns == null) $columns = array_keys($this->dbobject_columns);
        if(!is_array($columns)) $columns = explode(',',$columns);

        $colsql = '*';
        if(is_array($columns)) $colsql = '`' . implode('`,`',$columns) . '`';
        else if(!empty($columns)) $colsql = '`' . $columns . '`';

        $row = $this->dbobject_connection->GetRow("SELECT $colsql FROM `$this->dbobject_table_name` WHERE $selectsql");
        if($row)
        {
            foreach ($row as $key => $val)
            {
                $this->$key = $val;
            }
        }

        $this->changed_fields = array();
    }
}

/* database.php End */



/* membase.php Start */


class Membase
{
    private $connection;
    
    public function __construct()
    {
        $connected = false;
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
}

/* membase.php End */

