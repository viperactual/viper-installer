<?php

namespace Viper\Installer\Console;

use ZipArchive;
use RuntimeException;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Viper Installer New Command Class.
 *
 * @package      ViperInstaller
 * @category     Base
 * @name         NewCommand
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

class NewCommand extends Command
{
    const NAME = 'Viper Installer';
    const VERSION = '2.2.9';

    /**
     * @access private
     * @var    string|null $private_token  Viper Lab personal access token
     */
    private $private_token = null;

    /**
     * Configure the command options.
     *
     * @access protected
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new Viper application.')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces install even if the directory already exists')
            ->addOption('token', 't', InputOption::VALUE_REQUIRED, 'Add your private token for Viper Lab');
    }

    /**
     * Execute the command.
     *
     * @access protected
     * @param  \Symfony\Component\Console\Input\InputInterface   $input   User input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output  Output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! extension_loaded('zip')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $name = $input->getArgument('name');

        $directory = $name && $name !== '.' ? getcwd() . '/' . $name : getcwd();

        if (! $input->getOption('force')) {
            $this->verifyApplicationDoesntExist($directory);
        }

        $this->private_token = ($input->getOption('token')) ?? null;

        $output->writeln('<info>Installing application, please wait...</info>');

        $this
            ->download($zipFile = $this->makeFilename(), $this->getVersion($input))
            ->extract($zipFile, $directory)
            ->prepareStorageDirectories($directory, $output)
            ->prepareWritableDirectories($directory, $output)
            ->prepareR2d2($directory, $output)
            ->cleanUp($zipFile);

        $composer = $this->findComposer();

        $commands = [
            $composer . ' install --no-scripts',
            $composer . ' run-script post-root-package-install',
            $composer . ' run-script post-create-project-cmd',
            $composer . ' run-script post-autoload-dump',
        ];

        if ($input->getOption('no-ansi')) {
            $commands = array_map(function ($value) {
                return $value . ' --no-ansi';
            }, $commands);
        }

        if ($input->getOption('quiet')) {
            $commands = array_map(function ($value) {
                return $value . ' --quiet';
            }, $commands);
        }

        $process = new Process(implode(' && ', $commands), $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        $output->writeln('<comment>Application ready!</comment>');
    }

    /**
     * Verify that the application does not already exist.
     *
     * @access protected
     * @param  string $directory  Destination directory
     * @return void
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Generate a random temporary filename.
     *
     * @access protected
     * @return string
     */
    protected function makeFilename()
    {
        return getcwd() . '/viper_' . md5(time() . uniqid()) . '.zip';
    }

    /**
     * Download the temporary Zip to the given file.
     *
     * @access protected
     * @param  string $zipFile  Zip file
     * @param  string $version  Version
     * @return NewCommand
     */
    protected function download($zipFile, $version = 'master')
    {
        $url = 'https://viper-lab.com/api/v4/projects/viper%2Fapp/repository/archive.zip?sha=' . $version;

        $options = [];

        if ($this->private_token != null) {
            $options = [
                'headers' => [
                    'PRIVATE-TOKEN' => $this->private_token,
                ],
            ];
        }

        $response = (new Client)->get($url, $options);

        file_put_contents($zipFile, $response->getBody());

        return $this;
    }

    /**
     * Extract the Zip file into the given directory.
     *
     * @access protected
     * @param  string $zipFile    Zip file
     * @param  string $directory  Destination directory
     * @return NewCommand
     */
    protected function extract($zipFile, $directory)
    {
        $archive = new ZipArchive;

        $response = $archive->open($zipFile, ZipArchive::CHECKCONS);

        if ($response === ZipArchive::ER_NOZIP) {
            throw new RuntimeException('The zip file could not download. Verify that you are able to access: Viper Lab');
        }

        $archive->extractTo($directory);

        $index = $archive->statIndex(0);
        $parent = $index['name'];

        for ($i = 1; $i < $archive->numFiles; $i++) {
            $stat = $archive->statIndex($i);

            $file = str_replace($parent, '', $stat['name']);

            $origin = $directory . DIRECTORY_SEPARATOR . $parent . $file;
            $target = $directory . DIRECTORY_SEPARATOR . $file;

            $this->copyr($origin, $target);
        }

        $archive->close();

        $filesystem = new Filesystem;

        $filesystem->remove($directory . DIRECTORY_SEPARATOR . $parent);

        return $this;
    }

