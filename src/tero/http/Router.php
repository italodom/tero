<?php
namespace tero\http;

class Router{
	private $conditionalFilters	= array();
	private $requestFilters		= array();
	private $responseFilters	= array();
	private $routes				= array();

	public function __construct($routeFile = null){
		// start the request information
		return;
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

	public function any($path, $handler){
		return $this->route(Request::ANY, $path, $handler);
	}

	public function delete($path, $handler){
		return $this->route(Request::DELETE, $path, $handler);
	}

	public function filter($name){
		// create the class name
		$className = "\\tero\\http\\filters\\" . ucwords($name) . "Filter";

		if(class_exists($className)){
			// get the parameters
			$params = func_get_args(); array_shift($params);

			// create the filter
			$reflect	= new \ReflectionClass($className);
			$instance	= $reflect->newInstanceArgs($params);

			// add the filter
			$this->addFilter($instance);
		} else {
			throw new \Exception("Invalid filter: {$name}");
		}

		return $this;
	}

	public function get($path, $handler){
		return $this->route(Request::GET, $path, $handler);
	}

	public function handle(){
		// get the request path
		$path			= Request::getPath();
		$matchedRoute	= null;

		// search the rotes
		foreach($this->routes as $route){
			// check if the route matches the request
			if($route->match()){
				if($this->matchConditionalFilters($route) && $route->matchConditionalFilters()){
					// route suitable for the request, call the handler
					$matchedRoute = $route;
				} else {
					throw new exceptions\ForbiddenException();
				}
			}
		}

		// check if any route match the specified request
		if(!is_null($matchedRoute)){
			// run the request filters
			foreach($this->requestFilters as $filter){
				$filter->request($matchedRoute->getParameters());
			}

			// get the response
			$response = $matchedRoute->handle();

			// parse the response filters
			$lateFilters = array();
			foreach($this->responseFilters as $filter){
				if($filter instanceof \tero\http\filters\LateResponseFilter){
					$lateFilters[] = $filter;
				} else {
					$response = $filter->response($response, $matchedRoute->getParameters());
				}
			}

			foreach($lateFilters as $filter){
				$response = $filter->response($response, $matchedRoute->getParameters());
			}
	
			return $response;
		}

		throw new exceptions\NoRouteException();
	}

	public function matchConditionalFilters(routes\Route $route){
		// get the information about the route
		$conditionalFilters = $this->conditionalFilters;

		// check the request filters
		foreach($conditionalFilters as $filter){
			if(!$filter->conditional($route->getParameters())){ 
				return false; 
			}
		}

		return true;
	}

	public function post($path, $handler){
		return $this->route(Request::POST, $path, $handler);
	}

	public function put($path, $handler){
		return $this->route(Request::PUT, $path, $handler);
	}

	public function route($method, $path, $handler){
		// create the route
		$route = new routes\Route($path, $method, $handler);

		// save and return
		$this->routes[] = $route;
		return $route;
	}
}

?>
