<?php

if(! function_exists('color_hex_to_rgb')){
    function color_hex_to_rgb($hex_color, $decimal = 0){
        $hex_color       = str_replace('#', '', $hex_color);
        $split_hex_color = str_split($hex_color, 2);
        $rgb             = array();

        foreach ($split_hex_color as $key => $value) {
            $rgb[$key] = hexdec($value);
        }

        return implode(', ', $rgb);
    }
}

if(! function_exists('get_subdomain')){
    function get_subdomain(){
        if(php_sapi_name() != 'cli'){
            $url  = $_SERVER['HTTP_HOST'];
            $code = data_get(explode('.', $url), '0');
            
            return $code;
        }
    }
}

if(! function_exists('base64ToFile')){
    function base64ToFile($string)
    {
        $mimeType = substr(array_get(explode(';', $string), 0), 5);

        switch($mimeType) {
            case 'image/jpeg':
                $fileExt = '.jpg';
                break;
            case 'image/png':
                $fileExt = '.png';
                break;
            case 'image/gif':
                $fileExt = '.gif';
                break;
            default:
                $fileExt = '';
        }

        $data = base64_decode(array_last(explode(',', $string)));

        $filePath = temp_path(time() . rand() . $fileExt);

        file_put_contents($filePath, $data);

        $file = new \System\Models\File;
        $file = $file->fromFile($filePath);

        unlink($filePath);

        return $file;
    }
}

if(! function_exists('withoutNull')) {
    function withoutNull(array $args)
    {
        return array_filter($args, function ($value) {
            return !is_null($value);
        });
    }
}

if(! function_exists('phone_to_62')){
    function phone_to_62($phone){
        $six_two_phone = preg_replace("/[^0-9]/", '', $phone);

        switch (substr($six_two_phone, 0, 1)) {
            case '0':
                $six_two_phone = '62' . substr($six_two_phone, 1);
                break;
            case '8':
                $six_two_phone = '62' . $six_two_phone;
                break;
        }

        return $six_two_phone;
    }
}