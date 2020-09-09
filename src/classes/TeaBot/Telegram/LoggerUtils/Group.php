<?php

namespace TeaBot\Telegram\LoggerUtils;

use DB;
use PDO;
use Exception;
use TeaBot\Telegram\Exe;
use TeaBot\Telegram\Data;
use TeaBot\Telegram\Dlog;
use TeaBot\Telegram\Mutex;
use TeaBot\Telegram\LoggerUtils\File;
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
   * @var \TeaBot\Telegram\Data
   */
  private Data $data;


  /**
   * @var bool
   */
  private bool $needHistory = false;


  /**
   * @var ?int
   */
  private ?int $historyId = null;


  /**
   * @var bool
   */
  private bool $hasInsertAct = false;


  /**
   * @var string
   */
  private string $dateTime;


  /**
   * @var ?array
   */
  private ?array $u;


  /**
   * @param mixed $key
   * @return mixed
   */
  public function __get($key)
  {
    return $this->{$key} ?? null;
  }


  /**
   * @param \TeaBot\Telegram\Data $data
   * @return void
   */
  public function setData(Data $data): void
  {
    $this->data = $data;
  }


  /**
   * @return array
   */
  public function resolveGroup(): array
  {
    /* debug:assert */
    if (!$this->data) {
      throw new \Error("Data has not been set");
    }
    /* end_debug */

    $mutex = new Mutex("tg_groups", "{$this->data["chat_id"]}");
    $mutex->lock();

    $ret = $e = null;
    $pdo = $this->pdo;

    try {
      $pdo->beginTransaction();
      $this->dateTime = date("Y-m-d H:i:s");
      $ret = $this->resolveGroupInternal();
      $pdo->commit();
    } catch (Exception $e) {
      /* debug:warning */
      Dlog::err("We rollback the transaction at %s", __METHOD__);
      /* end_debug */
      $pdo->rollback();
      $mutex->unlock();
      throw $e;
    }

    $mutex->unlock();
    return $ret;
  }


  /**
   * @return array
   */
  private function resolveGroupInternal(): array
  {
    $pdo       = $this->pdo;
    $tgGroupId = $this->data["chat_id"];
    $st        = $pdo->prepare("SELECT id, username, name, link, photo, msg_count FROM tg_groups WHERE tg_group_id = ?");
    $st->execute([$tgGroupId]);

    if ($u = $st->fetch(PDO::FETCH_ASSOC)) {
      /* Group has already been stored in database. */
      $this->trackGroupUpdate($u);
      unset($u["username"], $u["name"]);
      $this->u = $u;
    } else {
      /* Found new user. */
      $data    = $this->data;
      $groupId = $this->insertGroup();

      $u = $this->u = [
        "id"         => $groupId,
        "link"       => null,
        "photo"      => null,
        "msg_count"  => 0,
      ];
      $this->hasInsertAct = true;
    }

    if ($this->needHistory) {
      $this->createGroupHistory($u["id"]);
    }

    return $u;
  }


  /**
   * @param array $u
   * @return void
   */
  private function trackGroupUpdate(array $u): void
  {
    /* Check for group info update. */

    $doUpdate    = false;
    $updateQuery = "UPDATE tg_groups SET ";
    $updateData  = [];
    $data        = $this->data;

    if ($u["username"] !== $data["chat_username"]) {
      $updateQuery .= "username = ?";
      $updateData[] = $data["chat_username"];
      $doUpdate = true;
    }

    if ($u["name"] !== $data["chat_title"]) {
      $updateQuery .= ($doUpdate ? "," : "")."first_name = ?";
      $updateData[] = $data["chat_title"];
      $doUpdate = true;
    }

    if ($doUpdate) {
      $updateQuery .= ",updated_at = ? WHERE id = ?";
      $updateData[] = $this->dateTime;
      $updateData[] = $u["id"];
      $this->pdo->prepare($updateQuery)->execute($updateData);
      $this->needHistory = true;
    }
  }


  /**
   * @return int
   */
  private function insertGroup(): int
  {
    $data = $this->data;
    $pdo  = $this->pdo;
    $pdo
      ->prepare("INSERT INTO tg_groups (tg_group_id, username, name, msg_count, created_at) VALUES (?, ?, ?, ?, ?)")
      ->execute(
        [
          $data["chat_id"],
          $data["chat_username"],
          $data["chat_title"],
          0,
          $this->dateTime
        ]
      );
    $this->needHistory = true;
    return (int)$pdo->lastInsertId();
  }


  /**
   * @param int $groupId
   * @return void
   */
  private function createGroupHistory(int $groupId): void
  {
    $data = $this->data;
    $pdo  = $this->pdo;
    $pdo
      ->prepare("INSERT INTO tg_group_history (group_id, username, name, link, photo, created_at) VALUES (?, ?, ?, ?, ?, ?)")
      ->execute(
        [
          $groupId,
          $data["username"],
          $data["name"],
          $this->u["link"] ?? null,
          $this->u["photo"] ?? null,
          $this->dateTime
        ]
      );

    $this->historyId = (int)$pdo->lastInsertId();
  }


  /**
   * @return void
   */
  public function trackPhoto(): void
  {
    $data = $this->data;
    $mutex = new Mutex("tg_groups", "{$this->data["chat_id"]}");
    $mutex->lock();

    /* debug:assert */
    if (!$this->u) {
      throw new \Error("\$this->u has not been set!");
    }
    /* end_debug */

    /* debug:global */
    $__jd = json_encode(
      [
        "user_id" => $data["user_id"],
        "chat_id" => $data["chat_id"]
      ]
    );
    /* end_debug */

    /* debug:p3 */
    Dlog::out("Getting group photo: %s", $__jd);
    /* end_debug */

    $ret = Exe::getUserProfilePhotos([
      "user_id" => $data["user_id"],
      "offset"  => 0,
      "limit"   => 1,
    ]);
    $j = json_decode($ret->getBody()->__toString(), true);


    if (!isset($j["result"]["photos"][0])) {
      /* Cannot get the photo or the user may not have. */
      /* debug:warning */
      Dlog::err("Cannot retrieve photo from getUserProfilePhotos: %s", $__jd);
      /* end_debug */
      return;
    }

    /* Get the highest resolution. */
    $photo = $j["result"]["photos"][0];
    usort($photo, fn($p1, $p2) =>
      $p2["width"] * $p2["height"] <=>
      $p1["width"] * $p1["height"]
    );
    $photo = $photo[0];

    if (!isset($photo["file_id"])) {
      /* Cannot get the photo or the user may not have. */
      /* debug:warning */
      Dlog::err("Cannot retrieve photo (file_id is not found): %s", $__jd);
      /* end_debug */
      return;
    }

    $pdo    = $this->pdo;
    $file   = new File($pdo);
    $fileId = $file->resolveFile($photo["file_id"]);
    unset($file);

    if (is_null($fileId)) {
      goto ret;
    }

    if ($this->u["photo"] !== $fileId) {
      if (is_null($this->historyId)) {
        /* Create new history. */
        $pdo
          ->prepare("UPDATE tg_users SET photo = ? WHERE id = ?")
          ->execute([$fileId, $this->u["id"]]);
        $this->createUserHistory($this->u["id"]);
      } else {
        /* Amend current history. */
        $pdo
          ->prepare("UPDATE tg_users AS a INNER JOIN tg_user_history AS b ON a.id = b.user_id SET a.photo = ?, b.photo = ? WHERE b.id = ?")
          ->execute([$fileId, $fileId, $this->historyId]);
      }
    }

    ret:
    $mutex->unlock();
  }
}
