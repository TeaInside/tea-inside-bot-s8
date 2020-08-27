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
   * @param ?string $reason
   * @return bool
   */
  public function ban(?string $reason = ""): bool
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
      "banned",
      trim((string)$reason),
      $ret
    );

    ret:
    return true;
  }


  /**
   * @param int $userId
   * @param int $chatId
   * @return mixed
   */
  private static function banMember(int $userId, int $chatId)
  {
    return json_decode(
      Exe::kickChatMember(
        [
          "chat_id" => $chatId,
          "user_id" => $userId
        ]
      )->getBody()->__toString(),
      true
    );
  }


  /**
   * @param string $name
   * @param string $rtype
   * @param string $reason
   * @param mixed  $ret
   * @return void
   */
  private function sendBanMessage(
    string $name, string $rtype, string $reason, $ret): void
  {
    if (isset($ret["ok"], $ret["result"]) && $ret["ok"] && $ret["result"]) {
      $text =
        "<a href=\"tg://user?id={$this->data["user_id"]}\">"
        .htmlspecialchars($name, ENT_QUOTES, "UTF-8")
        ."</a> has been {$rtype} from the group!";

      if ($reason !== "") {
        $text .= "\n\n<b>Reason</b>: ".htmlspecialchars($reason, ENT_QUOTES, "UTF-8");
      }

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
