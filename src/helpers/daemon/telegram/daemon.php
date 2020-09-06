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

    $errorMsg          = $e->__toString();
    $compressedPayload =
      base64_encode(gzencode(json_encode($data, JSON_UNESCAPED_SLASHES), 9));

    if (defined("TELEGRAM_ERROR_REPORT_CHAT_ID")) {
      if (is_array(TELEGRAM_ERROR_REPORT_CHAT_ID)) {
        foreach (TELEGRAM_ERROR_REPORT_CHAT_ID as $k => $chatId) {
          send_report($chatId, $errorMsg, $compressedPayload);
        }
      } else {
        send_report(TELEGRAM_ERROR_REPORT_CHAT_ID, $errorMsg, $compressedPayload);
      }
    }

  }
}

/**
 * @param string $chatId
 * @param string $errorMsg
 * @param string $compressedPayload
 * @return void
 */
function send_report(string $chatId, string $errorMsg, string $compressedPayload): void
{
  $msg = \TeaBot\Telegram\Exe::sendMessage([
    "chat_id" => $chatId,
    "text"    => $errorMsg
  ]);

  $j = json_decode($j->getBody()->__toString(), true);
  \TeaBot\Telegram\Exe::sendMessage([
    "chat_id" => $chatId,
    "text"    => $compressedPayload,
    "reply_to_message_id" => $j["result"]["message_id"]
  ]);
}
