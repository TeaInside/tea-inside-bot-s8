<?php

namespace TeaBot\Telegram;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
trait ResponseRoutes
{

  /**
   * @return bool
   */
  public function execRoutes(): bool
  {
    /* Skip edited message. */
    if ($this->data["is_edited_msg"]) {
      return false;
    }

    $text = $this->data["text"];

    /* Bot commands. */
    if (preg_match("/^(\/|\!|\~|\.)((\w)(?:\S+))(.*)/Ss", $text, $m)) {

      /* $m[1] start char.     */
      /* $m[2] the command.    */
      /* $m[3] Index char.     */
      $i = strtoupper($m[3]);
      /* $m[4] command arg     */

      $class = "\\TeaBot\\Telegram\\IndexRoutes\\".$i;
      if (class_exists($class)) {
        if ($class::exec($this, $m[2], trim($m[4]))) {
          return true;
        }
      }
    }

    



    // /* Promote command. */
    // if (preg_match("/^(\/|\!|\~|\.)promote$/USsi", $this->data["text"])) {
    //   if ($this->rtExec(Responses\Admin\Promote::class, "promote")) {
    //     return true;
    //   }
    // }

    // /* Promote me command. */
    // if (preg_match("/^(\/|\!|\~|\.)promote_me$/USsi", $this->data["text"])) {
    //   if ($this->rtExec(Responses\Admin\Promote::class, "promoteMe")) {
    //     return true;
    //   }
    // }

    // /* Ban command. */
    // if (preg_match("/^(?:\/|\!|\~|\.)ban((\s.*$)|$)/USsi", $this->data["text"], $m)) {
    //   if ($this->rtExec(Responses\Admin\Restriction::class, "ban", [$m[1]])) {
    //     return true;
    //   }
    // }

    // /* Kick command. */
    // if (preg_match("/^(?:\/|\!|\~|\.)kick((\s.*$)|$)/USsi", $this->data["text"], $m)) {
    //   if ($this->rtExec(Responses\Admin\Restriction::class, "kick", [$m[1]])) {
    //     return true;
    //   }
    // }

    return false;
  }
}
