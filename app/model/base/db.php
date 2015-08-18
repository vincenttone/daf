<?php
abstract class Model_Base_Db
{
    protected $_config = null;
    protected $_db = null;
    protected $_db_name = null;
    protected $_table_name = null;

    /**
     * @param array
     * @return null
     */
    abstract function init_db_with_config($config);

    /**
     * @param string
     */
    function init_with_conf_path($conf_path)
    {
        Lib_Log::debug("Init config with path: %s", json_encode($conf_path));
        $this->config($conf_path);
        $this->init_db_with_config($this->get_config());
    }

    /**
     * @return null|array
     */
    function get_db()
    {
        $this->_db->set_db_name($this->_db_name);
        return $this->_db;
    }

    /**
     * @param string
     * @return null|array
     */
    function set_table_name($table_name)
    {
        $this->_table_name = $table_name;
        return $this;
    }

    /**
     * @return null|array
     */
    function get_table()
    {
        $table = $this->get_db()->set_table_name($this->_table_name);
        return $table;
    }

    /**
     * @return null|array
     */
    function get_tables()
    {
        $tables = $this->get_db()->get_tables();
        return $tables;
    }

    /**
     * @param string
     * @return array|bool|null
     */
    function config($conf_path)
    {
        $this->_config = Da\Sys_Config::two_step_conf($conf_path, ['driver'=>'driver', 'db' => 'db']);
        return $this->_config;
    }

    /**
     * @return null|array
     */
    function get_config()
    {
        return $this->_config;
    }

    /**
     * @param array
     * @param bool
     * @return array
     */
    static function add_time($data, $with_created = true)
    {
        $time_now = time();
        if ($with_created) {
            if (isset($data['create_time'])) {
                $data['create_time'] = strtotime($data['create_time']);
            } else {
                $data['create_time'] = $time_now;
            }
        }
        $data['update_time'] = $time_now;
        return $data;
    }

    /**
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @param int
     * @param string
     * @return array
     */
    function save_fail_data($db, $task_id, $ap_id, $ts, $uid, $content, $module, $errno, $errmsg)
    {
        $data = [];
        $data['_id']     = $task_id . '_' . $uid;
        $data['task_id'] = $task_id;
        $data['uid']     = $uid;
        $data['ap_id']   = $ap_id;
        $data['ts']      = $ts;
        $data['content'] = $content;
        $data['module']  = $module;
        $data['errno']   = $errno;
        $data['errmsg']  = $errmsg;

        $this->get_db()->set_db_name('fail_data');
        $this->get_db()->set_table_name('t_fail_data');
        $ret = $this->get_db()->save($data);
        if (0 != $ret['errno']) {
            Lib_Log::error(__METHOD__ . ' Fail to save fail data.');
        }
        return $ret;
    }
}