<?php
namespace phpSplit\Analysis;

interface ChineseAnalysisInterface
{
    public function SetSource();

    public function StartAnalysis();

    public function GetFinallyResult();
}
