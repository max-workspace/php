<?php
namespace App\Repository;

use OFashion\Log\Log;
use Illuminate\Support\Facades\Mail;
use App\Repository\BaseServiceRepository;

class VinjakApiRepository
{
    private $apiPath = 'http://shop.vinjakmoda.com';

    private $imagePath = 'http://vinjak.mkoteam.com';

    private $apiAddressList = [
        'getGoodsList' => '/api/goods/list',
        'getGoodsDetail' => '/api/goods_detail',
    ];

    private $reciveEmailAddress = [
        'hongfei.wu@mfashion.com.cn',
    ];

    public function sendEmail($title, $content)
    {
        try {
            $reciveEmailAddress = $this->reciveEmailAddress;
            Mail::raw($content, function($m) use ($title, $reciveEmailAddress) {
                $m->to($reciveEmailAddress)->subject($title);
            });
        } catch (\Exception $e) {
            Log::warning('error:erp:vinjak:sendEmail', ['msg' => $e->getMessage()], 'error_log/erp/vinjak/sendEmail');
        }
        return;
    }

    public function getVinjakGoodsList($page = 1, $pageSize = 50, $allStock = 1)
    {
        $data = [
            'page' => $page,
            'page_size' => $pageSize,
            'all_stock' => $allStock,
        ];
        $serviceType = $this->apiAddressList['getGoodsList'];
        $response = BaseServiceRepository::executeService($serviceType, 'get', $data, $this->apiPath, 60 * 1000, 60 * 1000, 1);

        if (isset($response['code']) && $response['code'] == 0 && !empty($response['data'])) {
            return ['status' => true, 'data' => $response['data']];
        }

        if (!empty($response['message'])) {
            $errMessage = $response['message'];
        } elseif (!empty($response['msg'])) {
            $errMessage = $response['msg'];
        } else {
            $errMessage = '服务:请求失败';
        }

        $title = 'error:erp:vinjak:getVinjakGoodsList';
        $logContent = [
            'msg' => $errMessage,
            'queryData' => $data,
            'description' => '获取erp商品列表信息失败'
        ];
        $emailContent = json_encode($logContent);
        Log::warning($title, $logContent, 'error_log/erp/vinjak/getErpGoodsList');
        $this->sendEmail($title, $emailContent);

        return ['status' => false, 'msg' => $errMessage];
    }

    public function getVinjakGoodsDetail($goodsCode, $type = 1)
    {
        if ($type == 1) {
            $key = 'id';
        } elseif ($type == 2) {
            $key = 'mode_no';
        }
        $data = [
            $key => $goodsCode,
        ];

        $serviceType = $this->apiAddressList['getGoodsDetail'];
        $response = BaseServiceRepository::executeService($serviceType, 'get', $data, $this->apiPath, 60 * 1000, 60 * 1000, 1);

        if (isset($response['code']) && $response['code'] == 0 && !empty($response['data'])) {
            return ['status' => true, 'data' => $response['data']];
        }

        if (!empty($response['message'])) {
            $errMessage = $response['message'];
        } elseif (!empty($response['msg'])) {
            $errMessage = $response['msg'];
        } else {
            $errMessage = '服务:请求失败';
        }

        $title = 'error:erp:vinjak:getVinjakGoodsDetail';
        $logContent = [
            'msg' => $errMessage,
            'queryData' => $data,
            'description' => '获取erp商品详情信息失败'
        ];
        $emailContent = json_encode($logContent);
        Log::warning($title, $logContent, 'error_log/erp/vinjak/getErpGoodsDetail');
        $this->sendEmail($title, $emailContent);

        return ['status' => false, 'msg' => $errMessage];
    }
}