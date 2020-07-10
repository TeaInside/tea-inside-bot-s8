<?php

require __DIR__."/src/build/helpers.php";

$swooleDir = __DIR__."/src/ext/swoole";
$telegramConfigDir = __DIR__."/config/telegram";

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


  /**
   * Build telegram config.
   */
  recursiveCallbackScanDir($telegramConfigDir, function (string $dir, string $file) {
    if (preg_match("/^.+\.frag\.php$/S", $file)) {
      shw(null, escapeshellarg(PHP_BINARY)." ".escapeshellarg($dir."/".$file));
    }
  });

}
