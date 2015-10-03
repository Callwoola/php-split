<?php
use phpSplit\Split\Split;


class initTest extends PHPUnit_Framework_TestCase
{
    public function testIndex()
    {
        echo "test...";

        $split = new Split();

        $split->init();

        $this->assertTrue(True);
    }

}

