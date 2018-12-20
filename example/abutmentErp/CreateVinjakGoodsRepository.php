<?php
namespace App\Repository;

use OFashion\Log\Log;
use Illuminate\Support\Facades\Mail;
use App\Repository\ErpToolRepository;
use App\Repository\VinjakApiRepository;
use App\Repository\BaseCreateErpGoodsRepository;
use App\Models\ProductInfoModel;

class CreateVinjakGoodsRepository extends BaseCreateErpGoodsRepository
{
    public $supplierNo = null;

    public $erpTool = null;

    public $vinjakApiRepository = null;

    public $maxPrice = 999999900;

    public $currency = 'HKD';

    public $deliveryPlace = 1;

    public $channelSource = 1;

    public $logFilePath = 'error_log/erp/createVinjakGoods';

    public $emailAddress = [
        'hongfei.wu@mfashion.com.cn',
    ];

    public $supplierNoList = [
        0 => 'gj081791',
        1 => 'gj917903',
    ];

    public $typeListMap = [
        'woman' => [
            1 => 32,
            2 => 32,
            7 => 165,
            8 => 72,
            9 => 201,
            10 => 131,
            11 => 72,
            13 => 165,
            14 => 131,
            15 => 201,
            16 => 296,
            17 => 296,
            18 => 296,
            19 => 296,
            20 => 296,
            21 => 249,
            22 => 220,
            23 => 249,
            24 => 220,
            25 => 32,
            26 => 32,
        ],
        'man' => [
            1 => 63,
            2 => 63,
            7 => 160,
            8 => 47,
            9 => 182,
            10 => 131,
            11 => 47,
            13 => 160,
            14 => 131,
            15 => 182,
            16 => 296,
            17 => 296,
            18 => 296,
            19 => 296,
            20 => 296,
            21 => 249,
            22 => 220,
            23 => 249,
            24 => 220,
            25 => 63,
            26 => 63,
        ],
    ];

    public function __construct()
    {
        $this->erpTool = new ErpToolRepository();
        $this->vinjakApiRepository = new VinjakApiRepository();

        $isOnline = env('APP_ENV') == 'production' ? 1 : 0;
        $this->supplierNo = $this->supplierNoList[$isOnline];
        return;
    }

    public function handle()
    {
        $this->createErpGoods();
        return;
    }

    public function recordLog($title, $content, $logFilePath = '')
    {
        empty($logFilePath) && $logFilePath = $this->logFilePath;
        $this->erpTool->recordLog($title, $content, $logFilePath);
        return;
    }

    public function sendEmail($title, $content, $emailAddress = [])
    {
        empty($emailAddress) && $emailAddress = $this->emailAddress;
        $this->erpTool->sendEmail($title, $content, $emailAddress);
        return;
    }

    public function getErpGoodsData($start, $count)
    {
        return $this->vinjakApiRepository->getVinjakGoodsList($start, $count);
    }

