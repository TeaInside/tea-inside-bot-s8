<?php

defined("BASEPATH") or define("BASEPATH", __DIR__);
define("STORAGE_PATH", BASEPATH."/storage");
define("TELEGRAM_BOT_TOKEN", "xxxxxxxxxxxxxx");
define("TELEGRAM_BOT_DB_HOST", "127.0.0.1");
define("TELEGRAM_BOT_DB_USER", "root");
define("TELEGRAM_BOT_DB_PASS", "xxxxxx");
define("TELEGRAM_BOT_DB_PORT", "3306");
define("TELEGRAM_BOT_DB_NAME", "teabot8");
define("TELEGRAM_BOT_SUDOERS", [123123123]);
define("TELEGRAM_STORAGE_PATH", BASEPATH."/storage/telegram");
define("TELEGRAM_WEBHOOK_KEY", "6e9b497c7ab7be32fd0be83502123490");
define("TELEGRAM_ERROR_REPORT_CHAT_ID", [-100100000]);
define("TELEGRAM_DAEMON_ERROR_LOG", STORAGE_PATH."/logs/telegram_daemon_error.log");
define("TELEGRAM_DAEMON_LOG_FILE", STORAGE_PATH."/logs/daemon.log");
define("TELEGRAM_DAEMON_PID_FILE", STORAGE_PATH."/pid/telegram_daemon.pid");

define("TELEGRAM_DAEMON_MASTER_BA", "127.0.0.1:7000");
define("TELEGRAM_MUTEXES_LOCK_DIR", TELEGRAM_STORAGE_PATH."/mutexes");

define("TELEGRAM_DAEMON_LOGGER_WORKERS",
  [
    "127.0.0.1:7100",
    "127.0.0.1:7101",
    "127.0.0.1:7102",
    "127.0.0.1:7103",
  ]
);

define("TELEGRAM_DAEMON_RESPONDER_WORKERS",
  [
    "127.0.0.1:7200",
    "127.0.0.1:7201",
    "127.0.0.1:7202",
    "127.0.0.1:7203",
  ]
);

$ignoreAll = "*\n!.gitignore\n";
