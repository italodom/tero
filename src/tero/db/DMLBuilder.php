<?php
namespace tero\db;

class DMLBuilder{
	public $rawSql			= null;

	private $tables			= array();
	private $aliases		= array();
	private $params			= array();
	private $paramsCount	= array();

	CONST OPERATORS			= "/\s?(NOT)?\s?(=|==|<>|!=|>|>=|<|<=|LIKE)\s?$/";
	CONST FROM_WITH_ALIAS	= "/\s*(\w+)\s+(\w+)/";
	CONST JOIN_WITH_ALIAS	= "/\s*(\w+)\s+as\s+(\w+)/";
	CONST ALIASED_COLUMN	= "/\s*(\w+)\.(\w+)/";

	public function __construct(){
		$this->rawSql = "";
	}

	public function getParameters(){
		return $this->params;
	}

	private function formatConditions($conditions){
		// format the conditions
		if(count($conditions) === 1){
			return (is_array($conditions[0]) ? $conditions[0] : array($conditions[0]));
		} elseif(count($conditions) === 2){
			return array($conditions[0] => $conditions[1]);
		} else {
			throw new \InvalidArgumentException("Invalid conditions (where/on/having) specified!");
		}
	}

	private function parseOperation($operation){
		// format the operation name
		$operation = trim(strtoupper(preg_replace("/(?<=\\w)(?=[A-Z])/", " $1", $operation)));

		// update the query
		$this->rawSql .= "{$operation} ";
	}

	private function parseConditions($conditions, $glue = ",", $leftDelimiter = "( ", $rightDelimiter = " )"){
		// format the conditions
		$conditions = $this->formatConditions($conditions);

		foreach($conditions as $key => $value){
			if(is_int($key)){
				$conditions[$key] = $value;
			} elseif(preg_match(self::OPERATORS, $key)){
				$conditions[$key] = "{$key} " . $this->createParam($key, $value);
			} else {
				$conditions[$key] = "{$key} = " . $this->createParam($key, $value);
			}
		}

		// format the conditions
		$conditions = (count($conditions) > 1) ? $leftDelimiter . implode(" {$glue} ", $conditions) . $rightDelimiter : array_shift($conditions);

		// update the query
		$this->rawSql .= "{$conditions} ";
	}

	private function parseGeneric($data, $glue = ", ", $leftDelimiter = "", $rightDelimiter = ""){
		// format 
		if(is_array($data)){ $data = implode($glue, $data); }

		// trim and format the data
		$data = $leftDelimiter . trim($data) . $rightDelimiter;

		// add to the query if needed
		if(!empty($data)){ $this->rawSql .= "{$data} "; }
	}

	private function createParam($key, $value){
		// declare the needed variables
		$aliasData = array();

		// check if the value is a column
		if(preg_match(self::ALIASED_COLUMN, $value, $aliasData) && in_array($aliasData[1], $this->aliases)){
			return $value;
		} 

		// format the key
		$key = ":" . str_replace(' ', '', ucwords(strtolower(preg_replace('/[^a-zA-Z0-9]/', ' ', $key))));

		// check if the key exists already
		if(isset($this->params[$key])){
			// search for the next available key with this name
			$key = $key . "_" . $this->paramsCount[$key]++;
		} else {
			// start the param count
			$this->paramsCount[$key] = 1;
		}

		// store and return
		$this->params[$key] = $value;
		return $key;
	}

	private function extractTablesData(array $tables){
		foreach($tables as $table){
			// declare the needed variables
			$aliasData = array();
	
			// check if an alias has been specified
			if(preg_match(self::FROM_WITH_ALIAS, $table, $aliasData) > 0 || preg_match(self::JOIN_WITH_ALIAS, $table, $aliasData)){
				// store the alias
				array_push($this->tables, $aliasData[1]);
				array_push($this->aliases, $aliasData[2]);
			} else {
				array_push($this->tables, $table);
			}
		}
	}

	private function genericOperation($name, $data){
		$this->parseOperation($name);
		$this->parseGeneric($data);
		return $this;
	}

	private function conditionalOperation($name, $data){
		$this->parseOperation($name);
		$this->parseConditions($data);
		return $this;
	}

