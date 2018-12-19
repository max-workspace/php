<?php
namespace App\Repository;


abstract class BaseCreateErpGoodsRepository
{
    public $start = 0;

    public $count = 50;

    public $emailAddress = null;

    public $logFilePath = null;

    abstract public function getErpGoodsData($start, $count);

    abstract public function formatErpGoodsData($goodsData);

    abstract public function filterErpGoodsData($goodsData);

    abstract public function sendErpGoodsData($goodsData);

    abstract public function recordLog($title, $content, $logFilePath);

    abstract public function sendEmail($title, $content, $emailAddress);

    public function extraHandleErpGoodsData($goodsData)
    {
        return $goodsData;
    }

    public function finishSendErpGoodsData($result, $goodsData)
    {
        return;
    }

    public function createErpGoods()
    {
        try {
            echo '创建erp商品开始' . date("Y-m-d H:i:s") . "\n";
            $start = $this->start;
            $count = $this->count;
            do {
                $result = $this->getErpGoodsData($start, $count);
                if (!$result['status'] || empty($result['data'])) {
                    break;
                }
                $goodsData = $result['data'];
                $goodsNumber = count($goodsData);
                $start += $goodsNumber;

                $goodsData = $this->formatErpGoodsData($goodsData);

                $goodsData = $this->filterErpGoodsData($goodsData);

                $goodsData = $this->extraHandleErpGoodsData($goodsData);

                $result = $this->sendErpGoodsData($goodsData);

                $this->finishSendErpGoodsData($result, $goodsData);
            } while ($count == $goodsNumber);
        } catch (\Exception $e) {
            $errorTitle = 'Error:CreateErpGoods';
            $errorContent = (string)$e;
            $this->recordLog($errorTitle, $errorContent, $this->logFilePath);
            $this->sendEmail($errorTitle, $errorContent, $this->emailAddress);
        }
        echo '创建erp商品结束' . date("Y-m-d H:i:s") . "\n";
    }
}