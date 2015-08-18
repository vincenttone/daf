<?php
require_once(dirname(dirname(__FILE__)).'/app/init.php');

function get_args($short, $long = [], $require = [], $usage = '')
{
    $opt = getopt($short, $long);
    $keys = array_keys($opt);
    if (isset($require[0])) {
        foreach ($require as $_r) {
            $same = array_intersect($keys, $_r);
            if (count($same) != count($_r)) {
                echo $usage.PHP_EOL;
                exit;
            }
        }
    }
    return $opt;
}

function print_out($data, $type = 'default')
{
    switch ($type) {
    case 'echo':
        echo $data;
        echo PHP_EOL;
        break;
    case 'default':
        p($data);
        break;
    case 'json':
        echo json_encode($data);
        echo PHP_EOL;
        break;
    case 'tsv':
        $line = key($data);
        $line .= "\t";
        if (is_array($data)) {
            $_d = reset($data);
            $line .= implode(',', $_d);
        } else {
            $line .= $data;
        }
        $line .= PHP_EOL;
        echo $line;
        break;
    }
}

function print_out_all($data, $type = 'default')
{
    if ($type === 'tsv') {
        foreach ($data as $key => $_d) {
            print_out([$key => $_d], $type);
        }
    } else {
        print_out($data, $type);
    }
}
