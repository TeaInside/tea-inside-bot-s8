<?php

require __DIR__."/bootstrap/telegram/autoload.php";

$json = '{
    "update_id": 345849982,
    "message": {
        "message_id": 2793,
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
        "date": 1595141419,
        "reply_to_message": {
            "message_id": 2668,
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
            "date": 1595121822,
            "text": "This command can only be used in private message!"
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

// $json = '{
//     "update_id": 345883499,
//     "message": {
//         "message_id": 5242,
//         "from": {
//             "id": 639393712,
//             "is_bot": false,
//             "first_name": "Ammar",
//             "last_name": "Faizi^",
//             "username": "ammarfaizi2",
//             "language_code": "en"
//         },
//         "chat": {
//             "id": -1001226735471,
//             "title": "Private Cloud",
//             "type": "supergroup",
//             "username": null
//         },
//         "date": 1598704512,
//         "reply_to_message": {
//             "message_id": 5129,
//             "from": {
//                 "id": 639393712,
//                 "is_bot": false,
//                 "first_name": "Vasco De",
//                 "last_name": "Gamma",
//                 "username": "ieralliv"
//             },
//             "chat": {
//                 "id": -1001226735471,
//                 "title": "Private Cloud",
//                 "type": "supergroup"
//             },
//             "date": 1598521697,
//             "new_chat_participant": {
//                 "id": 639393712,
//                 "is_bot": false,
//                 "first_name": "Vasco De",
//                 "last_name": "Gamma",
//                 "username": "ieralliv"
//             },
//             "new_chat_member": {
//                 "id": 639393712,
//                 "is_bot": false,
//                 "first_name": "Vasco De",
//                 "last_name": "Gamma",
//                 "username": "ieralliv"
//             },
//             "new_chat_members": [
//                 {
//                     "id": 639393712,
//                     "is_bot": false,
//                     "first_name": "Vasco De",
//                     "last_name": "Gamma",
//                     "username": "ieralliv"
//                 }
//             ]
//         },
//         "text": "3583084xxx",
//         "entities": [
//             {
//                 "offset": 0,
//                 "length": 6,
//                 "type": "bot_command"
//             }
//         ]
//     }
// }';

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
