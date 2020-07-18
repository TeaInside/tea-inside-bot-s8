<?php

namespace TeaBot\Telegram\Loggers;

use DB;
use PDO;
use TeaBot\Telegram\LoggerFoundation;
use TeaBot\Telegram\Contracts\LoggerInterface;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Loggers
 * @version 8.0.0
 */
class PrivateLogger extends LoggerFoundation implements LoggerInterface
{

  /**
   * @return void
   */
  public function logText(): void
  {
    $userId = self::userInsert(
      [
        "tg_user_id" => $this->data["user_id"],
        "username" => $this->data["username"],
        "first_name" => $this->data["first_name"],
        "last_name" => $this->data["last_name"],
        "is_bot" => $this->data["is_bot"] ? 1 : 0,
        "private_msg_count" => 1
      ]
    );

    
  }

}
