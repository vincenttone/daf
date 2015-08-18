<?php
class Module_FlowManager_Action
{
    const KEY_ACTION = 'action';
    const ACTION_CREATE = 1;
    const ACTION_UPDATE = 2;

    public static function list_action()
    {
        $flows = Module_FlowManager_Main::all_flow();
        if($flows['errno'] !== Const_Err_Base::ERR_OK) {
            throw new Exception('获取流程失败', Const_Err_DataAccess::ERR_FLOW_NOT_EXISTS);
        }
        $flow_list = array();
        foreach($flows['data'] as $flow) {
            $flow_list[$flow['flow_id']]['flow_id'] = $flow['flow_id'];
            $flow_list[$flow['flow_id']]['flow_name'] = $flow['name'];
        }
        Module_Page_Main::render('flow_manager/flow_list', ['flow_list' => $flow_list]);
    }

    public static function add_show_action()
    {
        $data = self::_get_form_data();
        $data[self::KEY_ACTION] = self::ACTION_CREATE;
        Module_Page_Main::render('flow_manager/flow_gen', $data);
    }

    public static function add_action()
    {
        $flow = new Module_FlowManager_Flow();
        $data = self::_get_flow_data('/flow_manager/flow/new');
        $result = $flow->add($data);
        if($result['errno'] !== Const_Err_Base::ERR_OK) {
            throw new Exception('流程添加失败', Const_Err_Db::ERR_SAVE_DATA_FAIL);
        }
        $diff = Module_OperationRecord_Main::get_diff([], $data);
        if(Const_Err_Base::ERR_OK !== $diff['errno']) {
            $msg = isset($diff['data']['msg'])? $diff['data']['msg']:'未知错误!';
            Lib_Request::flash('warn', $msg);
            Module_HttpRequest_Router::redirect_to('/flow_manager/flow/new');
        }
        $diff = $diff['data'];
        Module_OperationRecord_Main::add_record(Module_OperationRecord_Main::OPCODE_FLOW_ADD, $diff);
        Lib_Request::flash('success', '流程添加成功!');
        Module_HttpRequest_Router::redirect_to('/flow_manager/flow/list');
    }

    public static function edit_action()
    {
        $flow_id = Lib_Request::get_int('flow_id');
        if(empty($flow_id)) {
            Lib_Request::flash('error', '流程ID为空!');
            Module_HttpRequest_Router::redirect_to('/flow_manager/flow/list');
        }
        $data = self::_get_form_data();
        $data['flow_id'] = $flow_id;
        $flow_model = new Model_FlowInfo();
        $flows = $flow_model->get_flow_info_by_id($flow_id);
        if($flows['errno'] === Const_Err_Base::ERR_OK) {
            $data['flow'] = $flows['data'];
        }
        $data[self::KEY_ACTION] = self::ACTION_UPDATE;
        Module_Page_Main::render('flow_manager/flow_gen', $data);
    }

    public static function update_action()
    {
        $flow_id = Lib_Request::post_var('flow_id');
        $flow_id = intval($flow_id);
        if(empty($flow_id)) {
            Lib_Request::flash('error', '流程ID为空!');
            Module_HttpRequest_Router::redirect_to('/flow_manager/flow/list');
        }
        $flow = new Module_FlowManager_Flow();
        $url = '/flow_manager/flow/edit?flow_id='.$flow_id;
        $data = self::_get_flow_data($url, $flow_id);
        $model_flow = new Model_FlowInfo();
        $old_flow = $model_flow->get_flow_info_by_id($flow_id);
        if($old_flow['errno'] !== Const_Err_Base::ERR_OK) {
            $old_flow['data'] = [];
        }
        $old_flow = $old_flow['data'];
        $ignore_fields = ['flow_id', 'create_time', 'update_time'];
        $diff = Module_OperationRecord_Main::get_diff($old_flow, $data, $ignore_fields);
        if(Const_Err_Base::ERR_OK !== $diff['errno']) {
            $msg = isset($diff['data']['msg'])? $diff['data']['msg']:'未知错误!';
            Lib_Request::flash('warn', $msg);
            Module_HttpRequest_Router::redirect_to($url);
        }
        $diff = $diff['data'];
        $result = $flow->update($flow_id, $data);
        if($result['errno'] !== Const_Err_Base::ERR_OK) {
            throw new Exception('流程修改失败', Const_Err_Db::ERR_SAVE_DATA_FAIL);
            Lib_Request::flash('error', '流程修改失败!');
            Module_HttpRequest_Router::redirect_to($url);
        }
        Module_OperationRecord_Main::add_record(Module_OperationRecord_Main::OPCODE_FLOW_EDIT, $diff, $flow_id);
        Lib_Request::flash('success', '流程修改成功!');
        Module_HttpRequest_Router::redirect_to('/flow_manager/flow/list');
    }

