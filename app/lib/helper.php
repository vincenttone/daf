<?php
class Lib_Helper
{
    /**
     * @param $data
     * @param $key
     * @param string $default
     * @return string
     */
    static function get_data_from_array($data, $key, $default = '')
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }

    /**
     * @param $data
     * @param $key
     * @param string $default
     * @param bool $conv2int
     * @return int|string
     */
    static function get_arr_val($data, $key, $default='', $conv2int=false)
    {
        $val = array_key_exists($key, $data) ? $data[$key] : $default;
        if ($conv2int) {
            return intval($val);
        }
        return $val;
    }

    /**
     * @param $array
     * @param $key
     * @param $default
     */
    static function update_array_empty_val(&$array, $key, $default)
    {
        array_key_exists($key ,$array)
            && empty($array[$key])
            && $array[$key] = $default;
    }

    /**
     * @param $array
     * @param $origin_key
     * @param $new_key
     */
    static function change_array_data_key(&$array, $origin_key, $new_key)
    {
        if (array_key_exists($origin_key, $array)) {
            $array[$new_key] = $array[$origin_key];
            unset($array[$origin_key]);
        }
    }

    /**
     * @param $array
     * @param string $group_sep
     * @param string $kv_sep
     * @return string
     */
    static function join_array_key_and_val($array, $group_sep = ', ', $kv_sep = ': ')
    {
        $arr = [];
        foreach ($array as $_k => $_v) {
            $arr[] = strval($_k).$kv_sep.$_v;
        }
        return implode($group_sep, $arr);
    }

    /**
     * @param $array
     * @param string $group_sep
     * @param string $kv_sep
     * @param string $key_wraper
     * @param string $val_wraper
     * @return string
     */
    static function join_and_wrap_array_key_and_val(
        $array,
        $group_sep = ', ',
        $kv_sep = ': ',
        $key_wraper = '',
        $val_wraper = ''
    )
    {
        $arr = [];
        foreach ($array as $_k => $_v) {
            $arr[] = $key_wraper.strval($_k).$key_wraper
                .$kv_sep
                .$val_wraper.$_v.$val_wraper;
        }
        return implode($group_sep, $arr);
    }

    /**
     * @param $delimiter
     * @param $array
     * @param $key
     * @param $default
     * @param null $trim
     * @return array
     */
    static function check_and_explode_array_val($delimiter, $array, $key, $default, $trim = null)
    {
        $data = isset($array[$key])
            ? (
                $trim === null
                ? $array[$key]
                : trim($array[$key], $trim)
            )
            : null;
        return $data === null
            ? $default
            : explode($delimiter, $data);
    }

    /**
     * @param $errno
     * @param $msg
     * @param null $file
     * @param null $line
     * @return array
     */
    static function get_err_struct($errno, $msg, $file = null, $line = null)
    {
        if (is_null($file) || is_null($line)) {
            $file = '';
            $line = '';
            $trace = debug_backtrace();
            if (isset($trace[0])) {
                isset($trace[0]['file']) && $file = $trace[0]['file'];
                isset($trace[0]['line']) && $line = $trace[0]['line'];
            }
        }
        return self::get_stat_and_err_struct(null, $errno, $msg, $file, $line);
    }

    /**
     * @param $stat
     * @param $errno
     * @param $msg
     * @param null $file
     * @param null $line
     * @return array
     */
    static function get_stat_and_err_struct($stat, $errno, $msg, $file = null, $line = null)
    {
        if (is_null($file) || is_null($line)) {
            $file = '';
            $line = '';
            $trace = debug_backtrace();
            if (isset($trace[0])) {
                isset($trace[0]['file']) && $file = $trace[0]['file'];
                isset($trace[0]['line']) && $line = $trace[0]['line'];
            }
        }
        $result = [
            Const_DataAccess::MREK_ERRNO => $errno,
            Const_DataAccess::MREK_DATA => [
                'msg' => $msg,
                'file' => $file,
                'line' => $line,
            ],
        ];
        $stat === null || $result[Const_DataAccess::MREK_STATUS] = $stat;
        return $result;
    }

    /**
     * @param $errno
     * @param $msg
     * @return array
     */
    static function common_err_struct($errno, $msg)
    {
        return [Const_DataAccess::MREK_ERRNO => $errno, Const_DataAccess::MREK_DATA => $msg];
    }

    /**
     * @param $err_struct
     * @return string
     */
    static function format_err_struct($err_struct)
    {
        $str = vsprintf(
            "errno:[".$err_struct[Const_DataAccess::MREK_ERRNO]."], msg:[%s], file:[%s], line:[%s]",
            $err_struct[Const_DataAccess::MREK_DATA]
        );
        return $str;
    }

    /**
     * @param $data
     * @return array
     */
    static function get_return_struct($data)
    {
        return [
            Const_DataAccess::MREK_ERRNO => Const_Err_Base::ERR_OK,
            Const_DataAccess::MREK_DATA => $data,
        ];
    }

    /**
     * @param $stat
     * @param $errno
     * @param $msg
     * @param $return_struct
     * @param null $file
     * @param null $line
     * @return array
     */
    static function switch_to_stat_err_struct($stat, $errno, $msg, $return_struct, $file = null, $line = null)
    {
        if (is_null($file) || is_null($line)) {
            $file = '';
            $line = '';
            $trace = debug_backtrace();
            if (isset($trace[0])) {
                isset($trace[0]['file']) && $file = $trace[0]['file'];
                isset($trace[0]['line']) && $line = $trace[0]['line'];
            }
        }
        $err_struct = self::get_stat_and_err_struct($stat, $errno, $msg, $file, $line);
        $err_struct[Const_DataAccess::MREK_META] = $return_struct[Const_DataAccess::MREK_DATA];
        return $err_struct;
    }

    /**
     * @param $size
     * @param string $format_str
     * @return string
     */
    static function format_size_to_str($size, $format_str = '%.2f')
    {
        $measure = 'B';
        $base = '1';
        if ($size > 1000000000000) {
            $base = 1000000000000;
            $measure = 'T';
        } elseif ($size > 1000000000) {
            $base = 1000000000;
            $measure = 'G';
        } elseif ($size > 1000000) {
            $base = 1000000;
            $measure = 'M';
        } elseif ($size > 1000) {
            $base = 1000;
            $measure = 'K';
        } else {
            $base = 1;
            $measure = 'B';
        }
        $size = sprintf($format_str.$measure, $size / $base);
        return $size;
    }

    /**
     * str_equal('str1', 'str2', 'str3', ...)
     * 只要有一个和str1相等就返回TRUE
     * @return bool
     */
    static function str_equal()
    {
        $args = func_get_args();
        if (!isset($args[1])) {
            return false;
        }
        if (!isset($args[2])) {
            if (strcmp($args[0], $args[1]) === 0) {
                return true;
            } else {
                return false;
            }
        }
        $str1 = array_shift($args);
        foreach ($args as $_arg) {
            if (strcmp($str1, $_arg) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $size
     * @return string
     */
    static function legible_size($size)
    {
        $get_val = function ($val) {
            return strval(sprintf("%.3f", $val));
        };
        $k_base = 1024 * 1024;
        if ($size < $k_base) {
            if ($size < 1024) {
                return strval($size).'B';
            } else {
                return $get_val($size/$k_base).'KB';
            }
        } else {
            $m_base = $k_base * 1024;
            $g_base = $m_base * 1024;
            if ($size < $m_base) {
                return $get_val($size / $k_base).'MB';
            } elseif ($size < $g_base) {
                return $get_val($size / $m_base).'GB';
            } else {
                return $get_val($size / $g_base).'TB';
            }
        }
    }

    /**
     * @param $time
     * @return string
     */
    static function legible_time($time)
    {
        $get_val = function ($val) {
            return strval(sprintf("%.4f", $val));
        };
        $hour_base = 3600;
        if ($time < $hour_base) {
            if ($time < 60) {
                return $get_val($time).'s';
            } else {
                return $get_val($time/60).'min';
            }
        } else {
            $day_base = $hour_base * 24;
            if ($time < $day_base) {
                return $get_val($time / $hour_base).'hour';
            } else {
                return $get_val($time / $day_base).'day';
            }
        }
    }

    /**
     * @param $array
     * @param $keys
     * @param bool $get_str
     * @return string
     */
    static function get_values_from_array_by_keys($array, $keys, $get_str = false)
    {
        $str = '';
        foreach ($array as $_k => $_v) {
            if (!in_array($_k, $keys)) {
                unset($array[$_k]);
                continue;
            }
            if ($get_str === true) {
                is_string($_v) && $str .= $_k.': '.$_v.' ';
            } elseif (is_string($get_str)) {
                is_string($_v) && $str .= sprintf($get_str, [$_k, $_v]);
            }
        }
        return $get_str ? $str : $array;
    }

    /**
     * @param $id
     * @return bool
     */
    static function CheckUint64($id) {
        $MAXUINT64 = '18446744073709551615';

        if(!preg_match("/^\d+$/", $id)){
            return false;
        }
        if(bccomp($id, $MAXUINT64) > 0){
            return false;
        }

        return true;
    }

    /**
     * @param $arrTitle
     * @return bool
     */
    static function ValidTitle($arrTitle) {
        $arrTitle = array_flip($arrTitle);
        if (isset($arrTitle['guid'])) {
            return true;
        }
        if (isset($arrTitle['uid'])) {
            return true;
        }
        if (isset($arrTitle['bid'])) {
            return true;
        }
        if (isset($arrTitle['name'])) {
            return true;
        }
        if (isset($arrTitle['src_type'])) {
            return true;
        }
        if (isset($arrTitle['sub_src'])) {
            return true;
        }
        if (isset($arrTitle['status'])) {
            return true;
        }
        if (isset($arrTitle['content'])) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    static function GetIntDbFields() {
        $arrIntDbFields = [];
        $arrIntDbFields['ap_id']  = 'int';
        $arrIntDbFields['src_id'] = 'int';
        $arrIntDbFields['status'] = 'int';
        $arrIntDbFields['errno']  = 'int';
        $arrIntDbFields['cardid'] = 'int';
        $arrIntDbFields['ts'] = 'int';
        $arrIntDbFields['ct'] = 'int';
        $arrIntDbFields['ut'] = 'int';
        $arrIntDbFields['task_id']      = 'int';
        $arrIntDbFields['check_status'] = 'int';
        $arrIntDbFields['check_info']   = 'int';
        $arrIntDbFields['errpchk']      = 'int';
        $arrIntDbFields['zhunru']       = 'int';
        return $arrIntDbFields;
    }

    /**
     * @param array $arrIntDbFields
     * @param array $arrData
     */
    static function ConvVal2Int($arrIntDbFields, &$arrData) {
        foreach ($arrData as $k => $v) {
            if (array_key_exists($k, $arrIntDbFields)) {
                $arrData[$k] = intval($v);
            }
        }
    }

    /**
     * @param array $array
     * @param $keys_map
     */
    static function switch_array_keys(&$array, $keys_map)
    {
        foreach ($keys_map as $_k => $_new_k) {
            if (isset($array[$_k])) {
                $array[$_new_k] = $array[$_k];
                unset($array[$_k]);
            }
        }
    }

    /**
     * @param array $array
     * @param array $collect_map
     */
    static function collect_getopt_long_options(&$array, $collect_map)
    {
        foreach ($collect_map as $_k => $_val) {
            if (isset($array[$_k])) {
                $_new_k = $_val[0];
                $_new_val = $_val[1];
                $array[$_new_k] = $_new_val;
                if ($_k !== $_new_k) {
                    unset($array[$_k]);
                }
            }
        }
    }

    /**
     * @param $receiver
     * @param $text
     * @param string $title
     * @param string $headers
     */
    static function send_mail($receiver, $text, $title='', $headers = '')
    {
        $arrMailTo = explode(';', $receiver);
        foreach ($arrMailTo as &$val) {
            $val .= '@baidu.com';
        }
        $receiver = implode(',', $arrMailTo);
        (''==$title) && ($title = $text);
        if (empty($headers)) {
            mail($receiver, $title, $text);
        } else {
            mail($receiver, $title, $text, $headers);
        }
    }

    /**
     * @param $enc
     * @param $string
     * @return string
     */
    static function encodeMIMEString ($enc, $string)
    {
        return '=?'.$enc.'?B?'.base64_encode($string).'?=';
    }

    /**
     * @param $array
     * @param $keys
     * @param null $not_exists_callback
     * @return array
     */
    static function filter_array_by_keys($array, $keys, $not_exists_callback = null)
    {
        $result = [];
        foreach ($keys as $_k) {
            isset($array[$_k])
                ? $result[$_k] = $array[$_k]
                : (
                    $not_exists_callback === null
                    || ($result[$_k] = $not_exists_callback($_k))
                );
        }
        return $result;
    }

    /**
     * @param $arrContPre
     * @param $arrContCur
     * @return array
     */
    static function GetCommonFields(&$arrContPre, &$arrContCur)
    {
        $arrDetailFields = [];
        is_array($arrContPre) || $arrContPre = [];
        foreach ($arrContPre as $field => $val) {
            (!isset($arrDetailFields[$field])) && ($arrDetailFields[$field] = '');
        }
        is_array($arrContCur) || $arrContCur = [];
        foreach ($arrContCur as $field => $val) {
            (!isset($arrDetailFields[$field])) && ($arrDetailFields[$field] = '');
        }
        return $arrDetailFields;
    }

    /**
     * @param $array
     * @return array
     */
    function object_array($array)
    {
        if(is_object($array)) {
            $array = (array)$array;
        }
        if(is_array($array)) {
            foreach($array as $key=>$value) {
                $array[$key] = object_array($value);
            }
        }
        return $array;
    }

    /**
     * @param null $extra
     * @param bool $rand
     * @return string
     */
    static function gen_uniq_id($extra = null, $rand = false)
    {
        list($mtime, $ttime) = explode(' ', microtime());
        $pid = posix_getpid();
        return sprintf(
            "%s-%s%s%s",
            dechex(($ttime+$mtime) * 1000000),
            dechex($pid),
            $extra === null ? '' : '-' . $extra,
            $rand ? '-' . dechex(rand(0,10000)) : ''
        );
    }

    /**
     * @param $search_str
     * @return null|string
     */
    static function analyze_input_id($search_str)
    {
        $id = null;
        if (strpos($search_str, '-') === false) {
            if (preg_match('/[a-zA-Z]+/', $search_str) > 0) {
                $search_str['1'] == 'x'
                    && $search_str = substr($search_str, 2);
                $id = Lib_Converter::uidDecode($search_str);
            } else {
                $id = $search_str;
            }
        } else {
            $id = Lib_Guid2uid::guid2uid($search_str);
        }
        return $id;
    }
}