    public function formatErpGoodsData($goodsData)
    {
        // 获取系统内已经录入的商品信息
        $productColumn = ['bar_code', 'show_status'];
        $barCodeList = array_column($goodsData, 'mode_no');
        $productInfoList = ProductInfoModel::getProductInfoByBarCodeList($productColumn, $barCodeList, $this->supplierNo);
        $productInfoList = array_column($productInfoList, null, 'bar_code');

        // 过滤掉系统内已经存在的商品
        $goodsData = array_filter($goodsData, function($goodsDataItem) use ($productInfoList) {
            $barCode = $goodsDataItem['mode_no'];
            if (array_key_exists($barCode, $productInfoList)) {
                return false;
            }
            return true;
        });

        // 获取品牌映射列表
        $brandInfoList = array_map(function ($goodsDataItem) {
            return [
                'normalized_name' => $this->erpTool->getNormalizedName($goodsDataItem['brand']['title'] ?? ''),
                'name_e' => $goodsDataItem['brand']['title'] ?? '',
                'name_c' => $goodsDataItem['brand']['title2'] ?? '',
            ];
        }, $goodsData);
        $brandInfoMap = $this->erpTool->getBrandInfoMap($brandInfoList);
        $brandInfoMap = array_column($brandInfoMap, null, 'normalized_name');

        // 格式化每个商品信息
        $formatGoodsData = [];
        foreach ($goodsData as $goodsDataItem) {
            $normalizedName = $this->erpTool->getNormalizedName($goodsDataItem['brand']['title'] ?? '');

            $genderAttribute = $this->getGenderAttribute($goodsDataItem);

            // 该供应商家的商品名称做特殊处理
            $productName = $this->getProductName($goodsDataItem, $genderAttribute['nameOfgender']);

            // 该供应商家的商品描述做特殊处理品
            $desc = $this->getProductDesc($goodsDataItem);

            // 处理sku信息
            $skuInfoList = $goodsDataItem['detail_list'] ?? [];
            $skuInfoList = $this->getProductSku($skuInfoList);

            // 获取属性信息
            $productProperty = $this->getProductProperty($goodsDataItem, $genderAttribute);

            // 生成格式化后的商品信息
            $formatGoodsDataItem['show_status'] = 0; // 新创建的商品状态默认为0
            $formatGoodsDataItem['brand_id'] = $brandInfoMap[$normalizedName] ?? 0; // 0为之后要过滤的字段
            $formatGoodsDataItem['price'] = $this->maxPrice; // 不同步价格时取默认值
            $formatGoodsDataItem['official_price'] = $this->maxPrice; // 不同步价格时取默认值
            $formatGoodsDataItem['product_name'] = $productName; // 商品名称
            $formatGoodsDataItem['currency'] = $this->currency; // 该供应商家的币种取固定值，不依赖于接口返回值
            $formatGoodsDataItem['delivery_place'] = $this->deliveryPlace; // 该供应商家的发货地取固定值，不依赖于接口返回值
            $formatGoodsDataItem['cover_image'] = $goodsDataItem['cover_image'];
            $formatGoodsDataItem['detail_image'] = $goodsDataItem['detail_image'];
            $formatGoodsDataItem['channel_source'] = $this->channelSource; // 该供应商的渠道分类取固定值，不依赖于接口返回值
            $formatGoodsDataItem['stock'] = $goodsDataItem['stock'] ?? 0; // 0为字段不存在时取得默认值
            $formatGoodsDataItem['final_level_cate_id'] = $goodsDataItem['final_level_cate_id'];
            $formatGoodsDataItem['final_level_cate_name'] = $goodsDataItem['final_level_cate_name'];
            $formatGoodsDataItem['bar_code'] = $goodsDataItem['bar_code'] ?? ''; // 空为之后要过滤的字段
            $formatGoodsDataItem['product_property'] = $productProperty; // 产品属性
            $formatGoodsDataItem['product_sku'] = $skuInfoList; // 商品的sku信息
            $formatGoodsDataItem['description'] = $desc; // 商品描述
            $formatGoodsDataItem['virtual_cate_id'] = $goodsDataItem['virtual_cate_id'];
            $formatGoodsDataItem['product_currency'] = $this->currency; // 该商品的币种取固定值，不依赖于接口返回值
            $formatGoodsData[] = $formatGoodsDataItem;
        }
        return $formatGoodsData;
    }

    public function filterErpGoodsData($goodsData)
    {
        $goodsData = array_filter($goodsData, function($goodsDataItem) {
            // 过滤掉没有品牌的商品
            if (empty($goodsDataItem['brand_id'])) {
                return false;
            }
            // 过滤掉没有唯一标示的商品
            if (empty($goodsDataItem['bar_code'])) {
                return false;
            }
            // 过滤掉没有sku信息的商品
            if (empty($goodsDataItem['product_sku'])) {
                return false;
            }
            // 过滤掉没有货号的商品
            if (empty($goodsDataItem['product_property'][0]['value_list']['property_value'])) {
                return false;
            }
            return true;
        });

        return $goodsData;
    }

