<?php

namespace TeaBot\Telegram\Loggers;

use DB;
use PDO;
use Error;
use PDOException;
use TeaBot\Telegram\Data;
use TeaBot\Telegram\TeaBot;
use TeaBot\Telegram\Logger;
use TeaBot\Telegram\LoggerFoundation;
use TeaBot\Telegram\Contracts\LoggerInterface;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Loggers
 * @version 8.0.0
 */
class GroupLogger extends LoggerFoundation
{
  /** 
   * @param \PDO   $data
   * @param array  $vars
   * @return array
   */
  public static function getCompulsoryIds(PDO $pdo, array $vars): array
  {
    /*
     * Get $groupId and $userId from database first.
     *
     * Important Note:
     * - $groupId and $userId are not chat/user ID that comes from Telegram.
     * - They are ID from database auto increment.
     */
    $data = $vars["data"];
    return [
      "groupId" => self::groupInsert(
        [
          "tg_group_id" => $data["chat_id"],
          "name" => $data["chat_title"],
          "username" => $data["chat_username"],
          "msg_count" => 0
        ]
      ),
      "userId" => self::userInsert(
        [
          "tg_user_id" => $data["user_id"],
          "first_name" => $data["first_name"],
          "last_name" => $data["last_name"],
          "username" => $data["username"],
          "is_bot" => $data["is_bot"] ? 1 : 0,
          "group_msg_count" => 0
        ]
      )
    ];
  }
}
