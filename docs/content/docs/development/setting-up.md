+++
title = "Setting up development environment"
weight = 10
+++

Selfoss makes use of many libraries to make our job as developers easier. To install them, you will need appropriate package managers. The server side uses [composer](https://getcomposer.org/) for PHP libraries and the client side uses [npm](https://www.npmjs.com/get-npm) for the JavaScript world.

Then you will be able to run `npm run install-dependencies` to install the libraries, and `npm run dev` to start a program that will rebuild client-side assets when needed.

To run the server side you will need at least [PHP](https://www.php.net/downloads) to be able to run the development server using `php -S 127.0.0.1:8000 run.php`. It would be also nice to have an array of database servers (MySQL and PostgreSQL) and web servers (Apache httpd and nginx) but the server built into PHP and SQLite will suffice for small changes.

For changing the selfoss web page in `docs/` directory, you will also want [Zola](https://www.getzola.org/documentation/getting-started/installation/).

You can install all of the above using your package manager of choice or by downloading the programs from the linked pages.

Alternately, on Linux and MacOS, you can run [Nix package manager](https://nixos.org/download.html)’s `nix-shell` command, and you will find yourself in a development environment with all the necessary dependencies on `PATH`.

Or even nicer, you can install [direnv](https://direnv.net/) and your terminal will load the Nix-based development environment automatically when you `cd` into the `selfoss` directory.
