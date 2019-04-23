# Upgrade guide

- [Upgrading to 2.0.0 from 1.0.10](#upgrade-2.0.0)
- [Upgrading to 2.1.0 from 2.0.0](#upgrade-2.1.0)
- [Upgrading to 2.1.4 from 2.1.3](#upgrade-2.1.4)
- [Upgrading to 3.0.0 from 2.1.4](#upgrade-3.0.0)

<a name="upgrade-2.0.0"></a>
## Upgrading To 2.0.0

Plugin requires OctoberCMS build 420+ with Laravel 5.5 and PHP >=7.0.

Plugin settings was reset to defaults, so please review and update them for your project needs.

<a name="upgrade-2.1.0"></a>
## Upgrading To 2.1.0

Dropbox integration was moved to external [Dropbox Adapter](https://octobercms.com/plugin/renatio-dropboxadapter) plugin. Read this plugin documentation how to configure Dropbox filesystem.

<a name="upgrade-2.1.4"></a>
## Upgrading To 2.1.4

Plugin requires to set database port in config. This only affects older installations of OctoberCMS.

<a name="upgrade-3.0.0"></a>
## Upgrading To 3.0.0

Main dependency `spatie/laravel-backup` was updated to 5.6.4 version.