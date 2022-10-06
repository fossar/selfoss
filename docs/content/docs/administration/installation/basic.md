+++
title = "Basic installation"
weight = 11
+++

selfoss is a lightweight PHP-based application. If you have a web server with PHP enabled, such as on a shared web host, you can just follow the steps below:

1. Upload all files in the `selfoss/` directory you extracted to the web root of your web server. **Important:** also upload the hidden `.htaccess` files.
2. Make the directories `data/cache`, `data/favicons`, `data/logs`, `data/thumbnails` and `data/sqlite` writeable for the user running PHP.
3. If you want to use MySQL or PostgreSQL database to store the selfoss data, set up a database using your server’s control panel or database’s command line tool. Then create a `config.ini` file as described in [“Configuring” section](@/docs/administration/configuring.md) and set the [database options](@/docs/administration/options.md#db-type) appropriately. You do not need to do anything if you are fine with the default SQLite. You do not need to create database tables, they will be created automatically.
4. If you want selfoss to periodically update the sources, set up a cron job/systemd timer for updating feeds and point it to https://yoururl.com/update via `wget` or `curl`. You can also execute the `cliupdate.php` from command line.

Next, you can [configure selfoss](@/docs/administration/configuring.md), for example, to require a password to access, or to better suit your preferences.

For further questions or any problems, use our [support forum](forum). For a more detailed step-by-step example installation, please visit the [community wiki](https://github.com/fossar/selfoss/wiki/).
