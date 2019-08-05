<?php
namespace Max\Repository;

//use Max\Repository\FormatManage;
require_once __DIR__ . '/FormatManage.php';

/**
 * Class FormatSocketObj
 * @package Max\Repository
 * 该类在socket对象的基础上进行封装
 * 不仅格式化返回结果，还记录了socket对象在创建过程中使用的参数。
 */
class FormatSocketObj
{
    use FormatManage;

    public $socketObj = null;

    public $socketProtocolDomain = null;

    public $socketProtocol = null;

    public $socketType = null;

    public $socketOptionArray = [];

    public function getSocketObj()
    {
        return $this->socketObj;
    }

    public function getSocketErrorInfo($desc = '')
    {
        $socketErrorCode = socket_last_error();
        $socketErrorMessage = socket_strerror($socketErrorCode);
        $formatErrorMessage = "{$desc} [{$socketErrorCode}] {$socketErrorMessage}";
        return [
            'socketErrorCode' => $socketErrorCode,
            'socketErrorMessage' => $socketErrorMessage,
            'formatErrorMessage' => $formatErrorMessage,
        ];
    }

    public function createSocketObject($socketProtocolDomain, $socketType, $socketProtocol)
    {
        $socket = socket_create($socketProtocolDomain, $socketType, $socketProtocol);
        if ($socket) {
            $this->socketObj = $socket;
            $this->socketProtocolDomain = $socketProtocolDomain;
            $this->socketType = $socketType;
            $this->socketProtocol = $socketProtocol;
            return $this->formatResult($this->successCode);
        } else {
            $socketErrorInfo = $this->getSocketErrorInfo('Unable to create socket: ');
            return $this->formatResult($this->errorCode, $socketErrorInfo['formatErrorMessage']);
        }
    }

    public function setSocketOption($level, $optionName, $optionValue)
    {
        if (!socket_set_option($this->socketObj, $level, $optionName, $optionValue)) {
            $socketErrorInfo = $this->getSocketErrorInfo('Unable to set socket option: ');
            return $this->formatResult($this->errorCode, $socketErrorInfo['formatErrorMessage']);
        }
        $this->socketOptionArray[$level][] = [$optionName => $optionValue];
        return $this->formatResult($this->successCode, '', $this->socketObj);
    }

    public function bindSocket($address, $port = 0)
    {
        $parameterArray = [$this->socketObj, $address];
        if ($this->socketProtocolDomain == AF_INET) {
            $parameterArray[] = $port;
        }
        $result = call_user_func_array('socket_bind', $parameterArray);
        if (!$result) {
            $socketErrorInfo = $this->getSocketErrorInfo('Unable to bind socket: ');
            return $this->formatResult($this->errorCode, $socketErrorInfo['formatErrorMessage']);
        }
        return $this->formatResult($this->successCode, '', $this->socketObj);
    }

    public function listenSocket($backlog = 0)
    {
        if (!in_array($this->socketType, [SOCK_STREAM, SOCK_SEQPACKET])) {
            return $this->formatResult($this->errorCode, 'Unable types of socket');
        }
        if (!socket_listen($this->socketObj, $backlog)) {
            $socketErrorInfo = $this->getSocketErrorInfo('Unable to listen socket: ');
            return $this->formatResult($this->errorCode, $socketErrorInfo['formatErrorMessage']);
        }
        return $this->formatResult($this->successCode, '', $this->socketObj);
    }

    public function connectSocket($address, $port = 0)
    {
        $parameterArray = [$this->socketObj, $address];
        if (in_array($this->socketProtocolDomain, [AF_INET, AF_INET6])) {
            $parameterArray[] = $port;
        }
        $result = call_user_func_array('socket_connect', $parameterArray);
        if (!$result) {
            $socketErrorInfo = $this->getSocketErrorInfo('Unable to connect socket: ');
            return $this->formatResult($this->errorCode, $socketErrorInfo['formatErrorMessage']);
        }
        return $this->formatResult($this->successCode, '', $this->socketObj);
    }

    public function acceptSocket()
    {
        $socketObjAccept = socket_accept($this->socketObj);
        if ($socketObjAccept) {
            return $this->formatResult($this->successCode, '', $socketObjAccept);
        }
        $socketErrorInfo = $this->getSocketErrorInfo('Unable to accept socket: ');
        return $this->formatResult($this->errorCode, $socketErrorInfo['formatErrorMessage']);
    }

    public function writeToSocket($buffer, $length = 0)
    {
        $parameterArray = [$this->socketObj, $buffer];
        if ($length) {
            $parameterArray[] = $length;
        }
        $result = call_user_func_array('socket_write', $parameterArray);
        if ($result === false) {
            $socketErrorInfo = $this->getSocketErrorInfo('Unable to write to socket: ');
            return $this->formatResult($this->errorCode, $socketErrorInfo['formatErrorMessage']);
        }
        return $this->formatResult($this->successCode, '', $result);
    }

    public function readFromSocket($socketObj, $length, $type = PHP_BINARY_READ)
    {
        if (!in_array($type, [PHP_BINARY_READ, PHP_NORMAL_READ])) {
            return $this->formatResult($this->errorCode, 'Unable types of read socket');
        }
        $parameterArray = [$socketObj, $length, $type];
        $result = call_user_func_array('socket_read', $parameterArray);
        if ($result === false) {
            $socketErrorInfo = $this->getSocketErrorInfo('Unable to read to socket: ');
            return $this->formatResult($this->errorCode, $socketErrorInfo['formatErrorMessage']);
        }
        return $this->formatResult($this->successCode, '', $result);
    }
}
