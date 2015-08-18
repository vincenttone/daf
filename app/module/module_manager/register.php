<?php
class Module_ModuleManager_Register extends Module_Base_System
{
    const ENTRY_FILE_NAME = 'main.php';
    const ENTRY_FILE_READ_LENGTH = 8192;

    const INFO_KEY_TYPE_STR = 'str';
    const INFO_KEY_TYPE_ARRAY = 'array';
    const INFO_KEY_TYPE_BOOL = 'bool';

    const MODULE_STATUS_UNREG = 0;
    const MODULE_STATUS_REG = 1;
    const MODULE_STATUS_LOST = 2;

    private $_all_modules = array();
    private $_reg_modules = array();
    private static $_needed_info_keys = array(
        Const_Module::META_NAME => self::INFO_KEY_TYPE_STR,
        Const_Module::META_BRIEF => self::INFO_KEY_TYPE_STR,
        Const_Module::META_AUTHOR => self::INFO_KEY_TYPE_STR,
        Const_Module::META_CREATE => self::INFO_KEY_TYPE_STR,
        Const_Module::META_UPDATE => self::INFO_KEY_TYPE_STR,
        Const_Module::META_TYPE => self::INFO_KEY_TYPE_STR,
        Const_Module::META_REGISTER => self::INFO_KEY_TYPE_ARRAY,
        Const_Module::META_VERSION => self::INFO_KEY_TYPE_STR,
    );
    private static $_instance = null;
    private function __construct()
    {
    }
    /**
     * 获取实例的方法
     * @return $this
     */
    static public function get_instance()
    {
        if (is_null(self::$_instance)) {
            $class = __CLASS__;
            self::$_instance = new $class;
        }
        return self::$_instance;
    }

    /**
     * 禁用对象克隆
     */
    private function __clone()
    {
        throw new Exception("Could not clone the object from class: ".__CLASS__);
    }

    /**
     * @return array
     */
    private function _get_all_modules_from_path()
    {
        $module_path = Module_ModuleManager_Main::module_path();
        if (empty($module_path)) {
            return array();
        }
        $files = scandir($module_path);
        $this->_module_dirs = array();
        foreach($files as $_f) {
            $_module_path = $module_path.'/'.$_f;
            if(strpos($_f, '.') !== 0 && is_dir($_module_path)) {
                $this->_get_module_info_from_path($_f, $_module_path);
            }
        }
        return $this->_all_modules;
    }

    /**
     * @return array
     */
    function get_registered_modules()
    {
        if (!empty($this->_reg_modules)) {
            return $this->_reg_modules;
        }
        $module_info_model = new Model_ModulesInfo();
        $modules =  $module_info_model->get_all();
        if ($modules['errno'] !== Const_Err_Base::ERR_OK) {
            Lib_Log::error(__METHOD__.' faild! cause: '.Lib_Helper::format_err_struct($modules));
            return [];
        }
        $this->_reg_modules = $modules['data'];
        return $this->_reg_modules;
    }

    /**
     * @return bool
     */
    function register_modules()
    {
        $all_modules = $this->_get_all_modules_from_path();
        $registered_modules = $this->get_registered_modules();
        foreach ($registered_modules as $_k => $_m) {
            $registered_modules[$_m['indentify']] = $_m;
            unset($registered_modules[$_k]);
        }
        $new_modules = [];
        $id_gen = new Model_IdGen();
        foreach ($all_modules as $_i => $_m) {
            if (isset($registered_modules[$_i])) {
                $_r_module = $registered_modules[$_i];
                if (
                    Lib_Helper::str_equal(
                        $_r_module[Const_Module::META_VERSION],
                        $_m[Const_Module::META_VERSION]
                    ) === false) {
                    $_m[Model_ModulesInfo::FIELD_MODULE_ID]
                        = $_r_module[Model_ModulesInfo::FIELD_MODULE_ID];
                    $_m[Model_ModulesInfo::FIELD_MODULE_REG_TIME] = time();
                    $new_modules[] = $_m;
                }
                unset($registered_modules[$_i]);
            } else {
                $_id = $id_gen->gen_inc_id_by_key(Const_DataAccess::ID_MODULE);
                if ($_id['errno'] != Const_Err_Base::ERR_OK) {
                    Lib_Log::error("get moduel id failed, result: %s", json_encode($_id));
                    return false;
                }
                $_id = $_id['data'];
                if (empty($_id)) {
                    Lib_Log::error('module id gen failed!');
                    continue;
                }
                $_m[Model_ModulesInfo::FIELD_MODULE_ID] = $_id;
                $_m[Model_ModulesInfo::FIELD_MODULE_REG_TIME] = time();
                $new_modules[] = $_m;
            }
        }
        $insert = false;
        if (!empty($new_modules)) {
            $modules_info = new Model_ModulesInfo();
            foreach ($new_modules as $_m) {
                $modules_info->save($_m);
            }
            $insert = true;
        }
        return $insert;
    }

