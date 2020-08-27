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
final class Debug extends ResponseFoundation
{
  /**
   * @return bool
   */
  public function debug(): bool
  {
    Exe::sendMessage(
      [
        "chat_id" => $this->data["chat_id"],
        "text" => 
            "<pre>".
            htmlspecialchars(
              json_encode(
                $this->data["in"],
                JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
              ),
              ENT_QUOTES, "UTF-8"
            ).
            "</pre>",
        "parse_mode" => "HTML",
        "reply_to_message_id" => $this->data["msg_id"]
      ]
    );

    return true;
  }
}
