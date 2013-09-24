<?php
namespace tero\http\filters;
use InvalidArgumentException;

abstract class AbstractFilter{
	private $callback;

	public function __construct($handler){
		if(!is_callable($handler)){
			throw new InvalidArgumentException("The filter handler must be callable");
		}

		$this->callback = $callback;
	}
}
