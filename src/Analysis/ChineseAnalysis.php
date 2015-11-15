<?php
namespace phpSplit\Analysis;

//class ChineseAnalysis extends Config
define('_SP_', chr(0xFF) . chr(0xFE));
define('UCS2', 'ucs-2be');


class ChineseAnalysis implements ChineseAnalysisInterface
{
    use Loader;
    use Config;

    /**
     * 构造函数
     * @param $source
     */
    public function __construct($source = '')
    {
//        $this->addonDicFile = dirname(__FILE__) . '/' . $this->addonDicFile;
//        $this->mainDicFile = dirname(__FILE__) . '/' . $this->mainDicFile;

        $this->differMax = false;
        $this->unitWord = false;

        $this->setSource($source);

        $load_all = true;
        $this->isLoadAll = $load_all;

        list($mainDicHand, $mainDic, $additionDict, $loadTime) = $this->getLoadDict();

        $this->mainDicHand = $mainDicHand;
        $this->mainDic = $mainDic;
        $this->addonDic = $additionDict;
        $this->loadTime = $loadTime;


        $this->analysis = new Analysis();
    }

    /**
     * 析构函数
     */
    function __destruct()
    {
        if ($this->mainDicHand !== false) {
            @fclose($this->mainDicHand);
        }
    }

    /**
     * 从文件获得词
     * @param $key
     * @param $type (类型 word 或 key_groups)
     * @return short int
     */
    public function GetWordInfos($key, $type = 'word')
    {
        if (!$this->mainDicHand) {
            $this->mainDicHand = fopen($this->mainDicFile, 'r');
        }
        $p = 0;

        // 根据字符串计算key索引
        $l = strlen($key);
        $h = 0x238f13af;
        while ($l--) {
            $h += ($h << 5);
            $h ^= ord($key[$l]);
            $h &= 0x7fffffff;
        }

        $keynum = ($h % $this->mask_value);

        if (isset($this->mainDicInfos[$keynum])) {
            $data = $this->mainDicInfos[$keynum];
        } else {
            //rewind( $this->mainDicHand );
            $move_pos = $keynum * 8;
            fseek($this->mainDicHand, $move_pos, SEEK_SET);
            $dat = fread($this->mainDicHand, 8);
            $arr = unpack('I1s/n1l/n1c', $dat);
            if ($arr['l'] == 0) {
                return false;
            }
            fseek($this->mainDicHand, $arr['s'], SEEK_SET);
            $data = @unserialize(fread($this->mainDicHand, $arr['l']));
            $this->mainDicInfos[$keynum] = $data;
        }
        if (!is_array($data) || !isset($data[$key])) {
            return false;
        }
        return ($type == 'word' ? $data[$key] : $data);
    }

    /**
     * 设置源字符串
     * @param $source
     * @param $source_charset
     * @param $target_charset
     *
     * @return bool
     */
    public function setSource($source)
    {
        $source_charset = 'utf-8';
        $target_charset = 'utf-8';

        $this->sourceCharSet = strtolower($source_charset);
        $this->targetCharSet = strtolower($target_charset);
        $this->simpleResult = [];
        $this->finallyResult = [];
        $this->finallyIndex = [];


        if ($source != '') {
            $rs = true;
            if (preg_match("/^utf/", $source_charset)) {
                $this->sourceString = iconv('utf-8', UCS2, $source);
            } else if (preg_match("/^gb/", $source_charset)) {
                $this->sourceString = iconv('utf-8', UCS2, iconv('gb18030', 'utf-8', $source));
            } else if (preg_match("/^big/", $source_charset)) {
                $this->sourceString = iconv('utf-8', UCS2, iconv('big5', 'utf-8', $source));
            } else {
                $rs = false;
            }
        } else {
            $rs = false;
        }
        return $rs;
    }

    /**
     * 设置结果类型(只在获取finallyResult才有效)
     * @param $rstype 1 为全部， 2去除特殊符号
     *
     * @return void
     */
    public function SetResultType($rstype)
    {
        $this->resultType = $rstype;
    }


