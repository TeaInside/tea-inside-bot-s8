<?php

/**
 * @param ?string $cwd
 * @param string  $cmd
 * @param ?array  $env
 * @return int
 */
function sh(?string $cwd = null, string $cmd, ?array $env = null): int
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
