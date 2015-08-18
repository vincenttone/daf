<?php
class Test_Unit_Helper
{
    static function json_file_data($filepath)
    {
        $data = file_get_contents($filepath);
        return json_decode($data, true);
    }

    static function data_file($filepath)
    {
        $path = DA_UT_DATA_PATH.'/'.$filepath;
        return $path;
    }

    static function visit_private_static_method($klass, $method, $data)
    {
        $m = new ReflectionMethod($klass, $method);
        $m->setAccessible(TRUE);
        if (is_array($data)) {
            $data = array_merge([$klass], $data);
        } else {
            $data = [$klass, $data];
        }
        $result = call_user_func_array([$m, 'invoke'], $data);
        return $result;
    }

    static function visit_private_method($klass, $method, $data)
    {
        $c = new $klass();
        $m = new ReflectionMethod($c, $method);
        $m->setAccessible(TRUE);
        if (is_array($data)) {
            $data = array_merge([$c], $data);
        } else {
            $data = [$c, $data];
        }
        $result = call_user_func_array([$m, 'invoke'], $data);
        return $result;
    }

    static function visit_private_property($klass, $property, $object)
    {
        $reflection = new ReflectionClass($klass);
        $p = $reflection->getProperty($property);
        $p->setAccessible(true);
        return $p->getValue($object);
    }

    static function set_private_property($klass, $property, $object, $value)
    {
        $reflection = new ReflectionClass($klass);
        $p = $reflection->getProperty($property);
        $p->setAccessible(true);
        return $p->setValue($object, $value);
    }
}
