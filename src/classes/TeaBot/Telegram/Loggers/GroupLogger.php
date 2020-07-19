<?php

namespace TeaBot\Telegram\Loggers;

use TeaBot\Telegram\LoggerFoundation;
use TeaBot\Telegram\Contracts\LoggerInterface;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Loggers
 * @version 8.0.0
 */
class GroupLogger extends LoggerFoundation implements LoggerInterface
{
  /**
   * @return void
   */
  public function logText(): void
  {
    $groupId = self::groupInsert(
      [
        "tg_group_id" => $this->data["chat_id"],
        "name" => $this->data["chat_title"],
        "username" => $this->data["chat_username"]
      ]
    );
  }
}
