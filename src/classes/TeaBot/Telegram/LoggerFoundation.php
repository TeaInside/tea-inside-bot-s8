<?php

namespace TeaBot\Telegram;

use DB;
use PDO;
use TeaBot\Exe;
use TeaBot\Telegram\Exceptions\LoggerException;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
abstract class LoggerFoundation
{
  /**
   * @var \Data
   */
  protected $data;

  /**
   * @param \Data &$data
   *
   * Constructor.
   */
  final public function __construct(Data &$data)
  {
    $this->data = $data;
  }

  /**
   * @param string $telegramFileId
   * @return ?int
   */
  public static function fileResolve(string $telegramFileId): ?int
  {
    /**
     * Check the $telegramFileId in database.
     * If it has already been stored, then returns
     * the stored primary key. Otherwise, it
     * downloads the file and insert it to the
     * database.
     */

    $pdo = DB::pdo();
    $st  = $pdo->prepare("SELECT `id` FROM `tg_files` WHERE tg_file_id = ?");
    $st->execute([$telegramFileId]);

    if ($r = $st->fetch(PDO::FETCH_NUM)) {
      return (int)$r[0];
    }

    /* This operation may error. */
    $v = json_decode(
      Exe::getFile(["file_id" => $telegramFileId])
      ->getBody()->__toString(),
      true
    );


    /* Return null if it cannot find the file path. */
    if (!isset($v["result"]["file_path"])) {
      return null;
    }


    /* Get file extension. */
    $fileExt = explode(".", $v["result"]["file_path"]);
    if (count($fileExt) > 1) {
      $fileExt = strtolower(end($fileExt));
    } else {
      $fileExt = null;
    }


    $tmpDownloadDir = "/tmp/telegram_tmp_download";
    $tmpFile = $tmpDownloadDir."/".bin2hex($telegramFileId).(
      isset($fileExt) ? ".".$fileExt : ""
    );


    /* Make sure the target directory exists. */
    is_dir(STORAGE_PATH) or mkdir(STORAGE_PATH);
    is_dir(STORAGE_PATH."/telegram") or mkdir(STORAGE_PATH."/telegram");
    is_dir(STORAGE_PATH."/telegram/files") or mkdir(STORAGE_PATH."/telegram/files");
    is_dir($tmpDownloadDir) or mkdir($tmpDownloadDir);


    /* Download the file. */
    $response = SaberGM::download(
      "https://api.telegram.org/file/bot".BOT_TOKEN."/".$v["result"]["file_path"],
      $tmpFile
    );


    /* Download failed. */
    if (!file_exists($tmpFile)) {
      return null;
    }


    $md5Hash    = md5_file($tmpFile, true);
    $sha1Hash   = sha1_file($tmpFile, true);
    $targetFile = bin2hex($md5Hash).bin2hex($sha1_file).(
      isset($fileExt) ? ".".$fileExt : ""
    );

    rename($tmpFile, $targetFile);

    return $fileId;
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
      $data["link"] = "https://t.me/".$username;
    } else {
      $data["link"] = null;
    }

    /**
     * Check whether the group has already been
     * stored in database or not.
     */
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT `id`,`name`,`username`,`photo`,`msg_count` FROM `tg_groups` WHERE `tg_group_id` = ?");
    $st->execute([$data["tg_group_id"]]);

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

      if ($data["username"] != $u["username"]) {
        $query .= ($exeUpdate ? "," : "")."`username`=:username";
        $updateData["username"] = $data["username"];
        $exeUpdate = $createGroupHistory = true;
      }

      if ($data["name"] != $u["name"]) {
        $query .= ($exeUpdate ? "," : "")."`name`=:name";
        $updateData["name"] = $data["name"];
        $exeUpdate = $createGroupHistory = true;
      }

      if ($exeUpdate) {
        $query .= " WHERE `id` = :id";
        $updateData["id"] = $u["id"];
        $pdo->prepare($query)->execute($updateData);
      }

      $data["group_id"] = $u["id"];

    } else {
      $pdo->prepare("INSERT INTO `tg_groups` (`tg_group_id`, `name`, `username`, `link`, `photo`, `msg_count`, `created_at`) VALUES (:tg_group_id, :name, :username, :link, :photo, :msg_count, NOW())")->execute($data);
      $createGroupHistory = true;
      $data["group_id"] = $pdo->lastInsertId();
    }


    if ($createGroupHistory) {

      /* Unset unused keys. */
      $data = array_filter($data, function ($k) {
        return in_array($k, ["group_id", "name", "username", "link", "photo"]);
      }, ARRAY_FILTER_USE_KEY);

      /* Record group history. */
      $pdo->prepare("INSERT INTO `tg_group_history` (`group_id`, `name`, `username`, `link`, `photo`, `created_at`) VALUES (:group_id, :name, :username, :link, :photo, NOW());")->execute($data);
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

      /**
       * In case createUserHistory is true,
       * we should assume the photo is the
       * same as before if and only if the
       * logger does not fetch the photo.
       */
      if (is_null($data["photo"])) {
        $data["photo"] = $u["photo"];
      }

      if ($exeUpdate) {
        $query .= " WHERE `id` = :id";
        $updateData["id"] = $u["id"];
        $pdo->prepare($query)->execute($updateData);
      }

      $data["user_id"] = $u["id"];

    } else {

      // $data["photo"] = self::getLatestUserPhoto($data["tg_user_id"]);

      /* Insert new user to database. */
      $pdo->prepare("INSERT INTO `tg_users` (`tg_user_id`,`username`,`first_name`,`last_name`,`photo`,`group_msg_count`,`private_msg_count`,`is_bot`,`created_at`) VALUES (:tg_user_id, :username, :first_name, :last_name, :photo, :group_msg_count, :private_msg_count, :is_bot, NOW())")
        ->execute($data);
      $createUserHistory = true;
      $data["user_id"] = $pdo->lastInsertId();

    }


    if ($createUserHistory) {

      /* Unset unused keys. */
      $data = array_filter($data, function ($k) {
        return in_array($k, ["user_id", "username", "first_name", "last_name", "photo"]);
      }, ARRAY_FILTER_USE_KEY);

      /* Record user history. */
      $pdo->prepare("INSERT INTO `tg_user_history` (`user_id`, `username`, `first_name`, `last_name`, `photo`, `created_at`) VALUES (:user_id, :username, :first_name, :last_name, :photo, NOW());")
        ->execute($data);

    }


    return (int)$data["user_id"];
  }
}
