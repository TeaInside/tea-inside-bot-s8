<?php

namespace TeaBot\Telegram\LoggerUtils;

use DB;
use PDO;
use Exception;
use TeaBot\Telegram\Data;
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
   * @param int                   $userId
   * @param int                   $groupId
   * @param \TeaBot\Telegram\Data $data
   * @return void
   */
  public function resolveMessage(int $userId, int $groupId, Data $data): void
  {
    $e     = null;
    $mutex = new Mutex("tg_group_messages", "{$groupId}_{$data["msg_id"]}");
    $mutex->lock();


    $pdo = $this->pdo;

    try {
      $pdo->beginTransaction();

      $this->resolveMessageInternal($userId, $groupId, $data);

      $pdo->commit();
    } catch (Exception $e) {
      $pdo->rollback();
    }

    $mutex->unlock();

    if ($e) {
      echo $e->getMessage(), "\n";
      throw new $e;
    }
  }


  /**
   * @param int                   $userId
   * @param int                   $groupId
   * @param \TeaBot\Telegram\Data $data
   * @return void
   */
  private function resolveMessageInternal(int $userId, int $groupId, Data $data): void
  {
    $pdo = $this->pdo;
    $st  = $pdo->prepare("SELECT id, has_edited_msg FROM tg_group_messages WHERE group_id = ? AND tg_msg_id = ?");
    $st->execute([$groupId, $data["msg_id"]]);

    $dateTime      = date("Y-m-d H:i:s");
    $insertMsgData = false;

    if ($r = $st->fetch(PDO::FETCH_NUM)) {

      /* 
       * Message has already been stored in database,
       * it is probably an edited message.
       */

      /* TODO: Track edited message. */


      $msgId = $r[0];
    } else {

      /* Insert message into database. */
      $msgId         = $this->insertMessage($userId, $groupId, $data, $dateTime);
      $insertMsgData = true;
    }


    if ($insertMsgData) {

      if ($data["is_forwarded_msg"]) {
        $this->saveForwardMsgState($msgId, $data);
      }

      $this->incrementMsgCounter($userId, $groupId);
      $this->insertMessageData($msgId, $data, $dateTime);
    }
  }


  /**
   * @param int $userId
   * @param int $groupId
   * @return void
   */
  private function incrementMsgCounter(int $userId, int $groupId): void
  {
    $pdo = $this->pdo;
    $pdo
      ->prepare("UPDATE tg_users SET group_msg_count = group_msg_count + 1 WHERE id = ?")
      ->execute([$userId]);
    $pdo
      ->prepare("UPDATE tg_groups SET msg_count = msg_count + 1 WHERE id = ?")
      ->execute([$groupId]);
  }


  /**
   * @param int                   $msgId
   * @param \TeaBot\Telegram\Data $data
   * @return void
   */
  private function saveForwardMsgState(int $msgId, Data $data): void
  {
    $user     = new User(DB::pdo());
    $msg      = $data->in["message"];
    $ff       = $msg["forward_from"];
    $userInfo = [
      "username"   => $ff["username"] ?? null,
      "first_name" => $ff["first_name"],
      "last_name"  => $ff["last_name"] ?? null,
      "is_bot"     => $ff["is_bot"] ?? false,
    ];

    $isInsertUser = false;
    $userId       = $user->resolveUser($ff["id"], $userInfo, $isInsertUser);

    unset($user);
    /* TODO: Track forwarder photo. */

    $this
      ->pdo
      ->prepare("INSERT INTO tg_group_message_fwd (user_id, msg_id, tg_forwarded_date) VALUES (?, ?, ?)")
      ->execute([
        $userId,
        $msgId,
        isset($msg["forward_date"]) ? date("Y-m-d H:i:s", $msg["forward_date"]) : null
      ]);
  }


  /**
   * @param int                   $userId
   * @param int                   $groupId
   * @param \TeaBot\Telegram\Data $data
   * @param string                $dateTime
   * @return int
   */
  private function insertMessage(int $userId, int $groupId, Data $data, string $dateTime): int
  {
    $pdo = $this->pdo;

    $pdo->prepare("INSERT INTO tg_group_messages (group_id, user_id, tg_msg_id, reply_to_tg_msg_id, msg_type, has_edited_msg, is_forwarded_msg, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
      ->execute(
        [
          $groupId,
          $userId,
          $data["msg_id"],
          $data["reply_to"]["message_id"] ?? null,
          $data["msg_type"],
          $data["is_edited_msg"] ? 1 : 0,
          $data["is_forwarded_msg"] ? 1 : 0,
          $dateTime,
        ]
      );

    return (int)$pdo->lastInsertId();
  }


  /**
   * @const array
   */
  private const MAP_INSERT_CLASS = [
    "text" => \TeaBot\Telegram\LoggerUtils\GroupMessage\Text::class
  ];


  /**
   * @param int                   $msgId
   * @param \TeaBot\Telegram\Data $data
   * @param string                $dateTime
   * @return bool
   */
  private function insertMessageData(int $msgId, Data $data, string $dateTime): bool
  {
    if ($class = self::MAP_INSERT_CLASS[$data["msg_type"]] ?? null) {

      $class     = new $class($this->pdo);
      $msgDataId = $class->saveData($msgId, $data, $dateTime);

      /* TODO: Prepare pending callback here, such as download photo, video, etc. */

      return true;
    }

    return false;
  }
}
