<?php

require __DIR__."/src/build/helpers.php";

$swooleDir = __DIR__."/src/ext/swoole";
$swooleLockFile = $swooleDir."/swoole_compiled.lock";
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
  if (!file_exists($swooleLockFile)) {
    shw($swooleDir, "phpize");
    shw($swooleDir, "./configure --enable-openssl --enable-sockets --enable-http2 --enable-mysqlnd");

    // Enable optimization.
    file_put_contents($swooleDir."/Makefile",
      str_replace(" -O0", " -O3", file_get_contents($swooleDir."/Makefile")));

    shw($swooleDir, "make");
    shw($swooleDir, "sudo make install");
    touch($swooleLockFile);
  }

  /*
   * Build telegram config.
   */
  recursiveCallbackScanDir($telegramConfigDir, function (string $dir, string $file) {
    if (preg_match("/^.+\.frag\.php$/S", $file)) {
      shw(null, escapeshellarg(PHP_BINARY)." ".escapeshellarg($dir."/".$file));
    }
  });

} else {

  /* Compile swoole */
  if (!file_exists($swooleLockFile)) {
    shw($swooleDir, "phpize");
    shw($swooleDir, "./configure --enable-openssl --enable-sockets --enable-http2 --enable-mysqlnd");

    // Enable optimization.
    file_put_contents($swooleDir."/Makefile",
      str_replace(" -O0", " -O3", file_get_contents($swooleDir."/Makefile")));

    shw($swooleDir, "make -j \$(nproc)");
    shw($swooleDir, "sudo make install");
    touch($swooleLockFile);
  }

  /**
   * Build telegram config.
   */
  recursiveCallbackScanDir($telegramConfigDir, function (string $dir, string $file) {
    if (preg_match("/^.+\.frag\.php$/S", $file)) {
      shw(null, escapeshellarg(PHP_BINARY)." ".escapeshellarg($dir."/".$file));
    }
  });
}
