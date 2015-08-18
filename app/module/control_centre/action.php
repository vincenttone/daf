<?php
class Module_ControlCentre_Action
{
    static function task_list_action()
    {
        $task_id = Lib_Request::get_int('task_id');
        $ap_name = Lib_Request::get_var('ap_name');
        $ap_name = trim($ap_name);
        $src_id = Lib_Request::get_var('sub_src');
        $src_id = trim($src_id);
        $status = Lib_Request::get_int('status');
        $time_begin = Lib_Request::get_var('time_begin');
        $time_begin = trim($time_begin);
        $time_end = Lib_Request::get_var('time_end');
        $time_end = trim($time_end);
        $cond = [];
        $search_vars = [];
        $ap_cond = [];
        if(!empty($task_id)) {
            $cond = ['_id' => $task_id];
            $search_vars = ['task_id' => $task_id];
        } else {
            if(strpos($src_id, ',')) {
                $src_ids = explode(',', $src_id);
                $int_src_ids = [];
                foreach($src_ids as $k=>$v) {
                    $int_src_ids[] = intval($v);
                }
                $cond['src_id'] = ['$in' => array_merge($src_ids,$int_src_ids)];
            }
            elseif(!empty($src_id)) {
                $cond['src_id'] = ['$in' => [$src_id,intval($src_id)]];
            }
            if(!empty($status)) {
                $cond['status'] = $status;
                $search_vars['status'] = $status;
            }
            if(!empty($time_begin) || !empty($time_end)) {
                $time_cond = [];
                if(!empty($time_begin)) {
                    $time_begin = strtotime($time_begin);
                    $time_cond['$gt'] = $time_begin;
                }
                if(!empty($time_end)) {
                    $time_end = strtotime($time_end);
                    $time_cond['$lte'] = $time_end;
                }
                if($time_begin >= $time_end) {
                    unset($time_cond['$lte']);
                }
                if(!empty($time_cond)) {
                    $cond['create_time'] = $time_cond;
                }
            }
        }
        if (!Module_Account_User::has_perms([Module_Account_Perm::PERM_AP_ADMIN])) {
            $ap_cond['interface_people'] = new MongoRegex("/".Module_Account_User::get_current_user()."/");
        }
        if(!empty($ap_name)) {
            $ap_cond = ['ap_name' => new MongoRegex("/".$ap_name."/")];
        }
        if(!empty($ap_cond)) {
            $search_vars['ap_name'] = $ap_name;
            $ap_ids = [];
            $int_ap_ids = [];
            $model = new Model_AccessPoint();
            $ap_infos = $model->get_all($ap_cond);
            if($ap_infos['errno'] == Const_Err_Base::ERR_OK) {
                foreach($ap_infos['data'] as $k => $ap)
                {
                    $ap_ids[] = $ap['ap_id'];
                    $int_ap_ids[] = intval($ap['ap_id']);
                }
            }
            $cond['ap_id'] = ['$in' => array_merge($ap_ids, $int_ap_ids)];
        }
        $model_task = new Model_Task();
        $total_num = $model_task->get_tasks_count($cond);
        $current_page = Lib_Request::get_int('page');//当前页码，必须
        $total_size = isset($total_num['data'])? intval($total_num['data']):0;//总记录数，必须
        $page_size = 15;//每页条数，必须
        $skip = ($current_page-1)*$page_size;//跳过记录，必须
        $skip = $skip<0? 0:$skip;
        $mode = 2;//页码模式，决定页码个数，默认1
        $tasks = $model_task->get_all($cond, $page_size, $skip);
        if ($tasks['errno'] !== Const_Err_Base::ERR_OK) {
            Lib_Log::error(Lib_Helper::format_err_struct($tasks));
            $tasks = [];
            Lib_Request::flash('未获取到任务信息');
        }
        $tasks = $tasks['data'];
        $ap_infos = [];
        $ap_model = new Model_AccessPoint();
        foreach ($tasks as $_k => $_t) {
            $ap_info = $ap_model->get_ap_by_id($_t['ap_id']);
            $ap_info['ap_id'] = $_t['ap_id'];
            if ($ap_info['errno'] !== Const_Err_Base::ERR_OK) {
                Lib_Log::error(Lib_Helper::format_err_struct($ap_info));
                Lib_Request::flash('获取接入点信息失败');
                Module_HttpRequest_Router::redirect_to('/');
            }
            $ap_info = $ap_info[Const_DataAccess::MREK_DATA];
            $ap_infos[$_t['ap_id']] = $ap_info;
        }

        $source_model = new Model_Source();
        $all_sources = $source_model ->get_all();
        $sources = $all_sources[Const_DataAccess::MREK_DATA];
        $all_src_type = [];
        foreach($all_sources['data'] as $k=>$v)
        {
            if(!in_array($v['src_type'], $all_src_type))
            {
                $all_src_type[] = $v['src_type'];
            }
        }
        asort($all_src_type);
        $task_status_list = Module_ControlCentre_Main::$task_status_list;
        $pages = Module_View_Template::get_pages_html($current_page,$total_size,$page_size,$mode);
        Module_Page_Main::render(
            'control_centre/task_list',
            [
                'ap_info' => $ap_infos,
                'tasks'=>$tasks,
                'sources' => $sources,
                'pages' => $pages,
                'all_src_type' => $all_src_type,
                'status_list' => $task_status_list,
                'search_vars' => $search_vars
            ]
        );
    }

