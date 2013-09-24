<?php
namespace tero\template;

class Template{
	private static $templatesPath;

	/**
	 * setTemplatesPath 
	 * 
	 * @param mixed $templatesPath 
	 * @static
	 * @access public
	 * @throws \tero\core\exceptions\FileNotFoundException
	 * @return void
	 */
	public static function setTemplatesPath($templatesPath){
		if(!file_exists($templatesPath) || !is_dir($templatesPath)){
			throw new \tero\core\exceptions\FileNotFoundException($templatesPath);
		}

		self::$templatesPath = $templatesPath;
	}

	public static function render($file, array $vars = array(), $returnData = false){
		// create the template path
		$templatePath = rtrim(self::$templatesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;

		// check if the file exists
		if(!file_exists($templatePath) || !is_readable($templatePath)){
			throw new \tero\core\exceptions\FileNotFoundException($templatePath);
		}

		// start the output buffer
		ob_start();

		// extract the parameters
		extract($vars);

		// load the view file
		include($templatePath);

		// check if the data must be returned
		if($returnData){
			// get the contents
			$data = ob_get_contents();

			// end the buffer and return the data
			ob_end_clean();

			return $data;
		}

		// flush the output buffer
		ob_end_flush();
	}
}
