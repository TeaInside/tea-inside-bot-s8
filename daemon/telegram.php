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

  $socket = stream_socket_server($tcpAddr, $errno, $errstr,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $ctx);

  if (!$socket) {
    echo "$errstr ($errno)\n";
  } else {

    $i = 0;
    echo "Listening on ".$tcpAddr."...\n";

    while ($conn = stream_socket_accept($socket, -1)) {
      go(function () use ($r, $conn) {
        stream_set_timeout($conn, 5);

        $data = fread($conn, 4096);
        $dataLen = unpack("S", substr($data, 0, 2))[1];
        $receivedLen = strlen($data) - 2;
        $data = substr($data, 2);

        while ($dataLen > $receivedLen) {
          $data .= fread($conn, 4096);
          $receivedLen = strlen($data);
        }

        $data = json_decode($data, true);
        if (is_array($data)) {
          fwrite($conn, "ok");
          fclose($conn);

          try {
            $bot = new \TeaBot\Telegram\TeaBot($data);
            $bot->run();
          } catch (\Error $e) {
            $bot->errorReport($e);
          }

          DB::dumpConnections();

        } else {
          fwrite($conn, "fail");
          fclose($conn);
        }
      });
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
