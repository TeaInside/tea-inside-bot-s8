<?php

namespace TeaBot\Telegram\Loggers;

use DB;
use PDO;
use Error;
use stdClass;
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
    $ret    = true;
    $data   = $this->data;
    $teaBot = $this->logger->teaBot ?? null;

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

    /*
     * Error callback.
     */
    $errCallback = function (PDO $pdo, $e) use ($teaBot, &$ret): bool {
      $teaBot and $teaBot->errorReport($e);
      return ($ret = false);
    };

    $vars = self::getCompulsoryIds($data);
    $vars["data"] = $data;
    $vars["teaBot"] = $teaBot;
    $vars["saveMsgData"] = new stdClass;
    $vars["saveMsgData"]->state = false;

    $trx = DB::transaction([$this, "saveMessageCallback"], $vars);
    /*debug:7*/
    $trx->setName("saveMessageCallback");
    /*enddebug*/
    $trx->setErrorCallback($errCallback);
    $trx->setDeadlockTryCount(20);
    $trx->setTrySleep(rand(1, 20));

    if (!$trx->execute()) {
      return false;
    }

    $vars["msgId"] = $trx->getRetVal();

    if ($vars["saveMsgData"]->state) {
      /*
       * Save the message data after touch the message info.
       */

      /*debug:7*/
      $name = "~~no_op_save_msg~~";
      /*endebug*/
      $callback = function () { return true; };
      $beforeCallback = function () {};
      switch ($this->data["msg_type"]) {

        case "text":
          /*debug:7*/
          $name = "saveTextMessage";
          /*endebug*/
          $callback = [self::class, "saveTextMessage"];
          break;

        case "photo":
          /*debug:7*/
          $name = "savePhotoMessage";
          /*endebug*/
          $callback = [self::class, "savePhotoMessage"];
          $beforeCallback = function (array &$vars) {
            /* TODO: Download the photo here. */
            /* This must not be in transaction. */
          };
          break;

        default:
          break;
      }

      $trx = DB::transaction(function (PDO $pdo, array &$vars) use ($callback) {
        $ret = $callback($pdo, $vars);

        /* ($type = 2) means `group_msg_count` */
        self::incrementUserMsgCount($vars["userId"], $type = 2);
        self::incrementGroupMsgCount($vars["groupId"]);

        return $ret;
      }, $vars);
      /*debug:7*/
      $trx->setName($name);
      /*enddebug*/
      $trx->setBeforeCallback($beforeCallback);
      $trx->setErrorCallback($errCallback);
      $trx->setDeadlockTryCount(20);
      $trx->setTrySleep(rand(1, 20));
      if (!$trx->execute()) {
        return false;
      }
    }

    return $ret;
  }

  /** 
   * @param TeaBot\Telegram\Data $data
   * @return array
   */
  public static function getCompulsoryIds(Data $data): array
  {
    /*
     * Get $groupId and $userId from database first.
     *
     * Important Note:
     * - $groupId and $userId are not chat/user ID that comes from Telegram.
     * - They are ID from database auto increment.
     */
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

  /**
   * @param \PDO  $pdo
   * @param array $vars
   * @return bool
   */
  public static function saveMessageCallback(PDO $pdo, array $vars): bool
  {
    /*debug:7*/
    DB::mustBeInTransaction("saveMessageCallback");
    /*enddebug*/

    extract($vars);
    $saveMsg = true;
    $msgId = self::touchMessage($groupId, $userId, $data, $saveMsg, $teaBot);
    $saveMsgData->state = $saveMsg;

    return true;
  }


  /**
   * @param int                     $groupId
   * @param int                     $userId
   * @param \TeaBot\Telegram\Data   $data
   * @param bool                    &$saveMsg
   * @param \TeaBot\Telegram\TeaBot $teaBot
   * @return int
   */
  private static function touchMessage(
    int $groupId,
    int $userId,
    Data $data,
    bool &$saveMsg,
    ?TeaBot $teaBot = null
  ): int
  {
    /*debug:7*/
    DB::mustBeInTransaction("touchMessage");
    /*enddebug*/

    $pdo = DB::pdo();
    $saveMsg = true;

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

      if (!$u["has_edited_msg"]) {
        $pdo->prepare("UPDATE `tg_group_messages` SET `has_edited_msg` = '1' WHERE `id` = ?")
        ->execute([$msgId]);
      }

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
        $saveMsg = false;
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
    }

    return $msgId;
  }

  /**
   * @param int                   $msgId
   * @param \TeaBot\Telegram\Data $data
   * @return bool
   */
  // int $msgId, Data $data
  public static function saveTextMessage(PDO $pdo, array $vars): bool
  {
    extract($vars);
    /*
     * Save the text message.
     */
    return $pdo->prepare("INSERT INTO `tg_group_message_data` (`msg_id`, `text`, `text_entities`, `file`, `is_edited`, `tg_date`, `created_at`) VALUES (?, ?, ?, NULL, ?, ?, NOW())")
    ->execute(
      [
        $msgId,
        $data["text"],
        json_encode($data["text_entities"], JSON_UNESCAPED_SLASHES),
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
