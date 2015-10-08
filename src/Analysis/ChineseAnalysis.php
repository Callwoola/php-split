<?php
namespace phpSplit\Analysis;


//常量定义
define('_SP_', chr(0xFF) . chr(0xFE));
define('UCS2', 'ucs-2be');

//class ChineseAnalysis extends Config



abstract class ChineseAnalysis
{
    use Loader;
    use Config;


    /**
     * 构造函数
     * @param $source_charset
     * @param $target_charset
     * @param $load_alldic
     * @param $source
     */
    public function __construct($source_charset = 'utf-8', $target_charset = 'utf-8', $load_all = true, $source = '')
    {
//        $this->addonDicFile = dirname(__FILE__) . '/' . $this->addonDicFile;
//        $this->mainDicFile = dirname(__FILE__) . '/' . $this->mainDicFile;
        $this->SetSource($source, $source_charset, $target_charset);
        $this->isLoadAll = $load_all;
        // auto load
//        if (self::$loadInit) $this->LoadDict();
        list($mainDicHand, $mainDic, $additionDict, $loadTime) = $this->getLoadDict();

        $this->mainDicHand = $mainDicHand;
        $this->mainDic = $mainDic;
        $this->addonDic = $additionDict;
        $this->loadTime = $loadTime;
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
     * 根据字符串计算key索引
     * @param $key
     * @return short int
     */
    private function _get_index($key)
    {
        $l = strlen($key);
        $h = 0x238f13af;
        while ($l--) {
            $h += ($h << 5);
            $h ^= ord($key[$l]);
            $h &= 0x7fffffff;
        }
        return ($h % $this->mask_value);
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
        $keynum = $this->_get_index($key);
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
    public function SetSource($source, $source_charset = 'utf-8', $target_charset = 'utf-8')
    {
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



//    /**
//     * 载入词典
//     *
//     * @return void
//     */
//    public function LoadDict($maindic = '')
//    {
//        $startt = microtime(true);
//        //正常读取文件
//        $dicAddon = $this->addonDicFile;
//        if ($maindic == '' || !file_exists($maindic)) {
//            $dicWords = $this->mainDicFile;
//        } else {
//            $dicWords = $maindic;
//            $this->mainDicFile = $maindic;
//        }
//
//        //加载主词典（只打开）
//        $this->mainDicHand = fopen($dicWords, 'r');
//
//        //加载附加的 分词
//        if (!empty($this->additionDict)) {
//            $this->mainDicHand = $this->mainDicHand . $this->getAdditionDict();
//        }
//
//        //载入副词典
//        $hw = '';
//        $ds = file($dicAddon);
//        foreach ($ds as $d) {
//            $d = trim($d);
//            if ($d == '') continue;
//            $estr = substr($d, 1, 1);
//            if ($estr == ':') {
//                $hw = substr($d, 0, 1);
//            } else {
//                $spstr = _SP_;
//                $spstr = iconv(UCS2, 'utf-8', $spstr);
//                $ws = explode(',', $d);
//                $wall = iconv('utf-8', UCS2, join($spstr, $ws));
//                $ws = explode(_SP_, $wall);
//                foreach ($ws as $estr) {
//                    $this->addonDic[$hw][$estr] = strlen($estr);
//                }
//            }
//        }
//        $this->loadTime = microtime(true) - $startt;
//        $this->isLoadDic = true;
//    }

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
     * 获取最终结果字符串（用空格分开后的分词结果）
     * @return string
     */
    public function GetFinallyResult($spword = ' ', $word_meanings = false)
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
            $w = $this->_out_string_encoding($v['w']);
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
            $w = $this->_out_string_encoding($v['w']);
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
            $w = $this->_out_string_encoding($v['w']);
            if ($w != ' ') {
                $rearr[$k]['w'] = $w;
                $rearr[$k]['t'] = $v['t'];
            }
        }
        return $rearr;
    }

    /**
     * 获取索引hash数组
     * @return array('word'=>count,...)
     */
    public function GetFinallyIndex()
    {
        $rearr = [];
        foreach ($this->finallyResult as $v) {
            if ($this->resultType == 2 && ($v['t'] == 3 || $v['t'] == 5)) {
                continue;
            }
            $w = $this->_out_string_encoding($v['w']);
            if ($w == ' ') {
                continue;
            }
            if (isset($rearr[$w])) {
                $rearr[$w]++;
            } else {
                $rearr[$w] = 1;
            }
        }
        arsort($rearr);
        return $rearr;
    }

    /**
     * 获取最终关键字(返回用 "," 间隔的关键字)
     * @return string
     */
    public function GetFinallyKeywords($num = 10)
    {
        $n = 0;
        $arr = $this->GetFinallyIndex();
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

    /**
     * 获得保存目标编码
     * @return int
     */
    private function _source_result_charset()
    {
        if (preg_match("/^utf/", $this->targetCharSet)) {
            $rs = 1;
        } else if (preg_match("/^gb/", $this->targetCharSet)) {
            $rs = 2;
        } else if (preg_match("/^big/", $this->targetCharSet)) {
            $rs = 3;
        } else {
            $rs = 4;
        }
        return $rs;
    }

//    /**
//     * 编译词典
//     * @parem $sourcefile utf-8编码的文本词典数据文件<参见范例dict/not-build/base_dic_full.txt>
//     * 注意, 需要PHP开放足够的内存才能完成操作
//     * @return void
//     */
//    public function MakeDict($source_file, $target_file = '')
//    {
//        $target_file = ($target_file == '' ? $this->mainDicFile : $target_file);
//        $allk = [];
//        $fp = fopen($source_file, 'r');
//        while ($line = fgets($fp, 512)) {
//            if ($line[0] == '@') continue;
//            list($w, $r, $a) = explode(',', $line);
//            $a = trim($a);
//            $w = iconv('utf-8', UCS2, $w);
//            $k = $this->_get_index($w);
//            if (isset($allk[$k]))
//                $allk[$k][$w] = [$r, $a];
//            else
//                $allk[$k][$w] = [$r, $a];
//        }
//        fclose($fp);
//        $fp = fopen($target_file, 'w');
//        $heade_rarr = [];
//        $alldat = '';
//        $start_pos = $this->mask_value * 8;
//        foreach ($allk as $k => $v) {
//            $dat = serialize($v);
//            $dlen = strlen($dat);
//            $alldat .= $dat;
//
//            $heade_rarr[$k][0] = $start_pos;
//            $heade_rarr[$k][1] = $dlen;
//            $heade_rarr[$k][2] = count($v);
//
//            $start_pos += $dlen;
//        }
//        unset($allk);
//        for ($i = 0; $i < $this->mask_value; $i++) {
//            if (!isset($heade_rarr[$i])) {
//                $heade_rarr[$i] = [0, 0, 0];
//            }
//            fwrite($fp, pack("Inn", $heade_rarr[$i][0], $heade_rarr[$i][1], $heade_rarr[$i][2]));
//        }
//        fwrite($fp, $alldat);
//        fclose($fp);
//    }

    /**
     * 导出词典的词条
     * @parem $targetfile 保存位置
     * @return void
     */
    public function ExportDict($targetfile)
    {
        if (!$this->mainDicHand) {
            $this->mainDicHand = fopen($this->mainDicFile, 'r');
        }
        $fp = fopen($targetfile, 'w');
//        for ($i = 0; $i <= $this->mask_value; $i++) {
//            $move_pos = $i * 8;
//            fseek($this->mainDicHand, $move_pos, SEEK_SET);
//            $dat = fread($this->mainDicHand, 8);
//            $arr = unpack('I1s/n1l/n1c', $dat);
//            if ($arr['l'] == 0) {
//                continue;
//            }
//            fseek($this->mainDicHand, $arr['s'], SEEK_SET);
//            $data = @unserialize(fread($this->mainDicHand, $arr['l']));
//            if (!is_array($data)) continue;
//            foreach ($data as $k => $v) {
//                $w = iconv(UCS2, 'utf-8', $k);
//                fwrite($fp, "{$w},{$v[0]},{$v[1]}\n");
//            }
//        }
        fwrite($fp, $this->ExportDictCore($this->mainDicHand));
        fclose($fp);
        return true;
    }

    public function ExportDictCore($source_str)
    {
        $str = '';
        for ($i = 0; $i <= $this->mask_value; $i++) {
            $move_pos = $i * 8;
            fseek($source_str, $move_pos, SEEK_SET);
            $dat = fread($source_str, 8);
            $arr = unpack('I1s/n1l/n1c', $dat);
            if ($arr['l'] == 0) {
                continue;
            }
            fseek($source_str, $arr['s'], SEEK_SET);
            $data = @unserialize(fread($source_str, $arr['l']));
            if (!is_array($data)) continue;
            foreach ($data as $k => $v) {
                $w = iconv(UCS2, 'utf-8', $k);
//                fwrite($fp, "{$w},{$v[0]},{$v[1]}\n");
                $str .= "{$w},{$v[0]},{$v[1]}\n";
            }
        }
        return $str;
    }

}

?>
