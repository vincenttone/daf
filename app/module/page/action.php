<?php
class Module_Page_Action
{
    static function refresh_nav()
    {
        $refresh = Module_Page_Manager::refresh_nav();
        Module_OperationRecord_Main::add_record(Module_OperationRecord_Main::OPCODE_NAV_UPDATE);
        Module_View_Main::view()->output($refresh);
    }
}
