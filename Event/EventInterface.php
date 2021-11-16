<?php
namespace Event;

interface EventInterface
{
    const EVENT_READ      = 1;
    const EVENT_WRITE     = 2;
    const EVENT_EXCEPTION = 4;
    public function add($rResource, $iFlag, $fCallback, $aArgs);
    public function del();
    public function loop();
}