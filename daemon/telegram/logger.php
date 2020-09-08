<?php

\Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
\Co\run(function () use ($process, $i) {
  go(function () use ($process, $i) { logger_handler($process, $i); });
});

/**
 * @param \Swoole\Process $process
 * @param int             $i
 * @return void
 */
function logger_handler(\Swoole\Process $process, int $i): void
{
  echo "logger_handler ({$i}) is running...\n";

  _start:
  $data = $process->read();

  

  goto _start;
}
