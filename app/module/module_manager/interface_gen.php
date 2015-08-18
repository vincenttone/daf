<?php
/**
 * @name:   交互生成
 * @brief:	根据使用的模块生成交互信息
 * @author: vincent
 * @create:	2014-5-13
 * @update:	2014-5-13
 *
 * @type:	system
 */
class Module_ModuleManager_InterfaceGen
{
    const FIELD_ID_PREFIX = 'apf_';

    private static $_instance = null;
    private $_fields = [];

    private $_interface_info = [];
    private $_current_flow_id = null;
    // interface info keys
    const IIK_FLOW_MODULES = 'flow_modules';
    const IIK_FLOW_ID = 'flow_id';

    private function __construct()
    {
    }
    /**
     * 禁用对象克隆
     */
    private function __clone()
    {
        throw new Exception("Could not clone the object from class: ".__CLASS__);
    }
    /**
     * 获取实例的方法
     * @return $this
     */
    static public function get_instance()
    {
        if (is_null(self::$_instance)) {
            $class = __CLASS__;
            self::$_instance = new $class;
        }
        return self::$_instance;
    }

    /**
     * @return array
     */
    function get_registered_fields()
    {
        if (empty($this->_fields)) {
            $modules = Module_ModuleManager_Register::get_instance()
                ->get_registered_modules();
            foreach ($modules as $_k => $_m) {
                $type = $_m[Const_Module::META_TYPE];
                if (strcmp($type, Const_Module::TYPE_ABSTRACT) === 0) {
                    continue;
                }
                $register = isset($_m[Const_Module::META_REGISTER])
                    ? $_m[Const_Module::META_REGISTER] : [];
                in_array(Const_Module::REGISTER_FIELDS, $register)
                    && $this->_register_module_fields($_m);
            }
        }
        return $this->_fields;
    }

    /**
     * @param array $info
     * @return bool
     */
    function _register_module_fields($info)
    {
        $class = $info[Model_ModulesInfo::FIELD_MODULE_CLASS];
        $indent = $info[Model_ModulesInfo::FIELD_MODULE_INDENT];
        $id = $info[Model_ModulesInfo::FIELD_MODULE_ID];
        if (method_exists($class, 'register_fields')) {
            $_filed = $class::register_fields();
            if (!empty($_filed)) {
                $this->_fields[$id] = $_filed;
                return $this->_fields[$id];
            }
        }
        return false;
    }

    /**
     * @param array $flow
     * @return array
     */
    function gen_fields($flow)
    {
        $sets = [];
        $flow_modules = $flow->modules;
        $this->_current_flow_id = $flow->id;
        $this->_interface_info[$this->_current_flow_id] = [
            self::IIK_FLOW_MODULES => $flow_modules
        ];
        $fields = $this->get_registered_fields();
        foreach ($fields as $_indent => $_values) {
            if (in_array($_indent, $flow_modules)) {
                $field_sets = $this->_format_all_fields($_indent, $_values);
                foreach ($field_sets as $_k => $_s) {
                    isset($sets[$_k]) || $sets[$_k] = [];
                    $sets[$_k] = array_merge($sets[$_k], $_s);
                }
            }
        }
        return $sets;
    }

    /**
     * @param string $indent
     * @param array $data
     * @return array
     */
    private function _format_all_fields($indent, $data)
    {
        $formated_fields = [
            Const_Interface::FIELDS_KEY_FIELDS =>[],
            Const_Interface::FIELDS_KEY_RELATIONS =>[]
        ];
        foreach ($data as $_k => $_d) {
            switch ($_k) {
                case Const_Interface::FIELDS_KEY_FIELDS:
                    foreach ($_d as $__name => $__data) {
                        $formated_fields[Const_Interface::FIELDS_KEY_FIELDS][]
                            = self::_format_field($indent, $__name, $__data);
                    }
                    break;
                case Const_Interface::FIELDS_KEY_SAME_AS:
                    $formated_fields[Const_Interface::FIELDS_KEY_FIELDS]
                        = array_merge(
                            $formated_fields[Const_Interface::FIELDS_KEY_FIELDS],
                            $this->_get_same_fields_of_other_module($_d)
                        );
                    break;
                case Const_Interface::FIELDS_KEY_RELATIONS:
                    foreach ($_d as $__name => $__data) {
                        $formated_fields[Const_Interface::FIELDS_KEY_RELATIONS][]
                            = self::_format_field($indent, $__name, $__data);
                    }
                    break;
            }
        }
        return $formated_fields;
    }

