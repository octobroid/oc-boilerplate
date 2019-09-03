Laravel horizon plugin for OctoberCMS
=
Provide [Laravel Horizon](https://horizon.laravel.com/) inside your OctoberCMS application.

> Minimal requirement : OctoberCMS 420, PHP 7.1

## Setup
> Requirement : Redis queue and PHP >=7.1

1. Check if your php version in your composer.json require is `>=7.1`
2. Install laravel horizon trough composer `composer require laravel/horizon "^2.2"`
3. Publish the laravel horizon vendors `php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"`
4. Install this plugin
4. Edit the config file `config/horizon.php` - [see here](https://divinglaravel.com/horizon/before-the-dive)
5. run `php artisan horizon`

For production this command needs to be supervised by a tool like supervisord.
Supervisord will take care of restarting a process when it fails.

[More information about running Horizon](https://laravel.com/docs/master/horizon#running-horizon)

## Graphs
Horizon provides a queue usage graph, if you want use them you need to have the [October CMS scheduler cron](http://octobercms.com/docs/setup/installation#crontab-setup)  correctly configured.