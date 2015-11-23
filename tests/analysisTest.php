<?php
/**
 * User: liyang
 * Date: 15/10/4
 * Time: 上午9:27
 */

use phpSplit\Analysis\ChineseAnalysis;

class analysisTest extends PHPUnit_Framework_TestCase
{

    /**
     * 基本测试
     */
    public function testAnalysis()
    {
        echo "analysis...\n";

        $str='对于五到十人小型团队来说，什么样的协作开发方式比较合适？Bitbuket + Worktile + WizNote';
        ChineseAnalysis::$loadInit = false;
        $pa = new ChineseAnalysis('utf-8', 'utf-8', false);
//        $pa->LoadDict();
        $pa->SetSource($str);
        $pa->differMax = false;
        $pa->unitWord = false;
        $pa->StartAnalysis(true);
//        $resultArray=$pa->GetFinallyIndex();
        $getInfo=true;
        $sign='-';
        $result=$pa->GetFinallyResult($sign,$getInfo);
        $result=explode($sign,$result);
        $filterResult=[];

        var_dump($result);

        foreach($result as $k=>$value){
            if (preg_match('/\/n/i', $value) === 1) {
                $arrValue=explode('/',$value);
                $filterResult[$arrValue[0]]=(int)preg_replace('/(n[a-z|A-Z]*)/','',$arrValue[1]);
            }
        }

        $this->assertTrue(count($filterResult)>0);
    }
}