    public function sendErpGoodsData($goodsData)
    {

    }

    public function getGenderAttribute($goodsDataItem)
    {
        $genderAttribute = [
            'propertyValueId' => 14559,
            'propertyValue' => '女款',
            'nameOfgender' => '女士'
        ];
        if (isset($goodsDataItem['gender']) && $goodsDataItem['gender'] == 1) {
            $genderAttribute = [
                'propertyValueId' => 14558,
                'propertyValue' => '男款',
                'nameOfgender' => '男士'
            ];
        }
        return $genderAttribute;
    }

    public function getProductName($goodsDataItem, $nameOfgender)
    {
        $productName = '';
        !empty($goodsDataItem['brand']['title']) && $productName .= $goodsDataItem['brand']['title'] . ' ';
        !empty($goodsDataItem['brand']['title2']) && $productName .= $goodsDataItem['brand']['title2'] . ' ';
        !empty($nameOfgender) && $productName .= $nameOfgender . ' ';
        !empty($goodsDataItem['title']) && $productName .= $goodsDataItem['title'] . ' ';
        !empty($goodsDataItem['origin_no']) && $productName .= $goodsDataItem['origin_no'];
        return $productName;
    }

    public function getProductDesc($goodsDataItem)
    {
        $desc = '';
        $descAttribute = array_column($goodsDataItem['attribute'] ?? [], 'name');
        $descAttribute = implode(',', $descAttribute);
        !empty($goodsDataItem['material']) && ($goodsDataItem['material'] != '#') &&
        $desc .= "【商品材质】" . $goodsDataItem['material'] . "\n";
        !empty($goodsDataItem['origin_area']) && $desc .= "【商品产地】" . $goodsDataItem['origin_area'] . "\n";
        !empty($goodsDataItem['season_title']) && $desc .= "【款式季节】" . $goodsDataItem['season_title'] . "\n";
        !empty($descAttribute) && $desc .= "【其他属性】" . $descAttribute . "\n";
        !empty($goodsDataItem['describe']) && $desc .= "【商品描述】" . $goodsDataItem['describe'] . "\n";
        return $desc;
    }

    public function getProductSku($skuInfoList)
    {
        $formatSkuInfoList = [];
        foreach ($skuInfoList as $key => $skuInfoItem) {
            if (empty($skuInfoItem['$skuInfoItem']) || empty($skuInfoItem['skuID'])) {
                continue;
            }
            $colorKey = $skuInfoItem['colorKey'] ?? '';
            $colorName = empty($skuInfoItem['colorName']) ? '如图' : $skuInfoItem['colorName'];
            $formatSkuInfoItem = [
                'sku_color' => $colorName . ' ' . $colorKey, // 该供应商的颜色做特殊处理
                'sku_spec' => $skuInfoItem['sizeName'], // sku规格
                'sku_price' => $this->maxPrice, // 不同步价格时取默认值
                'sku_code' => $skuInfoItem['skuID'], // sku唯一编码
                'sku_stock' => $skuInfoItem['quantity'] ?? 0, // sku库存
            ];
            $formatSkuInfoList[] = $formatSkuInfoItem;
        }
        return $formatSkuInfoList;
    }

    public function getProductProperty($goodsDataItem, $genderAttribute)
    {
        return [
            [
                'property_id' => 1,
                'value_list' => [
                    [
                        'property_value_id' => 1,
                        'property_value' => $goodsDataItem['origin_no'] ?? '',
                    ]
                ],
                'property_name' => '货号'
            ],
            [
                'property_id' => 1426,
                'value_list' => [
                    [
                        'property_value_id' => $genderAttribute['propertyValueId'],
                        'property_value' => $genderAttribute['propertyValue'],
                    ]
                ],
                'property_name' => '款式'
            ]
        ];
    }
}