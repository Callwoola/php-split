<?php
namespace phpSplit\Split;

interface SplitInterface
{

    /**
     * 驱动分词
     *
     * @return mixed
     */
    public function start();

    /**
     * 附加词 例如(康师傅手机)
     *
     * @return mixed
     */
    public function attach();

    /**
     * 简单分词 (只是获得中文) filter sign ,
     *
     * @return mixed
     */
    public function simple();
}

?>
