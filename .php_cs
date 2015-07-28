<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__)
;

return Symfony\CS\Config\Config::create()
    ->fixers(array('-concat_without_spaces', 'concat_with_spaces'))
    ->finder($finder)
;
