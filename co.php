<?php

$chan = new Swoole\Coroutine\Channel(5);
Co\run(function () use ($chan) {
    $cid = Swoole\Coroutine::getuid();
    echo "coro: {$cid}\n";
    for ($i = 0; $i < 4; $i++) {
        echo "pushing to chan... {$i}\n";
        co::sleep(1);
        $chan->push("test {$i}");
    }
});
Co\run(function () use ($chan) {
    $cid = Swoole\Coroutine::getuid();
    echo "coro: {$cid}\n";
    for ($i = 0; $i < 5; $i++) {
        echo "popping from chan..\n";
        $data = $chan->pop();
        var_dump($data);
    }
});
