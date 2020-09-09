<?php

require __DIR__."/src/build/helpers.php";

function usage() { echo "Usage: php {$argv[0]} [on|off] [code]\n"; }

if (!isset($argv[1])) {
  usage();
  exit(1);
}

const TARGET_DIR = [
  __DIR__."/src/classes",
  __DIR__."/daemon"
];

$argv[1] = strtolower(trim($argv[1]));
$code    = isset($argv[2]) ? preg_quote(trim($argv[2])) : null;

if ($argv[1] === "off") {

  if (is_string($code)) {
    $rxp = "/\/\*\s*debug\s*:\s*({$code})\s*\*\/(.*?)\/\*\s*end_debug\s*\*\//Ssi";
  } else {
    $rxp = "/\/\*\s*debug\s*:\s*([\w\d]+)\s*\*\/(.*?)\/\*\s*end_debug\s*\*\//Ssi";
  }

  $callback = function (string $dir, string $file) use ($rxp) {

    $ext = explode(".", $file);
    if (end($ext) !== "php") {
      return;
    }

    $targetFile = "{$dir}/{$file}";
    $content    = file_get_contents($targetFile);
    $r1 = $r2 = [];
    if (preg_match_all($rxp, $content, $m)) {
      echo "Turning off ".count($m[0])." debug code in {$targetFile}...";
      foreach ($m[0] as $k => $v) {
        $comp = base64_encode(gzencode($m[2][$k], 9));
        $r1[] = $v;
        $r2[] = "/*__debug_code({$m[1][$k]}):b64:gz:{$comp}*/";
      }
      file_put_contents($targetFile, str_replace($r1, $r2, $content));
      echo "OK!\n";
    }
  };

} else
if ($argv[1] === "on") {

  if (is_string($code)) {
    $rxp = "/\/\*__debug_code\(({$code})\):b64:gz:(.*?)\*\//Ssi";
  } else {
    $rxp = "/\/\*__debug_code\(([\w\d]+)\):b64:gz:(.*?)\*\//Ssi";
  }

  $callback = function (string $dir, string $file) use ($rxp) {

    $ext = explode(".", $file);
    if (end($ext) !== "php") {
      return;
    }

    $targetFile = "{$dir}/{$file}";
    $content    = file_get_contents($targetFile);
    $r1 = $r2 = [];
    if (preg_match_all($rxp, $content, $m)) {

      echo "Turning on ".count($m[0])." debug code in {$targetFile}...";
      foreach ($m[0] as $k => $v) {
        $code = gzdecode(base64_decode($m[2][$k]));
        $r1[] = $v;
        $r2[] = "/* debug:{$m[1][$k]} */{$code}/* end_debug */";
      }
      file_put_contents($targetFile, str_replace($r1, $r2, $content));
      echo "OK!\n";
    }
  };

} else {
  usage();
  exit(1);
}

foreach (TARGET_DIR as $dir) {
  recursiveCallbackScanDir($dir, $callback);
}

echo "Finished!\n";