    /**
     * 检测某个词是否存在
     */
    public function IsWord($word)
    {
        $winfos = $this->GetWordInfos($word);
        return ($winfos !== false);
    }

    /**
     * 获得某个词的词性及词频信息
     * @parem $word unicode编码的词
     * @return void
     */
    public function GetWordProperty($word)
    {
        if (strlen($word) < 4) {
            return '/s';
        }
        $infos = $this->GetWordInfos($word);
        return isset($infos[1]) ? "/{$infos[1]}{$infos[0]}" : "/s";
    }

    /**
     * 指定某词的词性信息（通常是新词）
     * @parem $word unicode编码的词
     * @parem $infos array('c' => 词频, 'm' => 词性);
     * @return void;
     */
    public function SetWordInfos($word, $infos)
    {
        if (strlen($word) < 4) {
            return;
        }
        if (isset($this->mainDicInfos[$word])) {
            $this->newWords[$word]++;
            $this->mainDicInfos[$word]['c']++;
        } else {
            $this->newWords[$word] = 1;
            $this->mainDicInfos[$word] = $infos;
        }
    }

    /**
     * 开始执行分析
     * @parem bool optimize 是否对结果进行优化
     * @return bool
     */
    public function startAnalysis($optimize = true)
    {
//
//
//        $this->analysis->analysis(
//            [
//                $this->sourceString,
//                $this->simpleResult,
//
//            ]
//        );


//        if (!$this->isLoadDic) {
//            $this->LoadDict();
//        }



        $this->simpleResult = $this->finallyResult = [];
        $this->sourceString .= chr(0) . chr(32);
        $slen = strlen($this->sourceString);
        $sbcArr = [];
        $j = 0;


        //全角与半角字符对照表
        for ($i = 0xFF00; $i < 0xFF5F; $i++) {
            $scb = 0x20 + $j;
            $j++;
            $sbcArr[$i] = $scb;
        }
        //对字符串进行粗分
        $onstr = '';
        $lastc = 1; //1 中/韩/日文, 2 英文/数字/符号('.', '@', '#', '+'), 3 ANSI符号 4 纯数字 5 非ANSI符号或不支持字符
        $s = 0;
        $ansiWordMatch = "[0-9a-z@#%\+\.-]";
        $notNumberMatch = "[a-z@#%\+]";
        for ($i = 0; $i < $slen; $i++) {
            $c = $this->sourceString[$i] . $this->sourceString[++$i];
            $cn = hexdec(bin2hex($c));
            $cn = isset($sbcArr[$cn]) ? $sbcArr[$cn] : $cn;
            //ANSI字符
            if ($cn < 0x80) {
                if (preg_match('/' . $ansiWordMatch . '/i', chr($cn))) {
                    if ($lastc != 2 && $onstr != '') {
                        $this->simpleResult[$s]['w'] = $onstr;
                        $this->simpleResult[$s]['t'] = $lastc;
                        $this->_deep_analysis($onstr, $lastc, $s, $optimize);
                        $s++;
                        $onstr = '';
                    }
                    $lastc = 2;
                    $onstr .= chr(0) . chr($cn);
                } else {
                    if ($onstr != '') {
                        $this->simpleResult[$s]['w'] = $onstr;
                        if ($lastc == 2) {
                            if (!preg_match('/' . $notNumberMatch . '/i', iconv(UCS2, 'utf-8', $onstr))) $lastc = 4;
                        }
                        $this->simpleResult[$s]['t'] = $lastc;
                        if ($lastc != 4) $this->_deep_analysis($onstr, $lastc, $s, $optimize);
                        $s++;
                    }
                    $onstr = '';
                    $lastc = 3;
                    if ($cn < 31) {
                        continue;
                    } else {
                        $this->simpleResult[$s]['w'] = chr(0) . chr($cn);
                        $this->simpleResult[$s]['t'] = 3;
                        $s++;
                    }
                }
            } //普通字符
            else {
                //正常文字
                if (($cn > 0x3FFF && $cn < 0x9FA6) || ($cn > 0xF8FF && $cn < 0xFA2D)
                    || ($cn > 0xABFF && $cn < 0xD7A4) || ($cn > 0x3040 && $cn < 0x312B)
                ) {
                    if ($lastc != 1 && $onstr != '') {
                        $this->simpleResult[$s]['w'] = $onstr;
                        if ($lastc == 2) {
                            if (!preg_match('/' . $notNumberMatch . '/i', iconv(UCS2, 'utf-8', $onstr))) $lastc = 4;
                        }
                        $this->simpleResult[$s]['t'] = $lastc;
                        if ($lastc != 4) $this->_deep_analysis($onstr, $lastc, $s, $optimize);
                        $s++;
                        $onstr = '';
                    }
                    $lastc = 1;
                    $onstr .= $c;
                } //特殊符号
                else {
                    if ($onstr != '') {
                        $this->simpleResult[$s]['w'] = $onstr;
                        if ($lastc == 2) {
                            if (!preg_match('/' . $notNumberMatch . '/i', iconv(UCS2, 'utf-8', $onstr))) $lastc = 4;
                        }
                        $this->simpleResult[$s]['t'] = $lastc;
                        if ($lastc != 4) $this->_deep_analysis($onstr, $lastc, $s, $optimize);
                        $s++;
                    }

                    //检测书名
                    if ($cn == 0x300A) {
                        $tmpw = '';
                        $n = 1;
                        $isok = false;
                        $ew = chr(0x30) . chr(0x0B);
                        while (true) {
                            if (!isset($this->sourceString[$i + $n + 1])) break;
                            $w = $this->sourceString[$i + $n] . $this->sourceString[$i + $n + 1];
                            if ($w == $ew) {
                                $this->simpleResult[$s]['w'] = $c;
                                $this->simpleResult[$s]['t'] = 5;
                                $s++;

                                $this->simpleResult[$s]['w'] = $tmpw;
                                $this->newWords[$tmpw] = 1;
                                if (!isset($this->newWords[$tmpw])) {
                                    $this->foundWordStr .= StringTool::encoding($tmpw,$this->targetCharSet) . '/nb, ';
                                    $this->SetWordInfos($tmpw, ['c' => 1, 'm' => 'nb']);
                                }
                                $this->simpleResult[$s]['t'] = 13;

                                $s++;

                                //最大切分模式对书名继续分词
                                if ($this->differMax) {
                                    $this->simpleResult[$s]['w'] = $tmpw;
                                    $this->simpleResult[$s]['t'] = 21;
                                    $this->_deep_analysis($tmpw, $lastc, $s, $optimize);
                                    $s++;
                                }

                                $this->simpleResult[$s]['w'] = $ew;
                                $this->simpleResult[$s]['t'] = 5;
                                $s++;

                                $i = $i + $n + 1;
                                $isok = true;
                                $onstr = '';
                                $lastc = 5;
                                break;
                            } else {
                                $n = $n + 2;
                                $tmpw .= $w;
                                if (strlen($tmpw) > 60) {
                                    break;
                                }
                            }
                        }//while
                        if (!$isok) {
                            $this->simpleResult[$s]['w'] = $c;
                            $this->simpleResult[$s]['t'] = 5;
                            $s++;
                            $onstr = '';
                            $lastc = 5;
                        }
                        continue;
                    }

                    $onstr = '';
                    $lastc = 5;
                    if ($cn == 0x3000) {
                        continue;
                    } else {
                        $this->simpleResult[$s]['w'] = $c;
                        $this->simpleResult[$s]['t'] = 5;
                        $s++;
                    }
                }//2byte symbol

            }//end 2byte char

        }//end for

        // 处理分词后的结果

        $newarr = [];
        $i = 0;

        // 转换最终分词结果到 finallyResult 数组
        foreach ($this->simpleResult as $k => $v) {
            if (empty($v['w'])) continue;
            if (isset($this->finallyResult[$k]) && count($this->finallyResult[$k]) > 0) {
                foreach ($this->finallyResult[$k] as $w) {
                    if (!empty($w)) {
                        $newarr[$i]['w'] = $w;
                        $newarr[$i]['t'] = 20;
                        $i++;
                    }
                }
            } else if ($v['t'] != 21) {
                $newarr[$i]['w'] = $v['w'];
                $newarr[$i]['t'] = $v['t'];
                $i++;
            }
        }
        $this->finallyResult = $newarr;
        $newarr = '';
    }

