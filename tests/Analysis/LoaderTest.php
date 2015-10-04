<?php

class testPuppet
{
    use \phpSplit\Analysis\Loader;
}


class LoaderTest extends PHPUnit_Framework_TestCase
{

    public function testLoader()
    {
        echo "loading...\n";
        $test = new testPuppet();
        $result = $test->getLoadDict();

        $this->assertTrue(true);
        var_dump($result);
    }
}

