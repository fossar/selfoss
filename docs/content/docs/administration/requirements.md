+++
title = "Requirements"
weight = 10
extra.show_next = true
+++

selfoss is not a hosted service. It has to be installed on your own web server. This web server must fulfil the following requirements (which are available from most providers)

* PHP 8.1.0 or higher with the `php-gd` and `php-http` extensions enabled. Some spouts may also require `curl`, `mbstring` or `tidy` extensions. The `php-imagick` extension is required if you want selfoss to support SVG site icons.
* MySQL 5.5.3 or higher, PostgreSQL, or SQLite
* Apache web server (nginx and Lighttpd also possible)

With Apache, ensure that you have `mod_authz_core`, `mod_rewrite` and `mod_headers` enabled and that `.htaccess` files are [allowed](http://httpd.apache.org/docs/current/mod/core.html#allowoverride) to set rewrite rules.

selfoss supports all modern browsers, including Mozilla Firefox, Safari, Google Chrome, Opera and Internet Explorer. selfoss also supports mobile browsers on iPad, iPhone, Android and other devices.
