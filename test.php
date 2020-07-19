<?php

require __DIR__."/bootstrap/telegram/autoload.php";

$json = '{
    "update_id": 345849923,
    "message": {
        "message_id": 2781,
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
        "date": 1595133700,
        "reply_to_message": {
            "message_id": 2765,
            "from": {
                "id": 466965678,
                "is_bot": true,
                "first_name": "Tea Inside",
                "username": "TeaInsideBot"
            },
            "chat": {
                "id": -1001149709623,
                "title": "Test Driven Development",
                "type": "supergroup"
            },
            "date": 1595129958,
            "text": "{\n    \"update_id\": 345849899,\n    \"message\": {\n        \"message_id\": 2752,\n        \"from\": {\n            \"id\": 243692601,\n            \"is_bot\": false,\n            \"first_name\": \"Ammar\",\n            \"last_name\": \"Faizi\",\n            \"username\": \"ammarfaizi2\",\n            \"language_code\": \"en\"\n        },\n        \"chat\": {\n            \"id\": -1001149709623,\n            \"title\": \"Test Driven Development\",\n            \"type\": \"supergroup\",\n            \"username\": null\n        },\n        \"date\": 1595128514,\n        \"forward_from\": {\n            \"id\": 448907482,\n            \"is_bot\": true,\n            \"first_name\": \"Ice Tea\",\n            \"username\": \"MyIceTea_Bot\"\n        },\n        \"forward_date\": 1595128509,\n        \"text\": \"/debug\",\n        \"entities\": [\n            {\n                \"offset\": 0,\n                \"length\": 6,\n                \"type\": \"bot_command\"\n            }\n        ],\n        \"reply_to_message\": null\n    }\n}",
            "entities": [
                {
                    "offset": 0,
                    "length": 935,
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
