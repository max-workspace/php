<?php

namespace library;

class LogService {
    const DIVISION = ':#:';

    /**
     * @param $desc
     * @param $itemInfo
     * @param string $result 处理的结果
     * @param string $extraName 日志文件名称的额外组成部分
     * @param string $extraDir 日志文件存储时额外的目录层级
     * @param string $division 默认分隔符
     * @param null $basePath 日志文件的默认存储目录
     */
    public static function addLog(
        $desc,
        $itemInfo,
        $result = '',
        $extraName = '',
        $extraDir = '',
        $division = null,
        $basePath = null
    ) {
        $division = is_null($division) ? self::DIVISION : $division;
        $basePath = is_null($basePath) ? __DIR__ . '/../log' : '/' . trim($basePath, '/');
        $extraDir = empty($extraDir) ? '' : '/' .trim($extraDir, '/');
        if (!is_dir($basePath . $extraDir)) {
            $extraDir = mkdir($basePath . $extraDir) ? $extraDir : '';
        }
        $fileName = date('Ymd') . "{$extraName}.log";
        $logPath = $basePath . $extraDir . "/{$fileName}";
        $itemInfo = is_array($itemInfo) ? json_encode($itemInfo, JSON_UNESCAPED_UNICODE) : $itemInfo;
        $result = is_array($result) ? json_encode($result, JSON_UNESCAPED_UNICODE) : $result;
        $logContent = implode($division, [
            date('Y-m-d H:i:s'),
            $desc,
            $itemInfo,
            $result,
        ]);
        file_put_contents($logPath, $logContent . "\n",FILE_APPEND);
    }
}