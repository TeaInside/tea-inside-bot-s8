<?php

require __DIR__."/bootstrap/telegram/autoload.php";

$json = '{
    "update_id": 345860270,
    "message": {
        "message_id": 82075,
        "from": {
            "id": 243692601,
            "is_bot": false,
            "first_name": "Ammar",
            "last_name": "Faizi",
            "username": "ammarfaizi2",
            "language_code": "en"
        },
        "chat": {
            "id": 243692601,
            "first_name": "Ammar",
            "last_name": "Faizi",
            "username": "ammarfaizi2",
            "type": "private"
        },
        "date": 1596389711,
        "photo": [
            {
                "file_id": "AgACAgUAAxkBAAEBQJtfJvlP9MZj1xZThUC5ho1ROvAFigACg6oxG-kjOVWT6eGjDaWSP1oB7Wt0AAMBAAMCAANtAANGRQEAARoE",
                "file_unique_id": "AQADWgHta3QAA0ZFAQAB",
                "file_size": 31213,
                "width": 320,
                "height": 320
            },
            {
                "file_id": "AgACAgUAAxkBAAEBQJtfJvlP9MZj1xZThUC5ho1ROvAFigACg6oxG-kjOVWT6eGjDaWSP1oB7Wt0AAMBAAMCAAN5AANERQEAARoE",
                "file_unique_id": "AQADWgHta3QAA0RFAQAB",
                "file_size": 95807,
                "width": 960,
                "height": 960
            },
            {
                "file_id": "AgACAgUAAxkBAAEBQJtfJvlP9MZj1xZThUC5ho1ROvAFigACg6oxG-kjOVWT6eGjDaWSP1oB7Wt0AAMBAAMCAAN4AANHRQEAARoE",
                "file_unique_id": "AQADWgHta3QAA0dFAQAB",
                "file_size": 109637,
                "width": 800,
                "height": 800
            }
        ],
        "caption": "test qweqwe"
    }
}';


loadConfig("telegram/telegram_bot");

go(function () use ($json) {
  $saber = \Swlib\Saber::create(
   [
    "base_uri" => "http://127.0.0.1:8000",
    "headers" => ["Content-Type" => \Swlib\Http\ContentType::JSON]
   ]
  );
  $ret = $saber->post(
    "/webhook/telegram/r1.php?key=".TELEGRAM_WEBHOOK_KEY,
    json_decode($json, true)
  );
  echo $ret->getBody()->__toString()."\n";
});
