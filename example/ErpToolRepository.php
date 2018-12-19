<?php
namespace App\Repository;

use App\Models\BrandModel;
use App\Models\OpenBrandModel;

class ErpToolRepository
{
    public function getNormalizedName(string $data)
    {
        $temp = strtolower($data);
        $temp = preg_replace("/[\'.]/", "", $temp);
        $temp = preg_replace("/[&+-\/]/", " ", $temp);
        $res = preg_replace("/ +/", "_", trim($temp));
        return $res;
    }

    public function createBrandInfo($insertBrandNameList, $brandInfoList)
    {
        foreach ($insertBrandNameList as $brandName) {
            if (empty($brandInfoList[$brandName]['name_e'])) {
                continue;
            }
            if (empty($brandInfoList[$brandName]['name_c'])) {
                $brandInfoList[$brandName]['name_c'] = $brandInfoList[$brandName]['name_e'];
            }
            $bid = BrandModel::max('brand_id');
            $bid++;
            $brandInfo = [
                'brand_id' => $bid,
                'name_e' => strval($brandInfoList[$brandName]['name_e']),
                'name_c' => strval($brandInfoList[$brandName]['name_c']),
                'normalized_name' => strval($brandName),
                'status' => 0,
            ];
            try {
                BrandModel::insert($brandInfo);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    public function getBrandInfoMap(array $brandInfoList):array
    {
        if (empty($brandInfoList)) {
            return [];
        }

        $column = ['brand_id', 'name_c', 'name_e', 'normalized_name', 'status'];
        $normalizedNameList = array_unique(array_column($brandInfoList, 'normalized_name'));
        $ofBrandInfoList = BrandModel::getBrandInfoListByNormalizedNameList($column, $normalizedNameList);

        $insertBrandNameList = array_diff($normalizedNameList, array_unique(array_column($ofBrandInfoList, 'normalized_name')));
        $this->createBrandInfo($insertBrandNameList, $brandInfoList);

        $brandInfoAble = [];
        $brandInfoUnable = [];
        foreach ($ofBrandInfoList as $ofbrandInfoItem) {
            if ($ofbrandInfoItem['status'] == 1) {
                $brandInfoAble[] = $ofbrandInfoItem;
            }else {
                $brandInfoUnable[] = $ofbrandInfoItem;
            }
        }
        $brandInfoUnable = array_column($brandInfoUnable, null, 'brand_id');

        $openBrandIdList = array_column($brandInfoUnable, 'brand_id');
        $openBrandInfoList = OpenBrandModel::getOpenBrandInfoListByOpenBrandIdList($openBrandIdList);

        $ofBrandIdList = array_column($openBrandInfoList, 'of_brand_id');
        $brandInfoMapping = BrandModel::getBrandInfoListByBrandIdList($column, $ofBrandIdList, [1]);
        $brandInfoMapping = array_column($brandInfoMapping, null, 'brand_id');

        $brandInfoReAble = [];
        foreach ($openBrandInfoList as $openBrandItem) {
            $openBrandId = $openBrandItem['open_brand_id'];
            $ofBrandId = $openBrandItem['of_brand_id'];
            if (empty($brandInfoUnable[$openBrandId]) || empty($brandInfoMapping[$ofBrandId])) {
                continue;
            }
            $item['normalized_name'] = $brandInfoUnable[$openBrandId]['normalized_name'];
            $item['brand_id'] = $brandInfoMapping[$ofBrandId]['brand_id'];
            $item['name_c'] = $brandInfoMapping[$ofBrandId]['name_c'];
            $item['name_e'] = $brandInfoMapping[$ofBrandId]['name_e'];
            $item['status'] = $brandInfoMapping[$ofBrandId]['status'];
            $brandInfoReAble[] = $item;
        }
        $brandInfoAble = array_column($brandInfoAble, null, 'normalized_name');
        $brandInfoReAble = array_column($brandInfoReAble, null, 'normalized_name');
        $ofBrandInfoAbleList = array_merge($brandInfoAble, $brandInfoReAble);

        return $ofBrandInfoAbleList;
    }

    public function getImageInfoMap(array $imageInfoList):array
    {

    }
}