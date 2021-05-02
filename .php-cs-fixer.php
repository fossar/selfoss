<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('assets')
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
    'yoda_style' => false,
    'semicolon_after_instruction' => false,
    // additional rules
    'array_syntax' => ['syntax' => 'short'],
    'dir_constant' => true,
    'echo_tag_syntax' => ['format' => 'short'],
    'modernize_types_casting' => true,
    'no_alias_functions' => true,
    'ordered_imports' => true,
    'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
    'phpdoc_order' => true,
    'psr_autoloading' => true,
    'strict_param' => true,
];

$config = new PhpCsFixer\Config();

return $config
    ->setRules($rules)
    ->setRiskyAllowed(true)
    ->setFinder($finder);
