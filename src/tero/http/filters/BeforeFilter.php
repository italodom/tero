<?php
namespace tero\http\filters;

class BeforeFilter implements RequestFilter{
	public function __construct($handler){
		if(!is_callable($handler)){
			throw new \InvalidArgumentException("Invalid handler for the BEFORE filter");
		}

		$this->handler = $handler;
	}

	public function request(array $params = array()){
		call_user_func_array($this->handler, $params);
	}
}
?>
