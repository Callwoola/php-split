<?php

namespace phpSplit\Dict;

class DictGenerator {



    /**
     * 编译词典
     * 注意, 需要PHP开放足够的内存才能完成操作
     * utf-8编码的文本词典数据文件<参见范例dict/not-build/base_dic_full.txt>
     *
     * @param $source_file
     * @param $target_file
     *
     * @return void
     */
    public function MakeDict($source_file, $target_file = '')
    {
        $target_file = ($target_file == '' ? $this->mainDicFile : $target_file);
        $allk = [];
        $fp = fopen($source_file, 'r');
        while ($line = fgets($fp, 512)) {
            if ($line[0] == '@') continue;
            list($w, $r, $a) = explode(',', $line);
            $a = trim($a);
            $w = iconv('utf-8', UCS2, $w);
            $k = $this->_get_index($w);
            if (isset($allk[$k]))
                $allk[$k][$w] = [$r, $a];
            else
                $allk[$k][$w] = [$r, $a];
        }
        fclose($fp);
        $fp = fopen($target_file, 'w');
        $heade_rarr = [];
        $alldat = '';
        $start_pos = $this->mask_value * 8;
        foreach ($allk as $k => $v) {
            $dat = serialize($v);
            $dlen = strlen($dat);
            $alldat .= $dat;

            $heade_rarr[$k][0] = $start_pos;
            $heade_rarr[$k][1] = $dlen;
            $heade_rarr[$k][2] = count($v);

            $start_pos += $dlen;
        }
        unset($allk);
        for ($i = 0; $i < $this->mask_value; $i++) {
            if (!isset($heade_rarr[$i])) {
                $heade_rarr[$i] = [0, 0, 0];
            }
            fwrite($fp, pack("Inn", $heade_rarr[$i][0], $heade_rarr[$i][1], $heade_rarr[$i][2]));
        }
        fwrite($fp, $alldat);
        fclose($fp);
    }

    /**
     * 导出词典的词条
     * 保存位置
     *
     * @param $targetFile
     *
     * @return bool
     */
    public function exportDict($targetFile)
    {
        if (!$this->mainDicHand) {
            $this->mainDicHand = fopen($this->mainDicFile, 'r');
        }
        $fp = fopen($targetFile, 'w');

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


    /**
     * @param $source_str
     * @return string
     */
    public function exportDictCore($source_str)
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
