<?php

require __DIR__."/bootstrap/telegram/autoload.php";

$json = '';


loadConfig("telegram/telegram_bot");

$ch = curl_init(
  "http://127.0.0.1:8000/webhook/telegram/r1.php?key=".TELEGRAM_WEBHOOK_KEY);
curl_setopt_array($ch,
  [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => TRUE,
    CURLOPT_POSTFIELDS => $jsonData
  ]
);
echo curl_exec($ch)."\n";
curl_close($ch);
