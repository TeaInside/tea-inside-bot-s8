<?php

require __DIR__."/bootstrap/telegram/autoload.php";

$json = '{
    "update_id": 345890870,
    "message": {
        "message_id": 6113,
        "from": {
            "id": 243692601,
            "is_bot": false,
            "first_name": "Ammar",
            "last_name": "Faizi^",
            "username": "ammarfaizi2",
            "language_code": "en"
        },
        "chat": {
            "id": -1001226735471,
            "title": "Private Cloud",
            "type": "supergroup",
            "username": null
        },
        "date": 1599362325,
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
