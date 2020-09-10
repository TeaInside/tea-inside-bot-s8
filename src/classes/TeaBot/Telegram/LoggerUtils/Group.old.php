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
   * @param  int    $tgGroupId
   * @param  ?array &$info
   * @param  ?bool  &$isInsert
   * @return ?int
   */
  public function resolveGroup(int $tgGroupId, ?array &$info = null, ?bool &$isInsert = null): ?int
  {
    $e    = null;
    $mutex = new Mutex("tg_groups", "{$tgGroupId}");
    $mutex->lock();

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

    try {

      $pdo->beginTransaction();

      if (is_array($info)) {
        $ret = $this->fullResolveGroup($tgGroupId, $info, $isInsert);
      } else {
        $ret = $this->directResolveGroup($tgGroupId);
      }

      $pdo->commit();
    } catch (Exception $e) {
      $pdo->rollback();
    }

    $mutex->unlock();

    if ($e) {
      throw new $e;
    }

    return $ret;
  }


  /**
   * @param  int    $tgGroupId
   * @param  array  &$info
   * @param  ?bool  &$isInsert
   * @return int
   */
  private function fullResolveGroup(int $tgGroupId, array &$info, ?bool &$isInsert = null): int
  {
    $st  = $this->pdo->prepare("SELECT id, username, name, link, photo, msg_count FROM tg_groups WHERE tg_group_id = ?");
    $st->execute([$tgGroupId]);

    $dateTime     = date("Y-m-d H:i:s");
    $trackHistory = false;

    if ($u = $st->fetch(PDO::FETCH_ASSOC)) {
      /* Group has already been stored in database. */
      $trackHistory = $this->updateGroup($u, $info, $dateTime);
      $id = (int)$u["id"];

      if (!is_null($isInsert)) $isInsert = false;

    } else {
      $id = $this->insertGroup($tgGroupId, $info, $dateTime);
      $trackHistory = true;

      if (!is_null($isInsert)) $isInsert = true;

    }

    if ($trackHistory && $this->allowTrackUpdate) {
      self::createHistory($id, $info, $dateTime);
    }

    return $id;
  }


  /**
   * @param int $tgGroupId
   * @return int
   */
  private function directResolveGroup(int $tgGroupId): ?int
  {
    $st  = $this->pdo->prepare("SELECT id FROM tg_groups WHERE tg_group_id = ?");
    $st->execute([$tgGroupId]);

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
  public function updateGroup(array $u, array &$info, string $dateTime): bool
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
      $info["link"] = $u["link"] ?? null;
    }

    if (array_key_exists("photo", $info)) {
      if ($u["photo"] !== $info["photo"]) {
        $updateQuery .= ($doUpdate ? "," : "")."photo = ?";
        $updateData[] = $info["photo"];
        $doUpdate = true;
      }
    } else {
      $info["photo"] = $u["photo"] ?? null;
    }

    /* Fill info fields reference. */
    if (!isset($info["msg_count"])) {
      $info["msg_count"] = 0;
    }


    if ($doUpdate) {
      if ($this->allowTrackUpdate) {
        $updateQuery .= ",updated_at = ? ";
        $updateData[] = $dateTime;
      }
      $updateQuery .= "WHERE id = ?";
      $updateData[] = $u["id"];
      $this->pdo->prepare($updateQuery)->execute($updateData);
      return true;
    }

    return false;
  }


  /**
   * @param int    $tgGroupId
   * @param array  &$info
   * @param string $dateTime
   * @return int
   */
  public function insertGroup(int $tgGroupId, array &$info, string $dateTime): int
  {
    $pdo = $this->pdo;

    if (!array_key_exists("link", $info)) {
      $info["link"] = null;
    }

    if (!array_key_exists("photo", $info)) {
      $info["photo"] = null;
    }

    if (!isset($info["msg_count"])) {
      $info["msg_count"] = 0;
    }

    $pdo
      ->prepare("INSERT INTO tg_groups (tg_group_id, username, name, link, photo, msg_count, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)")
      ->execute(
        [
          $tgGroupId,
          $info["username"],
          $info["name"],
          $info["link"],
          $info["photo"],
          $info["msg_count"],
          $dateTime,
        ]
      );

    return (int)$pdo->lastInsertId();
  }


  /**
   * @param int    $userId
   * @param array  $info
   * @param string $dateTime
   * @return void
   */
  public function createHistory(int $groupId, array $info, string $dateTime): void
  {
    $this
      ->pdo
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
