<?php namespace Backend\Classes;

use Backend\Models\BrandSetting;
use Exception;
use Url;

class LoginCustomization
{
    public static function getCustomizationVariables($controller)
    {
        $result = [];

        try {
            $result['logo'] = BrandSetting::getLogo();
        }
        catch (Exception $ex) {
            $result['logo'] = BrandSetting::getDefaultLogo();
        }

        if (!$result['logo']) {
            $result['logo'] = Url::asset('/modules/backend/assets/images/october-logo.svg');
        }

        $result['loginCustomization'] = BrandSetting::getLoginPageCustomization();

        $defaultImageNum = rand(1, 5);
        $result['defaultImage1x'] = $defaultImageNum.'.png';
        $result['defaultImage2x'] = $defaultImageNum.'@2x.png';

        return (object)$result;
    }
}
