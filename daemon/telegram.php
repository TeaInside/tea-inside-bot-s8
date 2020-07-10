<?php

require __DIR__."/../config/telegram/main.php";

Swoole\Runtime::enableCoroutine();
$s = microtime(true);


Co\run(function() {
go(function () {

  $tcpAddr = "tcp://0.0.0.0:9502";
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
      stream_set_timeout($conn, 5);
      fclose($conn);
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
