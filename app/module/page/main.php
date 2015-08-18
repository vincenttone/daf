<?php
/**
 * @name:	页面展示模块
 * @brief:	主要用于展现基础页面和其他模块调用
 * @author: vincent
 * @create:	2014-5-25
 * @update:	2014-5-25
 *
 * @type:	system
 * @version: 1.0.0.1
 */
class Module_Page_Main extends Module_Base_System
{
    use singleton_with_get_instance;

    /**
     * @return array
     */
    static function page()
    {
        return self::get_instance();
    }

    /**
     * @param array $menu
     * @param string $perm
     */
    function register_main_menu($menu, $perm = '766')
    {
        $menu_keyword = $menu['keyword'];
        $menu_name = $menu['name'];
        $menu_path = $menu['path'];
        $menu_perm = $perm;
    }

    /**
     * @param array $menu
     * @param int $main_menu_id
     * @param string $perm
     */
    function register_sub_menu($menu, $main_menu_id, $perm = '766')
    {
        $menu_keyword = $menu['keyword'];
        $menu_name = $menu['name'];
        $menu_path = $menu['path'];
        $menu_perm = $perm;
    }

    /**
     * @return array
     */
    static function register_router()
    {
        return [
            'index' => [__CLASS__, 'index_action'],
            'refresh_nav' => [
                'Module_Page_Action',
                'refresh_nav',
                'GET',
                [
                    Const_DataAccess::URL_PERM => Module_Account_Perm::PERM_FLOW_ADMIN,
                ],
            ],
        ];
    }

    static function index_action()
    {
        $model_task = new Model_Task();
        $tasks = $model_task->get_all(['status' => 2], 10);
        if ($tasks['errno'] !== Const_Err_Base::ERR_OK) {
            Lib_Log::error(Lib_Helper::format_err_struct($tasks));
            $tasks = [];
        }
        $tasks = $tasks['data'];

        $ap_ids = [];
        $src_ids = [];
        foreach ($tasks as $_k => $_t) {
            $ap_ids[$_k] = isset($_t['ap_id'])? $_t['ap_id']:'';
            $src_ids[$_k] = isset($_t['src_id'])? $_t['src_id']:'';
        }

        $ap_model = new Model_AccessPoint();
        $aps = $ap_model->get_ap_by_ids($ap_ids);
        if($aps['errno'] !== Const_Err_Base::ERR_OK) {
            Lib_Log::error(Lib_Helper::format_err_struct($aps));
            $aps = [];
        }
        $aps = $aps['data'];

        $source_model = new Model_Source();
        $sources = $source_model->get_sources_by_ids($src_ids);
        if ($sources['errno'] !== Const_Err_Base::ERR_OK) {
            Lib_Log::error(Lib_Helper::format_err_struct($sources));
            $sources = [];
        }
        $sources = $sources['data'];

        $gt_time = strtotime('-1 month');
        $source_model->set_table_name('t_src_statistics');
        $cond = ['update_time' => ['$gte' => $gt_time]];
        $source_statistics = $source_model->get_table()->get_all($cond, [], 0, 0, null, true);
        if ($source_statistics['errno'] !== Const_Err_Base::ERR_OK) {
            Lib_Log::error(Lib_Helper::format_err_struct($source_statistics));
            $source_statistics = [];
        }
        $source_statistics = $source_statistics['data'];
        $statistics = [];
        foreach($source_statistics as $v) {
            $day = date('m-d', $v['update_time']);
            $statistics[$day] = isset($statistics[$day])? $statistics[$day]:[];
            $statistics[$day]['total'] = isset($statistics[$day]['total'])? $statistics[$day]['total']:0;
            $statistics[$day]['status_1'] = isset($statistics[$day]['status_1'])? $statistics[$day]['status_1']:0;
            $statistics[$day]['status_other'] = isset($statistics[$day]['status_other'])? $statistics[$day]['status_other']:0;
            $statistics[$day]['total'] += intval($v['total']);
            $statistics[$day]['status_1'] += intval($v['status_1']);
            $statistics[$day]['status_other'] = $statistics[$day]['total'] - $statistics[$day]['status_1'];
        }
        foreach($statistics as $day => $v) {
            $source_statistics_min = isset($source_statistics_min) && $source_statistics_min<$v['status_1'] ? $source_statistics_min:$v['status_1'];
        }

        $_access = Module_Statistics_Action::get_index_month_charts();
        $access_date = $_access['access_date'];
        $receive_data = $_access['receive_data'];
        $send_data = $_access['send_data'];

        self::render('common/index', [
            'tasks' => $tasks,
            'aps' => $aps,
            'sources' => $sources,
            'source_statistics' => $statistics,
            'source_statistics_min' => $source_statistics_min,
            'access_date' => $access_date,
            'send_data' => $send_data,
            'receive_data' => $receive_data,
        ]);
    }

    /**
     * @param string $template
     * @param array $var
     */
    static function render($template, $var = [])
    {
        $global_var = [
            'title' => '数据接入 - Data Access',
            'user' => Module_Account_User::current_user_info(),
        ];
        if (Da\Sys_Config::config('app/base')['run_mode'] == DA_RUN_MODE_PRE) {
            $global_var['title'] = '预接入系统 - Data Access';
        } else if (Da\Sys_Config::config('app/base')['run_mode'] == DA_RUN_MODE_DEV) {
            $global_var['title'] = '开发接入系统 - Data Access';
        }

        $template_array = [
            'header' => ['common/header', $global_var],
            'nav' => ['common/nav', $global_var],
            'message_board' => ['common/message_board'],
            'crumb' => ['common/crumb'],
            'page' => [$template, $var],
            'footer' => ['common/footer', $global_var],
        ];
        Module_View_Main::view()->m_render($template_array, 'common/base_page');
    }

    /**
     * @return array
     */
    static function get_nav()
    {
        $nav = Module_Page_Manager::nav_struct();
        return $nav;
    }

    /**
     * @param array $nav_list
     * @return array
     */
    static function url_perm_map($nav_list)
    {
        $map = [];
        foreach ($nav_list as $_nav) {
            if ($_nav === null) {
                continue;
            }
            if (isset($_nav['perm'])) {
                $map[$_nav['path']] = $_nav;
            }
            if (isset($_nav['children'])) {
                $c_map = self::url_perm_map($_nav['children']);
                $map = array_merge($map, $c_map);
            }
        }
        return $map;
    }
}
