<?php

require __DIR__."/bootstrap/telegram/autoload.php";

$json = '{
    "update_id": 345861060,
    "message": {
        "message_id": 4182,
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
            "type": "supergroup",
            "username": null
        },
        "date": 1596461574,
        "reply_to_message": {
            "message_id": 4181,
            "from": {
                "id": 448907482,
                "is_bot": true,
                "first_name": "Ice Tea",
                "username": "MyIceTea_Bot"
            },
            "chat": {
                "id": -1001226735471,
                "title": "Private Cloud",
                "type": "supergroup"
            },
            "date": 1596461565,
            "text": "{\n    \"update_id\": 345861059,\n    \"message\": {\n        \"message_id\": 4180,\n        \"from\": {\n            \"id\": 243692601,\n            \"is_bot\": false,\n            \"first_name\": \"Ammar\",\n            \"last_name\": \"Faizi\",\n            \"username\": \"ammarfaizi2\",\n            \"language_code\": \"en\"\n        },\n        \"chat\": {\n            \"id\": -1001226735471,\n            \"title\": \"Private Cloud\",\n            \"type\": \"supergroup\",\n            \"username\": null\n        },\n        \"date\": 1596461563,\n        \"text\": \"/debug\",\n        \"entities\": [\n            {\n                \"offset\": 0,\n                \"length\": 6,\n                \"type\": \"bot_command\"\n            }\n        ],\n        \"reply_to_message\": null\n    }\n}",
            "entities": [
                {
                    "offset": 0,
                    "length": 719,
                    "type": "pre"
                }
            ]
        },
        "text": "/debug",
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
