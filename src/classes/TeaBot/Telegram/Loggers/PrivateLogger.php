<?php

namespace TeaBot\Telegram\Loggers;

use DB;
use PDO;
use TeaBot\Telegram\Data;
use TeaBot\Telegram\Logger;
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
    self::touchTextMessage($this->data);
  }

  /**
   * @param \TeaBot\Telegram\Data $data
   * @return void
   */
  public static function touchTextMessage(Data $data): void
  {
    $userId = self::userInsert(
      [
        "tg_user_id" => $data["user_id"],
        "username" => $data["username"],
        "first_name" => $data["first_name"],
        "last_name" => $data["last_name"],
        "is_bot" => $data["is_bot"] ? 1 : 0,
        "private_msg_count" => (isset($data["in"]["not_edit_event"]) ? 0 : 1)
      ]
    );

    $pdo = DB::pdo();

    if (isset($data["reply_to"]["message_id"])) {
      (new Logger(Data::buildMsg($data["reply_to"])))->run();
    }

    /**
     * Check whether the tg_msg_id has already
     * been stored in database or not.
     */
    $st = $pdo->prepare("SELECT `id`,`has_edited_msg` FROM `tg_private_messages` WHERE `tg_msg_id` = ? AND `user_id` = ?");
    $st->execute([$data["msg_id"], $data["user_id"]]);

    if ($u = $st->fetch(PDO::FETCH_ASSOC)) {
      /**
       * In case forwarded message gets edited.
       * It may be impossible in Telegram.
       */
      if ($data["is_forwarded_msg"]) {
        $ff = $data["msg"]["forward_from"];
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

      if (!isset($data["in"]["not_edit_event"])) {
        /* TODO: Save edited message here... */
      }

    } else {

      /* Insert new message. */
      $pdo->prepare("INSERT INTO `tg_private_messages` (`user_id`, `tg_msg_id`, `reply_to_tg_msg_id`, `msg_type`, `has_edited_msg`, `is_forwarded_msg`, `tg_date`, `created_at`) VALUES (?, ?, ?, 'text', '0', ?, ?, NOW())")
      ->execute(
        [
          $userId,
          $data["msg_id"],
          $data["reply_to"]["message_id"] ?? null,
          $data["is_forwarded_msg"] ? 1 : 0,
          date("Y-m-d H:i:s", $data["date"])
        ]
      );

      $msgId = $pdo->lastInsertId();

      /* Store forward message info. */
      if ($data["is_forwarded_msg"]) {
        $ff = $data["msg"]["forward_from"];
        $forwarderUserId = self::userInsert(
          [
            "tg_user_id" => $ff["id"],
            "username" => $ff["username"] ?? null,
            "first_name" => $ff["first_name"],
            "last_name" => $ff["last_name"] ?? null,
            "is_bot" => $ff["is_bot"] ? 1 : 0
          ]
        );

        $pdo->prepare("INSERT INTO `tg_private_message_fwd` (`user_id`, `msg_id`, `tg_forwarded_date`) VALUES (?, ?, ?);")
        ->execute(
          [
            $forwarderUserId,
            $msgId,
            (
              isset($data["msg"]["forward_date"]) ?
              date("Y-m-d H:i:s", $data["msg"]["forward_date"]) :
              null
            )
          ]
        );
      }

      /* Store message data. */
      $pdo->prepare("INSERT INTO `tg_private_message_data` (`msg_id`, `text`, `text_entities`, `file`, `is_edited`, `tg_date`, `created_at`) VALUES (?, ?, ?, NULL, ?, ?, NOW())")
      ->execute(
        [
          $msgId,
          $data["text"],
          json_encode($data["text_entities"]),
          $data["is_edited_msg"] ? 1 : 0,
          (
            isset($data["date"]) ?
            date("Y-m-d H:i:s", $data["date"]) :
            null
          )
        ]
      );
      if (isset($data["in"]["not_edit_event"])) {
        self::incrementUserMsgCount($userId, 1);
      }
    }
  }
}
