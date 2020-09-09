<?php

require __DIR__."/../bootstrap/telegram/autoload.php";

error_reporting(E_ALL);
ini_set("display_errors", true);

/* Load config. */
loadConfig("telegram/telegram_bot");

date_default_timezone_set("etc/UTC");

if (!defined("TELEGRAM_DAEMON_LOGGER_WORKERS")) {
  echo "TELEGRAM_DAEMON_LOGGER_WORKERS is not defined!\n";
  exit(1);
}

if (!defined("TELEGRAM_DAEMON_RESPONDER_WORKERS")) {
  echo "TELEGRAM_DAEMON_RESPONDER_WORKERS is not defined!\n";
  exit(1);
}

if (defined("TELEGRAM_DAEMON_PID_FILE")) {
  file_put_contents(TELEGRAM_DAEMON_PID_FILE, getmypid());
}

if (defined("TELEGRAM_DAEMON_LOG_FILE")) {
  $logHandle = fopen(TELEGRAM_DAEMON_LOG_FILE, "a+");
  \TeaBot\Telegram\Dlog::registerErrHandler($logHandle);
  \TeaBot\Telegram\Dlog::registerOutHandler($logHandle);
  \TeaBot\Telegram\Dlog::registerErrHandler(STDOUT);
  \TeaBot\Telegram\Dlog::registerOutHandler(STDOUT);
  unset($logHandle);  
}

\Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

pcntl_signal(SIGCHLD, SIG_IGN);

$GLOBALS["loggersPid"] = [];
foreach (TELEGRAM_DAEMON_LOGGER_WORKERS as $k => $bindAddr) {
  if (!($GLOBALS["loggersPid"][$k] = pcntl_fork())) {
    unset($GLOBALS["loggersPid"]);
    \Co\run(function () use ($bindAddr, $k) {
      go(function () use ($bindAddr, $k) {
        require __DIR__."/telegram/logger.php";
      });
    });
    exit;
  }
}

$GLOBALS["respondersPid"] = [];
foreach (TELEGRAM_DAEMON_RESPONDER_WORKERS as $k => $bindAddr) {
  if (!($GLOBALS["respondersPid"][$k] = pcntl_fork())) {
    unset($GLOBALS["respondersPid"]);
    $k = count(TELEGRAM_DAEMON_RESPONDER_WORKERS) - $k;
    \Co\run(function () use ($bindAddr, $k) {
      go(function () use ($bindAddr, $k) {
        require __DIR__."/telegram/responder.php";
      });
    });
    exit;
  }
}

unset($k, $bindAddr);
usleep(50000);
\Co\run(function () {
  go(function () { require __DIR__."/telegram/master.php"; });
});