    /**
     * @param array $module_data
     * @return array
     */
    private function _get_same_fields_of_other_module($module_data)
    {
        $other_module_fields = [];
        $interface_info = $this->_interface_info[$this->_current_flow_id];
        $flow_modules = $interface_info[self::IIK_FLOW_MODULES];
        foreach ($module_data as $_field_name => $_module_name) {
            //查看此次会不会有这个module载入，有则跳过，没有则取出来
            if (in_array($_module_name, $flow_modules)) {
                Lib_Log::DEBUG('Module %s would be loaded', $_module_name);
                continue;
            } else {
                $fields = $this->get_registered_fields();
                if (!isset($fields[$_module_name])) {
                    Lib_Log::WARN('No such module: %s', [$_module_name]);
                    continue;
                }
                if (!isset($fields[$_module_name][Const_Interface::FIELDS_KEY_FIELDS])) {
                    Lib_Log::WARN('No such fields in module: [%s]', [$_module_name]);
                    continue;
                }
                $values_in_field = $fields[$_module_name][Const_Interface::FIELDS_KEY_FIELDS];
                if (!isset($values_in_field[$_field_name])) {
                    Lib_Log::WARN('No such field [%s] in module: [%s]', [$_field_name, $_module_name]);
                    continue;
                }
                $field_data = $values_in_field[$_field_name];
                $other_module_fields[] = self::_format_field($_module_name, $_field_name ,$field_data);
            }
        }
        return $other_module_fields;
    }

    /**
     * @param string $indent
     * @param string $name
     * @param array $content
     * @return array
     */
    private static function _format_field($indent, $name, $content)
    {
        $type = Lib_Helper::get_data_from_array(
            $content,
            Const_Interface::FIELD_ATTR_TYPE,
            Const_Interface::NODE_TYPE_UNKNOW
        );
        $label = $content[Const_Interface::FIELD_ATTR_NAME];
        $is_global = isset($content[Const_Interface::FIELD_ATTR_IS_GLOBAL])
            && $content[Const_Interface::FIELD_ATTR_IS_GLOBAL]
            ? true : false;
        $desc = isset($content[Const_Interface::FIELD_ATTR_DESC])
            ? $content[Const_Interface::FIELD_ATTR_DESC] : '';
        $weight = isset($content[Const_Interface::FIELD_ATTR_WEIGHT])
            ? $content[Const_Interface::FIELD_ATTR_WEIGHT] : 100;
        $set = [
            Const_Interface::FIELD_ATTR_KEY => $name,
            Const_Interface::FIELD_ATTR_MODULE => $indent,
            Const_Interface::FIELD_ATTR_NAME => $label,
            Const_Interface::FIELD_ATTR_TYPE => $type,
            Const_Interface::FIELD_ATTR_IS_GLOBAL => $is_global,
            Const_Interface::FIELD_ATTR_DESC => $desc,
            Const_Interface::FIELD_ATTR_WEIGHT => $weight,
        ];
        switch ($type) {
            case Const_Interface::NODE_TYPE_INPUT:
                $set[Const_Interface::FIELD_ATTR_PLACEHOLDER]
                    = Lib_Helper::get_data_from_array(
                        $content,
                        Const_Interface::FIELD_ATTR_PLACEHOLDER,
                        $label
                    );
                $set[Const_Interface::FIELD_ATTR_DEFAULT]
                    = Lib_Helper::get_data_from_array(
                        $content,
                        Const_Interface::FIELD_ATTR_DEFAULT,
                        ''
                    );
                break;
            case Const_Interface::NODE_TYPE_SELECT:
            case Const_Interface::NODE_TYPE_RSELECT:
                $value = $content[Const_Interface::FIELD_ATTR_DATA];
                $set[Const_Interface::FIELD_ATTR_VALUE]
                    = self::_format_field_value($indent, $value);
                $set[Const_Interface::FIELD_ATTR_DEFAULT]
                    = Lib_Helper::get_data_from_array(
                        $content,
                        Const_Interface::FIELD_ATTR_DEFAULT,
                        array_keys($value)[0]
                    );
                break;
            case Const_Interface::NODE_TYPE_MINPUT:
                $set[Const_Interface::FIELD_ATTR_DEFAULT]
                    = Lib_Helper::get_data_from_array(
                        $content,
                        Const_Interface::FIELD_ATTR_DEFAULT,
                        []
                    );
                break;
            default:
                $set = [];
        }
        return $set;
    }

    /**
     * @param int $id
     * @param string $value
     * @return array
     */
    private static function _format_field_value($id, $value)
    {
        if (!is_array($value)) {
            Lib_Log::debug('filed values is not array, values: %s', json_encode($value));
            return [];
        }
        foreach ($value as $_k => $_v) {
            if (is_array($_v) && isset($_v[Const_Interface::FIELD_ATTR_RELATION])) {
                $new_value = [];
                if (!is_array($_v[Const_Interface::FIELD_ATTR_RELATION])) {
                    Lib_Log::error('Fields error, please check relaction fields :'.json_encode($_v).' in an array');
                    continue;
                }
                foreach ($_v[Const_Interface::FIELD_ATTR_RELATION] as $__value) {
                    $new_value[] = [
                        Const_Interface::FIELD_ATTR_KEY => $__value,
                        Const_Interface::FIELD_ATTR_MODULE => $id,
                     ];
                }
                $value[$_k][Const_Interface::FIELD_ATTR_RELATION] = $new_value;
            }
        }
        return $value;
    }

