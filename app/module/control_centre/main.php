<?php
/**
 * @name:	调度中心
 * @brief:	用于调度流程
 * @author: vincent
 * @create:	2014-5-16
 * @update:	2014-5-16
 *
 * @type:	system
 * @register: web
 * @version: 1.0.2
 */
class Module_ControlCentre_Main
{
    const TASK_STATUS_CREATE = 1; // 新建
    const TASK_STATUS_RUNNING = 2; // 运行中
    const TASK_STATUS_FINISH = 3; // 完成
    const TASK_STATUS_FAILED = 4; // 失败
    const TASK_STATUS_SHUTDOWN = 5; // 人为终止
    const TASK_STATUS_TERM = 6; // 正常终止
    const TASK_STATUS_ABORT = 7; // 中止

    const URL_CATALOG_TASK = '任务';

    use singleton_with_get_instance;

    private $_current_task = null;

    public static $task_status_list = [
        self::TASK_STATUS_CREATE => '新建',
        self::TASK_STATUS_RUNNING => '运行中',
        self::TASK_STATUS_FINISH => '完成',
        self::TASK_STATUS_FAILED => '失败',
        self::TASK_STATUS_SHUTDOWN => '人为停止',
        self::TASK_STATUS_TERM => '正常停止',
        self::TASK_STATUS_ABORT => '中断',
    ];

    private static $_module_task_stat_map = [
        Module_ModuleManager_Main::RUN_MODULE_STATUS_RUNNING => self::TASK_STATUS_RUNNING,
        Module_ModuleManager_Main::RUN_MODULE_STATUS_FINISH => self::TASK_STATUS_FINISH,
        Module_ModuleManager_Main::RUN_MODULE_STATUS_FAILED => self::TASK_STATUS_FAILED,
        Module_ModuleManager_Main::RUN_MODULE_STATUS_SHUTDOWN => self::TASK_STATUS_SHUTDOWN,
        Module_ModuleManager_Main::RUN_MODULE_STATUS_TERM => self::TASK_STATUS_TERM,
        Module_ModuleManager_Main::RUN_MODULE_STATUS_ABORT => self::TASK_STATUS_ABORT,
    ];

    /**
     * @return string
     */
    static function data_path()
    {
        return Da\Sys_App::data_path();
    }

    /**
     * @param string $file_name
     * @return string
     */
    static function data_file($file_name)
    {
        $file = self::data_path().'/'.$file_name;
        if (!is_dir(dirname($file))) {
            @mkdir(dirname($file), 0755,true);
        }
        return $file;
    }

