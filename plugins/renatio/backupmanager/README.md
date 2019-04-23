# Backup Manager Plugin

Backup your whole October application with ease.

The backup is a zip file that contains all files in the directories you specify along with a dump of your database. The backup can be stored on any of the filesystems you have configured in October. You can backup your application to multiple filesystems at once. In addition to making the backup, the plugin can also clean up old backups.

## Installation

Use October [Marketplace](http://octobercms.com/help/site/marketplace) and __Add to project__ button.

## Features
* Backup database and application files with mouse click
* Amazon S3, Rackspace, Dropbox Cloud storage support
* MySQL, PostgreSQL, SQLite and Mongo databases support
* Configurable scheduler for automatic backups
* Extensive settings options
* Encryption and password protection

## Requirements

This plugin requires **PHP 7.0 or higher** with the ZIP module and **Laravel 5.5 or higher**. It's not compatible with Windows servers.

The plugin needs free disk space where it can create backups. Ensure that you have at least as much free space as the total size of the files you want to backup.

Make sure `mysqldump` is installed on your system if you want to backup MySQL databases.

Make sure `pg_dump` is installed on your system if you want to backup PostgreSQL databases.

Make sure `mongodump` is installed on your system if you want to backup Mongo databases.

## Support

Please use [GitHub Issues Page](https://github.com/mplodowski/backupmanager-plugin-public/issues) to report any issues with plugin.

> Reviews should not be used for getting support or reporting bugs, if you need support please use the Plugin support link.

## Like this plugin?

If you like this plugin, give this plugin a Like or Make donation with [PayPal](https://www.paypal.me/mplodowski).

# Documentation

## Usage

After installation plugin will register backend **Backups** menu position.

**Application backup** button will create backup with all project files and database dump file. To change which files will be included see plugin **Settings**. Default settings will take project **base path** and exclude **vendor** and **node_modules** directories from it.

**Database backup** button will create only database backup.

**Delete selected** button will delete selected backups. This action is irreversible, so be careful.

**Clean old backups** button will cleanup old backups based on plugin **Settings**.

**View latest backup log** button will show latest backup log in popup window.

> **Important note:** Log is not created when you manually run console command.

To download backup just click position on the list.

## Searching backups list
Backups names are created dynamically from current datetime. This allows to easily search for backups created at specific date and time.

## Maximum execution time error

When you experience **Maximum execution time of .. seconds exceeded** error than most likely application backup is to large and PHP cannot do this process in a single request.

> What can I do?

1. Change **max_execution_time** property value in your PHP configuration.
2. Use [Scheduler](#scheduler) to perform automatic backups (recommended).
3. Use [Console commands](#console-commands) to perform backups.

## Settings

This plugin ships with a settings page. Go to **Settings** and you will see a menu item **Backup Manager** listed under **Backup** section.

### Source

Property | Description
--------------------- | ---------------------
**Databases** | The names of the connections to the databases that should be backed up. MySQL, PostgreSQL, SQLite and Mongo databases are supported.
**Gzip database dump** | The database dump can be gzipped to decrease disk space usage.
**Follow links** | Determines if symlinks should be followed.
**Include** | The list of directories and files that will be included in the backup. Leave empty to backup whole October project.
**Exclude** | These directories and files will be excluded from the backup. Directories used by the backup process will automatically be excluded.

### Destination

Property | Description
--------------------- | ---------------------
**Filename prefix** | The filename prefix used for the backup zip file.
**Name** | The name of this application.configured on your chosen filesystem(s)
**Disks** | The disk names on which the backups will be stored.

### Scheduler

Configure how often plugin will run automatic tasks for database backup, application backup and clean old backups actions.

> **Important note:** For scheduled tasks to operate correctly you must set up the scheduler: https://octobercms.com/docs/setup/installation#crontab-setup

### Security

Here you can specify password protection for backups. You will be asked to enter this password in order to unzip backup file.

#### Password

Remember to use long strings and to keep your password safe – without it you will never be able to open your backup.

Leave it blank if you want to keep your backup without a password.

#### Encryption

Using the *PKWARE/ZipCrypto* crypto gives you the best portability as most operating systems can natively unzip the file – however, ZipCrypto might be weak. The Winzip AES-methods on the other hand might require a separate app and/or licence to be able to unzip depending on your OS; suggestions for macOS are [Keka](http://www.kekaosx.com/en/) and [Stuffit Expander](https://itunes.apple.com/us/app/stuffit-expander-16/id919269455).

> **Important note:** When zipping very large files ZipCrypto might be very inefficient as the entire data-set will have to be loaded into memory to perform the encryption, if the zipped file's content is bigger than your available RAM you will run out of memory.

### Cleanup

Property | Description
--------------------- | ---------------------
**Keep all backups for days** | The number of days for which backups must be kept.
**Keep daily backups for days** | The number of days for which daily backups must be kept.
**Keep weekly backups for weeks** | The number of weeks for which one weekly backup must be kept.
**Keep monthly backups for months** | The number of months for which one monthly backup must be kept.
**Keep yearly backups for years** | The number of years for which one yearly backup must be kept.
**Delete oldest backups when using more megabytes than** | After cleaning up the backups remove the oldest backup until this amount of megabytes has been reached.

### Dumping the database

`mysqldump` and `pg_dump` are used to dump the database. If they are not installed in a default location, you can add a key named `dump.dump_binary_path` in October `database.php` config file. Only fill in the path to the binary. Do not include the name of the binary itself.

If your database dump takes a long time, you might exceed the default timeout of 60 seconds. You can set a higher (or lower) limit by providing a `dump.timeout` config key which specifies, in seconds, how long the command may run.

Here's an example for MySQL:

```
// config/database.php
'connections' => [
	'mysql' => [
		'driver' => 'mysql'
		...,
		'dump' => [
		   'dump_binary_path' => '/path/to/the/binary', // only the path, so without `mysqldump` or `pg_dump`
		   'use_single_transaction',
		   'timeout' => 60 * 5, // 5 minute timeout
		   'exclude_tables' => ['table1', 'table2'],
		   'add_extra_option' => '--optionname=optionvalue',
		]  
	],
```

## Filesystems

Plugin supports following storage drivers:

* Local Storage
* Amazon S3 Cloud Storage
* Rackspace Cloud Storage
* Dropbox Cloud Storage

More drivers can be added on feature requests. Just create an issue with **[Feature Request]** in title and I will see what can be done.

The filesystem configuration file is located at **config/filesystems.php**. Within this file you may configure all of your "disks". Example configurations for each supported driver is included in the configuration file. So, simply modify the configuration to reflect your storage preferences and credentials!

### Amazon S3 and Rackspace configuration

Install [October Drivers](http://octobercms.com/plugin/october-drivers) plugin.

Go to plugin **Settings** and check `s3/rackspace` disk in **Destination** tab.

### Dropbox configuration

Install [Dropbox Adapter](https://octobercms.com/plugin/renatio-dropboxadapter) plugin.

Read this external plugin documentation and configure Dropbox filesystem.

Go to plugin **Settings** and check `dropbox` disk in **Destination** tab.

## Console commands

Plugin will create three new artisan commands for working with console.

**backup:run** command will run new backup process. Add **--only-db** option for backup only database.

**backup:clean** command will run clean old backups process.