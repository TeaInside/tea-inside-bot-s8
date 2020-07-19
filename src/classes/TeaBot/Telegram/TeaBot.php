<?php

namespace TeaBot\Telegram;

use DB;
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
   * Destructor.
   */
  public function __destruct()
  {
    DB::close();
  }

  /**
   * @return void
   */
  public function run(): void
  {
    go(function () {
      // Run the logger.
      $logger = new Logger($this->data);
      $logger->run();
      DB::close();
    });

    $res = new Response($this->data);
    $res->execRoutes();
  }

  /**
   * @param \Error $e
   */
  public function errorReport(Error $e)
  {
    $now = date("c");
    $strInput = json_encode($this->data->in, JSON_UNESCAPED_SLASHES);
    $inputHash = sha1($strInput);

    $strErr = "{$now}\n[error:{$inputHash}]\n".
      $e->__toString();

    $inputData = "[input_data:{$inputHash}]\n".
      base64_encode(gzencode($strInput, 9));


    /* Write to server error log. */
    file_put_contents(STORAGE_PATH."/daemon_error.log",
      "{$strErr}\n{$inputData}\n\n", FILE_APPEND | LOCK_EX);

    try {
      $this->errorSendReport($strErr, $inputData);
    } catch (Error $e2) {

      /* In case the reporter also error. */
      $now = date("c");
      file_put_contents(STORAGE_PATH."/daemon_error.log",
        "{$now}\n[Reporter Error]\n{$e2}\n\n", FILE_APPEND | LOCK_EX);
    }
  }

  /**
   * @param string $strErr
   * @param string $inputData
   * @return void
   */
  private function errorSendReport(string $strErr, string $inputData): void
  {
    if (is_array(TELEGRAM_ERROR_REPORT_CHAT_ID)) {
      foreach (TELEGRAM_ERROR_REPORT_CHAT_ID as $chatId) {
        $v = json_decode(
          Exe::sendMessage(
            [
              "chat_id" => $chatId,
              "text" => $strErr
            ]
          )->getBody()->__toString(),
          true
        );

        Exe::sendMessage(
          [
            "chat_id" => $chatId,
            "text" => $inputData,
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
          "reply_to_message_id" => $v["result"]["message_id"]
        ]
      );
    }
  }
}
