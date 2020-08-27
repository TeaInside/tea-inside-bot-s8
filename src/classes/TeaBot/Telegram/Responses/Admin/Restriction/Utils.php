<?php

namespace TeaBot\Telegram\Responses\Admin\Restriction;

use TeaBot\Telegram\Exe;
use TeaBot\Telegram\ResponseFoundation;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Responses\Admin\Restriction
 * @version 8.0.0
 */
trait Utils
{
  /**
   * @param int $userId
   * @param int $chatId
   * @return mixed
   */
  public static function banMember(int $userId, int $chatId)
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
   * @param int $userId
   * @param int $chatId
   * @return mixed
   */
  public static function unbanMember(int $userId, int $chatId)
  {
    return json_decode(
      Exe::unbanChatMember(
        [
          "chat_id" => $chatId,
          "user_id" => $userId
        ]
      )->getBody()->__toString(),
      true
    );
  }

  /**
   * @param int $userId
   * @param int $chatId
   * @return array
   */
  public static function getPrivilegeInfo(int $userId, int $chatId): ?array
  {
    $ret = json_decode(
      Exe::getChatAdministrators(["chat_id" => $chatId])
        ->getBody()->__toString(),
      true
    );

    if ((!isset($ret["result"])) || (!is_array($ret["result"]))) {
      goto ret;
    }

    foreach ($ret["result"] as $k => $v) {
      if ($v["user"]["id"] === $userId) {
        return $v;
      }
    }

    ret:
    return null;
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
