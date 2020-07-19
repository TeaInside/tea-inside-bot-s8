<?php

require __DIR__."/../bootstrap/telegram/autoload.php";

loadConfig("telegram/api");
loadConfig("telegram/quran");
loadConfig("telegram/calculus");
loadConfig("telegram/telegram_bot");

Swoole\Runtime::enableCoroutine();

Co\run(function() {
go(function () {

  $tcpAddr = "tcp://127.0.0.1:7777";
  $ctx = stream_context_create(
    [
      "socket" => [
        "so_reuseaddr" => true,
        "backlog" => 128
      ]
    ]
  );

  $socket = stream_socket_server(
    $tcpAddr, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $ctx);

  if (!$socket) {
    echo "$errstr ($errno)\n";
  } else {

    $i = 0;
    echo "Listening on ".$tcpAddr."...\n";

    while ($conn = stream_socket_accept($socket, -1)) {
      go(function () use ($r, $conn) {
        stream_set_timeout($conn, 5);

        $body = fread($conn, 4096);
        $bodyLen = unpack("S", substr($body, 0, 2))[1];
        $readLen = strlen($body) - 2;

        $r = substr($r, 2);
        while ($readLen < $bodyLen) {
          $r .= fread($conn, 4096);
          $readLen = strlen($r);
        }

        $r = json_decode($r, true);
        if (is_array($r)) {
          fwrite($conn, "ok");
        }

        fclose($conn);


        try {
          $bot = new \TeaBot\Telegram\TeaBot($r);
          $bot->run();
        } catch (\Error $e) {
          $bot->errorReport($e);
          throw $e;
        }
        echo "OK!\n";
        unset($bot);
      });
      echo "Done!\n";
    }
  }

});
});

function tcp_pack(string $data): string
{
  return pack('n', strlen($data)).$data;
}

function tcp_length(string $head): int
{
  return unpack('n', $head)[1];
}
