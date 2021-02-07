<?php

namespace TeaBot\Telegram;

$telegramDaemonLogLevel = 5;
$telegramDaemonLogStreams = [];

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class Log
{
  /**
   * @param resource $stream
   * @return void
   */
  public static function registerLogStream($stream): void
  {
    global $telegramDaemonLogStreams;
    $telegramDaemonLogStreams[] = $stream;
  }

  /**
   * @param int    $logLevel
   * @param string $format
   * @param mixed  ...$args
   */
  public static function log(int $logLevel, string $format, ...$args): void
  {
    global $telegramDaemonLogLevel, $telegramDaemonLogStreams;

    if ($telegramDaemonLogLevel >= $logLevel) {
      foreach ($telegramDaemonLogStreams as $stream) {
        fprintf($stream, "[%s] %s\n", date("c"), vsprintf($format, $args));
      }      
    }
  }
}
