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
   * @param string
   * @return bool
   */
  public function execute(): bool
  {
    $data = $this->data;

    /*
     * Get $groupId and $userId from database first.
     *
     * Important Note:
     * - $groupId and $userId are not chat/user ID that comes from Telegram.
     * - They are ID from database auto increment.
     */
    $groupId = self::groupInsert(
      [
        "tg_group_id" => $data["chat_id"],
        "name" => $data["chat_title"],
        "username" => $data["chat_username"],
        "msg_count" => (isset($data["in"]["not_edit_event"]) ? 0 : 1)
      ]
    );

    /*debug:2*/
    var_dump("got groupId: ".$groupId);
    /*enddebug*/

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

    /*debug:2*/
    var_dump("got userId: ".$userId);
    /*enddebug*/


    $pdo = DB::pdo();
    $teaBot = $this->logger->teaBot ?? null;

    /*debug:5*/
    $cid = \Swoole\Coroutine::getCid();
    /*enddebug*/

    /*
     * If the message is supposed to reply another message,
     * we need to keep track the replied message first.
     */
    if (isset($data["reply_to"]["message_id"])) {
      if (!(new Logger($teaBot, Data::buildMsg($data["reply_to"])))->run()) {
        /*
         * Failed to store replied message.
         *
         * Don't log here, the error log and report has been
         * sent through that subroutine.
         */
        return false;
      }
    }

    try {
      /*debug:5*/
      var_dump("beginTransaction: ".$cid);
      /*enddebug*/

      $pdo->beginTransaction();

      /*debug:5*/
      var_dump("beginTransaction OK: ".$cid);
      /*enddebug*/


      $msgId = self::touchMessage($groupId, $userId, $data);

      /*
       * Save the message data after touch the message info.
       */
      switch ($this->data["msg_type"]) {

        case "text":
          self::saveTextMessage($msgId, $data);
          break;

        case "photo":
          break;

        case "video":
          break;
      }

      /*debug:5*/
      var_dump("commit: ".$cid);
      /*enddebug*/

      $pdo->commit();

    } catch (PDOException $e) {
      /*debug:5*/
      var_dump("rollback: ".$cid);
      var_dump($e."");
      /*enddebug*/

      $pdo->rollBack();
      $teaBot and $teaBot->errorReport($e);
      return false;
    } catch (Error $e) {
      /*debug:5*/
      var_dump("rollback: ".$cid);
      var_dump($e."");
      /*enddebug*/

      $pdo->rollBack();
      $teaBot and $teaBot->errorReport($e);
      return false;
    }

    return true;
  }

  /**
   * @param int                     $groupId
   * @param int                     $userId
   * @param \TeaBot\Telegram\Data   $data
   * @param \TeaBot\Telegram\TeaBot $teaBot
   * @return int
   */
  private static function touchMessage(
    int $groupId, int $userId, Data $data, ?TeaBot $teaBot = null): int
  {
    $pdo = DB::pdo();

    /*
     * Check whether the tg_msg_id has already
     * been stored in database or not.
     */
    $st = $pdo->prepare("SELECT `id`,`has_edited_msg` FROM `tg_group_messages` WHERE `group_id` = ? AND `tg_msg_id` = ?");
    $st->execute([$groupId, $data["msg_id"]]);

    /*
     * If the message has already been stored
     * in database, it may be an edited message.
     */
    if ($u = $st->fetch(PDO::FETCH_ASSOC)) {

      $msgId = (int)$u["id"];

      /*
       * In case forwarded message gets edited.
       *
       * (It may be impossible in Telegram).
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

      /*
       * Handle message that has not been stored
       * in database.
       */

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


      /*
       * If it is forwarded message, we should keep
       * track the forward message information.
       */
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

      if (isset($data["in"]["not_edit_event"])) {
        /* ($type === 2) means group_msg_count */
        self::incrementUserMsgCount($userId, $type = 2);
        self::incrementGroupMsgCount($groupId);
      }
    }

    return $msgId;
  }

  /**
   * @param int                   $msgId
   * @param \TeaBot\Telegram\Data $data
   * @return bool
   */
  public static function saveTextMessage(int $msgId, Data $data): bool
  {
    /*
     * Save the text message.
     */
    return DB::pdo()->prepare("INSERT INTO `tg_group_message_data` (`msg_id`, `text`, `text_entities`, `file`, `is_edited`, `tg_date`, `created_at`) VALUES (?, ?, ?, NULL, ?, ?, NOW())")
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
  }
}
