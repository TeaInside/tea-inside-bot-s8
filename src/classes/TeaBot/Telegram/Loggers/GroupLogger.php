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

    /**
     * First check whether the tg_msg_id has
     * already been stored in database or not.
     */
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT `id`,`has_edited_msg` FROM `tg_group_messages` WHERE `group_id` = ? AND `tg_msg_id` = ?");
    $st->execute([$groupId, $this->data["msg_id"]]);

    if ($u = $st->fetch(PDO::FETCH_ASSOC)) {
      
    } else {
      $pdo->prepare("INSERT INTO `tg_group_messages` (`group_id`, `user_id`, `tg_msg_id`, `reply_to_tg_msg_id`, `msg_type`, `has_edited_msg`, `is_forwarded_msg`, `tg_date`, `created_at`) VALUES (?, ?, ?, ?, 'text', ?, ?, ?, NOW())")
        ->execute(
          [
            $groupId,
            $this->data["user_id"],
            $this->data["msg_id"],
            $this->data["reply_to"]["message_id"] ?? null,
            $this->data["is_edited_msg"] ? 1 : 0,
            $this->data["is_forwarded_msg"] ? 1 : 0,
            date("Y-m-d H:i:s", $this->data["date"])
          ]
        );
    }
  }
}
