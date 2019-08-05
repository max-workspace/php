<?php
namespace Max\Repository;

//use Max\Repository\FormatManage;
//use Max\Repository\FormatSocketObj;
require_once __DIR__ . '/FormatManage.php';
require_once __DIR__ . '/FormatSocketObj.php';

class FormatSocketServiceManage
{
    use FormatManage;

    public $maxBacklog = 100;

    public $socketServiceArray = [];

    public function getSocketService($address, $port, $socketProtocolDomain = AF_INET, $socketType = SOCK_STREAM, $socketProtocol = SOL_TCP)
    {
        $key = $address . ':' . $port;
        if (array_key_exists($key, $this->socketServiceArray)) {
            return $this->formatResult($this->successCode, '', $this->socketServiceArray[$key]);
        }
        $result = $this->createSocketService($address, $port, $socketProtocolDomain, $socketType, $socketProtocol);
        if ($result['status'] == $this->successCode) {
            $this->socketServiceArray[$key] = $result['data'];
        }
        return $result;
    }

    public function createSocketService($address, $port, $socketProtocolDomain, $socketType, $socketProtocol)
    {
        try {
            // 创建socket服务器端
            $socketService = new FormatSocketObj();
            $result = $socketService->createSocketObject($socketProtocolDomain, $socketType, $socketProtocol);
            if ($result['status'] != $this->successCode) {
                throw new \Exception($result['description']);
            }
            $result = $socketService->bindSocket($address, $port);
            if ($result['status'] != $this->successCode) {
                throw new \Exception($result['description']);
            }
            $result = $socketService->listenSocket($this->maxBacklog);
            if ($result['status'] != $this->successCode) {
                throw new \Exception($result['description']);
            }
            return $this->formatResult($this->successCode, '', $socketService);
        } catch (\Exception $e) {
            return $this->formatResult($this->errorCode, $e->getMessage());
        }
    }

    public function runSocketService($socketService)
    {
        while (true) {
            $result = $socketService->acceptSocket();
            if ($result['status'] != $this->successCode) {
                continue;
            }
            $socketClient = $result['data'];
            while (true) {
                $result = $socketService->readFromSocket($socketClient, 11);
                $data = $result['status'] == $this->successCode ? $result['data'] : $result['description'];
                echo "read from client: " . $data . PHP_EOL;
                if ($data == 'EOL') {
                    break 2;
                }
            }
        }
    }
}

$socketService = null;
$socketServiceManage = new FormatSocketServiceManage();
$result = $socketServiceManage->getSocketService('127.0.0.1', 8450);
if ($result['status'] == $socketServiceManage->successCode) {
    $socketService = $result['data'];
}
if (is_null($socketService)) {
    exit($result['description']);
}
$socketServiceManage->runSocketService($socketService);