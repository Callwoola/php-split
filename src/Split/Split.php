<?php

namespace phpSplit\Split;

use phpSplit\Analysis\ChineseAnalysis;

class Split
{


    /**
     * 开始分词
     *
     * @param string $word
     * @return array
     */
    public function start($word = '')
    {
        ChineseAnalysis::$loadInit = false;
        $pa = new ChineseAnalysis('utf-8', 'utf-8', false);

        $pa->SetSource($word);
        $pa->differMax = false;
        $pa->unitWord = false;
        $pa->StartAnalysis(true);

        $getInfo=true;
        $sign='-';
        $result=$pa->GetFinallyResult($sign,$getInfo);
        return explode($sign,$result);
    }
}

?>
