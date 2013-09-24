<?php
namespace tero\db\orm;

class Entity implements \ArrayAccess{
	private $name;
	private $query;
	private $options = array(
		"pk" => "id", 
		"fk" => "%s_id", 
		"required" => true, 
		"conditions" => array()
	);

	public function __construct($name, Query &$query, $options = array()){
		$this->name			= $name;
		$this->query		= $query;
		$this->options		= array_merge($this->options, $options);
	}

	public function __get($entityName){
		// add a new entity to the query
		return $this->query->entity($entityName);
	}

	public function __call($entityName, $options){
		return $this->query->entity($entityName, $options);
	}

	public function getName(){
		return $this->name;
	}

	public function getOptions(){
		return $this->options;
	}

	public function getOption($option){
		return (isset($this->options[$option])) ? $this->options[$option] : null;
	}

	public function setOption($option, $value){
		$this->options[$option] = $value;
		return $this;
	}

	public function where($wheres){
		$this->setOption("conditions", array_merge($this->getOption("conditions"), $wheres));
		return $this;
	}

	public function offsetExists($offset){
		throw new \Exception("Unsuportted");
	}
	public function offsetGet($offset){
		$this->where(array($this->options["pk"] => $offset));
		return $this;
	}
	public function offsetSet ($offset, $value){
		throw new \Exception("Unsupported");
	}
	public function offsetUnset($offset){
		throw new \Exception("Unsupported");
	}

	public function fetchAll(){
		return $this->query->fetchAll();
	}
}
