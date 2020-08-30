<?php

namespace TeaBot\Telegram\Captcha\Entry\ComputerScience;

use TeaBot\Telegram\Exe;
use TeaBot\Telegram\Data;
use TeaBot\Telegram\Captcha\Utils;
use TeaBot\FloatingPoint as BaseFloatingPoint;
use TeaBot\Telegram\Captcha\Entry\CaptchaEntry;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Captcha
 * @version 8.0.0
 */
class FloatingPoint extends CaptchaEntry
{
  /**
   * @return bool
   */
  public function run(): bool
  {
    switch (rand(0, 0)) {
      case 0:
        $ret = $this->mantisa();
        break;

      default:
        $ret = $this->mantisa();
        break;
    }
    return $ret;
  }

  /**
   * @return bool
   */
  private function mantisa(): bool
  {
    $st = new BaseFloatingPoint();

    $latexImgUrl = Utils::genLatex(<<<LATEX
\\documentclass[12pt]{article}
\\usepackage{amsmath}
\\usepackage{amssymb}
\\usepackage{amsfonts}
\\usepackage{color}
\\usepackage{xcolor}
\\usepackage[utf8]{inputenc}
\\thispagestyle{empty}
\\begin{document}
\\noindent Calculate the mantisa for the following floating point number, based on IEEE-754 standard with single precision. \\\\
\$\$\\textbf{\\color{red}{$st->str}}\$\$
Answer in \\textbf{decimal} format!
\\end{document}
LATEX);

    // $latexImgUrl = "https://latex.teainside.org/latex/png/bb9d4d79e793148db69fc52744c09d0841be8501.png";


    $d             = $this->data;
    $correctAnswer = sprintf("%d", $st->mantisa);
    $text          = 
      "<a href=\"tg://user?id={$d["user_id"]}\">".e($d["full_name"])."</a>"
      .(isset($d["username"]) ? " (@{$d["username"]})" : "")
      ."\nPlease solve this captcha problem to make sure you are a human, otherwise you will be kicked in 10 minutes!";

    $ret  = Exe::sendPhoto(
      [
        "chat_id"     => $d["chat_id"],
        "photo"       => $latexImgUrl,
        "caption"     => $text,
        "parse_mode"  => "HTML",
      ]
    );
    $json = json_decode($ret->getBody()->__toString(), true);

    if (isset($json["result"]["message_id"])) {
      $msgId = $json["result"]["message_id"];
    } else {
      $msgId = null;
    }

    $this->writeCaptchaFile(
      [
        "msg_id"          => $msgId,
        "chat_id"         => $d["chat_id"],
        "user_id"         => $d["user_id"],
        "photo"           => $latexImgUrl,
        "correct_answer"  => 300,
      ]
    );

    /* Captcha timer here. */
    go(function () {

      $success   = false;
      $startTime = time();
      do {
        $curTime = time();
        echo "@";
        usleep(500000);

        if (!$this->isHavingCaptcha()) {
          $success = true;
          break;
        }

      } while (($curTime - $startTime) <= 60);

      if (!$success) {

        var_dump("=== scsc ===", $success, ($curTime - $startTime));
        $this->captchaFailKick();
      } else {
        var_dump("=== success ===", $success, ($curTime - $startTime));
      }

    });

    return true;
  }
}
