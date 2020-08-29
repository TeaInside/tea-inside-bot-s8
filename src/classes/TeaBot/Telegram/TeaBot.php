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
  private bool $skipResponse;

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
    if (!$this->skipResponse) {
      /* Run response here. */
      $res = new Response($this->data);
      $res->execRoutes();
    }
  }
}
