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
    $targetFile = "{$dir}/{$file}";
    $content    = file_get_contents($targetFile);
    $r1 = $r2 = [];
    if (preg_match_all($rxp, $content, $m)) {
      foreach ($m[0] as $k => $v) {
        $comp = base64_encode(gzencode($m[2][$k], 9));
        $r1[] = $v;
        $r2[] = "/*__debug_flag({$m[1][$k]}):b64:gz:{$comp}*/";
      }
      echo str_replace($r1, $r2, $content);
      die;
    }
  };

} else
if ($argv[1] === "on") {

} else {
  usage();
  exit(1);
}

foreach (TARGET_DIR as $dir) {
  recursiveCallbackScanDir($dir, $callback);
}

