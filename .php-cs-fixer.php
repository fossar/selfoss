<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('client')
    ->exclude('utils')
    ->in(__DIR__)
    ->name('*.phtml');

$rules = [
    '@Symfony' => true,
    // why would anyone put braces on different line
    'braces_position' => [
        'functions_opening_brace' => 'same_line',
        'classes_opening_brace' => 'same_line',
    ],
    'function_declaration' => [
        'closure_function_spacing' => 'none',
        'closure_fn_spacing' => 'none',
    ],

    // overwrite some Symfony rules
    'concat_space' => ['spacing' => 'one'],
    'global_namespace_import' => false,
    'blank_line_between_import_groups' => false,
    // We need the `mixed`s for PHPStan.
    'no_superfluous_phpdoc_tags' => [
        'allow_mixed' => true,
    ],
    'phpdoc_align' => false,
    'phpdoc_no_empty_return' => false,
    'phpdoc_summary' => false,
    'trailing_comma_in_multiline' => false,
    'yoda_style' => false,
    'semicolon_after_instruction' => false,

    // additional rules
    'dir_constant' => true,
    'echo_tag_syntax' => ['format' => 'short'],
    'modernize_types_casting' => true,
    'no_alias_functions' => true,
    'ordered_imports' => true,
    'phpdoc_add_missing_param_annotation' => true,
    'phpdoc_order' => true,
    // 'phpdoc_to_param_type' => true,
    // 'phpdoc_to_return_type' => true,
    'psr_autoloading' => true,
    'strict_param' => true,
    'declare_strict_types' => true,
    'simple_to_complex_string_variable' => true,
];

$config = new PhpCsFixer\Config();

return $config
    ->setRules($rules)
    ->setRiskyAllowed(true)
    ->setFinder($finder);
