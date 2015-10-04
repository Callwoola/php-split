# phpSplit php中文分词库
============================
[![Build Status](https://travis-ci.org/Callwoola/LBS.svg?branch=develop)](https://travis-ci.org/Callwoola/LBS)


phpSplit 是一个基于php开发的中文分词库

居于Unicode编码词典的php分词器
* 只适用于php5，必要函数 iconv
* 本程序是使用RMM逆向匹配算法进行分词的，词库需要特别编译，本类里提供了 MakeDict() 方法
* 简单操作流程： SetSource -> StartAnalysis -> GetResult
* 对主词典使用特殊格式进行编码, 不需要载入词典到内存操作


分词结果后缀说明
```php
名词n、
时间词t、
处所词s、
方位词f、
数词m、
量词q、
区别词b、
代词r、
动词v、
形容词a、
状态词z、
副词d、
介词p、
连词c、
助词u、
语气词y、
叹词e、
拟声词o、
成语i、
习用语l、
简称j、
前接成分h、
后接成分k、
语素g、
非语素字x、
标点符号w
```

同事增加了以下3类标记
*专有名词的分类标记，即人名nr，地名ns，团体机关单位名称nt，其他专有名词nz；
*语素的子类标记，即名语素Ng，动语素Vg，形容语素Ag，时语素Tg，副语素Dg等；
*动词和形容词的子类标记，即名动词vn（具有名词特性的动词），名形词an（具有名词特性的形容词），副动词vd（具有副词特性的动词），副形词ad（具有副词特性的形容词）

合计约40个左右。

欢迎大家完善

