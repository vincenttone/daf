<?php
class Module_ModuleManager_CommonHooks
{
    /**
     * @param int $task_id
     * @param int $mid
     * @param int $mtype
     */
    static function record_run_module_begin_hook($task_id, $mid, $mtype)
    {
        Lib_Log::debug(
            "%s begin module hook, task_id: %d, mid: %d, mtype %d",
            [__METHOD__, $task_id, $mid, $mtype]
        );
        $record_id = Module_ModuleManager_Main::get_instance()
            ->get_module_run_id($task_id, $mid);
        $model_module_record = new Model_TaskRunModuleRecord();
        $record = [
            'task_id' => intval($task_id),
            'module_id' => intval($mid),
            'order' => Lib_Counter::inc(),
            'status' => Module_ModuleManager_Main::RUN_MODULE_STATUS_RUNNING,
            'start_time' => time(),
        ];
        $mtype == Module_FlowManager_Main::FLOW_MODULE_TYPE_STUFF
            && $record['status'] = Module_ModuleManager_Main::RUN_MODULE_STATUS_CALLING;
        $model_module_record->save_record($record_id, $record);
    }

    /**
     * @param int $task_id
     * @param int $mid
     * @param int $mtype
     * @param array $data
     */
    static function record_run_module_finish_hook($task_id, $mid, $mtype, &$data)
    {
        Lib_Log::debug(
            "%s finsh module hook, task_id: %d, mid: %d, mtype %d",
            [__METHOD__, $task_id, $mid, $mtype]
        );
        $record_id = Module_ModuleManager_Main::get_instance()
            ->get_module_run_id($task_id, $mid);
        $record = [
            'status' => Module_ModuleManager_Main::RUN_MODULE_STATUS_FINISH,
            'end_time' => time(),
        ];
        if ($mtype == Module_FlowManager_Main::FLOW_MODULE_TYPE_STUFF) {
            $record['status'] = Module_ModuleManager_Main::RUN_MODULE_STATUS_CALLED;
        } elseif (
            !isset($data[Const_DataAccess::MREK_ERRNO])
            || $data[Const_DataAccess::MREK_ERRNO] !== Const_Err_Base::ERR_OK
        ) {
            $record['status'] = isset($data[Const_DataAccess::MREK_STATUS])
                ? $data[Const_DataAccess::MREK_STATUS]
                : Module_ModuleManager_Main::RUN_MODULE_STATUS_FAILED;
        }
        $counts = Module_ControlCentre_Counter::get_all_counts();
        if ($counts[Const_DataAccess::MREK_ERRNO] == Const_Err_Base::ERR_OK) {
            $counts = $counts[Const_DataAccess::MREK_DATA];
            $stat = Module_ControlCentre_Counter::formated_counts($mid, $counts);
            empty($stat) ||  $record['stat'] = $stat;
            $model_module_record = new Model_TaskRunModuleRecord();
            $model_module_record->update_record($record_id, $record);
        } else {
            Lib_Log::warn("%s return count not ok, %s", [__METHOD__, json_encode($count)]);
        }
    }

    /**
     * @param int $task_id
     * @param int $mid
     * @param array $data
     */
    static function record_run_section_finish_hook($task_id, $mid, &$data)
    {
        Lib_Log::debug(
            "%s finish section hook, task_id: %d, mid: %d",
            [__METHOD__, $task_id, $mid]
        );
        $record_id = Module_ModuleManager_Main::get_instance()
            ->get_module_run_id($task_id, $mid);
        $record = ['result' => $data];
        if (
            !isset($data[Const_DataAccess::MREK_ERRNO])
            || $data[Const_DataAccess::MREK_ERRNO] !== Const_Err_Base::ERR_OK
        ) {
            $record['status'] = isset($data[Const_DataAccess::MREK_STATUS])
                ? $data[Const_DataAccess::MREK_STATUS]
                : Module_ModuleManager_Main::RUN_MODULE_STATUS_FAILED;
        }
        $class = Module_ModuleManager_Main::get_instance()
            ->get_module_class($mid);
        if ($class) {
            $result = $data['data'];
            if (method_exists($class, 'format_record_msg')) {
                $stat = call_user_func([$class, 'format_record_msg'], $result);
            }
            empty($stat) || $record['section_stat'] = $stat;
        }
        $model_module_record = new Model_TaskRunModuleRecord();
        $model_module_record->update_record($record_id, $record);
    }

