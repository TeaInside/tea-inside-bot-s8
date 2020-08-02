<?php

namespace TeaBot\Telegram\LoggerFoundationTraits;

use DB;
use PDO;
use TeaBot\Telegram\Exe;
use TeaBot\Telegram\Exceptions\LoggerException;

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
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\LoggerFoundationTraits
 * @version 8.0.0
 */
trait UserResolver
{

  /**
   * @param array $data
   * @return int
   * @throws \TeaBot\Telegram\Exceptions\LoggerException
   */
  public static function userInsert(array $data): int
  {
    foreach (USER_INSERT_MANDATORY_FIELDS as $v) {
      if (!array_key_exists($v, $data)) {
        throw new LoggerException(
          "Invalid data to be inserted (missing mandatory fields): "
          .json_encode($data));
      }
    }

    foreach (USER_INSERT_DEFAULT_VALUES as $k => $v) {
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
