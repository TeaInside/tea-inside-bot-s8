<?php

namespace TeaBot\Telegram\Responses;

use TeaBot\Telegram\Exe;
use TeaBot\Telegram\ResponseFoundation;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class Admin extends ResponseFoundation
{
  /**
   * @const array
   */
  const PROMOTE_ME_ALLOWED_GROUPS = [
    -1001226735471  => true, /* Private Cloud. */
    -1001149709623  => true, /* Test Driven Development. */
  ];

  /**
   * @return bool
   */
  public function promote(): bool
  {
    var_dump(0);

    if ($this->hasAbilityToPromoteOther()) {

      var_dump(1);

      if (!isset($this->ct["reply_to"])) {
        /* No replied message, ignoring... */
        goto ret;
      }

      var_dump(2);

      if (!isset($this->data["reply_to"]["from"]["id"])) {
        /* No user_id to replied message, ignoring... */
        goto ret;
      }

      var_dump(3);

      $userId = $this->data["reply_to"]["from"]["id"];
      $ret = json_decode(
        Exe::promoteChatMember(
          [
            "chat_id"              => $this->data["chat_id"],
            "user_id"              => $userId,
            "can_change_info"      => true,
            "can_delete_messages"  => true,
            "can_invite_users"     => true,
            "can_restrict_members" => true,
            "can_pin_messages"     => true,
            "can_promote_members"  => true,
          ]
        )->getBody()->__toString(),
        true
      );

      $this->sendPromoteMessage(
        $this->data["first_name"].(
          isset($this->data["last_name"]) ? " ".$this->data["last_name"] : ""
        ),
        $ret
      );
    }

    ret:
    return true;
  }


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
  public function promoteMe(): bool
  {
    if (isset(self::PROMOTE_ME_ALLOWED_GROUPS[$this->data["chat_id"]])) {
      $ret = json_decode(
        Exe::promoteChatMember(
          [
            "chat_id"              => $this->data["chat_id"],
            "user_id"              => $this->data["user_id"],
            "can_change_info"      => true,
            "can_delete_messages"  => true,
            "can_invite_users"     => true,
            "can_restrict_members" => true,
            "can_pin_messages"     => true,
            "can_promote_members"  => true,
          ]
        )->getBody()->__toString(),
        true
      );

      $this->sendPromoteMessage(
        $this->data["first_name"].(
          isset($this->data["last_name"]) ? " ".$this->data["last_name"] : ""
        ),
        $ret
      );
    }

    return true;
  }


  /**
   * @param string $name
   * @param mixed  $ret
   * @return void
   */
  private function sendPromoteMessage(string $name, $ret): void
  {
    if (isset($ret["ok"], $ret["result"]) && $ret["ok"] && $ret["result"]) {

      $text =
        "<a href=\"tg://user?id={$this->data["user_id"]}\">"
        .htmlspecialchars($name, ENT_QUOTES, "UTF-8")
        ."</a> has been promoted to be an administrator!";

      Exe::sendMessage(
        [
          "chat_id"             => $this->data["chat_id"],
          "reply_to_message_id" => $this->data["msg_id"],
          "text"                => $text,
          "parse_mode"          => "HTML",
        ]
      );
    } else {
      Exe::sendMessage(
        [
          "chat_id"             => $this->data["chat_id"],
          "reply_to_message_id" => $this->data["msg_id"],
          "text"                => json_encode($ret, JSON_PRETTY_PRINT),
        ]
      );
    }
  }
}
