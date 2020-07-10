<?php

require __DIR__."/src/build/helpers.php";

if (isset($argv[1]) && ($argv[1] === "release")) {
  if (!sh("phpize", __DIR__."/src/ext")) {
    echo "phpize failed!";
  }
} else {
  // if (!sh("phpize", __DIR__."/src/ext")) {
  //   echo "phpize failed!";
  // }
  sh("./configure", __DIR__."/src/ext");
}
