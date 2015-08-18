<?php
/**
 * @name:	功能性基础模块
 * @brief:	主要用于提供一些简单方便发方法和继承使用
 * @author: vincent
 * @create:	2014-5-12
 * @update:	2014-5-12
 *
 * @type:	abstract
 */
abstract class Module_Base_Functional extends Module_Base_Main
{
    protected $_options = [];
    protected $_callback = null;

    /**
     * @return string
     */
    static function module_type()
    {
        return Const_Module::TYPE_FUNCTIONAL;
    }
    /**
     * 启动入口方法
     * @param int $task_id 任务ID，每个模块都该有个任务ID
     * @param array $data 此次任务传递的数据，可选字段
     */
    abstract function run($task_id, $data=[]);
    /**
     * static function register_fields(); 
     * 用于设置可配置项
     * input 文本输入数据
     * select 多选一
     * options 多选多
     * return 格式：
     *  array:[
     *        '选项名' => [
     *          'name' => '名称'
     *          'type' => input|select|options ...
     *          'data' => ['f1' => 'apple', 'f2' => 'banana', 'f3' => 'milk']
     *        ],
     *        ...
     *      ]
     *
     */

    /**
     * @param array $options
     * @return array $this
     */
    function set_options($options)
    {
        $this->_options = array_merge($options, $this->_options);
        return $this;
    }

    /**
     * @return array
     */
    function get_options()
    {
        return $this->_options;
    }

    /**
     * @param string $key
     * @return string
     */
    static function current_ap_info($key = null)
    {
        $ap = Module_ControlCentre_ApManager::get_instance()->current_ap();
        return self::_get_data_info($ap, $key);
    }

    /**
     * @param string $key
     * @return string
     */
    function get_ap_info($key = null)
    {
        return self::current_ap_info($key);
    }

    /**
     * @param string $key
     * @return string
     */
    static function current_flow_info($key = null)
    {
        $flow = Module_ControlCentre_FlowManager::get_instance()->current_flow();
        $flow_info = $flow->options;
        return self::_get_data_info($flow_info, $key);
    }

    /**
     * @param string $key
     * @return string
     */
    function get_flow_info($key = null)
    {
        return self::current_flow_info($key);
    }

    /**
     * @param string $key
     * @return string
     */
    static function current_task_info($key = null)
    {
        $task = Module_ControlCentre_Main::get_instance()->current_task();
        $task_info = [
            'task_id' => $task->id,
            'create_time' => $task->create_time,
        ];
        return self::_get_data_info($task_info, $key);
    }

    /**
     * @param string $key
     * @return string
     */
    function get_task_info($key = null)
    {
        return self::current_task_info($key);
    }

    /**
     * @param array $data
     * @param string $key
     * @return string
     */
    private static function _get_data_info($data, $key = null)
    {
        if (empty($key)) {
            return $data;
        }
        return isset($data[$key])
            ? $data[$key]
            : null;
    }

    /**
     * module允许增加一个callback进行扩展
     * 一般在一些特殊模式下使用，如loop callback
     * @param $callback
     * @return $this
     */
    function register_callback($callback)
    {
        if ($callback) {
            $this->_callback = $callback;
        }
        return $this;
    }

    /**
     * @param array $data
     * @param null|array $ctl_cmd
     * @return array
     */
    function do_callback($data, $ctl_cmd = null)
    {
        if (is_array($ctl_cmd)) {
            Module_ControlCentre_Main::current_task()
                ->add_ctl_cmds($ctl_cmd);
        }
        if ($this->_callback) {
            return call_user_func($this->_callback, $data);
        }
        return $data;
    }

    /**
     * @param string $key
     * @return array
     */
    static function task_ctl_cmd($key)
    {
        return Module_ControlCentre_Main::current_task()
            ->get_ctl_cmd($key);
    }
}