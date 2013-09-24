<?php
namespace tero\http\filters;

class AfterFilter implements ResponseFilter{
	public function __construct($handler){
		// format the handler, if needed
		if(is_array($handler) && count($handler) === 2 && class_exists($handler[0])){
			$handler[0] = new $handler[0];
		}

		if(!is_callable($handler)){
			throw new \InvalidArgumentException("Invalid handler for the AFTER filter");
		}

		$this->handler = $handler;
	}

	public function response($response, array $params = array()){
		return call_user_func_array($this->handler, array_merge(array($response), $params));
	}
}
?>
