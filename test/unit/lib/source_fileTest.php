<?php
require_once(dirname(dirname(__FILE__)).'/init.php');
class Lib_SourceFileTest extends PHPUnit_Framework_TestCase
{
    function test_source_file()
    {
        $expect = [
            ['num', 'char', 'roman_num'],
            ['1', 'a', 'Ⅰ' ],
            ['2', 'b','Ⅱ'],
            ['3', 'c', 'Ⅲ'],
        ];
        $file = DA_UT_DATA_PATH.'/lib/tabfile.tab';
        $sf = new Lib_SourceFile($file);
        $sf->has_header = FALSE;
        $result = [];
        foreach ($sf as $_k => $_v) {
            $result[] = $_v['data'];
        }
        $line_count = $sf->current_line_no();
        $this->assertEquals($expect, $result);
        $this->assertEquals(4, $line_count);
    }

    function test_source_file_with_header()
    {
        $expect = [
            [
                'num' => '1',
                'char' => 'a',
                'roman_num' => 'Ⅰ' 
            ],
            [
                'num' => '2',
                'char' => 'b',
                'roman_num' => 'Ⅱ'
            ],
            [
                'num' => '3',
                'char' => 'c',
                'roman_num' => 'Ⅲ'
            ],
        ];
        $file = DA_UT_DATA_PATH.'/lib/tabfile.tab';
        $sf = new Lib_SourceFile($file);
        $sf->has_header = true;
        $result = [];
        foreach ($sf as $_k => $_v) {
            $result[] = $_v['data'];
        }
        $line_count = $sf->current_line_no();
        $this->assertEquals($expect, $result);
        $this->assertEquals(3, $line_count);
    }

    function test_source_file_read()
    {
        $input_file = Test_Unit_Helper::data_file('lib/tsv.input.res');
        $output_file = Test_Unit_Helper::data_file('lib/tsv.output.json');
        $expect = Test_Unit_Helper::json_file_data($output_file);
        $sf = new Lib_SourceFile($input_file);
        $sf->has_header = true;
        $result = [];
        foreach ($sf as $_k => $_v) {
            $result[] = $_v;
        }
        $this->assertEquals($expect, $result);
        $line_count = $sf->current_line_no();
        $this->assertEquals(count($expect), $line_count);
    }
}