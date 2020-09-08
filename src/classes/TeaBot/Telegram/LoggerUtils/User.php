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
   * @var bool
   */
  private $allowTrackUpdate = true;


  /**
   * @return void
   */
  public function dontTrackUpdate(): void
  {
    $this->allowTrackUpdate = false;
  }


  /**
   * @return void
   */
  public function trackUpdate(): void
  {
    $this->allowTrackUpdate = true;
  }


  /**
   * @param  int    $tgUserId
   * @param  ?array &$info
   * @param  ?bool  &$isInsert
   * @return ?int
   */
  public function resolveUser(int $tgUserId, ?array &$info = null, ?bool &$isInsert = null): ?int
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

    try {

      $pdo->beginTransaction();

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
   * @param  array  &$info
   * @param  ?bool  &$isInsert
   * @return int
   */
  private function fullResolveUser(int $tgUserId, array &$info, ?bool &$isInsert): int
  {
    $pdo = $this->pdo;
    $st  = $pdo->prepare("SELECT id, group_msg_count, private_msg_count, username, first_name, last_name, photo FROM tg_users WHERE tg_user_id = ?");
    $st->execute([$tgUserId]);

    $dateTime     = date("Y-m-d H:i:s");
    $trackHistory = false;

    if ($u = $st->fetch(PDO::FETCH_ASSOC)) {
      /* User has already been stored in database. */
      $trackHistory = self::updateUser($u, $info, $dateTime);
      $id = (int)$u["id"];

      if (!is_null($isInsert)) $isInsert = false;

    } else {
      /* Insert user into database. */

      $id = self::insertUser($tgUserId, $info, $dateTime);
      $trackHistory = true;

      if (!is_null($isInsert)) $isInsert = true;
    }

    if ($trackHistory) {
      self::createHistory($id, $info, $dateTime);
    }

    return $id;
  }


  /**
   * @param int $tgUserId
   * @return int
   */
  private function directResolveUser(int $tgUserId): ?int
  {
    $st  = $this->pdo->prepare("SELECT id FROM tg_users WHERE tg_user_id = ?");
    $st->execute([$tgUserId]);

    if ($u = $st->fetch(PDO::FETCH_NUM)) {
      return (int)$u[0];
    } else {
      return null;
    }
  }


  /**
   * @param array  $u
   * @param array  &$info
   * @param string $dateTime
   * @return bool
   */
  public function updateUser(array $u, array &$info, string $dateTime): bool
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
      } else {
        $info["photo"] = null;
      }
    }

    /* Fill info fields reference. */
    if (!isset($info["group_msg_count"])) {
      $info["group_msg_count"] = $u["group_msg_count"];
    }

    if (!isset($info["private_msg_count"])) {
      $info["private_msg_count"] = $u["private_msg_count"];
    }



    if ($doUpdate) {
      if ($this->allowTrackUpdate) {
        $updateQuery .= ",updated_at = ? WHERE id = ?";
        $updateData[] = $dateTime;
        $updateData[] = $u["id"];
      }
      $this->pdo->prepare($updateQuery)->execute($updateData);
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
  public function insertUser(int $tgUserId, array &$info, string $dateTime): int
  {
    $pdo = $this->pdo;

    if (!array_key_exists("photo", $info)) {
      $info["photo"] = null;
    }

    if (!isset($info["group_msg_count"])) {
      $info["group_msg_count"] = 0;
    }

    if (!isset($info["private_msg_count"])) {
      $info["private_msg_count"] = 0;
    }

    $pdo
      ->prepare("INSERT INTO tg_users (tg_user_id, username, first_name, last_name, photo, group_msg_count, private_msg_count, is_bot, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
      ->execute(
        [
          $tgUserId,
          $info["username"],
          $info["first_name"],
          $info["last_name"],
          $info["photo"],
          $info["group_msg_count"],
          $info["private_msg_count"],
          $info["is_bot"],
          $dateTime
        ]
      );

    return $pdo->lastInsertId();
  }


  /**
   * @param int    $userId
   * @param array  $info
   * @param string $dateTime
   * @return void
   */
  public function createHistory(int $userId, array $info, string $dateTime): void
  {
    $this
      ->pdo
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
