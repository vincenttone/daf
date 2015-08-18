#!/usr/bin/env php
<?php
$opt = getopt('j::p::f::', ['json', 'php', 'var_dump', '2json']);
$type = 1;
(isset($opt['p']) || isset($opt['php'])) && $type = 2;
$f = fopen("php://stdin", 'r');
$a = '';
while (!feof($f)) {
    $a = fgets($f);
    $a = trim($a);
    $x = $type == 1 ? json_decode($a) : unserialize($a);
    if ($x && !isset($opt['var_dump'])) {
        if (isset($opt['2json'])) {
            echo json_encode($x);
            echo PHP_EOL;
        } else {
            if (isset($opt['f'])) {
                $fields = explode(',', $opt['f']);
                $sd = [];
                foreach ($fields as $_f) {
                    $sd[] = isset($x->$_f) ? $x->$_f : 'Nil';
                }
                $sd = implode("\t", $sd);
                echo $sd.PHP_EOL;
            } else {
                print_r($x);
            }
        }
    } else {
        var_dump($x);
    }
}
