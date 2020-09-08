<?php

namespace TeaBot\Telegram\Loggers;

use DB;
use TeaBot\Telegram\Data;
use TeaBot\Telegram\LoggerFoundation;
use TeaBot\Telegram\LoggerUtils\User;
use TeaBot\Telegram\LoggerUtils\Group;
use TeaBot\Telegram\LoggerUtils\GroupMessage;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Loggers
 * @version 8.0.0
 */
class GroupLogger extends LoggerFoundation
{
  /**
   * @return void
   */
  public function run(): void
  {
    $data         = $this->data;
    $user         = new User($this->pdo);
    $group        = new Group($this->pdo);
    $isInsertUser = $isInsertGroup = false;

    $userInfo = [
      "username"   => $data["username"],
      "first_name" => $data["first_name"],
      "last_name"  => $data["last_name"],
      "is_bot"     => $data["is_bot"],
    ];
    $groupInfo = [
      "username" => $data["chat_username"],
      "name"     => $data["chat_title"],
    ];

    $userId  = $user->resolveUser($data["user_id"], $userInfo, $isInsertUser);
    $groupId = $group->resolveGroup($data["chat_id"], $groupInfo, $isInsertGroup);


    if ($isInsertUser || (!($userInfo["group_msg_count"] % 10))) {
      self::trackUserPhoto($data, $userInfo);
    }


    if ($isInsertGroup || (!($groupInfo["msg_count"] % 10))) {
      self::trackGroupPhoto($data, $groupInfo);
    }

    $groupMsg = new GroupMessage($this->pdo);
    $groupMsg->resolveMessage($userId, $groupId, $data);
  }


  /**
   * @param \TeaBot\Telegram\Data Data
   * @param array                 $userInfo
   * @return void
   */
  private static function trackUserPhoto(Data $data, array $userInfo): void
  {
    go(function () use ($data, $userInfo) {

      $f = [
        "username"   => true,
        "first_name" => true,
        "last_name"  => true,
        "is_bot"     => true
      ];

      $userInfo2 = array_filter(
        $userInfo,
        fn($k) => isset($f[$k]),
        ARRAY_FILTER_USE_KEY
      );

      /* TODO: Retrieve the user photo. */
      $userInfo2["photo"] = 10;

      $user = new User(DB::pdo());
      $user->dontTrackUpdate();
      $user->updateUser($userInfo, $userInfo2, "");
    });
  }


  /**
   * @param \TeaBot\Telegram\Data Data
   * @param array                 $groupInfo
   * @return void
   */
  private static function trackGroupPhoto(Data $data, array $groupInfo): void
  {
    go(function () use ($data, $groupInfo) {

      $f = [
        "username" => true,
        "name"     => true,
      ];

      $groupInfo2 = array_filter(
        $groupInfo,
        fn($k) => isset($f[$k]),
        ARRAY_FILTER_USE_KEY
      );

      /* TODO: Retrieve the group photo. */
      $groupInfo2["photo"] = 1;

      $group = new Group(DB::pdo());
      $group->dontTrackUpdate();
      $group->updateGroup($groupInfo, $groupInfo2, "");

      /* TODO: Retrieve group admin. */
    });
  }
}
