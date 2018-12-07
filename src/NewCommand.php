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

class NewCommand extends Command {

    /**
     * @access protected
     * @var    string $download_url  The URL to download Zip files
     */
    protected $download_url = 'https://viperframe.work/download/';

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
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces install even if the directory already exists');
    } // End Method

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
        if ( ! class_exists('ZipArchive'))
        {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        } // End If

        $directory = ($input->getArgument('name')) ? getcwd().'/'.$input->getArgument('name') : getcwd();

        if ( ! $input->getOption('force'))
        {
            $this->verifyApplicationDoesntExist($directory);
        } // End If

        $output->writeln('<info>Crafting application...</info>');

        $version = $this->getVersion($input);

        $this->download($zipFile = $this->makeFilename(), $version)
             ->extract($zipFile, $directory)
             ->prepareWritableDirectories($directory, $output)
             ->cleanUp($zipFile);

        $composer = $this->findComposer();

        $commands = [
            $composer.' install --no-scripts',
            $composer.' run-script post-root-package-install',
            $composer.' run-script post-create-project-cmd',
            $composer.' run-script post-autoload-dump',
        ];

        if ($input->getOption('no-ansi'))
        {
            $commands = array_map(
                function ($value)
                {
                    return $value.' --no-ansi';
                }, $commands);
        } // End If

        $process = new Process(implode(' && ', $commands), $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty'))
        {
            $process->setTty(true);
        } // End If

        $process->run(
            function ($type, $line) use ($output)
            {
                $output->write($line);
            });

        $output->writeln('<comment>Application ready! Build something amazing.</comment>');
    } // End Method

    /**
     * Verify that the application does not already exist.
     *
     * @access protected
     * @param  string $directory  Destination directory
     * @return void
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd())
        {
            throw new RuntimeException('Application already exists!');
        } // End If
    } // End Method

    /**
     * Generate a random temporary filename.
     *
     * @access protected
     * @return string
     */
    protected function makeFilename()
    {
        return getcwd().'/viper_'.md5(time().uniqid()).'.zip';
    } // End Method

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
        switch ($version)
        {
            case ('develop'):
                $filename = 'framework-develop.zip';
                break;
            case ('master'): default:
                $filename = 'framework-master.zip';
                break;
        } // End Switch

        $response = (new Client)->get("{$this->download_url}{$filename}");

        file_put_contents($zipFile, $response->getBody());

        return $this;
    } // End Method

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

        $archive->open($zipFile);

        $archive->extractTo($directory);

        $archive->close();

        return $this;
    } // End Method

    /**
     * Clean-up the Zip file.
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
    } // End Method

    /**
     * Make sure the storage and bootstrap cache directories are writable.
     *
     * @access protected
     * @param  string                                            $appDirectory  The application directory
     * @param  \Symfony\Component\Console\Output\OutputInterface $output        Console output
     * @return NewCommand
     */
    protected function prepareWritableDirectories($appDirectory, OutputInterface $output)
    {
        $filesystem = new Filesystem;

        try
        {
            $filesystem->chmod($appDirectory.DIRECTORY_SEPARATOR."storage/cache", 0755, 0000, true);
            $filesystem->chmod($appDirectory.DIRECTORY_SEPARATOR."storage/logs", 0755, 0000, true);
        }
        catch (IOExceptionInterface $e)
        {
            $output->writeln('<comment>You should verify that the "storage/cache" and "storage/logs" directories are writable.</comment>');
        } // End Try

        return $this;
    } // End Method

    /**
     * Get the version that should be downloaded.
     *
     * @access protected
     * @param  \Symfony\Component\Console\Input\InputInterface $input  Console input
     * @return string
     */
    protected function getVersion(InputInterface $input)
    {
        if ($input->getOption('dev'))
        {
            return 'develop';
        } // End If

        return 'master';
    } // End Method

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd().'/composer.phar'))
        {
            return '"'.PHP_BINARY.'" composer.phar';
        } // End If

        return 'composer';
    } // End Method

} // End Class
