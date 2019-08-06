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

    public $connectSocketArray = [];

    public $EndOfInput = 'EOL';

    public $fetchLength = 11;

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

    // todo 首次连接协定传输的信息（每次读取的长度），再次连接时沿用上次协定的通信内容
    public function runSocketService(FormatSocketObj $socketService)
    {
        while (true) {
            $result = $socketService->acceptSocket();
            if ($result['status'] != $this->successCode) {
                continue;
            }
            $connectSocket = $result['data'];
            $this->recordConnectSocket($socketService, $connectSocket);
            while (true) {
                $result = $socketService->readFromSocket($connectSocket, $this->fetchLength);
                $data = $result['status'] == $this->successCode ? $result['data'] : $result['description'];
                echo "read from client: " . $data . PHP_EOL;
                if ($data == $this->EndOfInput) {
                    break 2;
                }
            }
        }
    }

    public function recordConnectSocket(FormatSocketObj $socketService, $connectSocket)
    {
        $result = $socketService->getConnectSocketInfo($connectSocket, true);
        if ($result['status'] != $this->successCode) {
            return $this->formatResult($this->errorCode, $result['description']);
        }
        $addr = $result['data']['addr'];
        $port = $result['data']['port'];
        $key = $port ? ($addr . ':' . $port) : $addr;
        echo "{$key}\n";
        if (!array_key_exists($key, $this->connectSocketArray)) {
            $this->connectSocketArray[$key] = $connectSocket;
        }
        return $this->formatResult($this->successCode, '', $result['data']);
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