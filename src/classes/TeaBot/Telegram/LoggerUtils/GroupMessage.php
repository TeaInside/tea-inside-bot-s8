<?php

namespace TeaBot\Telegram\LoggerUtils;

use DB;
use PDO;
use Exception;
use TeaBot\Telegram\Mutex;
use TeaBot\Telegram\LoggerUtilFoundation;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\LoggerUtils
 * @version 8.0.0
 */
class GroupMessage extends LoggerUtilFoundation
{
  /**
   * @param array $msgData
   * @return void
   */
  public function resolveMessage(array $msgData): void
  {
    $mutex = new Mutex("tg_group_messages", "{$msgData["group_id"]}_{$msgData["tg_msg_id"]}");
    $mutex->lock();

    /*debug:5*/
    if (is_array($msgData)) {
      $requiredFields = [
        "group_id",
        "user_id",
        "tg_msg_id",
        "reply_to_tg_msg_id",
        "msg_type",
        "is_edited_msg",
        "is_forwarded_msg",
        "tg_date",
        "reply_to_msg",
      ];
      $missing = [];
      foreach ($requiredFields as $k => $v) {
        if (!array_key_exists($v, $msgData)) {
          $missing[] = $v;
        }
      }
      if (count($missing) > 0) {
        throw new \Error("Missing required fields: ".json_encode($missing));
      }
    }
    /*enddebug*/

    $pdo = $this->pdo;

    try {
      $pdo->beginTransaction();

      $this->resolveMessageInternal($msgData);

      $pdo->commit();
    } catch (Exception $e) {
      $pdo->rollback();
    }

    $mutex->unlock();

    if ($e) {
      throw new $e;
    }
  }


  /**
   * @param array $msgData
   * @return void
   */
  private function resolveMessageInternal(array $msgData): void
  {
    $pdo = $this->pdo;
    $st  = $pdo->prepare("SELECT id, has_edited_msg FROM tg_group_messages WHERE group_id = ? AND tg_msg_id = ?");
    $st->execute([$msgData["group_id"], $msgData["tg_msg_id"]]);

    $dateTime      = date("Y-m-d H:i:s");
    $trackMsgData  = false;

    if ($r = $st->fetch(PDO::FETCH_NUM)) {
      /* 
       * Message has already been stored in database,
       * it is probably an edited message.
       */

      /* TODO: Track edited message. */


      $msgId = $r[0];
    } else {

      /* Insert message into database. */
      $msgId        = $this->insertMessage($msgData, $dateTime);
      $trackMsgData = true;
    }


    if ($trackMsgData) {
      $this->insertMessageData($msgId, $msgData);
    }
  }


  /**
   * @param array  $msgData
   * @param string $dateTime
   * @return int
   */
  private function insertMessage(array $msgData, string $dateTime): int
  {
    $pdo = $this->pdo;

    $pdo->prepare("INSERT INTO tg_group_messages (group_id, user_id, tg_msg_id, reply_to_tg_msg_id, msg_type, has_edited_msg, is_forwarded_msg, tg_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
      ->execute(
        [
          $msgData["group_id"],
          $msgData["user_id"],
          $msgData["tg_msg_id"],
          $msgData["reply_to_tg_msg_id"],
          $msgData["msg_type"],
          $msgData["is_edited_msg"] ? 1 : 0,
          $msgData["is_forwarded_msg"] ? 1 : 0,
          $msgData["tg_date"],
          $dateTime,
        ]
      );

    return (int)$pdo->lastInsertId();
  }
}
