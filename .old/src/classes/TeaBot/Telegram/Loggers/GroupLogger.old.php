<?php

namespace TeaBot\Telegram\Loggers;

use DB;
use TeaBot\Telegram\Exe;
use TeaBot\Telegram\Data;
use TeaBot\Telegram\Dlog;
use TeaBot\Telegram\LoggerFoundation;
use TeaBot\Telegram\LoggerUtils\File;
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
      $userInfo["id"] = $userId;
      $user->trackPhoto();
    }


    if ($isInsertGroup || (!($groupInfo["msg_count"] % 10))) {
      $groupInfo["id"] = $groupId;
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
    /* debug:assert */
    if (!isset($userInfo["id"])) {
      throw new \Error("Missing field: [\"id\"]");
    }
    /* end_debug */
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

      /* debug:p3 */
      Dlog::out("Getting user profile photo: %s",
        json_encode(
          [
            "user_id" => $data["user_id"],
            "chat_id" => $data["chat_id"]
          ]
        )
      );
      /* end_debug */

      $ret  = Exe::getUserProfilePhotos([
        "user_id" => $data["user_id"],
        "offset"  => 0,
        "limit"   => 1,
      ]);

      $j = json_decode($ret->getBody()->__toString(), true);


      /* debug:warning */
      $__json_warning = json_encode(
        [
          "user_id" => $data["user_id"],
          "chat_id" => $data["chat_id"]
        ]
      );
      /* end_debug */

      if (!isset($j["result"]["photos"][0])) {
        /* Cannot get the photo or the user may not have. */
        /* debug:warning */
        Dlog::err(
          "Cannot retrieve photo from getUserProfilePhotos: %s",
          $__json_warning
        );
        /* end_debug */
        return;
      }
      
      $photo = $j["result"]["photos"][0];
      usort($photo, function ($p1, $p2) {
        return
          ($p2["width"] * $p2["height"]) <=>
          ($p1["width"] * $p1["height"]);
      });
      $photo = $photo[0];

      if (!isset($photo["file_id"])) {
        /* Cannot get the photo or the user may not have. */
        /* debug:warning */
        Dlog::err(
          "Cannot retrieve photo (cannot find the file_id): %s",
          $__json_warning
        );
        /* end_debug */
        return;
      }

      $pdo  = DB::pdo();

      $file = new File($pdo);
      $userInfo2["photo"] = $file->resolveFile($photo["file_id"]);
      unset($file);

      // $user = new User($pdo);
      // $user->dontTrackUpdate();
      // $user->updateUser($userInfo, $userInfo2, "");
    });
  }


  /**
   * @param \TeaBot\Telegram\Data Data
   * @param array                 $groupInfo
   * @return void
   */
  private static function trackGroupPhoto(Data $data, array $groupInfo): void
  {
    /* debug:assert */
    if (!isset($groupInfo["id"])) {
      throw new \Error("Missing field: [\"id\"]");
    }
    /* end_debug */
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
