<?php

namespace TeaBot\Telegram;

use Error;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class TeaBot
{
  /**
   * @var \TeaBot\Telegram\Data
   */
  private $data;

  /**
   * @param array &$data
   *
   * Constructor.
   */
  public function __construct(array &$data)
  {
    $this->data = new Data($data);
  }

  /**
   * @return void
   */
  public function run(): void
  {
    $res = new Response($this->data);
    $res->execRoutes();
  }

  /**
   * @param \Error $e
   */
  public function errorReport(Error $e)
  {
    try {
      $now = date("c");
      $this->errorReportReal($now, $e);
    } catch (Error $e) {
      // In case the reporter error.
    }

    // Handle real server error log here.
    file_put_contents(
      STORAGE_PATH."/daemon_error.log",
      "[{$now}]\n".
      $e->__toString().
      "\n\n",
      FILE_APPEND | LOCK_EX
    );
  }

  /**
   * @param string $now
   * @param \Error $e
   */
  private function errorReportReal(string $now, Error $e)
  {
    $strInput = json_encode($this->data->in, JSON_UNESCAPED_SLASHES);
    $inputHash = sha1($strInput);
    $strErr =
      "{$now}\n<b>[error:{$inputHash}]</b>\n".
      htmlspecialchars($e->__toString(), ENT_QUOTES, "UTF-8");

    $inputData =
      "{$now}\n<b>[input_data:{$inputHash}]</b>\n".
      htmlspecialchars(
        base64_encode(gzencode($strInput, 9)), ENT_QUOTES, "UTF-8");

    if (is_array(TELEGRAM_ERROR_REPORT_CHAT_ID)) {
      foreach (TELEGRAM_ERROR_REPORT_CHAT_ID as $chatId) {
        $v = json_decode(
          Exe::sendMessage(
            [
              "chat_id" => $chatId,
              "text" => $strErr,
              "parse_mode" => "HTML"
            ]
          )->getBody()->__toString(),
          true
        );
        Exe::sendMessage(
          [
            "chat_id" => $chatId,
            "text" => $inputData,
            "parse_mode" => "HTML",
            "reply_to_message_id" => $v["result"]["message_id"]
          ]
        );
      }
    } else {
      $v = json_decode(
        Exe::sendMessage(
          [
            "chat_id" => TELEGRAM_ERROR_REPORT_CHAT_ID,
            "text" => $strErr
          ]
        )->getBody()->__toString(),
        true
      );
      Exe::sendMessage(
        [
          "chat_id" => $chatId,
          "text" => $inputData,
          "parse_mode" => "HTML",
          "reply_to_message_id" => $v["result"]["message_id"]
        ]
      );
    }
  }
}
