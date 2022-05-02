<?php

//
// see https://mlocati.github.io/php-cs-fixer-configurator/#version:3.8   for MORE OPTIONS
//

declare(strict_types=1);

$header = <<<'EOF'
    This file is part of Homebase 2 PHP Framework - https://github.com/homebase/hb-core
    EOF;

$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(true)
    ->ignoreVCSIgnored(true)
    ->exclude('tests')
#    ->in(__DIR__.'/src')
;

//    ->append([
//        __DIR__.'/dev-tools/doc.php',
        // __DIR__.'/php-cs-fixer', disabled, as we want to be able to run bootstrap file even on lower PHP version, to show nice message
//    ])

$config = new PhpCsFixer\Config();

$config
    ->setRiskyAllowed(true)
    ->setRules(array_merge($config->getRules(), [
        '@PHPUnit75Migration:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHP81Migration' => true,
        '@PHP80Migration:risky' => true,
        '@PSR12' => true,
        'array_push' => true,
        //      'array_syntax' => ['short'],
        'heredoc_indentation' => false,
        'header_comment' => ['header' => $header],
        'modernize_strpos' => true, // needs PHP 8+ or polyfill
        'use_arrow_functions' => true,
        'heredoc_indentation' => true,
        'list_syntax' => ['syntax' => 'long'],
        'visibility_required' => ['elements' => ['property']],
        'phpdoc_summary' => false, // no useless dots
        'explicit_string_variable' => false,  // "$a xxx $b" is OK !!
        'echo_tag_syntax' => ['format' => 'short'],         // "<?= ... " - good and short
        'braces' => [
            'position_after_functions_and_oop_constructs' => 'same',   // "function () { " same line
            'allow_single_line_closure' => true,
	],
	'strict_comparison' => false, // can break old code
	'yoda_style' => false, // ugly sometimes
    ]))->setFinder($finder)

;

return $config;
