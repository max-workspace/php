<?php
namespace App\Repository;


abstract class BaseSyncErpGoodsStockRepository
{
    public $start = 0;

    public $count = 50;

    public $emailAddress = null;

    public $logFilePath = null;

    abstract public function getSystemGoodsData($start, $count);

    abstract public function getErpGoodsIdList(array $goodsData);

    abstract public function getErpGoodsData(array $erpGoodsIdList);

    abstract public function formatSystemGoodsData(array $goodsData, array $erpGoodsData);

    abstract public function filterSystemGoodsData(array $goodsData);

    abstract public function sendSystemGoodsData(array $goodsData);

    abstract public function recordLog($title, $content, $logFilePath);

    abstract public function sendEmail($title, $content, $emailAddress);

    public function extraHandleSystemGoodsData(array $goodsData)
    {
        return $goodsData;
    }

    public function finishSendSystemGoodsData(array $result, array $goodsData)
    {
        return;
    }

    public function createErpGoods()
    {
        try {
            echo '同步erp商品库存开始' . date("Y-m-d H:i:s") . "\n";
            $start = $this->start;
            $count = $this->count;
            do {
                $result = $this->getSystemGoodsData($start, $count);
                if (!$result['status'] || empty($result['data'])) {
                    break;
                }
                $goodsData = $result['data'];
                $goodsNumber = count($goodsData);
                $start += $goodsNumber;

                $erpGoodsData = [];
                $erpGoodsIdList = $this->getErpGoodsIdList($goodsData);
                $result = $this->getErpGoodsData($erpGoodsIdList);
                if (!$result['status'] || empty($result['data'])) {
                    $erpGoodsData = $result['data'];
                }

                $goodsData = $this->formatSystemGoodsData($goodsData, $erpGoodsData);

                $goodsData = $this->filterSystemGoodsData($goodsData);

                $goodsData = $this->extraHandleSystemGoodsData($goodsData);

                $result = $this->sendSystemGoodsData($goodsData);

                $this->finishSendSystemGoodsData($result, $goodsData);
            } while ($count == $goodsNumber);
        } catch (\Exception $e) {
            $errorTitle = 'Error:SyncErpGoodsStock';
            $errorContent = (string)$e;
            $this->recordLog($errorTitle, $errorContent, $this->logFilePath);
            $this->sendEmail($errorTitle, $errorContent, $this->emailAddress);
        }
        echo '同步erp商品库存结束' . date("Y-m-d H:i:s") . "\n";
    }
}