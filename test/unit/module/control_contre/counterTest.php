<?php
require_once(dirname(dirname(dirname(__FILE__))).'/init.php');
class Module_ControlCentre_CounterTest extends PHPUnit_Framework_TestCase
{
    private static $_task_id = 102;
    private static $_mid = 10;
    private static $_test_key = 'unit-test';
    private static $_key1 = 'ut01';
    private static $_key2 = 'ut02';
    private static $_key3 = 'ut03';

    function setUp()
    {
        Module_ControlCentre_Main::get_instance()->create_task(self::$_task_id);
        Module_ControlCentre_Counter::register_keys_map(
            self::$_mid,
            [
                self::$_test_key => 'test',
                self::$_key1 => 'a',
                self::$_key2 => 'b',
            ]
        );
    }

    function tearDown()
    {
        $result = Module_ControlCentre_Counter::del_all_counts();
    }

    function test_incr()
    {
        $result = Module_ControlCentre_Counter::del_all_counts();
        $this->assertEquals($result['errno'], 0);
        Module_ControlCentre_Counter::incr(self::$_test_key, 20);
        Module_ControlCentre_Counter::incr(self::$_test_key);
        Module_ControlCentre_Counter::incr(self::$_test_key, 5);
        Module_ControlCentre_Counter::incr(self::$_test_key, -10);
        $result = Module_ControlCentre_Counter::get_count(self::$_test_key);
        $this->assertEquals($result['data'], 16);
        $result = Module_ControlCentre_Counter::get_all_counts();
        $this->assertEquals(
            $result['data'],
            [self::$_test_key => 16, self::$_key1 => 0, self::$_key2 => 0]
        );
        $result = Module_ControlCentre_Counter::del_all_counts();
        $this->assertEquals($result['errno'], 0);
    }
    function test_incr_counts()
    {
        $counts = [
            'ut01' => 1,
            'ut02' => 2,
        ];
        $result = Module_ControlCentre_Counter::del_all_counts();
        $this->assertEquals($result['errno'], 0);
        Module_ControlCentre_Counter::incr_counts($counts);
        foreach($counts as $_k => $_v) {
            $counts[$_k] = 2 * $_v;
        }
        Module_ControlCentre_Counter::incr_counts($counts);
        $counts = Module_ControlCentre_Counter::get_all_counts();
        $this->assertEquals(
            $counts['data'],
            [self::$_test_key => 0, self::$_key1 => 3, self::$_key2 => 6]
        );
        $result = Module_ControlCentre_Counter::del_counts(
            [self::$_key1, self::$_key3]
        );
        $this->assertEquals($result['errno'], 0);
        $this->assertEquals(
            $result['data'],
            [self::$_key1 => true, self::$_key3 => false]
        );
    }

}