<?php

namespace TeaBot\Telegram;

use DB;
use PDO;
use TeaBot\Telegram\Exe;
use Swoole\Coroutine\Channel;
use TeaBot\Telegram\Exceptions\LoggerException;
use TeaBot\Telegram\LoggerFoundationTraits\FileResolver;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
abstract class LoggerFoundation
{
  use FileResolver;

	/**
	 * @var \TeaBot\Telegram\Logger
	 */
	protected Logger $logger;

  /**
   * @var \TeaBot\Telegram\Data
   */
  protected Data $data;

  /**
   * @param \TeaBot\Telegram\Logger $logger
   */
	public function __construct(Logger $logger)
  {
    $this->logger = $logger;
    $this->data   = $logger->data;
	}

  /**
   * @param int $groupId
   * @return void
   */
  public static function incrementGroupMsgCount(int $groupId): void
  {
    DB::pdo()
      ->prepare("UPDATE `tg_groups` SET `msg_count`=`msg_count`+1 WHERE `id`=?")
      ->execute([$groupId]);
  }

  /**
   * @param int $userId
   * @param int $type
   * @return void
   * @throws \TeaBot\Telegram\Exceptions\LoggerException
   */
  public static function incrementUserMsgCount(int $userId, int $type): void
  {
    switch ($type) {
      case 1:
        DB::pdo()
          ->prepare("UPDATE `tg_users` SET `private_msg_count`=`private_msg_count`+1 WHERE `id`=?")
          ->execute([$userId]);
        break;
      case 2:
        DB::pdo()
          ->prepare("UPDATE `tg_users` SET `group_msg_count`=`group_msg_count`+1 WHERE `id`=?")
          ->execute([$userId]);
        break;
      default:
        throw new LoggerException("Invalid type: {$type}");
        break;
    }
  }

  /**
   * @param string $tgFileId
   * @param bool   $addHitCount
   * @param bool   $transactional
   * @return ?int
   */
  public static function fileResolve(
    string $tgFileId,
    bool $addHitCount = false,
    bool $transactional = false
  ): ?int
  {

    if (!$transactional) {
      return self::baseFileResolve($tgFileId, $addHitCount);
    }

    try {
      /*__debug_flag:5:41IAg7LEoviU0twCDaWk1PTMvJCixLzixOSSzPw8KwUlPZXkzBRNay6IUgA=*/

      $pdo->beginTransaction();

      /*__debug_flag:5:41IAg7LEoviU0twCDaWk1PTMvJCixLzixOSSzPw8BX9vKwUlPZXkzBRNay6IagA=*/

      $fileId = self::baseFileResolve($tgFileId, $addHitCount);

      /*__debug_flag:5:41IAg7LEoviU0twCDaXk/NzczBIrBSU9leTMFE1rLogCAA==*/

      $pdo->commit();

    } catch (PDOException $e) {
      /*__debug_flag:5:41IAg7LEoviU0twCDaWi/JycpMTkbCsFJT2V5MwUTWsuNCUqqXpKSnBhAA==*/

      $pdo->rollBack();
      $teaBot and $teaBot->errorReport($e);
      return null;
    } catch (Error $e) {
      /*__debug_flag:5:41IAg7LEoviU0twCDaWi/JycpMTkbCsFJT2V5MwUTWsuNCUqqXpKSnBhAA==*/

      $pdo->rollBack();
      $teaBot and $teaBot->errorReport($e);
      return null;
    }

    return $fileId;
  }

  /**
   * @param string $fullHexHash
   * @return string
   */
  public static function genIndexPath(string $fullHexHash): string
  {
    return implode("/", str_split(substr($fullHexHash, 0, 14), 2));
  }

  /**
   * @param string $dir
   * @return void
   */
  public static function mkdirRecursive(string $dir): void
  {
    $exp = explode("/", $dir);
    if (count($exp) == 1) return;

    $dir = "";
    foreach ($exp as $p) {
      $dir .= $p."/";
      is_dir($dir) or mkdir($dir, 0755);
    }
  }

  /** 
   * @param string $tgGroupId
   * @return ?int
   */
  public static function getLatestGroupPhoto(string $tgGroupId): ?int
  {
    $o = json_decode(
        Exe::getChat(
          [
            "chat_id" => $tgGroupId
          ]
        )->getBody()->__toString(),
        true
    );

    return isset($o["result"]["photo"]["big_file_id"])
      ? static::fileResolve($o["result"]["photo"]["big_file_id"])
      : null;
  }


