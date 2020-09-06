<?php

require __DIR__."/../bootstrap/telegram/autoload.php";

error_reporting(E_ALL);
ini_set("display_errors", true);

/* Load config. */
loadConfig("telegram/telegram_bot");

/* Save PID. */
if (defined("TELEGRAM_DAEMON_PID_FILE")) {
  file_put_contents(TELEGRAM_DAEMON_PID_FILE, getmypid());
}

\Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

$workers = [
  new \Swoole\Process(function($process){
    cli_set_process_title("logger");
    require __DIR__."/telegram/logger.php";
  }),
  new \Swoole\Process(function($process){
    cli_set_process_title("shell");
    require __DIR__."/telegram/shell.php";
  })
];

$workers[0]->start();
$workers[1]->start();

echo "Spawning response_handler...\n";
\Co\run(function () use ($workers) {
  require __DIR__."/telegram/response.php";
});