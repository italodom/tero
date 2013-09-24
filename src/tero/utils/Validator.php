<?php
namespace tero\utils;

class Validator{
	private $defaultErrors	= array();
	private $errorHandlers	= array();
	private $validations	= array();
	private $errors			= array();

	/**
	 * __call 
	 * 
	 * @param string $validation 
	 * @param array $arguments 
	 * @access public
	 * @return boolean
	 */
	public function __call($validation, array $arguments){
		// validate
		$this->validate(
			$validation, 
			$arguments[0], 
			(isset($arguments[1]) && is_string($arguments[1]) ? $arguments[1] : null), 
			(isset($arguments[2]) && is_string($arguments[2]) ? $arguments[2] : null), 
			(isset($arguments[3]) && is_array($arguments[3]) ? $arguments[3] : array())
		);

		return $this;
	}

	/**
	 * __construct 
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct(){
		// not empty validation
		$this->addvalidation("notEmpty", function($value, array $extraArguments = array()){
			return !empty($value);
		}, "The field %s cannot be empty!");

		// numeric validation
		$this->addValidation("numeric", function($value, array $extraArguments = array()){
			return is_numeric(trim($value));
		}, "The field %s must be numeric!");

		// email vlaidation
		$this->addValidation("email", function($value, array $extraArguments = array()){
			return (filter_var($value, FILTER_VALIDATE_EMAIL) === false ? false : true);
		});

		// equals validation
		$this->addValidation("equals", function($value, array $extraArguments = array()){
			if(!isset($extraArguments["value"])){
				throw new \Exception("You must specify the expected value for the 'equals' validator!");
			}

			// validate
			return ($value === $extraArguments["value"] ? true : false);
		}, "Invalid value for the field %s!");

		// regex validation
		$this->addvalidation("regex", function($value, array $extraArguments = array()){
			if(!isset($extraArguments["regex"])){
				throw new \Exception("You must specify a valid regex for the regular expression validator!");
			}

			// validate
			return (preg_match($extraArguments["regex"], $value) ? true : false);
		}, "The field %s is invalid!");
	}

	/**
	 * addValidation 
	 * 
	 * @param string $name 
	 * @param closure $validation 
	 * @access public
	 * @return void
	 */
	public function addValidation($name, \Closure $validation, $defaultErrorMessage = "Error validating the field %s"){
		// set the validation and the default error message
		$this->validations[$name]		= $validation;
		$this->defaultErrors[$name]		= $defaultErrorMessage;
	}

	/**
	 * getErrors 
	 * 
	 * @access public
	 * @return array
	 */
	public function getErrors(){
		return $this->errors;
	}

	/**
	 * hasErrors 
	 * 
	 * @access public
	 * @return boolean
	 */
	public function hasErrors(){
		return !empty($this->errors);
	}

	/**
	 * onError 
	 * 
	 * @param closure $handler 
	 * @access public
	 * @return void
	 */
	public function onError(\Closure $handler){
		array_push($this->errorHandlers, $handler);
	}

	/**
	 * validate 
	 * 
	 * @param string $validation 
	 * @param mixed $value 
	 * @param string $fieldName 
	 * @param string $customMsg 
	 * @param array $extraParameters 
	 * @access public
	 * @return boolean
	 */
	public function validate($validation, $value, $fieldName = null, $customMsg = null, array $extraParameters = array()){
		// format the parameters
		$fieldName	= (!is_null($fieldName) ? $fieldName : "");
		$customMsg	= (!is_null($customMsg) ? $customMsg : $this->defaultErrors[$validation]);

		// check if the validation exist
		if(!isset($this->validations[$validation])){
			throw new \Exception("Invalid validation!");
		}

		// run the validation
		if(!$this->validations[$validation]($value, $extraParameters)){
			// set the error message
			$errorMessage = @sprintf($customMsg, $fieldName);

			// store the error
			$this->errors[] = $errorMessage;

			// call the error handlers
			foreach($this->errorHandlers as $handler){
				$handler($errorMessage, $validation, $fieldName);
			}

			return false;
		}

		return true;
	}
}
?>
