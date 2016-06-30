<?php namespace Grohman\Tattler\Controllers;

use Grohman\Tattler\Facades\Tattler;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Class SettingsController
 * @package Grohman\Tattler\Controllers
 */
class SettingsController extends Controller
{

    /**
     * @param Request $request
     * @return mixed
     */
    public function getIndex(Request $request)
    {
        return Tattler::getWs($request->isSecure());
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function postIndex(Request $request)
    {
        return Tattler::getRooms($request->all());
    }
}