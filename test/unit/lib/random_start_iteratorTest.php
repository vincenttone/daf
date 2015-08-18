<?php
require_once(dirname(dirname(__FILE__)).'/init.php');
class Lib_RandomStartIteratorTest extends PHPUnit_Framework_TestCase
{
    function test_selecter()
    {
        $selecter = new Lib_RandomStartIterator();
        $selecter->set_max_loop_deep(6);
        $selecter->set_elements([1,2,3]);
        $result = [];
        foreach($selecter as $_k => $_c) {
            $result[] = $_c;
        }
        $this->assertEquals(count($result), 6);
        sort($result);
        $this->assertEquals($result, [1,1,2,2,3,3]);
        $selecter->set_elements([]);
        $selecter->add_element(1);
        $selecter->add_element(2);
        $selecter->add_element(3);
        $result = [];
        foreach($selecter as $_k => $_c) {
            $result[] = $_c;
        }
        $this->assertEquals(count($result), 6);
        sort($result);
        $this->assertEquals($result, [1,1,2,2,3,3]);
    }
}