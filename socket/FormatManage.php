<?php
namespace Max\Repository;

/**
 * Class FormatManage
 * @package Vendor\FormatManage
 * 格式化管理工具
 */
trait FormatManage
{
    public $successCode = '100';

    public $errorCode = '200';

    public $successDesciption = 'success';

    public $errorDescription = 'error';

    public function formatResult($statusCode, $data = [], $description = '')
    {
        $is_success = ($statusCode == $this->successCode) ? true : false;
        if (!$description) {
            $description = $is_success ? $this->successDesciption : $this->errorDescription;
        }
        return ['status' => $statusCode, 'description' => $description, 'data' => $data];
    }
}