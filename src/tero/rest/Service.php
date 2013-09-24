<?php
namespace tero\rest;
use tero\http as http;

class Service extends http\Router{
	public function __construct(){
		// call the parent constructor
		parent::__construct();

		// set the filters
		$this->filter("accept", array(
			"application/json"	=> array($this, "formatAsJSON"),
			"text/html"			=> array($this, "formatAsJSON"),
			"any"				=> array($this, "formatAsJSON")
		));
	}

	public function formatAsJSON($response){
		if(is_resource($response)){
			return $response;
		}

		// set the headers and encode the response
		header("Content-type: application/json");
		return json_encode($response);
	}

	public function handle(){
		// start the output buffer
		@ob_start();

		// declare the needed variables
		$response = null;

		try{
			// call the parent handler
			$response = parent::handle();
		} catch(\Exception $e){
			// handle the exception
			return $this->handleException($e);
		}

		// check if the response integrity
		if(!http\Response::isError()){
			if(!is_null($response)){
				if(is_resource($response)){
					$this->responseAsResource($response);
				} elseif(is_string($response)){
					$this->responseAsString($response);
				} else {
					$this->responseAsUnknow($response);
				}
			}
		} else {
			// flush the buffer
			@ob_flush();
		}
	}

	private function handleException(\Exception $e){
		if($e instanceof http\exceptions\NoRouteException){
			http\Response::notFound();
		} elseif($e instanceof http\exceptions\ForbiddenException){
			if(!http\Response::isError()) http\Response::forbidden();
		}

		throw $e;
	}

	public function responseAsString($response){
		echo $response;
	}

	public function responseAsUnknow($response){
		$this->responseAsString((string)$response);
	}

	public function responseAsResource($resource){
		fpassthru($resource); 
		fclose($resource);
	}
}
?>
