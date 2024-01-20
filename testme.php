<?php

declare(strict_types=1);

use Ccmiller2018\ImageEditor\ImageEditor;

require_once('vendor/autoload.php');

$editor = new ImageEditor();

$matrix = [
    [0,-100,0],
    [-100,500,-100],
    [0,-100,0]
];

$editor->loadImage('images/sources/baboon.png')
    ->customConvolution($matrix)
    ->saveTo('images/outputs/baboon-custom-convolution.png');