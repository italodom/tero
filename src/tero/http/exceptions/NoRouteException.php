<?php
namespace tero\http\exceptions;

class NoRouteException extends \Exception{

	public function __construct(){
		parent::__construct("No route has been found to the specified request!", 404);
	}
}
?>
