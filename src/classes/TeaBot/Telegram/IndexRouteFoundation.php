<?php

namespace TeaBot\Telegram;

use TeaBot\Telegram\Response;
use TeaBot\Telegram\Responses;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\IndexRoutes
 * @version 8.0.0
 */
abstract class IndexRouteFoundation
{
  /**
   * @param \TeaBot\Telegram\Response $res
   * @param string                    $cmd
   * @param string                    $arg
   * @return bool
   */
  public static function execMapped(Response $res, string $cmd, string $arg): bool
  {
    return false;
  }

  /**
   * @param \TeaBot\Telegram\Response $res
   * @param string                    $cmd
   * @param string                    $arg
   * @return bool
   */
  public static function execUnMapped(Response $res, string $cmd, string $arg): bool
  {
    return false;
  }

  /**
   * @param \TeaBot\Telegram\Response $res
   * @param string                    $cmd
   * @param string                    $arg
   * @return bool
   */
  public static function exec(Response $res, string $cmd, string $arg): bool
  {
    if (static::execMapped($res, $cmd, $arg)) {
      return true;
    }

    if (static::execUnMapped($res, $cmd, $arg)) {
      return true;
    }

    return false;
  }
}
