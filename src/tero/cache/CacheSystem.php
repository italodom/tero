<?php
namespace tero\cache;

interface CacheSystem{
	public function clear($name, $namespace = null);
	public function clearAll();
	public function clearNamespace($namespace);
	public function retrieve($name, $namespace = null);
	public function save($name, $value, $expiryTime, $namespace = null);
	public function setDefaultExpiryTime($defaultExpiryTime);
	public function setNamespaceExpiryTimes(array $namespaceExpiryTimes);
}
?>
