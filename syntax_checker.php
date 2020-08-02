<?php

require __DIR__."/src/build/helpers.php";

$silentCmd = true;
$directories = [
  __DIR__."/src/build",
  __DIR__."/src/classes",
  __DIR__."/src/helpers",
  __DIR__."/bootstrap",
  __DIR__."/config",
  __DIR__."/daemon",
  __DIR__."/public",
];

foreach ($directories as $k => $v) {
  recursiveCallbackScanDir($v, function (string $dir, string $file) {

    if (!preg_match("/\.php$/Si", $file)) {
      return;
    }

    $exec = trim(shell_exec("exec ".PHP_BINARY." -l ".escapeshellarg($dir."/".$file)));
    echo $exec."\n";
    if (!preg_match("/^No syntax errors detected/Si", $exec)) {
      echo "Error!\n";
      exit(1);
    }
  });
}

echo "OK!\n";
