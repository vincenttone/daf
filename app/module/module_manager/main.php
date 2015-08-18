<?php
/**
 * @name:	模块管理
 * @brief:	用于控制模块，获取模块配置
 * @author: vincent
 * @create:	2014-5-12
 * @update:	2014-5-12
 *
 * @type:	system
 * @register: web
 * @version: 1.0.1
 */
class Module_ModuleManager_Main extends Module_Base_System
{
    const RUN_MODULE_STATUS_RUNNING = 1; // 运行中
    const RUN_MODULE_STATUS_FAILED = 2; // 失败
    const RUN_MODULE_STATUS_FINISH = 3; // 完成
    const RUN_MODULE_STATUS_CALLING = 4; // 调度中
    const RUN_MODULE_STATUS_CALLED = 5; // 调度完毕
    const RUN_MODULE_STATUS_PREPARE = 6; // 准备中
    const RUN_MODULE_STATUS_SHUTDOWN = 7; // 人为终止
    const RUN_MODULE_STATUS_TERM = 8; // 正常终止
    const RUN_MODULE_STATUS_ABORT = 9; // 中断

    const HOOK_TYPE_BEFORE_RUN = 'before_run';
    const HOOK_TYPE_AFTER_RUN = 'after_run';
    const HOOK_TYPE_BEFORE_RUN_MODULE_PREFIX = 'before_run_module';
    const HOOK_TYPE_AFTER_RUN_MODULE_PREFIX = 'after_run_module';

    private static $_instance = null;
    private $_running_modules = [];
    private $_module_path = null;
    private $_hooks = [
        self::HOOK_TYPE_BEFORE_RUN => [],
        self::HOOK_TYPE_AFTER_RUN => [],
    ];

    static $run_module_status_list = [
        self::RUN_MODULE_STATUS_RUNNING => '运行中',
        self::RUN_MODULE_STATUS_FAILED => '运行失败',
        self::RUN_MODULE_STATUS_FINISH => '运行成功',
        self::RUN_MODULE_STATUS_CALLING => '调度中',
        self::RUN_MODULE_STATUS_CALLED => '调度完毕',
        self::RUN_MODULE_STATUS_PREPARE => '准备中',
        self::RUN_MODULE_STATUS_SHUTDOWN => '人为终止',
        self::RUN_MODULE_STATUS_TERM => '正常停止',
        self::RUN_MODULE_STATUS_ABORT => '中断',
    ];

    static $module_type_map = [
        'functional' => '功能模块',
        'system' => '系统模块',
        'configure' => '配置模块',
        'abstract' => '虚模块',
    ];

    private function __construct()
    {
    }
    /**
     * 获取实例的方法
     * @return $this
     */
    static public function get_instance()
    {
        if (is_null(self::$_instance)) {
            $class = __CLASS__;
            self::$_instance = new $class;
        }
        return self::$_instance;
    }

    /**
     * 禁用对象克隆
     */
    private function __clone()
    {
        throw new Exception("Could not clone the object from class: ".__CLASS__);
    }

    /**
     * @param string $path
     */
    function init_with_module_path($path)
    {
        $this->_module_path = $path;
    }

    /**
     * @return string
     */
    function get_module_path()
    {
        return $this->_module_path;
    }

    /**
     * @return string
     */
    static function module_path()
    {
        return self::get_instance()->get_module_path();
    }

    /**
     * @return array
     */
    static function register_router()
    {
        return [
            'modules' => ['Module_ModuleManager_Register', 'all_modules_action'],
            'module/list' => [
                'Module_ModuleManager_Action',
                'module_list_action',
                'GET',
                [
                    Const_DataAccess::URL_NAV => true,
                    Const_DataAccess::URL_NAME => '模块',
                    Const_DataAccess::URL_WEIGHT => 196,
                    Const_DataAccess::URL_CATALOG => Const_DataAccess::CATALOG_URL_SETUP,
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_FLOW_ADMIN,
                ],
            ],
            'module/refresh' => [
                'Module_ModuleManager_Action',
                'refresh_module_action',
                'GET',
                [
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_FLOW_ADMIN,
                    Const_DataAccess::URL_RENDER_TYPE => Module_View_Main::RENDER_TYPE_API,
                ]
            ],
        ];
    }

