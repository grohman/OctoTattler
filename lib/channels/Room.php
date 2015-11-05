<?php namespace Grohman\Tattler\Lib\Channels;

use Carbon\Carbon;
use Grohman\Tattler\Lib\Interfaces\Channel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class Room implements Channel
{

    protected $name;
    protected $sessionId;

    public function __construct($name, $sessionId)
    {
        $this->setName($name);
        $this->sessionId = $sessionId;
    }

    public function allow()
    {
        $allowedRooms = 'tattler:access:' . $this->getSessionId();
        $myRooms = Cache::get($allowedRooms);
        if (!$myRooms) {
            $myRooms = [ ];
        }
        $myRooms[] = $this->getName();
        $myRooms[] = 'broadcast';
        $myRooms[] = $this->getSessionId();

        return Cache::put($allowedRooms, array_unique($myRooms), Carbon::now()->addDay());
    }

    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return Room
     */
    public function setName($name)
    {
        if(is_object($name)){
            $name = get_class($name);
        }
        $this->name = $name;

        return $this;
    }

    public function deny()
    {
        $allowedRooms = 'tattler:access:' . $this->sessionId;
        $myRooms = Cache::get($allowedRooms);
        if (!$myRooms) {
            $myRooms = [ ];
        }
        if (isset($myRooms[ $this->name ])) {
            unset($myRooms[ $this->name ]);
        }

        return Cache::put($allowedRooms, $myRooms, Carbon::now()->addDay());
    }

    public function allowed()
    {
        $allowedRooms = 'tattler:access:' . $this->sessionId;
        $myRooms = Cache::get($allowedRooms);
        if (isset($myRooms[ $this->getName() ])) {
            return true;
        }

        return false;
    }

    public function getAllAllowedRooms()
    {
        return Cache::get('tattler:access:' . $this->getSessionId());
    }
}