<?php

namespace TeaBot\Telegram\Loggers;

use DB;
use PDO;
use PDOException;
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
class GroupLogger extends LoggerFoundation implements LoggerInterface
{
  /**
   * @return void
   */
  public function logText(): void
  {
    $msgId = self::touchMessage($this->data);
  }


  /**
   * @return void
   */
  public function logPhoto(): void
  {
  }


  /**
   * @return void
   */
  public function logSticker(): void
  {
  }


  /**
   * @return void
   */
  public function logAnimation(): void
  {
  }


  /**
   * @return void
   */
  public function logVoice(): void
  {
  }


  /**
   * @return void
   */
  public function logVideo(): void
  {
  }


  /**
   * @param \TeaBot\Telegram\Data $data
   * @return void
   */
  public static function touchMessage(Data $data): void
  {
    try {
      $groupId = self::groupInsert(
        [
          "tg_group_id" => $data["chat_id"],
          "name" => $data["chat_title"],
          "username" => $data["chat_username"],
          "msg_count" => (isset($data["in"]["not_edit_event"]) ? 0 : 1)
        ]
      );

      $userId = self::userInsert(
        [
          "tg_user_id" => $data["user_id"],
          "first_name" => $data["first_name"],
          "last_name" => $data["last_name"],
          "username" => $data["username"],
          "is_bot" => $data["is_bot"] ? 1 : 0,
          "group_msg_count" => (isset($data["in"]["not_edit_event"]) ? 0 : 1)
        ]
      );
      $pdo = DB::pdo();
      $pdo->beginTransaction();
      self::insertMessage($groupId, $userId, $data);
      $pdo->commit();
    } catch (PDOException $e) {
      $pdo->rollBack();
    }
  }

  /**
   * @param int                   $groupId
   * @param int                   $userId
   * @param \TeaBot\Telegram\Data $data
   * @return void
   */
  private static function insertMessage(int $groupId, int $userId, Data $data): void
  {
    if (isset($data["reply_to"]["message_id"])) {
      (new Logger(Data::buildMsg($data["reply_to"])))->run();
    }

    /**
     * Check whether the tg_msg_id has already
     * been stored in database or not.
     */
    $st = $pdo->prepare("SELECT `id`,`has_edited_msg` FROM `tg_group_messages` WHERE `group_id` = ? AND `tg_msg_id` = ?");
    $st->execute([$groupId, $data["msg_id"]]);

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
      $pdo->prepare("INSERT INTO `tg_group_messages` (`group_id`, `user_id`, `tg_msg_id`, `reply_to_tg_msg_id`, `msg_type`, `has_edited_msg`, `is_forwarded_msg`, `tg_date`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())")
        ->execute(
          [
            $groupId,
            $userId,
            $data["msg_id"],
            $data["reply_to"]["message_id"] ?? null,
            $data["msg_type"],
            $data["is_edited_msg"] ? 1 : 0,
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

        $pdo->prepare("INSERT INTO `tg_group_message_fwd` (`user_id`, `msg_id`, `tg_forwarded_date`) VALUES (?, ?, ?)")
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
      $pdo->prepare("INSERT INTO `tg_group_message_data` (`msg_id`, `text`, `text_entities`, `file`, `is_edited`, `tg_date`, `created_at`) VALUES (?, ?, ?, NULL, ?, ?, NOW())")
      ->execute(
        [
          $msgId,
          $data["text"],
          json_encode($data["text_entities"], JSON_UNESCAPED_SLASHES),
          $data["is_edited_msg "] ? 1 : 0,
          (
            isset($data["date"]) ?
            date("Y-m-d H:i:s", $data["date"]) :
            null
          )
        ]
      );

      if (isset($data["in"]["not_edit_event"])) {
        self::incrementUserMsgCount($userId, 2);
        self::incrementGroupMsgCount($groupId);
      }
    }
  }
}
