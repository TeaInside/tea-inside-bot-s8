<?php

ini_set("display_errors", true);

$test = new TeaBot\Telegram\Daemon(
  "127.0.0.1:7777",
  ["abc"],
  ["def"]
);
$test->run();
