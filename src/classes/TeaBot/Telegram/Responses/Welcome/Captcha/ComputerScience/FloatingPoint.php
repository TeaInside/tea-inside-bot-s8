<?php

namespace TeaBot\Telegram\Responses\Welcome\Captcha\ComputerScience;

use TeaBot\FloatingPoint as BaseFloatingPoint;
use TeaBot\Telegram\Responses\Welcome\Captcha\CaptchaFoundation;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram\Responses\Welcome\Captcha\ComputerScience
 * @version 8.0.0
 */
class FloatingPoint extends CaptchaFoundation
{

  /**
   * @return bool
   */
  public function run(): bool
  {
    $this->mantisa();
    return true;
  }

  /**
   * @return bool
   */
  private function mantisa(): bool
  {
    $handle = fopen($this->captchaFile, "w");
    flock($handle, LOCK_SH);

    $st          = new BaseFloatingPoint();
//     $latexImgUrl = $this->genLatex(<<<LATEX
// \\documentclass[12pt]{article}
// \\usepackage{amsmath}
// \\usepackage{amssymb}
// \\usepackage{amsfonts}
// \\usepackage{color}
// \\usepackage{xcolor}
// \\usepackage[utf8]{inputenc}
// \\thispagestyle{empty}
// \\begin{document}
// \\noindent Calculate the mantisa for the following floating point number, based on IEEE-754 standard with single precision. \\\\
// \$\$\\textbf{\\color{red}{$st->str}}\$\$
// Answer in \\textbf{decimal} format!
// \\end{document}
// LATEX);
  
    $latexImgUrl = "https://latex.teainside.org/latex/png/bb9d4d79e793148db69fc52744c09d0841be8501.png";

    $correctAnswer = sprintf("%d", $st->mantisa);
    $name   = $this->data["first_name"].(
      isset($this->data["last_name"]) ? " ".$this->data["last_name"] : ""
    );
    $text   = 
      "<a href=\"tg://user?id={$this->data["user_id"]}\">"
      .htmlspecialchars($name, ENT_QUOTES, "UTF-8")."</a>"
      .(isset($this->data["username"]) ? " (@{$this->data["username"]})" : "")
      ."\nPlease solve this captcha problem to make sure you are a human, otherwise you will be kicked in 10 minutes!";

    $ret    = $this->sendCaptchaPhoto($text, $latexImgUrl, "html");


    fwrite($handle, json_encode(
      [
        "chat_id" => $this->data["chat_id"],
        "user_id" => $this->data["user_id"],
        "text"    => $text,
        "photo"   => $latexImgUrl,
        "correct_answer" => $correctAnswer
      ],
      JSON_UNESCAPED_SLASHES
    ));
    fclose($handle);

    $startTime = time();
    do {
      $curTime = time();
      echo ".";
      usleep(500000);
    } while (($curTime - $startTime) <= 300);

    return true;
  }
}
