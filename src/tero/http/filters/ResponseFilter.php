<?php
namespace tero\http\filters;

interface ResponseFilter extends Filter{
	public function response($response, array $parameters = array());
}
?>
