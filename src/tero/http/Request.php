<?php
namespace tero\http;

class Request{
	const DELETE	= 'delete';
	const GET		= 'get';
	const POST		= 'post';
	const PUT		= 'put';
	const ANY		= 'any';

	private static $initialized	= false;
	private static $headers		= array();
	private static $method		= null;
	private static $path		= null;
	private static $params		= array(
		"get"		=> array(), 
		"post"		=> array(), 
		"put"		=> array(), 
		"delete"	=> array(),
		"any"		=> array()
	);

	private static function getallheaders() { 
        foreach($_SERVER as $name => $value){ 
            if(substr($name, 0, 5) == 'HTTP_'){ 
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))); 
                $headers[$name] = $value; 
            } else if ($name == "CONTENT_TYPE") { 
                $headers["Content-Type"] = $value; 
            } else if ($name == "CONTENT_LENGTH") { 
                $headers["Content-Length"] = $value; 
            } 
        } 
        return $headers; 
    }  


	private static function init(){
		// chek if the request is already initialised
		if(self::$initialized !== false){
			return true;
		}

		// find the request method
		switch(strtolower($_SERVER["REQUEST_METHOD"])){
			case 'delete':
				self::$method = self::DELETE;
			break;
			case 'get': 
				self::$method = self::GET;
			break;
			case 'post':
				self::$method = self::POST;
			break;
			case 'put':
				self::$method = self::PUT;
			break;
		}

		// store the path
		self::$path = (!isset($_SERVER["PATH_INFO"]) ? "/" : $_SERVER["PATH_INFO"]);

		// store the headers
		self::$headers = array_change_key_case(self::getallheaders(), CASE_LOWER);

		// store the put parameters
		parse_str(file_get_contents("php://input"), self::$params["put"]);

		// store the delete parameters
		self::$params["delete"] = self::$params["put"];

		// store the get and post parameters
		self::$params["get"]	= $_GET;
		self::$params["post"]	= $_REQUEST;

		// mix all the parameters
		self::$params["any"] = self::$params["delete"] + self::$params["put"] + self::$params["get"] + self::$params["post"];

		// set as initialised
		self::$initialized = true;
	}

	public static function accept($value){
		if(!isset($_SERVER["HTTP_ACCEPT"])){
			return false;
		}

		return stristr($_SERVER["HTTP_ACCEPT"], $value);
	}

	public static function getHeaders(){
		self::init();
		return self::$headers;
	}

	public static function getMethod(){
		self::init();
		return self::$method;
	}

	public static function getPath(){
		self::init();
		return self::$path;
	}

	public static function header($name){
		// init the request
		self::init();

		// get the headers list and format the name of the requested header
		$headers	= self::$headers;
		$name		= strtolower($name);

		return (isset($headers[$name]) ? trim($headers[$name]) : false);
	}

	public static function isMethodValid($method){
		if($method !== self::DELETE && $method !== self::GET && $method !== self::POST && $method !== self::PUT && $method !== self::ANY){
			return false;
		}

		return true;
	}

	public static function getParameters($method = self::ANY){
		// init the request
		self::init();

		// return the parameters
		$method = strtolower($method);
		return self::$params[$method];
	}

	public static function parameter($name, $method = self::ANY){
		// init the request
		self::init();
		
		// format the parameters
		$method = strtolower($method);
		return (isset(self::$params[$method][$name]) ? self::$params[$method][$name] : false);
	}
}
?>