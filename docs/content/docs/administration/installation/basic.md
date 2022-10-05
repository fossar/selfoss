+++
title = "Basic installation"
weight = 11
+++

selfoss is a lightweight php based application. Just follow the simple installation instructions:

1. Upload all files in the selfoss directory (IMPORTANT: also upload the hidden `.htaccess` files)
2. Make the directories `data/cache`, `data/favicons`, `data/logs`, `data/thumbnails` and `data/sqlite` writeable
3. Insert database access data in `config.ini` (see [database options](@/docs/administration/options.md#db-type) – you do not have to change anything if you would like to use SQLite.)
4. You do not need to create database tables, they will be created automatically.
5. Create cron job for updating feeds and point it to https://yoururl.com/update via `wget` or `curl`. You can also execute the `cliupdate.php` from command line.

For further questions or any problems, use our [support forum](forum). For a more detailed step-by-step example installation, please visit the [wiki](https://github.com/fossar/selfoss/wiki/).
