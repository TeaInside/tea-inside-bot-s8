<?php

shell_handler($process);

/**
 * @param \Swoole\Process $process
 * @return void
 */
function shell_handler(\Swoole\Process $process): void
{
  echo "shell_handler is running...\n";
  _start:
  $data = $process->read();


  var_dump($data);



  goto _start;
}
