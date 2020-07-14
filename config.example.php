<?php

defined("BASEPATH") or define("BASEPATH", __DIR__);
define("STORAGE_PATH", BASEPATH."/storage");
define("TELEGRAM_BOT_TOKEN", ".... your bot token here ...");
define("TELEGRAM_BOT_DB_HOST", "localhost");
define("TELEGRAM_BOT_DB_USER", "root");
define("TELEGRAM_BOT_DB_PASS", "asdqwe123zxcqwe");
define("TELEGRAM_BOT_DB_PORT", "3306");
define("TELEGRAM_BOT_DB_NAME", "teabot");
define("TELEGRAM_BOT_SUDOERS", [243692601]);
define("TELEGRAM_STORAGE_PATH", BASEPATH."/storage/telegram");
define("TELEGRAM_WEBHOOK_KEY", "xxxxxxxxxxxxxxxxxxx");
define("TELEGRAM_ERROR_REPORT_CHAT_ID", []);
define("TELEGRAM_DAEMON_ERROR_LOG", STORAGE_PATH."/logs/telegram_daemon_error.log");

define("CALCULUS_API_KEY", "xxxxxxxxx");
define("SRABATSROBOT_API_KEY", "xxxxxxxxx");

$ignoreAll = "*\n!.gitignore\n";
