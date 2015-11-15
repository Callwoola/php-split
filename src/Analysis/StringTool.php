<?php
namespace phpSplit\Analysis;


/**
 * 把uncode字符串转换为输出字符串
 * @parem str
 * return string
 */

class StringTool
{

    public static function encoding(&$str , $targetCharSet)
    {
        // 获得保存目标编码
        if (preg_match("/^utf/", $targetCharSet)) {
            $rsc = 1;
        } else if (preg_match("/^gb/", $targetCharSet)) {
            $rsc = 2;
        } else if (preg_match("/^big/", $targetCharSet)) {
            $rsc = 3;
        } else {
            $rsc = 4;
        }

        if ($rsc == 1) {
            $rsstr = iconv(UCS2, 'utf-8', $str);
        } else if ($rsc == 2) {
            $rsstr = iconv('utf-8', 'gb18030', iconv(UCS2, 'utf-8', $str));
        } else {
            $rsstr = iconv('utf-8', 'big5', iconv(UCS2, 'utf-8', $str));
        }

        return $rsstr;
    }
}
