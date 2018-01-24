# OctoberCMS Boilerplate
*by Octobro*

---

[OctoberCMS](http://octobercms.com) is a powerful CMS based on [Laravel PHP Framework](http://laravel.com).

## Prerequisites

1. PHP > 5.5.9
1. MySQL
1. [Composer](http://getcomposer.org)
1. [Yarn](https://yarnpkg.com)

## Getting Started

1. Clone to your base project directory.

	```
	git clone https://github.com/octobroid/oc-boilerplate.git <project-name>
	```

2. Don't forget to remove `.git` folder, create your own repository.

	```
	rm -rf !$/.git
	```

3. Install composer dependencies.

	```
	composer install
	```

4. Create configuration file `.env` (copy from `.env.example`) and set the database configuration.

	```
	DB_HOST=localhost
	DB_DATABASE=<database-name>
	DB_USERNAME=<database-user>
	DB_PASSWORD=<database-password>
	```

5. Migrate October database.

	```
	php artisan october:up
	```

6. Install frontend library using Yarn. Go to theme directory first.

    ```
    cd themes/my-theme
	yarn install
	```

7. For security reason, please generate new application key.

	```
	php artisan key:generate
	```

## Plugins

In this boilerplate, **we've installed**:

1. [RainLab.User](https://octobercms.com/plugin/rainlab-user)
1. [RainLab.Pages](https://octobercms.com/plugin/rainlab-pags)
1. [RainLab.Sitemap](https://octobercms.com/plugin/rainlab-sitemap)
1. [RainLab.GoogleAnalytics](https://octobercms.com/plugin/rainlab-googleanalytics)
1. [October.Drivers](https://octobercms.com/plugin/october-drivers)
1. [Bedard.Debugbar](https://octobercms.com/plugin/bedard-debugbar)
1. [Triasrahman.Premailer](https://octobercms.com/plugin/triasrahman-premailer)
1. [Mja.Mail](https://octobercms.com/plugin/mja-mail)

More plugins that we recommend (not installed yet):

1. [RainLab.Blog](https://octobercms.com/plugin/rainlab-blog)
1. [Responsiv.Uploader](https://octobercms.com/plugin/responsiv-uploader)
1. [eBussola.Feedback](https://octobercms.com/plugin/ebussola-feedback)

To install plugin, run the command:

```
php artisan plugin:install <plugin-name>
```

## Frontend Libraries

All frontend libraries are managed using **bower**. These packages are installed by default:

1. [Bootstrap](https://getbootstrap.com)
1. [Font Awesome](https://fortawesome.github.io/Font-Awesome)
1. [FastClick](https://github.com/ftlabs/fastclick)

To install additional library, run the command:

```
yarn add <package-name>
```

## Coding Standards

Please follow the following guides and code standards:

* [PSR 4 Coding Standards](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md)
* [PSR 2 Coding Style Guide](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
* [PSR 1 Coding Standards](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md)
