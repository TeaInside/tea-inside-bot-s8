<?php

namespace TeaBot\Telegram;

use DB;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class Dlog
{

  /**
   * @var array
   */
  private static $err = [];

  /**
   * @var array
   */
  private static $out = [];

  /**
   * @var \TeaBot\Telegram\Dlog
   */
  private static $dlog;

  /**
   * Constructor.
   */
  private function __construct()
  {
  }

  /** 
   * Destructor.
   */
  public function __destruct()
  {
    self::close();
  }

  /**
   * @return \TeaBot\Telegram\Dlog
   */
  public function getIns(): Dlog
  {
    return self::$dlog ?? (self::$dlog = new self);
  }

  /**
   * @param resource $res
   * @return void
   */
  public static function registerErrHandler($res)
  {
    self::$err[] = $res;
    self::getIns();
  }

  /**
   * @param resource $res
   * @return void
   */
  public static function regsiterOutHandler($res)
  {
    self::$out[] = $res;
    self::getIns();
  }

  /**
   * @return void
   */
  public static function close(): void
  {
    foreach (self::$err as $handle) @fclose($handle);
    foreach (self::$out as $handle) @fclose($handle);
  }

  /**
   * @param string $format
   * @param mixed  ...$args
   * @return void
   */
  public static function err(string $format, ...$args): void
  {
    foreach (self::$err as $handle) {
      @flock($handle, LOCK_EX);
      @fprintf($handle, "[%s][warning]: %s", date("c"), vsprintf($format, $args));
      @flock($handle, LOCK_UN);
    }
  }

  /**
   * @param string $format
   * @param mixed  ...$args
   * @return void
   */
  public static function out(string $format, ...$args): void
  {
    foreach (self::$out as $handle) {
      @flock($handle, LOCK_EX);
      @fprintf($handle, "[%s][out]: %s", date("c"), vsprintf($format, $args));
      @flock($handle, LOCK_UN);
    }
  }
}
