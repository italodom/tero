<?php
namespace tero\http\filters;

interface ConditionalFilter extends Filter{
	public function conditional(array $params = array());
}
?>
