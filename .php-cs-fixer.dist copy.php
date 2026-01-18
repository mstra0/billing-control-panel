<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->files(["control_panel.php"]);

// ERROR WAS HERE: You need ( ) around the "new" statement
return new PhpCsFixer\Config()
    ->setRules([
        "no_trailing_comma_in_multiline_array" => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(false)
    ->setPhpCsFixerVersion(3)
    ->setUsingCache(false);
