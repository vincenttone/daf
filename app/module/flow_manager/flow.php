<?php
class Module_FlowManager_Flow
{
    use class_common_hooks;

    public $id = null;
    public $name = '';
    public $flow = [];
    public $options = [];
    public $modules = [];
    private $_run_mode = Module_FlowManager_Main::RUN_MODE_CALLBACK;
    private $_ordered_mids = null;
    private $_current_running_mid = null;
    // for hooks
    const HOOK_TYPE_BEFORE_RUN_MODULE = 1;
    const HOOK_TYPE_AFTER_RUN_MODULE = 2;
    const HOOK_TYPE_BEFORE_RUN_SECTION = 3;
    const HOOK_TYPE_AFTER_RUN_SECTION = 4;
    const HOOK_TYPE_BEFORE_RUN_FLOW = 5;
    const HOOK_TYPE_AFTER_RUN_FLOW = 6;
    const HOOK_PREFIX_BEFORE_FLOW_MODULE = 'before_rfm';
    const HOOK_PREFIX_AFTER_FLOW_MODULE = 'after_rfm';
    // end for hooks

    /**
     * @param int $flow_id
     * @param array $control_options
     */
    public function __construct($flow_id = null, $control_options = [])
    {
        if (!empty($flow_id)) {
            $this->id = $flow_id;
        }
        if (!empty($control_options)) {
            $this->options = $control_options;
        }
    }

    /**
     * @param int $id
     * @return $this
     */
    public function set_id($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $name
     * @param array $flow
     * @param array $options
     * @return $this
     */
    public function set_flow($name, $flow, $options = [])
    {
        $this->name = $name;
        $this->flow = $flow;
        $this->options = $options;
        return $this;
    }

    /**
     * @param array $mode
     * @return $this
     */
    public function set_run_mode($mode)
    {
        $this->_run_mode = $mode;
        return $this;
    }

    /**
     * @param array $custom_flow
     * @return $this
     */
    public function custom_flow($custom_flow)
    {
        $this->flow = $custom_flow;
        return $this;
    }

    /**
     * @return $this|bool
     */
    public function get()
    {
        $flow_model = new Model_FlowInfo();
        $flow = $flow_model->get_flow_info_by_id($this->id);
        if ($flow['errno'] !== Const_Err_Base::ERR_OK) {
            Lib_Log::error(__METHOD__.' faild! cause: '.Lib_Helper::format_err_struct($flow));
            return false;
        }
        $flow = $flow['data'];
        if ($flow) {
            $this->name = $flow[Model_FlowInfo::FIELD_FLOW_NAME];
            $this->flow = $flow[Model_FlowInfo::FIELD_FLOW_FLOW];
            isset($flow[Model_FlowInfo::FIELD_FLOW_OPTIONS])
                || $flow[Model_FlowInfo::FIELD_FLOW_OPTIONS] = [];
            $this->options = array_merge($flow[Model_FlowInfo::FIELD_FLOW_OPTIONS], $this->options);
            $this->modules = $this->flow[Module_FlowManager_Main::FLOW_TYPE_MAIN];
            if (isset($this->flow[Module_FlowManager_Main::FLOW_TYPE_BRANCH])) {
                foreach ($this->flow[Module_FlowManager_Main::FLOW_TYPE_BRANCH] as $_m) {
                    if (is_array($_m)) {
                        $this->modules = array_merge($this->modules, $_m);
                    } else {
                        $this->modules[] = $_m;
                    }
                }
            }
            if (isset($this->flow[Module_FlowManager_Main::FLOW_TYPE_STUFF])) {
                foreach ($this->flow[Module_FlowManager_Main::FLOW_TYPE_STUFF] as $_m) {
                    if (is_array($_m)) {
                        $this->modules = array_merge($this->modules, $_m);
                    } else {
                        $this->modules[] = $_m;
                    }
                }
            }
            if (isset($this->flow[Module_FlowManager_Main::FLOW_TYPE_CONFIG])) {
                $this->modules = array_merge($this->modules, $this->flow[Module_FlowManager_Main::FLOW_TYPE_CONFIG]);
            }
            return $this;
        }
        return false;
    }

    /**
     * @param array $data
     * @return array
     */
    public function add($data)
    {
        if(isset($data[Model_FlowInfo::FIELD_FLOW_ID]))
        {
            unset($data[Model_FlowInfo::FIELD_FLOW_ID]);
        }
        $id_gen = new Model_IdGen();
        $id = $id_gen->gen_inc_id_by_key(Const_DataAccess::ID_FLOW);
        if ($id['errno'] != Const_Err_Base::ERR_OK) {
            return $id;
        }
        $id = $id['data'];
        if (empty($id)) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_GET_ID_FAILD,
                '获取ID失败',
                __FILE__,
                __LINE__
            );
        }
        
