<?php
/**
 * User: liyang
 * Date: 15/10/4
 * Time: 上午9:27
 */

use phpSplit\Analysis\ChineseAnalysis;

class analysisTest extends PHPUnit_Framework_TestCase
{

    public function testAnalysis()
    {
        echo "analysis...\n";

        $str='lasticSearch(简称ES)由java语言实现,运行环境依赖java。ES 1.
            0/,查看页面信息,是否正常启动.status=200表示正常启动了，还有一些es的版本信息,name为配';
        ChineseAnalysis::$loadInit = false;
        $pa = new ChineseAnalysis('utf-8', 'utf-8', false);
        $pa->LoadDict();
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

