+++
title = "Updating"
weight = 60
+++

Read carefully following instructions before you update your selfoss installation:

1. Backup your database and your `data/` directory
2. **IMPORTANT: do not delete the `data/` directory**. Delete all old files and folders excluding the directory `data/`.
3. Upload all new files and folders excluding the `data/` directory (IMPORTANT: also upload the hidden `.htaccess` files).
4. If upgrading from 1.3 or earlier, rename your directory `/data/icons` into `/data/favicons`
5. If upgrading from 2.17 or earlier, delete the files <code>/public/all-v<var>*</var>.css</code> and <code>/public/all-v<var>*</var>.js</code>. Additionally, when using Lighttpd, please check [the wiki](https://github.com/fossar/selfoss/wiki/Lighttpd-configuration#upgrading-from-selfoss-217-or-lower).
6. Clean your browser cache.

For further questions or on any problem use our [support forum](https://forum.selfoss.aditu.de/).
