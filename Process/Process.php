<?php
namespace Process;

pcntl_async_signals(true);
class Process
{
    public static $aConfig = array(
        'num'                  => 1,
        'daemonize'            => false,
        'master_process_title' => 'master:Process',
        'worker_process_title' => 'worker:Process',
        'master_pid_file'      => "master.pid",
        'child_pid_file'       => "child.pid",
    );
    public static $sRoot      = "";
    public static $aChildPids = array();
    public static $iMasterPid = 0;
    public static $oResource  = null;

    public function __construct()
    {
        $aConfigFromFile = [];
        $sConfigFile = './config.ini';
        if (is_file($sConfigFile)) {
            $aConfigFromFile = parse_ini_file("./config.ini");
        }
        self::$aConfig = array_merge(self::$aConfig, $aConfigFromFile);
        //print_r(self::$aConfig);exit;
        //self::$iMasterPid = posix_getpid();
        self::$sRoot = __DIR__;
    }

    public function forkChildProcess($iNum)
    {
        for($i = 1; $i <= $iNum; $i++) {
            $iPid = pcntl_fork();
            if ($iPid < 0) {
                exit("err in fork".PHP_EOL);
            } else if (0 == $iPid) {
                //pcntl_signal(SIGINT, array($this, 'signalHandler'), true);
                cli_set_process_title(self::$aConfig['worker_process_title']);
                // in child Process.
                //sleep(mt_rand(1, 10));
                self::$oResource->loop();
                exit;
            } else if ($iPid > 0) {
                self::$aChildPids[$iPid] = $iPid;
            }
        }
    }

    public function initMaster()
    {
        $iMasterPid = posix_getpid();
        self::$iMasterPid = $iMasterPid;
        cli_set_process_title(self::$aConfig['master_process_title']);
    }

    public function installSignalHandler()
    {
        //pcntl_signal(SIGINT, array($this, 'signalHandler'), true);
        pcntl_signal(SIGCHLD, array($this, 'signalHandler'), true);
        pcntl_signal(SIGUSR2, array($this, 'signalHandler'), true);
        pcntl_signal(SIGTERM, array($this, 'signalHandler'), true);
    }

    public function signalHandler($iSigno)
    {
        switch ($iSigno) {
            case SIGINT:
                break;
            case SIGTERM:
                $this->stopAll();
                break;
            case SIGCHLD:
                //回收进程 拉起来新的子进程
                $this->reForkChildProcess();
                break;
            // reload
            case SIGUSR2:
                //print_r(self::$aChildPids);
                //file_put_contents("./test.log", "lalala");
                $this->getStatus();
                break;
        }
    }

    public function getStatus()
    {
        $sChildPidFile  = self::$sRoot.DIRECTORY_SEPARATOR.self::$aConfig['child_pid_file'];
        $sChildPidFileDir  = dirname($sChildPidFile);
        if (!is_dir($sChildPidFileDir)) {
            mkdir($sChildPidFileDir, 0777, true);
        }
        file_put_contents($sChildPidFile, json_encode(self::$aChildPids));
        print_r(self::$iMasterPid);
        print_r(self::$aChildPids);
    }

    public function reForkChildProcess()
    {
        $iExitChildPid = pcntl_waitpid(0, $iStatus);
        unset(self::$aChildPids[$iExitChildPid]);
        $iNewChildPid = pcntl_fork();
        if ($iNewChildPid < 0) {
            exit("err in fork".PHP_EOL);
        }
        if (0 == $iNewChildPid) {
            cli_set_process_title(self::$aConfig['worker_process_title']." | new");
            //sleep(mt_rand(1, 10));
            self::$oResource->loop();
            exit;
        }
        if ($iNewChildPid > 0) {
            self::$aChildPids[$iNewChildPid] = $iNewChildPid;
        }
        //print_r(self::$aChildPids);
    }

