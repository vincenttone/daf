<?php
class Module_ControlCentre_Task
{
    const HOOK_TYPE_BEFORE_RUN = 1;
    const HOOK_TYPE_AFTER_RUN = 2;

    public $id = null;
    public $options = [];
    public $create_time = null;
    private $_hooks = [
        self::HOOK_TYPE_BEFORE_RUN => [],
        self::HOOK_TYPE_AFTER_RUN => [],
    ];

    /**
     * @param int $task_id
     * @throws Exception
     */
    public function __construct($task_id = null)
    {
        if ($task_id) {
            $this->id = $task_id;
        } else {
            $id_gen = new Model_IdGen();
            $_id = $id_gen->gen_inc_id_by_key(Const_DataAccess::ID_TASK);
            if ($_id['errno'] != Const_Err_Base::ERR_OK) {
                throw new Exception('Get task id failed!!!! '.json_encode($_id));
            }
            $this->id = $_id['data'];
            unset($id_gen);
        }
        $this->create_time = time();
    }

    /**
     * @param string $hook_name
     * @param $callback
     * @param bool $run_once
     * @return $this
     */
    public function register_before_run_hook($hook_name, $callback, $run_once = false)
    {
        $this->_hooks[self::HOOK_TYPE_BEFORE_RUN][$hook_name] = [$callback, $run_once];
        return $this;
    }

    /**
     * @param string $hook_name
     * @param $callback
     * @param bool $run_once
     * @return $this
     */
    public function register_after_run_hook($hook_name, $callback, $run_once = false)
    {
        $this->_hooks[self::HOOK_TYPE_AFTER_RUN][$hook_name] = [$callback, $run_once];
        return $this;
    }

    /**
     * @param int $hook_type
     * @param array $args
     * @return bool
     */
    function run_hooks($hook_type = self::HOOK_TYPE_BEFORE_RUN, $args = [])
    {
        if(!isset($this->_hooks[$hook_type])) {
            return false;
        }
        foreach ($this->_hooks[$hook_type] as $_name => $_hook) {
            if (!isset($_hook[1])) {
                unset($this->_hooks[$hook_type][$_name]);
                continue;
            }
            if (is_callable($_hook[0])) {
                Lib_Log::debug(
                    "TASK_HOOK: call hook type [%d], name [%s], once: [%s]",
                    [$hook_type, $_name, var_export($_hook[1], true)]
                );
                call_user_func_array($_hook[0], $args);
            } else {
                Lib_Log::notice("TASK_HOOK: hook [%s] not callable", json_encode($_hook[0]));
            }
            // run once, remove
            if ($_hook[1] == true) {
                unset($this->_hooks[$hook_type][$_name]);
            }
        }
        return true;
    }

    /**
     * @param array $data
     * @param array $control_options
     * @return array
     */
    public function run($data= [], $control_options= [])
    {
        $begin_time = microtime(true);
        Lib_Log::monitor(
            "TIMER task [%d] begin at [%.4f]",
            [
                Module_ControlCentre_Main::current_task_id(),
                $begin_time,
            ]
        );
        $this->options = $control_options;
        $flow = Module_ControlCentre_FlowManager::get_instance()->get_current_flow();
        // run before hooks
        $this->run_hooks(self::HOOK_TYPE_BEFORE_RUN, [$data]);
        // run
        $result = $flow->run($this->id, $data);
        // run after hook
        $this->run_hooks(self::HOOK_TYPE_AFTER_RUN, [$result]);
        $end_time = microtime(true);
        Lib_Log::monitor(
            "TIMER task [%d] end at [%.4f], use [%.4f]",
            [
                Module_ControlCentre_Main::current_task_id(),
                $end_time,
                $end_time - $begin_time,
            ]
        );
        return $result;
    }

    /**
     * @param string $key
     * @return string
     */
    function get_ctl_cmd($key)
    {
        if (
            isset($this->options['ctl_cmd'])
            && isset($this->options['ctl_cmd'][$key])
        ) {
            return $this->options['ctl_cmd'][$key];
        }
        return null;
    }

    /**
     * @param string $key
     * @param string $val
     * @return $this
     */
    function set_ctl_cmd($key, $val)
    {
        isset($this->options['ctl_cmd'])
            || ($this->options['ctl_cmd'] = []);
        $this->options['ctl_cmd'][$key] = $val;
        return $this;
    }

    /**
     * @param string $key
     * @return $this
     */
    function unset_ctl_cmd($key)
    {
        if (isset($this->options['ctl_cmd'][$key])) {
            unset($this->options['ctl_cmd'][$key]);
        }
        return $this;
    }

    /**
     * @param array $cmds
     * @return $this
     */
    function mset_ctl_cmds($cmds)
    {
        isset($this->options['ctl_cmd'])
            || ($this->options['ctl_cmd'] = []);
        $this->options['ctl_cmd'] = array_merge(
            $this->options['ctl_cmd'],
            $cmds
        );
        return $this;
    }

    /**
     * @param array $cmd_keys
     * @return $this
     */
    function munset_ctl_cmds($cmd_keys)
    {
        foreach ($cmd_keys as $_ck) {
            $this->unset_ctl_cmd($_ck);
        }
        return $this;
    }

    /**
     * @param int $id
     * @return mixed
     */
    static function task($id)
    {
        $task_model = new Model_Task();
        return $task_model->get_one_by_id($id);
    }
}