    /**
     * @param int $task_id
     */
    static function up_ratio_begin_hook($task_id)
    {
        Module_ControlCentre_Counter::get_instance()
            ->fork_count_processor([__CLASS__, 'ratio_of_process'], [$task_id]);
    }

    /**
     * @param int $task_id
     * @param array $result
     */
    static function release_up_ratio_hook($task_id, $result)
    {
        Module_ControlCentre_Counter::get_instance()
            ->recycle_count_processor();
    }

    /**
     * @param int $task_id
     */
    static function ratio_of_process($task_id)
    {
        $flow = Module_ControlCentre_FlowManager::current_flow()
            ->flow;
        $main_mids = $flow[Module_FlowManager_Main::FLOW_TYPE_MAIN];
        $task_status_key = self::_current_task_status_id($task_id);
        $task_status = xcache_get($task_status_key);
        $model_module_record = new Model_TaskRunModuleRecord();
        $cmid = null;
        $pre_mid = null;
        $pre_pre_mid = null;
        while (!$task_status) {
            $current_mid_key = self::_current_mid_hook_key($task_id);
            $current_mid = xcache_get($current_mid_key);
            if ($current_mid && in_array($current_mid, $main_mids)) {
                foreach ($main_mids as $_mid) {
                    if ($_mid == $current_mid) {
                        break;
                    }
                    $pre_pre_mid = $pre_mid;
                    $pre_mid = $_mid;
                }
                $ratio = Module_ControlCentre_Counter::get_instance()
                    ->get_ratio_count($current_mid, $pre_mid);
                $record_id = Module_ModuleManager_Main::module_run_id($task_id, $current_mid);
                $ratio && $model_module_record->update_record($record_id, ['ratio' => $ratio]);
                if ($cmid != $current_mid) {
                    $cmid = $current_mid;
                    if ($pre_mid) {
                        $pre_ratio = Module_ControlCentre_Counter::get_instance()
                            ->get_ratio_count($pre_mid, $pre_pre_mid);
                        $pre_record_id = Module_ModuleManager_Main::module_run_id($task_id, $pre_mid);
                        $pre_ratio && $model_module_record->update_record($pre_record_id, ['ratio' => $pre_ratio]);
                    }
                }
            }
            $task_status = xcache_get($task_status_key);
            sleep(3);
        }
    }

    /**
     * @param int $task_id
     * @return string
     */
    private static function _current_mid_hook_key($task_id)
    {
        return 'cmid-'.$task_id;
    }

    /**
     * @param int $task_id
     * @param int $mid
     */
    static function set_current_mid_hook($task_id, $mid)
    {
        $key = self::_current_mid_hook_key($task_id);
        xcache_set($key, $mid);
    }

    /**
     * @param int $task_id
     * @return string
     */
    private static function _current_task_status_id($task_id)
    {
        return 'task-status-'.$task_id;
    }

    /**
     * @param int $task_id
     * @param array $result
     */
    static function set_current_task_finish_flag($task_id, $result)
    {
        $key = self::_current_task_status_id($task_id);
        xcache_set($key, 1);
    }

    /**
     * @param string $method_name
     * @param array $args
     */
    private static function _run_all_modules_static_methods($method_name, $args = [])
    {
        $flow = Module_ControlCentre_FlowManager::current_flow();
        $ordered_mids = $flow->get_in_order_modules();
        // register hooks
        foreach ($ordered_mids as $_mid) {
            $_class = Module_ModuleManager_Register::functional_module_class_name($_mid);
            if ($_class['errno'] != Const_Err_Base::ERR_OK) {
                continue;
            }
            $_class = $_class['data'];
            if (method_exists($_class, $method_name)) {
                call_user_func_array([$_class, $method_name], $args);
            }
        }
    }

