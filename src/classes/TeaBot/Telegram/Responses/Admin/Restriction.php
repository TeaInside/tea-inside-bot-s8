<?php

namespace TeaBot\Telegram\Responses\Admin;

use TeaBot\Telegram\Exe;
use TeaBot\Telegram\ResponseFoundation;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Responses\Admin
 * @version 8.0.0
 */
class Restriction extends ResponseFoundation
{
  use Restriction\Utils;

  /**
   * @return bool
   */
  private function hasAbilityToUseBanHammer(): bool
  {
    if (in_array($this->data["user_id"], SUDOERS)) {
      return true;
    }

    $info = self::getUserInfo($this->data["user_id"], $this->data["chat_id"]);

    if (!$info) {
      goto ret;
    }

    if (
      ($info["status"] === "creator")
      || (isset($info["can_restrict_members"]) && $info["can_restrict_members"])
    ) {
      return true;
    }

    ret:
    return false;
  }

  /**
   * @param ?string $reason
   * @return bool
   */
  public function ban(?string $reason = ""): bool
  {
    if (!$this->hasAbilityToUseBanHammer()) {
      /* Unauthorized user. */
      $this->dontHavePermission();
      goto ret;
    }

    if (!isset($this->data["reply_to"])) {
      /* No replied message, ignoring... */
      goto ret;
    }

    if (!isset($this->data["reply_to"]["from"]["id"])) {
      /* No user_id to replied message, ignoring... */
      goto ret;
    }

    $replyTo = $this->data["reply_to"];
    $from    = $replyTo["from"];
    $userId  = $from["id"];

    /* Ban member here. */
    $ret = self::banMember($userId, $this->data["chat_id"]);

    $this->sendBanMessage(
      $userId,
      $from["first_name"]
      .(isset($from["last_name"]) ? " ".$from["last_name"] : ""),
      "banned",
      trim((string)$reason),
      $ret
    );

    ret:
    return true;
  }


  /**
   * @param ?string $reason
   * @return bool
   */
  public function unban(?string $reason = ""): bool
  {
    if (!$this->hasAbilityToUseBanHammer()) {
      /* Unauthorized user. */
      $this->dontHavePermission();
      goto ret;
    }

    if (!isset($this->data["reply_to"])) {
      /* No replied message, ignoring... */
      goto ret;
    }

    if (!isset($this->data["reply_to"]["from"]["id"])) {
      /* No user_id to replied message, ignoring... */
      goto ret;
    }

    $replyTo = $this->data["reply_to"];
    $from    = $replyTo["from"];
    $userId  = $from["id"];

    /* Ban member here. */
    $ret = self::unbanMember($userId, $this->data["chat_id"]);

    $this->sendBanMessage(
      $userId,
      $from["first_name"]
      .(isset($from["last_name"]) ? " ".$from["last_name"] : ""),
      "unbanned",
      trim((string)$reason),
      $ret
    );

    ret:
    return true;
  }

  /**
   * @return void
   */
  private function dontHavePermission(): void
  {
    Exe::sendMessage(
      [
        "chat_id"             => $this->data["chat_id"],
        "text"                => "You don't have permission to use this command!",
        "reply_to_message_id" => $this->data["msg_id"],
      ]
    );
  }
}
