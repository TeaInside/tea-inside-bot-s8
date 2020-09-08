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

const C = [
  // Array index.
  "k" => [
    "logger"    => 0,
    "responder" => 1,
  ],

  // Number of workers.
  "n" => [
    "logger"    => 3,
    "responder" => 3,
  ],

  "responder_bind_addr"  => "127.0.0.1",
  "responder_start_port" => 7000,
];

echo "Spawning logger_handler...\n";
$k = C["k"]["logger"];
$n = C["n"]["logger"];
for ($i = 0; $i < $n; $i++) {
  $l = new \Swoole\Process(function($process) use ($i) {
    $i = N_LOGGER - $i;
    cli_set_process_title("logger_{$i}");
    require __DIR__."/telegram/logger.php";
    exit;
  });
  $l->start();
  $GLOBALS["workers"][$k][] = $l;
}


echo "Spawning response_handler...\n";
$k = C["k"]["logger"];
$n = C["n"]["logger"];
for ($i = 0; $i < $n; $i++) {
  $l = new \Swoole\Process(function($process) use ($i) {
    $i = N_LOGGER - $i;
    cli_set_process_title("logger_{$i}");
    require __DIR__."/telegram/logger.php";
    exit;
  });
  $l->start();
  $GLOBALS["workers"][$k][] = $l;
}

echo "Spawning response_handler...\n";
$port = N_RESPONDER_START_PORT;
for ($i = 0; $i < N_RESPONDER; $i++) {

  $GLOBALS["workers"][N_RESPONDER];

  \Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
  \Co\run(function () { require __DIR__."/telegram/response.php"; });
}
