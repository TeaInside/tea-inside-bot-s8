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
class User extends LoggerUtilFoundation
{
  /**
   * @param  int    $tgUserId
   * @param  ?array info
   * @param  ?bool  &$isInsert
   * @return ?int
   */
  public function resolveUser(int $tgUserId, ?array $info = null, ?bool &$isInsert = null): ?int
  {
    $e    = null;
    $lock = new Mutex("tg_users", "{$tgUserId}");
    $lock->lock();

    /*debug:5*/
    if (is_array($info)) {
      $requiredFields = [
        "username",
        "first_name",
        "last_name",
        "is_bot",
      ];
      $missing = [];
      foreach ($requiredFields as $k => $v) {
        if (!array_key_exists($v, $info)) {
          $missing[] = $v;
        }
      }
      if (count($missing) > 0) {
        throw new \Error("Missing required fields: ".json_encode($missing));
      }
    }
    /*enddebug*/

    $pdo = $this->pdo;
    $pdo->beginTransaction();

    try {

      if (is_array($info)) {
        $ret = $this->fullResolveUser($tgUserId, $info, $isInsert);
      } else {
        $ret = $this->directResolveUser($tgUserId);
      }

      $pdo->commit();
    } catch (Exception $e) {
      $pdo->rollback();
    }

    $lock->unlock();

    if ($e) {
      throw new $e;
    }

    return $ret;
  }


  /**
   * @param  int    $tgUserId
   * @param  array  info
   * @param  ?bool  &$isInsert
   * @return int
   */
  private function fullResolveUser(int $tgUserId, array $info, ?bool &$isInsert): int
  {
    $pdo = $this->pdo;
    $st  = $pdo->prepare("SELECT id, group_msg_count, private_msg_count, username, first_name, last_name, photo FROM tg_users WHERE tg_user_id = ?");
    $st->execute([$tgUserId]);

    $dateTime     = date("Y-m-d H:i:s");
    $trackHistory = false;

    if ($u = $st->fetch(PDO::FETCH_ASSOC)) {
      /* User has already been stored in database. */
      $trackHistory = self::updateUser($pdo, $u, $info, $dateTime);
      $id = (int)$u["id"];

      if (!is_null($isInsert)) $isInsert = false;

    } else {
      $id = self::insertUser($pdo, $tgUserId, $info, $dateTime);
      $trackHistory = true;

      if (!is_null($isInsert)) $isInsert = true;
    }

    if ($trackHistory) {
      self::createHistory($pdo, $id, $info, $dateTime);
    }

    return $id;
  }


  /**
   * @param int $tgUserId
   * @return int
   */
  private function directResolveUser(int $tgUserId): ?int
  {
    $pdo = $this->pdo;
    $st  = $pdo->prepare("SELECT id FROM tg_users WHERE tg_user_id = ?");
    $st->execute([$tgUserId]);

    if ($u = $st->fetch(PDO::FETCH_NUM)) {
      return (int)$u[0];
    } else {
      return null;
    }
  }


  /**
   * @param \PDO   $pdo
   * @param array  $u
   * @param array  &$info
   * @param string $dateTime
   * @return bool
   */
  private static function updateUser(PDO $pdo, array $u, array &$info, string $dateTime): bool
  {
    $doUpdate    = false;
    $updateQuery = "UPDATE tg_users SET ";
    $updateData  = [];

    /* Check for info update. */
    if ($u["username"] !== $info["username"]) {
      $updateQuery .= "username = ?";
      $updateData[] = $info["username"];
      $doUpdate = true;
    }

    if ($u["first_name"] !== $info["first_name"]) {
      $updateQuery .= ($doUpdate ? "," : "")."first_name = ?";
      $updateData[] = $info["first_name"];
      $doUpdate = true;
    }

    if ($u["last_name"] !== $info["last_name"]) {
      $updateQuery .= ($doUpdate ? "," : "")."last_name = ?";
      $updateData[] = $info["last_name"];
      $doUpdate = true;
    }

    if (array_key_exists("photo", $info)) {
      if ($u["photo"] !== $info["photo"]) {
        $updateQuery .= ($doUpdate ? "," : "")."photo = ?";
        $updateData[] = $info["photo"];
        $doUpdate = true;
      }
    } else {
      if ($u["photo"] !== null) {
        $info["photo"] = $u["photo"];
      }
    }

    if ($doUpdate) {
      $updateQuery .= ",updated_at = ? WHERE id = ?";
      $updateData[] = $dateTime;
      $updateData[] = $u["id"];
      $pdo->prepare($updateQuery)->execute($updateData);
      return true;
    }
    
    return false;
  }


  /**
   * @param \PDO   $pdo
   * @param int    $tgUserId
   * @param array  &$info
   * @param string $dateTime
   * @return int
   */
  private static function insertUser(PDO $pdo, int $tgUserId, array &$info, string $dateTime): int
  {
    /* TODO: track user photo before insert. */

    $pdo
      ->prepare("INSERT INTO tg_users (tg_user_id, username, first_name, last_name, photo, group_msg_count, private_msg_count, is_bot, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
      ->execute(
        [
          $tgUserId,
          $info["username"],
          $info["first_name"],
          $info["last_name"],
          $info["photo"] ?? null,
          $info["group_msg_count"] ?? 0,
          $info["private_msg_count"] ?? 0,
          $info["is_bot"],
          $dateTime
        ]
      );

    return $pdo->lastInsertId();
  }


  /**
   * @param \PDO   $pdo
   * @param int    $userId
   * @param array  $info
   * @param string $dateTime
   * @return void
   */
  private function createHistory(PDO $pdo, int $userId, array $info, string $dateTime): void
  {
    $pdo
      ->prepare("INSERT INTO tg_user_history (user_id, username, first_name, last_name, photo, created_at) VALUES (?, ?, ?, ?, ?, ?)")
      ->execute(
        [
          $userId,
          $info["username"],
          $info["first_name"],
          $info["last_name"],
          $info["photo"] ?? null,
          $dateTime
        ]
      );
  }
}
