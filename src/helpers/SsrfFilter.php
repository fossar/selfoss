<?php

// SPDX-FileCopyrightText: 2026 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Selfoss\helpers;

use Graby\HttpClient\Plugin\ServerSideRequestForgeryProtection\Exception\InvalidURLException;
use Graby\HttpClient\Plugin\ServerSideRequestForgeryProtection\Options;
use Graby\HttpClient\Plugin\ServerSideRequestForgeryProtection\Url;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriFactoryInterface;

final class SsrfFilter {
    public function __construct(
        private UriFactoryInterface $uriFactory,
        private Options $options,
    ) {
    }

    /**
     * @param callable(RequestInterface, array<string, mixed>): PromiseInterface $handler
     *
     * @return callable(RequestInterface, array<string, mixed>): PromiseInterface
     */
    public function __invoke(callable $handler): callable {
        return function(RequestInterface $request, array $options) use ($handler): PromiseInterface {
            try {
                $urlData = Url::validateUrl((string) $request->getUri(), $this->options);
            } catch (InvalidURLException $e) {
                return new RejectedPromise(new RequestException($e->getMessage(), $request, null, $e));
            }

            $uri = $this->uriFactory->createUri($urlData['url']);

            if ((string) $uri !== (string) $request->getUri()) {
                $request = $request->withUri($uri->withHost($urlData['host']));
            }

            return $handler($request, $options);
        };
    }
}
