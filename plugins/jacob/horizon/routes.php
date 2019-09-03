<?php

Route::group([
    'prefix' => 'vendor/horizon',
    'namespace' => 'Jacob\Horizon\Classes\Controllers'
], function () {
    Route::get('js/app.js', 'HorizonFileController@appJS');

    Route::get('css/app.css', 'HorizonFileController@appCSS');

    Route::get('img/horizon.svg', 'HorizonFileController@horizonSVG');
});

