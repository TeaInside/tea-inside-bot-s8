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
class User extends LoggerUtilFoundation
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
  public function resolveUser(): array
  {
    /* debug:assert */
    if (!$this->data) {
      throw new \Error("Data has not been set");
    }
    /* end_debug */

    $mutex = new Mutex("tg_users", "{$this->data["user_id"]}");
    $mutex->lock();

    $ret = $e = null;
    $pdo = $this->pdo;

    try {
      $pdo->beginTransaction();
      $this->dateTime = date("Y-m-d H:i:s");
      $ret = $this->resolveUserInternal();
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
  private function resolveUserInternal(): array
  {
    $pdo      = $this->pdo;
    $tgUserId = $this->data["user_id"];
    $st       = $pdo->prepare("SELECT id, username, first_name, last_name, photo, group_msg_count, private_msg_count FROM tg_users WHERE tg_user_id = ?");
    $st->execute([$tgUserId]);

    if ($u = $st->fetch(PDO::FETCH_ASSOC)) {
      /* User has already been stored in database. */
      $this->trackUserUpdate($u);
      unset($u["username"], $u["first_name"], $u["last_name"]);
      $this->u = $u;
    } else {
      /* Found new user. */
      $data   = $this->data;
      $userId = $this->insertUser();

      $u = $this->u = [
        "id"                => $userId,
        "photo"             => null,
        "group_msg_count"   => 0,
        "private_msg_count" => 0,
      ];
      $this->hasInsertAct = true;
    }

    if ($this->needHistory) {
      $this->createUserHistory($u["id"]);
    }

    return $u;
  }


  /**
   * @param array $u
   * @return void
   */
  private function trackUserUpdate(array $u): void
  {
    /* Check for user info update. */

    $doUpdate    = false;
    $updateQuery = "UPDATE tg_users SET ";
    $updateData  = [];
    $data        = $this->data;

    if ($u["username"] !== $data["username"]) {
      $updateQuery .= "username = ?";
      $updateData[] = $data["username"];
      $doUpdate = true;
    }

    if ($u["first_name"] !== $data["first_name"]) {
      $updateQuery .= ($doUpdate ? "," : "")."first_name = ?";
      $updateData[] = $data["first_name"];
      $doUpdate = true;
    }

    if ($u["last_name"] !== $data["last_name"]) {
      $updateQuery .= ($doUpdate ? "," : "")."last_name = ?";
      $updateData[] = $data["last_name"];
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
  private function insertUser(): int
  {
    $data = $this->data;
    $pdo  = $this->pdo;
    $pdo
      ->prepare("INSERT INTO tg_users (tg_user_id, username, first_name, last_name, group_msg_count, private_msg_count, is_bot, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
      ->execute(
        [
          $data["user_id"],
          $data["username"],
          $data["first_name"],
          $data["last_name"],
          0,
          0,
          $data["is_bot"] ? 1 : 0,
          $this->dateTime
        ]
      );
    $this->needHistory = true;
    return (int)$pdo->lastInsertId();
  }


  /**
   * @param int $userId
   * @return void
   */
  private function createUserHistory(int $userId): void
  {
    $data = $this->data;
    $pdo  = $this->pdo;
    $pdo
      ->prepare("INSERT INTO tg_user_history (user_id, username, first_name, last_name, photo, created_at) VALUES (?, ?, ?, ?, ?, ?)")
      ->execute(
        [
          $userId,
          $data["username"],
          $data["first_name"],
          $data["last_name"],
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
    $mutex = new Mutex("tg_users", "{$this->data["user_id"]}");
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
    Dlog::out("Getting user profile photo: %s", $__jd);
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