    /**
     * @return array
     */
    function get_functional_modules()
    {
        $module_info_model = new Model_ModulesInfo();
        $modules = $module_info_model->get_modules_by_type(Const_Module::TYPE_FUNCTIONAL);
        if ($modules['errno'] !== Const_Err_Base::ERR_OK) {
            Lib_Log::error(__METHOD__.' faild! cause: '.json_encode($modules['data']));
            return [];
        }
        return $modules['data'];
    }

    /**
     * @param string $module_indent
     * @param string $module_path
     * @return bool
     */
    private function _get_module_info_from_path($module_indent, $module_path = '')
    {
        $module_indent = strtolower($module_indent);
        if (empty($module_path)) {
            $module_path = Module_ModuleManager_Main::module_path().'/'.$module_indent;
        }
        $entry_file = $module_path.'/'.self::ENTRY_FILE_NAME;
        if (isset($this->_all_modules[$module_indent])) {
            return $this->_all_modules[$module_indent];
        } else {
            if (file_exists($entry_file)) {
                $needed_info = self::read_comment_info_from_file($entry_file);
                $this->_all_modules[$module_indent] = $needed_info;
                $this->_all_modules[$module_indent][Model_ModulesInfo::FIELD_MODULE_VERSION]
                    = isset($needed_info[Const_Module::META_VERSION])
                    ? $needed_info[Const_Module::META_VERSION] : '1.0.0';
                $_cls = self::get_module_entry_class($module_indent);
                $this->_all_modules[$module_indent][Model_ModulesInfo::FIELD_MODULE_STATUS] = self::MODULE_STATUS_UNREG;
                $this->_all_modules[$module_indent][Model_ModulesInfo::FIELD_MODULE_CLASS] = $_cls ? $_cls : 'UNKNOW';
                $this->_all_modules[$module_indent][Model_ModulesInfo::FIELD_MODULE_INDENT] = $module_indent;
                $this->_all_modules[$module_indent][Model_ModulesInfo::FIELD_MODULE_PATH] = $module_path;
                return $this->_all_modules[$module_indent];
            }
        }
        $this->_all_modules[$module_indent] = false;
        return false;
    }

    /**
     * @param array $info
     * @return array
     */
    private static function _gen_module_info($info)
    {
        $needed_info = [];
        foreach (self::$_needed_info_keys as $_k => $_t) {
            if (isset($info[$_k])) {
                $info[$_k] = trim($info[$_k]);
                switch ($_t) {
                    case self::INFO_KEY_TYPE_ARRAY:
                        $needed_info[$_k] = preg_split('/\s*\|\s*/', $info[$_k]);
                        break;
                    case self::INFO_KEY_TYPE_BOOL:
                        strcmp(strtolower($info[$_k]), 'yes') == 0 && $needed_info[$_k] = true;
                        strcmp(strtolower($info[$_k]), 'no') == 0 && $needed_info[$_k] = false;
                        break;
                    case self::INFO_KEY_TYPE_STR:
                    default:
                        $needed_info[$_k] = $info[$_k];
                        break;
                }
            }
        }
        return $needed_info;
    }

