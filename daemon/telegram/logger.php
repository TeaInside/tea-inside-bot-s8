<?php

\Co\run(function () use ($process) {
  go(function () use ($process) { logger_handler($process); });
});

/**
 * @param \Swoole\Process $process
 * @return void
 */
function logger_handler(\Swoole\Process $process): void
{
  echo "logger_handler is running...\n";
  _start:
  $data = $process->read();


  var_dump($data);



  goto _start;
}
