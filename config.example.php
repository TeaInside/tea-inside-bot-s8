<?php

defined("BASEPATH") or define("BASEPATH", __DIR__);
define("STORAGE_PATH", BASEPATH."/storage");
define("PUBLIC_DIR", BASEPATH."/public");


define("RUN_USER", "nobody");
define("RUN_GROUP", "nogroup");

/* Telegram config */
define("TG_BOT_TOKEN", "bot token");
define("TG_BOT_DB_HOST", "127.0.0.1");
define("TG_BOT_DB_USER", "user");
define("TG_BOT_DB_PASS", "password");
define("TG_BOT_DB_PORT", 3306);
define("TG_BOT_DB_NAME", "teabot8");
define("TG_BOT_SUDOERS", [243692601]);
define("TG_STORAGE_PATH", BASEPATH."/storage/telegram");
define("TG_WEBHOOK_KEY", "123123123123123123");
define("TG_ERROR_REPORT_CHAT_ID", []);
define("TG_DAEMON_ERROR_LOG_FILE", STORAGE_PATH."/logs/telegram/error.log");
define("TG_DAEMON_NOTICE_LOG_FILE", STORAGE_PATH."/logs/telegram/notice.log");
define("TG_DAEMON_PID_FILE", STORAGE_PATH."/pids/tg_daemon.pid");





define("CALCULUS_API_KEY", "8e7eaa2822cf3bf77a03d63d2fbdeb36df0a409f");
