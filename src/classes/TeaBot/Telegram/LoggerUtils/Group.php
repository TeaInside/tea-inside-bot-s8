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
class Group extends LoggerUtilFoundation
{
  /**
   * @param  int    $tgGroupId
   * @param  ?array info
   * @param  ?bool  &$isInsert
   * @return ?int
   */
  public function resolveGroup(int $tgGroupId, ?array $info = null, ?bool &$isInsert = null): ?int
  {
    $e    = null;
    $lock = new Mutex("tg_groups", "{$tgGroupId}");
    $lock->lock();

    /*debug:5*/
    if (is_array($info)) {
      $requiredFields = [
        "username",
        "name",
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
        $ret = $this->fullResolveGroup($tgGroupId, $info, $isInsert);
      } else {
        $ret = $this->directResolveGroup($tgGroupId);
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
   * @param  int    $tgGroupId
   * @param  array  info
   * @param  ?bool  &$isInsert
   * @return int
   */
  private function fullResolveGroup(int $tgGroupId, array $info, ?bool &$isInsert = null): int
  {
    $pdo = $this->pdo;
    $st  = $pdo->prepare("SELECT id, username, name, link, photo, msg_count FROM tg_groups WHERE tg_group_id = ?");
    $st->execute([$tgGroupId]);

    $dateTime     = date("Y-m-d H:i:s");
    $trackHistory = false;

    if ($u = $st->fetch(PDO::FETCH_ASSOC)) {
      /* Group has already been stored in database. */
      $trackHistory = self::updateGroup($pdo, $u, $info, $dateTime);
      $id = (int)$u["id"];

      if (!is_null($isInsert)) $isInsert = false;

    } else {
      $id = self::insertGroup($pdo, $tgGroupId, $info, $dateTime);
      $trackHistory = true;

      if (!is_null($isInsert)) $isInsert = true;

    }

    if ($trackHistory) {
      self::createHistory($pdo, $id, $info, $dateTime);
    }

    return $id;
  }


  /**
   * @param \PDO   $pdo
   * @param array  $u
   * @param array  &$info
   * @param string $dateTime
   * @return bool
   */
  private static function updateGroup(PDO $pdo, array $u, array &$info, string $dateTime): bool
  {
    $doUpdate    = false;
    $updateQuery = "UPDATE tg_groups SET ";
    $updateData  = [];


    /* Check for info update. */
    if ($u["username"] !== $info["username"]) {
      $updateQuery .= "username = ?";
      $updateData[] = $info["username"];
      $doUpdate = true;
    }

    if ($u["name"] !== $info["name"]) {
      $updateQuery .= ($doUpdate ? "," : "")."name = ?";
      $updateData[] = $info["name"];
      $doUpdate = true;
    }

    if (array_key_exists("link", $info)) {
      if ($u["link"] !== $info["link"]) {
        $updateQuery .= ($doUpdate ? "," : "")."link = ?";
        $updateData[] = $info["link"];
        $doUpdate = true;
      }
    } else {
      if ($u["link"] !== null) {
        $info["link"] = $u["link"];
      }
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
   * @param int    $tgGroupId
   * @param array  &$info
   * @param string $dateTime
   * @return int
   */
  private static function insertGroup(PDO $pdo, int $tgGroupId, array &$info, string $dateTime): int
  {
    /* TODO: track group photo before insert. */

    $pdo
      ->prepare("INSERT INTO tg_groups (tg_group_id, username, name, link, photo, msg_count, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)")
      ->execute(
        [
          $tgGroupId,
          $info["username"],
          $info["name"],
          $info["link"] ?? null,
          $info["photo"] ?? null,
          $info["msg_count"] ?? 0,
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
  private function createHistory(PDO $pdo, int $groupId, array $info, string $dateTime): void
  {
    $pdo
      ->prepare("INSERT INTO tg_group_history (group_id, username, name, link, photo, created_at) VALUES (?, ?, ?, ?, ?, ?)")
      ->execute(
        [
          $groupId,
          $info["username"],
          $info["name"],
          $info["link"] ?? null,
          $info["photo"] ?? null,
          $dateTime
        ]
      );
  }
}