    /**
     * @param int $task_id
     */
    static function modules_prepare_to_run_hook($task_id)
    {
        self::_run_all_modules_static_methods('prepare_to_run');
    }

    /**
     * @param int $task_id
     * @param array $data
     */
    static function failed_send_mail_hook($task_id, $data)
    {
        if (
            !isset($data[Const_DataAccess::MREK_ERRNO])
            || $data[Const_DataAccess::MREK_ERRNO] !== Const_Err_Base::ERR_OK
        ) {
            $status = isset($data[Const_DataAccess::MREK_STATUS])
                ? $data[Const_DataAccess::MREK_STATUS]
                : Module_ModuleManager_Main::RUN_MODULE_STATUS_FAILED;
            switch ($status) {
                case Module_ModuleManager_Main::RUN_MODULE_STATUS_FAILED:
                case Module_ModuleManager_Main::RUN_MODULE_STATUS_ABORT:
                case Module_ModuleManager_Main::RUN_MODULE_STATUS_SHUTDOWN:
                    //case Module_ModuleManager_Main::RUN_MODULE_STATUS_TERM:
                    $current_ap = Module_ControlCentre_ApManager::current_ap();
                    $mail_to = Module_ControlCentre_ApManager::interface_people();
                    if (!empty($mail_to)) {
                        $mail_to = implode(';', $mail_to);
                        $title = '接入任务['
                            . $task_id
                            .']';
                        $msg = '接入任务<span style="color:red;">['
                            . $task_id
                            .']</span>';
                        if (isset($current_ap[Module_AccessPoint_Main::FIELD_AP_NAME])) {
                            $title .= $current_ap[Module_AccessPoint_Main::FIELD_AP_NAME];
                            $msg .= '<span style="font-size:2em;">';
                            $msg .= $current_ap[Module_AccessPoint_Main::FIELD_AP_NAME];
                            $msg .= '</span>';
                        }
                        $title .= '<<'
                            .Module_ModuleManager_Main::$run_module_status_list[$status]
                            .'>>';
                        $msg .= '</span><span style="color:red;font-weight:bold;">'
                            .Module_ModuleManager_Main::$run_module_status_list[$status]
                            .'</span>';
                        isset($data[Const_DataAccess::MREK_DATA]['msg'])
                            && $msg .= '<p>原因如下：'
                            .'<h2 style="color:red;">'.$data[Const_DataAccess::MREK_DATA]['msg'].'</h2>'
                            .'</p>';
                        Module_Notification_Mail::send_mail($mail_to, $title, $msg);
                }
                break;
            }
        }

    }

    /***********************
     * !!!  BELOW HOOKS   !!
     * !!! is out of date !!
     ***********************/
    /**
     * @param int $mid
     * @param array $stuff_mids
     * @param array $id_map
     * @return callable
     */
    static function before_run_module_record_hook($mid, $stuff_mids, $id_map)
    {
        if ($stuff_mids === null) {
            $stuff_mids = [];
        }
        if (!isset($id_map[$mid][1])) {
            Lib_Log::warn("%s gen record id and order failed!", __METHOD__);
        }
        return function($task_id, $module_id, $class, $data) use ($mid, $stuff_mids, $id_map) {
            $start_time = time();
            $record_id = $id_map[$mid][0];
            $count = $id_map[$mid][1];
            $model_module_record = new Model_TaskRunModuleRecord();
            // log for record
            Lib_Log::debug(
                "record beofre hook debug info: task id: [%d] module id: [%d], record_id: [%d]",
                [$task_id, $module_id, $record_id]
            );
            // end log
            //$count = Lib_Counter::inc();
            $record = [
                'task_id' => intval($task_id),
                'module_id' => intval($module_id),
                'order' => $count,
                'status' => Module_ModuleManager_Main::RUN_MODULE_STATUS_RUNNING,
                'start_time' => $start_time,
            ];
            $model_module_record->save_record($record_id, $record);
            foreach ($stuff_mids as $_mid) {
                $record_id = $id_map[$_mid][0];
                $count = $id_map[$_mid][1];
                $record = [
                    'task_id' => intval($task_id),
                    'module_id' => intval($_mid),
                    'order' => $count,
                    'status' => Module_ModuleManager_Main::RUN_MODULE_STATUS_CALLING,
                    'start_time' => $start_time,
                ];
                $model_module_record->save_record($record_id, $record);
            }
        };
    }

