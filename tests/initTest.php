<?php
use phpSplit\Split\Split;


class initTest extends PHPUnit_Framework_TestCase
{
    /**
     * 标准分词测试
     */
    public function testIndex()
    {
        echo "test...\n";

        $split = new Split();

        var_dump( $split->start("您好phpSplit,不管怎么说你开心就好"));

        $this->assertTrue(True);
    }


    /**
     * 简单测试
     */
    public function testSimple()
    {
        echo "test...\n";

        $split = new Split();

        var_dump( $split->simple("您好phpSplit,不管怎么说你开心就好"));

        $this->assertTrue(True);
    }


    /**
     * 附加词语测试
     */
    public function testAddonSimple()
    {
        echo "test attach ... \n";

        $split = new Split();
        $split->attach(['康师傅手机']);
        var_dump( $split->simple("您好phpSplit,你喜欢康师傅手机么?"));

        $this->assertTrue(True);
    }
}

