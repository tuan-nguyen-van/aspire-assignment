<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('*.php')
    ->ignoreDotFiles(true);

$config = new PhpCsFixer\Config();
$config->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'no_trailing_whitespace_in_comment' => false,
        'single_space_after_construct' => true,
        'no_spaces_after_function_name' => false,
        'return_type_declaration' => true,
        'concat_space' => false,
        'phpdoc_to_comment' => false,
        'phpdoc_var_without_name' => false,
        'no_superfluous_phpdoc_tags' => false, // this will stop removing already defined @tags that causes phpstorm to be unhappy
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_alias_functions' => true,
        'modernize_types_casting' => true,
        'is_null' => true,
        'function_to_constant' => true,
        'ternary_to_null_coalescing' => true,
        'yoda_style' => false,
        'not_operator_with_successor_space' => false, // find readability better without
        'echo_tag_syntax' => ['format' => 'short', 'shorten_simple_statements_only' => true],
    ])
    ->setFinder($finder);

return $config;
