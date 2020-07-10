<?php

require __DIR__."/src/build/helpers.php";

if (isset($argv[1]) && ($argv[1] === "release")) {
  sh("phpize", __DIR__."/src/ext");
} else {
  sh("phpize", __DIR__."/src/ext");
}
