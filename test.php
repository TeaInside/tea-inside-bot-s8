<?php

require __DIR__."/bootstrap/telegram/autoload.php";

$json = '{
    "update_id": 345849528,
    "message": {
        "message_id": 82000,
        "from": {
            "id": 243692601,
            "is_bot": false,
            "first_name": "zxczxc",
            "last_name": "Faizi",
            "username": "ammarfaizi2",
            "language_code": "en"
        },
        "chat": {
            "id": 243692601,
            "first_name": "Ammar",
            "last_name": "Faizi",
            "username": "ammarfaizi2",
            "type": "private",
            "title": null
        },
        "date": 1595064167,
        "reply_to_message": {
            "message_id": 81999,
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
            "date": 1595064163,
            "text": "\ud83d\ude0c\ud83d\ude0c"
        },
        "text": "/start",
        "entities": [
            {
                "offset": 0,
                "length": 6,
                "type": "bot_command"
            }
        ]
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