    static function task_module_list_action()
    {
        $task_id = Lib_Request::get_int('task_id');
        $page = Lib_Request::get_int('page');
        $page = $page < 1? 1:$page;
        $page_size = 20;
        $offset = intval(($page-1)*$page_size);
        if (empty($task_id) && $task_id != 0) {
            Module_View_Main::view()->output(
                [
                    'errno' => Const_Err_DataAccess::ERR_GET_PARAM,
                    'data' => '无此任务',
                ]
            );
        }
        $task_module_record = new Model_TaskRunModuleRecord();
        $records = $task_module_record->get_records_by_task_id($task_id, $page_size, $offset);
        if (!isset($records['errno']) && $records['errno'] !== Const_Err_Base::ERR_OK) {
            Module_View_Main::view()->output(
                [
                    'errno' => Const_Err_DataAccess::ERR_TASK_NOT_EXISTS,
                    'data' => '获取任务失败',
                ]
            );
        }
        $task_infos = $records['data'];
        $modules = Module_ModuleManager_Register::get_instance()->get_registered_modules();
        $status = Module_ModuleManager_Main::$run_module_status_list;
        $task_info = [];
        foreach($task_infos as $_k=>$_v)
        {
            $_v['start_time']= isset($_v['start_time'])? date('Y-m-d H:i:s', $_v['start_time']):'';
            $_v['end_time'] = isset($_v['end_time'])? date('Y-m-d H:i:s', $_v['end_time']):'';
            $_v['module'] = isset($modules[$_v['module_id']])
                ? $modules[$_v['module_id']]['name']
                : 'UNKNOW';
            $_v['module_brief'] = isset($modules[$_v['module_id']])
                ? $modules[$_v['module_id']]['brief']
                : '暂无信息';
            $_v['digit_status'] = $_v['status'];
            $_v['status'] = $status[$_v['status']];
            $task_info[] = $_v;
        }
        Module_View_Main::view()->output($task_info);
    }

    static function run_task_action()
    {
        // ap id for run
        $ap_id = Lib_Request::post_int_var('ap_id');
        if (empty($ap_id) && $ap_id != 0) {
            Module_View_Main::view()->output(
                [
                    'errno' => Const_Err_DataAccess::ERR_GET_PARAM,
                    'data' => '无此接入点',
                ]
            );
        }
        $options = Lib_Request::post_array_vars();
        // run mode select
        $mode = Module_FlowManager_Main::RUN_MODE_CALLBACK;
        isset($options['mode']) && $mode = $options['mode'];
        // run single module or not
        $mid = null;
        isset($options['mid']) && $mid = $options['mid'];
        // run task
        $result = Module_ControlCentre_Main::exec_task($ap_id, $mode, ['meta'=>true]);
        Module_View_Main::view()->output($result);
    }