    /**
     * 深入分词
     * @parem $str
     * @parem $ctype (2 英文类， 3 中/韩/日文类)
     * @parem $spos   当前粗分结果游标
     * @return bool
     */
    private function _deep_analysis(&$str, $ctype, $spos, $optimize = true)
    {

        //中文句子
        if ($ctype == 1) {
            $slen = strlen($str);
            //小于系统配置分词要求长度的句子
            if ($slen < $this->notSplitLen) {
                $tmpstr = '';
                $lastType = 0;
                if ($spos > 0) $lastType = $this->simpleResult[$spos - 1]['t'];
                if ($slen < 5) {
                    //echo iconv(UCS2, 'utf-8', $str).'<br/>';
                    if ($lastType == 4 && (isset($this->addonDic['u'][$str]) || isset($this->addonDic['u'][substr($str, 0, 2)]))) {
                        $str2 = '';
                        if (!isset($this->addonDic['u'][$str]) && isset($this->addonDic['s'][substr($str, 2, 2)])) {
                            $str2 = substr($str, 2, 2);
                            $str = substr($str, 0, 2);
                        }
                        $ww = $this->simpleResult[$spos - 1]['w'] . $str;
                        $this->simpleResult[$spos - 1]['w'] = $ww;
                        $this->simpleResult[$spos - 1]['t'] = 4;
                        if (!isset($this->newWords[$this->simpleResult[$spos - 1]['w']])) {
                            $this->foundWordStr .= StringTool::encoding($ww,$this->targetCharSet) . '/mu, ';
                            $this->SetWordInfos($ww, ['c' => 1, 'm' => 'mu']);
                        }
                        $this->simpleResult[$spos]['w'] = '';
                        if ($str2 != '') {
                            $this->finallyResult[$spos - 1][] = $ww;
                            $this->finallyResult[$spos - 1][] = $str2;
                        }
                    } else {
                        $this->finallyResult[$spos][] = $str;
                    }
                } else {
                    $this->_deep_analysis_cn($str, $ctype, $spos, $slen, $optimize);
                }
            } //正常长度的句子，循环进行分词处理
            else {
                $this->_deep_analysis_cn($str, $ctype, $spos, $slen, $optimize);
            }
        } //英文句子，转为小写
        else {
            if ($this->toLower) {
                $this->finallyResult[$spos][] = strtolower($str);
            } else {
                $this->finallyResult[$spos][] = $str;
            }
        }
    }

