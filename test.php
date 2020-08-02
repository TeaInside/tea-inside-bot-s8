<?php

require __DIR__."/bootstrap/telegram/autoload.php";

$json = '{
    "update_id": 345860161,
    "edited_message": {
        "message_id": 3720,
        "from": {
            "id": 243692601,
            "is_bot": false,
            "first_name": "Ammar",
            "last_name": "Faizi",
            "username": "ammarfaizi2",
            "language_code": "en"
        },
        "chat": {
            "id": -1001226735471,
            "title": "Private Cloud",
            "type": "supergroup"
        },
        "date": 1596386747,
        "edit_date": 1596386750,
        "text": "test edit"
    }
}
';


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
