<?php
namespace tero\db\orm;

class Database{
	public function __get($entityName){
		// create a new query list
		$query = new Query();

		// create the entity
		return $query->entity($entityName);
	}
}
