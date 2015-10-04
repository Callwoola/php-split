<?php

namespace phpSplit\Analysis;

//abstract class Config{
trait Config{

    //hash算法选项
    public $mask_value = 0xFFFF;

    //输入和输出的字符编码（只允许 utf-8、gbk/gb2312/gb18030、big5 三种类型）
    public $sourceCharSet = 'utf-8';
    public $targetCharSet = 'utf-8';

    //生成的分词结果数据类型 1 为全部， 2为 词典词汇及单个中日韩简繁字符及英文， 3 为词典词汇及英文
    public $resultType = 1;

    //句子长度小于这个数值时不拆分，notSplitLen = n(个汉字) * 2 + 1
    public $notSplitLen = 5;

    //把英文单词全部转小写
    public $toLower = false;

    //使用最大切分模式对二元词进行消岐
    public $differMax = false;

    //尝试合并单字
    public $unitWord = true;

    //初始化类时直接加载词典
    public static $loadInit = true;

    //使用热门词优先模式进行消岐
    public $differFreq = false;

    //被转换为unicode的源字符串
    public $sourceString = '';

    //附加词典
    public $addonDic = [];
//    public $addonDicFile = 'dict/words_addons.dic';

    //主词典
    public $dicStr = '';
    public $mainDic = [];
    public $mainDicHand = false;
    public $mainDicInfos = [];
//    public $mainDicFile = 'dict/base_dic_full.dic';

    //是否直接载入词典（选是载入速度较慢，但解析较快；选否载入较快，但解析较慢，需要时才会载入特定的词条）
    public $isLoadAll = false;

    //主词典词语最大长度 x / 2
    public $dicWordMax = 14;
    //粗分后的数组（通常是截取句子等用途）
    public $simpleResult = [];
    //最终结果(用空格分开的词汇列表)
    public $finallyResult = '';

    //是否已经载入词典
    public $isLoadDic = false;
    //系统识别或合并的新词
    public $newWords = [];
    public $foundWordStr = '';
    //词库载入时间
    public $loadTime = 0;

    // 附加分词数据 以数组传参数
    public $additionDict = [];
}
