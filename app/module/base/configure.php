<?php
/**
 * @name:	配置型基础模块
 * @brief:	主要用于继承和生成配置，模块本身不会执行
 * @author: vincent
 * @create:	2014-5-26
 * @update:	2014-5-26
 *
 * @type:	abstract
 */
class Module_Base_Configure extends Module_Base_Main
{
    protected $_options = [];

    /**
     * @return string
     */
    static function module_type()
    {
        return Const_Module::TYPE_CONFIGURE;
    }
}