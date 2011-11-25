<?php

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