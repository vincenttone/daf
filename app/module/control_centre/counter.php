<?php
class Module_ControlCentre_Counter
{
    use singleton_with_get_instance;

    const KEY_PREFIX = 'task-counter:';
    const MAX_POOL_ADD_COUNT = 200;

    private $_enable_counter = true;

    private $_current_task_id = null;
    private $_current_hash_key = null;

    private $_current_keys = [];
    private $_current_keys_map = [];
    private $_pool = [];
    private $_counter_pid = null;

    private $_ratio_key_map = [];

    private function __construct()
    {
        $task = Module_ControlCentre_Main::current_task();
        if ($task) {
            $task_id = $task->id;
            $this->_set_task_id($task_id);
        } else {
            $this->_set_counter_status(false);
        }
    }

    function __destruct()
    {
        $this->_flush_pool();
    }

    /**
     * @param $mid
     * @param array $map
     * @return mixed
     */
    static function register_keys_map($mid, $map)
    {
        return self::get_instance()->_register_keys_map($mid, $map);
    }

    /**
     * @return mixed
     */
    static function enable()
    {
        return self::get_instance()->_set_counter_status(true);
    }

    /**
     * @return mixed
     */
    static function disable()
    {
        return self::get_instance()->_set_counter_status(false);
    }

    /**
     * @param string $key
     * @param int $count
     * @return mixed
     */
    static function incr($key, $count = 1)
    {
        return self::get_instance()->_incr($key, $count);
    }

    /**
     * @param int $counts
     * @return mixed
     */
    static function incr_counts($counts)
    {
        return self::get_instance()->_incr_counts($counts);
    }

    /**
     * @param string $key
     * @return int
     */
    static function get_count($key)
    {
        return self::get_instance()->_get_count($key);
    }

    /**
     * @param array $keys
     * @return mixed
     */
    static function del_counts($keys)
    {
        return self::get_instance()->_del_counts($keys);
    }

    /**
     * @return array
     */
    static function get_all_counts()
    {
        return self::get_instance()->_get_all_counts();
    }

    /**
     * @return mixed
     */
    static function del_all_counts()
    {
        return self::get_instance()->_del_all_counts();
    }

    /**
     * @param $mid
     * @param array $counts
     * @return array
     */
    static function formated_counts($mid, $counts)
    {
        $map = [];
        isset(self::get_instance()->_current_keys_map[$mid])
            && $map = self::get_instance()->_current_keys_map[$mid];
        $result = [];
        if (!is_array($counts)) {
            return $result;
        }
        foreach ($counts as $_k => $_v) {
            if ($_v == 0) {
                continue;
            }
            if (isset($map[$_k])) {
                $result[$_k] = [
                    'name' => $map[$_k],
                    'value' => $_v,
                ];
            }
        }
        return $result;
    }

    /**
     * @param $mid
     * @param array $map
     */
    private function _register_keys_map($mid, $map)
    {
        foreach ($map as $_k => $_v) {
            $_hash_key = $this->_hash_key($_k);
            $this->_current_keys[$_k] = $_hash_key;
            isset($this->_current_keys_map[$mid])
                ? $this->_current_keys_map[$mid][$_k] = $_v
                : $this->_current_keys_map[$mid] = [$_k => $_v];
        }
    }

    /**
     * @param string $key
     * @return string
     */
    private function _hash_key($key)
    {
        return $this->_current_hash_key.':'.$key;
    }

    /**
     * @param int $task_id
     * @return $this
     */
    private function _set_task_id($task_id)
    {
        $this->_current_task_id = $task_id;
        $this->_current_hash_key = self::KEY_PREFIX.strval($task_id);
        return $this;
    }

    /**
     * @param bool $status
     * @return $this
     */
    private function _set_counter_status($status = true)
    {
        $this->_enable_counter = $status;
        return $this;
    }

    /**
     * @return bool
     */
    private function _get_counter_status()
    {
        return $this->_enable_counter;
    }

