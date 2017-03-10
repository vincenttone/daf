#!/usr/bin/env php
<?php
require_once(
    dirname(dirname(__FILE__))
    .'/init_env.php'
);
$opt = getopt('h::p::', ['2json', '2sp']);
$host = isset($opt['h']) ? $opt['h'] : '0.0.0.0';
$port = isset($opt['p']) ? $opt['p'] : '8765';

$socket = stream_socket_server(
    "tcp://$host:$port",
    $errno,
    $errstr
);
$process = function () use ($socket, $opt)
{
    $conn = stream_socket_accept($socket, 30);
    $result = fgets($conn);
    isset($opt['2json'])
    ? print(json_encode($result))
    : (
        isset($opt['2sp'])
        ? print(serialize($result))
        : print_r($result)
    );
    echo PHP_EOL;
    $data = ['errno'=>0, 'data'=> 'ok'];
    fwrite($conn, json_encode($data));
};

echo 'Listening at port: '.$port.PHP_EOL;
if (!$socket) {
    echo "$errstr ($errno)\n";
} else {
    $watcher = new EvIo($socket, EV::READ, $process);
    Ev::run();
}
