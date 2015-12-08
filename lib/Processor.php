<?php namespace Grohman\Tattler\Lib;

use Cache;
use Event;
use Httpful\Handlers\JsonHandler;
use Httpful\Httpful;
use Httpful\Request as HRequest;
use Illuminate\Support\Str;
use Log;
use Queue;
use Session;

/*
 * use Grohman\Tattler\Facades\Tattler;
 *
 * Tattler::say(['handler'=>'growl', 'message'=>'Test message', 'title'=>'For anyone']);
 * Tattler::room(new \Grohman\Reviews\Models\Item)->say(['handler'=>'growl', 'message'=>'Test message', 'title'=>'For anyone in Reviews']);
 * Tattler::user(\Backend\Models\User::first())->say(['handler'=>'growl', 'message'=>'Test message', 'title'=>'For backend admin']);
 * Tattler::currentUser()->say(['handler'=>'growl', 'message'=>'Test message', 'title'=>'For current user']);
 * */

class Processor
{
    private $sessionId = null;
    private $root = null;
    private $restful = null;
    private $rooms = [ ];
    private $target;
    private $tattlerApi;

    public function __construct()
    {
        $this->setSessionId();

        $this->root = $this->getRoot();

        $this->tattlerApi =
            (config()->get('grohman.tattler::ssl') == true ? 'https' : 'http') . '://' . $this->getTattlerUri();

        $json_handler = new JsonHandler([ 'decode_as_array' => true ]);
        Httpful::register('application/json', $json_handler);

        $this->restful = HRequest::init();
        $this->restful->uri($this->tattlerApi);
    }

    protected function getRoot()
    {
        $result = config()->get('grohman.tattler::root');
        if (null == $result) {
            $result = Cache::rememberForever('tattler:root', function () {
                return Str::random(64);
            });
        }

        return $result;
    }

    protected function getTattlerUri()
    {
        return config()->get('grohman.tattler::server');
    }

    public function addUser($user)
    {
        $room = new Channels\User($user, $this->getSessionId());
        $this->rooms[ $room->getName() ] = $room;

        return $room;
    }

    public function getSessionId()
    {
        return $this->sessionId;
    }

    public function setSessionId($value = null)
    {
        if ($value == null && config()->get('session.driver') != 'array') {
            $value = Session::get('tattler');
            if ($value == null) {
                Session::set('tattler', Str::random(32));

                return $this->setSessionId();
            }
        } else if ($value == null && config()->get('session.driver') == 'array') {
            return null;
        }
        $this->sessionId = $value;
    }

    public function getWs($secure = false)
    {
        if ($secure || config()->get('grohman.tattler::ssl') == true) {
            $prefix = 'wss';
        } else {
            $prefix = 'ws';
        }

        return [ 'ws' => $prefix . '://' . config()->get('grohman.tattler::server') ];
    }

    public function getRooms($query)
    {
        if ($this->sessionId == null) {
            return true;
        }
        $rooms = $query[ 'rooms' ];
        if (!is_array($rooms)) {
            if ($rooms != '') {
                if (preg_match('/,/', $rooms)) {
                    $rooms = explode(',', $rooms);
                } else {
                    $rooms = [ $rooms ];
                }
            } else {
                $rooms = [ ];
            }
        }

        $socketId = $query[ 'socketId' ];
        $sessionId = $this->getSessionId();

        $roomsQuery = [
            'client' => [ 'socketId' => $socketId, 'sessionId' => $sessionId ],
            'rooms' => $this->validateRoomsAccess($rooms),
            'root' => $this->getRoot()
        ];

        $setRooms =
            HRequest::init()
                ->uri($this->tattlerApi . '/tattler/rooms')
                ->sendsType('application/json')
                ->body($roomsQuery)
                ->method('POST')
                ->send();

        $result = $setRooms->body;
        is_array($result) && $result[ 'requestedRooms' ] = $query[ 'rooms' ];

        return $result;
    }

    protected function validateRoomsAccess(Array $rooms)
    {
        if ($this->sessionId == null) {
            return [ ];
        }
        $allowedRooms = 'tattler:access:' . $this->sessionId;
        $myRooms = Cache::get($allowedRooms);
        $result = [ 'broadcast', $this->getSessionId() ];
        foreach ($rooms as $room) {
            if (in_array($room, $myRooms)) {
                $result[] = $room;
            }
        }

        return array_unique($result);
    }

    public function getDefaultRooms($extraRooms = [ ])
    {
        $result = [ 'broadcast', $this->getSessionId() ];
        if (!empty($extraRooms)) {
            $result = array_merge($result, $extraRooms);
        }

        return $result;
    }

    public function room($room)
    {
        if (is_string($room)) {
            $this->target = $room;
        } else if ($room instanceof Channel) {
            $this->target = $room->getName();
        } else {
            $this->target = $this->addRoom($room)->getName();
        }

        return $this;
    }

    public function addRoom($room)
    {
        if ($room instanceof Room) {
            $result = $room;
        } else {
            $result = new Channels\Room($room, $this->getSessionId());
        }
        $this->rooms[ $result->getName() ] = $result;

        return $result;
    }

    public function user($user)
    {
        $room = new Channels\User($user, $this->getSessionId());
        $this->target = $room->getName();

        return $this;
    }

    public function currentUser()
    {
        $this->target = $this->getSessionId();

        return $this;
    }

    public function say($data, $now = false)
    {
        if ($this->target == null) {
            $room = 'broadcast';
        } else {
            $room = $this->target;
        }

        $data[ 'room' ] = $room;

        $payload = [ 'root' => $this->getRoot(), 'room' => $room, 'bag' => $data ];
        $tattlerBag = [ 'tattlerUri' => $this->getTattlerUri(), 'payload' => $payload ];
        if ($now) {
            return $this->sendPayload(null, $tattlerBag);
        } else {
            Queue::push('Grohman\Tattler\Lib\Processor@sendPayload', $tattlerBag);
        }

        return true;
    }

    public function sendPayload($job, $data)
    {
        Event::fire('grohman.tattler.sendPayload', [ $data ]);
        $result =
            HRequest::init()
                ->uri($this->tattlerApi . '/tattler/emit')
                ->sendsType('application/json')
                ->body($data[ 'payload' ])
                ->method('POST')
                ->send();


        $noErrors = $result->hasErrors() == false;

        if ($job) {
            if ($noErrors) {
                $job->delete();
            } else {
                if ($job->attempts() < 5) {
                    \Log::error('Tattler: restarting job ' . $job->getJobId());
                    $job->release(1);
                } else {
                    throw new \Exception('Tattler: ' . $job->getJobId() . ' failed');
                }
            }

            return;
        } else {
            if (isset($data[ 'attempt' ]) == false) {
                $data[ 'attempt' ] = 1;
            } else {
                $data[ 'attempt' ]++;
            }
            if ($noErrors) {
                return true;
            } else {
                if ($data[ 'attempt' ] < 5) {
                    sleep(1);

                    return $this->sendPayload($job, $data);
                } else {
                    throw new \Exception('Tattler: sendPayload failed -> ' . $result->body);
                }
            }
        }
    }
}
