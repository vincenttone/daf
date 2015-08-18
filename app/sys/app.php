<?php
/**
 * @author	vincent
 * @brief	加载器
 * @version	1.0
 */
namespace Da;
require_once(dirname(__FILE__).'/const.inc');
require_once(dirname(__FILE__).'/config.php');

class Sys_App
{
    private static $_instance = null;

    private $_init_file = null;
    private $_configure = null;

    private function __construct()
    {
    }

    /**
     * @param string the config file of data access system
     * @return array the instance of this class
     */
    static public function app($init_file = 'app.ini')
    {
        if (is_null(self::$_instance)) {
            $class = __CLASS__;
            self::$_instance = new $class;
            self::$_instance->_set_init_file($init_file);
        }
        return self::$_instance;
    }

    /**
     * Forbid to clone the object
     */
    private function __clone()
    {
        throw new \Exception("Could not clone the object from class: ".__CLASS__);
    }

    /**
     * @return bool
     */
    public function bootstrap()
    {

        $this->_make_app_config();		// config manager
        $this->_autoload_register();	// set autoloader
        Sys_Config::get_instance()->set_config_path(self::conf_path())
                                  ->check_run_mode_suffix(true);
        $this->_init_log();				// run the logger
        \Module_ModuleManager_Main::get_instance()
            ->init_with_module_path(self::module_path());	//init the modules manager
        return true;
    }

    /**
     * init the log manager
     */
    private function _init_log()
    {
        $config = Sys_Config::config('log/base');
        if ($config) {
            $this->_configure['path']['log'] = isset($config['path']) 
                ? $config['path'] : 'logs';
            $config['path'] = self::log_path();
        } else {
            $config = [];
        }
        \Lib_Log::get_instance()->init($config);
    }

    /**
     * @param string
     */
    private function _set_init_file($file)
    {
        $this->_init_file = $file;
    }

    /**
     * @return array|bool
     */
    private function _make_app_config()
    {
        $this->_configure = Sys_Config::get_instance()
            ->get_config_from_filepath($this->_init_file);
        if ($this->_configure === false) {
            return false;
        }
        $this->_run_mode_setting();
        return $this;
    }

    /**
     * @return int the run_mode
     */
    public function get_run_mode()
    {
        return (
            isset($this->_configure['base'])
            && isset($this->_configure['base']['run_mode']))
            ? $this->_configure['base']['run_mode']
            : DA_RUN_MODE_PRO;
    }

    /**
     * @return array
     */
    private function _run_mode_setting()
    {
        if (self::run_mode() == DA_RUN_MODE_PRO) {
            error_reporting(E_ERROR | E_WARNING | E_PARSE);
        } else {
            if (!ini_get('display_errors')) {
                ini_set('display_errors', 'On');
            }
            error_reporting(E_ALL);
        }
        return $this;
    }

    /**
     * @return int the run_mode
     */
    static function run_mode()
    {
        return self::app()->get_run_mode();
    }

    /**
     * @return bool true on success or false on failure.
     */
    private function _autoload_register()
    {
        return spl_autoload_register(array($this, '_loader'));
    }

    /**
     * load the class by the name
     * @param string classname
     */
    private function _loader($name)
    {
        if (class_exists($name, false)) {
            return;
        }
        $file = self::get_filepath_by_classname($name);
        if (is_file($file)) {
            require_once($file);
        }
        return;
    }
    /**
     * Get the file path by the classname
     * @param string classname
     * @return string filename
     */
    public static function get_filepath_by_classname($classname)
    {

        $ns_rpos = strripos(($classname), '\\');
        if ($ns_rpos !== false) {
            $classname = substr($classname, $ns_rpos + 1, strlen($classname));
        }
        $classname = str_replace('_', '/', $classname);
        $filepath = preg_replace('/\B([A-Z]{1})/', '_$1', $classname);
        $filepath = self::app_path().'/'.strtolower($filepath).'.php';
        return $filepath;
    }

    /**
     * @return array
     */
    private function _get_configure()
    {
        return $this->_configure;
    }

    /**
     * @param string
     * @param null|string $sub_path
     * @return null|string
     */
    public function get_path_by_name($pathname, $sub_path = '')
    {
        $path = null;
        $get_value = function ($base, $keyword) {
            $path = null;
            isset($this->_configure['path'])
            && isset($this->_configure['path'][$keyword])
            && $path = $this->_configure['path'][$keyword];
            if (is_null($path)) {
                throw new \Exception('No such configure :'.$base
                . '/'.$keyword.' in config file'
                , \Const_Err_Base::ERR_CONFIG_MISSING);
            }
            return $path;
        };
        $path = $get_value('path', $pathname);
        if (substr($path, 0,1) !== '/') {
            $path = $this->get_path_by_name('home') . '/' . $path;
        }
        if (substr($path, -1,1) === '/') {
            $path = substr($path, 0, strlen($path) - 1);
        }
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        if (!empty($sub_path)) {
            $path .= '/'.$sub_path;
        }
        return $path;
    }
    /**
     * @param array callback to run
     * @param array arguments for callback
     * @param array|int array modes, or int mode
     * @return null|array null (not in mode) or object (callback's result)
     */
    static function run_in_mode($callback, $args = [], $mode = DA_RUN_MODE_PRO)
    {
        $current_mode = self::run_mode();
        $in_mode = is_array($mode)
            ? in_array($current_mode, $mode)
            : $current_mode == $mode;
        if ($in_mode) {
            return call_user_func_array($callback, $args);
        }
        return null;
    }

    /**
     * @param string
     * @param array
     * @return string|null
     * @throws \Exception
     */
    static function __callStatic($name, $args)
    {
        if (stripos($name, '_path') > 0) {
            $app = self::app();
            $path = substr($name, 0, strlen($name) - 5);
            $config = $app->_get_configure();
            if (isset($config['path'])) {
                try {
                    $sub_path = '';
                    if (!empty($args) && isset($args[0])) {
                        $sub_path = $args[0];
                    }
                    return $app->get_path_by_name($path, $sub_path);
                } catch (\Exception $ex) {
                }
            }
        }
        throw new \Exception('Method '.$name.' not exists');
    }

}
