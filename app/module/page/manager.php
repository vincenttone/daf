<?php
class Module_Page_Manager
{
    use singleton_with_get_instance;

    const SAVE_KEY_NAV = 'page-nav';

    private $_nav = null;

    /**
     * @return array
     */
    function gen_nav_from_module()
    {
        $modules = Module_ModuleManager_Register::get_instance()
            ->get_modules_with_register('web');
        $nav_info = [];
        foreach ($modules as $_m) {
            if (
                !isset($_m['entry_class'])
                || !isset($_m['indentify'])
            ) {
                continue;
            }
            $pre_path = $_m['indentify'];
            $entry_class = $_m['entry_class'];
            if (!method_exists($entry_class, 'register_router')) {
                continue;
            }
            $_router = call_user_func([$entry_class, 'register_router']);
            foreach ($_router as $__p => $__i) {
                if (!isset($__i[3][Const_DataAccess::URL_NAME])) {
                    continue;
                }
                $__info = $__i[3];
                $nav_info['/'.$pre_path.'/'.$__p] = $__info;
            }
        }
        $convert_nav = function($url, $info) {
            $nav = [
                'name' => $info[Const_DataAccess::URL_NAME],
                'path' => $url,
                'weight' => isset($info[Const_DataAccess::URL_WEIGHT])
                    ? $info[Const_DataAccess::URL_WEIGHT]
                    : 0,
            ];
            isset($info[Const_DataAccess::URL_PERM])
                && $nav['perm'] = $info[Const_DataAccess::URL_PERM];
            $nav['show'] = (isset($info[Const_DataAccess::URL_NAV])
                && $info[Const_DataAccess::URL_NAV])
                ? true
                : false;
            return $nav;
        };
        $nav = [];
        foreach ($nav_info as $_url => $_info) {
            if (isset($_info[Const_DataAccess::URL_CATALOG])) {
                $_catalog = $_info[Const_DataAccess::URL_CATALOG];
                isset($nav[$_catalog])
                    || $nav[$_catalog] = [
                        'name' => $_info[Const_DataAccess::URL_CATALOG],
                        'children' => [],
                        'weight' => 0,
                    ];
                isset($_info[Const_DataAccess::URL_WEIGHT])
                    && $nav[$_catalog]['weight'] < $_info[Const_DataAccess::URL_WEIGHT]
                    && $nav[$_catalog]['weight'] = $_info[Const_DataAccess::URL_WEIGHT];
                $nav[$_info[Const_DataAccess::URL_CATALOG]]['children'][$_url] = $convert_nav($_url, $_info);
            } else {
                isset($_info[Const_DataAccess::URL_WEIGHT])
                    && $_info['weight'] = $_info[Const_DataAccess::URL_WEIGHT];
                $nav[$_info[Const_DataAccess::URL_NAME]] = $convert_nav($_url, $_info);
            }
        }
        $nav[] = [
            'name' => '首页',
            'path' => '/',
            'weight' => 3000,
        ];
        foreach ($nav as &$_v) {
            if (isset ($_v['children'])) {
                Lib_Array::sort_two_dimension_array($_v['children'], 'weight', false, true);
                $_f = reset($_v['children']);
                $_v['path'] = $_f['path'];
            }
        }
        Lib_Array::sort_two_dimension_array($nav, 'weight', false, false);
        $this->_nav = $nav;
        return $nav;
    }

    /**
     * @return array|mixed
     */
    static function refresh_nav()
    {
        $nav = self::get_instance()->gen_nav_from_module();
        $option_model = new Model_Option();
        $save = $option_model->save(self::SAVE_KEY_NAV, $nav);
        if ($save[Const_DataAccess::MREK_ERRNO] != Const_Err_Base::ERR_OK) {
            return $save;
        }
        return Lib_Helper::get_return_struct($nav);
    }

    /**
     * @return array
     * @throws Exception
     */
    function get_nav()
    {
        if ($this->_nav === null) {
            $mo = new Model_Option();
            $nav = $mo->get_one_by_keyword(Module_Page_Manager::SAVE_KEY_NAV);
            if ($nav[Const_DataAccess::MREK_ERRNO] != Const_Err_Base::ERR_OK) {
                throw new Exception(
                    Lib_Helper::format_err_struct($nav),
                    $nav[Const_DataAccess::MREK_ERRNO]
                );
            }
            $this->_nav = $nav[Const_DataAccess::MREK_DATA]['value'];
        }
        return $this->_nav;
    }

    /**
     * @return array
     */
    static function nav_struct()
    {
        return self::get_instance()->get_nav();
    }
}