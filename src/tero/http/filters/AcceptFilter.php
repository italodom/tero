<?php
namespace tero\http\filters;

class AcceptFilter implements LateResponseFilter{
	private $types;

	public function __construct(array $types){
		foreach($types as $type => $handler){
			if(!is_callable($handler)){
				throw new \InvalidArgumentException("Handler invalid for the content type {$type}");
			}
		}

		$this->types = $types;
	}

	public function response($response, array $params = array()){
		foreach($this->types as $type => $handler){
			// check if the request accept this content-type
			if(strtolower($type) !== "default" && \tero\http\Request::accept($type)){
				// call the handler
				return call_user_func_array($handler, array($response, $params));
			}
		}

		if(isset($this->types["any"])){
			return call_user_func_array($this->types["any"], array_merge(array($response), $params));
		}

		return $response;
	}
}
?>
