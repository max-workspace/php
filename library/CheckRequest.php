<?php

namespace library;

use library\FormatResult;

class CheckRequest {
    // 请求的有效期15min
    const REQUESTDURATION = 900;

    public static function validSignature(array $params, array $appList, $checkSignatureTimestamp = true)
    {
        try {
            if (empty($params) || empty($appList) || !isset($params['appKey'])) {
                throw new \Exception("缺少必要的参数");
            }
            // 根据appKey获取对应appSecret
            if (!isset($appList[$params['appKey']])) {
                throw new \Exception('无效的appKey');
            }
            $appSecret = $appList[$params['appKey']];

            // 检测请求是否在有效期内
            if ($checkSignatureTimestamp) {
                if (!isset($params['signatureTimestamp'])) {
                    throw new \Exception('缺少请求签名的时间戳');
                } elseif ((time() - $params['signatureTimestamp']) > self::REQUESTDURATION) {
                    throw new \Exception('超过了请求的有效期间');
                }
            }

            // 验证请求的参数签名
            $originSignature = $params['signature'];
            unset($params['signature']);
            $result = self::generateSignature($params, $appSecret, $checkSignatureTimestamp);
            if (!isset($result['code']) || $result['code'] != FormatResult::DEFAULTSUCCESSCODE) {
                throw new \Exception($result['desc']);
            }
            if ($result['data'] != $originSignature) {
                throw new \Exception('无效的参数签名');
            }

            return FormatResult::success();
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return FormatResult::error($errorMessage);
        }
    }

    public static function generateSignature(array $params, $appSecret, $checkSignatureTimestamp = true)
    {
        try {
            if (empty($params) || empty($appSecret) || !isset($params['appKey'])) {
                throw new \Exception('缺少必要的参数');
            }
            if ($checkSignatureTimestamp && !isset($params['signatureTimestamp'])) {
                throw new \Exception('缺少请求签名的时间戳');
            }

            ksort($params);
            $signature = http_build_query($params);
            // 生成签名的时候拼接appSecret，但在请求连接中不携带appSecret
            $signature .= "&appSecret={$appSecret}";
            $signature  = strtoupper(md5($signature));
            $signature = base64_encode($signature);
            return FormatResult::success($signature);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return FormatResult::error($errorMessage);
        }
    }

}