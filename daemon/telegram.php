<?php

require __DIR__."/../bootstrap/telegram/autoload.php";

error_reporting(E_ALL);
ini_set("display_errors", true);

/* Load configurations. */
loadConfig("telegram/api");
loadConfig("telegram/quran");
loadConfig("telegram/calculus");
loadConfig("telegram/telegram_bot");

/* Save PID. */
file_put_contents(TELEGRAM_DAEMON_PID_FILE, getmypid());

\Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

if (!($loggerPid = pcntl_fork())) {

  /* Child process. */
  $fx = require __DIR__."/telegram/logger.php";
  \Co\run(function () use ($fx) { go($fx); });

} else {

  /* Parent process. */
  $fx = require __DIR__."/telegram/response.php";
  \Co\run(function () use ($fx) { go($fx); });

}
