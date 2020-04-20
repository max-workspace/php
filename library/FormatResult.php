<?php
namespace library;

class FormatResult {
    const DEFAULTSUCCESSCODE = 200;
    const DEFAULTERRORCODE = 500;
    const DEFAULTSUCCESSDESCRIPTION = 'success';
    const DEFAULTERRORDESCRIPTION = 'error';

    public static function success($data = [], $message = null, $code = null)
    {
        $code = is_null($code) ? self::DEFAULTSUCCESSCODE : $code;
        $message = is_null($message) ? self::DEFAULTSUCCESSDESCRIPTION : $message;
        return self::formatResult($code, $message, $data);
    }

    public static function error($message = null, $code = null, $data = [])
    {
        $code = is_null($code) ? self::DEFAULTERRORCODE : $code;
        $message = is_null($message) ? self::DEFAULTERRORDESCRIPTION : $message;
        return self::formatResult($code, $message, $data);
    }

    public static function formatResult($code, $message, $data)
    {
        return [
            'code' => $code,
            'desc' => $message,
            'data' => $data,
        ];
    }
}