    /**
     * 中文的深入分词
     * @parem $str
     * @return void
     */
    private function _deep_analysis_cn(&$str, $lastec, $spos, $slen, $optimize = true)
    {
        $quote1 = chr(0x20) . chr(0x1C);
        $tmparr = [];
        $hasw = 0;
        //如果前一个词为 “ ， 并且字符串小于3个字符当成一个词处理。
        if ($spos > 0 && $slen < 11 && $this->simpleResult[$spos - 1]['w'] == $quote1) {
            $tmparr[] = $str;
            if (!isset($this->newWords[$str])) {
                $this->foundWordStr .= StringTool::encoding($str,$this->targetCharSet) . '/nq, ';
                $this->SetWordInfos($str, ['c' => 1, 'm' => 'nq']);
            }
            if (!$this->differMax) {
                $this->finallyResult[$spos][] = $str;
                return;
            }
        }
        //进行切分
        for ($i = $slen - 1; $i > 0; $i -= 2) {
            //单个词
            $nc = $str[$i - 1] . $str[$i];
            //是否已经到最后两个字
            if ($i <= 2) {
                $tmparr[] = $nc;
                $i = 0;
                break;
            }
            $isok = false;
            $i = $i + 1;
            for ($k = $this->dicWordMax; $k > 1; $k = $k - 2) {
                if ($i < $k) continue;
                $w = substr($str, $i - $k, $k);
                if (strlen($w) <= 2) {
                    $i = $i - 1;
                    break;
                }
                if ($this->IsWord($w)) {
                    $tmparr[] = $w;
                    $i = $i - $k + 1;
                    $isok = true;
                    break;
                }
            }
            //echo '<hr />';
            //没适合词
            if (!$isok) $tmparr[] = $nc;
        }
        $wcount = count($tmparr);
        if ($wcount == 0) return;
        $this->finallyResult[$spos] = array_reverse($tmparr);
        //优化结果(岐义处理、新词、数词、人名识别等)
        if ($optimize) {
            $this->_optimize_result($this->finallyResult[$spos], $spos);
        }
    }

