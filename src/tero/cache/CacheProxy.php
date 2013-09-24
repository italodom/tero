<?php
namespace tero\cache;

class CacheProxy{
	private static $cacheSystem = null;

	/**
	 * __call 
	 * 
	 * @param string $method 
	 * @param array $params 
	 * @access public
	 * @return mixed
	 */
	public static function __callStatic($method, array $params){
		// check if the cache system is specified
		if(is_null(self::$cacheSystem)){
			return false;
		}

		// call the function
		return call_user_func_array(array(self::$cacheSystem, $method), $params);
	}

	/**
	 * getCacheSystem 
	 * 
	 * @access public
	 * @return \tero\cache\CacheSystem
	 */
	public static function getCacheSystem(){
		return self::$cacheSystem;
	}

	/**
	 * setCacheSystem 
	 * 
	 * @param \tero\cache\CacheSystem $system 
	 * @access public
	 * @return void
	 */
	public static function setCacheSystem(CacheSystem $system){
		self::$cacheSystem = $system;
	}
}
?>
