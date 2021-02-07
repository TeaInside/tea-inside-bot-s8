<?php

namespace LightTeaPHP;

use LightTeaPHP\Components\PHPClass;
use LightTeaPHP\Exceptions\LightTeaPHPException;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \LightTeaPHP
 * @version 0.0.1
 */
final class LightTeaPHP
{
  /**
   * @var ?string
   */
  private $currentFile;

  /**
   * @var string
   */
  private $buildDir;

  /**
   * @var string
   */
  private $sourceDir;

  /**
   * @var string
   */
  private $trackFileDir;

  /**
   * @var string
   */
  private $extName;

  /**
   * @var string
   */
  private $upExtName;

  /**
   * @var string
   */
  private $extDescription;

  /**
   * @var array
   */
  private $files = [];

  /**
   * @var string
   */
  private $version = "0.0";

  /**
   * @var string
   */
  private $copyPattern;

  /**
   * @var string
   */
  private $prePattern;

  /**
   * @var \LightTeaPHP\Builder
   */
  private $builder;

  /**
   * @var array
   */
  private $classes = [];

  /**
   * @var string
   */
  private $classFragFile;

  /**
   * @var string
   */
  private $classCeExternFile;

  /**
   * @var array
   */
  private $includePath = [];

  /**
   * @var \LightTeaPHP\LightTeaPHP
   */
  private static $ins;


  /**
   * Constructor.
   */
  private function __construct()
  {
  }


  /**
   * @return void
   */
  public function setTrackFileDir(string $trackFileDir): void
  {
    $this->trackFileDir = $trackFileDir;
  }


  /**
   * @param mixed $key
   * @return mixed
   */
  public function __get($key)
  {
    return $this->{$key} ?? null;
  }


  /**
   * @return void
   */
  public function printFiles(): void
  {
    $i = 0;
    foreach ($this->files as $k => $v) {
      echo ($i++ ? " " : "").$k;
    }
  }


  /**
   * @return void
   */
  public function printIncludePath(): void
  {
    foreach ($this->includePath as $k => $v) {
      echo "PHP_ADD_INCLUDE({$k})\n";
    }
  }


  /**
   * @return bool
   * @throws \LightTeaPHP\Exceptions\LightTeaPHPException
   */
  public static function init(): bool
  {
    $ins = self::getIns();

    if (!is_string($ins->buildDir)) {
      throw new LightTeaPHPException("buildDir has not been set");
    }

    if (!is_string($ins->sourceDir)) {
      throw new LightTeaPHPException("sourceDir has not been set");
    }

    $builder = new Builder($ins);

    if (!$builder->init()) {
      throw new LightTeaPHPException($builder->error);
    }

    $ins->builder = $builder;
    $ins->files = [
      "entry_point.cpp" => true,
      "helpers.cpp" => true,
    ];
    $ins->classFragFile = "{$ins->buildDir}/class.frag.cpp";
    $ins->classCeExternFile = "{$ins->buildDir}/class_ce_extern.frag.h";

    @unlink($ins->classFragFile);

    $const = "__LTP_CE_EXTERN_FILE_{$ins->upExtName}";
    file_put_contents($ins->classCeExternFile,
      "\n#ifndef {$const}\n#define {$const}\n\n");

    return true;
  }


  /**
   * @return bool
   * @throws \LightTeaPHP\Exceptions\LightTeaPHPException
   */
  public static function build(): bool
  {
    $ins = self::getIns();

    $fe = new FileEnumerator($ins->builder);
    if (!$fe->run()) {
      throw new LightTeaPHPException($fe->error);
    }


    $const = "__LTP_CE_EXTERN_FILE_{$ins->upExtName}";
    file_put_contents(
      $ins->classCeExternFile,
      "\n#endif\n/* End of file. */\n",
      FILE_APPEND | LOCK_EX
    );

    if (!$ins->builder->buildFiles()) {
      throw new LightTeaPHPException($builder->error);
    }

    return true;
  }


  /**
   * @return \LightTeaPHP\LightTeaPHP
   */
  public static function getIns(): LightTeaPHP
  {
    if (!self::$ins) {
      self::$ins = new self;
    }

    return self::$ins;
  }


  /**
   * @param string $dir
   * @return bool
   * @throws \LightTeaPHP\Exceptions\LightTeaPHPException
   */
  public static function setBuildDir(string $dir): bool
  {
    $ins = self::getIns();

    is_dir($dir) or mkdir($dir);

    if (!is_dir($dir)) {
      throw new LightTeaPHPException("Cannot create build directory: {$dir}");
    }

    $ins->buildDir = $dir;
    return true;
  }


  /**
   * @param string $dir
   * @return bool
   */
  public static function setSourceDir(string $dir): bool
  {
    $ins = self::getIns();
    $ins->sourceDir = $dir;
    return true;
  }


  /**
   * @param string $extName
   * @return bool
   */
  public static function setExtName(string $extName): bool
  {
    $ins = self::getIns();
    $ins->extName   = $extName;
    $ins->upExtName = strtoupper($extName);
    return true;
  }


  /**
   * @param string $extDescription
   * @return bool
   */
  public static function setExtDescription(string $extDescription): bool
  {
    $ins = self::getIns();
    $ins->extDescription = $extDescription;
    return true;
  }


  /**
   * @param string $copyPattern
   */
  public static function setCopyPattern(string $copyPattern)
  {
    $ins = self::getIns();
    $ins->copyPattern = $copyPattern;
    return true;
  }


  /**
   * @param string $prePattern
   */
  public static function setPrePattern(string $prePattern)
  {
    $ins = self::getIns();
    $ins->prePattern = $prePattern;
    return true;
  }


  /**
   * @param \LightTeaPHP\Components\PHPClass $class
   * @return void
   */
  public static function addClass(PHPClass $class)
  {
    $ins = self::getIns();
    $ins->classes[$class->className] = $class;
  }


  /**
   * @param string $file
   * @return void
   */
  public static function addFile(string $file): void
  {
    $ins = self::getIns();
    $ins->files[$file] = true;
  }


  /**
   * @param string $path
   * @return void
   */
  public static function addIncludePath(string $path): void
  {
    $ins = self::getIns();
    $ins->includePath[$path] = true;
  }


  /**
   * @param string $filename
   * @return void
   */
  public static function beginFile(string $filename): void
  {
    $ins = self::getIns();
    $ins->currentFile = $filename;

    echo "\n";
    echo "/* {$filename} */";
    echo "\n\n";
    echo "#include <php.h>\n\n";
  }


  /**
   * @param string $filename
   * @return void
   * @throws \LightTeaPHP\Exceptions\LightTeaPHPException
   */
  public static function endFile(string $filename): void
  {
    $ins = self::getIns();

    if ($filename !== $ins->currentFile) {
      throw new LightTeaPHPException(
        "Invalid endFile call, current track file is: {$ins->currentFile},".
        " but called with: {$filename}"
      );
    }

    echo "/* End of file */";
    echo "\n";
  }
}
