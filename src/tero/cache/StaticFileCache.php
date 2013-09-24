<?php
namespace tero\cache;

class StaticFileCache implements CacheSystem{
	private $defaultExpiryTime		= 60;
	private $namespaceExpiryTimes	= array();

	private $enabled			= false;
	private $updated			= false;
	private $useCompression		= true;
	private $cacheFile			= null;

	private $cacheContents	= array(
		"updateTime"	=> null, 
		"entries"		=> array()
	);

	public function __construct($cacheFile, $useCompression = true){
		// get the path info
		$info = pathinfo($cacheFile);

		// check if the cache file is writeable
		if(!file_exists($info["dirname"]) || !is_writeable($info["dirname"]) || (file_exists($cacheFile) && !is_writeable($cacheFile))){
			throw new \Exception("The specified cache file is not writeable!");
		}

		// check if the cache file already exists
		if(file_exists($cacheFile)){
			// check if must use compression to read the file
			if($useCompression && function_exists("gzuncompress")){
				$cacheContents = @unserialize(gzuncompress(file_get_contents($cacheFile)));
			} else {
				$cacheContents = @unserialize(file_get_contents($cacheFile));
			}

			// check if the cache file have valid entries
			if(is_array($cacheContents) && isset($cacheContents["entries"]) && isset($cacheContents["updateTime"]) && !empty($cacheContents["entries"])){
				// get the entries
				$entries = $cacheContents["entries"];

				// merge the contents in  this cache file
				$this->cacheContents["entries"] = $entries;

				// clear the expired entries 
				foreach($entries as $name => $data){
					if(mktime() > $data["expireAt"]){
						$this->clear($name);
					}
				}
			}
		}

		// set the flags
		$this->cacheFile		= $cacheFile;
		$this->useCompression	= $useCompression;
		$this->enabled			= true;
	}

	public function __destruct(){
		// check if the cache is enabled and has been updated
		if($this->enabled && $this->updated){
			// set the update time
			$this->cacheContents["updateTime"] = mktime();

			// write it into the filesystem
			if($this->useCompression && function_exists("gzcompress")){
				file_put_contents($this->cacheFile, gzcompress(serialize($this->cacheContents)), LOCK_EX);
			} else {
				file_put_contents($this->cacheFile, serialize($this->cacheContents), LOCK_EX);
			}
		}
	}

	public function setDefaultExpiryTime($expiryTime){
		$this->defaultExpiryTime = $expiryTime;
	}

	public function setNamespaceExpiryTimes(array $expiryTimes){
		$this->namespaceExpiryTimes = $expiryTimes;
	}

	public function setUseCompression($useCompression = true){
		$this->useCompression	= true;
		$this->updated			= true;
	}

	public function save($name, $value, $expiryTime = null, $namespace = null){
		// discover the expiry time
		if(is_null($expiryTime) || $expiryTime === false){
			$expiryTime = (!is_null($namespace) && isset($this->namespaceExpiryTimes[$namespace]))
				? $this->namespaceExpiryTimes[$namespace]
				: $this->defaultExpiryTime;
		}

		// check if a namespace was specified
		if(!is_null($namespace)){
			// format the entry name
			$name = $this->formatNamespaceEntry($namespace, $name);
		}

		// save the entry
		$this->cacheContents["entries"][$name] = array(
			"expireAt" => (mktime() + $expiryTime), 
			"value" => $value, 
			"namespace" => $namespace
		);

		// set the updated flag
		$this->updated = true;
	}

	public function retrieve($name, $namespace = null){
		// check if a namespace was specified
		if(!is_null($namespace)){
			// format the entry name
			$name = $this->formatNamespaceEntry($namespace, $name);
		}


		// get the entries
		$entries = $this->cacheContents["entries"];

		// check if the entry exists
		if(isset($entries[$name])){
			// check the expire time
			if(mktime() > $entries[$name]["expireAt"]){
				$this->clear($name); // expired cache, clears it
				return false;
			} else {
				return $entries[$name]["value"]; // cache valid
			}
		}

		return false;
	}

	public function clear($name, $namespace = null){
		// check if a namespace was specified
		if(!is_null($namespace)){
			// format the entry name
			$name = $this->formatNamespaceEntry($namespace, $name);
		}

		// check if the entry exists
		if(isset($this->cacheContents["entries"][$name])){
			// unset the cache entry
			unset($this->cacheContents["entries"][$name]);
			$this->updated = true;
		}
	}

	public function clearAll(){
		// clear all cache entries
		$this->cacheContents["entries"]	= array();
		$this->updated						= true;
	}

	public function clearNamespace($namespace){
		// get the entries
		$entries = $this->cacheContents["entries"];

		// search for namespaced entries
		foreach($entries as $name => $data){
			if($data["namespace"] === $namespace){
				// clear the entry
				$this->clear($name);
			}
		}
	}

	private function formatNamespaceEntry($namespace, $name){
		return "ns__{$namespace}." . $name;
	}
}
?>
