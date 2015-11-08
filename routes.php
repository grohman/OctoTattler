<?php

    Route::get('_tattler', 'Grohman\Tattler\Controllers\SettingsController@getIndex');
    Route::post('_tattler', 'Grohman\Tattler\Controllers\SettingsController@postIndex');
