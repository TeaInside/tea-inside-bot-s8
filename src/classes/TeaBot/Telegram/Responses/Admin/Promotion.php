<?php

namespace TeaBot\Telegram\Responses\Admin;

use TeaBot\Telegram\Exe;
use TeaBot\Telegram\ResponseFoundation;

/**
 * @const array
 */
const PROMOTE_ME_ALLOWED_GROUPS = [
  -1001226735471  => true, /* Private Cloud. */
  -1001149709623  => true, /* Test Driven Development. */
];

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Responses\Admin
 * @version 8.0.0
 */
class Promotion extends ResponseFoundation
{
  use Promotion\Utils;

  /**
   * @return bool
   */
  private function hasAbilityToPromoteOther(): bool
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
  public function promote(): bool
  {
    if (!$this->hasAbilityToPromoteOther()) {
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

    /* Promote member here. */
    $ret = self::promoteMember($userId, $this->data["chat_id"]);

    $this->sendPromoteMessage(
      $userId,
      $from["first_name"]
      .(isset($from["last_name"]) ? " ".$from["last_name"] : ""),
      $ret
    );

    ret:
    return true;
  }


  /**
   * @return bool
   */
  public function promoteMe(): bool
  {
    if (!isset(PROMOTE_ME_ALLOWED_GROUPS[$this->data["chat_id"]])) {
      /* Unauthorized group, ignoring... */
      goto ret;
    }

    $ret = self::promoteMember($this->data["user_id"], $this->data["chat_id"]);
    $this->sendPromoteMessage(
      $this->data["user_id"],
      $this->data["first_name"]
      .(isset($this->data["last_name"]) ? " ".$this->data["last_name"] : ""),
      $ret
    );

    ret:
    return true;
  }
}
