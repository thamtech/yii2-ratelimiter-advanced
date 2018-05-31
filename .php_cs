<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__ . '/src');

return Symfony\CS\Config\Config::create()
    ->fixers([
        '-whitespacy_lines',
        '-phpdoc_separation',
        '-blankline_after_open_tag',
        '-phpdoc_inline_tag',
        '-phpdoc_params',
        '-empty_return',
        '-concat_without_spaces',
      ])
    ->finder($finder);
