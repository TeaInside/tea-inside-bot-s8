<?php

namespace TeaBot\Telegram\LoggerFoundationTraits;

use DB;
use PDO;
use TeaBot\Telegram\Exe;
use Swoole\Coroutine\Channel;
use TeaBot\Telegram\Exceptions\LoggerException;

/**
 * @const array
 */
const GROUP_INSERT_MANDATORY_FIELDS = [
  "tg_group_id",
  "name",
  "username"
];

/**
 * @const array
 */
const GROUP_INSERT_DEFAULT_VALUES = [
  "photo" => null,
  "msg_count" => 0
];

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\LoggerFoundationTraits
 * @version 8.0.0
 */
trait GroupResolver
{
  /**
   * @param array $data
   * @return int
   * @throws \TeaBot\Telegram\Exceptions\LoggerException
   */
  public static function groupInsert(array $data): ?int
  {
    foreach (GROUP_INSERT_MANDATORY_FIELDS as $v) {
      if (!array_key_exists($v, $data)) {
        throw new LoggerException(
          "Invalid data to be inserted (missing mandatory fields): "
          .json_encode($data));
      }
    }

    foreach (GROUP_INSERT_DEFAULT_VALUES as $k => $v) {
      isset($data[$k]) or $data[$k] = $v;
    }

    if (is_string($data["username"])) {
      $data["link"] = "https://t.me/".$data["username"];
    } else {
      $data["link"] = null;
    }

    /**
     * Check whether the group has already been
     * stored in database or not.
     */
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT `id`,`name`,`username`,`photo`,`link`,`msg_count` FROM `tg_groups` WHERE `tg_group_id` = ? FOR UPDATE");
    $st->execute([$data["tg_group_id"]]);

    $createGroupHistory = false;

    if ($u = $st->fetch(PDO::FETCH_ASSOC)) {

      /**
       * We need to build the query based
       * on differential condition in
       * order to reduce query size.
       */
      $updateData = [];
      $exeUpdate = false;
      $query = "UPDATE `tg_groups` SET ";

      if ($data["msg_count"] != 0) {
        $query .= "`msg_count`=`msg_count`+1";
        $exeUpdate = true;
      }

      if ($data["username"] !== $u["username"]) {
        $query .= ($exeUpdate ? "," : "")."`username`=:username";
        $updateData["username"] = $data["username"];
        $exeUpdate = $createGroupHistory = true;
      }

      if ($data["name"] !== $u["name"]) {
        $query .= ($exeUpdate ? "," : "")."`name`=:name";
        $updateData["name"] = $data["name"];
        $exeUpdate = $createGroupHistory = true;
      }

      if ($data["link"] !== $u["link"]) {
        $query .= ($exeUpdate ? "," : "")."`link`=:link";
        $updateData["link"] = $data["link"];
        $exeUpdate = $createGroupHistory = true; 
      }

      if (!($u["msg_count"] % 5)) {
        $fetchPhoto = true;
        $data["photo"] = self::getLatestGroupPhoto($data["tg_group_id"]);

        if ($data["photo"] != $u["photo"]) {
          $query .= ($exeUpdate ? "," : "")."`photo`=:photo";
          $updateData["photo"] = $data["photo"];
          $exeUpdate = $createGroupHistory = true;
        }

        self::groupAdminResolve($data["tg_group_id"], $u["id"]);

      } else {
        $fetchPhoto = false;
      }

      if ($exeUpdate) {
        $query .= " WHERE `id` = :id";
        $updateData["id"] = $u["id"];
        $pdo->prepare($query)->execute($updateData);

        /**
         * In case createGroupHistory is true,
         * we should assume the photo is the
         * same as before if and only if the
         * logger does not fetch the photo.
         */
        if ((!$fetchPhoto) && $createGroupHistory &&
            is_null($data["photo"])
        ) {
          $data["photo"] = $u["photo"];
        }
      }

      $data["group_id"] = $u["id"];

    } else {

      $data["photo"] = self::getLatestGroupPhoto($data["tg_group_id"]);
      $st = $pdo->prepare("INSERT INTO `tg_groups` (`tg_group_id`, `name`, `username`, `link`, `photo`, `msg_count`, `created_at`) VALUES (:tg_group_id, :name, :username, :link, :photo, :msg_count, NOW()) ON DUPLICATE KEY UPDATE `id`=LAST_INSERT_ID(`id`)");
      $st->execute($data);
      $createGroupHistory = ($st->rowCount() == 1);
      $data["group_id"] = $pdo->lastInsertId();

      self::groupAdminResolve($data["tg_group_id"], $data["group_id"]);
    }


    if ($createGroupHistory) {

      /* Unset unused keys. */
      $data = array_filter($data, function ($k) {
        return in_array($k, ["group_id", "name", "username", "link", "photo"]);
      }, ARRAY_FILTER_USE_KEY);

      /* Record group history. */
      $pdo->prepare("INSERT INTO `tg_group_history` (`group_id`, `name`, `username`, `link`, `photo`, `created_at`) VALUES (:group_id, :name, :username, :link, :photo, NOW())")->execute($data);
    }


    return (int)$data["group_id"];
  }


