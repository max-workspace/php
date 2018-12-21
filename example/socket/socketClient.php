<?php
class SocketClient
{
    public $socketObject = false;

    public function readUserInput()
    {
        print("Please input your message: ");
        $fp = fopen('/dev/stdin', 'r');
        $input = fgets($fp, 255);
        fclose($fp);
        $input = rtrim($input);
        return $input;
    }

    public function closeSocket($socketObject)
    {
        if (!$socketObject) {
            socket_close($socketObject);
        }
        return;
    }

    public function createIpv4TcpSocketObject()
    {
        $this->socketObject = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socketObject == false) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            throw new Exception("Couldn't create socket: [$errorcode] $errormsg");
        }
        return $this;
    }

    public function connetSocketService(string $ipAddress, int $port)
    {
        if (!socket_connect($this->socketObject, $ipAddress, $port)) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            throw new Exception("Couldn't connect socket: [$errorcode] $errormsg");
        }
        return $this;
    }

    public function writeSocketData($socketClient, $message = 'already read')
    {
        if (socket_write($socketClient, $message) === false) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            echo "Couldn't write socket data: [$errorcode] $errormsg";
        }
    }

    public function connectSocketService()
    {
        try {
            $this->createIpv4TcpSocketObject()
                ->connetSocketService('127.0.0.1', 8888);

            while (1) {
                $message = $this->readUserInput();
                $this->writeSocketData($this->socketObject, $message);
                if ($message == 'EOL') {
                    break;
                }
            }
        } catch (\Exception $e) {
            echo (string)$e;
        }
        $this->closeSocket($this->socketObject);
        return;
    }
}

$socketClient = new SocketClient();
$socketClient->connectSocketService();

# 使用nc -l 8888模拟服务器
# 可使用socketService.php模拟服务器