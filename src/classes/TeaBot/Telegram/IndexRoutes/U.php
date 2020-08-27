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
class U extends IndexRouteFoundation
{
  /**
   * @param \TeaBot\Telegram\Response $res
   * @param string                    $cmd
   * @param string                    $arg
   * @return bool
   */
  public static function execUnMapped(Response $res, string $cmd, string $arg): bool
  {
    /* Unban command. */
    if ($cmd === "unban") {
      if ($res->rtExec(Responses\Admin\Restriction::class, "ban", [$arg])) {
        return true;
      }
    }

    return false;
  }
}