    /**
     * 文件任务入口
     * @param int $ap_id
     * @param array $data
     * @param array $options
     * @param int $task_id
     * @return array
     */
    static function run_task_by_ap_id_and_record($ap_id, $data = [], $options = [], $task_id = null)
    {
        // get ap info
        $ap_manager = Module_ControlCentre_ApManager::get_instance()->set_current_ap($ap_id);
        if (empty($ap_manager)) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_GET_ACCESS_POINT_FAILD ,
                'Get ap failed! Result:'.Lib_Helper::format_err_struct($ap_id)
            );
        }
        $task_model = new Model_Task();
        $nearest_task = $task_model->get_nearest_task_by_ap_id(
            $ap_id,
            null,
            ['status', 'create_time']
        );
        if ($nearest_task[Const_DataAccess::MREK_ERRNO] != Const_Err_Base::ERR_OK) {
            return $nearest_task;
        }
        $nearest_task = $nearest_task[Const_DataAccess::MREK_DATA];
        $ap_info = $ap_manager->get_current_ap();
        if (!empty($nearest_task)) {
            if (
                isset($nearest_task['status'])
                && $nearest_task['status'] == self::TASK_STATUS_RUNNING
            ) {
                $msg = self::_pre_task_running_mail_msg($ap_info, $nearest_task);
                $people = Module_ControlCentre_ApManager::interface_people(false);
                empty($people)
                    || Da\Sys_App::run_in_mode(
                        ['Module_Notification_Mail', 'send_mail'],
                        [
                            implode(';', $people),
                            '接入点['.$ap_id.']任务运行失败',
                            $msg
                        ]
                    );
                return Lib_Helper::get_err_struct(
                    Const_Err_DataAccess::ERR_TASK_STATUS,
                    'pre-task is running!'
                );
            }
        }
        // get flow info
        $flow_id = $ap_info[Module_FlowManager_Main::KEY_FLOW_ID];
        $flow_manager = Module_ControlCentre_FlowManager::get_instance()->set_current_flow($flow_id);
        if (empty($flow_manager)) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_FLOW_NOT_EXISTS,
                '流程['.$flow_id.']不存在'
            );
        }
        $flow_options = [];
        if (isset($options[Const_DataAccess::RUN_OPTION_FLOW_INFO])) {
            $flow_options = $options[Const_DataAccess::RUN_OPTION_FLOW_INFO];
            if (isset($flow_options[Module_FlowManager_Main::RUN_MODE])) {
                $run_mode = $flow_options[Module_FlowManager_Main::RUN_MODE];
                $flow_manager->set_run_mode($run_mode);
            }
            if (isset($flow_options[Module_FlowManager_Main::RUN_OPTION_CUSTOM_FLOW])) {
                $custom_flow = $flow_options[Module_FlowManager_Main::RUN_OPTION_CUSTOM_FLOW];
                $flow_manager->custom_flow($custom_flow);
            }
        }
        empty($flow_options) || $flow_manager->custom_options($flow_options);
        // end process current flow
        $options[Const_DataAccess::RUN_OPTION_FLOW_ID] = intval($flow_id);
        //$options[Const_DataAccess::RUN_OPTION_ACCESS_POINT_INFO] = $ap_info;
        isset($ap_info[Module_AccessPoint_Main::FIELD_SOURCE_ID])
            && $options[Const_DataAccess::RUN_OPTION_SRC_ID] = $ap_info[Module_AccessPoint_Main::FIELD_SOURCE_ID];
        self::get_instance()->create_task($task_id);
        Module_ControlCentre_Counter::enable();
        self::_add_task_record_hook($options);
        self::_add_module_prepare_to_run_hook();
        self::_add_module_record_hook();
        self::_add_flow_stat_hook();
        self::_add_module_ratio_hook();
        Da\Sys_App::run_in_mode(function(){self::_add_failed_mail_hook();});
        return self::get_instance()->run_current_task($data, $options);
    }

    /**
     * @param array $data
     * @param array $options
     * @return array
     */
    function run_current_task($data = [], $options = [])
    {
        // cheack ap status
        $ap_status = Module_ControlCentre_ApManager::get_instance()->get_current_ap_status();
        if ($ap_status == null) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_AP_STATUS,
                'access point without status'
            );
        }
        if ($ap_status != Module_AccessPoint_Main::AP_STATUS_ONLINE) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_AP_STATUS,
                'access point is disabled'
            );
        }
        // run task
        $task = $this->get_current_task();
        if (empty($task)) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_TASK_NOT_EXISTS, 'no task to run!'
            );
        }
        try {
            $result = $task->run($data, $options);
        } catch (Exception $ex) {
            return Lib_Helper::get_err_struct($ex->getCode(), $ex->getMessage());
        }
        return $result;
    }
    /**
     * @return array (instance of Module_ControlCentre_Task) current task
     */
    static function current_task()
    {
        return self::get_instance()->get_current_task();
    }
    /**
     * @return int current task id
     */
    static function current_task_id()
    {
        return self::current_task()->id;
    }
    /**
     * create a new task
     * @param int task_id, if null, task will create a new one
     * @return $this
     */
    function create_task($task_id = null)
    {
        $this->_current_task = new Module_ControlCentre_Task($task_id);
        return $this;
    }
    /**
     * @return array
     */
    function get_current_task()
    {
        return $this->_current_task;
    }

    /**
     * @param int $task_id
     * @return array|mixed
     */
    static function kill_task($task_id)
    {
        $model_task = new Model_Task();
        $task = $model_task->get_one_by_id($task_id);
        if ($task['errno'] !== Const_Err_Base::ERR_OK) {
            return $task;
        }
        $task = $task['data'];
        if (Da\Sys_App::app()->get_run_mode() == DA_RUN_MODE_PRO) {
            if (isset($task['ap_info']) && isset($task['ap_info']['ap_id'])) {
                $ap_id = $task['ap_info']['ap_id'];
                $stop = Module_ScheduledTask_Main::stop_ap_task($ap_id);
            }
        }
        if (!isset($task['task_pid']) || $task['task_pid'] == 0 || $task['task_pid'] == -1) {
            return ['errno' => Const_Err_DataAccess::ERR_TASK_STOP, 'data' => '没有对应的进程'];
        }
        $pid = $task['task_pid'];
        $return = posix_kill($pid, SIGKILL);
        if ($return) {
            $model_task = new Model_Task();
            $upinfo = ['status' => self::TASK_STATUS_SHUTDOWN];
            $task_model_update = $model_task->update($task_id, $upinfo);
            return Lib_Helper::get_return_struct('已停止任务:'.'['.$task_id.']');
        }
        return ['errno' => Const_Err_DataAccess::ERR_TASK_STOP, 'data' => '停止任务失败!'];
    }

    /**
     * @return array
     */
    static function register_router()
    {
        return [
            'task/list' => [
                'Module_ControlCentre_Action',
                'task_list_action',
                'GET',
                [
                    Const_DataAccess::URL_NAV => true,
                    Const_DataAccess::URL_WEIGHT => 296,
                    Const_DataAccess::URL_NAME => '所有任务',
                    Const_DataAccess::URL_CATALOG => self::URL_CATALOG_TASK,
                ],
            ],
            'task/module/list' => [
                'Module_ControlCentre_Action',
                'task_module_list_action'
            ],
            'task/run' => [
                'Module_ControlCentre_Action',
                'run_task_action',
                'POST',
                [
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_TASK_ADMIN,
                    Const_DataAccess::URL_RENDER_TYPE => Module_View_Main::RENDER_TYPE_API,
                ],
            ],
            'task/stop' => [
                'Module_ControlCentre_Action',
                'stop_task_action',
                'POST',
                [
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_TASK_ADMIN,
                    Const_DataAccess::URL_RENDER_TYPE => Module_View_Main::RENDER_TYPE_API,
                ],
            ],
            'task/custom_run' => [
                'Module_ControlCentre_Action',
                'run_custom_task_action',
                'POST',
                [
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_TASK_ADMIN,
                    Const_DataAccess::URL_RENDER_TYPE => Module_View_Main::RENDER_TYPE_API,
                ],
            ],
            'task/status' => [
                'Module_ControlCentre_Action',
                'get_task_status_action'
            ],
            'task/status/modify' => [
                'Module_ControlCentre_Action',
                'set_task_status_action',
                'GET',
                [
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_TASK_ADMIN,
                    Const_DataAccess::URL_RENDER_TYPE => Module_View_Main::RENDER_TYPE_API,
                ],
            ],
        ];
    }

    /**
     * @param int $ap_id
     * @param int $mode
     * @param array $options
     * @param string $php_script
     * @return string
     */
    static function get_run_ap_script(
        $ap_id, 
        $mode = Module_FlowManager_Main::RUN_MODE_CALLBACK,
        $options = ['meta'=>true],
        $php_script = 'tools/access_point.php'
    )
    {
        $ap_id = escapeshellcmd($ap_id);
        $php = Da\Sys_Config::config('env/php');
        $script = '';
        $script .= $php['bin'].' ';
        $script .= Da\Sys_App::script_path($php_script);
        $script .= ' -a'.$ap_id;
        isset($options['task_id'])
            && !empty($options['task_id'])
            && $script .= ' -t'.escapeshellcmd($options['task_id']);
        switch ($mode) {
            case Module_FlowManager_Main::RUN_MODE_IN_ORDER:
                $script .= ' --order';
                break;
            case Module_FlowManager_Main::RUN_MODE_CALLBACK:
            default:
                $script .= ' --callback';
                break;
        }
        isset($options['mid'])
            && $options['mid'] &&
            $script .= ' -s'.escapeshellcmd($options['mid']);
        isset($options['meta'])
            && $options['meta']
            && $script .= ' --meta ';
        isset($options['continue'])
            && $options['continue']
            && $script .= ' --continue ';
        $script .= ' --run ';
        $output_file = self::get_output_file($ap_id);
        $script .= '> '.$output_file;
        isset($options['foreground'])
            && $options['foreground']
            || $script .= ' &';
        $output = [];
        return $script;
    }

    /**
     * @param int $ap_id
     * @return string
     */
    static function get_output_file($ap_id)
    {
        $time = time();
        $output_file = Da\Sys_App::log_path('output/'.$ap_id.'/'.date("Ymd", $time).'/'.date("His", $time).'.out ');
        if (!file_exists(dirname($output_file))) {
            mkdir(dirname($output_file), 0744, true);
        }
        return $output_file;
    }

    /**
     * @param int $ap_id
     * @param int $mode
     * @param array $options
     * @param string $php_script
     * @return array
     */
    static function exec_task(
        $ap_id,
        $mode = Module_FlowManager_Main::RUN_MODE_CALLBACK,
        $options = ['meta'=>true],
        $php_script = 'tools/access_point.php'
    )
    {
        if (empty($ap_id)) {
            return Lib_Helper::get_err_struct(Const_Err_DataAccess::ERR_ID_NOT_SET, '没有获取到接入点ID');
        }
        if (php_sapi_name() != 'cli') {
            Lib_Log::info("Run task, user [%s], Ap id:[%d]", [Module_Account_User::get_current_user(), $ap_id]);
        }
        if (
            Da\Sys_App::run_mode() == DA_RUN_MODE_PRO
            && $mode == Module_FlowManager_Main::RUN_MODE_CALLBACK
            && !isset($options['without_ct'])
        ) {
            return Module_ScheduledTask_Main::run_ap_task($ap_id);
        } else {
            self::exec_task_by_cli($ap_id, $mode, $options, $php_script);
        }
        return Lib_Helper::get_return_struct(['msg' => '运行成功']);
    }

    /**
     * @param int $ap_id
     * @param int $mode
     * @param array $options
     * @param string $php_script
     * @return array
     */
    static function exec_task_by_cli(
        $ap_id,
        $mode = Module_FlowManager_Main::RUN_MODE_CALLBACK,
        $options = ['meta'=>true],
        $php_script = 'tools/access_point.php'
    )
    {
        $script = self::get_run_ap_script($ap_id, $mode, $options, $php_script);
        $result = 0;
        $output = null;
        exec($script, $output, $result);
        if ($result != 0) {
            Lib_Log::notice("run ap script failed. case: %s", $output);
            return Lib_Helper::get_err_struct($result, '执行失败');
        }
        return Lib_Helper::get_return_struct('运行成功');
    }

    /**
     * @param int $status
     * @return bool
     */
    static function is_task_running($status)
    {
        if (
            $status == self::TASK_STATUS_CREATE
            || $status == self::TASK_STATUS_RUNNING
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param array $options
     * @return array
     */
    private static function _add_task_record_hook($options)
    {
        $current_task = self::current_task();
        // record
        $model_task = new Model_Task();
        $task_options = $options;
        $task_options['task_pid'] = posix_getpid();
        $task_options['create_time'] = $current_task->create_time;
        $before_record = function($data) use ($current_task, $task_options, $model_task) {
            $create = $model_task->create($current_task->id, $task_options, self::TASK_STATUS_RUNNING);
            return $create;
        };
        $after_record = function($result) use ($current_task, $model_task) {
            $status = self::TASK_STATUS_FINISH;
            if (!isset($result['errno'])) {
                $status = self::TASK_STATUS_FAILED;
            } elseif ($result['errno'] !== Const_Err_Base::ERR_OK) {
                if (isset($result[Const_DataAccess::MREK_STATUS])
                    && isset(self::$_module_task_stat_map[$result[Const_DataAccess::MREK_STATUS]])) {
                    $status = self::$_module_task_stat_map[$result[Const_DataAccess::MREK_STATUS]];
                } else {
                    $status = self::TASK_STATUS_FAILED;
                }
            }
            $upinfo = [
                'result' => $result,
                'status' => $status,
                'end_time' => time(),
            ];
            $all_counts = Module_ControlCentre_Counter::get_all_counts();
            if ($all_counts[Const_DataAccess::MREK_ERRNO] == Const_Err_Base::ERR_OK) {
                $counts = $all_counts[Const_DataAccess::MREK_DATA];
                foreach ($counts as $_k => $_c) {
                    if ($_c == 0) {
                        unset($counts[$_k]);
                    }
                }
                $upinfo['counts'] = $counts;
            }
            $upinfo['server'] = Module_ScheduledTask_Main::current_server_name();
            return $model_task->update($current_task->id, $upinfo);
        };
        $current_task->register_before_run_hook('record_create_task', $before_record);
        $current_task->register_after_run_hook('record_finish_task', $after_record);
    }

    private static function _add_module_record_hook()
    {
        $current_flow = Module_ControlCentre_FlowManager::current_flow();
        $current_flow->register_before_run_module_hook(
            null,
            'record_bmdata',
            ['Module_ModuleManager_CommonHooks', 'record_run_module_begin_hook'],
            false
        );
        $current_flow->register_after_run_module_hook(
            null,
            'record_fmdata',
            ['Module_ModuleManager_CommonHooks', 'record_run_module_finish_hook'],
            false
        );
        $current_flow->register_after_run_section_hook(
            'record_sdata',
            ['Module_ModuleManager_CommonHooks', 'record_run_section_finish_hook'],
            false
        );
    }

    private static function _add_flow_stat_hook()
    {
        $current_flow = Module_ControlCentre_FlowManager::current_flow();
        $current_flow->register_before_run_section_hook(
            'record-c-mid',
            ['Module_ModuleManager_CommonHooks', 'set_current_mid_hook'],
            false
        );
        $current_flow->register_after_run_flow_hook(
            'flow-finish-flag',
            ['Module_ModuleManager_CommonHooks', 'set_current_task_finish_flag']
        );
    }

    private static function _add_module_ratio_hook()
    {
        $current_flow = Module_ControlCentre_FlowManager::current_flow();
        $current_flow->register_before_run_flow_hook(
            'begin-ratio',
            ['Module_ModuleManager_CommonHooks', 'up_ratio_begin_hook']
        );
        $current_flow->register_after_run_flow_hook(
            'begin-ratio',
            ['Module_ModuleManager_CommonHooks', 'release_up_ratio_hook']
        );
    }

    private static function _add_module_prepare_to_run_hook()
    {
        Module_ControlCentre_FlowManager::current_flow()
            ->register_before_run_flow_hook(
                'mrun-prepare',
                ['Module_ModuleManager_CommonHooks', 'modules_prepare_to_run_hook']
            );
    }

    private static function _add_failed_mail_hook()
    {
        Module_ControlCentre_FlowManager::current_flow()
            ->register_after_run_flow_hook(
                'fmail',
                ['Module_ModuleManager_CommonHooks', 'failed_send_mail_hook']
            );
    }

    /**
     * @param array $ap_info
     * @param array $nearest_task
     * @return string
     */
    private static function _pre_task_running_mail_msg($ap_info, $nearest_task)
    {
        $msg = '';
        isset($nearest_task['create_time'])
            && $msg .= '起始于<span style="color:red;">['
            . date("Y-m-d H:i:s", $nearest_task['create_time'])
            .']</span>的';
        $msg .= '接入点('
            .$ap_info[Module_AccessPoint_Main::FIELD_AP_ID]
            .')';
        isset($ap_info['ap_name'])
            && (
                isset($nearest_task['_id'])
                ? (
                    $msg .= '<a href="'
                    .Module_HttpRequest_Router::site_url('control_centre/task/list?task_id=')
                    .$nearest_task['_id']
                    .'" style="font-size:2em;">['.$ap_info['ap_name'].']</a>'
                    .'(任务ID：'.$nearest_task['_id'].')'
                )
                : $msg .= '['.$ap_info['ap_name'].']'
            );
        $msg .= '仍在运行，后续任务暂不启动';
        return $msg;
    }
}
