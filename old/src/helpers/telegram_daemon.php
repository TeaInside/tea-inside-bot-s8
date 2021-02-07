<?php

/**
 * @param array  $data
 * @param string $forwardBaseUrl
 * @param string $forwardPath
 * @return void
 */
function payload_forwarder(array $data, string $forwardBaseUrl, string $forwardPath): void
{
  try {
    \Swlib\Saber::create(
      [
        "base_uri" => $forwardBaseUrl,
        "headers"  => ["Content-Type" => \Swlib\Http\ContentType::JSON],
        "timeout"  => 600,
      ]
    )->post($forwardPath, $data);
  } catch (Throwable $e) {
    echo "{$e}\n";
    telegram_daemon_error_report(
      $e, "payload_forwarder: ".json_encode($data, JSON_UNESCAPED_SLASHES));
  }
}


/**
 * @param  mixed  $e
 * @param  string $rawData
 * @return void
 */
function telegram_daemon_error_report($e, $rawData = null)
{
  $errorMsg  = "======================\n";
  $errorMsg .= date("c") . "\n";
  if (is_callable([$e, "getMessage"])) {
    $errorMsg .= "Error: {$e->getMessage()}\n";
  }

  $errorMsg .= $e->__toString() . "\n";
  if (is_string($rawData)) {
    $errorMsg .= "Data: {$rawData}\n";
  }
  $errorMsg .= "======================\n";

  $handle  = fopen(TELEGRAM_DAEMON_ERROR_LOG, "a");
  flock($handle, LOCK_EX);
  fwrite($handle, $errorMsg);
  flock($handle, LOCK_UN);
  fclose($handle);

  if (defined("TELEGRAM_ERROR_REPORT_CHAT_ID")) {
    if (is_array(TELEGRAM_ERROR_REPORT_CHAT_ID)) {
      foreach (TELEGRAM_ERROR_REPORT_CHAT_ID as $k => $chatId) {
        send_error_log_to_telegram($chatId, $errorMsg);
      }
    } else {
      send_error_log_to_telegram(TELEGRAM_ERROR_REPORT_CHAT_ID, $errorMsg);
    }
  }
}

/**
 * @param int     $chatId
 * @param string  $errorMsg
 * @return void
 */
function send_error_log_to_telegram(int $chatId, string $errorMsg): void
{
  $errorMsg = str_split($errorMsg, 4096);
  foreach ($errorMsg as $msg) {
    $ret = \TeaBot\Telegram\Exe::sendMessage([
      "chat_id"             => $chatId,
      "text"                => $msg,
      "reply_to_message_id" => $j["result"]["message_id"] ?? null
    ]);

    $j = json_decode($r = $ret->getBody()->__toString(), true);
  }
}
