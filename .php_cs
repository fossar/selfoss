<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('libs')
    ->exclude('utils')
    ->in(__DIR__)
    ->name('*.phtml');

$rules = [
    '@Symfony' => true,
    // why would anyone put braces on different line
    'braces' => ['position_after_functions_and_oop_constructs' => 'same'],
    'function_declaration' => ['closure_function_spacing' => 'none'],
    // overwrite some Symfony rules
    'concat_space' => ['spacing' => 'one'],
    'phpdoc_align' => false,
    'phpdoc_no_empty_return' => false,
    'phpdoc_summary' => false,
    'trailing_comma_in_multiline_array' => false,
    'yoda_style' => null,
    'semicolon_after_instruction' => false,
    // additional rules
    'array_syntax' => ['syntax' => 'short'],
    'ordered_imports' => true,
    'phpdoc_order' => true,
    'psr4' => true,
];

return PhpCsFixer\Config::create()
    ->setRules($rules)
    ->setRiskyAllowed(true)
    ->setFinder($finder);
