<?php

namespace Jacob\Horizon\Classes\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;

class HorizonFileController extends Controller
{
    public function appJS()
    {
        return $this->getHorizonFile('js/app.js', 'text/javascript');
    }

    public function appCSS()
    {
        return $this->getHorizonFile('css/app.css', 'text/css');
    }

    public function horizonSVG()
    {
        return $this->getHorizonFile('img/horizon.svg', 'image/svg+xml');
    }

    private function getHorizonFile($filePath, $type) {
        return Response::make(file_get_contents(base_path('vendor/laravel/horizon/public/' . $filePath)), 200, [
            'Content-Type' => $type
        ]);
    }
}