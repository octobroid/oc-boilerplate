<?php

/**
 * Register System routes before all user routes.
 */
App::before(function ($request) {
    /*
     * Combine JavaScript and StyleSheet assets
     */
    Route::any('combine/{file}', [\System\Classes\SystemController::class, 'combine']);

    /*
     * Resize image assets
     */
    Route::get('resize/{file}', [\System\Classes\SystemController::class, 'resize']);
});
