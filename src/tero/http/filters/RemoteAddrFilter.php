<?php
namespace tero\http\filters;

class RemoteAddrFilter implements ConditionalFIlter, UniqueFilter{
	private $validIPs;

	public function __construct($validIPs){
		if(!is_array($validIPs)){
			$validIPs = array($validIPs);
		}

		$this->validIPs = $validIPs;
	}

	public function conditional(array $params = array()){
		return in_array($_SERVER["REMOTE_ADDR"], $this->validIPs);
	}
}
?>
