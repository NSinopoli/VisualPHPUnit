<?php
/**
 * VisualPHPUnit
 *
 * VisualPHPUnit is a visual front-end for PHPUnit.
 *
 * PHP Version 5.3<
 *
 * @author    Nick Sinopoli <NSinopoli@gmail.com>
 * @copyright 2011-2015 VisualPHPUnit
 * @license   http://opensource.org/licenses/BSD-3-Clause The BSD License
 * @link      https://github.com/VisualPHPUnit/VisualPHPUnit VisualPHPUnit
 */
namespace visualphpunit;

use \PHPUnit_Framework_TestCase;

/**
 * PU Tests
 *
 * Testing class
 *
 * @author Nick Sinopoli <NSinopoli@gmail.com>
 */
class PUTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test one thing
     *
     * This test is just a dummy
     *
     * @test
     */
    public function oneThing()
    {
        $key = 'test';
        $value = 'value';
        print_r('some stuff');
        $this->assertEquals($key, $value, 'test_this() failed!');
    }

    /**
     * Test another thing
     *
     * This test is just a dummy
     *
     * @test
     */
    public function anotherThing()
    {
        $key = 'test';
        $value = 'value';
        $this->assertEquals($key, $value, 'test_this_too() failed!');
    }
}
