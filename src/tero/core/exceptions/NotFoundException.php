<?php
namespace tero\core\exceptions;

class NotFoundException extends \RuntimeException{
	public function __construct($resourceName = ""){
		parent::__construct("The resource {$resourceName} has not found!");
	}
}
?>
