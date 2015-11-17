<?php namespace Grohman\Tattler;

use Backend\Facades\BackendAuth;
use Event;
use Grohman\Tattler\Facades\Tattler;
use System\Classes\PluginBase;

/**
 * tattler Plugin Information File
 */
class Plugin extends PluginBase
{

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'OctoTattler',
            'description' => 'No description provided yet...',
            'author' => 'Daniel Podrabinek',
            'icon' => 'icon-leaf'
        ];
    }

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

            if ($columns) {
                $room = Tattler::addRoom(get_class($widget->model));
                $room->allow();

                $user = Tattler::addUser(BackendAuth::getUser());
                $user->allow();

                $this->loadAssets($widget, Tattler::getDefaultRooms([ $room->getName(), $user->getName() ]));
            }
        }
    }

    protected function loadAssets($widget, $rooms)
    {
        $widget->addCss('https://cdn.jsdelivr.net/jquery.gritter/1.7.4/css/jquery.gritter.css');
        $widget->addJs('https://cdn.jsdelivr.net/jquery.gritter/1.7.4/js/jquery.gritter.min.js');
        $widget->addJs('https://cdn.socket.io/socket.io-1.3.7.js');
        $widget->addJs('/plugins/grohman/tattler/js/tattler.js',
            [ 'id' => 'tattlerJs', 'data-debug' => env('APP_DEBUG'), 'data-rooms' => json_encode($rooms) ]);
        $widget->addJs('/plugins/grohman/tattler/js/crud_handlers.js');
    }
}
