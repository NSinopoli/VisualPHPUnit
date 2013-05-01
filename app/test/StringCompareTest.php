<?php

class StringCompareTest extends PHPUnit_Framework_TestCase {
    public function test_this() {
        $key = 'string';
        $value = 'different string';
        $this->assertEquals($key, $value, 'test_this() failed!');
    }

    public function test_this_too() {
        $key = 'test';
        $value = 'test';
        print_r('foo { breaks: this } bar');
        print_r('foo breaks: this { bar');
        $this->assertEquals($key, $value, 'test_this_too() failed!');
    }
}

?>
