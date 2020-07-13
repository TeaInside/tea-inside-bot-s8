<?php

require __DIR__."/bootstrap/telegram/autoload.php";

$json = '{
    "update_id": 345845257,
    "message": {
        "message_id": 2356,
        "from": {
            "id": 243692601,
            "is_bot": false,
            "first_name": "Ammar",
            "last_name": "Faizi",
            "username": "ammarfaizi2",
            "language_code": "en"
        },
        "chat": {
            "id": -1001149709623,
            "title": "Test Driven Development",
            "type": "supergroup",
            "username": null
        },
        "date": 1594647860,
        "text": "/start",
        "entities": [
            {
                "offset": 0,
                "length": 6,
                "type": "bot_command"
            }
        ],
        "reply_to_message": null
    }
}';


loadConfig("telegram/telegram_bot");


$ch = curl_init(
  "http://127.0.0.1:8000/webhook/telegram/r1.php?key=".TELEGRAM_WEBHOOK_KEY);
curl_setopt_array($ch,
  [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => TRUE,
    CURLOPT_POSTFIELDS => $json
  ]
);
echo curl_exec($ch)."\n";
curl_close($ch);
