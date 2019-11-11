#!/usr/bin/env php
<?php

define('APP_BASE', __DIR__);

if (file_exists(__DIR__ . '/../../autoload.php')) {
    require __DIR__ . '/../../autoload.php';
} else {
    require __DIR__ . '/vendor/autoload.php';
}

$app = new Symfony\Component\Console\Application('Viper Installer', '2.1.0');
$app->add(new Viper\Installer\Console\NewCommand);

$app->run();