    /**
     * @param string $url
     * @param int $id
     * @return array
     */
    private static function _get_flow_data($url, $id = null)
    {
        $data = [];
        $name = Lib_Request::post_var('name');
        $main = Lib_Request::post_var('main');
        $stuff = Lib_Request::post_var('stuff');
        $config = Lib_Request::post_var('config');

        $name = trim($name);
        $main = trim($main);
        $stuff = trim($stuff);
        $config = trim($config);

        $flow_model = new Model_FlowInfo();
        $flash_error = function ($msg, $url) {
            Lib_Request::flash('error', $msg);
            Module_HttpRequest_Router::redirect_to($url);
        };
        if(empty($name)) {
            $flash_error('流程名称不能为空', $url);
        }
        if(!empty($id)) {
            $cond = ['name' => $name, '_id'=>['$ne'=>intval($id)]];
        } else {
            $cond = ['name' => $name];
        }
        $flow_info = $flow_model->get_flow_by_cond($cond);
        if($flow_info['errno'] == Const_Err_Base::ERR_OK && !empty($flow_info['data'])) {
            $flash_error('流程名称存在', $url);
        }
        if(empty($main)) {
            $flash_error('主流程不能为空', $url);
        }

        $mains = self::_get_array_by_str($main);
        $stuffs = self::_get_array_by_str($stuff);
        $configs = self::_get_array_by_str($config);

        $data = [
            'name' => $name,
            'flow' => [
                Module_FlowManager_Main::FLOW_TYPE_MAIN => $mains,
                Module_FlowManager_Main::FLOW_TYPE_STUFF => $stuffs,
                Module_FlowManager_Main::FLOW_TYPE_CONFIG => $configs,
            ],
        ];
        return $data;
    }

    /**
     * @return array
     */
    private static function _get_form_data()
    {
        $data = [];
        $functional_modules = [];
        $configure_modules = [];
        $modules = Module_ModuleManager_Register::get_instance()->get_registered_modules();
        if(is_array($modules)) {
            foreach($modules as $k=>$v) {
                if($v['type'] == 'functional') {
                    $functional_modules[$k]['module_id'] = $v['module_id'];
                    $functional_modules[$k]['name'] = $v['name'];
                }
                if(
                    $v['type'] == 'configure'
                    && isset($v['register'])
                    && in_array('dataflow', $v['register'])
                ) {
                    $configure_modules[$k]['module_id'] = $v['module_id'];
                        $configure_modules[$k]['name'] = $v['name'];
                }
            }
        }
        $data['functional_modules'] = $functional_modules;
        $data['configure_modules'] = $configure_modules;
        return $data;
    }

    /**
     * @param string $str
     * @return array
     */
    private static function _get_array_by_str($str)
    {
        $result = [];
        $str_array = explode('|', $str);
        foreach ($str_array as $_a) {
            $array = explode(':', $_a);
            if (empty($array[0])) {
                continue;
            }
            if (isset($array[1])) {
                $result[$array[0]] = explode(',', $array[1]);
            } else {
                $result = array_merge($result, explode(',', $array[0]));
            }
        }
        return $result;
    }
}
