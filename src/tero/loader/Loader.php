<?php
namespace tero\loader;

/**
 * Loader class
 * 
 */
class Loader {
	private static $paths			= array();
	private static $splRegistered	= false;

	/**
	 * loadFilesForClass 
	 * 
	 * @param string $className 
	 * @static
	 * @access public
	 * @return boolean
	 */
	static public function loadFilesForClass($className){
		foreach(self::$paths as $path){
			// format the filename
			$filepath = $path . str_replace('\\', DIRECTORY_SEPARATOR, trim(ltrim($className, '\\'))) . ".php";

			// check if the file exist
			if(file_exists($filepath) && is_readable($filepath)){
				require_once($filepath);
				return true;
			}
		}
				
		// return false 
		return false;
	}
	
	/**
	 * registerPath 
	 * 
	 * @param string $path 
	 * @static
	 * @access public
	 * @return void
	 */
	static function registerPath($path){
		// register the autoload, if it's not been registered
		if(!self::$splRegistered){
			spl_autoload_register('\tero\loader\Loader::loadFilesForClass');
		}

		// check if the directory exists
		if(file_exists($path) && is_dir($path) && is_readable($path)){
			if(substr($path, -1, 1) !== DIRECTORY_SEPARATOR){
				$path .= DIRECTORY_SEPARATOR;
			}
			// register the path
			self::$paths[] = $path;
		} else {
			throw new \Exception("File or directory not found: " . $path);
		}
	}
}
?>
