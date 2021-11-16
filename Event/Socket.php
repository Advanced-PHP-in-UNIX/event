<?php
require 'http.php';

$iPort = 8080;
$sHost = '127.0.0.1';
$iBackLog = 1024;

$rListenSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
$bRet = socket_set_nonblock($rListenSocket);
$bRet = socket_set_option($rListenSocket, SOL_SOCKET, SO_REUSEADDR, 1);
$bRet = socket_bind($rListenSocket, $sHost, $iPort);
$bRet = socket_listen($rListenSocket, $iBackLog);

$aClients = [];
$aReads   = [];
$aWrites  = [];
$aExps    = [];
$aClients[] = $rListenSocket;

while (true) {
    $aReads = $aClients;
    print_r($aClients);
    //echo "block@select".PHP_EOL;
    $mRet = socket_select($aReads, $aWrites, $aExps, NULL);
    if ($mRet <= 0 || false === $mRet) {
        continue;
    }
    // 如果可读性的fd中，listen-socket在其中，说明有accept事件.
    print_r($aReads).PHP_EOL;
    if (in_array($rListenSocket, $aReads)) {
        $rConnectionSocket = socket_accept($rListenSocket);
        $aClients[] = $rConnectionSocket;
        $iListenSocketIndexInReads = array_search($rListenSocket, $aReads);
        unset($aReads[$iListenSocketIndexInReads]);
    }
    //echo json_encode($aClients)." | ".json_encode($aReads);
    // 开始遍历剩下的$aReads
    foreach($aReads as $iIndex => $rReadSocket) {
        $sContent = socket_read($rReadSocket, 4096);
        $iReadSocketIndexInClients = array_search($rReadSocket, $aClients);
        //var_dump($iReadSocketIndexInClients);
        //$iRet = socket_write($aClients[$iReadSocketIndexInClients], $sResp, strlen($sResp));
        $sResp = Http::encode("okoko");
        $iRet = socket_write($aClients[$iReadSocketIndexInClients], $sResp, strlen($sResp));
        //var_dump($iRet);
        socket_close($aClients[$iReadSocketIndexInClients]);
        unset($aClients[$iReadSocketIndexInClients]);
    }
}

socket_close($rListenSocket);