    /**
     * @param string $hook_type
     * @param string $hook_name
     * @param $callback
     * @param bool $run_once
     * @return $this
     */
    function register_hook($hook_type, $hook_name, $callback, $run_once = false)
    {
        isset($this->_hooks[$hook_type]) || ($this->_hooks[$hook_type] = []);
        $this->_hooks[$hook_type][$hook_name] = [$callback, $run_once];
        return $this;
    }

    /**
     * @param string $hook_name
     * @param $callback
     * @param bool $run_once
     * @return Module_ModuleManager_Main
     */
    function register_before_run_hook($hook_name, $callback, $run_once = false)
    {
        return $this->register_hook(self::HOOK_TYPE_BEFORE_RUN, $hook_name, $callback, $run_once);
    }

    /**
     * @param string $hook_name
     * @param $callback
     * @param bool $run_once
     * @return Module_ModuleManager_Main
     */
    function register_after_run_hook($hook_name, $callback, $run_once = false)
    {
        return $this->register_hook(self::HOOK_TYPE_AFTER_RUN, $hook_name, $callback, $run_once);
    }

    /**
     * @param string $type_prefix
     * @param string $mid
     * @return string
     */
    static function get_run_module_hook_type($type_prefix, $mid)
    {
        return $type_prefix.'_'.$mid;
    }

    /**
     * @param string $mid
     * @param string $hook_name
     * @param $callback
     * @param bool $run_once
     * @return Module_ModuleManager_Main
     */
    function register_before_run_module_hook($mid, $hook_name, $callback, $run_once = false)
    {
        return $this->register_hook(
            self::get_run_module_hook_type(self::HOOK_TYPE_BEFORE_RUN_MODULE_PREFIX, $mid),
            $hook_name,
            $callback,
            $run_once
        );
    }

    /**
     * @param string $mid
     * @param string $hook_name
     * @param $callback
     * @param bool $run_once
     * @return Module_ModuleManager_Main
     */
    function register_after_run_module_hook($mid, $hook_name, $callback, $run_once = false)
    {
        return $this->register_hook(
            self::get_run_module_hook_type(self::HOOK_TYPE_AFTER_RUN_MODULE_PREFIX, $mid),
            $hook_name,
            $callback,
            $run_once
        );
    }

