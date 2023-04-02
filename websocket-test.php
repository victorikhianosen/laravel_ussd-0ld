<!-- 
<?php
use React\Socket\ConnectionInterface;
use React\Http\HttpServer;
use React\Socket\SocketServer;
use React\EventLoop\Loop;
use React\EventLoop\Factory;
use React\Socket\Server;

require __DIR__ . '/vendor/autoload.php';

$loop = Loop::get();
$connector = new React\Socket\Connector($loop);


$stdin = new \React\Stream\ReadableResourceStream(STDIN, $loop);
$stdout = new \React\Stream\WritableResourceStream(STDOUT, $loop);

$connector
    ->connect('127.0.0.1:8888')
    ->then(
        function (ConnectionInterface $conn) use ($stdout, $stdin) {
            $stdin->pipe($conn)->pipe($stdout);
        },
        function (Exception $exception) use ($loop){
            // reject
        });

$loop->run();