    /**
     * Copy Recursively.
     *
     * @access protected
     * @param  mixed $origin  Path to the origin
     * @param  mixed $target  Path to the target
     * @return bool
     */
    protected function copyr($origin, $target)
    {
        if (is_link($origin)) {
            return @symlink(readlink($origin), $target);
        }

        if (is_file($origin)) {
            return @copy($origin, $target);
        }

        if (! is_dir($target)) {
            @mkdir($target);
        }

        $dir = dir($origin);

        while (false !== $entry = $dir->read()) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $this->copyr($origin . '/' . $entry, $target . '/' . $entry);
        }

        $dir->close();

        return true;
    }

    /**
     * Clean Up the Zip file.
     *
     * @access protected
     * @param  string $zipFile  Zip file
     * @return NewCommand
     */
    protected function cleanUp($zipFile)
    {
        @chmod($zipFile, 0777);

        @unlink($zipFile);

        return $this;
    }

    /**
     * Prepare R2d2 Files.
     *
     * @access protected
     * @param  string          $appDirectory  The application directory
     * @param  OutputInterface $output        Console output
     * @return NewCommand
     */
    protected function prepareR2d2($appDirectory, OutputInterface $output)
    {
        $filesystem = new Filesystem;

        try {
            $filesystem->chmod($appDirectory . DIRECTORY_SEPARATOR . 'r2d2', 0775, 0000, true);
            $filesystem->chmod($appDirectory . DIRECTORY_SEPARATOR . 'r2d2d', 0775, 0000, true);
        } catch (IOExceptionInterface $e) {
            $output->writeln('<comment>R2d2 Powered on.</comment>');
        }

        return $this;
    }

    /**
     * Prepare Storage Directories.
     *
     * @access protected
     * @param  string          $appDirectory  The application directory
     * @param  OutputInterface $output        Console output
     * @return NewCommand
     */
    protected function prepareStorageDirectories($appDirectory, OutputInterface $output)
    {
        $filesystem = new Filesystem;

        try {
            $filesystem->mkdir($appDirectory . DIRECTORY_SEPARATOR . 'app/storage/logs', 0755);
            $filesystem->mkdir($appDirectory . DIRECTORY_SEPARATOR . 'app/storage/cache', 0755);
        } catch (IOExceptionInterface $e) {
            $output->writeln('<comment>You should verify that the "storage/cache", "storage/logs" directories have been created.</comment>');
        }

        return $this;
    }

    /**
     * Make sure the storage and bootstrap cache directories are writable.
     *
     * @access protected
     * @param  string          $appDirectory  The application directory
     * @param  OutputInterface $output        Console output
     * @return NewCommand
     */
    protected function prepareWritableDirectories($appDirectory, OutputInterface $output)
    {
        $filesystem = new Filesystem;

        try {
            $filesystem->chmod($appDirectory . DIRECTORY_SEPARATOR . 'app/storage/logs', 0755, 0000, true);
            $filesystem->chmod($appDirectory . DIRECTORY_SEPARATOR . 'app/storage/cache', 0755, 0000, true);
            $filesystem->chmod($appDirectory . DIRECTORY_SEPARATOR . 'app/bootstrap/cache', 0755, 0000, true);
        } catch (IOExceptionInterface $e) {
            $output->writeln('<comment>You should verify that the "storage/cache", "storage/logs" and "bootstrap/cache" directories are writable.</comment>');
        }

        return $this;
    }

    /**
     * Get the version that should be downloaded.
     *
     * @access protected
     * @param  \Symfony\Component\Console\Input\InputInterface $input  Console input
     * @return string
     */
    protected function getVersion(InputInterface $input)
    {
        if ($input->getOption('dev')) {
            return 'develop';
        }

        return 'master';
    }

    /**
     * Get the composer command for the environment.
     *
     * @access protected
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd() . '/composer.phar')) {
            return '"' . PHP_BINARY . '" composer.phar';
        }

        return 'composer';
    }
}
