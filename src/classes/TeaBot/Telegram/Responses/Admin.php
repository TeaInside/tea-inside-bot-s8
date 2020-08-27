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

      if (isset($ret["ok"], $ret["result"]) && $ret["ok"] && $ret["result"]) {

        $name = $this->data["first_name"].(
          isset($this->data["last_name"]) ? " ".$this->data["last_name"] : ""
        );

        $text =
          "<a href=\"tg://user?id={$this->data["user_id"]}\">"
          .htmlspecialchars($name)
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

    return true;
  }
}
