<?php
namespace Event;

class Libevent implements EventInterface
{
    public $rResource   = null;
    public $aReads      = [];
    public $aWrites     = [];
    public $aExceptions = [];
    public function __construct($rResource)
    {
        if (null == $this->rResource) {
            $this->rResource = $rResource;
        }
    }
    public function add($rResource, $iFlag, $fCallback, $aArgs)
    {

    }

    public function del()
    {

    }

    public function loop()
    {

    }
}