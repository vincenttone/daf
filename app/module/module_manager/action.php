<?php
/**
 * @name:	展示source列表
 * @brief:	
 * @create:	2014-6-11
 * @update:	2014-6-11
 *
 * @type:
 * @register:
 */
class Module_ModuleManager_Action
{
    public static function module_list_action()
    {
        $modules = Module_ModuleManager_Register::get_instance()->get_registered_modules();
        Module_Page_Main::render('module_manager/list', ['module_list' => $modules]);
    }

    static public function refresh_module_action()
    {
        $register = Module_ModuleManager_Register::get_instance()->register_modules();
        if($register === false) {
            Module_View_Main::view()->output(0);
        } else {
            Module_OperationRecord_Main::add_record(Module_OperationRecord_Main::OPCODE_MODULE_UPDATE);
            Module_View_Main::view()->output($register);
        }
    }
}
