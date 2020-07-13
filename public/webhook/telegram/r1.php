<?php

require __DIR__."/../../../bootstrap/telegram/autoload.php";

loadConfig("telegram/telegram_bot");

header("Content-Type: text/plain");

if (
  isset($_GET["key"]) &&
  ($_GET["key"] === TELEGRAM_WEBHOOK_KEY)
) {

  $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

  if (!socket_connect($sock, "127.0.0.1", 7777)) {
    $msg = "Cannot connect to socket!\n";
    goto err;
  }

  $data    = file_get_contents("php://input");
  $dataLen = strlen($data);
  $data    = pack("S", $dataLen).$data;

  if (socket_send($sock, $data, $dataLen + 2, 0) === false) {
    $msg = "Cannot send to socket!\n";
    socket_close($sock);
    goto err;
  }

  if (socket_recv($sock, $buf, 100, 0) === false) {
    $msg = "Cannot retrieve response from the socket!\n";
    socket_close($sock);
    goto err;
  }

  socket_close($sock);
  echo $buf;
}


exit;

err:
http_response_code(500);
echo $msg;
