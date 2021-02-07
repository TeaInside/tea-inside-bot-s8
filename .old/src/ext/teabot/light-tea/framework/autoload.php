<?php

/**
 * @param string $class
 * @return void
 */
function light_tea_autoloader($class)
{
  if (file_exists($f = __DIR__."/classes/".str_replace("\\", "/", $class).".php")) {
    require $f;
  }
}

spl_autoload_register("light_tea_autoloader");

require __DIR__."/config.php";
require __DIR__."/helpers/global.php";

class_alias(LightTeaPHP\LightTeaPHP::class, "LightTeaPHP");
class_alias(LightTeaPHP\Components\PHPClass::class, "PHPClass");
