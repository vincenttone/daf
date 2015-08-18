<?php
class Module_ControlCentre_FlowManager
{
    use singleton_with_get_instance;

    private $_current_id = null;
    private $_current_flow = null;
    private $_flow_list = [];

    /**
     * @param int $id
     * @param array $custom_flow
     * @param array $custom_options
     * @return $this|null
     */
    function set_current_flow($id, $custom_flow = null, $custom_options = null)
    {
        if (isset($this->_flow_list[$id])) {
            $this->_current_id = $id;
            $this->_current_flow = $this->_flow_list[$id];
        } else {
            $flow = new Module_FlowManager_Flow();
            $flow->id = $id;
            $get_flow = $flow->get();
            if ($get_flow === false) {
                $this->_current_id = null;
                $this->_current_flow = null;
                return null;
            }
            $this->_current_id = $id;
            $this->_current_flow = $flow;
        }
        $this->custom_flow($custom_flow);
        $this->custom_options($custom_options);
        return $this;
    }

    /**
     * @param array $mode
     * @return $this
     */
    function set_run_mode($mode)
    {
        $this->_current_flow->set_run_mode($mode);
        return $this;
    }

    /**
     * @param array $custom_flow
     * @return $this
     */
    function custom_flow($custom_flow)
    {
        if (!empty($custom_flow)) {
            $this->_current_flow->custom_flow($custom_flow);
        }
        return $this;
    }

    /**
     * @param array $custom_options
     * @return $this
     */
    function custom_options($custom_options)
    {
        if (!empty($custom_options)) {
            $this->_current_flow->options = $custom_options;
        }
        return $this;
    }

    /**
     * @return int
     */
    function get_current_id()
    {
        return $this->_current_id;
    }

    /**
     * @return array
     */
    function get_current_flow()
    {
        return $this->_current_flow;
    }

    /**
     * @return int
     */
    static function current_id()
    {
        return self::get_instance()->get_current_id();
    }

    /**
     * @return array
     */
    static function current_flow()
    {
        return self::get_instance()->get_current_flow();
    }
}