#!/usr/bin/env php
<?php

if (file_exists(__DIR__.'/../../autoload.php'))
{
    require __DIR__.'/../../autoload.php';
}
else
{
    require __DIR__.'/vendor/autoload.php';
} // End If

$app = new Symfony\Component\Console\Application('Viper Installer', '2.0.0');

$app->add(new Viper\Installer\Console\NewCommand);

$app->run();
