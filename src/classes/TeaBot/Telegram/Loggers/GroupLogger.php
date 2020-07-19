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
        "username" => $this->data["chat_username"],
        "msg_count" => 1
      ]
    );

    $userId = self::userInsert(
      [
        "tg_user_id" => $this->data["user_id"],
        "first_name" => $this->data["first_name"],
        "last_name" => $this->data["last_name"],
        "username" => $this->data["username"],
        "is_bot" => $this->data["is_bot"] ? 1 : 0,
        "group_msg_count" => 1
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

      /**
       * In case forwarded message gets edited.
       * It may be impossible in Telegram.
       */
      if ($this->data["is_forwarded_msg"]) {
        $ff = $this->data["msg"]["forward_from"];
        self::userInsert(
          [
            "tg_user_id" => $ff["id"],
            "username" => $ff["username"] ?? null,
            "first_name" => $ff["first_name"],
            "last_name" => $ff["last_name"] ?? null,
            "is_bot" => $ff["is_bot"] ? 1 : 0
          ]
        );
      }

    } else {

      /* Insert new message. */
      $pdo->prepare("INSERT INTO `tg_group_messages` (`group_id`, `user_id`, `tg_msg_id`, `reply_to_tg_msg_id`, `msg_type`, `has_edited_msg`, `is_forwarded_msg`, `tg_date`, `created_at`) VALUES (?, ?, ?, ?, 'text', ?, ?, ?, NOW())")
        ->execute(
          [
            $groupId,
            $userId,
            $this->data["msg_id"],
            $this->data["reply_to"]["message_id"] ?? null,
            $this->data["is_edited_msg"] ? 1 : 0,
            $this->data["is_forwarded_msg"] ? 1 : 0,
            date("Y-m-d H:i:s", $this->data["date"])
          ]
        );

      $msgId = $pdo->lastInsertId();

      /* Store forward message info. */
      if ($this->data["is_forwarded_msg"]) {
        $ff = $this->data["msg"]["forward_from"];
        $forwarderUserId = self::userInsert(
          [
            "tg_user_id" => $ff["id"],
            "username" => $ff["username"] ?? null,
            "first_name" => $ff["first_name"],
            "last_name" => $ff["last_name"] ?? null,
            "is_bot" => $ff["is_bot"] ? 1 : 0
          ]
        );

        $pdo->prepare("INSERT INTO `tg_group_message_fwd` (`user_id`, `msg_id`, `tg_forwarded_date`) VALUES (?, ?, ?)")
          ->execute(
            [
              $forwarderUserId,
              $msgId,
              date("Y-m-d H:i:s", $this->data["msg"]["forward_date"])
            ]
          );
      }

      $pdo->prepare("INSERT INTO `tg_group_message_data` (`msg_id`, `text`, `text_entities`, `file`, `is_edited`, `created_at`) VALUES (?, ?, ?, ?, ?, NOW())")
        ->execute(
          [
            $msgId,
            $this->data["text"],
            json_encode($this->data["text_entities"], JSON_UNESCAPED_SLASHES),
            null, /* file */
            $this->data["is_edited_msg"] ? 1 : 0,
          ]
        );
    }
  }
}
