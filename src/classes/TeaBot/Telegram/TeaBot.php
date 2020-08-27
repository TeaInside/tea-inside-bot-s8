<?php

namespace TeaBot\Telegram;

use DB;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Telegram
 * @version 8.0.0
 */
final class TeaBot
{
  /**
   * @var \TeaBot\Telegram\Data
   */
  private Data $data;

  /**
   * @var bool
   */
  private $skipResponse;

  /**
   * @param array $data
   * @param bool  $skipResponse
   *
   * Constructor.
   */
  public function __construct(array $data, bool $skipResponse = false)
  {
    $this->data         = new Data($data);
    $this->skipResponse = $skipResponse;
  }

  /**
   * @return void
   */
  public function run(): void
  {
    /* Run logger here. */
    // go([$this, "runLogger"]);


    if (!$this->skipResponse) {
      /* Run response here. */
      $res = new Response($this->data);
      $res->execRoutes();
    }
  }

  /**
   * @return void
   */
  private function runLogger(): void
  {
    // $data    = $this->data;
    // $handler = new RunHandler($data);
    // $handler->setCallback([new Logger($data), "run"]);
    // $handler->run();
  }
}
