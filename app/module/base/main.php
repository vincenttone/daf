<?php
/**
 * @name:	基础模块
 * @brief:	主要用于继承
 * @author: vincent
 * @create:	2014-5-13
 * @update:	2014-5-13
 *
 * @type:	abstract
 */
class Module_Base_Main
{
    const MODULE_UNREG_ID = -1;
    protected static $module_id = self::MODULE_UNREG_ID;

    /**
     * 模块标识
     * @return mixed
     */
    static function indentify()
    {
        $path_slice = explode('/', self::dir());
        return array_pop($path_slice);
    }
    /**
     * 模块路径
     * @return string
     */
    static function dir()
    {
        $filepath = Da\Sys_App::get_filepath_by_classname(get_called_class());
        return dirname($filepath);
    }

    /**
     * @return int
     */
    static function module_id()
    {
        self::module_info();
        return self::$module_id;
    }

    /**
     * @return array
     */
    static function module_info()
    {
        $modules_info = new Model_ModulesInfo();
        $module_info = $modules_info->get_module_by_indentify(self::indentify());
        if ($module_info['errno'] !== Const_Err_Base::ERR_OK) {
            Lib_Log::error(__METHOD__.' faild! cause: '.json_encode($module_info['data']));
            self::$module_id = self::MODULE_UNREG_ID;
            return Lib_Helper::get_err_struct(
                Const_Err_Db::ERR_GET_DATA_FAIL,
                '获取模块失败',
                __FILE__,
                __LINE__
            );
        }
        $module_info = $module_info['data'];
        if (empty($module_info)) {
            self::$module_id = self::MODULE_UNREG_ID;
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_MODULE_UNREG,
                '模块未注册',
                __FILE__,
                __LINE__
            );
        }
        self::$module_id = $module_info['_id'];
        return [
            'errno' => Const_Err_Base::ERR_OK,
            'data' => $module_info,
        ];
    }

    /**
     * @param array $data
     * @param string $format
     */
    static function output($data, $format = 'json')
    {
        Module_View_Main::view()->output($data, $format);
    }

    /**
     * @param string $path
     * @return string
     */
    static function data_path($path = null)
    {
        $dir = Da\Sys_App::data_path() . '/' . self::indentify();
        empty($path) || $dir .= '/'.$path;
        return $dir;
    }

    /**
     * 获取模块相关ini文件配置
     * @param string $str
     * @return array
     */
    static function ini_conf($str)
    {
        return Lib_IniConf::config($str, self::dir());
    }
}