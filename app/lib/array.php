<?php
class Lib_Array
{
    /**
     * @param array $array
     * @param array $sort_key
     * @param bool $asc
     * @param bool $index
     * @return null|int
     */
    static function sort_two_dimension_array(&$array, $sort_key, $asc = true, $index = true)
    {
        $sort_func = function($a, $b) use ($sort_key, $asc) {
            $result = 0;
            if ($a[$sort_key] < $b[$sort_key]) {
                $result =  $asc ? -1 : 1;
            } elseif ($a[$sort_key] > $b[$sort_key]) {
                $result = $asc ? 1 : -1;
            }
            return $result;
        };
        $index ? uasort($array, $sort_func)
            : usort($array, $sort_func);
    }
}