<?php

require __DIR__."/../../bootstrap/frag_autoload.php";

$const["TELEGRAM_STORAGE_PATH"] = TELEGRAM_STORAGE_PATH;

$const["BOT_TOKEN"] = TELEGRAM_BOT_TOKEN;
$const["PDO_PARAM"] = [
    "mysql:host=".TELEGRAM_BOT_DB_HOST.";port=".TELEGRAM_BOT_DB_PORT.";dbname=".TELEGRAM_BOT_DB_NAME,
    TELEGRAM_BOT_DB_USER,
    TELEGRAM_BOT_DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET names utf8mb4",
      PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => 1,
      PDO::ATTR_EMULATE_PREPARES => 1
    ]
];
$const["SUDOERS"] = TELEGRAM_BOT_SUDOERS;
$const["TELEGRAM_WEBHOOK_KEY"] = TELEGRAM_WEBHOOK_KEY;
$const["TELEGRAM_ERROR_REPORT_CHAT_ID"] = TELEGRAM_ERROR_REPORT_CHAT_ID;
$const["TELEGRAM_DAEMON_ERROR_LOG"] = TELEGRAM_DAEMON_ERROR_LOG;
$const["TELEGRAM_DAEMON_LOG_FILE"] = TELEGRAM_DAEMON_LOG_FILE;
$const["TELEGRAM_DAEMON_PID_FILE"] = TELEGRAM_DAEMON_PID_FILE;

$const["TELEGRAM_DAEMON_LOGGER_WORKERS"] = TELEGRAM_DAEMON_LOGGER_WORKERS;
$const["TELEGRAM_DAEMON_RESPONDER_WORKERS"] = TELEGRAM_DAEMON_RESPONDER_WORKERS;
$const["TELEGRAM_DAEMON_MASTER_BA"] = TELEGRAM_DAEMON_MASTER_BA;
$const["TELEGRAM_MUTEXES_LOCK_DIR"] = TELEGRAM_MUTEXES_LOCK_DIR;

is_dir(TELEGRAM_STORAGE_PATH) or mkdir(TELEGRAM_STORAGE_PATH, 0755);
if (!file_exists(TELEGRAM_STORAGE_PATH."/.gitignore")) {
  file_put_contents(TELEGRAM_STORAGE_PATH."/.gitignore", $ignoreAll);
}

$config = [
  "const" => &$const,
  "target_file" => __DIR__."/telegram_bot.php"
];

(new \ConfigBuilder\ConfigBuilder($config))->build();