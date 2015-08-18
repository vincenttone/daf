#!/usr/bin/env php
<?php
require_once(dirname(dirname(dirname(__FILE__))).'/app/init.php');
$options = getopt('a:t:m:');
if (!isset($options['a']) || !isset($options['t']) || !isset($options['m'])) {
    p('use as xxx -a ap_id -t task_id -m module_id');
    exit;
}
$ap_id = $options['a'];//'16723523721449231476';
$task_id = $options['t']; // 123
$module_id = $options['m']; //'9691047284819742687'
$control_options = [
    Const_DataAccess::RUN_OPTION_FLOW_INFO => [
        Module_FlowManager_Main::RUN_MODE => Module_FlowManager_Main::RUN_MODE_SINGLE_MODULE, // 单模块模式
        Module_FlowManager_Main::RUN_OPTION_SINGLE_MODULE_ID => $module_id, //跑指定的模块
    ],
];
$ap_info = Module_AccessPoint_Ap::get_ap($ap_id);
if ($ap_info['errno'] !== Const_Err_Base::ERR_OK) {
    p('Get ap faild! Result:'.Lib_Helper::format_err_struct($ap_info));
    exit;
}
$ap_info = $ap_info['data'];
$flow_id = $ap_info[Module_FlowManager_Main::KEY_FLOW_ID];
unset($ap_info[Module_FlowManager_Main::KEY_FLOW_ID]);
$control_options[Const_DataAccess::RUN_OPTION_ACCESS_POINT_INFO] = $ap_info;
$data = Module_ControlCentre_Main::run($flow_id, $control_options, $task_id);
p($data);exit;