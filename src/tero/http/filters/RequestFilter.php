<?php
namespace tero\http\filters;

interface RequestFilter extends Filter{
	public function request(array $parameters = array());
}
?>
