<?php  
namespace tero\queue;

class MemcachedQueue implements Queue{
	private $name;
	private $connector;
	private $servers;
	private $compress;
	private $failServers;
	private $timeToLive = 0;
	
	public function __construct($name = "queue", $compress = true, $timeToLive = 0){
		// check if the extension is loaded
		if(!extension_loaded("memcache")){
			throw new Exception("The memcache extension is not loaded!");
		}

		// save the name
		$this->name = $name;

		// set the options
		$this->compress		= $compress;
		$this->timeToLive	= $timeToLive;

		// create the connection
		$this->connector = new \Memcache();
	}

	public function clear(){
		$name = $this->name;
		$conn = $this->connector;

		// get the head and tail
		$tail = $conn->get("{$name}_tail");

		// search the items in the queue
		for($i = 1; $i <= $tail; $i++){
			// delete the items
			$conn->delete("{$name}_{$i}");
		}

		// delete the head and tail
		$conn->delete("{$name}_head");
		$conn->delete("{$name}_tail");
	}

	public function connect($server, $port = 11211, $persistent = true, $weight = null, $timeout = 1, $retryInterval = 15, $status = true){
		// calculate the weight
		if(is_null($weight)){
			$weight = count($this->servers) + 1;
		}

		// add the new server
		$this->connector->addServer($server, $port, $persistent);

		// store the server data
		$this->servers[$server] = array_merge(func_get_args(), array(
			"online" => true
		));
	}

	public function dequeue(){
		$name = $this->name;
		$conn = $this->connector;  

		// get the tail
		$tail = $conn->get("{$name}_tail");  

		// try to increment the head
		$id = $conn->increment("{$name}_head");

		// check if the id could be incremented
        if($id === false){
			return false;
		}

		// check if the new head is smaller or equals the tail
		if($id <= $tail){
			// return the item
            return $conn->get($name . "_" . $id);
		} else {
			// invalid item size (dequeing and empty queue)
            $conn->decrement("{$name}_head");  
			return false;
        }
	}

	public function disableServer($server, $port = 11211){
		// set the server as offline
		$this->failServers++;
		$this->servers[$server]["online"] = false;
	}

	public function enqueue($message){  
		$name = $this->name;
		$conn = $this->connector;

		// increment the tail
		$tail = $conn->increment("{$name}_tail");

		// check if the tail was successfully incremented
		if($tail === false){
			// erro incrementing the tail, that probably means that the queue has not been initiated yet, start it
			$conn->add("{$name}_head", 0, null, 0);
			$conn->add("{$name}_tail", 0, null, 0);

			// try to increment the tail again
			$tail = $conn->increment("{$name}_tail");

			if($tail === false){
				return false;
			}
		}

		$head = $conn->get("{$name}_head");

		// enqueue the item
		if($conn->add("{$name}_{$tail}", $message, ($this->compress ? MEMCACHE_COMPRESSED : null), $this->timeToLive) === false){
			return false;
		}

		return $tail;
	}

	public function isEmpty(){
		if($this->queueSize() === 0){
			return true;
		}

		return false;
	}

	public function isMessageQueued($messageID){
		$name = $this->name;
		$conn = $this->connector;

		// get the head and tail
        $head = $conn->get($name . "_head");
		$tail = $conn->get($name . "_tail");

		if($head === false || $tail === false || $messageID > $tail || $messageID <= $head){
			return false;
		}

		return true;
	}

	public function queueSize(){
		$name = $this->name;
		$conn = $this->connector;

		// get the head and tail
        $head = $conn->get($name . "_head");  
		$tail = $conn->get($name . "_tail");

		if($tail === false || $head === false){
			return 0;
		} else {
			return $tail - $head;
		}
	}
}
?>
