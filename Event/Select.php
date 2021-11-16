<?php
namespace Event;

class Select implements EventInterface
{
    public $oResource    = null;
    public $aReads       = [];
    public $aWrites      = [];
    public $aExceptions  = [];
    public $aEvents      = [];
    // 保存连接，tcp/http/ws以及其他基于tcp的应用层自定义协议
    public $aConnections = [];
    public function __construct($oResource)
    {
        $this->oResource = $oResource;
        $this->add($oResource->rSocket, \Event\EventInterface::EVENT_READ, $oResource->onConnect, []);
    }

    public function add($rResource, $iFlag, $fCallback, $aArgs)
    {
        $iFd = intval($rResource);
        if (self::EVENT_READ == $iFlag) {
            $this->aReads[$iFd] = $rResource;
            $this->aEvents[$iFd][$iFlag] = array(
                'callback' => $fCallback,
                'arg'      => $aArgs,
            );
        }
        if (self::EVENT_WRITE == $iFlag) {
            $this->aWrites[$iFd] = $rResource;
            $this->aEvents[$iFd][$iFlag] = array(
                'callback' => $fCallback,
                'arg'      => $aArgs,
            );
        }
        if (self::EVENT_EXCEPTION == $iFlag) {

        }
    }

    public function del()
    {

    }

    public function loop()
    {
        while (true) {
            $aReads = $this->aReads;
            $iAffectedNum = socket_select($aReads, $this->aWrites, $this->aExceptions, NULL);
            if (false === $iAffectedNum) {
                exit("socket_select err".PHP_EOL);
            }
            if ($iAffectedNum > 0) {
                // 首先判断$this->rResource是否在可读中.
                if (in_array($this->oResource->rSocket, $aReads)) {
                    $rNewClient = socket_accept($this->oResource->rSocket);
                    $this->add($rNewClient, self::EVENT_READ, $this->oResource->onMessage, []);
                    unset($aReads[intval($this->oResource->rSocket)]);
                    //$this->oResource->aConnectionFds[intval($rNewClient)] = $rNewClient;


                    // 读取listen socket的回调事件.也就是会触发onConnect方法。
                    $iFd = intval($this->oResource->rSocket);
                    $aCallbackInfo = $this->aEvents[$iFd][self::EVENT_READ];
                    $fCallback = $aCallbackInfo['callback'];
                    socket_getpeername($rNewClient, $sClientHost, $iClientPort);
                    $aArgs = array(
                        'host' => $sClientHost,
                        'port' => $iClientPort,
                    );
                    call_user_func($fCallback, $aArgs);

                }
                // 再判断剩余的其他的可读 $rReadItem是一个可读性的resource类型的socket
                foreach($aReads as $iIndex => $rReadItem) {
                    socket_getpeername($rReadItem, $sClientHost, $iClientPort);
                    $sContent = '';
                    while (($iRecvLen = socket_recv($rReadItem, $sTmpContent, 1, MSG_DONTWAIT)) > 0) {
                        $sContent = $sContent.$sTmpContent;
                    }
                    $iFd = intval($rReadItem);
                    $aCallbackInfo = $this->aEvents[$iFd][self::EVENT_READ];
                    $fCallback = $aCallbackInfo['callback'];
                    $aArgs     = $aCallbackInfo['arg'];
                    $aArgs     = array_merge($aArgs, array(
                        'content' => $sContent,
                    ));
                    $aClient = array(
                        'host' => $sClientHost,
                        'port' => $iClientPort,
                    );
                    call_user_func($fCallback, $aClient, $aArgs);
                }
            }
        }
    }
}