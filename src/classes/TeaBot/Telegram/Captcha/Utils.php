<?php

namespace TeaBot\Telegram\Captcha;

use Swlib\Saber;
use Swlib\Http\Uri;
use Swlib\Http\ContentType;
use Swlib\Http\BufferStream;
use Swlib\Http\Exception\ConnectException;
use Swlib\Http\Exception\TransferException;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Captcha
 * @version 8.0.0
 */
class Utils
{
  /**
   * @param string $latex
   * @param string $bcolor
   * @param string $border
   * @param int    $d
   * @return ?string
   */
  public static function genLatex(string $latex, string $bcolor = "white", $border = "60x60", $d = 200): ?string
  {
    $ret = null;
    $payload = [
      "bcolor"  => $bcolor,
      "border"  => $border,
      "content" => $latex,
      "d" => $d
    ];

    $tryCounter = 0;

    try_ll:
    try {

      $tryCounter++;
      $saber = Saber::create([
        "base_uri" => "https://latex.teainside.org",
        "headers"  => ["Content-Type" => ContentType::JSON],
        "timeout"  => 500,
      ]);
      $rrr = $saber->post("/api.php?action=tex2png", $payload);

    } catch (TransferException $e) {

      $rrr = $e->getResponse();

      if (is_null($rrr) && ($tryCounter <= 5)) goto try_ll;

    } catch (ConnectException $e) {
      if ($tryCounter <= 5) goto try_ll;
    }

    $o = json_decode($rrr->getBody()->__toString(), true);
    if (isset($o["res"])) {
      $ret = "https://latex.teainside.org/latex/png/".$o["res"].".png";
    } else {
      echo "Cannot generate captcha: ".$o;
    }

    return $ret;
  }
}
