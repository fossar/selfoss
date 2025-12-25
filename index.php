<?php

declare(strict_types=1);

// SPDX-License-Identifier: GPL-3.0-or-later
// SPDX-FileCopyrightText: 2025 Jan Tojnar <jtojnar@gmail.com>

use Psr\Container\ContainerInterface;

require __DIR__ . '/src/common.php';

/** @var ContainerInterface $container */
$routes = $container->get(Selfoss\Web\Routes::class);
$routes->run();
