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
class S extends IndexRouteFoundation
{
  /**
   * @param \TeaBot\Telegram\Response $res
   * @param string                    $cmd
   * @param string                    $arg
   * @return bool
   */
  public static function execMapped(Response $res, string $cmd, string $arg): bool
  {
    static $maps = [
      "start" => [Responses\Start::class, "start"],
    ];

    if (isset($maps[$cmd])) {

      $map  = $maps[$cmd];
      $args = isset($map[3]) ? $map[3]($arg) : [];

      if ($res->rtExec($map[0], $map[1], $args)) {
        return true;
      }
    }

    return false;
  }
}