	public function select($fields){
		return $this->genericOperation(__FUNCTION__, $fields);
	}

	public function from($tables){
		// format the tables
		if(is_string($tables)){
			if(strstr($tables, ",")){
				$tables = explode(",", $tables);
			} else {
				$tables = array($tables);
			}
		}

		// extract the table data and parse the table
		$this->extractTablesData($tables);

		// generic operation from now on
		return $this->genericOperation(__FUNCTION__, $tables);
	}

	public function join($table, $on = array()){
		// extract the table data
		$this->extractTablesData(array($table));

		// generic operation from now on
		$this->genericOperation(__FUNCTION__, $table);

		// check the on condition
		if(!empty($on))
			$this->on($on);

		return $this;
	}

	public function leftJoin($tableName, $on = array()){
		// parse the operation and join
		$this->parseOperation("left");
		$this->join($tableName, $on);
		return $this;
	}

	public function rightJoin($tableName, $on = array()){
		// parse the operation and join
		$this->parseOperation("right");
		$this->join($tableName, $on);
		return $this;
	}

	public function innerJoin($tableName, $on = array()){
		// parse the operation and join
		$this->parseOperation("inner");
		$this->join($tableName, $on);
		return $this;
	}

	public function outerJoin($tableName, $on = array()){
		// parse the operation and join
		$this->parseOperation("outer");
		$this->join($tableName, $on);
		return $this;
	}

	public function on(){
		return $this->conditionalOperation(__FUNCTION__, func_get_args());
	}

	public function where(){
		return $this->conditionalOperation((stristr($this->rawSql, "where")) ? "and" : __FUNCTION__, func_get_args());
	}

	public function whereAnd(){
		return $this->conditionalOperation((!stristr($this->rawSql, "where")) ? "where" : "and", func_get_args());
	}

	public function whereOr(){
		return $this->conditionalOperation((!stristr($this->rawSql, "where")) ? "where" : "or", func_get_args());
	}

	public function having(){
		return $this->conditionalOperation(__FUNCTION__, func_get_args());
	}

	public function groupBy($fields){
		// format the fields
		if(is_string($fields)){
			if(strstr($fields, ",")){
				$fields = explode(",", $fields);
			} else {
				$fields = array($fields);
			}
		}

		// parse the operation
		return $this->genericOperation(__FUNCTION__, $fields);
	}

	public function orderBy($fields){
		// format the fields
		if(is_string($fields)){
			if(strstr($fields, ",")){
				$fields = explode(",", $fields);
			} else {
				$fields = array($fields);
			}
		}

		// parse the operation
		return $this->genericOperation(__FUNCTION__, $fields);

	}

	public function insert($table, array $fields){
		$this->genericOperation("insertInto", $table);
		$this->parseGeneric(array_keys($fields), "," , "(", ")");
		$this->parseOperation("values");

		// format the fields
		foreach($fields as $key => $item){
			$newKey = $this->createParam($key, $item);
			unset($fields[$key]); 
			$fields[$newKey] = $item;
		}

		// parse the data
		$this->parseGeneric(array_keys($fields), ",", "(", ")");
		return $this;
	}

	public function bulkInsert($table, array $rows){
		$this->genericOperation("insertInto", $table);
		$this->parseGeneric(array_keys($rows[0]), "," , "(", ")");
		$this->parseOperation("values");

		// iterate the rows
		foreach($rows as $index => $fields){
			// format the fields
			foreach($fields as $key => $item){
				$newKey = $this->createParam($key, $item);
				unset($fields[$key]); 
				$fields[$newKey] = $item;
			}

			// parse the data
			if($index !== 0){ $this->parseGeneric(","); }
			$this->parseGeneric(array_keys($fields), ",", "(", ")");
		}

		return $this;
	}

	public function update($table, array $fields, array $filters = array()){
		$this->genericOperation("update", $table);
		$this->parseOperation("set");
		$this->parseConditions(array($fields), ", ", "", "");
		$this->parseOperation("where");
		$this->parseConditions(array($filters), " AND ", "", "");


		return $this;
	}
}
