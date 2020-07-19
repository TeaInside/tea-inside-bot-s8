<?php

require __DIR__."/bootstrap/telegram/autoload.php";

$json = '{
    "update_id": 345849882,
    "message": {
        "message_id": 2700,
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
        "date": 1595124711,
        "text": "/debug",
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
