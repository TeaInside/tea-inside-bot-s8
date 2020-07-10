<?php

require __DIR__."/src/build/helpers.php";

$swooleDir = __DIR__."/src/ext/swoole";

function shw(...$argv)
{
  $n = sh(...$argv);
  if (!in_array($n, [0, -1])) {
    echo "Error, exit code {$n}\n";
    exit($n);
  }
}

if (isset($argv[1]) && ($argv[1] === "release")) {

  /* Compile swoole */
  shw($swooleDir, "phpize");
  shw($swooleDir, "./configure --enable-openssl --enable-sockets --enable-http2 --enable-mysqlnd");
  shw($swooleDir, "make");;

} else {

  /* Compile swoole */
  // shw($swooleDir, "phpize");
  // shw($swooleDir, "./configure --enable-openssl --enable-sockets --enable-http2 --enable-mysqlnd");
  // shw($swooleDir, "make");
  // shw($swooleDir, "sudo make install");

}
