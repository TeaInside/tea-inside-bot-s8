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
 * @package \TeaBot\Telegram\PrivateLogger
 * @version 8.0.0
 */
class PrivateLogger extends LoggerFoundation
{
  /**
   * @param string
   * @return bool
   */
  public function execute(): bool
  {
    $data = $this->data;

    /*
     * Get $userId from database first.
     *
     * Important Note:
     * - $userId IS not chat/user ID that comes from Telegram.
     * - It is ID from database auto increment.
     */
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

      $needToSaveMsg = true;
      $msgId = self::touchMessage($userId, $data, $needToSaveMsg, $teaBot);

      /*debug:7*/
      var_dump("need_to_save_msg: ".($needToSaveMsg ? "t" : "f"));
      /*enddebug*/

      if ($needToSaveMsg) {
        /*
         * Save the message data after touch the message info.
         */
        switch ($this->data["msg_type"]) {

          case "text":
            self::saveTextMessage($msgId, $data);
            break;

          case "photo":
            self::savePhotoMessage($msgId, $data);
            break;

          case "video":
            break;
        }

        /* ($type = 1) means private_msg_count */
        self::incrementUserMsgCount($userId, $type = 1);
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
   * @param int                     $userId
   * @param \TeaBot\Telegram\Data   $data
   * @param bool                    &$needToSaveMsg
   * @param \TeaBot\Telegram\TeaBot $teaBot
   * @return int
   */
  public static function touchMessage(
    int $userId,
    Data $data,
    bool &$needToSaveMsg,
    ?TeaBot $teaBot = null
  ): int
  {
    $pdo = DB::pdo();
    $needToSaveMsg = true;

    /*
     * Check whether the tg_msg_id has already
     * been stored in database or not.
     */
    $st = $pdo->prepare("SELECT `id`,`has_edited_msg` FROM `tg_private_messages` WHERE `user_id` = ? AND `tg_msg_id` = ?");
    $st->execute([$userId, $data["msg_id"]]);

    /*debug:7*/
    var_dump("execute data:");
    var_dump([$userId, $data["msg_id"]]);
    /*enddebug*/

    /*
     * If the message has already been stored
     * in database, it may be an edited message.
     */
    if ($u = $st->fetch(PDO::FETCH_ASSOC)) {

      /*debug:7*/
      var_dump("fetching old msg...");
      var_dump($u);
      /*enddebug*/

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

      if (isset($data["handle_replied_msg"])) {
        $needToSaveMsg = false;
      }

    } else {

      /*debug:7*/
      var_dump("inserting new msg...");
      var_dump($u);
      /*enddebug*/

      /*
       * Handle message that has not been stored
       * in database.
       */

      $pdo->prepare("INSERT INTO `tg_private_messages` (`user_id`, `tg_msg_id`, `reply_to_tg_msg_id`, `msg_type`, `has_edited_msg`, `is_forwarded_msg`, `tg_date`, `created_at`) VALUES (?, ?, ?, ?, '0', ?, ?, NOW())")
      ->execute(
        [
          $userId,
          $data["msg_id"],
          $data["reply_to"]["message_id"] ?? null,
          $data["msg_type"],
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
    }

    return $msgId;
  }

  /**
   * @param int                   $msgId
   * @param \TeaBot\Telegram\Data $data
   * @return bool
   */
  public static function saveTextMessage(int $msgId, Data $data)
  {
    /*
     * Save the text message.
     */
    return DB::pdo()->prepare("INSERT INTO `tg_private_message_data` (`msg_id`, `text`, `text_entities`, `file`, `is_edited`, `tg_date`, `created_at`) VALUES (?, ?, ?, NULL, ?, ?, NOW())")
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
  }

  /**
   * @param int                   $msgId
   * @param \TeaBot\Telegram\Data $data
   * @return bool
   */
  public static function savePhotoMessage(int $msgId, Data $data): bool
  {

    /*
     * Take the latest index of the array.
     * It should give the best resolution.
     */
    $tgFileId = $data["photo"][count($data["photo"]) - 1]["file_id"];

    return DB::pdo()->prepare("INSERT INTO `tg_private_message_data` (`msg_id`, `text`, `text_entities`, `file`, `is_edited`, `tg_date`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, NOW())")
    ->execute(
      [
        $msgId,
        $data["text"],
        json_encode($data["text_entities"]),
        static::fileResolve($tgFileId),
        $data["is_edited_msg"] ? 1 : 0,
        (
          isset($data["date"]) ?
          date("Y-m-d H:i:s", $data["date"]) :
          null
        )
      ]
    );
  }
}