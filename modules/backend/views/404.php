<!DOCTYPE html>
<html lang="<?= App::getLocale() ?>">
    <head>
        <meta charset="utf-8">
        <title><?= Lang::get('backend::lang.page.not_found.label') ?></title>
        <link href="<?= Url::to('/modules/system/assets/css/styles.css') ?>" rel="stylesheet">
    </head>
    <body>
        <div class="container">
            <h1><i class="icon-chain-broken warning"></i> <?= Lang::get('backend::lang.page.not_found.label') ?></h1>
            <br>
            <p class="lead"><?= Lang::get('backend::lang.page.not_found.help') ?></p>
            <p class="lead"><span style="font-family: monospace;"> <?= e(url()->current()) ?></span></p>
            <p><a href="javascript:history.go(-1)"><?= Lang::get('backend::lang.page.not_found.back_link') ?></a></p>
            <p><a href="<?= Backend::url('') ?>"><?= Lang::get('backend::lang.page.access_denied.cms_link') ?></a></p>
        </div>
    </body>
</html>
