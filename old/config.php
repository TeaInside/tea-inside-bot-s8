<?php

defined("BASEPATH") or define("BASEPATH", __DIR__);
define("STORAGE_PATH", BASEPATH."/storage");
define("TELEGRAM_BOT_TOKEN", "466965678:AAH__Cg3mqt3QjLh2f6GkKnOKTXbKtUowj8");
define("TELEGRAM_BOT_DB_HOST", "192.168.50.2");
define("TELEGRAM_BOT_DB_USER", "memcpy");
define("TELEGRAM_BOT_DB_PASS", "858869123qweASDzxc");
define("TELEGRAM_BOT_DB_PORT", "9999");
define("TELEGRAM_BOT_DB_NAME", "teabot_8");
define("TELEGRAM_BOT_SUDOERS", [243692601]);
define("TELEGRAM_STORAGE_PATH", BASEPATH."/storage/telegram");
define("TELEGRAM_WEBHOOK_KEY", "6e9b497c7ab7be32fd0be83502123490");
define("TELEGRAM_ERROR_REPORT_CHAT_ID", [-1001327448554]);
define("TELEGRAM_DAEMON_ERROR_LOG", STORAGE_PATH."/logs/telegram_daemon_error.log");
define("TELEGRAM_DAEMON_LOG_FILE", STORAGE_PATH."/logs/daemon.log");
define("TELEGRAM_DAEMON_PID_FILE", STORAGE_PATH."/pid/telegram_daemon.pid");

define("CALCULUS_API_KEY", "8e7eaa2822cf3bf77a03d63d2fbdeb36df0a409f");
define("SRABATSROBOT_API_KEY", "8Zvm1u34pMkv7FGLThjm4dkTu13xASN6XzTEij7/XAk=");

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
