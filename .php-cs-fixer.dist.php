<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name("control_panel.php");

$config = new PhpCsFixer\Config();
$config->setRules([
    "no_trailing_comma_in_singleline" => true,
    "method_argument_space" => [
        "on_multiline" => "ignore",
        "keep_multiple_spaces_after_comma" => false,
        "after_heredoc" => false,
    ],
]);
$config->setFinder($finder);
$config->setUsingCache(false);

return $config;