    /**
     * @param string $key
     * @param int $count
     * @return array
     */
    private function _incr($key, $count = 1)
    {
        if (!$this->_get_counter_status()) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_COUNTER_DISABLED,
                'counter disabled'
            );
        }
        if (isset($this->_current_keys[$key])) {
            $incr = 0;
            if ($count != 0) {
                $save_key = $this->_hash_key($key);
                isset($this->_pool[$save_key])
                    ? $this->_pool[$save_key] += $count
                    : $this->_pool[$save_key] = $count;
                if ($this->_pool[$save_key] >= self::MAX_POOL_ADD_COUNT) {
                    $incr = xcache_inc($save_key, $this->_pool[$save_key]);
                    unset($this->_pool[$save_key]);
                }
                $incr = $count;
            }
            return Lib_Helper::get_return_struct($incr);
        } else {
            return Lib_Helper::get_err_struct(Const_Err_Base::ERR_INVALID_PARAM, 'no such count');
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    static function fflush($key = null)
    {
        return self::get_instance()->_flush_pool($key);
    }

    /**
     * @param string $key
     * @return array|int
     */
    private function _flush_pool($key = null)
    {
        if (!$this->_get_counter_status()) {
            return [];
        }
        if (empty($this->_pool)) {
            return [];
        }
        if ($key === null) {
            $result = [];
            foreach ($this->_pool as $_key => $_val) {
                $result[$_key] = xcache_inc($_key, $_val);
            }
            $this->_pool = [];
            return $result;
        }
        $_count = 0;
        $key = $this->_hash_key($key);
        if (isset($this->_pool[$key])) {
            $_count = $this->_pool[$key];
            xcache_inc($key, $_count);
            unset($this->_pool[$key]);
        }
        return $_count;
    }

    /**
     * @param array $counts
     * @return array
     */
    private function _incr_counts($counts)
    {
        if (!$this->_get_counter_status()) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_COUNTER_DISABLED,
                'counter disabled'
            );
        }
        $result = [];
        foreach ($counts as $_k => $_c) {
            $incr = $this->_incr($_k, $_c);
            if ($incr['errno'] != Const_Err_Base::ERR_OK) {
                continue;
            }
            $result[$_k] = $incr['data'];
        }
        return Lib_Helper::get_return_struct($result);
    }

    /**
     * @param string $key
     * @return array
     */
    private function _get_count($key)
    {
        $this->_flush_pool($key);
        $key = $this->_hash_key($key);
        $get = xcache_get($key);
        empty($get) && $get = 0;
        return Lib_Helper::get_return_struct($get);
    }

    /**
     * @param array $keys
     * @return array
     */
    private function _del_counts($keys)
    {
        $this->_flush_pool();
        $result = [];
        foreach ($keys as $key) {
            $hash_key = $this->_hash_key($key);
            $get = xcache_unset($hash_key);
            $result[$key] = $get;
        }
        return Lib_Helper::get_return_struct($result);
    }

    /**
     * @return array
     */
    private function _get_all_counts()
    {
        $this->_flush_pool();
        $counts = [];
        foreach ($this->_current_keys as $_k => $_hash_key) {
            $_get_count = xcache_get($_hash_key);
            $counts[$_k] = empty($_get_count) ? 0 : $_get_count;
        }
        return Lib_Helper::get_return_struct($counts);
    }

    /**
     * @return array
     */
    private function _del_all_counts()
    {
        $this->_pool = [];
        $del = [];
        foreach ($this->_current_keys as $_k => $_hash_key) {
            $del[$_k] = xcache_unset($_hash_key);
        }
        return Lib_Helper::get_return_struct($del);
    }

    /**
     * @param $callback
     * @param array $args
     */
    function fork_count_processor($callback, $args)
    {
        $this->_counter_pid = pcntl_fork();
        if ($this->_counter_pid < 0) {
            Lib_Log::warn(
                "%s fork failed! result: %s",
                [__METHOD__, var_export($this->_counter_pid, true)]
            );
            $this->_counter_pid = null;
        } elseif ($this->_counter_pid == 0) {
            call_user_func_array($callback, $args);
            exit;
        }
    }

    function recycle_count_processor()
    {
        if ($this->_counter_pid) {
            $status = null;
            pcntl_waitpid($this->_counter_pid, $status);
        }
    }

    /**
     * @param string $mid
     * @param string $count_key
     * @param string $base_key
     * @return mixed
     */
    static function register_ratio_key_map($mid, $count_key, $base_key = null)
    {
        return self::get_instance()->set_ratio_key_map($mid, $count_key, $base_key);
    }

    /**
     * @param string $mid
     * @param string $count_key
     * @param string $base_key
     * @return $this
     */
    function set_ratio_key_map($mid, $count_key, $base_key = null)
    {
        $this->_ratio_key_map[$mid] = [$count_key, $base_key];
        return $this;
    }

    /**
     * @param string $mid
     * @param string $pre_mid
     * @return array|null
     */
    function get_ratio_count($mid, $pre_mid = null)
    {
        if (
            !isset($this->_ratio_key_map[$mid])
            && !isset($this->_ratio_key_map[$mid][1])
        ) {
            return null;
        }
        $key = $this->_ratio_key_map[$mid][0];
        $base = $this->_ratio_key_map[$mid][1];
        $count = self::get_count($key);
        $count = $count[Const_DataAccess::MREK_DATA];
        $base_count = null;
        if (
            $base === null
            && $pre_mid !== null
            && isset($this->_ratio_key_map[$pre_mid][0])
        ) {
            $pre_key = $this->_ratio_key_map[$pre_mid][0];
            $base_count = self::get_count($pre_key);
        } else {
            $base_count = self::get_count($base);
        }
        $base_count = $base_count[Const_DataAccess::MREK_DATA];
        return [$count, $base_count];
    }
}