    /**
     * 对最终分词结果进行优化（把simpleresult结果合并，并尝试新词识别、数词合并等）
     * @parem $optimize 是否优化合并的结果
     * @return bool
     */
    //t = 1 中/韩/日文, 2 英文/数字/符号('.', '@', '#', '+'), 3 ANSI符号 4 纯数字 5 非ANSI符号或不支持字符
    private function _optimize_result(&$smarr, $spos)
    {
        $newarr = [];
        $prePos = $spos - 1;
        $arlen = count($smarr);
        $i = $j = 0;
        //检测数量词
        if ($prePos > -1 && !isset($this->finallyResult[$prePos])) {
            $lastw = $this->simpleResult[$prePos]['w'];
            $lastt = $this->simpleResult[$prePos]['t'];
            if (($lastt == 4 || isset($this->addonDic['c'][$lastw])) && isset($this->addonDic['u'][$smarr[0]])) {
                $this->simpleResult[$prePos]['w'] = $lastw . $smarr[0];
                $this->simpleResult[$prePos]['t'] = 4;
                if (!isset($this->newWords[$this->simpleResult[$prePos]['w']])) {
                    $this->foundWordStr .= StringTool::encoding($this->simpleResult[$prePos]['w'],$this->targetCharSet) . '/mu, ';
                    $this->SetWordInfos($this->simpleResult[$prePos]['w'], ['c' => 1, 'm' => 'mu']);
                }
                $smarr[0] = '';
                $i++;
            }
        }
        for (; $i < $arlen; $i++) {

            if (!isset($smarr[$i + 1])) {
                $newarr[$j] = $smarr[$i];
                break;
            }
            $cw = $smarr[$i];
            $nw = $smarr[$i + 1];
            $ischeck = false;
            //检测数量词
            if (isset($this->addonDic['c'][$cw]) && isset($this->addonDic['u'][$nw])) {
                //最大切分时保留合并前的词
                if ($this->differMax) {
                    $newarr[$j] = chr(0) . chr(0x28);
                    $j++;
                    $newarr[$j] = $cw;
                    $j++;
                    $newarr[$j] = $nw;
                    $j++;
                    $newarr[$j] = chr(0) . chr(0x29);
                    $j++;
                }
                $newarr[$j] = $cw . $nw;
                if (!isset($this->newWords[$newarr[$j]])) {
                    $this->foundWordStr .= StringTool::encoding($newarr[$j],$this->targetCharSet) . '/mu, ';
                    $this->SetWordInfos($newarr[$j], ['c' => 1, 'm' => 'mu']);
                }
                $j++;
                $i++;
                $ischeck = true;
            } //检测前导词(通常是姓)
            else if (isset($this->addonDic['n'][$smarr[$i]])) {
                $is_rs = false;
                //词语是副词或介词或频率很高的词不作为人名
                if (strlen($nw) == 4) {
                    $winfos = $this->GetWordInfos($nw);
                    if (isset($winfos['m']) && ($winfos['m'] == 'r' || $winfos['m'] == 'c' || $winfos['c'] > 500)) {
                        $is_rs = true;
                    }
                }
                if (!isset($this->addonDic['s'][$nw]) && strlen($nw) < 5 && !$is_rs) {
                    $newarr[$j] = $cw . $nw;
                    //echo iconv(UCS2, 'utf-8', $newarr[$j])."<br />";
                    //尝试检测第三个词
                    if (strlen($nw) == 2 && isset($smarr[$i + 2]) && strlen($smarr[$i + 2]) == 2 && !isset($this->addonDic['s'][$smarr[$i + 2]])) {
                        $newarr[$j] .= $smarr[$i + 2];
                        $i++;
                    }
                    if (!isset($this->newWords[$newarr[$j]])) {
                        $this->SetWordInfos($newarr[$j], ['c' => 1, 'm' => 'nr']);
                        $this->foundWordStr .= StringTool::encoding($newarr[$j],$this->targetCharSet) . '/nr, ';
                    }
                    //为了防止错误，保留合并前的姓名
                    if (strlen($nw) == 4) {
                        $j++;
                        $newarr[$j] = chr(0) . chr(0x28);
                        $j++;
                        $newarr[$j] = $cw;
                        $j++;
                        $newarr[$j] = $nw;
                        $j++;
                        $newarr[$j] = chr(0) . chr(0x29);
                    }

                    $j++;
                    $i++;
                    $ischeck = true;
                }
            } //检测后缀词(地名等)
            else if (isset($this->addonDic['a'][$nw])) {
                $is_rs = false;
                //词语是副词或介词不作为前缀
                if (strlen($cw) > 2) {
                    $winfos = $this->GetWordInfos($cw);
                    if (isset($winfos['m']) && ($winfos['m'] == 'a' || $winfos['m'] == 'r' || $winfos['m'] == 'c' || $winfos['c'] > 500)) {
                        $is_rs = true;
                    }
                }
                if (!isset($this->addonDic['s'][$cw]) && !$is_rs) {
                    $newarr[$j] = $cw . $nw;
                    if (!isset($this->newWords[$newarr[$j]])) {
                        $this->foundWordStr .= StringTool::encoding($newarr[$j],$this->targetCharSet) . '/na, ';
                        $this->SetWordInfos($newarr[$j], ['c' => 1, 'm' => 'na']);
                    }
                    $i++;
                    $j++;
                    $ischeck = true;
                }
            } //新词识别（暂无规则）
            else if ($this->unitWord) {
                if (strlen($cw) == 2 && strlen($nw) == 2
                    && !isset($this->addonDic['s'][$cw]) && !isset($this->addonDic['t'][$cw]) && !isset($this->addonDic['a'][$cw])
                    && !isset($this->addonDic['s'][$nw]) && !isset($this->addonDic['c'][$nw])
                ) {
                    $newarr[$j] = $cw . $nw;
                    //尝试检测第三个词
                    if (isset($smarr[$i + 2]) && strlen($smarr[$i + 2]) == 2 && (isset($this->addonDic['a'][$smarr[$i + 2]]) || isset($this->addonDic['u'][$smarr[$i + 2]]))) {
                        $newarr[$j] .= $smarr[$i + 2];
                        $i++;
                    }
                    if (!isset($this->newWords[$newarr[$j]])) {
                        $this->foundWordStr .= StringTool::encoding($newarr[$j],$this->targetCharSet) . '/ms, ';
                        $this->SetWordInfos($newarr[$j], ['c' => 1, 'm' => 'ms']);
                    }
                    $i++;
                    $j++;
                    $ischeck = true;
                }
            }

            //不符合规则
            if (!$ischeck) {
                $newarr[$j] = $cw;
                //二元消岐处理——最大切分模式
                if ($this->differMax && !isset($this->addonDic['s'][$cw]) && strlen($cw) < 5 && strlen($nw) < 7) {
                    $slen = strlen($nw);
                    $hasDiff = false;
                    for ($y = 2; $y <= $slen - 2; $y = $y + 2) {
                        $nhead = substr($nw, $y - 2, 2);
                        $nfont = $cw . substr($nw, 0, $y - 2);
                        if ($this->IsWord($nfont . $nhead)) {
                            if (strlen($cw) > 2) $j++;
                            $hasDiff = true;
                            $newarr[$j] = $nfont . $nhead;
                        }
                    }
                }
                $j++;
            }

        }//end for
        $smarr = $newarr;
    }



