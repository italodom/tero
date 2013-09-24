<?php
// set the include path
set_include_path(realpath(__DIR__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . PATH_SEPARATOR . get_include_path());

// set the autoload
spl_autoload_register(function($className){
	@include(str_replace('\\', '/', ltrim($className, '\\')) . '.php');
});
?>
