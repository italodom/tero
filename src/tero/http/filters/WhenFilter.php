<?php
namespace tero\http\filters;

class WhenFilter implements ConditionalFilter{
	private $handler;

	public function __construct($handler){
		if(!is_callable($handler)){
			throw new \InvalidArgumentException("Invalid handler for the IF filter");
		}

		$this->handler = $handler;
	}

	public function conditional(array $params = array()){
		return call_user_func_array($this->handler, $params);
	}
}
?>
