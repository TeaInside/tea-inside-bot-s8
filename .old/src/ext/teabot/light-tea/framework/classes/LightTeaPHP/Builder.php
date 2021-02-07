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
final class Builder
{
  /**
   * @var \LightTeaPHP\LightTeaPHP
   */
  private $ins;


  /**
   * Constructor.
   *
   * @param \LightTeaPHP\LightTeaPHP $ins
   */
  public function __construct(LightTeaPHP $ins)
  {
    $this->ins = $ins;
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
   * @return bool
   */
  public function init(): bool
  {
    $ins = $this->ins;

    if (!$this->createTrackDir()) {
      goto rf;
    }

    if (!$this->buildEntryPoint()) {
      goto rf;
    }

    return true;

    rf:
    return false;
  }


  /**
   * @return bool
   */
  public function buildFiles(): bool
  {
    if (!$this->buildConfigM4()) {
      goto rf;
    }

    if (!$this->buildHelpers()) {
      goto rf;
    }

    return true;

    rf:
    return false;
  }


  /**
   * @return bool
   */
  private function buildEntryPoint(): bool
  {
    $ins = $this->ins;

    try {
      ob_start();
      isolate_require(TEMPLATE_DIR."/entry_point.php.cpp");
      $out = ob_get_clean();

      file_put_contents("{$ins->buildDir}/entry_point.cpp", $out);

      ob_start();
      isolate_require(TEMPLATE_DIR."/entry_point.php.h");
      $out = ob_get_clean();

      file_put_contents("{$ins->buildDir}/entry_point.h", $out);

    } catch (Error $e) {
      $this->error = $e->__toString();
      return false;
    }
    return true;
  }


  /**
   * @return bool
   */
  private function buildHelpers(): bool
  {
    $ins = $this->ins;

    try {
      ob_start();
      isolate_require(TEMPLATE_DIR."/helpers.php.cpp");
      $out = ob_get_clean();

      file_put_contents("{$ins->buildDir}/helpers.cpp", $out);

      ob_start();
      isolate_require(TEMPLATE_DIR."/helpers.php.hpp");
      $out = ob_get_clean();

      file_put_contents("{$ins->buildDir}/helpers.hpp", $out);
    } catch (Error $e) {
      $this->error = $e->__toString();
      return false;
    }
    return true;
  }


  /**
   * @return bool
   */
  private function createTrackDir(): bool
  {
    $ins = $this->ins;

    $trackFileDir = "{$ins->buildDir}/.track";
    is_dir($trackFileDir) or mkdir($trackFileDir);

    if (!is_dir($trackFileDir)) {
      $this->error = "Cannot create track directory";
      return false;
    }

    $ins->setTrackFileDir($trackFileDir);
    return true;
  }


  /**
   * @return bool
   */
  private function buildConfigM4(): bool
  {
    $ins = $this->ins;
    try {

      ob_start();
      isolate_require(TEMPLATE_DIR."/config.php.m4");
      $out = ob_get_clean();

      file_put_contents("{$ins->buildDir}/config.m4", $out);

    } catch (Error $e) {
      $this->error = $e->__toString();
      return false;
    }

    return true;
  }
}
