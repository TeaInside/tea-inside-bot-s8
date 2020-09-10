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
  public const GENERAL_INPUT = 0;


  /**
   * @const int
   */
  public const USER_DATA = 1;


  /**
   * @var array
   */
  private array $in = [];


  /**
   * @var array
   */
  private array $ct = [];


  /**
   * @var int
   */
  private int $type;


  /**
   * @param array $in
   * @param int   $type
   *
   * Constructor.
   */
  public function __construct(array $in, int $type = self::GENERAL_INPUT)
  {
    $this->in       = $in;
    $this->ct["in"] = &$this->in;
    $this->type     = $type;

    switch ($type) {
      case self::GENERAL_INPUT:
        $this->constructGeneralInput();
        break;
      case self::USER_DATA:
        $this->constructUserData();
        break;
      default:
        throw new \Error("Invalid type {$type}");
        break;
    }
  }


  /**
   * @return void
   */
  private function constructUserData(): void
  {
    $in = $this->in;
    /* debug:assert */
    $requiredFields = [
      "id",
      "first_name",
      "is_bot"
    ];
    $missingFields = [];
    foreach ($requiredFields as $k => $v) {
      if (!array_key_exists($v, $in)) {
        $missingFields[] = $v;
      }
    }
    if (count($missingFields)) {
      throw new \Error("Missing fields: ".json_encode($missingFields));
    }
    /* end_debug */

    $this->ct["user_id"]    = $in["id"];
    $this->ct["username"]   = $in["username"] ?? null;
    $this->ct["first_name"] = $in["first_name"];
    $this->ct["last_name"]  = $in["last_name"] ?? null;
    $this->ct["full_name"]  = $in["first_name"];
    if (isset($in["last_name"])) {
      $this->ct["full_name"] .= " ".$in["last_name"];
    }
    $this->ct["is_bot"]     = $in["is_bot"];
  }


  /**
   * @return void
   */
  private function constructGeneralInput(): void
  {
    $in = $this->in;
    if (isset($in["update_id"]) && (isset($in["message"]) || isset($in["edited_message"]))) {
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

    if (isset($msg["from"]["first_name"])) {
      $this->ct["full_name"] = $msg["from"]["first_name"];

      if (isset($msg["from"]["last_name"])) {
        $this->ct["full_name"] .= " ".$msg["from"]["last_name"];
      }
    } else {
      $this->ct["full_name"] = null;
    }

    $this->ct["chat_title"] = $msg["chat"]["title"] ?? $this->ct["full_name"];
    $this->ct["chat_username"] = $msg["chat"]["username"] ?? null;
    $this->ct["is_forwarded_msg"] = isset($msg["forward_from"]);
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
