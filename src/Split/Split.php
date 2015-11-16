<?php

namespace phpSplit\Split;

use phpSplit\Analysis\ChineseAnalysis;

class Split
{

    public $pa;

    public function __construct()
    {
//        $this->loadConfig();

        ChineseAnalysis::$loadInit = false;
        $this->pa = new ChineseAnalysis('utf-8', 'utf-8', false);
    }

    /**
     * 开始分词
     *
     * @param string $word
     * @return array
     */
    public function start($word = '')
    {
        $this->pa->setSource($word);
        $this->pa->startAnalysis(true);

        $getInfo = true;
        $sign = '-';
        $result = $this->pa->getFinallyResult($sign, $getInfo);

        return explode($sign, $result);
    }

    /**
     * 简单分词方法
     *
     * @param string $string
     * @return array
     */
    public function simple($string = '')
    {
        $this->pa->setSource($string);
        $this->pa->startAnalysis(true);

        $getInfo = true;
        $sign = '-';
        $result = $this->pa->getFinallyResult($sign, $getInfo);

        return array_map(function($word){
            $word = explode('/',$word);
            return $word[0];
        },explode($sign, $result));
    }

    /**
     * load config
     *
     * @return bool
     */
    public static function loadConfig()
    {
        $files = [
            __DIR__ . '/Config.php',
        ];

        foreach ($files as $file) {
            if (is_file($file)) {
                require_once($file);
                return true;
            }
        }

        return false;
    }
}

?>
