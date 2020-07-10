<?php

/**
 * @param string  $cmd
 * @param ?string $cwd
 * @param ?array  $env
 * @return int
 */
function sh(string $cmd, ?string $cwd = null, ?array $env = null): int
{
  echo "Executing {$cmd}...\n";
  $proc = proc_open(
    $cmd,
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
