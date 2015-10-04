<?php
namespace phpSplit\Analysis;

trait Loader
{


    /**
     * 载入词典
     * @param String $mainDic
     * @return array
     */
    public function getLoadDict($mainDic = '')
    {
        //常量定义

        $_SP_ = chr(0xFF) . chr(0xFE);
        $UCS2 = 'ucs-2be';

        $ADDITION_FILE = __DIR__ . '/dict/words_addons.dic';
        $mainDicFile = __DIR__ . '/dict/base_dic_full.dic';
//        $mainDicFile = null;
        $mainDicHand = null;
        $additionDict = [];


        $startTime = microtime(true);
        //正常读取文件
        $dicAddon = $ADDITION_FILE;

        if ($mainDic == '' || !file_exists($mainDic)) {
            $dicWords = $mainDicFile;
        } else {
            $dicWords = $mainDic;
            $mainDicFile = $mainDic;
        }

        // 加载主词典（只打开）
        $mainDicHand = fopen($dicWords, 'r');

//        //加载附加的 分词
//        if (!empty($additionDict)) {
//            $mainDicHand = $mainDicHand . $this->getAdditionDict();
//        }

        //载入副词典
        $hw = '';

        $ds = file($dicAddon);
        foreach ($ds as $d) {
            $d = trim($d);
            if ($d == '') continue;
            $estr = substr($d, 1, 1);
            if ($estr == ':') {
                $hw = substr($d, 0, 1);
            } else {
                $spstr = $_SP_;
                $spstr = iconv($UCS2, 'utf-8', $spstr);
                $ws = explode(',', $d);
                $wall = iconv('utf-8', $UCS2, join($spstr, $ws));
                $ws = explode($_SP_, $wall);
                foreach ($ws as $estr) {
                    $additionDict[$hw][$estr] = strlen($estr);
                }
            }
        }

        $loadTime = microtime(true) - $startTime;
//        $isLoadDic = true;

        return [$mainDicHand,$mainDic, $additionDict, $loadTime];
    }


}
