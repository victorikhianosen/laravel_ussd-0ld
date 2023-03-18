<?php

require 'vendor/autoload.php';

use React\EventLoop\Factory;

$loop = Factory::create();

$timeLeft = 60;

$timer = $loop->addPeriodicTimer(1, function () use (&$timeLeft, $loop) {
    echo "Time Left: {$timeLeft} seconds\n";
    $timeLeft--;
    if ($timeLeft === 0) {
        $loop->stop();
        echo "Time is up!\n";
    }
});

$loop->run();
