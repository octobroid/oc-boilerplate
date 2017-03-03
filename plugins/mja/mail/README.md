# Details

[Mja.Mail](http://octobercms.com/plugin/mja-mail) plugin is used for easy outgoing mail logging and detailed statistics.

# Documentation

## Installation

In order to install this plugin, please follow the steps below:

1. Open up the OctoberCMS backend (administration panel; http://localhost:8000/backend);
2. Go to `Settings` and then `Updates` in the left side panel;
3. Click `Install plugins` and search for `Mja.Mail`;
4. Once You have found the plugin, simply click on it and it will be installed.

## Info

As soon as Mja.Mail plugin is installed it will be set up and start tracking the outgoing mails. It does so by using Laravel's events. As soon as email is beginning to go out it simply logs the data in database.

An important thing to understand is that this plugin will append a transparent 1x1 PNG image to all of the outgoing email. This image is used to track email opens.

Detailed statistics are available right out of the box.

If You have enjoyed this plugin - please feel free to leave a positive review on the [OctoberCMS plugin page](http://octobercms.com/plugin/mja-mail) or [donate](http://octobercms.com/plugin/mja-mail).

# License

The MIT License (MIT)

Copyright (c) 2015 Matiss Janis Aboltins

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
