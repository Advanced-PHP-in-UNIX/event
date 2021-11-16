<?php
error_reporting(E_ALL);
ini_set("display_errors", "On");
spl_autoload_register(function ($sClassName){
    $sExtname = ".php";
    $sClassName = str_replace("\\", DIRECTORY_SEPARATOR, $sClassName);
    $sFile = $sClassName.$sExtname;
    require_once $sFile;
});

// 创建多进程，一个Master-Process与N个Worker-Process
$oProcess = new \Process\Process();

// 创建tcp socket
//$oServer = new \Server\Tcp();
$oServer = new \Server\Http();
$oServer->onConnect = function($oConnection) use($oServer) {
    //echo json_encode($oConnection)." | on-connect | ".posix_getpid().PHP_EOL;
    //print_r($oServer->aConnectionFds);
};
$oServer->onMessage = function($oConnection, $aMessage) use($oServer) {
    //print_r($oServer->aConnectionFds);
    //echo json_encode($oConnection)." | on-message | ".json_encode($aMessage).PHP_EOL;
};
$oServer->onClose = function($aClient) {
    echo "on-close".PHP_EOL;
};

// 创建基于IO多路复用的event loop
$oEvent = \Event\EventFactory::create($oServer);
//$oEvent->loop();

$oProcess->run('', $oEvent);