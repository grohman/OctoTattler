<?php namespace Grohman\Tattler;

use Backend\Facades\BackendAuth;
use Carbon\Carbon;
use Event;
use Grohman\Tattler\Facades\Lib as Tattler;
use Illuminate\Support\Facades\Cache;
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
            'name' => 'tattler',
            'description' => 'No description provided yet...',
            'author' => 'Grohman',
            'icon' => 'icon-leaf'
        ];
    }

    public function boot()
    {
        if(null != config()->get('grohman.tattler::server')) {
            // Extend all backend list usage
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
        if ($widget->model->isClassExtendedWith('\Grohman\Tattler\Lib\Inject') == false) {
            $widget->model->extendClassWith('\Grohman\Tattler\Lib\Inject');
        }

        $cacheIdx = $widget->model->getCacheIdx();

        if(method_exists($widget, 'getColumns')) {
            $columns = Cache::remember($cacheIdx, Carbon::now()->addHour(), function () use ($widget) {
                $result = [ ];
                foreach ($widget->getColumns() as $column => $col) {
                    $result[ $column ] = trans($col->label);
                }

                return $result;
            });
        } else {
            $columns = Cache::get($cacheIdx);
        }

        if ($columns) {
            // при открытии страницы редактирования записи столбцов таблицы уже нету
            $widget->model->addDynamicMethod('getWidgetColumns', function () use ($columns) {
                return $columns;
            });

            $widget->model->addDynamicMethod('forgetWidgetColumns', function () use ($cacheIdx) {
                Cache::forget($cacheIdx);
            });

            $room = Tattler::addRoom(get_class($widget->model));
            $room->allow();

            $user = Tattler::addUser(BackendAuth::getUser());
            $user->allow();

            $this->loadAssets($widget, Tattler::getDefaultRooms([ $room->getName(), $user->getName() ]));
        }
    }

    protected function loadAssets($widget, $rooms)
    {
        $widget->addCss('https://cdn.jsdelivr.net/jquery.gritter/1.7.4/css/jquery.gritter.css');
        $widget->addJs('https://cdn.jsdelivr.net/jquery.gritter/1.7.4/js/jquery.gritter.min.js');
        $widget->addJs('https://cdn.socket.io/socket.io-1.3.7.js');
        $widget->addJs('/plugins/grohman/tattler/js/tattler.js',
            [ 'id' => 'tattlerJs', 'data-rooms' => json_encode($rooms) ]);
        $widget->addJs('/plugins/grohman/tattler/js/crud_handlers.js');
    }
}
