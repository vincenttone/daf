<?php
class Module_Translate_Langs_Operation
{
    static $en = [
        '0x11' => "hello %s",
    ];

    static $zh_cn = [
        Module_OperationRecord_Main::OPCODE_SOURCE_ADD     => '%s添加来源',
        Module_OperationRecord_Main::OPCODE_SOURCE_EDIT    => '%s编辑%s来源',
        Module_OperationRecord_Main::OPCODE_CARD_ADD   => '%s添加卡片',
        Module_OperationRecord_Main::OPCODE_CARD_EDIT  => '%s编辑%s卡片',
        Module_OperationRecord_Main::OPCODE_ACCESS_POINT_ADD   => '%s添加接入点',
        Module_OperationRecord_Main::OPCODE_ACCESS_POINT_EDIT  => '%s编辑%s接入点',
        //Module_OperationRecord_Main::OPCODE_ACCESS_POINT_RUN   => '%s运行%s接入点',
        Module_OperationRecord_Main::OPCODE_TASK_RUN           => "%s运行%s任务",
        Module_OperationRecord_Main::OPCODE_RE_ACCESS          => '%s重发数据',
        //Module_OperationRecord_Main::OPCODE_TASK_RUN_RE        => '%s重新运行%s任务',
        //Module_OperationRecord_Main::OPCODE_TASK_RUN_CONTINUE  => '%s继续运行%s任务',
        Module_OperationRecord_Main::OPCODE_TASK_RUN_STOP      => '%s停止%s任务',
        Module_OperationRecord_Main::OPCODE_TASK_STATUS_EDIT   => '%s修改%s任务状态',
        Module_OperationRecord_Main::OPCODE_FLOW_ADD   => '%s添加流程',
        Module_OperationRecord_Main::OPCODE_FLOW_EDIT  => '%s编辑%s流程',
        Module_OperationRecord_Main::OPCODE_MODULE_UPDATE      => '%s刷新模块',
        Module_OperationRecord_Main::OPCODE_NAV_UPDATE => '%s刷新菜单',
        Module_OperationRecord_Main::OPCODE_USER_ADD   => '%s添加用户',
        Module_OperationRecord_Main::OPCODE_USER_EDIT  => '%s编辑%s用户',
        Module_OperationRecord_Main::OPCODE_GROUP_ADD  => '%s添加用户组',
        Module_OperationRecord_Main::OPCODE_GROUP_EDIT => '%s编辑%s用户组',
        Module_OperationRecord_Main::OPCODE_HELP_INFO      => '%s修改帮助信息',
        Module_OperationRecord_Main::OPCODE_HELP_OPEN      => '%s修改开放API',
        Module_OperationRecord_Main::OPCODE_HELP_TODO      => '%s修改TODO',
        Module_OperationRecord_Main::OPCODE_HELP_DEVELOP   => '%s修改开发者API',
        Module_OperationRecord_Main::OPCODE_HELP_INTERNAL  => '%s修改内部API',
    ];
}
