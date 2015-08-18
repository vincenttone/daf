<?php
/**
 * @name:	视图管理
 * @brief:	用于管理视图、模板
 * @author: vincent
 * @create:	2014-5-25
 * @update:	2014-5-25
 *
 * @type:	system
 */
class Module_View_Main
{
    const RENDER_TYPE_PAGE = 1;
    const RENDER_TYPE_API = 2;

    protected $_template_dir = null;
    protected $_vars = [];
    protected $_crumb = [];

    use singleton_with_get_instance;

    /**
     * @return array
     */
    static function view()
    {
        return self::get_instance();
    }

    /**
     * @param string $dir
     * @return $this
     */
    function set_template_dir($dir)
    {
        $this->_template_dir = $dir;
        return $this;
    }

    /**
     * @param array $template
     * @param array $base_on
     */
    function m_render($template, $base_on)
    {
        if (!is_array($template)) {
            exit;
        }
        $template_data = [];
        foreach ($template as $_name => $_temp_info) {
            $var = [];
            if (!isset($_temp_info)) {
                continue;
            }
            $temp = $_temp_info[0];
            isset($_temp_info[1]) && $var = $_temp_info[1];
            $_temp_data = $this->_render_template($temp, $var);
            if ($_temp_data === false ) {
                continue;
            }
            $template_data[$_name] = $_temp_data;
        }
        $this->render($base_on, $template_data);
    }

    /**
     * @param array $template
     * @param array $var
     */
    function render($template, array $var = [])
    {
        if (empty($this->_template_dir) || !is_dir($this->_template_dir)) {
            Lib_Log::error(
                __METHOD__.'please set correct template dir and check is a dir: '
                .var_export($template, true));
            exit(0);
        }
        echo $this->_render_template($template, $var);
        exit(0);
    }

    /**
     * @param array $data
     * @param string $format
     */
    function output($data, $format = 'json')
    {
        header("Content-Type: application/json");
        switch ($format) {
            case 'json':
            default:
                $data = json_encode($data);
                break;
        }
        echo $data;exit(0);
    }

    /**
     * @param array $template
     * @return bool|string
     */
    public function get_template_file($template)
    {
        $template_file = $this->_template_dir.'/'.$template.'.phtml';
        if (is_file($template_file)) {
            return $template_file;
        } else {
            Lib_Log::error('teplate file ['.$template_file.'] not exists!');
            return false;
        }
    }

    /**
     * @param array $vars
     * @return $this
     */
    public function assign_global_vars(array $vars)
    {
        $this->_vars = $vars;
        return $this;
    }

    /**
     * @param array $template
     * @param array $var
     * @return string
     */
    protected function _render_template($template, array $var = [])
    {
        $template_file = $this->get_template_file($template);
        if (empty($template_file)) {
            return '';
        }
        $var = array_merge($var, $this->_vars);
        extract($var);
        //ob_end_flush();
        ob_end_clean();
        ob_start();
        require $template_file;
        $content = ob_get_contents();
        ob_end_clean();
        ob_start();
        return $content;
    }
}