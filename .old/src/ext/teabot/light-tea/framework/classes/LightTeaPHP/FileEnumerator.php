<?php

namespace LightTeaPHP;

use Error;
use LightTeaPHP\Components\PHPClass;
use LightTeaPHP\Exceptions\LightTeaPHPException;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \LightTeaPHP
 * @version 0.0.1
 */
final class FileEnumerator
{

  /**
   * @var \LightTeaPHP\Builder
   */
  private $builder;


  /**
   * Constructor.
   *
   * @param \LightTeaPHP\Builder $builder
   */
  public function __construct(Builder $builder)
  {
    $this->builder = $builder;
  }


  /**
   * @return bool
   */
  public function run(): bool
  {

    $ltp = $this->builder->ins;

    $this->recursiveScan($ltp->sourceDir, $ltp->buildDir);

    return true;
  }


  /**
   * @param string $sourceDir
   * @param string $buildDir
   * @return bool
   */
  private function recursiveScan(string $sourceDir, string $buildDir): bool
  {
    $ins  = $this->builder->ins;
    $dirs = scandir($sourceDir);

    $curBase = ltrim(explode($ins->buildDir, $buildDir, 2)[1] ?? "", "/");

    foreach ($dirs as $k => $v) {

      if ($v === "." || $v === "..") continue;

      $rfile = "{$sourceDir}/{$v}";

      if (is_dir($rfile)) {
        $targetDir = "{$buildDir}/{$v}";
        is_dir($targetDir) or mkdir($targetDir);
        if (!$this->recursiveScan($rfile, $targetDir)) {
          return false;
        }
      } else {

        $sourceFile = $rfile;

        if (preg_match($ins->prePattern, $v, $m)) {

          $dothepre   = false;
          $targetFile = "{$buildDir}/{$m[1]}.{$m[2]}";

          LightTeaPHP::addFile(ltrim("{$curBase}/{$m[1]}.{$m[2]}", "/"));

          if (!file_exists($targetFile)) {
            $dothepre = true;
            goto dproc;
          }

          // $targetMtime = filemtime($targetFile);
          // $sourceMtime = filemtime($rfile);

          // if ($sourceMtime > $targetMtime) {
          //   $dothepre = true;
          // }

          $dothepre = true;

          dproc:
          if ($dothepre) {
            echo "Processing \"{$rfile}\"...";
            ob_start();
            isolate_require($rfile);
            $out = ob_get_clean();
            if (file_put_contents($targetFile, $out)) {
              echo "OK!\n";
            } else {
              echo "Failed!\n";
            }
          }

        } else
        if (preg_match($ins->copyPattern, $v, $m)) {

          $doCopy      = false;
          $targetFile  = "{$buildDir}/{$v}";

          LightTeaPHP::addFile(ltrim("{$curBase}/{$v}", "/"));

          if (!file_exists($targetFile)) {
            $doCopy = true;
            goto dproc2;
          }

          $doCopy = (filemtime($rfile) > filemtime($targetFile));

          dproc2:
          if ($doCopy) {
            echo "Copying {$rfile}...";
            if (copy($rfile, $targetFile)) {
              echo "OK!\n";
            } else {
              echo "Failed!\n";
            }
          }
        }
      }

    }

    return true;
  }
}
