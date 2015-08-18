#!/usr/bin/env php
<?php
require_once(
    dirname(dirname(__FILE__))
    .'/init_env.php'
);
require_once(
    Da\Sys_App::lib_path("CNsHead.class.php")
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
    $ns = new NsHead();
    $data = $ns->nshead_read($conn);
    $result = mc_pack_pack2array($data['buf']);
    isset($opt['2json'])
    ? print(json_encode($result))
    : (
        isset($opt['2sp'])
        ? print(serialize($result))
        : print_r($result)
    );
    echo PHP_EOL;
    $data = ['errno'=>0, 'data'=> 'ok'];
    $body = mc_pack_array2pack($data);
    $hdr  = array('body_len' => strlen($body));
    $ns->nshead_write($conn, $hdr, $body);
};

echo 'Listening at port: '.$port.PHP_EOL;
if (!$socket) {
    echo "$errstr ($errno)\n";
} else {
    $watcher = new EvIo($socket, EV::READ, $process);
    Ev::run();
}