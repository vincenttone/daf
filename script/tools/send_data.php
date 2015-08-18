#!/usr/bin/env php
<?php
require_once(dirname(dirname(dirname(__FILE__))).'/app/init.php');
$opt = getopt('H:P:', ['json', 'php', 'gbk', 'utf8', 'debug']);
if (!isset($opt['H']) || !isset($opt['P'])) {
    echo 'CMD -H host -P port [--json | --php] [--debug]'.PHP_EOL;
    exit;
}
$f = fopen("php://stdin", 'r');
$a = '';
while (!feof($f)) {
    $a = fgets($f);
    if (feof($f)) {
        break;
    }
    isset($opt['debug']) && p('data is:', $a);
    $x = (isset($opt['p']) || isset($opt['php']))
        ? unserialize($a)
        : json_decode($a, true);
    isset($opt['debug']) && p('decode data is:', $x);
    if ($x) {
        isset($opt['gbk'])
            || Module_DataEntry_Main::convert_encoding($x, Module_DataEntry_Main::$chinese_fields);
        $conf = [
            'host' => $opt['H'],
            'port' => $opt['P'],
        ];
        $result = Module_DataEntry_Main::send_data($x, $conf);
        p($result);
    } else {
        Lib_Log::notice('decode data failed!!! data: %s', $a);
    }
}
