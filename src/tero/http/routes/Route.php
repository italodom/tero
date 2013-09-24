<?php
namespace tero\http\routes;

use \tero\http\Request;

class Route{
	private $expectedPath;
	private $method;
	private $handler;

	private $conditionalFilters = array();
	private $requestFilters		= array();
	private $responseFilters	= array();
	
	private $expectedPathRegex;
	private $parameters;

	public function __call($methodName, $parameters){
		// create the class name
		$className = "\\tero\\http\\filters\\" . ucwords($methodName) . "Filter";

		if(class_exists($className)){
			$reflect	= new \ReflectionClass($className);
			$instance	= $reflect->newInstanceArgs($parameters);

			// add the filter
			$this->addFilter($instance);
		} else {
			throw new \Exception("Invalid filter: {$methodName}");
		}

		return $this;
	}

	public function __construct($expectedPath, $method, $handler){
		// validate the path
		if(!is_string($expectedPath) || empty($expectedPath) || substr($expectedPath, 0, 1) !== "/"){
			throw new \Exception("Invalid route path: " . $path);
		}


		// save the information
		$this->expectedPath		= $expectedPath;
		$this->method			= $method;
		$this->handler			= $handler;
	}

	public function addFilter(\tero\http\filters\Filter $filter){
		// get the key
		$key = ($filter instanceof \tero\http\filters\UniqueFilter) ? get_class($filter) : spl_object_hash($filter);

		// save the filter
		if($filter instanceof \tero\http\filters\ConditionalFilter){
			$this->conditionalFilters[$key] = $filter;
		} elseif($filter instanceof \tero\http\filters\RequestFilter){
			$this->requestFilters[$key] = $filter;
		} elseif($filter instanceof \tero\http\filters\ResponseFilter){
			$this->responseFilters[$key] = $filter;
		} else {
			throw new \Exception("Invalid filter: " . get_class($filter));
		}
	}

	public function getExpectedPath(){
		return $this->expectedPath;
	}

	public function getExpectedPathRegex(){
		if(is_null($this->expectedPathRegex)){
			// declare and initiate the variables
			$regex = $this->getExpectedPath();

			// set the patterns and replacements
			$patterns = array(
				"/\/([^\/]+)\/[*]{2}[?]/",
				"/\/[*]{2}/", 
				"/\/[*]/", 
				"/\/[#]/", 
				"/\/[$]/"
			);
			$replacements = array(
				"(?:/$1/(.+))?",
				"/(.+)", 
				"/([^/]+)", 
				"/([[:digit:]]+)", 
				"/([[:alpha:]]+)"
			);

			// perform the replacements
			$this->expectedPathRegex = "/^" . str_replace("/", "\/", preg_replace($patterns, $replacements, $regex)) . "$/i";
		}

		return $this->expectedPathRegex;
	}

	public function getMethod(){
		return $this->method;
	}

	public function getParameters(){
		if(is_null($this->parameters)){
			// declare the needed variables
			$matches = array();

			// find the parameters
			preg_match($this->getExpectedPathRegex(), Request::getPath(), $matches);

			// remove the first element of the matches
			array_shift($matches);

			$this->parameters = $matches;
		}

		return $this->parameters;
	}
	
	public function handle(){
		// get the handler
		$handler = $this->handler;

		// format the handler, if needed
		if(is_array($handler) && count($handler) === 2 && class_exists($handler[0])){
			$handler[0] = new $handler[0];
		}

		// check if the handler is callable
		if(!is_callable($handler)){
			throw new \Exception("Invalid route handler: ROUTE {$this->expectedPath} - METHOD: {$this->method}");
		}

		// run the request filters
		foreach($this->requestFilters as $filter){
			$filter->request($this->getParameters());
		}

		// get the response
		$response = call_user_func_array($handler, $this->getParameters());

		// parse the response filters
		$lateFilters = array();
		foreach($this->responseFilters as $filter){
			if($filter instanceof \tero\http\filters\LateResponseFilter){
				$lateFilters[] = $filter;
			} else {
				$response = $filter->response($response, $this->getParameters());
			}
		}

		foreach($lateFilters as $filter){
			$response = $filter->response($response, $this->getParameters());
		}

		return $response;
	}
	
	public function match(){
		// get the information about the route
		$conditionalFilters = $this->conditionalFilters;
		$method				= $this->getMethod();
		$requestMethod		= Request::getMethod();

		// check if the route matches the method
		if(strtolower($requestMethod) === $method || $method === Request::ANY){
			// check if the route macthes the path
			if(preg_match($this->getExpectedPathRegex(), Request::getPath()) === 1){
				return true;
			}
		}

		return false;
	}

	public function matchConditionalFilters(){
		// get the information about the route
		$conditionalFilters = $this->conditionalFilters;

		// check the request filters
		foreach($conditionalFilters as $filter){
			if(!$filter->conditional($this->getParameters())){ 
				return false; 
			}
		}

		return true;
	}
}