    static function run_custom_task_action()
    {
        // ap id for run
        $ap_id = Lib_Request::post_int_var('ap_id');
        if (empty($ap_id) && $ap_id != 0) {
            Module_View_Main::view()->output(
                [
                    'errno' => Const_Err_DataAccess::ERR_GET_PARAM,
                    'data' => '无此接入点',
                ]
            );
        }
        $options = Lib_Request::post_array_vars();
        $options['without_ct'] = true; // just run at cmd mode, not use ct to start task
        $mode = Module_FlowManager_Main::RUN_MODE_CALLBACK;
        isset($options['mode']) && $mode = $options['mode'];
        $result = Da\Sys_App::run_mode() == DA_RUN_MODE_PRO
            ? Module_ControlCentre_Main::exec_task($ap_id, $mode, $options, 'tools/run_ap.php')
            : Module_ControlCentre_Main::exec_task($ap_id, $mode, $options);

        $diff = Module_OperationRecord_Main::get_diff([], $options);
        if(Const_Err_Base::ERR_OK !== $diff['errno']) {
            $diff = [];
        }
        $diff = $diff['data'];
        Module_OperationRecord_Main::add_record(Module_OperationRecord_Main::OPCODE_TASK_RUN, $diff, $ap_id);
        Module_View_Main::view()->output($result);
    }

    static function stop_task_action()
    {
        $task_id = Lib_Request::post_int_var('task_id');
        if (empty($task_id) && $task_id != 0) {
            Module_View_Main::view()->output(
                [
                    'errno' => Const_Err_DataAccess::ERR_GET_PARAM,
                    'data' => '无此任务',
                ]
            );
        }
        Module_OperationRecord_Main::add_record(Module_OperationRecord_Main::OPCODE_TASK_RUN_STOP);
        $result = Module_ControlCentre_Main::kill_task($task_id);
        Module_View_Main::view()->output($result);
    }

    static function set_task_status_action()
    {
        $task_id = Lib_Request::get_int('task_id');
        $status = Lib_Request::get_int('status');
        $current_status = Lib_Request::get_int('current_status');
        $task_status_list = Module_ControlCentre_Main::$task_status_list;
        $rtn = [];
        if(isset($task_status_list[$status])) {
            $model_task = new Model_Task();
            $rs = $model_task->update($task_id, ['status'=>$status]);
            if($rs['errno'] === Const_Err_Base::ERR_OK && $rs['data'] === 1) {
                $rtn = ['errno'=>Const_Err_Base::ERR_OK];
            } else {
                $rtn = ['errno'=>Const_Err_Db::ERR_UPDATE_FAIL, 'msg'=>'修改失败'];
            }
        } else {
            $rtn = ['errno'=>Const_Err_Base::ERR_INVALID_PARAM, 'msg'=>'不存在此状态值'];
        }

        $diff = Module_OperationRecord_Main::get_diff(['status' => $current_status], ['status' => $status]);
        if(Const_Err_Base::ERR_OK === $diff['errno']) {
            $diff = $diff['data'];
            Module_OperationRecord_Main::add_record(Module_OperationRecord_Main::OPCODE_TASK_STATUS_EDIT, $diff, $task_id);
        }
        Module_View_Main::view()->output($rtn);
    }

    static function get_task_status_action()
    {
        $task_id = Lib_Request::get_int('task_id');
        $rtn = self::_get_task_status($task_id);
        Module_View_Main::view()->output($rtn);
    }

    /**
     * @param int $task_id
     * @return array
     */
    static private function _get_task_status($task_id)
    {
        $rtn = [];
        if($task_id > 0) {
            $model_task = new Model_Task();
            $tasks = $model_task->get_one_by_id($task_id);
            if ($tasks['errno'] !== Const_Err_Base::ERR_OK) {
                $rtn['msg'] = '任务信息获取失败!';
            } else {
                if(isset($tasks['data']['status'])) {
                    $rtn['status'] = $tasks['data']['status'];
                } else {
                    $rtn['msg'] = '未获取到任务状态!';
                }
            }
        } else {
            $rtn['msg'] = '任务ID错误!';
        }
        return $rtn;
    }
}
