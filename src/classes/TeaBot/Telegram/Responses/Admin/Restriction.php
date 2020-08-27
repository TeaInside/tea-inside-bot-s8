<?php

namespace TeaBot\Telegram\Responses\Admin;

use TeaBot\Telegram\Exe;
use TeaBot\Telegram\ResponseFoundation;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
class Restriction extends ResponseFoundation
{
    /**
   * @return bool
   */
  private function hasAbilityToUseBanHammer(): bool
  {
    if (in_array($this->data["user_id"], SUDOERS)) {
      return true;
    }

    /*
      TODO: Check if it is an admin with can_promote_members privilege.
    */

    return false;
  }

  /**
   * @return bool
   */
  public function ban(): bool
  {
    if (!$this->hasAbilityToUseBanHammer()) {
      /* Unauthorized user, ignoring... */
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
      $from["first_name"]
      .(isset($from["last_name"]) ? " ".$from["last_name"] : ""),
      $ret
    );

    ret:
    return true;
  }
}