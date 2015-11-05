<?php namespace Grohman\Tattler\Lib\Channels;

use Carbon\Carbon;
use Grohman\Tattler\Lib\Interfaces\Channel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class User implements Channel
{

    protected $name;
    protected $sessionId;
    protected $user;
    protected $userName;


    public function __construct($user, $sessionId)
    {
        if(is_object($user) == false) {
            throw new Exception('User should be an object');
        }
        $this->user = $user;
        $this->sessionId = $sessionId;
        $this->userName = get_class($this->user).':'.$this->user->getKey();
        $this->setName();
    }

    protected function setName()
    {
        $this->name = $this->sessionId;
        return $this;
    }

    public function getName()
    {
        $result = Cache::get('tattler:users:'.$this->userName);
        if($result) {
            return $result;
        }
        //throw new \Exception('User\'s socket not found');

    }

    public function allow()
    {
        Cache::put('tattler:users:' . $this->userName, $this->sessionId, Carbon::now()->addWeek());

        return true;
    }

    public function deny()
    {
        return true;
    }

    public function allowed()
    {
        return true;
    }
}
