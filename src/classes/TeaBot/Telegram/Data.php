<?php

namespace TeaBot\Telegram;

use Exception;
use ArrayAccess;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class Data implements ArrayAccess
{
  /**
   * @const int
   */
  public const MSG_TYPE_GENERAL = 1;

  /**
   * @var array
   */
  private $in;

  /**
   * @var array
   */
  private $ct;

  /**
   * @param array $in
   *
   * Constructor.
   */
  public function __construct(array $in)
  {
    $this->in = $in;
    $this->ct["in"] = &$this->in;

    if (isset($in["update_id"]) &&
       (isset($in["message"]) || isset($in["edited_message"]))
    ) {

      $msg = $in["message"] ?? $in["edited_message"] ?? null;

      if (isset($msg["text"])) {
        $this->ct["msg_type"] = "text";
        $this->ct["text"] = $msg["text"];
        $this->ct["text_entities"] = $msg["entities"] ?? null;
      } else
      if (isset($msg["photo"])) {
        $this->ct["msg_type"] = "photo";
        $this->ct["text"] = $msg["caption"] ?? null;
        $this->ct["photo"] = $msg["photo"];
        $this->ct["text_entities"] = $msg["caption_entities"] ?? null;
      } else
      if (isset($msg["sticker"])) {
        $this->ct["msg_type"] = "sticker";
        $this->ct["text"] = $msg["sticker"]["emoji"] ?? null;
      } else
      if (isset($msg["animation"])) {
        $this->ct["msg_type"] = "animation";
        $this->ct["text"] = $msg["caption"] ?? null;
        $this->ct["text_entities"] = $msg["caption_entities"] ?? null;
      } else
      if (isset($msg["voice"])) {
        $this->ct["msg_type"] = "voice";
        $this->ct["text"] = $msg["caption"] ?? null;
        $this->ct["text_entities"] = $msg["caption_entities"] ?? null;
      } else
      if (isset($msg["video"])) {
        $this->ct["msg_type"] = "video";
        $this->ct["text"] = $msg["caption"] ?? null;
        $this->ct["text_entities"] = $msg["caption_entities"] ?? null;
      } else
      if (isset($msg["new_chat_member"])) {
        $this->ct["msg_type"] = "new_chat_member";
      } else {
        $this->ct["msg_type"] = "unknown";
      }

      $this->buildGeneralMsg($msg, $in);
    
    }
  }

  /**
   * @param  array $msg
   * @param  array $in
   * @return void
   */
  private function buildGeneralMsg($msg, $in): void
  {
    $this->ct["msg"] = $msg;
    $this->ct["from"] = $msg["from"];
    $this->ct["chat"] = $msg["chat"];
    $this->ct["chat_id"] = $msg["chat"]["id"];
    $this->ct["user_id"] = $msg["from"]["id"];
    $this->ct["is_bot"] = $msg["from"]["is_bot"] ?? false;
    $this->ct["first_name"] = $msg["from"]["first_name"];
    $this->ct["last_name"] = $msg["from"]["last_name"] ?? null;
    $this->ct["msg_id"]  = $msg["message_id"];
    $this->ct["update_id"] = $in["update_id"];
    $this->ct["date"] = $msg["date"] ?? null;
    $this->ct["chat_type"] = $msg["chat"]["type"];
    $this->ct["username"] = $msg["from"]["username"] ?? null;
    $this->ct["reply_to"] = $msg["reply_to_message"] ?? null;
    $this->ct["full_name"] = (
      isset($msg["from"]["first_name"]) ?
      $msg["from"]["first_name"].
      (
        isset($msg["from"]["last_name"]) ? " ".$msg["from"]["last_name"] : ""
      ) : null
    );
    $this->ct["chat_title"] = $msg["chat"]["title"] ?? $this->ct["full_name"];
    $this->ct["chat_username"] = $msg["chat"]["username"] ?? null;
    $this->ct["is_forwarded_msg"] = isset($msg["forward_date"], $msg["forward_from"]);
    $this->ct["is_edited_msg"] = isset($in["edited_message"]);
  }

  /**
   * @return mixed
   */
  public function __get($key)
  {
    return $this->{$key} ?? null;
  }

  /**
   * @param mixed $idt
   * @param int   $updateId
   * @return \TeaBot\Telegram\Data
   */
  public static function buildMsg($idt, $updateId = -1): Data
  {
    return new self(
      [
        "update_id" => $updateId,
        "message" => $idt,
        "handle_replied_msg" => true
      ]
    );
  }

  /**
   * @param mixed $key
   * @return &mixed
   */
  public function &offsetGet($key)
  {
    if (!array_key_exists($key, $this->ct)) {
      $this->ct[$key] = null;
    }
    return $this->ct[$key];
  }

  /**
   * @param mixed $key
   * @param mixed &$data
   * @return void
   */
  public function offsetSet($key, $data)
  {
    throw new Exception("Cannot do offsetSet!");
  }

  /**
   * @param mixed $key
   * @return bool
   */
  public function offsetExists($key): bool
  {
    return isset($this->ct[$key]);
  }

  /**
   * @param mixed $key
   * @return void
   */
  public function offsetUnset($key)
  {
    unset($this->ct[$key]);
  }
}
