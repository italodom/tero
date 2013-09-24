<?php
namespace tero\queue;

interface Queue{
	public function clear();
	public function dequeue();
	public function enqueue($message);
	public function isEmpty();
	public function isMessageQueued($messageID);
	public function queueSize();
}
?>
