<?php

namespace TeaBot\Telegram\LoggerUtils\GroupMessage;

use TeaBot\Telegram\Data;
use TeaBot\Telegram\LoggerUtilFoundation;
use TeaBot\Telegram\Contracts\MessageLogger;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\LoggerUtils\GroupMessage
 * @version 8.0.0
 */
class Text extends LoggerUtilFoundation implements MessageLogger
{
  /**
   * @param int                   $msgId
   * @param \TeaBot\Telegram\Data $data
   * @param string                $dateTime
   * @return bool
   */
  public function saveData(int $msgId, Data $data, string $dateTime): int
  {
    $pdo = $this->pdo;

    $pdo->prepare("INSERT INTO tg_group_message_data (msg_id, `text`, text_entities, file, is_edited, tg_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)")
      ->execute(
        [
          $msgId,
          $data["text"],
          isset($data["text_entities"]) ? json_encode($data["text_entities"]) : null,
          null,
          $data["is_edited_msg"] ? 1 : 0,
          date("Y-m-d H:i:s", $data["date"]),
          $dateTime
        ]
      );

    return (int)$pdo->lastInsertId();
  }
}
