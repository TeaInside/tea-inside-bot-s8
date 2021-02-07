<?php

require __DIR__."/../../config.php";
require BASEPATH."/src/helpers/global.php";

/**
 * @param string $class
 * @return void
 */
function teabotInternalAutoloader($class)
{
	$class = str_replace("\\", "/", $class);
	if (file_exists($f = BASEPATH."/src/classes/{$class}.php"))
		require $f;
}

spl_autoload_register("teabotInternalAutoloader");
