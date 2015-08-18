<?php
if (!defined('DA_INIT')) {
    // 防止重复初始化    
    define("DA_INIT", 1);
    // 版本检查
    if (PHP_VERSION_ID < 50400) {
        exit("Need PHP-5.4.0 or upper.".PHP_EOL);
    }
    ini_set('memory_limit','30G');
    define('DA_PATH_INIT_FILE', dirname(__FILE__));
    define('DA_PATH_HOME', dirname(DA_PATH_INIT_FILE));
    define('DA_PATH_SYS', DA_PATH_INIT_FILE.'/sys');
    // functions
    require_once(DA_PATH_SYS.'/function.php');
    // start app
    require_once(DA_PATH_SYS.'/app.php');
    Da\Sys_App::app(DA_PATH_HOME.'/conf/app.ini')->bootstrap();
    file_exists(DA_PATH_INIT_FILE.'/extra.php')
        && require_once(DA_PATH_INIT_FILE.'/extra.php');
    Lib_Log::DEBUG('Framework(v'.DA_VERSION.') init ok');
} // end of if DA_LIB_INIT
