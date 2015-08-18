<?php
class Module_ControlCentre_ApManager
{
    use singleton_with_get_instance;

    private $_current_ap_id = null;
    private $_current_ap = null;
    private $_ap_list = [];

    /**
     * @param int $id
     * @return $this|null
     */
    function set_current_ap($id)
    {
        if (isset($this->_ap_list[$id])) {
            $this->_current_ap_id = $id;
            $this->_current_ap = $this->_ap_list[$id];
        } else {
            $ap_info = Module_AccessPoint_Ap::get_ap($id);
            if ($ap_info['errno'] !== Const_Err_Base::ERR_OK) {
                Lib_Log::notice('Get ap faild! Result: %s', Lib_Helper::format_err_struct($ap_info));
                $this->_current_ap_id = null;
                $this->_current_ap = null;
                return null;
            }
            $ap_info = $ap_info['data'];
            $this->set_current_ap_info($id, $ap_info);
        }
        return $this;
    }

    /**
     * @param int $id
     * @param array $ap_info
     * @return $this
     */
    function set_current_ap_info($id, $ap_info)
    {
        $this->_current_ap_id = $id;
        $this->_current_ap = $ap_info;
        $this->_ap_list[$id] = $ap_info;
        return $this;
    }

    /**
     * @param int $id
     * @return $this|null
     */
    function select_ap($id)
    {
        if (!isset($this->_ap_list[$id])) {
            return null;
        }
        $this->_current_ap_id = $id;
        $this->_current_ap = $this->_ap_list[$id];
        return $this;
    }

    /**
     * @return null
     */
    function get_current_ap_status()
    {
        $current_ap = $this->get_current_ap();
        if (
            is_array($current_ap)
            &&
            isset($current_ap[Module_AccessPoint_Main::FIELD_AP_STATUS])) {
            return $current_ap[Module_AccessPoint_Main::FIELD_AP_STATUS];
        }
        return null;
    }

    /**
     * @return int
     */
    function get_current_id()
    {
        return $this->_current_ap_id;
    }

    /**
     * @return array
     */
    function get_current_ap()
    {
        return $this->_current_ap;
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
    static function current_ap()
    {
        return self::get_instance()->get_current_ap();
    }

    /**
     * @param bool $all_people
     * @return array
     */
    static function interface_people($all_people = true)
    {
        $ap = self::current_ap();
        $people = Lib_Helper::check_and_explode_array_val(
            ';',
            $ap,
            Module_ScheduledTask_Main::FIELD_CALL_MAN,
            [],
            " \t;"
        );
        if ($all_people) {
            $interface_people = Lib_Helper::check_and_explode_array_val(
                ';',
                $ap,
                Module_AccessPoint_Main::FIELD_INTERFACE_PEOPLE,
                [],
                " \t;"
            );
            $people = array_merge($people, $interface_people);
        }
        $admins = Da\Sys_Config::config('account/users');
        $_p = [];
        empty($admins) || $_p = array_keys($admins);
        $people = array_merge($people, $_p);
        $people = array_unique($people);
        return $people;
    }
}