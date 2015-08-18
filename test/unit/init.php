<?php
if (!defined('DA_UT_PATH')) {
    define('DA_UT_PATH', dirname(__FILE__));
    define('DA_UT_DATA_PATH', DA_UT_PATH.'/data');
    // 版本检查
    if (PHP_VERSION_ID < 50400) {
        exit("Need PHP-5.4.0 or upper.".PHP_EOL);
    }
    ini_set('memory_limit','10240M');
    //require_once 'PHPUnit/Autoload.php';

    // start app
    defined('DA_PATH_HOME') || define('DA_PATH_HOME', dirname(dirname(DA_UT_PATH)));
    // functions
    if (DA_PATH_HOME) {
        $app = DA_PATH_HOME . '/app/sys/app.php';
        $conf = DA_PATH_HOME . '/conf/unit.ini';
        require_once($app);
        require_once(DA_PATH_HOME.'/app/sys/function.php');
        Da\Sys_App::app($conf)->bootstrap();
        Lib_Log::DEBUG('Unit test init ok');
    } else {
        throw new Exception('UT: wrong path.');
    }
    require_once(DA_UT_PATH.'/helper.php');
} // end of if DA_UT_INIT