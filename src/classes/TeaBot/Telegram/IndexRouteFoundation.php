<?php

namespace TeaBot\Telegram;

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

  /**
   * @param array                     $maps
   * @param \TeaBot\Telegram\Response $res
   * @param string                    $cmd
   * @param string                    $arg
   * @return bool
   */
  public static function mapExec(array $maps, Response $res, string $cmd, string $arg): bool
  {
    if (isset($maps[$cmd])) {

      $map  = $maps[$cmd];
      $args = isset($map[3]) ? $map[3]($arg) : [];

      if (isset($map[4])) {
        $cond = is_callable($map[4]) ? $map[4]($res, $cmd, $arg) : (bool)$map[4];
      } else {
        $cond = true;
      }

      if ($cond && $res->rtExec($map[0], $map[1], $args)) {
        return true;
      }
    }

    return false;
  }
}
