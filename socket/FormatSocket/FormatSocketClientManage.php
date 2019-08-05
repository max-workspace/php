<?php
namespace Max\Repository;

//use Max\Repository\FormatManage;
//use Max\Repository\FormatSocketObj;
require_once __DIR__ . '/FormatManage.php';
require_once __DIR__ . '/FormatSocketObj.php';

class FormatSocketClientManage
{
    use FormatManage;

    public $maxBacklog = 100;

    public $socketClientArray = [];

    public function getSocketClient($address, $port, $socketProtocolDomain = AF_INET, $socketType = SOCK_STREAM, $socketProtocol = SOL_TCP)
    {
        $key = $address . ':' . $port;
        if (array_key_exists($key, $this->socketClientArray)) {
            return $this->formatResult($this->successCode, '', $this->socketClientArray[$key]);
        }
        $result = $this->createSocketClient($address, $port, $socketProtocolDomain, $socketType, $socketProtocol);
        if ($result['status'] == $this->successCode) {
            $this->socketClientArray[$key] = $result['data'];
        }
        return $result;
    }

    public function createSocketClient($address, $port, $socketProtocolDomain, $socketType, $socketProtocol)
    {
        try {
            // 创建socket客户端
            $socketClient = new FormatSocketObj();
            $result = $socketClient->createSocketObject($socketProtocolDomain, $socketType, $socketProtocol);
            if ($result['status'] != $socketClient->successCode) {
                throw new \Exception($result['description']);
            }
            $result = $socketClient->connectSocket($address, $port);
            if ($result['status'] != $socketClient->successCode) {
                throw new \Exception($result['description']);
            }
            return $this->formatResult($this->successCode, '', $socketClient);
        } catch (\Exception $e) {
            return $this->formatResult($this->errorCode, $e->getMessage());
        }
    }
}

$socketClient = null;
$socketClientManage = new FormatSocketClientManage();
$result = $socketClientManage->getSocketClient('127.0.0.1', 8450);
if ($result['status'] == $socketClientManage->successCode) {
    $socketClient = $result['data'];
}
if (is_null($socketClient)) {
    exit($result['description']);
}
$socketClient->writeToSocket('hello world');
$socketClient->writeToSocket('EOL');