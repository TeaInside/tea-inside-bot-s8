<?php

namespace TeaBot\Telegram\Loggers;

use TeaBot\Telegram\LoggerFoundation;
use TeaBot\Telegram\LoggerUtils\User;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Loggers
 * @version 8.0.0
 */
class GroupLogger extends LoggerFoundation
{
  /**
   * @return void
   */
  public function run(): void
  {
    $data   = $this->data;
    $user   = new User($this->pdo);
    $userId = $user->resolveUser(
      $data["user_id"],
      [
        "username"        => $data["username"],
        "first_name"      => $data["first_name"],
        "last_name"       => $data["last_name"],
        "group_msg_count" => 1,
        "is_bot"          => $data["is_bot"],
      ]
    );
    var_dump($userId);
  }
}
