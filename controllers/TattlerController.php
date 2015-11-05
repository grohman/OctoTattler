<?php namespace Grohman\Tattler\Controllers;

use Grohman\Tattler\Facades\Lib as Tattler;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Tattler Controller Back-end Controller
 */
class TattlerController extends Controller
{

    /** Получение адреса сокета
     * @param Request $request
     * @return mixed
     */
    public function getIndex(Request $request)
    {
        return Tattler::getWs($request->isSecure());
    }

    /** Отправка socketId и листинга запрашиваемых комнат
     * @param Request $request
     * @return mixed
     */
    public function postIndex(Request $request)
    {
        return Tattler::getRooms($request->all());
    }
}