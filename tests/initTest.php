<?php
use phpSplit\Split\Split;


class initTest extends PHPUnit_Framework_TestCase
{
    public function testIndex()
    {
        echo "test...\n";

        $split = new Split();

        var_dump( $split->start("您好 phpSplit"));

        $this->assertTrue(True);
    }


    public function testSimple()
    {
        echo "test...\n";

        $split = new Split();

        var_dump( $split->simple("您好 phpSplit"));

        $this->assertTrue(True);
    }

}

