<?php

namespace TeaBot\Telegram;

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
  private $data;

  /**
   * @param array &$data
   *
   * Constructor.
   */
  public function __construct(array &$data)
  {
    $this->data = new Data($data);
  }

  /**
   * @return void
   */
  public function run(): void
  {
    echo "sending...\n";
    $o = Exe::sendMessage(
      [
        "chat_id" => $this->data->in["message"]["chat"]["id"],
        "text" => "test"
      ]
    );
    $v = $o->getBody();
    var_dump($v->__toString());
    // $res = new Response($this->data);
    // $res->execute();
  }
}