    /**
     * 获取最终结果字符串（用空格分开后的分词结果）
     * @return string
     */
    public function getFinallyResult($spword = ' ', $word_meanings = false)
    {
        $rsstr = '';
        foreach ($this->finallyResult as $v) {
            if ($this->resultType == 2 && ($v['t'] == 3 || $v['t'] == 5)) {
                continue;
            }
            $m = '';
            if ($word_meanings) {
                $m = $this->GetWordProperty($v['w']);
            }
            $w = StringTool::encoding($v['w'],$this->targetCharSet);
            if ($w != ' ') {
                if ($word_meanings) {
                    $rsstr .= $spword . $w . $m;
                } else {
                    $rsstr .= $spword . $w;
                }
            }
        }
        return $rsstr;
    }

    /**
     * 获取粗分结果，不包含粗分属性
     * @return array()
     */
    public function GetSimpleResult()
    {
        $rearr = [];
        foreach ($this->simpleResult as $k => $v) {
            if (empty($v['w'])) continue;
            $w = StringTool::encoding($v['w'],$this->targetCharSet);
            if ($w != ' ') $rearr[] = $w;
        }
        return $rearr;
    }

    /**
     * 获取粗分结果，包含粗分属性（1中文词句、2 ANSI词汇（包括全角），3 ANSI标点符号（包括全角），4数字（包括全角），5 中文标点或无法识别字符）
     * @return array()
     */
    public function GetSimpleResultAll()
    {
        $rearr = [];
        foreach ($this->simpleResult as $k => $v) {
            $w = StringTool::encoding($v['w'],$this->targetCharSet);
            if ($w != ' ') {
                $rearr[$k]['w'] = $w;
                $rearr[$k]['t'] = $v['t'];
            }
        }
        return $rearr;
    }

    /**
     * 获取最终关键字(返回用 "," 间隔的关键字)
     * @return string
     */
    public function GetFinallyKeywords($num = 10)
    {
        $n = 0;

        // 获取索引hash数组
        $arr = [];
        foreach ($this->finallyResult as $v) {
            if ($this->resultType == 2 && ($v['t'] == 3 || $v['t'] == 5)) {
                continue;
            }
            $w = StringTool::encoding($v['w'],$this->targetCharSet);
            if ($w == ' ') {
                continue;
            }
            if (isset($arr[$w])) {
                $arr[$w]++;
            } else {
                $arr[$w] = 1;
            }
        }
        arsort($arr);

        $okstr = '';
        foreach ($arr as $k => $v) {
            //排除长度为1的词
            if (strlen($k) == 1) {
                continue;
            } //排除长度为2的非英文词
            elseif (strlen($k) == 2 && preg_match('/[^0-9a-zA-Z]/', $k)) {
                continue;

            } //排除单个中文字
            elseif (strlen($k) < 4 && !preg_match('/[a-zA-Z]/', $k)) {
                continue;
            }
            $okstr .= ($okstr == '' ? $k : ',' . $k);
            $n++;
            if ($n > $num) break;
        }

        return $okstr;
    }
}

?>
