<?php

declare(strict_types=1);

namespace spouts;

interface Parameter {
    public const TYPE_TEXT = 'text';
    public const TYPE_URL = 'url';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_SELECT = 'select';

    public const VALIDATION_ALPHA = 'alpha';
    public const VALIDATION_EMAIL = 'email';
    public const VALIDATION_NUMERIC = 'numeric';
    public const VALIDATION_INT = 'int';
    public const VALIDATION_ALPHANUMERIC = 'alnum';
    public const VALIDATION_NONEMPTY = 'notempty';
}
