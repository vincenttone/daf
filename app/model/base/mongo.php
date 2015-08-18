<?php
class Model_Base_Mongo extends Model_Base_Db
{
    /**
     * @param array
     * @return null
     */
    function init_db_with_config($config)
    {
        $this->_db_name = $config[Lib_Db::CONFIG_FIELD_DB];
        $this->_db = Lib_Db::db($config);
    }

    /**
     * @param string
     * @param array
     * @param array
     * @param int
     * @param int
     * @param null|array
     * @return array
     */
    function get_all_then_change_id_key($id_key, $cond=[], $fields=[], $limit = 0, $offset = 0, $sort = null)
    {
        $result = $this->get_table()->get_all($cond, $fields, $limit, $offset, $sort);
        if ($result['errno'] !== Const_Err_Base::ERR_OK) {
            return $result;
        }
        $result = $result['data'];
        foreach ($result as $_k => $_r) {
            $result[$_k][$id_key] = $_r['_id'];
            unset($result[$_k]['_id']);
        }
        return Lib_Helper::get_return_struct($result);
    }

    /**
     * @param string
     * @param string
     * @return array
     */
    function get_one_by_id_then_change_id_key($id, $id_key)
    {
        $result = $this->get_table()->get_one(['_id' => $id]);
        if ($result['errno'] !== Const_Err_Base::ERR_OK) {
            return $result;
        }
        $result = $result['data'];
        $result[$id_key] = $result['_id'];
        unset($result['_id']);
        return Lib_Helper::get_return_struct($result);
    }

    /**
     * @param array
     * @param string
     * @param bool
     * @return array
     */
    function add_with_id_key($data, $id_key, $with_time = true)
    {
        $data['_id'] = $data[$id_key];
        unset($data[$id_key]);
        if ($with_time) {
            $data = self::add_time($data);
        }
        return $this->get_table()->insert($data);
    }

    /**
     * @param array
     * @param string
     * @param bool
     * @return array
     */
    function save_with_id_key($data, $id_key, $with_time = true)
    {
        $data['_id'] = $data[$id_key];
        unset($data[$id_key]);
        if ($with_time) {
            $data = self::add_time($data);
        }
        return $this->get_table()->save($data);
    }

    /**
     * @param array
     * @param string
     * @param bool
     * @return array
     */
    function add_batch_with_id_key($data, $id_key, $with_time = true)
    {
        foreach ($data as $_k => $_c) {
            $_c['_id'] = $_c[$id_key];
            unset($_c[$id_key]);
            if ($with_time) {
                $_c = self::add_time($_c);
            }
            $data[$_k] = $_c;
        }
        return $this->get_table()->insert($data, true);
    }

    /**
     * @param string
     * @param string
     * @return array
     */
    function get_by_ids_then_change_id_key($ids, $id_key)
    {
        $result = $this->get_table()->get_all(['_id' => ['$in' => $ids]]);
        if ($result['errno'] !== Const_Err_Base::ERR_OK) {
            return $result;
        }
        $result = $result['data'];
        foreach ($result as $_k => $_r) {
            $result[$_k][$id_key] = $_r['_id'];
            unset($result[$_k]['_id']);
        }
        return Lib_Helper::get_return_struct($result);
    }
}
