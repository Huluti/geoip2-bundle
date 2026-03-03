<?php
declare(strict_types=1);

$rules = [
    '@Symfony' => true,
    'array_syntax' => [
        'syntax' => 'short',
    ],
    'no_superfluous_phpdoc_tags' => false,
    'blank_line_after_opening_tag' => false,
    'phpdoc_no_empty_return' => false,
    'yoda_style' => false,
    'ordered_imports' => [
        'sort_algorithm' => 'alpha',
    ],
];

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->notPath('bootstrap.php')
;

return (new PhpCsFixer\Config())
    ->setRules($rules)
    ->setFinder($finder)
    ->setUsingCache(true)
;
