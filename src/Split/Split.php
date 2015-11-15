<?php

namespace phpSplit\Split;

use phpSplit\Analysis\ChineseAnalysis;

class Split
{

    public function __construct()
    {
//        $this->loadConfig();
    }

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
        $pa->StartAnalysis(true);

        $getInfo = true;
        $sign = '-';
        $result = $pa->GetFinallyResult($sign, $getInfo);
        return explode($sign, $result);
    }

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
