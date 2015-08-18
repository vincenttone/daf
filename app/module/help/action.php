<?php
class Module_Help_Action
{
    const KEYWORD = 'keyword';
    const TITLE = 'title';
    const URL = 'url';
    const SUBMIT_URL = 'submit_url';

    private static $_action_list = [
        'index' => [
            self::KEYWORD => 'help_index',
            self::TITLE => '帮助信息',
            self::URL => '/help/index',
            self::SUBMIT_URL => '/help/make-index',
        ],
        'dev' => [
            self::KEYWORD => 'help_dev',
            self::TITLE => '开发者',
            self::URL => '/help/development',
            self::SUBMIT_URL => '/help/make-development',
        ],
        'todo' => [
            self::KEYWORD => 'help_todo',
            self::TITLE => 'Todo List',
            self::URL => '/help/todo',
            self::SUBMIT_URL => '/help/make-todo',
        ],
        'internal_api' => [
            self::KEYWORD => 'internal_api',
            self::TITLE => '内部API',
            self::URL => '/help/api/internal',
            self::SUBMIT_URL => '/help/api/make-internal',
        ],
        'open_api' => [
            self::KEYWORD => 'open_api',
            self::TITLE => '开放API',
            self::URL => '/help/api/open',
            self::SUBMIT_URL => '/help/api/make-open'
        ],
    ];

    /**
     * @param string $msg
     * @param string $url
     * @throws Exception
     */
    private static function _error_and_redirect($msg, $url)
    {
        Lib_Request::flash('error', $msg);
        Module_HttpRequest_Router::redirect_to($url);        
    }

    /**
     * @param string $key
     */
    static function render_help_page($key)
    {
        if (!isset(self::$_action_list[$key])) {
            self::_error_and_redirect('不存在此页', '/');
        }
        $actions = self::$_action_list[$key];
        $keyword = $actions[self::KEYWORD];
        $model_option = new Model_Option();
        $data = $model_option->get_one_by_keyword($keyword);
        $content = [];
        if ($data['errno'] == Const_Err_Base::ERR_OK) {
            $content = $data['data'];
        } elseif ($data['errno'] != Const_Err_Db::ERR_MONGO_FINDONE_EMPTY) {
            self::_error_and_redirect(Lib_Helper::format_err_struct($data), '/');
        }
        $content = isset($content['value']) ? $content['value'] : '';
        if (trim($content) != '') {
            $content = str_replace("\r", '', $content);
            $content = str_replace("\n", '\n', $content);
            $content = htmlentities($content);
        }
        $render_data = [
            'help_title' => $actions[self::TITLE],
            'submit_action' => Module_HttpRequest_Router::site_url($actions[self::SUBMIT_URL]),
            'md_content' =>  $content,
        ];
        Module_Page_Main::render('help/markdown', $render_data);
    }

    /**
     * @param string $key
     * @throws Exception
     */
    static function save_md_data($key)
    {
        if (!isset(self::$_action_list[$key])) {
            self::_error_and_redirect('不存在此页', '/');
        }
        $actions = self::$_action_list[$key];
        $content = Lib_Request::post_var('content');
        if (trim($content) == '') {
            Lib_Request::flash('error', '提交内容为空');
            Module_HttpRequest_Router::redirect_to($actions[self::URL]);
        }
        $keyword = $actions[self::KEYWORD];
        $model_option = new Model_Option();
        $content = str_replace("\r", '', $content);
        $content = str_replace("\n", '\n', $content);
        //$content = htmlentities($content);
        $result = $model_option->update_option_by_keyword($keyword, $content);
        if ($result['errno'] != Const_Err_Base::ERR_OK) {
            self::_error_and_redirect(Lib_Helper::format_err_struct($result), $actions[self::URL]);
        } else {
            $help2opcode = [
                    'help_index'    => Module_OperationRecord_Main::OPCODE_HELP_INFO,
                    'help_dev'      => Module_OperationRecord_Main::OPCODE_HELP_DEVELOP,
                    'help_todo'     => Module_OperationRecord_Main::OPCODE_HELP_TODO,
                    'internal_api'  => Module_OperationRecord_Main::OPCODE_HELP_INTERNAL,
                    'open_api'      => Module_OperationRecord_Main::OPCODE_HELP_OPEN,
            ];
            $opcode = isset($help2opcode[$keyword])? $help2opcode[$keyword]:Module_OperationRecord_Main::OPCODE_HELP_INFO;
            Module_OperationRecord_Main::add_record($opcode);
            Lib_Request::flash('保存成功');
            Module_HttpRequest_Router::redirect_to($actions[self::URL]);
        }
    }

    /**
     * @param string $method
     * @param array $args
     * @throws Exception
     */
    static function __callStatic($method, $args)
    {
        if (strpos($method, 'make_') === 0) {
            $key = str_replace('make_', '', $method);
            $key = str_replace('_action', '', $key);
            self::save_md_data($key);
        } else {
            $key = str_replace('_action', '', $method);
            self::render_help_page($key);
        }
        throw new Exception('No such page');
    }
}
