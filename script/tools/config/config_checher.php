#!/usr/bin/env php
<?php
require_once(dirname(dirname(dirname(__FILE__))).'/init_env.php');

$opt = get_args('p:', ['ts'], [['p']], 'give me path by -p');
$config = isset($opt['ts'])
    ? Da\Sys_Config::two_step_conf($opt['p'])
    : Da\Sys_Config::config($opt['p']);
p($opt['p'], $config);