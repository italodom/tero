<?php
namespace tero\core\exceptions;

class FileNotFoundException extends \RuntimeException{
	private $filename;

	public function getFilename(){
		return $this->filename;
	}

	public function __construct($filename = ""){
		parent::__construct("The file {$filename} has not found!");
		$this->filename = $filename;
	}
}
?>
