<?php
class Module_HttpRequest_Router
{
    use singleton_with_get_instance;

    const ROUTER_REG_METHOD = 'register_router';
    const DEFAULT_MODULE = 'page';
    const DEFAULT_PATH = 'index';
    private $_router = [];
    private $_url_path_list = [];
    private $_current_url = '';
    private $_current_url_path = '';
    private $_current_url_info = [];
    private $_pre_route_hooks = [];

    /**
     * @param array $url_piece
     * @return array
     * @throws Exception
     */
    function route($url_piece)
    {
        if (isset($url_piece[0]) && strpos($url_piece[0], '.php') !== false) {
            array_shift($url_piece);
        }
        $module = array_shift($url_piece);
        $path = implode('/', array_filter($url_piece));
        if ($module == self::DEFAULT_MODULE && $path == self::DEFAULT_PATH) {
            self::redirect_to('/');
        }
        empty($module)
            && $module = self::DEFAULT_MODULE;
        empty($path)
            && $path = self::DEFAULT_PATH;
        $this->_current_url_info['module'] = $module;
        Lib_Log::debug('Try to route to module: [%s], path: [%s]', [$module, $path]);
        if (isset($this->_router[$module])) {
            return $this->_dispatch_to_method($this->_router[$module], $path);
        }
        $class = Module_ModuleManager_Register::get_module_entry_class($module);
        if ($class && is_callable([$class, self::ROUTER_REG_METHOD])) {
            $router = call_user_func([$class, self::ROUTER_REG_METHOD]);
            $this->_router[$module] = $router;
            return $this->_dispatch_to_method($router, $path);
        } else {
            return [
                'errno' => Da\Sys_Router::ERRNO_NOT_FOUND,
                'data' => 'class '.var_export($class, true). ' can not route.'
                .' module: ['.$module.']'.' path: ['.$path.']',
            ];
        }
    }

    /**
     * @param array $router
     * @param string $path
     * @return array
     */
    private function _dispatch_to_method($router, $path)
    {
        if (!isset($router[$path])) {
            return [
                'errno' => Da\Sys_Router::ERRNO_NOT_FOUND,
                'data' => 'path ['.$path.'] not exists.'
            ];
        }
        $rules = $router[$path];
        $count = count($rules);
        $http_method = 'get';
        if ($count > 2) {
            $http_method = $rules[2];
            if (strtolower($http_method) == 'post' && empty($_POST)) {
                return [
                    'errno' => Da\Sys_Router::ERRNO_NOT_FOUND,
                    'data' => 'ONLY SUPPORT POST FOR URL:'.$path.', but got empty Post vars.',
                ];
            }
        }
        $method = array_slice($rules, 0, 2);
        if (!is_callable($method)) {
            return [
                'errno' => Da\Sys_Router::ERRNO_NOT_FOUND,
                'data' => 'method '.json_encode($method). ' not exists',
            ];
        }
        $this->_carry_current_url_path();
        $this->_current_url_info['url_path'] = self::current_url_path();
        $this->_current_url_info['rule'] = $rules;
        foreach($this->_pre_route_hooks as $_hook) {
            if (is_callable($_hook)) {
                call_user_func($_hook);
            }
        }
        try {
            $result = call_user_func($method);
        } catch (Exception $ex) {
            \Lib_Log::error('Runtime error errno: [%d], msg: [%s]', [$ex->getCode(), $ex->getMessage()]);
            return [
                'errno' => Da\Sys_Router::ERRNO_SERVER_ERR,
                'data' => 'something error!',
            ];
        }
        return ['errno' => 200, 'data' => 'REUQEST OK!'];
    }

    /**
     * @param array $hook
     * @return $this
     */
    function register_pre_router_hook($hook)
    {
        $this->_pre_route_hooks[] = $hook;
        return $this;
    }

    /**
     * 注册urlpath
     * @param string $path
     * @return bool
     */
    function register_url_path($path)
    {
        if (!isset($path['name'])) {
            return false;
        }
        $_name = $path['name'];
        unset($path['name']);
        if (isset($this->_url_path_list[$_name])) {
            return false;
        }
        $this->_url_path_list[$_name] = $path;
        return true;
    }

    /**
     * @return array
     */
    function get_url_path_list()
    {
        return $this->_url_path_list;
    }

    /**
     * 返回页面的url
     * 建议使用绝对路径 /xxx/yyy/zzz
     * @param string $path
     * @return string
     */
    static function site_url($path = '/')
    {
        if (strpos($path, '/') === 0) {
            $base_path = trim($_SERVER['REQUEST_URI'], '/');
            $url_piece = explode('/', $base_path);
            if (isset($url_piece[0])) {
                $url_base = strpos($url_piece[0], '.php');
                if ($url_base) {
                    $path = $url_piece[0].$path;
                }
            }
        }
        $http_conf = Da\Sys_Config::config('env/http');
        $domain = $http_conf['domain'];
        return 'http://'.$domain.'/'.trim($path, '/');
    }

    /**
     * @return string
     */
    private function _carry_current_url_path()
    {
        $this->_current_url = trim($_SERVER['REQUEST_URI'], '/');
        $this->_current_url_path = $this->get_url_path_from_url($this->_current_url);
        return $this->_current_url_path;
    }

    /**
     * @param string $url
     * @return string
     */
    public function get_url_path_from_url($url)
    {
        $qustion_mark_pos = strpos($url, '?');
        if ($qustion_mark_pos !== false) {
            $url = substr($url, 0, $qustion_mark_pos);
        }
        $url_piece = explode('/', $url);
        $url_base = strpos($url_piece[0], '.php');
        if ($url_base) {
            array_shift($url_piece);
        }
        $path = implode('/', $url_piece);
        $path = '/'.trim($path, '/');
        return $path;
    }

    /**
     * @return string
     */
    public function get_current_url_path()
    {
        return $this->_current_url_path;
    }

    /**
     * @return string
     */
    static function current_url_path()
    {
        $path = self::get_instance()->get_current_url_path();
        return $path;
    }

    /**
     * @return string
     */
    static function current_url()
    {
        return self::get_instance()->_current_url;
    }

    /**
     * @return array
     */
    static function current_url_info()
    {
        return self::get_instance()->_current_url_info;
    }

    /**
     * @return string
     */
    static function current_url_extra_info()
    {
        $url_info = self::current_url_info();
        $perm = isset($url_info['rule'][3])
            ? $url_info['rule'][3]
            : null;
        return $perm;
    }

    /**
     * @param string $path
     * @return bool
     */
    static function is_current_url_path($path = '/')
    {
        if (is_null($path)) {
            return false;
        }
        $url = self::current_url_path();
        if (is_array($path)) {
            foreach ($path as $_p) {
                $_p = rtrim($_p, '/');
                if (Lib_Helper::str_equal($_p, $url)) {
                    return true;
                }
            }
            return false;
        }
        Lib_Helper::str_equal($path, '/')
            || $path = rtrim($path, '/');
        return Lib_Helper::str_equal($path, $url);
    }

    /**
     * @param string $path
     * @throws Exception
     */
    static function redirect_to($path)
    {
        if (self::is_current_url_path($path)) {
            throw new Exception('No End Loop Redirect!', Const_Err_Request::ERR_NO_END_LOOP);
        }
        $url = self::site_url($path);
        header('Location:'.$url);
        exit;
    }
}