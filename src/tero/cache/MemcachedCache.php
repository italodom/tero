<?php
namespace tero\cache;

class MemcachedCache implements CacheSystem{
	private $defaultExpiryTime		= 0;
	private $namespaceExpiryTimes	= array();

	private $compress				= false;
	private $servers				= array();
	private $connector				= null;
	private $failServers			= 0;

	private $namespaceVersions		= array();

	/**
	 * __construct 
	 * 
	 * @param string $server 
	 * @param bool $compress 
	 * @access public
	 * @return void
	 */
	public function __construct($server = null, $compress = false){
		// check if the extension is loaded
		if(!extension_loaded("memcache")){
			throw new \Exception("The memcache extension is not loaded!");
		}

		// set the options
		$this->compress = $compress;

		// create the connection
		$this->connector = new \Memcache();

		if(!is_null($server)){
			if(is_array($server)){
				foreach($server as $srv)
					// connect
					$this->connect($srv);
			} else {
				// connect
				$this->connect($server);
			}
		}
	}

	/**
	 * clear 
	 * 
	 * @param string $name 
	 * @param string $namespace 
	 * @access public
	 * @return mixed
	 */
	public function clear($name, $namespace = null){
		// check if a namespace was specified
		if(!is_null($namespace)){
			// format the entry name
			$name = $this->formatNamespaceEntry($namespace, $name);
		}

		// delete the item
		return $this->connector->delete($name);
	}

	/**
	 * clearAll 
	 * 
	 * @access public
	 * @return mixed
	 */
	public function clearAll(){
		// delete all items
		return $this->connector->flush();
	}

	/**
	 * clearNamespace 
	 * 
	 * @param string $namespace 
	 * @access public
	 * @return bool
	 */
	public function clearNamespace($namespace){
		// increment the namespace
		$this->connector->increment("nsversion__{$namespace}");

		// force the version update
		$this->getNamespaceVersion($namespace, true);
	}


	/**
	 * connect 
	 * 
	 * @param string $server 
	 * @param int $port 
	 * @param bool $persistent 
	 * @param mixed $weight 
	 * @param int $timeout 
	 * @param int $retryInterval 
	 * @param bool $status 
	 * @access public
	 * @return void
	 */
	public function connect($server, $port = 11211, $persistent = true, $weight = null, $timeout = 1, $retryInterval = 15, $status = true){
		// calculate the weight
		if(is_null($weight)){
			$weight = count($this->servers) + 1;
		}

		// add the new server
		$this->connector->addServer($server, $port, $persistent, $weight, $timeout, $retryInterval, $status, array(&$this, "disableServer"));

		// store the server data
		$this->servers[$server] = array_merge(func_get_args(), array(
			"online" => true
		));
	}

	/**
	 * createNamespace 
	 * 
	 * @param string $namespace 
	 * @access private
	 * @return int Namespace version
	 */
	private function createNamespace($namespace){
		// check if the namespace already exists
		$nsVersion = $this->getNamespaceVersion($namespace);
		if($nsVersion === false){
			// generates a random number for the initial version
			$nsVersion = rand(0, 100);

			// create the namespace
			$this->connector->set("nsversion__{$namespace}", (int)$nsVersion);
	
			// save the version
			$this->namespaceVersions[$namespace] = (int)$nsVersion;
		}

		return $nsVersion;
	}


	/**
	 * disableServer 
	 * 
	 * @param string $server 
	 * @param int $port 
	 * @access public
	 * @return void
	 */
	public function disableServer($server, $port = 11211){
		// set the server as offline
		$this->failServers++;
		$this->servers[$server]["online"] = false;
	}

	/**
	 * formatNamespaceEntry 
	 * 
	 * @param string $namespace 
	 * @param string $name 
	 * @access private
	 * @return bool
	 */
	private function formatNamespaceEntry($namespace, $name){
		// get the namespace version
		$nsVersion = $this->getNamespaceVersion($namespace);

		// create the key
		return "ns__{$namespace}__{$nsVersion}." . $name;
	}

	/**
	 * getNamespaceVersion
	 * 
	 * @access private
	 * @return array
	 */
	private function getNamespaceVersion($namespace, $force = false){
		if(!isset($this->namespaceVersions[$namespace]) || $force){
			$this->namespaceVersions[$namespace] = $this->connector->get("nsversion__{$namespace}");
		}

		return $this->namespaceVersions[$namespace];
	}

	/**
	 * retrieve 
	 * 
	 * @param string $name 
	 * @param string $namespace 
	 * @access public
	 * @return mixed
	 */
	public function retrieve($name, $namespace = null){
		// check if a namespace was specified
		if(!is_null($namespace)){
			// format the entry name
			$name = $this->formatNamespaceEntry($namespace, $name);
		}
	
		// retrieve the item
		$data = $this->connector->get($name);

		// check if the data is serialized
		$unserialized = @unserialize($data);
		if($unserialized === false || is_null($unserialized) || (!is_array($unserialized) && !is_object($unserialized))){
			return $data;
		} else {
			return $unserialized;
		}
	}

	/**
	 * save 
	 * 
	 * @param string $name 
	 * @param mixed $value 
	 * @param int $expiryTime 
	 * @param string $namespace 
	 * @access public
	 * @return bool
	 */
	public function save($name, $value, $expiryTime = null, $namespace = null){
		// discover the expiry time
		if(is_null($expiryTime) || $expiryTime === false){
			$expiryTime = (!is_null($namespace) && isset($this->namespaceExpiryTimes[$namespace]))
				? $this->namespaceExpiryTimes[$namespace]
				: $this->defaultExpiryTime;
		}

		// check if a namespace was specified
		if(!is_null($namespace)){
			// create the namespace if it doesnt exists
			$this->createNamespace($namespace);

			// format the name of the entry
			$name = $this->formatNamespaceEntry($namespace, $name);
		}

		// save the item
		return $this->connector->set($name, (is_object($value) || is_array($value) ? serialize($value) : $value), ($this->compress ? \MEMCACHE_COMPRESSED : null), $expiryTime);
	}

	/**
	 * setDefaultExpiryTime 
	 * 
	 * @param int $expiryTime 
	 * @access public
	 * @return void
	 */
	public function setDefaultExpiryTime($expiryTime){
		$this->defaultExpiryTime = $expiryTime;
	}

	/**
	 * setNamespaceExpiryTimes 
	 * 
	 * @param array $expiryTimes 
	 * @access public
	 * @return void
	 */
	public function setNamespaceExpiryTimes(array $expiryTimes){
		$this->namespaceExpiryTimes = $expiryTimes;
	}
}
?>
