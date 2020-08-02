<?php

require __DIR__."/src/build/helpers.php";

$targetDir = __DIR__."/src/classes";

if (isset($argv[1]) && in_array($argv[1], ["on", "off"])) {
  $switch = $argv[1];
} else {
  echo "Usage: php ".$argv[0]." on|off\n";
  exit(1);
}

if ($switch === "off") {
  $callback = function (string $dir, string $file) {
    $targetFile = $dir."/".$file;
    $content = file_get_contents($targetFile);
    $r1 = $r2 = [];
    if (preg_match_all("/\/\*debug\*\/(.*?)\/\*enddebug\*\//Ssi", $content, $m)) {
      echo "Turning off ".count($m[0])." debug flags in ".$targetFile."...";
      foreach ($m[0] as $k => $v) {
        $r1[] = $v;
        $r2[] = "/*__debug_flag:".base64_encode(gzdeflate($m[1][$k], 9))."*/";
      }
      file_put_contents($targetFile, str_replace($r1, $r2, $content));
      echo "OK!\n";
    }
  };
} else if ($switch === "on") {
  $callback = function (string $dir, string $file) {
    $targetFile = $dir."/".$file;
    $content = file_get_contents($targetFile);
    $r1 = $r2 = [];
    if (preg_match_all("/\/\*__debug_flag:(.+?)\*\//Ssi", $content, $m)) {
      echo "Turning on ".count($m[0])." debug flags in ".$targetFile."...";
      foreach ($m[0] as $k => $v) {
        $r1[] = $v;
        $r2[] = "/*debug*/".@gzinflate(base64_decode($m[1][$k]))."/*enddebug*/";
      }
      file_put_contents($targetFile, str_replace($r1, $r2, $content));
      echo "OK!\n";
    }
  };
}

recursiveCallbackScanDir($targetDir, $callback);
