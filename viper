#!/usr/bin/env php
<?php

define('APP_BASE', __DIR__);

if (file_exists(APP_BASE . '/../../autoload.php')) {
    require APP_BASE . '/../../autoload.php';
} else {
    require APP_BASE . '/vendor/autoload.php';
}

use Viper\Installer\Console\NewCommand;
use Symfony\Component\Console\Application;

$app = new Application(NewCommand::NAME, NewCommand::VERSION);
$app->add(new NewCommand);

$app->run();
