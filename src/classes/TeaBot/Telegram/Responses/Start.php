<?php

namespace TeaBot\Telegram\Responses;

use TeaBot\Telegram\Exe;
use TeaBot\Telegram\ResponseFoundation;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class Start extends ResponseFoundation
{
  /**
   * @return bool
   */
  public function start(): bool
  {
    if ($this->data["chat_type"] === "private") {

      Exe::sendMessage(
        [
          "chat_id" => $this->data["chat_id"],
          "text" => "Send /help to show the command list!",
          "reply_to_message_id" => $this->data["msg_id"]
        ]
      );

    } else {

      $v = json_decode(
        Exe::sendMessage(
          [
            "chat_id" => $this->data["chat_id"],
            "text" => "This command can only be used in private message!",
            "reply_to_message_id" => $this->data["msg_id"]
          ]
        )->getBody()->__toString(),
        true
      );

      if (isset($v["result"]["message_id"])) {
        sleep(5);
        Exe::deleteMessage(
          [
            "chat_id" => $this->data["chat_id"],
            "message_id" => $v["result"]["message_id"]
          ]
        );
      }
    }

    return true;
  }
}
