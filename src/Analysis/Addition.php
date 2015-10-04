<?php


class Addition
{

    public $additionDict = [];

    /**
     * @return String like -> '专用机,6,n...'
     */
    public function getAdditionDict()
    {
        $returnString = "";
        foreach ($this->additionDict as $value) {
            $returnString .= "\n{$value},100,nx";
        }

        return $returnString;
    }

    /**
     * @param array $arr
     */
    public function setAdditionDict($arr = [])
    {
        $this->additionDict = $arr;
    }
}
