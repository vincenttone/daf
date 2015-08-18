<?php
class Module_FlowManager_Interface
{
    /**
     * @param int $flow_id
     * @return array|bool
     */
    static function gen_cli_by_flow_id($flow_id)
    {
        $all_fields = self::_get_fields_by_flow_id($flow_id);
        if ($all_fields['errno'] !== Const_Err_Base::ERR_OK) {
            return false;
        }
        $all_fields = $all_fields['data'];
        if (empty($all_fields)) {
            return false;
        }
        $main_fields = $all_fields[Const_Interface::FIELDS_KEY_FIELDS];
        $relation_fields = [];
        isset($all_fields[Const_Interface::FIELDS_KEY_RELATIONS])
            && $relation_fields = $all_fields[Const_Interface::FIELDS_KEY_RELATIONS];
        $result = [];
        foreach ($main_fields as $_f) {
            $result = array_merge($result, self::_repl_to_get_info($_f, $relation_fields));
        }
        $result[Module_FlowManager_Main::KEY_FLOW_ID] = $flow_id;
        return $result;
    }

    /**
     * @param int $flow_id
     * @return array
     */
    static function get_fields_by_flow_id($flow_id)
    {
        return self::_get_fields_by_flow_id($flow_id);
    }

    /**
     * @param array $fields
     * @param array $relation_fields
     * @return array
     */
    private static function  _repl_to_get_info($fields, $relation_fields = [])
    {
        $result = [];
        $name = $fields[Const_Interface::FIELD_ATTR_NAME];
        $type = $fields[Const_Interface::FIELD_ATTR_TYPE];
        $module = $fields[Const_Interface::FIELD_ATTR_MODULE];
        $key = $fields[Const_Interface::FIELD_ATTR_KEY];
        $default = isset($fields[Const_Interface::FIELD_ATTR_DEFAULT]) ?
            $fields[Const_Interface::FIELD_ATTR_DEFAULT] : '';
        $id = $key;//strval($module).self::ID_SEPARATOR.$key;
        $repl = function ($output, $default = null) {
            echo $output;
            echo PHP_EOL;
            $get_data = trim(fgets(STDIN));
            if ($get_data === '') {
                $get_data = $default;
            }
            return $get_data;
        };
        switch ($type) {
            case Const_Interface::NODE_TYPE_INPUT:
                $output = $name.':';
                $read_data = '';
                do {
                    $read_data = $repl($output, '');
                } while (empty($read_data));
                //$data[Const_Interface::FIELD_ATTR_VALUE] = $read_data;
                $result[$id] = $read_data;
                break;
            case Const_Interface::NODE_TYPE_SELECT:
                $value = $fields[Const_Interface::FIELD_ATTR_VALUE];
                $output = [];
                foreach ($value as $__k => $__v) {
                    if (is_array($__v)) {
                        $__v = $__v[Const_Interface::FIELD_ATTR_VALUE];
                    }
                    $output[] = ''.strval($__k).":\t[".$__v.']';
                }
                $output = $name . ' 可选项：'
                    . PHP_EOL
                    . implode(PHP_EOL, $output)
                    . PHP_EOL.'默认: '.$default;
                $get_value = null;
                do {
                    $read_data = $repl($output, $default);
                    isset($value[$read_data])
                        && $get_value = $read_data;
                } while (is_null($get_value));
                //$data[Const_Interface::FIELD_ATTR_VALUE] = $get_value;
                $result[$id] = $get_value;
                if (
                    is_array($value[$get_value])
                    && isset($value[$get_value][Const_Interface::FIELD_ATTR_RELATION])
                ) {
                    $rel_fields = $value[$get_value][Const_Interface::FIELD_ATTR_RELATION];
                    foreach ($rel_fields as $_rf) {
                        foreach ($relation_fields as $_rt) {
                            if (
                                $_rt[Const_Interface::FIELD_ATTR_KEY] === $_rf[Const_Interface::FIELD_ATTR_KEY]
                                && $_rt[Const_Interface::FIELD_ATTR_MODULE] === $_rf[Const_Interface::FIELD_ATTR_MODULE]
                            ) {
                                $result = array_merge($result, self::_repl_to_get_info($_rt));
                            }
                        }
                    }
                }
                break;
            case Const_Interface::NODE_TYPE_MINPUT:
                is_array($default) && $default = implode('|', $default);
                $output = '请输入 '.$name.' (多字段请用|分隔, 默认 -> '.$default.'):';
                $read_data = '';
                do {
                    $read_data = trim($repl($output, $default), '|');
                } while ($read_data === '');
                $get_data = preg_split('/\s*\|+\s*/', $read_data);
                //$data[Const_Interface::FIELD_ATTR_VALUE] = $get_data;
                $result[$id] = $get_data;
                break;
            case Const_Interface::NODE_TYPE_RSELECT:
                $value = $fields[Const_Interface::FIELD_ATTR_VALUE];
                $parent_value = array_keys($value);

                $select_it = function ($value, $name, $default) use ($repl) {
                    $output = [];
                    foreach ($value as $__k => $__v) {
                        if (is_array($__v)) {
                            $__v = $__v[Const_Interface::FIELD_ATTR_VALUE];
                        }
                        $output[] = ''.strval($__k).":\t[".$__v.']';
                    }
                    $output = $name . ' 可选项：'
                    . PHP_EOL
                    . implode(PHP_EOL, $output)
                    . PHP_EOL.'默认: '.$default;
                    $get_value = null;
                    do {
                        $read_data = $repl($output, $default);
                        isset($value[$read_data])
                            && $get_value = $read_data;
                    } while (is_null($get_value));
                    return $read_data;
                };
                $read_data = $select_it($parent_value, $name, 0);
                //$data[Const_Interface::FIELD_ATTR_VALUE] = $get_value;
                $p_k = $parent_value[$read_data];
                $data = $value[$p_k];
                $result[$id] = $select_it($data, $name, '');
                break;
        }
    
        return $result;
    }

    /**
     * @param int $flow_id
     * @return array
     */
    private static function _get_fields_by_flow_id($flow_id)
    {
        $flow = new Module_FlowManager_Flow();
        $flow->id = $flow_id;
        $flow_info = $flow->get();
        if (!$flow_info) {
            return Lib_Helper::get_err_struct(
                Const_Err_DataAccess::ERR_FLOW_NOT_EXISTS,
                'no this flow, flow_id: '.$flow_id
            );
        }
        $fields = Module_ModuleManager_InterfaceGen::get_instance()->gen_fields($flow);
        return Lib_Helper::get_return_struct($fields);
    }
}
