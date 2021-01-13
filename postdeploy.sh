#!/bin/sh

git submodule init && git submodule update
php artisan config:cache
php artisan horizon:assets