        $data[Model_FlowInfo::FIELD_FLOW_ID] = intval($id);
       
        $flow_model = new Model_FlowInfo();
        return $flow_model->add_flow($data);
    }

    /**
     * @param int $id
     * @param array $data
     * @return array|mixed
     */
    public function update($id, $data)
    {
        if(isset($data[Model_FlowInfo::FIELD_FLOW_ID])) {
            unset($data[Model_FlowInfo::FIELD_FLOW_ID]);
        }
        if (empty($id)) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_GET_ID_FAILD,
                '获取ID失败',
                __FILE__,
                __LINE__
            );
        }
        
        $flow_model = new Model_FlowInfo();
        return $flow_model->update_by_id($id, $data);
    }

    /**
     * @return array
     */
    public function save()
    {
        if (empty($this->id)) {
            $id_gen = new Model_IdGen();
            $id = $id_gen->gen_inc_id_by_key(Const_DataAccess::ID_FLOW);
            if ($id['errno'] != Const_Err_Base::ERR_OK) {
                return $id;
            }
            $id = $id['data'];
            if (empty($id)) {
                return Lib_Helper::get_err_struct(
                    Const_Err_DataAccess::ERR_GET_ID_FAILD,
                    '获取ID失败',
                    __FILE__,
                    __LINE__
                );
            }
            $this->id = $id;
        }
        if (empty($this->flow) || empty($this->id)) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_FLOW_EMPTY_FLOW,
                '[空流程] 未设置流程',
                __FILE__,
                __LINE__
            );
        }
        $data = [
            Model_FlowInfo::FIELD_FLOW_ID => $this->id,
            Model_FlowInfo::FIELD_FLOW_NAME => $this->name,
            Model_FlowInfo::FIELD_FLOW_FLOW => $this->flow,
            Model_FlowInfo::FIELD_FLOW_OPTIONS => $this->options,
        ];
        $flow_model = new Model_FlowInfo();
        $save = $flow_model->save($data);
        if ($save['errno'] !== Const_Err_Base::ERR_OK) {
            Lib_Log::error(__METHOD__.' faild! cause: '.json_encode($save['data']));
            return Lib_Helper::get_err_struct(
                Const_Err_Db::ERR_SAVE_DATA_FAIL,
                __METHOD__.'数据保存失败',
                __FILE__,
                __LINE__
            );
        }
        return Lib_Helper::get_return_struct($this->id);
    }

    /**
     * @param int $task_id
     * @param int $called_mid
     * @return array
     */
    public function gen_module_callback_in_loop($task_id, $called_mid)
    {
        if (!isset($this->flow[Module_FlowManager_Main::FLOW_TYPE_STUFF])) {
            Lib_Log::notice("FLOW_CALL: No stuff module, module [%d]", $called_mid);
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_FLOW_UNEXPECT_MODE,
                'empty stuff'
            );
        }
        $callback_relation_mids = $this->flow[Module_FlowManager_Main::FLOW_TYPE_STUFF];
        if (!isset($callback_relation_mids[$called_mid])) {
            Lib_Log::notice("FLOW_CALL: No stuff module for module [%d]", $called_mid);
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_FLOW_UNEXPECT_MODE,
                'no stuff to call'
            );
        }
        $mids_to_call = $callback_relation_mids[$called_mid];
        $module_callback = null;
        if (empty($mids_to_call)) {
            Lib_Log::info("FLOW_CALL: empty mids to call. %d -> %s", [$called_mid, json_encode($callback_relation_mids)]);
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_FLOW_UNEXPECT_MODE,
                'empty sutff for module'
            );
        } else {
            Lib_Log::debug("FLOW_CALL: begin gen callback, task id %d, module: %d -> %s", [$task_id, $called_mid, json_encode($mids_to_call)]);
            $module_callback = function($data) use ($task_id, $mids_to_call, $called_mid) {
                Lib_Log::debug(
                    function() use ($task_id, $called_mid, $mids_to_call){
                        return vsprintf(
                            "FLOW_CALL: [%d] callback modules %s, task id: %d",
                            [$called_mid, json_encode($mids_to_call), $task_id]
                        );
                    }
                );
                do {
                    $current_mid = array_shift($mids_to_call);
                    $data = $this->_run_module($task_id, $current_mid, $data);
                    if (!isset($data['errno'])) {
                        Lib_Log::error("Call back return faild!. err: return data not has errno.\tdata:%s", json_encode($data));
                        $data = null;
                        break;
                    } elseif ($data['errno'] !== Const_Err_Base::ERR_OK) {
                        Lib_Log::error("Call back return faild!. err: %s", Lib_Helper::format_err_struct($data));
                        $data = null;
                        break;
                    }
                    $data = $data['data'];
                } while (!empty($mids_to_call));
                return $data;
            };
        }
        return Lib_Helper::get_return_struct($module_callback);
    }

    /**
     * @param int $task_id
     * @param array $data
     * @return array
     */
    public function run($task_id, $data = [])
    {
        $single_module = null;
        $continue_mid = null;
        $flow_info = $this->options;
        if (isset($flow_info[Module_FlowManager_Main::RUN_OPTION_SINGLE_MODULE_ID])
        && !empty($flow_info[Module_FlowManager_Main::RUN_OPTION_SINGLE_MODULE_ID])
        ) {
            $single_module = $flow_info[Module_FlowManager_Main::RUN_OPTION_SINGLE_MODULE_ID];
            Lib_Log::debug("%s use single module, mid %d", [__CLASS__, $single_module]);
        }
        if (isset($flow_info[Module_FlowManager_Main::RUN_OPTION_CONTINUE_MODULE_ID])
        && !empty($flow_info[Module_FlowManager_Main::RUN_OPTION_CONTINUE_MODULE_ID])
        ) {
            $continue_mid = $flow_info[Module_FlowManager_Main::RUN_OPTION_CONTINUE_MODULE_ID];
            Lib_Log::debug("%s continue run from mid %d", [__CLASS__, $continue_mid]);
        }
        switch ($this->_run_mode) {
            case Module_FlowManager_Main::RUN_MODE_IN_ORDER:
                $this->flow[Module_FlowManager_Main::FLOW_TYPE_MAIN] = $this->get_in_order_modules();
                $this->flow[Module_FlowManager_Main::FLOW_TYPE_STUFF] = [];
                break;
            case Module_FlowManager_Main::RUN_MODE_CALLBACK:
            default:
                break;
        }
        // before flow hook
        $this->run_hooks_by_type(self::HOOK_TYPE_BEFORE_RUN_FLOW, [$task_id]);
        // begin run flow
        if (is_null($single_module)) {
            $result = $this->run_flow($task_id, $data, $continue_mid);
        } else {
            $result = $this->run_module_in_flow($task_id, $single_module);
        }
        // after flow hook
        $this->run_hooks_by_type(self::HOOK_TYPE_AFTER_RUN_FLOW, [$task_id, $result]);
        return $result;
    }

    /**
     * @return array
     */
    public function get_in_order_modules()
    {
        if ($this->_ordered_mids === null) {
            $main_flow = [];
            $stuff = $this->flow[Module_FlowManager_Main::FLOW_TYPE_STUFF];
            foreach ($this->flow[Module_FlowManager_Main::FLOW_TYPE_MAIN] as $_mid) {
                array_push($main_flow, $_mid);
                if (isset($stuff[$_mid])) {
                    if(is_array($stuff[$_mid])) {
                        foreach ($stuff[$_mid] as $__mid) {
                            array_push($main_flow, $__mid);
                        }
                    } else {
                        array_push($main_flow, $stuff[$_mid]);
                    }
                }
            }
            $this->_ordered_mids = $main_flow;
        }
        return $this->_ordered_mids;
    }

    /**
     * @param int $task_id
     * @param array $data
     * @param null $continue_mid
     * @return array
     */
    public function run_flow($task_id, $data = [], $continue_mid = null)
    {
        $pre_mid = null;
        $mids = $this->flow[Module_FlowManager_Main::FLOW_TYPE_MAIN];
        foreach ($mids as $_mid) {
            if (!empty($continue_mid)) {
                if ($continue_mid == $_mid) {
                    $continue_mid = null;
                    if ($pre_mid) {
                        $meta_data = self::read_meta($task_id, $pre_mid);
                        if ($meta_data['errno'] !== Const_Err_Base::ERR_OK) {
                            return $meta_data;
                        }
                        $data = $meta_data['data'];
                    }
                } else {
                    $pre_mid = $_mid;
                    continue;
                }
            }
            // run main flow module
            $begin_time = microtime(true);
            $data = $this->_run_section($task_id, $_mid, $data);
            $end_time = microtime(true);
            Lib_Log::monitor(
                "TIMER: task [%d] run module section [%d] at [%.4f], end at [%.4f], use [%.4f]",
                [
                    Module_ControlCentre_Main::current_task_id(),
                    $_mid,
                    $begin_time,
                    $end_time,
                    $end_time - $begin_time,
                ]
            );
            if (!isset($data['errno'])) {
                Lib_Log::error("Call back return faild!. err: return data not has errno.\tdata:%s", json_encode($data));
                return Lib_Helper::get_err_struct(Const_Err_Base::ERR_DATA_FORMAT, '模块['.$_mid.']返回数据错误');
            }
            if ($data['errno'] !== Const_Err_Base::ERR_OK) {
                return $data;
            }
            $data = $data['data'];
        }
        return Lib_Helper::get_return_struct($data);
    }

    /**
     * @param int $task_id
     * @param int $mid
     * @param string $class
     * @return string
     */
    private function _loop_callback_hook($task_id, $mid, $class)
    {
        if (
            !isset($this->flow[Module_FlowManager_Main::FLOW_TYPE_MAIN])
            || !isset($this->flow[Module_FlowManager_Main::FLOW_TYPE_STUFF])
        ) {
            Lib_Log::info("empty task flow main or stuff, will not run callback hook, id: %d", $this->id);
            return null;
        }
        // 安全检查，只有主模块才能注册回调
        $main_mids = $this->flow[Module_FlowManager_Main::FLOW_TYPE_MAIN];
        if (!in_array($mid, $main_mids)) {
            Lib_Log::notice("mid [%d] not in flow, flow id: [%d]", [$mid, $this->id]);
            return null;
        }
        // 没有回调方案
        $callback_relation_mids = $this->flow[Module_FlowManager_Main::FLOW_TYPE_STUFF];
        if (!isset($callback_relation_mids[$mid])) {
            return null;
        }
        Lib_Log::debug(
            function () use ($mid, $callback_relation_mids) {
                return vsprintf(
                    "FLOW_CALL: prepare to gen callback for mid [%d], mids [%s]",
                    [$mid, json_encode($callback_relation_mids[$mid])]
                );
            }
        );
        // 生成回调
        $callback = $this->gen_module_callback_in_loop($task_id, $mid);
        // 注册
        if ($callback['errno'] == Const_Err_Base::ERR_OK) {
            $callback = $callback['data'];
            Lib_Log::debug(
                function() use ($task_id, $mid, $callback, $class){
                    return vsprintf("FLOW_CALL: register callback to class [%s], mid: %d", [get_class($class), $mid]);
                }
            );
            $class->register_callback($callback);
        } else {
            Lib_Log::notice("FLOW_CALL: gen callback failed. return %s", Lib_Helper::format_err_struct($callback));
        }
    }

    /**
     * @param int $mid
     * @return array
     */
    function section_ordered_stuff_mids($mid)
    {
        $mids = [];
        if (!in_array($mid, $this->flow[Module_FlowManager_Main::FLOW_TYPE_MAIN])) {
            return $mids;
        }
        if (isset($this->flow[Module_FlowManager_Main::FLOW_TYPE_STUFF][$mid])) {
            $mids = $this->flow[Module_FlowManager_Main::FLOW_TYPE_STUFF][$mid];
        }
        return $mids;
    }

    /**
     * @param string $hook_name
     * @param $callback
     * @return $this
     */
    function register_before_run_flow_hook($hook_name, $callback)
    {
        return $this->register_hook(
            self::HOOK_TYPE_BEFORE_RUN_FLOW,
            $hook_name,
            $callback,
            true
        );
    }

    /**
     * @param string $hook_name
     * @param $callback
     * @return $this
     */
    function register_after_run_flow_hook($hook_name, $callback)
    {
        return $this->register_hook(
            self::HOOK_TYPE_AFTER_RUN_FLOW,
            $hook_name,
            $callback,
            true
        );
    }

    /**
     * @param string $hook_name
     * @param $callback
     * @param bool $run_once
     * @return $this
     */
    function register_before_run_section_hook($hook_name, $callback, $run_once)
    {
        return $this->register_hook(
            self::HOOK_TYPE_BEFORE_RUN_SECTION,
            $hook_name,
            $callback,
            $run_once
        );
    }

    /**
     * @param string $hook_name
     * @param $callback
     * @param bool $run_once
     * @return $this
     */
    function register_after_run_section_hook($hook_name, $callback, $run_once)
    {
        return $this->register_hook(
            self::HOOK_TYPE_AFTER_RUN_SECTION,
            $hook_name,
            $callback,
            $run_once
        );
    }

    /**
     * @param int $mid
     * @param string $hook_name
     * @param $callback
     * @param bool $run_once
     * @return $this
     */
    function register_before_run_module_hook($mid, $hook_name, $callback, $run_once)
    {
        $type = $mid === null
            ? self::HOOK_TYPE_BEFORE_RUN_MODULE
            : self::_dynamic_hook_type(self::HOOK_PREFIX_BEFORE_FLOW_MODULE, $mid);
        return $this->register_hook(
            $type,
            $hook_name,
            $callback,
            $run_once
        );
    }

    /**
     * @param int $mid
     * @param string $hook_name
     * @param $callback
     * @param bool $run_once
     * @return $this
     */
    function register_after_run_module_hook($mid, $hook_name, $callback, $run_once)
    {
        $type = $mid === null
            ? self::HOOK_TYPE_AFTER_RUN_MODULE
            : self::_dynamic_hook_type(self::HOOK_PREFIX_AFTER_FLOW_MODULE, $mid);
        return $this->register_hook(
            $type,
            $hook_name,
            $callback,
            $run_once
        );
    }

    /**
     * run a section of module(s)
     * @param int $task_id (current task id)
     * @param int $mid  (moudle id, must be a main module in flow!)
     * @param array $data (run data)
     * @return array return (err_struct or return_struct)
     */
    private function _run_section($task_id, $mid, $data)
    {
        if (!in_array($mid, $this->flow[Module_FlowManager_Main::FLOW_TYPE_MAIN])) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_MODULE_WRONG_TYPE,
                $mid. ' is not a main module is flow '.$this->id
            );
        }
        $callback = null;
        if ($this->_run_mode == Module_FlowManager_Main::RUN_MODE_CALLBACK) {
            $callback = function($task_id, $module_id, $class, $data) use ($mid) {
                $this->_loop_callback_hook($task_id, $mid, $class);
            };
            Module_ModuleManager_Main::get_instance()
                ->register_before_run_module_hook($mid, 'm_callback', $callback, true);
        }
        // before section hook
        $this->run_hooks_by_type(self::HOOK_TYPE_BEFORE_RUN_SECTION, [$task_id, $mid]);
        // before run hook for section modules
        $ordered_smids = $this->section_ordered_stuff_mids($mid);
        $this->run_hooks_by_type(
            self::HOOK_TYPE_BEFORE_RUN_MODULE,
            [$task_id, $mid, Module_FlowManager_Main::FLOW_MODULE_TYPE_MAIN]
        );
        $this->run_hooks_by_type(
            self::_dynamic_hook_type(self::HOOK_PREFIX_BEFORE_FLOW_MODULE, $mid),
            [$task_id, $mid, Module_FlowManager_Main::FLOW_MODULE_TYPE_MAIN]
        );
        foreach ($ordered_smids as $_mid) {
            $this->run_hooks_by_type(
                self::HOOK_TYPE_BEFORE_RUN_MODULE,
                [$task_id, $_mid, Module_FlowManager_Main::FLOW_MODULE_TYPE_STUFF]
            );
            $this->run_hooks_by_type(
                self::_dynamic_hook_type(self::HOOK_PREFIX_BEFORE_FLOW_MODULE, $_mid),
                [$task_id, $_mid, Module_FlowManager_Main::FLOW_MODULE_TYPE_STUFF]
            );
        }
        // run module
        $this->_current_running_mid = $mid;
        $data = $this->_run_module($task_id, $mid, $data);
        // after run hook for section modules
        $this->run_hooks_by_type(
            self::HOOK_TYPE_AFTER_RUN_MODULE,
            [$task_id, $mid, Module_FlowManager_Main::FLOW_MODULE_TYPE_MAIN, &$data]
        );
        $this->run_hooks_by_type(
            self::_dynamic_hook_type(self::HOOK_PREFIX_AFTER_FLOW_MODULE, $mid),
            [$task_id, $mid, Module_FlowManager_Main::FLOW_MODULE_TYPE_MAIN, &$data]
        );
        foreach ($ordered_smids as $_mid) {
            $this->run_hooks_by_type(
                self::HOOK_TYPE_AFTER_RUN_MODULE,
                [$task_id, $_mid, Module_FlowManager_Main::FLOW_MODULE_TYPE_STUFF, &$data]
            );
            $this->run_hooks_by_type(
                self::_dynamic_hook_type(self::HOOK_PREFIX_AFTER_FLOW_MODULE, $_mid),
                [$task_id, $_mid, Module_FlowManager_Main::FLOW_MODULE_TYPE_STUFF, &$data]
            );
        }
        // after section hook
        $this->run_hooks_by_type(self::HOOK_TYPE_AFTER_RUN_SECTION, [$task_id, $mid, &$data]);
        // write meta file
        $_meta_field = ($data['errno'] == Const_Err_Base::ERR_OK)
            ? $_meta_field = 'data'
            : $_meta_field = 'meta';
        isset($data[$_meta_field])
            && $this->_write_meta_file($task_id, $mid, $data[$_meta_field]);
        return $data;
    }

    /**
     * @param int $task_id
     * @param int $mid
     * @param array $data
     * @return array
     */
    private function _run_module($task_id, $mid, $data)
    {
        $data = Module_ModuleManager_Main::get_instance()
            ->run_module($task_id, $mid, $data, $this->options);
        // 数据检查
        if (!isset($data['errno'])) {
            Lib_Log::error(
                "run module [%d] faild!. err: return data not has errno.\tdata:%s",
                [$mid, json_encode($data)]
            );
            return Lib_Helper::get_err_struct(
                Const_Err_Base::ERR_DATA_FORMAT,
                '模块['.$mid.']返回数据错误'
            );
        }
        return $data;
    }

    /**
     * @param int $task_id
     * @param int $mid
     * @param array $data
     * @return bool|int
     */
    private function _write_meta_file($task_id, $mid, $data)
    {
        // 检查meta选项，如果有则生成meta文件
        if (
            isset($this->options[Module_FlowManager_Main::EXPORT_META_FILE])
            && $this->options[Module_FlowManager_Main::EXPORT_META_FILE]
            == Module_FlowManager_Main::META_FILE_CREATE
        ) {
            Lib_Log::debug("%s use meta file, task: %d, mid %d", [__CLASS__, $task_id, $mid]);
            $meta_file = self::meta_file_path($task_id, $mid);
            if ($meta_file) {
                return file_put_contents($meta_file, json_encode($data));
            }
        }
        return false;
    }

    /**
     * @param int $task_id
     * @param int $module_id
     * @return array|mixed
     */
    public function run_module_in_flow($task_id, $module_id)
    {
        $mids = $this->flow[Module_FlowManager_Main::FLOW_TYPE_MAIN];
        $key = array_search($module_id, $mids);
        if ($key === false) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_MODULE_NOT_IN_FLOW,
                '模块['.$module_id.']不在主流程中，流程ID'.'['.$this->id.']'
            );
        }
        $data = [];
        if ($key > 0) {
            $pre_module_id = $mids[$key - 1];
            $data = self::read_meta($task_id, $pre_module_id);
            if ($data['errno'] !== Const_Err_Base::ERR_OK) {
                return $data;
            }
            $data = $data['data'];
        }
        $_stuff_mids = null;
        isset($this->flow[Module_FlowManager_Main::FLOW_TYPE_STUFF][$module_id])
            && $_stuff_mids = $this->flow[Module_FlowManager_Main::FLOW_TYPE_STUFF][$module_id];
        $data = $this->_run_section($task_id, $module_id, $data);
        return $data;
    }

    /**
     * @param int $task_id
     * @param int $module_id
     * @return string
     */
    static function meta_file_path($task_id, $module_id)
    {
        $meta_file = Module_ControlCentre_Main::data_file(
            'meta/task/'.$task_id.'/'.$module_id
            .'.'.Module_FlowManager_Main::META_FILE_SUFFIX
        );
        $meta_dir = dirname($meta_file);
        if (!file_exists($meta_dir)) {
            @mkdir($meta_dir, 0744, true);
        }
        return $meta_file;
    }

    /**
     * @param int $task_id
     * @param int $mid
     * @return array|mixed
     */
    static function read_meta($task_id, $mid)
    {
        $mtrmr = new Model_TaskRunModuleRecord();
        $pre_result = $mtrmr->get_nearest_record_by_task_and_module($task_id, $mid, null, ['result']);
        if ($pre_result[Const_DataAccess::MREK_ERRNO] != Const_Err_Base::ERR_OK) {
            return $pre_result;
        }
        $pre_result = $pre_result[Const_DataAccess::MREK_DATA];
        if (
            !isset($pre_result['result'])
            || !isset($pre_result['result'][Const_DataAccess::MREK_ERRNO])
        ) {
            return Lib_Helper::get_err_struct(
                Const_Err_Base::ERR_UNEXPECT_RETURN,
                'there is no result to use: '.json_encode($pre_result)
            );
        }
        $meta = null;
        if (
            $pre_result['result'][Const_DataAccess::MREK_ERRNO] == Const_Err_Base::ERR_OK
            && isset($pre_result['result'][Const_DataAccess::MREK_DATA])
        ){
            $meta = $pre_result['result'][Const_DataAccess::MREK_DATA];
        } elseif (isset($pre_result['result'][Const_DataAccess::MREK_META])) {
            $meta = $pre_result['result'][Const_DataAccess::MREK_META];            
        } else {
            return Lib_Helper::get_err_struct(
                Const_Err_Base::ERR_UNEXPECT_RETURN,
                'there is no result to use: '.json_encode($pre_result)
            );
        }
        return Lib_Helper::get_return_struct($meta);
    }

    /**
     * @return int
     */
    function get_current_running_mid()
    {
        return $this->_current_running_mid;
    }
}
