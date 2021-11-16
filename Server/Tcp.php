<?php
namespace Server;

class Tcp
{

    public $aTcpConfig = array(
        'port'     => 8080,
        'host'     => '127.0.0.1',
        'back_log' => 102,
    );
    public $rSocket   = null;
    public $onConnect = null;
    public $onMessage = null;
    public $onClose   = null;
    public $aConnectionFds = [];
    public $sProtocol = "tcp";

    public function __construct()
    {
        $sHost    = $this->aTcpConfig['host'];
        $iPort    = $this->aTcpConfig['port'];
        $iBackLog = $this->aTcpConfig['back_log'];
        $rListenSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $bRet = socket_set_nonblock($rListenSocket);
        $bRet = socket_set_option($rListenSocket, SOL_SOCKET, SO_REUSEADDR, 1);
        $bRet = socket_bind($rListenSocket, $sHost, $iPort);
        $bRet = socket_listen($rListenSocket, $iBackLog);
        $this->rSocket = $rListenSocket;
    }

}