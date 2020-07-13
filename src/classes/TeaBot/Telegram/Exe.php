<?php

namespace TeaBot\Telegram;

use Swlib\Saber;
use Swlib\Http\Uri;
use Swlib\Http\ContentType;
use Swlib\Http\BufferStream;
use Swlib\Http\Exception\ClientException;
use Swlib\Http\Exception\ConnectException;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class Exe
{
  /**
   * @param string $method
   * @param array  $parameters
   * @return mixed
   */
  public static function __callStatic(string $method, array $parameters = [])
  {
    return self::execPost($method, $parameters[0] ?? []);
  }

  public static function execPost(string $path, array $body)
  {
    $tryCounter = 0;

    try_ll:
    try {
      $tryCounter++;
      $saber = Saber::create([
        "base_uri" => "https://api.telegram.org",
        "headers" => ["Content-Type" => ContentType::JSON]
      ]);
      $ret = $saber->post("/bot".BOT_TOKEN."/".$path, $body);
    } catch (ClientException $e) {
      $ret = $e;
      if ($tryCounter <= 3) goto try_ll;
    } catch (ConnectException $e) {
      $ret = $e;
      if ($tryCounter <= 3) goto try_ll;
    }
    return $ret;
  }
}