    public function stopAll()
    {
        $sMasterPidFile = self::$sRoot.DIRECTORY_SEPARATOR.self::$aConfig['master_pid_file'];
        $sChildPidFile  = self::$sRoot.DIRECTORY_SEPARATOR.self::$aConfig['child_pid_file'];
        @unlink($sMasterPidFile);
        @unlink($sChildPidFile);
        $aChildPids = self::$aChildPids;
        $iMasterPid = self::$iMasterPid;
        posix_kill($iMasterPid, SIGKILL);
        sleep(1);
        while (count($aChildPids) > 0) {
            foreach($aChildPids as $iChildPid) {
                posix_kill($iChildPid, SIGKILL);
            }
        }
    }

    public function daemonize()
    {
        umask(0);
        $iPid = pcntl_fork();
        if ($iPid < 0) {
            exit("fork err".PHP_EOL);
        }
        if ($iPid > 0) {
            exit;
        }
        // fork-twice avoid SVR4 bug
        $iPid = pcntl_fork();
        if ($iPid < 0) {
            exit("fork err".PHP_EOL);
        }
        if ($iPid > 0) {
            exit;
        }
        $iRet = posix_setsid();
        if (-1 === $iRet) {
            exit("posix_setsid err".PHP_EOL);
        }
        chdir(self::$sRoot);
        return $iPid;
    }

    public static function status()
    {
        $sConfigFile = './config.ini';
        if (is_file($sConfigFile)) {
            $aConfigFromFile = parse_ini_file("./config.ini");
        }
        self::$aConfig = array_merge(self::$aConfig, $aConfigFromFile);
        $sMasterPidFile = __DIR__.DIRECTORY_SEPARATOR.self::$aConfig['master_pid_file'];
        $iMasterPid = file_get_contents($sMasterPidFile);
        //echo $iMasterPid.PHP_EOL;
        posix_kill($iMasterPid, SIGUSR2);
        //var_dump($bRet);
    }
    public static function reload()
    {
        //$iMasterPid = file_get_contents();
    }
    public static function stop()
    {
        $sConfigFile = './config.ini';
        if (is_file($sConfigFile)) {
            $aConfigFromFile = parse_ini_file("./config.ini");
        }
        self::$aConfig = array_merge(self::$aConfig, $aConfigFromFile);
        $sMasterPidFile = __DIR__.DIRECTORY_SEPARATOR.self::$aConfig['master_pid_file'];
        $iMasterPid = file_get_contents($sMasterPidFile);
        //$bRet = posix_kill($iMasterPid, SIGTERM);
        posix_kill($iMasterPid, SIGTERM);
    }


    public function flushData2FileAfterAllOver()
    {
        $sMasterPidFile = self::$sRoot.DIRECTORY_SEPARATOR.self::$aConfig['master_pid_file'];
        $sChildPidFile  = self::$sRoot.DIRECTORY_SEPARATOR.self::$aConfig['child_pid_file'];
        $sMasterPidFileDir = dirname($sMasterPidFile);
        $sChildPidFileDir  = dirname($sChildPidFile);
        if (!is_dir($sMasterPidFileDir)) {
            mkdir($sMasterPidFileDir, 0777, true);
        }
        if (!is_dir($sChildPidFileDir)) {
            mkdir($sChildPidFileDir, 0777, true);
        }
        file_put_contents($sMasterPidFile, self::$iMasterPid);
        //file_put_contents($sChildPidFile, json_encode(self::$aChildPids));
    }

    public function run($sOption = "", $oEvent)
    {
        self::$oResource = $oEvent;
        $bDaemon = self::$aConfig['daemonize'];
        if (trim($sOption) == '-d') {
            $bDaemon = 1;
        }
        if (1 == $bDaemon || true == $bDaemon) {
            $this->daemonize();
        }
        $this->installSignalHandler();
        $this->forkChildProcess(self::$aConfig['num']);
        $this->initMaster();
        if (1 == $bDaemon || true == $bDaemon) {
            $this->flushData2FileAfterAllOver();
        }
        while (true) {
            //echo "master:while-looping".PHP_EOL;
            sleep(1);
        }
    }

}