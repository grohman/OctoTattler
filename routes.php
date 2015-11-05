<?php

    Route::get('_tattler', 'Grohman\Tattler\Controllers\TattlerController@getIndex');
    Route::post('_tattler', 'Grohman\Tattler\Controllers\TattlerController@postIndex');
