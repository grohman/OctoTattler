<?php namespace Grohman\Tattler;

use Backend\Facades\BackendAuth;
use Grohman\Tattler\Facades\Tattler;
use Illuminate\Support\Facades\Event;
use System\Classes\PluginBase;

/**
 * Class Plugin
 * @package Grohman\Tattler
 */
class Plugin extends PluginBase
{

    /**
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'OctoTattler',
            'description' => 'October Tattler plugin',
            'author'      => 'Daniel Podrabinek',
            'icon'        => 'icon-leaf',
        ];
    }

    /**
     *
     */
    public function boot()
    {
        if (null != config()->get('grohman.tattler::server')) {
            Event::listen('backend.list.extendColumns', function ($widget) {
                $this->inject($widget);
            });

            Event::listen('backend.form.extendFields', function ($widget) {
                $this->inject($widget);
            });
        }
    }

    /**
     * @param $widget
     */
    protected function inject($widget)
    {
        if (isset($widget->model) && method_exists($widget->model, 'isClassExtendedWith')) {
            if ($widget->model->isClassExtendedWith('\Grohman\Tattler\Lib\Inject') == false) {
                $widget->model->extendClassWith('\Grohman\Tattler\Lib\Inject');
            }

            if (method_exists($widget, 'getColumns')) {
                $columns = $widget->model->getWidgetColumns($widget->getColumns());
            } else {
                $columns = $widget->model->getWidgetColumns();
            }

            $rooms = [];

            if ($columns) {
                $room = Tattler::addRoom(get_class($widget->model));
                $room->allow();
                $rooms[] = $room->getName();
            }
            $user = Tattler::addUser(BackendAuth::getUser());
            $user->allow();
            $rooms[] = $user->getName();

            $this->loadAssets($widget, Tattler::getDefaultRooms($rooms));
        }
    }

    /**
     * @param $widget
     * @param $rooms
     */
    protected function loadAssets($widget, $rooms)
    {
        $widget->addCss('https://cdn.jsdelivr.net/jquery.gritter/1.7.4/css/jquery.gritter.css');
        $widget->addJs('https://cdn.jsdelivr.net/jquery.gritter/1.7.4/js/jquery.gritter.min.js');
        $widget->addJs('/plugins/grohman/tattler/js/socket.io-1.3.7.min.js');
        $widget->addJs('/plugins/grohman/tattler/js/tattler.js',
            ['id' => 'tattlerJs', 'data-debug' => env('APP_DEBUG'), 'data-rooms' => json_encode($rooms)]);
        $widget->addJs('/plugins/grohman/tattler/js/crud_handlers.js');
    }
}
