<?php
require_once(
    dirname(dirname(__FILE__))
    .'/init_env.php'
);
$opt = get_args(
    'c:m:a:',
    ['static'], [['c', 'm']],
    'USAGE: CMD -c class -m method [--static]'
);
$args = isset($opt['a'])
    ? explode(',', $opt['a'])
    : [];
$r = isset($opt['static'])
    ? call_user_func_array([$opt['c'], $opt['m']], $args)
    : call_user_func_array([(new $opt['c']), $opt['m']], $args);
p($r);