<?php
class Model_Base_Getconfig
{
    static $_config = null;

    /**
     * @param string
     * @return array|null
     * @throws Exception
     */
    public static function config($conf_type)
    {
        $db_conf = Da\Sys_Config::config($conf_type);
        if (!is_array($db_conf) || !isset($db_conf['path'])) {
            throw new Exception(
                'configure not exists in path: '.$conf_type,
                Const_Err_Base::ERR_CONFIG_MISSING);
        }
        $conf_path = $db_conf['path'];
        self::$_config = Da\Sys_Config::config($conf_path);
        if (self::$_config === false) {
            throw new Exception(
                'configure not exists in path: '.$conf_path,
                Const_Err_Base::ERR_CONFIG_MISSING);
        }
        isset($db_conf['driver']) && self::$_config['driver'] = $db_conf['driver'];
        return self::$_config;
    }
}
