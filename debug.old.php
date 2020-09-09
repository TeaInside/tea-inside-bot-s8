<?php

require __DIR__."/src/build/helpers.php";

$targetDir = __DIR__."/src/classes";

if (isset($argv[1]) && in_array($argv[1], ["on", "off"])) {
  $switch = $argv[1];
  echo "Preparing to turn {$switch} debug flag...\n";

  if (isset($argv[2])) {
    $level = (int)$argv[2];
  } else {
    $level = -1;
  }

  if ($level < 0) {
    echo "Level: all level\n";
  } else {
    echo "Level: ".$level."\n";
  }

} else {
  echo "Usage: php ".$argv[0]." on|off\n";
  exit(1);
}

if ($switch === "off") {
  $callback = function (string $dir, string $file) use ($level) {
    $targetFile = $dir."/".$file;
    $content = file_get_contents($targetFile);
    $r1 = $r2 = [];

    if ($level < 0) {
      $regexPat = "/\/\*debug:(\d+)\*\/(.*?)\/\*enddebug\*\//Ssi";
    } else {
      $regexPat = "/\/\*debug:({$level})\*\/(.*?)\/\*enddebug\*\//Ssi";
    }

    if (preg_match_all($regexPat, $content, $m)) {
      echo "Turning off ".count($m[0])." debug flags in ".$targetFile."...";
      foreach ($m[0] as $k => $v) {
        $r1[] = $v;
        $r2[] = "/*__debug_flag:{$m[1][$k]}:".
          base64_encode(gzdeflate($m[2][$k], 9))."*/";
      }
      file_put_contents($targetFile, str_replace($r1, $r2, $content));
      echo "OK!\n";
    }
  };
} else if ($switch === "on") {
  $callback = function (string $dir, string $file) use ($level) {
    $targetFile = $dir."/".$file;
    $content = file_get_contents($targetFile);
    $r1 = $r2 = [];

    if ($level < 0) {
      $regexPat = "/\/\*__debug_flag:(\d+):(.+?)\*\//Ssi";
    } else {
      $regexPat = "/\/\*__debug_flag:({$level}):(.+?)\*\//Ssi";
    }

    if (preg_match_all($regexPat, $content, $m)) {
      echo "Turning on ".count($m[0])." debug flags in ".$targetFile."...";
      foreach ($m[0] as $k => $v) {
        $r1[] = $v;
        $r2[] = "/*debug:{$m[1][$k]}*/".
          @gzinflate(base64_decode($m[2][$k]))."/*enddebug*/";
      }
      file_put_contents($targetFile, str_replace($r1, $r2, $content));
      echo "OK!\n";
    }
  };
}

recursiveCallbackScanDir($targetDir, $callback);

echo "Finished!\n";
