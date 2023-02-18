<?php

// SPDX-FileCopyrightText: 2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace helpers;

class Request {
    public function getContentType(): string {
        return $_SERVER['CONTENT_TYPE'] ?? '';
    }

    public function getBody(): string {
        // Always seems to return a string, even with multipart/form-data where it is not supported (empty string).
        // Coercing it to string for peace of mind.
        return @file_get_contents('php://input') ?: '';
    }

    /**
     * @return mixed
     */
    public function getData() {
        $contentType = $this->getContentType();
        if (str_starts_with($contentType, 'application/json')) {
            $body = $this->getBody();

            return json_decode($body, true);
        } elseif (preg_match('(^(application/x-www-form-urlencoded|multipart/form-data)\b)', $contentType) === 1) {
            return $_POST;
        } else {
            $body = $this->getBody();
            parse_str($body, $data);

            return $data;
        }
    }
}
