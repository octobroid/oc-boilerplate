# OctoberCMS Boilerplate
*by Octobro*

---

[OctoberCMS](http://octobercms.com) is a powerful CMS based on [Laravel PHP Framework](http://laravel.com).

## Requirements

1. PHP >= 7.4
2. [Composer](http://getcomposer.org) 2
3. October CMS License Key (`auth.json`)

## Getting Started

1. Clone to your base project directory.

	```
	git clone https://github.com/octobroid/oc-boilerplate.git <project-name>
	```

2. Don't forget to remove `.git` folder, create your own repository.

	```
	rm -rf !$/.git
	```
    
3. Put the `auth.json` to the root directory for your access to download the October CMS library. Don't forget to remove it from `.gitignore` if you already set up the project.

4. Install composer dependencies.

	```
	composer install
	```

5. Create configuration file `.env` (copy from `.env.example`) and set the database configuration.

	```
	DB_HOST=localhost
	DB_DATABASE=<database-name>
	DB_USERNAME=<database-user>
	DB_PASSWORD=<database-password>
	```

6. Migrate October database.

	```
	php artisan october:migrate
	```

7. For security reason, please generate new application key.

	```
	php artisan key:generate
	```
    
8. To enable [Laravel Horizon](https://laravel.com/docs/master/horizon), run the command below to generate the assets.

    ```
    php artisan horizon:assets
    ```

## Plugins

In this boilerplate, **we've installed**:

1. [RainLab.User](https://octobercms.com/plugin/rainlab-user)
1. [RainLab.Debugbar](https://github.com/rainlab/debugbar-plugin)
1. [RainLab.Pages](https://octobercms.com/plugin/rainlab-pags)
1. [RainLab.Sitemap](https://octobercms.com/plugin/rainlab-sitemap)
1. [RainLab.GoogleAnalytics](https://octobercms.com/plugin/rainlab-googleanalytics)
1. [Bedard.Debugbar](https://octobercms.com/plugin/bedard-debugbar)
1. [Mja.Mail](https://octobercms.com/plugin/mja-mail)
1. [Jacob.Horizon](https://octobercms.com/plugin/jacob-horizon)

More plugins that we recommend (not installed yet):

1. [RainLab.Blog](https://octobercms.com/plugin/rainlab-blog)
1. [RainLab.Translate](https://octobercms.com/plugin/rainlab-translate)
1. [Responsiv.Uploader](https://octobercms.com/plugin/responsiv-uploader)

To install plugin, run the command:

```
php artisan plugin:install <plugin-name>
```

## Frontend Theme

We implement the minimalist CSS library [Pico.css](https://picocss.com/).

## Coding Standards

Please follow the following guide:

* [OctoberCMS Developer Guide](https://octobercms.com/help/guidelines/developer)