    /**
     * @param string $hook_type
     * @param string $hook_name
     * @return bool
     */
    function hook_exists($hook_type, $hook_name = null)
    {
        if ($hook_name === null) {
            if(isset($this->_hooks[$hook_type])) {
                return true;
            } else {
                return false;
            }
        }
        if (isset($this->_hooks[$hook_type][$hook_name])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $hook_type
     * @param string $hook_name
     * @return array
     */
    function get_hook($hook_type, $hook_name)
    {
        if (!$this->hook_exists($hook_type, $hook_name)) {
            return null;
        }
        $hook = $this->_hooks[$hook_type][$hook_name];
        if (!isset($hook[1])) {
            Lib_Log::notice(
                "Hook: [%s.%s] [%s] not has run_once flag",
                [strval($hook_type), $hook_name, var_export($hook[1], true)]
            );
            unset($this->_hooks[$hook_type][$hook_name]);
            return null;
        }
        if (!is_callable($hook[0])) {
            Lib_Log::notice(
                "Hook: [%s.%s] [%s] not callable",
                [strval($hook_type), $hook_name, json_encode($hook[0])]
            );
            unset($this->_hooks[$hook_type][$hook_name]);
            return null;
        }
        return $hook;
    }

    /**
     * @param string $hook_type
     * @param string $hook_name
     * @param array $args
     * @return bool
     */
    function run_hook($hook_type, $hook_name, $args = [])
    {
        $hook = $this->get_hook($hook_type, $hook_name);
        if ($hook === null) {
            return false;
        }
        call_user_func_array($hook[0], $args);
        // run once, remove
        if ($hook[1] == true) {
            unset($this->_hooks[$hook_type][$hook_name]);
        }
        return true;
    }

    /**
     * @param string $hook_type
     * @param array $args
     * @return bool
     */
    function run_hooks($hook_type = self::HOOK_TYPE_BEFORE_RUN, $args = [])
    {
        if (!$this->hook_exists($hook_type)) {
            return false;
        }
        foreach ($this->_hooks[$hook_type] as $_k => $_hook) {
            $this->run_hook($hook_type, $_k, $args);
        }
        return true;
    }

    /**
     * @param int $task_id
     * @param int $mid
     * @return mixed
     */
    function get_module_run_id($task_id, $mid)
    {
        if (!isset($this->_running_modules[$mid]['m_run_id'])) {
            isset($this->_running_modules[$mid])
                || ($this->_running_modules[$mid] = []);
            $this->_running_modules[$mid]['m_run_id'] = self::module_run_id($task_id, $mid);
        }
        return $this->_running_modules[$mid]['m_run_id'];
    }

    /**
     * @param int $task_id
     * @param int $mid
     * @return string
     */
    static function module_run_id($task_id, $mid)
    {
        $task_begin_time = Module_ControlCentre_Main::current_task()->create_time;
        return $task_id . '-' . $mid . '-' . $task_begin_time;
    }

    /**
     * @param int $module_id
     * @return array
     */
    function get_module_class($module_id)
    {
        $cache_cls_name_key = 'class_name';
        $cache_cls_key = 'class';
        $class_name = null;
        if (isset($this->_running_modules[$module_id][$cache_cls_key])) {
            $class = $this->_running_modules[$module_id][$cache_cls_key];
        } else {
            $class_name = Module_ModuleManager_Register::functional_module_class_name($module_id);
            if ($class_name[Const_DataAccess::MREK_ERRNO] !== Const_Err_Base::ERR_OK) {
                return $class_name;
            }
            $class_name = $class_name[Const_DataAccess::MREK_DATA];
            $class = new $class_name();
            isset($this->_running_modules[$module_id])
                || ($this->_running_modules[$module_id] = []);
            $this->_running_modules[$module_id][$cache_cls_name_key] = $class_name;
            $this->_running_modules[$module_id][$cache_cls_key] = $class;
        }
        return $class;
    }

    /**
     * @param int $task_id
     * @param int $module_id
     * @param array $data
     * @param array $options
     * @return array
     */
    function run_module($task_id, $module_id, $data, $options)
    {
        $class = $this->get_module_class($module_id);
        Lib_Log::debug(
            "%s begin run module [%d]\ttask_id:[%d]\toptions:%s",
            [__METHOD__, $module_id, $task_id, json_encode($options)]
        );
        $class->set_options($options);
        $this->run_hooks(
            self::get_run_module_hook_type(self::HOOK_TYPE_BEFORE_RUN_MODULE_PREFIX, $module_id),
            [$task_id, $module_id, $class, $data]
        );
        $this->run_hooks(
            self::HOOK_TYPE_BEFORE_RUN,
            [$task_id, $module_id, $class, $data]
        );
        try {
            $result = $class->run($task_id, $data);
        } catch (Exception $ex) {
            $code = $ex->getCode();
            $msg = $ex->getMessage();
            Lib_Log::error("Got a Exception, code [%d], message [%s]", [$code, $msg]);
            $result = Lib_Helper::get_err_struct($code, $msg);
        }
        $this->run_hooks(
            self::get_run_module_hook_type(self::HOOK_TYPE_AFTER_RUN_MODULE_PREFIX, $module_id),
            [$task_id, $module_id, $class, $result]
        );
        $this->run_hooks(
            self::HOOK_TYPE_AFTER_RUN,
            [$task_id, $module_id, $class, $result]
        );
        return $result;
    }
}
