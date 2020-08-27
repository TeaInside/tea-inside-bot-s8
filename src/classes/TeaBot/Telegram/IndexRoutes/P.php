<?php

namespace TeaBot\Telegram\IndexRoutes;

use TeaBot\Telegram\Response;
use TeaBot\Telegram\Responses;
use TeaBot\Telegram\IndexRouteFoundation;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\IndexRoutes
 * @version 8.0.0
 */
class P extends IndexRouteFoundation
{
  /**
   * @param \TeaBot\Telegram\Response $res
   * @param string                    $cmd
   * @param string                    $arg
   * @return bool
   */
  public static function execUnMapped(Response $res, string $cmd, string $arg): bool
  {

    /* Promote command. */
    if ($cmd === "promote") {
      if ($res->rtExec(Responses\Admin\Promote::class, "promote")) {
        return true;
      }
    }

    /* Promote me command. */
    if (($cmd === "promote_me") || ($cmd === "promoteme")) {
      if ($res->rtExec(Responses\Admin\Promote::class, "promoteMe")) {
        return true;
      }
    }

    return false;
  }
}