  /** 
   * @param string $tgGroupId
   * @return ?int
   */
  final public static function getLatestGroupPhoto(string $tgGroupId): ?int
  {
    $o = json_decode(
        Exe::getChat(["chat_id" => $tgGroupId])
        ->getBody()->__toString(), true);

    return isset($o["result"]["photo"]["big_file_id"])
      ? static::fileResolve($o["result"]["photo"]["big_file_id"])
      : null;
  }


  /**
   * @param string $tgUserId
   * @return ?int
   */
  final public static function getLatestUserPhoto(string $tgUserId): ?int
  {
    $json = json_decode(
      Exe::getUserProfilePhotos(
        [
          "user_id" => $tgUserId,
          "offset" => 0,
          "limit" => 1
        ]
      )->getBody()->__toString(),
      true
    );

    if (isset($json["result"]["photos"][0])) {
      $c = count($json["result"]["photos"][0]);
      if ($c > 0) {
        $p = $json["result"]["photos"][0][$c - 1];
        if (isset($p["file_id"])) {
          return static::fileResolve($p["file_id"]);
        }
      }
    }

    return null;
  }


  /**
   * @param int  $tgGroupId
   * @param ?int $groupId
   * @return void
   */
  final public static function groupAdminResolve(int $tgGroupId, ?int $groupId = null): void
  {
    $data = json_decode(
      Exe::getChatAdministrators(
        ["chat_id" => $tgGroupId]
      )->getBody()->__toString(),
      true
    );

    if (isset($data["result"])) {

      $pdo = DB::pdo();

      if (is_null($groupId)) {
        $st = $pdo->prepare("SELECT `id` FROM `tg_groups` WHERE `tg_group_id` = ?");
        $st->execute([$tgGroupId]);
        if (!($r = $st->fetch(PDO::FETCH_NUM))) {
          return;
        }
        $groupId = $r[0];
      }

      $channel = new Channel;
      foreach ($data["result"] as $k => $v) {
        go(function () use ($k, $v, $channel, $groupId) {
          $userId = static::userInsert(
            [
              "tg_user_id" => $v["user"]["id"],
              "first_name" => $v["user"]["first_name"],
              "last_name" => $v["user"]["last_name"] ?? null,
              "username" => $v["user"]["username"] ?? null,
              "is_bot" => $v["user"]["is_bot"] ? 1 : 0
            ]
          );
          $role = $v["status"];
          unset($v["status"], $v["user"]);
          $channel->push(
            [
              [$userId, $groupId, $role, count($v) ? json_encode($v) : null],
              "(?,?,?,?,NOW())"
            ]
          );
          DB::close();
        });
      }

      $c = count($data["result"]);
      $query = "INSERT INTO `tg_group_admins` (`user_id`,`group_id`,`role`,`info`,`created_at`) VALUES ";

      $data = [];
      for ($i = 0; $i < $c; $i++) { 
        $v = $channel->pop();
        $data = array_merge($data, $v[0]);
        $query .= ($i ? "," : "").$v[1];
      }

      $pdo->prepare("DELETE FROM `tg_group_admins` WHERE `group_id` = ?")
      ->execute([$groupId]);

      if (count($data)) {
        $pdo->prepare($query)->execute($data);
      }
    }
  }
}
