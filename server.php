<?php

use React\Socket\SocketServer;
use React\EventLoop\Loop;
use React\Socket\ConnectionInterface;
include_once('ConnectionsPool.php');
require __DIR__ . '/vendor/autoload.php';

$loop = Loop::get();
$socket = new SocketServer('127.0.0.1:8080',[], $loop);

$pool = new ConnectionsPool();

$socket->on('connection', function(ConnectionInterface $connection) use ($pool){
    $pool->add($connection);
});

echo "Listening on {$socket->getAddress()}\n";

$loop->run();
// require 'vendor/autoload.php';

// use React\Http\HttpServer;
// use React\Socket\SocketServer;
// use React\EventLoop\Loop;
// use React\EventLoop\Factory;
// use React\Socket\Server;
// use React\Socket\ConnectionInterface;

// require __DIR__ . '/vendor/autoload.php';

// $loop = Loop::get();
// $clients = new \SplObjectStorage();

// $socket = new React\Socket\SocketServer('127.0.0.1:8080');
// $clients = new \SplObjectStorage();

// $server = new SocketServer('127.0.0.1:8000',[], $loop);
// $server->on('connection', function (ConnectionInterface $conn) use ($clients) {
//     $clients->attach($conn);

//     $conn->on('data', function ($data) use ($clients, $conn) {
//         foreach ($clients as $client) {
//             if ($client !== $conn) {
//                 $client->write($data);
//             }
//         }
//     });

//     $conn->on('close', function () use ($clients, $conn) {
//         $clients->detach($conn);
//     });
// });

// $loop->run();