    /**
     * @param string $file
     * @return array
     */
    static function read_comment_info_from_file($file)
    {
        $f = fopen($file, 'r');
        $info_comment = fread($f, self::ENTRY_FILE_READ_LENGTH);
        fclose($f);
        $result = [];
        preg_match_all('/\*+?\s*@(\w+?)\s*:(.*?)\r?\n/', $info_comment, $result);
        if (is_array($result) && isset($result[1]) && isset($result[2])) {
            $info = array_combine($result[1], $result[2]);
            $needed_info = self::_gen_module_info($info);
            return $needed_info;
        }
        return [];
    }

    /**
     * @param int $module_id
     * @return array
     */
    function get_module_info_by_id($module_id)
    {
        if (isset($this->_reg_modules[$module_id])) {
            return $this->_reg_modules[$module_id];
        }
        $module_info_model = new Model_ModulesInfo();
        $module = $module_info_model->get_module_by_id($module_id);
        if ($module['errno'] !== Const_Err_Base::ERR_OK) {
            Lib_Log::error(__METHOD__.' faild! cause: '.Lib_Helper::format_err_struct($module));
            return [];
        }
        $this->_reg_modules[$module_id] = $module['data'];
        return $this->_reg_modules[$module_id];
    }

    /**
     * @return array
     */
    static function functional_modules()
    {
        return self::get_instance()->get_functional_modules();
    }

    /**
     * @param string $module_indent
     * @return bool|mixed|string
     */
    static function get_module_entry_class($module_indent)
    {
        $subfix = substr(self::ENTRY_FILE_NAME, 0, strlen(self::ENTRY_FILE_NAME) - 4);
        // prefix 目前只支持一层目录
        $prefix = str_replace(Da\Sys_App::app_path().'/', '', Da\Sys_App::module_path());
        $matchs = [];
        $r = function($matchs){
            return strtoupper($matchs[0][1]);
        };
        $cls = preg_replace_callback('/_\w/', $r, strtolower($module_indent));
        $cls = ucfirst($prefix).'_'.ucfirst($cls).'_'.ucfirst($subfix);
        if (!class_exists($cls)) {
            return false;
        }
        return $cls;
    }

    static function all_modules_action()
    {
         $register = self::get_instance();
        $all_modules = $register->_get_all_modules_from_path();
        $reg_modules = self::get_instance()->get_registered_modules();
        self::output($all_modules);
    }

    /**
     * @param int $module_id
     * @return array
     */
    static function functional_module_class_name($module_id)
    {
        $module = self::get_instance()
            ->get_module_info_by_id($module_id);
        if (empty($module)) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_MODULE_NOT_EXISTS,
                '模块['.$module_id.']不存在'
            );
        }
        if (!isset($module[Const_Module::META_TYPE])) {
            Lib_Log::error(
                'errno:'.Const_Err_DataAccess::ERR_MODULE_INCORRECT_INFO
                .' module not has meta type, module_id: '.$module_id
            );
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_MODULE_INCORRECT_INFO,
                ' module not has meta type, module_id: '.$module_id
            );
        }
        if (strcmp($module[Const_Module::META_TYPE], Const_Module::TYPE_FUNCTIONAL) !== 0) {
            Lib_Log::error(
                'errno:'.Const_Err_DataAccess::ERR_MODULE_WRONG_TYPE
                .' module is not functional module, module_id: '.$module_id
            );
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_MODULE_WRONG_TYPE,
                ' module not has meta type, module_id: '.$module_id
            );
        }
        $class_name = $module[Model_ModulesInfo::FIELD_MODULE_CLASS];
        if (!class_exists($class_name)) {
            Lib_Log::error(
                "errno: %d, err: module class %s not exists!",
                [Const_Err_DataAccess::ERR_MODULE_CLASS_NOT_EXISTS, $class_name]
            );
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_MODULE_CLASS_NOT_EXISTS,
                'Module class '.$class_name.' not exists!'
            );
        }
        return Lib_Helper::get_return_struct($class_name);
    }

    /**
     * @param array $register
     * @return array
     */
    function get_modules_with_register($register)
    {
        $modules = $this->get_registered_modules();
        $target_modules = [];
        foreach ($modules as $_m) {
            isset($_m['register'])
                && in_array($register, $_m['register'])
                && array_push($target_modules, $_m);
        }
        return $target_modules;
    }

}