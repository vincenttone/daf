<?php
/**
 * @name:	流程管理
 * @brief:	用于控制流程，组织模块
 * @author: vincent
 * @create:	2014-5-15
 * @update:	2014-5-15
 *
 * @type:	system
 * @register: web
 * @version: 1.0.1
 */
class Module_FlowManager_Main
{
    /**
     * [
     *        'id' => $id, //流程ID
     *        'name' => $name, //流程名
     *        'flow' => [  //流程信息
     *            'main'=> [
     *                //顺序执行的主流程模块ID
     *                1, 2, 3, //顺序执行1,2,3
     *            ],
     *            'branch' => [
     *                // 当前执行的模块ID => 下次执行的模块ID
     *                2 => 5,   // 2执行后执行5
     *                3 => [ 6, 7, [8, 9]] //3执行后无序并发执行(6,7,[8,9])
     *            ],
     *            'stuff' => [
     *                2 => [11, 12], // 回调使用，顺序执行
     *                3 => [13, 14], 
     *            ],
     *            'config' => [15, 16], //配置模块，无顺序要求
     *        ],
     *        'options' => [] //流程控制选项
     * ]
     */
    const RUN_MODE = 'flow_mode';
    const RUN_MODE_IN_ORDER = 1;
    const RUN_MODE_CALLBACK = 2;

    const RUN_OPTION_SINGLE_MODULE_ID = 'single_module_id';
    const RUN_OPTION_CONTINUE_MODULE_ID = 'continue_module_id';
    const RUN_OPTION_CUSTOM_FLOW = 'custom_flow';
    const RUN_OPTION_RECORD_MODULE_INFO = 'record_module_info';

    const KEY_FLOW_ID = 'flow_id';

    const FLOW_TYPE_MAIN = 'main';  //主流程模块
    const FLOW_TYPE_BRANCH = 'branch';  //分支流程模块
    const FLOW_TYPE_STUFF = 'stuff';  //额外流程模块
    const FLOW_TYPE_CONFIG = 'config';  //配置模块

    const FLOW_MODULE_TYPE_MAIN = 1;
    const FLOW_MODULE_TYPE_BRANCH = 2;
    const FLOW_MODULE_TYPE_STUFF = 3;
    const FLOW_MODULE_TYPE_CONFIG = 4;

    const EXPORT_META_FILE = 'export_meta_file';
    const META_FILE_SUFFIX = 'meta';
    const META_FILE_UNUSE = 0;
    const META_FILE_CREATE = 1;

    const RECORD_MODULE_INFO_YES = 1;
    const RECORD_MODULE_INFO_NO = 2;

    const URL_CATALOG_SETUP = '设置';

    private $_flows = [];  //流程信息
    private $_running_flows = []; //正在跑的流程

    /**
     * @param array $data
     * @param bool $is_main_flow
     * @return array
     */
    static function check_data ($data, $is_main_flow = true) {
        if (isset($data['count'])) {
            print_r($data['count']); // TODO: counter
        }
        return $data;
    }

    /**
     * @return array
     */
    static function all_flow()
    {
        $flow_model = new Model_FlowInfo();
        return $flow_model->get_all();
    }

    /**
     * @return array
     */
    static function register_router()
    {
        return [
            'flow/list' => [
                'Module_FlowManager_Action',
                'list_action',
                'GET',
                [
                    Const_DataAccess::URL_NAV => true,
                    Const_DataAccess::URL_WEIGHT => 199,
                    Const_DataAccess::URL_NAME => '添加接入',
                    Const_DataAccess::URL_CATALOG => Module_AccessPoint_Main::URL_CATALOG_AP,
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_AP_ADMIN,
                ]
            ],
            'flow/new' => [
                'Module_FlowManager_Action',
                'add_show_action',
                'GET',
                [
                    Const_DataAccess::URL_NAV => true,
                    Const_DataAccess::URL_WEIGHT => 195,
                    Const_DataAccess::URL_NAME => '添加流程',
                    Const_DataAccess::URL_CATALOG => self::URL_CATALOG_SETUP,
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_FLOW_ADMIN,
                ]
            ],
            'flow/add' => [
                'Module_FlowManager_Action', 'add_action', 'POST',
                [
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_FLOW_ADMIN,
                ]
            ],
            'flow/edit' => [
                'Module_FlowManager_Action', 'edit_action', 'GET',
                [
                    Const_DataAccess::URL_NAME => '编辑流程',
                    Const_DataAccess::URL_CATALOG => self::URL_CATALOG_SETUP,
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_FLOW_ADMIN,
                ]
            ],
            'flow/update' => [
                'Module_FlowManager_Action', 'update_action', 'POST',
                [
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_FLOW_ADMIN,
                ]
            ],
        ];
    }
}
