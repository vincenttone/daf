<?php
require_once(dirname(dirname(__FILE__)).'/init.php');
class Lib_FileTest extends PHPUnit_Framework_TestCase
{
    function test_removeDirOrFile()
    {
        $f1 = '/tmp/utdir/1/2';
        $f1Dir = '/tmp/utdir';
        $f2 = '/tmp/utfile';
        file_exists(dirname($f1)) || mkdir(dirname($f1), 0777, true);
        file_put_contents($f1, 'ut4dir');
        file_put_contents($f2, 'ut4file');
        $ef1 = file_exists($f1);
        $ef2 = file_exists($f2);
        $ed1 = file_exists($f1Dir);
        $this->assertEquals($ef1, true);
        $this->assertEquals($ef2, true);
        $this->assertEquals($ed1, true);
        $rmdir = Lib_File::removeDirOrFile($f1Dir);
        $rmfile = Lib_File::removeDirOrFile($f2);
        $this->assertEquals($rmdir, true);
        $this->assertEquals($rmfile, true);
        $ef1 = file_exists($f1);
        $ef2 = file_exists($f2);
        $ed1 = file_exists($f1Dir);
        $this->assertEquals($ef1, false);
        $this->assertEquals($ef2, false);
        $this->assertEquals($ed1, false);
    }
}