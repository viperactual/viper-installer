<?php

namespace Viper\Installer\Console\Tests;

use PHPUnit\Framework\TestCase;
use Viper\Installer\Console\NewCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Viper Installer New Command Test Class.
 *
 * @package      ViperInstaller
 * @category     Tests
 * @name         NewCommandTest
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

class NewCommandTest extends TestCase
{
    /**
     * Test It Can Scanffold A New Viper App.
     *
     * @access public
     * @return void
     */
    public function test_it_can_scaffold_a_new_viper_app()
    {
        $scaffoldDirectoryName = 'tests-output/my-app';
        $scaffoldDirectory = __DIR__ . '/../' . $scaffoldDirectoryName;

        if (file_exists($scaffoldDirectory)) {
            (new Filesystem)->remove($scaffoldDirectory);
        }

        $app = new Application('Viper Installer');
        $app->add(new NewCommand);

        $tester = new CommandTester($app->find('new'));

        $statusCode = $tester->execute(['name' => $scaffoldDirectoryName, '--auth' => null]);

        $this->assertEquals($statusCode, 0);
        $this->assertDirectoryExists($scaffoldDirectory . '/vendor');
        $this->assertFileExists($scaffoldDirectory . '/.env');
        //$this->assertFileExists($scaffoldDirectory . '/resources/views/auth/login.blade.php');
    }
}
