<?php namespace Grohman\Tattler\Lib;

use Backend\Facades\BackendAuth;
use Cache;
use Carbon\Carbon;
use Event;
use Exception;
use Grohman\Tattler\Facades\Tattler;
use Log;
use October\Rain\Extension\ExtensionBase;

class Inject extends ExtensionBase
{
    protected $target;

    public function __construct($target)
    {
        $this->target = $target;
        Event::listen('eloquent.created:*', function ($model) use ($target) {
            if (get_class($model) == get_class($target)) {
                Tattler::room($this->getRoom())->say($this->tattlerCollectMessageBag($model, 'crud_create'));
            }
        });
        Event::listen('eloquent.updated:*', function ($model) use ($target) {
            if(get_class($model) == 'RainLab\User\Models\User' && $this->getUser()['id'] == null) { // пропускаем сообщения об авторизации
                return;
            }
            if (get_class($model) == get_class($target)) {
                Tattler::room($this->getRoom())->say($this->tattlerCollectMessageBag($model, 'crud_update'));
            }
        });
        Event::listen('eloquent.deleted:*', function ($model) use ($target) {
            if (get_class($model) == get_class($target)) {
                Tattler::room($this->getRoom())->say($this->tattlerCollectMessageBag($model, 'crud_delete'));
            }
        });
    }

    /** Возвращает имя комнаты для реализации канала трафика в сокет
     * @return mixed
     */
    public function getRoom()
    {
        $result = get_class($this->target);
        Event::fire('tattler.getRoom', [&$result]);
        return $result;
    }

    /** Подготовка к отправке данных в сокет
     * @param $model
     * @param $handler
     * @return array
     */
    protected function tattlerCollectMessageBag($model, $handler)
    {
        try {
            $message = [ ];

            $columns = $this->target->getWidgetColumns(); // метод добавляется динамически из Plugin

            $modelData = $model->toArray();

            foreach ($columns as $column => $name) {
                if (isset($modelData[ $column ]) && is_object($model[ $column ]) == false && is_array($model[ $column ]) == false && $modelData[ $column ] != '') {
                    $message[ $column ] = $modelData[ $column ];
                }
            }

            $result = [
                'message_id' => uniqid(),
                'handler' => $handler,
                'row_id' => $model->getKey(),
                'row_key' => $model->getKeyName(),
                'by' => $this->getUser(),
                'at' => Carbon::now(),
                'columns' => $columns,
                'row_data' => $message
            ];

            return $result;
        } catch(Exception $e){
            if(config()->get('app.debug') == 1) {
                Log::error('Tattler::collectMessageBag -> ' . $e->getMessage());
            }
        }
    }

    /** Возвращает данные о текущем пользователе. Пусть все знают кто сделал изменения в базе данных.
     * @return array
     */
    protected function getUser()
    {
        if(BackendAuth::getUser()) {
            $user = BackendAuth::getUser();

            return [ 'id' => $user->getKey(), 'name' => $user[ 'first_name' ] . ' ' . $user[ 'last_name' ], ];
        }
        return ['id' => null, 'name' => 'Анонимно'];
    }

    /** Возвращает ключ массива с названиями столбцов, полученных в Plugin
     * @return string
     */
    public function getCacheIdx()
    {
        return 'tattler:models:' . get_class($this->target) . ':' . app()->getLocale();
    }

    public function getWidgetColumns($columns = null)
    {
        if($columns) {
            return Cache::remember($this->getCacheIdx(), 1440, function () use ($columns) {
                $result = [ ];
                foreach ($columns as $column => $col) {
                    $result[ $column ] = trans($col->label);
                }

                return $result;
            });
        } else {
            return Cache::get($this->getCacheIdx());
        }
    }

    public function forgetWidgetColumns()
    {
        return Cache::forget($this->getCacheIdx());
    }
}