    /**
     * @param int $mid
     * @param array $stuff_mids
     * @param array $id_map
     * @return callable
     */
    static function after_run_module_record_hook($mid, $stuff_mids, $id_map)
    {
        if ($stuff_mids === null) {
            $stuff_mids = [];
        }
        if (!isset($id_map[$mid][1])) {
            Lib_Log::warn("%s gen record id and order failed!", __METHOD__);
        }
        return function ($task_id, $module_id, $class, $result) use ($mid, $stuff_mids, $id_map) {
            $record_id = $id_map[$mid][0];
            $model_module_record = new Model_TaskRunModuleRecord();
            // log for record
            Lib_Log::debug(
                'record after hook task_id: [%d], module_id: [%d], record_id: [%d]',
                [$task_id, $module_id, $record_id]
            );
            // end log
            $stat = [];
            $end_time = time();
            $counts = Module_ControlCentre_Counter::get_all_counts();
            if (!isset($result['errno']) || $result['errno'] !== Const_Err_Base::ERR_OK) {
                $record = [
                    'status' => Module_ModuleManager_Main::RUN_MODULE_STATUS_FAILED,
                    'result' => $result,
                    'end_time' => $end_time,
                ];
                if (isset($result[Const_DataAccess::MREK_STATUS])) {
                    $record['status'] = $result[Const_DataAccess::MREK_STATUS];
                }
            } else {
                $record = [
                    'status' => Module_ModuleManager_Main::RUN_MODULE_STATUS_FINISH,
                    'end_time' => $end_time,
                ];
                $data = $result['data'];
                if (method_exists($class, 'format_record_msg')) {
                    $stat = call_user_func([$class, 'format_record_msg'], $data);
                }
                if (!empty($stat)) {
                    $record['result'] = [
                        'errno' => Const_Err_Base::ERR_OK,
                        'msg' => $stat,
                    ];
                    $record['stat'] = $stat;
                }
            }
            if ($counts['errno'] != Const_Err_Base::ERR_OK) {
                $stuff_mids = [];
            } else {
                $counts = $counts['data'];
                $_tmp_stat = Module_ControlCentre_Counter::formated_counts($mid, $counts);
                if (is_array($_tmp_stat) && !empty($_tmp_stat)) {
                    $record['stat'] = isset($record['stat'])
                        ? array_merge($_tmp_stat, $record['stat'])
                        : $_tmp_stat;
                }
                unset($_tmp_stat);
            }
            $model_module_record->update_record($record_id, $record);
            foreach ($stuff_mids as $_mid) {
                $stat = [];
                $record_id = $id_map[$_mid][0];
                $_tmp_stat = Module_ControlCentre_Counter::formated_counts($_mid, $counts);
                is_array($_tmp_stat) && $stat = array_merge($_tmp_stat, $stat);
                unset($_tmp_stat);
                $record = [
                    'status' => Module_ModuleManager_Main::RUN_MODULE_STATUS_CALLED,
                    'end_time' => $end_time,
                ];
                if (!empty($stat)) {
                    $record['stat'] = $stat;
                }
                $model_module_record->update_record($record_id, $record);
            }
        };
    }
}