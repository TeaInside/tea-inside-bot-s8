<?php

namespace TeaBot\Telegram\Loggers;

use DB;
use TeaBot\Telegram\LoggerFoundation;
use TeaBot\Telegram\Contracts\LoggerInterface;
use TeaBot\Telegram\Exceptions\LoggerException;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Loggers
 * @version 8.0.0
 */
class PrivateLogger extends LoggerFoundation implements LoggerInterface
{

  /**
   * @const array
   */
  const USER_INSERT_MANDATORY_FIELDS = [
    "tg_user_id",
    "username",
    "first_name",
    "last_name",
    "is_bot"
  ];

  /**
   * @const array
   */
  const USER_INSERT_DEFAULT_VALUES = [
    "photo" => null,
    "group_msg_count" => 0,
    "private_msg_count" => 0
  ];

  /**
   * @param array $data
   * @return void
   * @throws \TeaBot\Telegram\Exceptions\LoggerException
   */
  public static function userInsert(array $data): void
  {

    foreach (self::USER_INSERT_MANDATORY_FIELDS as $v) {
      if (!isset($data[$v])) {
        throw new LoggerException(
          "Invalid data to be inserted (missing mandatory fields): "
          .json_encode($data));
      }
    }

    foreach (self::USER_INSERT_DEFAULT_VALUES as $k => $v) {
      isset($data[$k]) or $data[$k] = $v;
    }

    $query = 
"INSERT INTO `tg_users` (`tg_user_id`,`username`,`first_name`,`last_name`,`photo`,`group_msg_count`,`private_msg_count`,`is_bot`,`created_at`)
VALUES (:tg_user_id, :username, :first_name, :last_name, :photo, :group_msg_count, :private_msg_count, :is_bot, NOW())
ON DUPLICATE KEY UPDATE
`username` = :username,
`first_name` = :first_name,
`last_name` = :last_name,
";

    if ($data["group_msg_count"] != 0) {
      $query .= "`group_msg_count` = `group_msg_count` + 1";
    } else
    if ($data["private_msg_count"] != 0) {
      $query .= "`private_msg_count` = `private_msg_count` + 1";
    }

    $query .= "`photo` = :photo;";

    $pdo = DB::pdo();
    $st = $pdo->prepare($query);
    $st->execute($data);

    $numRows = $st->rowCount();
    var_dump($numRows);
    if ($numRows > 0) {

      $data["user_id"] = $pdo->lastInsertId();

      /* Unset unused keys. */
      $data = array_filter($data, function ($k) {
        return in_array($k,
          [
            "user_id", "username", "first_name",
            "last_name", "photo", "created_at"
          ]
        );
      }, ARRAY_FILTER_USE_KEY);

      $pdo
        ->prepare("INSERT INTO `tg_user_history` (`user_id`, `username`, `first_name`, `last_name`, `photo`, `created_at`) VALUES (:user_id, :username, :first_name, :last_name, :photo, NOW());")
        ->execute($data);
    }
  }
}
