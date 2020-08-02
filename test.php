<?php

require __DIR__."/bootstrap/telegram/autoload.php";

$json = '{
    "update_id": 345860241,
    "message": {
        "message_id": 3726,
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
        "date": 1596389143,
        "photo": [
            {
                "file_id": "AgACAgUAAx0CSR5_bwACDo5fJvcX9VMr0rC0MZb5VoyC-wUN0gACHqoxGzyrOFUyMsILRRuDnyA-aGt0AAMBAAMCAANtAANYpwQAARoE",
                "file_unique_id": "AQADID5oa3QAA1inBAAB",
                "file_size": 23540,
                "width": 320,
                "height": 180
            },
            {
                "file_id": "AgACAgUAAx0CSR5_bwACDo5fJvcX9VMr0rC0MZb5VoyC-wUN0gACHqoxGzyrOFUyMsILRRuDnyA-aGt0AAMBAAMCAAN4AANZpwQAARoE",
                "file_unique_id": "AQADID5oa3QAA1mnBAAB",
                "file_size": 139764,
                "width": 800,
                "height": 449
            },
            {
                "file_id": "AgACAgUAAx0CSR5_bwACDo5fJvcX9VMr0rC0MZb5VoyC-wUN0gACHqoxGzyrOFUyMsILRRuDnyA-aGt0AAMBAAMCAAN5AANapwQAARoE",
                "file_unique_id": "AQADID5oa3QAA1qnBAAB",
                "file_size": 325708,
                "width": 1280,
                "height": 719
            }
        ]
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