    /**
     * @param array $field
     * @return string
     */
    static function gen_radio($field)
    {
        $values = $field['value'];
        $default = $field['default'];
        $key = $field['key'];
        $html = '<div id="'.self::FIELD_ID_PREFIX.$key.'">';
        foreach ($values as $_k => $_v) {
            $relation = '';
            $label_cls = '';
            if(is_array($_v)) {
                $relation_fields = [];
                foreach ($_v[Const_Interface::FIELD_ATTR_RELATION] as $_r) {
                    $relation_fields[] = self::FIELD_ID_PREFIX.$_r[Const_Interface::FIELD_ATTR_KEY];
                }
                $relation .= ' apf-rel="'.implode(',', $relation_fields).'"';
                $relation .= ' class="apfrel"';
                $label_cls .= ' ap-label-rel';
                $_v = $_v['value'];
            }
            $checked = $default == $_k? ' checked' : '';
            $html .= '<label class="radio-inline'.$label_cls.'">';
            $html .= '<input type="radio" name="'.$key.'" value="'.$_k.'"'.$checked.$relation;
            $html .= ' id="'.self::FIELD_ID_PREFIX.$key.'_'.$_k.'"/>';
            $html .= $_v;
            $html .= '</label>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * @param array $field
     * @return string
     */
    static function gen_input($field)
    {
        $key = $field['key'];
        $html = '<input type="text" name="'.$key.'" class="form-control" id="'.self::FIELD_ID_PREFIX.$key.'"'; 
        isset($field[Const_Interface::FIELD_ATTR_PLACEHOLDER])
            && $html .= ' placeholder="'.$field[Const_Interface::FIELD_ATTR_PLACEHOLDER].'" ' ;
        isset($field[Const_Interface::FIELD_ATTR_DEFAULT])
            && $html .= ' value="'.$field[Const_Interface::FIELD_ATTR_DEFAULT].'" ' ;
        $html .= '/>';
        return $html;
    }

    /**
     * @param array $field
     * @return string
     */
    static function gen_select($field)
    {
        $values = $field['value'];
        $default = $field['default'];
        $key = $field['key'];
        $html = '<select name="'.$key.'" class="form-control" id="'.self::FIELD_ID_PREFIX.$key.'">';
        foreach ($values as $_k => $_v) {
            $relation = '';
            if (is_array($_v)) {
                $relation_fields = [];
                foreach ($_v[Const_Interface::FIELD_ATTR_RELATION] as $_r) {
                    $relation_fields[] = self::FIELD_ID_PREFIX.$_r[Const_Interface::FIELD_ATTR_KEY];
                }
                $relation .= ' apf-rel="'.implode(',', $relation_fields).'"';
                $relation .= ' class="apfrel"';
                $_v = $_v['value'];
            }
            $selected = $default==$_k? ' selected="selected"':'';
            $html .= '<option value="'.$_k.'"'.$selected.$relation.' id="'.self::FIELD_ID_PREFIX.$key.'_'.$_k.'">'.$_v.'</option>';
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * @param array $field
     * @return string
     */
    static function gen_rselect($field)
    {
        $values = $field['value'];
        $default = isset($field['default']) ? $field['default'] : '';
        $key = $field['key'];
        $sub_fields = [];
        $sub_fields['default'] = $default;
        $sub_fields['fields'] = $values;
        $html = '<select class="form-control have_sub_select rselect" sub_name="'.$key.'" id="'.self::FIELD_ID_PREFIX.$key.'" sub_fields="';
        $html .= htmlspecialchars(json_encode($sub_fields)). '">';
        foreach ($values as $k=>$v) {
            $html .= '<option value="'.$k.'"';
            if (!empty($v) && is_array($v)) {
                foreach ($v as $srcid=>$src) {
                    $default==$srcid && $html .= 'selected="selected"';
                }
            }
            $html .= '>'.$k.'</option>';
        }
        $html .= '</select>';
        $html .= '<div class="ap-rselect-subnode"></div>';
        return $html;
    }

    /**
     * @param array $field
     * @return string
     */
    static function gen_minput($field)
    {
        $default = $field['default'];
        $key = $field['key'];
        $html = '<div  id="'.self::FIELD_ID_PREFIX.$key.'">';
        if (!empty($default) && is_array($default)) {
            foreach ($default as $k=>$v) {
                $html .= '<div class="col-sm-3">';
                $html .= '<input type="text" name="'.$key.'[]" class="form-control minput" value="'.$v.'" />';
                $html .= '</div>';
            }
        } else {
            $html .= '<div class="col-sm-3"><input type="text" name="'.$key.'[]" class="form-control minput" /></div>';
        }
        $html .= '<span onclick="input_plus($(this), \''.$key.'[]\')" class="glyphicon glyphicon-plus cursor-pointer" title="增加"></span>&nbsp;';
        $html .= '<span onclick="input_minus($(this))" class="glyphicon glyphicon-minus cursor-pointer" title="删除"></span>';
        $html .= '</div>';
        return $html;
    }
}