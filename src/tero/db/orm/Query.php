<?php
namespace tero\db\orm;

class Query{
	private $collection;

	public function __construct(){
		// create the collection
		$this->collection = new \SplDoublyLinkedList;
	}

	public function entity($entityName){
		// create the entity
		$entity = new Entity($entityName, $this);

		// add to the collection
		$this->collection->unshift($entity);

		return $entity;
	}

	public function fetchAll(){
		// declare the needed variables
		$prev	= null;
		$col	= $this->collection;
		$query	= new DMLBuilder();

		// restart the collection
		$col->rewind();

		// search the data
		while(!is_null(($item = $col->current()))){
			$col->next();
			continue;

			if($col->key() === 0){
				$query->
			$sql .= $item->getName();
			} else {
				$table = $item->getName();
				$sql .= " INNER JOIN {$table} ON {$table}.{$prev->getName()}_id = {$prev->getName()}.id ";
			}

			// $wheres = array_merge

			$prev = $item;
			$col->next();
		}


		$query = new DMLBuilder();

		$query
			->select("me.*")
			->from("module_events me")
			->join("module_acts ma")
			->on("ma.id", "me.main_acts")
			->where("ma.id", 7);


		var_dump($query->rawSql);
		var_dump($query->getParameters());

		die();

		// die(var_dump($sql));
	}
}
