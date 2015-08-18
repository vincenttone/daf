#!/usr/bin/env php
<?php
require_once(dirname(dirname(dirname(__FILE__))).'/app/init.php');
$o = getopt('r::s::l::', ['show', 'register', 'list']);
if (isset($o['register']) || isset($o['r'])) {
    $register = Module_ModuleManager_Register::get_instance()->register_modules();
    p($register);
} else {
    $modules = Module_ModuleManager_Register::get_instance()->get_registered_modules();
    if (empty($modules)) {
        p('No modules...');
        exit;
    }
    if (isset($o['list']) || isset($o['l'])) {
        foreach ($modules as $_k => $_v) {
            echo $_k ."\t"
                .$_v[Const_Interface::FIELD_ATTR_NAME] ."\t"
                .$_v[Const_Interface::FIELD_ATTR_TYPE] ."\t"
                .$_v['indentify'] . "\t".PHP_EOL;
                //.$_v['author'] . "\t"
         }
        exit;
    } else {
        p($modules);
        exit;
    }
}