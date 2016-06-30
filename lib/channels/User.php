<?php namespace Grohman\Tattler\Lib\Channels;

use Carbon\Carbon;
use Exception;
use Grohman\Tattler\Lib\Interfaces\Channel;
use Illuminate\Support\Facades\Cache;

/**
 * Class User
 * @package Grohman\Tattler\Lib\Channels
 */
class User implements Channel
{

    /**
     * @var string $name
     */
    protected $name;
    /**
     * @var string $sessionId
     */
    protected $sessionId;
    /**
     * @var object $user
     */
    protected $user;
    /**
     * @var string $userName
     */
    protected $userName;


    /**
     * User constructor.
     * @param $user
     * @param $sessionId
     * @throws Exception
     */
    public function __construct($user, $sessionId)
    {
        if (is_object($user) == false) {
            throw new Exception('User should be an object');
        }
        $this->user = $user;
        $this->sessionId = $sessionId;
        $this->userName = get_class($this->user) . ':' . $this->user->getKey();
        $this->setName();
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        $result = Cache::get('tattler:users:' . $this->userName);
        if ($result) {
            return $result;
        }
        return null;
    }

    /**
     * @return $this
     */
    protected function setName()
    {
        $this->name = $this->sessionId;
        return $this;
    }

    /**
     * @return bool
     */
    public function allow()
    {
        Cache::put('tattler:users:' . $this->userName, $this->sessionId, Carbon::now()->addWeek());

        return true;
    }

    /**
     * @return bool
     */
    public function deny()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function allowed()
    {
        return true;
    }
}
