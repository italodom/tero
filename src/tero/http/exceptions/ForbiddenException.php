<?php
namespace tero\http\exceptions;

class ForbiddenException extends \Exception{

	public function __construct(){
		parent::__construct("The access to the route is forbidden to this request!", 403);
	}
}
?>
