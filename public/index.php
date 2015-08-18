<?php
session_start();
require_once(dirname(dirname(__FILE__)).'/app/init.php');
Module_HttpRequest_Router::get_instance()
    ->register_pre_router_hook(['Module_Account_Manager', 'check_perm']);
Module_View_Main::view()->set_template_dir(Da\Sys_App::template_path());
$router = [Module_HttpRequest_Router::get_instance(), 'route'];
if (Da\Sys_Router::get_instance()->register_router($router)) {
    MT::lang(MT::LANG_ZH_CN);
    $dispatch = Da\Sys_Router::get_instance()->dispatch();
    if ($dispatch['errno'] != Da\Sys_Router::ERRNO_OK) {
        echo $dispatch['data'];
        exit;
    }
} else {
    Lib_Log::error('Route rules '.json_encode($rules).' is not callable!');
}
