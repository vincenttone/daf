<?php
/**
 * @name:	系统级基础模块
 * @brief:	主要用于继承
 * @author: vincent
 * @create:	2014-5-13
 * @update:	2014-5-13
 *
 * @type:	abstract
 */
class Module_Base_System extends Module_Base_Main
{
    /**
     * @return string
     */
    static function module_type()
    {
        return Const_Module::TYPE_SYSTEM;
    }
}