<?php

class SocketService
{
    public $socketObject = false;

    public function createIpv4TcpSocketObject()
    {
        $this->socketObject = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ( $this->socketObject == false) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            throw new Exception("Couldn't create socket: [$errorcode] $errormsg");
        }
        return $this;
    }

    public function bindSocketObject(string $ipAddress, int $port)
    {
        if (empty($ipAddress) || empty($port)) {
            throw new Exception('Lack Of Necessary parameters');
        }
        if (!socket_bind($this->socketObject, $ipAddress, $port)) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            throw new Exception("Couldn't bind socker: [$errorcode] $errormsg");
        }
        return $this;
    }

    public function setSocketListen(int $maxNumber = 0)
    {
        if (!socket_listen($this->socketObject, $maxNumber)) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            throw new Exception("Couldn't set socker listen number: [$errorcode] $errormsg");
        }
        return $this;
    }

    public function getClientInfo($socketClient)
    {
        if (socket_getpeername($socketClient, $addr, $port)) {
            echo "client connect server: ip = $addr, port = $port" . PHP_EOL;
        }
        return;
    }

    public function readSocketData($socketClient, $length = 1024)
    {
        $data = socket_read($socketClient, $length);
        if ($data === false) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            $data = "Couldn't read socket data: [$errorcode] $errormsg";
        }
        return $data;
    }

    public function writeSocketData($socketClient, $message = 'already read')
    {
        if (socket_write($socketClient, $message) === false) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            echo "Couldn't write socket data: [$errorcode] $errormsg";
        }
    }

    public function closeSocket($socketObject)
    {
        if (!$socketObject) {
            socket_close($socketObject);
        }
        return;
    }

    public function createIpv4TcpSocketService()
    {
        try {
            $this->createIpv4TcpSocketObject()
                ->bindSocketObject('127.0.0.1', 8888)
                ->setSocketListen(128);

            while (1) {
                $socketClient = socket_accept($this->socketObject);
                if (!$socketClient) {
                    continue;
                }
                $this->getClientInfo($socketClient);
                while (1) {
                    $data = $this->readSocketData($socketClient);
                    echo "read from client: " . $data . PHP_EOL;
                    if ($data == 'EOL') {
                        break 2;
                    }
                    $this->writeSocketData($socketClient);
                }
            }
        } catch (\Exception $e) {
            echo (string)$e;
        }
        $this->closeSocket($this->socketObject);
        $this->closeSocket($socketClient);
        return;
    }
}

$socketService = new SocketService();
$socketService->createIpv4TcpSocketService();

# 使用nc 127.0.0.1 8888来进行客户端链接
# 可使用socketClient.php进行客户端链接
