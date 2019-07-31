<?php
namespace Max\Repository;

use Max\Repository\FormatManage;

/**
 * Class SocketManage
 * 该文件依赖PHP的Socket扩展，使用前请确认是否安装对应的扩展
 */
class SocketManage
{
    use FormatManage;

    public $socketProtocolDomainArray = [AF_INET, AF_INET6, AF_UNIX];

    public $socketProtocolArray = [SOL_UDP, SOL_TCP];

    public $socketTypeArray = [
        SOCK_STREAM,
        SOCK_DGRAM,
        SOCK_SEQPACKET,
        SOCK_RAW,
        SOCK_RDM,
    ];

    public $defaultSocketProtocolDomain = AF_INET;

    public $defaultSocketProtocol = SOL_TCP;

    public $defaultSocketType = SOCK_STREAM;

    public $socketObjectArray = [];

    public function getSocketProtocolDomain($socketProtocolDomain)
    {
        $socketProtocolDomain = in_array($socketProtocolDomain, $this->socketProtocolDomainArray) ? $socketProtocolDomain : $this->defaultSocketProtocolDomain;
        return $socketProtocolDomain;
    }

    public function getSocketType($socketType)
    {
        $socketType = in_array($socketType, $this->socketTypeArray) ? $socketType : $this->defaultSocketType;
        return $socketType;
    }

    public function getsocketProtocol($socketProtocol)
    {
        $socketProtocol = in_array($socketProtocol, $this->socketProtocolArray) ? $socketProtocol : $this->defaultSocketProtocol;
        return $socketProtocol;
    }

    public function createSocketObject($socketProtocolDomain, $socketType, $socketProtocol)
    {
        $socketProtocolDomain = $this->getSocketProtocolDomain($socketProtocolDomain);
        $socketType = $this->getSocketType($socketType);
        $socketProtocol = $this->getsocketProtocol($socketProtocol);
        $socket = socket_create($socketProtocolDomain, $socketType, $socketProtocol);
        if ($socket) {
            return $this->formatResult($this->successCode, $socket);
        } else {
            return $this->formatResult($this->errorCode);
        }
    }
}
