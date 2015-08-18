<?php
class Lib_Mongo_Factory
{
    protected static $_mongos = [];

    /**
     * @param array $config
     * @param bool $hold
     * @return Lib_Mongo_Db
     */
    public static function getMongo($config, $hold = true)
    {
        if (!$hold) {
            return (new Lib_Mongo_Db($config));
        }
        $host = $config[Lib_Mongo_Db::CONFIG_FIELD_HOST];
        $port = $config[Lib_Mongo_Db::CONFIG_FIELD_PORT];
        $key = $host.':'.$port;
        if (empty(self::$_mongos) || !isset(self::$_mongos[$key])) {
            self::$_mongos[$key] = new Lib_Mongo_Db($config);
        }
        return self::$_mongos[$key];
    }

    private function __construct()
    {
    }
    /**
     * 禁用对象克隆
     */
    private function __clone()
    {
        throw new Exception("Could not clone the object from class: ".__CLASS__);
    }
}