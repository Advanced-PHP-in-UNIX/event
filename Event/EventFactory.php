<?php
namespace Event;
use Event\Select;
use Event\Libevent;

class EventFactory
{
    /*
     * @desc : resource socket
     * */
    public static function create($oServer)
    {
        $sType = "Select";
        if (extension_loaded('Event')) {
            $sType = 'Libevent';
        }
        // mock $sType
        $sType = "Select";
        $sEventClassName = "\\Event\\".ucfirst($sType);
        $oEvent = new $sEventClassName($oServer);
        return $oEvent;
    }

}