<?php
class Model_Base_Redis extends Model_Base_Db
{
    /**
     * @param array
     */
    function init_db_with_config($config)
    {
        $host = $config['host'];
        $port = $config['port'];
        Lib_Log::debug("Try to connect redis: %s", json_encode($config));
        try {
            $this->_db = new Lib_Redis($host, $port);
        } catch (RedisException $ex) {
            $this->_db = null;
            Lib_Log::error("REDIS-ERROR: %s", json_encode($ex));
        }
    }

    /**
     * @return array
     */
    function ping()
    {
        $ping = $this->_db->ping();
        if ($ping['errno'] != 0 || !isset($ping['data']) || $ping['data'] == false) {
            return Lib_Helper::get_err_struct(Const_Err_Db::ERR_CONNECT_FAIL, false);
        }
        return Lib_Helper::get_return_struct($ping['data']);
    }

    /**
     *
     */
    function __destruct()
    {
        if ($this->_db) {
            try {
                $this->_db->close_connection();
            } catch (RedisException $ex) {
                $this->_db = null;
                Lib_Log::error("REDIS-ERROR: %s", json_encode($ex));
            }
        }
        $this->_db = null;
    }

    /**
     * @param array
     * @return array
     */
    static function process_result($result)
    {
        if ($result['errno'] !== 0) {
            Lib_Log::error("Redis: get result failed! result: %s", json_encode($result));
            return  Lib_Helper::get_err_struct(Const_Err_DataAccess::ERR_GET_CARD, 'get card failed! result:'.json_encode($result));
        }
        return Lib_Helper::get_return_struct($result['data']);
    }
}