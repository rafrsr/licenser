<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__)
    ->exclude(
        [
            'vendor',
        ]
    );

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->fixers(['ordered_use', 'php_unit_construct', 'phpdoc_order', 'short_array_syntax'])
    ->finder($finder);
