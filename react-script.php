<?php

require __DIR__.'/vendor/autoload.php';

use React\EventLoop\Factory;
use React\EventLoop\TimerInterface;

function printLovelyMessage(TimerInterface $timer) {
    echo "Hello, Laravel! This is a lovely message.\n";
}
