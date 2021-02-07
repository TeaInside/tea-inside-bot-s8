<?php

namespace TeaBot\Telegram\Responses\Admin\Promotion;

use TeaBot\Telegram\Exe;
use TeaBot\Telegram\ResponseFoundation;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Responses\Admin\Promotion
 * @version 8.0.0
 */
trait Utils
{

  /**
   * @param int $userId
   * @param int $chatId
   * @return mixed
   */
  private static function promoteMember(int $userId, int $chatId)
  {
    /* TODO: Custom privilege by parameters. */
    return json_decode(
      Exe::promoteChatMember(
        [
          "chat_id"              => $chatId,
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
  }


  /**
   * @param int    $userId
   * @param string $name
   * @param mixed  $ret
   * @return void
   */
  private function sendPromoteMessage(int $userId, string $name, $ret): void
  {
    if (isset($ret["ok"], $ret["result"]) && $ret["ok"] && $ret["result"]) {
      $text =
        "<a href=\"tg://user?id={$userId}\">"
        .htmlspecialchars($name, ENT_QUOTES, "UTF-8")
        ."</a> has been promoted to be an administrator!";
      $r = [
        "chat_id"             => $this->data["chat_id"],
        "reply_to_message_id" => $this->data["msg_id"],
        "text"                => $text,
        "parse_mode"          => "HTML",
      ];
    } else {      
      $r = [
        "chat_id"             => $this->data["chat_id"],
        "reply_to_message_id" => $this->data["msg_id"],
        "text"                => json_encode($ret, JSON_PRETTY_PRINT),
      ];
    }

    Exe::sendMessage($r);
  }
}