  /**
   * @param string $tgUserId
   * @return ?int
   */
  public static function getLatestUserPhoto(string $tgUserId): ?int
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
          return self::fileResolve($p["file_id"]);
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
  public static function groupAdminResolve(int $tgGroupId, ?int $groupId = null): void
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
          $userId = self::userInsert(
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


  /**
   * @const array
   */
  const GROUP_INSERT_MANDATORY_FIELDS = [
    "tg_group_id",
    "name",
    "username",
  ];

  /**
   * @const array
   */
  const GROUP_INSERT_DEFAULT_VALUES = [
    "photo" => null,
    "msg_count" => 0
  ];

  /**
   * @param array $data
   * @return int
   * @throws \TeaBot\Telegram\Exceptions\LoggerException
   */
  public static function groupInsert(array $data): ?int
  {
    foreach (self::GROUP_INSERT_MANDATORY_FIELDS as $v) {
      if (!array_key_exists($v, $data)) {
        throw new LoggerException(
          "Invalid data to be inserted (missing mandatory fields): "
          .json_encode($data));
      }
    }

    foreach (self::GROUP_INSERT_DEFAULT_VALUES as $k => $v) {
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
    $st = $pdo->prepare("SELECT `id`,`name`,`username`,`photo`,`link`,`msg_count` FROM `tg_groups` WHERE `tg_group_id` = ?");
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
   * @const array
   */
  const USER_INSERT_MANDATORY_FIELDS = [
    "tg_user_id",
    "username",
    "first_name",
    "last_name",
    "is_bot"
  ];

  /**
   * @const array
   */
  const USER_INSERT_DEFAULT_VALUES = [
    "photo" => null,
    "group_msg_count" => 0,
    "private_msg_count" => 0
  ];


  /**
   * @param array $data
   * @return int
   * @throws \TeaBot\Telegram\Exceptions\LoggerException
   */
  public static function userInsert(array $data): int
  {
    foreach (self::USER_INSERT_MANDATORY_FIELDS as $v) {
      if (!array_key_exists($v, $data)) {
        throw new LoggerException(
          "Invalid data to be inserted (missing mandatory fields): "
          .json_encode($data));
      }
    }

    foreach (self::USER_INSERT_DEFAULT_VALUES as $k => $v) {
      isset($data[$k]) or $data[$k] = $v;
    }

    /**
     * Check whether the user has already been
     * stored in database or not.
     */
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT `id`,`username`,`first_name`,`last_name`,`photo`,`group_msg_count`,`private_msg_count` FROM `tg_users` WHERE `tg_user_id` = ?");
    $st->execute([$data["tg_user_id"]]);

    $createUserHistory = false;

    if ($u = $st->fetch(PDO::FETCH_ASSOC)) {

      /**
       * We need to build the query based
       * on differential condition in
       * order to reduce query size.
       */
      $updateData = [];
      $exeUpdate = false;
      $query = "UPDATE `tg_users` SET ";


      if ($data["group_msg_count"] != 0) {
        $query .= "`group_msg_count`=`group_msg_count`+1";
        $exeUpdate = true;
      } else
      if ($data["private_msg_count"] != 0) {
        $query .= "`private_msg_count`=`private_msg_count`+1";
        $exeUpdate = true;
      }

      if ($data["username"] !== $u["username"]) {
        $query .= ($exeUpdate ? "," : "")."`username`=:username";
        $updateData["username"] = $data["username"];
        $exeUpdate = $createUserHistory = true;
      }

      if ($data["first_name"] !== $u["first_name"]) {
        $query .= ($exeUpdate ? "," : "")."`first_name`=:first_name";
        $updateData["first_name"] = $data["first_name"];
        $exeUpdate = $createUserHistory = true;
      }

      if ($data["last_name"] !== $u["last_name"]) {
        $query .= ($exeUpdate ? "," : "")."`last_name`=:last_name";
        $updateData["last_name"] = $data["last_name"];
        $exeUpdate = $createUserHistory = true;
      }

      if ($exeUpdate) {
        $query .= " WHERE `id` = :id";
        $updateData["id"] = $u["id"];
        $pdo->prepare($query)->execute($updateData);

        /**
         * In case createUserHistory is true,
         * we should assume the photo is the
         * same as before if and only if the
         * logger does not fetch the photo.
         */
        if ($createUserHistory && is_null($data["photo"])) {
          $data["photo"] = $u["photo"];
        }
      }

      $data["user_id"] = $u["id"];

    } else {
      $data["photo"] = self::getLatestUserPhoto($data["tg_user_id"]);

      /* Insert new user to database. */
      $st = $pdo->prepare("INSERT INTO `tg_users` (`tg_user_id`,`username`,`first_name`,`last_name`,`photo`,`group_msg_count`,`private_msg_count`,`is_bot`,`created_at`) VALUES (:tg_user_id, :username, :first_name, :last_name, :photo, :group_msg_count, :private_msg_count, :is_bot, NOW()) ON DUPLICATE KEY UPDATE `id`=LAST_INSERT_ID(`id`)");
      $st->execute($data);

      $createUserHistory = ($st->rowCount() == 1);
      $data["user_id"] = $pdo->lastInsertId();
    }


    if ($createUserHistory) {

      /* Unset unused keys. */
      $data = array_filter($data, function ($k) {
        return in_array($k, ["user_id", "username", "first_name", "last_name", "photo"]);
      }, ARRAY_FILTER_USE_KEY);

      /* Record user history. */
      $pdo->prepare("INSERT INTO `tg_user_history` (`user_id`, `username`, `first_name`, `last_name`, `photo`, `created_at`) VALUES (:user_id, :username, :first_name, :last_name, :photo, NOW())")
        ->execute($data);

    }


    return (int)$data["user_id"];
  }
}
