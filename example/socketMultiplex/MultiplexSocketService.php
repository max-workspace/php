<?php
class MultiplexSocketService
{
    public $port = 8888;

    public $ipAddress = '127.0.0.1';

    public $socketObject = false;

    public $readSocket = array();

    public $writeSocket = array();

    public $exceptSocket = null;

    public $timeOut = null;

    public function createSocketObject(int $domain, int $type, int $protocol)
    {
        $this->socketObject = socket_create ($domain, $type, $protocol);
        if ($this->socketObject === false) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            throw new Exception("Couldn't create socket: [$errorcode] $errormsg");
        }
        return $this;
    }

    public function bindSocketObject(string $ipAddress = '', int $port = 0)
    {
        empty($port) && $port = $this->port;
        empty($ipAddress) && $ipAddress = $this->ipAddress;

        if (!socket_bind($this->socketObject, $ipAddress, $port)) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            throw new Exception("Couldn't bind socker: [$errorcode] $errormsg");
        }
        return $this;
    }

    public function setSocketListenNumber(int $maxNumber = 0)
    {
        if (!socket_listen($this->socketObject, $maxNumber)) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            throw new Exception("Couldn't set socker listen number: [$errorcode] $errormsg");
        }
        return $this;
    }

    public function socketSelect()
    {
        $count = socket_select(
            $this->readSocket,
            $this->writeSocket,
            $this->exceptSocket,
            $this->timeOut
        );
        if ($count === falses) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            throw new Exception("Couldn't set socker listen number: [$errorcode] $errormsg");
        }
        return $count;
    }

    public function closeSocket($socketObject)
    {
        if (!$socketObject) {
            socket_close($socketObject);
        }
        return;
    }

    public function run()
    {
        try {
            // 初始化socket对象
            $this->createSocketObject(AF_INET, SOCK_STREAM, SOL_TCP)
                ->bindSocketObject()
                ->setSocketListenNumber();

            $this->readSocket[] = $this->socketObject;

            while (1) {
                $count = $this->socketSelect();

                foreach ($this->readSocket as $readSocketItem) {
                    var_dump($readSocketItem);
                }
                break;
            }

        } catch (\Exception $e) {
            echo (string)$e;
        }
        $this->closeSocket($this->socketObject);
    }
}

$socketService = new MultiplexSocketService();
$socketService->run();
