<?php

/**
 * @param ?string $cwd
 * @param string  $cmd
 * @param ?array  $env
 * @return int
 */
function sh(?string $cwd = null, string $cmd, ?array $env = null): int
{
  global $silentCmd;

  if ((!isset($silentCmd)) || (!$silentCmd)) {
    echo "Executing {$cmd}...\n";
  }

  $proc = proc_open(
    "exec ".$cmd,
    [
      ["file", "php://stdin", "r"],
      ["file", "php://stdout", "w"],
      ["file", "php://stderr", "w"]
    ],
    $pipes,
    $cwd,
    $env
  );
  do {
    $status = proc_get_status($proc);
  } while ($status["running"]);
  proc_close($proc);
  return (int)$status["exitcode"];
}

/**
 * @param string    $directory
 * @param ?callable $callback
 * @return void
 */
function recursiveCallbackScanDir(string $directory, callable $callback = null): void
{
  $scan = scandir($directory);
  unset($scan[0], $scan[1]);
  foreach ($scan as $file) {
    $absFile = $directory."/".$file;
    if (is_dir($absFile)) {
      recursiveCallbackScanDir($absFile, $callback);
    } else {
      $callback($directory, $file);
    }
  }
}
